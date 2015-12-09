<?php

namespace Mornin\Bundle\TranslationBundle\Propel;

use Mornin\Bundle\TranslationBundle\Model\Translation;
use Mornin\Bundle\TranslationBundle\Propel\om\BaseTransUnit;
use Mornin\Bundle\TranslationBundle\Manager\TransUnitInterface;
use Mornin\Bundle\TranslationBundle\Manager\TranslationInterface;

class TransUnit extends BaseTransUnit implements TransUnitInterface
{
    protected $translations = array();

    /**
     * Return translations with  not blank content.
     *
     * @return array
     */
    public function filterNotBlankTranslations()
    {
        return array_filter($this->getTranslations()->getArrayCopy(), function (TranslationInterface $translation) {
            $content = $translation->getContent();

            return !empty($content);
        });
    }

    /** (non-PHPdoc)
     * @see \Mornin\Bundle\TranslationBundle\Manager\TransUnitInterface::hasTranslation()
     */
    public function hasTranslation($locale)
    {
        return null !== $this->getTranslation($locale);
    }

    /**
     * Return the content of translation for the given locale.
     *
     * @param string $locale
     * @return Translation
     */
    public function getTranslation($locale)
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->getLocale() == $locale) {
                return $translation;
            }
        }

        return null;
    }
}
