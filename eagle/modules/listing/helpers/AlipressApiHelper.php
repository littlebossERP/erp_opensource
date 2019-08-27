<?php
/*+----------------------------------------------------------------------
| 小老板,速卖通刊登相关
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: akirametero
+----------------------------------------------------------------------
| Create Date: 2016-05-12
+----------------------------------------------------------------------
 */
namespace eagle\modules\listing\helpers;
use yii;
use yii\data\Pagination;


use yii\db\mssql\PDO;

use console\helpers\AliexpressHelper as AlipressHelper;

use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use common\api\aliexpressinterface\AliexpressInterface_Helper;

use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\manual_sync\models\Queue;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\SaasAliexpressUser;

use eagle\modules\listing\models\AliexpressListing;
use eagle\modules\listing\models\AliexpressListingDetail;
use eagle\modules\listing\models\AliexpressCategory;
use eagle\models\AliexpressPromiseTemplate;
use eagle\models\AliexpressFreightTemplate;
use eagle\models\AliexpressDetailModule;
use eagle\modules\listing\models\AliexpressGroupInfo;
use eagle\models\AliexpressListingDetail as ALDMod;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;

/**
 *
 +------------------------------------------------------------------------------
 *速卖通相关操作
 +------------------------------------------------------------------------------
 * @package
 * @subpackage  Exception
 * @author akirametero
 +------------------------------------------------------------------------------
 */
class AlipressApiHelper {
   private static $_mod ='online';//online/test


