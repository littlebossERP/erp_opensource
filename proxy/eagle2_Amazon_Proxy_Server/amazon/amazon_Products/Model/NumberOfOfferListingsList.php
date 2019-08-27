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
 * MarketplaceWebServiceProducts_Model_NumberOfOfferListingsList
 * 
 * Properties:
 * <ul>
 * 
 * <li>OfferListingCount: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_NumberOfOfferListingsList extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'OfferListingCount' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebServiceProducts_Model_OfferListingCountType')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the OfferListingCount property.
     *
     * @return List<OfferListingCountType> OfferListingCount.
     */
    public function getOfferListingCount()
    {
        if ($this->_fields['OfferListingCount']['FieldValue'] == null)
        {
            $this->_fields['OfferListingCount']['FieldValue'] = array();
        }
        return $this->_fields['OfferListingCount']['FieldValue'];
    }

    /**
     * Set the value of the OfferListingCount property.
     *
     * @param array offerListingCount
     * @return this instance
     */
    public function setOfferListingCount($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['OfferListingCount']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear OfferListingCount.
     */
    public function unsetOfferListingCount()
    {
        $this->_fields['OfferListingCount']['FieldValue'] = array();
    }

    /**
     * Check to see if OfferListingCount is set.
     *
     * @return true if OfferListingCount is set.
     */
    public function isSetOfferListingCount()
    {
                return !empty($this->_fields['OfferListingCount']['FieldValue']);
            }

    /**
     * Add values for OfferListingCount, return this.
     *
     * @param offerListingCount
     *             New values to add.
     *
     * @return This instance.
     */
    public function withOfferListingCount()
    {
        foreach (func_get_args() as $OfferListingCount)
        {
            $this->_fields['OfferListingCount']['FieldValue'][] = $OfferListingCount;
        }
        return $this;
    }

}
