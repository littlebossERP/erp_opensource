<?php

namespace eagle\modules\amazoncs\controllers;

use yii\web\Controller;
use eagle\modules\amazoncs\models\CsSellerEmailAddress;
use eagle\models\SaasAmazonUser;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\modules\amazoncs\helpers\AmazoncsHelper;
use eagle\modules\amazoncs\models\CsQuestTemplate;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrder;
use eagle\modules\amazoncs\models\CsMailQuestList;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\message\helpers\EdmHelper;
use eagle\modules\amazoncs\helpers\AmazonSesHelper;
use eagle\modules\amazon\apihelpers\AmazonProductAdvertisingHelper;
use eagle\modules\amazoncs\models\AmazonFeedbackInfo;
use yii\data\Sort;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\amazoncs\models\AmazonReviewInfo;
use eagle\models\LtCustomizedRecommendedGroup;
use eagle\modules\util\helpers\SaasEbayUserHelper;
use eagle\models\SaasEbayUser;
use eagle\modules\order\helpers\CdiscountOrderHelper;

/**
 * 
 * @author lzhl
 *
 */
class AmazoncsController extends \eagle\components\Controller
{
	
	public $enableCsrfValidation = false;
	
	
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    
    
    //绑定的email列表
    public function actionEmailList(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	
    	$AllAccounts = SaasAmazonUser::find()->where(['uid'=>$puid])->all();
    	$MerchantId_StoreName_Mapping = [];
    	foreach ($AllAccounts as $account){
    		$MerchantId_StoreName_Mapping[$account->merchant_id] = $account->store_name;
    	}
    	
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	
    	$csEmailInfos = CsSellerEmailAddress::find()->where(" status <> 'waiting_for_verification' ")->all();
    	
    	return $this->render('lsit_email_address',[
    			'csEmailInfos'=>$csEmailInfos,
    			'MerchantId_StoreName_Mapping'=>$MerchantId_StoreName_Mapping,
    			'MarketPlace_CountryCode_Mapping'=>$MarketPlace_CountryCode_Mapping,
    	]);
    }
    
    //打开新建绑定邮箱窗口
    public function actionCreateEmailBindWin(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	$AllAccounts = SaasAmazonUser::find()->where(['uid'=>$puid])->all();
    	$MerchantId_StoreName_Mapping = [];
    	$amazon_uids = [];
    	foreach ($AllAccounts as $account){
    		$amazon_uids[] = $account->amazon_uid;
    		$MerchantId_StoreName_Mapping[$account->merchant_id]['store_name'] = $account->store_name;
    	}
    	
    	if(!empty($amazon_uids))
    		$MarketPlaces = SaasAmazonUserMarketplace::find()->where(['amazon_uid'=>$amazon_uids])->asArray()->all();
    	else 
    		$MarketPlaces = [];
    	foreach ($MarketPlaces as $mp){
    		foreach ($AllAccounts as $account){
    			if($mp['amazon_uid']==$account->amazon_uid){
    				if( empty($MarketPlace_CountryCode_Mapping[$mp['marketplace_id']]) )
    					continue;
    				$country_code = $MarketPlace_CountryCode_Mapping[$mp['marketplace_id']];
    				$MerchantId_StoreName_Mapping[$account->merchant_id]['market_places'][$country_code] = $mp['marketplace_id'];
    			}
    		}
    	}
    	
    	$csEmailInfos = CsSellerEmailAddress::find()->where(" status <> 'waiting_for_verification' ")->all();
    	foreach ($csEmailInfos as $csEmail){
    		
    	}
    	
    	//print_r($MerchantId_StoreName_Mapping);exit();
    	return $this->renderAjax('_email_bind_win',[
    			'MarketPlace_CountryCode_Mapping'=>$MarketPlace_CountryCode_Mapping,
    			'MerchantId_StoreName_Mapping'=>$MerchantId_StoreName_Mapping,
    	]);
    	
    }
    
    public function actionSendVerifyeEmail(){
    	$result=['success'=>true,'message'=>''];
    	try{
    		if(empty($_REQUEST['email_address']) || trim($_REQUEST['email_address'])=='')
    			exit(json_encode(['success'=>false,'message'=>'email地址有误!']));
    		$email_address = trim($_REQUEST['email_address']);
    		$puid = \Yii::$app->subdb->getCurrentPuid();
    		$userFullName = \Yii::$app->user->identity->getFullName();
    		if(empty($puid))
    			exit(json_encode(['success'=>false,'message'=>'登录状态失效，请重新登录!']));
    	
    		$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address));
    		
