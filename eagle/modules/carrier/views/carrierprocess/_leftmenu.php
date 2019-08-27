<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\models\OdOrder;

$counter = [];
$carrier_type = OdOrder::$carrier_type;
$active='';
//未匹配运输服务
$ods_tmp = OdOrder::find()
->where('order_status = '.OdOrder::STATUS_WAITSEND)
->andWhere('(default_warehouse_id < 0 or default_shipping_method_code="")');
//->andWhere('length(default_shipping_method_code)=0');
$counter['nomatchship']['all'] = $ods_tmp->count();
foreach ($carrier_type as $key=>$value){
	$allcarriers = [];
	switch ($key){
		case 1:$allcarriers = array_keys(CarrierApiHelper::getCarrierList(2,-1));break;//api
		case 2:$allcarriers = array_keys(CarrierApiHelper::getCarrierList(3,-1));break;//excel
		case 3:$allcarriers = array_keys(CarrierApiHelper::getCarrierList(4,-1));break;//trackno
	}
	$carrierprocess_carrier_step = OdOrder::$carrierprocess_carrier_step;
	foreach ($carrierprocess_carrier_step[$value] as $k=>$v){
		if ($v['value'] == OdOrder::CARRIER_FINISHED){
			$ods_tmp = OdOrder::find();
			$ods_tmp->where('order_status = '.OdOrder::STATUS_SHIPPED);
			$ods_tmp->andWhere(['default_carrier_code'=>$allcarriers]);
			$ods_tmp->andWhere('(default_warehouse_id > -1 and default_shipping_method_code<>"")');
		}else {
			$ods_tmp = OdOrder::find()->where('order_status = '.OdOrder::STATUS_WAITSEND);
			$ods_tmp->andWhere(['default_carrier_code'=>$allcarriers]);
			$ods_tmp->andWhere(['carrier_step'=>$v['value']]);
			$ods_tmp->andWhere('(default_warehouse_id > -1 and default_shipping_method_code<>"")');
		}
 		/* $command = $ods_tmp->createCommand();
  		print_r($command->getRawSql()); */
// 		echo '<br><br>';
// 		print_r($v['value']);
// 		echo $k.'<br><br>';
		$tmp_count = $ods_tmp->count();
		$counter[$value][$k]=$tmp_count;
		
		//统计
// 		if(!isset($counter[$value]['all'])){
// 			$counter[$value]['all'] = 0;
// 		}
// 		$counter[$value]['all'] += $tmp_count;
	}
}
// print_r($counter);
$menu = [
// 	TranslateHelper::t('待指定')=>[
// 		'icon'=>'icon-stroe',
// 		'items'=>[
// 			TranslateHelper::t('未指定运输服务')=>[
// 				'url'=>Url::to(['/carrier/carrierprocess/waitingmatch']),
// 				'tabbar'=>$counter['nomatchship']['all']
// 			],
// 		],
// 	],
	TranslateHelper::t('接口对接')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('待上传')=>[
						'url'=>Url::to(['/carrier/carrierprocess/waitingpost']),
						'tabbar'=>$counter['api']['UPLOAD']
						],
				TranslateHelper::t('待交运')=>[
						'url'=>Url::to(['/carrier/carrierprocess/waitingdelivery']),
						'tabbar'=>$counter['api']['DELIVERY']
					],		
				TranslateHelper::t('已交运')=>[
						'url'=>Url::to(['/carrier/carrierprocess/delivered']),
						 'tabbar'=>$counter['api']['DELIVERYED']
					],
				TranslateHelper::t('已完成(接口)')=>[
						'url'=>Url::to(['/carrier/carrierprocess/completed']),
						'tabbar'=>$counter['api']['FINISHED']
					],
		],
	],
	TranslateHelper::t('Excel对接')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('未导出')=>[
				'url'=>Url::to(['/carrier/carrierprocess/excelexport']),
				'tabbar'=>@$counter['excel']['EXPORT']
			],
			TranslateHelper::t('已导出')=>[
				'url'=>Url::to(['/carrier/carrierprocess/excelexported']),
				'tabbar'=>$counter['excel']['EXPORTED']
			],
			TranslateHelper::t('已完成(Excel)')=>[
				'url'=>Url::to(['/carrier/carrierprocess/excelcompleted']),
				'tabbar'=>$counter['excel']['FINISHED']
			],
		],
	],
	TranslateHelper::t('号码池分配')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('未分配')=>[
				'url'=>Url::to(['/carrier/carrierprocess/trackno-export']),
				'tabbar'=>@$counter['trackno']['EXPORT']
			],
			TranslateHelper::t('已分配')=>[
				'url'=>Url::to(['/carrier/carrierprocess/trackno-exported']),
				'tabbar'=>@$counter['trackno']['EXPORTED']
			],
			TranslateHelper::t('已完成')=>[
				'url'=>Url::to(['/carrier/carrierprocess/trackno-completed']),
				'tabbar'=>@$counter['trackno']['FINISHED']
			],
		],
	],
	/* TranslateHelper::t('物流设置')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('物流设置已转移到<br> &nbsp;&nbsp; 设置=》物流设置')=>[
				'url'=>Url::to(['/configuration/carrierconfig']),
							//'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
				],
		],
	], */
];

		if(@$this->context->action->id=='waitingmatch'){
			$active=TranslateHelper::t('未指定运输服务');
		}
		if(@$this->context->action->id=='waitingpost'){
			$active=TranslateHelper::t('待上传');
		}
		if(@$this->context->action->id=='waitingdelivery'){
			$active=TranslateHelper::t('待交运');
		}
		if(@$this->context->action->id=='delivered'){
			$active=TranslateHelper::t('已交运');
		}
		if(@$this->context->action->id=='completed'){
			$active=TranslateHelper::t('已完成(接口)');
		}
		if(@$this->context->action->id=='excelexport'){
			$active=TranslateHelper::t('未导出');
		}
		if(@$this->context->action->id=='excelexported'){
			$active=TranslateHelper::t('已导出');
		}
		if(@$this->context->action->id=='excelcompleted'){
			$active=TranslateHelper::t('已完成(Excel)');
		}
		if(@$this->context->action->id=='trackno-export'){
			$active=TranslateHelper::t('未分配');
		}
		if(@$this->context->action->id=='trackno-exported'){
			$active=TranslateHelper::t('已分配');
		}
		if(@$this->context->action->id=='trackno-completed'){
			$active=TranslateHelper::t('已完成');
		}
		echo $this->render('//layouts/new/left_menu_2',[
				'menu'=>$menu,
				'active'=>$active
				]);

?>
