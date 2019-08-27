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

namespace Wish\Exception;

class ServiceResponseException extends RuntimeException{

  protected $response;
  protected $request;

  protected $exceptionType;
  protected $exceptionCode;

  public function __construct($message,$request,$response){
    parent::__construct($message);
    $this->request = $request;
    $this->response = $response;
  }
  public function getExceptionCode(){
    return $this->exceptionCode;
  }

  public function getExceptionType(){
    return $this->exceptionType;
  }

  public function getRequest(){
    return $this->request;
  }

  public function setResponse($response){
    $this->response = $response;
  }
  public function getResponse(){
    return $this->response;
  }

  public function getErrorMessage(){
    return $this->response ? $this->response->getMessage() : null;
  }

  public function getStatusCode(){
    return $this->response ? $this->response->getStatusCode() : null;
  }

  public function __toString(){
    $message = get_class($this).': '
     .'Message: '.$this->getMessage()."\n"
     .'Status code: '.$this->getStatusCode()."\n"
     .'Error message: '.$this->getErrorMessage()."\n"
     .'Stack trace: '."\n";
     foreach($this->getTrace() as $trace){
      $message = $message.$trace['file'].' at '.$trace['function'].':'.
        $trace['line']."\n";
     }
     return $message;
  }


}