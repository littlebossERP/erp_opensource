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
 * MarketplaceWebServiceProducts_Model_IdentifierType
 * 
 * Properties:
 * <ul>
 * 
 * <li>MarketplaceASIN: MarketplaceWebServiceProducts_Model_ASINIdentifier</li>
 * <li>SKUIdentifier: MarketplaceWebServiceProducts_Model_SellerSKUIdentifier</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_IdentifierType extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'MarketplaceASIN' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebServiceProducts_Model_ASINIdentifier'),
'SKUIdentifier' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebServiceProducts_Model_SellerSKUIdentifier'),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the MarketplaceASIN property.
     *
     * @return ASINIdentifier MarketplaceASIN.
     */
    public function getMarketplaceASIN()
    {
        return $this->_fields['MarketplaceASIN']['FieldValue'];
    }

    /**
     * Set the value of the MarketplaceASIN property.
     *
     * @param MarketplaceWebServiceProducts_Model_ASINIdentifier marketplaceASIN
     * @return this instance
     */
    public function setMarketplaceASIN($value)
    {
        $this->_fields['MarketplaceASIN']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if MarketplaceASIN is set.
     *
     * @return true if MarketplaceASIN is set.
     */
    public function isSetMarketplaceASIN()
    {
                return !is_null($this->_fields['MarketplaceASIN']['FieldValue']);
            }

    /**
     * Set the value of MarketplaceASIN, return this.
     *
     * @param marketplaceASIN
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withMarketplaceASIN($value)
    {
        $this->setMarketplaceASIN($value);
        return $this;
    }

    /**
     * Get the value of the SKUIdentifier property.
     *
     * @return SellerSKUIdentifier SKUIdentifier.
     */
    public function getSKUIdentifier()
    {
        return $this->_fields['SKUIdentifier']['FieldValue'];
    }

    /**
     * Set the value of the SKUIdentifier property.
     *
     * @param MarketplaceWebServiceProducts_Model_SellerSKUIdentifier skuIdentifier
     * @return this instance
     */
    public function setSKUIdentifier($value)
    {
        $this->_fields['SKUIdentifier']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if SKUIdentifier is set.
     *
     * @return true if SKUIdentifier is set.
     */
    public function isSetSKUIdentifier()
    {
                return !is_null($this->_fields['SKUIdentifier']['FieldValue']);
            }

    /**
     * Set the value of SKUIdentifier, return this.
     *
     * @param skuIdentifier
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withSKUIdentifier($value)
    {
        $this->setSKUIdentifier($value);
        return $this;
    }

}
