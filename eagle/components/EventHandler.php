<?php
namespace eagle\components;


class EventHandler {
    //事件处理器-----登录成功
    // 需要在main.php中配置---- 'on afterLogin'=>['eagle\components\EventHandler','loginAfter']
    public static function loginAfter($event){
      	 $userObj=$event->identity;
    	 \Yii::info("EventHandler  loginAfter setcookie userName:".$userObj->user_name,"file");
    	 //lolo20151120 预防串号问题。 登录成功的话，设置username cookie
    	 setcookie("username",$userObj->user_name,time()+3600*24*200,"/" );
    	 return true;
    
    }
}