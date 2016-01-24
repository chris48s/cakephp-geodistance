<?php

namespace Chris48s\GeoDistance\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Chris48s\GeoDistance\Exception\GeoDistanceException;

class GeoDistanceBehavior extends Behavior
{
    protected $_defaultConfig = [
        'latitudeColumn' => 'latitude',
        'longitudeColumn' => 'longitude',
        'units' => 'miles'
    ];

    /**
     * Find By Distance using spherical cosine law
     *
     * @param \Cake\ORM\Query $query Query to modify
     * @param array $options Options for the query
     * @throws GeoDistanceException If parameters are missing or invalid
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
        $query->find('all', [
            'fields' => [
                'distance' => "ROUND(
                    (:earth_radius * ACOS(
                        COS(RADIANS(:latitude)) *
                        COS(RADIANS({$this->_config['latitudeColumn']})) *
                        COS( RADIANS({$this->_config['longitudeColumn']}) - RADIANS(:longitude) ) +
                        SIN(RADIANS(:latitude)) *
                        SIN(RADIANS({$this->_config['latitudeColumn']}))
                    ) )
                , 3)"
            ],
            'order' => ['distance ASC'],
            'having' => ['distance <=' => $radius]
        ])
        ->bind(':earth_radius', $earthRadius, 'integer')
        ->bind(':latitude', $latitude, 'float')
        ->bind(':longitude', $longitude, 'float');

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
     * @param array $options Miles or km
     * @throws GeoDistanceException If $options array contains invalid config options
     * @return void
     */
    private function _validateOptions(array $options)
    {
        if (!isset($options['latitude'])) {
            throw new GeoDistanceException('Parameter latitude must be specified');
        } else {
            if (($options['latitude'] < -90) || ($options['latitude'] > 90) || !is_numeric($options['latitude'])) {
                throw new GeoDistanceException('Parameter latitude must be in the range -90 - 90');
            }
        }

        if (!isset($options['longitude'])) {
            throw new GeoDistanceException('Parameter longitude must be specified');
        } else {
            if (($options['longitude'] < -180) || ($options['longitude'] > 180) || !is_numeric($options['longitude'])) {
                throw new GeoDistanceException('Parameter longitude must be in the range -180 - 180');
            }
        }

        if (!isset($options['radius'])) {
            throw new GeoDistanceException('Parameter radius must be specified');
        } else {
            if (!is_numeric($options['radius'])) {
                throw new GeoDistanceException('Parameter radius must be a number');
            }
        }

        if (isset($options['units']) && !empty($options['units'])) {
            if (!in_array($options['units'], ['miles', 'mi', 'kilometres', 'km'])) {
                throw new GeoDistanceException(
                    "Parameter units must be one of: 'miles', 'mi', 'kilometres', 'km'"
                );
            }
        }
    }
}
