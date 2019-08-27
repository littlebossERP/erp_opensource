<?php
namespace console\helpers;

use common\api\ebayinterface\shopping\getmultipleitem;
use common\helpers\Helper_Siteinfo;
use console\models\CustomerEbayItem;
use eagle\models\EbayCategory;
use eagle\modules\listing\models\EbayItem;
use yii\helpers\ArrayHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\models\AliexpressListingDetail;
use eagle\models\AliexpressCategory;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\models\OdOrder;
use eagle\models\SaasAliexpressUser;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\models\UserLastActivityTime;

/**
 * 
 */
class CustomerEmailHelper{
    public static $_last_db=0;
    public static $_fail_db=array();
    public static $aliexpress_category_arr = array();
    
    public static function getCustomerEbayItem(){
        do {
            //批量获取
            $eItemsArr=CustomerEbayItem::find()
                ->select(["id","puid","product_id"])
                ->where(["status"=>"P"])
                ->andwhere("`product_id` != ''")
                ->limit(20)
                ->orderBy('id')
                ->asArray()
                ->all();
            \yii::info($eItemsArr,"file");
            if (empty($eItemsArr)) {
                echo "CustomerEbayItem no match records\n";
                return false;
            }
            try{
                list($ret,$result)=self::_getEbayItemCategory($eItemsArr);
                if ($ret===false || empty($result)) {
                    echo "_getEbayItemCategory fail\n";
                    continue;
                }
            } catch (\Exception $e) {
                echo $e->getMessage()."\n";
                continue;
            }
            //逐个保存
            echo "===update category stepbystep \n";
            $ids=ArrayHelper::getColumn($eItemsArr, 'id');
            $eItems=CustomerEbayItem::find()
                ->where(["id"=>$ids])
                ->orderBy('id');
            foreach ($eItems->each() as $key => $item) {
                if ($result[$key-1]["id"]==$item->ID) {//保证一致
                    $item->category_name_one=$result[$key-1]["category_name_one"];
                    $item->status=$result[$key-1]["result"]?"C":"E";
                    $item->update_time=time();
                    $item->save(false);
                }else{
                    echo "no match itemid\n";
                }
            }
            echo "OK!\n";
        }while ( 1 );
        return true;
    }
    /**
     * [getEbayItemCategory 根据itemid获取目录(一级目录)]
     * @author willage 2017-10-24T15:42:30+0800
     * @update willage 2017-10-24T15:42:30+0800
     */
    public static function _getEbayItemCategory($array){
        echo "===batch get category \n";
        if (empty($array)||count($array)>300) {
            return [false,[]];
        }
        $itemIds=ArrayHelper::getColumn($array, 'product_id');
        if (empty($itemIds)) {
            return [false,[]];
        }
        $getAPI = new getmultipleitem();
        $getAPI->includeSelector='';
        $result=$getAPI->apiItem($itemIds);
        if ($getAPI->responseIsFail && isset($getAPI->error)) {
            \yii::info($getAPI->error,"file");
            echo $getAPI->error[0]["ShortMessage"]."\n";
            if ($getAPI->error[0]["ShortMessage"] != "Invalid item ID.") {
                return [false,[]];
            }
        }
            \yii::info($result,"file");
        foreach ($array as $key => $val) {
            $cateVal="";
            $ret=true;
            if (!$getAPI->responseIsFail) {
                foreach ($result as $reval) {
                    if ($val["product_id"]==$reval["ItemID"]) {
                        if (isset($reval["PrimaryCategoryName"])) {//没有该字段,就不处理
                            $cateArry=explode(':',$reval["PrimaryCategoryName"]);
                            $cateVal=$cateArry[0];
                        }
                        break;
                    }
                }
            }

            //接口获取不到,就从DB找
            if ($cateVal==="") {
                try{
                    if (!in_array($val["puid"], self::$_fail_db)) {
                        list($ret,$cateVal)=self::_getEbayItemCategoryFromDB($val["puid"],$val["product_id"]);
                    }else {
                        echo "in the fail db-".$val["puid"]."\n";
                    }
                }catch(\Exception $e){
                    echo $e->getMessage()."\n";
                    self::$_fail_db[] = $val["puid"];
                    $cateVal = "";
                    $ret = false;
                }
            }
            $array[$key]["category_name_one"]=$cateVal;
            $array[$key]["result"]=$ret;
        }
        return [true,$array];
    }
    /**
     * [_getEbayItemCategoryFromDB description]
     * @author willage 2017-10-24T16:20:06+0800
     * @update willage 2017-10-24T16:20:06+0800
     */
    public static function _getEbayItemCategoryFromDB($puid,$itemid){
        echo "get from DB ".$puid."-".$itemid."\n";
        $ret="";
        

        $ei = EbayItem::find()->where( ['itemid'=>$itemid] )->one();
        if (empty($ei)) {
            // \yii::info($ei,"file");
            echo "\n[".date('H:i:s').'-'.__LINE__.'- db no the item-'.$itemid."-puid".$puid."]\n";
            return [false,$ret];
        }
        $siteArry = Helper_Siteinfo::getEbaySiteIdList('en','no');
        if (!ArrayHelper::keyExists($ei->site, $siteArry, false)) {
            echo "no site exists:".$ei->site."\n";
            return [false,""];
        }

        $ec = EbayCategory::find()
            ->where( ['categoryid'=>$ei->primarycategory] )
            ->andwhere( ['siteid'=>$siteArry[$ei->site]] )
            ->one();
        if (empty($ec)) {
            return [false,""];
        }
        if ($ec->level==1) {
            return [true,$ec->name];
        }
        $cid=$ec->pid;
        for ($cnt=0; $cnt < ($ec->level-1) ; $cnt++) {
            $category=EbayCategory::find()
                ->where( ['categoryid'=>$cid] )
                ->andwhere( ['siteid'=>$siteArry[$ei->site]] )
                ->one();
            if (empty($category)) {
                return [false,$ret];
            }
            if ($category->level==1) {
                return [true,$category->name];
            }
            $cid=$category->pid;
        }
    }

