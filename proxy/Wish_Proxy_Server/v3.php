<?php 
require_once(dirname(__FILE__)."/WishServiceV2.php");
require_once(dirname(__FILE__)."/Utility.php"); 


class v3
{
	private $token;

	public function __construct(){
		$this->token = $_REQUEST['token'];
		WishService::setupToken(str_replace('@@@','=',$this->token));
	}

	/**
	 * 获取请求参数
	 * @return [type] [description]
	 */
	public function request($name=''){
		if(!$name){
			return $_REQUEST;
		}else{
			return $_REQUEST[$name];
		}
	}

	/**
	 * 调用参数
	 * @param  [type] $name  [description]
	 * @param  array  $param [description]
	 * @return [type]        [description]
	 */
	public function callApi($name,$param=[],$data=[],$debug=false){
		$param = array_merge([
			'access_token'=>WishService::$token
		],$param);
		// 记录接口调用时间
		$this->log("callApi[$name]");
		$this->log('param:'.var_export($param,true));
		$this->log('data:'.var_export($data,true));
		$result = WishService::call_WISH_api($name,$param,$data,180,$debug);
		$this->log('response:'.var_export($result,true));
		return $result;
	}

	public function log($str){
		date_default_timezone_set("Asia/Hong_Kong");
		error_log($str.PHP_EOL,3,'/tmp/wishapitest.log');
	}

	public function getProduct($data){
		$product = [];
		$productKeys = [
			'name','tags','description','sku','color','size','inventory','price','shipping','msrp','shipping_time','main_image','parent_sku','brand','landing_page_url','upc','extra_images'
		];
		if(!isset($data['extra_images'])){
			$_img = [];
			for ($i=1; $i<=10; $i++){
				if (isset($data['extra_image_'.$i]) && $data['extra_image_'.$i]){
					$_img[] = $data['extra_image_'.$i];
				}
			}
			$data['extra_images'] = implode('|', $_img);
		}
		foreach($data as $key=>&$val){
			// tags
			switch($key){
				case 'tags':
					$product[$key] = str_replace('，',',',$val);
					break;
				default:
					if(in_array($key, $productKeys)){
						$product[$key] = $val;
					}
					break;
			}
		}
		return $product;
	}

	public function getVariant($data,$parent_sku = ''){
		if(!isset($data['parent_sku']) && $parent_sku){
			$data['parent_sku'] = $parent_sku;
		}
		$variantKeys = [
			'parent_sku','sku','color','size','inventory','price','shipping','msrp','shipping_time','main_image'
		];
		$variant = [];
		foreach($data as $key=>&$val){
			switch($key){
				default:
					if(in_array($key, $variantKeys)){
						$variant[$key] = $val;
					}
					break;
			}
		}
		return $variant;
	}


}

error_reporting(E_ALL);
$result = [];
if( (!isset($_REQUEST['token']) || !$_REQUEST['token']) && $_REQUEST['token']!='refreshtoken' ){
	$result = [
		'code'=>401,
		'msg'=>'need token'
	];
}else{
	$v3 = new v3();
	$v3->log(date('Y-m-d H:i:s'));

	// 记录访问ip时间、参数等等。。。
	error_log(var_export($_REQUEST,true),3,'/tmp/wish_proxy_access.log');
	// 记录end
	if($v3->request('action')){
		$file = "api/".strtolower($v3->request('action')).".php";
		$result = include($file);
		if($v3->request('debug')){
			$result['debug'] = [
				'url'=>$_SERVER['REQUEST_URI'],
				'postData'=>$_POST,
				'getParams'=>$_GET
			];
		}
	}
	error_log(var_export($result,true),3,'/tmp/wish_proxy_response.log');
}


header("Content-type:text/json;charset=utf8");
echo json_encode($result);











