<?php
namespace console\helpers;

use yii;
use Exception;
use common\api\ebayinterface\base;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;
use common\helpers\Helper_Siteinfo;
use common\api\ebayinterface\getebaydetails;
use common\api\ebayinterface\getcategories;
use eagle\models\EbayCategory;
use common\helpers\Helper_Filesys;
use common\helpers\Helper_XmlLarge;
use common\api\ebayinterface\getcategoryspecifics;
use common\api\ebayinterface\lms\downloadfile;
use eagle\models\EbaySpecific;
use common\helpers\Helper_xml;
use eagle\models\EbaySite;
use common\api\ebayinterface\getcategoryfeatures;
/**
 +------------------------------------------------------------------------------
 * ebay 数据同步类
 +------------------------------------------------------------------------------
 * @category	SaasEbayAutosyncstatusHelper
 * @package		
 * @subpackage

 +------------------------------------------------------------------------------
 */
class SaasEbayAutosyncstatusHelper {
	/**
	 +----------------------------------------------------------
	 * 基础同步程序
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param type		同步类型  1: 订单 2: 刊登 3: 站内信 4: 评价 5: 售前纠纷Dispute 6: 全部纠纷UserCase 7:item
	 * @param block		代码块字符串
	 * @param $sub_id	当前进程号
	 +----------------------------------------------------------
	 * @return			mixed
	 +----------------------------------------------------------
	**/
	static function baseSyncFun($type, $block , $sub_id=''){
		//$queryTmp = QueueGetorder::find()->leftJoin('saas_ebay_user',"saas_ebay_user.selleruserid  = queue_getorder.selleruserid ")->where('(`status` = 1 AND `updated` < '.(time() - 240).') OR (`status` = 0 AND `created` <= '.time().' AND `updated` > 0)'.$coreStr)->orderBy('updated asc')->limit(150);
		$sub_id = '';// TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !==""){
			//多进程
			$totalJob = 3;
			if ($totalJob<=$sub_id){
				echo "\n sub id(".$sub_id.") must < total job number(".$totalJob.")";
				return;
			}
			$coreStr = ' and ebay_uid %'.$totalJob.'= '.$sub_id;
		}else{
			//单进程
			$coreStr = '';
		}
		
		if ($type == 1){
			// 订单同步，30分中内没有 同步 过的才执行同步 
			$coreStr .= " and lastrequestedtime <='".strtotime('-30 minutes')."' ";
		}
		
		$queryTmp = SaasEbayAutosyncstatus::find()
		->where('`type` = '.$type.' AND `status` = 1 AND `status_process` <> 1'.$coreStr)->orderBy("lastrequestedtime ASC")->limit(150);
		$Ms = $queryTmp->all();
		
