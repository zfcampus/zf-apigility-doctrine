<?php

namespace SoliantConsulting\Apigility\Server\Paginator\Adapter;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Zend\Paginator\Adapter\AdapterInterface;

/**
 * Class DoctrineOrmAdapter
 *
 * @package SoliantConsulting\Apigility\Server\Paginator\Adapter
 */
class DoctrineOrmAdapter extends Paginator implements AdapterInterface
{
    /**
     * @param $offset
     * @param $itemCountPerPage
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage) {
        $this->getQuery()->setFirstResult($offset);
        $this->getQuery()->setMaxResults($itemCountPerPage);

        return $this->getQuery()->getResult()->toArray();
    }
}