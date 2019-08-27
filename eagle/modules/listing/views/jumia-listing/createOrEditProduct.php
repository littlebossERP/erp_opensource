<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing/jumiaListing.css?v='.eagle\modules\util\helpers\VersionHelper::$jumia_listing_version );

// kindeditor 可见即可得编辑器
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/themes/default/default.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/themes/simple/simple.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.css' );

$this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditor.js' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/lang/zh_CN.js' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.js');

if(!isset($type) || $type == "add")
	$this->title = "Jumia刊登 添加待发布产品";
else 
	$this->title = "Jumia刊登 修改待发布产品";

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/jumia_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$jumia_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
if(isset($type) && $type == "reference"){
	$this->registerJs("jumiaListing.initReference=true;", \yii\web\View::POS_READY);
}
$this->registerJs("jumiaListing.init()", \yii\web\View::POS_READY);
$this->registerJs("jumiaListing.existingImages=[];", \yii\web\View::POS_READY);

$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js?v=".eagle\modules\util\helpers\VersionHelper::$jumia_listing_version, ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);




$categoryHistory = array();
if(isset($productDataStr)){
    $productDataStr_array = json_decode($productDataStr,true);
}

?>
<style>
.bbar {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    display: block;
    height: 40px;
    background: white;
    line-height: 17px;
    overflow: hidden;
}
.show{display:block;}
.hide{display:none;}
.bgColor5 {background-color:#eee;}
.select_photo {border-color: red !important;}
.main_image_tips{
  width: 38px;
  height: 38px;
  position: absolute;
  left: 15px;
  top: 0px;
  background: url('/images/wish/main_image_tips.png') 0 0;
}
.categoryChooseOutDiv {
  width: 100%;
  margin-top: 10px;
  height: 330px;
  overflow: hidden;
  overflow-x: auto;}
.categoryChooseMiddleDiv {
  width: auto;}
.categoryChooseInDiv {
  border: 1px solid #ddd;
  height: 308px;
  overflow-y: auto;
  border-radius: 4px;
  width: 245px;
  margin-right: 5px;
}
#image-list.row{
	width:600px !important;
}	
.categoryDiv {
  width: 100%;
  cursor: pointer;
  height: 24px;
  line-height: 24px;
}


.browseNodeCategoryChooseOutDiv {
  width: 100%;
  margin-top: 10px;
  height: 330px;
  overflow: hidden;
  overflow-x: auto;}
.browseNodeCategoryChooseMiddleDiv {
  width: auto;}
.browseNodeCategoryChooseInDiv {
  border: 1px solid #ddd;
  height: 308px;
  overflow-y: auto;
  border-radius: 4px;
  width: 245px;
  margin-right: 5px;
}
.browseNodeCategoryDiv {
  width: 100%;
  cursor: pointer;
  height: 24px;
  line-height: 24px;
}
.categoryNames {
  display: inline-block;
  width: 200px;
  overflow: hidden;
  padding-left: 10px;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.glyphicon-text-size{
	position: absolute;
    top: 11px;
    right: 24px;
}
.glyphicon-chevron-right {
  position: relative;
  top: -8px;
  left: 10px;
}
.jumia-listing-create-or-edit .lzdProductTitle {
    width: 100%;
    position: relative;
}
.jumia-listing-create-or-edit .lzdProductTitle a.productTitTextSize {
    right: 8px;
}
.jumia-listing-create-or-edit .lzdProductTitle .jishu {
    position: absolute;
    top: 5px;
    right: -54px;
}


.jumia-listing-create-or-edit{}
.jumia-listing-create-or-edit table {
    width: 95%;
    font-size: 13px;
}
.jumia-listing-create-or-edit table .firstTd {
    width: 14%;
	font-weight: 600;
    text-align: right;
}
.jumia-listing-create-or-edit table .secondTd {
    width: 86%;
	padding-left:3px;
}


.left_pannel{
	height:auto;
	float:left;
	background:url(/images/ebay/listing/profile_menu_bg1.png) -23px 0 repeat-y;
	position:fixed;
}
.left_pannel_first{
	float:left;
	background:url(/images/ebay/listing/profile_menu_bg.png) 0 -6px no-repeat;
	margin-bottom:55px;
	height:12px;
	padding-left:12px;
}
.left_pannel_last{
	float:left;
	background:url(/images/ebay/listing/profile_menu_bg.png) 0 -6px no-repeat;
	margin-top:5px;
	height:12px;
	padding-left:12px;
}
.left_pannel>p{
	margin:50px 0;
	background:url(/images/ebay/listing/profile_menu_bg.png) 0 -41px no-repeat;
	padding-left:16px;
}
.left_pannel>p>a{
	color:#333;
	font-weight:bold;
	cursor:pointer;
}
.left_pannel p:hover a{
	color:blue;
	font-weight:bold;
	cursor:pointer;
}
.left_pannel p a:hover{
	color:rgb(165,202,246);
}
.fRed{
	color:red;
}
.var-table th,.var-table td{
	text-align:center;
}
.nodeRow{
	font-weight: bold;
	padding-bottom:10px;
}
.right_content h3 {
	margin:0px;
}
</style>
<?php 
	$menu = LazadaApiHelper::getLeftMenuArr('jumia');
    echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>''
	]);
