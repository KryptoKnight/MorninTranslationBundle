<?php

namespace Mornin\Bundle\TranslationBundle\Storage;

/**
 * Doctrine MongoDB storage class.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class DoctrineMongoDBStorage extends AbstractDoctrineStorage
{
    /**
     * {@inheritdoc}
     */
    public function getLatestUpdatedAt()
    {
        return $this->getTransUnitRepository()->getLatestTranslationUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTransUnitByDomains()
    {
        return $this->getTransUnitRepository()->countByDomains();
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTranslationByLocales($domain)
    {
        return $this->getTransUnitRepository()->countTranslationsByLocales($domain);
    }
}
