<?php
namespace eagle\modules\amazon\apihelpers;

/**
 +---------------------------------------------------------------------------------------------
 * eagle的web代码(非amazon的后台程序)需要跟amazon proxy进行交互，这里的helper提供了通信的基础函数
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		xjq		2014/07/21				初始化
 +---------------------------------------------------------------------------------------------
 **/

use yii;
use yii\base\Exception;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\TimeUtil;

class AmazonSesHelper{
    // TODO proxy host
	public static $AMAZON_SES_PROXY_URL= 'http://localhost/amazon_aws_test_liang/ApiEntry.php';
	
	/**
	 +----------------------------------------------------------
	 * 为 AWS账号 SES 服务验证某个新的邮箱地址，发送一封验证邮件到该邮箱
	 * @param	string	$email_address	要验证的邮箱地址
	 * @return	array	验证邮件发送结果
	 * @author	lzhl	2017-3-7	初始化
	 +----------------------------------------------------------
	 **/
	public static function sendVerifyEmail($email_address){
		if(empty($email_address))
			return ['success'=>false,'message'=>'邮箱地址不能为空'];
		$action = 'VerifyEmailIdentity';
		$get_params = [];
		#todo 
		#需要做一个function，获取当前新使用用户绑定到哪个aws账号
		#aws绑定的邮箱数目有限制，超出限制可能需要申请多个
		$aws_account = '';
		$config = [
			'aws_account'=>'',
		];
		$get_params['config'] = json_encode($config);
		$params = [
			'email_address'=>$email_address,
		];
		$get_params['parms'] = json_encode($params);
		
		$rtn = self::call_amazon_ses_api($action,$get_params);
		
		if(!empty($rtn['success']) && !empty($rtn['response'])){
			$response = $rtn['response'];
			if(!empty($response['success']) && empty($response['message'])){
				return ['success'=>true,'message'=>'', ];
			}else{
				return ['success'=>false,'message'=>'proxy调用AWS接口失败：'.@$rtn['response']['message'] ];
			}
		}else{
			return ['success'=>false,'message'=>'链接proxy失败：'.@$rtn['message'] ];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 查询 AWS账号 SES 服务已经验证了的邮箱
	 * @param	string	$aws_account	aws账号，如果无多账号，则传入'default'
	 * @return	array	
	 * @author	lzhl	2017-3-7	初始化
	 +----------------------------------------------------------
	 **/
	public static function listIdentities($aws_account){
		if(empty($aws_account))
			return ['success'=>false,'message'=>'需要指定查询的账号','Identities'=>[] ];
		$action = 'ListVerifiedEmailAddresses';
		$get_params = [];
		$config = [
			'aws_account'=>$aws_account,
		];
		$get_params['config'] = json_encode($config);
		$params = [];
		$get_params['parms'] = json_encode($params);
	
		$rtn = self::call_amazon_ses_api($action,$get_params);
		
		if(!empty($rtn['success']) && !empty($rtn['response'])){
			$response = $rtn['response'];
			if(!empty($response['success']) && !empty($response['response'])){
				if(isset($response['response']['VerifiedEmailAddresses'])){
					return ['success'=>true,'message'=>'', 'Identities'=>$response['response']['VerifiedEmailAddresses'] ];
				}else{
					return ['success'=>false,'message'=>'AWS接口获取IdentitiesList失败', 'Identities'=>[] ];
				}
			}else{
				return ['success'=>false,'message'=>'proxy调用AWS接口失败：'.@$rtn['response']['message'],'Identities'=>[] ];
			}
		}else{
			return ['success'=>false,'message'=>'链接proxy失败：'.@$rtn['message'],'Identities'=>[] ];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 调用api,通过SES服务发送邮件
	 * @param	array	$mail_data	邮件内容
	 * @return	array
	 * @author	lzhl	2017-3-7	初始化
	 +----------------------------------------------------------
	 **/
	public static function sendEmailByAmazonSES($mail_data=[]){
		$err_msg = '';
		$perCheck = true;
		$tmp_mail_data = [];
		$post_params = [];
		if(!empty($mail_data['mail_from']))
			$tmp_mail_data['mail_from'] = $mail_data['mail_from'];
		else{
			$perCheck = false;
			$err_msg .= '发件人邮箱参数缺失；';
		}
		if(!empty($mail_data['mail_to']))
			$tmp_mail_data['mail_to'] = $mail_data['mail_to'];
		else{
			$perCheck = false;
			$err_msg .= '收件人邮箱参数缺失；';
		}
		if(!empty($mail_data['subject']))
			$tmp_mail_data['subject'] = $mail_data['subject'];
		else{
			$perCheck = false;
			$err_msg .= '邮件标题缺失；';
		}
		if(!empty($mail_data['body']))
			$tmp_mail_data['body'] = $mail_data['body'];
		else{
			$perCheck = false;
			$err_msg .= '邮件内容缺失；';
		}
		if(!$perCheck){
			return ['success'=>false,'message'=>$err_msg];
		}
		$post_params['mail_data'] = json_encode($tmp_mail_data);
		
		$action = 'SendEmail';
		$get_params = [];
		$params = [];
		
		#todo
		#需要做一个function，获取当前新使用用户绑定到哪个aws账号
		#aws绑定的邮箱数目可能有限制，超出限制可能需要申请多个
		$aws_account = '';
		$config = [
			'aws_account'=>$aws_account,
		];
		$get_params['config'] = json_encode($config);
		$get_params['parms'] = json_encode($params);
		
		$rtn = self::call_amazon_ses_api($action,$get_params,$post_params);
		
		if(!empty($rtn['success']) && !empty($rtn['response'])){
			$response = $rtn['response'];
			if(!empty($response['success'])){
				return ['success'=>true,'message'=>'','response'=>json_encode($response) ];
			}else{
				return ['success'=>false,'message'=>'proxy调用AWS接口失败：'.@$response['message'] ];
			}
		}else{
			return ['success'=>false,'message'=>'链接proxy失败：'.@$rtn['message'] ];
		}
	}
	
	
	public static function call_amazon_ses_api($action, $get_params = array(),  $post_params=array(), $TIME_OUT=120, $return_type='json' ){
		$url = self::$AMAZON_SES_PROXY_URL;
		$url .= "?action=$action";
		$rtn['success'] = true;  //跟proxy之间的网站是否ok
		$rtn['message'] = '';
	
		//	write_to_log("Line ".__LINE__." call_amazon_api reqParam:".json_encode($get_params),"debug",__FUNCTION__,basename(__FILE__));
		//	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
		foreach($get_params  as $key=>$value){
			$url .= "&$key=".urlencode(trim($value));
		}
		//print_r($url);
		try{
			$handle = curl_init($url);
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $TIME_OUT); //连接超时
		
		
			if (count($post_params)>0){
				curl_setopt($handle, CURLOPT_POST, true);
				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
			}
			//  output  header information
			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
		
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			if ($curl_errno > 0) { // network error
				$rtn['message']="cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				$rtn['response'] = "";
				curl_close($handle);
				return $rtn;
			}
		
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			//echo $httpCode.$response."\n";
			if ($httpCode == '200' ){
				if ($return_type == 'xml'){	$rtn['response'] = $response; }
				else $rtn['response'] = json_decode($response , true);
					
				if ($rtn['response']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
					//	   	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
					$rtn['success'] = false ;
				}
			}else{ // network error
				$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
				//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
				//	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
				$rtn['success'] = false ;
				$rtn['response'] = "";
			}
		
			curl_close($handle);
		}catch(\Exception $e) {
			$rtn['success'] = false ;
			$rtn['message'] = "连接proxy出现问题！请联系客服。";
		}
		return $rtn;
	}//end of call_amazon_ses_api by proxy
	
}//end of class
?>