    		$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::sendVerifyEmail($email_address);
    		if(!$rtn['success']){
    			$result['success'] = false;
    			$result['message'] = '发送验证邮件失败';
    			SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($rtn,true));
    		}else{
    			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
    		}
    	}catch(\Exception $e){
    		$result['success'] = false;
    		$result['message'] = '发送验证邮件失败';
    		if(isset($journal_id)){
    			SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($e->getMessage(),true));
    		}
    	}
    	exit(json_encode($result));
    }
    
    
    public function actionCheckVerifye(){
    	$result=['success'=>true,'message'=>''];
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	try{
    		if(empty($_REQUEST['email_address']) || trim($_REQUEST['email_address'])=='')
    			exit(json_encode(['success'=>false,'message'=>'email地址有误!']));
    		$email_address = trim($_REQUEST['email_address']);
    		
    		//获取aws账号已经授权的邮箱list
    		$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::listIdentities('default');
    		
    		if(!$rtn['success']){
    			$result['success'] = false;
    			$result['message'] = '获取验证结果失败，请联系客服。E001';
    			$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address,$rtn));
    		}else{
    			$Identities = $rtn['Identities'];
    			if(!in_array($email_address,$Identities)){
    				$result['success'] = false;
    				$result['message'] = '验证失败：邮箱未授权！';
    			}
    		}
    	}catch(\Exception $e){
    		$result['success'] = false;
    		$result['message'] = '获取验证结果失败，请联系客服。E002';
    		$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address,print_r($e->getMessage(),true)));
    	}
    	exit(json_encode($result));
    }
    
    /* 以下为通过思齐发送验证码进行邮箱验证的逻辑，使用AWS-SES后弃用
     * 
     */
    public function actionSendVerifyeEmailSiQi(){
    	$result=['success'=>true,'message'=>''];
    	try{
	    	if(empty($_REQUEST['email_address']) || trim($_REQUEST['email_address'])=='')
	    		exit(json_encode(['success'=>false,'message'=>'email地址有误!']));
	    	$email_address = trim($_REQUEST['email_address']);
	    	$puid = \Yii::$app->subdb->getCurrentPuid();
	    	$userFullName = \Yii::$app->user->identity->getFullName();
	    	if(empty($puid))
	    		exit(json_encode(['success'=>false,'message'=>'登录状态失效，请重新登录!']));
	    	
	    	$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address));
	    	
	    	//生成和记录验证码到redis
	    	$verification_code = rand(10000, 99999);
	    	$classification = 'Amazoncs_Verifye_Code';
	    	$existing_codes = RedisHelper::RedisGet($classification, 'user_'.$puid);
	    	if(!empty($existing_codes)){
	    		$existing_codes = json_decode($existing_codes,true);
	    	}else{
	    		$existing_codes = [];
	    	}
	    	$existing_codes[$email_address] = ['time'=>time(),'verification_code'=>$verification_code];//redis值为数组;time用来判断是否过期了
	    	RedisHelper::RedisSet($classification, 'user_'.$puid,json_encode($existing_codes));
	    	
	    	$fromEmail='service@littleboss.com';
	    	$fromName='service';
	    	$toEmail=$email_address;
	    	$Msubject='小老板-客服模块-邮箱绑定验证码';
	    	$Mbody='您好， '.$userFullName.'， 欢迎使用小老板客服功能！';
			$Mbody.='<br>您现申请此邮箱作为您客服功能使用的其中一个邮箱，  请您在小老板客服邮箱绑定界面输入以下验证码: '.$verification_code.',以验证邮箱的有效性。';
			$Mbody.='<br>谢谢!<br><br><br>小老板团队';
	    	$actName='AmazonCS-VerifyeEmail';
	    	$rtn = MailHelper::sendMailBySQ($fromEmail, $fromName, $toEmail, $Msubject, $Mbody);
	    	
	    	if(isset($rtn->Send2Result) && ($rtn->Send2Result=='Sent success' || $rtn->Send2Result=='Your email has submited successfully and will be send out soon.')){
	    		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
	    	}else{
	    		$result['success'] = false;
	    		$result['message'] = '发送验证邮件失败';
	    		SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($rtn,true));
	    	}
    	}catch(\Exception $e){
			$result['success'] = false;
	    	$result['message'] = '发送验证邮件失败';
	    	if(isset($journal_id)){
	    		SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($e->getMessage(),true));
	    	}
		}
    	exit(json_encode($result));
    }
    
    /*
     * error_code : 0：无异常/成功，  1：参数有误，  2：Exception，  3：未获授权但发送验证邮件成功， 4：发送验证邮件失败， 5：保存失败
     */
    public function actionSaveBindingEmail(){
    	$result=['success'=>true,'message'=>'','error_code'=>0];
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	if(empty($puid))
    		exit(json_encode(['success'=>false,'message'=>'登录状态失效，请重新登录!','error_code'=>'1']));
    	
    	$seller_id = empty($_REQUEST['merchant_id'])?'':trim($_REQUEST['merchant_id']);
    	if(empty($seller_id))
    		$seller_id = empty($_REQUEST['seller_id'])?'':trim($_REQUEST['seller_id']);
    	$site_id = empty($_REQUEST['market_places_id'])?'':trim($_REQUEST['market_places_id']);
    	if(empty($site_id))
    		$site_id = empty($_REQUEST['site_id'])?'':trim($_REQUEST['site_id']);
    	
    	if( empty($seller_id) || empty($site_id) )
    		exit(json_encode(['success'=>false,'message'=>'店铺选择不完整!','error_code'=>'1']));
    	
    	$other = CsSellerEmailAddress::find()->where(['seller_id'=>$seller_id,'site_id'=>$site_id])->count();
    	if(!empty($other))
    		exit(json_encode(['success'=>false,'message'=>'该店铺站点已经绑定了邮箱地址!','error_code'=>'1']));
    	
    	if(empty($_REQUEST['email_address']) || trim($_REQUEST['email_address'])=='')
    		exit(json_encode(['success'=>false,'message'=>'email地址有误!','error_code'=>'1']));
    	$email_address = trim($_REQUEST['email_address']);
    	
    	$verifyed = empty($_REQUEST['verifyed'])?false:$_REQUEST['verifyed'];//是否已经验证过
    	$go_to_save = false;//最后是否执行保存
    	
    	if($verifyed && $verifyed!=='false'){
    		$go_to_save = true;
    	}else{
	    	try{
	    		$check_rtn = AmazoncsHelper::checkEmailAddressVerifye($email_address);
	    		if($check_rtn['success'] && empty($check_rtn['error_code']) && empty($check_rtn['message'])){
	    			//验证成功，已经获得授权
	    			$go_to_save = true;
	    		}elseif(empty($check_rtn['success']) && !empty($check_rtn['error_code']) && $check_rtn['error_code']=='3' ){
	    			//验证成功，但未授权
	    			$result['success'] = false;
	    			$result['message'] = !empty($check_rtn['message'])?$check_rtn['message']:'验证失败：邮箱未授权！';
	    			$result['error_code'] = '3';
	    			//未授权的情况下，立即请求授权
	    			$send_rtn = AmazoncsHelper::sendAwsVerifyeEmailToUserEmailAddress($email_address);
	    			if($send_rtn['success'] && empty($send_rtn['error_code']) && empty($send_rtn['message'])){
	    				//发送成功
	    			}elseif(!empty($send_rtn['error_code'])){
	    				$result['error_code'] = '4';
	    				if($send_rtn['error_code']=='1')
	    					$result['message'] .= '<br>尝试向邮箱发送授权请求邮件失败：参数有误';
	    				if($send_rtn['error_code']=='2')
	    					$result['message'] .= '<br>尝试向邮箱发送授权请求邮件失败：接口调用出错';
	    				if($send_rtn['error_code']=='3')
	    					$result['message'] .= '<br>尝试向邮箱发送授权请求邮件失败：后台传输出错';
	    			}else{
	    				$result['error_code'] = '4';
	    				$result['message'] .= '<br>尝试向邮箱发送授权请求邮件失败：error_code丢失';
	    			}
	    		}else{
	    			$result['success'] = false;
	    			$result['message'] = '验证失败：过程出现问题导致验证失败';
	    			$result['error_code'] = '2';
	    		}
	    	}catch(\Exception $e){
	    		$result['success'] = false;
	    		$result['message'] = '验证授权过程出现问题，请联系客服。A001';
	    		$result['message'] = print_r($e->getMessage());
	    		$result['error_code'] = '2';
	    	}
    	}
    	
    	if($go_to_save){
	    	try{
	    		$csEmailAddress = new CsSellerEmailAddress();
	    		$csEmailAddress->email_address = $email_address;
	    		$csEmailAddress->platform = empty($_REQUEST['platform'])?'amazon':$_REQUEST['platform'];
	    		$csEmailAddress->seller_id = $seller_id;
	    		$csEmailAddress->site_id = $site_id;
	    		$csEmailAddress->create_time = TimeUtil::getNow();
	    		$csEmailAddress->status = 'active';
	    		
	    		if(!$csEmailAddress->save()){
	    			$result['success'] = false;
	    			$result['message'] = '保存邮箱地址失败S001';
	    			$result['error_code'] = '5';
	    			SysLogHelper::SysLog_Create("Amazoncs", __CLASS__,__FUNCTION__,"error",print_r($csEmailAddress->errors,true));
	    		}else{
	    		}
	    	}catch(\Exception $e){
	    		$result['success'] = false;
	    		$result['message'] = '保存邮箱地址失败S002';
	    		$result['error_code'] = '2';
	    		SysLogHelper::SysLog_Create("Amazoncs", __CLASS__,__FUNCTION__,"exception", print_r($e->getMessage(),true));
	    	}
    	}
    	exit(json_encode($result));
    }
    
    
    public function actionAjaxCheckVerifye(){
    	$result = ['success'=>true,'message'=>'','error_code'=>0];
    	
    	if(empty($_REQUEST['email_address']) || trim($_REQUEST['email_address'])=='')
    		exit(json_encode(['success'=>false,'message'=>'email地址有误!','error_code'=>'1']));
    	
    	$email_address = trim($_REQUEST['email_address']);
    	
    	$check_rtn = AmazoncsHelper::checkEmailAddressVerifye($email_address);
    	if($check_rtn['success'] && empty($check_rtn['error_code']) && empty($check_rtn['message'])){
    		exit(json_encode($result));
    	}else{
    		exit(json_encode($check_rtn));
    	}
    }
    
    public function actionUnbingEmailAddress(){
    	$id = empty($_REQUEST['id'])?'':(int)$_REQUEST['id'];
    	if(empty($id))
    		exit(json_encode(['success'=>false,'message'=>'未指定要删除的绑定']));
    	
    	$thisEmail = CsSellerEmailAddress::findOne($id);
    	if(empty($thisEmail)){
    		exit(json_encode(['success'=>false,'message'=>'无有效邮箱记录']));
    	}else{
    		if($thisEmail->delete()){
    			exit(json_encode(['success'=>true,'message'=>'']));
    		}else{
    			exit(json_encode(['success'=>false,'message'=>print_r($thisEmail->errors,true)]));
    		}
    	}
    }
    
    //模板列表
    public function actionTemplate(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$AllAccounts = SaasAmazonUser::find()->where(['uid'=>$puid])->all();
    	$MerchantId_StoreName_Mapping = [];
    	foreach ($AllAccounts as $account){
    		$MerchantId_StoreName_Mapping[$account->merchant_id] = $account->store_name;
    	}
    	 
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	
    	$data = AmazoncsHelper::getAmazoncsTemplateList($_REQUEST);
    	
    	
    	return $this->render('template',[
    			'models'=>$data['models'],
    			'pagination'=>$data['pagination'],
    			'MerchantId_StoreName_Mapping'=>$MerchantId_StoreName_Mapping,
    			'MarketPlace_CountryCode_Mapping'=>$MarketPlace_CountryCode_Mapping,
    	]);
    }
    
    
    public function actionCreateOrEditTemplate(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	if(empty($_REQUEST['act']))
    		$act = 'create';
    	else 
    		$act = $_REQUEST['act'];
    	
    	if($act=='edit'){
    		if(empty($_REQUEST['id']))
    			exit('请选择正确的模板，才能进行编辑');
    		else 
    			$model = CsQuestTemplate::findOne($_REQUEST['id']);
    	}else 
    		$model = new CsQuestTemplate();
    	
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	$seller_site_dropdown = [];
    	$default_selected_shop = '';
    	$amzUsers = SaasAmazonUser::find()->where(['uid'=>$puid])->asArray()->all();
    	$merchant_list = [];
    	foreach ($amzUsers as $amzUser){
    		$amz_user_marketplaces = SaasAmazonUserMarketplace::find()->where(['amazon_uid'=>$amzUser['amazon_uid']])->asArray()->All();
    		if(!empty($amz_user_marketplaces)){
    			$merchant_list[$amzUser['merchant_id']] = 1;
    			if(empty($default_selected_shop)) $default_selected_shop = $amzUser['merchant_id'];
    			foreach ($amz_user_marketplaces as $amz_user_marketplace){
    				$key = $amzUser['merchant_id'] .'-'.$amz_user_marketplace['marketplace_id'];
    				$label = empty($MarketPlace_CountryCode_Mapping[$amz_user_marketplace['marketplace_id']])?'':$MarketPlace_CountryCode_Mapping[$amz_user_marketplace['marketplace_id']];
    				$seller_site_dropdown[$key] = empty($label)?$amzUser['store_name'].'-'.$amz_user_marketplace['marketplace_id']:$amzUser['store_name'].'-'.$label;
    			}
    		}
    	}
    	
    	$merchant_list = empty($merchant_list)?[]:array_keys($merchant_list);
    	$recommended_groups = [];
    	if(!empty($merchant_list)){
	    	$amzGroups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])
	    		->andWhere(['platform'=>'amazon'])
	    		->andWhere(['seller_id'=>$merchant_list])
	    		->asArray()->All();
	    	if(!empty($amzGroups)){
	    		foreach ($amzGroups as $group){
	    			$recommended_groups[$group['seller_id']][$group['id']] = $group;
	    		}
	    	}
    	}
    	$default_recommended_group = empty($recommended_groups[$default_selected_shop])?[]:$recommended_groups[$default_selected_shop];
    	
    	return $this->renderAjax('_create_or_edit_template',[
    		'act'=>$act,
    		'model'=>$model,
    		'seller_site_dropdown'=>$seller_site_dropdown,
    		'merchant_list'=>$merchant_list,
    		'recommended_groups'=>$recommended_groups,
    		'default_recommended_group'=>$default_recommended_group,
    	]);
    }
    
    
    public function actionSaveTemplate(){
    	//exit(json_encode(['success'=>false,'message'=>'测试']));
    	if(empty($_REQUEST['act']) || ($_REQUEST['act']!=='edit' && $_REQUEST['act']!=='create') )
    		exit(json_encode(['success'=>false,'message'=>'无效的操作类型！']));
    	
    	if($_REQUEST['act']=='edit'){
    		if(empty($_REQUEST['id']))
    			exit(json_encode(['success'=>false,'message'=>'非法操作：没有传入模板id']));
    		$template = CsQuestTemplate::findOne($_REQUEST['id']);
    		if(empty($template))
    			exit(json_encode(['success'=>false,'message'=>'编辑失败：没有找到相应的模板信息']));
    	}else {
    		$template = new CsQuestTemplate();
    	}
    	
    	$err_msg = '';
    	$validation  =true;
    	
    	//验证店铺
    	if(empty($_REQUEST['seller_site'])){
    		$err_msg .= '<br>店铺不能为空；';
    		$validation  =false;
    	}else{
    		$seller_site_arr = explode('-',$_REQUEST['seller_site']);
    		Helper_Array::removeEmpty($seller_site_arr);
    		if(count($seller_site_arr)<2){
    			$err_msg .= '<br>店铺选择有误；';
    			$validation  =false;
    		}else{
    			$_REQUEST['seller_id'] = $seller_site_arr[0];
    			$_REQUEST['site_id'] = $seller_site_arr[1];
    		}
    	}
    	//验证模板名
    	if(empty($_REQUEST['name'])){
    		$err_msg .= '<br>模板名称不能为空；';
    		$validation  =false;
    	}
    	//验证模板标题
    	if(empty($_REQUEST['subject'])){
    		$err_msg .= '<br>模板标题不能为空；';
    		$validation  =false;
    	}
    	//验证模板内容
    	if(empty($_REQUEST['contents'])){
    		$err_msg .= '<br>模板内容不能为空；';
    		$validation  =false;
    	}
    	//验证send_after_order_created_days
    	if(!isset($_REQUEST['send_after_order_created_days'])){
    		$err_msg .= '<br>"订单产生多少天后"不能为空；';
    		$validation  =false;
    	}else{
    		if(!is_numeric($_REQUEST['send_after_order_created_days'])){
    			$err_msg .= '<br>"订单产生多少天后"不能为空 且 必须为数字；';
    			$validation  =false;
    		}
    		if((int)$_REQUEST['send_after_order_created_days']==0 || (int)$_REQUEST['send_after_order_created_days']<0){
    			$err_msg .= '<br>"订单产生多少天后"必须大于0';
    			$validation  =false;
    		}
    	}
    	//验证模板order_in_howmany_days
    	if(!isset($_REQUEST['order_in_howmany_days'])){
    		$err_msg .= '<br>"规则将套用在过去多少天内产生的订单"不能为空；';
    		$validation  =false;
    	}else{
    		if(!is_numeric($_REQUEST['order_in_howmany_days'])){
    			$err_msg .= '<br>"规则将套用在过去多少天内产生的订单"不能为空 且 必须为数字；';
    			$validation  =false;
    		}
    		if((int)$_REQUEST['order_in_howmany_days']==0 || (int)$_REQUEST['order_in_howmany_days']<0){
    			$err_msg .= '<br>"规则将套用在过去多少天内产生的订单"必须大于0';
    			$validation  =false;
    		}
    	}
    	/*
    	//验证send_one_pre_howmany_days
    	if(!isset($_REQUEST['send_one_pre_howmany_days'])){
    		$err_msg .= '<br>"同一个买家多少 天内只发送一封邮件"不能为空；';
    		$validation  =false;
    	}else{
    		if(!is_numeric($_REQUEST['send_one_pre_howmany_days'])){
    			$err_msg .= '<br>"同一个买家多少 天内只发送一封邮件"不能为空 且 必须为数字；';
    			$validation  =false;
    		}
    		if((int)$_REQUEST['send_one_pre_howmany_days']==0 || (int)$_REQUEST['send_one_pre_howmany_days']<0){
    			$err_msg .= '<br>"同一个买家多少 天内只发送一封邮件"必须大于0';
    			$validation  =false;
    		}
    	}
    	*/
    	//验证filter_order_item_type && filter_order_item_type组
    	if(!empty($_REQUEST['filter_order_item_type']) && $_REQUEST['filter_order_item_type']!=='non'){
    		if(empty($_REQUEST['order_item_keys'])){
    			$err_msg .= '<br>如果选择了商品匹配，则必须填入对应的商品编码，多个之间用";"隔开';
    			$validation  =false;
    		}
    	}
    	
    	if($_REQUEST['act']=='create'){
    		$existing = CsQuestTemplate::find()->where([
    			'platform'=>$_REQUEST['platform'],
    			'seller_id'=>$_REQUEST['seller_id'],
    			'site_id'=>$_REQUEST['site_id'],
    			'name'=>$_REQUEST['name']
    		])->all();
    		if(!empty($existing)){
    			$err_msg .= '<br>该店铺已经有同名的模板了，请修改模板名称;';
    			$validation  =false;
    		}
    	}
    	
    	if(!$validation){
    		exit(json_encode(['success'=>false,'message'=>$err_msg]));
    	}
    	
    	$rtn = AmazoncsHelper::saveTemplate($template, $_REQUEST);
    	exit(json_encode($rtn));
    }
    
    public function actionDelTemplate(){
    	$id = empty($_REQUEST['id'])?'':trim($_REQUEST['id']);
    	if(empty($id)){
    		exit(json_encode(['success'=>false,'message'=>'错误操作：无指定模板id']));
    	}
    	
    	$template = CsQuestTemplate::findOne($id);
    	if(empty($template)){
    		exit(json_encode(['success'=>false,'message'=>'删除出错：该模板已不存在，请刷新界面']));
    	}
    	$template->delete();
    	
    	exit(json_encode(['success'=>true,'message'=>'']));
    }
    
    public function actionPreviewTemplate(){
    	$template_data = [];
    	$tmplate_status = empty($_REQUEST['tmplate_status'])?'':$_REQUEST['tmplate_status'];
    	if($tmplate_status!=='editing' && $tmplate_status!=='saved')
    		exit('无效操作！');
    	
    	if(!empty($_REQUEST['id']) && $tmplate_status=='saved'){
    		$id = trim($_REQUEST['id']);
    		$template = CsQuestTemplate::findOne($id);
    		if(empty($template))
    			exit('找不到模板信息！');
    		$template_data['subject'] = $template->subject;
    		$template_data['contents'] = $template->contents;
    		$template_data['addi_info'] = $template->addi_info;
    	}else{
    		$template_data['subject'] = empty($_REQUEST['subject'])?'':$_REQUEST['subject'];
    		$template_data['contents'] = empty($_REQUEST['contents'])?'':$_REQUEST['contents'];
    		$template_data['addi_info'] = empty($_REQUEST['addi_info'])?'':$_REQUEST['addi_info'];
    	}
    	
    	return $this->renderAjax('_pre_view',[
    		'template_data'=>$template_data,
    	]);
    }
    
    public function actionSysTemplate(){
    	$sql = "select * from amazoncs_sys_template where 1 ";
    	$command = \Yii::$app->db->createCommand($sql);
    	$sysTemplates = $command->queryAll();
    	
    	return $this->renderAjax('_sys_templates',[
    		'sysTemplates'=>$sysTemplates,	
    	]);
    	
    }
    
    public function actionQuestList(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$platform = empty($_REQUEST['platform'])?'amazon':trim($_REQUEST['platform']);
    	if(!in_array($platform,array_keys(OdOrder::$orderSource))){
    		exit("无效的平台设置！");
    	}
    	if(!in_array($platform,['amazon'])){
    		exit("暂未支持该平台！");
    	}
    	
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-sent_time_location';
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	$sortConfig = new Sort(['attributes' => ['pending_send_time_location','sent_time_location']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$condition=[];
    	$condition['platform'] = $platform;
    	$condition['page'] = empty($_REQUEST['page'])?'':(int)$_REQUEST['page'];
    	$condition['per-page'] = empty($_REQUEST['per-page'])?'':(int)$_REQUEST['per-page'];
    	//发送状态筛选
    	if(!empty($_REQUEST['status'])){
    		if($_REQUEST['status']=='pending-send')
    			$condition['status'] = 'P';
    		if($_REQUEST['status']=='sent')
    			$condition['status'] = 'C';
    		if($_REQUEST['status']=='F')
    			$condition['status'] = ['F','CANCEL','CF'];//已发送包括发送成功和发送失败了的
    	}
    	
    	$QuestTemplateQuery = CsQuestTemplate::find()->where(['status'=>'active','platform'=>$platform]);
    	
    	//店铺筛选
    	$seller_id = '';
    	$site_id = '';
    	//if(!empty($_REQUEST['seller_stie'])){}
    	if(!empty($_REQUEST['seller_id'])){
    		//$QuestTemplateQuery->andWhere(['seller_id'=>$_REQUEST['seller_id']]);
    		$condition['seller_id'] = $_REQUEST['seller_id'];
    	}
    	if(!empty($_REQUEST['site_id'])){
    		//$QuestTemplateQuery->andWhere(['site_id'=>$_REQUEST['site_id']]);
    		$condition['site_id'] = $_REQUEST['site_id'];
    	}
    	
    	$quest_number = empty($_REQUEST['quest_number'])?'':trim($_REQUEST['quest_number']);
    	if(!empty($quest_number)){
    		$condition['quest_number'] = $quest_number;
    	}
    	if(!empty($_REQUEST['searchval']) && !empty($_REQUEST['keys'])){
    		$condition[$_REQUEST['keys']] = $_REQUEST['searchval'];
    	}
    	
    	//模板下拉数据
    	$QuestTemplates = $QuestTemplateQuery->all();
    	
    	$quest_templates = [];
    	$store_has_template = [];
    	foreach ($QuestTemplates as $quest){
    		$tmp_info['id'] = $quest->id;
    		$tmp_info['name'] = $quest->name;
    		$tmp_info['seller_id'] = $quest->seller_id;
    		$tmp_info['site_id'] = $quest->site_id;
    		$quest_templates[$quest->id] = $tmp_info;
    		$store_has_template[] = $quest->seller_id;
    	}
    	//店铺下拉数据
    	$AllAccounts = SaasAmazonUser::find()->where(['uid'=>$puid])->all();
    	$MerchantId_StoreName_Mapping = [];
    	foreach ($AllAccounts as $account){
    		if(in_array($account->merchant_id , $store_has_template))
    			$MerchantId_StoreName_Mapping[$account->merchant_id] = $account->store_name;
    	}
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	
    	$template_sellers = CsQuestTemplate::find()
    		->select(' `platform`, `seller_id`, `site_id` ,count(*) as `count` ')
    		->where(['status'=>'active'])
    		->groupBy(' `seller_id`,`site_id` ')
    		->asArray()->all();
    	$seller_site_list = [];
    	foreach ($template_sellers as $seller){
    		$seller_site_list[$seller['seller_id']][$seller['site_id']] = empty($MarketPlace_CountryCode_Mapping[$seller['site_id']])?$seller['site_id']:$MarketPlace_CountryCode_Mapping[$seller['site_id']];
    	}
    	
    	//获取quest list
    	$data = AmazoncsHelper::getMailQuestListByCondition($condition,$sort,$order);
    	
    	return $this->render('quest_list',[
    		'quest_templates'=>$quest_templates,
    		'MerchantId_StoreName_Mapping'=>$MerchantId_StoreName_Mapping,
    		'MarketPlace_CountryCode_Mapping'=>$MarketPlace_CountryCode_Mapping,
    		'seller_site_list'=>$seller_site_list,
    		'template_id_name_mapping'=>[],
    		'mail_quest_list'=>$data['list'],
    		'pagination'=>$data['pagination'],
    		'sort'=>$sortConfig,
    	]);
    }
    
    
    public function actionPreviewQuestMail(){
    	if(empty($_REQUEST['quest_id']))
    		exit('无效操作！');
    	$quest_id = $_REQUEST['quest_id'];
    	
    	$quest = CsMailQuestList::findOne($quest_id);
    	if(empty($quest))
    		exit('任务详情丢失！');
    	
    	return $this->renderAjax('_preview_quest',[
    		'quest'=>$quest,
    	]);
    }
    
    
    public function actionAjaxSaveQuestEditting(){
    	$_REQUEST['quest_id'] = empty($_REQUEST['quest_id'])?'':trim($_REQUEST['quest_id']);
    	$_REQUEST['subject'] = empty($_REQUEST['subject'])?'':trim($_REQUEST['subject']);
    	$_REQUEST['body'] = empty($_REQUEST['body'])?'':trim($_REQUEST['body']);
    	
    	if(empty($_REQUEST['quest_id']))
    		exit(json_encode(['success'=>false,'message'=>'任务编号丢失!','subject'=>'','body'=>'']));
    	if(empty($_REQUEST['subject']))
    		exit(json_encode(['success'=>false,'message'=>'邮件标题不能为空!','subject'=>'','body'=>'']));
    	if(empty($_REQUEST['body']))
    		exit(json_encode(['success'=>false,'message'=>'邮件内容不能为空!','subject'=>'','body'=>'']));
		
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,json_encode($_REQUEST)));
    	try{
	    	$quest = CsMailQuestList::findOne($_REQUEST['quest_id']);
	    	if(empty($quest))
	    		exit(json_encode(['success'=>false,'message'=>'邮件任务记录丢失!','subject'=>'','body'=>'']));
	    	
	    	$quest->subject = trim($_REQUEST['subject']);
	    	$quest->body = trim($_REQUEST['body']);
	    	$quest->update_time = TimeUtil::getNow();
	    	
	    	if(!$quest->save()){
	    		SysLogHelper::InvokeJrn_UpdateResult($journal_id,  print_r($quest->errors,true));
	    		exit(json_encode(['success'=>false,'message'=>'邮件任务记录更新失败!','subject'=>'','body'=>'']));
	    	}
	    	SysLogHelper::InvokeJrn_UpdateResult($journal_id, ['success'=>true,'message'=>'']);
	    	exit(json_encode(['success'=>true,'message'=>'','subject'=>$quest->subject,'body'=>$quest->body]));
    	}catch(\Exception $e){
    		$result['success'] = false;
    		$result['message'] = '保存邮箱地址失败E002';
    		if(isset($journal_id)){
    			SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($e->getMessage(),true));
    		}
    		exit(json_encode(['success'=>false,'message'=>'邮件任务记录更新时发生错误!','subject'=>'','body'=>'']));
    	}
    }
    
    
    public function actionCancelPendingSend(){
    	$quest_ids = empty($_REQUEST['quest_ids'])?'':trim($_REQUEST['quest_ids']);
    	$quest_ids = explode(';', $quest_ids);
    	Helper_Array::removeEmpty($quest_ids);
    	if(empty($quest_ids))
    		exit(json_encode(['success'=>false,'message'=>'操作失败：未选择具体任务编号']));
    	
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$update_time = TimeUtil::getNow();
    	
    	/*
    	$sql = "UPDATE `cs_mail_quest_list` SET
    	`status`='CANCEL',`update_time`='$update_time'
    	WHERE `status`='P' and `id` in (".implode(',', $quest_ids).")";
    	$command = \Yii::$app->subdb->createCommand ($sql);
    	$update= $command->execute();
    	*/
    	
    	///*
    	$update = CsMailQuestList::updateAll(
    				['status'=>'CANCEL','update_time'=>$update_time],
    				['and', ['id'=>$quest_ids],['status'=>'P']]
    			);
    	//*/
    	
    	//返还quota
    	EdmHelper::EdmQuotaChange($puid, $update,'+');
    	
    	if($update!==count($quest_ids)){
    		exit(json_encode(['success'=>false,'message'=>'操作结果：取消了'.$update.'/'.count($quest_ids).'条任务。剩余的部分任务可能已经被提交或者状态已经不再适合被取消']));
    	}else{
    		try{
	    		$sql = "UPDATE `edm_email_send_queue` SET 
	    				`status`=2,`error_message`='用户取消发送',`update_time`='$update_time' 
	    				WHERE `puid`=$puid and `status`=0 and `history_id` in (".implode(',', $quest_ids).")";
	    		$command = \Yii::$app->db_queue2->createCommand ($sql);
	    		$ret= $command->execute();
	    	}catch(\Exception $e) {
				
			}
    		exit(json_encode(['success'=>true,'message'=>'']));
    	}
    	
    }
    
    
    public function actionSendImmediately(){
    	$quest_ids = empty($_REQUEST['quest_ids'])?'':trim($_REQUEST['quest_ids']);
    	$quest_ids = explode(';', $quest_ids);
    	Helper_Array::removeEmpty($quest_ids);
    	if(empty($quest_ids))
    		exit(json_encode(['success'=>false,'message'=>'操作失败：未选择具体任务编号']));
    	
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	
    	//当任务状态为取消或失败的时候，quota应该已经返还过了，再次发送的时候要重新扣除
    	$had_returned_quotas = CsMailQuestList::find()->where(['id'=>$quest_ids,'status'=>['F','CANCEL']])->count();
    	$deduct_quota = EdmHelper::EdmQuotaChange($puid, $had_returned_quotas);
    	if(empty($deduct_quota['success'])){
    		exit(json_encode(['success'=>false,'message'=>'部分已取消的任务重新发送时，扣除邮件额度失败(邮件额度不足)，操作中止']));
    	}
    	
    	$update_time = TimeUtil::getNow();
    	$update = CsMailQuestList::updateAll(
    			['status'=>'P','priority'=>1,'update_time'=>$update_time],
    			['and',['id'=>$quest_ids], ['status'=>['P','F','CANCEL'] ] ]
    		);
    	if($update!==count($quest_ids)){
    		exit(json_encode(['success'=>false,'message'=>'操作结果：'.$update.'/'.count($quest_ids).'条任务成功。剩余的部分任务可能已经被提交或者状态已经不再适合发送']));
    	}else{
    		try{
    			$sql = "UPDATE `edm_email_send_queue` SET
	    				`update_time`='$update_time' , `priority`=1 ,`pending_send_time`='$update_time' 
    			    	WHERE `puid`=$puid and `status`=0 and `history_id` in (".implode(',', $quest_ids).")";
    			$command = \Yii::$app->db_queue2->createCommand ($sql);
    			$ret= $command->execute();
    		}catch(\Exception $e) {
    		
    		}
    		exit(json_encode(['success'=>true,'message'=>'']));
    	}
    }
    
    
    public function actionDelQuest(){
    	$quest_ids = empty($_REQUEST['quest_ids'])?'':trim($_REQUEST['quest_ids']);
    	$quest_ids = explode(';', $quest_ids);
    	Helper_Array::removeEmpty($quest_ids);
    	if(empty($quest_ids))
    		exit(json_encode(['success'=>false,'message'=>'操作失败：未选择具体任务编号']));
    	
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$update_time = TimeUtil::getNow();
    	$update = CsMailQuestList::deleteAll([ 'and',['id'=>$quest_ids],['status'=>['C','F','CANCEL']] ] );
    	//$update = CsMailQuestList::deleteAll(" id in (:id) and status in ('C','F','CANCEL') ",[':id'=>implode(',', $quest_ids)]);
    	if($update!==count($quest_ids)){
    		exit(json_encode(['success'=>false,'message'=>'操作结果：删除了'.$update.'/'.count($quest_ids).'条任务。剩余的部分任务可能已经被提交或者状态已经不再适合被删除']));
    	}else{
    		try{
	    		$sql = "UPDATE `edm_email_send_queue` SET
	    				`status`=2,`error_message`='用户取消发送',`update_time`='$update_time' 
	    		    	WHERE `puid`=$puid and `status`<>0 and `status`<>2 and `history_id` in (".implode(',', $quest_ids).")";
	    		$command = \Yii::$app->db_queue2->createCommand ($sql);
	    		$ret= $command->execute();
    		}catch(\Exception $e) {
				
			}
    		exit(json_encode(['success'=>true,'message'=>'']));
    	}
    	 
    }
    
    public function actionGetOrderInfo(){
    	$order_id = empty($_REQUEST['order'])?'':trim($_REQUEST['order']);
    	$platform = empty($_REQUEST['platform'])?'amazon':trim($_REQUEST['platform']);
    	$orderDetail = AmazoncsHelper::getOrderDetail($paramKey='order_source_order_id',$platform, $order_id);
    	if(empty($orderDetail))
    		exit('没有查询到订单内容');
    	return $this->renderAjax('_view_order_info', ['orderData'=>$orderDetail]);
    }
    
    public function actionGenerateQuest(){
    	$templateIds = empty($_REQUEST['template_ids'])?'':trim($_REQUEST['template_ids']);
    	if(empty($templateIds))
    		exit(json_encode(['success'=>false,'message'=>'没有指定任务模板']));
    	
    	$confirmed = (empty($_REQUEST['confirmed']) || $_REQUEST['confirmed']=='false')?false:true;
    	$templateIds = explode(';', $templateIds);
    	//check
    	if(!$confirmed){
    		$c = AmazoncsHelper::checkAllAmzCsTemplateReviewFeedbackOkForGenerate($templateIds);
    		if(!empty($c['result'])){
    			$checked = true;
    			$check_message = '';
    		}else {
    			$checked = false;
    			$check_message = empty($c['info'])?'':$c['info'];
    		}
    	}
    	
    	//checked or confirm 
    	if(!empty($checked) || $confirmed){
    		$generateResult = AmazoncsHelper::generateTemplateQuest($templateIds);
    		//exit(json_encode(['success'=>true,'message'=>'','generateResult'=>$generateResult]));
    		return $this->renderAjax('_generate_confirm', [
    				'step'=>'generated',
    				'generateResult'=>$generateResult
    		]);
    	}else{
    		return $this->renderAjax('_generate_confirm', [
    				'step'=>'confirm',
    				'message'=>$check_message,
    				'templateIds'=>is_array($templateIds)?implode(';', $templateIds):$templateIds,
    		]);
    	}
    }
    
    
    public function actionTemplateQuestGenerateLog(){
    	$tmplate_id = @$_REQUEST['tmplate_id'];
    	if(empty($tmplate_id)){
    		exit('未指定模板编号');
    	}
    	$template = CsQuestTemplate::findOne($tmplate_id);
    	if(empty($template)){
    		exit('模板信息丢失');
    	}
    	
    	return $this->renderAjax('_quest_generate_log',[
    			'template'=>$template,
    	]);
    }
    
    public function actionSwitchEmailAddressStatus(){
    	$id = empty($_REQUEST['id'])?'':trim($_REQUEST['id']);
    	$status = empty($_REQUEST['active_status'])?'':trim($_REQUEST['active_status']);
    	if(empty($id) || empty($status) || !in_array($status, ['active','unActive']))
    		exit(json_encode(['success'=>false,'message'=>'操作有误：参数有误']));
    	
    	try{
    		$mailAddress = CsSellerEmailAddress::findOne($id);
    		if(empty($mailAddress))
    			exit(json_encode(['success'=>false,'message'=>'邮箱信息记录有误']));
    		
    		$mailAddress->status = $status;
    		$mailAddress->update_time = TimeUtil::getNow();
    		if(!$mailAddress->save())
    			exit(json_encode(['success'=>false,'message'=>'邮箱信息记录更新失败E001']));
    		else 
    			exit(json_encode(['success'=>true,'message'=>'']));
    	}catch(\Exception $e) {
    		exit(json_encode(['success'=>false,'message'=>'操作出现错误E002']));
		}
    }
    
    // 介绍/帮助 页面
    public function actionHelps(){
    	return $this->render('helps',[
    	
    	]);
    }
    
    // ‘推荐商品’介绍 页面
    public function actionAboutRecommend(){
    	return $this->render('about_recommend',[]);
    }
    
    
    public function actionFeedbackList(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	
    	//店铺下拉数据
    	$AllAccounts = SaasAmazonUser::find()->where(['uid'=>$puid])->all();
    	$MerchantId_StoreName_Mapping = [];
    	foreach ($AllAccounts as $account){
    		$MerchantId_StoreName_Mapping[$account->merchant_id] = $account->store_name;
    	}
    	
    	$feedback_sellers = AmazonFeedbackInfo::find()
	    	->select(' `merchant_id` , `marketplace_id` , COUNT(*) AS `count` ')->where(1)
	    	->groupBy(' `merchant_id` , `marketplace_id` ')
	    	->asArray()->all();
    	$seller_site_list = [];
    	foreach ($feedback_sellers as $row){
    		$key = $row['merchant_id'].'-'.$row['marketplace_id'];
    		$name = empty($MerchantId_StoreName_Mapping[$row['merchant_id']])?$row['merchant_id']:$MerchantId_StoreName_Mapping[$row['merchant_id']];
    		$name .= '-'.(empty($MarketPlace_CountryCode_Mapping[$row['marketplace_id']])?$row['marketplace_id']:$MarketPlace_CountryCode_Mapping[$row['marketplace_id']]);
    		$seller_site_list[$key] = $name;
    	}
    	
    	$condition = [];
    	foreach ($_REQUEST as $key=>$val){
    		if($key=='seller_site' && !empty($val)){
    			$tmp_data = explode('-', $val);
    			$condition['merchant_id'] = empty($tmp_data[0])?'':$tmp_data[0];
    			$condition['marketplace_id'] = empty($tmp_data[1])?'':$tmp_data[1];
    		}
    		if($key=='source_order_id' && !empty($val))
    			$condition['order_source_order_id'] = trim($val);
    		if($key=='rating' && !empty($val))
    			$condition['rating'] = $val;
    		if($key=='rating_status')
    			$condition['rating_status'] = $val;
    	}
    	
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-feedback_id';
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	$sortConfig = new Sort(['attributes' => ['rating','rating_status','create_time']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$page_url = '/amazoncs/amazoncs/feedback-list';
    	$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
    	if(empty($last_page_size))
    		$last_page_size = 20;//默认显示值
    	if(!empty($_REQUEST['page'])){
    		if(empty($_REQUEST['per-page']))
    			$pageSize = 20;
    		else 
    			$pageSize = $_REQUEST['per-page'];
    	}else{
    		$pageSize = $last_page_size;
    	}
    	ConfigHelper::setPageLastOpenedSize($page_url,$pageSize);
    	
    	$page = empty($_REQUEST['page'])?1:$_REQUEST['page'];
    	
    	$data = AmazoncsHelper::getFeedbackList($condition, $sort, $order, $page, $pageSize);
    	
    	return $this->render('feedback',[
    			'feedback_list'=>$data['list'],
    			'pagination'=>$data['pagination'],
    			'MerchantId_StoreName_Mapping'=>$MerchantId_StoreName_Mapping,
    			'MarketPlace_CountryCode_Mapping'=>$MarketPlace_CountryCode_Mapping,
    			'seller_site_list'=>$seller_site_list,
    			'sort'=>$sortConfig,
    	]);
    }
    
    
    public function actionReviewList(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	 
    	$MarketPlace_CountryCode_Mapping = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
    	 
    	//店铺下拉数据
    	$AllAccounts = SaasAmazonUser::find()->where(['uid'=>$puid])->all();
    	$MerchantId_StoreName_Mapping = [];
    	foreach ($AllAccounts as $account){
    		$MerchantId_StoreName_Mapping[$account->merchant_id] = $account->store_name;
    	}
    	 
    	$review_sellers = AmazonReviewInfo::find()
    	->select(' `merchant_id` , `marketplace_id` , COUNT(*) AS `count` ')->where(1)
    	->groupBy(' `merchant_id` , `marketplace_id` ')
    	->asArray()->all();
    	$seller_site_list = [];
    	foreach ($review_sellers as $row){
    		$key = $row['merchant_id'].'-'.$row['marketplace_id'];
    		$name = empty($MerchantId_StoreName_Mapping[$row['merchant_id']])?$row['merchant_id']:$MerchantId_StoreName_Mapping[$row['merchant_id']];
    		$name .= '-'.(empty($MarketPlace_CountryCode_Mapping[$row['marketplace_id']])?$row['marketplace_id']:$MarketPlace_CountryCode_Mapping[$row['marketplace_id']]);
    		$seller_site_list[$key] = $name;
    	}
    	 
    	$condition = [];
    	foreach ($_REQUEST as $key=>$val){
    		if($key=='seller_site' && !empty($val)){
    			$tmp_data = explode('-', $val);
    			$condition['merchant_id'] = empty($tmp_data[0])?'':$tmp_data[0];
    			$condition['marketplace_id'] = empty($tmp_data[1])?'':$tmp_data[1];
    		}
    		if($key=='asin' && !empty($val))
    			$condition['asin'] = trim($val);
    		if($key=='rating' && !empty($val))
    			$condition['rating'] = $val;
    		if($key=='author' && !empty($val))
    			$condition['author'] = $val;
    	}
    	 
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-review_id';
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	$sortConfig = new Sort(['attributes' => ['rating','asin','create_time']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	 
    	$page_url = '/amazoncs/amazoncs/review-list';
    	$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
    	if(empty($last_page_size))
    		$last_page_size = 20;//默认显示值
    	if(!empty($_REQUEST['page'])){
    		if(empty($_REQUEST['per-page']))
    			$pageSize = 20;
    		else 
    			$pageSize = $_REQUEST['per-page'];
    	}else{
    		$pageSize = $last_page_size;
    	}
    	ConfigHelper::setPageLastOpenedSize($page_url,$pageSize);
    	 
    	$page = empty($_REQUEST['page'])?1:$_REQUEST['page'];
    	 
    	$data = AmazoncsHelper::getReviewList($condition, $sort, $order, $page, $pageSize);
    	 
    	return $this->render('review',[
    			'review_list'=>$data['list'],
    			'pagination'=>$data['pagination'],
    			'MerchantId_StoreName_Mapping'=>$MerchantId_StoreName_Mapping,
    			'MarketPlace_CountryCode_Mapping'=>$MarketPlace_CountryCode_Mapping,
    			'seller_site_list'=>$seller_site_list,
    			'sort'=>$sortConfig,
    			]);
    }
    
    
    public function actionTest(){
    	//$aa = MessageHelper::decryptBuyerLinkParam('MWIwMS0xMWYtMTM3NC0w');
    	//var_dump($aa);exit();
    	
    	$str = ['买家查看包裹追踪及商品推荐链接','商品链接','带图片的商品链接','联系卖家链接','review链接','feedback链接'];
    	echo json_encode($str);exit();
    	
    }
    
    public function actionTestAds(){
    	$marketplace_id = 'A1F83G8C2ARO7P';
    	//$Asin = 'B01H2T6L6S';
    	$Asin = ['B01EA55LZY'];
    	$Asin = 'B01EA54WAE,B01EHJP2EI,B01E9Z2WBG,B01E9Z2Y6O';
    	
    	$account_info=[
	    	'merchant_id'=>'A2LKIWII6Q50U',
	    	'marketplace_id'=>'A1F83G8C2ARO7P',
	    	'access_key_id'=>'AKIAJ4DYQHJUSEWLP33Q',
	    	'secret_access_key'=>'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',
    	];
    	
    	
    	//$rtn = AmazonProductAdvertisingHelper::getFullOffer($marketplace_id, $Asin);
    	
    	//$rtn = AmazonProductAdvertisingHelper::getItemAttributes($marketplace_id, $Asin);
    	//print_r($rtn);
    	//$rtn = \eagle\modules\amazon\apihelpers\AmazonApiHelper::getListingsInfosByPAS($account_info, $Asin, 'ShippingCharges,BrowseNodes,Images,ItemAttributes', 'B');
    	$rtn = \eagle\modules\amazon\apihelpers\AmazonApiHelper::getListingsInfosByPAS($account_info, $Asin, 'ShippingOptions', 'B');
    	 
    	echo "<br> <br> <br>";
    	echo json_encode($rtn);
    	
    	//$rtn = AmazonProductAdvertisingHelper::getItemImages($marketplace_id, $Asin);
    	//print_r($rtn);
    	return;
    	
    	$action = 'GetProductAttributesByAsin';
    	
    	
    	/*
    	 * The value you specified for ResponseGroup is invalid. Valid values include [ 
    	 * 		'Tags', 'Help', 'ListMinimum', 'VariationSummary', 'VariationMatrix', 'TransactionDetails', 'VariationMinimum', 
    	 * 		'VariationImages', 'PartBrandBinsSummary', 'CustomerFull', 'CartNewReleases', 'ItemIds', 'SalesRank', 'TagsSummary', 
    	 * 		'Fitments', 'Subjects', 'Medium', 'ListmaniaLists', 'PartBrowseNodeBinsSummary', 'TopSellers', 'Request', 
    	 * 		'HasPartCompatibility', 'PromotionDetails', 'ListFull', 'Small', 'Seller', 'OfferFull', 'Accessories', 'VehicleMakes', 
    	 * 		'MerchantItemAttributes', 'TaggedItems', 'VehicleParts', 'BrowseNodeInfo', 'ItemAttributes', 'PromotionalTag', 
    	 * 		'VehicleOptions', 'ListItems', 'Offers', 'TaggedGuides', 'NewReleases', 'VehiclePartFit', 'OfferSummary', 'VariationOffers', 
    	 * 		'CartSimilarities', 'Reviews', 'ShippingCharges', 'ShippingOptions', 'EditorialReview', 'CustomerInfo', 'PromotionSummary', 
    	 * 		'BrowseNodes', 'PartnerTransactionDetails', 'VehicleYears', 'SearchBins', 'VehicleTrims', 'Similarities', 'AlternateVersions', 
    	 * 		'SearchInside', 'CustomerReviews', 'SellerListing', 'OfferListings', 'Cart', 'TaggedListmaniaLists', 'VehicleModels', 
    	 * 		'ListInfo', 'Large', 'CustomerLists', 'Tracks', 'CartTopSellers', 'Images', 'Variations', 'RelatedItems','Collections' 
    	 *  ];
    	 */
    	/*
    	 * Your ResponseGroup parameter is invalid. Valid response groups for ItemLookup requests include [
    	 * 		'Request','ItemIds','Small','Medium','Large','Offers','OfferFull','OfferSummary','OfferListings','PromotionSummary',
    	 * 		'PromotionDetails','Variations','VariationImages','VariationMinimum','VariationSummary','TagsSummary','Tags',
    	 * 		'VariationMatrix','VariationOffers','ItemAttributes','MerchantItemAttributes','Tracks','Accessories','EditorialReview',
    	 * 		'SalesRank','BrowseNodes','Images','Similarities','Subjects','Reviews','ListmaniaLists','SearchInside','PromotionalTag',
    	 * 		'AlternateVersions','Collections','ShippingCharges','RelatedItems','ShippingOptions'
    	 *  ];
    	 */
    	$get_params = array();
    	
    	$config=[
    		'merchant_id'=>'A2LKIWII6Q50U',
    		'marketplace_id'=>'A1F83G8C2ARO7P',
    		'access_key_id'=>'AKIAJ4DYQHJUSEWLP33Q',
    		'secret_access_key'=>'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',
    	];
    	$get_params['config'] = json_encode($config);
    	$parms = [
	    	//'asin'=>'B01H2T6L6S,B000TASOEK',
	    	'asin'=>'B01LVXIM42,B01K1WP7Z4',
	    	//'response_group'=>'OfferFull',//'ItemAttributes',
	    	'response_group'=>'ItemAttributes',
	    	'operation'=>'ItemLookup',
    	];
    	$get_params['parms'] = json_encode($parms);
    	
    	$post_params = [];
    	$TIME_OUT=60;
    	$return_type='json';
    	
    	$url= 'http://198.11.178.150/amazon_proxy_server_liang/ApiEntry.php';
    	$url .= "?action=$action";
    	$rtn['success'] = true;  //跟proxy之间的网站是否ok
    	$rtn['message'] = '';
    	
    	foreach($get_params  as $key=>$value){
    		$url .= "&$key=".urlencode(trim($value));
    	}
    	
    	$handle = curl_init($url);
    	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
    	curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
    	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); //连接超时
    	
    	
    	if (count($post_params)>0){
    		curl_setopt($handle, CURLOPT_POST, true);
    		curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
    	}
    	//  output  header information
    	// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
    	
    	/* Get the HTML or whatever is linked in $url. */
    	$response = curl_exec($handle);
    	$curl_errno = curl_errno($handle);
    	$curl_error = curl_error($handle);
    	if ($curl_errno > 0) { // network error
    		$rtn['message']="cURL Error $curl_errno : $curl_error";
    		$rtn['success'] = false ;
    		$rtn['response'] = "";
    		curl_close($handle);
    		return $rtn;
    	}
    	$response = json_decode($response,true);
    	//var_dump($response);
    	echo json_encode($response);
    	exit();
    	/* Check for 404 (file not found). */
    	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    	//echo $httpCode.$response."\n";
    	if ($httpCode == '200' ){
    		if ($return_type == 'xml'){	$rtn['response'] = $response; }
    		else $rtn['response'] = json_decode($response , true);
    			
    		//check submit的请求，返回的response 的message比较特殊，是个数组！！！！！！ 需要先把数组转成字符串
    		if (isset($rtn['response']) and isset($rtn['response']["message"]) and is_array($rtn['response']["message"] )){
    			$rtn['response']["message"]=print_r($rtn['response']["message"],true);
    		}
    			
    		if ($rtn['response']==null){
    			// json_decode fails
    			$rtn['message'] = "content return from proxy is not in json format, content:".$response;
    			//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
    			//	   	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
    			$rtn['success'] = false ;
    		}
    	}else{ // network error
    		$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
    		//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
    		//	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
    		$rtn['success'] = false ;
    		$rtn['response'] = "";
    	}
    	
    	curl_close($handle);
    	exit(json_encode($rtn['response']));
    }
    
    private static function object_to_array($obj){
    	if(is_array($obj)){
    		return $obj;
    	}
    	if(is_object($obj)){
    		$_arr = get_object_vars($obj);
    	}else{
    		$_arr = $obj;
    	}
    	$arr = [];
    	foreach ($_arr as $key=>$val){
    		$val=(is_array($val)) || is_object($val)?self::object_to_array($val):$val;
    		$arr[$key] = $val;
    	}
    	return $arr;
    }
    
    public function actionTestProxy01(){
    	//$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::listIdentities('default');
    	//$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::sendVerifyEmail('102875908@qq.com');
    	$mail_data=[];
    	$mail_data['mail_from'] = 'akirametero@vip.qq.com';
    	$mail_data['mail_to'] = 'akirametero@vip.qq.com';
    	$mail_data['subject'] = 'test-mail';
    	$mail_data['body'] = 'this is a test mail for AWS SES.';
    	$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::sendEmailByAmazonSES($mail_data);
    	print_r($rtn);
    }
    
    public function actionTestHelper(){
    	$rtn = AmazoncsHelper::getAsinListByAccountSite(1, 'A2LKIWII6Q50U', 'DE');
    	print_r($rtn);
    }
    
    public function actionTestSync(){
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$merchant_id = 'A2LKIWII6Q50U';
    	$marketplace_id = 'A1F83G8C2ARO7P';
    	$rtn = \eagle\modules\amazon\apihelpers\AmazonApiHelper::UserManualSyncActiveListing($puid, $merchant_id, $marketplace_id);
    	exit(json_encode($rtn));
    }
    
    public function actionTestEbay(){
    	$start_time = '1501550662';
    	$end_time = '1504231756';
    	//$eu = SaasEbayUser::find()->where(['selleruserid'=>'game-paradise'])->one();
    	$api = new \common\api\ebayinterface\getmymessages();
    	$api->resetConfig(242);
    	$api->eBayAuthToken = 'AgAAAA**AQAAAA**aAAAAA**b7fQWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AHloCjDJOGqASdj6x9nY+seQ**Y4MDAA**AAMAAA**ncHJBmYUubNcuoBGcxQiexK61otMMg8jEI7HugW/nBZaNeISDWf/6fPbAV70aULvYZtyAA/hsi7J4Own6v3SCbkes6MddkoceYDCvKQ3sFn+eUdhmkMnucsZj6zHdvBdOfNg5lOIJ4iD1r934SfbHtxN5sX+L8s3d6IckKGpt32vcc1OWDJwpaBUoZcMidiAMmqSQCJJ1i1hChrA/2aFcXwc4cItUD+7F+YynNuSaVOl/cp2Ovy6/TKeqRmwDAjNwJnTof2EDfjhvRwRcH6EryFZqoojezhPqBCVfb52wlTjmh86fDlVZ9G5MukpTt0MrGrk5hdoU14eOLwwiHBb8nu8LgTUVT8kP4UwzLEur2Taz0q3ClrKPoWpAulGZOcb0O2ye1lmRpMWYApCJAYya93US6GhVLF0wiWggnczzHA/iv/Sb5k+PhddCONVKdWX36tcYloPT1kASqtYtLPT4DkDg8crVAcGC0sgGS8PMtBvE176jst7PPwobaIg7hs9wtK0dTabaRdAwsvbH9XW7DNb//IYq/b614w6PK8hmPsmHqjl99a9T+s/8cywAQCYK4Z/HtcgBlhFFNbd11IWWp85kar+Ewk4mWUFbV4/XAo+a9EBVCKOIBFrG2GDqMuAl5QYghYsHrOAiQn3NxJD0qaicCiTuyZjfdTHK8jEwTTIZqA6LDk6hp1wUdyN9+H7aA7P+Ny6fECYHafEKRP12dxxFDVR66bemtsq5zbHxnrIYg23ZqgXcd3HnmW2tUyX';
    	$api->StartTime = $start_time;
    	$api->EndTime = $end_time;
    	$api->DetailLevel = 'ReturnHeaders';
    	$api->EntriesPerPage = 100;
    	$api->PageNumber = 1;
    	$api->FolderID = 2;
    	$responseArr=$api->api();
    	
    	print_r($responseArr);
    	exit();
    }
    
    public function actionTestEbay2(){
    	$start_time = '1501550662';
    	$end_time = '1504231756';
    	$api = new \common\api\ebayinterface\getsellingmanageremaillog();
    	$api->resetConfig(242);
    	$api->eBayAuthToken = 'AgAAAA**AQAAAA**aAAAAA**b7fQWA**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AHloCjDJOGqASdj6x9nY+seQ**Y4MDAA**AAMAAA**ncHJBmYUubNcuoBGcxQiexK61otMMg8jEI7HugW/nBZaNeISDWf/6fPbAV70aULvYZtyAA/hsi7J4Own6v3SCbkes6MddkoceYDCvKQ3sFn+eUdhmkMnucsZj6zHdvBdOfNg5lOIJ4iD1r934SfbHtxN5sX+L8s3d6IckKGpt32vcc1OWDJwpaBUoZcMidiAMmqSQCJJ1i1hChrA/2aFcXwc4cItUD+7F+YynNuSaVOl/cp2Ovy6/TKeqRmwDAjNwJnTof2EDfjhvRwRcH6EryFZqoojezhPqBCVfb52wlTjmh86fDlVZ9G5MukpTt0MrGrk5hdoU14eOLwwiHBb8nu8LgTUVT8kP4UwzLEur2Taz0q3ClrKPoWpAulGZOcb0O2ye1lmRpMWYApCJAYya93US6GhVLF0wiWggnczzHA/iv/Sb5k+PhddCONVKdWX36tcYloPT1kASqtYtLPT4DkDg8crVAcGC0sgGS8PMtBvE176jst7PPwobaIg7hs9wtK0dTabaRdAwsvbH9XW7DNb//IYq/b614w6PK8hmPsmHqjl99a9T+s/8cywAQCYK4Z/HtcgBlhFFNbd11IWWp85kar+Ewk4mWUFbV4/XAo+a9EBVCKOIBFrG2GDqMuAl5QYghYsHrOAiQn3NxJD0qaicCiTuyZjfdTHK8jEwTTIZqA6LDk6hp1wUdyN9+H7aA7P+Ny6fECYHafEKRP12dxxFDVR66bemtsq5zbHxnrIYg23ZqgXcd3HnmW2tUyX';
    	$api->StartTime = $start_time;
    	$api->EndTime = $end_time;
    	$api->ItemID = '112438029720';
    	$api->TransactionID = '1702113589001';
    	
    	$responseArr=$api->api();
    	 
    	print_r($responseArr);
    	exit();
    }
	
	public function actionTestMail(){
		$fromEmail='service@littleboss.com';
		$fromName='service';
		$toEmail='akirametero@vip.qq.com';
		$Msubject='小老板-客服模块-邮箱绑定验证码';
		$Mbody='您好，  欢迎使用小老板客服功能！';
		$Mbody.='<br>您现申请此邮箱作为您客服功能使用的其中一个邮箱，  请您在小老板客服邮箱绑定界面输入以下验证码: 123456,以验证邮箱的有效性。';
		$Mbody.='<br>谢谢!<br><br><br>小老板团队';
		$actName='AmazonCS-VerifyeEmail';
		$rtn = MailHelper::sendMailBySQ($fromEmail, $fromName, $toEmail, $Msubject, $Mbody);
		exit(json_encode($rtn));
	}
	
	public function actionEbayMsg(){
		$api = new \common\api\ebayinterface\getmymessages();
		$api->resetConfig(242);
		$api->eBayAuthToken = 'AgAAAA**AQAAAA**aAAAAA**nEQOWg**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6ACl4CmAZWKpwmdj6x9nY+seQ**Y4MDAA**AAMAAA**BK1eyQvcTFah2u1r6uTnhiTnT15lpBq6qCG/9SUM7B149c5nrOft1hjll2pBOPaSCBCIXJvR2aVPk12QG6P+RTaLNE2E9NYKuvJFFSCmaObKzpQM/B6Fs7Sa7FkHW/hDzEQ1ouxIqTZG2KkoHtdP3910oJ6B1y6Do4VWaKETI+N+GOiSavzUiBRprH4qwsTSU+l7vhNIVqOs9gJYGdNLk6Oqa8kAiK/71P46iI/ZslaOnXbZp56hTjRrqfOV07p8ULDb1yzODCuK9HWZsIJDSgCbcP2siafB9RxkC/SQFvxItD32a/yIE6bRpfclddNRgKyNWIVe7IHPQID1JK4XV/Je17aCXIjLGJgXFRjxRjFfn91tyOW7qt6Q6YEX7RokRHhX2RO3l7SqeTI5Y7ohJ1mjJ+6WlGK/9zR+kGl3jF7DPP0ri3sXvYsw8hw5HErCm9uvMwm6x+UvEieMJpZbu7vqDx669itSX6rSb3umsQAI3n2qDC25RrObSte0fe3H/MRkWDkFJNLJNXFm4+666X4sJFqkWlqY2NuEtMWje/obj3+2pYdeLl0VG8uRXYM0XYWZAsN9+MnptO1QinXO11tsr4keCDSVaBXvZlR5ot8Iw8U3GZ/jLou09PZAwAndSYSRU7uFSzbLrtgjcCcGlJhsf59t+Kjd0kPBJKxN8eTfbVw0ubJp5LeS8HAi9+cqLE2Dxtp3b12bzHyP8qDLSjhTuMLsdjSHJd/qFMob9GsnryDXOFlNhy6eKj8ldAgl';
		$api->StartTime = 1510156980;//11-9
		$api->EndTime = 1510848180;//11-17
		$api->DetailLevel = 'ReturnHeaders';
		$api->EntriesPerPage = 50;
		$api->PageNumber = 1;
		$responseArr=$api->api();
		print_r($responseArr);exit();
	}
	
	public function actionEbayD(){
		$api = new \common\api\ebayinterface\getmymessages;
		$api->resetConfig(242);
		$api->eBayAuthToken = 'AgAAAA**AQAAAA**aAAAAA**nEQOWg**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6ACl4CmAZWKpwmdj6x9nY+seQ**Y4MDAA**AAMAAA**BK1eyQvcTFah2u1r6uTnhiTnT15lpBq6qCG/9SUM7B149c5nrOft1hjll2pBOPaSCBCIXJvR2aVPk12QG6P+RTaLNE2E9NYKuvJFFSCmaObKzpQM/B6Fs7Sa7FkHW/hDzEQ1ouxIqTZG2KkoHtdP3910oJ6B1y6Do4VWaKETI+N+GOiSavzUiBRprH4qwsTSU+l7vhNIVqOs9gJYGdNLk6Oqa8kAiK/71P46iI/ZslaOnXbZp56hTjRrqfOV07p8ULDb1yzODCuK9HWZsIJDSgCbcP2siafB9RxkC/SQFvxItD32a/yIE6bRpfclddNRgKyNWIVe7IHPQID1JK4XV/Je17aCXIjLGJgXFRjxRjFfn91tyOW7qt6Q6YEX7RokRHhX2RO3l7SqeTI5Y7ohJ1mjJ+6WlGK/9zR+kGl3jF7DPP0ri3sXvYsw8hw5HErCm9uvMwm6x+UvEieMJpZbu7vqDx669itSX6rSb3umsQAI3n2qDC25RrObSte0fe3H/MRkWDkFJNLJNXFm4+666X4sJFqkWlqY2NuEtMWje/obj3+2pYdeLl0VG8uRXYM0XYWZAsN9+MnptO1QinXO11tsr4keCDSVaBXvZlR5ot8Iw8U3GZ/jLou09PZAwAndSYSRU7uFSzbLrtgjcCcGlJhsf59t+Kjd0kPBJKxN8eTfbVw0ubJp5LeS8HAi9+cqLE2Dxtp3b12bzHyP8qDLSjhTuMLsdjSHJd/qFMob9GsnryDXOFlNhy6eKj8ldAgl';
		$api->DetailLevel = 'ReturnMessages';
		$api->FolderID=1;
		$api->MessageID = '92997600295';
		$responseArr = $api->api();
		print_r($responseArr);exit();
	}
	
	public function actionEbayR(){
		//\common\api\ebayinterface\getfeedback::cronRequest($eu);
		$api = new \common\api\ebayinterface\getfeedback;
		$api->resetConfig(242);
		$api->eBayAuthToken = 'AgAAAA**AQAAAA**aAAAAA**TIt5WQ**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AClIugD5WGpQidj6x9nY+seQ**Y4MDAA**AAMAAA**WPb91zQ+17n2CtHINZlXqILre7v0hMaENH2yCrrF5Hv6Hgwg+thC19H+yJfwwq9H4tKfJoC7Ccppp+4ZEZQRUQ5v9B3P3iVLWavGH1BuYFedbVdd9Rla5o2o3FGJl88gJmcW4SfmZdCZvwcesxUjcxpc+C8+WlGXQvbevYHIqxrPDGjeJRCDBSjbjdeyMWVzQiGE/BU7qaTdIe/6PWj7IjVPsHLAeN2Xi+2OL8WMAYXRxE884dB1+vtw7zGjmE53SZ0H73ENZBeAJMB8au2H2lz3hqxwu+ePnwG0yInWYtkd7teyCsGTGnoxJmPPqIXToMkUWzsshUwPWKw6bqXF8g+CZOBOjUiB58mTPMkrdJdEAFYQ2pUoPj36y2bV0XjFn8XMEQTMjwGsY/CW3w1+dJZ5Ruabt2qqz6th4r1RafBX06wi+tMilH8f9ndgctl2KeEgDyWzs8da/r8cKcux7tU6C/EerUKm2QWRqiJCCJHA5zhnoZwwobX8XJZdmei/hpX4vRpc4k7xHOnMhKK1q5YlS3Chj8LlGpaoE+uW6J2znMIOeETSuswHLrc5B0VXz7RXzixecW1/zt4ISOSwlaY3tP1OjrbM69yQ03gadJDU3rESkbH72j1s+5sqNz16TSlBSjru2RcIkBFeDd2G4t89OqY0bY6+hOlP0DSvp1bl4WM25JQhejYnPPZbzPPYB5ovGrSw1E9zi5bv5hwojMxRd9lodjnT2Px0g5j+Qt9MWGAcMywioocqwiYYldGI';
		$r=$api->api(null ,null,null,null);
		$selleruserid='zhongming0722';
		print_r(json_encode($r));
// 		if (!$api->responseIsFailure()){
// 			$api->save($r, $selleruserid);
// 			echo $selleruserid.' Get Feedback Success.<br>' ;
// 		}
// 		echo  $selleruserid.' Get Feedback Failure.<br>' ;
		exit();
		
	}
	
	public function actionTestC(){
		$new_start_time = '2017-11-30T00:00:00';
		$new_end_time='2017-12-13T00:00:00';
		$status = array("All");
		$token_key= '513ebe081b1a4101ad7ae908ffd9617b';
		$responeList = CdiscountOrderHelper::getOrderClaimList($token_key, '', $new_start_time, $new_end_time, $status);
	}
}
