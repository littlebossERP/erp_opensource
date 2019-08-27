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

class WishResponse{
  
  private $request;
  private $responseData;
  private $rawResponse;

  private $status_code;

  public function __construct($request,$responseData,$rawResponse){
    $this->request = $request;
    $this->responseData = $responseData;
    $this->rawResponse = $rawResponse;
    if(isset($this->responseData->paging)){
      $this->pager = new WishPager($this->responseData->paging);
    }
  }

  public function getStatusCode(){
    return $this->responseData->code;
  }

  public function getData(){
    return $this->responseData->data;
  }
  public function hasMore(){
    if(isset($this->pager)){
      return $this->pager->hasNext();
    }else {
      return false;
    }
  }

  public function getMessage(){
    return $this->responseData->message;
  }
  public function getRequest(){
    return $this->request;
  }
  public function getResponse(){
    return $this->responseData;
  }
  public function getRawResponse(){
    return $this->rawResponse;
  }



}