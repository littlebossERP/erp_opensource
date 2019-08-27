<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasEbayUser;
use eagle\modules\platform\helpers\EbayAccountsHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasMessageAutosync;
use eagle\models\EbayDeveloperAccountUsed;
use eagle\models\EbayDeveloperAccountInfo;
use eagle\models\EbayBindLog;
use common\api\ebayinterface\token;
use common\api\ebayinterface\config;

/**
 +------------------------------------------------------------------------------
 * ebay平台管理模块业务接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class EbayAccountsApiHelper {
	/**
	 +----------------------------------------------------------
	 * 获取物流商列表数据(系统表与用户表)
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 +----------------------------------------------------------
	 * @return				物流商数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/3/25				初始化
	 +----------------------------------------------------------
	**/
	public static function helpList( $sort , $order ) {
		//本站当前用户ID
		//$uid = \Yii::$app->user->id;
		$uid = \Yii::$app->user->identity->getParentUid();
		
		$ebayUserList = SaasEbayUser::find()->where("uid = '$uid'")
		->orderBy($sort.' '.$order)
		->asArray()
		->all();
		
       	foreach($ebayUserList as &$ebayUser)
        	$ebayUser['expiration_time'] = date('Y-m-d', $ebayUser['expiration_time']);

        return $ebayUserList;
	}


	/**
	 +----------------------------------------------------------
	 * 更新一条物流商数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		更新的数据(carrier_code为必须)
	 +----------------------------------------------------------
	 * @return				更新影响行数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpUpdate($post) {
        return EbayAccountsHelper::helpUpdate($post);
	}
	/**
	 +----------------------------------------------------------
	 * 删除ebay帐号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		删除数据所依据的ID
	 +----------------------------------------------------------
	 * @return				删除影响行数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpDelete($keys) 
	{
		return EbayAccountsHelper::helpDelete($keys);
	}

	/**
	 *
	 *  eBbay  token
	 * @date 2014-1-13
	 */
	public static function saveEbayToken($account_id, $selleruserid, $token, $HardExpirationTime){
      return EbayAccountsHelper::saveEbayToken($account_id, $selleruserid, $token, $HardExpirationTime);
	}


	/**
	 * 类型  type
	 * 1 : order 订单
	 * 2 : item  商品
	 * 3 : message  站内信
	 * 4 : feedback  评论
	 * 5 : dispute  纠纷
	 * 6 : ebp
	 */


	/**
	 * 添加新的eBay账号
	 * @param SaasEbayUser  $EU
	 * @author lxqun
	 * @date 2014-3-31
	 */
	static function AddNewEbayUser($ebay_uid, $selleruserid){
       return EbayAccountsHelper::AddNewEbayUser($ebay_uid, $selleruserid);
	}

	/**
	 * 返回本用户ebay账号数
	 * @author dzt
	 * @date 2014-3-31
	 */
	static function countBindingAccounts(){
		//本站当前用户ID
		$uid = \Yii::$app->user->id;
		return SaasEbayUser::find()->where("uid = '$uid'")->count();
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/02			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getLastOrderSyncDetail($account_key , $uid=0){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
	
		//where  type 为time 定时抓取新单
		//type		同步类型  1: 订单 2: 刊登 3: 站内信 4: 评价 5: 售前纠纷Dispute 6: 全部纠纷UserCase 7:item
		$info = SaasEbayAutosyncstatus::find()->where(['selleruserid'=>$account_key , 'type'=>1])->orderBy(' lastprocessedtime desc ')->asArray()->one();
		$tmpCommand =  SaasEbayAutosyncstatus::find()->where(['selleruserid'=>$account_key , 'type'=>1])->orderBy(' lastprocessedtime desc ')->createCommand();
		//echo "<br>".$tmpCommand->getRawSql();
		if (!empty($info)){
			/**/
			$account = SaasEbayUser::find()->where(['selleruserid'=>$account_key])->one();
			if($account==null)
				return  ['success'=>false , 'message'=>'账号信息有误' , 'result'=>[]];
				
			$result['is_active']=$info['status'];
			$result['last_time']=$info['lastprocessedtime'];
			
			if($info['status_process']==0){
				$result['message']='';
				$result['status']= 0;
			}
			if($info['status_process']==1){
				$result['message']='';
				$result['status']= 1;
			}
			if($info['status_process']==2){
				$result['message']='';
				$result['status']= 2;
			}
			
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{	
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	
	}//end of getLastOrderSyncDetail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay 商品同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getLastProductSyncDetail($account_key , $uid=0){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
	
		//where  type 为onSelling 同步商品
		/**
		 * 类型  type
		 * type		同步类型  
		 * 1: 订单 
		 * 2: 刊登 
		 * 3: 站内信 
		 * 4: 评价 
		 * 5: 售前纠纷Dispute 
		 * 6: 全部纠纷UserCase 
		 * 7:item
		 */
		$info = SaasEbayAutosyncstatus::find()->where(['selleruserid'=>$account_key , 'type'=>7])->orderBy(' lastprocessedtime desc ')->asArray()->one();
		if (!empty($info)){
			$account = SaasEbayUser::findOne(['selleruserid'=>$account_key]);
			if($account==null)
				return  ['success'=>false , 'message'=>'账号信息有误' , 'result'=>[]];
			$result['is_active']=$info['status'];
			$result['last_time']=$info['lastprocessedtime'];
			
			if($info['status_process']==0){
				$result['message']='';
				$result['status']= 0;
			}
			if($info['status_process']==1){
				$result['message']='';
				$result['status']= 1;
			}
			if($info['status_process']==2){
				$result['message']='';
				$result['status']= 2;
			}
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	
	}//end of getLastProductSyncDetail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay 异常账号
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'expiration'=>token 过期的账号
	 * 					'nosync'=> 同步关闭的账号
	 * 				    	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getProblemAccount($uid=''){
		
		//本站当前用户ID
		if (empty($uid)){
			$uid = \Yii::$app->user->id;
		}
		
		$accounts = self::helpList('create_time','asc');
		$result=[];
		$now = time();
		foreach($accounts as $account){
			if (empty($account['DevAcccountID'])){
				$result['token_expired'][] = $account;
			}elseif ($account['DevAcccountID'] ==150){
				$result['token_expired'][] = $account;
			}
			if($account['item_status']==0){
				//同步 关闭
				$result['nosync'][] = $account;
			}
			/*
			if ($account['expiration_time']<=$now){
				//token 过期
				$result['expiration'][] = $account;
			}else
			if($account['item_status']==0){
				//同步 关闭
				$result['nosync'][] = $account;
			}
			*/
		}
// 		var_dump($result);
// 		exit();
		return $result;
	}//end of getProblemAccount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	/*
	static public function resetSyncOrderInfo(){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		
		$model = SaasEbayAutosyncstatus::find()->where(['uid'=>$uid ,'selleruserid'=>$account_key , 'type'=>1])->one();
		
		if ($model->save()){
			return ['success' =>true , 'message'=>''];
		}else{
			return ['success'=>false , 'message'=>json_decode($model->errors)];
		}
	}//end of resetSyncOrderInfo
	*/
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置ebay订单同步的起始时间
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $time 						时间戳格式的时间
	 * @param $puid							用户的uid可以是array 也可以是string或者 int
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行影响了多少条的数据
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function resetEbaySyncOrderBeginning( $puid=0 , $time){
		if (is_numeric($time)  ){
			//$condition = [];
			$sql = '';
			if (!empty($puid)){
				if (is_array($puid)){
					$andSql = " u.uid in ('".implode("','", $puid)."') ";
				}else if (is_string($puid) || is_numeric($puid)){
					$andSql = " and u.uid ='$puid'  ";
				}else{
					return ['ack'=>false, 'message'=>'puid参数格式不正确！','code'=>'4002','data'=>''];
				}
	
				$sql = " update saas_ebay_autosyncstatus a , saas_ebay_user u set lastprocessedtime	= '$time' where  a.ebay_uid =u.ebay_uid and type= '1' ".$andSql;
			}else{
				// 1 = sync order
				$sql = " update saas_ebay_autosyncstatus set lastprocessedtime	= '$time' where type= '1' ";
			}
			
			return ['ack'=>true, 'message'=>'','code'=>'2000','data'=>$sql];
			/*
				if (!empty($sql)){
			$command = \Yii::$app->db->createCommand($sql);
			$affectRows = $command->execute();
			echo "<br> \n $sql and effect :".$affectRows;
	
			}
			*/
		}else{
			return ['ack'=>false, 'message'=>'时间格式不正确！','code'=>'4001','data'=>''];
		}
	
	}//end of function resetEbaySyncOrderBeginning
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置ebay订单同步的起始时间
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $time 						时间戳格式的时间
	 * @param $puid							用户的uid可以是array 也可以是string或者 int
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行影响了多少条的数据
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function bindEbayDeveloperMapping($selleruserid , $devID){
		//检查 账号是否有绑定 
		$model = EbayDeveloperAccountUsed::findOne(['sellerid'=>$selleruserid ]);
		if (empty($model)){
			$model = new EbayDeveloperAccountUsed();
			$model->sellerid = (String)$selleruserid;
			$model->dev_account_id = $devID;
			if ($model->save()){
				$rt = EbayDeveloperAccountInfo::updateAllCounters(['used'=>1 ],['account_id'=>$devID]);
				if ($rt >0 ){
					return ['success'=>true, 'message'=>'绑定成功！'];
				}else{
					return ['success'=>false, 'message'=>'更新绑定数量失败！'];
				}
			}else{
				$msg = $model->getErrors();
				return ['success'=>false, 'message'=>json_encode($msg)];
			}
		}else{
			return ['success'=>true, 'message'=>'已经绑定，请不要重复绑定！'];
		}
	}//end of function bindEbayDeveloperMapping
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 随机获取ebay 开发者账号使用次数最少 的账号
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return	int					dev account id 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/11/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getMinUsedEbayDeveloperAccount($type="order"){
		$sql  =  "	select * from ebay_developer_account_info   where used in (select min(used) from ebay_developer_account_info where lv in (1,2) and type='$type' and status = '1'  ) and lv in (1,2) and type='$type' and status = '1'   ";
		$rt = \Yii::$app->get ('db')->createCommand($sql)->queryAll();
		if(empty($rt)){// TODO multi ebay dev account 
		    return null;
		}
		
		$result = [];
		foreach($rt as $row){
		    $result[] = $row['account_id'];
		}
		
		shuffle($result); //打乱顺序  ，
		return $result[0];
		
	}//end of function getMinUsedEbayDeveloperAccount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 刊登 获取ebay 开发者账号 未使用过的的账号
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return	int					dev account id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/01/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getUnuseListingEbayDeveloperAccount(){
		$sql  =  "	select * from ebay_developer_account_info   where used   = 0 and type='listing' and status = 1  order by account_id ";
		$rt = \Yii::$app->get ('db')->createCommand($sql)->queryAll();
		
		$result = [];
		foreach($rt as $row){
			$result[] = $row['account_id'];
		}
		
		shuffle($result); //打乱顺序  ，
		if (isset($result[0])){
			return $result[0];
		}else{
			return 0;
		}
		
	}//end of function getListingEbayDeveloperAccount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解绑ebay 账号时清除 相关的开发都绑定信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return	int					dev account id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/11/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function unbindEbayDeveloperAccount($sellerID){
		return EbayDeveloperAccountUsed::deleteAll(['sellerid'=>$sellerID]);
	}//end of function unbindEbayDeveloperAccount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 绑定 是增加 ebay 账号 绑定 了哪个开发者账号的日志功能， 方便追查问题
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $puid							用户的uid可以是array 也可以是string或者 int
	 * @param $selleruserid					ebay账号
	 * @param $devId						绑定的开发者账号
	 * @param $addi_info					额外的绑定信息
	 +---------------------------------------------------------------------------------------------
	 * @return	array					dev account id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/12/29				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function addEbayBindingLog($puid , $selleruserid , $devId , $addi_info = []){
		try {
			$ebayBindLog = new EbayBindLog();
			$ebayBindLog->puid = $puid;
			$ebayBindLog->selleruserid = $selleruserid;
			$ebayBindLog->devid = $devId;
			if (!empty($addi_info)){
				$ebayBindLog->add_info = json_encode($addi_info);
			}
			$ebayBindLog->createtime = date('Y-m-d H:i:s' ,time());
			if ($ebayBindLog->save(false)){
				return ['success'=>true, 'message'=>''];
			}else{
				return ['success'=>false, 'message'=>'操作日志插入失败！原因：'.json_encode($ebayBindLog->getErrors())];
			}
		} catch (\Exception $e) {
			return ['success'=>false, 'message'=>'操作日志插入失败！'."原因:".$e->getMessage()." line no ".$e->getLine()]; 
		}
	}//end of function addEbayBindingLog
	
	
	static public function testlistingBindingLink($DevID){
		$token = new token();
		$token->resetConfig($DevID);
		$token->config ['siteID'] = 0;
		$_SESSION['ebayListingDevAccountID'] = $DevID;
		$sessionId = $token->getSessionId();
		if (empty($sessionId)) {
			echo '绑定失败!error code'.$DevID;
		}
		echo $token->config['tokenUrl'].$sessionId;
		
	}//end of testlistingBindingLink
	
	//获取ebay账号别名关系映射
	static public function getEbayAliasAccount($selleruserids){
		$uid = \Yii::$app->user->identity->getParentUid();
		
		$ebayUserList = SaasEbayUser::find()->select('selleruserid,store_name')->where("uid = '$uid'")->asArray()->all();

		$tmp_ebayUserList = array();
		
		if(count($ebayUserList) > 0){
			foreach ($ebayUserList as $ebayUserList_Val){
				$tmp_ebayUserList[$ebayUserList_Val['selleruserid']] = $ebayUserList_Val['store_name'];
			}
		}
		
		$selleruserids_new = array();
		
		if(count($selleruserids) > 0){
			foreach ($selleruserids as $tmp_selleruseridsKey => $tmp_selleruseridsVal){
				if(isset($tmp_ebayUserList[$tmp_selleruseridsKey])){
					if($tmp_ebayUserList[$tmp_selleruseridsKey] != ''){
						$selleruserids_new[$tmp_selleruseridsKey] = $tmp_ebayUserList[$tmp_selleruseridsKey];
					}else{
						$selleruserids_new[$tmp_selleruseridsKey] = $tmp_selleruseridsVal;
					}
				}else{
					$selleruserids_new[$tmp_selleruseridsKey] = $tmp_selleruseridsVal;
				}
			}
		}

		return $selleruserids_new;
	}
	
}
