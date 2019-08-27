<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;

$active = '';
$uid = \Yii::$app->user->id;
//$userBindingPlatforms = PlatformAccountApi::getAllPlatformBindingSituation($uid);

$url = \Yii::$app->request->hostinfo;

$OdLtMessageMenu = array();
$OdLtMessageMenu['二次营销商品设置'] = array(
		'url'=>$url.'/order/od-lt-message/custom_product_list',
		'qtipkey'=>'custom_recommend_setting',
		'target'=>'_norefresh',
);

$OdLtMessageMenu['二次营销商品分组设置'] = array(
		'url'=>$url.'/order/od-lt-message/group_list',
		'qtipkey'=>'custom_recommend_setting',
		'target'=>'_norefresh',
);


$menu = [
	TranslateHelper::t('Amazon售后推广')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('待发送的邮件')=>[
					'url'=>Url::to(['/amazoncs/amazoncs/quest-list','status'=>'pending-send']),
					//'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
				],		
			TranslateHelper::t('已发送的邮件')=>[
					'url'=>Url::to(['/amazoncs/amazoncs/quest-list','status'=>'sent']),
					//'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
				],
			TranslateHelper::t('已取消/发送失败')=>[
					'url'=>Url::to(['/amazoncs/amazoncs/quest-list','status'=>'F']),
					//'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
				],
			TranslateHelper::t('任务模板')=>[
					'url'=>Url::to(['/amazoncs/amazoncs/template',]),
					//'tabbar'=>empty($counter[OdOrder::STATUS_SHIPPED])?0:$counter[OdOrder::STATUS_SHIPPED]
				],
		],
	],
	TranslateHelper::t('邮箱绑定列表')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('邮箱列表')=>[
				'url'=>Url::to(['/amazoncs/amazoncs/email-list',]),
			],
		],
	],
	TranslateHelper::t('推荐商品设置')=>[
		'icon'=>'icon-stroe',
		'items'=>$OdLtMessageMenu,
	],
	TranslateHelper::t('Review & Feedback')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('查看Review')=>[
				'url'=>Url::to(['/amazoncs/amazoncs/review-list',]),
				//'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
			],
			TranslateHelper::t('查看Feedback')=>[
				'url'=>Url::to(['/amazoncs/amazoncs/feedback-list',]),
				//'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
			],
		],
	],
	TranslateHelper::t('Q&A')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('介绍/帮助')=>[
				'url'=>Url::to(['/amazoncs/amazoncs/helps',]),
			],
			TranslateHelper::t('关于推荐商品')=>[
				'url'=>Url::to(['/amazoncs/amazoncs/about-recommend',]),
			],
		],
	],
];

$uid = \Yii::$app->user->id;

		if(@$this->context->action->id=='quest-list' && @$_REQUEST['status']=='pending-send'){
			$active=TranslateHelper::t('待发送的邮件');
		}	
		elseif(@$this->context->action->id=='quest-list' && @$_REQUEST['status']=='sent'){
			$active = TranslateHelper::t('已发送的邮件');
		}
		elseif(@$this->context->action->id=='quest-list' && @$_REQUEST['status']=='F'){
			$active = TranslateHelper::t('已取消/发送失败');
		}
		elseif(@$this->context->action->id=='template'){
			$active = TranslateHelper::t('任务模板');
		}
		elseif(@$this->context->action->id=='email-list'){
			$active = TranslateHelper::t('邮箱列表');
		}
		elseif(@$this->context->action->id=='statistic'){
			$active = TranslateHelper::t('统计');
		}
		elseif(@$this->context->action->id=='helps'){
			$active = TranslateHelper::t('介绍/帮助');
		}
		elseif(@$this->context->action->id=='review-list'){
			$active = TranslateHelper::t('查看Review');
		}
		elseif(@$this->context->action->id=='feedback-list'){
			$active = TranslateHelper::t('查看Feedback');
		}
		elseif(@$this->context->action->id=='about-recommend'){
			$active = TranslateHelper::t('关于推荐商品');
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