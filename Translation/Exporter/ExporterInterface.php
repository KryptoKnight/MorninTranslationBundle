<?php

namespace Mornin\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Exporter interface.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
interface ExporterInterface
{
    /**
     * Export translations in to the given file.
     *
     * @param string $file
     * @param array $translations
     * @return boolean
     */
    public function export($file, $translations);

    /**
     * Returns true if this exporter support the given format.
     *
     * @param string $format
     * @return boolean
     */
    public function support($format);
}
