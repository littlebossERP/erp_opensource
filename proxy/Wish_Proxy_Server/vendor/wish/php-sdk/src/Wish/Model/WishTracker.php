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

namespace Wish\Model;

class WishTracker{

  private $tracking_provider;
  private $tracking_id;
  private $note;

  public function __construct($tracking_provider,$tracking_id=null,$note=null){

    $this->tracking_provider = $tracking_provider;
    if($tracking_id)$this->tracking_id = $tracking_id;
    if($note)$this->note = $note;
    

  }

   public function getParams(){
    $keys = array('tracking_provider','tracking_id','note');
    $params = array();
    foreach($keys as $key){
      if(isset($this->$key)){
        $params[$key] = $this->$key;
      }
    }
    return $params;
  }


}