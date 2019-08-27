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
 * MarketplaceWebService_Model - base class for all model classes
 */ 
abstract class MarketplaceWebService_Model
{
    
    /** @var array */
    protected  $fields = array ();
          
    /**
     * Construct new model class
     * 
     * @param mixed $data - DOMElement or Associative Array to construct from. 
     */
    public function __construct($data = null)
    {
        if (!is_null($data)) {
            if ($this->isAssociativeArray($data)) {
                $this->fromAssociativeArray($data);
            } elseif ($this->isDOMElement($data)) {
                $this->fromDOMElement($data);
            } else {
                throw new Exception ("Unable to construct from provided data. 
                                Please be sure to pass associative array or DOMElement");
            }
            
        }
    }

    /**
     * Support for virtual properties getters. 
     * 
     * Virtual property call example:
     *  
     *   $action->Property
     *   
     * Direct getter(preferred): 
     * 
     *   $action->getProperty()      
     * 
     * @param string $propertyName name of the property
     */
    public function __get($propertyName)
    {
       $getter = "get$propertyName"; 
       return $this->$getter();
    }

    /**
     * Support for virtual properties setters. 
     * 
     * Virtual property call example:
     *  
     *   $action->Property  = 'ABC'
     *   
     * Direct setter (preferred):
     * 
     *   $action->setProperty('ABC')     
     * 
     * @param string $propertyName name of the property
     */
    public function __set($propertyName, $propertyValue)
    {
       $setter = "set$propertyName";
       $this->$setter($propertyValue);
       return $this;
    }

         
    /**
     * XML fragment representation of this object
     * Note, name of the root determined by caller 
     * This fragment returns inner fields representation only
     * @return string XML fragment for this object
     */
    protected function toXMLFragment() 
    {
        $xml = "";
        foreach ($this->fields as $fieldName => $field) {
            $fieldValue = $field['FieldValue'];
            if (!is_null($fieldValue)) {
                $fieldType = $field['FieldType'];
                if (is_array($fieldType)) {
                    if ($this->isComplexType($fieldType[0])) {
                        foreach ($fieldValue as $item) {
                            $xml .= "<$fieldName>";
                            $xml .= $item->toXMLFragment();
                            $xml .= "</$fieldName>";
                        }
                    } else {
                        foreach ($fieldValue as $item) {
                            $xml .= "<$fieldName>";
                            $xml .= $this->escapeXML($item);
                            $xml .= "</$fieldName>";
                        }
                    }
                } else {
                    if ($this->isComplexType($fieldType)) {
                        $xml .= "<$fieldName>";
                        $xml .= $fieldValue->toXMLFragment();
                        $xml .= "</$fieldName>";
                    } else {
                        $xml .= "<$fieldName>";
                        $xml .= $this->escapeXML($fieldValue);
                        $xml .= "</$fieldName>";
                    }
                }
            }
        }
        return $xml;
    }


    /**
     * Escape special XML characters
     * @return string with escaped XML characters
     */
    private function escapeXML($str) 
    {
        $from = array( "&", "<", ">", "'", "\""); 
        $to = array( "&amp;", "&lt;", "&gt;", "&#039;", "&quot;");
        return str_replace($from, $to, $str); 
    }


    
    /**
     * Construct from DOMElement 
     * 
     * This function iterates over object fields and queries XML 
     * for corresponding tag value. If query succeeds, value extracted 
     * from xml, and field value properly constructed based on field type. 
     *
     * Field types defined as arrays always constructed as arrays,
     * even if XML contains a single element - to make sure that
     * data structure is predictable, and no is_array checks are
     * required.
     * 
     * @param DOMElement $dom XML element to construct from
     */
    private function fromDOMElement(DOMElement $dom)
    {
        $xpath = new DOMXPath($dom->ownerDocument);
        $xpath->registerNamespace('a', 'http://mws.amazonaws.com/doc/2009-01-01/');
        
        foreach ($this->fields as $fieldName => $field) {
            $fieldType = $field['FieldType'];   
            if (is_array($fieldType)) {
                if ($this->isComplexType($fieldType[0])) {
                    $elements = $xpath->query("./a:$fieldName", $dom);
                    if ($elements->length >= 1) {
                        // require_once (str_replace('_', DIRECTORY_SEPARATOR, $fieldType[0]) . ".php");
                        foreach ($elements as $element) {
                            $this->fields[$fieldName]['FieldValue'][] = new $fieldType[0]($element);
                        }
                    } 
                } else {
                    $elements = $xpath->query("./a:$fieldName", $dom);
                    if ($elements->length >= 1) {
                        foreach ($elements as $element) {
                            $text = $xpath->query('./text()', $element);
                            $this->fields[$fieldName]['FieldValue'][] = $text->item(0)->data;
                        }
                    }  
                }
            } else {
                if ($this->isComplexType($fieldType)) {
                    $elements = $xpath->query("./a:$fieldName", $dom);
                    if ($elements->length == 1) {
                        // require_once (str_replace('_', DIRECTORY_SEPARATOR, $fieldType) . ".php");
                        $this->fields[$fieldName]['FieldValue'] = new $fieldType($elements->item(0));
                    }   
                } else {
                    $element = $xpath->query("./a:$fieldName/text()", $dom);
                    $data = null;
                    if ($element->length == 1) {
                    	switch($this->fields[$fieldName]['FieldType']) {
                    		case 'DateTime':
                    			$data = new DateTime($element->item(0)->data, 
                    				new DateTimeZone('UTC'));
                    			break;
                    		case 'bool':
                    			$value = $element->item(0)->data;
                    			$data = $value === 'true' ? true : false;
                    			break;
                    		default:
                    			$data = $element->item(0)->data;
                    			break;
                    	}
                      $this->fields[$fieldName]['FieldValue'] = $data;
                    }
                }
            }
        }
    }


    /**
     * Construct from Associative Array
     * 
     * 
     * @param array $array associative array to construct from
     */
    private function fromAssociativeArray(array $array)
    {
        foreach ($this->fields as $fieldName => $field) {
            $fieldType = $field['FieldType'];   
            if (is_array($fieldType)) {
                if ($this->isComplexType($fieldType[0])) {
                    if (array_key_exists($fieldName, $array)) { 
                        $elements = $array[$fieldName];
                        if (!$this->isNumericArray($elements)) {
                            $elements =  array($elements);    
                        }
                        if (count ($elements) >= 1) {
                            // require_once (str_replace('_', DIRECTORY_SEPARATOR, $fieldType[0]) . ".php");
                            foreach ($elements as $element) {
                                $this->fields[$fieldName]['FieldValue'][] = new $fieldType[0]($element);
                            }
                        }
                    } 
                } else {
                    if (array_key_exists($fieldName, $array)) {
                        $elements = $array[$fieldName];
                        if (!$this->isNumericArray($elements)) {
                            $elements =  array($elements);    
                            }
                        if (count ($elements) >= 1) {
                            foreach ($elements as $element) {
                                $this->fields[$fieldName]['FieldValue'][] = $element;
                            }
                        }  
                    }
                }
            } else {
                if ($this->isComplexType($fieldType)) {
                    if (array_key_exists($fieldName, $array)) {
                        // require_once (str_replace('_', DIRECTORY_SEPARATOR, $fieldType) . ".php");
                        $this->fields[$fieldName]['FieldValue'] = new $fieldType($array[$fieldName]);
                    }   
                } else {
                    if (array_key_exists($fieldName, $array)) {
                        $this->fields[$fieldName]['FieldValue'] = $array[$fieldName];
                    }
                }
            }
        }
    }



    /**
     * Determines if field is complex type
     * 
     * @param string $fieldType field type name
     */
    private function isComplexType ($fieldType) 
    {
        return preg_match('/^MarketplaceWebService_Model_/', $fieldType);
    }

   /**
    * Checks  whether passed variable is an associative array
    *
    * @param mixed $var
    * @return TRUE if passed variable is an associative array
    */
    private function isAssociativeArray($var) {
        return is_array($var) && array_keys($var) !== range(0, sizeof($var) - 1);
    }

   /**
    * Checks  whether passed variable is DOMElement
    *
    * @param mixed $var
    * @return TRUE if passed variable is DOMElement
    */
    private function isDOMElement($var) {
        return $var instanceof DOMElement;
    }

   /**
    * Checks  whether passed variable is numeric array
    *
    * @param mixed $var
    * @return TRUE if passed variable is an numeric array
    */
    protected function isNumericArray($var) {
        return is_array($var) && array_keys($var) === range(0, sizeof($var) - 1);
    }
}
