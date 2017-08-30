<?php

namespace Chris48s\GeoDistance\Model\Behavior;

use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Chris48s\GeoDistance\Exception\GeoDistanceFatalException;
use Chris48s\GeoDistance\Exception\GeoDistanceInvalidArgumentException;

class GeoDistanceBehavior extends Behavior
{
    protected $_defaultConfig = [
        'latitudeColumn' => 'latitude',
        'longitudeColumn' => 'longitude',
        'units' => 'miles'
    ];

    /**
     * Constructor hook method
     *
     * @param array $config The configuration settings provided to this behavior
     * @throws GeoDistanceFatalException If database engine is not MySQL or Postgres
     * @throws GeoDistanceFatalException If latitudeColumn or longitudeColumn are invalid
     * @return void
     */
    public function initialize(array $config)
    {
        $connection = $this->_table->connection();

        //ensure database engine is MySQL or Postgres
        if ((!$connection->driver() instanceof Mysql) && (!$connection->driver() instanceof Postgres)) {
            throw new GeoDistanceFatalException('Only MySQL and Postgres are supported');
        }

        //ensure latitudeColumn and longitudeColumn exist in table
        $collection = $connection->schemaCollection();
        $columns = $collection->describe($this->_table->table())->columns();
        if (!in_array($this->_config['latitudeColumn'], $columns) ||
            !in_array($this->_config['longitudeColumn'], $columns)) {
            throw new GeoDistanceFatalException('Invalid column');
        }
        $this->_config['latitudeColumn'] = $this->_table->alias() . '.' . $this->_config['latitudeColumn'];
        $this->_config['longitudeColumn'] = $this->_table->alias() . '.' . $this->_config['longitudeColumn'];
    }

    /**
     * Find By Distance using spherical cosine law
     *
     * @param \Cake\ORM\Query $query Query to modify
     * @param array $options Options for the query
     * @throws GeoDistanceInvalidArgumentException If parameters are missing or invalid
     * @return \Cake\ORM\Query
     */
    public function findByDistance(Query $query, array $options)
    {
        // set up parameters
        $this->_validateOptions($options);

        $latitude = $options['latitude'];
        $longitude = $options['longitude'];
        $radius = $options['radius'];
        if (isset($options['units']) && !empty($options['units'])) {
            $units = $options['units'];
        } else {
            $units = $this->_config['units'];
        }

        $earthRadius = $this->_getEarthRadius($units);

        // construct query
        $sphericalCosineSql = "(:earth_radius * ACOS(
            COS(RADIANS(:latitude)) *
            COS(RADIANS({$this->_config['latitudeColumn']})) *
            COS( RADIANS({$this->_config['longitudeColumn']}) - RADIANS(:longitude) ) +
            SIN(RADIANS(:latitude)) *
            SIN(RADIANS({$this->_config['latitudeColumn']}))
        ) )";

        $connection = $query->connection();
        if ($connection->driver() instanceof Mysql) {
            $distance = "ROUND($sphericalCosineSql, 3)";
        } elseif ($connection->driver() instanceof Postgres) {
            $distance = "ROUND( CAST($sphericalCosineSql AS numeric), 3)";
        }

        $queryOptions = [
            'fields' => ['distance' => $distance],
            'order' => ['distance ASC'],
            'conditions' => ["$distance <= :radius"]
        ];
        $query->find('all', $queryOptions)
        ->bind(':earth_radius', $earthRadius, 'integer')
        ->bind(':latitude', $latitude, 'float')
        ->bind(':longitude', $longitude, 'float')
        ->bind(':radius', $radius, 'float');

        return $query;
    }

    /**
     * Earth Radius
     *
     * The (approximate) mean radius of the earth in miles or km
     * @param string $units miles or km
     * @return float
     */
    private function _getEarthRadius($units)
    {
        if (in_array($units, ['kilometres', 'km'])) {
            return 6371;
        } else {
            return 3958.756;
        }
    }

    /**
     * Validate Options
     *
     * Check the configuration options conform to expected values/formats
     * @param array $options Options for the query
     * @throws GeoDistanceInvalidArgumentException If parameters are missing or invalid
     * @return void
     */
    private function _validateOptions(array $options)
    {
        if (!isset($options['latitude'])) {
            throw new GeoDistanceInvalidArgumentException('Parameter latitude must be specified');
        } else {
            if (($options['latitude'] < -90) || ($options['latitude'] > 90) || !is_numeric($options['latitude'])) {
                throw new GeoDistanceInvalidArgumentException('Parameter latitude must be in the range -90 - 90');
            }
        }

        if (!isset($options['longitude'])) {
            throw new GeoDistanceInvalidArgumentException('Parameter longitude must be specified');
        } else {
            if (($options['longitude'] < -180) || ($options['longitude'] > 180) || !is_numeric($options['longitude'])) {
                throw new GeoDistanceInvalidArgumentException('Parameter longitude must be in the range -180 - 180');
            }
        }

        if (!isset($options['radius'])) {
            throw new GeoDistanceInvalidArgumentException('Parameter radius must be specified');
        } else {
            if (!is_numeric($options['radius'])) {
                throw new GeoDistanceInvalidArgumentException('Parameter radius must be a number');
            }
        }

        if (isset($options['units']) && !empty($options['units'])) {
            if (!in_array($options['units'], ['miles', 'mi', 'kilometres', 'km'])) {
                throw new GeoDistanceInvalidArgumentException(
                    "Parameter units must be one of: 'miles', 'mi', 'kilometres', 'km'"
                );
            }
        }
    }
}
