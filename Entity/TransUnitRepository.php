<?php

namespace Mornin\Bundle\TranslationBundle\Entity;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Mornin\Bundle\TranslationBundle\Model\File as ModelFile;

use Mornin\Bundle\TranslationBundle\Document\TransUnit as TransUnitDocument;
/**
 * Repository for TransUnit entity.
 *
 * @author Cédric Girard <c.girard@Mornin.fr>
 */
class TransUnitRepository extends EntityRepository
{
    /**
     * Returns all domain available in database.
     *
     * @return array
     */
    public function getAllDomainsByLocale()
    {
        return $this->createQueryBuilder('tu')
            ->select('te.locale, tu.domain')
            ->leftJoin('tu.translations', 'te')
            ->addGroupBy('te.locale')
            ->addGroupBy('tu.domain')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Returns all domains for each locale.
     *
     * @return array
     */
    public function getAllByLocaleAndDomain($locale, $domain)
    {
        return $this->createQueryBuilder('tu')
            ->select('tu, te')
            ->leftJoin('tu.translations', 'te')
            ->where('tu.domain = :domain')
            ->andWhere('te.locale = :locale')
            ->setParameter('domain', $domain)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Returns all trans unit with translations for the given domain and locale.
     *
     * @return array
     */
    public function getAllDomains()
    {
        $this->loadCustomHydrator();

        return $this->createQueryBuilder('tu')
            ->select('DISTINCT tu.domain')
            ->orderBy('tu.domain', 'ASC')
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');
    }

    public function insertDomain($domain, $em)
    {
        try{
            /**
             * @var EntityManager $em
             */
            $transUnit = new TransUnit();
            $transUnit->setDomain($domain);
            $transUnit->setKey("");
            $em->persist($transUnit);
            $em->flush();
            return true;
        }catch (\Exception $e){
            if($e instanceof UniqueConstraintViolationException){
                return false;
            }else{
                throw $e;
            }
        }
    }

    public function removeDomain($em)
    {
        try{
            /**
             * @var EntityManager $em
             */
            $tranUnitEntity = '\Mornin\Bundle\TranslationBundle\Entity\TransUnit';
            $this->loadCustomHydrator();

            $qb = $em->createQueryBuilder();
            $qb2 = $em->createQueryBuilder();
            $qb3 = $em->createQueryBuilder();

            $newDomains = $qb2->select('t.id')
                ->from($tranUnitEntity,'t')
                ->where("t.key = ''")
                ->andWhere($qb->expr()->notIn('t.domain', $qb->select('t1.domain')
                    ->from($tranUnitEntity,'t1')
                    ->where("t1.key <> ''")->getDQL())
                )
                ->getQuery()
                ->getResult('SingleColumnArrayHydrator');

            $domainsWithOutKeys = $qb3->select('t1.id')
                ->from($tranUnitEntity,'t1')
                ->where("t1.key = ''")
                ->getQuery()
                ->getResult('SingleColumnArrayHydrator');

            $keysToRemove = array_diff($domainsWithOutKeys, $newDomains);
            $domainToDelete = $em->getRepository($tranUnitEntity)->findBy(array('id' => $keysToRemove));
//            print_r(array_diff($domainsWithOutKeys, $newDomains));
//            die;

            if ($domainToDelete != null){
                foreach ( $domainToDelete as $val=>$transUnit ){
                    $em->remove($transUnit);
                    $em->flush();
                }
            }
            return true;
        }catch (\Exception $e){
            print_r($e->getMessage());die;
            return false;
        }
    }

    /**
     * Returns some trans units with their translations.
     *
     * @param array $locales
     * @param int   $rows
     * @param int   $page
     * @param array $filters
     * @return array
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {

        $this->loadCustomHydrator();
        $sortColumn = isset($filters['sidx']) ? $filters['sidx'] : 'id';
        $order = isset($filters['sord']) ? $filters['sord'] : 'DESC';

        $builder = $this->createQueryBuilder('tu')
            ->select('tu.id');

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        //get the 20 id's from trans_unit Table
        $ids = $builder->orderBy(sprintf('tu.%s', $sortColumn), $order)
            ->setFirstResult($rows * ($page - 1))
            ->setMaxResults(20)
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');

        $transUnits = array();

        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder('tu');
            $transUnits = $qb->select('tu, te')
                ->leftJoin('tu.translations', 'te')
                ->andWhere($qb->expr()->in('tu.id', $ids))
                //->andWhere($qb->expr()->in('te.locale', $locales))
                ->orderBy(sprintf('tu.%s', $sortColumn), $order)
                ->getQuery()
                ->getArrayResult();
        }

        return $transUnits;
    }

    /**
     * Count the number of trans unit.
     *
     * @param array $locales
     * @param array $filters
     * @return int
     */
    public function count(array $locales = null,  array $filters = null)
    {
        $this->loadCustomHydrator();

        $builder = $this->createQueryBuilder('tu')
            ->select('COUNT(DISTINCT tu.id) AS number');

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        return (int) $builder->getQuery()->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @return array
     */
    public function countByDomains()
    {
        return $this->createQueryBuilder('tu')
            ->select('COUNT(DISTINCT tu.id) AS number, tu.domain')
            ->groupBy('tu.domain')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Returns all translations for the given file.
     *
     * @param ModelFile $file
     * @param boolean   $onlyUpdated
     * @return array
     */
    public function getTranslationsForFile(ModelFile $file, $onlyUpdated)
    {
        $builder = $this->createQueryBuilder('tu')
            ->select('tu.key, te.content')
            ->leftJoin('tu.translations', 'te')
            ->where('te.file = :file')
            ->setParameter('file', $file->getId())
            ->orderBy('te.id', 'asc');

        if ($onlyUpdated) {
            $builder->andWhere($builder->expr()->gt('te.updatedAt', 'te.createdAt'));
        }

        $results = $builder->getQuery()->getArrayResult();

        $translations = array();
        foreach ($results as $result) {
            $translations[$result['key']] = $result['content'];
        }

        return $translations;
    }

    /**
     * Add conditions according to given filters.
     *
     * @param QueryBuilder $builder
     * @param array        $filters
     */
    protected function addTransUnitFilters(QueryBuilder $builder, array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            if (!empty($filters['domain'])) {
                $builder->andWhere($builder->expr()->like('tu.domain', ':domain'))
                    ->setParameter('domain', sprintf('%%%s%%', $filters['domain']));
            }

            if (!empty($filters['key'])) {
                $builder->andWhere($builder->expr()->like('tu.key', ':key'))
                    ->setParameter('key', sprintf('%%%s%%', $filters['key']));
            }
        }
    }

    /**
     * Add conditions according to given filters.
     *
     * @param QueryBuilder $builder
     * @param array        $locales
     * @param array        $filters
     */
    protected function addTranslationFilter(QueryBuilder $builder, array $locales = null, array $filters = null)
    {
        if (null !== $locales) {
            $qb = $this->createQueryBuilder('tu');
            $qb->select('DISTINCT tu.id')
                    ->leftJoin('tu.translations', 't')
                    ->andWhere($qb->expr()->neq('tu.key', ':key'))
                    ->setParameter(':key', "");

            //@note - filter data with locale. commented to display the all record
            //which available in trans_unit table
                    //->where($qb->expr()->in('t.locale', $locales));

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $qb->andWhere($qb->expr()->like('t.content', ':content'))
                        ->setParameter('content', sprintf('%%%s%%', $filters[$locale]));

                    $qb->andWhere($qb->expr()->eq('t.locale', ':locale'))
                        ->setParameter('locale', sprintf('%s', $locale));
                }
            }

            $ids = $qb->getQuery()->getResult('SingleColumnArrayHydrator');
            if (count($ids) > 0) {
                $builder->andWhere($builder->expr()->in('tu.id', $ids));
            }
        }
    }

    /**
     * Load custom hydrator.
     */
    protected function loadCustomHydrator()
    {
        $config = $this->getEntityManager()->getConfiguration();
        $config->addCustomHydrationMode('SingleColumnArrayHydrator', 'Mornin\Bundle\TranslationBundle\Util\Doctrine\SingleColumnArrayHydrator');
    }
}
