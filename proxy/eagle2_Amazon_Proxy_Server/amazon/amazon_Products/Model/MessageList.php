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
 * MarketplaceWebServiceProducts_Model_MessageList
 * 
 * Properties:
 * <ul>
 * 
 * <li>Message: array</li>
 *
 * </ul>
 */

 class MarketplaceWebServiceProducts_Model_MessageList extends MarketplaceWebServiceProducts_Model {

    public function __construct($data = null)
    {
    $this->_fields = array (
'Message' => array('FieldValue' => array(), 'FieldType' => array('MarketplaceWebServiceProducts_Model_Message')),
    );
    parent::__construct($data);
    }

    /**
     * Get the value of the Message property.
     *
     * @return List<Message> Message.
     */
    public function getMessage()
    {
        if ($this->_fields['Message']['FieldValue'] == null)
        {
            $this->_fields['Message']['FieldValue'] = array();
        }
        return $this->_fields['Message']['FieldValue'];
    }

    /**
     * Set the value of the Message property.
     *
     * @param array message
     * @return this instance
     */
    public function setMessage($value)
    {
        if (!$this->_isNumericArray($value)) {
            $value = array ($value);
        }
        $this->_fields['Message']['FieldValue'] = $value;
        return $this;
    }

    /**
     * Clear Message.
     */
    public function unsetMessage()
    {
        $this->_fields['Message']['FieldValue'] = array();
    }

    /**
     * Check to see if Message is set.
     *
     * @return true if Message is set.
     */
    public function isSetMessage()
    {
                return !empty($this->_fields['Message']['FieldValue']);
            }

    /**
     * Add values for Message, return this.
     *
     * @param message
     *             New values to add.
     *
     * @return This instance.
     */
    public function withMessage()
    {
        foreach (func_get_args() as $Message)
        {
            $this->_fields['Message']['FieldValue'][] = $Message;
        }
        return $this;
    }

}
