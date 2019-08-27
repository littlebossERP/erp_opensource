<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\delivery\helpers\DeliveryHelper;
$active = '';
$customConditionArray = [
TranslateHelper::t('所有订单')=>[
	'url'=>Url::to(['/order/rumall-order/list'])."?menu_select=all"
],
];
if (!empty($counter['custom_condition'])){
	$sel_custom_condition = array_merge(['加载常用筛选'] , array_keys($counter['custom_condition']));
	$tmpCondition = array_flip($sel_custom_condition);
	foreach($counter['custom_condition'] as $custom_condition_name=>$thisCondition):		
		$thisToUrl = Url::to(['/order/rumall-order/list']);
		$thisToUrl .='?sel_custom_condition='.$tmpCondition[$custom_condition_name].'&'. http_build_query($thisCondition);
		
		$customConditionArray [$custom_condition_name]  = [ 'url'=>$thisToUrl];
		if (isset($_REQUEST['sel_custom_condition']) ){
			if(@$_REQUEST['sel_custom_condition']==$tmpCondition[$custom_condition_name]){
				$active=$custom_condition_name;
			}
		}else if(@$_REQUEST['custom_condition_name']==$custom_condition_name){
			$active=$custom_condition_name;
		}			
	 endforeach;
}

$deliveryMenu = DeliveryHelper::getDeliveryMenuByPlatform('rumall');

$menu = [
	TranslateHelper::t('Rumall业务待处理')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('所有订单')=>[
					'url'=>Url::to(['/order/rumall-order/list','menu_select'=>'all'])
				],
// 				TranslateHelper::t('纠纷中的订单')=>[
// 					'url'=>Url::to(['/order/rumall-order/list','issuestatus'=>'IN_ISSUE']),
// 					'tabbar'=>empty($counter['issueorder'])?0:$counter['issueorder']
// 				],
			]
	],

	TranslateHelper::t('订单业务流程')=>[
	'icon'=>'icon-stroe',
	'items'=>[
			TranslateHelper::t('已付款')=>[
					'url'=>Url::to(['/order/rumall-order/list','order_status'=>OdOrder::STATUS_PAY]),
					'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
				],		
			TranslateHelper::t('发货中')=>[
// 					'url'=>DeliveryHelper::getDeliveryModuleUrl('rumall'),
// 					'target'=>'_blank',
// 					'qtipkey'=>'@oms_faHuoZhong',
			        'tabbar'=>$counter[OdOrder::STATUS_WAITSEND],
    			    'url'=>'#',
    			    'items'=>$deliveryMenu,
				],
			TranslateHelper::t('已完成')=>[
					'url'=>Url::to(['/order/rumall-order/list','order_status'=>OdOrder::STATUS_SHIPPED]),
					'tabbar'=>empty($counter[OdOrder::STATUS_SHIPPED])?0:$counter[OdOrder::STATUS_SHIPPED]
				],
			TranslateHelper::t('已取消')=>[
					'url'=>Url::to(['/order/rumall-order/list','order_status'=>OdOrder::STATUS_CANCEL]),
					'tabbar'=>empty($counter[OdOrder::STATUS_CANCEL])?0:$counter[OdOrder::STATUS_CANCEL]
					],
			TranslateHelper::t('暂停发货')=>[
					'url'=>Url::to(['/order/rumall-order/list','order_status'=>OdOrder::STATUS_SUSPEND]),
					'tabbar'=>empty($counter[OdOrder::STATUS_SUSPEND])?0:$counter[OdOrder::STATUS_SUSPEND]
				],
			
			TranslateHelper::t('缺货')=>[
					'url'=>Url::to(['/order/rumall-order/list','order_status'=>OdOrder::STATUS_OUTOFSTOCK]),
					'tabbar'=>empty($counter[OdOrder::STATUS_OUTOFSTOCK])?0:$counter[OdOrder::STATUS_OUTOFSTOCK]
				],
			
				/*
			TranslateHelper::t('挂起')=>[
				'url'=>Url::to(['/order/rumall-order/list','is_manual_order'=>'1']),
				'tabbar'=>empty($counter['guaqi'])?0:$counter['guaqi']
				],
				*/
			]
		],
		
