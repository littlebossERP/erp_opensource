<?php namespace eagle\modules\listing\models;

use common\helpers\ProxyHelper;

class Proxy 
{
	public $token;
	protected $proxyHost = 'hk';

	static private $instace = [];

	function __construct($site_id){
		$this->site_id = $site_id;
		// 获取token
		$this->token = $this->getToken();
	}

	static function getInstance($site_id){
		if(!isset(self::$instace[$site_id])){
			// self::$instace[$site_id] = new self($site_id);
			$class = get_called_class();
			// var_dump(new $class);die;
			self::$instace[$site_id] = new $class($site_id);
		}
		return self::$instace[$site_id];
	}


	function call($action,$get=[],$post=[],$noToken = false){
	    ProxyHelper::$host = $this->proxyHost;
	    $times = 0;
	    do{
	    	$param = array_merge($noToken?[]:[
		    	'access_token'=>$this->token,
		    	'token'=>$this->token
		    ],[
		    	'action'=>$action,
		    	'lb_auth'=>123
			],$get);
		    $result = ProxyHelper::send($this->getPath(),$param,$post);
			if(!isset($result['httpCode']) || $result['httpCode']!==401){
				break;
			}
			if( $times++ > 3){
				throw new \Exception($result['message'], 401);
			}
			sleep(2);
			$this->token = $this->getToken(true);
	    }while(1);
		return $result;
	}


}