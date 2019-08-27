<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/LazadaAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.LazadaAccountsNewOrEdit.initWidget();" , \yii\web\View::POS_READY);

?>

<div style="padding:10px 15px 0px 15px">
	<form id="platform-LazadaGetAuthInfo-form" >
    	<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
    		<table>  
    		
    				<tr>
    					<td><?= TranslateHelper::t('API账号邮箱:') ?></td>
    					<td>
    						<input class="form-control" type="text" name="account" style="width:180px;display:inline;" />
    						<span style="color:red;margin-left:3px">*</span>
    					</td>
    				</tr>
    				<tr>
    					<td><?= TranslateHelper::t('站点:') ?></td>
    					<td><?=Html::dropDownList('country',"",$lazadaSite,['class'=>'form-control input-sm','prompt'=>'-请选择-'])?></td>
    				</tr>
    				
    		</table>
        </div>
	</form>
</div>
