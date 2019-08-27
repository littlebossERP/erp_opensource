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
 * MarketplaceWebServiceProducts_Model_OffersList
 * 
 * Properties:
 * <ul>
 * 
 * <li>Offer: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_OffersList extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'Offer' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebServiceProducts_Model_OfferType')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the Offer property.
     *
     * @return List<OfferType> Offer.
     */
    public function getOffer()
    {
        if ($this->_fields['Offer']['FieldValue'] == null)
        {
            $this->_fields['Offer']['FieldValue'] = array();
        }
        return $this->_fields['Offer']['FieldValue'];
    }

    /**
     * Set the value of the Offer property.
     *
     * @param array offer
     * @return this instance
     */
    public function setOffer($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['Offer']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear Offer.
     */
    public function unsetOffer()
    {
        $this->_fields['Offer']['FieldValue'] = array();
    }

    /**
     * Check to see if Offer is set.
     *
     * @return true if Offer is set.
     */
    public function isSetOffer()
    {
                return !empty($this->_fields['Offer']['FieldValue']);
            }

    /**
     * Add values for Offer, return this.
     *
     * @param offer
     *             New values to add.
     *
     * @return This instance.
     */
    public function withOffer()
    {
        foreach (func_get_args() as $Offer)
        {
            $this->_fields['Offer']['FieldValue'][] = $Offer;
        }
        return $this;
    }

}
