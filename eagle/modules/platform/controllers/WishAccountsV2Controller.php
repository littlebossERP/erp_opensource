<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasWishUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\helpers\WishAccountsHelper;
use eagle\modules\platform\helpers\WishAccountsV2Helper;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ResultHelper;
use common\helpers\Helper_Curl;

class WishAccountsV2Controller extends \eagle\components\Controller{
	
    // dzt20191012通过小老板授权开关
    public static $goproxy = 0;
    
	public function actionTest(){
		echo "action is ok ";
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------
	 * wish授权前将PUID， 店铺名保存在session先
	 +----------------------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/26				初始化
	 +----------------------------------------------------------------------------------------------------------------------------
	 **/
	public function actionSetCreateInfo(){
		if (!empty($_REQUEST['store_name'])){
			$_SESSION['store_name'] = $_REQUEST['store_name'];
		}
		
		$_SESSION['puid'] = \Yii::$app->subdb->getCurrentPuid();
			
		exit(json_encode(['success'=>true,'message'=>'']));
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------
	 * wish授权的第一步
	 +----------------------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/26				初始化
	 +----------------------------------------------------------------------------------------------------------------------------
	 **/
	public function actionAuth1(){
		if (!empty($_REQUEST['site_id'])){
			$_SESSION['site_id'] = $_REQUEST['site_id'];
		}
			
		$_SESSION['puid'] = \Yii::$app->subdb->getCurrentPuid();
		
		if(empty(self::$goproxy)){
		    // TODO proxy dev account @XXX@
		    $url = "https://merchant.wish.com/v3/oauth/authorize?client_id=@XXX@";
		}else{
		    if (!empty($_SESSION['site_id'])){
		        $model = SaasWishUser::findOne(['site_id'=>$_SESSION['site_id']]);
		        unset($_SESSION['site_id']);
		        $_SESSION['store_name'] = $model->store_name;
		    }
		    
		    $url = "https://auth.littleboss.com/platform/wish-accounts-v2/open-auth1?account=".$_SESSION['store_name'];
		}
		
		$this->redirect($url);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish redirect uri , 通过 这个action 抓取出 wish  Authorization Code
	 * /platform/wish-accounts-v2/get-wish-authorization-code
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetWishAuthorizationCode(){
		
		if (!empty($_REQUEST['code']) ){
			if (!empty($_SESSION['site_id'])){
				$model = SaasWishUser::findOne(['site_id'=>$_SESSION['site_id']]);
				unset($_SESSION['site_id']);
			}else if (!empty($_SESSION['puid']) && !empty($_SESSION['store_name']) ){
				$model = SaasWishUser::findOne(['uid'=>$_SESSION['puid'] , 'store_name'=>$_SESSION['store_name']]);
				unset($_SESSION['puid']);
				unset($_SESSION['store_name']);
			}
			
			$wishReturn = WishAccountsV2Helper::getWishToken($_REQUEST['code']);
// 			$wishRT = json_decode($wishReturn,true);
			//20170814 验证access token 与当前的账号信息是否一致
			if (!empty($wishReturn['proxyResponse']['wishReturn']['data']['access_token'])){
				$tmpRT = WishAccountsV2Helper::getWishAccountInfo($wishReturn['proxyResponse']['wishReturn']['data']['access_token']);
				\Yii::info(" file=".__file__.' line='.__line__." result=".json_encode($tmpRT),"file");
				if (!empty($model->merchant_id) && !empty( $tmpRT['data']['merchant_id']) && $model->merchant_id != $tmpRT['data']['merchant_id']){
					return ['success'=>false, 'message'=>'授权失败：新授权的账号与当前账号不相符'];
				}
				if (isset($tmpRT['data']['merchant_id'])){
					$model->merchant_id = @$tmpRT['data']['merchant_id'];
				}
				
				if (isset($tmpRT['data']['merchant_username'])){
					$model->merchant_username = @$tmpRT['data']['merchant_username'];
				}
			}else{
				\Yii::info(" file=".__file__.' line='.__line__." no access token! result=".json_encode($wishReturn),"file");
			}
			$result = WishAccountsV2Helper::saveWishToken($model, $wishReturn);
			WishHelper::autoSyncFanbenInfo(); //添加同步队列信息
			//绑定账号时，将拉取站内信的app数据一并生成
			$rtn = MessageApiHelper::setSaasMsgAutosync($saasId, $model->site_id, $model->store_name, 'wish');
			return $this->render('successview',['title'=>'绑定成功']);
			
		}//end of if (!empty($_REQUEST['code']) && !empty($_REQUEST['client_id'])) code 和 client id 不能为空
		else {
			return $this->render('errorview',['title'=>'绑定失败']);
			
		}
	}//end of actionGetWishAuthorizationCode
	
	/**
	 +----------------------------------------------------------
	 * 增加绑定Wish账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lkh			2015/8/18			初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array("mode"=>"new"));
	}
	
	/**
	 +----------------------------------------------------------
	 * 查看或编辑Wish账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	 **/
	public function actionViewOrEdit() {
		$Wish_id = $_GET['wish_id'];
		$WishData = SaasWishUser::findOne($Wish_id);
		$WishData = $WishData->attributes;
		return $this->renderAjax('newOrEdit', array("mode"=>$_GET["mode"],"WishData"=>$WishData));
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的Wish账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2014/09/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["wish_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有Wish_id")));
		}
		list($ret,$message)=WishAccountsHelper::deleteWishAccount($_POST["wish_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		$site_id = $_POST["wish_id"];
		WishHelper::delSyncFanbenInfo($site_id);//解绑触发删除刊登商品信息
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	
	/**
	 * 设置Wish账号同步
	 * @author lzhl
	 */
	public function actionSetWishAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasWishUser::findOne(['site_id'=> $_POST['setusr']]);
			if ( null == $user ){
				exit (json_encode(array('success'=>false,'message'=>TranslateHelper::t('无该账号'))));
			}
			if ($_POST['setitem'] == 'is_active'){
				$uid = \Yii::$app->subdb->getCurrentPuid();
				$rt = PlatformAccountApi::resetSyncSetting('wish', $_POST['setusr'], $_POST['setval'], $uid);
				exit (json_encode(array('success'=>true,'message'=>TranslateHelper::t('设置成功'))));
			}
			else{
				exit (json_encode(array('success'=>false,'message'=>TranslateHelper::t('同步设置指定的属性非有效属性'))));
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 增加绑定Wish账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	 **/
	public function actionCreate() {
		list($ret,$message)=WishAccountsV2Helper::createWishAccount($_POST);
		if  ($ret===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}else{
			if (!empty($_REQUEST['store_name'])){
				$_SESSION['store_name'] = $_REQUEST['store_name'];
			}
			
			$_SESSION['puid'] = \Yii::$app->subdb->getCurrentPuid();
			
			$model = SaasWishUser::findOne(['store_name'=> $_REQUEST['store_name'] , 'uid'=> \Yii::$app->subdb->getCurrentPuid()]);
			$site_id = $model->site_id;
		}
		exit (json_encode(array("code"=>"ok","message"=>"" , 'site_id'=>$site_id)));
	
	}
	
	public function actionBindingGuide(){
		return $this->render('binding_guide');
	}//endofactionGuide
	
	/**
	 * 设置别名 页面显示
	 * hqw 2017/10/10
	 */
	public function actionSetaliasbox(){
		if (!empty($_REQUEST['site_id'] )){
			$account = SaasWishUser::find()->where(['site_id'=>$_REQUEST['site_id']])->asArray()->one();
			return $this->renderPartial('setalias', ['account'=>$account ]);
		}else{
			return TranslateHelper::t('找不到相关的账号信息');
		}
	}
	
	//保存wish别名
	public function actionSaveAlias(){
		if (!empty($_REQUEST['site_id'])){
			$account = SaasWishUser::find()->where(['site_id'=>$_REQUEST['site_id']])->one();
			if ($account->store_name_alias == $_REQUEST['store_name_alias']) 
				return json_encode(['success'=>false , 'message'=>TranslateHelper::t('别名已经是').$_REQUEST['store_name_alias']]);
			
			$account->store_name_alias = $_REQUEST['store_name_alias'];
			if ($account->save()){
				return json_encode(['success'=>true , 'message'=>'']);
			}else{
				$errors = $account->getErrors();
				$msg = "";
				foreach($errors as $row){
					$msg .= $row;
				}
				return json_encode(['success'=>false , 'message'=>$msg]);
			}
		}else{
			return json_encode(['success'=>false , 'message'=>TranslateHelper::t('找不到相关的账号信息')]);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取Lazada授权信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2019/10/11		初始化
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
	    
	    $uid = \Yii::$app->subdb->getCurrentPuid();
	    $model = SaasWishUser::findOne(['uid'=>$uid , 'store_name'=>$_POST['account']]);
        if(empty($model)){
            return ResultHelper::getResult(400, "", $_POST['account']."：wish卖家账号不存在。");
        }
	    
	    try {
	
	        
	        $ip = \eagle\helpers\IndexHelper::getClientIP();
	        $param = array('account'=>$_POST['account'], 'ip'=>$ip, 'host'=>\Yii::$app->request->hostInfo);
	        $rtn = Helper_Curl::post("https://auth.littleboss.com/platform/wish-accounts-v2/open-auth2", $param);
	
	        //             echo $rtn;
	        \Yii::info("wish actionAuth4:rtn:".$rtn, "file");
	        if(empty($rtn))
	            return ResultHelper::getResult(400, "", "获取数据失败。");
	
	        $result = json_decode($rtn, true);
	        if($result['code'] != 200)
	            return ResultHelper::getResult(400, "", "获取数据失败：".$result['message']);
	
	        $wishReturn = $result['data'];
	        if(empty($wishReturn['proxyResponse']['wishReturn']['data']['access_token'])){
	            return ResultHelper::getResult(400, "", "获取授权数据失败：".$result['data']['message']);
	        }
	
	        
	        
	        // 获取卖家账号基本信息，检查账号是否已经授权
	        $tmpRT = $wishReturn['proxyResponse']['wishReturn'];
            if (!empty($model->merchant_id) && !empty( $tmpRT['data']['merchant_id']) && $model->merchant_id != $tmpRT['data']['merchant_id']){
                return ResultHelper::getResult(400, "", '授权失败e2：新授权的账号与当前账号不相符');
            }
            
            if (isset($tmpRT['data']['AuthorizationCode'])){
                $model->code = @$tmpRT['data']['AuthorizationCode'];
            }
            
            if (isset($tmpRT['data']['merchant_id'])){
                $model->merchant_id = @$tmpRT['data']['merchant_id'];
            }
        
//             if (isset($tmpRT['data']['merchant_username'])){
//                 $model->merchant_username = @$tmpRT['data']['merchant_username'];
//             }
            if (isset($tmpRT['data']['merchant_user_id'])){
                $model->merchant_username = @$tmpRT['data']['merchant_user_id'];
            }
	        $result = WishAccountsV2Helper::saveWishToken($model, $wishReturn);
	        WishHelper::autoSyncFanbenInfo(); //添加同步队列信息
	        //绑定账号时，将拉取站内信的app数据一并生成
	        $rtn = MessageApiHelper::setSaasMsgAutosync($saasId, $model->site_id, $model->store_name, 'wish');
	        
	        return ResultHelper::getResult(200, "", "绑定成功");
	    }catch(\Exception $ex){
	        \Yii::error('file:'.$ex->getFile().'line:'.$ex->getLine()." ".$ex->getMessage(),"file");
	
	        return ResultHelper::getResult(400, "", '获取数据失败e。'.$ex->getMessage());
	    }
	
	}
	
	
	
	
}