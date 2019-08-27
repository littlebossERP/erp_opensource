<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\message\helpers\TrackingMsgHelper;

$letter_template_variance_list = [
	'收件人名称' , 
	'收件人国家' ,
	'收件人地址，包含城市' ,
	'收件人邮编' ,
	'收件人电话' ,
	'平台订单号' ,
	'ebay订单SRN' ,
	'订单金额' ,
	'订单物品列表(商品sku，名称，数量，单价)' ,
	'ebay订单Item Id' ,
	'包裹物流号' ,
	'包裹递送物流商' ,
	'买家查看包裹追踪及商品推荐链接',
	
];

$lay_out_list = [
	'1'=>'布局1',
	'2'=>'布局2',
	'3'=>'布局3',
				
];
if (!empty($data['track_no'])){
	if (is_string($data['track_no'])){
		$trackNoList = explode(',', $data['track_no']);
	}elseif (is_array($data['track_no'])){
		$trackNoList = $data['track_no'];
	}else{
		$trackNoList = [];
	}
	
}else {
	$trackNoList = [];
}
$puid = \Yii::$app->user->identity->getParentUid();
$platform = empty($_REQUEST['platform'])?'':$_REQUEST['platform'];
$recom_prod_groups = TrackingMsgHelper::getCustomizedRecommendedGroupByPlatform($puid,$platform);
$default_platform='';
$default_sellerid='';
$default_template_id='';
/*
if(!empty($recom_prod_groups)){
	foreach ($recom_prod_groups as $group){
		if(!empty($group['platform'])) $default_platform = $group['platform'];
		if(!empty($group['seller_id'])) $default_sellerid = $group['seller_id'];
		break;
	}
}
*/
//print_r($data);
if(!empty($data['addi_info'])){
	$addi_info = json_decode($data['addi_info'],true);
	if(!empty($addi_info['recom_prod']) && $addi_info['recom_prod']=='Y' && !empty($addi_info['recom_prod_group']))
		$default_template_id = (int)$addi_info['recom_prod_group'];
}


$tmp_group_infos=[];
foreach ($recom_prod_groups as $group){
	$tmp_group_infos[$group['id']]=[
		'id'=>$group['id'],
		'seller_id'=>$group['seller_id'],
		'platform'=>$group['platform'],
		'group_name'=>$group['group_name'],
	];
	if($default_template_id==(int)$group['id']){
		$default_platform = $group['platform'];
		$default_sellerid = $group['seller_id'];
	}
}
$recom_prod_groups = $tmp_group_infos;
?>

<script type="text/javascript">
StationLetter.recomGroups=<?=json_encode($recom_prod_groups)?>;
</script>
<style>
<!--

-->
#send-station-letter label{
font-family: SimSun;
	font-size: 12px;
	  white-space: nowrap;
	  margin-top: 5px;
}

.letter_tittle{
  color: #f0ad4e;
  font-size: 16px;
  line-height: 29px;
	font-family: SimSun;
}

.form-group>button{
	margin: 5px 0 10px 0;
}

.panel-body {
  padding: 0px 0px 0px 0px;
}

.form-group{
margin-bottom: 0px;

}

.form-horizontal .form-group {
  margin-right: 0px;
  margin-left: 0px;
}

