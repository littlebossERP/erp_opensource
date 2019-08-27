<?php
namespace common\helpers;

use Iterator;
use ArrayAccess;
use Countable;
use ArrayObject;
use SimpleXMLElement;
use DOMNodeList;
/**
 * xml解析转换助手
 * @package helper
 */ 
class Helper_xml{
	public static function addCData(&$xmlObj,$cdata_text)
	{
		//2016-1-20 处理数据是数组的特殊情况
	  if(is_array($cdata_text)){
	  	$cdata_text = $cdata_text['0'];
	  }
	  $node= dom_import_simplexml($xmlObj); 
	  $no = $node->ownerDocument; 
	  $node->appendChild($no->createCDATASection($cdata_text));
	} 
    /**
	 * 重载 simplexml_load_string，去掉xml 中的非法字符
	 *
	 * @param string $string
	 * @return SimpleXmlElement
	 */
	static function simplexml_load_string($string){
		$string=preg_replace('/[\x00-\x08\x0b-\x0c\x0e-\x1f]/',' ',$string);
		return simplexml_load_string($string);
	}
    /**
     * 用 simplexml_load_string 解晰 xml 字符串
     * 变为 数组     
     */
    static function xmlparse($xmlString){
       if($xmlString instanceof SimpleXMLElement ){
          return self::simplexml2XA($xmlString);
       }elseif(is_string($xmlString)){
          return self::simplexml2XA(self::simplexml_load_string($xmlString));
       }elseif($xmlString instanceof DOMNodeList){
           return self::domxml2XA($xmlString);
       }
    }
    /**
     * SimpleXmlElement 对象转换为数组
     *	@return array
     */
    static function simplexml2a($o){
        if(is_object($o)){
            settype($o,'Array');
        }
        if(is_array($o)){
           if(count($o)>0){
                foreach($o as $k=>$a){
                    $o[$k]=self::simplexml2a($a);
                }
           }else{
                $o='';
           }
        }
        return $o;
    }
    /**
     *  SimpleXmlElement 转成 xmlArray
     *  @return xmlArray Object     
     */         
    static function simplexml2XA($o){
        if(!($o instanceof  SimpleXMLElement)) return $o;   
           $n =new xmlArray();
           if(count($o->children())>0){ 
    			foreach($o->children() as $k=>$a){
                    if(count($a->attributes())){
                        foreach($a->attributes() as $ak=>$av){
                            $n->setChildrenAttrible($k,$ak,(string)$av );
                        }
                    }
                    if(isset($n[$k])){
                        if(self::isArray($n[$k])&&isset($n[$k][0])){
                            $n[$k][]=self::simplexml2XA($a);
                        }else{
                            $t=$n[$k];
                            $n[$k]=new xmlArray(array($t,self::simplexml2XA($a)));          
                        }
                    }else{
                        $n[$k]=self::simplexml2XA($a);
                    }
    			}
           }else{
                $n=(string)$o;
           }
		return $n;
    }
    /**
     * [xmlToArr description]
     * @Author willage 2017-02-13T17:24:47+0800
     * @Editor willage 2017-02-13T17:24:47+0800
     * @param  [type]  $xml                     [description]
     * @param  boolean $root                    [description]
     * @return [type]                           [description]
     */
    public static function xmlToArr ($xml, $root = true) {
        if (!$xml->children()) {
            return (string) $xml;
        }
        $array = array();
        foreach ($xml->children() as $element => $node) {
            $totalElement = count($xml->{$element});
        if (!isset($array[$element])) {
            $array[$element] = "";
        }

        // Has attributes
        if ($attributes = $node->attributes()) {
            $data = array(
            'attributes' => array(),
            'value' => (count($node) > 0) ? self::xmlToArr($node, false) : (string) $node
            );
            foreach ($attributes as $attr => $value) {
            $data['attributes'][$attr] = (string) $value;
            }
            if ($totalElement > 1) {
            $array[$element][] = $data;
            } else {
            $array[$element] = $data;
            }
        // Just a value
        } else {
            if ($totalElement > 1) {
                $array[$element][] = self::xmlToArr($node, false);
            } else {
                $array[$element] = self::xmlToArr($node, false);
            }
            }
        }
        if ($root) {
            return array($xml->getName() => $array);
        } else {
            return $array;
        }
    }

