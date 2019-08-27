<?php namespace eagle\modules\listing\helpers;

use common\api\ensogointerface\EnsogoProxyConnectHelper;
use eagle\modules\listing\service\Log;
use eagle\modules\listing\service\ensogo\Account;
use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\listing\models\EnsogoVariance;
use eagle\models\SaasEnsogoUser;

class EnsogoProxyHelper 
{

	static public $token;

	static private $retryTimes = 0;

	static public function setToken($token){
		self::$token = $token;
	}

	static public function call($action,$get=[],$post=[]){

        if(isset($get['site_id'])){
            $site_id = $get['site_id'];
        } else if(isset($post['site_id'])){
            $site_id = $post['site_id'];
        } else {
            $site_id = 0;
        }

		$response = EnsogoProxyConnectHelper::call_ENSOGO_api($action,array_merge([
			'access_token'=>self::$token,
			'lb_auth'=>self::getAuthCode(),
			'debug'=>'true'
		],$get,$post));
		if(!$response['success']){
			$msg = $response['message'];
		}else{
			$response = $response['proxyResponse'];
			if(!isset($response['success']) || !$response['success']){
				// echo 'fail';
				if(isset($response['message'])){
					if(is_array($response['message'])){
						$response['message'] = implode(',',$response['message']);
					}  
					$msg = $response['message'];
                    //拉去商品信息 时候 TOKEN失效
                    if(isset($response['httpCode']) && $response['httpCode']==401 && self::$retryTimes++ <=3){
                    	// error_log('site_id '.$site_id.' token error, will retry in 10s '.PHP_EOL,3,'/tmp/ensogo_token_retry.log');
                        sleep(10);//10S后重新获取TOKEN
                        $ensog_user = SaasEnsogoUser::findOne($site_id);
                        if($ensog_user !== false){
                            self::$token = $ensog_user->token;
                            return self::call($action,$get,$post);
                        }
                    }
					// Log::error('接口调用失败'.$response['message']);
				}else{
					$msg = '';
				}
			}
		}
		if(isset($msg)){
			if(!$msg){
				var_dump($response);
			}
			Log::error('接口调用失败:'.$msg);
		}
		return isset($msg) ? $msg : $response['data'];
	}

	static private function getAuthCode(){
		return '123';
		// return EnsogoProxyConnectHelper::call_ENSOGO_api($action,array_merge([
		// 	'access_token'=>self::$token
		// ],$get),$post)['proxyResponse']['data']['code'];
	}


	static public function getTokenByProduct(EnsogoProduct $product){
		// 获取site_id
		$site_id = $product->site_id;
		$account = Account::getAccountBySiteId($site_id);
		return $account->token;
	}


	static public function getTokenByVariance(EnsogoVariance $variance){
		// 获取product
		$product = EnsogoProduct::find()->where([
			'parent_sku'=>$variance->parent_sku
		])->one();
		return self::getTokenByProduct($product);
	}

}