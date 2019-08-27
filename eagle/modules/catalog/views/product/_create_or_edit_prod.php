<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\helpers\UserHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
/*
$this->registerJsFile($baseUrl."/js/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/inventory/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/inventory/text_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."/css/inventory/inventory.css");

$this->registerJs("inventory.stockTake.newStockTake();" , \yii\web\View::POS_READY);
$this->registerJs("inventory.stockTake.prodStatus=new Array();" , \yii\web\View::POS_READY);
foreach ($prodStatus as $k=>$v){
	$this->registerJs("inventory.stockTake.prodStatus.push({'key':'".$k."','value':'".$v."'})",\yii\web\View::POS_READY);
}
$this->registerJs("$('.lnk-del-img').css('display','none');" , \yii\web\View::POS_READY);
*/
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

$brandNames=[];
foreach ($userBrandData as $brandId=>$brand){
	$brandNames[$brandId]=$brand['name'];
}
$supplierNames=[];
foreach ($userSupplierData as $supplierId=>$supplier){
	$supplierNames[$supplierId]=$supplier['name'];
}

$proudctInfo['name'] = ((empty($model->name))?"":$model->name);
$proudctInfo['prod_name_ch']= ((empty($model->prod_name_ch))?"":$model->prod_name_ch);
$proudctInfo['prod_name_en']= ((empty($model->prod_name_en))?"":$model->prod_name_en) ;
$proudctInfo['sku']= ((empty($model->sku))?"":$model->sku);
$proudctInfo['declaration_ch']= ((empty($model->declaration_ch))?"":$model->declaration_ch) ;
$proudctInfo['declaration_en']= ((empty($model->declaration_en))?"":$model->declaration_en);
$proudctInfo['declaration_value_currency']= ((empty($model->declaration_value_currency))?"USD":$model->declaration_value_currency);
$proudctInfo['declaration_value']= ((empty($model->declaration_value))?"0":$model->declaration_value);
$proudctInfo['declaration_code']= ((empty($model->declaration_code))?"":$model->declaration_code);
$proudctInfo['battery']= ((!isset($model->battery) or empty($model->battery))?"":$model->battery);
$proudctInfo['tag_id']= ((empty($taglist[0]['tag_name']))?"":$taglist[0]['tag_name']);
$proudctInfo['brand_id']= ((empty($model->brand_id))?"":$model->brand_id);//实际上是brand name
$proudctInfo['prod_weight']= ((empty($model->prod_weight))?"0":$model->prod_weight);
$proudctInfo['prod_width']= ((empty($model->prod_width))?"0":$model->prod_width);
$proudctInfo['prod_length']= ((empty($model->prod_length))?"0":$model->prod_length);
$proudctInfo['prod_height']= ((empty($model->prod_height))?"0":$model->prod_height) ;
$proudctInfo['check_standard']= ((empty($model->check_standard))?"":$model->check_standard);
$proudctInfo['comment']= ((empty($model->comment))?"":$model->comment);
$proudctInfo['status']=((empty($model->status))?"OS":$model->status);
$proudctInfo['purchase_by']=((empty($model->purchase_by))?"":UserHelper::getFullNameByUid($model->purchase_by));
$proudctInfo['purchase_link']=((empty($model->purchase_link))?"":$model->purchase_link);
// 供应商 赋值
for($i = 0; $i <= 4; $i ++){
	$proudctInfo['supplierlist'][$i]['name'] = (empty($pdsupplierlist[$i]['name'])?"":$pdsupplierlist[$i]['name']);
	$proudctInfo['supplierlist'][$i]['purchase_price'] = (empty($pdsupplierlist[$i]['purchase_price'])?"":$pdsupplierlist[$i]['purchase_price']);
	$proudctInfo['supplierlist'][$i]['purchase_link'] = (empty($pdsupplierlist[$i]['purchase_link'])?"":$pdsupplierlist[$i]['purchase_link']);
}
$sku_readonly_html = "";
if (!empty($_GET['tt'])){
	if ($_GET['tt']=='edit'){
		$sku_readonly_html = 'readonly="readonly"';
	}

	if ( in_array($_GET['tt'], ['copy'])){
		//新建与复制商品 重置 sku
		$proudctInfo['sku']= "";
	}
}
$other_readonly_html = "";
if (!empty($_GET['tt'])){
	if ($_GET['tt']!=='add'){
		$other_readonly_html = 'readonly="readonly"';
	}
}
// 特殊字符处理
foreach($proudctInfo as &$value){
	if (is_string($value))
		$value = htmlspecialchars($value);
}
// 特殊字符处理
if(isset($children) && count($children)>0){
	for($i=0 ;$i<count($children); $i++){
		foreach ($children[$i] as $key=>&$value){
			if (is_string($value))
				$value = htmlspecialchars($value);
		}
	}
}
else 
	$children=array();

