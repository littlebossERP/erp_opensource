<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\util\models\TranslateCache;
use eagle\helpers\UserHelper;

$tmp_js_version = '1.05';
//$orderList = new \oms\modules\order\models\OrdOrder();
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/product_list.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/selectProduct.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/downloadexcel.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/bath_edit.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/purchase/purchase_link_list.js?v=1.0", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile($baseUrl."js/project/catalog/config_bundle.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerJsFile($baseUrl."js/project/tracking/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//图片 js and css
$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
/*
$supplierNames=[];
foreach ($supplierData as $supplier){
	$supplierNames[]=$supplier['name'];
}
$this->registerJs("productList.list.supplierNames=".json_encode($supplierNames),  \yii\web\View::POS_READY);

$brandNames=[];
foreach ($brandData as $brandId=>$brand){
	$brandNames[]=$brand['name'];
}
$this->registerJs("productList.list.brandNames=".json_encode($brandNames),  \yii\web\View::POS_READY);
*/
$fieldNames=[];
$fieldValues=[];
foreach ($prodFieldata as $name=>$valueArr){
	$fieldNames[]=$name;
	$fieldValues[$name]=$valueArr;
}
$this->registerJs("productList.list.fieldNames=".json_encode($fieldNames).";",  \yii\web\View::POS_READY);
$this->registerJs("productList.list.fieldValues=".json_encode($fieldValues).";",  \yii\web\View::POS_READY);

$tagNames=[];
foreach($tagData as $anTag){
	$tagNames[]=$anTag['tag_name'];
}
$this->registerJs("productList.list.tagNames=".json_encode($tagNames),  \yii\web\View::POS_READY);

$this->registerJs("productList.list.initShowAsskuTip();" , \yii\web\View::POS_READY);
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$puid=\Yii::$app->user->identity->getParentUid();
$uid=\Yii::$app->user->id;
if(empty($puid)) $puid=$uid;
$Users = UserHelper::getUsersNameByPuid($puid);
?>
<style>
.td_space_toggle {
	padding: 0 !important;
	height: auto;
}

.div_space_toggle {
	display: none;
}

.td_space_toggle {
	padding: 0 !important;
	height: auto;
}

.product_btn_menu {
	width: 90px;
}

.product_info .modal-body {
	max-height: 500px;
	overflow-y: auto;
}

.product_info .modal-dialog {
	width: 900px;
}

.bg_loading {
	background-image: url(/../images/loading.gif);
	background-repeat: no-repeat;
	background-position: center;
}

div[role=image-uploader-container] {
	margin: 0 !important;
	display: inline-block;
}

.select_photo {
	border-color: red !important;
}

.thumbnail{
	background-color: transparent !important;
}

.image-item {
padding-left: 10px !important;
padding-right: 10px !important;
}

.table > tbody > tr > th{
	height: 20px;
  	padding: 0px 0px 0px 8px;
  	vertical-align: middle;
}
.table > tbody > tr > td{
	word-break:break-word;
}

.cursor_pointer{
	cursor: pointer;
}
ul li a{
	cursor:pointer;
}
.ui-autocomplete {
z-index: 2000;
}
.popover-content{
	/*background-color: rgb(255, 168, 168);*/
}
/*
.ui-combobox-input{
	background-color: #fff;
	background-image: none;
	border: 1px solid #b9d6e8;
	box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	color: #637c99;
	font-size: 12px;
	height: 28px;
	line-height: 1.42857;
	padding: 5px;
	transition: border-color 0.15s ease-in-out 0s, box-shadow 0.15s ease-in-out 0s;
	margin: 5px 0 10px 0;
}
*/
.ui-combobox-input{
	border-radius:0px;
}
.ui-combobox-toggle{
	border-radius:0px;
}
.font-color-2 {
	color: #637c99;
}
.product_pending_list{
	background-color:#fff;
	border: 1px solid;
	position:fixed;
	display: none;
	top: 45px;
	right: 50px;
	z-index: 999;
	border-radius:5px;
	max-width:500px;
}
</style>

<form  class="form-horizontal"
	action="<?= Url::to(['/'.yii::$app->controller->module->id.'/'.yii::$app->controller->id.'/'.yii::$app->controller->action->id])?>"
	method="get" style="float:left;width:100%;">
	
	<div style="float:left;width:100%;">
		<div style="width:200px;float:left;">
			<div class="div-input-group" style="width:100%">
				<div style="float:left;">
					<select name='txt_tag' class="eagle-form-control"  style="float:left;">
						<option value="all"><?= TranslateHelper::t("商品标签")?></option>
						<?php foreach($tagData as $anTag):
							if (!empty($_GET['txt_tag'])) $isSelect = ($_GET['txt_tag'] == $anTag['tag_id'])?"selected":"";
							else $isSelect = "";?>
						<option value="<?= $anTag['tag_id']?>" <?= $isSelect ?>><?=$anTag['tag_name']?></option>
						<?php endforeach;?>
					</select> 
				</div>
			</div>
		</div>
		<div style="width:200px;float:left;">
			<div class="div-input-group" style="width:100%">
				<div style="float:left;">
					<select name='txt_brand'  class="eagle-form-control">
						<option value="all"><?= TranslateHelper::t("商品品牌")?></option>
						<?php foreach($brandData as $anBrand):
							if (!empty($_GET['txt_brand'])) $isSelect = ($_GET['txt_brand'] == $anBrand['brand_id'])?"selected":"";
							else $isSelect = ""; ?>
						<option value="<?= $anBrand['brand_id']?>" <?= $isSelect ?>><?=$anBrand['name']?></option>
						<?php endforeach;?>
					</select> 
				</div>
			</div>
		</div>
		<div style="width:200px;float:left;">
			<div class="div-input-group" style="width:100%">
				<div style="float:left;">
					<select name='txt_supplier'  class="eagle-form-control">
						<option value="all" <?=(!isset($_GET['txt_supplier']) or !is_numeric($_GET['txt_supplier']) )?" selected ":'' ?>><?= TranslateHelper::t("供应商")?></option>
						<?php foreach($supplierData as $anSupplier):
							if (isset($_GET['txt_supplier'])) $isSelect = ($_GET['txt_supplier'] == $anSupplier['supplier_id'])?"selected":"";
							else $isSelect = ""; ?>
						<option value="<?= $anSupplier['supplier_id']?>" <?= $isSelect ?>><?=$anSupplier['name']?></option>
						<?php endforeach;?>
						
					</select> 
				</div>
			</div>
		</div>
		<!-- 
		<div class="col-sm-3">
			<label class="control-label" style="float:left;width:25%;padding:5px 0px;"><?= TranslateHelper::t("商品状态")?></label>
			<div style="float:left;width:75%;">
				<select name='txt_status'  class="eagle-form-control">
					<option value="all"><?= TranslateHelper::t("商品状态")?></option>
					<?php foreach($statusMapping as $status_code =>$Status_labe):
						if (!empty($_GET['txt_status']))
							$isSelect = ($_GET['txt_status'] == $status_code)?"selected":"";
						else 
							$isSelect = "";
					?>
					<option value="<?=$status_code?>" <?= $isSelect ?>><?=$Status_labe?></option>
					<?php endforeach;?>
				</select> 
			</div>
		</div>
		-->
		
		<div style="width:130px; float:left;">
		    <div class="div-input-group" style="widht:100%">
		        <SELECT name="product_type" value="" class="eagle-form-control" style="width:100px;margin:0px">
		            <OPTION value="" <?=(empty($_GET['product_type']) or $_GET['product_type']=="")?"selected":"" ?>><?= TranslateHelper::t('商品类型') ?></OPTION>
	  				<OPTION value="S" <?=(!empty($_GET['product_type']) && $_GET['product_type']=="S")?"selected":"" ?>><?= TranslateHelper::t('普通商品') ?></OPTION>
	  				<OPTION value="C" <?=(!empty($_GET['product_type']) && $_GET['product_type']=="C")?"selected":"" ?>><?= TranslateHelper::t('变参商品') ?></OPTION>
  					<OPTION value="B" <?=(!empty($_GET['product_type']) && $_GET['product_type']=="B")?"selected":"" ?>><?= TranslateHelper::t('捆绑商品') ?></OPTION>
	  			</SELECT>
		    </div>
		</div>
		
		<div style="float:left;">
	        <SELECT name="search_type" value="" class="eagle-form-control" style="width:100px; margin:1px 0px 0px 0px; ">
	            <OPTION value="" <?=(empty($_GET['search_type']) or $_GET['search_type']=="")?"selected":"" ?>><?= TranslateHelper::t('模糊搜索') ?></OPTION>
  				<OPTION value="sku" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="sku")?"selected":"" ?>><?= TranslateHelper::t('SKU') ?></OPTION>
  				<OPTION value="name" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="name")?"selected":"" ?>><?= TranslateHelper::t('商品名称') ?></OPTION>
  				<OPTION value="declaration_ch" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="declaration_ch")?"selected":"" ?>><?= TranslateHelper::t('中文报关名') ?></OPTION>
  				<OPTION value="declaration_en" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="declaration_en")?"selected":"" ?>><?= TranslateHelper::t('英文报关名') ?></OPTION>
  				<OPTION value="prod_name_ch" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="prod_name_ch")?"selected":"" ?>><?= TranslateHelper::t('中文配货名') ?></OPTION>
  				<OPTION value="prod_name_en" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="prod_name_en")?"selected":"" ?>><?= TranslateHelper::t('英文配货名') ?></OPTION>
  			</SELECT>
		</div>
		
		<div style="width:300px;float:left;">
			<div class="div-input-group" style="width:100%">
				<div style="" class="input-group" style="float:left;">
					<input name='txt_search' type="text" class="form-control" style="height:28px;float:left;width:100%;" 
						placeholder="<?= TranslateHelper::t('输入产品sku或名称或描述字段')?>"
						value="<?= (empty($_GET['txt_search'])?'':$_GET['txt_search'])?>"/>
					<span class="input-group-btn" style="">
						<button type="submit" class="btn btn-default" style="">
							<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
					    </button>
				    </span>
				</div>
			</div>
		</div>
		
		<input name="class_id" value="<?= isset($_GET['class_id']) ? $_GET['class_id'] : ''; ?>" style="display: none; " />
	</div>
	<div style="float:left;width:100%;">
		<div style="float:left;margin:5px 0px;">
			<div class="btn-group">
				<button type="button" class="btn-xs btn-transparent font-color-1" id="btn_create_product" style="background-color: transparent;padding-right:0px;
					<?= $is_catalog_edit ? '' : 'color: lightgray; '?>" <?= $is_catalog_edit ? '' : 'disabled="disabled"'?>
				>
					<span class="glyphicon glyphicon-plus" style="vertical-align:middle;height: 16px;"></span> 
					<?=TranslateHelper::t('添加普通商品')?>
				</button>
				<a class="dropdown-toggle btn-xs btn-transparent font-color-1" style="padding-left:0px;"
					data-toggle="dropdown" aria-expanded="false">
					<span class="glyphicon glyphicon-menu-down"></span> 
				</a>
				<ul class="dropdown-menu" role="menu">
					<?php if($is_catalog_edit){?>
					<li style="font-size: 12px;"><a onclick="productList.ConfigAndBundle.addConfigProd()"><?=TranslateHelper::t('添加变参商品') ?></a></li>
					<li style="font-size: 12px;"><a onclick="productList.ConfigAndBundle.addBundleProd()"><?=TranslateHelper::t('添加捆绑商品') ?></a></li>
					<?php }?>
				</ul>
			</div>
			
			<div class="btn-group">
                <a data-toggle="dropdown" style="color: inherit;">
                    <button class="iv-btn" style="background-color: transparent; padding-top:1px; padding-bottom:1px;
						<?= $is_catalog_edit ? '' : 'color: lightgray; '?>" <?= $is_catalog_edit ? '' : 'disabled="disabled"'?>
					>
                        <span class="egicon-export"></span>
                                                                        导入商品
                        <span class="caret"></span>
                	</button>
            	</a>
            	<ul class="dropdown-menu">
            		<?php if($is_catalog_edit){?>
            		<li style="font-size: 12px;"><a onclick="productList.list.import_product_excel('S')">导入普通商品</a></li>
            		<li style="font-size: 12px;"><a onclick="productList.list.import_product_excel('L')">导入变参商品</a></li>
            		<li style="font-size: 12px;"><a onclick="productList.list.import_product_excel('B')">导入捆绑商品</a></li>
            		<?php }?>
            	</ul>
            </div>
			<!-- 
			<button type="button" class="btn-xs btn-transparent font-color-1" id="btn_import_sellertool_product">
				<span class="egicon-export" aria-hidden="true" style="margin-left: 20px;height:16px;"></span>
			  <?=TranslateHelper::t('导入赛兔子商品')?>
			</button>
			
			<button type="button" class="btn-xs btn-transparent font-color-1" id="btn_import_sellertool_bundle_product">
				<span class="egicon-export" aria-hidden="true" style="margin-left: 20px;height:16px;"></span>
			  <?=TranslateHelper::t('导入赛兔虚拟商品')?>
			</button>
			 -->
			<!-- 暂时隐藏状态属性
			<div class="btn-group">
				<button type="button" class="btn-xs btn-transparent font-color-1" style="margin-left: 20px;background-color: transparent;padding-right:0px;"
					onclick="productList.list.BatchUpdateProductStatus('OS')">
					<span class="glyphicon glyphicon-transfer" style="vertical-align:middle;height: 16px;"></span> 
					<?=TranslateHelper::t('批量修改为在售状态') ?>
				</button>
				<a class="dropdown-toggle btn-xs btn-transparent font-color-1" style="padding-left:0px;"
					data-toggle="dropdown" aria-expanded="false">
					<span class="glyphicon glyphicon-menu-down"></span> 
				</a>
				<ul class="dropdown-menu" role="menu">
			
					<li style="font-size: 12px;"><a
						onclick="productList.list.BatchUpdateProductStatus('RN')"><?=TranslateHelper::t('批量修改为紧缺状态') ?></a></li>
					<li style="font-size: 12px;"><a
						onclick="productList.list.BatchUpdateProductStatus( 'DR')"><?=TranslateHelper::t('批量修改为下架状态') ?></a></li>
					<li style="font-size: 12px;"><a
						onclick="productList.list.BatchUpdateProductStatus('AC')"><?=TranslateHelper::t('批量修改为归档状态') ?></a></li>
					<li style="font-size: 12px;"><a
						onclick="productList.list.BatchUpdateProductStatus('RS')"><?=TranslateHelper::t('批量修改为重新上架状态') ?></a></li>
				</ul>
			</div>
			-->
			<div class="btn-group">
                <a data-toggle="dropdown" style="color: inherit;">
					<button class="iv-btn" style="background-color: transparent; padding-top:1px; padding-bottom:1px;
						<?= $is_catalog_edit ? '' : 'color: lightgray; '?>" <?= $is_catalog_edit ? '' : 'disabled="disabled"'?>
					>
                        <span class="egicon-edit"></span>
                                  批量编辑
                        <span class="caret"></span>
					</button>
            	</a>
            	<ul class="dropdown-menu">
            		<?php if($is_catalog_edit){?>
            		<li style="font-size: 12px;"><a onclick="productList.list.BathEdit('basic')">商品信息</a></li>
            		<li style="font-size: 12px;"><a onclick="productList.list.BathEdit('declaration')">报关信息</a></li>
            		<?php }?>
            	</ul>
            </div>
            
			<button type="button" class="btn-xs btn-transparent font-color-1" id="batch_del" style="margin-left: 20px;
				<?= $is_catalog_delete ? '' : 'color: lightgray; '?>" <?= $is_catalog_delete ? '' : 'disabled="disabled"'?>
			>
				<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
			  <?=TranslateHelper::t('批量删除')?>
			</button>
			
			<button type="button" class="btn-xs btn-transparent font-color-1" id="btn_bcode" style="margin-left: 20px;">
				<span class="glyphicon glyphicon-print" aria-hidden="true" style="height:16px;"></span>
			  <?=TranslateHelper::t('打印SKU条码')?>
			</button>
			
			<div class="btn-group">
                <a data-toggle="dropdown" style="color: inherit;">
                    <button class="iv-btn" style="background-color: transparent; padding-top:1px; padding-bottom:1px;
						<?= $is_catalog_export ? '' : 'color: lightgray; '?>" <?= $is_catalog_export ? '' : 'disabled="disabled"'?>
                    >
                        <span class="glyphicon glyphicon-folder-close"></span>
                                                                        导出
                        <span class="caret"></span>
                        </button>
            	</a>
            	<ul class="dropdown-menu">
            		<?php if($is_catalog_export){?>
            		<li style="font-size: 12px;"><a onclick="productList.list.exportExcelSelect(0)">按勾选导出</a></li>
            		<li style="font-size: 12px;"><a onclick="productList.list.exportExcelSelect(1)">按所有页导出</a></li>
            		<?php }?>
            	</ul>
            </div>
            
            <button type="button" class="btn-xs btn-transparent font-color-1" id="batch_merge"" style="
            	<?= $is_catalog_edit ? '' : 'color: lightgray; '?>" <?= $is_catalog_edit ? '' : 'disabled="disabled"'?>
            >
				<span class="glyphicon glyphicon-log-in" aria-hidden="true" style="height:16px;"></span>
			  <?=TranslateHelper::t('合并商品')?>
			</button>
			
			<div class="btn-group">
                <a data-toggle="dropdown" style="color: inherit;">
                    <button class="iv-btn" style="background-color: transparent; padding-top:1px; padding-bottom:1px;
            			<?= $is_catalog_edit ? '' : 'color: lightgray; '?>" <?= $is_catalog_edit ? '' : 'disabled="disabled"'?>
            		>
                        <span class="glyphicon glyphicon-list-alt"></span>
                                                                        移动到分类
                        <span class="caret"></span>
                	</button>
            	</a>
            	<ul class="dropdown-menu">
            		<?php if($is_catalog_edit){?>
            	    <li style="font-size: 12px;"><a class="changeClass" class_id="0">|- 未分类</a></li>
            		<?php foreach($classData as $class){?>
            		    <li style="font-size: 12px; <?= strlen($class['number']) > 2 ? 'margin-left:'.((int)(strlen($class['number']) / 2 - 1) * 15).'px' : ''; ?>"><a class="changeClass" class_id="<?= $class['ID'] ?>">|- <?= $class['name'] ?></a></li>
            		<?php }}?>
            	</ul>
            </div>
		</div>
	</div>
