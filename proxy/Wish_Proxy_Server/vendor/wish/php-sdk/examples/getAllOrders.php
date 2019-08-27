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
use Wish\Model\WishTracker;
use Wish\Exception\OrderAlreadyFulfilledException;
use Wish\Model\WishReason;


$key = 'JHBia2RmMiQxMDAkMTlxN0YyS00wYnFYa3JJMkJnRGdYQSRDVllqZmVCbk1VWjVhak13OUgxTk91Z2kwUDg=';
$client = new WishClient($key,'sandbox');

//Fulfill one order by ID
//$tracker = new WishTracker('USPS','123123123','Thanks for buying!');
//$client->fulfillOrderById('537850c38b72ac0db9472cb4',$tracker);
//or 
//$one_order = $client->getOrderById('53767deb43d2470c6d04d856');
//$client->fulfillOrder($one_order);

//Get an array of all changed orders since January 20, 2010
$changed_orders = $client->getAllChangedOrdersSince();
print(count($changed_orders)." changed orders.\n");

//Get an array of all unfufilled orders since January 20, 2010
$unfulfilled_orders = $client->getAllUnfulfilledOrdersSince('2010-01-20');
print(count($unfulfilled_orders)." changed orders.\n");


//Fulfill all unfulfilled orders
foreach($unfulfilled_orders as $order){
  try {
    //Generate your own tracking information here:"
    $tracker = new WishTracker('USPS','123123123','Thanks for buying!');
    //Fulfill the order using the tracking information
    $client->fulfillOrder($order,$tracker);
  } catch(OrderAlreadyFulfilledException $e){
    print 'Order '.$order->order_id." already fulfilled.\n";
  }
}


//Update tracking information
$tracker = new WishTracker('USPS','123123123','Thanks for buying!');
$client->updateTrackingInfoById('53785043482e680c58a08f53',$tracker);
//or 
//$one_order = $client->getOrderById('53767deb43d2470c6d04d856');
//$client->updateTrackingInfo($one_order,$tracker);

//Refund an order
$client->refundOrderById('537850c38b72ac0db9472cb4',
  WishReason::NO_MORE_INVENTORY);
