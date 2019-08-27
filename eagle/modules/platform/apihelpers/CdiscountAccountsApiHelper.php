<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasCdiscountUser;
use eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\RedisHelper;
/**
 +------------------------------------------------------------------------------
 * cdiscount绑定的账号接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class CdiscountAccountsApiHelper {
	/**
	+----------------------------------------------------------
	* 获取指定uid 的cdiscount账号 当uid为空的时候则为返回的是全部账号
	+----------------------------------------------------------
	* @access static
	+----------------------------------------------------------
	* @param uid			uid
	+----------------------------------------------------------
	* @return				cdiscount user 表的详细数据
	+----------------------------------------------------------
	* log			name		date				note
	* @author		lzhl		2015/12/01			初始化
	+----------------------------------------------------------
	**/
	public static function ListAccounts( $uid='' ) {
		$query = SaasCdiscountUser::find();
		if (!empty($uid)){
			$query->where(['uid'=>$uid]);
		}
		
		return $query->asArray()->all();
	}//end of ListAccount
	
	static public function listActiveAccounts($uid = 0){
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
			
		return SaasCdiscountUser::find()->where(['uid'=>$uid , 'is_active'=>1])->asArray()->all();
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Cdiscount账号商品同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/01			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getCdiscountProductSyncInfo($account_key,$uid){
		$account = SaasCdiscountUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
		if($account<>null){
			$result['is_active']=$account->is_active;
			$result['last_time']=strtotime($account->last_product_retrieve_time);
			$result['message']=$account->product_retrieve_message;
				
			if( (time()-$result['last_time']=strtotime($account->last_product_retrieve_time))<3600 )
				$result['status']= 1;
			else
				$result['status']= 3;
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
	
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Cdiscount账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/01			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getCdiscountOrderSyncInfo($account_key,$uid){
		$account = SaasCdiscountUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
		if($account<>null){
			$result['is_active']=$account->is_active;
			$result['last_time']=strtotime($account->last_order_retrieve_time);
			$result['message']=$account->order_retrieve_message;
			
			$time_past = time()-strtotime($account->last_order_retrieve_time);
			if( $time_past<1800 )
				$result['status']= 2;
			elseif($time_past>=1800 && $time_past<3600)
				$result['status']= 1;
			else
				$result['status']= 3;
				
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
	
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Cdiscount账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/01			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getCdiscountMessageSyncInfo($account_key,$uid){
		return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
	}
	//普通账号默认爆款上限
	public static $CdiscountTerminatorDefaultMaxHotSale = 1;
	
	//普通账号默认关注上限
	public static $CdiscountTerminatorDefaultMaxFellow = 1;
	
	public static $CdiscountTerminatorVipRank = [
		'0'=>'免费',
		'1'=>'VIP1',
		'2'=>'VIP2',
		'3'=>'VIP3',
		'4'=>'VIP4',
		'5'=>'VIP5',
		'6'=>'定制',
		'7'=>'定制',
		'8'=>'定制',
	];
	
	//vip账号爆款上限加值
	public static $CdiscountTerminatorVipAddiHotSale = [
		'0'=>0,
		'1'=>5,
		'2'=>10,
		'3'=>20,
		'4'=>50,
		'5'=>100,
		'6'=>1000,
		'7'=>2000,
		'8'=>250,
	];
	//vip账号关注上限加值
	public static $CdiscountTerminatorVipAddiFellow = [
		'0'=>0,
		'1'=>50,
		'2'=>100,
		'3'=>200,
		'4'=>500,
		'5'=>1000,
		'6'=>2000,
		'7'=>4000,
		'8'=>500,
	];
	
	public static $EagleVipAddiQuota = [
		'v1'=>['H'=>0,'F'=>0],
		'v2'=>['H'=>0,'F'=>0],
		'v3'=>['H'=>0,'F'=>0],
	];
	/**
	* 获取某用户CD账号的小老板VIP数据
	**/
	public static function getCdAccountVipInfo($puid){
		$vipInfos = [];
		$sql = "select * from `saas_cdiscount_vip_user` where `puid`=:puid";
		
		$command = \Yii::$app->db->createCommand($sql);
		$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
		
		$records = $command->queryAll();
		foreach ($records as $row){
			$vipInfos[$row['puid']] = $row;
		}
		return $vipInfos;
	}
	
	/**
	* 检查某用户是否CD跟卖终结者VIP
	* @return true or false
	* @author	lzhl	2016/11/24		初始化
	**/
	public static function checkUserIsCdTerminatorVip($puid){
		$sql = "select * from `saas_cdiscount_vip_user` where `puid`=:puid and `vip_rank`>0 ";
		$command = \Yii::$app->db->createCommand($sql);
		$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
		$records = $command->queryAll();
		if(count($records)>0){
			return true;
		}
		else{
			return false;
		}
	}
	
	
	/**
	 * 获取所有CdTerminator vip用户id和等级(vip0不算)
	 * @return	array(puid=>vip_rank)
	 **/
	public static function getCdTerminatorVipUsers(){
		$vipUsers = [];
		$sql = "select * from `saas_cdiscount_vip_user` where `vip_rank`>0";
		
		$command = \Yii::$app->db->createCommand($sql);
		$records = $command->queryAll();
		
		foreach ($records as $row){
			$vipUsers[$row['puid']] = $row['vip_rank'];
		}
		return $vipUsers;
	}
	
	/**
	 * 获取所有到了需要发自动提醒时间的CdTerminator vip用户
	 * @return	array(puid=>vipRecord)
	 **/
	public static function getNeedToAnnounceCdTerminatorVipUsers(){
		$vipUsers = [];
		$sql = "select * from `saas_cdiscount_vip_user` where `vip_rank`>0 and (`next_announce_send` is null or `next_announce_send`='' or `next_announce_send`<'".TimeUtil::getNow()."')";
	
		$command = \Yii::$app->db->createCommand($sql);
		$records = $command->queryAll();
	
		foreach ($records as $row){
			$vipUsers[$row['puid']] = $row;
		}
		return $vipUsers;
	}
	
	/*
	 * 设置用户的Cdiscount账号在小老板的VIP等级，用于提高CD跟卖终结者APP提高关注上限 等。
	 +---------------------------------------------------------------------------------------------
	 * @params	$puid			用户id
	 * @params	$rank			VIP等级
	 +---------------------------------------------------------------------------------------------
	 * @return
	 * array('success'=>,'message'=>)
	 +---------------------------------------------------------------------------------------------
	 * @author	lzhl	2016/06/08	初始化
	 */
	public static function setCdTerminatorVipRank($puid,$rank){
		
		$default_fellow_max = self::$CdiscountTerminatorDefaultMaxFellow;
		$default_hotsale_max = self::$CdiscountTerminatorDefaultMaxHotSale;
		if(isset(self::$CdiscountTerminatorVipAddiFellow[(string)$rank]))
			$addi_fellow = self::$CdiscountTerminatorVipAddiFellow[(string)$rank];
		else 
			return ['success'=>false,'message'=>'vip等级对应额外关注额度未指定！'];
		if(isset(self::$CdiscountTerminatorVipAddiHotSale[(string)$rank]))
			$addi_hotsale = self::$CdiscountTerminatorVipAddiHotSale[(string)$rank];
		else
			return ['success'=>false,'message'=>'vip等级对应额外爆款监视额度未指定！'];
		
		//$new_fellow_max = (int)$default_fellow_max+(int)$addi_fellow;
		//$new_hotsale_max = (int)$default_hotsale_max+(int)$addi_hotsale;
		
		$sql = "select * from `saas_cdiscount_vip_user` where `puid`=:puid";
		$command = \Yii::$app->db->createCommand($sql);
		$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
		$record = $command->queryOne();
		try {
			if(!empty($record)){
				//update
				$sql_update = "update `saas_cdiscount_vip_user` set `vip_rank`=$rank , `addi_hot_sale`=$addi_hotsale ,`addi_follow`=$addi_fellow ";
				
				if($rank>5)
					$sql_update .= ", `max_quota`= $addi_fellow ";
				
				$sql_update .= " where `puid`=:puid ";
				$command = \Yii::$app->db->createCommand($sql_update);
				$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
				$command->execute();
				//清除QuotaSnapshot成功与否并不影响vip等级设置生效，因此失败也不报错
				$ret = self::clearCdTerminatorVipQuotaSnapshot($puid);
			}else{
				//insert
				$sql_insert = "insert into `saas_cdiscount_vip_user` 
						(`puid`, `vip_rank`, `addi_hot_sale`, `addi_follow`,`max_quota`) 
						VALUES 
						($puid, $rank, $addi_hotsale, $addi_fellow, ".(($rank>5)?$addi_fellow:100).")";
				$command = \Yii::$app->db->createCommand($sql_insert);
				$command->execute();
			}
		}catch (\Exception $e){
			return array('success'=>false,'message'=>"vip data to db Exception:".$e->getMessage());
		}
		//update or insert 成功后，删除redis记录
		//\Yii::$app->redis->hdel("CdiscountAccountMaxHotSale","user_$puid");//无用记录，后续可废弃
		RedisHelper::RedisDel('CdiscountAccountMaxHotSale', "user_$puid");
		RedisHelper::RedisDel('CdiscountAccountAddiHotSale', "user_$puid");
		//\Yii::$app->redis->hdel("CdiscountAccountMaxFellow","user_$puid");//无用记录，后续可废弃
		RedisHelper::RedisDel('CdiscountAccountMaxFellow', "user_$puid");
		RedisHelper::RedisDel('CdiscountAccountAddiFellow', "user_$puid");
		return ['success'=>true,'message'=>''];
	}
	
	/*
	 * 清除saas_cdiscount_vip_user表的	额度使用情况数据
	 */
	public static function clearCdTerminatorVipQuotaSnapshot($puid){
		try {
			$sql = "select * from `saas_cdiscount_vip_user` where `puid`=:puid";
			$command = \Yii::$app->db->createCommand($sql);
			$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
			$record = $command->queryOne();
			if(!empty($record)){
				$addi_info = $record['addi_info'];
				if(!empty($addi_info))
					$addi_info = json_decode($addi_info,true);
				
				if(!empty($addi_info['used_quota_info'])){
					unset($addi_info['used_quota_info']);
					$sql_update = "update `saas_cdiscount_vip_user` set `addi_info`=:addi_info where `puid`=:puid ";
					$command = \Yii::$app->db->createCommand($sql_update);
					$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
					$command->bindValue ( ':addi_info', json_encode($addi_info), \PDO::PARAM_STR);
					$command->execute();
				}
			}
		}catch (\Exception $e){
			return array('success'=>false,'message'=>"clear CdTerminatorVipQuotaSnapshot Exception:".$e->getMessage());
		}
		return ['success'=>true,'message'=>''];
	}
	
	/*
	 * 支付接口调用，用户支付CD跟卖终结者VIP之后的处理
	 * @params	int		$puid	
	 * @params	array	$params		//付款模块传如的参数,like : Array ( [value] =>VIP1 [use] => 1 [delay] => 0 )
	 * 													value是用来告诉你是哪一个套餐。
	 *													use为1是开通/0就是取消。
	 *													delay就是是否立即生效。
	 * @return	array('success'=>boolean,'message'=>'')
	 * @author	lzhl	2016/06/14	初始化
	 */
	public static function CdTerminatorVipPaymentResponse($puid,$params){
		$old_rank=0;
		//$appName='cdOfferTerminator';
		
		if(empty($params['use'])){//取消了vip
			$new_rank = 0;
		}else{//vip等级变更
			if(!empty($params['value'])){
				$vipStr = strtolower($params['value']);
				$vipStr = str_replace('vip', '', $vipStr);
				$new_rank = (int)$vipStr;
			}
			if(empty($new_rank))
				return ['success'=>false,'message'=>'vip等级丢失，回调失败！'];
			if(!in_array((string)$new_rank, array_keys(self::$CdiscountTerminatorVipRank)))
				return ['success'=>false,'message'=>'vip等级不在有效等级范围，回调失败！'];
		}
		
		$sql = "select * from `saas_cdiscount_vip_user` where `puid`=:puid";
		$command = \Yii::$app->db->createCommand($sql);
		$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
		$record = $command->queryOne();
		if(!empty($record)){
			$old_rank = empty($record['vip_rank'])?0:(int)$record['vip_rank'];
		}
		//vip等级无变化
		if($new_rank==$old_rank){
			return ['success'=>true,'message'=>'vip等级没有变化，无需操作。'];
		}
		//vip等级上升
		if($new_rank > $old_rank){
			//upgrade,直接更改上限。
			$rtn = self::setCdTerminatorVipRank($puid, $new_rank);
			return $rtn;
		}
		//vip等级下降
		if($new_rank < $old_rank){
			//downgrade
			if(empty($params['delay'])){
				//即时修改,或 到期自动修改
				$rtn = self::setCdTerminatorVipRank($puid, $new_rank);
				if($rtn['success']){
					//对超出quota的关注和爆款进行强制失效
					//如果前端调用，可能导致超值
					CdiscountOfferTerminatorHelper::unActiveOverQuotaTerminatorOfferWhenVipRankDown($puid, $old_rank, $new_rank);
				}
				return $rtn;
			}
			if(!empty($params['delay']) && $params['delay']==1){
				//延迟修改。提示成功并提示用户更改爆款和关注，直到不超出上限(提示需要取消的数量)
				//$rtn = self::setCdTerminatorVipRank($puid, $new_rank);//延迟修稿时，暂不修改vip信息
				return ['success'=>true,'message'=>''];
			}
		}
		
	}
	
	/*
	 * 设置CD用户订单拉取起始时间点
	 * $uids	:	int or array, -1=all user 
	 */
	public static function setCdiscountUserFetchOrderTime($uids,$time){
		if(empty($uids))
			return '';
		
		if (is_numeric($time) ){
			$time = date('Y-m-d H:i:s',$time);
		}
		
		$sql = "UPDATE `saas_cdiscount_user` SET `last_order_success_retrieve_time`='$time' WHERE ";
		if(!is_array($uids)){
			if( (string)$uids=='-1'){
				$sql .= " 1 ";
			}else {
				$sql .= "`uid`=$uids";
			}
		}else{
			$sql .= "`uid` in (" .implode(',', $uids). ")";
		}
		
		return $sql;
	}
}