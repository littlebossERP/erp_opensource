<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\delivery\helpers\DeliveryHelper;
$active = '';
if (isset($_REQUEST['pos']) && $_REQUEST['pos']=='RSHP'){
	$active=TranslateHelper::t('启运通知');
}
if (isset($_REQUEST['pos']) && $_REQUEST['pos']=='RPF'){
	$active=TranslateHelper::t('到达待取通知');
}
if (isset($_REQUEST['pos']) && $_REQUEST['pos']=='RRJ'){
	$active=TranslateHelper::t('异常退回通知');
}
if (isset($_REQUEST['pos']) && $_REQUEST['pos']=='RGE'){
	$active=TranslateHelper::t('已发货求好评');
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