<?php
//$this->registerJs("StationLetter.templateList=".json_encode($data['listTemplate']).";" , \yii\web\View::POS_READY);


?>
<script type="text/javascript">
<!--

//-->
<?php
if (empty($data['listTemplate'])) $data['listTemplate'] = [];
if (empty($data['defaultTemplate'])) $data['defaultTemplate'] = [];
if (empty($data['listTemplateAddinfo'])) $data['listTemplateAddinfo'] = [];
if (empty($data['matchRoleTracking'])) $data['matchRoleTracking'] = [];
if (empty($data['unMatchRoleTracking'])) $data['unMatchRoleTracking'] = [];
?>


StationLetter.templateList=<?=json_encode($data['listTemplate'])?>;
StationLetter.defaultTemplate=<?= json_encode($data['defaultTemplate']).";"?> 
StationLetter.templateAddiInfoList=<?=json_encode($data['listTemplateAddinfo'])?>;
StationLetter.MatchResult=<?=json_encode(['match_data'=>$data['matchRoleTracking'],'unmatch_data'=>$data['unMatchRoleTracking']])?>;
</script>
<style>
.xlbox .modal-dialog{
    max-width: 900px;
	width:auto;
}
.order_info .modal-body{
	max-height: 600px;
	max-width: 900px;
	width:auto;
}
</style>
<div class="row">
	
	<?php if (!empty($data['history']) && !empty($data['show_method']) && $data['show_method']=="history"){?>
	<div class="col-sm-12" name="message_history"><?= $this->render('_message_history',['data'=>$data]) ?></div>
	<div class="div_space_toggle" name="station_letter"><?= $this->render('_station_letter',['data'=>$data]) ?></div>
	<?php }else{?>
	<div class="col-sm-12" name="station_letter"><?= $this->render('_station_letter',['data'=>$data]) ?></div>
	<?php }?>
</div>