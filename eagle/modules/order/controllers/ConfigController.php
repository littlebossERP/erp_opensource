<?php

namespace eagle\modules\order\controllers;

use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\assets\PublicAsset;
use eagle\models\SaasCdiscountUser;
use eagle\models\SaasAmazonUser;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
class ConfigController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
    public function actionSet()
    {
    	AppTrackerApiHelper::actionLog("Oms-erp", "/config/set");
    	if (\Yii::$app->request->isPost){
    		//设置sku是否存在的订单检测
    		if (isset($_POST['check_sku'])){
    			ConfigHelper::setConfig('order/check_sku',$_POST['check_sku']);
    		}
    		//设置库存是否充足的订单检测
    		if (isset($_POST['check_stock'])){
    			ConfigHelper::setConfig('order/check_stock',$_POST['check_stock']);
    		}
    		//设置paypal符合度的订单检测
    		if (isset($_POST['check_paypal'])){
    			ConfigHelper::setConfig('order/check_paypal',$_POST['check_paypal']);
    		}
    		//设置物流检测的订单检测
    		if (isset($_POST['check_wuliu'])){
    			ConfigHelper::setConfig('order/check_wuliu',$_POST['check_wuliu']);
    		}
    		//订单item转商品库
    		if (isset($_POST['sku_toproduct'])){
    			ConfigHelper::setConfig('order/sku_toproduct',$_POST['sku_toproduct']);
    		}
    		
    		echo '<script>alert("操作已完成");</script>';
    	}
    	//订单数量统计
    	$counter[OdOrder::STATUS_NOPAY]=OdOrder::find()->where('order_source = "ebay" and order_status = '.OdOrder::STATUS_NOPAY)->count();
    	$counter[OdOrder::STATUS_PAY]=OdOrder::find()->where('order_source = "ebay" and order_status = '.OdOrder::STATUS_PAY)->count();
    	$counter[OdOrder::STATUS_WAITSEND]=OdOrder::find()->where('order_source = "ebay" and order_status = '.OdOrder::STATUS_WAITSEND)->count();
    	$counter['all']=OdOrder::find()->where('order_source = "ebay"')->count();
    	$counter['guaqi']=OdOrder::find()->where('order_source = "ebay" and is_manual_order = 1')->count();
    	 
    	$counter[OdOrder::EXCEP_WAITSEND]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_WAITSEND)->count();
    	$counter[OdOrder::EXCEP_HASNOSHIPMETHOD]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_HASNOSHIPMETHOD)->count();
    	$counter[OdOrder::EXCEP_PAYPALWRONG]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_PAYPALWRONG)->count();
    	$counter[OdOrder::EXCEP_SKUNOTMATCH]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_SKUNOTMATCH)->count();
    	$counter[OdOrder::EXCEP_NOSTOCK]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_NOSTOCK)->count();
    	$counter[OdOrder::EXCEP_WAITMERGE]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_WAITMERGE)->count();
        return $this->render('set',['counter'=>$counter]);
    }

    public function actionAddInvoiceInfo(){
    	$uid = \Yii::$app->user->id;
    	if(empty($uid))
    		exit ("请先登录");
    	if(empty($_REQUEST['act']))
    		$act='add';
    	return $this->renderAjax('_invoice_info_set',[
    			'act'=>$act,
    			'infos'=>[],	
    		]);
    }
    
    public function actionAddOrViewInvoiceInfo(){
    	$uid = \Yii::$app->user->id;
    	if(empty($uid))
    		exit ("请先登录");
    	if(empty($_REQUEST['act']))
    		$act='add';
    	else 
    		$act=$_REQUEST['act'];
    	$id = (int)$_REQUEST['id'];
    	if(empty($id) && $act!=='add')
    		exit("没有指定发票信息");
    	
    	$query = "SELECT * FROM `od_seller_invoice_info` WHERE `puid`=$uid and `id`=$id";
    	$command = \Yii::$app->db->createCommand($query);
    	$record = $command->queryOne();
    	
    	$invoiceInfos = OrderHelper::getSellerInvoiceInfos($uid);
    	$canChoseStores = [];//编辑or新建时可选店铺
    	$stores=[];//已有对应发票卖家信息的店铺arr
    	//获取，组织所有已有对应发票卖家信息的店铺arr：
    	foreach ($invoiceInfos as $invoiceInfo){
    		if(!empty($invoiceInfo['stores'])){
    			foreach ($invoiceInfo['stores'] as $platform=>$siteArr){
    				foreach ($siteArr as $site=>$storeArr){
    					if(empty($stores[$platform][$site]))
    						$stores[$platform][$site] = $storeArr;
    					else
    						$stores[$platform][$site] =array_merge($stores[$platform][$site], $storeArr);
    					
    					if(is_array($stores[$platform][$site])){
    						$stores[$platform][$site] = array_unique($stores[$platform][$site]);
    					}
    				}
    			}
    		}
    	}
		//组织CD可选店铺
    	$cdAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->asArray()->all();
    	foreach ($cdAccounts as $cdAccount){
    		if(empty($stores['cdiscount']['FR'])){
    			$canChoseStores['cdiscount']['FR'][]=$cdAccount['username'];
    			continue;
    		}
    		if(!in_array($cdAccount['username'], $stores['cdiscount']['FR'])){
    			$canChoseStores['cdiscount']['FR'][]=$cdAccount['username'];
    		}
    	}
    	
    	//组织amz可选店铺
    	$amzAccounts = SaasAmazonUser::find()->where(['uid'=>$uid])->asArray()->all();
    	foreach ($amzAccounts as $amzAccount){
    		//mk //@todo
    		$marketPlace = AmazonApiHelper::getMarketPlaceCountryCode($amzAccount['amazon_uid']);
    		foreach ($marketPlace as $mk){
    			if(empty($stores['amazon'][$mk])){
    				$canChoseStores['amazon'][$mk][] = $amzAccount['merchant_id'];
    				continue;
    			}
    			if(!in_array($amzAccount['merchant_id'], $stores['amazon'][$mk])){
    				$canChoseStores['amazon'][$mk][]=$amzAccount['merchant_id'];
    			}
    		}
    	}
    	//@todo 其他平台后续添加
    	
    	return $this->renderAjax('_invoice_info_set',[
    			'act'=>$act,
    			'infos'=>$record,
    			'canChoseStores'=>$canChoseStores,
    			]);
    }
    
    public function actionSaveInvoiceInfo(){
    	$uid = \Yii::$app->user->id;
    	if(empty($uid))
    		return json_encode(['success'=>false,'message'=>'请先登录']);
    	
    	$rtn['success']=true;
    	$rtn['message']="";
    	//$info=$_GET;
    	$_GET['company'] = trim($_GET['company']);
    	if(empty($_GET['company'])){
    		$rtn['success']=false;
    		$rtn['message'].='公司名必须填写;';
    	}else 
	    	$info['company'] = $_GET['company'];
    	
    	if(empty($_GET['address'])){
    		$rtn['success']=false;
    		$rtn['message'].='地址必须填写;';
    	}else
    		$info['address'] = $_GET['address'];
    	
    	if(empty($_GET['stores'])){
    		$rtn['success']=false;
    		$rtn['message'].='必须选择至少一个店铺';
    	}else
    		$info['stores'] = $_GET['stores'];
    	
    	if($rtn['success']==false)
    		return json_encode($rtn);
    	
    	$info['vat'] = empty($_GET['vat'])?'':$_GET['vat'];
    	$info['tax_rate'] = empty($_GET['tax_rate'])?'0':$_GET['tax_rate'];
    	$info['phone'] = empty($_GET['phone'])?'':$_GET['phone'];
    	$info['email'] = empty($_GET['email'])?'':$_GET['email'];
    	
    	if(!empty($_GET['act']) && !empty($_GET['info_id'])){
    		$info['id'] = (int)$_GET['info_id'];
    		$rtn=OrderHelper::setSellerInvoiceInfos($uid, $info,'edit');
    	}else 
    		$rtn=OrderHelper::setSellerInvoiceInfos($uid, $info,'add');
    	
    	return json_encode($rtn);
    }
    
    public function actionDelInvoiceInfo(){
    	$uid = \Yii::$app->user->id;
    	if(empty($uid))
    		exit(json_encode(['success'=>false,'message'=>'请先登录']));
    	 if(empty($_GET['id'])) $_GET['id'] = trim($_GET['id']);
    	 if(empty($_GET['id']))
    	 	exit(json_encode(['success'=>false,'message'=>'未选定需要删除的条目。']));
    	 $id=$_GET['id'];
    	 try{
	    	 $query = "DELETE  FROM `od_seller_invoice_info` WHERE `puid`=$uid and `id`=$id";
	    	 $command = \Yii::$app->db->createCommand($query);
	    	 $record = $command->execute();
	    	 if($record){
	    	 	$rtn=['success'=>true,'message'=>''];
	    	 }else{
	    	 	$rtn=['success'=>false,'message'=>'未找到指定条目'];
	    	 }
    	 }catch(\Exception $e){
			$rtn['success']=true;
			$rtn['message'] = $e->getMessage();
		}
		exit (json_encode($rtn));
    }
}