?>
<div class="col2-layout jumia-listing jumia-listing-create-or-edit ">
		<div class="col-lg-11">
			<!--店铺信息-->
			<div id="store-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<div style="display: none;">
						<input type="hidden" id="productId" value="<?= isset($productId)?$productId:0?>">
						<input type="hidden" id="productCategoryIds" value='<?= isset($storeInfo['categories'])?json_encode($storeInfo['categories']):'';?>'>
						<input type="hidden" id="productBrowseNodeCategoryIds" value='<?= isset($productDataStr_array['base-info']['BrowseNodes'])?json_encode($productDataStr_array['base-info']['BrowseNodes']):'';?>'>
						<input type="hidden" id="skus" value='<?= isset($variantDataStr)?$variantDataStr:'';?>' name="skus">
		                <input type="hidden" id="productDataStr" value='<?= isset($productDataStr)?$productDataStr:'';?>' name="productDataStr">
					</div>
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>店铺信息</h3>
				</div>
				
				<div class="panel-body">
					<table>
					<tbody>
						<tr>
							<td class="firstTd">
								<span class="fRed">*</span>
								店铺:
							</td>
							<td class="secondTd">
								<?=Html::dropDownList('lazadaUid',@$jum_uid,$jumiaUsersDropdownList,['class'=>'form-control','onchange'=>"jumiaListing.selectShop();",'prompt'=>'---- 请选择分类 ----','style'=>"display:inline-block;width: 640px;",'id'=>'lazadaUid'])?>
	                            <input type="hidden" id="categoryId" name="categoryId" value="<?= @$storeInfo['primaryCategory']?>">
							</td>
						</tr>
						<tr>
						    <td class="firstTd"></td>
							<td class="secondTd">
							    <span id="select_shop_info" style="color:red;"></span>
							</td>
						</tr>
						<tr>
							<td class="firstTd">
								<span class="fRed">*</span>
								产品目录:
							</td>
							<td class="secondTd">
								<?=Html::dropDownList('categoryHistoryId',@$storeInfo['primaryCategory'],$categoryHistory,['class'=>'form-control','onchange'=>"jumiaListing.selectHistoryCategory(this);",'prompt'=>'---- 请选择产品目录 ----','style'=>"display:inline-block;width: 640px;",'id'=>'categoryHistoryId'])?>
	                            <button class="btn btn-primary categoryModalShow" type="button" data-names="treeSelectBtn" id="fullCid" data-id="" onclick="jumiaListing.selectCategory()">选择产品目录 </button>
							</td>
						</tr>
						<tr>
						    <td class="firstTd"></td>
							<td class="secondTd">
							    <span id="select_info" style="color:red;"></span>
							</td>
						</tr>
						<tr>
							<td class="firstTd"></td>
							<td id="category-info" class="secondTd category">
							    <span class="category">未选择分类</span>
							</td>
						</tr>
						<tr style="height: 13px;">
							<td class="firstTd"></td>
							<td id="category-info" class="secondTd">
							</td>
						</tr>
						<tr>
							<td class="firstTd" style="vertical-align: top;">Recommended Browse Nodes:</td>
							<td id="category-info" class="secondTd" style="vertical-align: top;">
							    <span class="nodecategory"></span>
							    <button class="btn btn-primary" type="button" data-names="nodesBtn" id="fullCid" data-id="" onclick="jumiaListing.browseNodeSelectCategory()">Add Browse Nodes</button>
							</td>
						</tr>
					</tbody>
				</table>
				</div>
			</div>
			<!--店铺信息end-->
			<!--基本信息-->
			<div id="base-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>产品信息<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table>
						<tbody>
							<tr cid="Name" attrtype="input" ismust="1" name="Product Name">
								<td class="firstTd">
									<span class="fRed">*</span>
									Product Name:
								</td>
								<td class="secondTd">
									<div class="lzdProductTitle">
										<input type="text" class="form-control" uid="translateIpt"
											id="" name="" value="" placeholder="">
										<a href="javascript:;"
											class="glyphicon glyphicon-text-size productTitTextSize"
											data-toggle="tooltip" data-placement="top" title=""
											data-original-title="把单词首字母转为大写（点击生效）"></a>
										<span class="jishu">
											<span class="unm">0</span>
											/255
										</span>
									</div>
								</td>
							</tr>
							<tr cid="Brand" attrtype="input" ismust="1" name="Brand">
								<td class="firstTd">
									<span class="fRed">*</span>
									Brand:
								</td>
								<td class="secondTd">
									<input class="labelIpt ui-autocomplete-input form-control" type="text"
										id="labelIpt" value="" autocomplete="off">
								</td>
							</tr>
							<tr cid="Model" attrtype="input" ismust="0">
								<td class="firstTd">Model:</td>
								<td class="secondTd">
									<input type="text" class="form-control" value="">
								</td>
							</tr>
							<tr cid="ColorFamily" attrtype="checkbox" ismust="0"
								style="display: none;">
								<td class="firstTd vAlignTop">主颜色:</td>
								<td class="secondTd"></td>
							</tr>
							<tr>
								<td colspan="2" class="secondTd">
									<div class="divModular ">
										<table cid="lzdAttrShow"></table>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--基本信息end-->
			<!--产品属性-->
			<div id="product-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>产品属性<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table>
						<tbody>
							<tr>
								<td class="secondTd">
									<div class="divModular ">
										<table cid="lzdProductAttr"></table>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--产品属性end-->
			<!--变参信息-->
			<div id="variant-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>变体信息</h3>
				</div>
				
				<div class="panel-body">
					<table>
						<tbody>
							<tr>
								<td class="firstTd vAlignTop" style="width:6%;"></td>
								<td class="secondTd">
									<div class="lzdSkuInfo">
										<table class="myj-table var-table"></table>
									</div>
									<button class="btn btn-primary " cid="addOneSku" onclick="jumiaListing.addOneSku()">添加一个变体</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--变参信息end-->
			<!--图片信息-->
			<div id="image-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>图片信息<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table>
						<tbody>
							<tr>
								<td class="vAlignTop">
								    <input name="Product[photo_primary]" id="Product_photo_primary" type="hidden" value="">
	                                <input name="Product[photo_others]" id="Product_photo_others" type="hidden" value="">
	                                <input name="Product[photo_primary_thumbnail]" id="Product_photo_primary_thumbnail" type="hidden" value="">
	                                <input name="Product[photo_others_thumbnail]" id="Product_photo_others_thumbnail" type="hidden" value="">
	                                <font>
	                               	<strong>普通类产品的图片，</strong> 长宽比1:1(最小500×500像素，最大2000×2000)
									或者宽长比4:5（竖着的长方形），最小680×850像素。
									同时， <strong> 图片必须纯白底！</strong>不可以有任何背景、水印，文字， logo，品牌
									名，及其他任何辅助性的文字或标志。
	                                </font>
	    							<div role="image-uploader-container">
	                                	<div class="btn-group" role="group">
	                                		<button type="button" class="btn btn-info" id="btn-uploader" >上传本地照片</button>
	                                		<button type="button" class="btn btn-info btn-group" id="btn-upload-from-lib" data-toggle="modal" data-target="#addImagesBox" ><?= TranslateHelper::t('通过 URL 添加图片'); ?></button>
	                                	</div>
	                                	<font style="color: red;font-size:16px;">点击图片选择主图</font>
	                                </div>
								</td>
							</tr>
							<tr><td><span id="upload_image_info" style="color:red"></span></td></tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--图片信息end-->
			<!--产品描述-->
			<div id="description-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>产品描述<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table>
						<tr cid="Description" attrtype="kindeditor" isMust="1" name="产品描述">
							<td class="firstTd vAlignTop"><span class="fRed">*</span> Product Description:</td>
							<td class="secondTd">
								<div class="mBottom10">
									<textarea id="Description" name="content" style="width:100%;height:100%;"></textarea>
								</div>
								<div id="DescriptionCacheDiv" style="display:none;">
								</div>
							</td>
						</tr>
						<tr cid="ShortDescription" attrtype="kindeditor" isMust="1" name="Highlights">
							<td class="firstTd vAlignTop"><span class="fRed">*</span>Highlights:</td>
							<td class="secondTd">
								<div class="mBottom10">
									<textarea id="ShortDescription" name="content" style="width:100%;height:100%;"></textarea>
								</div>
								<div id="ShortDescriptionCacheDiv" style="display:none;">
								</div>
							</td>
						</tr>
						<tr cid="PackageContent" attrtype="kindeditor" isMust="0" name="包装内容">
							<td class="firstTd vAlignTop">What's in the box:</td>
							<td class="secondTd">
								<div class="mBottom10">
									<textarea id="PackageContent" name="content" style="width:100%;height:100%;"></textarea>
								</div>
								<div id="PackageContentCacheDiv" style="display:none;">
								</div>
							</td>
						</tr>
						<table cid="descriptionAttr"></table>
					</table>
				</div>
			</div>
			<!--产品描述end-->
			<!--运输信息-->
			<div id="shipping-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>运输信息<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table class="zldPacking">
						<tbody>
							<tr cid="ProductMeasures" attrtype="input" ismust="0">
								<td class="firstTd">商品尺寸(如:12 x 3 x90):</td>
								<td class="secondTd">
									<input type="text" class="form-control" id="" name="" value=""
										placeholder="单位厘米，长 x 宽 x 高，例如:12 x 3 x90" onkeyup="jumiaListing.replaceSize(this);">
								</td>
							</tr>
							<tr cid="ProductWeight" attrtype="input" ismust="1" name="商品重量:">
								<td class="firstTd"><span class="fRed">*</span>商品重量:</td>
								<td class="secondTd">
									<div class="inebayLineDiv iptAndSpan">
										<input type="text" id="" name="" value=""
											onkeyup="jumiaListing.replaceFloat(this);" placeholder="单位千克，例如:12">
										<font style="font-size: 13px;">kg</font>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--运输信息end-->
			<!--制造商信息-->
			<div id="warranty-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>制造商信息<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table>
						<tbody>
							<tr cid="ManufacturerTxt" attrtype="kindeditor" ismust="0"
								style="display: none;">
								<td class="firstTd">From the Manufacturer:</td>
								<td class="secondTd">
									<div class="">
										<textarea id="ManufacturerTxt" name="ManufacturerTxt"
											style="width: 100%; height: 100%;"></textarea>
									</div>
								</td>
							</tr>
							<tr cid="CareLabel" attrtype="kindeditor" ismust="0"
								style="display: none;">
								<td class="firstTd vAlignTop">Care Label:</td>
								<td class="secondTd">
									<div class="">
										<textarea id="CareLabel" name="CareLabel"
											style="width: 100%; height: 100%;"></textarea>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--制造商信息end-->
			<!--底部btn组-->
			<div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;z-index: 11;">
				<button type="button" class="btn btn-sm btn-primary" onclick="jumiaListing.save(2);">保存并发布</button>
				<button type="button" class="btn btn-sm btn-primary" onclick="jumiaListing.save(1);">保存</button>
				<!-- <button type="button" class="btn btn-sm btn-primary" onclick="jumiaListing.listReferences();">引用产品</button> -->
			</div>
			<!--底部btn组end-->
		</div>
		
		<!-- 快捷导航 -->
		<div class="col-lg-1">
			<div class="left_pannel" id="floatnav">
				<div class="left_pannel_first"></div>
				<p onclick="goto('store-info')"><a>店铺信息</a></p>
				<p onclick="goto('base-info')"><a>产品信息</a></p>
				<p onclick="goto('variant-info')"><a>变体信息</a></p>
				<p onclick="goto('image-info')"><a>图片信息</a></p>
				<p onclick="goto('description-info')"><a>产品描述</a></p>
				<p onclick="goto('shipping-info')"><a>运输信息</a></p>
				<p onclick="goto('warranty-info')"><a>制造商信息</a></p>
				<div class="left_pannel_last"></div>
			</div>
		</div>
