# Sypex Geo for Phalcon

Configuration

```php
return new \Phalcon\Config([
    //...
    'geo' => [
        'debug' => true,
        'path' => './geo-files',
        'types' => 1 | 2 // SXGEO_MEMORY | SXGEO_BATCH
    ],
    //...
]);
```

Download files

```php
$geoClass = new \Geo\Geo();
$geoClass->downloadFiles(); // all
$geoClass->downloadFiles('city'); // city
$geoClass->downloadFiles(['city']); // city
$geoClass->downloadFiles('https://sypexgeo.net/files/SxGeoCity_utf8.zip'); // city
$geoClass->downloadFiles(['https://sypexgeo.net/files/SxGeoCity_utf8.zip', 'country']); // city and country
```

Download one file

```php
$geoClass = new \Geo\Geo();
$geoClass->downloadFile('https://sypexgeo.net/files/SxGeoCity_utf8.zip');
```

Get city base

```php
$geoClass = new \Geo\Geo();
$cityData = $geoClass->getCity();

$ip = $this->request->getClientAddress();
var_dump($cityData->getCityFull($ip));
```

Get country base

```php
$geoClass = new \Geo\Geo();
$countryData = $geoClass->getCountry();

$ip = $this->request->getClientAddress();
var_dump($countryData->getCountry($ip));
```
