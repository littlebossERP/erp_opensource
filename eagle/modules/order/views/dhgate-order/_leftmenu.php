<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\delivery\helpers\DeliveryHelper;

define('LINK_DHGATE','/order/dhgate-order/list');
$active = '';
$customConditionArray = [
TranslateHelper::t('所有订单')=>[
	'url'=>Url::to([LINK_DHGATE])."?menu_select=all"
],
];

$deliveryMenu = DeliveryHelper::getDeliveryMenuByPlatform('dhgate');

$menu = [
	TranslateHelper::t('DHgate业务待处理')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('所有订单')=>[
					'url'=>Url::to([LINK_DHGATE])."?menu_select=all"
				],
				]
	],

	TranslateHelper::t('订单业务流程')=>[
	'icon'=>'icon-stroe',
	'items'=>[
			// 敦煌有未付款订单，而且有请款接口，但是我们还没有实现接口，所以直接屏蔽未付款状态下的操作界面，只在所有订单显示未付款订单
// 			TranslateHelper::t('未付款')=>[
// 				'url'=>Url::to([LINK_DHGATE,'order_status'=>OdOrder::STATUS_NOPAY]),
// 				 'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
// 			],
			TranslateHelper::t('已付款')=>[
					'url'=>Url::to([LINK_DHGATE,'order_status'=>OdOrder::STATUS_PAY , 'pay_order_type'=>'all']),
					'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
				],
			TranslateHelper::t('发货中')=>[
					'url'=>'#',
					'items'=>$deliveryMenu,
					'tabbar'=>empty($counter[OdOrder::STATUS_WAITSEND])?0:$counter[OdOrder::STATUS_WAITSEND]
				],
			TranslateHelper::t('已完成')=>[
					'url'=>Url::to([LINK_DHGATE,'order_status'=>OdOrder::STATUS_SHIPPED]),
// 					'tabbar'=>$counter[OdOrder::STATUS_SHIPPED]
				],
			TranslateHelper::t('已取消')=>[
					'url'=>Url::to([LINK_DHGATE,'order_status'=>OdOrder::STATUS_CANCEL]),
					'tabbar'=>empty($counter[OdOrder::STATUS_CANCEL])?0:$counter[OdOrder::STATUS_CANCEL]
					],
			TranslateHelper::t('暂停发货')=>[
					'url'=>Url::to([LINK_DHGATE,'order_status'=>OdOrder::STATUS_SUSPEND]),
					'tabbar'=>empty($counter[OdOrder::STATUS_SUSPEND])?0:$counter[OdOrder::STATUS_SUSPEND]
				],
			TranslateHelper::t('缺货')=>[
					'url'=>Url::to([LINK_DHGATE,'order_status'=>OdOrder::STATUS_OUTOFSTOCK]),
					'tabbar'=>empty($counter[OdOrder::STATUS_OUTOFSTOCK])?0:$counter[OdOrder::STATUS_OUTOFSTOCK]
				],
			]
		],

		];

		if(@$this->context->action->id=='order-sync-info'){
			$active=TranslateHelper::t('同步订单');
		}
		// if (@$_REQUEST['order_status']==OdOrder::STATUS_NOPAY && empty($_REQUEST['custom_condition_name'])){
		// 	$active = TranslateHelper::t('未付款');
		// }
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
		if(@$_REQUEST['menu_select']== 'all'){
			$active= TranslateHelper::t('所有订单');
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
		//每个request对应的action
		echo $this->render('//layouts/new/left_menu_2',[
				'menu'=>$menu,
				'active'=>$active
				]);

?>