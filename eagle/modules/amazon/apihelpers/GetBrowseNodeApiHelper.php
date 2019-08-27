<?php
namespace eagle\modules\amazon\apihelpers;

use yii\base\Exception;
use eagle\models\AmazonBrowsenode;

class GetBrowseNodeApiHelper
{
    /**
     * Your Amazon Access Key Id
     * @access private
     * @var string
     */
    private $public_key     = "";
    
    /**
     * Your Amazon Secret Access Key
     * @access private
     * @var string
     */
    private $private_key    = "";
    
    private $media_type     = "";
    
    private $region         = "";
    
    private $out_file_fp    = "";
    
    
    public function __construct($public, $private, $region) {
        $this->public_key   = $public;
        $this->private_key  = $private;
        $this->region       = $region;
    }        
    
    public function getNode($node)
    {
    	// amazon 建议 最多1 request / s
    	sleep(1);
        $parameters = array("Operation"  => "BrowseNodeLookup",
                                            "BrowseNodeId"   => $node,
                                            "ResponseGroup" => "BrowseNodeInfo");
                            
        $rtn = $this->aws_signed_request($parameters,  $this->public_key,  $this->private_key,  $this->region);

        if( false == $rtn['success'] ){
        	\Yii::error(["Amazon", __CLASS__,__FUNCTION__,"","get BrowseNodeId:".$node." error:".print_r($rtn,true) ] , 'file');
        	return false;
        }else{
        	return $rtn['response'];
        }
    }
    
    
    public function setMedia($media, $file = "") {
        $media_type = array("display", "csv");
        
        if(!in_array($media,$media_type)) {
            throw new Exception("Invalid Media Type");
            exit();
        }
        
        $this->media_type = $media;
        
        if($media == "csv") {
            $this->out_file_fp = fopen($file,'a+');
        }
    }
    
    
    private function writeOut($level, $name, $id, $parent) {
        if($this->media_type == "display") {
            $spaces = str_repeat( ' ', ( $level * 6 ) );
            echo $spaces . $level . ' : ' . $parent->BrowseNodeId . ' : ' . $parent->Name . ' : ' . $id . ' : ' . $name . "\n";   
        } elseif ($this->media_type == "csv") {
        	$spaces = '';
//         	for( $i = 0 ; $i < $level ; $i++ ){
//         		$spaces .= '"",';
//         	}
        	
            $csv_line = $spaces . '"' . $level . '","' . $parent->BrowseNodeId . '","' . $parent->Name . '","' . $id . '","' . $name . '"' . "\n";
            fputs($this->out_file_fp, $csv_line);
        } else {
            throw new Exception("Invalid Media Type");
            exit();
        }
    }
    
    
    
    public function getBrowseNodes($nodeValue, $level = 0)
    {
    	$tryCount = 0;
    	while (true) {
    		$tryCount++ ;
    		
    		try {
    			$result = $this->getNode($nodeValue);
    		}
    		catch(Exception $e) {
    			\Yii::error(["Amazon", __CLASS__,__FUNCTION__,"","Exception: get BrowseNodeId:".$nodeValue." error:". $e->getMessage() ] , 'file');
    			continue;
    		}
    		
    		if(!empty($result) && isset($result->BrowseNodes)){
    			break;
    		}
    		
    		if(!empty($result) && !empty($result->Error)){
    			\Yii::error(["Amazon", __CLASS__,__FUNCTION__,"","get BrowseNodeId:".$nodeValue." error:". $result->Error->Code .":". $result->Error->Message ] , 'file');
    			continue;
    		}
    		
    		if ( $tryCount > 50 ){
    			\Yii::error(["Amazon", __CLASS__,__FUNCTION__,"","get BrowseNodeId:".$nodeValue." error: try more than 50 times,stop it !!!" ] , 'file');
				break;
			}
			
			if(false === $result){
				continue;
			}
			
			continue;
		}	
		
		if(isset($result->BrowseNodes->BrowseNode->IsCategoryRoot) && '1'== (string)$result->BrowseNodes->BrowseNode->IsCategoryRoot){
			
			$parent = null;
			if(isset($result->BrowseNodes->BrowseNode->Ancestors)){
				$parent = $result->BrowseNodes->BrowseNode->Ancestors->BrowseNode;
			}
			
			$this->recordNodeInfo($parent , $result->BrowseNodes->BrowseNode);
		}
		
        if(isset($result->BrowseNodes->BrowseNode->Children) && count($result->BrowseNodes->BrowseNode->Children->BrowseNode) > 0) {
        	foreach($result->BrowseNodes->BrowseNode->Children->BrowseNode as $node) {
                $this->writeOut($level, $node->Name , $node->BrowseNodeId , $result->BrowseNodes->BrowseNode);
                
                $this->recordNodeInfo($result->BrowseNodes->BrowseNode , $node);
                
                $this->getBrowseNodes($node->BrowseNodeId, $level+1);
            }
        } else {
        	if (isset($result->BrowseNodes)) {
        		return $result->BrowseNodes->BrowseNode;    
        	}else {
        		return ;
        	}
                
        }
    }
    
    // 通过amazon api 获取$nodeValue的 Node Name
    public function getNodeName($nodeValue)
    {
        try {
            $result = $this->getNode($nodeValue);
        }
        catch(Exception $e) {
            echo $e->getMessage();
        }
        
        if(!isset($result->BrowseNodes->BrowseNode->Name)) return;
        
        return (string)$result->BrowseNodes->BrowseNode->Name;
    }
    
