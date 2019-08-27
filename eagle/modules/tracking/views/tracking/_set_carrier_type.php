<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;


$groupBy_shipBy = [];
foreach ($trackings as $oneTracking){
	$groupBy_shipBy[$oneTracking->ship_by][] = $oneTracking;
}
$carrier_types = CarrierTypeOfTrackNumber::$expressCode;
?>
<style>
.modal-body{
	padding:0px;
}

</style>
<div>
	<div class="alert alert-info" style="margin-bottom:5px;">手动设置物流单号在物流跟踪助手查询时使用的物流渠道，可以让物流跟踪助手更有效地查询到物流信息。<br>设置后，后续的所有该物流渠道运单，都会优先使用用户设置的渠道进行查询。</div>
	<?php if(!empty($_REQUEST['act']) && $_REQUEST['act']=='batch') { ?>
	<div style="width:100%;text-align: center;margin-bottom: 10px;">
		<span>相同的物流商服务使用同样的设置？</span>
		<label for="use_same_Y">是</label><input type="radio" name="group_use_same_setting" id="use_same_Y" value="Y">
		<label for="use_same_N">否</label><input type="radio" name="group_use_same_setting" id="use_same_N" value="N">
	</div>
	<?php } ?>
	<form id="set-carrier-type-data" style="width: 100%;">
		<table class="table">
			<tr>
				<th>物流号</th>
				<th>订单号</th>
				<th>物流商服务</th>
				<th>查询时使用的物流渠道</th>
			</tr>
			<?php foreach ($groupBy_shipBy as $shipBy=>$TrackingArr){ 
				$rowspan = count($TrackingArr);
				foreach ($TrackingArr as $index=>$oneTracking){
			?>
			<tr>
				<td><?=$oneTracking->track_no?></td>
				<td><?=$oneTracking->order_id?></td>
				<td><?=$oneTracking->ship_by?></td>
				<?php 
					$selected_carrier = '';
					$addi_info = json_decode($oneTracking->addi_info,true);
					if(!empty($addi_info['set_carrier_type']))
						$selected_carrier = $addi_info['set_carrier_type'];
					else 
						$selected_carrier = $oneTracking->carrier_type;
				?>
				<td><select name="carrier_type[<?=$oneTracking->id?>]" data-ship-by="<?=base64_encode($oneTracking->ship_by)?>"
					onchange="optionChanged(this,'<?=base64_encode($oneTracking->ship_by)?>')">
					<?php foreach ($carrier_types as $value=>$name){?>
					<option value="<?=$value?>" <?=($value==$selected_carrier)?'selected=true':'' ?>><?=$name?></option>
					<?php }?>
					</select>
				</td>
			</tr>
			<?php }
			} ?>
		</table>
	
	</form>
	
</div>
<script>
	function optionChanged(dom,shipByEncode){
		//debugger
		var group_use_same = $("input[name='group_use_same_setting']:checked").val();
		if(group_use_same!=='Y')
			return;
		var checked_val = $(dom).val();
		console.log(checked_val);
		$("select[name^='carrier_type']").each(function(){
			if($(this).data('ship-by')==shipByEncode){
				//$(this).find("option[value='"+checked_val+"']").attr("selected","selected");
				
				$(this).find("option").each(function(){
					if($(this).val()!==checked_val)
						$(this).removeAttr("selected")
					else{
						$(this).attr("selected",true);
					}
				});
				$(this).val(checked_val);
			}
		});
	}
</script>