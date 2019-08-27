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
 * MarketplaceWebServiceProducts_Model_Error
 * 
 * Properties:
 * <ul>
 * 
 * <li>Type: string</li>
 * <li>Code: string</li>
 * <li>Message: string</li>
 * <li>Detail: MarketplaceWebServiceProducts_Model_ErrorDetail</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_Error extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'Type' => array('FieldValue' => null, 'FieldType' => 'string'),
'Code' => array('FieldValue' => null, 'FieldType' => 'string'),
'Message' => array('FieldValue' => null, 'FieldType' => 'string'),
'Detail' => array('FieldValue' => null, 'FieldType' => 'MarketplaceWebServiceProducts_Model_ErrorDetail'),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the Type property.
     *
     * @return String Type.
     */
    public function getType()
    {
        return $this->_fields['Type']['FieldValue'];
    }

    /**
     * Set the value of the Type property.
     *
     * @param string type
     * @return this instance
     */
    public function setType($value)
    {
        $this->_fields['Type']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if Type is set.
     *
     * @return true if Type is set.
     */
    public function isSetType()
    {
                return !is_null($this->_fields['Type']['FieldValue']);
            }

    /**
     * Set the value of Type, return this.
     *
     * @param type
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withType($value)
    {
        $this->setType($value);
        return $this;
    }

    /**
     * Get the value of the Code property.
     *
     * @return String Code.
     */
    public function getCode()
    {
        return $this->_fields['Code']['FieldValue'];
    }

    /**
     * Set the value of the Code property.
     *
     * @param string code
     * @return this instance
     */
    public function setCode($value)
    {
        $this->_fields['Code']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if Code is set.
     *
     * @return true if Code is set.
     */
    public function isSetCode()
    {
                return !is_null($this->_fields['Code']['FieldValue']);
            }

    /**
     * Set the value of Code, return this.
     *
     * @param code
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withCode($value)
    {
        $this->setCode($value);
        return $this;
    }

    /**
     * Get the value of the Message property.
     *
     * @return String Message.
     */
    public function getMessage()
    {
        return $this->_fields['Message']['FieldValue'];
    }

    /**
     * Set the value of the Message property.
     *
     * @param string message
     * @return this instance
     */
    public function setMessage($value)
    {
        $this->_fields['Message']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if Message is set.
     *
     * @return true if Message is set.
     */
    public function isSetMessage()
    {
                return !is_null($this->_fields['Message']['FieldValue']);
            }

    /**
     * Set the value of Message, return this.
     *
     * @param message
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withMessage($value)
    {
        $this->setMessage($value);
        return $this;
    }

    /**
     * Get the value of the Detail property.
     *
     * @return ErrorDetail Detail.
     */
    public function getDetail()
    {
        return $this->_fields['Detail']['FieldValue'];
    }

    /**
     * Set the value of the Detail property.
     *
     * @param MarketplaceWebServiceProducts_Model_ErrorDetail detail
     * @return this instance
     */
    public function setDetail($value)
    {
        $this->_fields['Detail']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Check to see if Detail is set.
     *
     * @return true if Detail is set.
     */
    public function isSetDetail()
    {
                return !is_null($this->_fields['Detail']['FieldValue']);
            }

    /**
     * Set the value of Detail, return this.
     *
     * @param detail
     *             The new value to set.
     *
     * @return This instance.
     */
    public function withDetail($value)
    {
        $this->setDetail($value);
        return $this;
    }

}
