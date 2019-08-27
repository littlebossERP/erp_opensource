<?php

namespace eagle\modules\message\helpers;

use eagle\modules\message\models\CsRecmProductPerform;
use eagle\modules\message\models\CsRecommendProduct;

use yii;
use yii\data\Pagination;

use eagle\models\SaasWishUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishOrder;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\models\WishOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\StringHelper;
use eagle\modules\message\models\MsgTemplate;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\message\models\Message;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\base\Exception;
use eagle\modules\message\models\AutoRoles;
use eagle\models\LtCustomizedRecommendedGroup;
use eagle\models\LtCustomizedRecommendedProd;
use eagle\modules\order\models\OdOrder;

/**
 * 
 +------------------------------------------------------------------------------
 * Message模块Tracking信息及推荐商品helper
 +------------------------------------------------------------------------------
 * @category	Message
 * @package		Helper/Message
 * @subpackage  Exception
 * @author		lzhl
 +------------------------------------------------------------------------------
 */
class TrackingMsgHelper {
	
	private static $TRANSLATE_MAPPING=[
		'EN'=>[
			'The Parcel Information'=>'The Parcel Information',
			'Order ID'=>'Order ID',
			'Order Date'=>'Order Date',
			'quantity'=>'quantity',
			'Total'=>'Total',
			'Origin Country'=>'Origin Country',
			'Destination Country'=>'Destination Country',
			'Hot Sale'=>'Hot Sale',
			'Go to official website'=>'Go to official website',
			'Download Invoice'=>'Download Invoice',
		],
		'CN'=>[
			'The Parcel Information'=>'包裹信息',
			'Order ID'=>'订单号',
			'Order Date'=>'订单日期',
			'quantity'=>'数量',
			'Total'=>'合计',
			'Origin Country'=>'始运国家',
			'Destination Country'=>'目的地国家',
			'Hot Sale'=>'热销商品',
			'Go to official website'=>'查看官方网站',
			'Download Invoice'=>'点击下载发票',
		],
		'FR'=>[
			'The Parcel Information'=>'la parcelle d\'information',
			'Order ID'=>'Order ID',
			'Order Date'=>'Order Date',
			'quantity'=>'quantité',
			'Total'=>'Total',
			'Origin Country'=>'Pays d\'Origine',
			'Destination Country'=>'Pays de Destination',
			'Hot Sale'=>'Vente chaude',
			'Go to official website'=>'Accéder au site officiel',
			'Download Invoice'=>'Télécharger Invoice',
		],
		'DE'=>[
			'The Parcel Information'=>'Die Parcel Informationen',
			'Order ID'=>'Bestellnummer',
			'Order Date'=>'Bestelldatum',
			'quantity'=>'Quantität',
			'Total'=>'Total',
			'Origin Country'=>'Herkunftsland',
			'Destination Country'=>'Zielland',
			'Hot Sale'=>'Vente chaude',
			'Go to official website'=>'Besuchen offiziellen Website',
			'Download Invoice'=>'Download Rechnung',
		],
		'RU'=>[
			'The Parcel Information'=>'Пакета информация',
			'Order ID'=>'номер заказа',
			'Order Date'=>'Дата Заказа',
			'quantity'=>'количество',
			'Total'=>'в общей сложности',
			'Origin Country'=>'Страна отправки',
			'Destination Country'=>'Страна назначения',
			'Hot Sale'=>'жарко, продажа',
			'Go to official website'=>'Просмотреть официальном сайте',
			'Download Invoice'=>'Скачать счет-фактура',
		],//俄语
		'ES'=>[
			'The Parcel Information'=>'La información del paquete',
			'Order ID'=>'ID de orden',
			'Order Date'=>'Fecha de orden',
			'quantity'=>'cantidad',
			'Total'=>'Total',
			'Origin Country'=>'País de origen',
			'Destination Country'=>'País de destino',
			'Hot Sale'=>'Venta caliente',
			'Download Invoice'=>'Descargar factura',
		],//西班牙
		/**
		'PT'=>[],//葡萄牙
		'GR'=>[],//希腊
		'IT'=>[],//意大利
		'SE'=>[],//瑞典
		'HU'=>[],//匈牙利
		**/
	
	];
	
