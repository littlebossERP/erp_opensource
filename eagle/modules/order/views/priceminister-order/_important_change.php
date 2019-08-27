<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\SysBaseInfoHelper;

?>

<?php 
$uid = \Yii::$app->user->id;
?>
<style>
.important-change-tip-win .modal-dialog{
	min-width:860px;
	max-width:80%;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}
.important-change-tip-win .modal-body{
	padding: 0px 15px 15px 15px;
}
.important-change-tip-win p{
	font-size: 16px;
	margin:5px;
	width:100%;
	display:inline-block;
}
.structure_1_left{
	float:left;
	padding:0px 0px 0px 10px;
}
.structure_1_rigth{
	padding:0px 0px 0px 0px;
}
.structure_2_left{
	padding:0px 20px 0px 0px;
}
.structure_2_rigth{
	float:left;
	padding:0px 0px 0px 40px;
}
.structure_3_left{
	padding:0px 30px 0px 0px;
}
.structure_3_rigth{
	float:left;
	padding:0px 0px 0px 60px;
}
.important-change-tip-win-footer{
	margin-top:10px;
	width:100%;
	display:inline-block;
	font-size: 18px;
	background-color: #f4f9fc;
}
</style>
<div style="width: 100%;display:inline-block">
	<h4 class="h4" style="text-align: center;">Priceminister 订单管理系统 2.1a 版本上线</h4>
	<p>
		<span class="structure_1_left">1）</span>
		<span class="structure_1_rigth">“同步订单”界面，检查每个店铺的同步时间，同步问题</span>
	</p>
	<p>
		<span class="structure_1_left">2）</span>
		<span class="structure_1_rigth">“订单挂起”菜单不再使用，改为“暂停发货” “缺货”2个订单业务流程代替，更加精细化管理流程</span>
	</p>
	<p>
		<span class="structure_1_left">3）</span>
		<span class="structure_1_rigth">“已付款”也就是待发货的订单，菜单增加4大子状态的管理</span>
	</p>
	<p><span class="structure_2_rigth">已付款-待检测：</span></p>
	<p><span class="structure_3_rigth">新同步订单的默认都为“待检测”的状态，用户需要都为新订单手动做一次检测订单来开始订单流程操作</span></p>

	<p><span class="structure_2_rigth">已付款-重新发货：</span></p>
	<p><span class="structure_3_rigth">当暂停订单，缺货订单，已出库订单，取消订单，废弃订单点击了【重新发货】的后，都会汇总到这个状态，集中处理</span></p>

	<p><span class="structure_2_rigth">已付款-有异常：</span></p>
	<p><span class="structure_3_rigth">汇总所有【检测订单】后，发现订单异常，需要用户确认处理方案后才能发货的订单</span></p>

	<p><span class="structure_2_rigth">已付款-可发货：</span></p>
	<p><span class="structure_3_rigth">汇总所有没有异常可以直接发货的订单</span></p>

	<h4 class="modal-footer">更多详细操作介绍，请参考 小老板2.1版操作流程教程（<a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_60.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_60.html')?></a>）</h4>
</div>