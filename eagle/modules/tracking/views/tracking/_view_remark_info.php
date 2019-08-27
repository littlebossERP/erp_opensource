<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\TrackingHelper;

if (empty($RemarkList)) $RemarkList = [];

//检查参数是否需要json decode
if (is_array($RemarkList)){
	
}else{
	$RemarkList = json_decode($RemarkList,true);
}
/*
if (!empty($RemarkList)){
	if (strtolower($sort)== 'desc' )
		$reSortRemarklist = $reSortRemarklist = array_reverse($RemarkList);
	else
		$reSortRemarklist = $RemarkList;
}else $reSortRemarklist = [];
*/


$reSortRemarklist = $RemarkList;

?>
<style>
<!--

-->
table>tbody>tr:nth-child(even){
  background-color: #f9f9f9;
}
</style>

<div class="panel">
	
	<div class="panel-body">
	<div class="row">
		<span><?= TranslateHelper::t('物流号')?>:</span>
		<span><?= $track_no?></span>
	</div>
	<div class="row" style="margin-top: 15px;">
		<?php if (count($orderids) > 1){?>
		
		<section style='color: #337ab7'>
		<dt><small>
	
	 	请注意，此包裹是以下多个订单合并发送<br>
		<?php foreach($orderids as $orderid)
				echo "$orderid<br>";
		?>
		</small></dt>	
		</section>
		<br>
		<?php
		} 
		?>
		
		<label><?= TranslateHelper::t('备注记录')?></label>
		
		
		<table class="table table-bordered">
			<tbody>
				<?php foreach($reSortRemarklist as $oneRemark):?>
				<tr>
					<td><?= nl2br($oneRemark['what'])?></td>
					<td style="width: 140px;"><?= $oneRemark['when']?></td>
				</tr>
				<?php endforeach;?>
			</tbody>
		</table>
		
		<textarea id="tracking_add_remark" name="tracking_add_remark" class="form-control"  style="height: 80px;"  ></textarea>
		<!-- 
	 
		<div class="col-xs-7">
			
		
			<?= TrackingHelper::generateRemarkHTML($RemarkList);?>
			
		</div>
		<div  class="col-xs-5">
			<textarea id="tracking_remark" name="tracking_remark" class="form-control"  ></textarea>
		</div>
		
		 -->
	</div>
	</div>
</div>