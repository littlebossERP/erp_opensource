<?php
/*******************************************************************************
 * Copyright 2009-2013 Amazon Services. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License"); 
 *
 * You may not use this file except in compliance with the License. 
 * You may obtain a copy of the License at: http://aws.amazon.com/apache2.0
 * This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR 
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the 
 * specific language governing permissions and limitations under the License.
 *******************************************************************************
 * PHP Version 5
 * @category Amazon
 * @package  Marketplace Web Service Products
 * @version  2011-10-01
 * Library Version: 2013-11-01
 * Generated: Fri Nov 08 21:23:22 GMT 2013
 */

/**
 *  @see MarketplaceWebServiceProducts_Model
 */

require_once (dirname(__FILE__) . '/../Model.php');


/**
 * MarketplaceWebServiceProducts_Model_GetLowestOfferListingsForSKURequest
 * 
 * Properties:
 * <ul>
 * 
 * <li>SellerId: string</li>
 * <li>MarketplaceId: string</li>
 * <li>SellerSKUList: MarketplaceWebServiceProducts_Model_SellerSKUListType</li>
 * <li>ItemCondition: string</li>
 * <li>ExcludeMe: bool</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_GetLowestOfferListingsForSKURequest extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'SellerId' => array('FieldValue' => null, 'FieldType' => 'string'),
'MarketplaceId' => array('FieldValue' => null, 'FieldType' => 'string'),
'SellerSKUList' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebServiceProducts_Model_SellerSKUListType'),
'ItemCondition' => array('FieldValue' => null, 'FieldType' => 'string'),
'ExcludeMe' => array('FieldValue' => null, 'FieldType' => 'bool'),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the SellerId property.
     *
     * @return String SellerId.
     */
    public function getSellerId()
    {
        return $this->_fields['SellerId']['FieldValue'];
    }

    /**
     * Set the value of the SellerId property.
     *
     * @param string sellerId
     * @return this instance
     */
    public function setSellerId($value)
    {
        $this->_fields['SellerId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if SellerId is set.
     *
     * @return true if SellerId is set.
     */
    public function isSetSellerId()
    {
                return !is_null($this->_fields['SellerId']['FieldValue']);
            }

    /**
     * Set the value of SellerId, return this.
     *
     * @param sellerId
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withSellerId($value)
    {
        $this->setSellerId($value);
        return $this;
    }

    /**
     * Get the value of the MarketplaceId property.
     *
     * @return String MarketplaceId.
     */
    public function getMarketplaceId()
    {
        return $this->_fields['MarketplaceId']['FieldValue'];
    }

    /**
     * Set the value of the MarketplaceId property.
     *
     * @param string marketplaceId
     * @return this instance
     */
    public function setMarketplaceId($value)
    {
        $this->_fields['MarketplaceId']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if MarketplaceId is set.
     *
     * @return true if MarketplaceId is set.
     */
    public function isSetMarketplaceId()
    {
                return !is_null($this->_fields['MarketplaceId']['FieldValue']);
            }

    /**
     * Set the value of MarketplaceId, return this.
     *
     * @param marketplaceId
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withMarketplaceId($value)
    {
        $this->setMarketplaceId($value);
        return $this;
    }

    /**
     * Get the value of the SellerSKUList property.
     *
     * @return SellerSKUListType SellerSKUList.
     */
    public function getSellerSKUList()
    {
        return $this->_fields['SellerSKUList']['FieldValue'];
    }

    /**
     * Set the value of the SellerSKUList property.
     *
     * @param MarketplaceWebServiceProducts_Model_SellerSKUListType sellerSKUList
     * @return this instance
     */
    public function setSellerSKUList($value)
    {
        $this->_fields['SellerSKUList']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if SellerSKUList is set.
     *
     * @return true if SellerSKUList is set.
     */
    public function isSetSellerSKUList()
    {
                return !is_null($this->_fields['SellerSKUList']['FieldValue']);
            }

    /**
     * Set the value of SellerSKUList, return this.
     *
     * @param sellerSKUList
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withSellerSKUList($value)
    {
        $this->setSellerSKUList($value);
        return $this;
    }

    /**
     * Get the value of the ItemCondition property.
     *
     * @return String ItemCondition.
     */
    public function getItemCondition()
    {
        return $this->_fields['ItemCondition']['FieldValue'];
    }

    /**
     * Set the value of the ItemCondition property.
     *
     * @param string itemCondition
     * @return this instance
     */
    public function setItemCondition($value)
    {
        $this->_fields['ItemCondition']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if ItemCondition is set.
     *
     * @return true if ItemCondition is set.
     */
    public function isSetItemCondition()
    {
                return !is_null($this->_fields['ItemCondition']['FieldValue']);
            }

    /**
     * Set the value of ItemCondition, return this.
     *
     * @param itemCondition
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withItemCondition($value)
    {
        $this->setItemCondition($value);
        return $this;
    }

    /**
     * Check the value of ExcludeMe.
     *
     * @return true if ExcludeMe is set to true.
     */
    public function isExcludeMe()
    {
        return !is_null($this->_fields['ExcludeMe']['FieldValue']) && $this->_fields['ExcludeMe']['FieldValue'];
    }

    /**
     * Get the value of the ExcludeMe property.
     *
     * @return Boolean ExcludeMe.
     */
    public function getExcludeMe()
    {
        return $this->_fields['ExcludeMe']['FieldValue'];
    }

    /**
     * Set the value of the ExcludeMe property.
     *
     * @param bool excludeMe
     * @return this instance
     */
    public function setExcludeMe($value)
    {
        $this->_fields['ExcludeMe']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if ExcludeMe is set.
     *
     * @return true if ExcludeMe is set.
     */
    public function isSetExcludeMe()
    {
                return !is_null($this->_fields['ExcludeMe']['FieldValue']);
            }

    /**
     * Set the value of ExcludeMe, return this.
     *
     * @param excludeMe
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withExcludeMe($value)
    {
        $this->setExcludeMe($value);
        return $this;
    }

}
