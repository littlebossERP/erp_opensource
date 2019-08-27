<?php
/**
 * Copyright 2014 Wish.com, ContextLogic or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at 
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once '../vendor/autoload.php';

use Wish\WishClient;

$key = 'JHBia2RmMiQxMDAkTG1WTUNTRkVLSVdRa3ZJZXcvZ2ZndyRoM1pNL3BoQmtmZG8vbnlRWFl0WE1XWnozMjA=';

$client = new WishClient($key,'sandbox');

//Get an array of all your products
$products = $client->getAllProducts();
print(count($products));

//Get an array of all product variations
$product_vars = $client->getAllProductVariations();
print(count($product_vars));