</div>
<!--类目浮窗-->
<div class="modal fade" id="categoryChoose" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" >
    <div class="modal-dialog" style="width:1100px;margin-top:60px;">
    	<div class="modal-content bs-example bs-example-tabs">
    		<!--modalHead-->
    		<div class="modal-header">
    			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
    			<h4 class="modal-title" id="myModalLabel">选择分类</h4>
    		 </div>
    		<!--conter-->
    		<div class="modal-body tab-content" style="padding-left:20px;padding-right:20px;">
    			<div style="height:34px;">
    				<span class="categoryChooseCrumbs">
        				<span data-level="1">未选择分类</span>
        				<span data-level="2"></span>
        				<span data-level="3"></span>
        				<span data-level="4"></span>
        				<span data-level="5"></span>
        				<span data-level="6"></span>
    				</span>
    				<!--button type="button" class="btn btn-primary pull-right">同步</button-->
    			</div>
    			<div class="categoryChooseOutDiv">
        			<div class="categoryChooseMiddleDiv">
        				<div class="pull-left categoryChooseInDiv" data-level="1"></div>
        				<div class="pull-left categoryChooseInDiv" data-level="2"></div>
        				<div class="pull-left categoryChooseInDiv" data-level="3"></div>
        				<div class="pull-left categoryChooseInDiv" data-level="4"></div>
        				<div class="pull-left categoryChooseInDiv" data-level="5"></div>
        				<div class="pull-left categoryChooseInDiv" data-level="6"></div>
        			</div>
    			</div>
    		</div>
    		<!--modalFoot-->
    		<div class="modal-footer">
    			<button type="button" class="btn btn-primary" onclick="jumiaListing.selectedClik()">选择</button>
    			<button type="button" class="btn btn-primary" data-dismiss="modal">关闭</button>
    		</div>
    	</div>
    </div>
