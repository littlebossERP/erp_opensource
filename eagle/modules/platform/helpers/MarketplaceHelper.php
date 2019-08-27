<?php
namespace eagle\modules\platform\helpers;

use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\SaasAmazonUser;
use eagle\modules\amazon\apihelpers\AmazonProxyConnectApiHelper;
//use eagle\models\SaasAmazonAutosync;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\modules\util\helpers\TimeUtil;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: hxl <plokplokplok@163.com>
+----------------------------------------------------------------------
| Create Date: 2014-01-30
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/method
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class MarketplaceHelper
{
	// amazon店铺code和国家缩写的对应关系
	public static $AMAZON_MARKETPLACE_REGION_CONFIG = array(
			'A2EUQ1WTGCTBG2'=>"CA",
			'ATVPDKIKX0DER'=>"US",
			'A1PA6795UKMFR9'=>"DE",
			'A1RKKUPIHCS9HS'=>"ES",
			'A13V1IB3VIYZZH'=>"FR",
			'A21TJRUUN4KGV'=>"IN",
			'APJ6JRA9NG5V4'=>"IT",
			'A1F83G8C2ARO7P'=>"UK",
			'A1VC38T7YXB528'=>"JP",
			'AAHKV2X7AFYLW'=>"CN",
			'A1AM78C64UM0Y8'=>"MX",
			'A39IBJ37TRP1C6'=>"AU",
			'A2Q3Y263D00KWC'=>"BR",
			'A2VIGQ35RCS4UG'=>"AE",
			'A33AVAJ2PDY3EV'=>"TR",
	);
	
	/**
	 +----------------------------------------------------------
	 * 获取物流方式列表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param queryString	查询条件
	 +----------------------------------------------------------
	 * @return				订单数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpList($queryString)
	{
        try{
            if(empty($queryString['amazon_uid']))return;
            $data=SaasAmazonUserMarketplace::model()->findAllByAttributes(array('amazon_uid'=>$queryString['amazon_uid']));

            $data=json_decode(CJSON::encode($data),true);

            foreach($data as &$v)
            {
                $v['access_key_id'] = '';
                $v['secret_access_key'] = '';
                //$v['create_time'] = date('Y-m-d', $v['create_time']);
                $v['create_time'] = gmdate('Y-m-d H:i:s', $v['create_time']+8*3600);
                //$v['update_time'] = date('Y-m-d', $v['update_time']);
                $v['update_time'] = gmdate('Y-m-d H:i:s', $v['update_time']+8*3600);

                if (isset(self::$AMAZON_MARKETPLACE_REGION_CONFIG[$v['marketplace_id']])) $v['country_code']=self::$AMAZON_MARKETPLACE_REGION_CONFIG[$v['marketplace_id']];
                else $v['country_code']="";
            }
            return $data;
        }catch (Exception $e) {
            SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
            return $e;
        }
        
	}

	/**
	 +----------------------------------------------------------
	 * 获取一个物流方式的详细数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param key			物流方式id
	 +----------------------------------------------------------
	 * @return				物流方式详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function editData($pk) 
	{
        try{
		    return SaasAmazonUserMarketplace::model()->findByPk($pk)->attributes;
        }catch (Exception $e) {
            SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
            return $e;
        }
	}

	/**
	 +----------------------------------------------------------
	 * 更新已有marketplace 以及同步表数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		更新的数据
	 +----------------------------------------------------------
	 * @return				boolean 是否更新成功
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/04/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpUpdate($params) 
	{
        try{
            //检查更新的帐号是否已存在
         	//1.更新saas_amazon_user_marketplace表
			$marketplace = SaasAmazonUserMarketplace::find()->where(['amazon_uid'=>$params['amazon_uid'],'marketplace_id'=>$params['marketplace_id']])->one();
	        if(!empty($marketplace)){
				// dzt20190319
	        	// $marketplace->access_key_id = $params['access_key_id'];
	        	// $marketplace->secret_access_key = $params['secret_access_key'];
				$marketplace->mws_auth_token = $params['mws_auth_token'];
				
	        	$marketplace->is_active = $params['is_active'];
	        	$marketplace->update_time = time();
	        	if (!$marketplace->save(false)){
	        		\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',marketplace->save() '.print_r($marketplace->errors,true) , "file");
	        		return false;  // 出现异常，请联系小老板的相关客服
	        	}
	        }else{
	        	\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .'amazon_uid:'.$params['amazon_uid'].' marketplace_id'.$params['marketplace_id'].'record not exists.'  , "file");
	        	return false;  // 出现异常，请联系小老板的相关客服
	        }
			
			//2. 店铺级别的信息同时更新到amazon的订单同步信息数据表
			$ret=AmazonAccountsv2BaseHelper::UpdateSaasAmazonAutosyncV2($marketplace,$params['merchant_id']);
			return $ret;
        }catch (\Exception $e) {
        	\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ ."平台绑定错误日志： ".$e->getMessage() , "file");
        	return false;
        }
	}

	/**
	 +----------------------------------------------------------
	 * 插入一条物流方式数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		插入的数据（carrier_code为必需）
	 +----------------------------------------------------------
	 * @return				插入影响行数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpInsert($params,$needCheck=true) 
	{
		try{
			if ($needCheck){
				// dzt20190319
		        // if(!$params['marketplace_id'] or !$params['access_key_id'] or !$params['secret_access_key']) return false;
				if(!$params['marketplace_id'] or !$params['mws_auth_token']) return false;
				
		        //1.检查合法性
		        //1.1 检查是否已存在该帐号
		        $count = SaasAmazonUserMarketplace::find()->where(array(
		            'amazon_uid'=>$params['amazon_uid'], 'marketplace_id'=>$params['marketplace_id']
		        ))->count();
		        
		        //如果已存在该帐号，就返回1062
		        if($count > 0) {
		        	\Yii::error('Amazon,' . __CLASS__ . ',' . __FUNCTION__ .',amazon_uid:'.$params['amazon_uid'].' marketplace_id:'.$params['marketplace_id'],"file");
// 		        	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"",'amazon_uid:'.$params['amazon_uid'].' marketplace_id:'.$params['marketplace_id'],"Error");
		        	return false;
		        }
		        
		        //1.2 通过访问proxy，检查用户 输入的amazon的api信息是否ok
		        $saasAmazonUserObject = SaasAmazonUser::findOne($params['amazon_uid']);
		        
		        $config = array(
		        		'merchant_id' => $saasAmazonUserObject->merchant_id,
		        		'marketplace_id' => $params['marketplace_id'],
		                // dzt20190319
		        		// 'access_key_id' => $params['access_key_id'],
		        		// 'secret_access_key' => $params['secret_access_key'],
		                'mws_auth_token' => $params['mws_auth_token'],
		        );
		        
		        $ret = AmazonProxyConnectApiHelper::testAmazonAccount($config);
		        if ($ret === false) {
		        	\Yii::error('Amazon,' . __CLASS__ . ',' . __FUNCTION__ .',AmazonProxyConnectHelper::testAmazonAccount fails . Line '.__LINE__,"file");
// 		        	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","AmazonProxyConnectHelper::testAmazonAccount fails . Line ".__LINE__,"Error");
		        	return false;
		        }   
			}
	             
	        //2.写入saas_amazon_user_marketplace表
			$marketplace = new SaasAmazonUserMarketplace();
			$marketplace->amazon_uid = $params['amazon_uid'];
	        $marketplace->marketplace_id = $params['marketplace_id'];
	        // dzt20190319
	        // $marketplace->access_key_id = $params['access_key_id'];
	        // $marketplace->secret_access_key = $params['secret_access_key'];
	        $marketplace->mws_auth_token = $params['mws_auth_token'];
	        
	        $marketplace->is_active = $params['is_active'];
	        $marketplace->create_time = time();
	        $marketplace->update_time = time();
			if (!$marketplace->save(false)){
				\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',marketplace->save() '.print_r($marketplace->errors,true) , "file");
				return false;  // 出现异常，请联系小老板的相关客服
			}

			//4. 店铺级别的信息同时插入到amazon的订单同步信息数据表
			$ret=AmazonAccountsv2BaseHelper::InsertSaasAmazonAutosyncV2($marketplace,$params['merchant_id']);
			return $ret;

		}catch (\Exception $e) {
			\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .', 平台绑定错误日志：'.$e->getMessage() , "file");
			return $e;
		}
		return true;
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除物流方式数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param params		删除所依据的ID
	 +----------------------------------------------------------
	 * @return				删除影响行数
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpDelete($amazonUid,$marketplaceId)
	{
		\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .",amazonUid:$amazonUid,marketplaceId:$marketplaceId","file");
// 		SysLogHelper::SysLog_Create("Platform", __CLASS__,__FUNCTION__,"","amazonUid:$amazonUid,marketplaceId:$marketplaceId","Debug");		
        try{
        	SaasAmazonUserMarketplace::deleteAll('marketplace_id=:marketplace_id and amazon_uid=:amazon_uid',array(':marketplace_id'=>$marketplaceId,':amazon_uid'=>$amazonUid));
        	//TODO   最好判断是否正在运行。或者物理上不删除，只做标志。
        	// SaasAmazonAutosync::deleteAll('marketplace_id=:marketplace_id and amazon_user_id=:amazon_user_id',array(':marketplace_id'=>$marketplaceId,':amazon_user_id'=>$amazonUid));
        	
        	// dzt20190415 处理queue表
        	$saasV2Objs = SaasAmazonAutosyncV2::find()->where(['eagle_platform_user_id'=>$amazonUid, 'site_id'=>$marketplaceId])->all();
        	foreach ($saasV2Objs as $saasV2Obj){
        	    AmazonAccountsHelper::processQueue(3, $saasV2Obj->id);
        	}
        	
        	SaasAmazonAutosyncV2::deleteAll('site_id=:marketplace_id and eagle_platform_user_id=:amazon_user_id',array(':marketplace_id'=>$marketplaceId,':amazon_user_id'=>$amazonUid));

        	return true;
        }catch (\Exception $e) {
			\Yii::trace('Platform,' . __CLASS__ . ',' . __FUNCTION__ .', 平台绑定错误日志： $e ' , "file");
//         	SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
            return $e;
        }
	}
}
