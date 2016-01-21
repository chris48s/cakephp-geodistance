<?php

namespace Chris48s\GeoDistance\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class FooFixture extends TestFixture
{
    public $table = 'foo';

    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'null' => true],
        'lat' => ['type' => 'float', 'null' => false],
        'lng' => ['type' => 'float', 'null' => false],
        'active' => ['type' => 'boolean', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    public $records = [
        ['id' => 1, 'name' => 'south pole', 'lat' => -90, 'lng' => 0, 'active' => false ],
        ['id' => 2, 'name' => 'north pole', 'lat' => 90, 'lng' => 0, 'active' => false ],
        ['id' => 3, 'name' => 'same 1', 'lat' => 0, 'lng' => -180, 'active' => false ],
        ['id' => 4, 'name' => 'same 2', 'lat' => 0, 'lng' => 180, 'active' => false ],
        ['id' => 5, 'name' => 'birminham centre', 'lat' => 52.47980068128972, 'lng' => -1.8967723846435545, 'active' => true ],
        ['id' => 6, 'name' => 'birmingham close 1', 'lat' => 52.4858640956247, 'lng' => -1.8966865539550781, 'active' => true ],
        ['id' => 7, 'name' => 'birmingham close 2', 'lat' => 52.47985295567416, 'lng' => -1.904325485229492, 'active' => true ],
        ['id' => 8, 'name' => 'birmingham close 3', 'lat' => 52.47718688287627, 'lng' => -1.8944549560546875, 'active' => false ],
        ['id' => 9, 'name' => 'birmingham far', 'lat' => 52.50514646853436, 'lng' => -1.8513679504394531, 'active' => true ],
        ['id' => 10, 'name' => 'wrong birmingham', 'lat' => 33.519644153199245, 'lng' => -86.8033218383789, 'active' => true ],
    ];
}
