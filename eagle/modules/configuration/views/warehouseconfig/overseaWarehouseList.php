<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\MenuHelper;
// use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/warehouseconfig/overseawarehouselist.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("overseaWarehouseList.init();" , \yii\web\View::POS_READY);

$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/carrierconfig/custom/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/carrierconfig/custom/excelFormat.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

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

<!-- <div class="alert alert-warning" role="alert"> 
	<span style='color: red;font-weight: bold;'>提示：如果您使用的仓库是第三方托管，仓库的工作人员不是是直接登录小老板系统进行拣货、分拣、打印、发货等，我们称此类仓库为“第三方仓库”！</span>
</div> -->
<input type="hidden" name="tab_active" id="search_tab_active" value='<?=$tab_active ?>'>
<input type="hidden" id="search_warehouse_id" value='<?=@$_REQUEST['twarehouseid'] ?>'>
<div>
	<!-- tab panes start -->
	<div>
		<!-- 
		<ul class="nav nav-tabs" role="tablist" style="height:42px;">
			<li role="presentation" class="<?php echo $tab_active==='self' ? '' : 'active';?> sys">
				<a class='tablist_class' value='' href="#syscarrier" aria-controls="syscarrier" role="tab" data-toggle="tab">海外仓</a>
			</li>
			<li role="presentation" class="<?php echo $tab_active==='self' ? 'active' : '';?> self">
				<a class='tablist_class' value='self' href="#selfcarrier" aria-controls="selfcarrier" role="tab" data-toggle="tab">自定义第三方仓库</a>
			</li>
		</ul>
		 -->
		<!-- Tab panes -->
		<div class="tab-content">
			<!-- 接口对接第三方仓库 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='self' ? '' : 'active';?>" id="syscarrier">
				<div class="pull-left" style="margin-right: 3px;min-width: 154px;" >
					<ul class="nav nav-pills nav-stacked">
					<li role="presentation" ><a class ="iv-btn change_warehouse" value=-1 data='0' >添加海外仓</a></li>
					<?php
						if (!empty($warehouseIdNameMap)){
							foreach ($warehouseIdNameMap as $warehouse_code => $warehouse_name){
								if($warehouse_name['oversea_type'] == 0){
					?>
			  				<li role="presentation" <?=@$_REQUEST['twarehouseid'] == $warehouse_code ? "class='active'" : '' ?> <?= $warehouse_name['is_active'] == 'N' ? "style='background-color: #ADADAD;'" : '' ?> >
			  					<a class="iv-btn change_warehouse" value=<?=$warehouse_code ?> data='0' ><?=$warehouse_name['name']?></a>
			  				</li>
					<?php }}}?>
					</ul>
				</div>
				
				<div class="right_content_oversea" id='syscarrier_show_div'>
				</div>
			</div>
			<!-- 自定义第三方仓库 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='self' ? 'active' : '';?>" id="selfcarrier">
				<div class="pull-left" style="margin-right: 3px;min-width: 154px;" >
					<ul class="nav nav-pills nav-stacked">
					<li role="presentation" ><a class ="iv-btn change_warehouse" value=-1 data='1' >添加第三方仓库</a></li>
					<?php
						if (!empty($warehouseIdNameMap)){
							foreach ($warehouseIdNameMap as $warehouse_code => $warehouse_name){
								if($warehouse_name['oversea_type'] == 1){
					?>
			  				<li role="presentation" <?=@$_REQUEST['twarehouseid'] == $warehouse_code ? "class='active'" : '' ?> <?= $warehouse_name['is_active'] == 'N' ? "style='background-color: #ADADAD;'" : '' ?> >
			  					<a class ="iv-btn change_warehouse" value=<?=$warehouse_code ?> data='1' ><?=$warehouse_name['name']?></a>
			  				</li>
					<?php }}}?>
					</ul>
				</div>
			
				<div class="right_content_oversea" id='selfcarrier_show_div'>
				</div>
	  		</div>
		</div>
	</div>
	<!-- table panes end -->
</div>

