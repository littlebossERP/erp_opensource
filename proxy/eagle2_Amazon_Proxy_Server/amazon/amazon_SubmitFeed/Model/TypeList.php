<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     MarketplaceWebService
 *  @copyright   Copyright 2009 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2009-01-01
 */
/******************************************************************************* 

 *  Marketplace Web Service PHP5 Library
 *  Generated: Thu May 07 13:07:36 PDT 2009
 * 
 */

/**
 *  @see MarketplaceWebService_Model
 */
// require_once ('MarketplaceWebService/Model.php');  

    

/**
 * MarketplaceWebService_Model_TypeList
 * 
 * Properties:
 * <ul>
 * 
 * <li>Type: string</li>
 *
 * </ul>
 */ 
class MarketplaceWebService_Model_TypeList extends MarketplaceWebService_Model
{


    /**
     * Construct new MarketplaceWebService_Model_TypeList
     * 
     * @param mixed $data DOMElement or Associative Array to construct from. 
     * 
     * Valid properties:
     * <ul>
     * 
     * <li>Type: string</li>
     *
     * </ul>
     */
    public function __construct($data = null)
    {
        $this->fields = array (
        'Type' => array('FieldValue' => array(), 'FieldType' => array('string')),
        );
        parent::__construct($data);
    }

        /**
     * Gets the value of the Type .
     * 
     * @return array of string Type
     */
    public function getType() 
    {
        return $this->fields['Type']['FieldValue'];
    }

    /**
     * Sets the value of the Type.
     * 
     * @param string or an array of string Type
     * @return this instance
     */
    public function setType($type) 
    {
        if (!$this->isNumericArray($type)) {
            $type =  array ($type);    
        }
        $this->fields['Type']['FieldValue'] = $type;
        return $this;
    }
  

    /**
     * Sets single or multiple values of Type list via variable number of arguments. 
     * For example, to set the list with two elements, simply pass two values as arguments to this function
     * <code>withType($type1, $type2)</code>
     * 
     * @param string  $stringArgs one or more Type
     * @return MarketplaceWebService_Model_TypeList  instance
     */
    public function withType($stringArgs)
    {
        foreach (func_get_args() as $type) {
            $this->fields['Type']['FieldValue'][] = $type;
        }
        return $this;
    }  
      

    /**
     * Checks if Type list is non-empty
     * 
     * @return bool true if Type list is non-empty
     */
    public function isSetType()
    {
        return count ($this->fields['Type']['FieldValue']) > 0;
    }




}