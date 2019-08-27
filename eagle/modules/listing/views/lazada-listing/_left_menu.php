<?php
use eagle\helpers\MenuHelper;

$menu = [
	[
		'name'=>'刊登管理',
		'icon'=>'cog',
		'action'=>'lazada-listing/publish',
		'items'=>[
			[
				'name'=>'待发布',
				'action'=>'lazada-listing/publish'
			],
			[
			'name'=>'发布中',
			'action'=>'lazada-listing/publishing'
			],
			[
			'name'=>'发布失败',
			'action'=>'lazada-listing/publish-fail'
			],
// 			[
// 				'name'=>'发布成功',
// 				'action'=>'lazada-listing/publish-success',
// 				'badge'=>0
// 			]
		]
	],
    [
    'name'=>'商品列表',
    'icon'=>'list-alt',
    'action'=>'lazada/online-product',
    'items'=>[
        [
            'name'=>'在线商品',
				'action'=>'lazada/online-product'
        ],
        [
        'name'=>'下架商品',
				'action'=>'lazada/off-shelf-product',
        'badge'=>0
        ]
    ],
    	],
];
// 这里使用和 好评助手一样的cookie 会影响好评助手左边栏显示，但是由于好评助手 多处的共用代码都使用了这个cookie 全部重写比较麻烦，所以这里就用这个cookie了
$cookie_status = isset($_COOKIE['sidebar-status']) && $_COOKIE['sidebar-status'];
?>
<!-- 左侧标签快捷区域 -->
<div id="sidebar" class="lb-left-menu" style="width:205px;left:<?= $cookie_status?'-215':'0' ?>px;">
	<?php 
	foreach($menu as $i){
		echo MenuHelper::left_menu_ul($i);
	}
	?>
	<div class="resize"><</div>
</div>
<div class="lb-left-menu-min" style="left:<?= $cookie_status?'0':'-60' ?>px;">
	<?php 
	foreach($menu as $i){
		echo MenuHelper::left_menu_ul_min($i);
	}
	?>
	<div class="resize">></div>
</div>