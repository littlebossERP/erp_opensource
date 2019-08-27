<?php
namespace eagle\modules\ticket\helpers;

use yii;

use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\TimeUtil;
use yii\grid\DataColumn;
use yii\data\Pagination;
use eagle\modules\util\helpers\SQLHelper;

/**
 +------------------------------------------------------------------------------
 * 
 +------------------------------------------------------------------------------
 * @category	
 * @package		/
 * @subpackage  
 * @author		
 +------------------------------------------------------------------------------
 */
define('CRYPT_IS_WINDOWS', !strncasecmp(PHP_OS, 'WIN', 3));
class TicketHelper {
	//generate apikey link:
	private static $TEST_APIKEY_URL = '';
	private static $PRODUCTION_APIKEY_URL = '';
	//create ticket link:
	private static $TEST_CREATE_TICKET_URL = '';
	private static $PRODUCTION_CREATE_TICKET_URL = '';
	
	//default passwd
	private static $default_passwd = '';
		
	private static $user_status = array(
		'0'=>'inactive',
		'1'=>'active',
	);
	private static $AccessibleTicket_Status = array(
			'open'=>'待回复',
			'closed'=>'已解决/已取消',//Closed tickets. Tickets will still be accessible on client and staff panels.
			//'Archived',//unaccessible,存档:Tickets only adminstratively available but no longer accessible on ticket queues and client panel.
			//'Deleted',//unaccessible,Tickets queued for deletion. Not accessible on ticket queues.
			//'replyed'=>'客服已回复',
			//'resolved'=>'已解决',
	);
	
	/**
	 * 根据查询条件，获取当前用户的所有符合条件的tickets
	 * @param 	int 	$status
	 * @param 	string	$keyword
	 * @return	array
	 */
	public static function get_OST_Ticket_List($status='',$keyword='',$pagesize=20){
		$result=array();
		$tickets=array();
		$LB_uid = \Yii::$app->user->id;
		$OST_Uid = self::get_LB_User_OST_Uid($LB_uid);
		
		if(!$OST_Uid){
			return array('success'=>false,['message'=>'用户在工单系统的用户初始化失败，请联系客服！']);
		}

		try{
			$connection =  \Yii::$app->ost_db;
			$sql = "select distinct o.* from ost_ticket o , ost_ticket_thread t where o.user_id = $OST_Uid and o.ticket_id=t.ticket_id ";
			
			if($status!=='' && is_numeric($status)){
				if((int)$status==1){//打开状态且没有回复=>"待回复"
					$sql .= " and o.status_id = 1  and o.isanswered=0 " ;
				}elseif((int)$status==6){//打开状态且有回复=>"已回复"
					$sql .= " and o.status_id = 1  and o.isanswered=1 " ;
				}
				else 
					$sql .= " and o.status_id = $status ";
			}else{//只显示'开启','关闭' 2种状态的工单给用户
				$closed_state_id=self::get_Closed_State_Id();
				$open_state_id = self::get_Open_State_Id();
				$sql .= " and o.status_id in ($closed_state_id,$open_state_id) ";
			}
			if($keyword!==''){
				$sql .= " and ( o.number like '%$keyword%'  or t.title like '%$keyword%' ) ";
			}
			$command = $connection->createCommand($sql);
			//pagination data
			$pagination = new Pagination([
					'pageSize' => $pagesize,
					'totalCount' =>count($command->queryAll()),
					'pageSizeLimit'=>[5,200],//每页显示条数范围
					]);
			$result['pagination'] = $pagination;
			
			$sql .= " limit ".$pagination->offset." ,".$pagination->limit;
			$connection =  \Yii::$app->ost_db;
			$command = $connection->createCommand($sql);
			$rows = $command->queryAll();
			
			if(empty($rows)){
				return $result = array('success'=>true, 'message'=>'', 'data'=>array('tickets'=>array(),'pagination'=>$pagination) );
			}
			
			foreach($rows as &$r){
				$ticket_id = $r['ticket_id'];
				$connection =  \Yii::$app->ost_db;
				$command = $connection->createCommand("select * from ost_ticket_thread where ticket_id=$ticket_id ");
				$threads = $command->queryAll();
				if(empty($threads)){
					$r['threads']=[];
					$tickets[]=$r;
				}else{
					foreach ($threads as $t){
						if(!empty($t['title'])){
							$r['title']=$t['title'];
							break;
						}
					}
					$r['threads']=$threads;
					$tickets[]=$r;
				}
			}
			$result['tickets']=$tickets;
			//var_dump($tickets);
			return array('success'=>true, 'message'=>'', 'data'=>$result );
			
		}catch (\Exception $e) {
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}
	
	/**
	 * 获取指定的ticket信息
	 * @return array()
	 */
	public static function get_OST_Ticket_Info($ticket_id){
		$result = array();
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select * from ost_ticket where ticket_id = $ticket_id ");
		$rows = $command->queryAll();
		if (count($rows)>0){
			$result = $rows[0];
		}
		return $result;
	}
	
	/**
	 * 获取ost系统所有的工单对应状态
	 * @return array()
	 */
	public static function get_OST_Status(){
		$result = array();
		$AccessibleTicket_Status = self::$AccessibleTicket_Status;
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select * from ost_ticket_status where 1 order by `sort` asc ");
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$name = strtolower($row['state']);
			if(isset($AccessibleTicket_Status[$name]))
				$result[$row['id']] = $row['name'];
		}
		return $result;
	}
	
	public static function get_Closed_State_Id(){
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select `id` from ost_ticket_status where `state`='closed' ");
		$id = $command->queryOne();
		if(empty($id))
			return 0;
		else 
			return (int)$id['id'];
	}
	public static function get_Open_State_Id(){
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select `id` from ost_ticket_status where `state`='open' ");
		$id = $command->queryOne();
		if(empty($id))
			return 0;
		else
			return (int)$id['id'];
	}
	
