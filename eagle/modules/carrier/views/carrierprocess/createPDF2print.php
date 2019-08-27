<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\carrier\helpers\CarrierHelper;
?>

<style>
.container-fluid{
	margin-right: inherit;
    margin-left: inherit;
}
</style>

<div class="content-wrapper container-fluid" style='margin-top: 20px;'>
<?php 
	if(!empty($data['notMethodOrder'])){
?>
<div style='margin-bottom: 20px;'>
<?php 
		echo "<font color='red'>该订单列表没有运输服务不能打印：</font>".implode(",",$data['notMethodOrder']);
?>
</div>
<?php
	} 
?>

<?php if(!empty($data['emslist'])){ ?>
<table class="table table-condensed table-bordered" style="font-size:12px;table-layout: fixed;word-break: break-all;">
<tr><th>运输服务</th><th>操作</th></tr>
<?php
foreach($data['emslist'] as $k=>$v){
?>
<tr>
<td><?php echo $k;?></td>
<td><input type="button"  class="btn btn-primary btn-xs" value="预览并打印" onclick="print_one('<?php echo $v['order_ids'];?>','<?php echo $k;?>','<?php echo $v['priorityPrint'];?>')"/></td>
</tr>
<?php } ?>
</table>
<?php }else{
	echo '该状态下没有订单';
}?>

</div>

<script>
function print_one(ids, ems, priorityPrint) {
	if (ids != "") {
		switch (priorityPrint){
			case("is_api_print"):
				window.open(global.baseUrl + "carrier/carrierprocess/doprintapi?orders=" + ids + "&ems=" + ems);
				break;
			case("is_custom_print"):
				window.open(global.baseUrl + "carrier/carrierprocess/doprintcustom?orders=" + ids + "&ems=" + ems);
				break;
			case("is_print"):
				window.open(global.baseUrl + "carrier/carrierprocess/doprint?orders=" + ids + "&ems=" + ems);
				break;
			case("is_xlb_print"):
				window.open(global.baseUrl + "carrier/carrierprocess/doprint-new?orders=" + ids + "&ems=" + ems);
				break;
			case("is_custom_print_new"):
				window.open(global.baseUrl + "carrier/carrierprocess/doprintcustom-new?orders=" + ids + "&ems=" + ems);
			break;
		}
	} else {
		alert("无法找到该服务下的订单");
	}
}
</script>