		echo "\n  v1.8 ".date('Y-m-d H:i:s')." job id=$sub_id queue count =".count($Ms);
  		echo "\n".$queryTmp->createCommand()->getRawSql();
// 		exit();
		if(count($Ms)){
			foreach($Ms as $M) {
				$eu = SaasEbayUser::find()->where(['selleruserid'=>$M->selleruserid])->one();
				if (empty($eu)){
					echo 'no selleruserid!'.$M->selleruserid.",system will delete the sync of this account;\n";
					SaasEbayAutosyncstatus::deleteAll(['selleruserid'=>$M->selleruserid]);
					continue;
				}
				$CurrentTime = time();
				$M->lastrequestedtime = $CurrentTime;
				$M->status_process = 1;
				if (!$M->save()){
					print_r($M->getErrors());
				}
				
				$default_ModTimeFrom =time() - 86400 * 29 - 10;
				//是否首次同步同步间隔不同
				$ModTimeFrom = ($M->lastprocessedtime > 0) ? ($M->lastprocessedtime - 1200) : $default_ModTimeFrom;
				if ($type=='1'){
					//$ModTimeFrom = $ModTimeFrom- 86400*5; // 防止拍卖订单不能拉取， 试用 5天为起点
					//订单同步 最多 只能获取30天的订单
					if ($ModTimeFrom<$default_ModTimeFrom) $ModTimeFrom = $default_ModTimeFrom;
				}
				
				
				echo "\n++++++++++++++++++++\n[".date('Ymd His')."]beginCron\nselleruserid:".$M->selleruserid." \n from:".date('Ymd His', $ModTimeFrom).'- to:'.date('Ymd His', time())." \n uid=".$eu->uid."\n";
				$M->status_process = 0;
				try{
					$flag = false;
					//执行block
					eval($block);
					if($flag) {
						$M->lastprocessedtime = $CurrentTime;
						$M->status_process = 2;
					}
				}catch(\Exception $ex){
					echo "\n".(__function__).'Error Message:'.$ex->getMessage()." Line no :".$ex->getLine()."\n";
				}
				$M->save();
			}
		}
		//半个小时都未处理完成的,状态 重置为0
		SaasEbayAutosyncstatus::updateAll(['status_process' => 0], 'type='.$type.' AND `status` = 1 AND `status_process` = 1 AND lastrequestedtime < :p', array(':p'=>time()-1800));
	}

	/**
	 +----------------------------------------------------------
	 * 同步订单数据(每个账号半小时同步一次)
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return			mixed
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lxqun	2014/02/05				初始化
	 * @editor		hxl		2014/06/13				代码结构简化
	 +----------------------------------------------------------
	**/
	static function AutoSyncOrder($sub_id='') {
		$block = '$flag = \common\api\ebayinterface\getsellertransactions::cronInsertIntoQueueGetOrder($eu, 0, $ModTimeFrom, time());';
		self::baseSyncFun(1, $block,$sub_id);
	}

	/**
	 +----------------------------------------------------------
	 * 同步站内信
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return			mixed
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lxqun	2014/02/05				初始化
	 * @editor		hxl		2014/06/13				代码结构简化
	 +----------------------------------------------------------
	**/
	static function AutoSyncMessage() {
		$block = '$flag = \common\api\ebayinterface\getmymessages::cronRequest($eu, $ModTimeFrom, time());';
		self::baseSyncFun(3, $block);
	}

	/**
	 +----------------------------------------------------------
	 * 同步评价
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return			mixed
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lxqun	2014/02/05				初始化
	 * @editor		hxl		2014/06/13				代码结构简化
	 +----------------------------------------------------------
	**/
	static function AutoSyncFeedback() {
		$block = '$flag = \common\api\ebayinterface\getfeedback::cronRequest($eu);';
		self::baseSyncFun(4, $block);
	}

	/**
	 +----------------------------------------------------------
	 * 同步售前纠纷
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return			mixed
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lxqun	2014/02/05				初始化
	 * @editor		hxl		2014/06/13				代码结构简化
	 +----------------------------------------------------------
	**/
	static function AutoSyncDispute() {
		$block = 'Yii::app()->subdb->setDbBySelleruserid($M->selleruserid);$flag = EbayInterface_GetUserDisputes::cronRequest($eu, $ModTimeFrom, time());';
		self::baseSyncFun(5, $block);
	}

	/**
	 +----------------------------------------------------------
	 * 同步售后纠纷
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return			mixed
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lxqun	2014/02/05				初始化
	 * @editor		hxl		2014/06/13				代码结构简化
	 +----------------------------------------------------------
	**/
	static function AutoSyncUserCase() {
		$block = 'Yii::app()->subdb->setDbBySelleruserid($M->selleruserid);$flag = EbayInterface_Resolution_getUserCases::cronRequest($eu, $ModTimeFrom, time());';
		self::baseSyncFun(6, $block);
	}
	
	/*@author fanjs
	 * 同步eBay的Site信息
	 * **/
	// static function AutoSyncEbaySite(){
	// 	$sites=Helper_Siteinfo::getEbaySiteIdList();
	// 	foreach ($sites as $site){
	// 		echo "start site:".$site['en']."------>\n";
	// 		$api=new getebaydetails();
	// 		set_time_limit(0);
	// 		$api->renewEbayDetail($site['no']);
	// 		echo "site:".$site['en']."updated!------>\n";
	// 	}
	// }
	
	
	/*@author fanjs
	 * 同步eBay的刊登类目信息
	* **/
	// static function AutoSyncEbayCategory(){
	// 	$sites=Helper_Siteinfo::getEbaySiteIdList();
	// 	$ue=SaasEbayUser::find()->where('selleruserid = :s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
	// 	foreach ($sites as $site){
	// 		echo "start sync category,site:".$site['en']."------>\n";
	// 		$api=new getcategories();
 //            $api->eBayAuthToken=$ue->token;
 //            $api->siteID=$site['no'];
 //            set_time_limit(0);
 //            //如果版本没有做更新则不更新
 //            $r=$api->realtimeApi();
 //            if ($r['Ack']=='Failure'){
 //            	echo "getcategory api is failure!\n";
 //            	continue;
 //            }
 //            $category=EbayCategory::find()->where('siteid = :s',[':s'=>$site['no']])->orderBy('version DESC')->one();
 //            if (!empty($category)&&$category->version >= $r['CategoryVersion']){
 //            	echo "no updates\n";
 //            	continue;
 //            }
 //            $version=$r['CategoryVersion'];
 //            echo "new version :".$r['CategoryVersion']."\n";

 //            $xmlfilename=Yii::$app->basePath.'/runtime/xml/category/'.$site['no'].'.xml';
 //            Helper_Filesys::mkdirs(dirname($xmlfilename));
 //            echo "start download all categories\n";
 //            $r=$api->realtimeApi(0,100,600,$xmlfilename);

 //            if ($r===false){
 //            	echo "update failed\n";
 //            	continue;
 //            }
 //            echo "start analyze\n";

 //            $reader=new Helper_XmlLarge($xmlfilename);
 //            try{
 //            $reader->read('Ack');
 //            }catch (Exception $ex){
 //            	echo "read file failed\n";
 //            	continue;
 //            }
 //            if ((string)$reader->toSimpleXmlObj()=='Failure'){
 //            	echo "update failed\n";
 //            	continue;
 //            }
 //            $fp=fopen($xmlfilename,'r');
 //            fseek($fp,-24,SEEK_END);
 //            if (fgets($fp)!='</GetCategoriesResponse>'){
 //            	echo "update failed\n";
 //            	if($fp) fclose($fp);
 //            	continue;
 //            }
 //            if($fp) fclose($fp);
 //            EbayCategory::deleteAll('siteid=:s',[':s'=>$site['no']]);

 //            while ($reader->read('Category')){
 //            	$c=$reader->toSimpleXmlObj();
 //            	$ec=new EbayCategory();
 //            	$ec->setAttributes(array(
 //            			'categoryid'=>(int)$c->CategoryID,
 //            			'name'=>(string)$c->CategoryName,
 //            			'pid'=>(string)$c->CategoryParentID,
 //            			'level'=>(int)$c->CategoryLevel,
 //            			'leaf'=>((@$c->LeafCategory)?1:0),
 //            			'siteid'=>$site['no'],
 //            			'islsd'=>((@$c->LSD)?1:0),
 //            			'bestofferenabled'=>@$c->BestOfferEnabled==true?1:0,
 //            			'version'=>$version,
 //            	));
 //            	$ec->save();
 //            }
	// 		echo "category,site:".$site['en']." updated!------>\n";
	// 	}
	// }
	
	/*@author fanjs
	 * 同步eBay的Specifics信息
	* **/
