<?php

namespace Mornin\Bundle\TranslationBundle\Form\Handler;

use Mornin\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Mornin\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Mornin\Bundle\TranslationBundle\Manager\FileInterface;
use Mornin\Bundle\TranslationBundle\Manager\FileManagerInterface;
use Mornin\Bundle\TranslationBundle\Storage\StorageInterface;
use Mornin\Bundle\TranslationBundle\Propel\TransUnit as PropelTransUnit;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class TransUnitFormHandler implements FormHandlerInterface
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;

    /**
     * @var FileManagerInterface
     */
    protected $fileManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var LocaleManagerInterface
     */
    protected $localeManager;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param FileManagerInterface      $fileManager
     * @param StorageInterface          $storage
     * @param LocaleManagerInterface    $localeManager
     * @param string                    $rootDir
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, FileManagerInterface $fileManager, StorageInterface $storage, LocaleManagerInterface $localeManager, $rootDir)
    {
        $this->transUnitManager = $transUnitManager;
        $this->fileManager = $fileManager;
        $this->storage = $storage;
        $this->localeManager = $localeManager;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function createFormData()
    {
        return $this->transUnitManager->newInstance($this->localeManager->getLocales());
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions($default)
    {
        return array(
            'domains'           => array($this->storage->getTransUnitDomains(), $default['domain']),
            'data_class'        => $this->storage->getModelClass('trans_unit'),
            'translation_class' => $this->storage->getModelClass('translation')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process(FormInterface $form, Request $request)
    {
        $valid = false;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $transUnit = $form->getData();

                $translations = $transUnit->filterNotBlankTranslations(); // only keep translations with a content

                // link new translations to a file to be able to export them.
                foreach ($translations as $translation) {
                    if (!$translation->getFile()) {
                        $file = $this->fileManager->getFor(
                            sprintf('%s.%s.yml', $transUnit->getDomain(), $translation->getLocale()),
                            $this->rootDir.'/Resources/translations'
                        );

                        if ($file instanceof FileInterface) {
                            $translation->setFile($file);
                        }
                    }
                }

                if ($transUnit instanceof PropelTransUnit) {
                    // The setTranslations() method only accepts PropelCollections
                    $translations = new \PropelObjectCollection($translations);
                }

                $transUnit->setTranslations($translations);

                $this->storage->persist($transUnit);
                $this->storage->flush();

                $valid = true;
            }
        }

        return $valid;
    }
}