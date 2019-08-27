<?php 
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
table {
	font-size: 12px;
}

.modal-header{
    height: auto;
}
</style>

<div style='margin: 20px'>
<h4 class="modal-title" id="myModalLabel" style="display:inline-block"><?=$carrier_name ?> 运输方式</h4>
<button style='margin-left: 25px' data-toggle="modal" onclick="editCarrierMethod('<?=$code ?>','carrier')" data-target="#checkOrder" class="btn btn-success"><?=TranslateHelper::t('批量增加或修改运输方式') ?></button>

<br><br>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
<tr><th></th><th>运输代码</th><th>运输方式</th><th>是否支持API打印</th><th>是否对接高仿打印</th><th>支持高仿面单格式</th><th>海外仓库代码</th><th>通知平台代码</th><th>是否废弃</th><th>操作</th></tr>
 <?php
	foreach($carrier_method as $methodKey => $methodOne){
		?>
		<tr>
			<td><?=$methodKey+1; ?></td>
			<td><?=$methodOne['shipping_method_code'] ?></td><td><?=$methodOne['shipping_method_name'] ?></td>
			<td><?=$methodOne['is_api_print'] ?></td>
			<td><?=$methodOne['is_print'] ?></td>
			<td>
				<?php
					$methodOne['print_params'] = json_decode($methodOne['print_params'],true);
					
					$tmpPrintList = ['label_address'=>'地址单','label_declare'=>'报关单','label_items'=>'配货单'];
					
					if(is_array($methodOne['print_params'])){
						foreach ($methodOne['print_params'] as &$tmpPrintparams){
							$tmpPrintparams = $tmpPrintList[$tmpPrintparams];
						}
						
						echo implode(',', $methodOne['print_params']);
					}
				?>
			</td>
			<td><?=$methodOne['third_party_code']; ?></td>
			<td><?=$methodOne['service_code']; ?></td>
			<td><?=$methodOne['is_close'] == 1 ? '<span style="color: red;">是</span>' : '否' ?></td>
			<td>
			<button data-toggle="modal" onclick="editCarrierPrint('<?=$methodOne['carrier_code'] ?>','<?=$methodOne['shipping_method_code'] ?>','<?=$methodOne['third_party_code'] ?>','carrier')" data-target="#carrierPrint" class="btn btn-default btn-sm"><?=TranslateHelper::t('编辑') ?></button>
			</td>
		</tr>
		<?php
	}
 ?>
 
</table>

</div>
	<!-- Modal -->
	<div id="checkOrder" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
        </div><!-- /.modal-content -->
    </div>
    </div>
    
    <div id="carrierPrint" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
        </div><!-- /.modal-content -->
    </div>
    </div>
    <!-- /.modal-dialog -->
 
 
<script>
	function editCarrierMethod(carrierCode,op) {//添加订单标签
		var reUrl = '';
		if(op=='carrier')reUrl='<?= \Yii::$app->urlManager->createUrl("/configuration/carrierbackstage/create-channel") ?>';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code : carrierCode},
			url: reUrl,
	        success:function(response) {
	        	$('#checkOrder .modal-content').html(response);
	        }
	    });
	}

	function editCarrierPrint(carrierCode,methodCode,thirdPartyCode,op) {//添加订单标签
		var reUrl = '';
		if(op=='carrier')reUrl='<?= \Yii::$app->urlManager->createUrl("/configuration/carrierbackstage/edit-shipping-print") ?>';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code : carrierCode,methodCode : methodCode,thirdPartyCode : thirdPartyCode},
			url: reUrl,
	        success:function(response) {
	        	$('#carrierPrint .modal-content').html(response);
	        }
	    });
	}
</script>