    public static function insertCustomerEmail($table_type, $puid, $puid_activitys){
    	try{
    		$dbqueue2Conn = \yii::$app->db_queue2;
    		$subdbConn = \yii::$app->subdb;
    
    		//查询订单信息
    		$sql = "select distinct order_id, consignee_email, order_source, source_buyer_user_id, grand_total, consignee_country_code, consignee_city, consignee_province, create_time, consignee_phone, consignee_mobile, currency
    			from od_order".$table_type."_v2 where consignee_email!='' and consignee_email is not null and order_status>100 ";
    		$conditon = "";
    		//忽略某些平台
    		//$conditon .= " and order_source!='amazon' and order_source!='cdiscount'";
    		//先拉取ebay and aliexpress，国家为非RU
    		$conditon .= " and order_source in ('ebay', 'aliexpress') and consignee_country_code!='RU'";
    		//分页循环
    		$page = 1;
    		$page_size = 5000;
    		$count = 0;
    		$new_insert_count = 0;
    		$start_time = time();
    		while(1){
    			$email_str = '';
    			$cus_list = array();
    			$start_row = ($page - 1) * $page_size;
    			$orders = $subdbConn->createCommand($sql.$conditon.' group by consignee_email limit '.$start_row.','.$page_size)->queryAll();
    			if(!empty($orders)){
    				$insert_time = time();
    				$count += count($orders);
    				foreach($orders as $order){
    					$email = str_replace("'", "", $order['consignee_email']);
    					if(!array_key_exists($email, $cus_list)){
    						$cus_list[$email] = [
	    						'puid' => $puid,
	    						'email' => $email,
	    						'order_id' => ltrim($order['order_id'], "0"),
	    						'cus_name' => $order['source_buyer_user_id'],
	    						'platform_source' => $order['order_source'],
	    						'country_code' => $order['consignee_country_code'],
	    						'create_time' => time(),
	    						'email_create_time' => $order['create_time'],
	    						'last_active_time' => strtotime($puid_activitys[$puid]),
	    						'phone' => empty($order['consignee_phone']) ? $order['consignee_mobile'] : $order['consignee_phone'].' \ '.$order['consignee_mobile'],
	    						'city' => empty($order['consignee_province']) ? $order['consignee_province'] : $order['consignee_city'],
	    						'grand_total' => $order['grand_total'],
	    						'currency' => $order['currency'],
	    						'product_id' => '',
	    						'pro_title' => '',
    						];
    							
    						$email_str .= "'$email',";
    					}
    				}
    					
    				if($email_str != ''){
    					$email_str = rtrim($email_str, ",");
    					//查询已存在的记录
    					$rows = $dbqueue2Conn->createCommand("select email from customer_email where email in ($email_str)")->queryAll();
    					if(!empty($rows)){
    						foreach($rows as $row){
    							if(array_key_exists($row['email'], $cus_list)){
    								unset($cus_list[$row['email']]);
    							}
    						}
    					}
    					unset($rows);
    				}
    					
    				//查询对应的Item信息
    				$order_id_str = '';
    				$order_source_as_order_id = array();
    				foreach($cus_list as $val){
    					if(in_array($val['platform_source'], ['ebay', 'aliexpress'])){
    						$order_id = ltrim($val['order_id'],"0");
    						$order_id_str .= "'$order_id',";
    						$order_source_as_order_id[$order_id] = $val['platform_source'];
    					}
    				}
    					
    				$items = array();
    				$items_ebay = array();
    				$items_ebay_str = '';
    				$items_aliexpress = array();
    				$items_aliexpress_str = '';
    				if(!empty($order_id_str)){
    					$order_id_str = rtrim($order_id_str, ",");
    					$rows = $subdbConn->createCommand("select order_id, order_source_itemid, product_name from od_order_item".$table_type."_v2 where order_id in ($order_id_str) and order_source_itemid is not null and order_source_itemid!='' group by order_id, order_source_itemid")->queryAll();
    					foreach($rows as $val){
    						$order_id = ltrim($val['order_id'],"0");
    						$items[$order_id] = [
	    						'order_source_itemid' => $val['order_source_itemid'],
	    						'product_name' => $val['product_name'],
    						];
    							
    						if(!empty($order_source_as_order_id[$order_id])){
    							$product_id = $val['order_source_itemid'];
    							if($order_source_as_order_id[$order_id] == 'ebay'){
    								$items_ebay[$product_id] = [
	    								'puid' => $puid,
	    								'product_id' => $product_id,
	    								'create_time' => time(),
    								];
    									
    								$items_ebay_str .= "'$product_id',";
    							}
    							else if($order_source_as_order_id[$order_id] == 'aliexpress'){
    								$items_aliexpress[$product_id] = [
	    								'puid' => $puid,
	    								'product_id' => $product_id,
	    								'create_time' => time(),
    								];
    								$items_aliexpress_str .= "'$product_id',";
    							}
    						}
    					}
    				}
    					
    				if(!empty($items)){
    					foreach($cus_list as $key => $val){
    						if(array_key_exists($val['order_id'], $items)){
    							$cus_list[$key]['product_id'] = $items[$val['order_id']]['order_source_itemid'];
    							$cus_list[$key]['pro_title'] = $items[$val['order_id']]['product_name'];
    						}
    					}
    				}
    					
    				//插入到ebay item 类别名称队列
    				if(!empty($items_ebay)){
    					$items_ebay_str = rtrim($items_ebay_str, ",");
    					//查询已存在的记录
    					$rows = $dbqueue2Conn->createCommand("select product_id from customer_ebay_item where product_id in ($items_ebay_str)")->queryAll();
    					if(!empty($rows)){
    						foreach($rows as $row){
    							if(array_key_exists($row['product_id'], $items_ebay)){
    								unset($items_ebay[$row['product_id']]);
    							}
    						}
    					}
    
    					if(!empty($items_ebay)){
    						\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_ebay_item', $items_ebay, 'db_queue2');
    					}
    				}
    				//插入到aliexpress item 类别名称队列
    				if(!empty($items_aliexpress)){
    					$items_aliexpress_str = rtrim($items_aliexpress_str, ",");
    					//查询已存在的记录
    					$rows = $dbqueue2Conn->createCommand("select product_id from customer_aliexpress_item where product_id in ($items_aliexpress_str)")->queryAll();
    					if(!empty($rows)){
    						foreach($rows as $row){
    							if(array_key_exists($row['product_id'], $items_aliexpress)){
    								unset($items_aliexpress[$row['product_id']]);
    							}
    						}
    					}
    				
    					if(!empty($items_aliexpress)){
    						\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_aliexpress_item', $items_aliexpress, 'db_queue2');
    					}
    				}
    					
    				$new_insert_count += count($cus_list);
    				\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_email', $cus_list, 'db_queue2');
    				$page++;
    				echo "\n count: ".count($orders).", insert: ".count($cus_list).", consuming time: ".(time() - $insert_time);
    				unset($cus_list);
    				unset($orders);
    			}
    			else{
    				break;
    			}
    
    			//超过20分钟则退出
    			if(time() - $start_time > 1200){
    				echo "\n search od_order".$table_type."_v2 timeout";
    				break;
    			}
    		}
    		echo "\n -----od_order".$table_type."_v2 count: ". $count.", new count: $new_insert_count";
    	}
    	catch(\Exception $ex){
    		print_r($ex->__toString());
    	}
    }
    