</form>

<?php 
$sort = new Sort(['attributes' => ['sku','name','type','class_id','status','purchase_price','prod_weight','update_time']]);
?>
<!-- table -->
<div style="width:100%;float:left;">
	<table cellspacing="0" cellpadding="0" style="font-size: 12px;width:100%;float:left;"
		class="table table-hover">
		<thead>
		<tr>
			<th nowrap width="30px">
				<input type="checkbox" name="chk_product_all">
			</th>
			<th nowrap width="50px"><?=TranslateHelper::t('商品图片') ?></th>
			<th nowrap width="150px"><?=$sort->link('sku',['label'=>TranslateHelper::t('SKU')]) ?></th>
			<th nowrap width="250px"><?=$sort->link('name',['label'=>TranslateHelper::t('商品名称')])?></th>
			<th nowrap width="100px"><?=$sort->link('class_id',['label'=>TranslateHelper::t('所属分类')])?></th>
			<th nowrap width="80px"><?=$sort->link('type',['label'=>TranslateHelper::t('商品类型')])?></th>
			<!-- 
			<th nowrap><?=$sort->link('status',['label'=>TranslateHelper::t('状态')])?></th>
			 -->
			<th nowrap width="60px"><?=$sort->link('purchase_price',['label'=>TranslateHelper::t('采购价')])?></th>
			<th nowrap width="150px"><?=TranslateHelper::t('库存') ?></th>
			<th nowrap width="50px"><?=$sort->link('prod_weight',['label'=>TranslateHelper::t('重量')]) ?></th>
			<th nowrap width="100px"><?=$sort->link('update_time',['label'=>TranslateHelper::t('更新日期')]) ?></th>
			<th nowrap width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		</thead>
		<tbody>
        <?php foreach($productData['data'] as $index=>$row):?>
            <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
                <!-- 
    			<td style="vertical-align: middle; cursor: pointer;"><span
    				class="glyphicon glyphicon-plus" aria-hidden="true"
    				onclick="productList.list.showDetailView(this)"></span></td>
    			 -->
    			
    			<?php if($row['type']!='L'){?>
        			<td nowrap <?= $row['type']=='C' ? "rowspan=".($row['relationship_count'] * 3 - 1) : "" ?>>
        				<input type="checkbox" name="chk_product_info" value="<?=base64_encode($row['sku']) //base 64 防单双引号?>" prodType="<?=$row['type'] ?>" product_id="<?=$row['product_id'] ?>">
        			</td>
    			<?php }?>
    
    			<td nowrap><div style="height: 50px;">
    					<img class="prod_img" style="max-height: 50px; max-width: 80px;"
    						src="<?=$row['photo_primary'] ?>" 
    						data-toggle="popover" data-content="<img width='250px' src='<?=$row['photo_primary'] ?>'>" data-html="true" data-trigger="hover" />
    				</div></td>
    			<td nowrap>
    			    <p>
    			        <?=\yii\helpers\Html::encode($row['sku']) ?>
    			        <?php if($row['type']=='B'){?>
    			            <a href="javascript:void(0);" value="tip_<?=base64_encode($row['sku'])?>" class ="as_sku_tip" style="width:100%;text-align:center;clear:both;">&nbsp(捆绑)</a>
    			        <?php }?>
    			        <?php if(!empty($row['purchase_link'])){?>
    			            <a href='<?= $row['purchase_link'] ?>' target='_blank' class='purchase_link_list_show' purchase_link_json='<?= $row['purchase_link_list'] ?>'><span class='glyphicon glyphicon-shopping-cart' title='已设置了采购链接，点击打开该链接' style='cursor:pointer;color:#2ecc71;margin-left:5px;'></span></a>
    			        <?php }?>
    			    </p>
    			    <?php foreach ($row['other_attributes_arr'] as $other_attributes){?>
    			    <p style="color:#bbb!important;"><?=str_replace(':', ': ', trim($other_attributes)) ?></p>
    			    <?php }?>
    			</td>
    			<td name='chk_product_name'><?=\yii\helpers\Html::encode($row['name']); ?></td>
    			
    			<td><?=$row['class_name'] ?></td>
    			<td><?=(empty($typeMapping[$row['type']])?$row['type']:$typeMapping[$row['type']]) ?></td>
    
    			<td><?=$row['purchase_price']?></td>
    			<td><?=$row['stock'] ?></td>
    			<td><span class="font-color-2"><?=$row['prod_weight'] ?></span></td>
    			<td><span class="font-color-2"><?=$row['update_time'] ?></span></td>
    			<td>
    				<div class="btn-group product_btn_menu" style="white-space: nowrap;font-size:12px">
    					<button type="button" class="btn btn-default" 
    						onclick="productList.list.viewProduct('<?= base64_encode($row['sku']) //base 64 防单双引号?>')"><?=TranslateHelper::t('查看') ?> </button>
    					<button type="button" class="btn btn-default dropdown-toggle"
    						data-toggle="dropdown" aria-expanded="false">
    						<span class="caret"></span> <span class="sr-only">Toggle Dropdown</span>
    					</button>
    					<ul class="dropdown-menu" role="menu">
    
    						<li style="font-size:12px"><a
    							<?= $is_catalog_edit ? 'onclick="productList.list.editProduct(\''. base64_encode($row['sku']).'\',\''.$row['type'].'\')"' : 'style="color: lightgray; " disabled="disabled"'?>
    							><?=TranslateHelper::t('修改') ?></a></li>
    						<li style="font-size:12px"><a
    							<?= $is_catalog_delete ? 'onclick="productList.list.deleteProduct(\''. base64_encode($row['sku']).'\',\''.htmlspecialchars($row['sku']).'\',\''.$row['type'].'\')"' : 'style="color: lightgray; " disabled="disabled"'?>
    							><?=TranslateHelper::t('删除') ?></a></li>
    						<li style="font-size:12px"><a 
    							<?= $is_catalog_edit ? 'onclick="productList.list.copyProduct(\''. base64_encode($row['sku']).'\')"' : 'style="color: lightgray; " disabled="disabled"'?>
    							><?=TranslateHelper::t('复制产品') ?></a></li>
    						<?php if($row['type']=='S'): ?>
    						<li style="font-size:12px"><a
    							<?= $is_catalog_edit ? 'onclick="productList.ConfigAndBundle.copyToConfigProd(\''. base64_encode($row['sku']).'\')"' : 'style="color: lightgray; " disabled="disabled"'?> 
    							><?=TranslateHelper::t('以此创建变参产品') ?></a></li>
    						<?php endif; ?>
    						<?php if($row['type']=='S' or $row['type']=='L'): ?>
    						<li style="font-size:12px"><a 
    							<?= $is_catalog_edit ? 'onclick="productList.ConfigAndBundle.copyToBundleProd(\''. base64_encode($row['sku']).'\')"' : 'style="color: lightgray; " disabled="disabled"'?>
    							><?=TranslateHelper::t('以此创建捆绑产品') ?></a></li>
    						<?php endif; ?>
    					</ul>
    				</div>
    				<input name="lb_product_id" type="hidden" value="<?= $row['product_id'] ?>"/>
    			</td>
    		</tr>
    		<tr>
    			<td colspan='12' valign='top' class='td_space_toggle'>
    				<div id='div_product_info_<?=$row['sku'] ?>'
    					class="div_space_toggle">
    					<table style="width: 100%;">
    						<tbody>
    							<tr>
    								<!--  <th>SKU别名</th>-->
    								<th>标签</th>
    								<!-- <th>供应商采购价</th>-->
    								<th>中文配货名称</th>
    								<th>英文配货名称</th>
    								<th>备注</th>
    							</tr>
    							<tr>
    								<!-- 
    								<td><?php
    						/*	$skuList = array();
    							foreach ($row['aliaslist'] as $alia) {
    								$skuList[] = $alia['alias_sku'];
    							}
    							echo implode(',', $skuList);*/
    							?></td>
    							 -->
    								<td><?php
    							$tagList = array();
    							foreach ($row['taglist'] as $tag) {
    								$tagList[] = $tag['tag_name'];
    							}
    							echo implode(',', $tagList);
    							?></td>
    								<!--  <td><?= $row['brand_id']?></td>-->
    								<td><?= $row['prod_name_ch']?></td>
    								<td><?= $row['prod_name_en']?></td>
    								<td><?= $row['comment']?></td>
    
    							</tr>
    
    						</tbody>
    					</table>
    
    
    				</div>
    			</td>
    		</tr>
    		<tr style="background-color: #d9d9d9;">
            	<td colspan="12" border:1px solid #d1d1d1" style="padding: <?=empty($productData['data'][$index+1]['type']) ? 0 : ($productData['data'][$index+1]['type']=='L' ? 1 : 2.5) ?>px;"></td>
            </tr>
         
        <?php endforeach;?>
        </tbody>
    </table>
	<!-- table -->


