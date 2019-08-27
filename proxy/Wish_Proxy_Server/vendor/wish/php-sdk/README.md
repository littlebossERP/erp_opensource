# Wish SDK for PHP

## Documentation

http://merchant.wish.com/documentation/api

## Install Instructions

Download composer:

````
curl -sS https://getcomposer.org/installer | php
````

Add wish/wish-sdk-php as a dependect in your project's composer.json

````
{
  "minimum-stability": "dev",
  "require":{
      "wish/php-sdk":"*"
  }
}
````

Run the following:
````
php composer.phar install
````


Put the following at the top of your file:

````
require 'vendor/autoload.php'
````

Sample
````php
<?php 
require_once 'vendor/autoload.php';

use Wish\WishClient;

$key = 'JHBia2RmMiQxMDAkTG1WTUNTRkVLSVdRa3ZJZXcvZ2ZndyRoM1pNL3BoQmtmZG8vbnlRWFl0WE1XWnozMjA=';
$client = new WishClient($key,'sandbox');


print "RESULT: ".$client->authTest();
````
