<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasPriceministerUser;
/**
 +------------------------------------------------------------------------------
 * Priceminister绑定的账号接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class PriceministerAccountsApiHelper {
	/**
	+----------------------------------------------------------
	* 获取指定uid 的Priceminister账号 当uid为空的时候则为返回的是全部账号
	+----------------------------------------------------------
	* @access static
	+----------------------------------------------------------
	* @param uid			uid
	+----------------------------------------------------------
	* @return				Priceminister user 表的详细数据
	+----------------------------------------------------------
	* log			name		date				note
	* @author		lzhl		2016/3/30			初始化
	+----------------------------------------------------------
	**/
	public static function ListAccounts( $uid='' ) {
		$query = SaasPriceministerUser::find();
		if (!empty($uid)){
			$query->where(['uid'=>$uid]);
		}
		
		return $query->asArray()->all();
	}//end of ListAccount
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Priceminister账号商品同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/3/30			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getPriceministerProductSyncInfo($account_key,$uid){
		$account = SaasPriceministerUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
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
	 * 返回Priceminister账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/3/30			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getPriceministerOrderSyncInfo($account_key,$uid){
		$account = SaasPriceministerUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
		if($account<>null){
			$result['is_active']=$account->is_active;
			$result['last_time']=strtotime($account->last_order_retrieve_time);
			$result['message']=$account->order_retrieve_message;
				
			if( (time()-$result['last_time']=strtotime($account->last_order_retrieve_time))<3600 )
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
	 * 返回Priceminister账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/01			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getPriceministerMessageSyncInfo($account_key,$uid){
		return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
	}
	
	/*
	 * 设置PM用户订单拉取起始时间点
	 * $uids	:	int or array, -1=all user 
	*/
	public static function setPriceministerUserFetchOrderTime($uids,$time){
		if(empty($uids))
			return '';
	
		if (is_numeric($time) ){
			$time = date('Y-m-d H:i:s',$time);
		}
	
		$sql = "UPDATE `saas_priceminister_user` SET `last_order_success_retrieve_time`='$time' WHERE ";
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