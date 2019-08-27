<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use yii\data\Pagination;
use eagle\models\SaasPaypalUser;
use common\api\paypalinterface\PaypalInterface_GetTransactionDetails;
class PaypalAccountsController extends \eagle\components\Controller{
	/**
	 * Paypal账号列表view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/15		初始化
	 +----------------------------------------------------------
	**/
	public function actionList() {
		$puid = \Yii::$app->user->identity->getParentUid();
		
		$query = SaasPaypalUser::find()->where(["puid" => $puid]);
		
		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $query->count(),
		]);
		
		$PaypalAccounts = $query
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy(' ppid ASC ')
			->asArray()
			->all();
		
		return $this->render('list', [
				"pagination"=>$pagination ,
				"PaypalAccounts"=>$PaypalAccounts
			]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定PaypalUser账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/15		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array("mode"=>"new"));
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Paypal账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/15		初始化
	 +----------------------------------------------------------
	 **/	
	public function actionCreate() {
		$paypal_user = empty($_POST['paypal_user'])?'':trim($_POST['paypal_user']);
		$overwrite_ebay = @$_POST['overwrite_ebay_consignee_address'];
		if(empty($paypal_user) || empty($overwrite_ebay))
			exit (json_encode(array("code"=>"fail","message"=>"提交的信息不完整，绑定失败")));
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$uid = \Yii::$app->user->id;
		
		//测试权限
		$test_connent = PaypalInterface_GetTransactionDetails::test_request($transactionid='', $paypal_user);
		//var_dump($test_connent);
		if(stripos($test_connent,'do not have permissions')!==false){
			//测试失败
			exit (json_encode(array("code"=>"fail","message"=>"绑定失败，请先到Pypal后台授权小老板对此Paypal账号的第三方许可")));
		}else{
			//测试成功
			$paypaluser = new SaasPaypalUser();
			$paypaluser->paypal_user = $paypal_user;
			$paypaluser->is_active = 1;
			$paypaluser->overwrite_ebay_consignee_address = $overwrite_ebay;
			$paypaluser->create_time = time();
			$paypaluser->update_time = time();
			$paypaluser->uid = $uid;
			$paypaluser->puid = $puid;
			
			if(!$paypaluser->save()){
				$message = '';
	            foreach ($paypaluser->errors as $k => $anError){
					$message .= "Insert Paypal user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
				}
				exit (json_encode(array("code"=>"fail","message"=>$message)));
			}
		}
		
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}

	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的paypal账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/15		初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["ppid"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有paypal小老板id")));
		}
		$ppid = (int)$_REQUEST['ppid'];
		$rtn = SaasPaypalUser::deleteAll("ppid=$ppid");
		if  (empty($rtn))  {
			exit (json_encode(array("code"=>"fail","message"=>"删除失败，请刷新页面后重试")));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	
	
	public function actionSwitchOverwriteEbay(){
		if (!isset($_REQUEST["ppid"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有paypal账号小老板id")));
		}
		if (!isset($_REQUEST["overwrite"]) || !in_array($_REQUEST["overwrite"], ['Y','N'])){
			exit (json_encode(array("code"=>"fail","message"=>"设置类型有误")));
		}
		$ppid = (int)$_REQUEST['ppid'];
		$rtn = SaasPaypalUser::updateAll(['overwrite_ebay_consignee_address'=>$_REQUEST["overwrite"]],"ppid=$ppid");
		if  (empty($rtn))  {
			exit (json_encode(array("code"=>"fail","message"=>"更新失败，请刷新页面后重试")));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
}