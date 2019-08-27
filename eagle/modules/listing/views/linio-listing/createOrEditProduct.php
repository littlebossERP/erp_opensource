<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing/linioListing.css?v='.eagle\modules\util\helpers\VersionHelper::$linio_listing_version );

// kindeditor 可见即可得编辑器
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/themes/default/default.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/themes/simple/simple.css' );
$this->registerCssFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.css' );

$this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditor.js' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/lang/zh_CN.js' );
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.js');
$this->registerJsFile('/js/project/uitest/ui-test.js',['depends' =>
    ['eagle\assets\PublicAsset']
]);

if(!isset($type) || $type == "add")
	$this->title = "Linio刊登 添加待发布产品";
else 
	$this->title = "Linio刊登 修改待发布产品";

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/linio_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$linio_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
if(isset($type) && $type == "reference"){
	$this->registerJs("linioListing.initReference=true;", \yii\web\View::POS_READY);
}
$this->registerJs("linioListing.init()", \yii\web\View::POS_READY);
$this->registerJs("linioListing.existingImages=[];", \yii\web\View::POS_READY);

$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);




$categoryHistory = array();
$images_message = array();
//图片处理
if(!empty($imageInfo)){
    if(isset($imageInfo['Product_photo_primary'])){
        $images_message[] = [
            'primary'=>true,
            'src'=>$imageInfo['Product_photo_primary'],
        ];
    }
    
    if(isset($imageInfo['Product_photo_others'])){
        foreach ($imageInfo['Product_photo_others'] as $detail_other_photo){
            $images_message[] = [
                'src'=>$detail_other_photo,
            ];
        }
    }
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
.linio-listing-create-or-edit .lzdProductTitle {
    width: 100%;
    position: relative;
}
.linio-listing-create-or-edit .lzdProductTitle a.productTitTextSize {
    right: 8px;
}
.linio-listing-create-or-edit .lzdProductTitle .jishu {
    position: absolute;
    top: 5px;
    right: -54px;
}


.linio-listing-create-or-edit{}
.linio-listing-create-or-edit table {
    width: 95%;
    font-size: 13px;
}
.linio-listing-create-or-edit table .firstTd {
    width: 14%;
	font-weight: 600;
    text-align: right;
}
.linio-listing-create-or-edit table .secondTd {
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
.right_content h3 {
	margin:0px;
}
.save-size{
	width:115px;
	height:32px;
}
.row{
	margin-top: 5px;
	margin-left: 0px;
	background-color:#F5F5F5;
}
.input-area{
	padding:10px;
}
</style>
<?php 
	$menu = LazadaApiHelper::getLeftMenuArr('linio');
    echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>''
	]);