    /**
     * 获取token的公共方法
     * @author akirametero
     */
    private static function getToken( $sellerloginid ){
        if( self::$_mod=='test' ){
            $token= '91c58f9f-ddaa-4b73-a013-a329238270dc';
            return $token;
        }elseif( self::$_mod=='online' ){
            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $sellerloginid );
            return $access_token;
        }
    }
    //end function

    /**
     * 同步店铺速卖通商品及商品详情
     * $sellerloginid
     * @author akirametero
     */
    public static function syncAlipressProtuctDetail( $queue,$sellerloginid ){
    	//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
    	$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($sellerloginid);
    	if($is_aliexpress_v2){
    		$result = self::syncAlipressProtuctDetailV2($queue, $sellerloginid);
    		return $result;
    	}
    	//****************判断此账号信息是否v2版    end*************
    	
        //echo 'begin--',date("H:i:s");
        //$sellerloginid= $queue->site_id;
        $connection=Yii::$app->db_queue;

        if( $sellerloginid=='' ){
            $queue->data(['error'=>'店铺未找到']);
            return false;
        }

        //获取puid,切换数据库准备
        $rs_user= SaasAliexpressUser::find()->where( ['sellerloginid'=>$sellerloginid] )->asArray()->one();
        $puid= $rs_user['uid'];
        //$puid= 1;
        if( $puid=='' ){
            $queue->data(['error'=>'用户未找到']);
            return false;
        }
        //检查授权
        $auth_eof = AliexpressInterface_Auth::checkToken($sellerloginid);
        if( $auth_eof===false ){
            $queue->data(['error'=>'授权过期']);
            return false;
        }
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken($sellerloginid);


        $api->access_token = $access_token;

        /******************************************************************************************************/

        //获取用户的商品列表
        $nowTime = time();
        //type-(onSelling-上架销售中/offline-下架/auditing-审核中/editingRequired-审核不通过)
        $type_arr= array('onSelling','offline','auditing','editingRequired');
        //$hasDeleted = false;
        //AliexpressListing::deleteAll( [ 'selleruserid'=>$sellerloginid ] );
        //AliexpressListingDetail::deleteAll( [ 'selleruserid'=>$sellerloginid ] );

        foreach( $type_arr as $kyp=>$type_vss ){
            //break;
            //echo 'begin--',$type_vss,'----',date("H:i:s");
            $page= 1;
            $pageSize= 100;
            $type= $type_vss;

            do {
                $param = array(
                    'currentPage' => $page,
                    'pageSize' => $pageSize,
                    'productStatusType' => $type,
                );
                //echo 'begin--prolist','----',date("H:i:s");
                $prolist = $api->findProductInfoListQuery($param);
                //echo 'end--prolist','----',date("H:i:s");
                //判断是否有商品
                if ( isset ($prolist ['productCount']) && isset($prolist ['aeopAEProductDisplayDTOList']) ) {

                    $batchInsertDatas= array();
                    foreach ($prolist ['aeopAEProductDisplayDTOList'] as $one) {
                        $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                        $gmtModified = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtModified']);
                        $WOD = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['wsOfflineDate']);
                        if ($one['imageURLs']!= '') {
                            $photo_arr= explode( ';',$one['imageURLs'] );
                            $photo_primary= $photo_arr[0];
                            if( count( $photo_arr )==1 ){
                                $imageurls= '';
                            }else{
                                unset($photo_arr[0]);
                                $imageurls= implode(";",$photo_arr);
                            }
                        } else {
                            $photo_primary= '';
                            $imageurls= '';
                        }

                        $batchInsertData = array();
                        $batchInsertData["productid"] = $one['productId'];
                        //$batchInsertData["freight_template_id"] = isset($one['freightTemplateId']) ? $one['freightTemplateId'] : '';
                        $batchInsertData["owner_member_seq"] = $one['ownerMemberSeq'];
                        $batchInsertData["subject"] = $one['subject'];
                        $batchInsertData["photo_primary"] = $photo_primary;
                        $batchInsertData["imageurls"] = $imageurls;
                        $batchInsertData["selleruserid"] = $sellerloginid;
                        $batchInsertData["ws_offline_date"] = $WOD;
                        $batchInsertData["product_min_price"] = $one['productMinPrice'];
                        $batchInsertData["ws_display"] = $one['wsDisplay'];
                        $batchInsertData["product_max_price"] = $one['productMaxPrice'];
                        $batchInsertData["gmt_modified"] = $gmtModified;
                        $batchInsertData["gmt_create"] = $gmtCreate;
                        $batchInsertData["sku_stock"] = 0;
                        $batchInsertData["created"] = $nowTime;
                        $batchInsertData["updated"] = $nowTime;
                        $batchInsertData["product_status"] = ceil($kyp+1);

                        //$batchInsertDatas[] = $batchInsertData;

                        //当前productid的商品状态和数据库中的对比,如果不一致,则删除listing和detail中的
                        $listingsx = AliexpressListing::find()->where(['productid' => $one['productId']])->asArray()->one();
                        //print_r ($listingsx);exit;
                        if( !empty( $listingsx ) ){
                            if( ceil($listingsx['product_status'])!=ceil($batchInsertData["product_status"]) ){
                                //删除
                                //echo '状态不一样删除:',PHP_EOL;
                                //echo $one['productId'].'--数据库中状态:'.$listingsx['product_status'].'--新状态:'.$batchInsertData["product_status"].PHP_EOL;
                                AliexpressListing::deleteAll(['productid' => $one['productId']]);
                                AliexpressListingDetail::deleteAll(['productid' => $one['productId']]);
                            }
                        }

                        $md5_json_data= md5( json_encode( $one ) );
                        //echo 'get-md5-begin',date("H:i:s"),PHP_EOL;
                        $query = $connection->createCommand("SELECT * FROM queue_product_info_md5 WHERE product_id= '".$one['productId']."' ")->query();
                        $re= $query->read();
                        if( empty( $re ) ){
                            //insert
                            $insert= "INSERT INTO queue_product_info_md5( `product_id`,`listen_md5`,`selleruserid` )VALUES( '".$one['productId']."','{$md5_json_data}','{$sellerloginid}' )";
                            $connection->createCommand( $insert )->execute();
                            AliexpressListing::deleteAll(['productid' => $one['productId']]);
                            AliexpressListingDetail::deleteAll(['productid' => $one['productId']]);
                            //echo 'MD5数据不存在删除:',$one['productId'],PHP_EOL;
                            $batchInsertDatas[] = $batchInsertData;
                        }else{
                            //当保存的md5 和 现在的md5 不一致,才修改等操作
                            $alerdy_save_md5= $re['listen_md5'];
                            if( $alerdy_save_md5!=$md5_json_data ) {
                                //update
                                $update = "UPDATE queue_product_info_md5 SET listen_md5='{$md5_json_data}',listen_detail_md5='' WHERE id=" . $re['id'];
                                $connection->createCommand($update)->execute();
                                //delete
                                //echo 'del1';exit;
                                AliexpressListing::deleteAll( ['productid'=>$one['productId']] );
                                AliexpressListingDetail::deleteAll( ['productid'=>$one['productId']] );
                                //echo 'MD5数据更新删除:',$one['productId'],PHP_EOL;
                                $batchInsertDatas[] = $batchInsertData;
                            }
                        }
                    }
                    //echo 'get-md5-end',date("H:i:s"),PHP_EOL;
                    //end foreach
                    //echo 'batchInsertDatas',PHP_EOL;
                    if( !empty( $batchInsertDatas ) ){
                        SQLHelper::groupInsertToDb("aliexpress_listing", $batchInsertDatas);
                    }
                }
                $page++;
                $p = isset($prolist['totalPage']) ? $prolist['totalPage'] : 0;
            }while( $page <= $p );
        }


        /******************************************************************************************************/
        $listings = AliexpressListing::find()->where(['selleruserid' => $sellerloginid])->asArray()->all();

        //start foreach
        foreach ($listings as $row) {
            //echo 'bbb--',date("H:i:s"),PHP_EOL;
            $productid = $row["productid"];
            if( ceil($productid)==0 ){
                continue;
            }

            $productInfo = $api->findAeProductById(array('productId' => $productid));

            //echo 'eee-',date("H:i:s"),PHP_EOL;
            if (empty($productInfo['success']) || $productInfo['success'] != 1) {
                //echo 'del2';exit;
                //商品应该被删除了,没有获取到任何信息,删除listing表中的数据
                AliexpressListing::deleteAll(['productid' => $productid]);
                AliexpressListingDetail::deleteAll(['productid' => $productid]);
                //删除对应的md5表数据
                $del= "DELETE FROM queue_product_info_md5 WHERE product_id='{$productid}' ";
                $connection->createCommand( $del )->execute();
                //print_r ($productInfo);
                //echo 'findAeProductById接口返回失败删除:',$productid,PHP_EOL;
                continue;
            }

            //加密详细信息
            $listen_detail_md5= md5( json_encode( $productInfo ) );
            //通过商品ID ,获取加密表数据
            $sql= "SELECT * FROM queue_product_info_md5 WHERE product_id='{$productid}' ";

            $query= $connection->createCommand( $sql )->query();
            $rs= $query->read();
            if( empty( $rs )  ){
                //echo 'ccccc1-',date("H:i:s"),PHP_EOL;
                $insert= "INSERT INTO queue_product_info_md5 (`product_id`,`listen_detail_md5`,`selleruserid`)VALUES ('{$productid}','{$listen_detail_md5}','{$sellerloginid}')";
                $connection->createCommand( $sql )->execute();

            }else{
                $alerdy_listen_detail_md5= $rs['listen_detail_md5'];
                if( $alerdy_listen_detail_md5!=$listen_detail_md5 ){
                    //update
                    $sql= "UPDATE queue_product_info_md5 SET listen_detail_md5='{$listen_detail_md5}' WHERE id= ".$rs['id'];
                    //echo $sql,PHP_EOL;
                    $connection->createCommand( $sql )->execute();
                    //delete
                    AliexpressListingDetail::deleteAll(['productid' => $productid]);
                    //echo 'detail的md5不存在删除:',$productid,PHP_EOL;
                }else{
                    //echo 'ccccc2-',date("H:i:s"),PHP_EOL;
                    continue;
                }
            }


            $aliexpressListDetail = new AliexpressListingDetail;
            $aliexpressListDetail->productid = $productid;

            $aliexpressListDetail->listen_id= $row['id'];

            if (!empty($productInfo["categoryId"])) {
                $aliexpressListDetail->categoryid = $productInfo["categoryId"];
            }

            $aliexpressListDetail->selleruserid = $sellerloginid;
            if (!empty($productInfo["productPrice"])) {
                $aliexpressListDetail->product_price = $productInfo["productPrice"];
            }

            if (!empty($productInfo["grossWeight"])) {
                $aliexpressListDetail->product_gross_weight = $productInfo["grossWeight"];
            }

            if (!empty($productInfo["packageLength"])) {
                $aliexpressListDetail->product_length = $productInfo["packageLength"];
            }

            if (!empty($productInfo["packageWidth"])) {
                $aliexpressListDetail->product_width = $productInfo["packageWidth"];
            }

            if (!empty($productInfo["packageHeight"])) {
                $aliexpressListDetail->product_height = $productInfo["packageHeight"];
            }

            if (!empty($productInfo["currencyCode"])) {
                $aliexpressListDetail->currencyCode = $productInfo["currencyCode"];
            }

            if (!empty($productInfo["aeopAeProductPropertys"])) {
                $aliexpressListDetail->aeopAeProductPropertys = json_encode($productInfo["aeopAeProductPropertys"]);
            } else {
                $aliexpressListDetail->aeopAeProductPropertys = json_encode(array());
            }

            if (!empty($productInfo["aeopAeProductSKUs"])) {
                $aliexpressListDetail->aeopAeProductSKUs = json_encode($productInfo["aeopAeProductSKUs"]);

                $arr_sku= $productInfo['aeopAeProductSKUs'];
                $skucode_arr= array();
                foreach( $arr_sku as $vss_sku ){
                    if( isset($vss_sku['skuCode']) && $vss_sku['skuCode']!='' ){
                        $skucode_arr[]= $vss_sku['skuCode'];
                    }
                }
                if( !empty( $skucode_arr ) ){
                    $skucode_str= implode(';',$skucode_arr);
                }else{
                    $skucode_str= '';
                }
                $aliexpressListDetail->sku_code= $skucode_str;

            } else {
                $aliexpressListDetail->aeopAeProductSKUs = json_encode(array());
                $aliexpressListDetail->sku_code= '';
            }

            if (!empty($productInfo["detail"])) {
                $aliexpressListDetail->detail = $productInfo["detail"];
            }

            if( !empty( $productInfo['deliveryTime'] ) ){
                $aliexpressListDetail->delivery_time = $productInfo['deliveryTime'];
            }

            if( !empty( $productInfo['packageType'] ) ){
                $aliexpressListDetail->package_type = $productInfo['packageType'];
            }

            if( !empty( $productInfo['lotNum'] ) ){
                $aliexpressListDetail->lot_num = $productInfo['lotNum'];
            }

            if( !empty( $productInfo['isPackSell'] ) ){
                $aliexpressListDetail->isPackSell = $productInfo['isPackSell'];
            }

            if( !empty( $productInfo['reduceStrategy'] ) ){
                $aliexpressListDetail->reduce_strategy = $productInfo['reduceStrategy'];
            }
            if( !empty( $productInfo['productUnit'] ) ){
                $aliexpressListDetail->product_unit = $productInfo['productUnit'];
            }

            if( !empty( $productInfo['wsValidNum'] ) ){
                $aliexpressListDetail->wsValidNum = $productInfo['wsValidNum'];
            }

            if( !empty( $productInfo['currencyCode'] ) ){
                $aliexpressListDetail->currencyCode = $productInfo['currencyCode'];
            }

            //if( !empty( $productInfo['promiseTemplateId'] ) ){
                $aliexpressListDetail->promise_templateid = $productInfo['promiseTemplateId'];
            //}

            if( !empty( $productInfo['groupIds'] ) ){
                $aliexpressListDetail->product_groups = implode(',',$productInfo['groupIds']);
            }

            $aliexpressListDetail->save(false);

            if( !empty( $productInfo['freightTemplateId'] ) ){
                AliexpressListing::updateAll( ['freight_template_id'=>$productInfo['freightTemplateId']],'id='.$row['id'] );
            }

            if( $queue!='' ){
                $queue->addProgress();
            }
            echo $productid.PHP_EOL;
        }

        //end foreach
        return true;

    }
    //end function

    /**
     +----------------------------------------------------------
     * 同步店铺速卖通商品及商品详情，v2
     +----------------------------------------------------------
     * @param	$buyerid		买家账号id
     +----------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/01/11		初始化
     +----------------------------------------------------------
     **/
    public static function syncAlipressProtuctDetailV2( $queue, $sellerloginid ){
    	$connection = Yii::$app->db_queue;
    	if( $sellerloginid == '' ){
    		$queue->data(['error'=>'店铺未找到']);
    		return false;
    	}
    
    	//获取puid,切换数据库准备
    	$rs_user = SaasAliexpressUser::find()->where( ['sellerloginid'=>$sellerloginid] )->asArray()->one();
    	if( empty($rs_user)){
    		$queue->data(['error'=>'用户未找到']);
    		return false;
    	}
    	$puid = $rs_user['uid'];
    	//检查授权
    	$auth_eof = AliexpressInterface_Helper_Qimen::checkToken($sellerloginid);
    	if( $auth_eof===false ){
    		$queue->data(['error'=>'授权过期']);
    		return false;
    	}
    	$api = new AliexpressInterface_Api_Qimen();
    	//$access_token = $api->getAccessToken ( $sellerloginid );
    	//$api->access_token = $access_token;

    	
    	/******************************************************************************************************/
    	//获取用户的商品列表
    	$nowTime = time();
    	$type_arr= array('onSelling','offline','auditing','editingRequired');
    	foreach( $type_arr as $kyp => $type_vss ){
    		$page = 1;
    		$pageSize = 100;
    		$type = $type_vss;
    
    		do {
    			$param = ['id' => $sellerloginid, 'aeop_a_e_product_list_query' => json_encode(['current_page' => $page, 'page_size' => $pageSize, 'product_status_type' => $type])];
    			$prolist = $api->findProductInfoListQuery($param);
    			//判断是否有商品
    			if ( isset ($prolist['product_count']) && isset($prolist['aeop_a_e_product_display_d_t_o_list']) ) {
    				$batchInsertDatas = array();
    				foreach ($prolist['aeop_a_e_product_display_d_t_o_list'] as $one) {
    					$gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmt_create']);
    					$gmtModified = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmt_modified']);
    					$WOD = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['ws_offline_date']);
    					if ($one['image_u_r_ls']!= '') {
    						$photo_arr= explode( ';',$one['image_u_r_ls'] );
    						$photo_primary= $photo_arr[0];
    						if( count( $photo_arr )==1 ){
    							$imageurls= '';
    						}else{
    							unset($photo_arr[0]);
    							$imageurls= implode(";",$photo_arr);
    						}
    					} else {
    						$photo_primary= '';
    						$imageurls= '';
    					}
    
    					$batchInsertData = array();
    					$batchInsertData["productid"] = $one['product_id'];
    					//$batchInsertData["freight_template_id"] = isset($one['freightTemplateId']) ? $one['freightTemplateId'] : '';
    					$batchInsertData["owner_member_seq"] = $one['owner_member_seq'];
    					$batchInsertData["subject"] = $one['subject'];
    					$batchInsertData["photo_primary"] = $photo_primary;
    					$batchInsertData["imageurls"] = $imageurls;
    					$batchInsertData["selleruserid"] = $sellerloginid;
    					$batchInsertData["ws_offline_date"] = $WOD;
    					$batchInsertData["product_min_price"] = $one['product_min_price'];
    					$batchInsertData["ws_display"] = $one['ws_display'];
    					$batchInsertData["product_max_price"] = $one['product_max_price'];
    					$batchInsertData["gmt_modified"] = $gmtModified;
    					$batchInsertData["gmt_create"] = $gmtCreate;
    					$batchInsertData["sku_stock"] = 0;
    					$batchInsertData["created"] = $nowTime;
    					$batchInsertData["updated"] = $nowTime;
    					$batchInsertData["product_status"] = ceil($kyp+1);
    
    					//$batchInsertDatas[] = $batchInsertData;
    
    					//当前productid的商品状态和数据库中的对比,如果不一致,则删除listing和detail中的
    					$listingsx = AliexpressListing::find()->where(['productid' => $one['product_id']])->asArray()->one();
    					//print_r ($listingsx);exit;
    					if( !empty( $listingsx ) ){
    						if( ceil($listingsx['product_status'])!=ceil($batchInsertData["product_status"]) ){
    							//删除
    							//echo '状态不一样删除:',PHP_EOL;
    							//echo $one['productId'].'--数据库中状态:'.$listingsx['product_status'].'--新状态:'.$batchInsertData["product_status"].PHP_EOL;
    							AliexpressListing::deleteAll(['productid' => $one['product_id']]);
    							AliexpressListingDetail::deleteAll(['productid' => $one['product_id']]);
    						}
    					}
    
    					$md5_json_data = md5( json_encode( $one ) );
    					//echo 'get-md5-begin',date("H:i:s"),PHP_EOL;
    					$query = $connection->createCommand("SELECT * FROM queue_product_info_md5 WHERE product_id= '".$one['product_id']."' ")->query();
    					$re = $query->read();
    					if( empty( $re ) ){
    						//insert
    						$insert= "INSERT INTO queue_product_info_md5( `product_id`,`listen_md5`,`selleruserid` )VALUES( '".$one['product_id']."','{$md5_json_data}','{$sellerloginid}' )";
    						$connection->createCommand( $insert )->execute();
    						AliexpressListing::deleteAll(['productid' => $one['product_id']]);
    						AliexpressListingDetail::deleteAll(['productid' => $one['product_id']]);
    						//echo 'MD5数据不存在删除:',$one['productId'],PHP_EOL;
    						$batchInsertDatas[] = $batchInsertData;
    					}else{
    						//当保存的md5 和 现在的md5 不一致,才修改等操作
    						$alerdy_save_md5 = $re['listen_md5'];
    						if( $alerdy_save_md5!=$md5_json_data ) {
    							//update
    							$update = "UPDATE queue_product_info_md5 SET listen_md5='{$md5_json_data}',listen_detail_md5='' WHERE id=" . $re['id'];
    							$connection->createCommand($update)->execute();
    							//delete
    							//echo 'del1';exit;
    							AliexpressListing::deleteAll( ['productid'=>$one['product_id']] );
    							AliexpressListingDetail::deleteAll( ['productid'=>$one['product_id']] );
    							//echo 'MD5数据更新删除:',$one['productId'],PHP_EOL;
    							$batchInsertDatas[] = $batchInsertData;
    						}
    					}
    				}
    				
    				if( !empty( $batchInsertDatas ) ){
    					SQLHelper::groupInsertToDb("aliexpress_listing", $batchInsertDatas);
    				}
    			}
    			$page++;
    			$p = isset($prolist['total_page']) ? $prolist['total_page'] : 0;
    		}while( $page <= $p );
    	}
    
    
    	/******************************************************************************************************/
    	$listings = AliexpressListing::find()->where(['selleruserid' => $sellerloginid])->asArray()->all();
    
    	//start foreach
    	foreach ($listings as $row) {
    		$productid = $row["productid"];
    		if( ceil($productid)==0 ){
    			continue;
    		}
    
    		$productInfo = $api->findAeProductById(array('id' => $sellerloginid, 'product_id' => $productid));
    		if (empty($productInfo['success']) || $productInfo['success'] != 1) {
    			//echo 'del2';exit;
    			//商品应该被删除了,没有获取到任何信息,删除listing表中的数据
    			AliexpressListing::deleteAll(['productid' => $productid]);
    			AliexpressListingDetail::deleteAll(['productid' => $productid]);
    			//删除对应的md5表数据
    			$del= "DELETE FROM queue_product_info_md5 WHERE product_id='{$productid}' ";
    			$connection->createCommand( $del )->execute();
    			//print_r ($productInfo);
    			//echo 'findAeProductById接口返回失败删除:',$productid,PHP_EOL;
    			continue;
    		}
    
    		//加密详细信息
    		$listen_detail_md5= md5( json_encode( $productInfo ) );
    		//通过商品ID ,获取加密表数据
    		$sql= "SELECT * FROM queue_product_info_md5 WHERE product_id='{$productid}' ";
    
    		$query= $connection->createCommand( $sql )->query();
    		$rs= $query->read();
    		if( empty( $rs )  ){
    			//echo 'ccccc1-',date("H:i:s"),PHP_EOL;
    			$insert= "INSERT INTO queue_product_info_md5 (`product_id`,`listen_detail_md5`,`selleruserid`)VALUES ('{$productid}','{$listen_detail_md5}','{$sellerloginid}')";
    			$connection->createCommand( $sql )->execute();
    
    		}else{
    			$alerdy_listen_detail_md5= $rs['listen_detail_md5'];
    			if( $alerdy_listen_detail_md5!=$listen_detail_md5 ){
    				//update
    				$sql= "UPDATE queue_product_info_md5 SET listen_detail_md5='{$listen_detail_md5}' WHERE id= ".$rs['id'];
    				//echo $sql,PHP_EOL;
    				$connection->createCommand( $sql )->execute();
    				//delete
    				AliexpressListingDetail::deleteAll(['productid' => $productid]);
    				//echo 'detail的md5不存在删除:',$productid,PHP_EOL;
    			}else{
    				//echo 'ccccc2-',date("H:i:s"),PHP_EOL;
    				continue;
    			}
    		}
    
    
    		$aliexpressListDetail = new AliexpressListingDetail;
    		$aliexpressListDetail->productid = $productid;
    
    		$aliexpressListDetail->listen_id= $row['id'];
    
    		if (!empty($productInfo["category_id"])) {
    			$aliexpressListDetail->categoryid = $productInfo["category_id"];
    		}
    
    		$aliexpressListDetail->selleruserid = $sellerloginid;
    		if (!empty($productInfo["product_price"])) {
    			$aliexpressListDetail->product_price = $productInfo["product_price"];
    		}
    
    		if (!empty($productInfo["gross_weight"])) {
    			$aliexpressListDetail->product_gross_weight = $productInfo["gross_weight"];
    		}
    
    		if (!empty($productInfo["package_length"])) {
    			$aliexpressListDetail->product_length = $productInfo["package_length"];
    		}
    
    		if (!empty($productInfo["package_width"])) {
    			$aliexpressListDetail->product_width = $productInfo["package_width"];
    		}
    
    		if (!empty($productInfo["package_height"])) {
    			$aliexpressListDetail->product_height = $productInfo["package_height"];
    		}
    
    		if (!empty($productInfo["currency_code"])) {
    			$aliexpressListDetail->currencyCode = $productInfo["currency_code"];
    		}
    
    		if (!empty($productInfo["aeop_ae_product_propertys"])) {
    			//兼容旧版本
    			foreach($productInfo["aeop_ae_product_propertys"] as &$item){
    				$item['attrNameId'] = empty($item['attr_name_id']) ? '' : $item['attr_name_id'];
    				$item['attrName'] = empty($item['attr_name']) ? '' : $item['attr_name'];
    				$item['attrValueId'] = empty($item['attr_value_id']) ? '' : $item['attr_value_id'];
    				$item['attrValue'] = empty($item['attr_value']) ? '' : $item['attr_value'];
    			}
    			
    			$aliexpressListDetail->aeopAeProductPropertys = json_encode($productInfo["aeop_ae_product_propertys"]);
    		} else {
    			$aliexpressListDetail->aeopAeProductPropertys = json_encode(array());
    		}
    
    		if (!empty($productInfo["aeop_ae_product_s_k_us"])) {
    			//兼容旧版本
    			foreach($productInfo["aeop_ae_product_s_k_us"] as &$item){
    				if(!empty($item['aeop_s_k_u_property_list'])){
    					foreach($item["aeop_s_k_u_property_list"] as $i){
    						$item["aeopSKUProperty"][] = [
    							'skuPropertyId' => empty($i['sku_property_id']) ? '' : $i['sku_property_id'],
    							'propertyValueId' => empty($i['property_value_id']) ? '' : $i['property_value_id'],
    							'propertyValueDefinitionName' => empty($i['property_value_definition_name']) ? '' : $i['property_value_definition_name'],
    							'skuImage' => empty($i['sku_image']) ? '' : $i['sku_image'],
    						];
    					}
    				}
    				else{
    					$item["aeopSKUProperty"] = [];
    				}
    				$item['skuPrice'] = empty($item['sku_price']) ? '' : $item['sku_price'];
    				$item['skuCode'] = empty($item['sku_code']) ? '' : $item['sku_code'];
    				$item['skuStock'] = empty($item['sku_stock']) ? '' : $item['sku_stock'];
    				$item['ipmSkuStock'] = empty($item['ipm_sku_stock']) ? '' : $item['ipm_sku_stock'];
    				$item['currencyCode'] = empty($item['currency_code']) ? '' : $item['currency_code'];
    			}
    			$aliexpressListDetail->aeopAeProductSKUs = json_encode($productInfo["aeop_ae_product_s_k_us"]);
    
    			$arr_sku= $productInfo['aeop_ae_product_s_k_us'];
    			$skucode_arr= array();
    			foreach( $arr_sku as $vss_sku ){
    				if( isset($vss_sku['sku_code']) && $vss_sku['sku_code']!='' ){
    					$skucode_arr[]= $vss_sku['sku_code'];
    				}
    			}
    			if( !empty( $skucode_arr ) ){
    				$skucode_str= implode(';',$skucode_arr);
    			}else{
    				$skucode_str= '';
    			}
    			$aliexpressListDetail->sku_code= $skucode_str;
    
    		} else {
    			$aliexpressListDetail->aeopAeProductSKUs = json_encode(array());
    			$aliexpressListDetail->sku_code= '';
    		}
    
    		if (!empty($productInfo["detail"])) {
    			$aliexpressListDetail->detail = $productInfo["detail"];
    		}
    
    		if( !empty( $productInfo['delivery_time'] ) ){
    			$aliexpressListDetail->delivery_time = $productInfo['delivery_time'];
    		}
    
    		if( !empty( $productInfo['package_type'] ) ){
    			$aliexpressListDetail->package_type = $productInfo['package_type'];
    		}
    
    		if( !empty( $productInfo['lot_num'] ) ){
    			$aliexpressListDetail->lot_num = $productInfo['lot_num'];
    		}
    
    		if( !empty( $productInfo['is_pack_sell'] ) ){
    			$aliexpressListDetail->isPackSell = $productInfo['is_pack_sell'];
    		}
    
    		if( !empty( $productInfo['reduce_strategy'] ) ){
    			$aliexpressListDetail->reduce_strategy = $productInfo['reduce_strategy'];
    		}
    		if( !empty( $productInfo['product_unit'] ) ){
    			$aliexpressListDetail->product_unit = $productInfo['product_unit'];
    		}
    
    		if( !empty( $productInfo['ws_valid_num'] ) ){
    			$aliexpressListDetail->wsValidNum = $productInfo['ws_valid_num'];
    		}
    
    		if( !empty( $productInfo['currency_code'] ) ){
    			$aliexpressListDetail->currencyCode = $productInfo['currency_code'];
    		}
    
    		//if( !empty( $productInfo['promiseTemplateId'] ) ){
    		$aliexpressListDetail->promise_templateid = $productInfo['promise_template_id'];
    		//}
    
    		if( !empty( $productInfo['group_Ids'] ) ){
    			$aliexpressListDetail->product_groups = implode(',',$productInfo['group_Ids']);
    		}
    
    		$aliexpressListDetail->save(false);
    
    		if( !empty( $productInfo['freight_template_id'] ) ){
    			AliexpressListing::updateAll( ['freight_template_id'=>$productInfo['freight_template_id']],'id='.$row['id'] );
    		}
    
    		if( $queue!='' ){
    			$queue->addProgress();
    		}
    		echo $productid.PHP_EOL;
    	}
    
    	//end foreach
    	return true;
    
    }
    //end function
    



    /**
     *速卖通在线商品列表,针对用户
     * @author akirametero
     *
     * $puid 小老板用户ID
     * $each - 每页条数
     * $search=array('selleruserid'=>店铺ID,'sku'=>sku,'subject'=>标题,'template'=>运费模板id,'group'=>'用户自定义分组id')
     *$type =0-待发布,1-上架销售中,2-下架 3-审核中 4-审核不通过,5-修改中,6-发布中,7-发布失败,99-all
     */
    public static function getAlipressProduct( $puid,$search=array(),$type='1',$each=20 ){
        if( ceil($puid)==0 ){
            return false;
        }


        $query= AliexpressListing::find();

        $query->leftJoin('aliexpress_listing_detail','aliexpress_listing.productid=aliexpress_listing_detail.productid');
        if( isset( $search['sku'] ) ) {
            $query->where(['like', 'aliexpress_listing_detail.sku_code', $search['sku']]);
        }
        if( isset( $search['selleruserid'] ) ){
            $query->where( ['aliexpress_listing.selleruserid'=>$search['selleruserid']] );
        }
        if( $type!='99' ){
            $query->where( [ 'aliexpress_listing.product_status'=>$type ] );
        }

        $query->orderBy('aliexpress_listing.id DESC');
        $pagination = new Pagination([
            'defaultPageSize' => $each,
            'totalCount' => $query->count()
        ]);


        $query->offset($pagination->offset);
        $query->limit($pagination->limit);

        //$query->asArray();
        $list= $query->all();

        $return= array();
        $return['list']= $list;
        $return['page']= $pagination;

        return $return;
    }
    //end function

    /**
     * 通过类目ID 获取类目下的属性信息
     * cateid 类目id
     * sellerloginid 调用api需要用到的店铺ID,不用改
     * line 是否调用线上接口获取 true/false  ,当时false的时候,走数据库获取
     * @author akirametero
     */
    public static function getCartInfo( $cateid,$line=false,$sellerloginid='cn1001790257' ){

        if( $line===false ){
            $query= AliexpressCategory::find();
            $query->where(['cateid'=>$cateid]);
            $query->asArray();
            $res= $query->one();
            $return= array();
            if( !empty( $res ) && !empty( $res['attribute'] ) ){
                $attribute= json_decode($res['attribute']);
                foreach( $attribute as $key=>$vs ){

                    $return[$key]['id']= $vs->id;

                    if( isset( $vs->sku ) && $vs->sku==true ){
                        $return[$key]['sku']= 1;
                    }else{
                        $return[$key]['sku']= 0;
                    }
                    if( isset( $vs->spec ) ){
                        $return[$key]['spec']= $vs->spec;
                    }else{
                        $return[$key]['spec']= '';
                    }
                    if( isset( $vs->visible ) ){
                        $return[$key]['visible']= $vs->visible;
                    }else{
                        $return[$key]['visible']= '';
                    }
                    if( isset( $vs->inputType ) ){
                        $return[$key]['inputType']= $vs->inputType;
                    }else{
                        $return[$key]['inputType']= '';
                    }
                    if( isset( $vs->attributeShowTypeValue ) ){
                        $return[$key]['attributeShowTypeValue']= $vs->attributeShowTypeValue;
                    }else{
                        $return[$key]['attributeShowTypeValue']= '';
                    }


                    if( isset($vs->required) && $vs->required==true ){
                        $return[$key]['required']= 1;
                    }else{
                        $return[$key]['required']= 0;
                    }
                    if( isset($vs->keyAttribute) && $vs->keyAttribute==true ){
                        $return[$key]['keyAttribute']= 1;
                    }else{
                        $return[$key]['keyAttribute']= 0;
                    }
                    if( isset($vs->customizedName) && $vs->customizedName==true ){
                        $return[$key]['customizedName']= 1;
                    }else{
                        $return[$key]['customizedName']= 0;
                    }
                    if( isset($vs->customizedPic) &&  $vs->customizedPic==true ){
                        $return[$key]['customizedPic']= 1;
                    }else{
                        $return[$key]['customizedPic']= 0;
                    }

                    if( isset( $vs->name_zh ) ){
                        $return[$key]['name_zh']= $vs->name_zh;
                    }else{
                        $return[$key]['name_zh']= '';
                    }



                    if( isset( $vs->name_en ) ){
                        $return[$key]['name_en']= $vs->name_en;
                    }else{
                        $return[$key]['name_en']= '';
                    }

                    if( isset($vs->values) ){
                        $values= $vs->values;

                        foreach( $values as $k=>$v ){
                            $return[$key]['values'][$k]['id']= $v->id;

                            if( isset( $v->name_zh ) ){
                                $return[$key]['values'][$k]['name_zh']= $v->name_zh;
                            }

                            if( isset( $v->name_en ) ){
                                $return[$key]['values'][$k]['name_en']= $v->name_en;
                            }

                        }
                    }

                }
                return $return;
            }else{
                return $return;
            }
        }else if( $line===true ){
            //设置token
            $api = new AliexpressInterface_Api ();
            $access_token= self::getToken( $sellerloginid );

            $api->access_token = $access_token;
            $param= ['cateId'=>$cateid];

            $result= $api->getChildAttributesResultByPostCateIdAndPath( $param );
            if( isset( $result['success'] ) ){
                if( $result['success']==1 ){
                    if( isset( $result['attributes'] ) ){
                        $return= array();
                        if( !empty( $result['attributes'] ) ){
                            foreach( $result['attributes'] as $key=>$vs ){
                                $return[$key]['id']= $vs['id'];
                                if( $vs['sku']===true ){
                                    $return[$key]['sku']= 1;
                                }else{
                                    $return[$key]['sku']= 0;
                                }
                                $return[$key]['spec']= $vs['spec'];
                                $return[$key]['visible']= $vs['visible'];
                                $return[$key]['inputType']= $vs['inputType'];
                                $return[$key]['attributeShowTypeValue']= $vs['attributeShowTypeValue'];
                                if( $vs['required']===true ){
                                    $return[$key]['required']= 1;
                                }else{
                                    $return[$key]['required']= 0;
                                }
                                if( $vs['keyAttribute']===true ){
                                    $return[$key]['keyAttribute']= 1;
                                }else{
                                    $return[$key]['keyAttribute']= 0;
                                }
                                if( $vs['customizedName']===true ){
                                    $return[$key]['customizedName']= 1;
                                }else{
                                    $return[$key]['customizedName']= 0;
                                }
                                if( $vs['customizedPic']===true ){
                                    $return[$key]['customizedPic']= 1;
                                }else{
                                    $return[$key]['customizedPic']= 0;
                                }

                                $return[$key]['name_zh']= $vs['names']['zh'];
                                $return[$key]['name_en']= $vs['names']['en'];
                                //$return[$key]['attributeShowTypeValue']= $vs['attributeShowTypeValue'];
                                if( isset( $vs['values'] ) ){
                                    $value= $vs['values'];
                                    foreach( $value as $k=>$vas ){
                                        $return[$key]['values'][$k]['id']= $vas['id'];
                                        $return[$key]['values'][$k]['name_zh']= $vas['names']['zh'];
                                        $return[$key]['values'][$k]['name_en']= $vas['names']['en'];

                                        //是否存在第三级的属性
                                        if( !empty($vas['attributes']) ){
                                            $res_attributes= $vas['attributes'];
                                            foreach( $res_attributes as $kk=>$vs_attributes ){
                                                $return[$key]['values'][$k]['art'][$kk]['id']= $vs_attributes['id'];
                                                $return[$key]['values'][$k]['art'][$kk]['name_zh']= $vs_attributes['names']['zh'];
                                                $return[$key]['values'][$k]['art'][$kk]['name_en']= $vs_attributes['names']['en'];

                                                if( $vs_attributes['sku']===true ){
                                                    $return[$key]['values'][$k]['art'][$kk]['sku']= 1;
                                                }else{
                                                    $return[$key]['values'][$k]['art'][$kk]['sku']= 0;
                                                }
                                                $return[$key]['values'][$k]['art'][$kk]['spec']= $vs_attributes['spec'];
                                                $return[$key]['values'][$k]['art'][$kk]['visible']= $vs_attributes['visible'];
                                                $return[$key]['values'][$k]['art'][$kk]['inputType']= $vs_attributes['inputType'];
                                                $return[$key]['values'][$k]['art'][$kk]['attributeShowTypeValue']= $vs_attributes['attributeShowTypeValue'];
                                                if( $vs_attributes['required']===true ){
                                                    $return[$key]['values'][$k]['art'][$kk]['required']= 1;
                                                }else{
                                                    $return[$key]['values'][$k]['art'][$kk]['required']= 0;
                                                }
                                                if( $vs_attributes['keyAttribute']===true ){
                                                    $return[$key]['values'][$k]['art'][$kk]['keyAttribute']= 1;
                                                }else{
                                                    $return[$key]['values'][$k]['art'][$kk]['keyAttribute']= 0;
                                                }

                                                if( $vs_attributes['customizedName']===true ){
                                                    $return[$key]['values'][$k]['art'][$kk]['customizedName']= 1;
                                                }else{
                                                    $return[$key]['values'][$k]['art'][$kk]['customizedName']= 0;
                                                }

                                                if( $vs_attributes['customizedPic']===true ){
                                                    $return[$key]['values'][$k]['art'][$kk]['customizedPic']= 1;
                                                }else{
                                                    $return[$key]['values'][$k]['art'][$kk]['customizedPic']= 0;
                                                }

                                                if( isset( $vs_attributes['values'] ) ){
                                                    foreach($vs_attributes['values'] as $kv=>$vssa){
                                                        $return[$key]['values'][$k]['art'][$kk]['vv'][$kv]['id']= $vssa['id'];
                                                        $return[$key]['values'][$k]['art'][$kk]['vv'][$kv]['name_zh']=$vas['names']['zh'];
                                                        $return[$key]['values'][$k]['art'][$kk]['vv'][$kv]['name_en']=$vas['names']['en'];
                                                    }
                                                }
                                            }
                                        }else{

                                        }
                                    }
                                }
                            }
                        }
                        //print_r ($return);exit;
                        return $return;
                    }else{
                        return false;
                    }
                }
            }else{
                return false;
            }
        }else{
            return false;
        }

    }
    //end function




    /**
     * 上传图片到图片银行
     * @author akirametero
     */
    public static function uploadImage( $sellerloginid,$fileName,$imgurl,$groupId='' ){
        //token
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        $param=array();
        $param['fileName']= $fileName;

        $param['groupId']= $groupId;
        $result= $api->uploadImage($param,$imgurl);

        return $result;
    }
    //end function

    /**
     * 商品上架/下架
     * productIds 产品id。多个产品ID用英文分号隔开。
     * type on/off  上架 /下架
     * 完成后,需要调用getProductInfo,获取状态,然后调用editProductStatus修改状态
     * @author akirametero
     */
    public static function onoffProduct( $sellerloginid,$productIds,$type='on' ){
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        if( $type=='on' ){
            $result= $api->onlineAeProduct( ['productIds'=>$productIds] );
        }elseif( $type=='off' ){
            $result= $api->offlineAeProduct( ['productIds'=>$productIds] );
        }else{
            return false;
        }
        return $result;
    }
    //end function

    /**
     * 服务模板查询
     * templateId -1 获取所有服务列表
     * @author akirametero
     */
    public static function queryPromiseTemplateById ($sellerloginid,$templateId){
        $puid= self::getPuid( $sellerloginid );
        $query= AliexpressPromiseTemplate::find()->where(['selleruserid'=>$sellerloginid]);
        if( $templateId!='-1' ){
            $query->where(['templateid'=>$templateId]);
        }
        $query->asArray();
        $result= $query->all();
        return $result;

        /**
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        $result= $api->queryPromiseTemplateById(['templateId'=>$templateId]);
        if( isset( $result['templateList'] ) ){
            return $result['templateList'];
        }else{
            return false;
        }
         * */

    }
    //end function

    /**
     * 获取运费模板
     * @author akirametero
     */
    public static function listFreightTemplate( $sellerloginid ){
        $puid= self::getPuid( $sellerloginid );
        $result= AliexpressFreightTemplate::find()->where(['selleruserid'=>$sellerloginid])->asArray()->all();
        return $result;
        /**
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        $list= $api->listFreightTemplate();
        if( isset( $list ) && $list['success']===true ){
            return $list['aeopFreightTemplateDTOList'];
        }else{
            return false;
        }
         * */

    }
    //end function



    /**
     * 获取所有的商品单位
     * @author akirametero
     */
    public static function getProductUnit(){
        $str= '100000000:袋:(bag/bags);100000001:桶:(barrel/barrels);100000002:蒲式耳:(bushel/bushels);100078580:箱:(carton);100078581:厘米:(centimeter);100000003:立方米:(cubic meter);100000004:打:(dozen);100078584:英尺:(feet);100000005:加仑:(gallon);100000006:克:(gram);100078587:英寸:(inch);100000007:千克:(kilogram);100078589:千升:(kiloliter);100000008:千米:(kilometer);100078559:升:(liter/liters);100000009:英吨:(long ton);100000010:米:(meter);100000011:公吨:(metric ton);100078560:毫克:(milligram);100078596:毫升:(milliliter);100078597:毫米:(millimeter);100000012:盎司:(ounce);100000014:包:(pack/packs);100000013:双:(pair);100000015:件/个:(piece/pieces);100000016:磅:(pound);100078603:夸脱:(quart);100000017:套:(set/sets);100000018:美吨:(short ton);100078606:平方英尺:(square feet);100078607:平方英寸:(square inch);100000019:平方米:(square meter);100078609:平方码:(square yard);100000020:吨:(ton);100078558:码:(yard/yards)';
        $arr= explode(';',$str);
        $return= array();
        foreach( $arr as $k=>$vs ){
            $vsa= explode(':',$vs);
            $return[$k]['id']= $vsa[0];
            $return[$k]['zh']= $vsa[1];
            $return[$k]['en']= str_replace(array('(',')'),'',$vsa[2]);
        }

        return $return;
    }
    //end function


    /**
     * 修改一个商品的类目属性
     * @author akirametero
     */
    public static function editProductCategoryAttributes( $sellerloginid,$produceId,$param=array() ){
        //先获取一个产品的类目属性,从本地
        /**
        $query= AliexpressListingDetail::find();
        $query->where(['productid'=>$produceId]);
        $query->asArray();
        $result= $query->one();
        $aeopAeProductPropertys= json_decode($result['aeopAeProductPropertys']);
        //$categoryid= $result['categoryid'];
        **/
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token= $access_token;
        $pa= ['productId'=>$produceId,'productCategoryAttributes'=>$param];
        $result= $api->editProductCategoryAttributes($pa);
        return $result;
    }
    //end function


    /**
     * 更新类目信息
     * @author akirametero
     */
    public static function updateCateInfo(){
        $sellerloginid= 'cn1001790257';
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token= $access_token;
        //
        //先用现成的给同步下类目
        AlipressHelper::autoSyncAliexpressCategory( $sellerloginid,array(0),$access_token );
        //获取所有叶子节点的数据
        $query= AliexpressCategory::find();
        $query->where(['isleaf'=>'true']);
        $query->asArray();
        $res= $query->all();
        if( !empty( $res ) ){
            foreach( $res as $vss ){
                $v= self::getCartInfo( $vss['cateid'],true );
                //反写回类目表
                $json_v= json_encode($v);
                //echo $json_v;exit;
                AliexpressCategory::updateAll( ['attribute'=>$json_v],'cateid='.$vss['cateid'] );
                echo $vss['cateid'],PHP_EOL;
            }
        }else{
            return false;

        }
        return true;

    }
    //end function

    /**
     *
     * 统计审核中/审核不通过/已下架/正在销售 的商品总数
     * 已同步后的数据为准
     * status -1-onSelling-上架销售中 /2-offline-下架/ 3-auditing-审核中 /4-editingRequired-审核不通过/
     * @author akirametero
     */
    public static function getProContyByStatus($status,$sellerloginid=''){
        //获取puid
        $puid= self::getPuid( $sellerloginid );

        $query= AliexpressListing::find();
        $query->where(['product_status'=>$status]);
        if( $sellerloginid!='' ){
            $query->where(['selleruserid'=>$sellerloginid]);
        }
        $count= $query->count();
        return $count;
    }
    //end function


    /**
     * 获取商品的信息信息,编辑用,直接读取在线的数据,不用本地的
     *
     * @author akirametero
     */
    public static function getProductInfo($product_id,$sellerloginid){
        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token= $access_token;
        $res= $api->findAeProductById( ['productId'=>$product_id] );
        return $res;
    }
    //end function

    /**
     * 获取用户的产品分组信息list
     * @author akirametero
     */
    public static function getProductGroupList( $sellerloginid ){
        $puid= self::getPuid( $sellerloginid );
        //管他有数据没数据,先给丫的同步算了
        self::tongbuProductGroup( $sellerloginid );
        $api = new AliexpressInterface_Api ();
        $access_token = self::getToken($sellerloginid);
        $api->access_token= $access_token;
        $return= $api->getProductGroupList();
        if( isset( $return['success'] ) && $return['success']===true ) {
            if (isset($return['target']) && !empty($return['target'])) {
                return $return['target'];
            }
        }
        return array();

    }
    //end function

    /**
     * 通过group id获取单条数据
     * @author akirametero
     */
    public static function getGroupInfoRow( $sellerloginid,$groupid ){
        $puid= self::getPuid( $sellerloginid );

        $result= AliexpressGroupInfo::find()->where(['group_id'=>$groupid])->asArray()->one();
        return $result;
    }
    //end function

    /**
     * 获取某个groupid下的所有子分组
     * @author akirametero
     */
    public static function getChildGroupInfoList( $sellerloginid,$groupid ){
        $puid= self::getPuid( $sellerloginid );

        $result= AliexpressGroupInfo::find()->where(['parent_group_id'=>$groupid])->asArray()->all();
        return $result;
    }
    //end function

    /**
     * 同步用户的产品分组列表
     * author akirametero
     */
    public static function tongbuProductGroup( $sellerloginid ){
        $puid= self::getPuid( $sellerloginid );

        $api = new AliexpressInterface_Api ();
        $access_token = self::getToken($sellerloginid);
        $api->access_token= $access_token;
        $return= $api->getProductGroupList();
        if( isset( $return['success'] ) && $return['success']===true ){
            if( isset( $return['target'] ) && !empty( $return['target'] )  ){
                //del all
                AliexpressGroupInfo::deleteAll(['selleruserid'=>$sellerloginid]);
                foreach( $return['target'] as $v ){
                    //insert
                    $model= new AliexpressGroupInfo();
                    $model->group_id= $v['groupId'];
                    $model->group_name= $v['groupName'];
                    $model->selleruserid= $sellerloginid;
                    $model->save();
                    if( isset( $v['childGroup'] ) && !empty( $v['childGroup'] ) ){
                        foreach( $v['childGroup'] as $vs ){
                            $models= new AliexpressGroupInfo();
                            $models->group_id= $vs['groupId'];
                            $models->group_name= $vs['groupName'];
                            $models->parent_group_id= $v['groupId'];
                            $models->selleruserid= $sellerloginid;
                            $models->save();
                        }
                    }
                }
            }
            return true;
        }else{
            return false;
        }

    }
    //end function

    /**
     * 保存商品信息到本地库,不含发布动作
     * @param $info,发布商品的各种属性
     * $info['selleruserid'] = 刊登店铺 =not null
     * $info['subject'] = 商品标题 =not null
     * $info['product_status'] = 商品状态设置,默认为保存未发布状态 =not null
     * $info['img_url'] = 产品图片,多个图片用;分开,第一个图片地址是主图,请使用图片银行接口上传图片,成功后会返回url地址,此处需要是url地址传入 =not null
     * $info['freight_templateid'] = 产品运费模板 =not null
     * $info['categoryid'] = 产品类目id =not null
     * $info['aeopAeProductPropertys'] = 产品类目属性aeopAeProductPropertys ,json格式 =not null
     * $info['groups'] = 产品分组 =null
     * $info['product_unit'] = 最小计量单位 = not null
     * $info['package_type'] = 销售方式  打包销售: true 非打包销售:false = not null
     * $info['lot_num'] = 每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1 = null
     * $info['reduce_strategy'] = 库存扣减方式 ,null ,1-下单减库存 2-支付减库存 =  null
     * $info['delivery_time'] = 发货期 = not null
     * $info['bulk_order'] = 批发最小数量 。取值范围2-100000。批发最小数量和批发折扣需同时有值或无值 =  null
     * $info['bulk_discount'] = 批发折扣。扩大100倍，存整数。取值范围:1-99。注意：这是折扣，不是打折率。 如,打68折,则存32 = null
     * $info['detail'] = 产品详细描述 = not null
     * $info['product_price'] = 零售价 = null
     * $info['aeopAeProductSKUs'] = 商品编码等sku属性,json格式 = not null
     * $info['product_gross_weight'] = 产品包装后的重量,毛重 = not null
     * $info['isPackSell'] = 自定义称重,0-false 1-true = null
     * $info['baseUnit'] = isPackSell为true时,此项必填。购买几件以内不增加运费。取值范围1-1000 = null
     * $info['addUnit'] = isPackSell为true时,此项必填。 每增加件数.取值范围1-1000 = null
     * $info['addWeight'] = isPackSell为true时,此项必填。 对应增加的重量.取值范围:0.001-500.000,保留三位小数,采用进位制,单位:公斤 = null
     * $info['product_length'] = 商品包装长度 = not null
     * $info['product_width'] = 商品包装宽度 = not null
     * $info['product_height'] = 商品包装高度 = not null
     * $info['promise_templateid'] = 服务模板ID = not null
     * $info['wsValidNum'] = 产品有效期 = not null
     *
     * @param $action ,add/edit
     * @param $id ,需要修改的主表aliexpress_listing的id
     *
     * @author akirametero
     * 参数格式参考:
     * http://gw.api.alibaba.com/dev/doc/intl/api.htm?ns=aliexpress.open&n=api.postAeProduct&v=1
     */
    public static function saveProductInfo( $info=array(),$action='add',$id='' ){
        $error= array();

        //获取puid
        $puid= self::getPuid( $info['selleruserid'] );


        if( $action=='add' ){
            $listing= new AliexpressListing();
            $listing_detail= new AliexpressListingDetail();
        }elseif( $action=='edit' ){
            if( intval( $id )=='' ){
                $error['msg']= '参数错误';
                $error['return']= false;
                return $error;
            }
            $listing = AliexpressListing::findOne( ['id' => $id] );
            $listing_detail= $listing->detail;
        }else{
            $error['msg']= '参数错误';
            $error['return']= false;
            return $error;
        }
        if( $action=="add" ){
            $listing->productid= '';
        }
        //刊登店铺
        if( !isset($info['selleruserid']) || $info['selleruserid']=='' ) {
            $error['msg']= '未选择需要刊登的店铺';
            $error['return']= false;
            return $error;
        }else{
            $listing->selleruserid= $info['selleruserid'];
        }

        //产品标题
        if( !isset( $info['subject'] ) || trim( $info['subject'] )=='' ){
            $error['msg']= '未填写商品标题';
            $error['return']= false;
            return $error;
        }else{
            $listing->subject= trim( $info['subject'] );
        }

        //商品状态设置,默认为保存未发布状态
        if( !isset( $info['product_status'] ) || $info['product_status']=='' ){
            $product_status= 0;
        }else{
            $listing->product_status= $info['product_status'];
        }

        //产品图片,请使用图片银行接口上传图片,成功后会返回url地址,此处需要是url地址传入
        //这里图片是产品橱窗图片地址
        if( !isset( $info['img_url'] ) || $info['img_url']=='' ){
            $error['msg']= '未选择商品图片';
            $error['return']= false;
            return $error;
        }else{
            //这里图片是产品主图,取产品图片的第一个图就行了
            $photo_arr= explode( ';',$info['img_url'] );
            $listing->photo_primary= $photo_arr[0];
            if( count( $photo_arr )==1 ){
                $listing->imageurls= '';
            }else{
                unset($photo_arr[0]);
                $listing->imageurls= implode(";",$photo_arr);
            }
        }

        //产品运费模板,int
        if( !isset( $info['freight_templateid'] ) || $info['freight_templateid']=='' ){
            $error['msg']= '未选择运费模板';
            $error['return']= false;
            return $error;
        }else{
            $listing->freight_template_id= $info['freight_templateid'];
        }
        //$listing->product_status= 0;
        $listing->created= time();
        $res_listing= $listing->save();
        ///////////////////////////////////////////////////////////////////////////////////////////////

        if( $action=="add" ) {
            $listing_detail->productid = '';
        }
        $listing_detail->selleruserid= $info['selleruserid'];
        if( $res_listing!==true ){
            $error['msg']= $listing->errors;
            $error['return']= false;
            return $error;
        }else{
            $listen_id= $listing->id;
            $listing_detail->listen_id= $listen_id;
        }

        //产品类目
        if( !isset( $info['categoryid'] ) || $info['categoryid']=='' ){
            $listing->delete();
            $error['msg']= '未选择类目';
            $error['return']= false;
            return $error;
        }else{
            $categoryid= intval( $info['categoryid'] );//aliexpress_listing_detail
            $listing_detail->categoryid= $info['categoryid'];
        }

        //产品类目属性aeopAeProductPropertys ,json格式
        if( !isset( $info['aeopAeProductPropertys'] ) ){
            $listing->delete();
            $error['msg']= '未设置商品属性';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->aeopAeProductPropertys= $info['aeopAeProductPropertys'];
        }

        //产品分组,null
        if( isset( $info['groups'] ) && $info['groups']!='' ){
            $listing_detail->product_groups= $info['groups'];
        }else{
            //no error
        }

        //最小计量单位
        if( !isset( $info['product_unit'] ) || $info['product_unit']=='' ){
            $listing->delete();
            $error['msg']= '未选择最小计量单位';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->product_unit= $info['product_unit'];
        }

        //销售方式  打包销售: true 非打包销售:false
        if( !isset( $info['package_type'] ) || $info['package_type']=='' ){
            $listing->delete();
            $error['msg']= '未选择销售方式';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->package_type= $info['package_type'];
        }
        //每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1
        if( isset( $info['lot_num'] ) ){
            $listing_detail->lot_num= $info['lot_num'];
        }

        //库存扣减方式 ,null ,1-下单减库存 2-支付减库存
        if( isset( $info['reduce_strategy'] ) && $info['reduce_strategy']!='' ){
            $listing_detail->reduce_strategy= $info['reduce_strategy'];
        }else{
            //no error
        }

        //发货期
        if( !isset( $info['delivery_time'] ) || $info['delivery_time']=='' ){
            $listing->delete();
            $error['msg']= '未填写发货期';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->delivery_time= $info['delivery_time'];
        }

        //bulk_order 批发最小数量 。取值范围2-100000。批发最小数量和批发折扣需同时有值或无值
        //bulk_discount 批发折扣。扩大100倍，存整数。取值范围:1-99。注意：这是折扣，不是打折率。 如,打68折,则存32
        $listing_detail->is_bulk= 0;
        if( isset( $info['bulk_order'] ) && isset( $info['bulk_discount'] ) ){
            $listing_detail->bulk_order= $info['bulk_order'];
            $listing_detail->bulk_discount= $info['bulk_discount'];
            if( ceil( $info['bulk_order'] )>1 ){
                $listing_detail->is_bulk= 1;
            }
        }else{
            //no error
        }

        //产品详细描述
        if(  !isset( $info['detail'] ) || $info['detail']=='' ){
            $listing->delete();
            $error['msg']= '未填写商品详细描述';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->detail= $info['detail'];
        }

        //零售价
        if( isset( $info['product_price'] ) && $info['product_price']!=''  ){
            $listing_detail->product_price= $info['product_price'];
        }else{
            //no error
        }

        //商品编码等sku属性
        if( !isset( $info['aeopAeProductSKUs'] ) ){
            $listing->delete();
            $error['msg']= '未设置sku属性';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->aeopAeProductSKUs= $info['aeopAeProductSKUs'];
            //skucode处理
            $arr_sku= json_decode($info['aeopAeProductSKUs'],true);
            if( !empty( $arr_sku ) ){
                $skucode_arr= array();
                foreach( $arr_sku as $vss_sku ){
                    if( isset($vss_sku['skuCode']) && $vss_sku['skuCode']!='' ){
                        $skucode_arr[]= $vss_sku['skuCode'];
                    }
                }
                if( !empty( $skucode_arr ) ){
                    $skucode_str= implode(';',$skucode_arr);
                }else{
                    $skucode_str= '';
                }
                $listing_detail->sku_code= $skucode_str;
            }
        }

        //产品包装后的重量,毛重
        if( !isset( $info['product_gross_weight'] ) || $info['product_gross_weight']=='' ){
            $listing->delete();
            $error['msg']= '未设置商品毛重';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->product_gross_weight= $info['product_gross_weight'];
        }

        //自定义称重,0-false 1-true
        if( isset( $info['isPackSell'] ) ){
            //勾选了自定义称重
            $isPackSell= $info['isPackSell'];
            $listing_detail->isPackSell= $isPackSell;
            if( $isPackSell==1 ){
                //true
                if( isset( $info['baseUnit'] ) && isset( $info['addUnit'] ) && isset( $info['addWeight'] ) ){
                    $listing_detail->baseUnit= $info['baseUnit'];
                    $listing_detail->addUnit= $info['addUnit'];
                    $listing_detail->addWeight= $info['addWeight'];
                }else{
                    $listing->delete();
                    $error['msg']= '自定义称重属性未填完整';
                    $error['return']= false;
                    return $error;
                }
            }else{
                //false
            }
        }else{
            //没有勾选自定义称重
        }

        //商品包装长度
        if( !isset( $info['product_length'] ) || $info['product_length']=='' ){
            $listing->delete();
            $error['msg']= '未设置包装长度';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->product_length= $info['product_length'];
        }

        //商品包装宽度
        if( !isset( $info['product_width'] ) || $info['product_width']=='' ){
            $listing->delete();
            $error['msg']= '未设置包装宽度';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->product_width= $info['product_width'];
        }

        //商品包装高度
        if( !isset( $info['product_height'] ) || $info['product_height']=='' ){
            $listing->delete();
            $error['msg']= '未设置包装高度';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->product_height= $info['product_height'];
        }

        //货币,美元,写死
        $listing_detail->currencyCode= 'USD';

        //服务设置
        if( !isset( $info['promise_templateid'] ) || $info['promise_templateid']=='' ){
            $listing->delete();
            $error['msg']= '未选择服务设置';
            $error['return']= false;
            return $error;
        }else{
            $listing_detail->promise_templateid= $info['promise_templateid'];
        }

        //产品有效期
        $listing_detail->wsValidNum= $info['wsValidNum'];

        $detail_res= $listing_detail->save();
        if( $detail_res!==true ){
            $listing->delete();
            $error['msg']= $listing_detail->errors;
            $error['return']= false;
            return $error;
        }else{
            //$error['msg']= $listing->id;
            $error['msg']= ['listing_id'=>$listing->id,'product_id'=>$listing->productid];
            $error['return']= true;
            return $error;
        }
    }

    /*
     * 管理商品分组,最多3个分组关联在一起,当发布的产品,包含多个分组的时候,需要用到这个接口
     * productid 产品ID
     * groupIds = 123,456,789
     * @author akirametero
     */
    public static function setGroups( $sellerloginid,$productid,$groupIds ){
        $api = new AliexpressInterface_Api ();
        $access_token = self::getToken($sellerloginid);
        $api->access_token= $access_token;
        $param=['productId'=>$productid,'groupIds'=>$groupIds];
        $return= $api->setGroups( $param );
        return $return;
    }
    //end function


    /**
     * 发布/修改商品信息,调用这个接口前,统一调用下保存的方法吧
     *
     * 完成后,需要调用getProductInfo,获取状态,然后调用editProductStatus修改状态
     * $id 需要发布的listing表中的自增ID
     * $action add/edit
     * 参数参考:
     * http://gw.api.alibaba.com/dev/doc/intl/api.htm?ns=aliexpress.open&n=api.postAeProduct&v=1
     * http://gw.api.alibaba.com/dev/doc/intl/api.htm?ns=aliexpress.open&n=api.editAeProduct&v=1
     * @author akirametero
     */
    public static function postAeProduct( $sellerloginid,$id,$action='add' ){
        $puid= self::getPuid( $sellerloginid );
        $msg= array();


        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        $post= array();
        //获取保存的商品属性

        $rs_listing= AliexpressListing::findOne(['id'=>$id]);
        //$rs_listing_detail= ALDMod::findOne( ['listen_id'=>$id] );
        $rs_listing_detail= $rs_listing->detail;
        //print_r ($rs_listing_detail);exit;
        if( empty( $rs_listing ) || empty($rs_listing_detail) ){
            $msg['return']= false;
            $msg['msg']= 'no listing data';
        }
        $param= array();
        $param['detail']= $rs_listing_detail->detail;
        $param['aeopAeProductSKUs']= $rs_listing_detail->aeopAeProductSKUs;
        $param['deliveryTime']= $rs_listing_detail->delivery_time;
        $param['promiseTemplateId']= $rs_listing_detail->promise_templateid;
        $param['categoryId']= $rs_listing_detail->categoryid;
        $param['subject']= $rs_listing->subject;
        $param['productPrice']= $rs_listing_detail->product_price;
        $param['freightTemplateId']= $rs_listing->freight_template_id;
        //如果$rs_listing->imageurls 为空,就表示只有一个图,取主图字段的值,反之拼接成一个
        if( $rs_listing->imageurls=='' ){
            $param['imageURLs']= $rs_listing->photo_primary;
        }else{
            $param['imageURLs']= $rs_listing->photo_primary.';'.$rs_listing->imageurls;
        }
        $param['productUnit']= $rs_listing_detail->product_unit;
        $param['packageType']= $rs_listing_detail->package_type;
        $param['lotNum']= $rs_listing_detail->lot_num;
        $param['packageLength']= $rs_listing_detail->product_length;
        $param['packageWidth']= $rs_listing_detail->product_width;
        $param['packageHeight']= $rs_listing_detail->product_height;
        $param['grossWeight']= $rs_listing_detail->product_gross_weight;
        $param['isPackSell']= $rs_listing_detail->isPackSell;
        $param['baseUnit']= $rs_listing_detail->baseUnit;
        $param['addUnit']= $rs_listing_detail->addUnit;
        $param['addWeight']= $rs_listing_detail->addWeight;
        $param['wsValidNum']= $rs_listing_detail->wsValidNum;
        $param['aeopAeProductPropertys']= $rs_listing_detail->aeopAeProductPropertys;
        $param['bulkOrder']= $rs_listing_detail->bulk_order;
        $param['bulkDiscount']= $rs_listing_detail->bulk_discount;
        //$param['sizechartId']= '';
        $param['reduceStrategy']= $rs_listing_detail->reduce_strategy;
        //如果是单个分组ID,则传入,否则完成后调动setgroup接口
        if( $rs_listing_detail->product_groups!='' ){
            $groups_arr= explode(',',$rs_listing_detail->product_groups);
            $group_count= count( $groups_arr );
            if( $group_count==1 ) {
                $param['groupId'] = $rs_listing_detail->product_groups;
            }
        }else{
            $group_count= 0;
        }

        $param['currencyCode']= $rs_listing_detail->currencyCode;
        if( $action=='add' ){
            $result= $api->postAeProduct($param);
        }elseif( $action=='edit' ){
            if( ceil( $rs_listing_detail->productid )==0 ){
                $msg['return']= false;
                $msg['msg']= 'product id is null';
            }
            $param['productId']= $rs_listing_detail->productid;
            $result= $api->editAeProduct($param);
        }else{
            $msg['return']= false;
            $msg['msg']= 'action is error';
        }
        if( isset( $result['success'] ) && $result['success']===true ){
            //反写produce id
            $productid= $result['productId'];
            $rs_listing->productid= $productid;
            $rs_listing->save(false);

            $rs_listing_detail->productid= $productid;
            $rs_listing_detail->save(false);
            if( isset( $group_count ) && $group_count>1 ){
                //存在多个groups id ,调用setgroup接口
                self::setGroups( $sellerloginid,$productid,$rs_listing_detail->product_groups );
            }
            $msg['return']= true;
            $msg['msg']= $productid;

        }else{
            $msg['return']= false;
            $msg['msg']= $result;
        }
        return $msg;

    }
    //end function

    /**
     * 修改商品状态,直接传入状态字符串就可以了
     * $proid 商品ID
     * $status  onSelling/offline/auditing/editingRequired
     * @author akirametero
     */
    public static function editProductStatus( $sellerloginid,$proid,$status ){
        //切换数据库先
        $puid= self::getPuid( $sellerloginid );

        switch( $status ){
            case "onSelling":
                $t= 1;
                break;
            case "offline":
                $t= 2;
                break;
            case "auditing":
                $t= 3;
                break;
            case "editingRequired":
                $t= 4;
                break;
            default:
                $t= 1;
        }
        $rs_listing= AliexpressListing::findOne(['productid'=>$proid]);
        $rs_listing->product_status= $t;
        $rs_listing->save();
        return true;
    }
    //end function

    /**
     * 同步服务模板,保存到本地数据库
     * @author akirametero
     */
    public static function tongbuPromiseTemplate( $sellerloginid ){
        $puid= self::getPuid( $sellerloginid );

        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        $result= $api->queryPromiseTemplateById(['templateId'=>'-1']);
        if( isset( $result['templateList'] ) ){
            if( !empty( $result['templateList'] )  ){
                AliexpressPromiseTemplate::deleteAll(['selleruserid'=>$sellerloginid]);
                foreach( $result['templateList'] as $v ){
                    $model= new AliexpressPromiseTemplate();
                    $model->templateid= $v['id'];
                    $model->selleruserid= $sellerloginid;
                    $model->name= $v['name'];
                    $model->created= time();
                    $model->save();
                }
            }else{
            }
            $result= self::queryPromiseTemplateById($sellerloginid,'-1');
            return $result;
        }else{
            //同步失败
            return false;
        }
    }
    //end function


    /**
     * 同步运费模板,保存到本地数据库
     * @author akirametero
     */
    public static function tongbuFreightTemplate( $sellerloginid ){
        $puid= self::getPuid( $sellerloginid );

        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token = $access_token;
        $list= $api->listFreightTemplate();
        if( isset( $list ) && isset( $list['success'] ) ){
            if( $list['success']===true ){
                if( !empty( $list['aeopFreightTemplateDTOList'] ) ){
                    AliexpressFreightTemplate::deleteAll(['selleruserid'=>$sellerloginid]);
                    foreach( $list['aeopFreightTemplateDTOList'] as $v ){
                        $model= new AliexpressFreightTemplate();
                        $model->templateid= $v['templateId'];
                        if( $v['default']===false ){
                            $model->default= 'false';
                        }else{
                            $model->default= 'true';
                        }
                        $model->template_name= $v['templateName'];
                        $model->created= time();
                        $model->selleruserid= $sellerloginid;
                        $model->save();
                    }
                }
                $result= self::listFreightTemplate( $sellerloginid );
                return $result;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    //end function

    /**
     * 同步信息模板
     * @author akirametero
     */
    public static function tongbuProductDetailModule( $sellerloginid,$mname='',$moduleStatus='approved' ){
        $puid= self::getPuid( $sellerloginid );

        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );
        $api->access_token= $access_token;
        $page= 1;
        AliexpressDetailModule::deleteAll(['sellerloginid'=>$sellerloginid]);
        do {
            $param= array();
            $param['moduleStatus']= $moduleStatus;
            $param['pageIndex']= $page;
            $result= $api->findAeProductDetailModuleListByQurey( $param );
            if( isset( $result['success'] ) ){
                if( $result['success']===true ){
                    $aeopDetailModuleList= $result['aeopDetailModuleList'];
                    if( !empty( $aeopDetailModuleList ) ){
                        foreach( $aeopDetailModuleList as $v ){
                            $model= new AliexpressDetailModule();
                            $model->id= $v['id'];
                            $model->display_content= $v['displayContent'];
                            $model->module_contents= $v['moduleContents'];
                            $model->status= $v['status'];
                            $model->name= $v['name'];
                            $model->type= $v['type'];
                            $model->ali_member_id= $v['aliMemberId'];
                            $model->sellerloginid= $sellerloginid;
                            $model->save(false);
                        }
                    }
                    ++$page;
                    $totalPage= $result['totalPage'];
                }else{
                    return false;
                }
            }else{
                return false;
            }


        }while ($page <= $totalPage);
        $result= self::findAeProductDetailModuleListByQurey($sellerloginid,$mname);
        return $result;

    }
    //end function

    /**
     * 查询信息模板列表
     * @author akirametero
     */
    public static function findAeProductDetailModuleListByQurey( $sellerloginid,$name='' ){
        $puid= self::getPuid( $sellerloginid );
        $query= AliexpressDetailModule::find()->where(['sellerloginid'=>$sellerloginid]);
        if( $name!='' ){
            $query->where(['like', 'name', $name]);
        }
        $result= $query->asArray()->all();
        return $result;
    }
    //end function

    /**
     * 获取puid
     * @author akirametero
     */

    private static function getPuid($sellerloginid){
        $rs_user= SaasAliexpressUser::find()->where( ['sellerloginid'=>$sellerloginid] )->asArray()->one();
        $puid= $rs_user['uid'];
        return $puid;
    }

    public static function testapi( $sellerloginid  ){

        $api = new AliexpressInterface_Api ();
        $access_token= self::getToken( $sellerloginid );

        $api->access_token = $access_token;
        $productInfo = $api->findAeProductById(array('productId' => '32699611772'));
        print_r ($productInfo);exit;
    }



}
