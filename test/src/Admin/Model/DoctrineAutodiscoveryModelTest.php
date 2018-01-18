<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use ZF\Apigility\Doctrine\Admin\Model\DoctrineAutodiscoveryModel;
use ZFTest\Apigility\Doctrine\TestCase;
use ZFTestApigilityDb\Entity\Album;
use ZFTestApigilityDb\Entity\Artist;
use ZFTestApigilityDb\Entity\Product;
use ZFTestApigilityDbMongo\Document\Meta;

class DoctrineAutodiscoveryModelTest extends TestCase
{
    public function testORMAutodiscoveryEntitiesWithFields()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../config/ORM/application.config.php'
        );

        $model = new DoctrineAutodiscoveryModel([]);
        $model->setServiceLocator($this->getApplicationServiceLocator());

        $result = $model->fetchFields(null, null, 'doctrine.entitymanager.orm_default');

        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);

        $this->assertEquals(Album::class, $result[0]['entity_class']);
        $this->assertEquals('Album', $result[0]['service_name']);
        $this->assertCount(2, $result[0]['fields']);

        $this->assertEquals(Product::class, $result[1]['entity_class']);
        $this->assertEquals('Product', $result[1]['service_name']);
        $this->assertCount(1, $result[1]['fields']);

        $this->assertEquals(Artist::class, $result[2]['entity_class']);
        $this->assertEquals('Artist', $result[2]['service_name']);
        $this->assertCount(2, $result[2]['fields']);
    }

    public function testODMAutodiscoveryEntitiesWithFields()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../config/ODM/application.config.php'
        );

        $model = new DoctrineAutodiscoveryModel([]);
        $model->setServiceLocator($this->getApplicationServiceLocator());

        $result = $model->fetchFields(null, null, 'doctrine.documentmanager.odm_default');

        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);

        $this->assertEquals(Meta::class, $result[0]['entity_class']);
        $this->assertEquals('Meta', $result[0]['service_name']);
        $this->assertCount(2, $result[0]['fields']);
    }
}
