<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;

//$this->registerJsFile(\Yii::getAlias('@web').'/js/project/tracking/tracking_dash_board.js',['depends' => ['eagle\assets\PublicAsset']]);
//$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerJs("TrackingDashBoard.chartData=".json_encode($chartData).";" , \yii\web\View::POS_READY);
//$this->registerJs("TrackingDashBoard.initChart();" , \yii\web\View::POS_READY);
//$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<?php 
$uid = \Yii::$app->user->id;
$Carriers = CarrierApiHelper::getCarriers();
$express = empty(CarrierTypeOfTrackNumber::$expressCode[$model->carrier_type])?'未知':CarrierTypeOfTrackNumber::$expressCode[$model->carrier_type];
?>
<style>
.report-no-info-win .modal-dialog{
	width:700px;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}

</style>
<?php 
$readonly = '';
if(!empty($act)){
	if($act=='view'){
		$readonly_arr = ['disabled'=>'disabled'];
		$readonly = 'disabled';
	}
	else{
		$readonly_arr = ['readonly'=>false];
	}
}else
	$readonly_arr = ['readonly'=>false];
?>
<form id="report_info_<?=$model->id ?>" style="width:100%;display:inline-block;clear:both;">
	<div class="alert-info" role="alert">如果您的物流号在其他网站查询得到物流信息，您可以将您查询到的情况告知我们，方便我们为您查出物流助手查询不到的原因，同时改善系统。</div>
	<input type="hidden" name="act" value="<?=empty($act)?'add':$act ?>">
	<table style="width:100%" class="table">
		<tr>
			<td width="30%">物流号：<?=empty($model->track_no)?'--':$model->track_no ?><input type="hidden" name="track_no" value="<?=empty($model->track_no)?'':$model->track_no ?>"></td>
			<td width="30%">订单号：<?=empty($model->order_id)?'--':$model->order_id ?><input type="hidden" name="order_id" value="<?=empty($model->order_id)?'':$model->order_id ?>"></td>
			<td width="40%">更新时间：<?=empty($model->update_time)?'--':$model->update_time ?></td>
		</tr>
		<tr>
			<?php
			$carrier_name = '';
			if(!empty($model->addi_info)){
				$addi_info = urldecode($model->addi_info);
				$addi_info = json_decode($addi_info);
				if(!empty($addi_info->carrier_name))
					$carrier_name = $addi_info->carrier_name;
			}
			if(!empty($carrier_name)){
				foreach ($Carriers as $code=>$name){
					if($name==$carrier_name){
						$carrier_code = $code;
						break;
					}
				}
			}
			if(!empty($case['carrier_type']))
				$carrier_code = $case['carrier_type'];
			?>
			<td colspan="2">
				<span>物流方式：</span>
				<?=Html::dropDownList('carrier_type',empty($carrier_code)?$model->carrier_type:$carrier_code,[$model->carrier_type=>$express]+$Carriers,['class'=>'form-control input-sm','style'=>'width:200px;margin:0px;display:inline-block;']+$readonly_arr)?>
				<span style="color:red;font-size:14px;font-weight:600;">*</span>
			</td>
			<td>状态：<?php if(empty($case['status'])) echo "<span class='alert-info'>未反馈</span>";
						  elseif($case['status']=='P') echo "<span class='alert-info'>等待处理</span>";
						  elseif($case['status']=='R') echo "<span class='alert-warning'>客服提出异议</span>";
						  elseif($case['status']=='C') echo "<span class='alert-success'>已完成</span>";
			?>
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<span>查询平台网址：</span><input type="text" name="customer_url" <?=$readonly ?> value="<?=empty($case['customer_url'])?'':$case['customer_url']?>" class="form-control" style="width:500px;display:inline-block;"><span style="color:red;font-size:14px;font-weight:600;">*</span>
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<span style="vertical-align:top;">其他描述：</span>
				<textarea rows="5" cols="10" name="desc" <?=$readonly ?> style="width:300px;display:inline-block;"><?=empty($case['desc'])?'':$case['desc']?></textarea>
			</td>
		</tr>
		<tr>
			<td colspan="3">
				<span style="margin:5px;float:left;">客服回复：</span>
				<span class='alert-info' style="margin:5px;float:left;"><?=empty($case['comment'])?'':$case['comment'] ?></span>
			</td>
		</tr>
	</table>
</form>
<div style="display:inline-block;width:100%;clear:both;text-align:center;">
	<?php if(!empty($act) && $act=='add'): ?>
	<button type="button" onclick="submitCase(<?=$model->id ?>)" class="btn btn-primary">提交</button>
	<?php endif;
		if(!empty($act) && $act=='edit'): ?>
	<button type="button" onclick="submitCase(<?=$model->id ?>)" class="btn btn-primary">修改并提交</button>
	<?php endif; ?>
	<button type="button" onclick="submitCancle()" class="btn btn-warning">取消</button>
</div>
<script>

function submitCase(id){
	$.showLoading();
	$.ajax({
		type: "post",
		url:'/tracking/tracking/save-customer-report', 
		data:$("#report_info_"+id).serialize(),
		dataType: 'json',
		success: function (result) {
			$.hideLoading();
			if(result.success==true){
				bootbox.alert({
					buttons: {
						ok: {
							label: 'OK',
							className: 'btn-primary'
						}
					},
					message: Translator.t("提交成功!"),
					callback: function() {
						$('.report-no-info-win').modal('hide');
						$('.report-no-info-win').on('hidden.bs.modal', '.modal', function(event) {
							$(this).removeData('bs.modal');
						});
					}, 
				});
			}else{
				alert('提交失败:'+result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert('提交失败,后台返回异常!');
			return false;
		}
	});
}

function submitCancle(){
	$('.report-no-info-win').modal('hide');
	$('.report-no-info-win').on('hidden.bs.modal', '.modal', function(event) {
		$(this).removeData('bs.modal');
	});
}
</script>