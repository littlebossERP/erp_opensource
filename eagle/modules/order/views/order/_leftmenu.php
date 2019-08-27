<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
?>
<div id="sidebar">
		<div class="sidebarLv1Title">
			<div id="">
			<span class="egicon-striped-ectangular-blue" style="margin: 1px 5px 0px 0px;"></span>
			<a  class="" href="<?=Url::to(['/order/order/listebay'])?>"><?= TranslateHelper::t('订单管理')?></a></div>
		</div>
		<ul class="ul-sidebar-one">
			<li class="ul-sidebar-li<?=(empty($_REQUEST['order_status'])&&empty($_REQUEST['is_manual_order']))?' active':''?>">
				<span class="sidebarLv2Title"><a class="" href="<?=Url::to(['/order/order/listebay'])?>"><font><?= TranslateHelper::t('全部订单('.$counter['all'].')')?></font></a></span>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('状态')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_NOPAY)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_NOPAY])?>"><font><?= TranslateHelper::t('未付款')?></font><span class="badge"><?=$counter[OdOrder::STATUS_NOPAY] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_PAY)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_PAY])?>"><font><?= TranslateHelper::t('已付款')?></font><span class="badge"><?=$counter[OdOrder::STATUS_PAY] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_WAITSEND)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_WAITSEND])?>"><font><?= TranslateHelper::t('发货中')?></font><span class="badge"><?=$counter[OdOrder::STATUS_WAITSEND] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_SHIPPED)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_SHIPPED])?>"><font><?= TranslateHelper::t('已完成')?></font></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_CANCEL)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_CANCEL])?>"><font><?= TranslateHelper::t('已取消')?></font></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['is_manual_order']=='1')?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','is_manual_order'=>'1'])?>"><font><?= TranslateHelper::t('挂起')?></font><span class="badge"><?=$counter['guaqi'] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_SUSPEND)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_SUSPEND])?>"><font><?= TranslateHelper::t(OdOrder::$status[OdOrder::STATUS_SUSPEND])?></font></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['order_status']==OdOrder::STATUS_OUTOFSTOCK)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','order_status'=>OdOrder::STATUS_OUTOFSTOCK])?>"><font><?= TranslateHelper::t(OdOrder::$status[OdOrder::STATUS_OUTOFSTOCK])?></font></a>
					</li>
					
					
				</ul>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('异常')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li<?=(@$_REQUEST['exception_status']==OdOrder::EXCEP_WAITSEND)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_WAITSEND])?>"><font><?= TranslateHelper::t('可发货')?></font><span class="badge"><?=$counter[OdOrder::EXCEP_WAITSEND] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['exception_status']==OdOrder::EXCEP_HASNOSHIPMETHOD)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_HASNOSHIPMETHOD])?>"><font><?= TranslateHelper::t('未匹配到物流')?></font><span class="badge"><?=$counter[OdOrder::EXCEP_HASNOSHIPMETHOD] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['exception_status']==OdOrder::EXCEP_PAYPALWRONG)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_PAYPALWRONG])?>"><font><?= TranslateHelper::t('Paypal账号不符')?></font><span class="badge"><?=$counter[OdOrder::EXCEP_PAYPALWRONG] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['exception_status']==OdOrder::EXCEP_SKUNOTMATCH)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_SKUNOTMATCH])?>"><font><?= TranslateHelper::t('SKU不存在')?></font><span class="badge"><?=$counter[OdOrder::EXCEP_SKUNOTMATCH] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['exception_status']==OdOrder::EXCEP_NOSTOCK)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_NOSTOCK])?>"><font><?= TranslateHelper::t('库存不足')?></font><span class="badge"><?=$counter[OdOrder::EXCEP_NOSTOCK] ?></span></a>
					</li>
					<li class="ul-sidebar-li<?=(@$_REQUEST['exception_status']==OdOrder::EXCEP_WAITMERGE)?' active':''?>">
						<a class="" href="<?=Url::to(['/order/order/listebay','exception_status'=>OdOrder::EXCEP_WAITMERGE])?>"><font><?= TranslateHelper::t('待合并')?></font><span class="badge"><?=$counter[OdOrder::EXCEP_WAITMERGE] ?></span></a>
					</li>
				</ul>
			</li>
		</ul>
		<div class="sidebarLv1Title">
			<div id="">
			<span class="glyphicon glyphicon-usd toggleMenuL" style="margin: 1px 5px 0px 0px;"></span>
			<a  class="" href="<?=Url::to(['/order/bestoffer/list'])?>"><?= TranslateHelper::t('议价管理')?></a></div>
		</div>
		<div class="sidebarLv1Title">
			<div id="">
			<span class="glyphicon glyphicon-paperclip toggleMenuL" style="margin: 1px 5px 0px 0px;"></span>
			<a  class="" href="<?=Url::to(['/order/logshow/list'])?>"><?= TranslateHelper::t('操作历史')?></a></div>
		</div>
		<hr>
		<div class="sidebarLv1Title">
			<div>
			<span class="egicon-binding" style="margin: 3px 5px 0px 0px;"></span>
			<?= TranslateHelper::t('操作')?></div>
		</div>
		<ul  class="ul-sidebar-one">
			<li class="ul-sidebar-li"><a class="" href="<?=Url::to(['/order/excel/excel-model-list'])?>" target="_blank"> <font><?= TranslateHelper::t('导出订单样式设置')?></font></a></li>
		</ul>
		<ul  class="ul-sidebar-one">
			<li class="ul-sidebar-li"><a class="" href="#" data-toggle="modal" data-target="#myModal"> <font><?= TranslateHelper::t('导入物流单号')?></font></a></li>
		</ul>
		<ul  class="ul-sidebar-one">
			<li class="ul-sidebar-li"><a class="" href="<?=Url::to(['/order/custom/feedback-template-list'])?>" target="_blank"> <font><?= TranslateHelper::t('评价范本设置')?></font></a></li>
		</ul>
		<ul  class="ul-sidebar-one">
			<li class="ul-sidebar-li"><a class="" href="<?php echo \Yii::getAlias('@web');?>/docs/paypalHelper.html" target="_blank"> <font><?= TranslateHelper::t('Paypal订单同步设置')?></font></a></li>
		</ul>
		<div class="sidebarLv1Title">
			<div id="">
			<span class="glyphicon glyphicon-wrench toggleMenuL" style="margin: 1px 5px 0px 0px;"></span>
			<a  class="" href="<?=Url::to(['/configuration/carrierconfig/index'])?>"><?= TranslateHelper::t('设置')?></a></div>
		</div>
	</div>