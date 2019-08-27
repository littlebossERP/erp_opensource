<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
//$this->registerJsFile($baseUrl."js/project/ticket/ticket.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerCssFile($baseUrl."css/catalog/catalog.css");

$this->registerJs("ticket.list.initReplyBtn();" , \yii\web\View::POS_READY);
$this->registerJs("ticket.list.initFormValidate();" , \yii\web\View::POS_READY);
?>
<?php
$topicId =empty($ticket['topic_id'])?0:$ticket['topic_id'];
if(!empty($threads)){
	$ticket['title']=empty($threads[0]['title'])?'':$threads[0]['title'];
	$ticket['body']=empty($threads[0]['body'])?'':$threads[0]['body'];
}
?>
<style>

</style>
<div class="">
	<form class="tick-reply-data">
		<table class="table table-striped table-bordered detail-view">
	   		<tr style="display:none;">
	    		<th>Ticket ID</th>
	    		<td><input type="hidden" name="ticket_id" value="<?=empty($ticket['ticket_id'])?'':$ticket['ticket_id'] ?>" readonly>
	    		</td>
	    	</tr>
			<tr>
				<th><?=TranslateHelper::t('工单标题') ?></th><td><input type="text" class="form-control" value="<?=empty($ticket['title'])?'':$ticket['title'] ?>" readonly></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('所属模块') ?></th>
				<td><input type="text" class="form-control" value="<?=(isset($topic[$topicId]['topic'])?$topic[$topicId]['topic']:'') ?>" readonly style="width:200px;"></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('回复内容') ?></th>
				<td>
					<textarea name="message" class="form-control" style="width:100% !important;height:150px !important;"></textarea>
				</td>
			</tr>
			<!-- 
			<tr>
				<th><?=TranslateHelper::t('添加附件') ?></th>
				<td>	
				</td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('QQ号码') ?></th>
				<td><input type="text" class="form-control" name="contact_info[qq]" value="<?=empty($contact_info['qq'])?'':$contact_info['qq'] ?>" style="width:200px;"></td>
			</tr>
			 -->
		</table>
	</form>
</div>