<?php
namespace eagle\modules\platform\helpers;

use eagle\models\SaasEbayUser;
use yii\data\Pagination;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\models\EbayDeveloperAccountInfo;
use eagle\modules\platform\helpers\EbayVipHelper;
use eagle\models\SaasEbayVip;
use eagle\modules\listing\models\EbayAutoInventory;
use eagle\modules\listing\models\EbayAutoTimerListing;

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
 * 平台管理模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/EbayAccounts
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class EbayAccountsHelper
{
	/**
	 +----------------------------------------------------------
	 * 获取物流商列表数据(系统表与用户表)
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param sort			排序字段
	 * @param order			排序类似 asc/desc
	 * @param queryString	其他条件
	 +----------------------------------------------------------
	 * @return				物流商数据列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpList( $sort , $order )
	{
        try{
            //本站当前用户ID
            $uid = \Yii::$app->user->id;
            $query = SaasEbayUser::find()->where("uid = '$uid'");
            
			$pagination = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $query->count(),
			]);
			
			$ebayUserList = $query
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy($sort.' '.$order)
			->asArray()
			->all();
			
            foreach($ebayUserList as &$ebayUser)
                $ebayUser['expiration_time'] = date('Y-m-d', $ebayUser['expiration_time']);

            return array( 'ebayUserList'=>$ebayUserList , 'pagination'=>$pagination);
        }catch (\Exception $e) {
        	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .', 平台绑定错误日志： '.$e->getMessage() , "file");
//             SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
            return $e->getMessage();
        }
	}

	/**
	 +----------------------------------------------------------
	 * 获取一个物流商的详细数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param key			用户表中物流商id
	 +----------------------------------------------------------
	 * @return				物流商详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public static function helpEdit($pk) {
        try{
		    return SaasEbayUser::model()->findByPk($pk);
        }catch (Exception $e) {
            SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
            return $e;
        }
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
        try{
            //判断是否更新同步信息状态表
            /*
            if(isset($post['ebay_select'])){
                switch($post['ebay_select']){
                    //订单
                case 'order_status': $type = 1; break;
                //刊登
                case 'item_status': $type = 2; break;
                //站内信
                case 'message_status': $type = 3; break;
                //评价
                case 'feedback_status': $type = 4; break;
                //售前纠纷Dispute
                case 'dispute_status': $type = 5; break;
                //全部纠纷UserCase
                case 'usercase_status': $type = 6; break;
                }
                $find = SaasEbayAutosyncstatus::model()->findByAttributes(array('ebay_uid'=>$post['ebay_uid'], 'type'=>$type));
                $find->status = $post['is_active'];
                return $find->save();
            }
            */
            $ebay_uid = $post['ebay_uid'];
            unset($post['ebay_uid']);
            return SaasEbayUser::model()->updateByPk($ebay_uid, $post);
        }catch (Exception $e) {
            SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
            return $e;
        }
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
        try{
            foreach($keys as $key){
                $ebayUser = SaasEbayUser::findOne($key);
				$sellerid = $ebayUser->selleruserid;
                $user_info = \Yii::$app->user->identity;
                $uid = $user_info['puid'] == 0 ? $user_info['uid'] : $user_info['puid'];
                $listDevAccountID = $ebayUser->listing_devAccountID; //刊登开发者账号
                if (!empty($listDevAccountID)){
                	EbayDeveloperAccountInfo::updateAllCounters(['used'=>'-1'] , ['account_id'=>$listDevAccountID]);
                }
                $devAccountID = $ebayUser->DevAcccountID;//订单开发者账号
                if (!empty($devAccountID)){
                	EbayDeveloperAccountInfo::updateAllCounters(['used'=>'-1'] , ['account_id'=>$devAccountID]);
                }
                SaasEbayUser::findOne($key);
                $re = $ebayUser->delete();
//                 if($re)PlatformAccountsLogHelper::AccountsLog_Create('卖家账号删除','Ebay',$ebayUser->selleruserid,$uid);
            }
            //成功删除后回调对应的函数
            PlatformAccountApi::callbackAfterDeleteAccount('ebay',$uid,['site_id'=>$key ,'selleruserid'=>$sellerid]);
            EbayAccountsApiHelper::unbindEbayDeveloperAccount($sellerid);
            SaasEbayVip::deleteAll('ebay_uid=:ebay_uid', array(':ebay_uid'=>$keys));
            EbayAutoInventory::deleteAll('ebay_uid=:ebay_uid', array(':ebay_uid'=>$keys));
            EbayAutoTimerListing::deleteAll('ebay_uid=:ebay_uid', array(':ebay_uid'=>$keys));
            return SaasEbayAutosyncstatus::deleteAll('ebay_uid=:ebay_uid', array(':ebay_uid'=>$keys));
        }catch (\Exception $e) {
//             SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
        	\Yii::info("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： $e ","file");
            return $e;
        }
	}

	/**
	 *
	 *  eBbay  token
	 * @date 2014-1-13
	 */
	public static function saveEbayToken($account_id, $selleruserid, $token, $HardExpirationTime, $devID){
        try{
            $M=SaasEbayUser::find()->where("account_id=:a and selleruserid=:b",array(':a'=>$account_id,':b'=>$selleruserid))->one();
            
            if(empty($M)){
                $M = new SaasEbayUser;
                $M->account_id=$account_id;
                $M->uid=$account_id;
                $M->selleruserid=$selleruserid;
                $M->create_time=time();
                $M->update_time=time();
            }
            $M->token=$token;
            $M->item_status=1;
            $M->expiration_time=$HardExpirationTime;
            $M->update_time=time();
            $M->DevAcccountID = $devID;
            $M->error_message=''; // 清空错误信息
            
            //同步绑定刊登
            $M->listing_token=$token;
            $M->listing_expiration_time=$HardExpirationTime;
            $M->listing_status = 0;
            $M->listing_update_time=time();
            $M->listing_devAccountID = $devID;
            
            // 保存成功写日志 
            if ($M->save()){
            	$logRT = EbayAccountsApiHelper::addEbayBindingLog($M->uid, $M->selleruserid, $M->DevAcccountID);
            	if ($logRT['success'] == false){
            		\Yii::error(__FUNCTION__." Error :".$logRT['message'],'file');
            	}
            }
            return $M->ebay_uid;
        }catch (\Exception $e) {
//             SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
        	\Yii::info("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： $e ","file");
            return $e;
        }
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存 刊登 专用的token
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @return keys		订单id集合数组
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/02				初始化
	 +----------------------------------------------------------
	 **/
	public static function saveListingEbayToken($account_id, $selleruserid, $token, $HardExpirationTime, $devID){
		try{
			$M=SaasEbayUser::find()->where("account_id=:a and selleruserid=:b",array(':a'=>$account_id,':b'=>$selleruserid))->one();
			
			if(empty($M)){
				$M = new SaasEbayUser;
				$M->account_id=$account_id;
				$M->uid=$account_id;
				$M->selleruserid=$selleruserid;
				$M->create_time=time();
				$M->listing_update_time=time();
			}
			$M->listing_token=$token;
			$M->listing_expiration_time=$HardExpirationTime;
			$M->listing_status = 1;
			$M->listing_update_time=time();
			if ($M->listing_devAccountID != $devID){
				$OriginDevID = $M->listing_devAccountID;
			}
			$M->listing_devAccountID = $devID;
			// 保存成功写日志
			if ($M->save()){
				$logRT = EbayAccountsApiHelper::addEbayBindingLog($M->uid, $M->selleruserid, $M->listing_devAccountID);
				//release dev id 
				if (!empty($OriginDevID)){
					$rt = EbayDeveloperAccountInfo::updateAllCounters(['used'=>-1 ],['account_id'=>$OriginDevID]);
				}
				//release dev id
				$rt = EbayDeveloperAccountInfo::updateAllCounters(['used'=>1 ],['account_id'=>$devID]);
				if ($logRT['success'] == false){
					\Yii::error(__FUNCTION__." Error :".$logRT['message'],'file');
				}
			}
			return $M->ebay_uid;
		}catch (\Exception $e) {
			//             SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
			\Yii::info("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： $e ","file");
			return $e;
		}
	} //end of function saveListingEbayToken
	
	


	/**
	 * 类型  type
	 * 1 : order 订单
	 * 2 : item  商品
	 * 3 : message  站内信
	 * 4 : feedback  评论
	 * 5 : dispute  纠纷
	 * 6 : ebp
     * 7 : item拉取
     * 8 : best offer
     * 9 : 在线数量检查
     * 10: 补货
     * 11:定时刊登
	 */


	/**
	 * 添加新的eBay账号
	 * @param SaasEbayUser  $EU
	 * @author lxqun
	 * @date 2014-3-31
	 */
	static function AddNewEbayUser($ebay_uid, $selleruserid){
        try{
            SaasEbayAutosyncstatus::deleteAll('selleruserid=:name', array(':name'=>$selleruserid));
            //@willage-2017-03-27
            SaasEbayVip::deleteAll('selleruserid=:name', array(':name'=>$selleruserid));
            //将新绑定帐号的六种状态写入managedb.saas_ebay_autosyncstatus
            $sql = "INSERT INTO `saas_ebay_autosyncstatus` (`selleruserid`, `ebay_uid`, `type`, `status`, `status_process`, `lastrequestedtime`, `lastprocessedtime`, `created`, `updated`) VALUES ";
            $time = time();
            for($i=1; $i<=7; $i++){
                $sql .= "('$selleruserid', $ebay_uid, $i, 1, 0, 0, 0, $time, $time),";
            }
            //@willage-2017-03-27 添加自动补货相关JOB,(type-8属于bestoffer,保留)
            $sql .= "('$selleruserid', $ebay_uid, 9, 1, 0, 0, 0, $time, $time),";
            $sql .= "('$selleruserid', $ebay_uid, 10, 1, 0, 0, 0, $time, $time),";
            //@willage-2017-04-12 添加定时刊登相关JOB,(type-8属于bestoffer,保留)
            $sql .= "('$selleruserid', $ebay_uid, 11, 1, 0, 0, 0, $time, $time),";
            $sql = rtrim($sql, ',');
            //连接并写入数据库
            $connection = \Yii::$app->db;
            \Yii::info("AddNewEbayUser:".$sql, "file");
            
            $command = $connection->createCommand($sql);
            $rt = $command->execute();

            //@willage-2017-03-27
            $uid=\Yii::$app->user->identity->getParentUid();
            EbayVipHelper::helpCreate('inventory',$selleruserid,$ebay_uid,$uid);


            if ($rt){
            	//成功增加账号后回调对应的函数
            	PlatformAccountApi::callbackAfterRegisterAccount('ebay',$uid,['selleruserid'=>$selleruserid]);
            }
            //return $command->queryAll();
            return $rt;
        }catch (\Exception $e) {
//             SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
        	\Yii::info("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： $e ","file");
            return $e;
        }
	}


}
