<?php

namespace eagle\modules\ticket\controllers;

use yii\web\Controller;
use eagle\modules\ticket\helpers\TicketHelper;
use eagle\models\UserInfo;

class TicketController extends \eagle\components\Controller
{
    public function actionIndex()
    {
    	//little boss user init connect to OST
    	$LB_uid = \Yii::$app->user->id;
    	if(! TicketHelper::get_LB_User_OST_Uid($LB_uid)){
	    	$connect_err = TicketHelper::init_Connect_OST();
    	}
    	//
    	$status ='';
    	if(!empty($_GET['status']) && is_numeric($_GET['status'])){
    		$status = $_GET['status'];
    	}
    	$keyword = isset($_GET['keyword'])?$_GET['keyword']:'';
    	$pagesize=20;
    	if(!empty($_GET['per-page']) && is_numeric($_GET['per-page'])){
    		$pagesize = $_GET['per-page'];
    	}
    	
    	$rtn = TicketHelper::get_OST_Ticket_List($status,$keyword,$pagesize);
    	$errMsg = '';
    	$tickets = array();
    	if($rtn['success']){
    		$tickets = $rtn['data']['tickets'];
    	}else{
    		$errMsg = $rtn['message'];
    	}
    	$pagination=array();
    	if(!empty($rtn['data']['pagination']))
    		$pagination=$rtn['data']['pagination'];
    	//var_dump($tickets);
    	$topic = TicketHelper::get_OST_Topic();
		return $this->render('index',[
       			'tickets'=>$tickets,
       			'status'=>TicketHelper::get_OST_Status(),
				'topic'=>$topic,
				'pagination'=>$pagination,
				'connect_err'=>empty($connect_err)?'':$connect_err,
       		]);
	}
	public function actionCreateKey(){
		//$ip = '192.168.1.6';
		$ip = $_SERVER['REMOTE_ADDR'];
		$uid = \Yii::$app->user->id;
		$rtn = TicketHelper::getApiKey($ip,$uid);
		//var_dump($rtn);
	}
	
	/**
	 * 查看工单详情页面view层
	 * @return mixed
	 */
	public function actionView($id){
		$ticket = TicketHelper::get_OST_Ticket_Info($id);
		$threads = TicketHelper::get_OST_Ticket_Threads($id);
		$topic = TicketHelper::get_OST_Topic();
		
		$LB_uid = \Yii::$app->user->id;
		$OST_Uid = TicketHelper::get_LB_User_OST_Uid($LB_uid);
		$LB_User_Info = UserInfo::findOne($LB_uid);
		
		$contact_info['qq']=!empty($LB_User_Info->qq)?$LB_User_Info->qq:'';
		$contact_info['mobile']=!empty($LB_User_Info->cellphone)?$LB_User_Info->cellphone:'';
		$contact_info['phone']=!empty($LB_User_Info->telephone)?$LB_User_Info->telephone:'';
		
		return $this->renderAjax('_view',[
				'tt'=>'view',
				'ticket'=>$ticket,
				'threads'=>$threads,
				'topic'=>$topic,
				'ost_uid'=>$OST_Uid,
				'status'=>TicketHelper::get_OST_Status(),
				'contact_info'=>$contact_info
				]);
	}
	
	/**
	 * 新建ticket的win界面action
	 */
	public function actionCreate(){
	
		$topic = TicketHelper::get_OST_Topic();
		return $this->renderAjax('_create',[
				'topic'=>$topic,
				]);
	}
	
