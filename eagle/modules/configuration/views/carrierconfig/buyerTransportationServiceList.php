<?php
use yii\helpers\Html;
?>

<style>

.platform_label_div{
	color: #393939;
	font-size: 14px;
	float: left;
    width: 33%;
    margin: 0 0 5px 0;
}

.two_column_50{
    width: 50%;
}

.two_column_25{
    width: 25%;
}

.modal-body{
 	width:980px; 
}

#over-lay .modal-footer, .iv-modal .modal-footer{
	text-align: right;
}

.all_select_platform_service_div{
	margin-top: 5px;
}

</style>

<div style='width: 950px;'>
	<!-- tab panes start -->
	<div>
    	<div class="div-input-group" style="width:200px;float:right;">
			<div style="" class="input-group">
				<input name="txt_search_selected_country" type="text" class="form-control" style="height:34px;float:left;width:100%;" onchange='txt_search_selected_buyer_service(this)' placeholder="搜索" value="">
				<span class="input-group-btn" style="">
					<button type="button" class="btn btn-default" style="">
						<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				    </button>
			    </span>
			</div>
		</div>
		
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist" style="height:42px;">
			<li role="presentation" class="active sys" id='aliexpress_platform_li'>
				<a class='tablist_class index_li_a' value='' href="#aliexpress_platform" role="tab" data-toggle="tab" data="aliexpress_platform" ><label>速卖通</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='ebay_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#ebay_platform" role="tab" data-toggle="tab" data="ebay_platform" ><label>eBay</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='amazon_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#amazon_platform" role="tab" data-toggle="tab" data="amazon_platform" ><label>Amazon</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='cdiscount_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#cdiscount_platform" role="tab" data-toggle="tab" data="cdiscount_platform"><label>Cdiscount</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='priceminister_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#priceminister_platform" role="tab" data-toggle="tab" data="priceminister_platform"><label>Priceminister</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='newegg_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#newegg_platform" role="tab" data-toggle="tab" data="newegg_platform"><label>newegg</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='linio_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#linio_platform" role="tab" data-toggle="tab" data="linio_platform"><label>linio</label><span class="badge country_count"></span></a>
			</li>
			<li role="presentation" class="self" id='jumia_platform_li'>
				<a class='tablist_class index_li_a' value='self' href="#jumia_platform" role="tab" data-toggle="tab" data="jumia_platform"><label>jumia</label><span class="badge country_count"></span></a>
			</li>
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<!-- 速卖通 -->
			<div role="tabpanel" class="tab-pane active" id="aliexpress_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="aliexpress_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='aliexpress_platform_selected' class='platform_selected'>
					<?php
						foreach ($aliexpress_services as $tmp_platformKey => $tmp_platformVal){
					?>
					<div class="platform_label_div two_column_50" >
						<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[aliexpress]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
					</div>
					<?php
						}
					?>
				</div>
			</div>
			<!-- eBay -->
			<div role="tabpanel" class="tab-pane" id="ebay_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="ebay_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
						
						<?= Html::dropDownList('eaby_site_select','',$ebay_services_site, ['prompt'=>'请选择站点','onchange'=>'eaby_site_change(this)'])?>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='ebay_platform_selected' class='platform_selected'>
					<?php
					foreach ($ebay_services as $tmp_platform_site => $tmp_platformSiteVal){
						foreach ($tmp_platformSiteVal as $tmp_platformKey => $tmp_platformVal){
					?>
					<div class="platform_label_div <?=$tmp_platform_site ?> two_column_50" style='display: none;'>
						<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[ebay][<?=$tmp_platform_site ?>]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
					</div>
					<?php
						}
					}
					?>
				</div>
			</div>
			<!-- Amazon -->
			<div role="tabpanel" class="tab-pane" id="amazon_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="amazon_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='amazon_platform_selected' class='platform_selected'>
				<?php
					foreach ($amazon_services as $tmp_platformKey => $tmp_platformVal){
				?>
				<div class="platform_label_div two_column_25" >
					<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[amazon]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
				</div>
				<?php
					}
				?>
				</div>
			</div>
			<!-- Cdiscount -->
			<div role="tabpanel" class="tab-pane" id="cdiscount_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="cdiscount_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='cdiscount_platform_selected' class='platform_selected'>
				<?php
					foreach ($cdiscount_services as $tmp_platformKey => $tmp_platformVal){
				?>
				<div class="platform_label_div two_column_25" >
					<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[cdiscount]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
				</div>
				<?php
					}
				?>
				</div>
			</div>
			<!-- Priceminister -->
			<div role="tabpanel" class="tab-pane" id="priceminister_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="priceminister_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='priceminister_platform_selected' class='platform_selected'>
				<?php
					foreach ($priceminister_services as $tmp_platformKey => $tmp_platformVal){
				?>
				<div class="platform_label_div two_column_25" >
					<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[priceminister]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
				</div>
				<?php
					}
				?>
				</div>
			</div>
			<!-- newegg -->
			<div role="tabpanel" class="tab-pane" id="newegg_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="newegg_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='newegg_platform_selected' class='platform_selected'>
				<?php
					foreach ($newegg_services as $tmp_platformKey => $tmp_platformVal){
				?>
				<div class="platform_label_div two_column_50" >
					<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[newegg]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
				</div>
				<?php
					}
				?>
				</div>
			</div>
			<!-- linio -->
			<div role="tabpanel" class="tab-pane" id="linio_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="linio_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='linio_platform_selected' class='platform_selected'>
				<?php
					foreach ($linio_services as $tmp_platformKey => $tmp_platformVal){
				?>
				<div class="platform_label_div two_column_50" >
					<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[linio]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
				</div>
				<?php
					}
				?>
				</div>
			</div>
			<!-- jumia -->
			<div role="tabpanel" class="tab-pane" id="jumia_platform">
				<div>
					<div class="all_select_platform_service_div">
						<label><input type="checkbox" value="jumia_platform_selected" onclick="all_platform_service_ck_click(this)" >全选</label>
					</div>
				</div>
				<hr style='margin: 5px 0px;'>
				<div id='jumia_platform_selected' class='platform_selected'>
				<?php
					foreach ($jumia_services as $tmp_platformKey => $tmp_platformVal){
				?>
				<div class="platform_label_div two_column_50" >
					<label><input type="checkbox" value="<?=$tmp_platformKey ?>" name='[jumia]' onclick="buyer_transportation_service_click(this)" ><?=$tmp_platformVal ?></label>
				</div>
				<?php
					}
				?>
				</div>
			</div>
		</div>
	</div>
	<!-- table panes end -->
	
	<div style="height:20px;clear: both;"><hr></div>
    
	<div style='border: 0px solid #dff0d8;'>
		<div style='background-color: #dff0d8;border-color: #d6e9c6;padding:10px;'>
			<span style='font-size: 120%!important;'>已选择</span>
			<div class='pull-right' style='margin-top: -6px;'>
				<?=Html::Button('清空',['class'=>"iv-btn btn-danger btn-xs",'onclick'=>"selected_platform_service_clear()"])?>
			</div>
		</div>
		<div id='buyer_transportation_service_div' style='margin-top: 10px;'>
		</div>
	</div>
</div>

<div style="height:20px;clear: both;"><hr></div>
<div class="modal-footer">
	<button type="button" class="iv-btn btn-primary modal-close selected_platform_service_save" >确定</button>
	<button class="iv-btn btn-default modal-close">取消</button>
</div>