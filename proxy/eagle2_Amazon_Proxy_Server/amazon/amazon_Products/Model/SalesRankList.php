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
 * MarketplaceWebServiceProducts_Model_SalesRankList
 * 
 * Properties:
 * <ul>
 * 
 * <li>SalesRank: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_SalesRankList extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'SalesRank' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebServiceProducts_Model_SalesRankType')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the SalesRank property.
     *
     * @return List<SalesRankType> SalesRank.
     */
    public function getSalesRank()
    {
        if ($this->_fields['SalesRank']['FieldValue'] == null)
        {
            $this->_fields['SalesRank']['FieldValue'] = array();
        }
        return $this->_fields['SalesRank']['FieldValue'];
    }

    /**
     * Set the value of the SalesRank property.
     *
     * @param array salesRank
     * @return this instance
     */
    public function setSalesRank($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['SalesRank']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear SalesRank.
     */
    public function unsetSalesRank()
    {
        $this->_fields['SalesRank']['FieldValue'] = array();
    }

    /**
     * Check to see if SalesRank is set.
     *
     * @return true if SalesRank is set.
     */
    public function isSetSalesRank()
    {
                return !empty($this->_fields['SalesRank']['FieldValue']);
            }

    /**
     * Add values for SalesRank, return this.
     *
     * @param salesRank
     *             New values to add.
     *
     * @return This instance.
     */
    public function withSalesRank()
    {
        foreach (func_get_args() as $SalesRank)
        {
            $this->_fields['SalesRank']['FieldValue'][] = $SalesRank;
        }
        return $this;
    }

}
