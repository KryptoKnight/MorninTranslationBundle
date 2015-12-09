<?php

namespace Mornin\Bundle\TranslationBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Mornin\Bundle\TranslationBundle\Model\Translation as TranslationModel;
use Mornin\Bundle\TranslationBundle\Manager\TranslationInterface;

/**
 * @UniqueEntity(fields={"transUnit", "locale"})
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class Translation extends TranslationModel implements TranslationInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Mornin\Bundle\TranslationBundle\Entity\TransUnit
     */
    protected $transUnit;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transUnit
     *
     * @param Mornin\Bundle\TranslationBundle\Entity\TransUnit $transUnit
     */
    public function setTransUnit(\Mornin\Bundle\TranslationBundle\Model\TransUnit $transUnit)
    {
        $this->transUnit = $transUnit;
    }

    /**
     * Get transUnit
     *
     * @return Mornin\Bundle\TranslationBundle\Entity\TransUnit
     */
    public function getTransUnit()
    {
        return $this->transUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $now             = new \DateTime("now");
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }
}
