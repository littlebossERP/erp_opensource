<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper;

?>
<style>
#over-lay .over-lay-modal {	
	<?php if(empty($error)){ ?>
		width:1200px !important;
		z-index:2;
	<?php }else{ ?>
		width:600px !important;
		z-index:2;
	<?php } ?>
}
</style>
<div>
	<form id="applyTrackNum" class="form-inline">
		<?php echo $html; ?>
		<?php 
		if($html == ''){
			echo $msg;
		}
		?>
	</form>
</div>
<div class="modal-footer col-xs-12">
	<?php if(empty($error)){ ?>
	<?php if($html != ''){ ?>
	<button type="button" class="btn btn-primary queding">确定</button>
	<button class="btn-default btn modal-close">取消</button>
	<?php }else{
	?>
	<input type="hidden" id='distribution_tracking_no' value="<?=$tracking_number ?>"/>
	<button class="btn btn-primary sure_tracking_btn">确定</button>
	<?php
	} ?>
	<?php }else{ ?>
	<button class="btn btn-primary modal-close">确定</button>
	<?php } ?>
</div>

<script>
$('.modal-close').click(function(){
	$('.modal-backdrop').remove();
});

</script>