<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
?>
<style>
	#title
	{
	    font-family: 'Applied Font Bold', 'Applied Font';
	    font-style: normal;
	    font-size: 24px;
	    color: rgb(51, 51, 51);
	}
	#code_input
	{
		font-family: "Applied Font";
	    width: 400px;
	    height: 50px;
	    text-align: left;
	    font-size: 13px;
	    margin-bottom: 20px;
	}
	.btn_right
	{
		margin-right:10px;
	}
</style>

<span id = "title">跟踪号：</span>
<input id="code_input" type="text" value="扫描跟踪号条码或手动输入或直接按确定绑定" class="text_sketch">

<form name="frm_declaration" method="POST">
<table class="table">
	<tr>
		<th style="width: 150px;">小老板单号</th>
		<th style="width: 200px;">运输服务</th>
		<th style="width: 150px;">跟踪号</th>
		<th style="width: 100px;">提示</th>
		<th style="width: 50px;">操作</th>
	</tr>
	
<?php
foreach ( $OrderList as $order_id=>$shipping_method_name){

?>
<tr>
	<td><?=preg_replace('/^0+/','',$order_id) ?><input type="hidden" name="orderid[]" value="<?php echo preg_replace('/^0+/','',$order_id) ?>"></td>
	<td><?=$shipping_method_name ?><input type="hidden" name="shippingmethod[]" value="<?php echo $shipping_method_name ?>"></td>
	<td><input type="hidden" name="trackingnumber[]" class="<?php echo $is_tracking_numberlist[$order_id] ?>" value=""></td>
	<td><input type="hidden" name="status[]" value=""></td>
	<td style="min-width: 60px;"><a name="delete[]" >删除</a></td>
</tr>
<?php 
}
?>	
</table>
</form>
<div id="div_declaration_btn_bar"  class="text-center">
	<input type="button" id="btn_ok" class="btn_right iv-btn btn-primary" value="<?=TranslateHelper::t('确定绑定')?>">
	<input type="button" id="btn_cancel" class="btn_right iv-btn btn-primary" value="<?=TranslateHelper::t('关闭')?>">
	<input type="button" id="btn_print" class="btn_right iv-btn btn-primary" value="<?=TranslateHelper::t('打印面单')?>">
	<input type="button" id="btn_invoice" class="btn_right iv-btn btn-primary" value="<?=TranslateHelper::t('打印发票')?>">
</div>


