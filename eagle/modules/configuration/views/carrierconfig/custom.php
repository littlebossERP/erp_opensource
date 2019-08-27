<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl.'/';

$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/trackwarehouse.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/insertTrack.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/shipping.js");
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/custom.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/excelFormat.js");
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/address.js");
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/shippingRules.js");

$this->registerCssFile($baseUrl."css/configuration/carrierconfig/switch.css");

if(!empty($open_carriers)){
	$codes_key = isset($_GET['codes_key'])?$_GET['codes_key']:$codes_key;
	$open = @$customCarrier['is_used'];
	$carrier_type = $customCarrier['carrier_type'];
}
?>

<style>

.right_content_oversea {
  padding: 10px 30px 30px 220px;
  -webkit-flex: 1;
  flex: 1;
}
.right_content_oversea .block {
  margin-top: 20px;
  margin-bottom: 20px;
}
.right_content_oversea .block.middle {
  text-align: center;
}
.right_content_oversea h3 {
  margin: 20px 0;
  font-weight: bold;
  padding-left: 6px;
  border-left: 3px solid #01bdf0;
}

.nav-pills > li.active > a, .nav-pills > li.active > a:hover, .nav-pills > li.active > a:focus {
    color: #fff;
    background-color: #01bdf0;
}
</style>

<?php echo $this->render('../leftmenu/_leftmenu');?>
<?= Html::hiddenInput('ThisType','custom')?>
<?= Html::hiddenInput('carrier_code',@$codes_key)?>

<div class="alert alert-warning" role="alert">
	<span style='color: red;font-weight: bold;'>提示：小老板ERP还没有对接的货代物流系统，用户可自定义！</span>
</div>
<input type="hidden" name="tab_active" id="search_tab_active" value='<?=$tab_active ?>'>
<input type="hidden" id="search_carrier_code" value='<?=@$_REQUEST['tcarrier_code'] ?>'>
<div>
	<!-- tab panes start -->
	<div>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist" style="height:42px;">
		
			<li role="presentation" class="<?php echo $tab_active==='customtracking' ? '' : 'active';?> sys">
				<a class='tablist_class' value='' href="#excelcarrier" aria-controls="excelcarrier" role="tab" data-toggle="tab">Excel对接</a>
			</li>
	
			<li role="presentation" class="<?php echo $tab_active==='customtracking' ? 'active' : '';?> self">
				<a class='tablist_class' value='self' href="#trackingcarrier" aria-controls="trackingcarrier" role="tab" data-toggle="tab">无数据对接</a>
			</li>
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<!-- 接口对接第三方仓库 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='customtracking' ? '' : 'active';?>" id="excelcarrier">
				<div class="pull-left" style="margin-right: 3px;min-width: 154px;" >
					<ul class="nav nav-pills nav-stacked">
					<li role="presentation" ><a class ="iv-btn change_self_carrier" value=-1 data='1' >添加物流商</a></li>
					<?php
						if (!empty($custom_carriers)){
							foreach ($custom_carriers as $custom_carrier_code => $custom_carrier_name){
								if($custom_carrier_name['carrier_type'] == 1){
					?>
			  				<li role="presentation" <?=@$_REQUEST['tcarrier_code'] == $custom_carrier_code ? "class='active'" : '' ?> <?= $custom_carrier_name['is_used'] == '0' ? "style='background-color: #ADADAD;'" : '' ?> >
			  					<a class="iv-btn change_self_carrier" value=<?=$custom_carrier_code ?> data='1' ><?=$custom_carrier_name['carrier_name']?></a>
			  				</li>
					<?php }}}?>
					</ul>
				</div>
				
				<div class="right_content_oversea" id='excelcarrier_show_div'>
				</div>
			</div>
			<!-- 自定义第三方仓库 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='customtracking' ? 'active' : '';?>" id="trackingcarrier">
				<div class="pull-left" style="margin-right: 3px;min-width: 154px;" >
					<ul class="nav nav-pills nav-stacked">
					<li role="presentation" ><a class ="iv-btn change_self_carrier" value=-1 data='0' >添加物流商</a></li>
					<?php
						if (!empty($custom_carriers)){
							foreach ($custom_carriers as $custom_carrier_code => $custom_carrier_name){
								if($custom_carrier_name['carrier_type'] == 0){
					?>
			  				<li role="presentation" <?=@$_REQUEST['tcarrier_code'] == $custom_carrier_code ? "class='active'" : '' ?> <?= $custom_carrier_name['is_used'] == '0' ? "style='background-color: #ADADAD;'" : '' ?> >
			  					<a class ="iv-btn change_self_carrier" value=<?=$custom_carrier_code ?> data='0' ><?=$custom_carrier_name['carrier_name']?></a>
			  				</li>
					<?php }}}?>
					</ul>
				</div>
			
				<div class="right_content_oversea" id='trackingcarrier_show_div'>
				</div>
	  		</div>
		</div>
	</div>
	<!-- table panes end -->
</div>