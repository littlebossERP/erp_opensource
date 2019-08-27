<?php
namespace eagle\models;

use Yii;
use yii\db\Connection;



class DBManager extends Connection
{
	private $_currnetPuid=false;
	private $_userDbStatus=0; //0---正常状态,1----正在搬数据库
	static $dbPrefix="user_";
	
	/**
	 * 返回user库的 db数据库名前缀
	 */
	public function getCurrentDBPrefix(){
		return self::$dbPrefix;
	}
	
	/**
	 * 在创建用户的时候，需要获取新注册用户的数据库信息.
	 * 这样会导致测试场地的注册功能有问题
	 */
	public function getRegisterAccountDBInfo(){
		$dbserverId=1;
		$host="127.0.0.1";		
	    
		$dbusername='root';
		$password='123456';
		
		return array($dbserverId,$host,$dbusername,$password);
		
	}

	// 获取当前的使用puid。  如果返回为false，说明还没有在puid在使用 
	public function getCurrentPuid(){
		return $this->_currnetPuid;
	}
	
	// 获取当前的已登录用户的status
	public function getCurrentUserDbStatus(){
	    return $this->_userDbStatus;
	}
	
	// 当前已登录用户是否正在搬数据库
	public function isUserDbMoving(){
	    if ($this->_userDbStatus==1) return true;
	    return false;
	}	
	
		
	/**
	 * 从config文件读取 （只有数据表没有的时候才读取）！！！ 根据$dbServerId （user_database表中dbserverid）返回对应的数据库连接信息.
	 * 目前每个puid都有对应一个dbserverid，每个dbserverid 对应一台db服务器，取值为1，2...，默认为1
	 * 具体
	 * @param  $dbServerId
	 * @return [
         "host"=>"localhost",
         "dbPrefix"=>"user_",
         "username"=>"root",
         "password"=>"",
         
      ]
	 * 
	 */
	private function getDBServerFromConfigByDbServerid($dbServerId){
	    $subdbInfo=\Yii::$app->params["subdb"];
	    
	    if ($dbServerId==1) return $subdbInfo;
	    //main.php中的配置名称 如 subdb2
	    $subdbConfigName="subdb".$dbServerId;
	    if (isset(\Yii::$app->params[$subdbConfigName])){
	        $subdbTmpInfo=\Yii::$app->params[$subdbConfigName];
	        if (isset($subdbTmpInfo["host"]))       $subdbInfo["host"]=$subdbTmpInfo["host"];
	        if (isset($subdbTmpInfo["username"]))       $subdbInfo["username"]=$subdbTmpInfo["username"];
	        if (isset($subdbTmpInfo["password"]))       $subdbInfo["password"]=$subdbTmpInfo["password"];
	        if (isset($subdbTmpInfo["dbPrefix"]))       $subdbInfo["dbPrefix"]=$subdbTmpInfo["dbPrefix"];	         
	    }	    
	    return $subdbInfo;
	    
	}
	
	public function __construct() {
		\Yii::warning("DBManager __construct");
		$uid=1;
		
		if (isset(\Yii::$app->user) and \Yii::$app->user->id<>"")	$uid=\Yii::$app->user->id;  //isset(\Yii::$app->user) 为了console调用
		// 获取数据库id
		//list($currentPuid,$dataBaseId,$dbServerId,$status)=$this->getDid($uid);
		list($currentPuid,$dataBaseId,$dbServerId,$status,$host,$username,$password)=$this->getDBInfoFromDB($uid);		
		$this->_currnetPuid=$currentPuid;
		$this->_userDbStatus=$status;
		
		
		
		$dsn='mysql:host='.$host.';dbname='.self::$dbPrefix.$dataBaseId;
		parent::__construct(['dsn'=>$dsn, 'username'=>$username,'password'=>$password ]);
		
	}

	/**
	 +----------------------------------------------------------
	 * 切换subdb数据库
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param uid		用户id
	 +----------------------------------------------------------
	 * @return		boolean
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	public function changeUserDataBase($uid) {
	    $this->_currnetPuid=$uid;
		return true;
	}	
	

	
	/**
	 +----------------------------------------------------------
	 * 根据uid来获取数据库的连接信息
	 * user库转移的job也会调用该接口
	 * 2016-01-08 修改    user库数据库的连接信息以数据表 user_database为主， config下的配置为辅
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param uid		用户id
	 +----------------------------------------------------------
	 * @return		array(puid, did 数据库后缀id)
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/02/27				初始化
	 +----------------------------------------------------------                                                                              
	 **/
	public function getDBInfoFromDB($uid) {
		if(empty($uid)) return 0;
		$connection = \Yii::$app->db;
		$userData = $connection->createCommand("SELECT `puid` FROM `user_base` WHERE `uid` = '{$uid}'")->queryOne();
		$dbuid = $userData['puid'] == 0 ? $uid : $userData['puid'];
		$dbData = $connection->createCommand("SELECT `did`,`dbserverid`,`status`,`ip`,`dbusername`,`password` FROM `user_database` WHERE `uid` = '{$dbuid}'")->queryOne();
		if ($dbData['ip']==null or $dbData['ip']==""){
		//	\Yii::info("get db info from config	 ","file");
			// 数据表没有这个数据库信息，从config上获取，测试环境一般就是这样
			$subInfo=$this->getDBServerFromConfigByDbServerid($dbData['dbserverid']);
			//$subdbInfo
			return array($dbuid,$dbData['did'],$dbData['dbserverid'],$dbData['status'],$subInfo['host'],$subInfo['username'],$subInfo['password']);
			
		}
		
		return array($dbuid,$dbData['did'],$dbData['dbserverid'],$dbData['status'],$dbData['ip'],$dbData['dbusername'],$dbData['password']);
	}	
	
	/**
	 +----------------------------------------------------------
	 * 根据uid来获取数据库id
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param uid		用户id
	 +----------------------------------------------------------
	 * @return		array(puid, did 数据库后缀id)
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/02/27				初始化
	 +----------------------------------------------------------
	 **/
	/*private function getDid($uid) {
		if(empty($uid)) return 0;
		$connection = \Yii::$app->db;
		$userData = $connection->createCommand("SELECT `puid` FROM `user_base` WHERE `uid` = '{$uid}'")->queryOne();
		$dbuid = $userData['puid'] == 0 ? $uid : $userData['puid'];
		$dbData = $connection->createCommand("SELECT `did`,`dbserverid`,`status` FROM `user_database` WHERE `uid` = '{$dbuid}'")->queryOne();
		return array($dbuid,$dbData['did'],$dbData['dbserverid'],$dbData['status']);
	}	*/
}
