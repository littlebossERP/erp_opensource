<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
?>
<div class="inventory-menu">
<ul  class="nav nav-pills nav-stacked">
	<li role="presentation">
		<a><?= TranslateHelper::t('订单')?></a>
		<ul  class="nav nav-pills nav-stacked">
			<li <?=(yii::$app->controller->action->id == 'list' || yii::$app->controller->action->id =='listdata')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('待发货')?></a></li>
		</ul>
		<?php if ($warehouse->is_oversea == 0 ){?>
		<a><?= TranslateHelper::t('发货流程')?></a>
		<ul  class="nav nav-pills nav-stacked">
			<li <?=yii::$app->controller->action->id == 'waiting-picking'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/waiting-picking','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('等待打印拣货单')?>(<?php echo OdOrder::find()->where(['delivery_status'=>OdOrder::DELIVERY_NOPRINTPICKING,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'picking'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/picking','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('拣货中')?>(<?php echo OdOrder::find()->where(['delivery_status'=>OdOrder::DELIVERY_PICKING,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'complete-picking-list'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/complete-picking-list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('拣货完成')?>(<?php echo OdOrder::find()->where(['delivery_status'=>OdOrder::DELIVERY_COMPLETEPICKING,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'complete-distribution-list'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/complete-distribution-list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('配货完成')?>(<?php echo OdOrder::find()->where(['delivery_status'=>OdOrder::DELIVERY_COMPLETEDISTRIBUTION,'order_status'=>300])->count();?>)</a></li>
		</ul>
		<?php }?>
		<!-- <a><?= TranslateHelper::t('物流操作步骤')?></a>
		<ul  class="nav nav-pills nav-stacked">
		<?php if ($warehouse->is_oversea == 0 ){?>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('未上传订单到物流系统')?>(<?php echo OdOrder::find()->where(['carrier_step'=>0,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已上传订单到物流系统')?>(<?php echo OdOrder::find()->where(['carrier_step'=>1,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已交运')?>(<?php echo OdOrder::find()->where(['carrier_step'=>2,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已获取跟踪号')?>(<?php echo OdOrder::find()->where(['carrier_step'=>3,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已打印物流单')?>(<?php echo OdOrder::find()->where(['carrier_step'=>4,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已取消')?>(<?php echo OdOrder::find()->where(['carrier_step'=>5,'order_status'=>300])->count();?>)</a></li>
		<?php }else{?>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('未上传订单到物流系统')?>(<?php echo OdOrder::find()->where(['carrier_step'=>0,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已上传订单到物流系统')?>(<?php echo OdOrder::find()->where(['carrier_step'=>1,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已交运')?>(<?php echo OdOrder::find()->where(['carrier_step'=>2,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已获取跟踪号')?>(<?php echo OdOrder::find()->where(['carrier_step'=>3,'order_status'=>300])->count();?>)</a></li>
			<li <?=yii::$app->controller->action->id == 'waiting-delivery'?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/delivery/order/list','warehouse_id'=>$warehouse_id])?>"><?= TranslateHelper::t('已取消')?>(<?php echo OdOrder::find()->where(['carrier_step'=>5,'order_status'=>300])->count();?>)</a></li>
		<?php }?>
		</ul> -->
	</li>
</ul>
</div>