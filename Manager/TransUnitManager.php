<?php

namespace Mornin\Bundle\TranslationBundle\Manager;

use Mornin\Bundle\TranslationBundle\Model\Translation;
use Mornin\Bundle\TranslationBundle\Storage\StorageInterface;
use Mornin\Bundle\TranslationBundle\Storage\PropelStorage;

/**
 * Class to manage TransUnit entities or documents.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TransUnitManager implements TransUnitManagerInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var FileManagerInterface
     */
    private $fileManager;

    /**
     * @var String
     */
    private $kernelRootDir;

    /**
     * Construct.
     *
     * @param StorageInterface $storage
     * @param FileManagerInterface $fm
     * @param String $kernelRootDir
     */
    public function __construct(StorageInterface $storage, FileManagerInterface $fm, $kernelRootDir)
    {
        $this->storage = $storage;
        $this->fileManager = $fm;
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function newInstance($locales = array())
    {
        $transUnitClass = $this->storage->getModelClass('trans_unit');
        $translationClass = $this->storage->getModelClass('translation');

        $transUnit = new $transUnitClass();

        foreach ($locales as $locale) {
            $translation = new $translationClass();
            $translation->setLocale($locale);

            $transUnit->addTranslation($translation);
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function create($keyName, $domainName, $flush = false)
    {
        $transUnit = $this->newInstance();
        $transUnit->setKey($keyName);
        $transUnit->setDomain($domainName);

        $this->storage->persist($transUnit);

        if ($flush) {
            $this->storage->flush();
        }

        return $transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(TransUnitInterface $transUnit, $locale, $content, FileInterface $file = null, $flush = false)
    {
        $translation = null;

        if (!$transUnit->hasTranslation($locale)) {

            $class = $this->storage->getModelClass('translation');

            $translation = new $class();
            $translation->setLocale($locale);
            $translation->setContent($content);

            if ($file !== null) {
                $translation->setFile($file);
            }

            $transUnit->addTranslation($translation);


            $this->storage->persist($translation);

            if ($flush) {
                $this->storage->flush();
            }
        }

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslation(TransUnitInterface $transUnit, $locale, $content, $flush = false, $merge = false, \DateTime $modifiedOn = null, $file = null)
    {
        $translation = null;
        $i = 0;
        $end = $transUnit->getTranslations()->count();
        $found = false;

        while ($i < $end && !$found) {
            $found = ($transUnit->getTranslations()->get($i)->getLocale() == $locale);
            $i++;
        }

        if ($found) {

            /* @var Translation $translation */
            $translation = $transUnit->getTranslations()->get($i - 1);

            if( empty($translation->getFile()) && isset($file)){
               $translation->setFile($file);
            }

            if ($merge) {
                if ($translation->getContent() == $content) {
                    return null;
                }
                if ($translation->getCreatedAt() != $translation->getUpdatedAt() && (!$modifiedOn || $translation->getUpdatedAt() > $modifiedOn)) {
                    return null;
                }
                $newTranslation = clone $translation;
                $this->storage->remove($translation);
                $this->storage->flush();

                $newTranslation->setContent($content);
                $this->storage->persist($newTranslation);
                $translation = $newTranslation;
            }
            $translation->setContent($content);
        }

        if (null !== $translation && $this->storage instanceof PropelStorage) {
            $this->storage->persist($translation);
        }

        if ($flush) {
            $this->storage->flush();
        }

        return $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTranslationsContent(TransUnitInterface $transUnit, array $translations, $flush = false, $forceUpdateWithNewFile = false)
    {

        foreach ($translations as $locale => $content) {
            if (!empty($content)) {

                if ($transUnit->hasTranslation($locale)) {

                    $file = $this->getTranslationFile($transUnit, $locale);
                    $this->updateTranslation($transUnit, $locale, $content, false, false, null, $file);

                    if ($this->storage instanceof PropelStorage) {
                        $this->storage->persist($transUnit);
                    }
                } else {
                    //We need to get a proper file for this translation
                    $file = $this->getTranslationFile($transUnit, $locale);
                    $this->addTranslation($transUnit, $locale, $content, $file);
                }
            }
        }

        if ($flush) {
            $this->storage->flush();
        }
    }

    /**
     * Get the proper File for this TransUnit and locale
     *
     * @param TransUnitInterface $transUnit
     * @param string $locale
     *
     * @return FileInterface|null
     */
    protected function getTranslationFile(TransUnitInterface & $transUnit, $locale)
    {
        $file = null; $name = null;
        foreach ($transUnit->getTranslations() as $translationModel) {
            if (null !== $file = $translationModel->getFile()) {
                break;
            }
        }

        //if we found a file
        if ($file !== null) {
            //make sure we got the correct file for this locale and domain
            $name = sprintf('%s.%s.%s', $file->getDomain(), $locale, $file->getExtention());
            $file = $this->fileManager->getFor($name, $this->kernelRootDir.DIRECTORY_SEPARATOR.$file->getPath());
        }else{
            //create new trans file if not exist
            $file = $this->fileManager->getFor(
                sprintf('%s.%s.yml', $transUnit->getDomain(), $locale),
                $this->kernelRootDir.'/Resources/translations'
            );
        }

        return $file;
    }

    /**
     * @param TransUnitInterface $transUnit
     * @return bool
     */
    public function delete(TransUnitInterface $transUnit)
    {
        try {
            $this->storage->remove($transUnit);
            $this->storage->flush();

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param TransUnitInterface $transUnit
     * @param string             $locale
     * @return bool
     */
    public function deleteTranslation(TransUnitInterface $transUnit, $locale)
    {
        try {
            $translation = $transUnit->getTranslation($locale);

            $this->storage->remove($translation);
            $this->storage->flush();

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}