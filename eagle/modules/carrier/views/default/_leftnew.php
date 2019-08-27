<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
//use eagle\modules\order\models\OdOrder;
$active='';
$menu = [
	TranslateHelper::t('物流业务流程')=>[
	'icon'=>'icon-stroe',
	'items'=>[
				TranslateHelper::t('待上传')=>[
						'url'=>Url::to(['/carrier/default/waitingpost']),
						 //'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
						],
				TranslateHelper::t('待交运')=>[
						'url'=>Url::to(['/carrier/default/waitingdelivery']),
						//'tabbar'=>$counter[OdOrder::STATUS_PAY]
					],		
				TranslateHelper::t('已交运')=>[
						'url'=>Url::to(['/carrier/default/deliveryed']),
						 //'tabbar'=>$counter[OdOrder::STATUS_WAITSEND]
					],
				TranslateHelper::t('已完成')=>[
						'url'=>Url::to(['/carrier/default/completed']),
						//'tabbar'=>$counter[OdOrder::STATUS_SHIPPED]
					],
			],
			
		],
	TranslateHelper::t('物流设置')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('物流设置已转移到<br> &nbsp;&nbsp; 设置=》物流设置')=>[
					'url'=>Url::to(['/configuration/carrierconfig']),
							//'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
							],
				],
			],
		];

		if(@$this->context->action->id=='waitingpost'){
			$active=TranslateHelper::t('待上传');
		}
		if(@$this->context->action->id=='waitingdelivery'){
			$active=TranslateHelper::t('待交运');
		}
		if(@$this->context->action->id=='deliveryed'){
			$active=TranslateHelper::t('已交运');
		}
		if(@$this->context->action->id=='completed'){
			$active=TranslateHelper::t('已完成');
		}	
		echo $this->render('//layouts/new/left_menu',[
				'menu'=>$menu,
				'active'=>$active
				]);

?>