    public static function CustomerAliexpressItem(){
    	try{
    		$dbqueue2Conn = \yii::$app->db_queue2;
    		
    		//获取aliexpress 类别表
    		$rows = AliexpressCategory::find()->select(['cateid', 'pid', 'name_en'])->asArray()->all();
    		foreach($rows as $row){
    			self::$aliexpress_category_arr[$row['cateid']] = $row;
    		}
    		unset($rows);
    		 
    		//需查询类别信息的aliexpress item
    		$sql = "select ID, puid, product_id from customer_aliexpress_item where status='P' order by puid";
    		//分页循环
    		$page = 1;
    		$page_size = 100;
    		$count = 0;
    		$start_time = time();
    		$not_exists_puid = array();
    		$ali_user_arr = array();
    		while(1){
    			$batch_count = 0;
    			$batch_success_count = 0;
    			$update_str = '';
    			$cus_list = array();
    			$start_row = ($page - 1) * $page_size;
    			$rows = $dbqueue2Conn->createCommand($sql.' limit '.$start_row.','.$page_size)->queryAll();
    			if(!empty($rows)){
    				$update_time = time();
    				foreach($rows as $row){
    					$batch_count++;
    					$puid = $row['puid'];
    					$productid = $row['product_id'];
    					
    					if(in_array($puid, $not_exists_puid)){
    						continue;
    					}
    					
    					 
						 
						if(false){
							 
						}
						else{
							$subdbConn = \yii::$app->subdb;
							//step 1，先从aliexpress_listing_detail查询类目Id
							$categoryid = 0;
							$listring = AliexpressListingDetail::find()->select(['categoryid'])->where(['productid' => $productid])->asArray()->one();
							if(!empty($listring['categoryid'])){
								$categoryid = $listring['categoryid'];
							}
							
							//step 2，当user不存在此listing，则重新从API接口获取
							$sellerloginid = '';
							if(empty($categoryid)){
								//查询对应的aliexpress账号
								$item = OdOrderItem::findOne(['order_source_itemid' => $productid]);
								if(!empty($item)){
									$order = OdOrder::findOne(['order_id' => $item->order_id]);
									if(!empty($order)){
										$user = array();
										$sellerloginid = $order->selleruserid;
										if(array_key_exists($sellerloginid, $ali_user_arr)){
											$user = $ali_user_arr[$sellerloginid];
										}
										else{
											$saas_ali = SaasAliexpressUser::findOne(['sellerloginid' => $sellerloginid]);
											if(!empty($saas_ali)){
												$ali_user_arr[$sellerloginid] = [
													'sellerloginid' => $sellerloginid,
													'access_token' => $saas_ali->access_token,
													'app_key' => $saas_ali->app_key,
													'app_secret' => $saas_ali->app_secret,
													'status' => true,
												];
												$user = $ali_user_arr[$sellerloginid];
											}
										}
										if(!empty($user)){
											$api = new AliexpressInterface_Api ();
											if (empty($user['status'])){
												echo "\n get categoryid err from api, sellerloginid fail, productId:$productid, selleruserid:$sellerloginid";
												$update_str .= "update customer_aliexpress_item set status='F', selleruserid='$sellerloginid' where ID=".$row['ID'].";";
												continue;
											}
											$api->access_token = $user['access_token'];
											$api->app_key = $user['app_key'];
											$api->app_secret = $user['app_secret'];
											$result = $api->findAeProductById(["productId"=>$productid]);
											if(!empty($result) && !empty($result['categoryId'])){
												$categoryid = $result['categoryId'];
											}
											else if(!empty($result) && !empty($result['error_message'])){
												if($result['error_message'] == 'Request need user authorized'){
													if(!empty($ali_user_arr[$sellerloginid])){
														$ali_user_arr[$sellerloginid]['status'] = false;
													}
													echo "\n get categoryid err from api, productId:$productid, selleruserid:$sellerloginid";
												}
												else if(strpos($result['error_message'], 'does not exist') !== false){
													echo "\n ".$result['error_message'];
												}
												$update_str .= "update customer_aliexpress_item set status='F', selleruserid='$sellerloginid' where ID=".$row['ID'].";";
												continue;
											}
											else if(!empty($result) && !empty($result['error_message']) && strpos($product_attributes, ' + ') !== false){
												echo "\n get categoryid err from api, productId:$productid, selleruserid:$sellerloginid";
												$update_str .= "update customer_aliexpress_item set status='F', selleruserid='$sellerloginid' where ID=".$row['ID'].";";
												continue;
											}
											else{
												echo "\n get categoryid err from api, productId:$productid, selleruserid:$sellerloginid";
												print_r($result);
												$update_str .= "update customer_aliexpress_item set status='F', selleruserid='$sellerloginid' where ID=".$row['ID'].";";
												continue;
											}
										}
									}
								}
							}
							
							//step 3，查询一级目录名称
							$categoryName = self::getCategoryName($categoryid);
							
							if(!empty($categoryid) && empty($categoryName)){
								$update_str .= "update customer_aliexpress_item set status='F' where ID=".$row['ID'].";";
							    echo "\n get category name err: puid:$puid, product_id:$productid is not exists in db_category!";
							}
							else if(!empty($categoryName)){
							    $update_str .= "update customer_aliexpress_item set category_name_one='$categoryName', status='C', selleruserid='$sellerloginid' where ID=".$row['ID'].";";
							    $count++;
							    $batch_success_count++;
							}
							else{
								$update_str .= "update customer_aliexpress_item set status='E' where ID=".$row['ID'].";";
							}
						}
						
						//超过5分钟则退出
						if(time() - $update_time > 600){
							echo "\n update category name timeout";
							break;
						}
    				}
    	
    				if(!empty($update_str)){
    					$dbqueue2Conn->createCommand($update_str)->execute();
    					echo "\n batch_count: $batch_count, success count: $batch_success_count, consuming time: ".(time() - $update_time);
    				}
    	
    				$page++;
    				unset($rows);
    			}
    			else{
    			    break;
    		    }
        	}
    	}
		catch(\Exception $ex){
		    print_r($ex->__toString());
    	}
    }
    
