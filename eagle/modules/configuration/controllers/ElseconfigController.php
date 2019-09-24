<?php
namespace eagle\modules\configuration\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\order\models\Excelmodel;
use eagle\modules\order\models\OdOrder;
use yii\data\Pagination;
use yii\web\Controller;
use eagle\modules\order\models\EbayFeedbackTemplate;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SaasCdiscountUser;
use eagle\models\SaasAmazonUser;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\models\SaasPriceministerUser;
use eagle\modules\util\helpers\ExcelHelper;


class ElseconfigController extends \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;

	/**
	 * 自定义导出订单格式修改
	 * @author fanjs edit：2015-3-18
	 */
	public  function actionExcelModelList(){
		AppTrackerApiHelper::actionLog("Oms-erp", "configuration/elseconfig/excel-model-list");
		$models	= Excelmodel::find(['tablename'=>OdOrder::className()]);
		$pages = new Pagination(['totalCount'=>$models->count()]);
		$models = $models->offset($pages->offset)
		->limit($pages->limit)
		->all();
		return $this->render('modellist', array(
				'models'=>$models,
				'pages'=>$pages
		));
	}
	/**
	 * 自定义导出范本的编辑和新增
	 * @author fanjs
	 *
	 */
	public function actionExcelmodel_edit(){
		if (\Yii::$app->request->isPost){
			if (isset($_POST['mid'])){
				$model = Excelmodel::findOne($_POST['mid']);
			}else{
				$model = new Excelmodel();
			}
			try {
				$model->name = $_POST['modelname'];
				$model->content = implode(',',$_POST['to']);
				$model->type = 1;
				$model->belong = \Yii::$app->user->identity->getFullName();
				$model->tablename = OdOrder::tableName();
				if ($model->save()){
					return $this->redirect(['/configuration/elseconfig/excel-model-list']);
				}else{
					return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'保存失败']);
				}
			}catch (\Exception $e){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>$e->getMessage()]);
			}
		}
		if ($_GET['mid']>0){
			$excelmodel = Excelmodel::findOne($_GET['mid']);
			if (empty($excelmodel)){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'未找到相应的自定义导出范本']);
			}
			if ($excelmodel->tablename != OdOrder::tableName()){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'传入的范本ID有误']);
			}
		}else{
			$excelmodel = new Excelmodel();
		}
		return $this->renderPartial('edit2',['model'=>$excelmodel]);
	}
	
	/**
	 * 删除自定义导出格式
	 * @author fanjs
	 * @return string
	 */
	public function actionExcelmodel_del(){
		if(\Yii::$app->request->isPost){
			if (!empty($_POST['mid'])){
				try {
					Excelmodel::deleteAll(['id'=>$_POST['mid']]);
					return 'success';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}
		}
	}
	
	public function items_arr(){
		return ExcelHelper::$content;
	}
	
	/**
	 * 好评范本的列表 @author fanjs
	 * @return Ambigous <string, string>
	 */
	public function actionFeedbackTemplateList()
	{
		AppTrackerApiHelper::actionLog("Oms-ebay", "/feedback/list");
		$list = EbayFeedbackTemplate::find()->all();
		return $this->render('feedback-template-list',['lists'=>$list]);
	}
	/**
	 * 根据post数据，进行创建/修改好评范本
	 * @return Ambigous <\eagle\modules\configuration\controllers\Ambigous, string, string>
	 */
	public function actionSaveFeedbackTemplate(){
		if(\Yii::$app->request->isPost){
			if (isset($_POST['templateid']) && trim($_POST['templateid']) != ''){
				$template = EbayFeedbackTemplate::findOne($_POST['templateid']);
			}else{
				$template = new EbayFeedbackTemplate();
			}
			try {
				$template->template_type = $_POST['feedbacktype'];
				$template->template = $_POST['feedbackval'];
				if ($template->isNewRecord){
					$template->create_time = time();
				}
				$template->update_time = time();
				if($template->save()){
					return json_encode(['error'=>0,'msg'=>'保存成功']);
				}
				else{
					return json_encode(['error'=>1,'msg'=>'保存失败']);
				}
// 				return $this->actionFeedbackTemplateList();
			}catch (\Exception $e){
				return json_encode(['error'=>1,'msg'=>$e->getMessage()]);
			}
		}
	}
	/**
	 * 打开修改/创建好评范本model
	 */
	public function actionCreate(){
		if(isset($_GET['id'])&&$_GET['id']>0){
			$template = EbayFeedbackTemplate::findOne($_GET['id']);
		}else{
			$template = new EbayFeedbackTemplate();
		}
		return $this->renderAjax('create',['template'=>$template]);
	}
	
	/**
	 * 删除好评范本
	 */
	function actionDelete(){
		if(\Yii::$app->request->isPost){
			try {
				EbayFeedbackTemplate::deleteAll('id = '.$_POST['id']);
				return 'success';
			}catch (Exception $e){
				return print_r($e->getMessage());
			}
		}
	}
	
	public function actionOmsSet()
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
			
			//移入发货中时自动生成商品
			if (isset($_POST['shipandcreateSKU'])){
				ConfigHelper::setConfig('order/shipandcreateSKU',$_POST['shipandcreateSKU']);
			}
	
			echo '<script>alert("操作已完成");</script>';
		}
		//订单数量统计
		/*
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
		*/
		$counter = [];
		
		$is_show_OtherOperation = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		
		$is_show_AvailableStock = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowAvailableStock();
		
		return $this->render('omsset',['counter'=>$counter, 'is_show_OtherOperation'=>$is_show_OtherOperation,'is_show_AvailableStock'=>$is_show_AvailableStock]);
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
		
		//组织PM可选店铺
		$pmAccounts = SaasPriceministerUser::find()->where(['uid'=>$uid])->asArray()->all();
		foreach ($pmAccounts as $pmAccount){
			if(empty($stores['priceminister']['FR'])){
				$canChoseStores['priceminister']['FR'][]=$pmAccount['username'];
				continue;
			}
			if(!in_array($pmAccount['username'], $stores['priceminister']['FR'])){
				$canChoseStores['priceminister']['FR'][]=$pmAccount['username'];
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
		 
		//高青发票不需选择店铺
		if(empty($_GET['invoice_type']) || $_GET['invoice_type'] != "G")
		{
    		if(empty($_GET['stores'])){
    			$rtn['success']=false;
    			$rtn['message'].='必须选择至少一个店铺';
    		}else
    			$info['stores'] = $_GET['stores'];
		}
		else 
		    $info['stores'] = '';
		
		if(!empty($_GET['autographurl']))
		{
		    //判断图片是否有效
		    $opts = array(
		    		'http'=>array(
		    				'timeout'=>3,
		    	));
		    
		    $context = stream_context_create($opts);
		    $resource = @file_get_contents($_GET['autographurl'], false, $context);
		    if($resource) 
		    {
		    	$info['autographurl'] = $_GET['autographurl'];
		    } 
		    else 
		    {
		    	$rtn['success']=false;
    			$rtn['message'].='签名图片Url不存在！';
		    }
		}
		else
		    $info['autographurl'] = '';
		
		//是否输出错误提示
		if($rtn['success']==false)
			return json_encode($rtn);
		 
		$info['vat'] = empty($_GET['vat'])?'':$_GET['vat'];
		$info['tax_rate'] = empty($_GET['tax_rate'])?'0':$_GET['tax_rate'];
		$info['tax_formula'] = empty($_GET['tax_formula'])?'1':$_GET['tax_formula'];
		$info['phone'] = empty($_GET['phone'])?'':$_GET['phone'];
		$info['email'] = empty($_GET['email'])?'':$_GET['email'];
		$info['invoice_type'] = empty($_GET['invoice_type'])?'O':$_GET['invoice_type'];
		
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

	public function actionEditcontent(){
		$name=$_POST['name'];
		$val=$_POST['val'];
		$ordername=$_POST['ordername'];
		$name = \yii\helpers\Html::decode($name);
		$name = \yii\helpers\Html::encode($name);
		return $this->renderPartial('_editcontent',[
				'name'=>$name,
				'val'=>$val,
				'ordername'=>$ordername
				]);
	}
	
	public function actionSavecontent(){
		$content=$_POST['content'];
		$content=substr($content,0,-1);
		$modelname=$_POST['name'];
		$val=$_POST['val'];
		
		if($val==-1)
			$model = new Excelmodel();
		else
			$model = Excelmodel::findOne($val);
		try {
			$model->name = $modelname;
			$model->content = $content;
			$model->type = 1;
			$model->belong = \Yii::$app->user->identity->getFullName();
			$model->tablename = OdOrder::tableName();
			if ($model->save()){
				return exit(json_encode(array('code'=>1, 'msg'=>'保存成功', 'data'=>'')));   		
			}else{
				return exit(json_encode(array('code'=>0, 'msg'=>'保存失败', 'data'=>'')));   		
			}
		}catch (\Exception $e){
			return exit(json_encode(array('code'=>0, 'msg'=>'保存失败', 'data'=>'')));   		
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 新增模板打开页面
	 +----------------------------------------------------------
	 * @param mid 模板id
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExcelmodel_edit_new(){
		$mid=isset($_GET['mid'])?$_GET['mid']:'-1';
		
		if($mid>-1){
			$excelmodel = Excelmodel::findOne($mid);
			if (empty($excelmodel)){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'未找到相应的自定义导出范本']);
			}
			if ($excelmodel->tablename != OdOrder::tableName()){
				return $this->render('//errorview',['title'=>'编辑自定义导出范本','error'=>'传入的范本ID有误']);
			}
		}
		else
			$excelmodel = new Excelmodel();
		
		return $this->renderPartial('addexportshow',['model'=>$excelmodel]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取范本页面html
	 +----------------------------------------------------------
	 * @param arr 范本字段
	 * @param templateType 范本类型
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/01/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExportHtmlTableView(){
		$arr=empty($_POST['arr'])?'':$_POST['arr'];
		$templateType=empty($_POST['templateType'])?'':$_POST['templateType'];
		$html="";
try{
		if(!empty($arr)){
			$content=ExcelHelper::$content;
			$number=1;
			$items=array();
			$showselect_key = explode(',',$arr);
			foreach ($showselect_key as $item){
				$items_arr=explode(':',$item);
				if(isset($content[$items_arr[0]]) || strstr($items_arr[0],'-custom-')!=false){
					$items[$number]=$item;
					$number++;
				}
			}
				}
			$html.='<table class="myj-table excl"><tbody>';

			$content_count=(count($content)%6==0)?(count($content)/6):(floor(count($content)/6)+1);
			$j=1;
			for($i=0;$i<$content_count;$i++){
				$html.='<tr>';
				$j_list=$j;
				for($k=1;$k<=6;$k++){
					$html.='<td data-names='.$j_list.'><div class="checkbox pull-left">';
					if(!empty($arr) && !empty($items[$j_list])){
						$items_arr=explode(':',$items[$j_list]);
							if(strstr($items_arr[0],'-custom-')){
								$temp_item_arr=explode('-', $items_arr[0]);
								if($templateType=='order')
									$html.='<span class="spanObj spanObjOther" data-field="'.$temp_item_arr[1].'" data-value="'.$items_arr[2].'">'.$items_arr[1].'</span>';
								else if($templateType=='orderbiaozhun')
									$html.='<label><input type="checkbox" checked="" name="exportKey" value="'.$temp_item_arr[2].'"> <span class="spanObj" data-field="'.$temp_item_arr[1].'" data-value="'.$items_arr[2].'">'.$items_arr[1].'</span></label>';
								else 
									$html.='<div class="tdDivCont"><span class="glyphicon glyphicon-remove tdRemove pull-left mTop5"></span><span class="spanObj" data-field="'.(empty($temp_item_arr[1])?:$temp_item_arr[1]).'" data-customname="'.(empty($temp_item_arr[2])?:$temp_item_arr[2]).'" data-value="'.($items_arr[2]).'" ordername=" '.(empty($items_arr[1])?$temp_item_arr[1]:$items_arr[1]).'"> '.(empty($items_arr[1])?$temp_item_arr[1]:$items_arr[1]).'</span><span class="glyphicon glyphicon-pencil tdPencil pull-right mTop5"></span></div>';
							}
							else{
								if($templateType=='order')
									$html.='<span class="spanObj spanObjOther" data-field="'.(empty($items_arr[0])?:$items_arr[0]).'" data-value="">'.(empty($items_arr[1])?$content[$items_arr[0]]:$items_arr[1]).'</span>';
								else if($templateType=='orderbiaozhun')
									$html.='<label><input type="checkbox" checked="" name="exportKey" value="'.(empty($items_arr[0])?:$items_arr[0]).'"> <span class="spanObj" data-field="'.(empty($items_arr[0])?:$items_arr[0]).'" data-value="'.(empty($items_arr[0])?:$items_arr[0]).'">'.(empty($items_arr[1])?$content[$items_arr[0]]:$items_arr[1]).'</span></label>';
								else
									$html.='<div class="tdDivCont"><span class="glyphicon glyphicon-remove tdRemove pull-left mTop5"></span><span class="spanObj" data-field="'.(empty($items_arr[0])?:$items_arr[0]).'" data-customname="'.(empty($items_arr[0])?:$items_arr[0]).'" data-value="" ordername=" '.(empty($items_arr[1])?$content[$items_arr[0]]:$items_arr[1]).'"> '.(empty($items_arr[1])?$content[$items_arr[0]]:$items_arr[1]).'</span><span class="glyphicon glyphicon-pencil tdPencil pull-right mTop5"></span></div>';
							}
					}
					$html.='</div></td>';
					$j_list=$j_list+$content_count;
				}
				$j++;
				$html.='</tr>';
			}
			$html.='</tbody></table>';
}catch(\Exception $ex){
	print_r($ex->getMessage());die;
}

		return $html;
	}
	
	//保存 OMS 设置没有指定小老板订单状态时是否显示特定操作
	public function actionOtherOperationOrderSet(){
		$result = array('success'=>true, 'message'=>'');
		
		$tmp_is_show_OtherOperation = empty($_REQUEST['is_show_OtherOperation']) ? 0 : $_REQUEST['is_show_OtherOperation'];
		
		$is_show_OtherOperation = 'N';
		
		if($tmp_is_show_OtherOperation == 1){
			$is_show_OtherOperation = 'Y';
		}else{
			$is_show_OtherOperation = 'N';
		}
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		
		ConfigHelper::setGlobalConfig('Order/isShowMenuAllOtherOperation_'.$puid, $is_show_OtherOperation);
		
		exit(json_encode($result));
	}
	
	//保存 OMS 设置已付款状态是否不显示可用库存功能
	public function actionAvailablestockSet(){
		$result = array('success'=>true, 'message'=>'');
	
		$tmp_is_show_OtherOperation = isset($_REQUEST['is_show_Availablestock']) ? $_REQUEST['is_show_Availablestock'] : 0;
		
		$is_show_OtherOperation = 'N';
		
		if($tmp_is_show_OtherOperation == 1){
			$is_show_OtherOperation = 'Y';
		}else{
			$is_show_OtherOperation = 'N';
		}
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		
		ConfigHelper::setGlobalConfig('Order/isShowAvailablestock_'.$puid, $is_show_OtherOperation);
		
		exit(json_encode($result));
	}
	
}