?>
<div class="col2-layout linio-listing linio-listing-create-or-edit ">
		<div class="col-lg-11">
			<!--店铺信息-->
			<div id="store-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<div style="display: none;">
						<input type="hidden" id="productId" value="<?= isset($productId)?$productId:0?>">
						<input type="hidden" id="productCategoryIds" value='<?= isset($storeInfo['categories'])?json_encode($storeInfo['categories']):'';?>'>
						<input type="hidden" id="skus" value='<?= isset($variantDataStr)?$variantDataStr:'';?>' name="skus">
		                <input type="hidden" id="productDataStr" value='<?= isset($productDataStr)?$productDataStr:'';?>' name="productDataStr">
	                    <input type="hidden" id="copyType" name="copyType" value="singleCopy">				
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
								<?=Html::dropDownList('lazadaUid',@$lin_uid,$linioUsersDropdownList,['class'=>'form-control','onchange'=>"linioListing.selectShop();",'prompt'=>'---- 请选择分类 ----','style'=>"display:inline-block;width: 720px;",'id'=>'lazadaUid'])?>
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
								<?=Html::dropDownList('categoryHistoryId',@$storeInfo['primaryCategory'],$categoryHistory,['class'=>'form-control','onchange'=>"linioListing.selectHistoryCategory(this);",'prompt'=>'---- 请选择产品目录 ----','style'=>"display:inline-block;width: 720px;",'id'=>'categoryHistoryId'])?>
	                            <button class="btn btn-primary categoryModalShow" type="button" data-names="treeSelectBtn" id="fullCid" data-id="" onclick="linioListing.selectCategory()">选择产品目录 </button>
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
							<tr cid="TaxClass" attrtype="select" ismust="1" name="Taxes">
								<td class="firstTd">
									<span class="fRed">*</span>
									Taxes:
								</td>
								<td class="secondTd">
									<select  class="form-control">
										<option>请选择</option>
									</select>
								</td>
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
			<div id="variant-info" class="panel panel-default form-horizontal form-bordered min search-info" data-id="main-variant">
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
									<button class="btn btn-primary " cid="addOneSku" onclick="linioListing.addOneSku()">添加一个变体</button>
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
									或者宽长比2:3（竖着的长方形），最小600×900像素。
									同时， <strong> 图片必须纯白底！</strong>不可以有任何背景、水印，文字， logo，品牌
									名，及其他任何辅助性的文字或标志。
	                                </font>
                    				<div class="row">
                        				<div class="input-control input-area">
                        					<?= $this->renderFile(\Yii::getAlias('@modules').'/util/views/ui/img-list.php',[
                        						'name'=>'extra_images',
                        						'max'=>8,
                        						'primaryKey'=>'main_image',
                        						'btn'=>[
                        							'shanchu','link'
                        						],
                        					    'checkbox'=>false,
                        					    'images'=>$images_message,
//                         						'images'=>[
//                         							[
//                         								'src'=>'http://littleboss-image.s3.amazonaws.com/1/20151217/20151217175935-84f3ccd.jpg'
//                         							],
//                         							[
//                         								'primary'=>true,
//                         								'src'=>'https://contestimg.wish.com/api/webimage/57281b26d3541460929b2c4e-original.jpg?cache_buster=bea8bab454db0a9ca88ab678e926119a',
//                         								'title'=>'喜洋洋'
//                         							],
//                         							[
//                         								'src'=>'https://contestimg.wish.com/api/webimage/5728668a47f013606c5ccf50-original.jpg?cache_buster=9a1e237ffa6b0908e54979225817f8c2'
//                         							]
//                         						]
                        					]) ?>
                        				</div>
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
							<td class="firstTd vAlignTop"><span class="fRed">*</span>产品描述:</td>
							<td class="secondTd">
								<div class="mBottom10">
									<textarea id="Description" name="content" style="width:100%;height:100%;"></textarea>
								</div>
								<div id="DescriptionCacheDiv" style="display:none;">
								</div>
							</td>
						</tr>
						<tr cid="ShortDescription" attrtype="kindeditor" isMust="0">
							<td class="firstTd vAlignTop">Short Description:</td>
							<td class="secondTd">
								<div class="mBottom10">
									<textarea id="ShortDescription" name="content" style="width:100%;height:100%;"></textarea>
								</div>
								<div id="ShortDescriptionCacheDiv" style="display:none;">
								</div>
							</td>
						</tr>
						<tr cid="PackageContent" attrtype="kindeditor" isMust="0" name="包装内容">
							<td class="firstTd vAlignTop">包装内容:</td>
							<td class="secondTd">
								<div class="mBottom10">
									<textarea id="PackageContent" name="content" style="width:100%;height:100%;"></textarea>
								</div>
								<div id="PackageContentCacheDiv" style="display:none;">
								</div>
							</td>
						</tr>
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
							<tr cid="shippingTime" style="display: none;">
								<td class="firstTd"><span class="fRed">*</span>运输天数:</td>
								<td class="secondTd">
									<input cid="MinDeliveryTime" ismust="1" type="text" id=""
										name="最小天数" value="" placeholder="最小天数"
										onkeyup="linioListing.replaceNumber(this);">
									&nbsp;&nbsp;-&nbsp;&nbsp;
									<input cid="MaxDeliveryTime" ismust="1" type="text" id=""
										name="最大天数" value="" placeholder="最大天数"
										onkeyup="linioListing.replaceNumber(this);">
								</td>
							</tr>
							<tr cid="ProductMeasures" attrtype="input" ismust="0">
								<td class="firstTd">商品尺寸:</td>
								<td class="secondTd">
									<input type="text" class="form-control" id="" name="" value=""
										placeholder="单位厘米，长 x 宽 x 高，例如:12 x 3 x90" onkeyup="linioListing.replaceSize(this);">
								</td>
							</tr>
							<tr cid="ProductWeight" attrtype="input" ismust="0">
								<td class="firstTd">商品重量:</td>
								<td class="secondTd">
									<div class="inebayLineDiv iptAndSpan">
										<input type="text" id="" name="" value=""
											onkeyup="linioListing.replaceFloat(this);" placeholder="单位千克，例如:12">
										<font style="font-size: 13px;">kg</font>
									</div>
								</td>
							</tr>
							<tr cid="packingSize">
								<td class="firstTd">
									<span class="fRed">*</span>
									包装后的尺寸:
								</td>
								<td class="secondTd">
									<input cid="PackageLength" name="包装尺寸（长）" ismust="1" type="text"
										id="" value="" placeholder="长(cm)" onkeyup="linioListing.replaceFloat(this);">
									&nbsp;&nbsp;X&nbsp;&nbsp;
									<input cid="PackageWidth" name="包装尺寸（宽）" ismust="1" type="text"
										id="" value="" placeholder="宽(cm)" onkeyup="linioListing.replaceFloat(this);">
									&nbsp;&nbsp;X&nbsp;&nbsp;
									<input cid="PackageHeight" name="包装尺寸（高）" ismust="1" type="text"
										id="" value="" placeholder="高(cm)" onkeyup="linioListing.replaceFloat(this);">
								</td>
							</tr>
							<tr cid="PackageWeight" attrtype="input" ismust="1" name="包装后的重量" onkeyup="linioListing.replaceFloat(this);">
								<td class="firstTd">
									<span class="fRed">*</span>
									包装后的重量:
								</td>
								<td class="secondTd">
									<div class="inebayLineDiv iptAndSpan">
										<input type="text" id="" name="" value=""
											onkeyup="linioListing.replaceFloat(this);" placeholder="单位千克，例如:0.8">
										<font style="font-size: 13px;">kg</font>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
					<table cid="transportAttr"></table>
				</div>
			</div>
			<!--运输信息end-->
			<!--担保信息-->
			<div id="warranty-info" class="panel panel-default form-horizontal form-bordered min search-info">
				<div class="panel-heading">
					<input type="hidden" id="productId" value="">
					<h3 class="panel-title"><i class="ico-file4 mr5"></i>担保信息<span class="glyphicon glyphicon-chevron-up"></span></h3>
				</div>
				
				<div class="panel-body show">
					<table>
						<tbody>
							<tr cid="SupplierWarrantyMonths" attrtype="input" ismust="0"
								style="display: none;">
								<td class="firstTd">质保时间:</td>
								<td class="secondTd">
									<input type="text" id="" name="" value=""
											onkeyup="linioListing.replaceNumber(this);" placeholder="单位月，例如:1">
										<font style="font-size: 13px;">月</font>
								</td>
							</tr>
							<tr cid="ProductWarranty" attrtype="kindeditor" ismust="0"
								style="display: none;">
								<td class="firstTd vAlignTop">质保说明:</td>
								<td class="secondTd">
									<div class="">
										<textarea id="ProductWarranty" name="content"
											style="width: 100%; height: 100%;"></textarea>
									</div>
									<div id="ProductWarrantyCacheDiv" style="display: none;"></div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!--担保信息end-->
			<!--底部btn组-->
			<div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;z-index: 11;">
				<button type="button" class="btn btn-sm btn-primary save-size" onclick="linioListing.save(2);">保存并发布</button>
				<button type="button" class="btn btn-sm btn-primary save-size" onclick="linioListing.save(1);">保存</button>
				<!-- <button type="button" class="btn btn-sm btn-primary" onclick="linioListing.listReferences();">引用产品</button> -->
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
				<p onclick="goto('warranty-info')"><a>担保信息</a></p>
				<div class="left_pannel_last"></div>
			</div>
		</div>
