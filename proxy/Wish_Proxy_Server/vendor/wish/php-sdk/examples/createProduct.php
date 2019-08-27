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
use Wish\Exception\ServiceResponseException;

$key = 'JHBia2RmMiQxMDAkMTlxN0YyS00wYnFYa3JJMkJnRGdYQSRDVllqZmVCbk1VWjVhak13OUgxTk91Z2kwUDg=';
$client = new WishClient($key,'sandbox');

$product = array(
  'name'=>'Red Shoe',
  'main_image'=>'https://www.google.com/images/srpr/logo11w.png',
  'sku'=>'prod 7',
  'parent_sku'=>'prod 7',
  'shipping'=>'10',
  'tags'=>'red,shoe,cool',
  'description'=>'a cool shoe',
  'price'=>'100',
  'inventory'=>'10',
  'randomfield'=>'12321'
  );


try {
  $prod_res = $client->createProduct($product);
  print_r($prod_res);


  $product_var = array(
    'parent_sku'=>$product['parent_sku'],
    'color'=>'red',
    'sku'=>'var 7',
    'inventory'=>10,
    'price'=>10,
    'shipping'=>10
    );

  $prod_var = $client->createProductVariation($product_var);
  print_r($prod_var);



}catch(ServiceResponsException $e){
  echo "There was an error performing an operation.\n";
}