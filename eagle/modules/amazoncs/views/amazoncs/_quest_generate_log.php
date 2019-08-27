<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTagApi.js", ['depends' => ['yii\web\JqueryAsset']]);
$tag_class_list = OrderTagHelper::getTagColorMapping();
$this->registerJs("OrderTagApi.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderTagApi.init();" , \yii\web\View::POS_READY);
?>
<style>

</style>
<div style="max-height:700px;overflow-y: scroll;">
	<?php if(!empty($template->last_generated_log)){ 
		$last_generated_log = json_decode($template->last_generated_log,true);
	?>
	<span>最后生成时间：<?=empty($last_generated_log['generate_time'])?'--':$last_generated_log['generate_time']?></span>
	<br>
	
	
	<div>
		<span>模板规则指定的订单时间内，有如下订单符合发送条件:</span>
		<br>
		<?php if(!empty($last_generated_log['matchOrders'])){ ?>
		<?=implode('<br>', $last_generated_log['matchOrders'])?>
		<?php }else{
			echo '--<br>';
		} ?>
	</div>
	
	
	<?php if(!empty($last_generated_log['insert_result']['failed'])){?>
	<div>
		<span>符合发送条件的订单，有以下订单，邮件任务生成失败:</span>
		<br>
		<?php foreach ($last_generated_log['insert_result']['failed'] as $order_no=>$msg){ ?>
			<?=$order_no?> : <?=$msg?><br>
		<?php } ?>
	</div>
	<?php } ?>
	
	<div>
		<span>模板规则指定的订单时间内，有如下订单不符合发送条件:</span>
		<br>
		<?php if(!empty($last_generated_log['unMatchOrdersMsg'])){?>
			<?php foreach ($last_generated_log['unMatchOrdersMsg'] as $order_no=>$msg){ ?>
			<?=$order_no?> : <?=$msg?><br>
			<?php } ?>
		<?php }else{
			echo '--<br>';
		} ?>
	</div>
	
	
	
	
	<?php } ?>
</div>