/*速卖通特有，CD不显示
        TranslateHelper::t('通知平台发货')=>[
            'icon'=>'icon-stroe',
            'items'=>[
                TranslateHelper::t('未通知平台发货')=>[
//                    'qtipkey'=>'tracker_setting_platform_binding',
                    'url'=>Url::to(['/order/informdelivery/noinformdelivery?shipping_status='.OdOrder::NO_INFORM_DELIVERY.'&is_exsit_tracking_number_order='.OdOrder::NO_TRACKING_NUMBER_ORDER.'&platform=rumall']),
                   'tabbar'=>empty($counter['shipping_status_0'])?0:$counter['shipping_status_0']
                ],
                TranslateHelper::t('通知平台发货中')=>[
//                    'qtipkey'=>'tracker_setting_platform_binding',
                    'url'=>Url::to(['/order/informdelivery/processinformdelivery'.'?platform=rumall']),
                   'tabbar'=>empty($counter['shipping_status_2'])?0:$counter['shipping_status_2']
                ],
                TranslateHelper::t('已通知平台发货')=>[
//                    'qtipkey'=>'tracker_setting_platform_binding',
                    'url'=>Url::to(['/order/informdelivery/alreadyinformdelivery?shipping_status='.OdOrder::ALREADY_INFORM_DELIVERY.'&platform=rumall']),
                   'tabbar'=>empty($counter['shipping_status_1'])?0:$counter['shipping_status_1']
                ],
            ]
        ],
*/

	];

if(empty($this->title))
    $this->title = "Rumall 订单列表";
    
if(@$this->context->action->id=='order-sync-info'){
	$active=TranslateHelper::t('同步订单');
}	
if (@$_REQUEST['order_status']==OdOrder::STATUS_NOPAY && empty($_REQUEST['custom_condition_name'])){
	$active = TranslateHelper::t('未付款');
}
if(@$_REQUEST['order_status']==OdOrder::STATUS_PAY && empty($_REQUEST['custom_condition_name'])){
	$active=TranslateHelper::t('已付款');
}
if(@$_REQUEST['order_status']==OdOrder::STATUS_WAITSEND && empty($_REQUEST['custom_condition_name'])){
	$active=TranslateHelper::t('发货中');
}
if(@$_REQUEST['order_status']==OdOrder::STATUS_SHIPPED && empty($_REQUEST['custom_condition_name'])){
	$active=TranslateHelper::t('已完成');
}
if(@$_REQUEST['order_status']==OdOrder::STATUS_CANCEL && empty($_REQUEST['custom_condition_name'])){
	$active=TranslateHelper::t('已取消');
}
if(@$_REQUEST['order_status']==OdOrder::STATUS_SUSPEND&& empty($_REQUEST['custom_condition_name'])){
	$active=TranslateHelper::t('暂停发货');
}
/*
if(@$_REQUEST['is_manual_order']=='1' && empty($_REQUEST['custom_condition_name'])){
	$active=TranslateHelper::t('挂起');
}
*/
if(@$_REQUEST['menu_select']== 'all'){
	$active= TranslateHelper::t('所有订单');
}
if(@$_REQUEST['issuestatus']== 'IN_ISSUE'){
	$active= TranslateHelper::t('纠纷中的订单');
}
if(@$this->context->action->id=='noinformdelivery'){
	$active=TranslateHelper::t('未通知平台发货');
}
if(@$this->context->action->id=='processinformdelivery'){
	$active=TranslateHelper::t('通知平台发货中');
}
if(@$this->context->action->id=='alreadyinformdelivery'){
	$active= TranslateHelper::t('已通知平台发货');
}
if (!empty($menu_select_list[@$_REQUEST['menu_select']])){
	$active = $menu_select_list[$_REQUEST['menu_select']];
}
echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$active
]);

?>
<style>
.toggle_menu {
    bottom: 50% !important;
}
</style>