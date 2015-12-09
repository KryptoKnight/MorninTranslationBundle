<?php

namespace Mornin\Bundle\TranslationBundle\Translation\Exporter;

/**
 * Export translations to a Json file.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class JsonExporter implements ExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export($file, $translations)
    {
        $bytes = file_put_contents($file, json_encode($translations, JSON_PRETTY_PRINT));

        return ($bytes !== false);
    }

    /**
     * {@inheritdoc}
     */
    public function support($format)
    {
        return ('json' == $format);
    }
}
