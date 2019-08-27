<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

$controller = Yii::$app->controller->id;
$action = Yii::$app->controller->action->id;
?>
<style>

.td_space_toggle{
	height: auto;
	padding: 0!important;
}
.table th{
	text-align: center;
}
.table td{
	text-align:left;
}

table{
	font-size:12px;
}

.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
</style>
<div id="sidebar" style="height:100%;">
		<div class="sidebarLv1Title">
			<div id="">
			<span class="egicon-striped-ectangular-blue" style="margin: 1px 5px 0px 0px;"></span>
			<a  class="" href="javascript:void(0);"><?= TranslateHelper::t('Wish刊登')?></a></div>
		</div>
		<ul class="ul-sidebar-one">
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('刊登管理')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li <?php if(isset($lb_status)):?><?php if($lb_status == '1'):?> active<?php endif?><?php endif;?>">
						<a href="<?=Url::to(['/listing/wish/wish-list?type=2&lb_status=1'])?>"><font><?= TranslateHelper::t('待发布')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li <?php if(isset($lb_status)):?><?php if($lb_status == '4'):?> active<?php endif?><?php endif;?>">
						<a href="<?=Url::to(['/listing/wish/wish-list?type=2&lb_status=4'])?>"><font><?= TranslateHelper::t('刊登失败')?></font><span class="badge"></span></a>
					</li>
				</ul>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('商品列表')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li<?= ($controller == 'wish-online' && $action =='online-product-list') || ($controller == 'wish' && $action =='online-fan-ben-edit')  ?' active':''?>">
						<a href="<?=Url::to(['/listing/wish-online/online-product-list'])?>"><font><?= TranslateHelper::t('在线商品')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li<?= ($controller == 'wish-online' && $action =='offline-product-list') || ($controller == 'wish' && $action =='offline-fan-ben-edit')  ?' active':''?>">
						<a  href="<?=Url::to(['/listing/wish-online/offline-product-list'])?>"><font><?= TranslateHelper::t('下架商品')?></font><span class="badge"></span></a>
					</li>
				</ul>
			</li>
		</ul>
	</div>