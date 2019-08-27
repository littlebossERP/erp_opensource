<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/listing/fanben_capture.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
if (! empty($WishFanbenModel->parent_sku)){
	$this->registerJs("fanbenCapture.editMethod();" , \yii\web\View::POS_READY);
}else{
	$this->registerJs("fanbenCapture.addVariance();" , \yii\web\View::POS_READY);
}


$wishdata['site_id'] = empty($WishFanbenModel->site_id)?"":$WishFanbenModel->site_id;
$wishdata['wish_product_id'] = empty($WishFanbenModel->wish_product_id)?"":$WishFanbenModel->wish_product_id;
$wishdata['wish_parent_sku'] = empty($WishFanbenModel->parent_sku)?"":$WishFanbenModel->parent_sku;
$wishdata['wish_fanben_status'] = empty($WishFanbenModel->status )?"":$WishFanbenModel->status;
$wishdata['wish_fanben_status'] = empty($StatusMapping[$wishdata['wish_fanben_status']])?$wishdata['wish_fanben_status']:$StatusMapping[$wishdata['wish_fanben_status']];
$wishdata['wish_product_name'] =empty($WishFanbenModel->name)?"":$WishFanbenModel->name;

$wishdata['wish_product_tags'] =empty($WishFanbenModel->tags)?"":$WishFanbenModel->tags;
$wishdata['wish_product_upc'] =empty($WishFanbenModel->upc)?"":$WishFanbenModel->upc;
$wishdata['wish_product_brand'] =empty($WishFanbenModel->brand)?"":$WishFanbenModel->brand;
$wishdata['wish_product_landing_page_url'] =empty($WishFanbenModel->landing_page_url)?"":$WishFanbenModel->landing_page_url;
$wishdata['wish_product_description'] =empty($WishFanbenModel->description)?"":$WishFanbenModel->description;
$wishdata['main_image'] =empty($WishFanbenModel->main_image)?"":$WishFanbenModel->main_image;
$wishdata['extra_image_1'] =empty($WishFanbenModel->extra_image_1)?"":$WishFanbenModel->extra_image_1;
$wishdata['extra_image_2'] =empty($WishFanbenModel->extra_image_2)?"":$WishFanbenModel->extra_image_2;
$wishdata['extra_image_3'] =empty($WishFanbenModel->extra_image_3)?"":$WishFanbenModel->extra_image_3;

$wishdata['extra_image_4'] =empty($WishFanbenModel->extra_image_4)?"":$WishFanbenModel->extra_image_4;
$wishdata['extra_image_5'] =empty($WishFanbenModel->extra_image_5)?"":$WishFanbenModel->extra_image_5;
$wishdata['extra_image_6'] =empty($WishFanbenModel->extra_image_6)?"":$WishFanbenModel->extra_image_6;
$wishdata['extra_image_7'] =empty($WishFanbenModel->extra_image_7)?"":$WishFanbenModel->extra_image_7;
$wishdata['extra_image_8'] =empty($WishFanbenModel->extra_image_8)?"":$WishFanbenModel->extra_image_8;
$wishdata['extra_image_9'] =empty($WishFanbenModel->extra_image_9)?"":$WishFanbenModel->extra_image_9;
$wishdata['extra_image_10'] =empty($WishFanbenModel->extra_image_10)?"":$WishFanbenModel->extra_image_10;

$wishdata['msrp'] =empty($WishFanbenModel->msrp)?"0":$WishFanbenModel->msrp;
$wishdata['wish_shipping_time'] =empty($WishFanbenModel->shipping_time)?"":$WishFanbenModel->shipping_time;


$ImageList = [];

$row['thumbnail'] = $wishdata['main_image'];
$row['original'] = $wishdata['main_image'];
$ImageList[] = $row;

// 图片数据 生成 
for($i=1;$i<11;$i++){
	$row['thumbnail'] = $wishdata['extra_image_'.$i] ;
	$row['original'] = $wishdata['extra_image_'.$i];
	$ImageList[] = $row;
}

if (! empty($ImageList)){
	$this->registerJs("fanbenCapture.existingImages=".json_encode($ImageList).";" , \yii\web\View::POS_READY);
	$this->registerJs("fanbenCapture.initPhotosSetting();" , \yii\web\View::POS_READY);
}

//variance 数据 生成 
if (! empty($WishFanbenVarianceData)){
	$this->registerJs("fanbenCapture.existtingVarianceList=".json_encode($WishFanbenVarianceData).";" , \yii\web\View::POS_READY);
	$this->registerJs("fanbenCapture.fillVarianceData();" , \yii\web\View::POS_READY);
	
}