	/**
	 * 获取ost系统所有的topic
	 * @return array()
	 */
	public static function get_OST_Topic(){
		$result = array();
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select * from ost_help_topic where 1 ");
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$result[$row['topic_id']] = $row;
		}
		return $result;
	}
	
	/**
	 * 获取对应id工单的所有threads,包括原始和回复
	 * @param	int		$ticket_id
	 * @return	array(
	 * 				thread_id1=>array(thread1's data),
	 * 				thread_id2=>array(thread2's data),
	 * 				.
	 * 				.
	 * 			)
	 */
	public static function get_OST_Ticket_Threads($ticket_id){
		$result=array();
		
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select * from ost_ticket_thread where ticket_id = $ticket_id ORDER BY `id` ASC");
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$result[] = $row;
		}
		
		return $result;
	}

	/**
	 * Little Boss 用户初始化连接到OST系统时，自动创建一个其对应的APIKEY
	 * @param 	string	 $ip
	 * @param 	int		 $uid
	 * @return 	string	 key(string) or 'false'
	 */
	public static function getApiKey($ip,$uid){
		//if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
		//	$url = self::$PRODUCTION_APIKEY_URL;//production
		//else
		//	$url = self::$TEST_APIKEY_URL;//test
		

		$time = time();
		$apikey = strtoupper(md5($time.$ip.md5(self::randCode(16))));
		
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand("select * from ost_api_key where notes like '%##LB_User##".$uid."##LB_User##%' ");
		$keys = $command->queryAll();
		$exist = false;
		//print_r($keys);
		foreach ($keys as $keyInfo){
			if(!empty($keyInfo['apikey'])){
				$exist=true;
				return $keyInfo['apikey'];
			}
		}
		if(!$exist){
			$time =date('Y-m-d H:i:s',$time);
			$sql = "INSERT INTO `ost_api_key`
				(`isactive`, `ipaddr`, `apikey`, `can_create_tickets`, `can_exec_cron`,`notes`, `created`,`updated`) VALUES
				(1, '$ip' , '$apikey' ,1,1,'##LB_User##$uid##LB_User##','$time','$time')";
			$command = $connection->createCommand($sql);
			if($command->execute())
				return $apikey;
			else
				return 'false';
		}
		
		/*
		$data = array(
			'do'=>'add',
			'a'=>'add',
			'id'=>'',
			'isactive'=>1,
			'ipaddr'=>$ip,
			'can_create_tickets'=>1,
			'can_exec_cron'=>1,
			'notes'=>'##LB_User##'.$uid.'##LB_User##',
			'submit'=>'Add Key'
		);
		
		#set timeout
		set_time_limit(30);
		#curl post
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
		curl_setopt($ch,CURLOPT_USERAGENT,'osTicket API Client v1.7');
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,FALSE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		$result=curl_exec($ch);
		$code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($code != 201){
			$result = ['success'=>false,'message'=>'Unable to create ticket: '.$result];
		}else{
			$result = ['success'=>true,'message'=>$result];
		}
		return ($result);
		*/
	}

	/**
	 * function getApiKey 调用的加密function 1
	 */
	private static function randCode($len=8, $chars=false) {
		$chars = $chars ?: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_=';
	
		// Determine the number of bits we need
		$char_count = strlen($chars);
		$bits_per_char = ceil(log($char_count, 2));
		$bytes = ceil(4 * $len / floor(32 / $bits_per_char));
		// Pad to 4 byte boundary
		$bytes += (4 - ($bytes % 4)) % 4;
	
		// Fetch some random data blocks
		$data = self::random($bytes);
	
		$mask = (1 << $bits_per_char) - 1;
		$loops = (int) (32 / $bits_per_char);
		$output = '';
		$ints = unpack('V*', $data);
		foreach ($ints as $int) {
			for ($i = $loops; $i > 0; $i--) {
				$output .= $chars[($int & $mask) % $char_count];
				$int >>= $bits_per_char;
			}
		}
		return substr($output, 0, $len);
	}
	
	/**
	 * function getApiKey 调用的加密function 2
	 */
	private static function random($len) {
	
		if(CRYPT_IS_WINDOWS) {
			if (function_exists('openssl_random_pseudo_bytes')
			&& version_compare(PHP_VERSION, '5.3.4', '>='))
				return openssl_random_pseudo_bytes($len);
	
			// Looks like mcrypt_create_iv with MCRYPT_DEV_RANDOM is still
			// unreliable on 5.3.6:
			// https://bugs.php.net/bug.php?id=52523
			if (function_exists('mcrypt_create_iv')
			&& version_compare(PHP_VERSION, '5.3.7', '>='))
				return mcrypt_create_iv($len);
	
		} else {
	
			if (function_exists('openssl_random_pseudo_bytes'))
				return openssl_random_pseudo_bytes($len);
	
			static $fp = null;
			if ($fp == null)
				$fp = @fopen('/dev/urandom', 'rb');
	
			if ($fp)
				return fread($fp, $len);
	
			if (function_exists('mcrypt_create_iv'))
				return mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
		}
		/*
		$seed = session_id().microtime().getmypid();
		$key = pack('H*', sha1($seed . 'A'));
		$iv = pack('H*', sha1($seed . 'C'));
		$crypto = new Crypt_AES(CRYPT_AES_MODE_CTR);
		$crypto->setKey($key);
		$crypto->setIV($iv);
		$crypto->enableContinuousBuffer(); //Sliding iv.
		$start = mt_rand(5, PHP_INT_MAX);
		$output ='';
		for($i=$start; strlen($output)<$len; $i++)
			$output.= $crypto->encrypt($i);
	
			return substr($output, 0, $len);
		*/
	}
	
	/**
	 * 根据当前登录的用户，获取改用户在ost系统的对应用户信息
	 * @param		int		$LB_uid		little boss系统uid
	 * @return		array(
	 * 					'id'=>,
	 *					'default_email_id'=>,
	 *					'org_id'=>,
	 *					'status'=>,
	 *					'name'=>,
	 *					'created'=>,
	 *					'updated'=>,
	 *					'eagle_uid'=>,
	 *					'eagle_user_name'=>,
	 *					'eagle_account_email'=>,
	 *				)
	 */
	public static function get_LB_User_OST_info($LB_uid){
		$data = array();
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
				"select * from ost_user , ost_user_eagle_mapping 
				where ost_user.id = ost_user_eagle_mapping.ost_user_id and 
				ost_user_eagle_mapping.eagle_uid = $LB_uid");
		$users = $command->queryAll();
		if(empty($users)){
			return $data;
		}else{
			$data['id'] = $users[0]['id'];
			$data['default_email_id'] = $users[0]['default_email_id'];
			$data['org_id'] = $users[0]['org_id'];
			$data['status'] = $users[0]['status'];
			$data['name'] = $users[0]['name'];
			$data['created'] = $users[0]['created'];
			$data['updated'] = $users[0]['updated'];
			$data['eagle_uid'] = $users[0]['eagle_uid'];
			$data['eagle_user_name'] = $users[0]['eagle_user_name'];
			$data['eagle_account_email'] = $users[0]['eagle_account_email'];
		}
		
		if(!empty($data['id'])){
			$command = $connection->createCommand(
				"select * from ost_user_email 
				where id = ".$data['default_email_id']." ");
			$default_email = $command->queryAll();
			if(!empty($default_email)){
				$data['default_email'] = $default_email[0]['address'];
			}
		}
		return $data;
	}
	
	/**
	 * 根据当前登录的用户，获取改用户在ost系统的对应用户id
	 * @param 	int		$LB_uid
	 * @return 	int		$OST_uid (0:have no this user)
	 */
	public static function get_LB_User_OST_Uid($LB_uid){
		$OST_uid = 0;
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
			"select ost_user.id from ost_user , ost_user_eagle_mapping
			where ost_user.id = ost_user_eagle_mapping.ost_user_id and
			ost_user_eagle_mapping.eagle_uid = $LB_uid");
		$users = $command->queryAll();
		if(!empty($users)){
			$OST_uid = $users[0]['id'];
		}
		return $OST_uid;
	}
	
	/**
	 * 获取ost staff 人员信息
	 * @param int $staff_id
	 */
	public static function get_OST_Staff_Info_By_Id($staff_id){
		$info = array();
		if(!is_numeric($staff_id))
			return $info;
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
				"SELECT * FROM `ost_staff` 
				WHERE staff_id = $staff_id");
		$staff = $command->queryOne();
		if(!empty($staff)){
			$info = $staff;
		}
		return $info;
	}
	
	/**
	 * 获取工单的开始处理时间(已第一个staff人员的回复作为开始时间)
	 * @param int $ticket_id
	 */
	public static function get_Ticket_Start_Duedate($ticket_id){
		$start_duedate= '';
		if(!is_numeric($ticket_id))
			return $start_duedate;
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
				"SELECT `created` FROM `ost_ticket_thread`
				WHERE `ticket_id` = $ticket_id AND `thread_type`='R' ORDER BY `id` ASC");
		$created = $command->queryOne();
		if(!empty($created)){
			$start_duedate = $created['created'];
		}
		return $start_duedate;
	}
	
	/**
	 * 初始化little boss 用户连接到OST系统，自动创建OST用户信息
	 * @return string
	 */
	public static function init_Connect_OST(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$uid = \Yii::$app->user->id;
		$email = \Yii::$app->user->identity->getEmail();
		$name = \Yii::$app->user->identity->getFullName();
		if(empty($name))
			$name=$email;
		$err = '';
		if (!preg_match('/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/',$email)){
			$err = '你的注册邮箱不是一个有效邮箱地址';
		}
		try{
			$connection =  \Yii::$app->ost_db;
			$command = $connection->createCommand(
					"select * from ost_user , ost_user_eagle_mapping 
					where ost_user.id = ost_user_eagle_mapping.ost_user_id and 
					ost_user_eagle_mapping.eagle_uid = $uid");
			$users = $command->queryAll();
			//var_dump($user);
			if(count($users)==0){//have no this eagle user account
				$transaction = Yii::$app->get('ost_db')->beginTransaction ();
				$go_on = true;
				$connection =  \Yii::$app->ost_db;
				$time =date('Y-m-d H:i:s');
				$sql = "INSERT INTO `ost_user`
					(`org_id`, `default_email_id`, `status`, `name`, `created`, `updated`) VALUES
					(0,0,0,:name,'$time','$time')";
				$command = $connection->createCommand($sql);
				$command->bindValue(":name", $name, \PDO::PARAM_STR);
				if($command->execute())
					$id = \Yii::$app->ost_db->getLastInsertID();
				else
					$id=false;
				if($id){
					$connection =  \Yii::$app->ost_db;
					$passwd = self::$default_passwd;//default passwd = 123456
					$sql = "INSERT INTO `ost_user_account`
						( `user_id`, `status`, `timezone_id`, `dst`,  `username`, `passwd`, `registered`) VALUES
						($id,1,8,1,NULL,:passwd,'$time')";
					$command = $connection->createCommand($sql);
					$command->bindValue(":passwd", $passwd, \PDO::PARAM_STR);
					if(!$command->execute())
						$go_on = false;
				}
				if($go_on){
					$connection =  \Yii::$app->ost_db;
					$sql = "INSERT INTO `ost_user_email`(`user_id`, `address`) VALUES ($id,:email)";
					$command = $connection->createCommand($sql);
					$command->bindValue(":email", $email, \PDO::PARAM_STR);
					if($command->execute())
						$default_email_id = \Yii::$app->ost_db->getLastInsertID();
					else{
						$default_email_id=false;
						$go_on = false;
					}
					if($default_email_id){
						$connection =  \Yii::$app->ost_db;
						$sql = "UPDATE `ost_user` SET `default_email_id`=$default_email_id  WHERE `id`=$id";
						$command = $connection->createCommand($sql);
						if(!$command->execute())
							$go_on = false;
					}
				}
				if($go_on){
					$connection =  \Yii::$app->ost_db;
					$sql = "INSERT INTO `ost_user_eagle_mapping`
						(`eagle_uid`, `ost_user_id`, `eagle_user_name`, `eagle_account_email`, `eagle_user_puid`) VALUES
						($uid,$id,:name,:email,$puid)";
					$command = $connection->createCommand($sql);
					$command->bindValue(":email", $email, \PDO::PARAM_STR);
					$command->bindValue(":name", $name, \PDO::PARAM_STR);
					if(!$command->execute())
						$go_on = false;
				}
				if($go_on){
					$transaction->commit();
				}else
					$transaction->rollBack();
			}
			else{
				$user = $users[0];
				$ost_uid = $user['id'];
				$default_email_id = $user['default_email_id'];
				if(!empty($ost_uid) && !empty($default_email_id)){
					$connection =  \Yii::$app->ost_db;
					$command = $connection->createCommand(
						"select * from ost_user_email 
						where user_id = $ost_uid and id = $default_email_id");
					$query_email = $command->queryAll();
					if(!empty($query_email)){
						$old_mail_addr = $query_email[0]['address'];
						if($old_mail_addr !== $email){
							$connection =  \Yii::$app->ost_db;
							$sql = "UPDATE `ost_user_email` SET `address`=:email  where user_id = $ost_uid and id = $default_email_id";
							$command = $connection->createCommand($sql);
							$command->bindValue(":email", $email, \PDO::PARAM_STR);
							if(!$command->execute()){
								
							}
						}
					}
				}
			}
		}catch (\Exception $e) {
			echo $e->getMessage();
		}

		return $err;
	}
	
	/**
	 * 根据uid获得该用户最近一条ticket
	 * @param 	int 	$uid
	 * @return 	int		ticket_id(when no ticket,return 0)
	 */
	public static function get_Last_Ticket_Id_By_user($uid){
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
			"SELECT `ticket_id` FROM `ost_ticket`
			WHERE `user_id`=$uid 
			ORDER BY `ticket_id` DESC ");
		$last_ticket = $command->queryOne();
		if(!empty($last_ticket)){
			return $last_ticket;
		}else 
			return 0;
	}
	
	/**
	 * 保存工单用户的自定义额外联系方式
	 * @param 	int		 $ticket_id
	 * @param 	array	 $contact_info
	 */
	public static function save_Ticket_Addt_Contact_Info($ticket_id, $uid, $contact_info){
		//初始化数据
		$qq='';
		$msn='';
		$phone='';
		$mobile='';
		if(!empty($contact_info['qq']))
			$qq=$contact_info['qq'];
		if(!empty($contact_info['msn']))
			$qq=$contact_info['msn'];
		if(!empty($contact_info['phone']))
			$qq=$contact_info['phone'];
		if(!empty($contact_info['mobile']))
			$qq=$contact_info['mobile'];
		echo "<br>save_Ticket_Addt_Contact_Info 1 <br>";
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
			"SELECT * FROM `ost_ticket_contact_info`
			WHERE `ticket_id`=$ticket_id ");
		$count = $command->queryOne();
		try{
			if(!empty($count)){
				//if has this ticket record ,update it
				$connection =  \Yii::$app->ost_db;
				$command = $connection->createCommand(
					"UPDATE `ost_ticket_contact_info` SET 
					`qq`=:qq,`msn`=:msn,`phone`=:phone,`mobile`=:mobile 
					WHERE `ticket_id`=$ticket_id ");
				$command->bindValue(":qq", $qq, \PDO::PARAM_STR);
				$command->bindValue(":msn", $msn, \PDO::PARAM_STR);
				$command->bindValue(":phone", $phone, \PDO::PARAM_STR);
				$command->bindValue(":mobile", $mobile, \PDO::PARAM_STR);
				$command->execute();
			}else{
				//if haven't this ticket record ,insert one
				$connection =  \Yii::$app->ost_db;
				$command = $connection->createCommand(
					"INSERT INTO `ost_ticket_contact_info`
					(`ticket_id`, `uid`, `qq`, `msn`, `phone`, `mobile`) VALUES 
					($ticket_id,$uid,:qq,:msn,:phone,:mobile)");
				$command->bindValue(":qq", $qq, \PDO::PARAM_STR);
				$command->bindValue(":msn", $msn, \PDO::PARAM_STR);
				$command->bindValue(":phone", $phone, \PDO::PARAM_STR);
				$command->bindValue(":mobile", $mobile, \PDO::PARAM_STR);
				$command->execute();
			}
		}catch (\Exception $e) {
			echo $e->getMessage();
		}
	}
	
	/**
	 * 获取指定id的ticket的客户额外联系方式
	 * @param int $ticket_id
	 */
	public static function get_Ticket_Addt_Contact_Info($ticket_id){
		$contact_info=array();
		$connection =  \Yii::$app->ost_db;
		$command = $connection->createCommand(
			"SELECT * FROM `ost_ticket_contact_info`
			WHERE `ticket_id`=$ticket_id ");
		$contact_info = $command->queryOne();
		return $contact_info;
	}
	
	/**
	 * 创建ticket
	 * @param		array		$user
	 * @param 		string		$apiKey
	 * @param 		array		$data
	 * @return		array		['success'=>,'message'=>]
	 */
	public static function createTicket($user,$apiKey,$data){
		$result = array();
		$result['success']=true;
		$result['message']='';
		
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production')
			$config['url'] = self::$PRODUCTION_CREATE_TICKET_URL;
		else 
			$config['url'] = self::$TEST_CREATE_TICKET_URL;
		$config['key'] = $apiKey;
		/*联系方式用lb的user信息
		$contact_info = $data['contact_info'];
		unset($data['contact_info']);
		*/
		
		//var_dump($apiKey);
		if(!empty($user['name']) && !empty($user['default_email']) && !empty($user['eagle_uid'])){
			//补全数据
			$data['name'] = $user['name'];
			$data['email'] = $user['default_email'];
			//var_dump($data,true);
			try{
				set_time_limit(30);
				#curl post
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $config['url'].'?LB_uid='.$user['eagle_uid']);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.7');
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Expect:', 'X-API-Key: '.$config['key']));
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				$response=curl_exec($ch);
				$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($code != 201){
					$result['success'] = false;
					$result['message'] = '创建工单失败: '.$response;
				}
				
				/*/*联系方式用lb的user信息
				$ticket_id = self::get_Last_Ticket_Id_By_user($user['ost_user_id']);
				self::save_Ticket_Addt_Contact_Info($ticket_id, $user['ost_user_id'],$contact_info);
				*/
			}catch (\Exception $e) {
				return array('success'=>false,'message'=>$e->getMessage());
			}
		}else{
			return array('success'=>false,'message'=>'工单系统用户名称或邮箱缺失');
		}
		
		return $result;
	}
	
	/**
	 * 创建ticket回复
	 * @param		int		$uid
	 * @param 		int		$ticket_id
	 * @param 		array	$data
	 */
	public static function createReply($uid,$ticket_id,$data){
		$result=['success'=>true,'message'=>''];
		$ost_user = self::get_LB_User_OST_info($uid);
		$ost_uid = $ost_user['id'];
		$ost_user_name = $ost_user['name'];
		//权限验证
		if(empty($ost_uid)){
			return ['success'=>false,'message'=>'您还未激活工单系统用户权限,请联系客服激活'];
		}
		
		$ticket=self::get_OST_Ticket_Info($ticket_id);
		if(!empty($ticket)){
			if(!empty($ticket['user_id']) && $ticket['user_id']!==$ost_uid){
				return ['success'=>false,'message'=>'该工单并非由您发起,您不能回复此工单'];
			}
		}else{
			return ['success'=>false,'message'=>'工单信息已丢失,回复失败'];
		}
		//权限验证结束
		/*联系方式用lb的user信息
		$contact_info = $data['contact_info'];
		unset($data['contact_info']);
		*/
		$ip =  $_SERVER['REMOTE_ADDR'];
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
		$go_on = true;
		$create_time = date('Y-m-d H:i:s',time());
		try{	
			#step 1 : inster ost_ticket_thread
			$connection =  \Yii::$app->ost_db;
			$sql = "INSERT INTO `ost_ticket_thread`
				(`ticket_id`,`staff_id`,`user_id`,`thread_type`,`poster`,`source`,`body`,`format`,`ip_address`,`created`,`updated`) VALUES
				($ticket_id,0,$ost_uid,'M','$ost_user_name','eagle2',:message,'html','$ip','$create_time','$create_time')";
			$command = $connection->createCommand($sql);
			$command->bindValue(":message", $data['message'], \PDO::PARAM_STR);
			if(!$command->execute()){
				$transaction->rollBack();
				$result['success']=false;
				$result['message']="保存工单详情失败";
				return $result;
			}else{
				$thread_id = \Yii::$app->ost_db->getLastInsertID();
			}
			
			#step 2 : inster ost_ticket_attachment
			//continue;
			
			#stpe 3 : inster ost__search
			$connection =  \Yii::$app->ost_db;
			if(!isset($thread_id))
				$thread_id=0;
			$sql = "INSERT INTO `ost__search`
				(`object_type`,`object_id`,`title`,`content`) VALUES
				('H',$thread_id,'',:message)";
			$command = $connection->createCommand($sql);
			$command->bindValue(":message", $data['message'], \PDO::PARAM_STR);
			if(!$command->execute()){
				$transaction->rollBack();
				$result['success']=false;
				$result['message']="保存工单索引失败";
				return $result;
			}
			
			#step 4 : update ost_ticket.updated
			$connection =  \Yii::$app->ost_db;
			
			$sql = "UPDATE `ost_ticket` SET 
				`lastmessage`='$create_time' 
				WHERE `ticket_id`=$ticket_id ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$transaction->rollBack();
				$result['success']=false;
				$result['message']="工单信息更新失败";
				return $result;
			}
			
			#step 5 : commit changes
			if($result['success']){
				/*联系方式用lb的user信息
				self::save_Ticket_Addt_Contact_Info($ticket_id, $ost_uid, $contact_info);
				*/
				$transaction->commit();
				return $result;
			}
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}
		
	/**
	 * 撤销工单（更改状态使工单不能在前端做除查看以外的操作）
	 */
	public static function cancelTicket($ticket_id){
		$result['success']=true;
		$result['message']="";
		
		$ost_user_name='';
		$uid = \Yii::$app->user->id;
		$ost_user = self::get_LB_User_OST_info($uid);
		if(empty($ost_user)){
			$result['success']=false;
			$result['message']="获取用户工单信息失败";
			return $result;
		}else{
			$ost_user_name = $ost_user['name'];
			$ost_uid = $ost_user['id'];
		}
		$cancel_time = date('Y-m-d H:i:s',time());
		$closed_state_id = self::get_Closed_State_Id();
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
		try{
			$connection =  \Yii::$app->ost_db;
			$sql = "UPDATE `ost_ticket` SET
			`status_id`=$closed_state_id,`closed`='$cancel_time',`updated`='$cancel_time' 
			WHERE `ticket_id`=$ticket_id ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$transaction->rollBack();
				$result['success']=false;
				$result['message']="工单撤销失败";
				return $result;
			}
			
			$staff_id = 0;
			$team_id = 0;
			$dept_id = 1;
			$topic_id = 0;
			
			$connection =  \Yii::$app->ost_db;
			$command = $connection->createCommand(
				"SELECT * FROM `ost_ticket_event`
				WHERE `ticket_id`=$ticket_id
				ORDER BY `timestamp` DESC ");
			$last_event = $command->queryOne();
			if(!empty($last_event)){
				$staff_id=$last_event['staff_id'];
				$team_id=$last_event['team_id'];
				$dept_id=$last_event['dept_id'];
				$topic_id=$last_event['topic_id'];
			}
			
			$connection =  \Yii::$app->ost_db;
			$sql = "INSERT INTO `ost_ticket_event`
				(`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`, `state`, `staff`, `annulled`, `timestamp`) VALUES
				($ticket_id,$staff_id,$team_id,$dept_id,$topic_id,'closed','$ost_user_name',0,'$cancel_time') ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单事件记录失败";
				$transaction->rollBack();
				return $result;
			}
			
			$ip =  $_SERVER['REMOTE_ADDR'];
			$connection =  \Yii::$app->ost_db;
			$sql = "INSERT INTO `ost_ticket_thread`
				(`ticket_id`,`staff_id`,`user_id`,`thread_type`,`poster`,`source`,`body`,`format`,`ip_address`,`created`,`updated`) VALUES
				($ticket_id,$staff_id,$ost_uid,'N','$ost_user_name','eagle2',:message,'html','$ip','$cancel_time','$cancel_time')";
			$command = $connection->createCommand($sql);
			$command->bindValue(":message", "Status changed from 打开 to 已关闭 by $ost_user_name", \PDO::PARAM_STR);
			if(!$command->execute()){
				$transaction->rollBack();
				$result['success']=false;
				$result['message']="保存工单状态变更记录失败";
				return $result;
			}
			
			$transaction->commit();
			return $result;
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
		
	}
	
	/**
	 * 批量关闭(撤销)工单
	 */
	public static function batchCancelTicket($ticket_ids){
		$result['success']=true;
		$result['message']="";
	
		$ost_user_name='';
		$uid = \Yii::$app->user->id;
		$ost_user = self::get_LB_User_OST_info($uid);
		if(empty($ost_user)){
			$result['success']=false;
			$result['message']="获取用户工单信息失败";
			return $result;
		}else{
			$ost_user_name = $ost_user['name'];
			$ost_uid = $ost_user['id'];
		}
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
		if(count($ticket_ids)==1){
			$ids_str=$ticket_ids[0];
		}else{
			$ids_str = implode("','", $ticket_ids);
		}
	
		try{
			$cancel_time = date('Y-m-d H:i:s',time());
			
			$connection =  \Yii::$app->ost_db;
			$sql = "SELECT `ticket_id` FROM `ost_ticket` 
			WHERE `ticket_id` in ('$ids_str') AND `status_id`=1 ";
			$command = $connection->createCommand($sql);
			$rows = $command->queryAll();
			if(empty($rows)){
				$result['success']=false;
				$result['message']="所选工单没有可以进行关闭操作的。";
				$transaction->rollBack();
				return $result;
			}else{
				$ticket_ids=array();
				foreach ($rows as $row){
					$ticket_ids[]=$row['ticket_id'];
				}
			}
			if(count($ticket_ids)==1){
				$ids_str=$ticket_ids[0];
			}else{
				$ids_str = implode("','", $ticket_ids);
			}
			
			$closed_state_id = self::get_Closed_State_Id();
			
			$connection =  \Yii::$app->ost_db;
			$sql = "UPDATE `ost_ticket` SET
			`status_id`=$closed_state_id,`closed`='$cancel_time',`updated`='$cancel_time'
			WHERE `ticket_id` in ('$ids_str') ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单关闭失败";
				$transaction->rollBack();
				return $result;
			}
	
			//inster ost_ticket_event
			$event_datas=array();
			$ip =  $_SERVER['REMOTE_ADDR'];
			foreach ($ticket_ids as $ticket_id){
				$staff_id = 0;
				$team_id = 0;
				$dept_id = 1;
				$topic_id = 0;
	
				$connection =  \Yii::$app->ost_db;
				$command = $connection->createCommand(
						"SELECT * FROM `ost_ticket_event`
						WHERE `ticket_id`=$ticket_id
						ORDER BY `timestamp` DESC ");
				$last_event = $command->queryOne();
				if(!empty($last_event)){
					$staff_id=$last_event['staff_id'];
					$team_id=$last_event['team_id'];
					$dept_id=$last_event['dept_id'];
					$topic_id=$last_event['topic_id'];
				}
				$event_datas[]=array(
					'ticket_id'=>$ticket_id,
					'staff_id'=>$staff_id,
					'team_id'=>$team_id,
					'dept_id'=>$dept_id,
					'topic_id'=>$topic_id,
					'state'=>'closed',
					'staff'=>$ost_user_name,
					'annulled'=>0,
					'timestamp'=>$cancel_time						
				);
				
				$connection =  \Yii::$app->ost_db;
				$sql = "INSERT INTO `ost_ticket_thread`
					(`ticket_id`,`staff_id`,`user_id`,`thread_type`,`poster`,`source`,`body`,`format`,`ip_address`,`created`,`updated`) VALUES
					($ticket_id,$staff_id,$ost_uid,'N','$ost_user_name','eagle2',:message,'html','$ip','$cancel_time','$cancel_time')";
				$command = $connection->createCommand($sql);
				$command->bindValue(":message", "Status changed from 打开 to 已关闭 by $ost_user_name", \PDO::PARAM_STR);
				if(!$command->execute()){
					$transaction->rollBack();
					$result['success']=false;
					$result['message']="保存工单状态变更记录失败";
					return $result;
				}
			}
			
			if(!empty($event_datas)){
				$instered = SQLHelper::groupInsertToDb('ost_ticket_event', $event_datas,'ost_db');
				if($instered){
					$transaction->commit();
					return $result;
				}
			}
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}
	
	/**
	 * 重新打开工单
	 */
	public static function reopenTicket($ticket_id){
		$result['success']=true;
		$result['message']="";
		
		$ost_user_name='';
		$uid = \Yii::$app->user->id;
		$ost_user = self::get_LB_User_OST_info($uid);
		if(empty($ost_user)){
			$result['success']=false;
			$result['message']="获取用户工单信息失败";
			return $result;
		}else{
			$ost_user_name = $ost_user['name'];
			$ost_uid = $ost_user['id'];	
		}
		
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
		$open_state_id = self::get_Open_State_Id();
		try{
			$reopen_time = date('Y-m-d H:i:s',time());
			$connection =  \Yii::$app->ost_db;
			$sql = "UPDATE `ost_ticket` SET
			`status_id`=$open_state_id,`reopened`='$reopen_time',`closed`=NULL,`updated`='$reopen_time' 
			WHERE `ticket_id`=$ticket_id ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单打开失败";
				$transaction->rollBack();
				return $result;
			}
			
			//inster ost_ticket_event
			$staff_id=0;
			$team_id=0;
			$dept_id=1;
			$topic_id=0;
			
			$connection =  \Yii::$app->ost_db;
			$command = $connection->createCommand(
					"SELECT * FROM `ost_ticket_event` 
					WHERE `ticket_id`=$ticket_id 
					ORDER BY `timestamp` DESC ");
			$last_event = $command->queryOne();
			if(!empty($last_event)){
				$staff_id=$last_event['staff_id'];
				$team_id=$last_event['team_id'];
				$dept_id=$last_event['dept_id'];
				$topic_id=$last_event['topic_id'];
			}
			
			$connection =  \Yii::$app->ost_db;
			$sql = "INSERT INTO `ost_ticket_event` 
					(`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`, `state`, `staff`, `annulled`, `timestamp`) VALUES 
					($ticket_id,$staff_id,$team_id,$dept_id,$topic_id,'reopened','$ost_user_name',0,'$reopen_time') ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单事件记录失败";
				$transaction->rollBack();
				return $result;
			}
			
			$ip =  $_SERVER['REMOTE_ADDR'];
			$connection =  \Yii::$app->ost_db;
			$sql = "INSERT INTO `ost_ticket_thread`
				(`ticket_id`,`staff_id`,`user_id`,`thread_type`,`poster`,`source`,`body`,`format`,`ip_address`,`created`,`updated`) VALUES
				($ticket_id,$staff_id,$ost_uid,'N','$ost_user_name','eagle2',:message,'html','$ip','$reopen_time','$reopen_time')";
			$command = $connection->createCommand($sql);
			$command->bindValue(":message", "Status changed from 已关闭 to 打开 by $ost_user_name", \PDO::PARAM_STR);
			if(!$command->execute()){
				$transaction->rollBack();
				$result['success']=false;
				$result['message']="保存工单状态变更记录失败";
				return $result;
			}
			
			$transaction->commit();
			return $result;
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}
	
	/**
	 * 批量打开工单
	 */
	public static function batchReopenTicket($ticket_ids){
		$result['success']=true;
		$result['message']="";
	
		$ost_user_name='';
		$uid = \Yii::$app->user->id;
		$ost_user = self::get_LB_User_OST_info($uid);
		if(empty($ost_user)){
			$result['success']=false;
			$result['message']="获取用户工单信息失败";
			return $result;
		}else{
			$ost_user_name = $ost_user['name'];
			$ost_uid = $ost_user['id'];
		
		}
	
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
		$open_state_id = self::get_Open_State_Id();
		$closed_state_id = self::get_Closed_State_Id();
		$reopen_time = date('Y-m-d H:i:s',time());
		if(count($ticket_ids)==1){
			$ids_str=$ticket_ids[0];
		}else{
			$ids_str = implode("','", $ticket_ids);
		}
	
		try{
			$connection =  \Yii::$app->ost_db;
			$sql = "SELECT `ticket_id` FROM `ost_ticket`
				WHERE `ticket_id` in ('$ids_str') AND `status_id`=$closed_state_id ";
			$command = $connection->createCommand($sql);
			$rows = $command->queryAll();
			if(empty($rows)){
				$result['success']=false;
				$result['message']="所选工单没有可以进行开启的。";
				$transaction->rollBack();
				return $result;
			}else{
				$ticket_ids=array();
				foreach ($rows as $row){
					$ticket_ids[]=$row['ticket_id'];
				}
			}
			if(count($ticket_ids)==1){
				$ids_str=$ticket_ids[0];
			}else{
				$ids_str = implode("','", $ticket_ids);
			}
								
			$connection =  \Yii::$app->ost_db;
			$sql = "UPDATE `ost_ticket` SET
				`status_id`=$open_state_id,`reopened`='$reopen_time',`closed`=NULL,`updated`='$reopen_time' 
				WHERE `ticket_id` in ('$ids_str') ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单开启失败";
				$transaction->rollBack();
				return $result;
			}

			//inster ost_ticket_event
			$event_datas=array();
			$ip =  $_SERVER['REMOTE_ADDR'];
			foreach ($ticket_ids as $ticket_id){
				$staff_id = 0;
				$team_id = 0;
				$dept_id = 1;
				$topic_id = 0;

				$connection =  \Yii::$app->ost_db;
				$command = $connection->createCommand(
					"SELECT * FROM `ost_ticket_event`
					WHERE `ticket_id`=$ticket_id
					ORDER BY `timestamp` DESC ");
				$last_event = $command->queryOne();
				if(!empty($last_event)){
					$staff_id=$last_event['staff_id'];
					$team_id=$last_event['team_id'];
					$dept_id=$last_event['dept_id'];
					$topic_id=$last_event['topic_id'];
				}
				$event_datas[]=array(
					'ticket_id'=>$ticket_id,
					'staff_id'=>$staff_id,
					'team_id'=>$team_id,
					'dept_id'=>$dept_id,
					'topic_id'=>$topic_id,
					'state'=>'reopened',
					'staff'=>$ost_user_name,
					'annulled'=>0,
					'timestamp'=>$reopen_time
				);
				
				
				$connection =  \Yii::$app->ost_db;
				$sql = "INSERT INTO `ost_ticket_thread`
				(`ticket_id`,`staff_id`,`user_id`,`thread_type`,`poster`,`source`,`body`,`format`,`ip_address`,`created`,`updated`) VALUES
				($ticket_id,$staff_id,$ost_uid,'N','$ost_user_name','eagle2',:message,'html','$ip','$reopen_time','$reopen_time')";
				$command = $connection->createCommand($sql);
				$command->bindValue(":message", "Status changed from 已关闭 to 打开 by $ost_user_name", \PDO::PARAM_STR);
				if(!$command->execute()){
					$transaction->rollBack();
					$result['success']=false;
					$result['message']="保存工单状态变更记录失败";
					return $result;
				}
			}
						
			if(!empty($event_datas)){
				$instered = SQLHelper::groupInsertToDb('ost_ticket_event', $event_datas,'ost_db');
				if($instered){
					$transaction->commit();
					return $result;
				}
			}
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}

	/**
	 * 删除工单（更改状态使工单为deleted,不再再网页显示）
	 */
	public static function deleteTicket($ticket_id){
		$result['success']=true;
		$result['message']="";
	
		$ost_user_name='';
		$uid = \Yii::$app->user->id;
		$ost_user = self::get_LB_User_OST_info($uid);
		if(empty($ost_user)){
			$result['success']=false;
			$result['message']="获取用户工单信息失败";
			return $result;
		}else
			$ost_user_name = $ost_user['name'];
	
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
		try{
			$delete_time = date('Y-m-d H:i:s',time());
			$connection =  \Yii::$app->ost_db;
			$sql = "UPDATE `ost_ticket` SET
			`status_id`=5,`closed`='$delete_time',`updated`='$delete_time' 
			WHERE `ticket_id`=$ticket_id ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单删除失败";
				$transaction->rollBack();
				return $result;
			}
		
			//inster ost_ticket_event
			$staff_id=0;
			$team_id=0;
			$dept_id=1;
			$topic_id=0;
			
			$connection =  \Yii::$app->ost_db;
			$command = $connection->createCommand(
					"SELECT * FROM `ost_ticket_event`
					WHERE `ticket_id`=$ticket_id
					ORDER BY `timestamp` DESC ");
			$last_event = $command->queryOne();
			if(!empty($last_event)){
				$staff_id=$last_event['staff_id'];
				$team_id=$last_event['team_id'];
				$dept_id=$last_event['dept_id'];
				$topic_id=$last_event['topic_id'];
			}
		
			$connection =  \Yii::$app->ost_db;
			//用户删除工单操作，也定义为closed事件
			$sql = "INSERT INTO `ost_ticket_event`
				(`ticket_id`, `staff_id`, `team_id`, `dept_id`, `topic_id`, `state`, `staff`, `annulled`, `timestamp`) VALUES
				($ticket_id,$staff_id,$team_id,$dept_id,$topic_id,'closed','$ost_user_name',0,'$delete_time') ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
			$result['success']=false;
			$result['message']="工单事件记录失败";
				$transaction->rollBack();
				return $result;
			}
		
			$transaction->commit();
			return $result;
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}
	
	/**
	 * 批量删除工单
	 */
	public static function batchDeleteTicket($ticket_ids){
		$result['success']=true;
		$result['message']="";
	
		$ost_user_name='';
		$uid = \Yii::$app->user->id;
		$ost_user = self::get_LB_User_OST_info($uid);
		if(empty($ost_user)){
			$result['success']=false;
			$result['message']="获取用户工单信息失败";
			return $result;
		}else
			$ost_user_name = $ost_user['name'];
	
		$transaction = Yii::$app->get('ost_db')->beginTransaction ();
	
		$delete_time = date('Y-m-d H:i:s',time());
		if(count($ticket_ids)==1){
			$ids_str=$ticket_ids[0];
		}else{
			$ids_str = implode("','", $ticket_ids);
		}
		
		try{
			$connection =  \Yii::$app->ost_db;
			$sql = "UPDATE `ost_ticket` SET
			`status_id`=5,`closed`='$delete_time',`updated`='$delete_time'
			WHERE `ticket_id` in ('$ids_str') ";
			$command = $connection->createCommand($sql);
			if(!$command->execute()){
				$result['success']=false;
				$result['message']="工单删除失败";
				$transaction->rollBack();
				return $result;
			}
		
			//inster ost_ticket_event
			$event_datas=array();
			foreach ($ticket_ids as $ticket_id){
				$staff_id = 0;
				$team_id = 0;
				$dept_id = 1;
				$topic_id = 0;
				
				$connection =  \Yii::$app->ost_db;
				$command = $connection->createCommand(
					"SELECT * FROM `ost_ticket_event`
					WHERE `ticket_id`=$ticket_id
					ORDER BY `timestamp` DESC ");
				$last_event = $command->queryOne();
				if(!empty($last_event)){
					$staff_id=$last_event['staff_id'];
					$team_id=$last_event['team_id'];
					$dept_id=$last_event['dept_id'];
					$topic_id=$last_event['topic_id'];
				}
				//用户删除工单操作，也定义为closed事件
				$event_datas[]=array(
					'ticket_id'=>$ticket_id,
					'staff_id'=>$staff_id,
					'team_id'=>$team_id,
					'dept_id'=>$dept_id,
					'topic_id'=>$topic_id,
					'state'=>'closed',
					'staff'=>$ost_user_name,
					'annulled'=>0,
					'timestamp'=>$delete_time
					
				);
			}
			
			if(!empty($event_datas)){
				$instered = SQLHelper::groupInsertToDb('ost_ticket_event', $event_datas,'ost_db');
				if($instered){
					$transaction->commit();
					return $result;
				}
			}
		}catch (\Exception $e) {
			$transaction->rollBack();
			return array('success'=>false, 'message'=>$e->getMessage() );
		}
	}
}