	public static function getTranslateMapping(){
		return self::$TRANSLATE_MAPPING;
	}
	
	public static function getToNationLanguage($country_code){
		$Language=array(
			'EN'=>[],
			'CN'=>['CN'],
			'FR'=>['FR','MC','TD','RW','TG','GN','ML','BF','CG','CM','BJ','NE','BI','SN','DJ','MG','KM','HT','VU','TN','MA'],
			'DE'=>['DE','LI','AT','BE','LU','CH'],
			'RU'=>['RU','KZ','BY','KG'],
			'ES'=>['ES','AR','BO','CL','CO','CR','CU','DO','EC','SV','GQ','GT','HN','MX','NI','PA','PY','PE','UY','VE'],
		);
		$result='EN';
		foreach ($Language as $lang=>$countrys){
			foreach ($countrys as $code){
				if(strtoupper($country_code) == $code){
					return $lang;
				}
				
			}
		}
		return $result;
	}
	/**
	 * 通过puid和order_id获取指定用户订单的tracking信息
	 * @params	string	$puid
	 * 			string	$track_id
	 * @return	array
	 */
	public static function getTrackingInfo($puid,$track_id){
		$rtn=array();
		$rtn['errorMsg']='';
		$rtn['data']=array();
		if(!true){
			$rtn['errorMsg'] = 'Database check failure';
			return $rtn;
		}else{
			$tracking = Tracking::findOne(['id'=>$track_id]);
			if($tracking==null){
				$rtn['errorMsg'] = 'This order have no tracking recoder';
				return $rtn;
			}else{
				$rtn['data']=$tracking;
			}
		}
		return $rtn;
	}
	
	/**
	 * 通过puid和order_id获取指定用户订单的tracking信息
	 * @params	string	$puid
	 * 			string	$order_id
	 * @return	array
	 * @author	zhl		2015/7/18		初始化
	 */
	public static function getOrderAndTrackingInfo($puid,$order_id){
		$rtn=array();
		$rtn['errorMsg']='';
		$rtn['data']=array();
		if(!true){
			$rtn['errorMsg'] = 'Database check failure';
			return $rtn;
		}else{
			$order=OdOrder::find()->where(['order_id'=>$order_id])->asArray()->one();
			if(empty($order)){
				$rtn['errorMsg'] = 'This order have no order recoder';
				return $rtn;
			}
			$rtn['order']=$order;
			$tracking = Tracking::find()->where(['order_id'=>$order['order_source_order_id']])->one();
			if($tracking==null){
				$rtn['errorMsg'] = 'This order have no tracking recoder';
				return $rtn;
			}else{
				$rtn['data']=$tracking;
			}
		}
		return $rtn;
	}
	
