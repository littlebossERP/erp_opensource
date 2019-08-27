<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use \eagle\modules\carrier\models\MatchingRule;
use eagle\modules\inventory\helpers\WarehouseHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->title=TranslateHelper::t('添加运输服务匹配规则');
//$this->params['breadcrumbs'][] = $this->title;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/carrier/match_edit.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$region = WarehouseHelper::countryRegionChName();
?>
<style>
<!--
.sm-input{
	height:21px;
}
-->
body{
color:#637c99;
}
</style>

<div class="tracking-index col2-layout">
<?= $this->render('//layouts/menu_left_carrier') ?>
<!-- 右侧table内容区域 -->
<div class="content-wrapper" >
	<?php if (!empty($errors)){?>
	<div class="alert alert-danger" role="alert">
	<?php foreach ($errors as $error){?>
	<?=$error?><br>
	<?php }?>
	</div>
	<?php }?>
	<form action="" method="post" onsubmit='return checkform()'>
	<?=Html::hiddenInput('return_url',$return_url);?>
	<?=Html::hiddenInput('id',$rule->id)?>
	<div>
	<font style="size:14px;font-weight:bold;">规则名 </font><?=Html::textInput('rule_name',$rule->rule_name,['size'=>'18','class'=>'eagle-form-control','placeholder'=>"规则名必填",'title'=>'规则名'])?>&nbsp;
	<font style="size:14px;font-weight:bold;">运输服务 </font><?=Html::dropDownList('transportation_service_id',$rule->transportation_service_id,$services,['prompt'=>'运输服务','class'=>'eagle-form-control','title'=>'运输服务'])?>&nbsp;
	<font style="size:14px;font-weight:bold;">优先级 </font><?=Html::textInput('priority',isset($rule->priority)?$rule->priority:1,['size'=>'3','class'=>'sm-input','class'=>'eagle-form-control','placeholder'=>"优先级必填",'title'=>'优先级 数字越大优先级越低'])?>&nbsp;
	<font style="size:14px;font-weight:bold;">是否开启 </font><?=Html::dropDownList('is_active',strlen($rule->is_active)?$rule->is_active:1,['0'=>TranslateHelper::t('关闭'),'1'=>TranslateHelper::t('开启')],['prompt'=>'是否开启','class'=>'eagle-form-control','title'=>'是否启用'])?>&nbsp;
	<?= Html::submitButton(TranslateHelper::t('提交'), ['class' => 'btn btn-primary']) ?>
	</div>
	<div style="float: left;">
	<font style="size:14px;font-weight:bold;"><?=TranslateHelper::t('匹配项');?>&nbsp;&nbsp; </font>
	</div>
	<div>
	<?=Html::checkboxList('rules',$rule->rules,MatchingRule::$rules,['class'=>'rules'])?>
	</div>