</div>
<input id="search_condition" type="hidden" value='<?=$search_condition?>'></input>
<input id="search_count" type="hidden" value='<?=empty($productData['count']) ? 0 : $productData['count']?>'></input>
<?php
if(! empty($productData['pagination'])):?>
<div>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$productData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $productData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>
<div id="div_as_sku_tip" style="display: none">
    <table cellspacing="0" cellpadding="0" style="font-size: 12px;width:100%;float:left;"
		class="table table-hover">
		<thead>
		<tr>
			<th width="50px">图片</th>
			<th width="150px">SKU</th>
			<th width="200px">商品名称</th>
			<th width="20px">数量</th>
		</tr>
		</thead>
		<tbody>
        <?php foreach($productData['bundleArr'] as $bd){?>
            <tr class="as_sku_tip_detail " name="tip_<?=base64_encode($bd['bdsku'])?>" style="height: 50px; display: none;">
    			<td><div style="max-height: 50px; max-width:150px">
    					<img style="max-height: 50px; max-width: 50px;"
    						src="<?=$bd['photo_primary'] ?>" />
    				</div></td>
    			<td>
    			    <div style="max-height:50px; max-width:150px; overflow: hidden;">
    			        <?=$bd['sku'] ?>
    			    </div>
    			</td>
    			<td>
    			    <div style="max-height:50px; max-width: 150px; overflow: hidden;">
    			        <?=$bd['name'] ?>
    			    </div>
    			</td>
    			<td><?=$bd['qty']?></td>
    		</tr>
        <?php }?>
        </tbody>
    </table>