$productType = isset($_GET['type'])?$_GET['type']:$model->type;
if(empty($productType)) $productType='S';

if (isset($fromSku))
	$fromSku = htmlspecialchars($fromSku);
else 
	$fromSku='';

$puid=\Yii::$app->user->identity->getParentUid();
$uid=\Yii::$app->user->id;
if(empty($puid)) $puid=$uid;
$Users = UserHelper::getUsersNameByPuid($puid);

?>
<style>
#product-create-form .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow-y: auto;	
}
#product_under_config_tb th{
	text-align:center;
	vertical-align: middle;
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
#product_under_config_tb td{
	text-align:center;
	vertical-align: middle;
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
#product_under_bundle_tb th{
	text-align:center;
	vertical-align: middle;
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
#product_under_bundle_tb td{
	text-align:center;
	vertical-align: middle;
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
#product_alias_table td{
	text-align:center;
	vertical-align: middle;
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
#product_alias_table th{
	text-align:center;
	vertical-align: middle;
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
tbale th,td{
	vertical-align: middle;
}
.content_lfet{
	float:left;
	width:100%;
}
.content_right{
	float:right;
}
#image-list{
	width: 100%!important;
}
/*
#product-create-form td{
	padding:0px!important;
}
*/
</style>
<div class="panel panel-default">
  	<FORM id="product-create-form" role="form">
  		<ul  class="list-unstyled">
  			<li>
  				<input type="hidden" name="tt" value="<?=isset($_GET['tt'])?$_GET['tt']:'' ?>" />
  				<input type="hidden" name="fromSku" value="<?=$fromSku ?>" />
  				<input type="hidden" id="Product_type" class="form-control"name="Product[type]" value="<?=$productType ?>" />
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品基本信息') ?></th></tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right">sku<span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_sku" name="Product[sku]" 
		  					value="<?php echo $proudctInfo['sku'];
		  					if(isset($_GET['type'])){
		  						if($fromSku!=='' && $_GET['type']=='C') echo "_C";
		  						if($fromSku!=='' && $_GET['type']=='B') echo "_B";
		  					}
		  					?>" <?=$sku_readonly_html?>></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('商品名称') ?><span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_name" name="Product[name]" value="<?=$proudctInfo['name'] ?>"></div></td>
		  			</tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('中文配货名称') ?><span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_prod_name_ch" name="Product[prod_name_ch]" value="<?=$proudctInfo['prod_name_ch'] ?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('英文配货名称') ?><span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_prod_name_en" name="Product[prod_name_en]" value="<?=$proudctInfo['prod_name_en'] ?>"></div></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('品牌') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet">
		  					<select class="form-control" id="Product_brand_id" name="Product[brand_id]" value="<?=$proudctInfo['brand_id'] ?>">
		  					<option value="" <?=empty($proudctInfo['brand_id'])?"eslected":'' ?>></option>
		  					<?php foreach ($brandNames as $id=>$val){ ?>
		  						<option value="<?=$val ?>" <?=($proudctInfo['brand_id']==$val)?"selected":""; ?>><?=$val ?></option>
		  					<?php } ?>
		  					</select>
		  				</div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('采购人员') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet">
		  					<select class="form-control" name="Product[purchase_by]">
		  					<?php foreach($Users as $i=>$user){ ?>
		  						<option value="<?=$user['uid'] ?>" <?=($user['username']==$proudctInfo['purchase_by'])?"selected":"" ?>><?=$user['username'] ?></option>
		  					<?php } ?>
		  					</select></div>
		  				</td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('分类') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet">
		  					<div class="dropdown" style="width: 100%;">
                    			<button class="btn eagle-form-control" style="margin: 0px; width: 100%; text-align: right; " type="button" data-toggle="dropdown" aria-expanded="true">
                    				<span id="search_class_id" class_id="" style="float: left; "><?= empty($class_name) ? '未分类' : $class_name ?></span>
                    				<span class="caret"></span>
                    			</button>
                    			<ul class="dropdown-menu" style="width: 100%;">
                    				<li style="font-size: 12px;"><a class="changeClass" class_id="0" onclick="productList.list.changeClass(this)">|- 未分类</a></li>
                    				<?php foreach($classData as $class){?>
                    				<li style="font-size: 12px; <?= 'margin-left:'.((int)(strlen($class['number']) / 2 - 1) * 15).'px'; ?>"><a class="changeClass" class_id="<?= $class['ID'] ?>" onclick="productList.list.changeClass(this)">|- <?= $class['name'] ?></a></li>
                    				<?php }?>
                    			</ul>
                    			<input name="edit_class_id" type="text" value="<?= empty($model->class_id) ? '0' : $model->class_id ?>" style="display: none" />
                    		</div>
		  				</div></td>
		  				<td style="width: 20%;"></td>
		  				<td style="width: 30%;"></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('标签') ?></div></td>
						<td colspan="3">
						<div class="form-group" style="float:left;width:200px;vertical-align:middle;margin:0px;">
							<label for="Product_tag" class="control-label" style="float:left;width:10%;padding:6px 0px;">
							<a class="cursor_pointer" onclick="productList.list.addTagHtml(this)"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
							</label>
							<input type="text" class="form-control" id="Product_tag" name="Tag[tag_name][]" value="<?=$proudctInfo['tag_id']?>" style="float:left;width:85%;"/>
						</div>
		  				<?php if (isset($taglist) && !empty($taglist)){
							$tag_no = 0;
							foreach($taglist as $onetag){
								$tag_no++;
								if ($tag_no == 1) {
									continue;
								}
								echo "<div class=\"form-group\" style=\"float:left;width:200px;vertical-align:middle;margin:0px;\">".
								"<label for=\"Product_tag\" class=\"control-label\" style=\"float:left;width:10%;padding:6px 0px;\">".
								"<a  class=\"cursor_pointer\"  onclick=\"productList.list.delete_form_group(this)\"><span class=\"glyphicon glyphicon-remove-circle\"  class=\"text-danger\" aria-hidden=\"true\"></span></a>".
								"</label>".
								"<input type=\"text\" class=\"form-control\" name=\"Tag[tag_name][]\" value=\"".$onetag['tag_name']."\" style=\"float:left;width:85%;\"/>".
								"</div>";	
							}	
						}?>
		  				</td>
		  			</tr>
		  			<tr>
		  		<?php if( $productType=='C' ) {?>
		  				<td colspan="4" style="vertical-align: middle;">
		  					<table class="table">
								<tr>
									<th colspan="4"><span qtipkey="product_alias_mean"><?= TranslateHelper::t('商品别名');?></span></th>
								</tr>
								<tr>
									<td colspan="4"><?= TranslateHelper::t('变参产品是一个品类集合，并非实际销售的商品，因此不支持别名！');?></td>
								</tr>
							</table>
		  				</td>
		  		<?php }else{?>
		  				<td colspan="4" style="vertical-align: middle;">
							<table id="product_alias_table" class="table">
								<tr>
									<th colspan="6" style="text-align:left;">
										<span qtipkey="product_alias_mean"><?= TranslateHelper::t('商品别名');?></span>
									</th>
								</tr>
								<tr>
									<td colspan="6" style="text-align:left;">
										<button id="btn-add-alias" type="button" class="btn btn-success" onclick="productList.list.addaliasHtml();">
											<?= TranslateHelper::t('添加别名');?>
										</button>
									</td>
								</tr>
								<tr>
									<td style="width:22%;"><?= TranslateHelper::t('别名') ?></td>
									<td style="width:15%;"><?= TranslateHelper::t('单位销售数量') ?></td>
									<td style="width:15%;"><?= TranslateHelper::t('平台') ?></td>
									<td style="width:20%;"><?= TranslateHelper::t('店铺') ?></td>
									<td style="width:15%;"><?= TranslateHelper::t('备注') ?></td>
									<td style="width:8%;"><?= TranslateHelper::t('操作') ?></td>
									<td style="width:0%; display:none;"><?= TranslateHelper::t('状态') ?></td>
								</tr>
							</table>
						</td>
				<?php } ?>
		  			</tr>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr>
						<th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品图片') ?></th>
					</tr>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
						<div role="image-uploader-container" style="width:100%;">
							<?php if ( ! in_array($_GET['tt'], ['view'])):?>
							<div class="btn-group" role="group">
			
								<button type="button" class="btn btn-success" id="btn-uploader"><?= TranslateHelper::t('上传本地图片'); ?></button>
								<button type="button" class="btn btn-success btn-group"
									id="btn-upload-from-lib" data-toggle="modal"
									data-target="#addImagesBox"><?= TranslateHelper::t('通过 URL 添加图片'); ?></button>
							</div>
							<?php endif;?>
						</div>
						<input name="Product[photo_primary]" id="Product_photo_primary" type="hidden" value="">
						<input name="Product[photo_others]" id="Product_photo_others" type="hidden" value="">
		  			</td></tr>
		  		</table>
  			</li>
