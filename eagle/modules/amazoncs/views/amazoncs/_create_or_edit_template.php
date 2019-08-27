<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\amazoncs\models\CsQuestTemplate;
use eagle\modules\util\helpers\SysBaseInfoHelper;

$active = '';
$uid = \Yii::$app->user->id;

if(empty($seller_site_dropdown))
	$seller_site_dropdown=[];

$model_status = [
	'active'=>'正常启用',
	//'test'=>'调试',
	'unActive'=>'已停用',
];
$pending_send_time_arr=[
	''=>'不考虑时段，尽快发送',
	'1'=>'01:00',
	'2'=>'02:00',
	'3'=>'03:00',
	'4'=>'04:00',
	'5'=>'05:00',
	'6'=>'06:00',
	'7'=>'07:00',
	'8'=>'08:00',
	'9'=>'09:00',
	'10'=>'10:00',
	'11'=>'11:00',
	'12'=>'12:00',
	'13'=>'13:00',
	'14'=>'14:00',
	'15'=>'15:00',
	'16'=>'16:00',
	'17'=>'17:00',
	'18'=>'18:00',
	'19'=>'19:00',
	'20'=>'20:00',
	'21'=>'21:00',
	'22'=>'22:00',
	'23'=>'23:00',
	'00'=>'00:00',
];
$filter_order_item_type_arr=[
	'non'=>'不做限制',
	'out'=>'不包含',
	'in'=>'包含',
];

$this->registerJs("amazoncs.Template.initFormValidateInput()", \yii\web\View::POS_READY);

$addi_info = empty($model->addi_info)?[]:json_decode($model->addi_info,true);
$url_title_info = empty($addi_info['url_title'])?[]:$addi_info['url_title'];
$this->registerJs("amazoncs.Template.addi_url_title=".json_encode($url_title_info), \yii\web\View::POS_READY);

//$this->registerJs("amazoncs.Template.shop_list=".json_encode($merchant_list), \yii\web\View::POS_READY);
$this->registerJs("amazoncs.Template.shop_recommended_product_groups=".json_encode($recommended_groups), \yii\web\View::POS_READY);
$this->registerJs("amazoncs.Template.initSelectDom();" , \yii\web\View::POS_READY);

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

?>
<style>
.create-or-edit-win .modal-dialog{
	min-width: 900px;
}

.create-or-edit-win .modal-content{
	min-width: 900px;
}
.create-or-edit-win .modal-body{
	min-width: 900px;
}
.required_input{
	color: red;
}
.content-variance li{
	text-align: left;
	width: 100%;
	float: left;
	/*white-space: nowrap;*/
	border-bottom: 1px solid #cdced0;
	cursor: pointer;
	margin: 3px 0px;
}
.create-or-edit-win table{
	table-layout: fixed;
}

.addi_title_div{
	margin: 3px 0px;
	width: 100%;
	display: inline-block;
}
.addi_title_div label{
	width: 35%;
	float: left;
	text-align: right;
}
.url_addi_tittle{
	width: 60%;
	float: left;
	text-align: left;
}
</style>

