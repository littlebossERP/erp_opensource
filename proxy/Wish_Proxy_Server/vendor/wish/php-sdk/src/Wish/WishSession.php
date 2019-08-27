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
namespace Wish;

use Wish\Exception\InvalidArgumentException;

class WishSession{
  const SESSION_PROD = 1;
  const SESSION_SANDBOX = 2;
  const SESSION_STAGE = 3;
  private static $api_key;
  private static $session_type;
  private static $merchant_id;

  public function __construct($api_key,$session_type,$merchant_id=null){
    $this->api_key = $api_key;
    $this->merchant_id = $merchant_id;
    switch($session_type){
      case 'sandbox':
        $this->session_type = static::SESSION_SANDBOX;break;
      case 'prod':
        $this->session_type = static::SESSION_PROD;break;
      case 'stage':
        $this->session_type = static::SESSION_STAGE;break;
      default:
        throw new InvalidArgumentException('Invalid session type');
    }
  }

  public function getAPIKey(){
    return $this->api_key;
  }
  public function getSessionType(){
    return $this->session_type;
  }
  public function getMerchantId(){
    return $this->merchant_id;
  }


}


