<?php

use yii\helpers\Html;
?>
<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/inventory/_menu', ['class_html' => '']) ?>
	<!-- 右侧内容区域 -->
    <?php 
		//判断子账号是否有权限查看，lrq20170829
		if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('warehouse_setting')){?>
			<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
				<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
			</div>
	<?php return;}?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	<?= $this->render('selfWarehouseList',
		['warehouseList' => $warehouseList
		]) ?>
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>