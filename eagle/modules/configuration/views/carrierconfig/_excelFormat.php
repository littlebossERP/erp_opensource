<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<script>
	excelFormat.init(<?= json_encode(@$excel_mode)?>,<?= json_encode(@$excel_format)?>,<?= json_encode(@$excelSysDATA)?>);
</script>
<style>
.excelForamtTable{
		width:100%;text-align:center;table-layout:fixed;line-height:30px; margin:0;
		border-radius: 5px 5px 0 0;
		-moz-border-radius: 5px 5px 0 0;
		-webkit-border-radius: 5px 5px 0 0;
		-khtml-border-radius: 5px 5px 0 0;
		border-color: #797979;
	    border-style: solid;
	    border-width: 1px;
		font-size: 13px;
    	color: rgb(51, 51, 51);
		font-family: 'Arial Normal', Arial;
	}
	.excelForamtTable th{
/* 		background:#D7D7D7; */
		border-color: #797979;
	    border-style: solid;
	    border-width: 1px;
	}
	.excelForamtTable td{
/* 		background:#D7D7D7; */
		border-color: #797979;
	    border-style: solid;
	    border-width: 1px;
		line-height:25px;
	}
	.excelForamtTable>tbody>tr>td{
		padding: 3px 5px;
	}
	.excelForamtTable input{
		height:25px;
		line-height:25px;
	}
	.excelForamtTable select{
		height:25px;
		line-height:25px;
	}
	
	.iv-modal .modal-content{
/*   		max-height: none; */
 		min-height: 610px;
	}
</style>

<form id="excelformatFORM">
	<?= Html::hiddenInput('carrier_code',$carrier_code)?>
	<div>
		<b class="text-right">导出格式：</b>
		<label><input type="radio" name="params[excel_mode]" value="orderToOneLine" class="req"> 一个订单一行</label>
		<label><input type="radio" name="params[excel_mode]" value="orderToSku"> 一个订单多行(按商品)</label>
		<label><input type="radio" name="params[excel_mode]" value="orderToLine"> 一个订单多行(按子订单数)</label>
	</div>
	<div style="margin: 8px 0;">
		<input type="button" class="btn btn-primary" id="btn_import_product" value="从物流商提供的表格导入标题行">
		<input type="button" class="btn btn-success" onclick="excelFormat.insertOneLine()" value="添加一列">
	</div>
	<div>
		<table class="excelForamtTable">
			<thead>
				<tr>
					<th class="text-nowrap text-center" style="width:100px;"></th>
				    <th class="text-nowrap text-center" style="width:200px;"><?=TranslateHelper::t('标题列') ?></th>
				    <th class="text-nowrap text-center"><?= TranslateHelper::t('数据')?></th>
				    <th class="text-nowrap text-center" style="width:200px;"><?= TranslateHelper::t('操作')?></th>
				</tr>
		    </thead>
		    <tbody id="excelTbody">
				
			</tbody>
		</table>
	</div>
</form>
<div class="modal-footer">
	<button type="button" onclick="excelFormat.saveFormat()" class="btn btn-success" >保存</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>
