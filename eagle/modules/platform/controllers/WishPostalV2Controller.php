<?php
namespace eagle\modules\platform\controllers;

use Yii;
use common\helpers\Helper_Curl;
use eagle\modules\carrier\models\SysCarrierAccount;

class WishPostalV2Controller extends \eagle\components\Controller
{
    // TODO carrier dev account @XXX@
    static private $client_id = '@XXX@';
    static private $client_secret = '@XXX@';
    // 要到wish邮开发者后台 设置redirect_uri 为  https://您的erp网址/platform/wish-postal-v2/get-wish-postal-authorization-code
    static private $redirect_uri = 'https://您的erp网址/platform/wish-postal-v2/get-wish-postal-authorization-code';
	static private $domain;

    /**
     * 进入wish邮授权界面，并存在物流商ID到SESSION
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lrq		2016/09/01				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public function actionAuth()
    {

    	if (!empty($_REQUEST['wish_account_id']))
    	{
    	    // dzt20190924 客户绑定wish 返回获取不到session内容，发现绑定授权跳出的网站没有www，wish邮返回的回调有www，所以读取不到session。
    	    // 跟换成读redis或这个controller修改session域
    		Yii::$app->session['carrierid'] = $_REQUEST['wish_account_id'];
    	}

    	if( \Yii::$app->subdb->getCurrentPuid()>0 ){
			$url= "https://www.wishpost.cn/oauth_v3/authorize?response_type=code&client_id=".self::$client_id."&state=success&scope=user.order.write user.order.read user.label.read";
		}else{
			$url = "http://www.wishpost.cn/oauth/authorize?client_id=".self::$client_id;
		}
		//$url= "https://www.wishpost.cn/oauth_v3/authorize?response_type=code&client_id=".self::$client_id."&state=success&scope=user.order.write user.order.read user.label.read";

		$this->redirect($url);
    }
    
    /**
     * 获取访问令牌、刷新令牌，并保存到物流商认证参数
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lrq		2016/09/01				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionGetWishPostalAuthorizationCode()
    {
        if (!empty($_REQUEST['code']) )
        {
        	try
        	{
				if( \Yii::$app->subdb->getCurrentPuid()>0 ){
					$Basic= base64_encode(self::$client_id.':'.self::$client_secret);

					$header= array();
					$header[]= "Authorization:Basic {$Basic}";
					$header[]= "Content-Type:application/x-www-form-urlencoded";

					$Parameters= array();
					$Parameters['grant_type']= 'authorization_code';
					$Parameters['code']= $_REQUEST['code'];
					$Parameters['redirect_uri']= self::$redirect_uri;
					$Parameters['client_id']= self::$client_id;
					$Parameters['client_secret']= self::$client_secret;
					
					\Yii::info("actionGetWishPostalAuthorizationCode Parameters:".json_encode($Parameters), "file");
					$response = Helper_Curl::post('https://www.wishpost.cn/api/v3/access_token',http_build_query($Parameters),$header);
					\Yii::info("actionGetWishPostalAuthorizationCode response:".$response, "file");
					$result= json_decode( $response,true );
					if( isset( $result['code'] ) && $result['code']==0  ){
						$wish_account_id = Yii::$app->session['carrierid'];

						if(!empty($wish_account_id)) {
							$account = SysCarrierAccount::find()->where(['id'=>$wish_account_id])->one();
							$account->is_used = 1;

							$api_params = $account->api_params;
							$api_params['user_id'] = $result['user_id'];
							$api_params['access_token'] = $result['access_token'];
							$api_params['refresh_token'] = $result['refresh_token'];
							$api_params['expires_in'] = $result['access_token_expires_in'];
							$api_params['expiry_time'] = $result['access_token_expiry_time'];
							$account->api_params = $api_params;

							if($account->save())
							{
								return $this->render('successview',['title'=>'授权成功,token:'.$result['access_token']]);
							}
						}
						else{
							return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号！']);
						}
					}else{
						return $this->render('errorview',['title'=>'授权失败', 'error'=>'access_token接口返回失败']);
					}


				}else{

					$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
						'<root>'.
						'<client_id>'.self::$client_id.'</client_id>'.
						'<client_secret>'.self::$client_secret.'</client_secret>'.
						'<redirect_uri>'.self::$redirect_uri.'</redirect_uri>'.
						'<grant_type>authorization_code</grant_type>'.
						'<code>'.$_REQUEST['code'].'</code>'.
						'</root>';

					$header=array();
					$header[]='Content-Type:text/xml;charset=utf-8';

					$response = Helper_Curl::post('https://www.wishpost.cn/api/v2/oauth/access_token',$getorder_xml,$header);
					$xml = simplexml_load_string($response);//将xml转化为对象
					$xml = (array)$xml;

					/*$xml =
                     [
                         'status' => '0',
                         'timestamp' => '2016/09/01 17:53:29',
                         'user_id' => '56f99034aeed6390e41ae7c5',
                         'token_type' => 'access_token',
                         'access_token' => '265d3054e5a9446d9f7f2ba967aa746a',
                         'expires_in' => '2591980',
                         'expiry_time' => '1475315589',
                         'refresh_token' => '01e3cd9b578d4f3d97f8b909a66c4d20',
                     ];*/