    /**
     * 从数组生成 xml
     *  @ $arr 是数组与 simplexml 混合 
     *  以3个空格 区分 属性     
     *  $xmlstr=self::simpleArr2xml(
    array(
        //' '后 设置 GetItemTransactionsRequest 的属性,因为 数组键名,让人吃惊的强壮
        'GetItemTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array( 
            'IncludeContainingOrder'=>'true',
            'ItemID testK="testV"' =>array( // 设置 ItemId
                $itemid,
            ),
            'ListingEnhancement'=>array( // 转成 Xml 时 , 
                'Border',
                'CustomCode',
                'Featured',
                'Highlight'
            ),
        )
    ));
     */
    static public function simpleArr2xml($arr,$header=1){
        if($header){
            $str='<?xml version="1.0" encoding="utf-8" ?>'."\r\n";
        }else{
            $str='';
        }
        if(is_array($arr)){
            foreach($arr as $k=>$v){
                $n=$k;
                if(($b=strpos($k,' '))>0){
                    $f=substr($k,0,$b);
                }else{
                    $f=$k;
                }
                if(is_array($v)&&is_numeric(implode('',array_keys($v)))){ 
                // 就是为 Array 为适应 Xml 的可以同时有多个键 所做的 变通
                        foreach($v as $cv){
                            $str.="<$n>".self::simpleArr2xml($cv,0)."</$f>\r\n";
                        }
                }elseif ($v instanceof SimpleXMLElement ){
                    $xml = $v->asXML();
                    $xml =preg_replace('/\<\?xml(.*?)\?\>/is','',$xml);
                    $str.=$xml;
                }else{
                    $str.="<$n>".self::simpleArr2xml($v,0)."</$f>\r\n";
                }
            }
        }else{
            $str.=$arr;
        }
        return $str;
    }

    /*
     * 简易数组转xml方法（待改进）
     * add by rice
     * 2015-08-06
     */
    public static function array2xml($arr, $header=1, $key=''){
        if($header){
            $str='<?xml version="1.0" encoding="utf-8" ?>'."\r\n";
        }else{
            $str='';
        }

        if(is_array($arr)){
            foreach($arr as $k=>$v) {
                if(is_array($v)) {
                    if(is_int($k)) {
                        $str.="<{$key}>\r\n";
                    }else {
                        if(!isset($v[0])) {
                            $str.="<{$k}>\r\n";
                        }
                    }

                    $str.= self::simpleArr2xml($v, 0, $k);

                    if(is_int($k)) {
                        $str.="</{$key}>\r\n";
                    }else {
                        if(!isset($v[0])) {
                            $str.="</{$k}>\r\n";
                        }
                    }

                }else {
                    $str.= "<{$k}>{$v}</{$k}>\r\n";
                }
            }
        }else {
            $str.=$arr;
        }
        return $str;
    }


