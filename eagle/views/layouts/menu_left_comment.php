<?php
use eagle\helpers\MenuHelper;

$menu = [
	[
		'name'=>'手动批量好评',
		'icon'=>'list-alt',
		'action'=>'comment/index',
		'items'=>[],
	],
	[
		'name'=>'好评记录',
		'icon'=>'heart-empty',
		'action'=>'comment/log',
		'items'=>[],
	],
	[
		'name'=>'设置',
		'icon'=>'cog',
		'items'=>[
			[
				'name'=>'好评模板',
				'action'=>'comment/template'
			],
			[
				'name'=>'自动好评',
				'action'=>'comment/rule',
				'badge'=>0
			]
		]
	],
];
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
