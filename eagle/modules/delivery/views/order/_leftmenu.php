<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\delivery\models\OdDelivery;
use eagle\modules\inventory\models\Warehouse;
use common\helpers\Helper_Array;
use eagle\modules\inventory\helpers\InventoryHelper;
?>

	
<?php
$active='';
$a =Helper_Array::toHashmap( Warehouse::find()->where('is_active = :is_active and is_oversea = :is_oversea',[':is_active'=>'Y',':is_oversea'=>0])->select(array('warehouse_id','name'))->asArray()->All(),'warehouse_id','name');
$b = Helper_Array::toHashmap( Warehouse::find()->where('is_active = :is_active and is_oversea = :is_oversea',[':is_active'=>'Y',':is_oversea'=>1])->select(array('warehouse_id','name'))->asArray()->All(),'warehouse_id','name');
$items1 = array();
foreach ($a as $id=>$name){
	$items1[TranslateHelper::t($name)] =array(
					'url'=>Url::to(['/delivery/order/listplanceanorder','warehouse_id'=>$id,'delivery_status'=>OdOrder::DELIVERY_PLANCEANORDER]),
					'tabbar'=>$counter[$id]
	);
	if(@$_REQUEST['warehouse_id']==$id){
		$active=TranslateHelper::t($name);
	}
}
$items2 = array();
if (count($b)){
foreach ($b as $id=>$name){
	if(isset($counter[$id])){
		$items2[TranslateHelper::t($name)] =array(
				'url'=>Url::to(['/delivery/order/overseaslistplanceanorder','warehouse_id'=>$id,'delivery_status'=>OdOrder::DELIVERY_PLANCEANORDER]),
				'tabbar'=>$counter[$id]
		);
		if(@$_REQUEST['warehouse_id']==$id || @$_REQUEST['default_warehouse_id']==$id){
			$active=TranslateHelper::t($name);
		}
	}
}
}
if(@$this->context->action->id=='listnodistributionwarehouse'){
	$active=TranslateHelper::t('未指定运输服务');
}else if(@$this->context->action->id=='listalldelivery'){
	$active=TranslateHelper::t('所有发货中订单');
}
$menu = [
		TranslateHelper::t('待指定')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('所有发货中订单')=>[
				'url'=>Url::to(['/delivery/order/listalldelivery']),
								'tabbar'=>$counter['所有发货中']
										],
// 				TranslateHelper::t('未指定运输服务')=>[
// 						//'qtipkey'=>'qtipkey',
// 						'url'=>Url::to(['/delivery/order/listnodistributionwarehouse']),
// 						'tabbar'=>$counter[-1]
// 								],
			]
		],
		TranslateHelper::t('自营仓库')=>[
			'icon'=>'icon-stroe',
			'items'=>$items1
		],
		TranslateHelper::t('第三方仓库')=>[
			'icon'=>'icon-shezhi',
			'items'=>$items2
		],
		];
		echo $this->render('//layouts/new/left_menu_2',[
				'menu'=>$menu,
				'active'=>$active
				]);

?>
