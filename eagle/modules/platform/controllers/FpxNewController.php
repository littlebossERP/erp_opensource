<?php
namespace eagle\modules\platform\controllers;

use Yii;
use eagle\modules\util\helpers\LoginHelper;
use eagle\modules\carrier\models\SysCarrierAccount;
use common\api\carrierAPI\LB_4PXNewCarrierAPI;

/**
 * 递四方新系统授权，不能打4开头，用了F
 *
 */
class FpxNewController extends \eagle\components\Controller
{
	
    public function actionAuth()
    {
        // 回调没有自定义code的字段做验证，只能加session 来判断redirect回来的请求
		if (!empty($_REQUEST['4px_account_id']))
		{
			Yii::$app->session['4px_account_id'] = $_REQUEST['4px_account_id'];
		}else{
			return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号']);
		}
		
		$api = new LB_4PXNewCarrierAPI();
		$redirect_uri = \Yii::$app->request->hostInfo.'/platform/fpx-new/get-authorization-code';
		
		$url = $api->getAuthUrl($redirect_uri);
		
		$this->redirect($url);
    }

	/**
	 *
	 * 授权回调
	 */
	public function actionGetAuthorizationCode()
	{
		//取回session数据
		$account_id = Yii::$app->session['4px_account_id'];
		if( $account_id=='' ){
			return $this->render('errorview',['title'=>'授权失败', 'error'=>'找不到物流商账号！']);
		}
		$redirect_uri = \Yii::$app->request->hostInfo.'/platform/fpx-new/get-authorization-code';
		
		$api = new LB_4PXNewCarrierAPI();
		
		list($ret, $retData) = $api->getAccessToken($redirect_uri, $_REQUEST['code']);
		if (!empty($ret)){
			//保存信息
			$account = SysCarrierAccount::find()->where(['id' => $account_id])->one();
			$account->is_used = 1;
			$api_params = $account->api_params;
			$api_params['access_token'] = $retData['access_token'];
			$api_params['refresh_token'] = $retData['refresh_token'];
			$api_params['expires_in'] = $retData['expires_in'];
			$api_params['access_token_timeout'] = time() + round($retData['expires_in'] / 1000) - 60;// 提前60秒结束
			
			$account->api_params = $api_params;
			if ($account->save()) {
				return $this->render('//successview', ['title' => '授权成功']);
			}
		}else{
			return $this->render('//errorview',['title'=>'授权失败', 'error'=>'access_token接口返回失败']);
		}
	}
	
	/**
	 * 获取v2模拟登陆url
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq		2016/09/01				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionGetUrl()
	{
	    if (!empty($_POST['4px_account_id']))
	    {
	        if( strpos($_SERVER['HTTP_HOST'], 'v2.littleboss') === false && false)// 递四方不用 https callback直接在当前域名处理可以 
	        {
	            $url = LoginHelper::getMockLoginAjaxUrl(array("carrierid"=> $_POST['4px_account_id']));
	            return json_encode(['status'=>0, 'url'=>$url]);
	        }
	        return json_encode(['status'=>1, 'url'=>'']);
	    }
	    return json_encode(['status'=>1, 'url'=>'']);
	}
}
?>