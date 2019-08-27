<?php
	$menu = [
		'商品采集'=>[
			'icon'=>'icon-moban',
			'items'=>[
				'eBay草稿箱'=>[
					'url'=>'/collect/collect/ebay',
				],
			]
		],
		'Item列表'=>[
			'icon'=>'icon-moban',
			'items'=>[
				'在线Item'=>[
					'url'=>'/listing/ebayitem/list',
					// 'tabbar'=>81,
					// 'qtipkey'=>'',
				],
				'下线Item'=>[
					'url'=>'/listing/ebayitem/listend',
				],
			]
		],
		'范本'=>[
			'icon'=>'icon-mobanguanli',
			'items'=>[
				'刊登范本'=>[
					'url'=>'#',
					'items'=>[
						'范本列表'=>[
							'url'=>'/listing/ebaymuban/list',
						],
						'商品信息模板'=>[
							'url'=>'/listing/ebaymuban/templatelist',
						],
						'销售信息范本'=>[
							'url'=>'/listing/ebaymuban/salesinfolist',
						],
						'产品推荐范本'=>[
							'url'=>'/listing/ebaymuban/crosslist',
						],
					]
				],
				'其他范本'=>[
					'url'=>'#',
					'items'=>[
						'汽配兼容范本'=>[
							'url'=>'/listing/ebaycompatibility/show',
						],
						'促销规则范本'=>[
							'url'=>'/listing/ebaypromotion/show',
						],
					]
				],
			]
		],
// 		'刊登范本'=>[
// 			'icon'=>'icon-mobanguanli',
// 			'items'=>[
// 				'范本列表'=>[
// 					'url'=>'/listing/ebaymuban/list',
// 				],
// 				'商品信息模板'=>[
// 					'url'=>'/listing/ebaymuban/templatelist',
// 				],
// 				'销售信息范本'=>[
// 					'url'=>'/listing/ebaymuban/salesinfolist',
// 				],
// 				'产品推荐范本'=>[
// 					'url'=>'/listing/ebaymuban/crosslist',
// 				],
// 			]
// 		],
		'刊登列表'=>[
			'icon'=>'icon-liebiao',
			'items'=>[
				'定时队列'=>[
					'url'=>'/listing/additemset/list',
						// 'tabbar'=>81,
						// 'qtipkey'=>'',
				],
				'刊登失败'=>[
					'url'=>'/listing/additemset/loglist',
				],
			]
		],
// 		'其他范本'=>[
// 			'icon'=>'icon-yijianxiugaifahuoriqi',
// 			'items'=>[
// 					'汽配兼容范本'=>[
// 							'url'=>'/listing/ebaycompatibility/show',
// 							],
// 					'促销规则范本'=>[
// 							'url'=>'/listing/ebaypromotion/show',
// 							],
// 					]
// 			],
		'账户管理'=>[
			'icon'=>'icon-dianpu',
			'items'=>[
				'账户信息'=>[
					'url'=>'/listing/ebaystorecategory/liststorecategory',
				],
				'账户绑定管理'=>[
					'url'=>'/listing/ebaystorecategory/bindaccountmap',
				],
			]
		],
	];
	echo $this->render('//layouts/new/left_menu_2',[
		'menu'=>$menu,
		'active'=>$active
	]);
?>	
