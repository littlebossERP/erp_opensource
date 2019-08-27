<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use Qiniu\json_decode;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\carrier\helpers\CarrierOpenHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/configuration/config.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
	.leftDIV{
		float:left;
		width:150px;
		text-align:right;
		line-height:26px;
	}
	.rightDIV{
		margin-left:160px;
	}
	.lShow{
		float:left;
		width:70px;
		text-align:right;
		line-height:26px;
		margin-bottom:5px;
	}
	.rShow{
		margin-left:80px;
		margin-bottom:5px;
	}
	.sizetext{
		width:35px;
	}
	.mtBtn{
		padding-left:15px;
		padding-right:15px;
	}
	.iv-input[disabled]{
		cursor: default;
		background:#F0F0F0;
	}
</style>
<!------------------------------ oms 2.1 左侧菜单  start  ----------------------------------------->
<?php echo $this->render('../leftmenu/_leftmenu');?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('warehouse_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<div class="content-wrapper" >
<form class="config-form" method="post" action="<?php echo Url::to('configuration/default/setconfig')?>">
	<table  style="width:100%">
		<tr>
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('采购支持多收');?>：</td>
			<td style="width:80%"><?= Html::radioList('config[support_purchase_out]',ConfigHelper::getConfig('support_purchase_out')==null?'Y':ConfigHelper::getConfig('support_purchase_out'),['N'=>'不支持','Y'=>'支持'])?></td>
			<td>
			<input type="hidden" name='path' value="support_purchase_out">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);"></td>
		</tr>
	</table>
</form>
<hr>

</div>
