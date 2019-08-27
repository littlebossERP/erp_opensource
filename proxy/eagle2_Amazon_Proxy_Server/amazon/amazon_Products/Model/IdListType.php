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
 * MarketplaceWebServiceProducts_Model_IdListType
 * 
 * Properties:
 * <ul>
 * 
 * <li>Id: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_IdListType extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'Id' => array('FieldValue' => array(), 'FieldType' => array('string')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the Id property.
     *
     * @return List<String> Id.
     */
    public function getId()
    {
        if ($this->_fields['Id']['FieldValue'] == null)
        {
            $this->_fields['Id']['FieldValue'] = array();
        }
        return $this->_fields['Id']['FieldValue'];
    }

    /**
     * Set the value of the Id property.
     *
     * @param array id
     * @return this instance
     */
    public function setId($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['Id']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear Id.
     */
    public function unsetId()
    {
        $this->_fields['Id']['FieldValue'] = array();
    }

    /**
     * Check to see if Id is set.
     *
     * @return true if Id is set.
     */
    public function isSetId()
    {
                return !empty($this->_fields['Id']['FieldValue']);
            }

    /**
     * Add values for Id, return this.
     *
     * @param id
     *             New values to add.
     *
     * @return This instance.
     */
    public function withId()
    {
        foreach (func_get_args() as $Id)
        {
            $this->_fields['Id']['FieldValue'][] = $Id;
        }
        return $this;
    }

}
