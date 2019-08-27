<?php

namespace eagle\modules\listing\controllers;

use eagle\models\SaasEbayUser;
use common\api\ebayinterface\setpromotionalsale;
use eagle\modules\listing\models\EbayPromotion;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\data\Pagination;
use common\api\ebayinterface\getpromotionalsaledetails;
class EbaypromotionController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	
	/**
	 * list用户目前所有的促销规则
	 * @author fanjs
	 */
	public function actionShow(){
		AppTrackerApiHelper::actionLog('listing_ebay','/ebaypromotion/show');
		$ebayselleruserid = SaasEbayUser::find()
                                ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                                ->andwhere('listing_status = 1')
                                ->andwhere('listing_expiration_time > '.time())
                                ->select('selleruserid')
                                ->asArray()
                                ->all();
        $ebaydisableuserid = SaasEbayUser::find()
                ->where('uid = '.\Yii::$app->user->identity->getParentUid())
                ->andwhere('listing_status = 0 or listing_expiration_time < '.time().' or listing_expiration_time is null')
                ->asArray()
                ->all();
        $data= EbayPromotion::find();

        //不显示 解绑的账号的订单
        $data->andWhere(['selleruserid'=>$ebayselleruserid]);
		if (isset($_REQUEST['selleruserid']) && strlen($_REQUEST['selleruserid'])){
			$data->andWhere(['selleruserid'=>$_REQUEST['selleruserid']]);
		}
		$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
		$proms = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		// $ebayselleruserid = SaasEbayUser::find()->where('uid = '.\Yii::$app->user->identity->getParentUid())->all();
		return $this->render('show',['proms'=>$proms,'pages'=>$pages,'ebayselleruserid'=>$ebayselleruserid,'ebaydisableuserid'=>$ebaydisableuserid]);
	}
	
	/**
	 * 用户ajax添加促销方式的入库
	 * @author fanjs
	 */
	public function actionAjaxAdd(){
		if (\Yii::$app->request->isPost){
			//处理api呼叫时间
			$starttime = strtotime($_POST['startdate'].' '.$_POST['starttime']);
			$endtime = strtotime($_POST['enddate'].' '.$_POST['endtime']);
			if ($endtime<=$starttime){
				return '开始时间必须小于结束时间';
			}
			
			$action = 'Add';
			$promotion = [];
			$promotion['PromotionalSaleStartTime'] = $starttime;
			$promotion['PromotionalSaleEndTime'] = $endtime;
			$promotion['PromotionalSaleType'] = $_POST['promotionalsaletype'];
			$promotion['PromotionalSaleName'] = $_POST['pname'];
			
			if ($_POST['promotionalsaletype'] == 'PriceDiscountOnly'){
				if($_POST['discounttype_only'] == 'Percentage'){
					$promotion['DiscountType'] = 'Percentage';
					$promotion['DiscountValue'] = $_POST['discountvaluepercent_only'];
				}
				if($_POST['discounttype_only'] == 'Price'){
					$promotion['DiscountType'] = 'Price';
					$promotion['DiscountValue'] = $_POST['discountvalueprice_only'];
				}
			}
			
			if ($_POST['promotionalsaletype'] == 'PriceDiscountAndFreeShipping'){
				if($_POST['discounttype_shipping'] == 'Percentage'){
					$promotion['DiscountType'] = 'Percentage';
					$promotion['DiscountValue'] = $_POST['discountvaluepercent_shipping'];
				}
				if($_POST['discounttype_shipping'] == 'Price'){
					$promotion['DiscountType'] = 'Price';
					$promotion['DiscountValue'] = $_POST['discountvalueprice_shipping'];
				}
			}
			
			//获取授权
			$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$_POST['selleruserid']]);
			if (empty($ebayuser)){
				return '数据库无该选中的eBay账号';
			}
			
			$api = new setpromotionalsale();
			$api->resetConfig($ebayuser->DevAcccountID);
			$api->eBayAuthToken = $ebayuser->token;	
			$result = $api->api($action,$promotion);
			
			if ($api->responseIsFailure()){
				return $result['Errors']['LongMessage'];
			}else{
				$prom = new EbayPromotion();
				$prom->selleruserid = $_POST['selleruserid'];
				$prom->promotionalsaleid = $result['PromotionalSaleID'];
				$prom->promotionalsalename = $promotion['PromotionalSaleName'];
				$prom->action = 'Add';
				$prom->discounttype = @$promotion['DiscountType'];
				$prom->discountvalue = @$promotion['DiscountValue'];
				$prom->promotionalsaleendtime = $endtime;
				$prom->promotionalsalestarttime = $starttime;
				$prom->promotionalsaletype = $promotion['PromotionalSaleType'];
				$prom->status = $result['Status'];
				$prom->created = time();
				$prom->updated = time();
				
				if ($prom->save()){
					return 'success';
				}else{
					return $prom->getErrors();
				}
			}
		}
	}
	
	/**
	 * 同步在线的促销规则
	 * @author fanjs
	 */
	public function actionSync(){
		return $this->renderPartial('sync');
	}
	
	/**
	 * 同步在线的促销规则
	 * @author fanjs
	 */
	public function actionAjaxsync(){
		$uid = \Yii::$app->user->identity->getParentUid();
		$ebayusers = SaasEbayUser::find()->where('uid = '.$uid)->all();
		$api = new getpromotionalsaledetails();
		$text = '';
		foreach ($ebayusers as $ebayuser){
			$api->resetConfig($ebayuser->DevAcccountID);
			$api->eBayAuthToken = $ebayuser->token;
			$result = $api->api('');
			if ($api->responseIsFailure()){
				$text.='<p><strong>'.$ebayuser->selleruserid.'</strong>:'.$result['Errors']['LongMessage'].'</p>';
			}else{
				if (isset($result['PromotionalSaleDetails']['PromotionalSale'])){
					if (isset($result['PromotionalSaleDetails']['PromotionalSale']['PromotionalSaleID'])){
						$_tmp = ['0'=>$result['PromotionalSaleDetails']['PromotionalSale']];
					}else{
						$_tmp = $result['PromotionalSaleDetails']['PromotionalSale'];
					}
					foreach ($_tmp as $v){
						$prom = EbayPromotion::findOne(['selleruserid'=>$ebayuser->selleruserid,'promotionalsaleid'=>$v['PromotionalSaleID']]);
						if (empty($prom)){
							$prom = new EbayPromotion();
						}
						
						$prom->selleruserid = $ebayuser->selleruserid;
						$prom->promotionalsaleid = $v['PromotionalSaleID'];
						$prom->promotionalsalename = $v['PromotionalSaleName'];
						$prom->action = 'Add';
						$prom->discounttype = @$v['DiscountType'];
						$prom->discountvalue = @$v['DiscountValue'];
						$prom->promotionalsaleendtime = strtotime($v['PromotionalSaleStartTime']);
						$prom->promotionalsalestarttime = strtotime($v['PromotionalSaleEndTime']);
						$prom->promotionalsaletype = $v['PromotionalSaleType'];
						$prom->status = $v['Status'];
						$prom->updated = time();
						if (is_null($prom->created)){
							$prom->created = time();
						}
						$prom->save();
					}
				}
				$text.='<p><strong>'.$ebayuser->selleruserid.'</strong>:success'.'</p>';
			}
		}
		return $text;
	}
	
	/**
	 * 删除某个促销规则
	 * @author fanjs
	 */
	public function actionDelete(){
		if (\Yii::$app->request->isPost){
			$prom = EbayPromotion::findOne($_POST['id']);
			if (empty($prom)){
				return '数据库无改规则记录';
			}
			$ebayuser = SaasEbayUser::findOne(['selleruserid'=>$prom->selleruserid]);
			if (empty($ebayuser)){
				return '数据库无改eBay账号';
			}
			$api = new setpromotionalsale();
			$api->resetConfig($ebayuser->DevAcccountID);
			$api->eBayAuthToken = $ebayuser->token;
			$result = $api->delete($prom->promotionalsaleid);
			if ($api->responseIsFailure()){
				return $result['Errors']['LongMessage'];
			}else{
				$prom->status = 'Deleted';
				if($prom->save()){
					return 'success';
				}else{
					return $prom->getErrors();
				}
			}
		}
	}
}