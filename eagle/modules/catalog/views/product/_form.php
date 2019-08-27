<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

/* @var $this yii\web\View */
/* @var $model eagle\modules\catalog\models\Product */

$product_base_info_attributes_label = [
'sku'=>TranslateHelper::t('sku'),
'name'=>TranslateHelper::t('商品名称'),
'prod_name_ch'=>TranslateHelper::t('中文配货名称'),
'prod_name_en'=>TranslateHelper::t('英文配货名称'),
'declaration_ch'=>TranslateHelper::t('商品中文报关名称'),
'declaration_en'=>TranslateHelper::t('商品英文报关名称'),
'declaration_value_currency'=>TranslateHelper::t('海关申报货币'),
'declaration_value'=>TranslateHelper::t('海关申报金额'),
'brand_id'=>TranslateHelper::t('品牌'),
];


$product_sell_info_attributes_label = [];

$proudctInfo['name'] = ((empty($model->name))?"":$model->name);
$proudctInfo['prod_name_ch']= ((empty($model->prod_name_ch))?"":$model->prod_name_ch);
$proudctInfo['prod_name_en']= ((empty($model->prod_name_en))?"":$model->prod_name_en) ;
$proudctInfo['sku']= ((empty($model->sku))?"":$model->sku);
$proudctInfo['declaration_ch']= ((empty($model->declaration_ch))?"":$model->declaration_ch) ;
$proudctInfo['declaration_en']= ((empty($model->declaration_en))?"":$model->declaration_en);
$proudctInfo['declaration_value_currency']= ((empty($model->declaration_value_currency))?"":$model->declaration_value_currency);
$proudctInfo['declaration_value']= ((empty($model->declaration_value))?"":$model->declaration_value);
$proudctInfo['tag_id']= ((empty($taglist[0]['tag_name']))?"":$taglist[0]['tag_name']);
$proudctInfo['brand_id']= ((empty($model->brand_id))?"":$model->brand_id);
$proudctInfo['prod_weight']= ((empty($model->prod_weight))?"0":$model->prod_weight);
$proudctInfo['prod_width']= ((empty($model->prod_width))?"0":$model->prod_width);
$proudctInfo['prod_length']= ((empty($model->prod_length))?"0":$model->prod_length);
$proudctInfo['prod_height']= ((empty($model->prod_height))?"0":$model->prod_height) ;
$proudctInfo['check_standard']= ((empty($model->check_standard))?"":$model->check_standard);
$proudctInfo['comment']= ((empty($model->comment))?"":$model->comment);
// 供应商 赋值
for($i = 0; $i <= 4; $i ++){
	$proudctInfo['supplierlist'][$i]['name'] = (empty($pdsupplierlist[$i]['name'])?"":$pdsupplierlist[$i]['name']);
	$proudctInfo['supplierlist'][$i]['purchase_price'] = (empty($pdsupplierlist[$i]['purchase_price'])?"":$pdsupplierlist[$i]['purchase_price']);
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

// 特殊字符处理
foreach($proudctInfo as &$value){
	if (is_string($value))
	$value = htmlspecialchars($value);
}

?>
<div class="bs-docs-section">
	<form class="form-horizontal" role="form" id="product-create-form">
		<input type="hidden" name='tt' value='<?=$_GET['tt']?>' />
		<!--  商品基本信息  -->
		<h5 class="page-header"><?= TranslateHelper::t('商品基本信息')?></h5>
		<div class="form-group">
			<label for="Product_name" class="col-sm-3 control-label"><?= TranslateHelper::t('商品名称')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_name"
					name="Product[name]" value="<?= $proudctInfo['name'] ?>" />
			</div>
		</div>
		<div class="form-group">
			<label for="Product_prod_name_ch" class="col-sm-3 control-label"><?= TranslateHelper::t('中文配货名称')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_prod_name_ch"
					name="Product[prod_name_ch]"
					value="<?=$proudctInfo['prod_name_ch'] ?>" />
			</div>
		</div>

		<div class="form-group">
			<label for="Product_prod_name_en" class="col-sm-3 control-label"><?= TranslateHelper::t('英文配货名称')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_prod_name_en"
					name="Product[prod_name_en]"
					value="<?=$proudctInfo['prod_name_en']?>" />
			</div>
		</div>

		<div class="form-group">
			<label for="Product_sku" class="col-sm-3 control-label"><?= TranslateHelper::t('sku')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_sku"
					name="Product[sku]" value="<?= $proudctInfo['sku']?>"
					<?= $sku_readonly_html?> />
			</div>
		</div>

		<div class="form-group">
			<label for="Product_declaration_ch" class="col-sm-3 control-label"><?= TranslateHelper::t('商品中文报关名称')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_declaration_ch"
					name="Product[declaration_ch]"
					value="<?=$proudctInfo['declaration_ch']?>" />
			</div>
		</div>
		<div class="form-group">
			<label for="Product_declaration_en" class="col-sm-3 control-label"><?= TranslateHelper::t('商品英文报关名称')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_declaration_en"
					name="Product[declaration_en]"
					value="<?= $proudctInfo['declaration_en']?>" />
			</div>
		</div>
		<div class="form-group">
			<label for="Product_declaration_value_currency"
				class="col-sm-3 control-label"><?= TranslateHelper::t('海关申报货币')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control"
					id="Product_declaration_value_currency"
					name="Product[declaration_value_currency]"
					value="<?= $proudctInfo['declaration_value_currency']?>" />
			</div>
		</div>
		<div class="form-group">
			<label for="Product_declaration_value" class="col-sm-3 control-label"><?= TranslateHelper::t('海关申报金额')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control"
					id="Product_declaration_value" name="Product[declaration_value]"
					value="<?= $proudctInfo['declaration_value']?>" />
			</div>
		</div>

		<div class="form-group">
			<label for="Product_tag" class="col-sm-3 control-label">
				<?= TranslateHelper::t('标签')?>
				<a class="cursor_pointer" onclick="productList.list.addTagHtml(this)"><span
					class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>

			</label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_tag"
					name="Tag[tag_name][]" value="<?= $proudctInfo['tag_id']?>" />
			</div>
		</div>
		
		<?php 
		
		if (!empty($taglist)){
			$tag_no = 0;
			foreach($taglist as $onetag){
				$tag_no++;
				if ($tag_no == 1) {
					continue;
				}

				echo "<div class=\"form-group\">".
				"<label for=\"Product_tag\" class=\"col-sm-3 control-label\">".
				"<a  class=\"cursor_pointer\"  onclick=\"productList.list.delete_form_group(this)\"><span class=\"glyphicon glyphicon-remove-circle\"  class=\"text-danger\" aria-hidden=\"true\"></span></a>".
				"</label>".
				"<div class=\"col-sm-9\">".
				"<input type=\"text\" class=\"form-control\" name=\"Tag[tag_name][]\" value=\"".$onetag['tag_name']."\"/>".
				"</div></div>";	
			}	
		}
	
		?>
		
		
		<div class="form-group">
			<label for="Product_brand_id" class="col-sm-3 control-label"><?= TranslateHelper::t('品牌')?></label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="Product_brand_id"
					name="Product[brand_id]" value="<?= $proudctInfo['brand_id']?>" />
			</div>
		</div>
		
		<h5 class="page-header"><?= TranslateHelper::t('商品别名');?> 
			<button
				id="btn-add-alias" type="button" class="btn btn-default "
				onclick="productList.list.addaliasHtml();"><?= TranslateHelper::t('添加别名');?></button>
		</h5>
		<table id="product_alias_table" class="table">
			<thead>
			<tr>
				<th><?= TranslateHelper::t('别名') ?></th>
				<th><?= TranslateHelper::t('单位销售数量') ?></th>
				<th><?= TranslateHelper::t('网站') ?></th>
				<th><?= TranslateHelper::t('备注') ?></th>
				<th><?= TranslateHelper::t('操作') ?></th>
			</tr>
			</thead>
		</table>

		<!--  商品属性 -->
		<h5 class="page-header"><?= TranslateHelper::t('商品属性');?> <button
				id="btn-create-attribute" type="button" class="btn btn-default "
				onclick="productList.list.addOtherAttrHtml();"><?= TranslateHelper::t('添加属性');?></button>
		</h5>
		<div id="catalog-product-list-attributes-panel">
			<input type="hidden" id="Product_other_attributes" class="form-control"
				name="Product[other_attributes]" value="" />
				<?php 
				$Column_key = TranslateHelper::t('属性名');
				$Column_value =  TranslateHelper::t('属性值');
				if (!empty($PdAttr)){
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


		<!--  销售信息 -->
		<h5 class="page-header"><?= TranslateHelper::t('销售信息')?></h5>

		<div class="form-group">
			<label for="Product_supplier_id" class="col-sm-2 control-label"><?= TranslateHelper::t('首选供应商')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[supplier_id][]"
					value="<?=$proudctInfo['supplierlist'][0]['name']?>">
			</div>

			<label for="Product_purchase_price" class="col-sm-2 control-label"><?= TranslateHelper::t('采购价(CNY)')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[purchase_price][]"
					value="<?=$proudctInfo['supplierlist'][0]['purchase_price']?>">
			</div>
		</div>

		<div class="form-group">
			<label for="Product_supplier_id" class="col-sm-2 control-label"><?= TranslateHelper::t('备选供应商1')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[supplier_id][]"
					value="<?=$proudctInfo['supplierlist'][1]['name']?>">
			</div>

			<label for="Product_purchase_price" class="col-sm-2 control-label"><?= TranslateHelper::t('采购价(CNY)')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[purchase_price][]"
					value="<?=$proudctInfo['supplierlist'][1]['purchase_price']?>">
			</div>
		</div>

		<div class="form-group">
			<label for="Product_supplier_id" class="col-sm-2 control-label"><?= TranslateHelper::t('备选供应商2')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[supplier_id][]"
					value="<?=$proudctInfo['supplierlist'][2]['name']?>">
			</div>

			<label for="Product_purchase_price" class="col-sm-2 control-label"><?= TranslateHelper::t('采购价(CNY)')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[purchase_price][]"
					value="<?=$proudctInfo['supplierlist'][2]['purchase_price']?>">
			</div>
		</div>

		<div class="form-group">
			<label for="Product_supplier_id" class="col-sm-2 control-label"><?= TranslateHelper::t('备选供应商3')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[supplier_id][]"
					value="<?=$proudctInfo['supplierlist'][3]['name']?>">
			</div>

			<label for="Product_purchase_price" class="col-sm-2 control-label"><?= TranslateHelper::t('采购价(CNY)')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[purchase_price][]"
					value="<?=$proudctInfo['supplierlist'][3]['purchase_price']?>">
			</div>
		</div>

		<div class="form-group">
			<label for="Product_supplier_id" class="col-sm-2 control-label"><?= TranslateHelper::t('备选供应商4')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[supplier_id][]"
					value="<?=$proudctInfo['supplierlist'][4]['name']?>">
			</div>

			<label for="Product_purchase_price" class="col-sm-2 control-label"><?= TranslateHelper::t('采购价(CNY)')?></label>
			<div class="col-sm-4">
				<input type="text" class="form-control"
					name="ProductSuppliers[purchase_price][]"
					value="<?=$proudctInfo['supplierlist'][4]['purchase_price']?>">
			</div>
		</div>

		<div class="form-group">
			<label for="Product_weight" class="col-sm-3 control-label"><?= TranslateHelper::t('重量(g)')?></label>
			<div class="col-sm-3">
				<input type="text" class="form-control" id="Product_weight"
					name="Product[prod_weight]"
					value="<?= $proudctInfo['prod_weight']?>" />
			</div>

			<label for="Product_width" class="col-sm-3 control-label"><?= TranslateHelper::t('宽(cm)')?></label>
			<div class="col-sm-3">
				<input type="text" class="form-control" id="Product_width"
					name="Product[prod_width]" value="<?=$proudctInfo['prod_width'] ?>" />
			</div>
		</div>

		<div class="form-group">
			<label for="Product_length" class="col-sm-3 control-label"><?= TranslateHelper::t('长(cm)')?></label>
			<div class="col-sm-3">
				<input type="text" class="form-control" id="Product_length"
					name="Product[prod_length]"
					value="<?= $proudctInfo['prod_length']?>" />
			</div>

			<label for="Product_height" class="col-sm-3 control-label"><?= TranslateHelper::t('高(cm)')?></label>
			<div class="col-sm-3">
				<input type="text" class="form-control" id="Product_height"
					name="Product[prod_height]"
					value="<?=$proudctInfo['prod_height']?>" />
			</div>
		</div>



		<!--  图片信息 -->
		<!--  -->
		<h5 class="page-header"><?= TranslateHelper::t('图片信息')?> <small class="text-danger"><?= TranslateHelper::t('红色边框为主图')?></small></h5>
		<div role="image-uploader-container">
			<?php if ( ! in_array($_GET['tt'], ['view'])):?>
			<div class="btn-group" role="group">
				
				<button type="button" class="btn btn-info" id="btn-uploader"><?= TranslateHelper::t('上传本地图片'); ?></button>
				<button type="button" class="btn btn-info btn-group"
					id="btn-upload-from-lib" data-toggle="modal"
					data-target="#addImagesBox"><?= TranslateHelper::t('通过 URL 添加图片'); ?></button>
			</div>
			<?php endif;?>
		</div>
		<input name="Product[photo_primary]" id="Product_photo_primary" type="hidden" value="">
		<input name="Product[photo_others]" id="Product_photo_others" type="hidden" value="">

		<!--  质检标准 -->
		<h5 class="page-header"><?= TranslateHelper::t('质检标准')?></h5>

		<label for="Product_check_standard" class="col-sm-2 control-label"><?= TranslateHelper::t('质检标准')?></label>
		<div class="col-sm-10">
			<textarea class="form-control" rows="5"
				name="Product[check_standard]" id="Product_check_standard"><?=$proudctInfo['check_standard'] ?></textarea>
		</div>



		<!--  备注  -->

		<h5 class="page-header"><?= TranslateHelper::t('备注')?></h5>
		<label for="Product_comment" class="col-sm-2 control-label"><?= TranslateHelper::t('备注')?></label>
		<div class="col-sm-10">
			<textarea class="form-control" rows="5" name="Product[comment]"
				id="Product_comment"><?= $proudctInfo['comment']?></textarea>
		</div>

	</form>

</div>
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

<script>
<?php 
//只读的情况下设置readonly 和  disabled
if ( in_array($_GET['tt'], ['view'])):?>
	$('#product-create-form input').prop('readonly','readonly');
	$('#product-create-form textarea').prop('disabled','disabled');
	$('#product-create-form button').prop('disabled','disabled');
	$('#product-create-form .cursor_pointer').css('display','none');
<?php endif;

// 设置
$ImageList = [];

if (!empty($photos)){
	if (is_array($photos)){
		foreach ($photos as $photo_url){
			$row['thumbnail'] = $photo_url;
			$row['original'] = $photo_url;
			$ImageList[] = $row;
		}
	}
}
?>

productList.list.existingImages = <?=json_encode($ImageList);?>;
<?php if (! empty($aliaslist)):?>
//alias 数据 生成
productList.list.existtingAliasList=<?= json_encode($aliaslist)?>;
productList.list.fillAliasData();
//alert(productList.list.existtingAliasList);
<?php endif;?>
</script>
