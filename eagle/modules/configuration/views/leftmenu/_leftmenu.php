<?php
use eagle\helpers\MenuHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;


$menu = [
     // 常用搜索条件基本已经屏蔽了 这里都屏蔽
// 	'全局设置'=>[
// 		'icon'=>'icon-shezhi',
// // 		'qtipkey'=>'how-purchasesug-work',
// 		'items'=>[
// 			'常用搜索条件管理'=>[
// 				'url'=>'/configuration/carrierconfig/searchlist',
// 			],
// 		],
// 	],
	'OMS设置'=>[
			'icon'=>'icon-shezhi',
// 			'qtipkey'=>'how-purchasesug-work',
			'items'=>[
				'导出订单样式设置'=>[
					'url'=>'/configuration/elseconfig/excel-model-list',
				],
// 				'评价范本设置'=>[
// 					'url'=>'/configuration/elseconfig/feedback-template-list',
// 				],
// 				'Paypal订单同步设置'=>[
// 					'url'=>'/docs/paypalHelper.html" target="_blank',
// 				],
				'oms习惯设置'=>[
					'url'=>'/configuration/elseconfig/oms-set',
				],
			],
		],
// 	'营销推广设置'=>[
// 		'icon'=>'icon-shezhi',
// 		//'qtipkey'=>'',
// 		'items'=>[
// 			'推广商品设置'=>[
// 				'url'=>'/tracking/tracking-recommend-product/custom-product-list',
// 				'target'=>'_blank',
// 				//'qtipkey'=>'',
// 			],
// 			'推广商品分组设置'=>[
// 				'url'=>'/tracking/tracking-recommend-product/group-list',
// 				'target'=>'_blank',
// 				//'qtipkey'=>'',
// 			],
// 			'发信模板设置'=>[
// 				'url'=>'/tracking/tracking/mail_template_setting',
// 				'target'=>'_blank',
// 				//'qtipkey'=>'',
// 			],
// 		],
// 	],
	'仓库设置'=>[
		'icon'=>'icon-shezhi',
// 		'qtipkey'=>'how-purchasesug-work',
		'items'=>[
			'自营仓库'=>[
				'url'=>'/configuration/warehouseconfig/self-warehouse-list',
			],
			'仓库习惯设置'=>[
				'url'=>'/configuration/warehouseconfig/customsettings',
			],
// 			'海外仓'=>[
// 				'url'=>'/configuration/warehouseconfig/oversea-warehouse-list',
// 			],
// 			'仓库分配规则'=>[
// 				'url'=>'/configuration/warehouseconfig/warehouse-rule-list',
// 			],
		],
	],
	'物流设置'=>[
		'icon'=>'icon-shezhi',
// 		'qtipkey'=>'how-purchasesug-work',
		'items'=>[
	        '物流商设置'=>[
	                'url'=>'/configuration/carrierbackstage/index',
	        ],
			'选择运输服务'=>[
				'url'=>'/configuration/carrierconfig/index',
			],
			'运输服务匹配规则'=>[
				'url'=>'/configuration/carrierconfig/rule',
			],
			'物流标签自定义'=>[
				'url'=>'/configuration/carrierconfig/carrier-custom-label-list',
			],
			'物流标签自定义(新)'=>[
				'url'=>'/configuration/carrierconfig/carrier-custom-label-list-new',
			],
			'地址管理'=>[
				'url'=>'/configuration/carrierconfig/commonaddresslist',
			],
			'物流模块习惯设置'=>[
				'url'=>'/configuration/carrierconfig/carrier-module-custom-setting-list',
			],
			'常用报关信息'=>[
				'url'=>'/configuration/carrierconfig/common-declared-info',
			],
		]
	],
	'发货设置'=>[
			'icon'=>'icon-shezhi',
// 			'qtipkey'=>'how-purchasesug-work',
			'items'=>[
					'发货习惯设置'=>[
					'url'=>'/configuration/deliveryconfig/customsettings',
				],
			]
		],
	'商品设置'=>[
			'icon'=>'icon-shezhi',
// 			'qtipkey'=>'how-purchasesug-work',
			'items'=>[
				'SKU解析规则'=>[
						'url'=>'/configuration/productconfig/index',
			],
		]
	],
];

//假如没有使用过则屏蔽
if(\eagle\modules\carrier\helpers\CarrierOpenHelper::isExistCrtemplateOld() == false){
	unset($menu['物流设置']['items']['物流标签自定义']);
}

$isMain = UserApiHelper::isMainAccount();
if(!$isMain){
	unset($menu['物流设置']['items']['物流商设置']);
}

//oms设置
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('oms_setting')){
	unset($menu['OMS设置']);
}
//仓库设置
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('warehouse_setting')){
	unset($menu['仓库设置']);
}
//物流发货设置
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){
	unset($menu['物流设置']);
	unset($menu['发货设置']);
}
//商品设置
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('catalog_setting')){
	unset($menu['商品设置']);
}

$active = '';
foreach ($menu as $mone){
	if(!empty($active)) break;
	foreach ($mone['items'] as $name=>$one){
		if(Yii::$app->request->getUrl() == $one['url']){
			$active=TranslateHelper::t($name);
			break;
		}
	}
}
$puid = \Yii::$app->user->identity->getParentUid();
$puid = (int)$puid;
if( $puid!==1 && $puid!==297){
	unset($menu['营销推广设置']);
}
echo $this->render('//layouts/new/left_menu_2',[
		'menu'=>$menu,
		'active'=>$active,
		'style' => array('line-height'=>'32px'),
		]);
?>