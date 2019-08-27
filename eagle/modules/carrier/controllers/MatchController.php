<?php

namespace eagle\modules\carrier\controllers;

use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\data\Pagination;
use eagle\modules\carrier\models\MatchingRule;
use eagle\models\SysCountry;
use eagle\models\EbayExcludeshippinglocation;
use eagle\models\EbaySite;
use eagle\models\EbayShippingservice;
use common\helpers\Helper_Array;
use eagle\modules\carrier\apihelpers\ApiHelper;
use eagle\models\SaasEbayUser;
use eagle\models\SaasAliexpressUser;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use yii\db\Transaction;
use eagle\modules\util\helpers\TranslateHelper;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
/**
 * 运输服务匹配规则控制器，主要负责运输服务管理和订单运输服务匹配等相关功能
 * @author Million <88028624@qq.com> 
 * 2015-03-03
 */
class MatchController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	/**
	 * 运输服务匹配规则列表
	 * @author Million <88028624@qq.com> 
	 * 2015-03-03
	 */
    public function actionIndex()
    {
    	return "<a href='/configuration/carrierconfig/rule'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/match/index");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
        $query = MatchingRule::find();
        $sort = 'is_active';
        $order = 'desc';
    	if (Yii::$app->request->isPost){
    		$data = Yii::$app->request->post();
    	}else{
    		$data = Yii::$app->request->get();
    	}
    	
    	$query->andWhere('created > 0');
    	
        $pagination = new Pagination([
        		'defaultPageSize' => 15,
        		'pageSize' => $pageSize,
        		'totalCount' => $query->count(),
        		'pageSizeLimit'=>[15,200],//每页显示条数范围
        		'params'=>$data,
        		]);
        $list['pagination'] = $pagination;
        $sort_arr = array('is_active'=>'is_active desc','priority'=>'priority asc','transportation_service_id'=>'transportation_service_id asc','rule_name'=>'rule_name asc');
        unset($sort_arr[$sort]);
        $str = $sort.' '.$order.','.implode(',', $sort_arr);
        //var_dump($str);die;
        $query->orderBy($str);
        $query->limit($pagination->limit);
        $query->offset( $pagination->offset );
        $list['data'] = $query->all();
         
        $url_arr = array_merge(['/carrier/match/index'],$data);
        $return_url = Url::to($url_arr);
        return $this->render('index', [
            'list' => $list,'return_url'=>$return_url
        ]);
    }
    
    /**
     * 运输服务匹配规则编辑
     * @author Million <88028624@qq.com>
     * 2015-03-03
     */
    public function actionEdit()
    {
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/match/edit");
    	//收件国家
    	$query = SysCountry::find();
    	$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
    	$countrys =[];
    	foreach ($regions as $region){
    		$arr['name']= $region['region'];
    		$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
    		$countrys[]= $arr;
    	}
    	//站点
    	$sites = PlatformAccountApi::getAllPlatformOrderSite();
    	//账号
    	$selleruserids=PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	//买家选择物流物流
    	$buyer_transportation_services = PlatformAccountApi::getAllPlatformShippingServices();
    	//仓库
    	$warehouses=InventoryApiHelper::getWarehouseIdNameMap();
    	//商品标签
    	$product_tags = ProductApiHelper::getAllTags();
    	
    	
    	$errors = [];
    	if (Yii::$app->request->isPost){
    		$return_url = \Yii::$app->request->post('return_url');
    		//保存数据
    		$id = Yii::$app->request->post('id');
    		$rule = MatchingRule::find()->where(['id'=>$id])->one();
    		if (empty($rule)){
    			$rule = new MatchingRule();
    			$rule->uid = Yii::$app->user->identity->getParentUid();
    		}
    		$rule->operator = Yii::$app->user->identity->uid;
    		$rule->rule_name=Yii::$app->request->post('rule_name');
    		$rule->priority=Yii::$app->request->post('priority');
    		$rule->transportation_service_id=Yii::$app->request->post('transportation_service_id');
    		$rule->is_active = Yii::$app->request->post('is_active');
    		//验证提交数据
    		//var_dump(Yii::$app->request->post('rules'));die;
    		if (count(Yii::$app->request->post('rules')) == 0){
    			$errors[] = '请选择匹配项！';
    		}else{
    			$rule->rules=Yii::$app->request->post('rules');
    			foreach (Yii::$app->request->post('rules') as $rule_value){
    				switch ($rule_value){
    					case 'source':
    						if (count(Yii::$app->request->post('source')) == 0){
    							$errors[] = '请选择订单来源！';
    						}else {
    							$rule->source=Yii::$app->request->post('source');
    						};
    						break;
    					case 'site':
    						if (count(Yii::$app->request->post('site')) == 0){
    							$errors[] = '请选择站点！';
    						}else {
    							$rule->site=Yii::$app->request->post('site');
    						};
    						break;
    					case 'selleruserid':
    						if (count(Yii::$app->request->post('selleruserid')) == 0){
    							$errors[] = '请选择卖家账号！';
    						}else {
    							$rule->selleruserid=Yii::$app->request->post('selleruserid');
    						};
    						break;
    					case 'buyer_transportation_service':
    						if (count(Yii::$app->request->post('buyer_transportation_service')) == 0){
    							$errors[] = '请选择买家选择运输服务！';
    						}else {
    							$rule->buyer_transportation_service=Yii::$app->request->post('buyer_transportation_service');
    						};
    						break;
    					case 'warehouse':
    						if (count(Yii::$app->request->post('warehouse')) == 0){
    							$errors[] = '请选择仓库！';
    						}else {
    							$rule->warehouse=Yii::$app->request->post('warehouse');
    						};
    						break;
    					case 'receiving_country':
    						if (count(Yii::$app->request->post('receiving_country')) == 0){
    							$errors[] = '请选择收件国家！';
    						}else {
    							$rule->receiving_country=Yii::$app->request->post('receiving_country');
    						};
    						break;
    					case 'total_amount':
    						$total_amount = Yii::$app->request->post('total_amount');
    						if (strlen($total_amount['min'])>0){
    							if (preg_match("/\\D/",$total_amount['min'])){
    								$errors[] = '订单总金额最小值只能用数字！';
    							}
    						}else{
    							$errors[] = '请填写订单总金额最小值！';
    						}
    						if (strlen($total_amount['max'])>0){
    							if (preg_match("/\\D/",$total_amount['max'])){
    								$errors[] = '订单总金额最大值只能用数字！';
    							}
    						}else{
    							$errors[] = '请填写订单总金额最大值！';
    						}
    						if ($total_amount['min'] >= $total_amount['max']){
    							$errors[] = '订单总金额最大值必须大于最小值！';
    						}
    						$rule->total_amount=Yii::$app->request->post('total_amount');
    						break;
    					case 'freight_amount':
    						$freight_amount = Yii::$app->request->post('freight_amount');
    						if (strlen($freight_amount['min'])>0){
    							if (preg_match("/\\D/",$freight_amount['min'])){
    								$errors[] = '买家支付运费最小值只能用数字！';
    							}
    						}else{
    							$errors[] = '请填写买家支付运费最小值！';
    						}
    						if (strlen($freight_amount['max'])>0){
    							if (preg_match("/\\D/",$freight_amount['max'])){
    								$errors[] = '买家支付运费最大值只能用数字！';
    							}
    						}else{
    							$errors[] = '请填写买家支付运费最大值！';
    						}
    						if ($freight_amount['min'] >= $freight_amount['max']){
    							$errors[] = '买家支付运费最大值必须大于最小值！';
    						}
    						$rule->freight_amount=Yii::$app->request->post('freight_amount');
    						break;
    					case 'total_weight':
    						$total_weight = Yii::$app->request->post('total_weight');
    						if (strlen($total_weight['min'])>0){
    							if (preg_match("/\\D/",$total_weight['min'])){
    								$errors[] = '总重量最小值只能用数字！';
    							}
    						}else{
    							$errors[] = '请填写总重量最小值！';
    						}
    						if (strlen($total_weight['max'])>0){
    							if (preg_match("/\\D/",$total_weight['max'])){
    								$errors[] = '总重量最大值只能用数字！';
    							}
    						}else{
    							$errors[] = '请填写总重量最大值！';
    						}
    						if ($total_weight['min'] >= $total_weight['max']){
    							$errors[] = '总重量最大值必须大于最小值！';
    						}
    						$rule->total_weight=Yii::$app->request->post('total_weight');
    						break;
    					case 'product_tag':
    						if (count(Yii::$app->request->post('product_tag')) == 0){
    							$errors[] = '请选择商品标签！';
    						}else {
    							$rule->product_tag=Yii::$app->request->post('product_tag');
    						};
    						break;
    				}
    			}
    		}
    		$rule->created=time();
    		$rule->updated=time();
    		//规则名不能重复
    		if ($rule->isNewRecord){
    			$count = MatchingRule::find()->where(['rule_name'=>Yii::$app->request->post('rule_name')])->andWhere('created > 0')->count();
    		}else{
    			$count = MatchingRule::find()->where('rule_name = :rule_name and id <> :id',[':rule_name'=>Yii::$app->request->post('rule_name'),':id'=>$id])->andWhere('created > 0')->count();
    		}
    			
    			if ($count>0){
    				$errors[] = '规则名重复';
    			}else{
    				if (count($errors)==0){
    					if ($rule->save()){
    						return $this->redirect($return_url);
    					}else{
    						$modelErrors = $rule->getErrors();
    						foreach ($modelErrors as $error){
    							$errors[] = $error[0];
    						}
    					}
    				}
    				
    			}
    		
    	}else {
    		$id = Yii::$app->request->get('id');
    		$rule = MatchingRule::find()->where(['id'=>$id])->one();
    		if (empty($rule)){
    			$rule = new MatchingRule();
    		}
    	}
    	if (Yii::$app->request->isGet){
    		$return_url = \Yii::$app->request->get('return_url');
    	}
    	$services = ApiHelper::getShippingServices();
    	return $this->render('edit', ['rule'=>$rule,'services'=>$services,'countrys' =>$countrys,'sites'=>$sites,'selleruserids'=>$selleruserids,'warehouses'=>$warehouses,'product_tags'=>$product_tags,'buyer_transportation_services'=>$buyer_transportation_services,'errors'=>$errors,'return_url'=>$return_url]);
    	
    }
    
    public function actionOnoff(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/match/onoff");
    	try {
    		$Obj = MatchingRule::findOne(\Yii::$app->request->get('id'));
    		$Obj->is_active = \Yii::$app->request->get('is_active');
    		$Obj->save();
    	}catch (\Exception $ex){
    		exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
    	}
    	exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('操作成功！'))));
    }
    
    /**
     * 运输服务匹配规则删除
     * @author hqw
     * 2016-01-14
     */
    public function actionDelMatch(){
    	if (Yii::$app->request->isPost){
    		try {
    			$Obj = MatchingRule::findOne(\Yii::$app->request->post('id'));
    			$Obj->is_active = 0;
    			$Obj->created = 0;
    			$Obj->save(false);
    			
    			$params = array('paramstr1'=>'time:'.time().'id:'.$Obj->id);
    			AppTrackerApiHelper::actionLog("eagle_v2","/carrier/match/del-match",$params);
    		}catch (\Exception $ex){
    			return json_encode(array('Ack' => 1,'msg'=>$ex->getMessage()));
    		}
    		return json_encode(array('Ack' => 0,'msg'=>'操作成功！'));
    	}
    	
    	return json_encode(array('Ack' => 1,'msg'=>'非法操作！'));
    }
    
    public function actionTest()
    {
    	 
    	$query = SysCountry::find()->all();
    	foreach ($query as $one){
    		$a = EbayExcludeshippinglocation::find()->where(['location'=>$one->country_code])->one();
    		if (!empty($a)){
    			$one->region = $a->region;
    			$one->save();
    		}
    	}
    	 
    }
}