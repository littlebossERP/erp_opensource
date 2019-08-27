<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\amazoncs\models\CsQuestTemplate;

$uid = \Yii::$app->user->id;

$subject = empty($quest->subject)?'':$quest->subject;
$content = empty($quest->body)?'':$quest->body;

//$content = str_replace(chr(10), '' ,$content);
//$content = str_replace(chr(13), '<br>' ,$content);


?>
<style>

</style>

<div id="quest-mail-data">
	<input type="hidden" value="<?=$quest->id?>" name="quest_id">
	<div style="width:100%;border-bottom:1px solid;margin-ottom:10px;">
		<span style="font: bold 14px/40px SimSun,Arial;color: #374655;">邮件标题:</span>
		<div style="font: bold 14px/40px SimSun,Arial;color: #374655;width:100%;">
			<input type="text" class="form-control editable" name="subject" readonly value="<?=$subject?>" aastyle="width:100%;">
		</div>
	</div>
	<div style="width:100%;padding:10px;font:14px SimSun,Arial;verflow-y:auto;max-height:600px;">
		<!-- <textarea class="form-control editable" name="body" readonly style="width:100%;height:500px;overflow-y:auto;"><?=$content?></textarea> -->
		<div class="form-control editable" name="body" readonly style="width:100%;height:500px;overflow-y:auto;">
			<?=str_replace(chr(10), '<br>', $content);?>
		</div>
	</div>
	
	<div class="button-group" style="text-align:center">
	<!-- 
	<?php if($quest->status=='P'){ ?>
		<button type="button" class="btn btn-info" id="edit-btn" onclick="amazoncs.QuestList.EditQuestMailContent()" style="display:initial;">修改内容</button>
		<button type="button" class="btn btn-success" id="save-edit-btn" onclick="amazoncs.QuestList.SaveQuestMailContentEditting()" style="display:none;">保存修改</button>
	<?php } ?>
	-->
	</div>
</div>