</div>
<!--browse node类目浮窗-->
<div class="modal fade" id="browseNodeCategoryChoose" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" >
    <div class="modal-dialog" style="width:1100px;margin-top:60px;">
    	<div class="modal-content bs-example bs-example-tabs">
    		<!--modalHead-->
    		<div class="modal-header">
    			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
    			<h4 class="modal-title" id="myModalLabel">选择分类</h4>
    		 </div>
    		<!--conter-->
    		<div class="modal-body tab-content" style="padding-left:20px;padding-right:20px;">
    			<div style="height:34px;">
    				<span class="browseNodeCategoryChooseCrumbs">
        				<span data-level="1">未选择分类</span>
        				<span data-level="2"></span>
        				<span data-level="3"></span>
        				<span data-level="4"></span>
        				<span data-level="5"></span>
        				<span data-level="6"></span>
    				</span>
    				<!--button type="button" class="btn btn-primary pull-right">同步</button-->
    			</div>
    			<div class="browseNodeCategoryChooseOutDiv">
        			<div class="browseNodeCategoryChooseMiddleDiv">
        				<div class="pull-left browseNodeCategoryChooseInDiv" data-level="1"></div>
        				<div class="pull-left browseNodeCategoryChooseInDiv" data-level="2"></div>
        				<div class="pull-left browseNodeCategoryChooseInDiv" data-level="3"></div>
        				<div class="pull-left browseNodeCategoryChooseInDiv" data-level="4"></div>
        				<div class="pull-left browseNodeCategoryChooseInDiv" data-level="5"></div>
        				<div class="pull-left browseNodeCategoryChooseInDiv" data-level="6"></div>
        			</div>
    			</div>
    		</div>
    		<!--modalFoot-->
    		<div class="modal-footer">
    			<button type="button" class="btn btn-primary" onclick="jumiaListing.browseNodeSelectedClik()">选择</button>
    			<button type="button" class="btn btn-primary" data-dismiss="modal">关闭</button>
    		</div>
    	</div>
    </div>