    // 通过amazon api 获取$nodeValue的 Parent Node
    public function getParentNode($nodeValue) {
        try {
            $result = $this->getNode($nodeValue);
        }
        catch(Exception $e) {
            echo $e->getMessage();
        }
        
        if(!isset($result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->BrowseNodeId)) return;
        
        $parent_node = array("id" => (string)$result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->BrowseNodeId,
                             "name" => (string)$result->BrowseNodes->BrowseNode->Ancestors->BrowseNode->Name);
        return $parent_node;
    }
    
    // 记录节点数据到数据库
    // 不同分支的叶节点存在相同BrowseNodeId
    public function recordNodeInfo($parentNode , $childNode) {
		if(!isset($childNode->BrowseNodeId)){
			\yii::error('save AmazonBrowsenode failed , child node info lost:'.print_r($childNode,true),'file');
			return false;
		}
		if($parentNode != null && !isset($parentNode->BrowseNodeId)){
			\yii::error('save AmazonBrowsenode failed , parent node info lost:'.print_r($parentNode,true),'file');
			return false;
		}
		
		$amazonBN = AmazonBrowsenode::findOne(['node_id'=>(string)$childNode->BrowseNodeId , 'parent'=>(string)$parentNode->BrowseNodeId ]);
		if(!empty($amazonBN)){
			\yii::trace('Same AmazonBrowsenode already exists:'.print_r($amazonBN->attributes,true).print_r($childNode,true),'file');
			return true;
		}
		
		$amazonBN = new AmazonBrowsenode();
// 		echo "<br>".(string)$childNode->BrowseNodeId .' and '.$childNode->BrowseNodeId;
		$amazonBN->node_id = (string)$childNode->BrowseNodeId;
		$amazonBN->name = (string)$childNode->Name;
		$amazonBN->parent = $parentNode != null ? (string)$parentNode->BrowseNodeId : 0;
		$amazonBN->region = $this->region;
		if(!$amazonBN->save()){
			\yii::error('save AmazonBrowsenode failed:'.print_r($amazonBN->errors,true).print_r($amazonBN->attributes,true)
					.'<br> parentNode:'.print_r($parentNode,true).'<br> childNode:'.print_r($childNode,true),'file');
			return false;
		}
		return true;
    }

   	// 与amazon 服务器通信的基础函数
    private function  aws_signed_request($params,$public_key,$private_key,$region) {
    	$method = "GET";
//     	$host = "ecs.amazonaws.".$region; // must be in small case
    	$host = "webservices.amazon.".$region; // must be in small case
    	
    	$uri = "/onca/xml";
    
    	$rtn['success'] = true; 
    	$rtn['message'] = '';
    	$rtn['response'] = "";
    	
    	$params["Service"]          = "AWSECommerceService";
    	$params["AWSAccessKeyId"]   = $public_key;
    	$params["AssociateTag"]     = 'YOUR-ASSOCIATES-ID-HERE';
    	$params["Timestamp"]        = gmdate("Y-m-d\TH:i:s\Z");
    	//     $params["Version"]          = "2009-03-31";
    	$params["Version"]          = "2013-08-01";// version 不同貌似结果都一样
    
    	/* The params need to be sorted by the key, as Amazon does this at
    	 their end and then generates the hash of the same. If the params
    	are not in order then the generated hash will be different thus
    	failing the authetication process.
    	*/
    	ksort($params);
    
    	$canonicalized_query = array();
    
    	foreach ($params as $param=>$value)
    	{
    		$param = str_replace("%7E", "~", rawurlencode($param));
    		$value = str_replace("%7E", "~", rawurlencode($value));
    		$canonicalized_query[] = $param."=".$value;
    	}
    
    	$canonicalized_query = implode("&", $canonicalized_query);
    
    	$string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
    
    	/* calculate the signature using HMAC with SHA256 and base64-encoding.
    	 The 'hash_hmac' function is only available from PHP 5 >= 5.1.2.
    	*/
    	$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
    
    	/* encode the signature for the request */
    	$signature = str_replace("%7E", "~", rawurlencode($signature));
    
    	/* create request */
    	$request = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;
    
    	/* I prefer using CURL */
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL,$request);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	
    	if(!defined('CURLE_OPERATION_TIMEDOUT')){
    		define('CURLE_OPERATION_TIMEDOUT',28);//curl_errno返回28,表示timeout
    	}
    
    	$response = curl_exec($ch);

    	$curl_errno = curl_errno($ch);
    	$curl_error = curl_error($ch);
    	if ($curl_errno > 0) { // network error
    		$rtn['message']="cURL Error $curl_errno : $curl_error";
    		//		SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
    		$rtn['success'] = false ;
    		$rtn['response'] = "";
    		curl_close($ch);
    		return $rtn;
    	}
    	
    	/* Check for 404 (file not found). */
    	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	//echo $httpCode.$response."\n";
    	if ($httpCode == '200' ){
    		$rtn['response'] = @simplexml_load_string($response);
    		if ($rtn['response'] === false){
    			// parse XML fails
    			$rtn['message'] = "Parse XML error , content:".print_r($response,true);
    			$rtn['success'] = false ;
    		}
    	}else{ // network error
    		$rtn['message'] = "Failed for $request , Got error respond code $httpCode from amazon server";
    		$rtn['success'] = false ;
    		$rtn['response'] = "";
    	}
    	
    	curl_close($ch);
    	return $rtn;
    	
    }

}

?>
