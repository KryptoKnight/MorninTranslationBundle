<?php

namespace Mornin\Bundle\TranslationBundle\Manager;

/**
 * File manager interface.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
interface FileManagerInterface
{
    /**
     * Create a new file.
     *
     * @param string $name
     * @param string $path
     * @return File
     */
    public function create($name, $path, $flush = false);

    /**
     * Returns a translation file according to the given name and path.
     *
     * @param string $name
     * @param string $path
     * @return File
     */
    public function getFor($name, $path);
}
