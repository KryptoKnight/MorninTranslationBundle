<?php

namespace Mornin\Bundle\TranslationBundle\Util\DataGrid;

use Mornin\Bundle\TranslationBundle\Manager\LocaleManagerInterface;
use Mornin\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
use Mornin\Bundle\TranslationBundle\Manager\TransUnitManagerInterface;
use Mornin\Bundle\TranslationBundle\Model\TransUnit;
use Mornin\Bundle\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class DataGridRequestHandler
{
    /**
     * @var TransUnitManagerInterface
     */
    protected $transUnitManager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var LocaleManagerInterface
     */
    protected $localeManager;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var bool
     */
    protected $createMissing;

    /**
     * @param TransUnitManagerInterface $transUnitManager
     * @param StorageInterface          $storage
     * @param LocaleManagerInterface    $localeManager
     */
    public function __construct(TransUnitManagerInterface $transUnitManager, StorageInterface $storage, LocaleManagerInterface $localeManager)
    {
        $this->transUnitManager = $transUnitManager;
        $this->storage = $storage;
        $this->localeManager = $localeManager;
        $this->createMissing = false;
    }

    /**
     * @param Profiler $profiler
     */
    public function setProfiler(Profiler $profiler = null)
    {
        $this->profiler = $profiler;
    }

    /**
     * @param bool $createMissing
     */
    public function setCreateMissing($createMissing)
    {
        $this->createMissing = (bool) $createMissing;
    }

    /**
     * Returns an array with the trans unit for the current page and the total of trans units
     *
     * @param Request $request
     * @return array
     */
    public function getPage(Request $request)
    {
        $parameters = $this->fixParameters($request->query->all());

        $transUnits = $this->storage->getTransUnitList(
            $this->localeManager->getLocales(),
            $request->query->get('rows', 20),
            $request->query->get('page', 1),
            $parameters
        );

        $count = $this->storage->countTransUnits($this->localeManager->getLocales(), $parameters);

        return array($transUnits, $count);
    }

    /**
     * Returns an array with the trans unit for the current profile page.
     *
     * @param Request $request
     * @param string  $token
     * @return array
     */
    public function getPageByToken(Request $request, $token)
    {
        list($transUnits, $count) = $this->getByToken($token);

        $parameters = $this->fixParameters($request->query->all());

        return $this->filterTokenTranslations($transUnits, $count, $parameters);
    }

    /**
     * Get a profile's translation messages based on a previous Profiler token.
     *
     * @param string $token by which a Profile can be found in the Profiler
     *
     * @return array with collection of TransUnits and it's count
     */
    public function getByToken($token)
    {
        if (null === $this->profiler) {
            throw new \RuntimeException('Invalid profiler instance.');
        }

        $profile = $this->profiler->loadProfile($token);

        // In case no results were found
        if (!$profile instanceof Profile) {
            return array(array(), 0);
        }

        try {
            /** @var TranslationDataCollector $collector */
            $collector = $profile->getCollector('translation');
            $messages = $collector->getMessages();

            $transUnits = array();
            foreach ($messages as $message) {
                $transUnit = $this->storage->getTransUnitByKeyAndDomain($message['id'], $message['domain']);

                if ($transUnit instanceof TransUnit) {
                    $transUnits[] = $transUnit;
                } elseif (true === $this->createMissing) {
                    $transUnits[] = $transUnit = $this->transUnitManager->create($message['id'], $message['domain'], true);
                }

                // Also store the translation if profiler state was defined
                if (!$transUnit->hasTranslation($message['locale']) && $message['state'] == DataCollectorTranslator::MESSAGE_DEFINED) {
                    $this->transUnitManager->addTranslation($transUnit, $message['locale'], $message['translation'], null, true);
                }
            }

            return array($transUnits, count($transUnits));

        } catch (\InvalidArgumentException $e) {

            // Translation collector is a 2.7 feature
            return array(array(), 0);
        }
    }

    /**
     * Updates a trans unit from the request.
     *
     * @param integer $id
     * @param Request $request
     * @throws NotFoundHttpException
     * @return \Mornin\Bundle\TranslationBundle\Model\TransUnit
     */
    public function updateFromRequest($id, Request $request)
    {
        $transUnit = $this->storage->getTransUnitById($id);

        if (!$transUnit) {
            throw new NotFoundHttpException(sprintf('No TransUnit found for "%s"', $id));
        }

        $translationsContent = array();
        foreach ($this->localeManager->getLocales() as $locale) {
            $translationsContent[$locale] = $request->request->get($locale);
        }

        $this->transUnitManager->updateTranslationsContent($transUnit, $translationsContent);

        if ($transUnit instanceof TransUnitDocument) {
            $transUnit->convertMongoTimestamp();
        }

        $this->storage->flush();

        return $transUnit;
    }

    /**
     * @param array $transUnits
     * @param int   $count
     * @param array $parameters
     * @return array
     */
    protected function filterTokenTranslations($transUnits, $count, $parameters)
    {
        // filter data
        if (isset($parameters['_search']) && $parameters['_search']) {
            $nonFilterParams = array('rows', 'page', '_search');
            $filters = array();

            array_walk($parameters, function ($value, $key) use (&$filters, $nonFilterParams) {
                if (!in_array($key, $nonFilterParams) && !empty($value)) {
                    $filters[$key] = $value;
                }
            });

            if (count($filters) > 0) {
                $end = count($transUnits);

                for ($i=0; $i<$end; $i++) {
                    $match = true;

                    foreach ($filters as $column => $str) {
                        if (in_array($column, array('key', 'domain'))) {
                            $value = $transUnits[$i]->{sprintf('get%s', ucfirst($column))}();
                        } else {
                            $translation = $transUnits[$i]->getTranslation($column);
                            $value = $translation ? $translation->getContent() : '';
                        }

                        $match = $match && (1 === preg_match(sprintf('/.*%s.*/i', $str), $value));
                    }

                    if (!$match) {
                        unset($transUnits[$i]);
                    }
                }

                $transUnits = array_values($transUnits);
                $count = count($transUnits);
            }
        }

        // slice data according to page number and rows
        if ($count > $parameters['rows']) {
            $transUnitsPage = array_slice(
                $transUnits,
                $parameters['rows'] * ($parameters['page'] - 1),
                $parameters['rows']
            );
        } else {
            $transUnitsPage = $transUnits;
        }

        return array($transUnitsPage, $count);
    }

    /**
     * @param array $dirtyParameters
     * @return array
     */
    protected function fixParameters(array $dirtyParameters)
    {
        $parameters = array();

        array_walk($dirtyParameters, function ($value, $key) use (&$parameters) {
            if ($key != '_search') {
                $key = trim($key, '_');
                $value = trim($value, '_');
            }
            $parameters[$key] = $value;
        });

        return $parameters;
    }
}
