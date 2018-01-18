<?php

namespace ZFTestApigilityDb\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;

class RevGenerator extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {
        do {
            $value = md5(time() . mt_rand());
        } while ($value === strrev($value));

        return $value;
    }
}
