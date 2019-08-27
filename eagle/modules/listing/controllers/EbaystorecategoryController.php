<?php

namespace eagle\modules\listing\controllers;

use eagle\models\SaasEbayUser;
use common\helpers\Helper_Array;
use common\api\ebayinterface\getstore;
use eagle\modules\listing\helpers\StorecategoryHelper;
use yii\helpers\Json;
use eagle\modules\listing\models\EbayStorecategory;
use common\api\ebayinterface\setstorecategories;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\listing\models\EbayAccountMap;
class EbaystorecategoryController extends \yii\web\Controller
{
	public $enableCsrfValidation = false;
	/**
	 * 读取ebay店铺分类
	 *@author fanjs
	 */
	public function actionListstorecategory(){
		AppTrackerApiHelper::actionLog('listing_ebay','/ebaystorecategory/liststorecategory');
		$ebayselleruserid=SaasEbayUser::findAll(['uid'=>\Yii::$app->user->identity->getParentUid()]);
		$list=NULL;
		if (\Yii::$app->request->isPost){
			$categorys=EbayStorecategory::find()->where(['selleruserid'=>$_POST['selleruserid']])->asArray()->all();
			$ct=Helper_Array::toTree($categorys, 'categoryid', 'category_parentid');
			Helper_Array::QTree2list ( $list, $ct, 'categoryid', 'category_parent', 'children' );
		}
		return $this->render('liststorecategory',['ebayselleruserid'=>Helper_Array::toHashmap($ebayselleruserid, 'selleruserid', 'selleruserid'),'ct'=>$list]);
	}
	
