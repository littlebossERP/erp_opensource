<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\modules\platform\apihelpers\NeweggAccountsApiHelper;
use eagle\models\SaasNeweggUser;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\SaasNeweggAutosync;

/**
 +------------------------------------------------------------------------------
 * newegg账号模块控制类
 +------------------------------------------------------------------------------
 +------------------------------------------------------------------------------
 */
class NeweggAccountsController extends \eagle\components\Controller {

	/**
	 * 打开新建/编辑newegg账号窗口
	 * @author winton
	 * @return string
	 */
	public function actionAccountInfoWindow(){
		$site_id = $_POST['site_id'];
		
		$info = SaasNeweggUser::findOne($site_id);
// 		$info = [];
		
		return $this->renderPartial('account-info',[
					'info' => $info
				]);
	}
	
	public function actionSaveAccountInfo(){
		$ret = NeweggAccountsApiHelper::saveNeweggAccountInfo($_POST);
		
		return $ret;
	}
	
	public function actionSetAccountSync(){
		if (\Yii::$app->request->isPost){
			
			$site_id = $_POST['site_id'];
			
			$user = SaasNeweggUser::findOne(['site_id'=> $site_id]);
			if ( null == $user ){
				return ResultHelper::getFailed('', 1, TranslateHelper::t('无该账号'));
			}
			if (isset($_POST['is_active'])){
				$user->is_active = $_POST['is_active'];
				if($user->save()){
					
					SaasNeweggAutosync::updateAll(['is_active'=>$user->is_active],['site_id'=>$site_id]);
					
					return ResultHelper::getSuccess('', 1, TranslateHelper::t('设置成功'));
				}else{
					$rtn_message = '';
					foreach ($user->errors as $k => $anError){
						$rtn_message .= ($rtn_message==""?"":"<br>"). $k.":".$anError[0];
					}
					return ResultHelper::getFailed('', 1, $rtn_message);
				}
			}
			else{
				return ResultHelper::getFailed('', 1, TranslateHelper::t('网络异常！请刷新后再试！'));
			}
		}
	}
	
	public function actionDeleteAccount(){
		if (!isset($_POST["site_id"])){
			return ResultHelper::getFailed('', 1, 'Err:NE_Del001。网络异常，请刷新后再试！');
		}
		if (!isset($_POST["store_name"])){
			return ResultHelper::getFailed('', 1, 'Err:NE_Del002。网络异常，请刷新后再试！');
		}
		$store_name = @$_POST["store_name"];
		return NeweggAccountsApiHelper::deleteNeweggAccount($_POST["site_id"], $store_name);
	}
}