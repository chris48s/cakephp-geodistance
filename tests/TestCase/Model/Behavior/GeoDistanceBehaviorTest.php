<?php

namespace Chris48s\GeoDistance\Test\TestCase\Model\Behavior;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Chris48s\GeoDistance\Exception\GeoDistanceFatalException;
use Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException;
use Chris48s\GeoDistance\Model\Behavior\GeoDistanceBehavior;

class GeoDistanceBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Chris48s\GeoDistance.foo'
    ];

    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    // set up a table to use for testing
    private function getValidTable()
    {
        $table = TableRegistry::get('Foo');
        $table->addBehavior('Chris48s/GeoDistance.GeoDistance', [
            'latitudeColumn' => 'lat',
            'longitudeColumn' => 'lng'
        ]);
        return $table;
    }

    /* our fixture table does not contain columns called 'latitude' or 'longitude'
       so we expect a GeoDistanceFatalException to be thrown */
    public function testSetupInvalidColumns()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceFatalException');

        $table = TableRegistry::get('Foo');
        $table->addBehavior('Chris48s/GeoDistance.GeoDistance');
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'latitude' is not specified
    public function testInvalidQueryParamsNoLat()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'longitude' => 0,
            'radius' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'longitude' is not specified
    public function testInvalidQueryParamsNoLng()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'radius' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'radius' is not specified
    public function testInvalidQueryParamsNoRadius()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'longitude' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'latitude' is outside valid range
    public function testInvalidQueryParamsBadLat1()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 91,
            'longitude' => 0,
            'radius' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'latitude' is not numeric
    public function testInvalidQueryParamsBadLat2()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 'foo',
            'longitude' => 0,
            'radius' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'longitude' is outside valid range
    public function testInvalidQueryParamsBadLng1()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'longitude' => 181,
            'radius' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'longitude' is not numeric
    public function testInvalidQueryParamsBadLng2()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'longitude' => 'foo',
            'radius' => 0
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // ensure GeoDistanceInvalidArgumentException is thrown if 'radius' is not numeric
    public function testInvalidQueryParamsBadRadius()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'longitude' => 0,
            'radius' => 'foo'
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    /* ensure GeoDistanceInvalidArgumentException is thrown if
       'units' is not 'miles', 'mi', 'kilometres' or 'km' */
    public function testInvalidQueryParamsBadUnits()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException');

        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'longitude' => 0,
            'radius' => 0,
            'units' => 'foo'
        ];
        $query = $table->find('bydistance', $options);
        $query->toArray();
    }

    // check (0, 180) and (0, -180) are considered the same point
    public function testWrapAround()
    {
        $table = $this->getValidTable();
        $options = [
            'latitude' => 0,
            'longitude' => 180,
            'radius' => 0,
            'units' => 'km'
        ];
        $query = $table
            ->find('bydistance', $options)
            ->select(['name', 'lat', 'lng']);
        $result = $query->toArray();
        $this->assertEquals(2, count($result));
    }

    // ensure correct points are returned with a small radius
    public function testSmallDistance()
    {
        $table = $this->getValidTable();
        $options = [
            'latitude' => 52.47980068128972,
            'longitude' => -1.8967723846435545,
            'radius' => 0.9,
            'units' => 'km'
        ];
        $query = $table
            ->find('bydistance', $options)
            ->select(['name', 'lat', 'lng']);
        $result = $query->toArray();
        $this->assertEquals(4, count($result));
        foreach ($result as $row) {
            $this->assertTrue(in_array(
                $row['name'],
                ['birminham centre', 'birmingham close 1', 'birmingham close 2', 'birmingham close 3']
            ));
        }
    }

    // ensure extra conditions are also applied
    public function testExtraConditions()
    {
        $table = $this->getValidTable();
        $options = [
            'latitude' => 52.47980068128972,
            'longitude' => -1.8967723846435545,
            'radius' => 1,
            //'units' => 'miles', is implicit if not specified
            'conditions' => [ 'active' => 1 ]
        ];
        $query = $table
            ->find('bydistance', $options)
            ->select(['name', 'lat', 'lng']);
        $result = $query->toArray();
        $this->assertEquals(3, count($result));
        foreach ($result as $row) {
            $this->assertTrue(in_array(
                $row['name'],
                ['birminham centre', 'birmingham close 1', 'birmingham close 2']
            ));
        }
    }

    /* every point on the earth's surface should be a max of
       round((pi() * (2 * 6371)) / 2, 3)km away from every other point
       based on 6371km as an approximation of the earth's mean radius */
    public function testLargeDistance()
    {
        $table = $this->getValidTable();
        $options = [
            'latitude' => 90,
            'longitude' => 0,
            'radius' => round((pi() * (2 * 6371)) / 2, 3),
            'units' => 'km'
        ];
        $query = $table
            ->find('bydistance', $options)
            ->select(['name', 'lat', 'lng']);
        $result = $query->toArray();
        $this->assertEquals(10, count($result));
        foreach ($result as $row) {
            if ($row['name'] == 'south pole') {
                $this->assertEquals(round((pi() * (2 * 6371)) / 2, 3), $row['distance']);
            }
        }
    }

    /* Only MySQL and Postgres are supported
       Pass in a connection to another DB engine
       and ensure correct exception is thrown */
    public function testInvalidDB()
    {
        $this->setExpectedException('Chris48s\GeoDistance\Exception\GeoDistanceFatalException');

        //set up a SQLite DB connection - SQLite is not supported
        ConnectionManager::config('invalid', [
            'url' => 'sqlite:///:memory:',
            'timezone' => 'UTC'
        ]);
        $conn = ConnectionManager::get('invalid');

        //create a table in SQLite
        $conn->query("CREATE TABLE `Foo` (
            `id` int(11) NOT NULL,
            `lat` float,
            `lng` float,
            PRIMARY KEY (`id`)
        );");
        $table = TableRegistry::get('Foo', ['connection' => $conn]);
        $table->addBehavior('Chris48s/GeoDistance.GeoDistance', [
            'latitudeColumn' => 'lat',
            'longitudeColumn' => 'lng'
        ]);

        //tidy up
        ConnectionManager::dropAlias('invalid');
    }
}