<?php if($productType=='C'):?>  			
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;" ><div qtipkey="config_prod_children"><?=TranslateHelper::t('子产品列表') ?></div></th></tr>
	<?php if(isset($_GET['tt']) && $_GET['tt']!=='view'):?>
					<tr><td colspan="4"  style="vertical-align: middle;"><div>
							<button type="button" class="btn btn-success" onclick="productList.ConfigAndBundle.addConfigChild()" qtipkey="add_config_child"><?=TranslateHelper::t('添加子产品') ?></button>
							<div class="have_no_srcSku_alert" style="display:none;color:red;font-weight:700;background-color:rgb(135, 225, 255);"><?=TranslateHelper::t('本变参商品由商品：').$proudctInfo['sku'].TranslateHelper::t("通过‘转化成变参产品’获得大部分信息，但该sku产品现在不在子商品列表！保存则此变参商品与原sku商品无任何关系。") ?></div>
						</div></td>
					</tr>
	<?php endif; ?>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
							<table id="product_under_config_tb" style="width: 100%;">
								<tr>
									<th style="width:15%;text-align:center;"><div qtipkey="config_child_img"><?=TranslateHelper::t('图片') ?></div></th>
									<th style="width:20%;text-align:center;"><div qtipkey="config_child_sku">sku</div></th>
									<th style="width:10%;text-align:center;"><?=TranslateHelper::t('状态') ?></th>
									<th style="width:13%;text-align:center;"><input type="text" placeholder="<?=TranslateHelper::t('变参属性1') ?>" id="Product_config_field_1" name="children[config_field_1]" value="<?=isset($configField[0])?$configField[0]:'' ?>" class="form-control"/></th>
									<th style="width:13%;text-align:center;"><input type="text" placeholder="<?=TranslateHelper::t('变参属性2') ?>" id="Product_config_field_2" name="children[config_field_2]" value="<?=isset($configField[1])?$configField[1]:'' ?>" class="form-control"/></th>
									<th style="width:13%;text-align:center;"><input type="text" placeholder="<?=TranslateHelper::t('变参属性3') ?>" id="Product_config_field_3" name="children[config_field_3]" value="<?=isset($configField[2])?$configField[2]:'' ?>" class="form-control"/></th>
									<th style="width:15%;text-align:center;"><?=TranslateHelper::t('操作') ?></th>
								</tr>
