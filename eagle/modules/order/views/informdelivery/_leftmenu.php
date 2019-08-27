<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;

$customConditionArray = [
TranslateHelper::t('所有订单')=>[
	'url'=>Url::to(['/order/aliexpressorder/aliexpresslist'])."?menu_select=all"
],
];
if (!empty($counter['custom_condition'])){
	$sel_custom_condition = array_merge(['加载常用删选'] , array_keys($counter['custom_condition']));
	$tmpCondition = array_flip($sel_custom_condition);
	foreach($counter['custom_condition'] as $custom_condition_name=>$thisCondition):		
		$thisToUrl = Url::to(['/order/aliexpressorder/aliexpresslist']);
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
$menu = [
	TranslateHelper::t('速卖通业务待处理')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('所有订单')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist'])."?menu_select=all"
				],
				]
	],

	TranslateHelper::t('订单业务流程')=>[
	'icon'=>'icon-stroe',
	'items'=>[
			TranslateHelper::t('同步订单')	=>[
					'url'=>Url::to(['/order/aliexpressorder/order-sync-info']),
					//'tabbar'=>''
				],
			TranslateHelper::t('未付款')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_NOPAY]),
					 'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
					],
			TranslateHelper::t('已付款')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_PAY , 'pay_order_type'=>OdOrder::PAY_PENDING]),
					'tabbar'=>$counter[OdOrder::STATUS_PAY]
				],		
			TranslateHelper::t('发货中')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_WAITSEND]),
					 'tabbar'=>$counter[OdOrder::STATUS_WAITSEND]
				],
			TranslateHelper::t('已完成')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_SHIPPED]),
					//'tabbar'=>$counter[OdOrder::STATUS_SHIPPED]
				],
			TranslateHelper::t('已取消')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_CANCEL]),
					//'tabbar'=>$counter[OdOrder::STATUS_CANCEL]
					],
			TranslateHelper::t('暂停发货')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_SUSPEND]),
					'tabbar'=>$counter[OdOrder::STATUS_SUSPEND]
				],
			TranslateHelper::t('缺货')=>[
					'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_OUTOFSTOCK]),
					'tabbar'=>$counter[OdOrder::STATUS_OUTOFSTOCK]
				],
			]
		],
		

        TranslateHelper::t('通知平台发货')=>[
            'icon'=>'icon-stroe',
            'items'=>[
                TranslateHelper::t('未通知平台发货')=>[
//                    'qtipkey'=>'tracker_setting_platform_binding',
                    'url'=>Url::to(['/order/informdelivery/noinformdelivery?shipping_status='.OdOrder::NO_INFORM_DELIVERY.'&is_exsit_tracking_number_order='.OdOrder::NO_TRACKING_NUMBER_ORDER]),
                   'tabbar'=>$counter['shipping_status_0']
                ],
                TranslateHelper::t('通知平台发货中')=>[
//                    'qtipkey'=>'tracker_setting_platform_binding',
                    'url'=>Url::to(['/order/informdelivery/processinformdelivery']),
                   'tabbar'=>$counter['shipping_status_2']
                ],
                TranslateHelper::t('已通知平台发货')=>[
//                    'qtipkey'=>'tracker_setting_platform_binding',
                    'url'=>Url::to(['/order/informdelivery/alreadyinformdelivery?shipping_status='.OdOrder::ALREADY_INFORM_DELIVERY]),
                   'tabbar'=>$counter['shipping_status_1']
                ],
            ]
        ],


		];

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
		echo $this->render('//layouts/new/left_menu',[
				'menu'=>$menu,
				'active'=>$active
				]);

?>
