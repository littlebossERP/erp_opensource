<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/aliexpressAccountList.js", ['depends' => ['yii\web\JqueryAsset']]);

?>

<div style="padding:10px 15px 0px 15px">
	<form id="platform-AliexpressGetAuthInfo-form" >
    	<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
    		<table>  
    		
    				<tr>
    					<td><?= TranslateHelper::t('输入需要获取授权信息的账号:') ?></td>
    					<td>
    						<input class="form-control" type="text" name="account" style="width:180px;display:inline;" />
    						<span style="color:red;margin-left:3px">*</span>
    					</td>
    				</tr>
    				
    		</table>
        </div>
	</form>
	
	<div class="text-center">
    	<button type="button" class="iv-btn btn-success" id="btn_ok"><?=TranslateHelper::t('保存')?></button>
    	<button type="button" class="iv-btn btn-default" id="btn_cancel"><?=TranslateHelper::t('取消')?></button>
	</div>
</div>
