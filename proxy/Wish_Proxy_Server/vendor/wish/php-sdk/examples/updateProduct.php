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

$key = 'JHBia2RmMiQxMDAkMTlxN0YyS00wYnFYa3JJMkJnRGdYQSRDVllqZmVCbk1VWjVhak13OUgxTk91Z2kwUDg=';
$client = new WishClient($key,'sandbox');

try{
  //Get your product by its ID
  $product = $client->getProductById('535d205bb9ee84128ac15fc0');
  print_r( $product);

  //Get your product variation by its SKU
  $product_var = $client->getProductVariationBySKU('var 7');
  print_r($product_var);

  //Enable or disable your product variation
  $client->enableProductVariation($product_var);
  //or $client->enableProductVariationBySKU('var 6');
  $client->disableProductVariation($product_var);
  //or $client->disableProductVariationBySKU('var 6');

  //Update your product variation and save it
  $product_var->inventory = 10;
  $client->updateProductVariation($product_var);
}catch(ServiceResponsException $e){
  echo "There was an error performing an operation.\n";
}



