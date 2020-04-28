<?php
namespace eagle\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use eagle\helpers\AliexpressApiPostHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Auth_V2;
use Qiniu\json_decode;
use eagle\modules\util\helpers\TranslateHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;

class AliexpressApiPostController extends \yii\web\Controller
{
	public $enableCsrfValidation = FALSE; 
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::className(),
				'only' => ['logout', 'signup'],
				'rules' => [
				],
			],
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'logout' => ['post'],
				],
			],
		];
	}
	
	/**
	 * 授权第二步通过code获取访问令牌和长时令牌
	 */
	function actionCodeToToken(){
		set_time_limit(0);
		 
		\Yii::info("AliexpressAccountsV2Controller: ".json_encode($_GET), "file");
		// TODO erp host
		$err_url = 'http://您的erp域名/platform/aliexpress-accounts-v2/auth-err?';
		//返回的code用于去长时令牌和访问令牌
		$code = empty($_GET['code']) ? '' : $_GET['code'];
		$state = empty($_GET['state']) ? '' : $_GET['state'];
		try{
			if(strpos($state, 'littleboss_') === false){
				//throw new \Exception('验证错误，请通过小老板后台进行授权使用！');
				$redirect_url = 'http://您的erp域名';
				\Yii::$app->getResponse()->redirect($redirect_url);
				return;
			}elseif(empty($code)){
				throw new \Exception('can not find Code !');
			}
			// 返回erp授权成功地址
			// TODO erp host
			$redirect_uri = 'http://您的erp域名/platform/aliexpress-accounts-v2/auth3';
			$ApiAuth = new AliexpressInterface_Auth_V2();
			//给用户分配的开发者账号
			$dev_account = $ApiAuth->getDevAccount();
	
			//使用code获取长时令牌和访问令牌
			$d = $ApiAuth->codeToToken($code, $redirect_uri);
			\Yii::info("AliexpressAccountsV2Controller: ".json_encode($d), "file");
			if(isset($d['access_token'])){
				$res = $ApiAuth->writeAliAccount($d['user_nick'], $d);
				if($res['success']){
					$expire_time = empty($d['expire_time']) ? 0 : substr($d['expire_time'], 0, 10);
					// TODO erp host
					$redirect_url = 'http://您的erp域名/platform/aliexpress-accounts-v2/auth-to-db?sellerloginid='.$d['user_nick'].'&state='.$state.'&expire_time='.$expire_time;
					\Yii::$app->getResponse()->redirect($redirect_url);
					
					//exit('<script type="text/javascript">alert('.json_encode(TranslateHelper::t('操作成功')).','.json_encode($d['user_nick'].TranslateHelper::t('绑定成功')).');window.close();</script>');
				}
				else{
					$redirect_url = $err_url.'err_code=101&msg='.$res['msg'];
					\Yii::$app->getResponse()->redirect($redirect_url);
				}
			}elseif(isset($d['error_msg'])){
				if($d['error_msg'] == 'application need purchase'){// 需要先购买服务才能绑定
					$redirect_url = $err_url.'err_code=102&msg=';
				}
				else{
					$redirect_url = $err_url.'err_code=101&msg='.urlencode($d['error_msg']);
				}
				\Yii::$app->getResponse()->redirect($redirect_url);
			}elseif(isset($d['error'])){
				throw new \Exception(print_r($d,1));
			}else{
				throw new \Exception('error:'.print_r($d,true));
			}
		}catch(\Exception $ex){
			header("content-type:text/html;charset=utf-8");
			var_dump($ex->getMessage());die;
		}
	}
	
	// 获取开展国内物流业务的物流公司
	public function actionQureywlbdomesticlogisticscompany(){
		return AliexpressApiPostHelper::AliexpressPost( __FUNCTION__);
	}
	
	// 获取线上发货标签(线上物流发货专用接口)
	public function actionGetprintinfo(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '6');
	}
	
	// 交易订单详情查询
	public function actionFindorderbyid(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 交易订单列表查询
	public function actionFindorderlistquery(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 查询物流追踪信息
	public function actionQuerytrackingresult(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '3');
	}
	
	// 查询物流订单信息
	public function actionQuerylogisticsorderdetail(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '4');
	}
	
	// 获取卖家地址
	public function actionGetlogisticsselleraddresses(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '5');
	}
	
	// 列出平台所支持的物流服务列表
	public function actionListlogisticsservice(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__);
	}
	
	// 声明发货接口
	public function actionSellershipmentfortop(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '7');
	}
	
	// 修改声明发货
	public function actionSellermodifiedshipmentfortop(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '7');
	}
	
	// 创建线上物流订单
	public function actionCreatewarehouseorder(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '8');
	}
	
	// 面单云打印
	public function actionGetpdfsbycloudprint(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '9');
	}
	
	// 获取单个产品信息
	public function actionFindaeproductbyid(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 商品列表查询接口
	public function actionFindproductinfolistquery(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 新增站内信/订单留言(NEW)2.0
	public function actionAddmsg(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 根据买家ID获取站内信对话ID
	public function actionQuerymsgchannelidbybuyerid(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '10');
	}
	
	// V2.0站内信/订单留言获取关系列表
	public function actionQuerymsgrelationlist(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// V2.0站内信/订单留言查询详情列表
	public function actionQuerymsgdetaillist(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 延长买家收货时间 
	public function actionExtendsbuyeracceptgoodstime(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 卖家对未评价的订单进行评价 
	public function actionSavesellerfeedback(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 批量获取线上单独的发货标签
	public function actionGetaloneprintinfos(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '6');
	}
	
	// 为已授权的用户开通消息服务
	public function actionTaobaotmcuserpermit(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '11');
	}
	
	// 消费多条消息
	public function actionTaobaotmcmessagesconsume(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '11');
	}
	
	// 确认消费消息的状态
	public function actionTaobaotmcmessagesconfirm(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 获取用户已开通消息
	public function actionTaobaotmcuserget(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '2');
	}
	
	// 取消用户的消息服务
	public function actionTaobaotmcusercancel(){
		return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '12');
	}
	
	// 自定义场景接口，交易订单列表查询
	public function actionCustomgetorder(){
	    return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '12');
	}
	
	// 自定义场景接口，上传图片库
	public function actionUploadimageforsdk(){
	    return AliexpressApiPostHelper::AliexpressPost(__FUNCTION__, '12');
	}
	
	
}