	/**
	 * 当推荐商品页面show某页推荐商品时，增加它们的life_view_count
	 * @params	array	$puid
	 * 			array	$idsArray
	 * @return	array
	 */
	public static function addViewCountByIds($puid , $idsArray){
		$result['success'] = true;
		$result['message'] = '';
		
		if(!true){
			$result['success'] = false;
			$result['message'] = 'Database check failure';
			return $result;
		}else{
			if(!empty($idsArray)){
				foreach ($idsArray as $id){
					$recommendModel = CsRecommendProduct::findOne(['id'=>$id]);
					if($recommendModel<>null){
						$recommendModel->life_view_count = $recommendModel->life_view_count+1;
						$recommendModel->update_time = date('Y-m-d H:i:s');
						if(!$recommendModel->save()){
							$result['success'] = false;
							foreach ($recommendModel->errors as $k => $anError){
								$result['message'] .= $k.":".$anError[0]."<br>";
							}
						}
					}else{
						$result['success'] = false;
						$result['message'] .= 'have not this CsRecommendProduct:id='.$id."<br>";
					}
					$theday = date('Y-m-d');
					$cs_recm = CsRecmProductPerform::find()
								->where(['product_id'=>$id,'theday'=>$theday])->one();
					if($cs_recm==null){
						$cs_recm = new CsRecmProductPerform();
						$cs_recm->product_id = $id;
						$cs_recm->theday = $theday;
						$cs_recm->view_count = 1;
						$cs_recm->click_count = 0;
					}else{
						$cs_recm->view_count = $cs_recm->view_count+1;
					}
					if(!$cs_recm->save()){
						$result['success'] = false;
						foreach ($cs_recm->errors as $k => $anError){
							$result['message'] .= $k.":".$anError[0]."<br>";
						}
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * 当推荐商品页面某推荐商品被点击时，增加它们的life_click_count
	 * @params	array	$puid
	 * 			array	$id
	 * @return	array
	 */
	public static function addClickCountById($puid , $id){
		$result['success'] = true;
		$result['message'] = '';
	
		if(!true){
			$result['success'] = false;
			$result['message'] = 'Database check failure';
			return $result;
		}else{
			$recommendModel = CsRecommendProduct::findOne(['id'=>$id]);
			if($recommendModel<>null){
				$recommendModel->life_click_count = $recommendModel->life_click_count+1;
				$recommendModel->update_time = date('Y-m-d H:i:s');
				if(!$recommendModel->save()){
					$result['success'] = false;
					foreach ($recommendModel->errors as $k => $anError){
						$result['message'] .= $k.":".$anError[0]."<br>";
					}
				}
			}else{
				$result['success'] = false;
				$result['message'] .= 'have not this CsRecommendProduct:id='.$id."<br>";
			}
			$theday = date('Y-m-d');
			$cs_recm = CsRecmProductPerform::find()
							->where(['product_id'=>$id,'theday'=>$theday])->one();
			if($cs_recm==null){
				$cs_recm = new CsRecmProductPerform();
				$cs_recm->product_id = $id;
				$cs_recm->theday = $theday;
				$cs_recm->view_count = 0;
				$cs_recm->click_count = 1;
			}else{
				$cs_recm->click_count = $cs_recm->click_count+1;
			}
			if(!$cs_recm->save()){
				$result['success'] = false;
				foreach ($cs_recm->errors as $k => $anError){
					$result['message'] .= $k.":".$anError[0]."<br>";
				}
			}
		}
		return $result;
	}
	
	/**
	 * 获取给定平台,店铺,对应国家  的所有  匹配规则
	 * @params	string	$platform
	 * 			string	$account
	 *			string	$nation
	 * @return	array
	 */
	public static function getAllTrackerAuotRules($platform, $account, $nation){
		$matchRules = [];
		//模糊查询数据
		$query = AutoRoles::find()
				->where(['like','platform',$platform])
				->andWhere(['like','accounts',$account])
				->andWhere(['like','nations',$nation])
				->orderBy('priority ASC')
				->asArray()
				->all();
		//从模糊查询的结果中再验证
		foreach ($query as $rule){
			$platforms = empty($rule['platform'])?array():json_decode($rule['platform'],true);
			if(!in_array($platform, $platforms))
				break;
			$accounts = empty($rule['accounts'])?array():json_decode($rule['accounts'],true);
			if(!in_array($account, $accounts))
				break;
			$nations = empty($rule['nations'])?array():json_decode($rule['nations'],true);
			if(!in_array($nation, $nations))
				break;
			
			$matchRules[] = $rule;
		}
		return $matchRules;
	}
	
	/*
	 * 根据条件获取用户自定义商品分组信息
	 * @author		lzhl		2016/7/19		初始化
	 */
	public static function getCustomizedRecommendedGroupByPlatform($puid,$platform='',$seller_id=''){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		$query = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid]);
		if(!empty($platform))
			$query->andWhere(['platform'=>$platform]);
		if(!empty($seller_id))
			$query->andWhere(['seller_id'=>$seller_id]);
		$groups = $query->asArray()->all();
		return $groups;
	}
	
	/*
	 * 根据条件获取用户自定义商品分组对应的推荐商品信息
	* @author		lzhl		2016/7/19		初始化
	*/
	public static function getUserRecommendedProdByGroupId($puid,$id){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		if(empty($id))
			return [];
		$groupProds = LtCustomizedRecommendedProd::find(['puid'=>$puid,'group_id'=>$id])->asArray()->All();
		return $groupProds;
	}
}