    public static function UpdateEbayCategory(){
    	try{
    		$dbqueue2Conn = \yii::$app->db_queue2;
    	
    		//查询存在类别信息的ebay item
    		$sql = "select puid, product_id, category_name_one
    			from customer_ebay_item where status='C' and category_name_one != '' ";
    		//分页循环
    		$page = 1;
    		$page_size = 50;
    		$count = 0;
    		$new_insert_count = 0;
    		$start_time = time();
    		while(1){
    			$batch_count = 0;
    			$update_str = '';
    			$cus_list = array();
    			$start_row = ($page - 1) * $page_size;
    			$rows = $dbqueue2Conn->createCommand($sql.' limit '.$start_row.','.$page_size)->queryAll();
    			if(!empty($rows)){
    				$update_time = time();
    				foreach($rows as $row){
    					if(!empty($row['product_id']) && !empty($row['category_name_one'])){
    						$update_str .= "update customer_email set category_name_one='".$row['category_name_one']."' where puid='".$row['puid']."' and platform_source='ebay' and product_id='".$row['product_id']."';";
    						$count++;
    						$batch_count++;
    					}
    				}
    				
    				if(!empty($update_str)){
    					$dbqueue2Conn->createCommand($update_str)->execute();
    				}
    				
    				$page++;
    				echo "\n batch_count: $batch_count, consuming time: ".(time() - $update_time);
    				unset($rows);
    			}
    			else{
    				break;
    			}
    	
    			//超过5分钟则退出
    			if(time() - $start_time > 300){
    				echo "\n update category name timeout";
    				break;
    			}
    		}
    		
    		echo "\n\n ----- sum count: $count, consuming time: ".(time() - $start_time);
    	}
    	catch(\Exception $ex){
    		print_r($ex->__toString());
    	}
    }
    