	/**
	# Fill in the data for the new ticket, this will likely come from $_POST.
	# NOTE: your variable names in osT are case sensiTive.
	# So when adding custom lists or fields make sure you use the same case
	# For examples on how to do that see Agency and Site below.
	$data = array(
		'name'      =>      'John Doe',  // from name aka User/Client Name
		'email'     =>      'john@gmail.com',  // from email aka User/Client Email
		'phone' 	=>		'1234567890',  // phone number aka User/Client Phone Number
		'subject'   =>      'Test API message',  // test subject, aka Issue Summary
		'message'   =>      'This is a test of the osTicket API',  // test ticket body, aka Issue Details.
		'ip'        =>      $_SERVER['REMOTE_ADDR'], // Should be IP address of the machine thats trying to open the ticket.
		'topicId'   =>      '1', //BUGFIX: there was a semi-colon instead of a comma // the help Topic that you want to use for the ticket
		//'Agency'  =>		'58', //this is an example of a custom list entry. This should be the number of the entry.
		//'Site'	=>		'Bermuda'; // this is an example of a custom text field.  You can push anything into here you want.
		'attachments' =>  array(
			array(
			// BUGFIX: there was a semi-colon instead of comma after base64
			'savedname.mp3' => 'data:audio/mpeg;base64,' . base64_encode(file_get_contents('/path/to/attachment.mp3')),
			/###
			 savedname.mp3 is the display name the file will have in the ticket
			
			 This example attaches a mp3 (voicemail) to the ticket but the method is the same
			 for other files just change the 'audio/mpeg' to another file type
			
			 '/path/to/attachment1.mp3' is the file path for the attachment
			
			 if you want to use a file from a stream or remote URL you can add a line in before declaring the $data array to do something like this
			
			 it does require the /path/to/ directory to be chmod 777 (writable)
			 ###/
				
			)
		)
	);
	*/
	/**
	 * 新建ticket至ost 的action
	 */
	public function actionCreateTicket(){
		//$ip = '192.168.1.6';//test
		$ip =  $_SERVER['REMOTE_ADDR'];//production
		$uid = \Yii::$app->user->id;
		$apiKey = TicketHelper::getApiKey($ip,$uid);
		if(empty($apiKey) || $apiKey=='false'){
			//have on api key or api key inValid
			exit(json_encode(array('success'=>false,'message'=>'工单系统apiKey验证失败，请联系客服')));
		}
		
		$user_ost_info = TicketHelper::get_LB_User_OST_info($uid);
		if(empty($user_ost_info)){
			//user info inValid
			exit(json_encode(array('success'=>false,'message'=>'用户在工单系统的信息有误，请联系客服')));
		}
		$topicId = empty($_POST['topicId'])?0:$_POST['topicId'];
		/* test data
		$data = array(
			'subject'   =>'Test API message',//$_POST['subject']
			'message'   =>'Test of the osTicket API -09/30',//$_POST['message']
			'ip'        =>$ip,//test
			'topicId'	=>$topicId,
			'attachments' => array(),//$_POST['attachments']
		);
		*/
		
		/*联系方式用lb的user信息
		$contact_info=array();
		if(!empty($_POST['contact_info']))
			$contact_info = $_POST['contact_info'];
		*/
		$data = array(
				'subject'   =>$_POST['subject'],
				'message'   =>$_POST['message'],
				'ip'        =>$ip,
				'topicId'	=>$topicId,
				'attachments' => array(),//$_POST['attachments']
				//'contact_info'=>$contact_info,
		);
		
		$rtn = TicketHelper::createTicket($user_ost_info,$apiKey,$data);
		exit(json_encode($rtn));
	}
	
	/**
	 * 保存回复view
	 */
	public function actionReply($ticket_id){
		$ticket = TicketHelper::get_OST_Ticket_Info($ticket_id);
		$threads = TicketHelper::get_OST_Ticket_Threads($ticket_id);
		$topic = TicketHelper::get_OST_Topic();
		
		$LB_uid = \Yii::$app->user->id;
		$OST_Uid = TicketHelper::get_LB_User_OST_Uid($LB_uid);
		//$contact_info = TicketHelper::get_Ticket_Addt_Contact_Info($ticket_id);
		
		return $this->renderAjax('_reply',[
				'ticket'=>$ticket,
				'threads'=>$threads,
				'topic'=>$topic,
				//'contact_info'=>$contact_info,
				]);

	}
	