// 	static function AutoSyncEbaySpecific(){
// 		$sites=Helper_Siteinfo::getEbaySiteIdList();
// 		$ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
// 		foreach ($sites as $site){
// 			echo "start specific site:".$site['en']."------>\n";
            
// 			$s = EbaySite::findOne('siteid = '.$site['no']);
//             $gcs=new getcategoryspecifics();
//             $gcs->eBayAuthToken=$ue->token;
//             $gcs->siteID=$site['no'];
//             $result=$gcs->api();
//             if ($result['Ack']!=='Success'&&$result['Ack']!=='Failure'){
//             	continue;
//             }
//             $fileReferenceId = $result['FileReferenceID'];
//             $taskReferenceId = $result['TaskReferenceID'];
//             if (strlen($fileReferenceId)==0&&strlen($taskReferenceId)==0){
//             	continue;
//             }
//             if ($fileReferenceId.'-'.$taskReferenceId == $s->specifics_jobid){
//                 echo "no updates\n";
//                 continue;
//             }
//             echo "start download specifics\n";
//             $df=new downloadfile();
//             $df->eBayAuthToken=$ue->token;
//             $filename=Yii::$app->basePath.'/runtime/xml/specifics/'.$site['no'].'.zip';
//             $response=$df->api($fileReferenceId,$taskReferenceId,$filename);
//             set_time_limit(0);
//             if (!$response){
//                 echo "download failed\n";
//                 continue;
//             }
//             echo "start analyze specific\n";
//             $xmlfile=dirname($filename).'/'.$taskReferenceId.'_report.xml';
//             $reader=new Helper_XmlLarge($xmlfile);
//             while ($reader->read('Recommendations')){
//                 $xr=$reader->toSimpleXmlObj();
                