					if( !empty($xml) && is_array($xml) && $xml['status'] == 0)
					{
						//取回session数据
						$wish_account_id = Yii::$app->session['carrierid'];
						//保存信息
						if(!empty($wish_account_id))
						{
							$account = SysCarrierAccount::find()->where(['id'=>$wish_account_id])->one();
							$account->is_used = 1;

							$api_params = $account->api_params;
							$api_params['user_id'] = $xml['user_id'];
							$api_params['access_token'] = $xml['access_token'];
							$api_params['refresh_token'] = $xml['refresh_token'];
							$api_params['expires_in'] = $xml['expires_in'];
							$api_params['expiry_time'] = $xml['expiry_time'];
							$account->api_params = $api_params;

							if($account->save())
							{
								return $this->render('successview',['title'=>'授权成功']);
							}
						}
						else
							return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号！']);
					}
					else
					{
						if(!empty($xml['error_message']))
						{
							$msg = self::gerErrInfo($xml['status']);
							$err = $msg == '' ? $xml['error_message'] : $msg;
							return $this->render('errorview',['title'=>'授权失败', 'error'=>$err]);
						}
						else
							return $this->render('errorview',['title'=>'授权失败', 'error'=>'wish邮返回授权信息有误！']);
					}


				}

        	}
        	catch(\Exception $e)
        	{
        		return $this->render('errorview',['title'=>'授权失败', 'error'=>$e->getMessage()]);
        	}
        }
    }
    
    /**
     * 返回可用的访问令牌
     *
     * @param $access_token 令牌
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lrq		2016/09/01				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public static function ReturnAccessToken($account_id)
    {
        try
        {
            //查询认证参数
            $account = SysCarrierAccount::find()->where(['id'=>$account_id])->one();
            if(!empty($account))
            {
                $api_params = $account->api_params;
                
                //检查访问令牌是否有效
                $ret = self::CheckAccessToken($api_params['access_token']);
                
                if($ret['status'] == 0)
                {
                    return self::getResult(0, $api_params['access_token'], '');
                }
                else 
                {
                    //尝试重新刷新访问令牌
                    $ret = self::RefreshAccessToken($api_params['refresh_token']);
                    
                    if($ret['status'] == 0)
                    {
                        //保存访问令牌
                        $api_params['user_id'] = $ret['data']['user_id'];
                        $api_params['access_token'] = $ret['data']['access_token'];
                        $api_params['expires_in'] = $ret['data']['expires_in'];
                        $api_params['expiry_time'] = $ret['data']['expiry_time'];
                        $account->api_params = $api_params;
                        $account->save();
                        
                    	return self::getResult(0, $ret['data']['access_token'], '');
                    }
                    else
                    {
                    	return self::getResult(1, '', $ret['msg']);
                    }
                }
            }
            else 
                return self::getResult(1, '', '物流商账号不存在！');
        }
        catch(\Exception $e)
        {
        	return self::getResult(1, '', $e->getMessage());
        }
    }
    
    /**
     * 检查访问令牌是否有效
     *
     * @param $access_token 令牌
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lrq		2016/09/01				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function CheckAccessToken($access_token)
    {
    	try
    	{
    		$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
    				'<root>'.
    				'<access_token>'.$access_token.'</access_token>'.
    				'</root>';

    		$header=array();
    		$header[]='Content-Type:text/xml;charset=utf-8';
    
    		$response = Helper_Curl::post('https://www.wishpost.cn/api/v2/auth_test',$getorder_xml,$header);
    
    		$xml = simplexml_load_string($response);//将xml转化为对象
    		$xml = (array)$xml;
    		
    		if($xml['status'] == 0)
    		{
    		    return self::getResult(0, '', '');
    		}
    		else 
    		{
    		    $msg = self::gerErrInfo($xml['status']);
    		    $err = $msg == '' ? $xml['error_message'] : $msg;
    		    return self::getResult(1, '', $err);
    		}
    	}
    	catch(\Exception $e)
    	{
    		return self::getResult(1, '', $e->getMessage());
    	}
    }

    /**
     * 刷新访问令牌
     *
     * @param $refresh_token 刷新令牌
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lrq		2016/09/01				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function RefreshAccessToken($refresh_token)
    {
    	try
    	{
			if( \Yii::$app->subdb->getCurrentPuid()>0 ){

				$Basic= base64_encode(self::$client_id.':'.self::$client_secret);

				$header= array();
				$header[]= "Authorization:Basic {$Basic}";
				$header[]= "Content-Type:application/x-www-form-urlencoded";

				$Parameters= array();
				$Parameters['grant_type']= 'authorization_code';
				$Parameters['refresh_token']= $refresh_token;


				$response = Helper_Curl::post('https://www.wishpost.cn/api/v3/access_token',http_build_query($Parameters),$header);
				$result= json_decode( $response,true );
				if( isset( $result['code'] ) && $result['code']==0  ){
					$wish_account_id = Yii::$app->session['carrierid'];
					if(!empty($wish_account_id)) {
						$account = SysCarrierAccount::find()->where(['id'=>$wish_account_id])->one();
						$account->is_used = 1;

						$api_params = $account->api_params;
						$api_params['user_id'] = $result['user_id'];
						$api_params['access_token'] = $result['access_token'];
						$api_params['refresh_token'] = $result['refresh_token'];
						$api_params['expires_in'] = $result['access_token_expires_in'];
						$api_params['expiry_time'] = $result['access_token_expiry_time'];
						$account->api_params = $api_params;

						if($account->save())
						{
							return $this->render('successview',['title'=>'授权成功']);
						}
					}
					else{
						return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号！']);
					}

				}else{

				}

			}else{
				$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
					'<root>'.
					'<client_id>'.self::$client_id.'</client_id>'.
					'<client_secret>'.self::$client_secret.'</client_secret>'.
					'<refresh_token>'.$refresh_token.'</refresh_token>'.
					'<grant_type>refresh_token</grant_type>'.
					'</root>';

				$header=array();
				$header[]='Content-Type:text/xml;charset=utf-8';

				$response = Helper_Curl::post('https://www.wishpost.cn/api/v2/oauth/refresh_token',$getorder_xml,$header);

				$xml = simplexml_load_string($response);//将xml转化为对象
				$xml = (array)$xml;

				if($xml['status'] == 0)
				{
					return self::getResult(0, $xml, '');
				}
				else
				{
					$msg = self::gerErrInfo($xml['status']);
					$err = $msg == '' ? $xml['error_message'] : $msg;
					return self::getResult(1, '', $err);
				}
			}

    	}
    	catch(\Exception $e)
    	{
    		return self::getResult(1, '', $e->getMessage());
    	}
    }
    
    private function getResult($status = 0, $data, $msg) {
    	return array('status' => $status, 'data' => $data, 'msg' => $msg);
    }
    
    private function gerErrInfo($code)
    {
    	$arr = 
    	[
        	1001 => '缺少参数',
        	1007 => '访问令牌已过期',
        	1008 => '访问令牌已被撤销',
        	1009 => '授权码已过期',
        	1010 => '授权代码已经被用来赎回一个令牌',
        	4000 => '访问令牌或授权代码不被识别，请重新授权',
        	9000 => '无法识别的错误',
        ];
    	
    	if(!empty($arr[$code]))
    	    return $arr[$code];
    	else 
    	    return '';
	}
	
	/**
	 * 获取v2模拟登陆url
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/01				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionGetUrl()
	{
	    if (!empty($_POST['wish_account_id']))
	    {
	    	return json_encode(['status'=>1, 'url'=>'']);
	    }
	    return json_encode(['status'=>1, 'url'=>'']);
	}
}
?>