<?php if(count($children)>0):
		foreach($children as $index=>$child):?> 
								<tr class="children_product_list_tr" attrStr="<?=$child['other_attributes'] ?>">
									<td >
										<div style="position:relative;">
											<input type="hidden" id="children_try_<?=$index ?>" name="children[photo_primary][]" value="<?=$child['photo_primary'] ?>">
											<img style="width:100%;height:100%;" src="<?=$child['photo_primary'] ?>" id="children_try_<?=$index ?>_img" />
											<a href="javascript:void(0)" onclick="productList.ConfigAndBundle.selectChildPhoto(<?=$index ?>)" style="cursor:pointer;position:absolute;top:0px;right:0px;background-color:rgb(135, 225, 255);padding:0px 3px;"><span class="glyphicon glyphicon-repeat" aria-hidden="true"></span></a>
										</div>
									</td>
									<td><input type="text" name="children[sku][]" value="<?=$child['sku'] ?>" class="form-control"/></td>
									<td><?=(empty($productStatus[$child['status']]))?'--':$productStatus[$child['status']] ?></td>
								<?php 
									$field_value_1 = '';
									if(isset($configField[0])){
										foreach ($child['attrArr'] as $attr_name=>$attr_value){
											if($attr_name == $configField[0]){
												$field_value_1 = $attr_value;
												break;
											}												
										}
									}  
									$field_value_2 = '';
									if(isset($configField[1])){
										foreach ($child['attrArr'] as $attr_name=>$attr_value){
											if($attr_name == $configField[1]){
												$field_value_2 = $attr_value;
												break;
											}
										}
									}
									$field_value_3 = '';
									if(isset($configField[2])){
										foreach ($child['attrArr'] as $attr_name=>$attr_value){
											if($attr_name == $configField[2]){
												$field_value_3 = $attr_value;
												break;
											}
										}
									}
								?>
									<td><input type="text" name="children[config_field_value_1][]" value="<?=$field_value_1 ?>" class="form-control"/></td>
									<td><input type="text" name="children[config_field_value_2][]" value="<?=$field_value_2 ?>" class="form-control"/></td>
									<td><input type="text" name="children[config_field_value_3][]" value="<?=$field_value_3 ?>" class="form-control"/></td>
									<?php if($_GET['tt']=='view') { ?>
									<td>
										<input type="button" value="<?=TranslateHelper::t('查看') ?>" class="btn btn-info" onclick="productList.list.secondViewProduct('<?=base64_encode($child['sku']) ?>')"/>
									</td>
									<?php }else{ ?>
									<td title="<?=TranslateHelper::t('创建变参产品至少需要有一个子产品')?>"><input type="button" value="<?=TranslateHelper::t('删除') ?>" class="btn btn-danger delete_child_prod"/></td>
									<?php } ?>
								</tr>
		
