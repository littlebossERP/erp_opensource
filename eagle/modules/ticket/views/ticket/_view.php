<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\ticket\helpers\TicketHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
//$this->registerJsFile($baseUrl."js/project/ticket/ticket.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerCssFile($baseUrl."css/catalog/catalog.css");

$this->registerJs("ticket.list.initReplyBtn();" , \yii\web\View::POS_READY);

$closed_state_id = TicketHelper::get_Closed_State_Id();
$open_state_id = TicketHelper::get_Open_State_Id();

?>

<style>

</style>

<?php 
$topicId =empty($ticket['topic_id'])?0:$ticket['topic_id'];
$canReply = 1;
if(empty($ticket['status_id']) || (!empty($ticket['status_id']) && (int)$ticket['status_id']!==$open_state_id)){
	$canReply=0;
}
$status_cn = '--';
if(isset($status[$ticket['status_id']])){
	$status_cn = $status[$ticket['status_id']];
	if((int)$ticket['status_id']==$open_state_id){
		if((int)$ticket['isanswered']==1)
			$status_cn='已回复';
		else
			$status_cn='待回复';
	}elseif((int)$ticket['status_id']==$closed_state_id){
		$status_cn='已解决/已撤销已回复';
	}
}

if(!empty($threads)){
	$ticket['title']=empty($threads[0]['title'])?'':$threads[0]['title'];
	$ticket['body']=empty($threads[0]['body'])?'':$threads[0]['body'];
}

?>
<div class="ticket-view">
	<div class="tick-main-info">
	    <table class="table-striped detail-view" style="width:100%">
	   		<tr style="display:none;">
	    		<td>Ticket ID</td>
	    		<td><input type="hidden" name="ticket_id" value="<?=empty($ticket['ticket_id'])?'':$ticket['ticket_id'] ?>" readonly></td>
	    		<td>Thread ID</td>
	    		<td>
	    			<input type="hidden" value="<?=empty($threads[0]['id'])?'':$threads[0]['id'] ?>" readonly>
	    			<input type="hidden" name="canReply" value="<?=$canReply ?>" />
	    			<input type="hidden" name="status_cn" value="<?=$status_cn ?>" />
	    		</td>
	    	</tr>
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('工单标题') ?></td><td><input type="text" class="form-control" name="subject" value="<?=empty($ticket['title'])?'':$ticket['title'] ?>" readonly></td>
			</tr>
			<tr>
				<td width="15%" style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('工单状态') ?></td>
				<td width="35%">
					<input type="text" class="form-control" value="<?=$status_cn ?>" readonly style="width:100%;">
				</td>
				<td width="15%" style="font:bold 12px SimSun,Arial;padding-left:5px;"><?=TranslateHelper::t('所属模块') ?></td>
				
				<td width="35%">
					<input type="text" class="form-control" name="topicId" value="<?=(isset($topic[$topicId]['topic'])?$topic[$topicId]['topic']:'') ?>" readonly style="width:100%;">
				</td>
			</tr>
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('联系人') ?></td>
				<td>
					<input type="text" class="form-control" value="<?=empty($threads[0]['poster'])?'':$threads[0]['poster'] ?>" readonly style="width:100%;">
				</td>
				<td style="font:bold 12px SimSun,Arial;padding-left:5px;"><?=TranslateHelper::t('处理人') ?></td>
				<?php 
				$staff_name = '';
				if(!empty($ticket['staff_id'])){
					$staff_info = TicketHelper::get_OST_Staff_Info_By_Id($ticket['staff_id']);
					$staff_name .= empty($staff_info['firstname'])?'':$staff_info['firstname'];
					$staff_name .= empty($staff_name)?'':' '. (empty($staff_info['lastname'])?'':$staff_info['lastname']);
				}
				?>
				<td>
					<input type="text" class="form-control" value="<?=empty($staff_name)?'--':$staff_name ?>" readonly style="width:100%;">
				</td>
			</tr>
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('QQ号码') ?></td>
				<td>
					<input type="text" class="form-control" value="<?=empty($contact_info['qq'])?'':$contact_info['qq'] ?>" readonly style="width:100%;">
				</td>
				<td style="font:bold 12px SimSun,Arial;padding-left:5px;"><?=TranslateHelper::t('联系电话') ?></td>
				<?php 
				$contact_phone = '';
				if(!empty($contact_info['mobile']))
					$contact_phone=$contact_info['mobile'];
				if(empty($contact_phone) && !empty($contact_info['phone']))
					$contact_phone = $contact_info['phone'];
				?>
				<td>
					<input type="text" class="form-control" value="<?=$contact_phone ?>" readonly style="width:100%;">
				</td>
			
			</tr>	
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('创建时间') ?></td>
				<td>
					<input type="text" class="form-control" value="<?=empty($ticket['created'])?'':$ticket['created'] ?>" readonly style="width:100%;">
				</td>
			</tr>
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('开始处理时间') ?></td>
				<?php 
				$start_duedate='';
				if(!empty($threads)){
					foreach ($threads as $t){
						if(!empty($t['thread_type']) && $t['thread_type']=='R'){
							$start_duedate = $t['created'];
							break;
						}
					}
				}
				?>
				<td>
					<input type="text" class="form-control" value="<?=$start_duedate ?>" readonly style="width:100%;">
				</td>
				<td style="font:bold 12px SimSun,Arial;padding-left:5px;"><?=TranslateHelper::t('处理/关闭 时间') ?></td>
				
				<td>
					<input type="text" class="form-control" value="<?=empty($ticket['closed'])?'':$ticket['closed'] ?>" readonly style="width:100%;">
				</td>
			</tr>
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('详细描述') ?></td>
				<td colspan=3>
					<textarea name="message" class="form-control" style="width:100%;max-height:150px;min-height:50px;" readonly><?=empty($ticket['body'])?'':$ticket['body'] ?></textarea>
				</td>
			</tr>
			<!-- 
			<tr>
				<td style="font:bold 12px SimSun,Arial;"><?=TranslateHelper::t('附件详情') ?></td>
				<td>
					
				</td>
			</tr>
			 -->
		</table>
	</div>
