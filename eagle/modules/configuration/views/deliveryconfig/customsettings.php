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
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<div class="content-wrapper" >
<form class="config-form" method="post" action="<?php echo Url::to('configuration/default/setconfig')?>">
	<table  style="width:100%">
		<tr>
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('支持负库存');?>：</td>
			<td style="width:80%"><?= Html::radioList('config[support_zero_inventory_shipments]',ConfigHelper::getConfig('support_zero_inventory_shipments')==null?'Y':ConfigHelper::getConfig('support_zero_inventory_shipments'),['N'=>'不支持','Y'=>'支持'])?></td>
			<td>
			<input type="hidden" name='path' value="support_zero_inventory_shipments">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);"></td>
		</tr>
	</table>
</form>
<hr>
<form class="config-form" method="post" <?php echo Url::to('configuration/default/setconfig')?>>
	<table  style="width:100%">
		<tr>
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('拣货单显示内容');?>：</td>
			<td style="width:80%"><?php echo Html::checkbox('config[no_show_product_image]',ConfigHelper::getConfig('no_show_product_image')=='N'?true:false,['value'=>'N','label'=>'不显示图片'])?></td>
			<td>
			<input type="hidden" name='path' value="no_show_product_image">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);">
			</td>
		</tr>
	</table>
</form>
<hr>
<form class="config-form" method="post" <?php echo Url::to('configuration/default/setconfig')?>>
	<table  style="width:100%">
		<tr>
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('不自动通知平台发货');?>：</td>
			<?php 
			//已经启用的平台
			$platformBindingSituation = PlatformAccountApi::getAllPlatformBindingSituation();
			$platForm = array();
			foreach (OdOrder::$orderSource as $k=>$v){
				if ($platformBindingSituation[$k]){
					$platForm[$k]=$v;
				}
			}
			$no_auto_mark_shipment = json_decode(ConfigHelper::getConfig('no_auto_mark_shipment'));
			$no_auto_mark_shipment = empty($no_auto_mark_shipment)?OdOrder::$no_autoShippingPlatform:$no_auto_mark_shipment;
			?>
			<td style="width:80%"><?php echo Html::checkboxList('config[no_auto_mark_shipment]',$no_auto_mark_shipment,$platForm)?></td>
			<td>
			<input type="hidden" name='path' value="no_auto_mark_shipment">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);">
			</td>
		</tr>
	</table>
</form>
<hr>
<?php
if($platformBindingSituation['amazon']){
?>
<form class="config-form" method="post" <?php echo Url::to('configuration/default/setconfig')?>>
	<table  style="width:100%">
		<tr>
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('不自动生成虚拟跟踪号');?>：</td>
			<?php 
			$platForm = array();
			$platForm['amazon']=OdOrder::$orderSource['amazon'];
			
			$no_auto_mark_tracking_num = json_decode(ConfigHelper::getConfig('no_auto_mark_tracking_num'));
			$no_auto_mark_tracking_num = empty($no_auto_mark_tracking_num) ? array() : $no_auto_mark_tracking_num;
// 			print_r($no_auto_mark_tracking_num);
			?>
			<td style="width:80%"><?php echo Html::checkboxList('config[no_auto_mark_tracking_num]',$no_auto_mark_tracking_num, $platForm)?></td>
			<td>
			<input type="hidden" name='path' value="no_auto_mark_tracking_num">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);">
			</td>
		</tr>
	</table>
</form>
<hr>
<?php } ?>
<form class="config-form" method="post" <?php echo Url::to('configuration/default/setconfig')?>>
	<table  style="width:100%">
		<tr>
<!-- 		暂时只对一体化面单生效  -->
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('拣货单配货名字');?>：</td>
			<?php 
			$d_listpicking_name = ConfigHelper::getConfig('d_listpicking_name');
			$d_listpicking_name = empty($d_listpicking_name) ? 0 : $d_listpicking_name;
			?>
			<td style="width:80%"><?= Html::radioList('config[d_listpicking_name]',$d_listpicking_name,['0'=>'中文报关名称','1'=>'中文配货名称'])?></td>
			<td>
			<input type="hidden" name='path' value="d_listpicking_name">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);"></td>
		</tr>
	</table>
</form>
<hr>
<form class="config-form" method="post" <?php echo Url::to('configuration/default/setconfig')?>>
	<table  style="width:100%">
		<tr>
<!-- 		暂时只有台湾高青有需求  -->
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('是否使用扫描绑定跟踪号');?>：</td>
			<?php 
			$use_scan_binding_tracking = ConfigHelper::getConfig('use_scan_binding_tracking');
			$use_scan_binding_tracking = empty($use_scan_binding_tracking) ? 0 : $use_scan_binding_tracking;
			?>
			<td style="width:80%"><?= Html::radioList('config[use_scan_binding_tracking]',$use_scan_binding_tracking,['0'=>'否','1'=>'是'])?></td>
			<td>
			<input type="hidden" name='path' value="use_scan_binding_tracking">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);"></td>
		</tr>
	</table>
</form>
<hr>
<?php
 $cTemplateList = CarrierOpenHelper::getCrTemplateListV2();
 if(count($cTemplateList) > 0){
?>
<form class="config-form" method="post" <?php echo Url::to('configuration/default/setconfig')?>>
	<table  style="width:100%">
		<tr>
			<td style="text-align: right;width:10%"><?php echo TranslateHelper::t('拣货单指定格式');?>：</td>
			<?php 
			$use_scan_picking_format = ConfigHelper::getConfig('use_scan_picking_format');
			$use_scan_picking_format = empty($use_scan_picking_format) ? '' : $use_scan_picking_format;
			?>
			<td style="width:80%"><?=Html::dropDownList('config[use_scan_picking_format]', $use_scan_picking_format, $cTemplateList,['class'=>'iv-input','style'=>'margin:0px','prompt'=>'默认为系统模板'])?></td>
			<td>
			<input type="hidden" name='path' value="use_scan_picking_format">
			<input type="button" class="iv-btn btn-primary mtBtn" value="保存" onclick="setconfig(this);"></td>
		</tr>
	</table>
</form>
<hr>
<?php } ?>

</div>
