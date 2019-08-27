<?php

use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\SizePager;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

//$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("amazoncs.QuestList.seller_site_list=".json_encode($seller_site_list), \yii\web\View::POS_READY);
$this->registerJs("amazoncs.QuestList.template_list=".json_encode($quest_templates), \yii\web\View::POS_READY);

$this->registerJs("amazoncs.QuestList.initSelectDom()", \yii\web\View::POS_READY);
$active = '';
$uid = \Yii::$app->user->id;

$status_mapping = ['P'=>'等待发送','S'=>'发送中','C'=>'已发送','F'=>'发送失败','CF'=>'创建失败','CANCEL'=>'已取消'];

?>


<style>
.table td hr{
	margin:0px;
	border-top: 1px solid rgb(196,196,196);
}
</style>

<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper">
		<form class="form-inline" id="form1" name="form1" action="/amazoncs/amazoncs/quest-list" method="post" style="margin-bottom:10px;">
			<input type="hidden" name="platform" value="amazon" >
			<input type="hidden" name="status" value="<?=@$_REQUEST['status']?>" >
			<select name="seller_id" class="form-control" id="seller_id_select" style="width:150px;margin:0px;">
				<option value="">卖家账号</option>
				<?php foreach ($MerchantId_StoreName_Mapping as $seller=>$name){?>
				<option value="<?=$seller?>" <?=($seller==@$_REQUEST['seller_id'])?'selected':''?> ><?=$name?></option>
				<?php } ?>
			</select>
			<?php //echo Html::dropDownList('seller_id',@$_REQUEST['seller_id'],$MerchantId_StoreName_Mapping,['class'=>'iv-input','id'=>'seller_id_select','style'=>'width:150px;margin:0px;','prompt'=>'卖家账号']); ?>
			<?php 
				$default_site_list = [];
				if(!empty($_REQUEST['seller_id']))
					$default_site_list = empty($seller_site_list[$_REQUEST['seller_id']])?[]:$seller_site_list[$_REQUEST['seller_id']];
			?>
			<select name="site_id" class="form-control" id="site_id_select" style="width:150px;margin:0px;">
				<option value="">销售站点</option>
				<?php foreach ($default_site_list as $site=>$site_name){?>
				<option value="<?=$site?>" <?=($site==@$_REQUEST['site_id'])?'selected':''?> ><?=$site_name?></option>
				<?php } ?>
			</select>
			
			<?php //echo Html::dropDownList('site_id',@$_REQUEST['site_id'],$default_site_list,['class'=>'iv-input','id'=>'site_id_select','style'=>'width:150px;margin:0px;','prompt'=>'站点']); ?>
			<?php 
				$default_template_list = [];
				if(!empty($_REQUEST['seller_id']) && !empty($_REQUEST['site_id'])){
					foreach ($quest_templates as $template){
						if($template['seller_id']==$_REQUEST['seller_id'] && $template['site_id']==$_REQUEST['site_id'])
							$default_template_list[$template['id']]=$template['name'];
					}
				}elseif(!empty($_REQUEST['seller_id']) && empty($_REQUEST['site_id'])){
					foreach ($quest_templates as $template){
						if($template['seller_id']==$_REQUEST['seller_id'])
							$default_template_list[$template['id']]=$template['name'];
					}	
				}elseif(empty($_REQUEST['seller_id']) && empty($_REQUEST['site_id'])){
					foreach ($quest_templates as $template){
						$default_template_list[$template['id']]=$template['name'];
					}
				}
			?>
			<select name="quest_template_id" class="form-control" id="quest_template_id" style="width:250px;margin:0px;">
				<option value="">模板名称</option>
				<?php foreach ($default_template_list as $template_id=>$template_name){?>
				<option value="<?=$template_id?>" <?=($template_id==@$_REQUEST['quest_template_id'])?'selected':''?> ><?=$template_name?></option>
				<?php } ?>
			</select>
			
			<div class="input-group iv-input" style="border-radius: 0px;border: 1px solid #b9d6e8;height: 30px;">
		        <?php $sel = [
		        	'order_source_order_id'=>'对应订单号',
					'quest_number'=>'任务批次',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num','style'=>'width:150px'])?>
		      	
		    </div>
		    
			<?php //echo Html::textInput('quest_number',@$_REQUEST['quest_number'],['class'=>'iv-input','placeholder'=>'任务批次','style'=>'width:150px'])?>
			<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		</form>
		
		<div class="input-group" style="float:left;width:100%;margin:5px 0px;">
			<?php if($_REQUEST['status']=='pending-send'){ ?>
			<a class="btn btn-info" href="javascript:void(0)" onclick="amazoncs.QuestList.batchCancelPendingSend()" style="margin:3px;padding:3px 6px;">批量取消发送</a>
			<?php } ?>
			<?php if($_REQUEST['status']=='F'){ ?>
			<a class="btn btn-info" href="javascript:void(0)" onclick="amazoncs.QuestList.batchDelQuest()" style="margin:3px;padding:3px 6px;">批量删除任务</a>
			<?php } ?>
		</div>
		
		<div>
			<table class="table">
				<tr>
					<th width="1%" style="">
						<input id="ck_all" class="ck_0" type="checkbox" onchange="selected_switch()">
					</th>
					<th>店铺(站点)/订单号</th>
					<th>模板名称/任务批次</th>
					<th>状态</th>
					<th>发出的邮箱/目标邮箱/买家名称</th>
					<th><i title="点击进行排序"><?=$sort->link('pending_send_time_location',['label'=>TranslateHelper::t('预计发出时间')]) ?></i></th>
					<th><i title="点击进行排序"><?=$sort->link('sent_time_location',['label'=>TranslateHelper::t('发出时间')]) ?></i></th>
					<th>操作</th>
				</tr>
				<?php if(!empty($mail_quest_list)): foreach ($mail_quest_list as $index=>$quest){
				?>
				<tr>
					<td>
						<label><input type="checkbox" class="ck" name="quest_id[]" value="<?=$quest->id?>" data-platform="<?=$quest->platform?>"></label>
					</td>
					<td><span><?=empty($MerchantId_StoreName_Mapping[$quest->seller_id])?$quest->seller_id:$MerchantId_StoreName_Mapping[$quest->seller_id]?></span> <i style="font-style:italic;letter-spacing:1px;font-weight:600;">(<?=empty($MarketPlace_CountryCode_Mapping[$quest->site_id])?$quest->site_id:$MarketPlace_CountryCode_Mapping[$quest->site_id]?>)</i><br><a onclick="amazoncs.QuestList.showOrderInfo('amazon','<?=$quest->order_source_order_id?>')"><?=$quest->order_source_order_id?></a></td>
					<td>
						<?=empty($quest_templates[$quest->quest_template_id]['name'])?$quest->quest_template_id:$quest_templates[$quest->quest_template_id]['name'] ?>
						<hr>
						<?=$quest->quest_number ?>
					</td>
					<td><?=empty($status_mapping[$quest->status])?$quest->status:$status_mapping[$quest->status] ?></td>
					<td><?=$quest->mail_from ?><hr><?=$quest->mail_to ?><hr><?=$quest->consignee ?></td>
					<td>
						北京时间: <?=($_REQUEST['status']=='pending-send' && ( (int)$quest->priority==1 || $quest->status=='S') )?'<span style="color:#2ecc71" title="'.$quest->pending_send_time_location.'">即将发送</span>':date("Y-m-d H",strtotime($quest->pending_send_time_location)).'时' ?>
						<br>
						买家时区: <?=date("Y-m-d H" ,strtotime($quest->pending_send_time_consignee)).'时' ?>
					</td>
					<td>
						北京时间: <?=empty($quest->sent_time_location)?'--':date("Y-m-d H" ,strtotime($quest->sent_time_location)).'时' ?>
						<br>
						买家时区: <?=empty($quest->sent_time_consignee)?'--':date("Y-m-d H" ,strtotime($quest->sent_time_consignee)).'时' ?>
					</td>
					<td>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.QuestList.ViewQuestContent(<?=$quest->id?>)" ><span class="egicon-eye" style="height:12px;" title="查看邮件内容"></span></a>
						<?php if($quest->status=='P'){ //取消任务 按钮?>
						<a href="javascript:void(0)" style="margin-left:5px;color:red" onclick="amazoncs.QuestList.CancelPendingSend(<?=$quest->id?>)" ><span class="glyphicon glyphicon-remove-circle" title="取消该任务"></span></a>
						<?php } ?>
						<?php if(in_array($quest->status, ['P','F','CANCEL'])){ //立即发送 按钮?>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.QuestList.SendImmediately(<?=$quest->id?>)" ><span class="glyphicon glyphicon-ok-circle" title="<?=($quest->status=='P')?'立即发送':'立即重新发送'?>"></span></a>
						<?php } ?>
						<?php if(in_array($quest->status, ['C','F','CANCEL','CF'])){ //删除任务 按钮?>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.QuestList.delQuest(<?=$quest->id?>)" ><span class="glyphicon glyphicon-trash" title="删除记录"></span></a>
						<?php } ?>
						
					</td>
				</tr>
				<tr style="background-color: #d9d9d9;">
					<td colspan="10" class="row" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1;height:5px;padding:0px;"></td>
				</tr>
				<?php }endif; ?>
			</table>
			<?=SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
			<div class="btn-group" style="width: 49.6%;text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' =>$pagination,'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
	</div>
	<div class="order_info"></div>
	<div class="pre-view-win"></div>
</div>

<script>
function selected_switch(){
	var checked = $("#ck_all:checked").length;
	if(checked){
		$(".ck").each(function(){
			$(this).prop("checked",true);
		});
	}else{
		$(".ck").each(function(){
			$(this).prop("checked",false);
		});
	}	
	
}
</script>