<?php
/**
 * Created by PhpStorm.
 * User: darren
 * Date: 15/09/17
 * Time: 10:47
 */

namespace Mornin\Bundle\TranslationBundle\Command;


use Doctrine\ORM\EntityManager;
use Mornin\Bundle\TranslationBundle\Entity\TransUnit;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\MessageCatalogue;

class MissingTranslationsCommand extends ContainerAwareCommand
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;


    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;


    public function configure()
    {
        $this
            ->setName('Mornin:translations:missing')
            ->setDefinition(array(
            new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
            new InputOption('email', null, InputOption::VALUE_OPTIONAL, 'Alerts missing translations via email to recipients provided within this option field'),
            new InputOption('template', "tpl", InputOption::VALUE_OPTIONAL, "The template you want to use to send emails")
            ))
            ->setDescription('Listing any Symfony translations that are missing in the Mornin\' translations database')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command helps finding missing translation
messages and comparing them with the stored ones by inspecting the
templates and translation files of a all possible bundles. This is a fork of
translation:debug. Please note that the command will ignore any variable stored translations, more information about this is on https://symfony.com/doc/current/translation/debug.html


You can find all missing translations with this command:

  <info>php %command.full_name% de </info>

You can also send an email for missing translations by providing the email you want to send to, for multiple emails separate them with a comma, example: darren@darren.com,darren2@darren.com

  <info>php %command.full_name% de --email darren@darren.com</info>

The template you want to use for sending the email, if not found it will use the default template

  <info>php %command.full_name% de --email darren@darren.com --template jira</info>
EOF
            );

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->findMissingTranslations();
    }


    public function findMissingTranslations()
    {
        try {
            $locale = $this->input->getArgument("locale");

            /**
             * @var Kernel $kernel
             */
            $kernel = $this->getContainer()->get("kernel");
            /**
             * @var EntityManager $em
             */
            $em = $this->getContainer()->get("doctrine")->getManager();

            $transPaths = [];
            foreach ($kernel->getBundles() as $bundle) {
                $transPaths[] = $bundle->getPath() . '/Resources/';
                $transPaths[] = sprintf('%s/Resources/%s/', $kernel->getRootDir(), $bundle->getName());
            }

            $extractedCatalogue = new MessageCatalogue($locale);
            foreach ($transPaths as $path) {
                $path = $path . 'views';
                try {
                    if (is_dir($path)) {
                        $this->getContainer()->get('translation.extractor')->extract($path, $extractedCatalogue);
                    }
                }catch(\Twig_Error_Syntax $e){
                    $this->output->writeln(("<error>The file: {$e->getFile()} has a syntax error, therefor the search on this file has been skipped.</error>"));
                    continue;
                }
            }


            $missing = [];

            foreach ($extractedCatalogue->all() as $domain => $translation) {

                foreach ($translation as $key => $null) {


                    switch ($domain) {
                        case "_undefined":

                            $found = $em
                                ->getRepository("MorninTranslationBundle:TransUnit")
                                ->findOneBy([
                                    "key" => $key
                                ]);

                            if (!$found instanceOf TransUnit) {
                                $missing[] = [
                                    $domain, $key
                                ];
                            }


                            break;
                        default:

                            $found = $em
                                ->getRepository("MorninTranslationBundle:TransUnit")
                                ->findOneBy([
                                    "key" => $key,
                                    "domain" => $domain
                                ]);

                            if (!$found instanceOf TransUnit) {
                                $missing[] = [
                                    $domain, $key
                                ];
                            }

                            break;
                    }

                }
            }

            $table = new Table($this->output);
            $table->setHeaders(["Domain", "Key"]);
            $table->setRows($missing);
            $table->render();


            if (($emails = $this->input->getOption("email")) !== null) {
                $emails = explode(",", $emails);


                if(!$this->getContainer()->hasParameter("mailer_transport")) {
                    throw new \Exception("mailer_transport parameter not set, therefor email was not sent");
                }

                switch($this->input->getOption("template"))
                {
                    case "jira":
                        $template = 'MorninTranslationBundle:Email:missing_translations_email_jira.html.twig';
                        break;
                    default:
                        $template = 'MorninTranslationBundle:Email:missing_translations_email.html.twig';
                        break;
                }

                $message = (new \Swift_Message('List of missing translations'))
                    ->setFrom('no-reply@mornin-translation-bundle.com')
                    ->setTo($emails)
                    ->setBody(
                        $this->getContainer()->get("twig")->render(
                            $template,
                            [
                                "missing" => $missing
                            ]
                        ),
                        'text/html'
                    )
                ;

                if(!$this->getContainer()->has("mailer")){
                    throw new \Exception("I am afraid the default mailer is not found");
                }

                $mailer = $this->getContainer()->get("mailer");
                $mailer->send($message);

                $recipients = implode("|", $emails);

                $this->output->writeln("<info>The email has been sent to: {$recipients}</info>");
            }
        }catch(\Exception $e){
            $this->output->writeln(("<error>{$e->getMessage()}</error>"));
        }
    }

}