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
 * MarketplaceWebServiceProducts_Model_QualifiersType
 * 
 * Properties:
 * <ul>
 * 
 * <li>ItemCondition: string</li>
 * <li>ItemSubcondition: string</li>
 * <li>FulfillmentChannel: string</li>
 * <li>ShipsDomestically: string</li>
 * <li>ShippingTime: MarketplaceWebServiceProducts_Model_ShippingTimeType</li>
 * <li>SellerPositiveFeedbackRating: string</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_QualifiersType extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'ItemCondition' => array('FieldValue' => null, 'FieldType' => 'string'),
'ItemSubcondition' => array('FieldValue' => null, 'FieldType' => 'string'),
'FulfillmentChannel' => array('FieldValue' => null, 'FieldType' => 'string'),
'ShipsDomestically' => array('FieldValue' => null, 'FieldType' => 'string'),
'ShippingTime' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebServiceProducts_Model_ShippingTimeType'),
'SellerPositiveFeedbackRating' => array('FieldValue' => null, 'FieldType' => 'string'),
    );
    parent::__construct($data);
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
     * Get the value of the ItemSubcondition property.
     *
     * @return String ItemSubcondition.
     */
    public function getItemSubcondition()
    {
        return $this->_fields['ItemSubcondition']['FieldValue'];
    }

    /**
     * Set the value of the ItemSubcondition property.
     *
     * @param string itemSubcondition
     * @return this instance
     */
    public function setItemSubcondition($value)
    {
        $this->_fields['ItemSubcondition']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if ItemSubcondition is set.
     *
     * @return true if ItemSubcondition is set.
     */
    public function isSetItemSubcondition()
    {
                return !is_null($this->_fields['ItemSubcondition']['FieldValue']);
            }

    /**
     * Set the value of ItemSubcondition, return this.
     *
     * @param itemSubcondition
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withItemSubcondition($value)
    {
        $this->setItemSubcondition($value);
        return $this;
    }

    /**
     * Get the value of the FulfillmentChannel property.
     *
     * @return String FulfillmentChannel.
     */
    public function getFulfillmentChannel()
    {
        return $this->_fields['FulfillmentChannel']['FieldValue'];
    }

    /**
     * Set the value of the FulfillmentChannel property.
     *
     * @param string fulfillmentChannel
     * @return this instance
     */
    public function setFulfillmentChannel($value)
    {
        $this->_fields['FulfillmentChannel']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if FulfillmentChannel is set.
     *
     * @return true if FulfillmentChannel is set.
     */
    public function isSetFulfillmentChannel()
    {
                return !is_null($this->_fields['FulfillmentChannel']['FieldValue']);
            }

    /**
     * Set the value of FulfillmentChannel, return this.
     *
     * @param fulfillmentChannel
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withFulfillmentChannel($value)
    {
        $this->setFulfillmentChannel($value);
        return $this;
    }

    /**
     * Get the value of the ShipsDomestically property.
     *
     * @return String ShipsDomestically.
     */
    public function getShipsDomestically()
    {
        return $this->_fields['ShipsDomestically']['FieldValue'];
    }

    /**
     * Set the value of the ShipsDomestically property.
     *
     * @param string shipsDomestically
     * @return this instance
     */
    public function setShipsDomestically($value)
    {
        $this->_fields['ShipsDomestically']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if ShipsDomestically is set.
     *
     * @return true if ShipsDomestically is set.
     */
    public function isSetShipsDomestically()
    {
                return !is_null($this->_fields['ShipsDomestically']['FieldValue']);
            }

    /**
     * Set the value of ShipsDomestically, return this.
     *
     * @param shipsDomestically
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withShipsDomestically($value)
    {
        $this->setShipsDomestically($value);
        return $this;
    }

    /**
     * Get the value of the ShippingTime property.
     *
     * @return ShippingTimeType ShippingTime.
     */
    public function getShippingTime()
    {
        return $this->_fields['ShippingTime']['FieldValue'];
    }

    /**
     * Set the value of the ShippingTime property.
     *
     * @param MarketplaceWebServiceProducts_Model_ShippingTimeType shippingTime
     * @return this instance
     */
    public function setShippingTime($value)
    {
        $this->_fields['ShippingTime']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if ShippingTime is set.
     *
     * @return true if ShippingTime is set.
     */
    public function isSetShippingTime()
    {
                return !is_null($this->_fields['ShippingTime']['FieldValue']);
            }

    /**
     * Set the value of ShippingTime, return this.
     *
     * @param shippingTime
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withShippingTime($value)
    {
        $this->setShippingTime($value);
        return $this;
    }

    /**
     * Get the value of the SellerPositiveFeedbackRating property.
     *
     * @return String SellerPositiveFeedbackRating.
     */
    public function getSellerPositiveFeedbackRating()
    {
        return $this->_fields['SellerPositiveFeedbackRating']['FieldValue'];
    }

    /**
     * Set the value of the SellerPositiveFeedbackRating property.
     *
     * @param string sellerPositiveFeedbackRating
     * @return this instance
     */
    public function setSellerPositiveFeedbackRating($value)
    {
        $this->_fields['SellerPositiveFeedbackRating']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if SellerPositiveFeedbackRating is set.
     *
     * @return true if SellerPositiveFeedbackRating is set.
     */
    public function isSetSellerPositiveFeedbackRating()
    {
                return !is_null($this->_fields['SellerPositiveFeedbackRating']['FieldValue']);
            }

    /**
     * Set the value of SellerPositiveFeedbackRating, return this.
     *
     * @param sellerPositiveFeedbackRating
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withSellerPositiveFeedbackRating($value)
    {
        $this->setSellerPositiveFeedbackRating($value);
        return $this;
    }

}