	/**
	 * ajax即时更新eBay账号的店铺信息
	 * @author fanjs
	 */
	public function actionUpdatecategory(){
		AppTrackerApiHelper::actionLog('listing_ebay','/ebaystorecategory/updatecategory');
		$selleruserid = $_POST['selleruserid'];
		$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$_POST['selleruserid']]);
		$api = new getstore();
		$api->resetConfig($ebayuser->DevAcccountID);
		$api->eBayAuthToken = $ebayuser->token;
		$response = $api->api ( $selleruserid );
		//保存 类目
		if (isset ( $response ['Store'] ['CustomCategories'] ['CustomCategory'] )) {
			StorecategoryHelper::saveStorecategory ( \Yii::$app->user->identity->getParentUid(), $selleruserid, $response ['Store'] ['CustomCategories'] ['CustomCategory'] );
			StorecategoryHelper::clearStorecategory ( \Yii::$app->user->identity->getParentUid(), $selleruserid, $response ['Store'] ['CustomCategories'] ['CustomCategory'] );
		}
		return Json::encode($response);
	}
	
	/**
	 * 展示修改店铺猎木的model层
	 * @author fanjs
	 */
	public function actionMod(){
		$category = EbayStorecategory::findOne(['selleruserid'=>$_POST['selleruserid'],'categoryid'=>$_POST['categoryid']]);
		if ($category->category_parentid>0){
			$pa_category = EbayStorecategory::findOne(['selleruserid'=>$_POST['selleruserid'],'categoryid'=>$category->category_parentid]);
		}else{
			$pa_category = NULL;
		}
		$type = $_POST['type'];
		return $this->renderPartial('mod',['ca'=>$category,'pca'=>$pa_category,'type'=>$type]);
	}
	
	/**
	 * 接口处理修改店铺类目
	 * @author fanjs
	 */
	public function actionDomod(){
		if (\Yii::$app->request->isPost){
			$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$_POST['selleruserid']]);
			if (empty($ebayuser))return '找不到对应账号';
			if ($_POST['type']=='addlevel'||$_POST['type']=='addsub'){
				$api = new setstorecategories();
				$api->resetConfig($ebayuser->DevAcccountID);
				$api->eBayAuthToken = $ebayuser->token;
				$input = ['Name' => '<![CDATA[' . $_POST['name'] . ']]>' ];
				$result = $api->add($input,$_POST['cid']);
				if ($result['Ack']=='Success'||$result['Ack']=='Warning'){
					StorecategoryHelper::saveStorecategory ( \Yii::$app->user->identity->getParentUid(), $_POST['selleruserid'], $result['CustomCategory'] ['CustomCategory'],$_POST['cid'] );
					return $result['Ack'];
				}else{
					return $result['Errors']['LongMessage'];
				}
			}elseif ($_POST['type']=='edit'){
				$api = new setstorecategories();
				$api->resetConfig($ebayuser->DevAcccountID);
				$api->eBayAuthToken = $_POST['selleruserid'];
				$input = ['CategoryID' => $_POST['cid'], 'Name' => '<![CDATA[' . $_POST['name'] . ']]>' ];
				$result = $api->rename($input);
				if ($result['Ack']=='Success'||$result['Ack']=='Warning'){
					$category = EbayStorecategory::findOne(['selleruserid'=>$_POST['selleruserid'],'categoryid'=>$_POST['cid']]);
					$category->category_name=$_POST['name'];
					$category->save(false);
					return $result['Ack'];
				}else{
					return $result['Errors']['LongMessage'];
				}
			}
		}
	}
	
	/**
	 * 删除ebay类目
	 * @author fanjs
	 */
	public function actionDodel(){
		$selleruserid = $_POST['selleruserid'];
		$categoryid = $_POST['cid'];
		$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$_POST['selleruserid']]);
		if (empty($ebayuser))return '找不到对应账号';
		// 删除
		$api  = new setstorecategories();
		$api->resetConfig($ebayuser->DevAcccountID);
		$api->eBayAuthToken = $ebayuser->token;
		$result = $api->delete ( $categoryid );
		if ($result['Ack']=='Success') {
			EbayStorecategory::deleteAll(['uid'=>\Yii::$app->user->identity->getParentUid(),'selleruserid'=>$_POST['selleruserid'],'categoryid'=>$categoryid]);
			return $result['Ack'];
		}else{
			return $result['Errors']['LongMessage'];
		}
		
	}
	
	/**
	 * 读取ebay店铺分类
	 *
	 */
	function actionData(){
		$selleruserid = $_GET['selleruserid'];
		$result = EbayStorecategory::findAll(['selleruserid'=>$selleruserid]);
		exit(Json::encode($result));
	}
	
	/**
	 * 模板设置店铺类目的modal
	 * @author fanjs
	 */
	function actionMubansetstorecategory(){
		if (\Yii::$app->request->isPost){
			$categorys=EbayStorecategory::find()->where(['selleruserid'=>$_POST['selleruserid']])->asArray()->all();
			$ct=Helper_Array::toTree($categorys, 'categoryid', 'category_parentid');
			Helper_Array::QTree2list ( $list, $ct, 'categoryid', 'category_parent', 'children' );
			return $this->renderPartial('mubansetstorecategory',['ct'=>$list,'cid'=>$_POST['cid']]);
		}
	}
	
	/**
	 * 绑定ebay与paypal账号的映射关系的列表
	 * @author fanjs
	 */
	function actionBindaccountmap(){
		$maps = EbayAccountMap::find()->all();
		$_tmp = [];
		foreach ($maps as $map){
			$_subtmp=[
				'paypal'=>$map->paypal,
				'desc'=>$map->desc
			];
			$_tmp[$map->selleruserid][]=$_subtmp;
		}
		return $this->render('bindaccountmaplist',['maps'=>$_tmp]);
	}
	
	/**
	 * 编辑或新建账号映射关系的逻辑处理
	 * @author fanjs
	 */
	function actionEditmap(){
		if(\Yii::$app->request->isPost){
			if (isset($_POST['selleruserid'])){
				EbayAccountMap::deleteAll(['selleruserid'=>$_POST['selleruserid']]);
			}
			for ($i=0;$i<count($_POST['paypal'])-1;$i++){
				$map = new EbayAccountMap();
				$map->selleruserid = $_POST['selleruserid'];
				$map->paypal = $_POST['paypal'][$i];
				$map->desc = $_POST['desc'][$i];
				$map->created = time();
				$map->updated = time();
				$map->save();
			}
			echo '<script>window.opener.location.reload();</script>';
			return $this->redirect(['/listing/ebaystorecategory/bindaccountmap']);
		}
		if (isset($_GET['selleruserid'])&&strlen($_GET['selleruserid'])){
			$maps = EbayAccountMap::find()->where(['selleruserid'=>$_GET['selleruserid']])->all();
		}else{
			$maps = [];
		}
		$ebayselleruserid = SaasEbayUser::find()->where('uid = '.\Yii::$app->user->identity->getParentUid())->all();
		return $this->render('editmap',['ebayselleruserid'=>$ebayselleruserid,'maps'=>$maps]);
	}
	
	/**
	 * 删除存在的映射关系
	 * @author fanjs
	 */
	function actionDeletemap(){
		if(\Yii::$app->request->isPost){
			if (isset($_POST['selleruserid'])){
				EbayAccountMap::deleteAll(['selleruserid'=>$_POST['selleruserid']]);
				return 'success';
			}
		}
	}
}
