<?php 
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
<!--

-->
#letter-history-list .letter-history-list-row:nth-child(odd){
	background:#f9f9f9;
}
</style>
<form class="form-horizontal" role="form" id="station-letter-history">

<div class="form-group">
	<label for="track_no_list" class="col-sm-2 control-label" ><?= TranslateHelper::t('平台订单号')?></label>
	<div class="col-sm-10 form-control-static">
	<?= (empty($data['order_id'])?"":$data['order_id'])?>

	</div>
				
</div>
<div id="letter-history-list">
<?php
if (!empty($data['history']))
foreach ($data['history'] as $row):
	//$isFailed = strtoupper($row['status'])=='F';
	if (strtoupper($row['status'])=='F'){
		$addi_info = json_decode($row['addi_info'] , true);
	}

?>
<div class="letter-history-list-row">
	<div class="form-group">
		<label for="time" class="col-sm-2 control-label"><?= empty($row['create_time'])?"":$row['create_time']?></label>
		<label for="message" class="col-sm-10 form-control-static">
			<?php if (strtoupper($row['status'])=='F'){?>
			<span class="egicon-envelope-remove" style="margin-top: 3px;"></span>
			<small><a onclick="StationLetter.fillEditData(this,'<?= $row['id']?>')"><?= TranslateHelper::t('再次编辑发送')?></a></small>
			<?php }elseif (strtoupper($row['status'])=='C'){?>
			<span class="egicon-envelope-ok" style="margin-top: 3px;"></span>
			<?php }?>
		</label>
	</div>
	<?php if ($isFailed = strtoupper($row['status'])=='F'):?>
	<div class="form-group">
		<label for="tip" class="col-sm-2 control-label"><?= TranslateHelper::t('错误提示')?></label>
		<div class="col-sm-10 form-control-static">
			<?= empty($addi_info['error'])?"":$addi_info['error']?>
		</div>
	</div>
	<?php endif;?>
	
	<div class="form-group">
		<label for="subject" class="col-sm-2 control-label"><?= TranslateHelper::t('标题')?></label>
		<div name="subject" class="col-sm-10 form-control-static"><?= empty($row['subject'])?"":$row['subject']?></div>
	</div>
	<div class="form-group">
		<label for="content" class="col-sm-2 control-label"><?= TranslateHelper::t('内容')?></label>
		<div name="content" class="col-sm-10 form-control-static"><?= empty($row['content'])?"":$row['content']?></div>
	</div>
</div>
<?php endforeach;?>
</div>
</form>