<div>
	<form id="template-data-form">
		<input type="hidden" name="act" value="<?=@$_REQUEST['act']?>">
		<input type="hidden" name="platform" value="amazon">
		<input type="hidden" name="id" value="<?=(!empty($_REQUEST['act']) && $_REQUEST['act']=='edit')?$model->id:'' ?>">
		<table style="width:100%">
		<!-- 5td -->
			<tr>
				<td width="130px" style="text-align:right">选择店铺：</td>
				<td width="170px" style="text-align:left">
					<?php 
					$selection = '';
					if(!empty($model->seller_id) && !empty($model->site_id))
						$selection = $model->seller_id.'-'.$model->site_id;
					?>
					<?=Html::dropDownList('seller_site',$selection,$seller_site_dropdown,['class'=>'iv-input','style'=>'width:150px;margin:0px;margin-bottom:5px;'])?>
					<span class="required_input">*</span>
				</td>
				<td width="130px" style="text-align:right">模板名称：</td>
				<td width="170px" style="text-align:left">
					<input type="text" class="iv-input" name="name" id="name" value="<?=@$model->name?>" style="margin-bottom:5px;">
					<span class="required_input">*</span>
				</td>
				<td width="100px" style="text-align:right">模板状态：</td>
				<td width="200px" style="text-align:left">
					<?=Html::dropDownList('status',@$model->status,$model_status,['class'=>'iv-input','style'=>'width:150px;margin:0px;margin-bottom:5px;'])?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="1" style="text-align:left">
					<button type="button" class="btn btn-important" onclick="amazoncs.Template.openSysTemplatesWin()" style="margin-bottom:5px;padding:3px 6px;">选择系统模板</button>
					<span style="margin-top:15px;" qtipkey="select_sys_template"></span>
				</td>
				<td width="100px" style="text-align:right">自动生成任务：</td>
				<td width="200px" style="text-align:left">
					<?=Html::dropDownList('auto_generate',empty($model->auto_generate)?0:$model->auto_generate,['0'=>'不自动生成','1'=>'自动生成'],['class'=>'iv-input','style'=>'width:150px;margin:0px;margin-bottom:5px;'])?>
					<span style="margin-top:15px;" qtipkey="amzcs_quest_auto_generate"></span>
				</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td style="text-align:right">邮件标题：</td>
				<td colspan="5" style="text-align:left">
					<input type="text" class="iv-input" name="subject" id="subject" value="<?=@$model->subject?>" style="width:550px;margin-bottom:5px;">
					<span class="required_input">*</span>
				</td>
			
			</tr>
			<tr>
				<td style="text-align:right;vertical-align:top;">邮件内容：</td>
				<td colspan="4" style="text-align:left">
					<a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_257_293.html')?>" target="_blank" style="padding:4px;margin-bottom:5px;float:left;background-color:rgb(230, 138, 0);color:#fff;">邮件内容注意事项</a>
					<textarea class="iv-input" name="contents" id="contents" style="width:550px;height:350px;"><?=@$model->contents?></textarea>
				</td>
				<td colspan="1" style="vertical-align:top;">
					<span style="float:left;margin-top:5px;font-weight:bold">内容变量:</span>
					<span style="float:left;margin-top:5px" qtipkey="msg_insert_var"></span>
					<ul style="width:160px;max-height:350px;overflow:auto;float:left;" class="content-variance">
						<li data-value="[平台订单号]" onclick="amazoncs.Template.addContentVariance(this)" title="平台订单号">平台订单号</li>
						<li data-value="[店铺SKU]" onclick="amazoncs.Template.addContentVariance(this)" title="店铺SKU">店铺SKU</li>
						<li data-value="[ASIN]" onclick="amazoncs.Template.addContentVariance(this)" title="ASIN">ASIN</li>
						<li data-value="[收件人名称]" onclick="amazoncs.Template.addContentVariance(this)" title="收件人名称">收件人名称</li>
						<li data-value="[买家名称]" onclick="amazoncs.Template.addContentVariance(this)" title="买家名称">买家名称</li>
						<li data-value="[订单物品列表(商品sku，名称，数量，单价)]" onclick="amazoncs.Template.addContentVariance(this)" title="订单物品列表(商品sku，名称，数量，单价)">订单物品列表(商品sku，名称，数量，单价)</li>
						<li data-value="[包裹物流号]" onclick="amazoncs.Template.addContentVariance(this)" title="包裹物流号">包裹物流号</li>
						<li data-value="[包裹递送物流商]" onclick="amazoncs.Template.addContentVariance(this)" title="包裹递送物流商">包裹递送物流商</li>
						<li data-value="[买家查看包裹追踪及商品推荐链接]" onclick="amazoncs.Template.addContentVariance(this)" title="买家查看包裹追踪及商品推荐链接">买家查看包裹追踪及商品推荐链接</li>
						<li data-value="[商品链接]" onclick="amazoncs.Template.addContentVariance(this)" title="商品链接">商品链接</li>
						<li data-value="[带图片的商品链接]" onclick="amazoncs.Template.addContentVariance(this)" title="带图片的商品链接">带图片的商品链接</li>
						<li data-value="[联系卖家链接]" onclick="amazoncs.Template.addContentVariance(this)" title="联系卖家链接">联系卖家链接</li>
						<li data-value="[review链接]" onclick="amazoncs.Template.addContentVariance(this)" title="review链接">review链接</li>
						<li data-value="[feedback链接]" onclick="amazoncs.Template.addContentVariance(this)" title="feedback链接">feedback链接</li>
					</ul>
				</td>
			</tr>
			
			<tr>
				<td style="text-align:right">此规则将适用于过去：</td>
				<td colspan="5" style="text-align:left">
					<input type="text"class="iv-input" name="order_in_howmany_days" id="order_in_howmany_days" value="<?=@$model->order_in_howmany_days?>" style="margin-top:5px;width:40px" >
					<span>天内产生的订单</span><span class="required_input">*(可填范围:7-30)</span>
					<span style="" qtipkey="amzcs_order_in_howmany_days"></span>
				</td>
			</tr>
			
			<tr>
				<?php 
				$for_order_type = $model->for_order_type;
				if(!empty($for_order_type))
					$for_order_type = explode(';', $for_order_type);
				else 
					$for_order_type = [];
				
				$for_FBA = false;
				$for_FBM = false;
				if(in_array('AFN', $for_order_type) || in_array('all', $for_order_type))
					$for_FBA = true;
				if(in_array('MFN', $for_order_type) || in_array('all', $for_order_type))
					$for_FBM = true;
				?>
				<td style="text-align:right">订单类型：</td>
				<td colspan="5" style="text-align:left">
					<label for="for_order_fba">FBA订单</label><input type="checkbox" id="for_order_fba" class="iv-input" name="for_order_type[]" value="AFN" <?=($for_FBA)?'checked':'' ?>>
					<label for="for_order_fbm">FBM订单</label><input type="checkbox" id="for_order_fbm" class="iv-input" name="for_order_type[]" value="MFN" <?=($for_FBM)?'checked':'' ?>>
					<span class="required_input">*</span>
					<span style="" qtipkey="amzcs_for_order_type"></span>
				</td>
			</tr>
			<tr>
				<td style="text-align:right">于订单产生：</td>
				<td colspan="5" style="text-align:left">
					<input type="text" class="iv-input" name="send_after_order_created_days" id="send_after_order_created_days" value="<?=@$model->send_after_order_created_days?>" style="margin-bottom:5px;width:40px">
					<span>天后发送邮件</span><span class="required_input">*</span>
					<span style="" qtipkey="amzcs_send_after_order_created_days"></span>
				</td>
			</tr>
			
			<tr>
				<td style="text-align:right">邮件发送时段：</td>
				<td colspan="5" style="text-align:left">
					<?=Html::dropDownList('pending_send_time',(string)$model->pending_send_time,$pending_send_time_arr,['class'=>'iv-input','style'=>'width:200px;margin:0px;margin-bottom:5px;'])?>
					<span style="" qtipkey="amzcs_pending_send_time"></span>
				</td>
			</tr>
			<tr>
				<td style="text-align:right;vertical-align:top;"><span style="margin-top:9px;float:right;">订单剔除选项：</span></td>
				<td colspan="5" style="text-align:left">
					<input type="checkbox" id="can_send_when_reviewed" class="iv-input" name="can_send_when_reviewed" value="N" <?=(empty($model->can_send_when_reviewed) || $model->can_send_when_reviewed=='N')?'checked':'' ?>><label for="can_send_when_reviewed">排除已经留过Review的订单</label>
					<span style="" qtipkey="amzcs_can_send_when_reviewed"></span>
					<br>
					<input type="checkbox" id="can_send_when_feedbacked" class="iv-input" name="can_send_when_feedbacked" value="N" style="" <?=(empty($model->can_send_when_feedbacked) || $model->can_send_when_feedbacked=='N')?'checked':'' ?>><label for="can_send_when_feedbacked">排除已经留过FeedBack的订单</label>
					<span style="" qtipkey="amzcs_can_send_when_feedbacked"></span>
					<br>
					<input type="checkbox" id="can_shen_when_contacted" class="iv-input" name="can_shen_when_contacted" value="N" style="" <?=(empty($model->can_shen_when_contacted) || $model->can_shen_when_contacted=='N')?'checked':'' ?>><label for="can_shen_when_contacted">排除已有过往来邮件的订单</label>
					<span style="" qtipkey="amzcs_can_shen_when_contacted"></span>
					<br>
					<!-- 
					<input type="checkbox" id="can_send_to_blacklist_buyer" class="iv-input" name="can_send_to_blacklist_buyer" value="N" style="" <?=(empty($model->can_send_to_blacklist_buyer) || $model->can_send_to_blacklist_buyer=='N')?'checked':'' ?>><label for="can_send_to_blacklist_buyer">排除买家在黑名单的订单</label>
					<br>
					 -->
				</td>
			</tr>
			<tr>
				<td style="text-align:right">商品过滤：</td>
				<td colspan="5" style="text-align:left">
					订单 <?=Html::dropDownList('order_item_key_type',@$model->order_item_key_type,['sku'=>'SKU','asin'=>'ASIN'],['class'=>'iv-input',])?> 中 <?=Html::dropDownList('filter_order_item_type',@$model->filter_order_item_type,$filter_order_item_type_arr,['class'=>'iv-input','style'=>'margin-bottom:5px;'])?> 以下的
					<input type="text" class="iv-input" name="order_item_keys" value="<?=@$model->order_item_keys?>" >
					<span style="" qtipkey="amzcs_filter_order_item"></span>
				</td>
			</tr>
			<?php if( preg_match('/\[买家查看包裹追踪及商品推荐链接\]/', $model->contents)){
						$recommended_setting_display = '';
					}else 
						$recommended_setting_display = 'display:none';
			?>
			<tr id="recommended_product_setting" style="<?=$recommended_setting_display?>;">
				<td style="text-align:right">推荐商品分组：</td>
				<td colspan="5" style="text-align:left">
					<?php if(empty($addi_info['recommended_group']))
						$recommended_product_group=0;
					else 
						$recommended_product_group=(int)$addi_info['recommended_group'] ;
					?>
					<select id="recommended_group" name="recommended_group" class="iv-input" value="<?=$recommended_product_group?>">
						<option value="0" <?=empty($recommended_product_group)?"selected":''; ?>></option>
						<?php if(empty($default_recommended_group)) $default_recommended_group=[]; ?>
						<?php foreach ($default_recommended_group as $key=>$data){ ?>
						<option value="<?=$key?>" <?=((int)$recommended_product_group==(int)$key )?"selected":''; ?>><?=$data['group_name']?></option>
						<?php } ?>
					</select>
					<span style="" qtipkey="amzcs_recommended_product_setting"></span>
				</td>
			</tr>
		</table>
		<div class="parma_url_addi_tittle" style="display:none">
		</div>
	</form>
</div>