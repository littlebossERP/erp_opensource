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
	<h4 class="h4" style="text-align: center;">小老板 Cdiscount"跟卖终结者" 功能上线</h4>
	<p>
		<span class="structure_1_left"></span>
		<span class="structure_1_rigth">Cdiscount产品被其他卖家跟卖怎么办？跟卖其他卖家商品效果不理想？小老板 Cdiscount"跟卖终结者"帮你监控！</span>
	</p>
	<p>
		<span class="structure_1_left">功能介绍：</span>
		<span class="structure_1_rigth"></span>
	</p>
	<p>
		<span class="structure_2_left">1.获取店铺商品列表：</span>
		<span class="structure_2_rigth"></span>
		<span class="structure_3_left"></span>
		<span class="structure_3_rigth">初次使用该功能时使用，及之后有需要时提交小老板后台获取(获取间隔为2天)。获取后您可以在小老板系统查看您的Cdiscount商品。</span>
	</p>
	<p>
		<span class="structure_2_left">2.设置不同的监控优先级：</span>
		<span class="structure_2_rigth"></span>
		<span class="structure_3_left"></span>
		<span class="structure_3_rigth">根据实际需求，可以对商品进行3个优先等级设置：关注、未关注(默认)、忽略。<br>关注——每6小时同步一次跟卖信息(每个店铺关注上限暂时为30个)；<br>未关注——每6天同步一次；<br>忽略——不再进行同步。</span>
	</p>
	<p>
		<span class="structure_2_left">3.显示当前商品的BestSeller情况：</span>
		<span class="structure_2_rigth"></span>
		<span class="structure_3_left"></span>
		<span class="structure_3_rigth">显示商品的BestSeller是谁、您是否是BestSeller、BestSeller的售价、您的售价、采购价、库存等，助您适当的对商品销售策略作出调整，夺回BestSeller位置。</span>
	</p>
	<p>
		<span class="structure_2_left">4.导出售价和库存Excel：</span>
		<span class="structure_2_rigth"></span>
		<span class="structure_3_left"></span>
		<span class="structure_3_rigth">导出售价和Excel，可用户上传CD后台修改商品售价和库存。</span>
	</p>
	<p>更多详细操作介绍，请参考 <a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_73.html')?>" target="_blank">小老板 Cdiscount"跟卖终结者" 功能介绍</a></p>
	<p><span class="structure_1_rigth"></span></p>
	<p></p>
	<h4 class="modal-footer">
		<p>历史通知：</p>
		<p><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_60.html')?>" target="_blank">小老板2.1版操作流程教程</a></p>
	</h4>
</div>