#preview_track_no{
	margin-right: 15px;
	float:right;
	display:none;
}
</style>
<div class="panel">
	<?php if (!empty($data['showtitle'])):?>
		<h4 for="letter_top_tittle" class="letter_tittle"><?= TranslateHelper::t('批量发信')?></h4>
	<?php endif;?>
	<!-- Default panel contents -->
	<div class="panel-body">
		<form class="form-horizontal" role="form" id="send-station-letter">
			<input type="hidden" name="pos" value="<?=isset($_REQUEST['pos'])?$_REQUEST['pos']:'' ?>">
			<div class="form-group">
				<input type="hidden" name="template_id" id="template_id" value="">
				<input type="hidden" name="path" id="path" value="">
				<input type="hidden" name="op_method" id="op_method" value="">
				<input type="hidden" name="msg_id" id="msg_id" value="">
				
				<label for="order_no_list" class="col-sm-1 control-label"  qtipkey=""><?= TranslateHelper::t('收件人订单号')?></label>
				<div class="col-sm-11">

					<textarea class="form-control" name="order_no_list"
						id="order_no_list"><?= empty($data['order_no'])?"":$data['order_no']?></textarea>

				</div>
			</div>

			<div name="div_letter_template_used" class="form-group">
				<label for="letter_template_used" class="col-sm-1 control-label"  qtipkey="message_template"><?= TranslateHelper::t('使用模板')?></label>
				<div class="col-sm-5">
						
					<select name="letter_template_used" class="form-control">
						<option value="-2"><?= TranslateHelper::t("自动匹配")?></option>
						<?php foreach ($data['listTemplate'] as $row):?>
						<option value="<?= $row['id']?>"><?= TranslateHelper::t($row['name'])?></option>
						
						<?php endforeach;?>
						<option value="-1"><?= TranslateHelper::t("新建模版")?></option>
						
					</select>
				</div>
				<div id="div_new_template">
				</div>
			</div>
			<div name="div_auto_template" class="form-group">
			<!-- 
				<label class="col-sm-1"></label>
				<div class="col-sm-11">
					<?php if (!empty($data['matchRoleTracking'])):?>
					<table class="table">
						<thead>
							<tr>
								<th><?= TranslateHelper::t('物流号')?></th>
								<th><?= TranslateHelper::t('匹配内容模板')?></th>
								<th><?= TranslateHelper::t('订单号')?></th>
								<th><?= TranslateHelper::t('递送国家')?></th>
								<th><?= TranslateHelper::t('跟踪连接')?></th>
								<th><?= TranslateHelper::t('匹配状态')?></th>
								<th><?= TranslateHelper::t('操作')?></th>
							</tr>
							<?php foreach ($data['matchRoleTracking'] as $row):?>
							<tr>
								<td><?= $row['track_no']?></td>
								<td><?= $row['role_name'].(empty($data['listTemplateAddinfo'][$row['template_id']]['name'])?"":":".$data['listTemplateAddinfo'][$row['template_id']]['name'])?><input type="hidden" name="match_role_tracker" value="<?= $row['template_id']?>" data-track-no="<?= $row['track_no']?>"></td>
								<td><?= $row['platform'].':'.$row['order_id']?></td>
								
								<td><?= $row['nation']?></td>
								<td><?= (!empty($data['listTemplateAddinfo'][$row['template_id']]) && $data['listTemplateAddinfo'][$row['template_id']]['recom_prod'] =='Y')?TranslateHelper::t('是'):TranslateHelper::t('否')?></td>
								<td><?= TranslateHelper::t('匹配成功')?></td>
								<td><a onclick="StationLetter.showPreviewBoxByObj(this)"><?= TranslateHelper::t('预览')?></a></td>
							</tr>
							<?php endforeach;?>
						</thead>
					</table>
					<?php endif;?>
					
					<?php if (!empty($data['unMatchRoleTracking'])):?>
					<p><?= TranslateHelper::t('匹配模板失败，请手动指定使用模板')?></p>
					<table class="table">
						<thead>
							<tr>
								<th><?= TranslateHelper::t('物流号')?></th>
								<th><?= TranslateHelper::t('匹配内容模板')?></th>
								<th><?= TranslateHelper::t('订单号')?></th>
								<th><?= TranslateHelper::t('递送国家')?></th>
								<th><?= TranslateHelper::t('跟踪连接')?></th>
								<th><?= TranslateHelper::t('匹配状态')?></th>
								<th><?= TranslateHelper::t('操作')?></th>
							</tr>
							<?php foreach ($data['unMatchRoleTracking'] as $row):?>
							<tr>
								<td><?= $row['track_no']?></td>
								<td>
									<select name="unmatch_template" data-track-no="<?= $row['track_no']?>">
										<option value="-1"><?= TranslateHelper::t("未指定")?></option>
										<?php foreach ($data['listTemplate'] as $arow):?>
										<option value="<?= $arow['id']?>"><?= TranslateHelper::t($arow['name'])?></option>
										<?php endforeach;?>
									</select>
								</td>
								<td><?= $row['platform'].':'.$row['order_id']?></td>
								
								<td><?= $row['nation']?></td>
								<td data-recom-prod=''><?= TranslateHelper::t('否')?></td>
								<td><?= TranslateHelper::t('匹配失败')?></td>
								<td><a onclick="StationLetter.showPreviewBoxByObj(this)"><?= TranslateHelper::t('预览')?></a></td>
							</tr>
							<?php endforeach;?>
						</thead>
					</table>
					<?php endif;?>
				</div>
				 -->
			</div>

			<div name="div_manual_template" class="form-group">
				<label for="letter_theme" class="col-sm-1 control-label"  qtipkey="message_title"><?= TranslateHelper::t('标 题')?></label>
				<div class="col-sm-11">
					<input type="text" class="form-control" id="subject"
						name="subject"	value="<?= empty($data['subject'])?"":$data['subject'] ?>" />
				</div>
			</div>

			<div name="div_manual_template"  class="form-group">
				<label for="letter_template_variance" class="col-sm-1 control-label"  qtipkey="message_variables"><?= TranslateHelper::t('可用变量')?></label>
				<div class="col-sm-5">

					<select name="letter_template_variance" class="form-control">
						<?php foreach ($letter_template_variance_list as $variance):?>
						<option value="[<?= $variance?>]"><?= $variance?></option>
						<?php endforeach;?>
					</select>
					
				</div>

				<button type="button" class="btn btn-default btn-sm"
							onclick="StationLetter.addLetterVariance()" qtipkey="msg_insert_var">
							<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
		    		<?= TranslateHelper::t('插入变量')?>
		    	</button>
		    	
		    	<?php /*if (!empty($data['hideSendMessageBtn'])):?>
		    		<select id ="preview_track_no" name="preview_track_no" class="eagle-form-control">
						<?php foreach($trackNoList as $trackno):?>
						<option value="<?= $trackno?>"><?= $trackno?></option>
						<?php endforeach;?>
					</select>
		    	<?php endif;*/?>
			</div>

			<div name="div_manual_template"  class="form-group div_template">
				<div class="col-sm-1"></div>
				<div class="col-sm-11">
					<textarea class="form-control" name="letter_template" id="letter_template" style="height:300px;"><?= empty($data['body'])?"":$data['body']?></textarea>
				</div>
				<div style="display:none;">
					<label class="col-sm-1"><?= TranslateHelper::t('布局')?></label>
					<div class="col-sm-5">
						<select name="template_layout">
						<?php foreach($lay_out_list as $lay_out_id=>$lay_out_label):?>
						<option value="<?= $lay_out_id?>" <?=($lay_out_id=='1')?"selected":'' ?> ><?= TranslateHelper::t($lay_out_label)?></option>
						<?php endforeach;?>
						</select>
					</div>
					<label class="col-sm-1"><?= TranslateHelper::t('商品展示数量')?></label>
					<div class="col-sm-5">
						<select name="recom_prod_count">
						<?php for($i=1;$i<9;$i++):?>
						<option value="<?= $i*2?>" <?=($i==4)?"selected":'' ?>><?= $i*2?></option>
						<?php endfor;?>
						</select>
					</div>
				</div>
				
				<div style="margin-left:12px;margin-top:10px;display:inline-block;color:blue"><?= TranslateHelper::t('仅当选择的订单都属于同一平台及同一卖家账号，且该平台账号已经有对应的自定义推荐商品分组设置时，才可选择分组。')?></div>
				<div style="<?=empty($data['isSameSeller'])?"display:none;":"" ?>" id="select_recom_group">
					<label class="col-sm-1" style="padding-top: 5px"><?= TranslateHelper::t('销售平台')?></label>
					<div class="col-sm-2">
						<input id="template_platform" value="<?=$default_platform ?>" readonly class="form-control">
					</div>
					<label class="col-sm-1"  style="padding-top: 5px"><?= TranslateHelper::t('卖家账号')?></label>
					<div class="col-sm-2">
						
						<input id="template_sellerid" value="<?=$default_sellerid ?>" readonly class="form-control">
					</div>
					<label class="col-sm-1"  style="padding-top: 5px"><?= TranslateHelper::t('推荐商品组')?></label>
					<div class="col-sm-4">
						<select name="recom_prod_group" class="form-control">
						<option value="0"><?= TranslateHelper::t('请选择推荐商品分组')?></option>
						<?php foreach($recom_prod_groups as $group_id=>$group){?>
						<option value="<?=$group_id?>"><?= TranslateHelper::t($group['group_name'])?></option>
						<?php } ?>
						</select>
					</div>
				</div>
			</div>
			
			<div id="div_preview" style="display: none">
				<div  class="form-group"  > 
					<label for="div_preview_subject" class="col-sm-1 control-label"><?= TranslateHelper::t('标 题')?>:</label>
					<div id="div_preview_subject" class="col-sm-11 form-control-static">
	
					</div>
				</div>
				<div  class="form-group"  > 
					<label for="div_preview_content" class="col-sm-1 control-label"><?= TranslateHelper::t('内 容')?>:</label>
					<div id="div_preview_content" class="col-sm-11 form-control-static"></div>
				</div>
				
			</div>
		</form>
	</div>
</div>
