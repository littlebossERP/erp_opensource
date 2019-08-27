<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\helpers\UserHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

$proudctInfo['name'] = ((empty($model->name))?"":$model->name);
$proudctInfo['prod_name_ch']= ((empty($model->prod_name_ch))?"":$model->prod_name_ch);
$proudctInfo['prod_name_en']= ((empty($model->prod_name_en))?"":$model->prod_name_en) ;
$proudctInfo['sku']= ((empty($model->sku))?"":$model->sku);
$proudctInfo['declaration_ch']= ((empty($model->declaration_ch))?"":$model->declaration_ch) ;
$proudctInfo['declaration_en']= ((empty($model->declaration_en))?"":$model->declaration_en);
$proudctInfo['declaration_value_currency']= ((empty($model->declaration_value_currency))?"CNY":$model->declaration_value_currency);
$proudctInfo['declaration_value']= ((empty($model->declaration_value))?"0":$model->declaration_value);
$proudctInfo['declaration_code']= ((empty($model->declaration_code))?"":$model->declaration_code);
$proudctInfo['battery']= ((!isset($model->battery) or empty($model->battery))?"":$model->battery);
$proudctInfo['tag_id']= ((empty($taglist[0]['tag_name']))?"":$taglist[0]['tag_name']);
$proudctInfo['brand_id']= ((empty($model->brand_id))?"":$model->brand_id);
$proudctInfo['prod_weight']= ((empty($model->prod_weight))?"0":$model->prod_weight);
$proudctInfo['prod_width']= ((empty($model->prod_width))?"0":$model->prod_width);
$proudctInfo['prod_length']= ((empty($model->prod_length))?"0":$model->prod_length);
$proudctInfo['prod_height']= ((empty($model->prod_height))?"0":$model->prod_height) ;
$proudctInfo['check_standard']= ((empty($model->check_standard))?"":$model->check_standard);
$proudctInfo['comment']= ((empty($model->comment))?"":$model->comment);
$proudctInfo['status']=((empty($model->status))?"OS":$model->status);
$proudctInfo['purchase_by']=((empty($model->purchase_by))?"":UserHelper::getFullNameByUid($model->purchase_by));
$proudctInfo['capture_user_name']=((is_numeric($model->capture_user_id))?UserHelper::getFullNameByUid($model->capture_user_id):"");
$proudctInfo['purchase_link']=((empty($model->purchase_link))?"":$model->purchase_link);
// 供应商 赋值
for($i = 0; $i <= 4; $i ++){
	$proudctInfo['supplierlist'][$i]['name'] = (empty($pdsupplierlist[$i]['name'])?"":$pdsupplierlist[$i]['name']);
	$proudctInfo['supplierlist'][$i]['purchase_price'] = (empty($pdsupplierlist[$i]['purchase_price'])?"":$pdsupplierlist[$i]['purchase_price']);
	$proudctInfo['supplierlist'][$i]['purchase_link'] = (empty($pdsupplierlist[$i]['purchase_link'])?"":$pdsupplierlist[$i]['purchase_link']);
}
$readonly_html = 'readonly="readonly"';

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
tbale th,td{
	vertical-align: middle;
}
.content_lfet{
	float:left;
}
.content_right{
	float:right;
}
#image-list{
	width: 100%!important;
}
</style>
<div class="panel panel-default">
  	<FORM id="product-create-form" role="form">
  		<ul  class="list-unstyled">
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品基本信息') ?></th></tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right">sku</div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" name="Product[sku]" value="<?=$proudctInfo['sku']?>" <?=$readonly_html?>></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('商品名称') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" name="Product[name]" value="<?=$proudctInfo['name'] ?>" <?=$readonly_html?>></div></td>
		  			</tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('中文配货名称') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" name="Product[prod_name_ch]" value="<?=$proudctInfo['prod_name_ch'] ?>" <?=$readonly_html?>></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('英文配货名称') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" name="Product[prod_name_en]" value="<?=$proudctInfo['prod_name_en'] ?>" <?=$readonly_html?>></div></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('品牌') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" name="Product[brand_id]" value="<?=$proudctInfo['brand_id'] ?>" <?=$readonly_html?>></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('采购人员') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" value="<?=$proudctInfo['purchase_by'] ?>" <?=$readonly_html?>></div></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('分类') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" value="<?= empty($class_name) ? '未分类' : $class_name ?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('最后修改者') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" value="<?=$proudctInfo['capture_user_name'] ?>" <?=$readonly_html?>></div></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('标签') ?></div></td>
						<td colspan="3">
						<div class="form-group" style="float:left;width:200px;vertical-align:middle;margin:0px;">
							<label for="Product_tag" class="control-label" style="float:left;width:10%;padding:6px 0px;">
							<a class="cursor_pointer" onclick="productList.list.addTagHtml(this)"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
							</label>
							<?php if ($proudctInfo['tag_id']!==''){?>
							<input type="text" class="form-control" id="Product_tag" name="Tag[tag_name][]" value="<?=$proudctInfo['tag_id']?>" style="float:left;width:85%;"/>
							<?php }else{ ?>
							<?=TranslateHelper::t('无标签') ?>
							<?php } ?>
						</div>
		  				<?php if (isset($taglist) && !empty($taglist)){
							$tag_no = 0;
							foreach($taglist as $onetag){
								$tag_no++;
								if ($tag_no == 1) {
									continue;
								}
								echo "<div class=\"form-group\" style=\"float:left;width:200px;vertical-align:middle;margin:0px;\">".
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
		  		<?php }else{
		  				if(count($aliaslist)>0){
		  			?>
		  				<td colspan="4" style="vertical-align: middle;">
							<table class="table">
								<tr>
									<th colspan="5"><span qtipkey="product_alias_mean"><?= TranslateHelper::t('商品别名');?></span></th>
								</tr>
								<tr>
									<td style="width:25%;text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?= TranslateHelper::t('别名') ?></td>
									<td style="width:15%;text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?= TranslateHelper::t('单位销售数量') ?></td>
									<td style="width:20%;text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?= TranslateHelper::t('平台') ?></td>
									<td style="width:25%;text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?= TranslateHelper::t('店铺') ?></td>
									<td style="width:15%;text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?= TranslateHelper::t('备注') ?></td>
								</tr>
							<?php foreach ($aliaslist as $row):?>
								<tr>
									<td style="text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?=$row['alias_sku'] ?></td>
									<td style="text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?=$row['pack'] ?></td>
									<td style="text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?=$row['platform'] ?></td>
									<td style="text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?=$row['shopname'] ?></td>
									<td style="text-align:center;vertical-align: middle;padding: 4px;border: 1px solid rgb(202,202,202);"><?=$row['comment'] ?></td>
								</tr>
							<?php endforeach;?>
							</table>
						</td>
				<?php }else{?>
						<td colspan="4" style="vertical-align: middle;">
							<table class="table">
								<tr>
									<th colspan="4"><span qtipkey="product_alias_mean"><?= TranslateHelper::t('商品别名');?></span></th>
								</tr>
								<tr>
									<td colspan="4"><?= TranslateHelper::t('无商品别名');?></td>
								</tr>
							</table>
						</td>
				<?php } ?>
		  			</tr>
		  			<?php } ?>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr>
						<th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品图片') ?></th>
					</tr>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
						<div role="image-uploader-container" style="width:100%;">
						<?php foreach ($photos as $photo_url): ?>
							<div class="image-item col-xs-2" style="width:130px;height:130px;">
								<a class="thumbnail <?=($photo_url==$model->photo_primary)?'select_photo':'' ?>" style="width:110px;height:110px;text-align:center;vertical-align: middle;display: table-cell;">
								<img style="max-width:100px; max-height:100px;" src="<?=$photo_url ?>">
								</button>
								</a>
							</div>
						<?php endforeach; ?>
						</div>
						<input name="Product[photo_primary]" type="hidden" value="<?=$model->photo_primary ?>">
		  			</td></tr>
		  		</table>
  			</li>
<?php if($productType=='C'):?>  			
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;" ><div qtipkey="config_prod_children"><?=TranslateHelper::t('子产品列表') ?></div></th></tr>
		  			<tr><td colspan="4"  style="vertical-align: middle;">
							<table id="product_under_config_tb" style="width: 100%;">
								<tr>
									<th style="width:15%;text-align:center;"><div qtipkey="config_child_img"><?=TranslateHelper::t('图片') ?></div></th>
									<th style="width:20%;text-align:center;"><div qtipkey="config_child_sku">sku</div></th>
									<th style="width:10%;text-align:center;"><?=TranslateHelper::t('状态') ?></th>
									<th style="width:13%;text-align:center;"><input type="text" name="children[config_field_1]" value="<?=isset($configField[0])?$configField[0]:'变参属性1' ?>" class="form-control"/></th>
									<th style="width:13%;text-align:center;"><input type="text" name="children[config_field_2]" value="<?=isset($configField[1])?$configField[1]:'变参属性2' ?>" class="form-control"/></th>
									<th style="width:13%;text-align:center;"><input type="text" name="children[config_field_3]" value="<?=isset($configField[2])?$configField[2]:'变参属性3' ?>" class="form-control"/></th>
									<th style="width:15%;text-align:center;"><?=TranslateHelper::t('操作') ?></th>
								</tr>
<?php if(count($children)>0):
		foreach($children as $index=>$child):?> 
								<tr class="children_product_list_tr" attrStr="<?=$child['other_attributes'] ?>">
									<td >
										<div style="position:relative;">
											<img style="width:100%;height:100%;" src="<?=$child['photo_primary'] ?>" />
										</div>
									</td>
									<td><?=$child['sku'] ?></td>
									<td><?=$productStatus[$child['status']] ?></td>
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
									<td><?=$field_value_1 ?></td>
									<td><?=$field_value_2 ?></td>
									<td><?=$field_value_3 ?></td>
									<td>
										<input type="button" value="<?=TranslateHelper::t('查看') ?>" class="btn btn-info" onclick="productList.list.viewProduct('<?=base64_encode($child['sku']) ?>')"/>
									</td>
								</tr>
<?php 	endforeach;
	else: ?>
								<tr>
									<td colspan="7" style="text-align:center">
										<b style="color:red;"><?=TranslateHelper::t('该变参产品没有子产品！')?></b>
									</td>
								</tr>					
<?php endif; ?>
							</table>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
<?php endif; ?>
<?php if($productType=='B'):?>  			
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;" ><div qtipkey="bundle_prod_children"><?=TranslateHelper::t('子产品列表') ?></div></th></tr>
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
									<td><?=$child['sku'] ?></td>
									<td><?=$child['name'] ?></td>
									<td ><?=(empty($productStatus[$child['status']]))?'--':$productStatus[$child['status']] ?></td>
									<td><?=isset($child['bundle_qty'])?$child['bundle_qty'] :0 ?></td>
									<td>
										<input type="button" value="<?=TranslateHelper::t('查看') ?>" class="btn btn-info" onclick="productList.list.viewProduct('<?=base64_encode($child['sku']) ?>')"/>
									</td>
								</tr>
<?php endforeach;
	else: ?>
								<tr>
									<td colspan="6" style="text-align:center">
										<b style="color:red;"><?=TranslateHelper::t('该捆绑产品没有子产品！')?></b>
									</td>
								</tr>
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
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品报关信息') ?></th></tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('中文报关名称') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_ch" name="Product[declaration_ch]" value="<?=$proudctInfo['declaration_ch']?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('英文报关名称') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_en" name="Product[declaration_en]" value="<?=$proudctInfo['declaration_en']?>"></div></td>
		  			</tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('海关申报货币') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_value_currency" name="Product[declaration_value_currency]" value="<?=$proudctInfo['declaration_value_currency']?>"></div></td>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('海关申报金额') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_value" name="Product[declaration_value]" value="<?=$proudctInfo['declaration_value']?>"></div></td>
		  			</tr >
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('是否含电池') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_declaration_battery" name="Product[battery]" value="<?=($proudctInfo['battery']=='N')?"否":"是" ?>"></div></td>
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
		  				<td style="width: 25%;"><div class="content_lfet"><input type="text" class="form-control" value="<?=$proudctInfo['supplierlist'][$num]['name']?>"></div></td>
		  				<td style="width: 15%;"><div class="content_right"><?=TranslateHelper::t('采购价(CNY)') ?></div></td>
		  				<td style="width: 10%;"><div class="content_lfet"><input type="text" class="form-control" value="<?=$proudctInfo['supplierlist'][$num]['purchase_price']?>"></div></td>
		  				<td style="width: 15%;"><div class="content_right"><?=TranslateHelper::t('采购链接') ?></div></td>
		  				<td style="width: 20%;"><div class="content_lfet"><input type="text" class="form-control" value="<?=$proudctInfo['supplierlist'][$num]['purchase_link']?>"></div></td>
		  			</tr>
		  			<?php }}?>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品尺寸信息') ?></th></tr>
		  			<tr>
		  				<td style="width: 20%;"><div class="content_right"><?=TranslateHelper::t('重量(g)') ?></div></td>
		  				<td style="width: 30%;"><div class="content_lfet"><input type="text" class="form-control" id="Product_weight" name="Product[prod_weight]" value="<?=$proudctInfo['prod_weight']?>"></div><span style="color: red;">(默认重量为50g)</span></td>
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
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('商品属性') ?></th></tr>
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
		  		<table id="catalog-product-list-commission-table" class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('平台佣金比例') ?></th></tr>
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
                      					'.$Column_key.'
                      				</div>
                      			</td>
                      			<td style="width: 30%;">
                                    <div class="content_lfet"><input type="text" class="form-control" name="other_attr_key[]" value="'.$commission_key.'"/></div>
                      			</td>
                      			<td style="width: 20%;">
                      				<div class="content_right">'.$Column_value.'</div>
                      			</td>
                      			<td style="width: 30%;">
                      				<div class="content_lfet"><input type="text" class="form-control" name="commission_value[]" value="'.$commission_val.'" /></div>
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
		  					<textarea class="form-control" rows="5" name="Product[check_standard]"><?=$proudctInfo['check_standard'] ?></textarea>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
  			<li>
		  		<table class="table" style="width: 100%;margin-bottom:5px">
		  			<tr><th colspan="4"  style="vertical-align: middle;"><?=TranslateHelper::t('备注') ?></th></tr>
		  			<tr>
		  				<td colspan="4"  style="vertical-align: middle;">
		  					<textarea class="form-control" rows="5" name="Product[comment]" ><?= $proudctInfo['comment']?></textarea>
		  				</td>
		  			</tr>
		  		</table>
  			</li>
		</ul>
  	</FORM>
</div>


<script>
    $.initQtip();
//只读的情况下设置readonly 和  disabled
	$('#product-create-form input').prop('readonly','readonly');
	$('#product-create-form textarea').prop('disabled','disabled');
	$('#product-create-form button').prop('disabled','disabled');
	$('#product-create-form .cursor_pointer').css('display','none');

</script>
