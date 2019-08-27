<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
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
<div id="sidebar">
		<div class="sidebarLv1Title">
			<div id="">
			<span class="egicon-striped-ectangular-blue" style="margin: 1px 5px 0px 0px;"></span>
			<a  class="" href="javascript:void(0);"><?= TranslateHelper::t('eBay刊登')?></a></div>
		</div>
		<ul class="ul-sidebar-one">
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('Item列表')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/ebayitem/list'])?>"><font><?= TranslateHelper::t('在线Item')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/ebayitem/listend'])?>"><font><?= TranslateHelper::t('下架Item')?></font><span class="badge"></span></a>
					</li>
				</ul>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('刊登范本')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/ebaymuban/list'])?>"><font><?= TranslateHelper::t('范本列表')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/ebaymuban/templatelist'])?>"><font><?= TranslateHelper::t('商品信息模板')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/ebaymuban/salesinfolist'])?>"><font><?= TranslateHelper::t('销售信息范本')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/ebaymuban/crosslist'])?>"><font><?= TranslateHelper::t('产品推荐范本')?></font><span class="badge"></span></a>
					</li>
				</ul>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="#"><font><?= TranslateHelper::t('刊登队列')?> </font></a></span>
			</li>
			<li class="sidebar-shrink-li">
				<ul class="ul-sidebar-two">
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/additemset/list'])?>"><font><?= TranslateHelper::t('定时队列')?></font><span class="badge"></span></a>
					</li>
					<li class="ul-sidebar-li">
						<a class="" href="<?=Url::to(['/listing/additemset/loglist'])?>"><font><?= TranslateHelper::t('刊登失败')?></font><span class="badge"></span></a>
					</li>
				</ul>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="<?=Url::to(['/listing/ebaystorecategory/liststorecategory'])?>"><font><?= TranslateHelper::t('店铺管理')?> </font></a></span>
			</li>
			<li class="ul-shrinkHead-li">
				<span class="glyphicon glyphicon-menu-down toggleMenuL"></span><span class="sidebarLv2Title"><a class="" href="<?=Url::to(['/listing/ebaycompatibility/show'])?>"><font><?= TranslateHelper::t('汽配兼容')?> </font></a></span>
			</li>
		</ul>
	</div>