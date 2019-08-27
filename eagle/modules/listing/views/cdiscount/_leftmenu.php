<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\util\helpers\RedisHelper;

//$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
//$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerJs("cdOffer.list.init()", \yii\web\View::POS_READY);
$active = '';
/*
$customConditionArray = [
	TranslateHelper::t('Cdiscount商品列表')=>[
		'url'=>Url::to(['/listing/cdiscount/index'])
	],
];
if (!empty($counter['custom_condition'])){
	$sel_custom_condition = array_merge(['加载常用筛选'] , array_keys($counter['custom_condition']));
	$tmpCondition = array_flip($sel_custom_condition);
	foreach($counter['custom_condition'] as $custom_condition_name=>$thisCondition):
	$thisToUrl = Url::to(['/order/cdiscount-order/list']);
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
*/
$puid = \Yii::$app->user->identity->getParentUid();

$menu = [
	TranslateHelper::t('Cdiscount商品列表')=>[
		'icon'=>'icon-stroe',
		'items'=>[
				TranslateHelper::t('所有商品')=>[
					'url'=>Url::to(['/listing/cdiscount/index']),
					'tabbar'=>empty($counter['all'])?0:$counter['all'],
				],
				TranslateHelper::t('BestSeller商品')=>[
					'url'=>Url::to(['/listing/cdiscount/index','is_bestseller'=>'Y']),
					'tabbar'=>empty($counter['is_bestseller'])?0:$counter['is_bestseller'],
				],
				TranslateHelper::t('非BestSeller商品')=>[
					'url'=>Url::to(['/listing/cdiscount/index','is_bestseller'=>'N']),
					'tabbar'=>empty($counter['not_bestseller'])?0:$counter['not_bestseller'],
				],
				TranslateHelper::t('非在售商品')=>[
					'url'=>Url::to(['/listing/cdiscount/index','offer_state'=>'Inactive']),
					'tabbar'=>empty($counter['Inactive'])?0:$counter['Inactive'],
				],
			]
	],

	TranslateHelper::t('跟卖终结者')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('爆款')	=>[
					'url'=>Url::to(['/listing/cdiscount/index','focuse_status'=>'H']),
					'tabbar'=>empty($counter['H'])?0:$counter['H'],
				],
			TranslateHelper::t('已关注')	=>[
					'url'=>Url::to(['/listing/cdiscount/index','focuse_status'=>'F']),
					'tabbar'=>empty($counter['F'])?0:$counter['F'],
				],
			TranslateHelper::t('超额导致失效')=>[
				'url'=>Url::to(['/listing/cdiscount/index','t_active'=>'N']),
				'tabbar'=>empty($counter['UT'])?0:$counter['UT'],
				],
			TranslateHelper::t('未关注')=>[
					'url'=>Url::to(['/listing/cdiscount/index','focuse_status'=>'N']),
					'tabbar'=>empty($counter['N'])?0:$counter['N'],
				],
			TranslateHelper::t('已忽略')=>[
					'url'=>Url::to(['/listing/cdiscount/index','focuse_status'=>'I']),
					'tabbar'=>empty($counter['I'])?0:$counter['I'],
				],
			TranslateHelper::t('最近被抢走BestSeller')=>[
					'url'=>Url::to(['/listing/cdiscount/index','lostbs'=>'1']),
					'tabbar'=>empty($counter['lostbs'])?0:$counter['lostbs'],
				],
			]
		],
		
	TranslateHelper::t('导出')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('导出Excel')=>[
			'url'=>Url::to(['/listing/cdiscount/excel-stock-price']),
			//'tabbar'=>empty($counter[OdOrder::STATUS_PAY])?0:$counter[OdOrder::STATUS_PAY]
			],							
		]
	],
	
	TranslateHelper::t('统计')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('每日统计')=>[
				'url'=>Url::to(['/listing/cdiscount/daily-statistics','been_surpassed'=>'Y','date'=>date("Y-m-d",time()-3600*24)]),
			],
			TranslateHelper::t('通知设置')=>[
				'url'=>Url::to(['/listing/cdiscount/setting']),
			],
		]
	],
					
	TranslateHelper::t('Cdiscount订单管理')=>[
		'icon'=>'icon-stroe',
		'items'=>[
			TranslateHelper::t('转到订单管理')=>[
				'url'=>Url::to(['/order/cdiscount-order/list']),
			],
		],
	],
];

$uid = \Yii::$app->user->id;
$user_valid_mail_address = RedisHelper::RedisGet('user_valid_mail_address', 'cd_terminator_uid_'.$uid);
if(empty($user_valid_mail_address)){
	$menu[TranslateHelper::t('统计')]['items'][TranslateHelper::t('通知设置')]['icon'] = 'icon-jinggao';
}

if(@$this->context->action->id=='index'){
	if (empty($_REQUEST['focuse_status']) && isset($_REQUEST['is_bestseller']) && $_REQUEST['is_bestseller']=='Y'){
		$active=TranslateHelper::t('BestSeller商品');
	}
	elseif (empty($_REQUEST['focuse_status']) && isset($_REQUEST['is_bestseller']) && $_REQUEST['is_bestseller']=='N'){
		$active=TranslateHelper::t('非BestSeller商品');
	}
	elseif (empty($_REQUEST['focuse_status']) && isset($_REQUEST['offer_state']) && $_REQUEST['offer_state']=='Inactive'){
		$active=TranslateHelper::t('非在售商品');
	}
	elseif (isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='F'){
		$active=TranslateHelper::t('已关注');
	}
	elseif (isset($_REQUEST['t_active']) && $_REQUEST['t_active']=='N'){
		$active=TranslateHelper::t('超额导致失效');
	}
	elseif (isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='N'){
		$active=TranslateHelper::t('未关注');
	}
	elseif (isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='I'){
		$active=TranslateHelper::t('已忽略');
	}
	elseif (isset($_REQUEST['focuse_status']) && $_REQUEST['focuse_status']=='H'){
		$active=TranslateHelper::t('爆款');
	}elseif(!empty($_REQUEST['lostbs'])){
		$active=TranslateHelper::t('最近被抢走BestSeller');
	}
	else{
		$active=TranslateHelper::t('所有商品');
	}
}
if (@$this->context->action->id=='excel-stock-price'){
	$active = TranslateHelper::t('导出Excel');
}
if (@$this->context->action->id=='daily-statistics'){
	$active = TranslateHelper::t('每日统计');
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