<!-- ------------------------------------------------------------------------------------- -->
	<div>
	<?php if (!is_array($rule->rules)){$rule->rules=[];}?>
		<ul id="myTab" class="nav nav-tabs" style="height: 41px;">
		<?php foreach (MatchingRule::$rules as $key=>$value){?>
		   <li class="<?=in_array($key, $rule->rules)?'':'sr-only';?> <?php echo $key;?>"><a href="<?php echo '#'.$key;?>" data-toggle="tab"><?php echo $value?></a></li>
		<?php }?>
		</ul>
		<!-- ------------------------------------------------------------- -->
		<div id="myTabContent" class="tab-content">
		<div class='<?=in_array('source', $rule->rules)?'':'sr-only';?> source tab-pane fade' id="source" style="padding-top:8px;">
			<?=Html::checkboxList('source',$rule->source,MatchingRule::$source)?>
		</div>
		<div class='<?=in_array('site', $rule->rules)?'':'sr-only';?> site tab-pane fade' id="site"  style="padding-top:8px;">
			<input class="btn btn-success btn-xs  site" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=site]:visible').prop('checked',true);">
			<input class="btn btn-danger btn-xs  site" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=site]:visible').removeAttr('checked');">
			<?php foreach ($sites as $platform=>$site){?>
			<div class="<?=$platform?>">
			<hr/>
			<input class="btn btn-success btn-xs site" platform=<?=$platform?> type="button" value="<?=TranslateHelper::t($platform.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
			<input class="btn btn-danger btn-xs site" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
			<?=Html::checkboxList('site['.$platform.']',@$rule->site[$platform],$site,['platform'=>$platform])?>
			</div>
			<?php }?>
		</div>
		<div class='<?=in_array('selleruserid', $rule->rules)?'':'sr-only';?> selleruserid tab-pane fade' id="selleruserid" style="padding-top:8px;">
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=selleruserid]:visible').prop('checked',true);">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=selleruserid]:visible').removeAttr('checked');">
			<?php foreach ($selleruserids as $platform=>$selleruserid){?>
			<div class="<?=$platform?>">
			<hr/>
			<input class="btn btn-success btn-xs site" type="button" value="<?=TranslateHelper::t($platform.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
			<input class="btn btn-danger btn-xs site" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
			<?=Html::checkboxList('selleruserid['.$platform.']',@$rule->selleruserid[$platform],$selleruserid)?>
			</div>
			<?php }?>
		</div>
		<div class='<?=in_array('buyer_transportation_service', $rule->rules)?'':'sr-only';?> buyer_transportation_service tab-pane fade' id="buyer_transportation_service" style="padding-top:8px;">
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=buyer_transportation_service]:visible').prop('checked',true);">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=buyer_transportation_service]:visible').removeAttr('checked');">
			<button type="button" class="btn btn-warning btn-xs display_all_transportation_toggle">全部展开/折叠</button>
			<?php foreach ($buyer_transportation_services as $platform=>$buyer_transportation_service){?>
			<div class="<?=$platform?>">
			<?php foreach ($buyer_transportation_service as $site=>$site_services){?>
			<div class="<?=$platform.$site?> <?php if (isset($rule->site[$platform])&&is_array($rule->site[$platform])) {echo in_array($site, @$rule->site[$platform])?'':'';}else{echo '';}?>">
			<hr/>
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t($site.'站点全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
			<button type="button" class="btn btn-warning btn-xs display_toggle">展开/折叠</button>
			<div style="display:none" class="transportation">
			<?=Html::checkboxList('buyer_transportation_service['.$platform.']['.$site.']',@$rule->buyer_transportation_service[$platform][$site],$site_services)?>
			</div>
			</div>
			<?php }?>
			</div>
			<?php }?>
		</div>
		<div class='<?=in_array('warehouse', $rule->rules)?'':'sr-only';?> warehouse tab-pane fade' id="warehouse" style="padding-top:8px;">
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=warehouse]:visible').prop('checked',true);">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=warehouse]:visible').removeAttr('checked');">
			<?=Html::checkboxList('warehouse',$rule->warehouse,$warehouses)?>
		</div>
		<div class='<?=in_array('receiving_country', $rule->rules)?'':'sr-only';?> receiving_country tab-pane fade' id="receiving_country" style="padding-top:8px;">
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=receiving_country]:visible').prop('checked',true);">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=receiving_country]:visible').removeAttr('checked');">
			<button type="button" class="btn btn-warning btn-xs display_all_country_toggle">全部展开/折叠</button>
			<?php foreach ($countrys as $one){?>
			<div>
			<hr/>
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t($region[$one['name']].'('.$one['name'].')'.'全选');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').prop('checked','checked');">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('取消');?>" onclick="$(this).parent().find('input[type=checkbox]:visible').removeAttr('checked');">
			<button type="button" class="btn btn-warning btn-xs display_toggle">展开/折叠</button>
			<div style="display:none" class="region_country">
			<?=Html::checkboxList('receiving_country', $rule->receiving_country,$one['value'])?>
			</div>
			</div>
			<?php }?>
		</div>
		<div class='<?=in_array('total_amount', $rule->rules)?'':'sr-only';?> total_amount tab-pane fade' id="total_amount" style="padding-top:8px;">
			<?=Html::textInput('total_amount[min]',$rule->total_amount['min'],['placeholder'=>'订单总金额最小值'])?> ~
			<?=Html::textInput('total_amount[max]',$rule->total_amount['max'],['placeholder'=>'订单总金额最大值'])?>
		</div>
		<div class='<?=in_array('freight_amount', $rule->rules)?'':'sr-only';?>  freight_amount tab-pane fade' id="freight_amount" style="padding-top:8px;">
			<?=Html::textInput('freight_amount[min]',$rule->freight_amount['min'],['placeholder'=>'买家支付运费最小值'])?> ~
			<?=Html::textInput('freight_amount[max]',$rule->freight_amount['max'],['placeholder'=>'买家支付运费最大值'])?>
		</div>
		<div class='<?=in_array('total_weight', $rule->rules)?'':'sr-only';?>  total_weight tab-pane fade' id="total_weight" style="padding-top:8px;">
			<?=Html::textInput('total_weight[min]',$rule->total_weight['min'],['placeholder'=>'总重量最小值'])?> g~
			<?=Html::textInput('total_weight[max]',$rule->total_weight['max'],['placeholder'=>'总重量最大值'])?> g
		</div>
		<div class='<?=in_array('product_tag', $rule->rules)?'':'sr-only';?>  product_tag tab-pane fade' id="product_tag" style="padding-top:8px;">
			<input class="btn btn-success btn-xs" type="button" value="<?=TranslateHelper::t('全部全选');?>" onclick="$('input[name^=product_tag]').prop('checked',true);">
			<input class="btn btn-danger btn-xs" type="button" value="<?=TranslateHelper::t('全部取消');?>" onclick="$('input[name^=product_tag]').removeAttr('checked');">
			<hr/>
			<?=Html::checkboxList('product_tag',$rule->product_tag,$product_tags)?>
		</div>
	</div>
	</div>
	
	</form>
</div>
</div>
