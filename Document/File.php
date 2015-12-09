<?php

namespace Mornin\Bundle\TranslationBundle\Document;

use Mornin\Bundle\TranslationBundle\Model\File as FileModel;
use Mornin\Bundle\TranslationBundle\Manager\FileInterface;

/**
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class File extends FileModel implements FileInterface
{
    /**
     * {@inheritdoc}
     */
    public function prePersist()
    {
        $now = new \DateTime("now");

        $this->createdAt = $now->format('U');
        $this->updatedAt = $now->format('U');
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        $now = new \DateTime("now");

        $this->updatedAt = $now->format('U');
    }
}