<?php endforeach;
	else:
	if($_GET['tt']!=='view'): ?>			
								<tr class="children_product_list_tr">
									<td >
										<div style="position:relative;">
											<input type="hidden" id="children_try_0" name="children[photo_primary][]" value="">
											<img style="width:100%;height:100%; " src="/images/batchImagesUploader/no-img.png" id="children_try_0_img">
											<a href="javascript:void(0)" onclick="productList.ConfigAndBundle.selectChildPhoto(0)" style="cursor:pointer;position:absolute;top:0px;right:0px;background-color:rgb(135, 225, 255);padding:0px 3px;"><span class="glyphicon glyphicon-repeat" aria-hidden="true"></span></a>
										</div>
									</td>
									<td><input type="text" name="children[sku][]" value="" class="form-control"/></td>
									<td><?=$productStatus['OS'] ?></td>
									<td><input type="text" name="children[config_field_value_1][]" value="" class="form-control"/></td>
									<td><input type="text" name="children[config_field_value_2][]" value="" class="form-control"/></td>
									<td><input type="text" name="children[config_field_value_3][]" value="" class="form-control"/></td>
									<td title="<?=TranslateHelper::t('创建变参产品至少需要有一个子产品')?>"><input type="button" value="<?=TranslateHelper::t('删除') ?>" class="btn btn-danger delete_prod_under_config" disabled="disabled"/></td>
								</tr>
	<?php else:?>
								<tr>
									<td colspan="7" style="text-align:center">
										<b style="color:red;"><?=TranslateHelper::t('该变参产品没有子产品！')?></b>
									</td>
									
								</tr>
	<?php endif;?>						
	<?php endif; ?>								
								
								
							</table>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
<?php endif; ?>
<!-- bundle only start -->

<?php if($productType=='B'):?>  			
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;" ><div qtipkey="bundle_prod_children"><?=TranslateHelper::t('子产品列表') ?></div></th></tr>
	<?php if(isset($_GET['tt']) && $_GET['tt']!=='view'):?>
					<tr><td colspan="4"  style="vertical-align: middle;"><div>
							<button type="button" class="btn btn-success" onclick="productList.ConfigAndBundle.addBundleChild()" qtipkey="add_bundle_child"><?=TranslateHelper::t('添加子产品') ?></button>
							<div class="have_no_srcSku_alert" style="display:none;color:red;font-weight:700;background-color:rgb(135, 225, 255);"><?=TranslateHelper::t('本捆绑商品由商品：').$proudctInfo['sku'].TranslateHelper::t("通过‘转化成捆绑产品’获得大部分信息，但该sku产品现在不在子商品列表！保存则此捆绑产品与原sku商品无任何关系。") ?></div>
						</div></td>
					</tr>
	<?php endif; ?>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
							<table id="product_under_bundle_tb" style="width: 100%;">
								<tr>
									<th style="width:15%;text-align:center;"><div qtipkey="bundle_child_img"><?=TranslateHelper::t('图片') ?></div></th>
									<th style="width:25%;text-align:center;"><div qtipkey="bundle_child_sku">sku</div></th>
									<th style="width:25%;text-align:center;"><?=TranslateHelper::t('商品名称') ?></th>
									<th style="width:10%;text-align:center;"><?=TranslateHelper::t('状态') ?></th>
									<th style="width:10%;text-align:center;"><?=TranslateHelper::t('绑定数量') ?></th>
									<th style="width:15%;text-align:center;"><?=TranslateHelper::t('操作') ?></th>
								</tr>
<?php if(count($children)>0):
		foreach($children as $index=>$child):?> 
								<tr class="children_product_list_tr">
									<td >
										<div style="position:relative;">
											<img style="width:100%;height:100%;" src="<?=$child['photo_primary'] ?>" class="bundle_children_img" />
										</div>
									</td>
									<td><input type="text" name="children[sku][]" value="<?=$child['sku'] ?>" class="form-control"/></td>
									<td class="child_prod_name"><?=$child['name'] ?></td>
									<td class="child_prod_status"><?=(empty($productStatus[$child['status']]))?'--':$productStatus[$child['status']] ?></td>
									<td><input type="text" name="children[bundle_qty][]" value="<?=isset($child['bundle_qty'])?$child['bundle_qty'] :1 ?>" class="form-control"/></td>
									<?php if($_GET['tt']=='view') { ?>
									<td>
										<!--
										<input type="button" value="<?=TranslateHelper::t('查看') ?>" class="btn btn-info" onclick="productList.list.secondViewProduct('<?=base64_encode($child['sku']) ?>')"/>
										-->
									</td>
									<?php }else{ ?>
									<td><input type="button" value="<?=TranslateHelper::t('删除') ?>" class="btn btn-danger delete_child_prod"/></td>
									<?php } ?>
								</tr>
		
