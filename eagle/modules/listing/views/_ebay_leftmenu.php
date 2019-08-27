<?php
	$menu = [
		'模块'=>[
			'icon'=>'icon-moban',
			'items'=>[
				'商品信息模板'=>['url'=>'/listing/ebaymuban/templatelist',],
				'销售信息'=>['url'=>'/listing/ebaymuban/salesinfolist',],
				'产品推荐'=>['url'=>'/listing/ebaymuban/crosslist',],
				'汽配兼容'=>['url'=>'/listing/ebaycompatibility/show',],
				'促销规则'=>['url'=>'/listing/ebaypromotion/show',],
				// '模块组合'=>[
				// 	'items'=>[
				// 		'模板设置'=>[],
				// 		'卖家描述'=>[],
				// 		'物品推广'=>[],
				// 		'展示窗'=>[],
				// 	],
				// ],
				// '买家要求'=>[],
				// '物品所在地'=>[],
				// '付款'=>[],
				// '退货政策'=>[],
				// '运送选项'=>[],
				// '不运送地区'=>[],
				// '广告特色'=>[],
				// '折扣'=>[],
			]
		],
		'范本'=>[
			'icon'=>'icon-mobanguanli',
			'items'=>[
				'所有范本'=>[
					'url'=>'/listing/ebaymuban/list',
				],
				// '所有范本'=>[],
				// '有在线刊登'=>[],
				// '无在线刊登'=>[],
				// '定时'=>[],
				// '未定时'=>[],
				// '无效'=>[],
				// '标签'=>[],
				// '封存'=>[],
				// '回收站'=>[],
				// '清除'=>[],
			]
		],
		'刊登'=>[
			'icon'=>'icon-fabu',
			'items'=>[
				'在线'=>['url'=>'/listing/ebayitem/list',],
				'已下线'=>['url'=>'/listing/ebayitem/listend',],
				// '在线'=>[],
				// '已售'=>[],
				// '未卖出'=>[
				// 	'items'=>[
				// 		'符合退费物品'=>[],
				// 		'其他'=>[],
				// 	]
				// ],
				// 'eBay删除'=>[],
				// '刊登历史'=>[],
				// '标签'=>[],
				// '回收站'=>[],
			]
		],
		'自动功能'=>[
			'icon'=>'icon-moban',
			'items'=>[
				'自动补货'=>['url'=>'/listing/ebay-auto-inventory/index'],
				'定时刊登'=>['url'=>'/listing/ebay-auto-timer-listing/index'],
				// '持续在线'=>[],
			]
		],

		// '定时'=>[
		// 	'icon'=>'icon-jiance',
		// 	'items'=>[
		// 		'定时队列'=>['url'=>'/listing/additemset/list',],
		// 		'刊登失败'=>['url'=>'/listing/additemset/loglist',],
		// 		// '定时规则'=>[],
		// 		// '预刊登队列'=>[],
		// 		// '刊登监察'=>[],
		// 	]
		// ],
		// '补充与重新刊登'=>[
		// 	'icon'=>'icon-moban',
		// 	'items'=>[
		// 		'补充与重新刊登规则'=>[],
		// 		'重新刊登队列'=>[],
		// 		'重新刊登监察'=>[],
		// 		'自动补充监察'=>[],
		// 	]
		// ],
		// '账户管理'=>[
		// 	'icon'=>'icon-dianpu',
		// 	'items'=>[
		// 		'账户信息'=>[
		// 			'url'=>'/listing/ebaystorecategory/liststorecategory',
		// 		],
		// 		'账户绑定管理'=>[
		// 			'url'=>'/listing/ebaystorecategory/bindaccountmap',
		// 		],
		// 	]
		// ],
	];

	$style['line-height'] = '30px';

	echo $this->render('//layouts/new/left_menu_2',[
		'menu'=>$menu,
		'style'=>$style,
		'active'=>$active
	]);
?>	