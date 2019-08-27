<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\amazoncs\models\CsQuestTemplate;

$uid = \Yii::$app->user->id;

$this->registerJs("$('.SysTemplateScreenshot').popover();" , \yii\web\View::POS_READY);


?>
<style>
.sys-templates-win .modal-dialog{
	width: inherit;
}
.sys-templates-win .modal-body{
	display: inline-block;
}
.popover{
	max-width: inherit;
    max-height: inherit;
}
</style>

<div class="sys_template_list">
	<?php 
		$hiddenHtml = '';
	?>
	<?php $temp_index = 0;
		foreach ($sysTemplates as $sys_temp):?>
		<div style="margin:20px 10px;width:400px;height:360px;float:left;text-align:center;">
			<span style="font-weight:bolder;"><?=$sys_temp['name'];?></span>
			<div style="width:400px;height:320px;overflow:hidden;margin: 5px 0px;border:1px solid;border-radius:5px;" 
				class="SysTemplateScreenshot" 
				data-toggle="popover" data-content="<img src='<?=$sys_temp['thumbnail']?>'>" data-html="true" data-trigger="click" 
				data-placement="<?php
					if( intval($temp_index/2)%2!==1 )
						echo "right";
					else 
						echo "left";
				?>"
			>
				<img src="<?=$sys_temp['thumbnail'];?>" style="cursor:pointer;">
			</div>
			<buttom class="btn btn-success" onclick="amazoncs.Template.replaceTmeplateContentBySysTemplateContent(<?=$sys_temp['id']?>)">选择</buttom>
		</div>
		<?php 
			$hiddenHtml.='<div id="sys_template_'.$sys_temp['id'].'_subject" style="display:none">';
			$hiddenHtml.=htmlentities($sys_temp['subject']);
			$hiddenHtml.='</div>';
			$hiddenHtml.='<div id="sys_template_'.$sys_temp['id'].'_contents" style="display:none">';
			$hiddenHtml.=htmlentities($sys_temp['contents']);
			$hiddenHtml.='</div>';
		?>
	<?php $temp_index++; ?>
	<?php endforeach;?>
	
	<?=$hiddenHtml;?>
</div>