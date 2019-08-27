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
 * MarketplaceWebServiceProducts_Model_SellerSKUListType
 * 
 * Properties:
 * <ul>
 * 
 * <li>SellerSKU: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_SellerSKUListType extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'SellerSKU' => array('FieldValue' => array(), 'FieldType' => array('string')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the SellerSKU property.
     *
     * @return List<String> SellerSKU.
     */
    public function getSellerSKU()
    {
        if ($this->_fields['SellerSKU']['FieldValue'] == null)
        {
            $this->_fields['SellerSKU']['FieldValue'] = array();
        }
        return $this->_fields['SellerSKU']['FieldValue'];
    }

    /**
     * Set the value of the SellerSKU property.
     *
     * @param array sellerSKU
     * @return this instance
     */
    public function setSellerSKU($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['SellerSKU']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear SellerSKU.
     */
    public function unsetSellerSKU()
    {
        $this->_fields['SellerSKU']['FieldValue'] = array();
    }

    /**
     * Check to see if SellerSKU is set.
     *
     * @return true if SellerSKU is set.
     */
    public function isSetSellerSKU()
    {
                return !empty($this->_fields['SellerSKU']['FieldValue']);
            }

    /**
     * Add values for SellerSKU, return this.
     *
     * @param sellerSKU
     *             New values to add.
     *
     * @return This instance.
     */
    public function withSellerSKU()
    {
        foreach (func_get_args() as $SellerSKU)
        {
            $this->_fields['SellerSKU']['FieldValue'][] = $SellerSKU;
        }
        return $this;
    }

}
