<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
?>

<style>
	.modal-content{
		border-color:#797979;
	}
	.modal-header{
		background:#364655;
		height:44px;
		border-bottom:2px solid #797979;
	}
	.modal-header .modal-title {
	    font: bold 18px;
	    color: white;
		height:44px;
		line-height:44px;
	}
	.modal-header .close {
	    color: white;
		height:44px;
		line-height:44px;
	}
	.drop{
		width:250px;
		height:25px;
		line-height:25px;
	}
	.modal-body_rules{
		min-height:300px;
		line-height:18px;
		font-size: 13px;
    	color: rgb(51, 51, 51);
		padding: 5px 5px;
	}
	.clear {
	    clear: both;
	    height: 0px;
	}
	.modal-dialog{
		width:1302px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.distributionDIV{
		width:200px;min-height:500px;border:1px solid #797979;float:left;
	}
	.fullsetDIV{
/* 		margin:5px 0 0 220px; */
	}
	.leftDIVtitle{
		height:35px;
		line-height:35px;
		text-align:center;
		border-bottom:2px solid #797979;
		margin-bottom:8px;
	}
	.right-title{
		font-family: 'Applied Font Bold', 'Applied Font';
		font-weight: 700;
	    font-style: normal;
	    font-size: 24px;
	    color: #333333;
		margin-bottom:25px;
	}
	.full_leftDIV>div{
		min-height:35px;
		padding-bottom:8px;
	}
	.rules>label{
		width:100%;
		padding-left:15px;
	}
	.full_leftDIV{
		width:1220px;float:left;
	}
	.full_rightDIV{
		margin-left:730px;width:0px;
	}
	.rightModal{
		border:1px solid #797979;
	}
	.rightModal-header{
		height:28px;
		line-height:28px;
		text-align:center;
		background:#364655;
		color:white;
		font-weight:bold;
		border-bottom:2px solid #797979;
	}
	.rightModal-body{
		height:420px;
		padding-left:20px;
		overflow-y:auto;
	}
	.rightModal-footer{
		height:40px;
		line-height:40px;
		padding:0 8px;
	}
	.textareabody{
		padding:3px;
	}
	.right_textarea{
		width:552px;
		height:408px;
		padding:5px;
	}
	.rightModal-2{
		padding:25px 8px;
	}
	.leftlist{
		margin:8px 0 0 210px;
	}
	.impor_red{
		color:#ED5466 ;
		background:#F5F7F7;
		padding:0 3px;
		margin-right:3px;
	}
</style>

<div class="modal-dialog">
      <div class="modal-content">
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
		设置运输服务分配规则
	</h4>
</div>
<div class="modal-body_rules">
	<div class="fullsetDIV">
		<div class="full_leftDIV">
			<form id="rulesForm">
			<div class="distributionDIV">
				<div class="leftDIVtitle">分配项</div>
				<?=Html::checkboxList('rules',@$rule['rules']['rules'],$rules,['class'=>'rules'])?>
			</div>
			<?= Html::hiddenInput('id',@$rule['id'])?>
			<?= Html::hiddenInput('is_active',isset($rule['is_active'])?$rule['is_active']:1)?>
			<?= Html::hiddenInput('warehouse_rules_id',@$warehouse_rules_id)?>
			<?= Html::hiddenInput('name',@$rule['rule_name'])?>
			<?= Html::hiddenInput('items_location_provinces',@$rule['rules']['items_location_provinces'])?>
			<?= Html::hiddenInput('items_location_city',@$rule['rules']['items_location_city'])?>
			<?= Html::hiddenInput('receiving_provinces',@$rule['rules']['receiving_provinces'])?>
			<?= Html::hiddenInput('receiving_city',@$rule['rules']['receiving_city'])?>
			<?= Html::hiddenInput('skus',@$rule['rules']['skus'])?>
			<?= Html::hiddenInput('freight_amount[min]',@$rule['rules']['freight_amount']['min'])?>
			<?= Html::hiddenInput('freight_amount[max]',@$rule['rules']['freight_amount']['max'])?>
			<div class="leftlist">
				<label class="right-title"></label>
				<div id = 'items_location_country' style="display:<?= empty($rule['rules']['rules'])?'none':(in_array('items_location_country', $rule['rules']['rules'])?'block':'none');?>"><label>物品所在地国家：</label><a class="btn btn-xs ruleshows">请添加国家</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="mycountry_value"></div></div>
				<div id = 'items_location_provinces' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('items_location_provinces', $rule['rules']['rules'])?'block':'none');?>"><label>物品所在地区：</label><a class="btn btn-xs ruleshows">请填写地区</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="myprovince_value"></div></div>
				<div id = 'items_location_city' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('items_location_city', $rule['rules']['rules'])?'block':'none');?>"><label>物品所在地城市：</label><a class="btn btn-xs ruleshows">请填写城市</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="mycity_value"></div></div>
				<div id = 'receiving_country' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('receiving_country', $rule['rules']['rules'])?'block':'none');?>"><label>收件人国家：</label><a class="btn btn-xs ruleshows">请添加国家</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="receiving_value"></div></div>
				<div id = 'receiving_provinces' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('receiving_provinces', $rule['rules']['rules'])?'block':'none');?>"><label>收件人州/省：</label><a class="btn btn-xs ruleshows">请填写州/省</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="recprovince_value"></div></div>
				<div id = 'receiving_city' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('receiving_city', $rule['rules']['rules'])?'block':'none');?>"><label>收件人城市：</label><a class="btn btn-xs ruleshows">请填写城市</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="reccity_value"></div></div>
				<div id = 'skus' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('skus', $rule['rules']['rules'])?'block':'none');?>"><label>SKU：</label><a class="btn btn-xs ruleshows">请填写或导入SKU</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="sku_value"></div></div>
				<div id = 'sources' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('sources', $rule['rules']['rules'])?'block':'none');?>"><label>平台、账号、站点：</label><a class="btn btn-xs ruleshows">请选择平台、账号、站点</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="sources_value"></div></div>
				<div id = 'freight_amount' style="display:<?=empty($rule['rules']['rules'])?'none':(in_array('freight_amount', $rule['rules']['rules'])?'block':'none');?>"><label>买家支付运费：</label><a class="btn btn-xs ruleshows">请填写买家支付运费金额范围</a><a class="btn btn-xs btn-warning openOrCloseTextValue">折叠</a><div class="freight_value"></div></div>
			</div>
			</form>
		</div>
		<div class="full_rightDIV">
			<div class="rightModal items_location_country" style="display: none">
				<div class="rightModal-header">添加国家</div>
				<div class="rightModal-body">
					<div class=' my_country' id="my_country" style="padding-top:8px;">
						<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=my_country]:visible').prop('checked',true);">
						<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=my_country]:visible').removeAttr('checked');">
						<button type="button" class="btn btn-warning btn-xs display_all_country_toggle">全部展开/折叠</button>
						<button type="button" class="btn btn-warning btn-xs display_all_country_open">全部展开</button>
						<button type="button" class="btn btn-warning btn-xs display_all_country_close">全部折叠</button>
						<?php foreach ($countrys as $one){?>
						<div>
						<hr/>
						<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t($region[$one['name']].'('.$one['name'].')'.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
						<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
						<button type="button" class="btn btn-warning btn-xs display_toggle">展开/折叠</button>
						<div style="display:none" class="region_country">
						<?=Html::checkboxList('my_country', @$rule['rules']['items_location_country'],$one['value'])?>
						</div>
						</div>
						<?php }?>
					</div>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick="setMyCountry();">保存</button>
				</div>
			</div>
			<div class="rightModal items_location_provinces" style="display: none">
				<div class="rightModal-header">填写地区</div>
				<div class="rightModal-body textareabody">
					<?= Html::textarea('Myprovince',@$rule['rules']['items_location_provinces'],['placeholder'=>'地区之间用逗号隔开','class'=>'right_textarea iv-input'])?>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick='setMyProvince()'>保存</button>
				</div>
			</div>
			<div class="rightModal items_location_city" style="display: none">
				<div class="rightModal-header">填写城市</div>
				<div class="rightModal-body textareabody">
					<?= Html::textarea('Mycity',@$rule['rules']['items_location_city'],['placeholder'=>'城市之间用逗号隔开','class'=>'right_textarea iv-input'])?>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick='setMyCity()'>保存</button>
				</div>
			</div>
			<div class="rightModal receiving_country" data='rule4' style="display: none">
				<div class="rightModal-header">添加国家</div>
				<div class="rightModal-body">
					<div class='receiving_country' id="receiving_country" style="padding-top:8px;">
						<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=receiving_country]:visible').prop('checked',true);">
						<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=receiving_country]:visible').removeAttr('checked');">
						<button type="button" class="btn btn-warning btn-xs display_all_country_toggle">全部展开/折叠</button>
						<button type="button" class="btn btn-warning btn-xs display_all_country_open">全部展开</button>
						<button type="button" class="btn btn-warning btn-xs display_all_country_close">全部折叠</button>
						<?php foreach ($countrys as $one){?>
						<div>
						<hr/>
						<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t($region[$one['name']].'('.$one['name'].')'.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
						<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
						<button type="button" class="btn btn-warning btn-xs display_toggle">展开/折叠</button>
						<div style="display:none" class="region_country">
						<?=Html::checkboxList('receiving_countrys', @$rule['rules']['receiving_country'],$one['value'])?>
						</div>
						</div>
						<?php }?>
					</div>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick="setReceivingCountry();">保存</button>
				</div>
			</div>
			<div class="rightModal receiving_provinces" style="display: none">
				<div class="rightModal-header">填写州/省</div>
				<div class="rightModal-body textareabody">
					<?= Html::textarea('Recprovince',@$rule['rules']['receiving_provinces'],['placeholder'=>'州/省之间用逗号隔开','class'=>'right_textarea iv-input'])?>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick='setRecProvince()'>保存</button>
				</div>
			</div>
			<div class="rightModal receiving_city" style="display: none">
				<div class="rightModal-header">填写城市</div>
				<div class="rightModal-body textareabody">
					<?= Html::textarea('Reccity',@$rule['rules']['receiving_city'],['placeholder'=>'城市之间用逗号隔开','class'=>'right_textarea iv-input'])?>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick='setRecCity()'>保存</button>
				</div>
			</div>
			<div class="rightModal skus" style="display: none">
				<div class="rightModal-header">填写SKU</div>
				<div class="rightModal-body textareabody">
					<?= Html::textarea('sku',@$rule['rules']['skus'],['placeholder'=>'SKU之间用逗号隔开','class'=>'right_textarea iv-input'])?>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick='setSKU()'>保存</button>
				</div>
			</div>
			<div class="rightModal sources" style="display: none">
				<div class="rightModal-header">选择平台、账号、站点</div>
				<div class="rightModal-body">
					<div class='source' id ='source' style="padding-top:8px;">
						<label>平台：</label>
						<?=Html::checkboxList('sourceCheck',@$rule['rules']['sources']['source'],$source)?>
					</div>
					<div class='site' id="site"  style="padding-top:8px;">
						<label>站点：</label>
						<input class="btn btn-success btn-xs  site" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=sites]:visible').prop('checked',true);">
						<input class="btn btn-danger btn-xs  site" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=sites]:visible').removeAttr('checked');">
						<BUTTON type="button" class="btn btn-xs btn-warning openOrCloseNextDIV">（站点）展开/折叠</BUTTON>
						<div class="siteDIV" style="display:none;">
							<?php foreach ($sites as $platform=>$site){?>
							<div class="<?=$platform?> <?= empty($rule['rules']['sources']['source'])?'sr-only':(in_array($platform, $rule['rules']['sources']['source'])?'':'sr-only');?>">
							<hr/>
							<input class="btn btn-success btn-xs site" platform=<?=$platform?> type="button" value="<?=TranslateHelper::t($platform.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');siteChange();">
							<input class="btn btn-danger btn-xs site" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');siteChange();">
							<?=Html::checkboxList('sites['.$platform.']',@$rule['rules']['sources']['site'][$platform],$site,['platform'=>$platform])?>
							</div>
							<?php }?>
						</div>
					</div>
					<div class='selleruserid' id="selleruserid" style="padding-top:8px;">
						<label>账号：</label>
						<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=selleruserids]:visible').prop('checked',true);">
						<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=selleruserids]:visible').removeAttr('checked');">
						<button type="button" class="btn btn-xs btn-warning openOrCloseNextDIV">（账号）展开/折叠</button>
						<div class="selleruseridDIV" style="display:none;">
							<?php foreach ($selleruserids as $platform=>$selleruserid){?>
							<div class="<?=$platform?> <?= empty($rule['rules']['sources']['source'])?'sr-only':(in_array($platform, $rule['rules']['sources']['source'])?'':'sr-only');?>">
							<hr/>
							<input class="btn btn-success btn-xs site" type="button" value="<?=TranslateHelper::t($platform.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
							<input class="btn btn-danger btn-xs site" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
							<?=Html::checkboxList('selleruserids['.$platform.']',@$rule['rules']['sources']['selleruserid'][$platform],$selleruserid,['platform'=>$platform])?>
							</div>
							<?php }?>
						</div>
					</div>
					
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick="setSources();">保存</button>
				</div>
			</div>
			<div class="rightModal freight_amount" style="display: none">
				<div class="rightModal-header">填写买家支付运费</div>
				<div class="rightModal-body">
					<div class="rightModal-2">
						<label style="width:130px;text-align:right;">大于等于最小值：</label>
						<?= Html::input('text','minfreight',@$rule['rules']['freight_amount']['min'],['placeholder'=>'买家支付运费最小值','class'=>'iv-input'])?>
						<label>USD</label>
					</div>
					<div class="rightModal-2">
						<label style="width:130px;text-align:right;">小于最大值：</label>
						<?= Html::input('text','maxfreight',@$rule['rules']['freight_amount']['max'],['placeholder'=>'买家支付运费最大值','class'=>'iv-input'])?>
						<label>USD</label>
					</div>
				</div>
				<div class="rightModal-footer">
					<button type="button" class="btn btn-default pull-right rightModal-close" style="margin-left:8px;">关闭</button>
					<button type="button" class="btn btn-success pull-right" onclick='set_freight()'>保存</button>
				</div>
			</div>
		</div>
	</div>
	<div class="clear"></div>
