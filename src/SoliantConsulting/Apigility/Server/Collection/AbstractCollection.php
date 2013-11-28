<?php
namespace SoliantConsulting\Apigility\Server\Collection;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Zend\Paginator\Adapter\AdapterInterface;

abstract class AbstractCollection extends Paginator implements AdapterInterface
{
    public function getItems($offset, $itemCountPerPage) {
        $this->getQuery()->setFirstResult($offset);
        $this->getQuery()->setMaxResults($itemCountPerPage);

        return $this->getQuery()->getResult();
    }
}