<?php
use yii\helpers\Html;
?>

<style>

.all_select_platform_div{
	color: #393939;
	font-size: 14px;
	margin:10px 0px;
}

.all_select_platform_div > label{
	cursor: pointer;
}

.platform_label_div{
	color: #393939;
	font-size: 14px;
	float: left;
    width: 33%;
    margin: 0 0 5px 0;
}

.platform_label_div > label{
	cursor: pointer;
}

.add_span{
	font-size: 70%;
	margin-left:10px;
}

#over-lay .modal-footer, .iv-modal .modal-footer{
	text-align: right;
}

.platform_count{
	margin-left: 5px;
}

span.badge{
	margin-right: 0;
}

.label_red{
	color: red;
}

.modal-body{
/*  	width:980px; */
}

</style>

<div style='width: 950px;'>
	<!-- tab panes start -->
	<div>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist" style="height:42px;">
<!-- 		active sys -->
<!-- 		self -->
			<?php
			if(count($selleruseridMap) > 0){
				$tmp_class_location = 'active sys';

				foreach ($selleruseridMap as $selleruseridMapKey => $selleruseridMapVal){
					if(($type == 'oms') && ($platform != $selleruseridMapKey)){
						continue;
					}else if(($type == 'delivery') && ($platform == '')){
					}else if(($type == 'delivery') && ($platform != $selleruseridMapKey)){
						continue;
					}
			?>
			<li role="presentation" class="<?=$tmp_class_location ?>" id='<?=$selleruseridMapKey ?>_platform_li'>
				<a class='tablist_class index_li_a' value='' href="#<?=$selleruseridMapKey ?>_platform" role="tab" data-toggle="tab" data="<?=$selleruseridMapKey ?>_platform" ><label><?=$selleruseridMapKey ?></label><span class="badge platform_count"><?=(count($selleruseridMapVal) > 0 ? count($selleruseridMapVal) : '') ?></span></a>
			</li>
			<?php
					$tmp_class_location = 'self';
				}
			}
			?>
			
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
<!-- 		active -->
			<?php
			if(count($selleruseridMap) > 0){
				$tmp_class_location = 'active';

				foreach ($selleruseridMap as $selleruseridMapKey => $selleruseridMapVal){
					if(($type == 'oms') && ($platform != $selleruseridMapKey)){
						continue;
					}else if(($type == 'delivery') && ($platform == '')){
					}else if(($type == 'delivery') && ($platform != $selleruseridMapKey)){
						continue;
					}
			?>
			<div role="tabpanel" class="tab-pane <?=$tmp_class_location ?>" id="<?=$selleruseridMapKey ?>_platform">
				<div>
					<div class="all_select_platform_div">
						<label><input type="checkbox" value="<?=$selleruseridMapKey ?>_platform_selected" onclick="OrderCommon.all_platform_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='<?=$selleruseridMapKey ?>_platform_selected'>
					<?php
						foreach ($selleruseridMapVal as $selleruseridMapVal_k => $selleruseridMapVal_v){
					?>
					<div class="platform_label_div" id="platform_<?=$selleruseridMapKey ?>_<?=$selleruseridMapVal_k ?>_div" >
						<label ><input type="checkbox" value="<?=$selleruseridMapVal_k ?>" onclick="OrderCommon.platform_sel_click(this)" ><?=$selleruseridMapVal_v ?></label>
					</div>
					<?php
						}
					?>
				</div>
			</div>
			<?php
					$tmp_class_location = '';
				}
			}
			?>
		</div>
	</div>
	<!-- table panes end -->
	
	<div style="height:20px;clear: both;"><hr></div>
    
	<div style='border: 0px solid #dff0d8;'>
		<div style='background-color: #dff0d8;border-color: #d6e9c6;padding:10px;'>
			<span style='font-size: 120%!important;'>已选择的账号</span>
			<div class='pull-right' style='margin-top: -6px;'>
				<?=Html::Button('保存为常用',['class'=>"iv-btn btn-primary btn-xs",'onclick'=>"OrderCommon.selected_platform_common(\"".$type."\",\"".$platform."\")"])?>
				<?=Html::Button('清空',['class'=>"iv-btn btn-danger btn-xs",'onclick'=>"OrderCommon.selected_platform_clear()"])?>
			</div>
		</div>
		<div id='selected_platform_div' style='margin-top: 10px;'>
		<?php 
		if((1 == 0) && (count($selectedCountryArr) > 0)){
				foreach ($selectedCountryArr as $selectedCountryVal){
					$tmpCountryName = '';

					if(isset($countryList[$selectedCountryVal])){
						$tmpCountryName = $countryList[$selectedCountryVal]['cn'];
					}
					
					if($tmpCountryName != ''){
					?>
					<div class="country_label_div" id="country_selected_<?=$selectedCountryVal ?>_div" onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$selectedCountryVal ?>" onclick="country_remove_click(this)" checked><?=$tmpCountryName ?></label>
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style="display: none;" class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
					</div>
					<?php
					}
				}			
		} 
		?>
		</div>
	</div>
</div>

<div style="height:20px;clear: both;"><hr></div>
<div class="modal-footer">
	<button type="button" class="iv-btn btn-primary modal-close selected_platform_save" >确定</button>
	<button class="iv-btn btn-default modal-close">取消</button>
</div>