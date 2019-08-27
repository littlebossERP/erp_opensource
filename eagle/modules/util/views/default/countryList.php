<?php
use yii\helpers\Html;
?>
<style>
/* $('.select_country_div').find('label') */

.all_select_country_div{
	color: #393939;
	font-size: 14px;
	margin:10px 0px;
}

.all_select_country_div > label{
	cursor: pointer;
}

.country_label_div{
	color: #393939;
	font-size: 14px;
	float: left;
    width: 33%;
    margin: 0 0 5px 0;
}

.country_label_div > label{
	cursor: pointer;
}

.add_span{
	font-size: 70%;
	margin-left:10px;
}

#over-lay .modal-footer, .iv-modal .modal-footer{
	text-align: right;
}

.country_count{
	margin-left: 5px;
}

span.badge{
	margin-right: 0;
}

.label_red{
	color: red;
}

.modal-body{
 	width:980px; 
}

</style>


<div style='width: 950px;'>
	<!-- tab panes start -->
	<div>
    	<div class="div-input-group" style="width:200px;float:right;">
			<div style="" class="input-group">
				<input name="txt_search_selected_country" type="text" class="form-control" style="height:34px;float:left;width:100%;" onchange='txt_search_selected_country(this)' placeholder="支持中英文搜索" value="">
				<span class="input-group-btn" style="">
					<button type="button" class="btn btn-default" style="">
						<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				    </button>
			    </span>
			</div>
		</div>
		
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist" style="height:42px;">
			<li role="presentation" class="active sys" id='common_country_li'>
				<a class='tablist_class index_li_a' value='' href="#common_country" role="tab" data-toggle="tab" data="common_country" ><label>常用国家</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='asia_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#asia_country" role="tab" data-toggle="tab" data="asia_country" ><label>亚洲</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='europe_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#europe_country" role="tab" data-toggle="tab" data="europe_country" ><label>欧洲</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='north_america_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#north_america_country" role="tab" data-toggle="tab" data="north_america_country"><label>北美洲</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='south_america_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#south_america_country" role="tab" data-toggle="tab" data="south_america_country"><label>南美洲</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='oceania_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#oceania_country" role="tab" data-toggle="tab" data="oceania_country"><label>大洋洲</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='africa_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#africa_country" role="tab" data-toggle="tab" data="africa_country"><label>非洲</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self"id='other_country_li'>
				<a class='tablist_class index_li_a' value='self' href="#other_country" role="tab" data-toggle="tab" data="other_country"><label>其它</label><span class="badge country_count"></span></a>
			</li>
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<!-- 常用国家 -->
			<div role="tabpanel" class="tab-pane active" id="common_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="common_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='common_country_selected'>
					<?php
						foreach ($common_country as $common_countryVal){
					?>
					<div class="country_label_div" id="country_common_<?=$common_countryVal ?>_div" onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
						<label <?php if(($common_countryVal == 'GB') || ($common_countryVal == 'UK')) echo 'class="label_red"'; ?>><input type="checkbox" value="<?=$common_countryVal ?>" en_name='<?=strtoupper($countryList[$common_countryVal]['en_name']) ?>' onclick="common_country_click(this)" <?=in_array($common_countryVal, $selectedCountryArr) ? 'checked' : '' ?>><?=$countryList[$common_countryVal]['cn'] ?></label>
						<a href="javascript:void(0);" title='删除常用国家'><span style="display: none;" class="add_span glyphicon glyphicon-remove" onclick="common_country_remove(this)"></span></a>
					</div>
					<?php
						}
					?>
				</div>
			</div>
			<!-- 亚洲 -->
			<div role="tabpanel" class="tab-pane" id="asia_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="asia_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='asia_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'Asia'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
			<!-- 欧洲 -->
			<div role="tabpanel" class="tab-pane" id="europe_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="europe_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='europe_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'Europe'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label <?php if(($tmpCountryKey == 'GB') || ($tmpCountryKey == 'UK')) echo 'class="label_red"'; ?>><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
			<!-- 北美洲 -->
			<div role="tabpanel" class="tab-pane" id="north_america_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="north_america_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='north_america_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'North America'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
			<!-- 南美洲 -->
			<div role="tabpanel" class="tab-pane" id="south_america_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="south_america_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='south_america_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'South America'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
			<!-- 大洋洲 -->
			<div role="tabpanel" class="tab-pane" id="oceania_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="oceania_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='oceania_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'Oceania'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
			<!-- 非洲 -->
			<div role="tabpanel" class="tab-pane" id="africa_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="africa_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='africa_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'Africa'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
			<!-- 其它 -->
			<div role="tabpanel" class="tab-pane" id="other_country">
				<div>
					<div class="all_select_country_div">
						<label><input type="checkbox" value="other_country_selected" onclick="all_country_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='other_country_selected'>
				<?php 
					foreach ($countryList as $tmpCountryKey => $tmpCountryVal){
					if($tmpCountryVal['continent'] == 'other'){
				?>
				<div class='country_label_div' id='country_label_div_<?=$tmpCountryKey ?>' onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">
					<label><input type="checkbox" value="<?=$tmpCountryKey ?>" en_name='<?=strtoupper($countryList[$tmpCountryKey]['en_name']) ?>' <?=in_array($tmpCountryKey, $selectedCountryArr) ? 'checked' : '' ?> onchange='country_click(this)'><?=$tmpCountryVal['cn'] ?></label>
					
					<a href="javascript:void(0);" title='添加到常用国家' onclick='add_common_country(this)'><span style='display: none;' class="add_span glyphicon glyphicon-plus" onclick=""></span></a>
				</div>
				<?php
					}}
				?>
				</div>
			</div>
		</div>
	</div>
	<!-- table panes end -->
	
	<div style="height:20px;clear: both;"><hr></div>
    
	<div style='border: 0px solid #dff0d8;'>
		<div style='background-color: #dff0d8;border-color: #d6e9c6;padding:10px;'>
			<span style='font-size: 120%!important;'>已选择的国家</span>
			<div class='pull-right' style='margin-top: -6px;'>
				<?=Html::Button('清空',['class'=>"iv-btn btn-danger btn-xs",'onclick'=>"selected_country_clear()"])?>
			</div>
		</div>
		<div id='selected_country_div' style='margin-top: 10px;'>
		<?php if(count($selectedCountryArr) > 0){
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
		} ?>
		</div>
	</div>
</div>

<div style="height:20px;clear: both;"><hr></div>
<div class="modal-footer">
	<button type="button" class="iv-btn btn-primary modal-close selected_country_save" >确定</button>
	<button class="iv-btn btn-default modal-close">取消</button>
</div>