<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
//use eagle\modules\order\models\OdOrder;
$active='';
$menu = [
	TranslateHelper::t('物流业务流程')=>[
	'icon'=>'icon-stroe',
	'items'=>[
				TranslateHelper::t('待上传至物流商')=>[
						'url'=>Url::to(['/carrier/default/waitingupload']),
						 //'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
						],
				TranslateHelper::t('待重新上传')=>[
						'url'=>Url::to(['/carrier/default/waitingupload']),
						//'tabbar'=>$counter[OdOrder::STATUS_PAY]
				],
				TranslateHelper::t('待交运')=>[
						'url'=>Url::to(['/carrier/default/waitingdispatch']),
						//'tabbar'=>$counter[OdOrder::STATUS_PAY]
					],		
				TranslateHelper::t('待获取跟踪号')=>[
						'url'=>Url::to(['/carrier/default/waitinggettrackingno']),
						 //'tabbar'=>$counter[OdOrder::STATUS_WAITSEND]
					],
				TranslateHelper::t('待打印物流单')=>[
						'url'=>Url::to(['/carrier/default/waitingprint']),
						//'tabbar'=>$counter[OdOrder::STATUS_SHIPPED]
					],
				TranslateHelper::t('待揽收')=>[
						'url'=>Url::to(['/carrier/default/carriercomplete']),
						//'tabbar'=>$counter[OdOrder::STATUS_CANCEL]
						],
				TranslateHelper::t('已揽收')=>[
						'url'=>Url::to(['/carrier/default/carriercomplete']),
						//'tabbar'=>$counter[OdOrder::STATUS_PAY]
				],
				TranslateHelper::t('全部订单')=>[
				'url'=>Url::to(['/carrier/default/index']),
						//'tabbar'=>$counter[OdOrder::STATUS_PAY]
						],
			],
			
		],
		
		];

		if(@$this->context->action->id=='waitingupload'){
			$active=TranslateHelper::t('待上传至物流商');
		}
		if(@$this->context->action->id=='waitingdispatch'){
			$active=TranslateHelper::t('待交运');
		}
		if(@$this->context->action->id=='waitinggettrackingno'){
			$active=TranslateHelper::t('待获取跟踪号');
		}
		if(@$this->context->action->id=='waitingprint'){
			$active=TranslateHelper::t('待打印物流单');
		}
		if(@$this->context->action->id=='carriercomplete'){
			$active=TranslateHelper::t('待揽收');
		}	
		if(@$this->context->action->id=='index'){
			$active=TranslateHelper::t('全部订单');
		}
		echo $this->render('//layouts/new/left_menu',[
				'menu'=>$menu,
				'active'=>$active
				]);

?>
