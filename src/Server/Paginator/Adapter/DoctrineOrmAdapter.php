<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Server\Paginator\Adapter;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Zend\Paginator\Adapter\AdapterInterface;

class DoctrineOrmAdapter extends Paginator implements AdapterInterface
{
    /**
     * @var array
    */
    public $cache = [];

    /**
     * @param $offset
     * @param $itemCountPerPage
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        if (array_key_exists($offset, $this->cache)
            && array_key_exists($itemCountPerPage, $this->cache[$offset])
        ) {
            return $this->cache[$offset][$itemCountPerPage];
        }

        $this->getQuery()->setFirstResult($offset);
        $this->getQuery()->setMaxResults($itemCountPerPage);

        if (! array_key_exists($offset, $this->cache)) {
            $this->cache[$offset] = [];
        }

        $this->cache[$offset][$itemCountPerPage] = $this->getQuery()->getResult();

        return $this->cache[$offset][$itemCountPerPage];
    }
}