//                 $categoryID=(string)$xr->CategoryID;
//                 EbaySpecific::deleteAll('categoryid = :categoryid and siteid = :siteid',[':categoryid'=>$categoryID,':siteid'=>$site['no']]);
// //                Yii::app()->db->createCommand("OPTIMIZE TABLE ".EbaySpecific::model()->tableName()." ;")->query();
//                 foreach ($xr->NameRecommendation as $xrnr){
//                     $xrnr=Helper_xml::simplexml2a($xrnr);
                    
//                     $name=$xrnr['Name'];
//                     $maxvalue='';
//                     $minvalue='';
//                     $val=array();
//                     $relationship=array();
//                     if (isset($xrnr['ValidationRules']['MaxValues'])){
//                         $maxvalue=$xrnr['ValidationRules']['MaxValues'];
//                     }
//                     if (isset($xrnr['ValidationRules']['MinValues'])){
//                         $minvalue=$xrnr['ValidationRules']['MinValues'];
//                     }
//                     if (isset($xrnr['ValidationRules']['Relationship'])){
//                         if(!empty($xrnr['ValidationRules']['Relationship']['ParentName'])){
//                             $relationship['ParentName']=$xrnr['ValidationRules']['Relationship']['ParentName'];
//                         }
//                         if(!empty($xrnr['ValidationRules']['Relationship']['ParentValue'])){
//                             $relationship['ParentValue']=$xrnr['ValidationRules']['Relationship']['ParentValue'];
//                         }
//                     }
//                     $selectionmode=$xrnr['ValidationRules']['SelectionMode'];
//                     if (isset($xrnr['ValueRecommendation']['Value'])){
//                         $xrnr['ValueRecommendation']=array($xrnr['ValueRecommendation']);
//                     }
//                     if (isset($xrnr['ValueRecommendation'])){
//                         foreach ($xrnr['ValueRecommendation'] as $xrnrvr){
//                             array_push($val,$xrnrvr['Value']);
//                         }
//                     }
//                     $es=new EbaySpecific();
//                     foreach ($val as &$v){
//                         $v=(string)$v;
//                     }
//                     $es->setAttributes(array(
//                         'categoryid'=>$categoryID,
//                         'siteid'=>$site['no'],
//                         'name'=>$name,
// //                         'value'=>$val,
// //                         'relationship'=>$relationship,
//                         'maxvalue'=>$maxvalue,
//                         'minvalue'=>$minvalue,
//                         'selectionmode'=>$selectionmode
//                     ));
//                     $es->relationship = $relationship;
//                     $es->value = $val;
//                     @$es->save();
//                 }
//             }
//             $sitetmp=EbaySite::find()->where('siteid = :s',[':s'=>$site['no']])->one();
//             $sitetmp->specifics_jobid=$fileReferenceId.'-'.$taskReferenceId;
//             $sitetmp->save();
//             if(file_exists($xmlfile)){
//                 unlink($xmlfile);
//                 unlink($filename);
//             }
// 			echo "specific site:".$site['en']." updated!------>\n";
// 		}
// 	}
	
	/*@author fanjs
	 * 同步eBay的Specifics信息,通过特定的类目
	* **/
// 	static function AutoSyncEbaySpecificByCategoryID($siteid=0,$categoryid){
// 		$sites=Helper_Siteinfo::getEbaySiteIdList();
// 		$ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
// 		echo "start specific site:".$siteid." categoryid:".$categoryid."------>\n";