</div>
<div class="modal-footer">
	<label><span class="impor_red">*</span>规则名：</label>
	<?= Html::input('text','newname',@$rule['rule_name'],['placeholder'=>'规则名不能重复','style'=>'width:150px;margin-right:20px;'])?>
	<label>状态：</label>
	<?php $isactive = (isset($rule['is_active'])) ? $rule['is_active'] : 1;?>
	<label><input type="radio" name="is_actives" value="1" <?php if($isactive == 1){echo 'checked';}?>> 开启</label>
	<label><input type="radio" name="is_actives" value="0" <?php if($isactive != 1){echo 'checked';}?>> 关闭</label>
	
	<button type="button" class="btn btn-success" onclick="saveAllRulesByAjaxOver()" style="margin-left:20px;">保存</button>
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
</div><!-- /.modal-content -->
	</div><!-- /.modal -->

<script>

$(function() {
	setMyCountry();
	setMyProvince();
	setMyCity();
	setReceivingCountry();
	setRecProvince();
	setRecCity();
	setSKU();
	setSources();
	set_freight();

	//订单来源
	$('input[name^=sourceCheck]').change(function(){
		$('input[name^="sourceCheck"]').each(function(){
			var n = $(this).val();
			if($(this).prop('checked')){
				$("."+n).removeClass('sr-only');
			}else{
				$("."+n).addClass('sr-only');
			}
		})
	})
	//点击站点
	$('input[name^=site]').click(function(){
		$('input[name^=site]').each(function(){
			var n = $(this).val();
			var platform = $(this).parent().parent().attr('platform');
			if($(this).prop('checked')){
				$("."+platform+n).removeClass('sr-only');
			}else{
				$("."+platform+n).addClass('sr-only');
			}
		})
	})
	$('input[name=is_actives]').change(function(){
		$('input[name=is_active]').val($(this).val());
	});
	$('input[name=newname]').change(function(){
		$('input[name=name]').val($(this).val());
	});
	$('.openOrCloseTextValue').click(function(){
		obj = $(this).next();
		var hidden = obj.css('display');
		if(typeof(hidden)=='undefined' || hidden=='none'){
			$(this).text('折叠');
			obj.css('display','block');
		}else{
			$(this).text('展开');
			obj.css('display','none');
		}
	});
	$('.openOrCloseNextDIV').click(function(){
		obj = $(this).next();
		var hidden = obj.css('display');
		if(typeof(hidden)=='undefined' || hidden=='none'){
			obj.css('display','block');
		}else{
			obj.css('display','none');
		}
	});
	$('.ruleshows').click(function(){
		sobj=$(this).parent();
		var id = sobj.attr('id');
		obj = $('.'+id);
		var hidden = obj.css('display');
		if(typeof(hidden)=='undefined' || hidden=='none'){
			closeAllRightModals();
			obj.css('display','block');
			$('.full_rightDIV').css('width','560');
			$('.full_leftDIV').css('width','620');
		}else if( hidden=='block'){
			obj.css('display','none');
			$('.full_rightDIV').css('width','0');
			$('.full_leftDIV').css('width','1220');
		}
	});
	$('.rightModal-close').click(function(){
		obj=$(this).parent().parent();
		obj.hide();
		$('.full_rightDIV').css('width','0');
		$('.full_leftDIV').css('width','1220');
	});
	$('input[name^=rules]').click(function(){
		var n = $(this).val();
		if($(this).prop('checked')){
			$('.full_rightDIV').css('width','560');
			$('.full_leftDIV').css('width','620');
			$('#'+n).show();
			closeAllRightModals();
			$('.'+n).show();
// 			$('#rulesDIV').html($(this).parent().parent().html());
		}else{
			closeAllRightModals();
			$('#'+n).hide();
			$('.full_rightDIV').css('width','0');
			$('.full_leftDIV').css('width','1220');
		}
	});
	$(".display_all_transportation_toggle").click(function(){
		$('.transportation').each(function(){
			obj=$(this);
			var hidden = obj.css('display');
			if(typeof(hidden)=='undefined' || hidden=='none'){
				obj.css('display','block');
			}else if( hidden=='block'){
				obj.css('display','none');
			}
		})
	});
	$('.display_toggle').click(function(){
		obj=$(this).next();
		var hidden = obj.css('display');
		if(typeof(hidden)=='undefined' || hidden=='none'){
			obj.css('display','block');
		}else if( hidden=='block'){
			obj.css('display','none');
		}
	});
	$(".display_all_country_toggle").click(function(){
		$(this).parent().children('div').find('.region_country').each(function(){
			obj=$(this);
			var hidden = obj.css('display');
			if(typeof(hidden)=='undefined' || hidden=='none'){
				obj.css('display','block');
			}else if( hidden=='block'){
				obj.css('display','none');
			}
		})
	});
	$(".display_all_country_open").click(function(){
		$(this).parent().children('div').find('.region_country').each(function(){
			$(this).css('display','block');
		});
	});
	$(".display_all_country_close").click(function(){
		$(this).parent().children('div').find('.region_country').each(function(){
			$(this).css('display','none');
		});
	});
});

</script>