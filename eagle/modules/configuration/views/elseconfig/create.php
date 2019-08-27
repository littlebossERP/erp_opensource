<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>
<form id="FeedbackTemplateFORM">

<div class="form-group text-center" style="margin-top:20px;">
<?=Html::hiddenInput('templateid',$template->id) ?>
<?=Html::dropDownList('feedbacktype',$template->template_type,['1'=>'好评','2'=>'中评','3'=>'差评'],['class'=>'iv-input'])?>
<?=Html::textInput('feedbackval',$template->template,['class'=>'iv-input','style'=>'margin-left:8px;'])?>
</div>
</form>
