<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
//$this->registerJsFile($baseUrl."js/project/ticket/ticket.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerCssFile($baseUrl."css/catalog/catalog.css");

?>

<style>

</style>


<div class="ticket-view">
	<form id="ticket_info_form">
	    <table class="table table-striped table-bordered detail-view">
	    	<?php if(isset($tt) && $tt=='view'){ ?>
	   		<tr style="display:none;">
	    		<th>Ticket ID</th><td><input type="hidden" class="form-control" name="ticket_id" value="<?=empty($ticket['ticket_id'])?'':$ticket['ticket_id'] ?>" readonly></td>
	    	</tr>
	    	<?php } ?>
			<tr>
				<th><?=TranslateHelper::t('工单标题') ?></th><td><input type="text" class="form-control" name="subject" value="<?=empty($ticket['subject'])?'':$ticket['subject'] ?>" ></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('所属模块') ?></th>
				<td><select class="form-control" name="topicId" style="width:200px;">
						<option value="0"></option>
						<?php
						if(!empty($topic)){
							foreach ($topic as $id=>$t){
								$select = '';
								if(isset($topicId) && $topicId==$id)
									$select = 'selected="selected"';
						?>
						<option value="<?=$id ?>" <?=$select?> ><?=empty($t['topic'])?'':$t['topic'] ?></option>
						<?php 
							}
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('详细描述') ?></th>
				<td>
					<textarea name="message" class="form-control" style="width:100% !important;height:150px !important;"><?=empty($ticket['body'])?'':$ticket['body'] ?></textarea>
				</td>
			</tr>
			<!-- 
			<tr>
				<th><?=TranslateHelper::t('插入图片') ?></th>
				<td>
					
				</td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('图片预览') ?></th>
				<td>
					
				</td>
			</tr>
			 -->
			<!--
			<tr>
				<th><?=TranslateHelper::t('QQ号码') ?></th>
				<td>
					<input type="text" class="form-control" name="contact_info[qq]" value="" style="width:200px;">
				</td>
			</tr>
			-->
		</table>
	</form>
</div>