    /**
     * 对Dom document 进行解晰 . 
     * 接收      $DOMNodeList
     * 如:     
     * $response = $DOM -> getElementsByTagName('GetItemResponse');
     * Dom document  转成 xmlArray 
     *      
     *  @return xmlArray Object     
     */ 
    static function domxml2XA($DOMNodeList){
        $a=new xmlArray();
		if($DOMNodeList instanceof DOMNodeList){
        for($i=0;$i<$DOMNodeList->length;$i++){
                $DOMNode=$DOMNodeList->item($i);
                if($DOMNode instanceof DOMText){
						if($DOMNodeList->length==1){ //$DOMNode->nodeName=='#text'&&
                        $a=$DOMNode->nodeValue; //self::domxml2XA_saveItem($a,$DOMNode->nodeName,$DOMNode->nodeValue);
                    } // 忽略不正常的
                }elseif($DOMNode instanceof DOMElement){
                    if($DOMNode->hasChildNodes()&&$DOMNode->childNodes->length>0){ 
                        self::domxml2XA_saveItem($a,$DOMNode->nodeName,self::domxml2XA($DOMNode->childNodes));
                    }else{
                        self::domxml2XA_saveItem($a,$DOMNode->nodeName,$DOMNode->nodeValue);
                    }
                }else{ // 忽略其它格式
                    self::domxml2XA_saveItem($a,$DOMNode->nodeName,null);
                }
                // 节点属性
                if(self::isArray($a)&&$DOMNode->hasAttributes()&&$DOMNode->attributes->length>0){
                    for($j=0;$j<$DOMNode->attributes->length;$j++){
                        $DOMAttr=$DOMNode->attributes->item($j);
                        $a->setChildrenAttrible($DOMNode->nodeName,$DOMAttr->nodeName,$DOMAttr->nodeValue);
                }
            }
        }
		}elseif($DOMNodeList instanceof DOMElement){
			$DOMNode=$DOMNodeList;
			if($DOMNode->hasChildNodes()&&$DOMNode->childNodes->length>0){ 
				self::domxml2XA_saveItem($a,$DOMNode->nodeName,self::domxml2XA($DOMNode->childNodes));
			}else{
				self::domxml2XA_saveItem($a,$DOMNode->nodeName,$DOMNode->nodeValue);
			}
		}else{
			return null;
		}
        return $a;
    }
    /***
     * 附属 domxml2a , 存节点 . 
     */
    static function domxml2XA_saveItem(&$arr,$itemName,$item){
        if(isset($arr[$itemName])){
            if(self::isArray($arr[$itemName] )&&isset($arr[$itemName][0])){
                $arr[$itemName][]=$item;
            }else{
                $t=$arr[$itemName];
                $arr[$itemName]=new xmlArray(array($t,$item));                
            }
        }elseif($itemName=='#text'){
            $arr=$item;
        }else{
            $arr[$itemName]=$item;
        }
    }
    
	/**
	 * 判断是否使用了 xmlArray
	 * 在部分地方 代替 is_array 
	 */
	static function isArray($arr){
		//return is_array($arr)||($arr instanceof xmlArray);
		return ($arr instanceof ArrayObject);
	}
}

/***
 * 组织 xml 数据的对象
 */
class SuperXml implements Iterator, ArrayAccess, Countable {
	private $_value;
	private $_attributes;
    protected $_is_valid = false;
    
