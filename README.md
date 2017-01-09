[![Build Status](https://travis-ci.org/chris48s/cakephp-geodistance.svg?branch=master)](https://travis-ci.org/chris48s/cakephp-geodistance)
[![Coverage Status](https://coveralls.io/repos/github/chris48s/cakephp-geodistance/badge.svg?branch=master)](https://coveralls.io/github/chris48s/cakephp-geodistance?branch=master)

# CakePHP GeoDistance Plugin
## A CakePHP 3 Behavior for querying geocoded data by distance.

CakePHP-GeoDistance is a CakePHP 3 behavior for querying geocoded data based on
cartographic distance using the spherical cosine law. It is great for 'find my
nearest X' or 'find Y near me' type queries. If your database doesn't already
have latitude/longitude co-ordinates attached to your geographic data, you can
add them using a geocoding plugin. Try
[this one](https://github.com/chris48s/cakephp-geocoder).


## Installation

Install from [packagist](https://packagist.org/packages/chris48s/cakephp-geodistance) using [composer](https://getcomposer.org/).
Add the following to your `composer.json`:

```
"require": {
    "chris48s/cakephp-geodistance": "^1.0.0"
}
```

and run `composer install` or `composer update`, as applicable.

## Supported databases

Only MySQL and Postgres are supported.

## Usage

### Loading the plugin

Add the code `Plugin::load('Chris48s/GeoDistance');` to your `bootstrap.php`.

### Using the Behavior

Add the behavior in your table class.

```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class MyTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Chris48s/GeoDistance.GeoDistance');
    }
}
```

#### Configuration

By default, the behavior assumes your table contains columns called `latitude`
and `longitude`, and you want to perform queries in miles. These can be changed
though. Simply pass an array of options when attaching the behavior:

```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class MyTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Chris48s/GeoDistance.GeoDistance' [
            'latitudeColumn' => 'lat',
            'longitudeColumn' => 'lng',
            'units' => 'km'
        ]);
    }
}
```

Supported units are miles or kilometres.

#### Querying data

Having added the behavior to a table class, you now have access to the query
method `find('bydistance')`, which you can use to find database records within
`radius` of a given point:

```php
<?php

use Cake\ORM\TableRegistry;

$myTable = TableRegistry::get('MyTable');

$options = [
    'latitude' => 51.3984830139,
    'longitude' => -0.236298886484,
    'radius' => 10
];
$query = $myTable
    ->find('bydistance', $options)
    ->select(['address', 'lat', 'lng']);
```

`latitude`, `longitude` and `radius` are required parameters. If required
parameters are missing or invalid, an exception of class `GeoDistanceInvalidArgumentException`
will be thrown.

You can also pass additional conditions or parameters to the query and
override the default for 'units', for example:

```php
<?php

use Cake\ORM\TableRegistry;

$myTable = TableRegistry::get('MyTable');

$options = [
    'latitude' => 51.3984830139,
    'longitude' => -0.236298886484,
    'radius' => 10,
    'units' => 'kilometres',
    'conditions' => [ 'active' => 1 ]
];
$query = $myTable
    ->find('bydistance', $options)
    ->select(['address', 'lat', 'lng']);
```

The method `find('bydistance')` returns a CakePHP query object, so you can chain
additional methods on to this (e.g: `->order()`, `->limit()`, etc).

## Reporting Issues

If you have any issues with this plugin then please feel free to create a new
[Issue](https://github.com/chris48s/cakephp-geodistance/issues) on the
[GitHub repository](https://github.com/chris48s/cakephp-geodistance).
This plugin is licensed under the MIT License.
