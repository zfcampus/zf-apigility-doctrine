<?php
namespace ZF\Apigility\Doctrine\Server\Resource\Query;

use DoctrineModule\Persistence\ProvidesObjectManager;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use ZF\Apigility\Doctrine\Server\Delegator\HydrationDelegator;

class FetchOrmQuery implements ApigilityFetchQuery
{
    use ServiceLocatorAwareTrait;
    use ProvidesObjectManager;

    public function createQuery($entityClass, $id, $parameters)
    {
        $queryBuilder = $this->getObjectManager()->createQueryBuilder();

        $queryBuilder->select('row')
            ->from($entityClass, 'row')
            ->where('row = :id')
            ->setParameter('id', $id);

        if (isset($parameters['zoom'])) {
            $metadata = $this->getObjectManager()->getClassMetadata($entityClass);
            $zoomCount = 0;

            foreach ($parameters['zoom'] as $key => $zoom) {
                if (!$metadata->hasAssociation($zoom)) {
                    unset($parameters['zoom'][$key]);
                    continue;
                }

                $queryBuilder->leftJoin('row.' . $zoom, 'l' . $zoomCount);
                $queryBuilder->add('select', 'l' . $zoomCount, true);

                $zoomCount++;
            }

            $this->registerDelegator($entityClass, $parameters['zoom']);
        }

        $query = $queryBuilder->getQuery();

        return $query;
    }

    private function registerDelegator($entityClass, $zoom)
    {
        $config = $this->getServiceLocator()->get('Config');

        if (!isset($config['zf-hal']['metadata_map'][$entityClass])) {
            return;
        }

        $hydrator = $config['zf-hal']['metadata_map'][$entityClass]['hydrator'];
        $delegator = new HydrationDelegator();

        $delegator->setZoom($zoom);

        $this->getServiceLocator()->get('HydratorManager')->addDelegator($hydrator, $delegator);
    }
}