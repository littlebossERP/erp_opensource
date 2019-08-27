<?php
namespace console\helpers;


use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\ResultHelper;

class AutoCheckHelper {
	
	/**
	 * 发送邮件
	 * @param string $sendto_email 接收邮箱
	 * @param string $subject 标题
	 * @param string $body 主体
	 * @param array $email 用来发送的邮箱(随机在其中选择一个)
	 *  array(
	 *  	array("email"=>"@xxx@@163.com","password" =>"@xxx@" ,"host"=>"smtp.163.com"),
	 array("email"=>"@xxx@@qq.com","password" =>"@xxx@" ,"host"=>"smtp.qq.com"),
	 *  )
	 * @return boolean
	 */
	public static function sendEmail($sendto_email,$subject,$body,$email=array()){
		// TODO add send mail info @xxx@
		$emailsArr=array(
			array("email"=>"@xxx@@163.com","password" =>"@xxx@" ,"host"=>"smtp.163.com"),
			array("email"=>"@xxx@@qq.com","password" =>"@xxx@" ,"host"=>"smtp.qq.com"),
		);
		if(!empty($email)){
			$emailsArr = $email;
		}
		
		$emailNum=count($emailsArr);
	
		$emailIndex=rand(1,1000) % $emailNum;
	
		$littlebossEmail=$emailsArr[$emailIndex]["email"];
		$littlebossEmailPW=$emailsArr[$emailIndex]["password"];
		$emailHost=$emailsArr[$emailIndex]["host"];
	
		$mail = new \PHPMailer();
		$mail->CharSet='UTF-8';
		$mail->IsSMTP();                            // 经smtp发送
		$mail->Host     = $emailHost;           	// SMTP 服务器
		$mail->SMTPAuth = true;                     // 打开SMTP 认证
		$mail->Username = $littlebossEmail;    		// 用户名
		$mail->Password = $littlebossEmailPW;		// 密码
		$mail->From     = $littlebossEmail;         // 发信人
		$mail->FromName = "小老板 ERP";        		// 发信人别名
		if(is_array($sendto_email)){
			foreach ($sendto_email as $oneMail){
				$mail->AddAddress($oneMail);
			}
		}else{
			$mail->AddAddress($sendto_email); 		// 收信人
		}
		
		$mail->WordWrap = 50;
// 		$mail->IsHTML(true);                            // 以html方式发送
// 		$mail->AltBody  =  "请使用HTML方式查看邮件。";
		// 邮件主题
// 		$mail->Subject = $subject;//'littleboss verify code: '.$authnum;
		//如果标题有中文，用下面这行
		$mail->Subject = "=?utf-8?B?" . base64_encode($subject) . "?=";
		//邮件内容
		$mail->Body = $body;
	
		$sendResult = $mail->send();
		return $sendResult;
	}

	public static function creatInstance($fullNamespaceHelper){
		$ary_element = explode('.', $fullNamespaceHelper);
		$fullHelperName = implode('\\', $ary_element);
		return $fullHelperName;
	}
	
	public static function dividFullMethodName($fullMethodName){
		$ary_element = explode('.', $fullMethodName);
		$methodName = array_pop($ary_element);
		$fullHelperName = implode('.', $ary_element);
		return array($fullHelperName, $methodName);
	}
	
	
	static $checkHosts = array(
			'美国Proxy'=>'198.11.178.150',//us
			'香港Proxy'=>'47.90.54.242',//hk
			'香港Proxy2'=>'47.89.38.83',//hk2 (php7)
			'美国Proxy2'=>'47.88.12.209',//us2 (apache)
			'新加坡Proxy'=>'47.88.150.185',//sg php7
	);
	
	// 定时自动检查 proxy 连接
	public static function checkAccessProxy(){
		$rtnMsg = "";
		$checkSuccess = true;
		foreach (self::$checkHosts as $key=>$host){
			// test access html
			$t1 = TimeUtil::getCurrentTimestampMS();
			$resultHtml = self::acccess('http://'.$host.'/test_access/1.html?t='.time());
			$t2 = TimeUtil::getCurrentTimestampMS();
			if(true == $resultHtml['success']){
				$rtnMsg .= $key.'链接html成功,耗时:'.($t2-$t1).'ms,测试获取内容:'.$resultHtml['response'];
			}else{
				$checkSuccess = false;
				$rtnMsg .= $key.$resultHtml['message'];
			}
			
			$rtnMsg .= "<br />";
			
			// test access php
			$t1 = TimeUtil::getCurrentTimestampMS();
			$resultPhp = self::acccess('http://'.$host.'/test_access/1.php?t='.time());
			$t2 = TimeUtil::getCurrentTimestampMS();
			if(true == $resultPhp['success']){
				$rtnMsg .= $key.'链接php成功,耗时:'.($t2-$t1).'ms,测试获取内容:'.$resultPhp['response'];
			}else{
				$checkSuccess = false;
				$rtnMsg .= $key.$resultPhp['message'];
			}
			
			$rtnMsg .= "<br />";
		}
		
		return array($checkSuccess,$rtnMsg);
	}
	
	public static function acccess ($url,$TIME_OUT=60){
		$rtn['success'] = true;  //跟proxy之间的网站是否ok
		$rtn['message'] = '';
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); //连接超时
	
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);
		//\yii::info("lazada proxy connect response:".print_r($response,true),"file");
			
		$curl_errno = curl_errno($handle);
		$curl_error = curl_error($handle);
		if ($curl_errno > 0) { // network error
			$rtn['message']="cURL Error $curl_errno : $curl_error";
			$rtn['success'] = false ;
			$rtn['state_code'] = $curl_errno;// 后面可以总结一下 无论是proxy还是本机的属于网络问题的 可以根据状态码重试 ， 目测出现$curl_errno的有可能要重试
			curl_close($handle);
			return $rtn;
		}
	
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		//echo $httpCode.$response."\n";
		if ($httpCode == '200' ){
			$rtn['state_code'] = 200;
			$rtn['response'] = $response;
		}else{ // network error
			$rtn['message'] = "Error respond code $httpCode from Proxy";
			$rtn['success'] = false ;
			$rtn['state_code'] = $httpCode;
		}
		curl_close($handle);
		return $rtn;
	}

   

}