// 		$s = EbaySite::findOne('siteid = '.$siteid);
// 		$gcs=new getcategoryspecifics();
// 		$gcs->eBayAuthToken=$ue->token;
// 		$gcs->categoryid = $categoryid;
// 		$gcs->siteID=$siteid;
// 		$result=$gcs->api();
// 		if ($result['Ack']!=='Success'&&$result['Ack']!=='Failure'){
// 			echo "getcategoryspecifics returns not OK, so stop\n";
// 			return ;
// 		}
// 		EbaySpecific::deleteAll('categoryid = :categoryid and siteid = :siteid',[':categoryid'=>$categoryid,':siteid'=>$siteid]);
// 		foreach ($result['Recommendations']['NameRecommendation'] as $xrnr){

// 			$name=$xrnr['Name'];
// 			$maxvalue='';
// 			$minvalue='';
// 			$val=array();
// 			$relationship=array();
// 			if (isset($xrnr['ValidationRules']['MaxValues'])){
// 				$maxvalue=$xrnr['ValidationRules']['MaxValues'];
// 			}
// 			if (isset($xrnr['ValidationRules']['MinValues'])){
// 				$minvalue=$xrnr['ValidationRules']['MinValues'];
// 			}
// 			if (isset($xrnr['ValidationRules']['Relationship'])){
// 				if(!empty($xrnr['ValidationRules']['Relationship']['ParentName'])){
// 					$relationship['ParentName']=$xrnr['ValidationRules']['Relationship']['ParentName'];
// 				}
// 				if(!empty($xrnr['ValidationRules']['Relationship']['ParentValue'])){
// 					$relationship['ParentValue']=$xrnr['ValidationRules']['Relationship']['ParentValue'];
// 				}
// 			}
// 			$selectionmode=$xrnr['ValidationRules']['SelectionMode'];
// 			if (isset($xrnr['ValueRecommendation']['Value'])){
// 				$xrnr['ValueRecommendation']=array($xrnr['ValueRecommendation']);
// 			}
// 			if (isset($xrnr['ValueRecommendation'])){
// 				foreach ($xrnr['ValueRecommendation'] as $xrnrvr){
// 					array_push($val,$xrnrvr['Value']);
// 				}
// 			}
// 			$es=new EbaySpecific();
// 			foreach ($val as &$v){
// 				$v=(string)$v;
// 			}
// 			$es->setAttributes(array(
// 				'categoryid'=>$categoryid,
// 				'siteid'=>$siteid,
// 				'name'=>$name,
// //				'value'=>$val,
// //				'relationship'=>$relationship,
// 				'maxvalue'=>$maxvalue,
// 				'minvalue'=>$minvalue,
// 				'selectionmode'=>$selectionmode
// 			));
// 			$es->relationship = $relationship;
// 			$es->value = $val;
// 			$es->save();
// 		}
// 		echo "specific site:".$siteid." categoryid:".$categoryid."!------>\n";
// 	}
	
	/*@author fanjs
	 * 同步eBay的feature信息
	* **/
	// static function AutoSyncEbayFeature(){
	// 	$sites=Helper_Siteinfo::getEbaySiteIdList();
	// 	$ue=SaasEbayUser::find()->where('selleruserid=:s',[':s'=>base::DEFAULT_REQUEST_USER])->one();
	// 	foreach ($sites as $site){
	// 		$categorys=EbayCategory::findBySql('select * from ebay_category where leaf=1 AND siteid='.$site['no'])->all();
	// 		foreach ($categorys as $c){
	// 			echo "start feature site:".$site['en'].",categoryid:".$c->categoryid."------>\n";
	// 			set_time_limit(0);
	//             $api=new getcategoryfeatures();
	//             $api->eBayAuthToken=$ue->token;
 // 	            $api->siteID=$site['no'];
 // 	            $r=$api->saveAllConditions($c->categoryid);
	// 			echo "feature site:".$site['en'].",categoryid:".$c->categoryid." updated!------>\n";
	// 		}
	// 	}
	// }
}