	/**
	 * 保存回复action
	 */
	public function actionCreateReply(){
		$uid = \Yii::$app->user->id;
		
		$ticket_id = $_GET['ticket_id'];
		$message = $_POST['message'];
		/*联系方式用lb的user信息
		$contact_info=array();
		if(!empty($_POST['contact_info']))
			$contact_info = $_POST['contact_info'];
		*/
		$data = array(
			'message' 	 	=> $message,
			'attachments' 	=> array(),
			//'contact_info'=>$contact_info,
		);
		
		$rtn = TicketHelper::createReply($uid,$ticket_id,$data);
		exit(json_encode($rtn));
		
		/*
		#set timeout
		set_time_limit(30);
		#curl post
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $config['url']);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Cookie:OSTSESSID=0u2hn2sg4bdjat5aqp1veumpg7; PHPSESSID=0mdnm8vrev4uut5smhtdgtdt06'));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result=curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($code != 201)
			die('Unable to create ticket: '.$result);
		exit($result);
		*/
	}
	
	/**
	 * 用户撤销工单action
	 * @param int $ticket_id
	 */
	public function actionCancel($ticket_id){
		if(!is_numeric($ticket_id) || empty($ticket_id))
			exit(json_encode(array('success'=>false,'message'=>'工单信息有误，工单撤销失败：E001') ));
		$result=TicketHelper::cancelTicket($ticket_id);
		exit(json_encode($result));
	}
	
	/**
	 * 批量关闭(撤销)工单action
	 */
	public function actionBatchCancel(){
		$ticket_id_arr = array();
		if(isset($_POST['ids']))
			$ticket_id_arr = json_decode($_POST['ids'],true);
	
		if(empty($ticket_id_arr)){
			exit(json_encode(array('success'=>false,'message'=>'缺少工单号，工单关闭失败：E001B') ));
		}else{
			$result=TicketHelper::batchCancelTicket($ticket_id_arr);
			exit(json_encode($result));
		}
	}
	
	/**
	 * 用户reopen工单action
	 * @param int $ticket_id
	 */
	public function actionReopen($ticket_id){
		if(!is_numeric($ticket_id) || empty($ticket_id))
			exit(json_encode(array('success'=>false,'message'=>'工单信息有误，工单激活失败：E002') ));
		$result=TicketHelper::reopenTicket($ticket_id);
		exit(json_encode($result));
	}
	
	/**
	 * 批量开启(激活)工单action
	 */
	public function actionBatchReopen(){
		$ticket_id_arr = array();
		if(isset($_POST['ids']))
			$ticket_id_arr = json_decode($_POST['ids'],true);
	
		if(empty($ticket_id_arr)){
			exit(json_encode(array('success'=>false,'message'=>'缺少工单号，工单开启失败：E002B') ));
		}else{
			$result=TicketHelper::batchReopenTicket($ticket_id_arr);
			exit(json_encode($result));
		}
	}
	
	/**
	 * 用户删除工单action
	 * @param int $ticket_id
	 */
	public function actionDelete($ticket_id){
		if(!is_numeric($ticket_id) || empty($ticket_id))
			exit(json_encode(array('success'=>false,'message'=>'工单信息有误，工单删除失败：E003') ));
		$result=TicketHelper::deleteTicket($ticket_id);
		exit(json_encode($result));
	}
	
	/**
	 * 批量删除工单action
	 * @param int $ticket_id
	 */
	public function actionBatchDelete(){
		$ticket_id_arr = array();
		if(isset($_POST['ids']))
			$ticket_id_arr = json_decode($_POST['ids'],true);
		
		if(empty($ticket_id_arr)){
			exit(json_encode(array('success'=>false,'message'=>'缺少工单号，工单删除失败：E003B') ));
		}else{
			$result=TicketHelper::batchDeleteTicket($ticket_id_arr);
			exit(json_encode($result));
		}
	}
	
	/**
	 * little boss 用户首次使用工单系统时，初始化用户数据到ost数据库
	 */
	public function actionInitConnectOST(){
		$rtn = TicketHelper::init_Connect_OST();
	}


}
