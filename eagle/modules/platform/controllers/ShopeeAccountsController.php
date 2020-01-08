<?php
namespace eagle\modules\platform\controllers;

use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\platform\apihelpers\ShopeeAccountsApiHelper;
use eagle\modules\platform\helpers\ShopeeAccountsHelper;
use eagle\models\SaasShopeeUser;
use common\api\shopeeinterface\ShopeeInterface_Base;
use common\api\shopeeinterface\ShopeeInterface_Api;
use eagle\modules\util\helpers\ResultHelper;
use common\helpers\Helper_Curl;

class ShopeeAccountsController extends \eagle\components\Controller{
	
    // dzt20191015通过小老板授权开关
    public static $goproxy = 0;
    
	/**
	 +----------------------------------------------------------
	 * 显示增加shopee账号界面
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/25		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew(){
		return $this->renderAjax('newOrEdit', [
				"mode" => "new",
				'sites' => ShopeeAccountsApiHelper::getCountryCodeSiteMapping(), 
			]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存shopee账号信息
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/26		初始化
	 +----------------------------------------------------------
	 **/
	public function actionSave(){
		$ret = ShopeeAccountsHelper::saveShopeeAccount($_POST);
			//组合参数跳转到shoppe去
		/**
			if( $ret['success']===true ){
				$partner_id= $_POST['partner_id'];
				$secret_key= $_POST['secret_key'];
				$redirect= 'http://auth.littleboss.com/platform/platform/all-platform-account-binding';
				$token= hash_hmac("sha256",$secret_key.$redirect , $secret_key);
				$token= hash( "sha256",$secret_key.$redirect );
				$url= "https://partner.uat.shopeemobile.com/api/v1/shop/auth_partner?id={$partner_id}&token={$token}&redirect={$redirect}";
				$this->redirect($url);
			}
		**/
		return json_encode($ret);
	}
	
