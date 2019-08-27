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
 * MarketplaceWebServiceProducts_Model_GetProductCategoriesForASINResult
 * 
 * Properties:
 * <ul>
 * 
 * <li>Self: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_GetProductCategoriesForASINResult extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'Self' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebServiceProducts_Model_Categories')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the Self property.
     *
     * @return List<Categories> Self.
     */
    public function getSelf()
    {
        if ($this->_fields['Self']['FieldValue'] == null)
        {
            $this->_fields['Self']['FieldValue'] = array();
        }
        return $this->_fields['Self']['FieldValue'];
    }

    /**
     * Set the value of the Self property.
     *
     * @param array self
     * @return this instance
     */
    public function setSelf($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['Self']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear Self.
     */
    public function unsetSelf()
    {
        $this->_fields['Self']['FieldValue'] = array();
    }

    /**
     * Check to see if Self is set.
     *
     * @return true if Self is set.
     */
    public function isSetSelf()
    {
                return !empty($this->_fields['Self']['FieldValue']);
            }

    /**
     * Add values for Self, return this.
     *
     * @param self
     *             New values to add.
     *
     * @return This instance.
     */
    public function withSelf()
    {
        foreach (func_get_args() as $Self)
        {
            $this->_fields['Self']['FieldValue'][] = $Self;
        }
        return $this;
    }

}
