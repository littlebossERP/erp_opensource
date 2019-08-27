<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\UrlManager;
use eagle\modules\util\helpers\TranslateHelper;
?>
<table>
<!-- 如果是海外仓 则直接在文件中获取仓库和运输方式信息 -->
<tr style="line-height:25px">
<td>
<?php foreach ($params as $param):?>
	<!--  <td><?=TranslateHelper::t($param->carrier_param_name) ?></td>-->
	<?php 
	$carrier_params = $service->carrier_params;
	if ($param->display_type == 'text'){
		echo Html::input('','carrier_params['.$param->carrier_param_key.']',@$carrier_params[$param->carrier_param_key],['class'=>'eagle-form-control','placeholder'=>$param->carrier_param_name]);
		echo '&nbsp;';
	}elseif ($param->display_type == 'radio'){
		echo Html::radioList('carrier_params['.$param->carrier_param_key.']',@$carrier_params[$param->carrier_param_key],$param->carrier_param_value,['class'=>'eagle-form-control']);
		echo '&nbsp;';
	}elseif ($param->display_type == 'checkbox'){
		echo Html::checkboxList('carrier_params['.$param->carrier_param_key.']',@$carrier_params[$param->carrier_param_key],$param->carrier_param_value,['class'=>'eagle-form-control']);
		echo '&nbsp;';
	}elseif ($param->display_type == 'dropdownlist'){
		echo Html::dropDownList('carrier_params['.$param->carrier_param_key.']',@$carrier_params[$param->carrier_param_key],$param->carrier_param_value,['prompt'=>$param->carrier_param_name,'style'=>'width:150px;','class'=>'eagle-form-control']);
		echo '&nbsp;';
	}?>
<?php  endforeach;?>
</td>
</tr>
</table>
<script>
	function getwarehouseService(me){
		$('select[name="carrier_params[warehouseService]"]').css('display','none');
		$('select[name="carrier_params[warehouseService]"]').attr('disabled','disabled');
		$('#warehouse_'+$(me).val()).css('display','block');
		$('#warehouse_'+$(me).val()).attr('disabled',false);
	}
	function changeValues(me){
		var serviceValue = $(me).val();
		var serviceName = $(me).find('option[value='+serviceValue+']').html();

		var carrierValue = $('#carrier_code').val();
		var carrierName = $('#carrier_code').find('option[value='+carrierValue+']').html();
		$('input[name="service_name"]').val(carrierName+'-'+serviceName);
	}
	//修改运输方式页面初始化时 加载运输方式
	if($('select[name="carrier_params[warehouse]"]').val() != '')getwarehouseService($('select[name="carrier_params[warehouse]"]'));

	$('select[name="carrier_params[shippingService]"]').bind('change',function(){
		changeValues(this);
	})
</script>