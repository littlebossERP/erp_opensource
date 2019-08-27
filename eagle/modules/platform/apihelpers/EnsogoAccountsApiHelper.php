<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasEnsogoUser;
/**
 +------------------------------------------------------------------------------
 * ebay平台管理模块业务接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class EnsogoAccountsApiHelper {
	/**
	+----------------------------------------------------------
	* 获取指定uid 的Ensogo账号 当uid为空的时候则为返回的是全部账号
	+----------------------------------------------------------
	* @access static
	+----------------------------------------------------------
	* @param uid			uid
	+----------------------------------------------------------
	* @return				Ensogo user 表的详细数据
	+----------------------------------------------------------
	* log			name	date					note
	* @author		lkh		2016/01/16				初始化
	+----------------------------------------------------------
	**/
	public static function ListAccounts( $uid='' ) {
		$query = SaasEnsogoUser::find();
		if (!empty($uid)){
			$query->where(['uid'=>$uid]);
		}
		
		return $query->asArray()->all();
	}//end of ListAccount
	
	/**
	+----------------------------------------------------------
	* 获取指定site id 的Ensogo token
	+----------------------------------------------------------
	* @access static
	+----------------------------------------------------------
	* @invoking					 
	*	EnsogoAccountsApiHelper::RetrieveTokenBySiteID('1');
	+----------------------------------------------------------
	* @param uid			uid
	+----------------------------------------------------------
	* @return				
	*		 success        boolean true成功 false失败
	*		 message		string 失败信息
	*		 token   		Ensogo 的token
	+----------------------------------------------------------
	* log			name	date					note
	* @author		lkh		2016/01/16				初始化
	+----------------------------------------------------------
	**/
	public static function RetrieveTokenBySiteID( $site_id='' ) {
		$query = SaasEnsogoUser::find();
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
	 * 获取指定site id 的Ensogo 账号信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @invoking
	 *	EnsogoAccountsApiHelper::RetrieveAccountBySiteID('1');
	 +----------------------------------------------------------
	 * @param uid			uid
	 +----------------------------------------------------------
	 * @return
	 *		 success        boolean true成功 false失败
	 *		 message		string 失败信息
	 *		 account   		Ensogo 的 账号信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/16				初始化
	 +----------------------------------------------------------
	 **/
	public static function RetrieveAccountBySiteID( $site_id='' ) {
		$query = SaasEnsogoUser::find();
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
	 * 检查指定site id 的Ensogo 是否绑定成功
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @invoking
	 *	EnsogoAccountsApiHelper::checkAccountBindingOrNot('1');
	 +----------------------------------------------------------
	 * @param uid			uid
	 +----------------------------------------------------------
	 * @return
	 *		 success        boolean true成功 false失败   是否绑定 成功
	 *		 message		string 失败信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/16				初始化
	 +----------------------------------------------------------
	 **/
	public static function checkAccountBindingOrNot($site_id=''){
		$query = SaasEnsogoUser::find();
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
	 +----------------------------------------------------------
	 * 生成指定长度的字符串
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @invoking
	 *	EnsogoAccountsApiHelper::getRandChar(6);
	 +----------------------------------------------------------
	 * @param $length			长度
	 +----------------------------------------------------------
	 * @return
	 *		 success        boolean true成功 false失败   是否绑定 成功
	 *		 message		string 失败信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/16				初始化
	 +----------------------------------------------------------
	 **/
	static public function getRandChar($length){
		$str = null;
		//$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
		$strPol = "0123456789";
		$max = strlen($strPol)-1;
	
		for($i=0;$i<$length;$i++){
			$str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
		}
	
		return $str;
	}
	
	
	
	
	
	
}