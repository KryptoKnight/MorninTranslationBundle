<?php

namespace Mornin\Bundle\TranslationBundle\Tests\Command;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DropSchemaDoctrineCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Mornin\Bundle\TranslationBundle\Command\ImportTranslationsCommand;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Test the translations import command, with option and arguments
 *
 * @covers Mornin\Bundle\TranslationBundle\Command\ImportTranslationsCommand
 */
class ImportTranslationsCommandTest extends WebTestCase
{
    /**
     * @var Application
     */
    private static $application;

    /**
     *
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        static::$application = new Application(static::$kernel);

        static::addDoctrineCommands();

        static::rebuildDatabase();
    }

    /**
     *
     */
    private static function addDoctrineCommands()
    {
        static::$application->add(new DropSchemaDoctrineCommand());
        static::$application->add(new CreateSchemaDoctrineCommand());
    }

    /**
     *
     */
    private static function rebuildDatabase()
    {
        static::$kernel->getContainer()->get('doctrine.dbal.default_connection');

        static::runCommand("doctrine:schema:drop", array('--force' => true));
        static::runCommand("doctrine:schema:create");
    }

    private static function runCommand($commandName, $options = array())
    {
        $options["-e"] = self::$kernel->getEnvironment();

        $options['command'] = $commandName;

        $input = new ArrayInput($options);
        $output = new NullOutput();

        static::$application->setAutoExit(false);
        self::$application->run($input, $output);
    }

    /**
     * Test execute with all the options
     *
     * @group command
     */
    public function testExecute()
    {
        static::$application->add(new ImportTranslationsCommand());

        $command = static::$application->find("Mornin:translations:import");
        $command->setContainer(static::$kernel->getContainer());

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command'       => $command->getName(),
                'bundle'        => 'MorninTranslationBundle',
                '--cache-clear' => true,
                '--force'       => true,
                '--locales'     => array('en', 'fr'),
            )
        );

        $resultLines = explode("\n", $commandTester->getDisplay());

        $this->assertEquals('# MorninTranslationBundle:', $resultLines[0]);
        $this->assertRegExp('/Using dir (.)+(Mornin|Mornin\/Bundle\/)TranslationBundle\/Resources\/translations to lookup translation files/', $resultLines[1]);
        $this->assertRegExp('/translations\/MorninTranslationBundle\.((fr)|(en))\.yml" \.\.\. 28 translations/', $resultLines[2]);
        $this->assertRegExp('/translations\/MorninTranslationBundle\.((fr)|(en))\.yml" \.\.\. 28 translations/', $resultLines[3]);
        $this->assertEquals('Removing translations cache files ...', $resultLines[4]);
    }
}
