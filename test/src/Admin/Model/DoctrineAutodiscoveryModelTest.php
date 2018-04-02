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

        foreach ($result as $row) {
            switch ($row['entity_class']) {
                case Album::class:
                    $this->assertEquals('Album', $row['service_name']);
                    $this->assertCount(2, $row['fields']);
                    break;
                case Artist::class:
                    $this->assertEquals('Artist', $row['service_name']);
                    $this->assertCount(2, $row['fields']);
                    break;
                case Product::class:
                    $this->assertEquals('Product', $row['service_name']);
                    $this->assertCount(1, $row['fields']);
                    break;
                default:
                    throw new \Exception("Unexpected result: " . $row['entity_class']);
            }
        }
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