<?php endforeach;
	else:
	if($_GET['tt']!=='view'): ?>
								<tr class="children_product_list_tr">
									<td >
										<div style="position:relative;">
											<img style="width:100%;height:100%; " src="/images/batchImagesUploader/no-img.png" id="children_try_0_img">
										</div>
									</td>
									<td><input type="text" name="children[sku][]" value="" placeholder="<?=TranslateHelper::t('输入已存在的普通商品sku') ?>" class="form-control"/></td>
									<td class="child_prod_name"></td>
									<td class="child_prod_status"></td>
									<td><input type="text" name="children[bundle_qty][]" value="1" class="form-control"/></td>
									<td><input type="button" value="<?=TranslateHelper::t('删除') ?>" class="btn btn-danger delete_child_prod"/></td>
								</tr>
	<?php else:?>
								<tr>
									<td colspan="6" style="text-align:center">
										<b style="color:red;"><?=TranslateHelper::t('该捆绑产品没有子产品！')?></b>
									</td>
									
								</tr>
	<?php endif;?>						
	<?php endif; ?>								
								
								
							</table>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
<?php endif; ?>

<!-- bundle only end -->
			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品属性(其他)') ?></th></tr>
<?php if(isset($_GET['tt']) && $_GET['tt']!=='view'):?>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
							<button id="btn-create-attribute" type="button" class="btn btn-success" onclick="productList.list.addOtherAttrHtml();"><?= TranslateHelper::t('添加属性');?></button>
						</td>
					</tr>