</div>
<!-- lzd批量修改模态层 -->
<div class="modal lzdSkuBatchEdit" id="lzdSkuBatchEdit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content bs-example bs-example-tabs">
			<!--modalHead-->
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title" id="myModalLabel"></h4>
			 </div>
			<!--conter-->
			<div class="modal-body tab-content p10"></div>
			<!--modalFoot-->
			<div class="modal-footer" style="clear:both;">
				<button type="button" class="btn btn-primary lzdSkuBatchEdit" data-names="">确定</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
			</div>
		</div>
	</div>
</div>

<!-- 添加图片描述模态层 -->
<div class="modal" id="lazada-add-decs-pic" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog" style="width: 750px;">
		<div class="modal-content bs-example bs-example-tabs">
			<!--modalHead-->
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title" id="myModalLabel">添加图片描述</h4>
			 </div>
			<!--conter-->
			<div class="modal-body tab-content p10">
				<div class="row">
					<div class="col-lg-2"><p class="text-right">图片</p></div>
					<div class="col-lg-10">
				
					<div id="divimgurl">
					<div>
					<img src="" width="50px" height="50px"> <?php echo Html::textInput('imgurl[]','',array('size'=>80,'style'=>'width: 300px;','onblur'=>'javascript:jumiaListing.blurImageUrlInput(this)'));?>
					<?php echo Html::button('删除',array('onclick'=>'javascript:jumiaListing.delImageUrlInput(this)'))?>
					
					<!-- 	添加本地上传按钮 -->
					<?php echo Html::button('本地上传',['onclick'=>'javascript:jumiaListing.localUpOneImg(this)'])?>
					</div>
					</div><br>
					<?php echo Html::button('添加一张图片',array('onclick'=>'javascript:jumiaListing.addImageUrlInput();return false;'))?>
					<?=Html::input('file','product_photo_file','',['id'=>'img_tmp','class'=>'hidden'])?>

					</div>
				</div>
				<hr/>
				<div class="row">
					<div class="col-lg-2"><p class="text-right">图片大小</p></div>
					<div class="col-lg-10">
						宽<input type="text" value="" id="localPicWidth">
						<span class="fColor2">根据已设置的图片宽度，等比例缩放高度。</span>
					</div>
				</div>
				<hr/>
				<div class="row">
					<div class="col-lg-2"><p class="text-right">对齐方式</p></div>
					<div class="col-lg-10">
						<label>
							<input checked="" type="radio" value="left" name="localPicAlign">&nbsp;左对齐
						</label>
						<label>
							<input type="radio" value="center" name="localPicAlign">&nbsp;中对齐
						</label>
						<label>
							<input type="radio" value="right" name="localPicAlign">&nbsp;右对齐
						</label>				
					</div>
				</div>
			</div>
			<!--modalFoot-->
			<div class="modal-footer" style="clear:both;">
				<button type="button" class="btn btn-primary lzdSkuBatchEdit" onclick="javascript:jumiaListing.showDecriptionPic()" data-names="">确定</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
			</div>
		</div>
	</div>
</div>