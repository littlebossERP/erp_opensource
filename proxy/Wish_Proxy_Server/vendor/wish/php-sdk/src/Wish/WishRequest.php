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

use Wish\Exception\ConnectionException;

class WishRequest{
  const VERSION = "v2/";
  const BASE_PROD_PATH = "https://merchant.wish.com/api/";
  const BASE_SANDBOX_PATH = "https://sandbox.merchant.wish.com/api/";
  const BASE_STAGE_PATH = "https://merch.corp.contextlogic.com/api/";
  private $session;
  private $method;
  private $path;
  private $params;

  public function __construct($session,$method,$path,$params = array()) {
    $this->session = $session;
    $this->method = $method;
    $this->path = $path;
    $params["access_token"] = $session->getAPIKey();
    if($session->getMerchantId())$params['merchant_id']=$session->merchant_id;
    $this->params = $params;
  }

  public function getVersion(){
    return static::VERSION;
  }

  public function getRequestURL(){
    switch($this->session->getSessionType()){
      case WishSession::SESSION_PROD: return static::BASE_PROD_PATH;
      case WishSession::SESSION_SANDBOX: return static::BASE_SANDBOX_PATH;
      case WishSession::SESSION_STAGE: return static::BASE_STAGE_PATH;
      default: throw new InvalidArgumentException("Invalid session type");
    }
  }

  public function execute(){
    $url = $this->getRequestURL().$this->getVersion().$this->path;
    $curl = curl_init();
    $params = $this->params;

    $options = array(
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_USERAGENT => 'wish-php-sdk',
      CURLOPT_HEADER  => 'true'
      );

    if($this->method === "GET"){
      $url = $url."?".http_build_query($params);
    }else {
      $options[CURLOPT_POSTFIELDS] = $params;
    }
    $options[CURLOPT_URL] = $url;
    curl_setopt_array($curl, $options);

    $result = curl_exec($curl);
    $error = curl_errno($curl);
    
    $error_message = curl_error($curl);

    if($error){
      throw new ConnectionException($error_message);
    }
    $httpStatus = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($curl,CURLINFO_HEADER_SIZE);
    curl_close($curl);
    
    $decoded_result = json_decode($result);
    if($decoded_result === null){
      $out = array();
      parse_str($result,$out);
      return new WishResponse($this,$out,$result);
    }
    return new WishResponse($this,$decoded_result,$result);
  }




}