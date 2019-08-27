<?php

namespace eagle\modules\listing\controllers;

use eagle\models\SaasEbayUser;
use common\helpers\Helper_Array;
use eagle\models\EbayAutoadditemset;
use yii\data\Pagination;
use eagle\modules\listing\models\EbayLogMuban;
use eagle\modules\listing\models\EbayLogMubanDetail;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
class AdditemsetController extends \yii\web\Controller
{
	public $enableCsrfValidation = false;
	/**
	 * 展示用户定时设置
	 * @author fanjs
	 */
	function actionList(){
		AppTrackerApiHelper::actionLog('listing_ebay','/additemset/list');
		return $this->render('../_ebay_disable');
		$data = EbayAutoadditemset::find()->andWhere(['uid'=>\Yii::$app->user->identity->getParentUid()]);
		if (isset($_REQUEST['selleruserid'])&&strlen($_REQUEST['selleruserid'])){
			$data->andWhere(['selleruserid'=>@$_REQUEST['selleruserid']]);
		}
		if (isset($_REQUEST['title'])&&strlen($_REQUEST['title'])){
			$data->andWhere(['itemtitle'=>@$_REQUEST['title']]);
		}
		if (isset($_REQUEST['mubanid'])&&strlen($_REQUEST['mubanid'])){
			$data->andWhere(['mubanid'=>@$_REQUEST['mubanid']]);
		}
		$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>'50','params'=>$_REQUEST]);
		$sets = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		
		$ebayselleruserid=SaasEbayUser::find()->where('uid = :uid and expiration_time > :expiretime',[':uid'=>\Yii::$app->user->identity->getParentUid(),':expiretime'=>time()])->all();
		return $this->render('list', ['ebayselleruserid'=>Helper_Array::toHashmap($ebayselleruserid, 'selleruserid', 'selleruserid'),'sets'=>$sets,'pages'=>$pages]);
	}
	
	/**
	 * 删除定时刊登
	 * @author fanjs
	 */
	function actionDelete(){
		if (\Yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog('listing_ebay','/additemset/delete');
			try {
				if (isset($_POST['timerid'])||isset($_POST['mubanid'])){
					if (isset($_POST['timerid'])){
						$ids=explode(',',$_POST['timerid']);
					}elseif (isset($_POST['mubanid'])){
						$ids = explode(',',$_POST['mubanid']);
						$ids = array_filter($ids);
						$ids = EbayAutoadditemset::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid(),'mubanid'=>$ids])->select('timerid')->asArray()->all();
						$ids = Helper_Array::getCols($ids,'timerid');
					}
					EbayAutoadditemset::deleteAll(['uid'=>\Yii::$app->user->identity->getParentUid(),'timerid'=>$ids]);
					return 'success';
				}else{
					return '未选中相应订单';
				}
			}catch (\Exception $e){
				return $e->getMessage();
			}
		}
	}
	
	/**
	 * 刊登失败的记录
	 * @author fanjs
	 */
	function actionLoglist(){
		AppTrackerApiHelper::actionLog('listing_ebay','/additemset/loglist');
		return $this->render('../_ebay_disable');
		$data = EbayLogMuban::find()->where('result<1');
		if (isset($_REQUEST['selleruserid'])&&strlen($_REQUEST['selleruserid'])){
			$data->andWhere(['selleruserid'=>@$_REQUEST['selleruserid']]);
		}
		if (isset($_REQUEST['title'])&&strlen($_REQUEST['title'])){
			$data->andWhere(['title'=>@$_REQUEST['title']]);
		}
		if (isset($_REQUEST['mubanid'])&&strlen($_REQUEST['mubanid'])){
			$data->andWhere(['mubanid'=>@$_REQUEST['mubanid']]);
		}
		$data->orderBy('createtime DESC');
		$data->with(['detail']);
		$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>'50','params'=>$_REQUEST]);
		$sets = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		
		$ebayselleruserid=SaasEbayUser::find()->where('uid = :uid and expiration_time > :expiretime',[':uid'=>\Yii::$app->user->identity->getParentUid(),':expiretime'=>time()])->all();
		return $this->render('loglist', ['ebayselleruserid'=>Helper_Array::toHashmap($ebayselleruserid, 'selleruserid', 'selleruserid'),'sets'=>$sets,'pages'=>$pages]);
	}
	
	/**
	 * 删除定时刊登
	 * @author fanjs
	 */
	function actionDeletelog(){
		if (\Yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog('listing_ebay','/additemset/deletelog');
			try {
				if (strlen($_POST['logid'])){
					$ids=explode(',',$_POST['logid']);
					$ids = array_filter($ids);
					EbayLogMuban::deleteAll(['logid'=>$ids]);
					EbayLogMubanDetail::deleteAll(['logid'=>$ids]);
					return 'success';
				}else{
					return '未选中相应订单';
				}
			}catch (\Exception $e){
				return $e->getMessage();
			}
		}
	}
}
