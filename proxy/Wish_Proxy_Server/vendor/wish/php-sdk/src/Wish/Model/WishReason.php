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


class WishReason{
  const NO_MORE_INVENTORY       =  0;
  const UNABLE_TO_SHIP          =  1;
  const CUSTOMER_REQUEST        =  2;
  const ITEM_DAMAGED            =  3;
  const WRONG_ITEM              =  7;
  const DOES_NOT_FIT            =  8;
  const LATE_ARRIVAL_OR_MISSING =  9;
  const OTHER                   = -1;




}