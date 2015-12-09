<?php

namespace Mornin\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Mornin\Bundle\TranslationBundle\Model\TransUnit as TransUnitModel;
use Mornin\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * @UniqueEntity(fields={"key", "domain"})
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class TransUnit extends TransUnitModel implements TransUnitInterface
{
    /**
     * Add translations
     *
     * @param Mornin\Bundle\TranslationBundle\Entity\Translation $translations
     */
    public function addTranslation(\Mornin\Bundle\TranslationBundle\Model\Translation $translation)
    {
        $translation->setTransUnit($this);

        $this->translations[] = $translation;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime("now");
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}
