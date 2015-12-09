<?php

namespace Mornin\Bundle\TranslationBundle\Manager;

/**
 * Translation interface.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
interface TranslationInterface
{
    /**
     * @return string
     */
    public function getLocale();

    /**
     * @return string
     */
    public function getContent();
}