</div>

<div class="modal-body tab-content" id="dialog_edit_info_sml" style="display:none;">
	<input type="text" class="bath_edit_input" value="" placeholder="请输入批量修改的内容" style="width: 100%; height: 40px; padding-left: 5px;"></input>
</div>
<div class="modal-body tab-content" id="dialog_edit_info_battery" style="display:none;">
	<select class="form-control" name="battery">
		<option value="N" selected="">否</option>
		<option value="Y">是</option>
	</select>
</div>
<div class="modal-body tab-content" id="dialog_edit_info_spec1" style="display:none;">
	<table class="table_edit_info_spec1">
	    <tr>
			<td><span>名称开头添加：</span></td>
			<td><input type="text" name="startStr" value="" style="width: 400px; padding-left: 5px;"></td>
	    </tr>
	    <tr>
			<td><span>名称结尾添加：</span></td>
			<td><input type="text" name="endStr" value="" style="width: 400px; padding-left: 5px;"></td>
	    </tr>
	    <tr>
			<td><span>名称中的：</span></td>
			<td>
				<input type="text" name="searchStr" value="" style="width: 150px; padding-left: 5px;">
				<span style="width: 80px;">替换为：</span>
				<input type="text" name="replaceStr" value="" style="width: 150px; float: right; padding-left: 5px;">
			</td>
	    </tr>
	</table>
</div>