    //获取第一级的类别名称
    public static function getCategoryName($categoryid){
        $name = "";
        if(!empty($categoryid) && array_key_exists($categoryid, self::$aliexpress_category_arr)){
            $node = self::$aliexpress_category_arr[$categoryid];
            if(empty($node['pid'])){
                $name = $node['name_en'];
            }
            else{
                $name = self::getCategoryName($node['pid']);
            }
        }
    	
        return $name;
    }
    
    //导出email信息
    public static function exportEmail(){
    	//step 1 读取需导出的email信息
    	$subdbConn = \yii::$app->db_queue2;
    	$orderby = "order by last_active_time, email_create_time, ID";
    	$condition = '';
    	$condition .= " and category_name_one!='' and country_code in ('FR') ";
    	$condition .= " and last_active_time<=".(time() - 3600 * 24 * 14) ." and email_create_time<=".(time() - 3600 * 24 * 90);
    	
    	//排除14天活跃的用户
    	$active_puid_str = '';
    	$users = UserLastActivityTime::find()->select(['puid'])->where("last_activity_time>'".date("Y-m-d", time() - 3600 * 24 * 14)."'")->asArray()->all();
    	foreach($users as $user){
    		$active_puid_str .= "'".$user['puid']."',";
    	}
    	if(!empty($active_puid_str)){
    		$active_puid_str = rtrim($active_puid_str, ",");
    		$condition .= " and puid not in ($active_puid_str) ";
    	}
    	
    	$data = $subdbConn->createCommand("select ID, email, cus_name, country_code, category_name_one from customer_email where is_export='N' $condition $orderby limit 0, 50000")->queryAll();
    	if(!empty($data)){
    		$csv_data = array();
    		$email_id_str = '';
    		$count = 0;
    		$row = 0;
    		$email_export = array();
    		$sum_count = count($data);
    		foreach ($data as $key => $value) {
    			$email_export[] = [
    				'email_id' => $value['ID'],
    				'email' => $value['email'],
    				'create_time' => time(),
    			];
    			$csv_data[] = [
	    			'email' => $value['email'],
	    			'cus_name' => $value['cus_name'],
	    			'country_code' => $value['country_code'],
	    			'category_name' => $value['category_name_one'],
    			];
    			
    			//标记此类email为已导出
    			$email_id_str .= "'".$value['ID']."',";
    			$row++;
    			$count++;
    			if($count == 5000 || $row == $sum_count){
    				if($email_id_str != ''){
    					//更新email表状态
    					$email_id_str = rtrim($email_id_str, ",");
    					$subdbConn->createCommand("update customer_email set is_export='Y', export_time=".time()." where ID in ($email_id_str)")->execute();
    					$count = 0;
    					$email_id_str = '';
    					
    					//插入到已导出表
    					\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_email_exported', $email_export, 'db_queue2');
    					$email_export = array();
    				}
    			}
    			
    			unset($data[$key]);
    		}
    		
    		$csv_data = array_merge([0 => ['email', 'cus_name', 'country_code', 'category_name']], $csv_data);
    		$file_name = 'customer_email'.date("YmdHis", time()).'.csv';
    		
    		$phpexcel = new \PHPExcel();
    		$phpexcel->setActiveSheetIndex(0);
    		$sheet = $phpexcel->getActiveSheet();
    		$sheet->setTitle("customer_email");
    		$sheet->fromArray($csv_data);
    		$writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
    		
    		$pathArr = ExcelHelper::createExcelDir(true);
    		$path = $pathArr['path'];
    		$urlpath = $pathArr['urlpath'];
    		$writer->save($path.DIRECTORY_SEPARATOR.$file_name);
    	}
    }
    
