<?php
namespace eagle\modules\util\helpers;
use yii;
class SMSHelper{
	
    /**
     * 给指定的手机发送一个验证码。  该验证码5分钟有效。
     * 
     * @param $phoneNumber
     * @param $verifyCode
     * @return array(ret,message)
     * ret 是 true or false
     * message是错误信息
     */
	public static function sendVerifyCode($phoneNumber,$verifyCode){
	   
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, "http://api.weimi.cc/2/sms/send.html");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($ch, CURLOPT_POST, TRUE);
	    /*
	     短信接口二，触发类模板短信接口，可以设置动态参数变量。适合：验证码、订单短信等。
	     1、设定微米账号的接口UID和接口密码
	     2、传入目标手机号码，多个以“,”分隔，一次性调用最多100个号码，示例：139********,138********
	     3、短信模板cid，通过微米后台创建，由在线客服审核。必须设置好短信签名，签名规范：
	     1）、模板内容一定要带签名，签名放在模板内容的最前面；
	     2）、签名格式：【***】，签名内容为三个汉字以上（包括三个）；
	     3）、短信内容不允许双签名，即短信内容里只有一个“【】”。s
	     4、传入模板参数。短信模板内容示例：
	     【微米】您的验证码是：%P%，%P%分钟内有效。如非您本人操作，可忽略本消息。
	     传入两个参数：
	     p1：610912
	     p2：3
	     最终发送内容：
	     【微米】您的验证码是：123456，3分钟内有效。如非您本人操作，可忽略本消息。
	    */
	    // TODO add weimi send sms account info @XXX@
	    curl_setopt($ch, CURLOPT_POSTFIELDS, 'uid=@XXX@&pas=@XXX@&mob='.$phoneNumber.'&cid=@XXX@&p1='.$verifyCode.'&type=json');
	    $response = curl_exec( $ch );
	    curl_close( $ch );
	    $response = json_decode($response , true);
	    /*
	     注意：以上参数传入时不包括“<>”符号
	    */
	    if ($response["code"]==0) return array(true,"ok");
	    
	    return array(false,$response["msg"]);
	 
	}
}