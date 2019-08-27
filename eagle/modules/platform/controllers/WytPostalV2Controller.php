<?php
namespace eagle\modules\platform\controllers;

use Yii;
use eagle\modules\carrier\models\SysCarrierAccount;

class WytPostalV2Controller extends \eagle\components\Controller
{
	
    public function actionAuth()
    {
		if (!empty($_REQUEST['wyt_account_id']))
		{
			Yii::$app->session['carrierid'] = $_REQUEST['wyt_account_id'];
		}else{
			return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号']);
		}
		
		//万邑通后台地址复制过来
		// TODO carrier dev account @XXX@
		// 要到万邑通开发者后台设置 redirect_uri 为https://您的erp网址/platform/wyt-postal-v2/get-wyt-postal-authorization-code
		$tempu = parse_url(\Yii::$app->request->hostInfo);
		$host = $tempu['host'];
		$url= "http://openapi.winit.com.cn/openapi/oauth2/authorize?response_type=token&client_id=@XXX@&redirect_uri=https://{$host}/platform/wyt-postal-v2/get-wyt-postal-authorization-code&scope=OSWH ISP";
		
		$this->redirect($url);
    }


	public function actionGetUrl()
	{
		if (!empty($_POST['wyt_account_id']))
		{
			return json_encode(['status'=>1, 'url'=>'']);
		}
		return json_encode(['status'=>1, 'url'=>'']);
	}


	/**
	 *
	 * 授权回调
	 */
	public function actionGetWytPostalAuthorizationCode()
	{
		//取回session数据
		$wyt_account_id = Yii::$app->session['carrierid'];
		if( $wyt_account_id=='' ){
			return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号！']);
		}
		if ( !empty($_REQUEST['access_token']) ){
			//保存信息
			$account = SysCarrierAccount::find()->where(['id' => $wyt_account_id])->one();
			$account->is_used = 1;
			$api_params = $account->api_params;
			$api_params['token'] = $_REQUEST['access_token'];
			$account->api_params = $api_params;
			if ($account->save()) {
				return $this->render('successview', ['title' => '授权成功,token:'.$_REQUEST['access_token']]);
			}
		}else{
			return $this->render('errorview',['title'=>'授权失败', 'error'=>'access_token接口返回失败']);
		}
	}
}
?>