    public static function UpdateActiveTime(){
    	try{
    		//获取puid对应的活跃时间
    		$puid_activitys = array();
    		$dbConn = \yii::$app->db;
    		$puidArr = $dbConn->createCommand("SELECT puid, last_activity_time FROM user_last_activity_time where `last_activity_time`>'2017-10-10' order by puid, last_activity_time")->queryAll();
    		$dbConn->close();
    		if(!empty($puidArr)){
    			$dbqueue2Conn = \yii::$app->db_queue2;
    			foreach($puidArr as $activity){
    				$update_str = "update customer_email set last_active_time ='".strtotime($activity['last_activity_time'])."' where puid='".$activity['puid']."'";
    				$dbqueue2Conn->createCommand($update_str)->execute();
    				echo "\n update ".$activity['puid']." end";
    			}
    		}
    	}
    	catch(\Exception $ex){
    		print_r($ex->__toString());
    	}
    }
    
    public static function insertCustomerEmailCn($table_type, $puid, $puid_activitys){
    	try{
    		$dbqueue2Conn = \yii::$app->db_queue2;
    		$subdbConn = \yii::$app->subdb;
    
    		//查询订单信息
    		$sql = "select distinct consignee_email, order_source, source_buyer_user_id, consignee_country_code, create_time, consignee_phone, consignee_mobile
    			from od_order".$table_type."_v2 where consignee_email!='' and consignee_email is not null and consignee_country_code='US' ";
    		$conditon = "";
    		//忽略某些平台
    		//$conditon .= " and order_source!='amazon' and order_source!='cdiscount'";
    		//先拉取ebay and aliexpress，国家为非RU
    		//$conditon .= " and order_source in ('ebay', 'aliexpress') and consignee_country_code!='RU'";
    		//分页循环
    		$page = 1;
    		$page_size = 5000;
    		$count = 0;
    		$new_insert_count = 0;
    		$start_time = time();
    		while(1){
    			$email_str = '';
    			$cus_list = array();
    			$start_row = ($page - 1) * $page_size;
    			$orders = $subdbConn->createCommand($sql.$conditon.' group by consignee_email limit '.$start_row.','.$page_size)->queryAll();
    			if(!empty($orders)){
    				$insert_time = time();
    				$count += count($orders);
    				foreach($orders as $order){
    					$email = str_replace("'", "", $order['consignee_email']);
    					if(!array_key_exists($email, $cus_list) && (strpos($email, 'qq.') !== false || strpos($email, '163.') !== false)){
    						$cus_list[$email] = [
	    						'puid' => $puid,
	    						'email' => $email,
	    						'cus_name' => $order['source_buyer_user_id'],
	    						'platform_source' => $order['order_source'],
	    						'country_code' => $order['consignee_country_code'],
	    						'create_time' => time(),
	    						'email_create_time' => $order['create_time'],
	    						'last_active_time' => strtotime($puid_activitys[$puid]),
	    						'phone' => empty($order['consignee_phone']) ? $order['consignee_mobile'] : $order['consignee_phone'].' \ '.$order['consignee_mobile'],
    						];
    							
    						$email_str .= "'$email',";
    					}
    				}
    					
    				if($email_str != ''){
    					$email_str = rtrim($email_str, ",");
    					//查询已存在的记录
    					$rows = $dbqueue2Conn->createCommand("select email from customer_email_CN where email in ($email_str)")->queryAll();
    					if(!empty($rows)){
    						foreach($rows as $row){
    							if(array_key_exists($row['email'], $cus_list)){
    								unset($cus_list[$row['email']]);
    							}
    						}
    					}
    					unset($rows);
    				}
    					
    				$new_insert_count += count($cus_list);
    				\eagle\modules\util\helpers\SQLHelper::groupInsertToDb('customer_email_CN', $cus_list, 'db_queue2');
    				$page++;
    				echo "\n count: ".count($orders).", insert: ".count($cus_list).", consuming time: ".(time() - $insert_time);
    				unset($cus_list);
    				unset($orders);
    			}
    			else{
    				break;
    			}
    
    			//超过20分钟则退出
    			if(time() - $start_time > 1200){
    				echo "\n search od_order".$table_type."_v2 timeout";
    				break;
    			}
    		}
    		echo "\n -----od_order".$table_type."_v2 count: ". $count.", new count: $new_insert_count";
    	}
    	catch(\Exception $ex){
    		print_r($ex->__toString());
    	}
    }
    
}//enc class