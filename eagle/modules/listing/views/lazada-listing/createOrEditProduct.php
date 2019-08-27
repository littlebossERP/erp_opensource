<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;

$this->registerCssFile(\Yii::getAlias('@web') . '/css/listing/lazadaListing.css?v='.eagle\modules\util\helpers\VersionHelper::$lazada_listing_version);

// kindeditor 可见即可得编辑器
$this->registerCssFile(\Yii::getAlias('@web') . '/js/lib/kindeditor/themes/default/default.css');
$this->registerCssFile(\Yii::getAlias('@web') . '/js/lib/kindeditor/themes/simple/simple.css');
$this->registerCssFile(\Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.css');

$this->registerJsFile(\Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditor.js');
$this->registerJsFile(\Yii::getAlias('@web') . '/js/lib/kindeditor/lang/zh_CN.js');
$this->registerJsFile(\Yii::getAlias('@web') . '/js/lib/kindeditor/kindeditorEdit.js');
$this->registerJsFile('/js/project/uitest/ui-test.js', ['depends' =>
    ['eagle\assets\PublicAsset']
]);

if (!isset($type) || $type == "add")
    $this->title = "lazada刊登 添加待发布产品";
else
    $this->title = "lazada刊登 修改待发布产品";

$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/listing/lazada_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$lazada_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
if (isset($type) && $type == "reference") {
    $this->registerJs("lazadaListing.initReference=true;", \yii\web\View::POS_READY);
}
$this->registerJs("lazadaListing.init()", \yii\web\View::POS_READY);

$this->registerCssFile(\Yii::getAlias('@web') . "/css/batchImagesUploader.css");
$this->registerJsFile(\Yii::getAlias('@web') . "/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web') . "/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);


$categoryHistory = array();
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

.show {
    display: block;
}

.hide {
    display: none;
}

.select_photo {
    border-color: red !important;
}

.main_image_tips {
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
    overflow-x: auto;
}

.categoryChooseMiddleDiv {
    width: auto;
}

#image-list.row {
    width: 600px !important;
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
    overflow-x: auto;
}

.browseNodeCategoryChooseMiddleDiv {
    width: auto;
}

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

.glyphicon-text-size {
    position: absolute;
    top: 11px;
    right: 24px;
}

.glyphicon-chevron-right {
    position: relative;
    top: -8px;
    left: 10px;
}

.lazada-listing-create-or-edit .lzdProductTitle {
    width: 100%;
    position: relative;
}

.lazada-listing-create-or-edit .lzdProductTitle a.productTitTextSize {
    right: 8px;
}

.lazada-listing-create-or-edit .lzdProductTitle .jishu {
    position: absolute;
    top: 5px;
    right: -54px;
}

.lazada-listing-create-or-edit {
}

.lazada-listing-create-or-edit table {
    width: 95%;
    font-size: 13px;
}

.lazada-listing-create-or-edit table .firstTd {
    width: 14%;
    font-weight: 600;
    text-align: right;
}

.lazada-listing-create-or-edit table .secondTd {
    width: 86%;
    padding: 3px 5px;
}

.left_pannel {
    height: auto;
    float: left;
    background: url(/images/ebay/listing/profile_menu_bg1.png) -23px 0 repeat-y;
    position: fixed;
}

.left_pannel_first {
    float: left;
    background: url(/images/ebay/listing/profile_menu_bg.png) 0 -6px no-repeat;
    margin-bottom: 55px;
    height: 12px;
    padding-left: 12px;
}

.left_pannel_last {
    float: left;
    background: url(/images/ebay/listing/profile_menu_bg.png) 0 -6px no-repeat;
    margin-top: 5px;
    height: 12px;
    padding-left: 12px;
}

.left_pannel > p {
    margin: 50px 0;
    background: url(/images/ebay/listing/profile_menu_bg.png) 0 -41px no-repeat;
    padding-left: 16px;
}

.left_pannel > p > a {
    color: #333;
    font-weight: bold;
    cursor: pointer;
}

.left_pannel p:hover a {
    color: blue;
    font-weight: bold;
    cursor: pointer;
}

.left_pannel p a:hover {
    color: rgb(165, 202, 246);
}

.fRed {
    color: red;
}

.var-table th, .var-table td {
    text-align: center;
}

.nodeRow {
    font-weight: bold;
    padding-bottom: 10px;
}

.right_content h3 {
    margin: 0px;
}

.save-size {
    width: 115px;
    height: 32px;
}

.row {
    margin: 5px 0px;
    background-color: #F5F5F5;
}
.input-area {padding: 10px;}
.vAlignTop{vertical-align: top;}
.iv-alert.alert-remind>p{margin: 5px;}
</style>
<?php
$menu = LazadaApiHelper::getLeftMenuArr('lazada');
echo $this->render('//layouts/new/left_menu_2', [
    'menu' => $menu,
    'active' => ''
]);
?>
<div class="col2-layout lazada-listing lazada-listing-create-or-edit ">
    <div class="col-lg-11">
        <!--店铺信息-->
        <div id="store-info" class="panel panel-default">
            <div class="panel-heading">
                <div style="display: none;">
                    <input type="hidden" id="productId" value="<?= isset($productId) ? $productId : 0 ?>">
                    <input type="hidden" id="productCategoryIds"
                           value='<?= isset($storeInfo['categories']) ? json_encode($storeInfo['categories']) : ''; ?>'>
                    <input type="hidden" id="skus" value='<?= isset($variantDataStr) ? $variantDataStr : ''; ?>'
                           name="skus">
                    <input type="hidden" id="productDataStr"
                           value='<?= isset($productDataStr) ? $productDataStr : ''; ?>' name="productDataStr">
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
                            <?= Html::dropDownList('lazadaUid', @$lzd_uid, $lazadaUsersDropdownList, ['class' => 'form-control', 'onchange' => "lazadaListing.selectShop();", 'prompt' => '---- 请选择分类 ----', 'style' => "display:inline-block;width: 640px;", 'id' => 'lazadaUid']) ?>
                            <input type="hidden" id="categoryId" name="categoryId"
                                   value="<?= @$storeInfo['primaryCategory'] ?>">
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
                            <?= Html::dropDownList('categoryHistoryId', @$storeInfo['primaryCategory'], $categoryHistory, ['class' => 'form-control', 'onchange' => "lazadaListing.selectHistoryCategory(this);", 'prompt' => '---- 请选择产品目录 ----', 'style' => "display:inline-block;width: 640px;", 'id' => 'categoryHistoryId']) ?>
                            <button class="btn btn-warning btn-sm categoryModalShow" type="button"
                                    data-names="treeSelectBtn" id="fullCid" data-id=""
                                    onclick="lazadaListing.selectCategory()">选择产品目录
                            </button>
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
                    </tbody>
                </table>
            </div>
        </div>
        <!--店铺信息end-->
        <!--基本信息-->
        <div id="base-info" class="panel panel-default search-info">
            <div class="panel-heading">
                <input type="hidden" id="productId" value="">
                <h3 class="panel-title"><i class="ico-file4 mr5"></i>产品信息<span
                        class="glyphicon glyphicon-chevron-up"></span></h3>
            </div>

            <div class="panel-body show">
                <table>
                    <tbody>
                    <tr cid="name" attrtype="input" ismust="1" name="name">
                        <td class="firstTd">
                            <span class="fRed">*</span>
                            Name:
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
                    <!-- 
                    <tr cid="name_ms" attrtype="input" ismust="1" name="name">
                        <td class="firstTd">
                            <span class="fRed">*</span>
                            Name(Malay):
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
                     -->
                    <tr cid="brand" attrtype="input" ismust="1" name="brand">
                        <td class="firstTd">
                            <span class="fRed">*</span>
                            Brand:
                        </td>
                        <td class="secondTd">
                            <input class="labelIpt ui-autocomplete-input form-control" type="text"
                                   id="labelIpt" value="OEM" autocomplete="off">
                        </td>
                    </tr>
                    <tr cid="model" attrtype="input" ismust="1">
                        <td class="firstTd">
                            <span class="fRed">*</span>
                            Model:
                        </td>
                        <td class="secondTd">
                            <input type="text" class="form-control" value="">
                        </td>
                    </tr>
                    
                    </tbody>
                </table>
            </div>
        </div>
        <!--基本信息end-->
        <!--产品属性-->
        <div id="product-info" class="panel panel-default search-info">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="ico-file4 mr5"></i>产品属性<span
                        class="glyphicon glyphicon-chevron-up"></span></h3>
            </div>

            <div class="panel-body show">
                <table>
                    <tbody>
                    <tr>
                        <td class="secondTd">
                            <div class="divModular ">
                                <table class="left-table-location" cid="lzdProductAttr">
                                    
                                </table>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!--产品属性end-->
        <!--产品描述-->
        <div id="description-info" class="panel panel-default search-info">
            <div class="panel-heading">
                <input type="hidden" id="productId" value="">
                <h3 class="panel-title"><i class="ico-file4 mr5"></i>产品描述<span
                        class="glyphicon glyphicon-chevron-up"></span></h3>
            </div>

            <div class="panel-body show">
                <table cid="descriptionAttr">
            
                </table>
            </div>
        </div>
        <!--产品描述end-->
        <!--变参信息-->
        <div id="variant-info" class="panel panel-default search-info">
            <div class="panel-heading">
                <h3 class="panel-title"><i class=""></i>变体信息</h3>
            </div>

            <div class="panel-body">
                <table>
                    <tbody>
                        <tr>
                            <td class="secondTd">
                                <div class="divModular ">
                                    <table class="left-table-location" cid="lzdVariantAttr">
                                        
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="secondTd">
                                <div class="panel-group" cid="lzdVariant"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!--变参信息end-->
        
        <!--底部btn组-->
        <div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;z-index: 11;">
            <button type="button" class="btn btn-success btn-sm save-size" onclick="lazadaListing.save(2);">保存并发布
            </button>
            <button type="button" class="btn btn-success btn-sm save-size" onclick="lazadaListing.save(1);">保存</button>
            <!-- <button type="button" class="btn btn-sm btn-primary" onclick="lazadaListing.listReferences();">引用产品</button> -->
        </div>
        <!--底部btn组end-->
    </div>

    <!-- 快捷导航 -->
    <div class="col-lg-1">
        <div class="left_pannel" id="floatnav">
            <div class="left_pannel_first"></div>
            <p onclick="goto('store-info')"><a>店铺信息</a></p>
            <p onclick="goto('base-info')"><a>产品信息</a></p>
            <p onclick="goto('description-info')"><a>产品描述</a></p>
            <p onclick="goto('variant-info')"><a>变体信息</a></p>
            <div class="left_pannel_last"></div>
        </div>
    </div>
</div>
<!--类目浮窗-->
<div class="modal fade" id="categoryChoose" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width:1100px;margin-top:60px;">
        <div class="modal-content bs-example bs-example-tabs modal-style">
            <!--modalHead-->
            <div class="modal-header header-style">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                        class="sr-only">Close</span></button>
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
                <button type="button" class="btn btn-success" onclick="lazadaListing.selectedClik()">选择</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- lzd批量修改模态层 -->
<div class="modal lzdSkuBatchEdit" id="lzdSkuBatchEdit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="false" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content bs-example bs-example-tabs modal-style">
            <!--modalHead-->
            <div class="modal-header header-style">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                        class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <!--conter-->
            <div class="modal-body tab-content p10"></div>
            <!--modalFoot-->
            <div class="modal-footer" style="clear:both;">
                <button type="button" class="btn btn-success lzdSkuBatchEdit" data-names="">确定</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>

<!-- 添加图片描述模态层 -->
<div class="modal" id="lazada-add-decs-pic" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="false" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" style="width: 750px;">
        <div class="modal-content bs-example bs-example-tabs">
            <!--modalHead-->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                        class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">添加图片描述</h4>
            </div>
            <!--conter-->
            <div class="modal-body tab-content p10">
                <div class="row">
                    <div class="col-lg-2"><p class="text-right">图片</p></div>
                    <div class="col-lg-10">

                        <div id="divimgurl">
                            <div>
                                <img src="" width="50px"
                                     height="50px"> <?php echo Html::textInput('imgurl[]', '', array('size' => 80, 'style' => 'width: 300px;', 'onblur' => 'javascript:lazadaListing.blurImageUrlInput(this)')); ?>
                                <?php echo Html::button('删除', array('onclick' => 'javascript:lazadaListing.delImageUrlInput(this)')) ?>

                                <!-- 	添加本地上传按钮 -->
                                <?php echo Html::button('本地上传', ['onclick' => 'javascript:lazadaListing.localUpOneImg(this)']) ?>
                            </div>
                        </div>
                        <br>
                        <?php echo Html::button('添加一张图片', array('onclick' => 'javascript:lazadaListing.addImageUrlInput();return false;')) ?>
                        <?= Html::input('file', 'product_photo_file', '', ['id' => 'img_tmp', 'class' => 'hidden']) ?>

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
                <button type="button" class="btn btn-primary lzdSkuBatchEdit"
                        onclick="javascript:lazadaListing.showDecriptionPic()" data-names="">确定
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>