	/**
	 +----------------------------------------------------------
	 * 显示编辑shopee账号界面
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/25		初始化
	 +----------------------------------------------------------
	 **/
	public function actionEdit(){
		if(!empty($_GET['shopee_uid'])){ 
			$user = SaasShopeeUser::findOne(['shopee_uid' => $_GET['shopee_uid']]);
			if(!empty($user)){
				return $this->renderAjax('newOrEdit', [
						'mode' => 'edit',
						'shopeeUser' => $user,
						'sites' => ShopeeAccountsApiHelper::getCountryCodeSiteMapping(),
					]);
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 设置shopee同步状态
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/26		初始化
	 +----------------------------------------------------------
	 **/
	public function actionSetShopeeAccountSync(){
		$shopee_uid = trim($_POST['shopee_uid']);
    	$status = $_POST['status'];
    	$uid = \Yii::$app->user->id;
    	$ret = PlatformAccountApi::resetSyncSetting('shopee', $shopee_uid, $status, $uid);
    	return json_encode($ret);
	}
	
	/**
	 +----------------------------------------------------------
	 * 解绑shopee账号
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/26		初始化
	 +----------------------------------------------------------
	 **/
	public function actionUnbind(){
		$ret = ShopeeAccountsHelper::Unbind($_POST);
		return json_encode($ret);
	}
	
	
	/**
	 * 授权第一步组织请求url向速卖通 提交
	 */
	function actionAuth1() {
	    try{
	        $puid = \Yii::$app->user->identity->getParentUid();
	        
            if(empty(self::$goproxy)){
                //app自定义参数，会原样返回，从而知道对应的账号
                $state = 'littleboss_'.$puid;
                $redirect_uri = \Yii::$app->request->hostInfo.'/platform/shopee-accounts/auth2';
                // $redirect_uri = 'https://auth.littleboss.com/platform/shopee-accounts/auth2';
                $ApiAuth = new ShopeeInterface_Base();
                $url = $ApiAuth->getAuthUrl($state, $redirect_uri);
                
            }else{
                $url = "https://auth.littleboss.com/platform/shopee-accounts/open-auth1";
            }
            
//             echo $url;            
            $this->redirect($url);
	    }catch(\Exception $ex){
	        return $this->render('//errorview',['title'=>'授权失败Auth1 Exception','error'=>$ex->getMessage()]);
	    }
	}
	
	/**
	 * 授权第一步组织请求url向速卖通 提交
	 */
	function actionAuth2() {
	    try{
    	    $ApiAuth = new ShopeeInterface_Api();
    	    $ApiAuth->shop_id = intval($_GET['shop_id']);
    	    $shopInfo = $ApiAuth->GetShopInfo();
//     	    echo json_encode($shopInfo);
    	    $puid = \Yii::$app->user->identity->getParentUid();
    	    
    	    \Yii::info('actionAuth2 puid:'.$puid.',shopInfo:'.json_encode($shopInfo),"file");
    	    
    	    // {"request_id":"e6b4ee185d5a09c46d527c5d2d150346","msg":"partner and shop has no linked","error":"error_auth"}
    	    if(!empty($shopInfo['error'])){
    	        return $this->render('//errorview',['title'=>'授权失败','error'=>$shopInfo['msg']]);
    	    }
    	    
    	    // {"status":"NORMAL","item_limit":1000,"disable_make_offer":0,"videos":[],"country":"SG","shop_description":"My Store VTH","shop_id":205753,"request_id":"4kZsxONxdmcuCRHAeU5zUs","images":["https:\/\/cf.shopee.sg\/file\/3359822d0ab04a96cd60b4370430093a","https:\/\/cf.shopee.sg\/file\/13ad5d1da1f3c9c8190a0d6c3e520cae"],"shop_name":"Eunimart-sampleshop","enable_display_unitno":true}
    	    $data = ['store_name'=>$shopInfo['shop_name'], 'partner_id'=>$ApiAuth->partner_id, 
    	            'shop_id'=>$shopInfo['shop_id'], 'secret_key'=>$ApiAuth->secret_key, 'site'=>$shopInfo['country']];
    	    
    	    \Yii::info('actionAuth2 puid:'.$puid.',before saveShopeeAccount data:'.json_encode($data),"file");
    	    $ret = ShopeeAccountsHelper::saveShopeeAccount($data);
    	    \Yii::info('actionAuth2 puid:'.$puid.',saveShopeeAccount ret:'.json_encode($ret),"file");
    	    
    	    if(!$ret['success']){
    	        return $this->render('//errorview',['title'=>'授权失败2','error'=>$ret['msg']]);
    	    }
    	    
    	    return $this->render('//successview',['title'=>'授权成功','message'=>$shopInfo['shop_name']."绑定成功。若没填写店铺名，请点击编辑，填写并保存"]);
	    
	    }catch(\Exception $ex){
	        return $this->render('//errorview',['title'=>'授权失败Auth2 Exception','error'=>$ex->getMessage()]);
	    }
	    
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取shopee授权信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2019/10/15		初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetAuthInfoWindow() {
	    return $this->renderAjax('getAuthInfoWindow');
	}
	
	/**
	 * 从小老板获取授权信息，添加账号
	 */
	function actionAuth4(){
	
	    if(empty($_POST['account'])){
	        return ResultHelper::getResult(400, "", "请输入wish卖家账号邮箱。");
	    }
	     
	    $puid = \Yii::$app->subdb->getCurrentPuid();
	    try {
	
	        $ip = \eagle\helpers\IndexHelper::getClientIP();
	        $param = array('account'=>$_POST['account'], 'ip'=>$ip, 'host'=>\Yii::$app->request->hostInfo);
	        $rtn = Helper_Curl::post("https://auth.littleboss.com/platform/shopee-accounts/open-auth2", $param);
	
//             echo $rtn;
	        \Yii::info("shopee actionAuth4:rtn:".$rtn, "file");
	        if(empty($rtn))
	            return ResultHelper::getResult(400, "", "获取数据失败。");
	
	        $result = json_decode($rtn, true);
	        if($result['code'] != 200)
	            return ResultHelper::getResult(400, "", "获取数据失败：".$result['message']);
	
	        $shopInfo = $result['data'];
    	    // {"request_id":"e6b4ee185d5a09c46d527c5d2d150346","msg":"partner and shop has no linked","error":"error_auth"}
    	    if(!empty($shopInfo['error'])){
    	        return ResultHelper::getResult(200, "", "授权失败:".$shopInfo['msg']);
    	    }
    	    
    	    // {"status":"NORMAL","item_limit":1000,"disable_make_offer":0,"videos":[],"country":"SG","shop_description":"My Store VTH","shop_id":205753,"request_id":"4kZsxONxdmcuCRHAeU5zUs","images":["https:\/\/cf.shopee.sg\/file\/3359822d0ab04a96cd60b4370430093a","https:\/\/cf.shopee.sg\/file\/13ad5d1da1f3c9c8190a0d6c3e520cae"],"shop_name":"Eunimart-sampleshop","enable_display_unitno":true}
    	    $data = ['store_name'=>$shopInfo['shop_name'], 'partner_id'=>"@XXX@", 
    	            'shop_id'=>$shopInfo['shop_id'], 'secret_key'=>"@XXX@", 'site'=>$shopInfo['country']];
    	    
    	    \Yii::info('shopee actionAuth4 puid:'.$puid.',before saveShopeeAccount data:'.json_encode($data),"file");
    	    $ret = ShopeeAccountsHelper::saveShopeeAccount($data);
    	    \Yii::info('shopee actionAuth4 puid:'.$puid.',saveShopeeAccount ret:'.json_encode($ret),"file");
    	    
    	    if(!$ret['success']){
    	        return ResultHelper::getResult(400, "", "授权失败2：".$ret['msg']);
    	    } 
    	    
	        return ResultHelper::getResult(200, "", "绑定成功");
	    }catch(\Exception $ex){
	        \Yii::error('file:'.$ex->getFile().'line:'.$ex->getLine()." ".$ex->getMessage(),"file");
	
	        return ResultHelper::getResult(400, "", '获取数据失败e。'.$ex->getMessage());
	    }
	
	}
	
}



