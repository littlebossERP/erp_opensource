<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasWishUser;
/**
 +------------------------------------------------------------------------------
 * ebay平台管理模块业务接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class WishAccountsApiHelper {
	/**
	+----------------------------------------------------------
	* 获取指定uid 的wish账号 当uid为空的时候则为返回的是全部账号
	+----------------------------------------------------------
	* @access static
	+----------------------------------------------------------
	* @param uid			uid
	+----------------------------------------------------------
	* @return				wish user 表的详细数据
	+----------------------------------------------------------
	* log			name	date					note
	* @author		lkh		2015/11/03				初始化
	+----------------------------------------------------------
	**/
	public static function ListAccounts( $uid='' ) {
		$query = SaasWishUser::find();
		if (!empty($uid)){
			$query->where(['uid'=>$uid]);
		}
		
		return $query->asArray()->all();
	}//end of ListAccount
	
	/**
	+----------------------------------------------------------
	* 获取指定site id 的wish token
	+----------------------------------------------------------
	* @access static
	+----------------------------------------------------------
	* @invoking					 
	*	WishAccountsApiHelper::RetrieveTokenBySiteID('1');
	+----------------------------------------------------------
	* @param uid			uid
	+----------------------------------------------------------
	* @return				
	*		 success        boolean true成功 false失败
	*		 message		string 失败信息
	*		 token   		wish 的token
	+----------------------------------------------------------
	* log			name	date					note
	* @author		lkh		2015/11/16				初始化
	+----------------------------------------------------------
	**/
	public static function RetrieveTokenBySiteID( $site_id='' ) {
		$query = SaasWishUser::find();
		if (!empty($site_id)){
			$query->where(['site_id'=>$site_id]);
		}
		
		$result = $query->asArray()->one();
		
		if (!empty($result)){
			return ['success'=>true,'message'=>'' , 'token'=>$result['token']] ;
		}else{
			return ['success'=>false,'message'=>'site id 无效' , 'token'=>''] ;
		}
	}//end of ListAccount
	
	
	/**
	 +----------------------------------------------------------
	 * 获取指定site id 的wish 账号信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @invoking
	 *	WishAccountsApiHelper::RetrieveAccountBySiteID('1');
	 +----------------------------------------------------------
	 * @param uid			uid
	 +----------------------------------------------------------
	 * @return
	 *		 success        boolean true成功 false失败
	 *		 message		string 失败信息
	 *		 account   		wish 的 账号信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/16				初始化
	 +----------------------------------------------------------
	 **/
	public static function RetrieveAccountBySiteID( $site_id='' ) {
		$query = SaasWishUser::find();
		if (!empty($site_id)){
			$query->where(['site_id'=>$site_id]);
		}
	
		$result = $query->asArray()->one();
	
		if (!empty($result)){
			return ['success'=>true,'message'=>'' , 'account'=>$result] ;
		}else{
			return ['success'=>false,'message'=>'site id 无效' , 'account'=>''] ;
		}
	}//end of RetrieveAccountBySiteID
	
	
	/**
	 +----------------------------------------------------------
	 * 检查指定site id 的wish 是否绑定成功
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @invoking
	 *	WishAccountsApiHelper::checkAccountBindingOrNot('1');
	 +----------------------------------------------------------
	 * @param uid			uid
	 +----------------------------------------------------------
	 * @return
	 *		 success        boolean true成功 false失败   是否绑定 成功
	 *		 message		string 失败信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/15				初始化
	 +----------------------------------------------------------
	 **/
	public static function checkAccountBindingOrNot($site_id=''){
		$query = SaasWishUser::find();
		if (!empty($site_id)){
			$query->where(['site_id'=>$site_id]);
		}
		
		$result = $query->asArray()->one();
		
		if (!empty($result)){
			/*
			 * token 与 refresh token 同时 不为空的情况 下则认为是绑定 成功 （refresh token 只有v2 的绑定 才会有的） token （v1与v2都会有，所以只用token 做验证可能v1绑定 了的都可以通过）,
			 * 因为 token都是都是api生成 ，解绑都是直接删除记录， 所以暂时不需要考虑 api验证token 是否有效， 不为空的都是有效
			 * */
			if (!empty($result['token']) && ! empty($result['refresh_token'])){
				return ['success'=>true,'message'=>'绑定成功' ] ;
			}else{
				return ['success'=>false,'message'=>'绑定失败' ] ;
			}
		}else{
			return ['success'=>false,'message'=>'site id 无效' ] ;
		}
	}//end of checkAccountBindingOrNot
	
	
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回wish账号商品同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/02			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWishProductSyncInfo($account_key,$uid){
		$account = SaasWishUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
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
	 * 返回Wish账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/02			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWishOrderSyncInfo($account_key,$uid){
		$account = SaasWishUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
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
	 * 返回Wish账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/12/02			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getWishMessageSyncInfo($account_key,$uid){
		return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取指定uid 的aliexpress 账号 当uid为空的时候则为返回的是全部账号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param uid			uid (int)
	 +----------------------------------------------------------
	 * @return				aliexpress user 表的详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/07/20				初始化
	 +----------------------------------------------------------
	 **/
	static public function getAllAccounts($uid = 0){
	    if (empty($uid)){
	        $userInfo = \Yii::$app->user->identity;
	        if ($userInfo['puid']==0){
	            $uid = $userInfo['uid'];
	        }else {
	            $uid = $userInfo['puid'];
	        }
	    }
	
	    return SaasWishUser::find()->where(['uid'=>$uid ])->asArray()->all();
	}//end of getAllAccounts
	
	/**
	 +----------------------------------------------------------
	 * 获取指定uid 的wish 账号 当uid为空的时候则为返回的是全部账号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param uid			uid (int)
	 +----------------------------------------------------------
	 * @return				aliexpress user 表的详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/08/02				初始化
	 +----------------------------------------------------------
	 **/
	static public function listActiveAccounts($uid = 0){
	    if (empty($uid)){
	        $userInfo = \Yii::$app->user->identity;
	        if ($userInfo['puid']==0){
	            $uid = $userInfo['uid'];
	        }else {
	            $uid = $userInfo['puid'];
	        }
	    }
	    	
	    return SaasWishUser::find()->where(['uid'=>$uid , 'is_active'=>1])->asArray()->all();
	}//end of listAccounts
	
	/*
	 * 设置wish用户订单拉取起始时间点
	 * $uids	:	int or array, -1=all user
	 */
	public static function setWishUserFetchOrderTime($uids,$time){
	    if(empty($uids))
	        return '';
	
	    if (is_numeric($time) ){
	        $time = date('Y-m-d H:i:s',$time);
	    }
	
	    $sql = "UPDATE `saas_wish_user` SET `last_order_success_retrieve_time`='$time' WHERE ";
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
	
	//获取wish账号别名关系映射
	static public function getWishAliasAccount($selleruserids){
		$uid = \Yii::$app->user->identity->getParentUid();
		
		$wishUserList = SaasWishUser::find()->select('store_name,store_name_alias')->where("uid = '$uid'")->asArray()->all();
	
		$tmp_wishUserList = array();
	
		if(count($wishUserList) > 0){
			foreach ($wishUserList as $wishUserList_Val){
				$tmp_wishUserList[$wishUserList_Val['store_name']] = $wishUserList_Val['store_name_alias'];
			}
		}
	
		$selleruserids_new = array();
	
		if(count($selleruserids) > 0){
			foreach ($selleruserids as $tmp_selleruseridsKey => $tmp_selleruseridsVal){
				if(isset($tmp_wishUserList[$tmp_selleruseridsKey])){
					if($tmp_wishUserList[$tmp_selleruseridsKey] != ''){
						$selleruserids_new[$tmp_selleruseridsKey] = $tmp_wishUserList[$tmp_selleruseridsKey];
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