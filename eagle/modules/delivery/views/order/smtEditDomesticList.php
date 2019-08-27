<?php
?>

<div class="inventory_import_excel_content" style="width:100%;float:left;">
	<div>请录入对应的小老板订单号，国内快递公司，国内快递单号。</div>
	<br>

	<span class="watermark_container" style="display: inline-block; position: relative; width: 100%;">
		<textarea id="import_text_data_smt" class="form-control" style="width:100%;height:400px;margin-top:0px;" 
			data-percent-width="true" data-jq-watermark="processed" 
			placeholder="<?='如以下例子：&#13;&#10;123	中通快递	RX12345678CN&#13;&#10;589	中通快递	RX19875635CN&#10;852	中通快递	RX15668871CN' ?>"></textarea>
	</span>
</div>

<div class="modal-footer">
	<button type="button" class="iv-btn btn-primary btn-sm smt">提交</button>
	<button class="iv-btn btn-default btn-sm modal-close">关闭</button>
</div>