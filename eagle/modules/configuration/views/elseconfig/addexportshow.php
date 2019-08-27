<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\ExcelHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>
<script>
ExportJS.init();
$.initQtip();
</script>
<style>
.modal-box{
	width:1060px;
}
.p0{
	    padding: 0;
}
.mTop10{
	margin-top: 10px;
}
.myj-table {
    width: 100%;
    line-height: 22px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
}
.myj-table2 {
    width: 100%;
    line-height: 22px;
    margin-bottom: 10px;
}
.modal-content table tr td {
    font-size: 13px;
}
table.excl td {
    min-width: 139px;
    height: 32px;
    text-align: left;
    padding: 2px 5px;
}
.myj-table tr td {
    border: 1px solid #ccc;
    text-align: center;
    font-size: 13px;
    padding: 3px;
    word-wrap: break-word;
    word-break: break-all;
	background-color: #eee;
	width:172px;
}
.myj-table2 tr td {
    text-align: center;
    font-size: 13px;
    padding: 0px;
    word-wrap: break-word;
    word-break: break-all;
}
.exportOrder {
    border-radius: 4px;
}
.border3 {
    border: 1px solid #ccc;
}
#exportForm td div {
    width: 160px;
    border: 1px solid #ccc;
    padding: 3px;
    border-radius: 4px;
    cursor: pointer;
    text-align: left;
	padding-left: 5px;
}
.glyphicon {
    position: relative;
    top: 1px;
    display: inline-block;
    font-family: 'Glyphicons Halflings';
    font-style: normal;
    font-weight: 400;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
.checkbox{
	margin-top: 6px;
	margin-bottom: 0px;
}
.orderExportActive {
    background-color: #0099cc;
    color: #fff;
}
.tdDivCont {
    width: 160px;
    border: 1px solid #ccc;
    padding: 0 5px;
    border-radius: 4px;
    font-size: 12px;
    background-color: #fff;
    min-height: 21px;
    margin-right: 0;
}
.tdDivCont .spanObj {
	margin-top: 1px;
    display: inline-block;
    width: 115px;
    height: 21px;
    /*overflow: hidden;*/
    line-height: 18px;
    margin-left: 0;
    padding-left: 5px;
	text-align:left;
   /* white-space: nowrap;*/
}
.tdRemove, .tdPencil {
    cursor: pointer;
}
.mTop5 {
    margin-top: 5px;
}
.topleft{
 	position: relative; 
    top: 5px; 
    left: 6px;
 } 
</style>
<div class="col-xs-12 p0" style="margin-top: -8px;">
	<div class="col-xs-1 p0" style="width:100px;padding-top:8px;margin-top: 8px;" >自定义范本名：</div>
	<div class="col-xs-4 p0">
		<input id="templateName" type="text" class="form-control" value="<?php echo empty($model->name)?'':$model->name; ?>">
	</div>
	<span qtipkey="excel_order_model" class="topleft"></span>
	<!-- <div class="col-xs-4 p0" id="vipDiv" style="margin-left: 20px ;margin-top: 9px;">
		<label class="vipLabel" style="cursor: pointer;">
			<input id="vipPic" type="checkbox" value="" style="cursor: pointer;">
			<input id="level" type="hidden" value="0">
			<input id="imgUrl" type="hidden" value="">
			<span id="vip1" class="vipLabel" > <font id="vipExport">导出订单图片</font></span>
		</label>
	</div>-->
	<button class="btn btn-primary pull-right orderExportReset">重置</button>
</div>
<?php 
$content=ExcelHelper::$content;
$number=1;
$items=array();  //读取字段
$temp1=array();  //需要变样式的字段
$content_items=array();  //字段分组
if(!empty($model->content)){
	$showselect_key = explode(',',$model->content);
	foreach ($showselect_key as $item){
			$item_arr=explode(':',$item);
			if(isset($content[$item_arr[0]]) || strstr($item_arr[0],'-custom-')!=false){
				$items[$number]=$item;
				$number++;
				if(empty($item_arr[1]))
					$temp1[$item_arr[0]]=isset($content[$item_arr[0]])?$content[$item_arr[0]]:'';
				else
					$temp1[$item_arr[0]]=$item_arr[1];
			}
	}
}
$number=1;
foreach ($content as $key=>$contentone){
	$content_items[$number]=$key;
	$number++;
}
echo '<input id="number" type="hidden" value="'.(isset($model->id)?$model->id:-1).'">';
?>
<div class="col-xs-12 p0 mTop10" id="exportTable">
	 <table class="myj-table excl">
		<tbody>
			<?php 
				$content_count=(count($content)%6==0)?(count($content)/6):(floor(count($content)/6)+1);
				$j=1;
				for($i=0;$i<$content_count;$i++){
					?>
						<tr>
							<?php 
								$j_list=$j;
								for($k=1;$k<=6;$k++){
									?>
										<td data-names="<?php echo $j_list; ?>">
										<?php 
											if(!empty($model->content) && !empty($items[$j_list])){
												$items_arr=explode(':',$items[$j_list]);
												if(strstr($items_arr[0],'-custom-')){
													$temp_item_arr=explode('-', $items_arr[0]);
													echo '<div class="tdDivCont"><span class="glyphicon glyphicon-remove tdRemove pull-left mTop5"></span><span class="spanObj" data-field="'.(empty($temp_item_arr[1])?:$temp_item_arr[1]).'" data-customname="'.(empty($temp_item_arr[2])?:$temp_item_arr[2]).'" data-value="'.($items_arr[2]).'" ordername="自定义"> '.(empty($items_arr[1])?$temp_item_arr[1]:$items_arr[1]).'</span><span class="glyphicon glyphicon-pencil tdPencil pull-right mTop5"></span></div>';
												}
												else
													echo '<div class="tdDivCont"><span class="glyphicon glyphicon-remove tdRemove pull-left mTop5"></span><span class="spanObj" data-field="'.(empty($items_arr[0])?:$items_arr[0]).'" data-customname="'.(empty($items_arr[0])?:$items_arr[0]).'" data-value="" ordername=" '.$content[$items_arr[0]].'"> '.(empty($items_arr[1])?$content[$items_arr[0]]:$items_arr[1]).'</span><span class="glyphicon glyphicon-pencil tdPencil pull-right mTop5"></span></div>';
											}
										?>
										</td>
									<?php 
									$j_list=$j_list+$content_count;
								}
								$j++;
							?>
						</tr>
					<?php 
				}
			?>
		</tbody>
	</table>
</div>
<!-- <div class="col-xs-12 p0">
	<div class="col-xs-12 border3 exportOrder">
		<table id="exportForm" class="myj-table2">
			<tbody>
				<?php 
					$j=1;
					$k=1;
					foreach ($content as $key=>$contentone){
						$temp=trim($key);
						if(array_key_exists($temp,$temp1) && $temp!='custom')
							$orderExportActive='orderExportActive';
						else 
							$orderExportActive='';
						if($k==1)
							echo '<tr>';
						echo '<td data-names="'.$j.'">
								<div class="checkbox addTab m0 '.$orderExportActive.'"><span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<span class="addTabContent" data-state="0" data-field="'.$key.'">'.$contentone.'</span></div>
								</td>';
						if($k==6){
							echo '</tr>';
							$k=1;
						}
						else
							$k++;
						$j++;
					}
				?>
			</tbody>
		</table>
	</div>
</div>-->
<div class="col-xs-12 p0">
	<div class="col-xs-12 border3 exportOrder">
		<table id="exportForm" class="myj-table2">
			<tbody>
			<?php 
				$content_count=(count($content)%6==0)?(count($content)/6):(floor(count($content)/6)+1);
				$j=1;
				for($i=0;$i<$content_count;$i++){
					?>
						<tr>
							<?php 
								$j_list=$j;
								for($k=1;$k<=6;$k++){
									?>
										<td data-names="<?php echo $j_list; ?>">
										<?php 
										if(!empty($content_items[$j_list])){
											$temp=trim($content_items[$j_list]);
											if(array_key_exists($temp,$temp1) && $temp!='custom')
												$orderExportActive='orderExportActive';
											else
												$orderExportActive='';
										?>
											<div class="checkbox addTab m0 <?php echo $orderExportActive; ?>"><span class="glyphicon glyphicon-plus"></span>&nbsp;&nbsp;<span class="addTabContent" data-state="0" data-field="<?php echo empty($content_items[$j_list])?'':$content_items[$j_list]; ?>"><?php echo empty($content[$content_items[$j_list]])?'':$content[$content_items[$j_list]]; ?></span></div>
										<?php 
										}
										?>
										</td>
									<?php 
									$j_list=$j_list+$content_count;
								}
								$j++;
							?>
						</tr>
					<?php 
				}
			?>
			</tbody>
		</table>
	</div>
</div>
<div class="modal-footer col-xs-12">
	<button type="button" class="btn btn-primary" onclick="exportshowsave(<?php echo empty($model->id)?'-1':$model->id; ?>)">保存</button>
	<button class="btn-default btn modal-close">关 闭</button>
</div>