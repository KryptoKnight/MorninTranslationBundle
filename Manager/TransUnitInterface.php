<?php

namespace Mornin\Bundle\TranslationBundle\Manager;

/**
 * TransUnit manager interface.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
interface TransUnitInterface
{
    public function getTranslations();

    public function hasTranslation($locale);

    public function getTranslation($locale);

    public function setKey($key);

    public function setDomain($domain);
}