</div>
<!--类目浮窗-->
<div data-name="linio_float">
    <div class="modal fade" id="categoryChoose" tabindex="-1" data-name="linio_float_class" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" >
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
        			<button type="button" class="btn btn-primary" onclick="linioListing.selectedClik(this)">选择</button>
        			<button type="button" class="btn btn-primary" data-dismiss="modal">关闭</button>
        		</div>
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
				<button type="button" class="btn btn-primary lzdSkuBatchEdit" data-names="" data-id="">确定</button>
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
					<img src="" width="50px" height="50px"> <?php echo Html::textInput('imgurl[]','',array('size'=>80,'style'=>'width: 300px;','onblur'=>'javascript:linioListing.blurImageUrlInput(this)'));?>
					<?php echo Html::button('删除',array('onclick'=>'javascript:linioListing.delImageUrlInput(this)'))?>
					
					<!-- 	添加本地上传按钮 -->
					<?php echo Html::button('本地上传',['onclick'=>'javascript:linioListing.localUpOneImg(this)'])?>
					</div>
					</div><br>
					<?php echo Html::button('添加一张图片',array('onclick'=>'javascript:linioListing.addImageUrlInput();return false;'))?>
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
				<button type="button" class="btn btn-primary lzdSkuBatchEdit" onclick="javascript:linioListing.showDecriptionPic()" data-names="">确定</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
			</div>
		</div>
	</div>
</div>