?>
<style>
<!--
-->
.cursor_pointer {
	cursor: pointer;
}

.select_photo {
	border-color: red !important;
}

#image-list{
	width: auto!important;
}
</style>

<div class="panel panel-default">
	<div class="panel-body">
		<form id="fanben_capture" name="fanben_capture" method="post"
			action="<?= Url::to(['/'.yii::$app->controller->module->id.'/'.yii::$app->controller->id.'/create-fan-ben'])?>">
			<input id="fanben_id" name="fanben_id" value="<?= (empty( $_GET['id'] ) ?"0":$_GET['id']) ?>" type="hidden"> <input
				id="variance" name="variance" value="" type="hidden" value="">

			<h3 class="page-header"><?= TranslateHelper::t('基本信息')?></h3>
			<!-- 第一行 -->
			<div class="form-group">
				<div class="row">
					<label for="site_id" class="col-sm-2 control-label"><?= TranslateHelper::t('Wish账号')?></label>
					<div class="col-sm-4">
						<select id="site_id" class="form-control" >
						<?php foreach($userWishAccountsComboBox as $row ):?>
						<option value="<?= $row['site_id']?>" <?=($wishdata['site_id']==$row['site_id'])?"selected":""?>><?= $row['store_name']?></option>
						<?php endforeach;?>
						</select>
						<!--  --> 
						<input type="hidden" name="site_id" value="<?= $wishdata['site_id'] ?>" />
							
					</div>

					<label for="wish_product_id" class="col-sm-2 control-label"><?= TranslateHelper::t('已刊登的Product Id')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_product_id"
							name="wish_product_id" value="<?=$wishdata['wish_product_id'] ?>" readonly />
					</div>
				</div>
			</div>

			<!-- 第二行 -->
			<div class="form-group">
				<div class="row">
					<label for="wish_parent_sku" class="col-sm-2 control-label"><?= TranslateHelper::t('Unique SKU(Parent)')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_parent_sku"
							name="parent_sku" value="<?= $wishdata['wish_parent_sku'] ?>" />
					</div>

					<label for="wish_fanben_status" class="col-sm-2 control-label"><?= TranslateHelper::t('范本刊登状态')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_fanben_status"
							name="status" value="<?=$wishdata['wish_fanben_status'] ?>" readonly/>
					</div>
				</div>

			</div>

			<!-- 第三行 -->
			<div class="form-group">
				<div class="row">
					<label for="wish_product_name" class="col-sm-2 control-label"><?= TranslateHelper::t('商品名称')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_product_name"
							name="name" value="<?= $wishdata['wish_product_name'] ?>" />
					</div>

					<label for="wish_product_tags" class="col-sm-2 control-label"><?= TranslateHelper::t('Tags(标签)')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_product_tags"
							name="tags" value="<?=$wishdata['wish_product_tags'] ?>" />
					</div>
				</div>
			</div>

			<!-- 第四行 -->

			<div class="form-group">
				<div class="row">
					<label for="wish_product_upc" class="col-sm-2 control-label"><?= TranslateHelper::t('UPC/EAN编码')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_product_upc"
							name="upc" value="<?= $wishdata['wish_product_upc'] ?>" />
					</div>

					<label for="wish_product_brand" class="col-sm-2 control-label"><?= TranslateHelper::t('品牌')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_product_brand"
							name="brand" value="<?=$wishdata['wish_product_brand'] ?>" />
					</div>
				</div>
			</div>
			<!-- 第五行 -->

			<div class="form-group">
				<div class="row">
					<label for="wish_product_landing_page_url"
						class="col-sm-2 control-label"><?= TranslateHelper::t('自营网站的商品链接')?></label>
					<div class="col-sm-10">
						<input type="text" class="form-control"
							id="wish_product_landing_page_url" name="landing_page_url"
							value="<?= $wishdata['wish_product_landing_page_url'] ?>" />
					</div>
				</div>
			</div>
			<!-- 第六行 -->

			<div class="form-group">
				<div class="row">
					<label for="wish_product_description"
						class="col-sm-2 control-label"><?= TranslateHelper::t('商品描述')?></label>
					<div class="col-sm-10">
						<textarea class="form-control" name="description"
							id="wish_product_description"><?=$wishdata['wish_product_description'] ?></textarea>
					</div>
				</div>
			</div>

			<!--  图片信息 -->



			<h3 class="page-header"><?= TranslateHelper::t('图片信息')?>
				<small class="text-danger"><?= TranslateHelper::t('红色边框为主图')?></small>
			</h3>

			<div role="image-uploader-container">
				<div class="btn-group" role="group">
					<button type="button" class="btn btn-info" id="btn-uploader"><?= TranslateHelper::t('上传本地图片'); ?></button>
					<button type="button" class="btn btn-info btn-group"
						id="btn-upload-from-lib" data-toggle="modal"
						data-target="#addImagesBox"><?= TranslateHelper::t('通过 URL 添加图片'); ?></button>
				</div>
			</div>

			<!-- 第七行 -->
			<div class="form-group">
				<div class="row">
					<!-- label for="main_image" class="col-sm-2 control-label"><?= TranslateHelper::t('主图')?></label> -->
					<div class="col-sm-10">
						<div class="input-group">
							<input type="hidden" name='main_image' id="main_image" class="form-control"
								value='<?= $wishdata['main_image'] ?>' /> <!--  <span
								class="input-group-btn">
								<button type="button" class="btn btn-default"
									data-toggle="collapse" data-target="#collapseMorePhotos"
									aria-expanded="false" aria-controls="collapseMorePhotos">
									<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
								<?= TranslateHelper::t('更多图片')?>
					    		</button>
							</span>
							 -->
						</div>


					</div>
				</div>
			</div>

			<div class="collapse" id="collapseMorePhotos">
			
				<?php  for($i=0 ;$i<5;$i++):
				$index = $i*2;
				?>
				<div class="form-group">
					<div class="row">

						<label for="extra_image_<?=($index+1)?>"
							class="col-sm-2 control-label"><?= TranslateHelper::t('额外图片').($index+1)?></label>
						<div class="col-sm-4">
							<input type="hidden" class="form-control"
								id="extra_image_<?=($index+1)?>"
								name="extra_image_<?=($index+1)?>"
								value="<?= $wishdata['extra_image_'.($index+1)] ?>" />
						</div>

						<label for="extra_image_<?=($index+2)?>"
							class="col-sm-2 control-label"><?= TranslateHelper::t('额外图片2')?></label>
						<div class="col-sm-4">
							<input type=""hidden"" class="form-control"
								id="extra_image_<?=($index+2)?>"
								name="extra_image_<?=($index+2)?>"
								value="<?=$wishdata['extra_image_'.($index+2)] ?>" />
						</div>
					</div>
				</div>	
						
				<?php endfor;?>
			</div>

			<h3 class="page-header"><?= TranslateHelper::t('Inventory and Shipping')?></h3>

			<!-- 第八行 -->
			<div class="form-group">
				<div class="row">
					<label for="msrp" class="col-sm-2 control-label"><?= TranslateHelper::t('原建议零售价(USD)')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="msrp" name="msrp"
							value="<?= $wishdata['msrp'] ?>" />
					</div>

					<label for="wish_shipping_time" class="col-sm-2 control-label"><?= TranslateHelper::t('递送时间')?></label>
					<div class="col-sm-4">
						<input type="text" class="form-control" id="wish_shipping_time"
							name="shipping_time"
							value="<?=$wishdata['wish_shipping_time'] ?>" />
					</div>
				</div>
			</div>
			<h3 class="page-header"><?= TranslateHelper::t('产品参数')?></h3>

			<div>
				<div class="btn-group" role="group" aria-label="...">
					<button id="fanben-wish-add-variance" type="button"
						class="btn btn-default">
						<span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
						<?=TranslateHelper::t('add variance')?>
					</button>
					<button id="submitFormData" type="button" class="btn btn-default">
						<span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span>
						<?=TranslateHelper::t('保存编辑')?>
					</button>
					<button id="submitFormDataAndPost" type="button"
						class="btn btn-default">
						<span class="glyphicon glyphicon-floppy-open" aria-hidden="true"></span>
						<?=TranslateHelper::t('保存并提交刊登')?>
					</button>
					<button id="cancelFormData" type="button" class="btn btn-default">
						<span class="glyphicon glyphicon-floppy-remove" aria-hidden="true"></span>
						<?=TranslateHelper::t('取消')?>
					</button>
				</div>
			</div>

			<table id="fanben_variance_table" class="table">
				<thead>
					<tr>
						<th><?=TranslateHelper::t('Unique SKU(Variance)')?></th>
						<th><?=TranslateHelper::t('英文颜色')?></th>
						<th><?=TranslateHelper::t('英文尺寸')?></th>
						<th><?=TranslateHelper::t('售价(USD)')?></th>
						<th><?=TranslateHelper::t('运费')?></th>
						<th><?=TranslateHelper::t('可销售数量')?></th>
						<th><?=TranslateHelper::t('操作')?></th>
					</tr>
				</thead>
				
			</table>
		</form>
	</div>
</div>
