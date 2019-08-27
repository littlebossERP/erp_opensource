<?php
/**
 * 用户处理所有的ebay议价相关功能
 * @author witsionjs
 *
 */
namespace eagle\modules\order\controllers;

use yii;
use eagle\models\SaasEbayUser;
use common\helpers\Helper_Array;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\order\models\EbayBestoffer;
use common\api\ebayinterface\getbestoffers;
use common\api\ebayinterface\respondtobestoffer;
use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
class BestofferController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	/**
	 * 议价的列表页面
	 * @author fanjs
	 */
    public function actionList()
    {
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/bestoffer/list");
    	$data = EbayBestoffer::find();
    	if (!empty($_REQUEST['bestofferstatus'])){
    		$data->andWhere('bestofferstatus = :s',[':s'=>$_REQUEST['bestofferstatus']]);
    	}
    	if (!empty($_REQUEST['selleruserid'])){
    		$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
    	}
    	if (!empty($_REQUEST['buyerid'])){
    		$data->andWhere(['like','bestoffer',$_REQUEST['buyerid']]);
    	}
    	if (!empty($_REQUEST['startdate'])){
    		$data->andWhere(['>','createtime',$_REQUEST['startdate']]);
    	}
    	if (!empty($_REQUEST['enddate'])){
    		$data->andWhere(['<','createtime',$_REQUEST['enddate']]);
    	}
    	$pages = new Pagination(['totalCount'=>$data->count(),'pageSize'=>'50','params'=>$_REQUEST]);
    	$bestoffers = $data->offset($pages->offset)
    	->limit($pages->limit)
    	->all();
    	$selleruserids = SaasEbayUser::find()->where('uid = :uid and expiration_time > :expiretime',[':uid'=>\Yii::$app->user->id,':expiretime'=>time()])->all();
    	$selleruserids = Helper_Array::toHashmap($selleruserids, 'selleruserid','selleruserid');
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
    	
        return $this->render('list',['selleruserids'=>$selleruserids,'bestoffers'=>$bestoffers,'counter'=>$counter,'pages'=>$pages]);
    }
    
    /**
     * 同步议价的页面操作
     * @author fanjs
     */
    public function actionSynclist(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/bestoffer/synclist");
    	if (\Yii::$app->request->isPost){
    		if (isset($_POST['selleruserid'])&&count($_POST['selleruserid'])){
    			foreach ($_POST['selleruserid'] as $seller){
    				try {
    					$sea=SaasEbayAutosyncstatus::find()->where('selleruserid = :s and type = :t',[':s'=>$seller,':t'=>SaasEbayAutosyncstatus::BestOffer])->one();
    					if (empty($sea)){
    						$sea = new SaasEbayAutosyncstatus();
    					}
    					$sea->selleruserid=$seller;
    					$sea->ebay_uid=SaasEbayUser::find()->where('selleruserid = :s',[':s'=>$seller])->one()->ebay_uid;
    					$sea->type=SaasEbayAutosyncstatus::BestOffer;
    					$sea->status=0;
    					$sea->status_process=0;
    					if (empty($sea->created)){
    						$sea->created = time();
    					}
    					if (empty($sea->lastrequestedtime)){
    						$sea->lastrequestedtime = time();
    					}
    					$sea->updated=time();
    					$sea->save();
    				}catch (\Exception $e){
    					print_r($e);
    				}
    			}
    			echo '<script>bootbox.alert("同步请求已插入队列");</script>';
    		}
    	}
    	$selleruserids = SaasEbayUser::find()->where('uid = :uid and expiration_time > :expiretime',[':uid'=>\Yii::$app->user->id,':expiretime'=>time()])->asArray()->all();
    	$selleruserids = Helper_Array::toHashmap($selleruserids, 'selleruserid','selleruserid');
    	$bestoffers = SaasEbayAutosyncstatus::find()->where('status = 0 and status_process = 0')->andWhere(['selleruserid'=>$selleruserids])->all();
    	return $this->render('synclist',['selleruserids'=>$selleruserids,'bestoffers'=>$bestoffers]);
    }
    
    /**
     * ajax更新bestoffer
     * @author fanjs
     */
    public function actionAjaxsync(){
    	if(Yii::$app->request->isPost){
    		if (empty($_POST['bestofferid'])){
    			return '未传输相应的议价ID';
    		}
    		$bestoffer = EbayBestoffer::find()->where('bestofferid=:b',[':b'=>$_POST['bestofferid']])->one ();
    		$itemid = $bestoffer->itemid;
    		$sellerid = $bestoffer->selleruserid;
    		$api = new getbestoffers();
    		$api->eBayAuthToken = SaasEbayUser::find()->where('selleruserid=:s',[':s'=>$sellerid])->one ()->token;
    		$result = $api->api ( $itemid, $_POST['bestofferid']);
    		if ($api->responseIsSuccess ()){
    			try{
    				$api->saveone($result, $sellerid);
    			}catch (\Exception $e){
    				return $e->getMessage();
    			}
    			return 'success';
    		}else{
    			return $result['Errors']['LongMessage'];
    		}
    	}
    } 
    
    /**
     * ajax接受买家的议价
     * @author fanjs
     */
    public function actionAjaxresponse(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/bestoffer/response");
    	if(Yii::$app->request->isPost){
    		if (empty($_POST['bestofferid'])){
    			return '未传输相应的议价ID';
    		}
    		$bestoffer = EbayBestoffer::find()->where('bestofferid=:b',[':b'=>$_POST['bestofferid']])->one ();
    		$itemid = $bestoffer->itemid;
    		$sellerid = $bestoffer->selleruserid;
    		$reqarr=[
    			'Action'=>$_POST['action'],
    			'BestOfferID'=>$_POST['bestofferid'],
    			'ItemID'=>$itemid,
    		];
    		if (isset($_POST['price'])){
    			$reqarr['CounterOfferPrice']=$_POST['price'];
    			$reqarr['CounterOfferQuantity']=$bestoffer->bestoffer['Quantity'];
    		}
    		$api = new respondtobestoffer();
    		$api->eBayAuthToken = SaasEbayUser::find()->where('selleruserid=:s',[':s'=>$sellerid])->one ()->token;
    		$result = $api->api ( $reqarr, $_POST['bestofferid'],\Yii::$app->user->identity->getFullName());
    		if ($api->responseIsSuccess ()){
    			return 'success';
    		}else{
    			return $result['Errors']['LongMessage'];
    		}
    	}
    }
    
    /**
     * 生成议价的交互内容视图
     * @author fanjs
     */
    public function actionCountview(){
    	if (\Yii::$app->request->isPost){
    		if (empty($_POST['bestofferid'])){
    			return '未传输相应的议价ID';
    		}
    		$bestoffer = EbayBestoffer::findOne($_POST['bestofferid']);
    		return $this->renderPartial('countview',['bestoffer'=>$bestoffer]);
    	}
    }

}
