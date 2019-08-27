<?php namespace eagle\modules\util\helpers;

use eagle\modules\amazon\apihelpers\AmazonSesHelper;
use eagle\models\UserBase;
class MailHelper
{/*
EDM 账户：
xxx   密码 ：xxx  邮件量：10W 通道类型：国内会员通道
  (登陆平台https://app1.rspread.com/Login.aspx)
api接口地址：http://service1.rspread.com/
api帮助文档：http://developer.rspread.com/SpreadWS/SpreadWS.aspx

事务通知：
登录邮箱: 	xxx@qq.com
接口密钥: 	xxx


*/
	static $SiQiAccount='xxx@qq.com';
	static $SiQiPassword='xxx';
	
	static $SiQiEDMAccount='xxx';
	static $SiQiEDMPassword='xxx';
	
	static $sendgridProxy = '';
	
	// TODO add send mail info
	static function sendText($address,$title,$content){
		$mail = new \PHPMailer();
		$mail->CharSet='UTF-8';
		$mail->IsSMTP();                            // 经smtp发送  
		$mail->Host     = "smtp.163.com";           // SMTP 服务器  
		$mail->SMTPAuth = true;                     // 打开SMTP 认证  
		$mail->Username = "xxx@163.com";    // 用户名  
		$mail->Password = "xxx";// 密码  
		$mail->From     = "xxx@163.com";            // 发信人  
		$mail->FromName = "小老板 ERP";        // 发信人别名  
		$mail->AddAddress($address);                 // 收信人  
		$mail->WordWrap = 50;  
		// $mail->IsHTML(true);                            // 以html方式发送  
		$mail->AltBody  =  "请使用HTML方式查看邮件。";  
		$mail->Subject = $title;
		$mail->Body = $content;
		return $mail->send();
	}

	/**
	 * @name     sendOne
	 * @function send email one by one, Mbody can be html content
	 * @return
	 *   'Invalid Email Address'
	 *   'Information required'
	 *   'LoginEmail and Password do not match'
	 *   'Sent failed'
	 *   'Your email has submitted successfully and will by send out soon.'
	 *   'Sent success'
	 *   
	 */
	static function sendMailBySQ($fromEmail,$fromName,$toEmail,$Msubject,$Mbody, $actName=''){
		return self::sendEmailByAmazonSES($fromEmail, $fromName, $toEmail, $Msubject, $Mbody, $actName);
		//思齐弃用，改用amazon SES
		$sendParam = array(
					'LoginEmail'    => self::$SiQiAccount,
					'Password'      => self::$SiQiPassword,
					'CampaignName'  => empty( $actName ) ? '小老板系统邮件'.date( 'Y-n-j' ) : $actName,
					'From'          => $fromEmail,
					'FromName'      => $fromName,
					'To'            => $toEmail,
					'Subject'       => $Msubject,
					'Body'          => $Mbody  //body can be html content
			);
		try{
			//思齐每个账号都有一个对应的API Url ,比如:	http://service1.rspread.com
			//$Client=new \SoapClient("http://service.rspread.com/Service.asmx?WSDL");
			$Client=new \SoapClient("http://service1.rspread.com/Service.asmx?WSDL");
			

			$sendResult = $Client -> Send2( $sendParam );
			//$sendResult = $Client -> EmailSend( $sendParam );
		}catch(\Exception $e){
			$sendResult = $e->getMessage();
		}
			return $sendResult;

	}
	
	/**
	 * 通过Amazon SES 服务发送邮件
	 * @param string $fromEmail		发件人邮箱
	 * @param string $fromName		发件人名称
	 * @param string $toEmail		收件人邮箱
	 * @param string $Msubject		邮件标题
	 * @param string $Mbody			邮件内容
	 * @param string $actName		调用的app
	 * @return array
	 * @author	lzhl	2017-3-10	初始化
	 */
	static function sendEmailByAmazonSES($fromEmail,$fromName,$toEmail,$Msubject,$Mbody, $actName=''){
		//通过SES发邮件，不需要设置 fromName、actName 参数
		$mail_data = [];
		$mail_data['mail_from'] = $fromEmail;
		$mail_data['mail_to'] = $toEmail;
		$mail_data['subject'] = $Msubject;
		$mail_data['body'] = $Mbody;
		
		try{
			$sendResult = AmazonSesHelper::sendEmailByAmazonSES($mail_data);
		}catch(\Exception $e){
			$sendResult = ['success'=>false,'message'=>print_r($e->getMessage()) ];
		}
		return $sendResult;
	}
	
	static function sendEmailBySendGrid($fromEmail,$fromName,$toEmail,$toName,$Msubject,$Mbody, $actName=''){
		try{
			$TIME_OUT = 60;
			$url = self::$sendgridProxy;
				
			$rtn['success'] = true;  //跟proxy之间的网站是否ok
			$rtn['message'] = '';
			
			$handle = curl_init($url);
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); //连接超时
		
			$postInf = [];
			$postInfo['fromEmail'] = $fromEmail;
			$postInfo['fromName'] = $fromName;
			$postInfo['toEmail'] = $toEmail;
			$postInfo['toName'] = $toName;
			$postInfo['subject'] = base64_encode($Msubject);
			$postInfo['content'] = base64_encode($Mbody);
			$postInfo['isBasc64'] = 1;
			
			curl_setopt($handle, CURLOPT_POST, true);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $postInfo );
		
			$response = curl_exec($handle);
				
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			
			if ($curl_errno > 0) { // network error
				$rtn['message']="local:cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				$rtn['response'] = "";
				curl_close($handle);
				return $rtn;
			}
		
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			//echo $httpCode.$response."\n";
			if ($httpCode == '200' ){
				$rtn['response'] = json_decode($response , true);
		
				$rtn['state_code'] = 200;
				if ($rtn['response']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".print_r($response,true);
					$rtn['success'] = false ;
				}else{
					if (!empty($rtn['response']['errors'])){
						$rtn['message'] = "发送失败:".json_encode($rtn['response']['errors']);
						$rtn['success'] = false ;
					}
				}
		
		
			}else{ // network error
				$rtn['message'] = "local: Got error respond code $httpCode from Proxy";
				$rtn['success'] = false ;
			}
			curl_close($handle);
			return $rtn;
		
		}catch (\Exception $e){
			echo "sendgrid proxy connect exception:".print_r($e,true);
// 			\Yii::error("sendgrid proxy connect exception:".print_r($e,true),"file");
			$rtn['message'] = 'local:'.$e->getMessage();
			$rtn['success'] = false ;
			$rtn['response'] = "";
			return $rtn;
		}
	}
	
	 
}