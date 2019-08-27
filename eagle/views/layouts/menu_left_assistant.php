<?php
use eagle\helpers\MenuHelper;

$menu = [
	[
		'name'=>'催款设置',
		'icon'=>'cog',
		'items'=>[
			[
				'name'=>'催款规则列表',
				'action'=>'rule/list'
			],
//			[
//				'name'=>'店铺催款启用设置',
//				'action'=>'rule/enable',
//				'badge'=>0
//			],
			[
				'name'=>'催款模板设置',
				'action'=>'template/list',
			]
		]
	],
	[
		'name'=>'催款记录',
		'icon'=>'th-list',
		'items'=>[
			[
				'name'=>'催款记录',
				'action'=>'due/list'
			]
		]
	],
	[
		'name'=>'统计信息',
		'icon'=>'th-list',
		'items'=>[
			[
				'name'=>'店铺统计',
				'action'=>'due/byshop'
			],
			[
				'name'=>'规则统计',
				'action'=>'due/dueinfo'
			]
		]
	]
];

?>
<!-- 左侧标签快捷区域 -->
<div id="sidebar" style="width:205px;">
	<?php 
	foreach($menu as $i){
		echo MenuHelper::left_menu_ul($i);
	}
	?>
</div>