	function setAttributes($as){
		$this->_attributes=$as;
	}
	function setValue($v){
		$this->_value=$v;
	}
	function setAttribute($name,$value){
		$this->_attributes[$name]=$value;
	}
	function attribute($name=null){
		if (is_null($name)){
			return $this->_attributes;
		}
		return $this->_attributes[$name];
	}
	/**
	 * 从 SimpleXml 转换
	 *
	 * @param SimpleXml $o
	 * @return SuperXml
	 */
	static function fromSimpleXml($o){
  		$obj=new SuperXml();
  		if (count($o->attributes())){
  			foreach ($o->attributes() as $k => $v){
  				$obj->setAttribute($k,(string)$v);
  			}
  		}
  		if (count($o->children())){
  			$duplicate=array();
  			$duplicate_nodes=array();
  			foreach ($o->children() as $k=>$v){
  				@$duplicate[$k]++;
  			}
  			foreach ($o->children() as $k=>$v){
  				if ($duplicate[$k]>1){
  					$duplicate_nodes[$k][]=self::fromSimpleXml($v);
  				}else {
  					$obj[$k]=self::fromSimpleXml($v);
  				}
  			}
  			foreach ($duplicate_nodes as $k => $v){
  				$obj[$k]=$v;
  			}
  		}else {
  			$obj->setValue((string)$o);
	   		if (count($o->attributes())){
	   			foreach ($o->attributes() as $k => $v){
	   				$obj->setAttribute($k,(string)$v);
	   			}
	   		}
  		}
  		return $obj;
 	}
	function __toString(){
		return $this->_value;
	}
	function __set($name,$value){
		$this->_value[$name]=$value;
	}
	function __get($name){
		return $this->_value[$name];
	}
    /**
     * ArrayAccess 接口方法
     *
     * @param string $prop_name
     *
     * @return boolean
     */
    function offsetExists($prop_name)
    {
        return array_key_exists($prop_name, $this->_value);
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $prop_name
     * @param mixed $value
     */
    function offsetSet($prop_name, $value)
    {
        $this->_value[$prop_name] = $value;
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $prop_name
     *
     * @return boolean
     */
    function offsetGet($prop_name)
    {
        return $this->_value[$prop_name];
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $prop_name
     */
    function offsetUnset($prop_name)
    {
    	unset($this->_value[$prop_name]);
    }
    
    /**
     * 返回当前位置的对象，实现 Iterator 接口
     *
     * @return mixed
     */
    function current()
    {
        return current($this->_value);
    }

    /**
     * 返回遍历时的当前索引，实现 Iterator 接口
     *
     * @return mixed
     */
    function key()
    {
        return key($this->_value);
    }

    /**
     * 遍历下一个对象，实现 Iterator 接口
     */
    function next()
    {
        $this->_is_valid = (false !== next($this->_value));
    }

    /**
     * 重置遍历索引，实现 Iterator 接口
     */
    function rewind()
    {
        $this->_is_valid = (false !== reset($this->_value));
    }

    /**
     * 判断是否是调用了 rewind() 或 next() 之后获得的有效对象，实现 Iterator 接口
     *
     * @return boolean
     */
    function valid()
    {
        return $this->_is_valid;
    }
    
    /**
     * 返回对象总数，实现 Countable 接口
     *
     * @return int
     */
    function count()
    {
        return count($this->_value);
    }

}


/***
 *  数组对象 用于解析xml 
 */
class xmlArray extends ArrayObject
{
    private $_attribles;  // 
    
    /**
     * 从数组转
     * @param array  $array
     */
    public function __construct( $array = array())
    {
        foreach ($array as &$value)
            is_array($value) && $value = new self($value);
        parent::__construct($array);
    }
 
    /**
     * @param string  $index
     * @return mixed
     */
    public function __get($index)
    {
        return @$this->offsetGet($index);
    }
 
    /**
     * @param string  $index
     * @param mixed   $value
     */
    public function __set($index, $value)
    {
        @$this->offsetSet($index, $value);
    }
 
    /**
     * @param string  $index
     * @return boolean
     */
    public function __isset($index)
    {
        return @$this->offsetExists($index);
    }
 
    /**
     * @param string  $index
     */
    public function __unset($index)
    {
        $this->offsetUnset($index);
    }
 
    /**
     * 将数据信息转换为数组形式
     *
     * @return array
     */
    public function toArray()
    {
        $array = $this->getArrayCopy();
        foreach ($array as &$value)
            ($value instanceof self) && $value = $value->toArray();
        return $array;
    }
 
    /**
     * 将数据组转换为字符串形式
     *
     * @return array
     */
    public function __toString()
    {
        return var_export($this->toArray(), true);
    }
    /***
     *  设置属性
     */
    public function setChildrenAttrible($n,$k,$v=null){
        $this->_attribles[$n][$k]=$v;
    }
    /**
     * 取属性
     */         
    public function getChildrenAttrible($n=null,$k=null){
        if($n){
			if(isset($this->_attribles[$n])){
          if($k&&isset($this->_attribles[$n][$k]))
              return $this->_attribles[$n][$k];
          return $this->_attribles[$n]; 
			}
			return null;
        }
        return $this->_attribles;
    }
}