<?php 
	if(!empty($threads))
		unset($threads[0]);
	if(!empty($threads)){
		krsort($threads);//最近回复排前面
?>
	<div class="reply-list" style="width:100%;margin-top:20px;background-color:rgb(239,239,239);display:inline-block;">
	<p style="font:bold 12px SimSun,Arial;">回复：（最近回复在顶部）</p>
<?php
		foreach ($threads as $index=>$thread){
			$position = 'right';
			$backgroundColor='rgb(217,217,217)';
			if(isset($ost_uid) && !empty($thread['user_id']) && $ost_uid==$thread['user_id']){
				$position = 'left';
				$backgroundColor='rgb(249,188,18)';
			}
?>
		<div class="tick-reply-info" style="width:80%;float:<?=$position ?>;margin-left: 10px;margin-right: 10px;">
	   	 <table class="" style="width:100%;max-width:100%;border-radius:4px;background-color:<?=$backgroundColor ?>;margin-bottom:10px;">
	   			<tr style="display:none;">
	    			<th>Thread ID</th><td><input type="hidden" class="form-control" value="<?=empty($thread['id'])?'':$thread['id'] ?>" readonly></td>
	    		</tr>
				<tr>
					<td colspan=2 style="padding: 5px;">
						<div class="message" style="width:100%;"><?=empty($thread['body'])?'':$thread['body'] ?></div>
					</td>
				</tr>
				<tr>
					<td <?=($position=='left')?'width="100px"':'' ?> style="padding: 5px;"><span style="float:<?=$position ?>;"><?=empty($thread['poster'])?'':$thread['poster'] ?></span></td>
					<td <?=($position=='right')?'width="180px"':'' ?> style="padding: 5px;"><span style="float:<?=$position ?>;"><?=empty($thread['created'])?'':$thread['created'] ?></span></td>
				</tr>
			</table>
		</div>
<?php 
		}
	}
?>
	</div>
</div>