<?php endif; ?>
					<tr><td colspan="4"  style="vertical-align: middle;">
							<div id="catalog-product-list-attributes-panel">
								<input type="hidden" id="Product_other_attributes" class="form-control"name="Product[other_attributes]" value="" />
									<?php 
									$Column_key = TranslateHelper::t('属性名');
									$Column_value =  TranslateHelper::t('属性值');
									if (isset($PdAttr) && !empty($PdAttr)){
										foreach($PdAttr as $anAttr){
					echo '
					<div class="form-group">
					<label class="col-sm-2 control-label">
								<a  class="cursor_pointer" onclick="productList.list.delete_form_group(this)" class="text-danger"><span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a> 
							'.$Column_key.'
							</label>
							<div class="col-sm-4">
								<input type="text" class="form-control" name="other_attr_key[]" value="'.$anAttr['key'].'"/>
							</div>
							
							<label class="col-sm-2 control-label">'.$Column_value.'</label>
							<div class="col-sm-4">
								<input type="text" class="form-control" name="other_attr_value[]" value="'.$anAttr['value'].'"/>
							</div>
						</div>
						';			
										}//end of each $PdAttr
									}
									?>
							</div>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品报关信息') ?></th></tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('中文报关名称') ?><span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_ch" name="Product[declaration_ch]" value="<?=$proudctInfo['declaration_ch']?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('英文报关名称') ?><span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_en" name="Product[declaration_en]" value="<?=$proudctInfo['declaration_en']?>"></div></td>
		  			</tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('海关申报货币') ?><span style="color:red;font-size:14px;font-weight:bolder;">*</span></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_value_currency" name="Product[declaration_value_currency]" value="<?=$proudctInfo['declaration_value_currency']?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('海关申报金额') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_value" name="Product[declaration_value]" value="<?=$proudctInfo['declaration_value']?>"></div></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('是否含电池') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet">
		  					<select class="form-control" id="Product_declaration_battery" name="Product[battery]">
		  						<option value="N" <?=($proudctInfo['battery']=='N' or empty($proudctInfo['battery']))?"selected":"" ?>><?=TranslateHelper::t('否') ?></option>
		  						<option value="Y" <?=($proudctInfo['battery']=='Y')?"selected":"" ?>><?=TranslateHelper::t('是') ?></option>
		  					</select>
		  				</div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('报关编码') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_code" name="Product[declaration_code]" value="<?=$proudctInfo['declaration_code']?>"></div></td>
		  			</tr >
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="6"  style="vertical-align: middle;"><?=TranslateHelper::t('商品供应商信息') ?></th></tr>
		  			<?php if( $productType=='C' ) {?>
						<tr>
							<td colspan="6"><?= TranslateHelper::t('变参产品是一个品类集合，并非实际销售的商品，因此不支持采购价！');?></td>
						</tr>
		  			<?php }else if( $productType=='B' ) {?>
						<tr>
							<td colspan="6"><?= TranslateHelper::t('捆绑产品是一个商品的组合，不存在单独的采购价！');?></td>
						</tr>
		  			<?php }else{
		  				for($num = 0; $num < 5; $num++){
		  			?>
		  			<tr>
		  				<td style="width: 15%;"><div class="content_right"><?= $num == 0 ? "首选供应商" : "备选供应商$num" ?></div></td>
		  				<td style="width: 25%;"><div class="content_lfet">
		  					<select class="form-control" name="ProductSuppliers[supplier_id][<?= $num ?>]" value="<?=$proudctInfo['supplierlist'][$num]['name']?>">
		  					<?php foreach ($supplierNames as $id=>$val){ ?>
		  						<option value="<?=$val ?>" <?=($proudctInfo['supplierlist'][$num]['name']==$val)?"selected":'' ?> ><?=$val ?></option>
		  					<?php } ?>
		  					</select>
		  				</div></td>
		  				<td style="width: 15%;"><div class="content_right"><?=TranslateHelper::t('采购价(CNY)') ?></div></td>
		  				<td style="width: 10%;"><div class="content_lfet"><input type="text" class="form-control" name="ProductSuppliers[purchase_price][<?= $num ?>]" value="<?=$proudctInfo['supplierlist'][$num]['purchase_price']?>">
		  				<td style="width: 15%;"><div class="content_right"><?=TranslateHelper::t('采购链接') ?></div></td>
		  				<td style="width: 20%;"><div class="content_lfet"><input type="text" class="form-control" name="ProductSuppliers[purchase_link][<?= $num ?>]" value="<?=$proudctInfo['supplierlist'][$num]['purchase_link']?>">
		  				</div></td>
		  			</tr>
		  			<?php }}?>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品尺寸信息') ?></th></tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('重量(g)') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet" style="width: 60%"><input type="text" class="form-control" id="Product_weight" name="Product[prod_weight]" value="<?=$proudctInfo['prod_weight']?>"></div><span style="color: red;">(默认重量为50g)</span></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('长(cm)') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_length" name="Product[prod_length]" value="<?=$proudctInfo['prod_length']?>"></div></td>
		  			</tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('宽(cm)') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_width" name="Product[prod_width]" value="<?=$proudctInfo['prod_width']?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('高(cm)') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_height" name="Product[prod_height]" value="<?=$proudctInfo['prod_height']?>"></div></td>
		  			</tr >
		  		</table>
  			</li>
  			<li>
		  		<table id="catalog-product-list-commission-table" class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('平台佣金比例') ?></th></tr>
					<?php if(isset($_GET['tt']) && $_GET['tt']!=='view'):?>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
							<button id="btn-create-commission-per" type="button" class="btn btn-success" onclick="productList.list.addCommissionPer();"><?= TranslateHelper::t('添加比例');?></button>
						</td>
					</tr>
					<?php endif; ?>
					<?php 
					if(!empty($model->addi_info)){
						$addi_info = json_decode($model->addi_info, true);
					}
					else {
						$addi_info = [];
					}
					$Column_key = TranslateHelper::t('平台');
					$Column_value =  TranslateHelper::t('比例值 (%)');
					if (!empty($addi_info['commission_per'])){
                        $html = '';
						foreach($addi_info['commission_per'] as $commission_key => $commission_val){
							$html .= '
                            <tr>
                                <td style="width: 20%;">
                      				<div class="content_right">
                      					<a class="cursor_pointer" onclick="productList.list.delete_commission_group(this)" class="text-danger"><span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a> 
                      					'.$Column_key.'
                      				</div>
                      			</td>
                      			<td style="width: 30%;">
                    	  			<select class="form-control" name="commission_platform[]">
                                    <option value="" >请选择平台</option>';
							foreach($platformAccount as $plat => $val){
                                if(!empty($plat) && $plat != '所有平台' && !in_array($plat, ['wish', 'ebay', 'cdiscount'])){
                                    $html .= '<option value="'.$plat.'" '.($plat == $commission_key ? 'selected' : '').'>'.$plat.'</option>';
                                }
                            }
                    	  	$html .= '</select>
                      			</td>
                      			<td style="width: 20%;">
                      				<div class="content_right">'.$Column_value.'</div>
                      			</td>
                      			<td style="width: 30%;">
                      				<input type="text" class="form-control" name="commission_value[]" value="'.$commission_val.'" />
                      			</td>
							</tr>';			
						}
						echo $html;
					}
					?>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('质检标准') ?></th></tr>
		  			<tr>
		  				<td colspan="4"  style="vertical-align: middle;">
		  					<textarea class="form-control" rows="5" name="Product[check_standard]" id="Product_check_standard"><?=$proudctInfo['check_standard'] ?></textarea>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('备注') ?></th></tr>
		  			<tr>
		  				<td colspan="4"  style="vertical-align: middle;">
		  					<textarea class="form-control" rows="5" name="Product[comment]" id="Product_comment"><?= $proudctInfo['comment']?></textarea>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
		</ul>
  	</FORM>
	<input id="data_empty_message" type="hidden" value="<?=TranslateHelper::t('无输入数据,请重新输入') ?>">
