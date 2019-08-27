<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
?>
<div class="panel panel-default">
<div class="panel-heading"><strong><?= TranslateHelper::t('菜单')?></strong></div>
<div class="panel-body">
<strong><?= TranslateHelper::t('订单')?></strong>
<ul class="list-unstyled">
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','order_status'=>''])?>"><?= TranslateHelper::t('全部订单')?></a></li>
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','order_status'=>100])?>"><?= TranslateHelper::t('未付款')?></a></li>
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','order_status'=>200])?>"><?= TranslateHelper::t('已付款')?></a></li>
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','order_status'=>300])?>"><?= TranslateHelper::t('待发货')?></a></li>
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','order_status'=>500])?>"><?= TranslateHelper::t('已发货')?></a></li>
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','order_status'=>600])?>"><?= TranslateHelper::t('已取消')?></a></li>
	<li><a  class="menu_lev2" href="<?= Url::to(['/order/aliexpressorder/aliexpress-list','is_manual_order'=>1])?>"><?= TranslateHelper::t('挂起')?></a></li>
</ul>
</div>
</div>