</div>

<div class="slecest_child_photo_dialog"></div>
<div id="other_attribute" style="display: none">
	<div class="form-group">
		<label class="col-sm-2 control-label"> <a  class="cursor_pointer" 
			onclick="productList.list.delete_form_group(this)"
			class="text-danger"><span class="glyphicon glyphicon-remove-circle"
				aria-hidden="true"></span></a> <?= TranslateHelper::t('属性名')?>
		</label>
		<div class="col-sm-4">
			<input type="text" class="form-control" name="other_attr_key[]" value="" />
		</div>

		<label class="col-sm-2 control-label"><?= TranslateHelper::t('属性值')?></label>
		<div class="col-sm-4">
			<input type="text" class="form-control" name="other_attr_value[]" value="" />
		</div>
	</div>
</div>

<div id="commission_per" style="display: none">
	<table>
		<tr>
  			<td style="width: 20%;">
  				<div class="content_right">
  					<a class="cursor_pointer" onclick="productList.list.delete_commission_group(this)" class="text-danger"><span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span></a> 
  					平台
  				</div>
  			</td>
  			<td style="width: 30%;">
	  			<select class="form-control" name="commission_platform[]">
	  				<option value="" >请选择平台</option>
	  				<?php $html = '';
	  				      foreach($platformAccount as $plat => $val){
                              if(!empty($plat) && $plat != '所有平台' && !in_array($plat, ['wish', 'ebay', 'cdiscount'])){
                                  $html .= '<option value="'.$plat.'">'.$plat.'</option>';
                              }
                          }
                          echo $html;
                    ?>
	  			</select>
  			</td>
  			<td style="width: 20%;">
  				<div class="content_right">比例值 (%)</div>
  			</td>
  			<td style="width: 30%;">
  				<input type="text" class="form-control" name="commission_value[]" value="" />
  			</td>
  		</tr>
  	</table>
</div>


<script>
    $.initQtip();
<?php 
//只读的情况下设置readonly 和  disabled
if ( in_array($_GET['tt'], ['view'])):?>
	$('#product-create-form input').prop('readonly','readonly');
	$('#product-create-form textarea').prop('disabled','disabled');
	$('#product-create-form button').prop('disabled','disabled');
	$('#product-create-form .cursor_pointer').css('display','none');
	$('#product-create-form .delete_prod_under_config').remove();
	$('#product-create-form .children_product_list_tr td').eq(0).find("a").remove();
<?php endif;
if ( !in_array($_GET['tt'], ['add'])):?>
	$("#product-create-form input[name='children[sku][]']").prop('readonly','readonly');
	$("#product-create-form input[name^='children[config_field_value_']").prop('readonly','readonly');
<?php endif;
// 设置
$ImageList = [];

if (!empty($photos)){
	if (is_array($photos)){
		foreach ($photos as $photo_url){
			if(preg_match('/\/no\-img\.png$/i', $photo_url))
				continue;
			$row['thumbnail'] = $photo_url;
			$row['original'] = $photo_url;
			$ImageList[] = $row;
		}
	}
}
?>
<?php if(isset($_GET['type']) && $_GET['type']=='C') {?>
productList.ConfigAndBundle.initSkuChange();
<?php } ?>
	
<?php if(!isset($_GET['type']) or ($_GET['type']=='S' or $_GET['type']=='L') ){?>
productList.list.existingImages = <?=json_encode($ImageList);?>;
<?php }elseif($_GET['type']=='C' or $_GET['type']=='B'){?>
productList.ConfigAndBundle.existingImages = <?=json_encode($ImageList);?>;
<?php } ?>

productList.ConfigAndBundle.configField=new Array();
<?php
if(isset($configField)){
	foreach ($configField as $index=>$field){?>
	productList.ConfigAndBundle.configField.push("<?=$field ?>");
<?php 	}
}?>
	
<?php
if(isset($productStatus)){
	foreach ($productStatus as $k=>$v){?>
	productList.ConfigAndBundle.productStatus.push(["<?=$k ?>","<?=$v ?>"]);
<?php 	}
}?>
	
<?php if (! empty($aliaslist)):?>
//alias 数据 生成
productList.list.existtingAliasList=<?= json_encode($aliaslist)?>;
productList.list.fillAliasData();
<?php endif;?>

productList.list.platformAccount=<?= json_encode($platformAccount)?>;

</script>
