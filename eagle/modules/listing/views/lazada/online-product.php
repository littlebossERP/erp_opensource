<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\helpers\HtmlHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\listing\helpers\LazadaProductStatus;

$this->registerCssFile(\Yii::getAlias('@web') . '/css/listing/lazadaListing.css?v='.eagle\modules\util\helpers\VersionHelper::$lazada_listing_version);
$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/listing/lazada_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$lazada_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("lazadaListing.list_init()", \yii\web\View::POS_READY);
// $station=[
//   'MY'=>'MY', 
//   'TL'=>'TL'
// ];
// $shop_name=[
//     '1'=>'123@qq.com',
//     '2'=>'321@qq.com'
// ];
$condition = [
    'title' => '标题',
    'sku' => 'Sku'
];
$edit_type = [
    'price' => '价格',
    'sale_message' => '促销信息',
    'quantity' => '库存'
];

$this->title = TranslateHelper::t("lazada在线商品列表");
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
?>
<style>
.pd-title {
    position: relative;
    padding-bottom: 36px!important;
    word-wrap: break-word;
    word-break: break-all;
}
.pd-title .store-tags {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 5px;
    opacity: 0.5;
}
td .popover{
    max-width: inherit;
    max-height: inherit;
}
    
</style>
<?php
$menu = LazadaApiHelper::getLeftMenuArr('lazada');
echo $this->render('//layouts/new/left_menu_2', [
    'menu' => $menu,
    'active' => $activeMenu
]);
?>

<div class="col2-layout lazada-listing lazada-listing-online-product">
    <div class="search">
        <form action="/listing/lazada/online-product" method="get" id="online_search" name="online_search">
            <?= Html::dropDownList('shop_name', @$_REQUEST['shop_name'], $shop_name, ['onchange' => "search_submit($(this).val());", 'class' => 'eagle-form-control', 'id' => '', 'style' => 'padding-top:3px;', 'prompt' => '全部lazada店铺']) ?>
            <?= Html::dropDownList('condition', @$_REQUEST['condition'], $condition, ['onchange' => "", 'class' => 'eagle-form-control', 'id' => '', 'style' => 'padding-top:3px;']) ?>
            <input type="text" class="eagle-form-control" id="condition_search" name="condition_search"
                   value="<?php echo !empty($_REQUEST['condition_search']) ? htmlentities($_REQUEST['condition_search']) : null ?>">
            <button type="submit" id="search" class="iv-btn btn-search serach-button"><span
                    class="iconfont icon-sousuo"></span></button>
            <br/>
            <button class="btn btn-info operate-button" type="button" onclick="lazadaListing.checkBox()"><span
                    class="iconfont icon-xiugai"></span> 批量修改
            </button>
            <button class="btn btn-info operate-button" type="button" data-toggle="modal" data-target="#Sync_product"
                    onclick="lazadaListing.reset()"><span class="iconfont icon-tongbuzhong1"></span> 同步商品
            </button>
            <button class="btn btn-info operate-button hidden" type="button" onclick="lazadaListing.batchPutOff()"><span
                    class="iconfont icon-xiajia"></span> 批量下架
            </button>
            <?= Html::hiddenInput('sub_status', @$_REQUEST['sub_status'], ['id' => 'sub_status']) ?>
        </form>
    </div>

    <div>
        <table class="table table-bordered table-condensed">
            <thead>
            <tr>
                <th style="width: 50px">
                    <input type="checkbox" id="chk_all">
                </th>
                <th style="width: 100px">图片</th>
                <th style="width: 200px">标题</th>
                <th style="width: 125px">sku</th>
                <th style="width: 85px">价格</th>
                <th style="width: 85px">促销价</th>
                <th style="width: 85px">库存</th>
                <th style="width: 115px">
                <?php
                unset($subStatus['deleted']);
                ?>
                <?= Html::dropDownList('sub_status', @$_REQUEST['sub_status'], $subStatus, ['prompt' => '产品QC状态', 'style' => 'width:100px', 'onchange' => "dosearch('sub_status',$(this).val());"]) ?>
                </th>
                <th style="width: 125px">修改状态</th>
                <th style="width: 50px">操作</th>
            </tr>
            </thead>
            <?php if (!empty($listings)): ?>
                <tbody class="lzd_body">
                <?php foreach ($listings as $groupKey => $items): ?>
                    <?php foreach ($items as $index=>$listing): ?>
                        <tr class="striped-row" data-groupid="<?= md5($listing['lazada_uid'].$listing['group_id']) ?>" style="">
                            <?php if($index==0):?>
                            <td rowspan="<?=count($items) ?>"><input type="checkbox" name="groupCheck"><input type="hidden" name="group_id" val="<?= $listing['group_id'] ?>"></td>
                            <td rowspan="<?=count($items) ?>"><img class="prod_img" src="<?= $listing['MainImage']; ?>" style="max-width:60px;max-height:60px;" data-toggle="popover" data-content="<img width='350px' src='<?= str_replace("-catalog", "", $listing['MainImage']);?>'>" data-html="true" data-trigger="hover"></td>
                            <td rowspan="<?=count($items) ?>" class="pd-title">
                                <?= $listing['name']; ?> 
                                <div class="store-tags"><?= !empty($shop_name[$listing['lazada_uid']])?$shop_name[$listing['lazada_uid']]:'';?></div>
                            </td>
                            <?php endif; ?>
                            
                            
                            <td><input type="hidden" name="listingId" value="<?= $listing['id'] ?>"><?= $listing['SellerSku'] ?><div>尺寸：<?= $listing['_compatible_variation_'] ?></div></td>
                            <td><?= $listing['price'] ?></td>
                            <td><?= $listing['special_price'] ?></td>
                            <td><?= $listing['quantity'] ?></td>
                            <td><?= $listing['sub_status'] ?></td>
                            <td style="word-break:break-all;">
                                <?php if($listing['lb_status'] <> 0 && $listing['lb_status'] <> LazadaProductStatus::EDITING_FAIL):?>
                                <?= @LazadaProductStatus::$LISTING_EDITING_TYPE_MAP[$listing['lb_status']]?>
                                <?php elseif($listing['lb_status'] == 0 && !empty($listing['error_message'])) :?>
                                QC失败原因:<br>
                                <p class="text-warning"><?= $listing['error_message'] ?></p>
                                <?php elseif($listing['lb_status'] == LazadaProductStatus::EDITING_FAIL && !empty($listing['error_message'])) :?>
                                <?= @LazadaProductStatus::$LISTING_EDITING_TYPE_MAP[$listing['lb_status']]?>:<br>
                                <p class="text-danger"><?= $listing['error_message'] ?></p>
                                <?php endif;?>
                            </td>
                            <td class="table-operate-style"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
    <div style="text-align: left;">
        <div class="btn-group">
            <?php echo LinkPager::widget(['pagination' => $pages, 'options' => ['class' => 'pagination']]); ?>
        </div>
        <?php echo \eagle\widgets\SizePager::widget(['pagination' => $pages, 'pageSizeOptions' => array(10, 20, 50, 100, 200), 'class' => 'btn-group dropup']); ?>
    </div>
</div>
<input type="hidden" id="search_status" name="search_status"
       value="<?php echo !empty($search_status) ? "search" : "no_search"; ?>">
<!-- 批量修改的模态层 -->
<div class="modal fade" id="edit_product" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-style">
            <div class="modal-header header-style">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">批量修改</h4>
            </div>
            <form id="edit-product">
                <div class="modal-body">
                    <input type="hidden" id="productIds" name="productIds" value="">
                    <span style="padding-left:106px"><label
                            for="edit_type">修改选项：</label><?= Html::dropDownList('edit_type', @$_REQUEST['edit_type'], $edit_type, ['onchange' => "lazadaListing.editTypeChange(this)", 'class' => 'eagle-form-control', 'id' => 'edit_type', 'style' => 'width:260px;', 'prompt' => '请选择修改项']) ?></span>
                    <br/><span style="padding-left:106px;"><label for="edit_method">修改方式：</label><select
                            id="edit_method" name="edit_method" class="eagle-form-control" style="width:260px;"
                            onchange="lazadaListing.methodChange(this)"><option
                                value="">请选择修改方式：</option></select></span>
                    <br/><span class="input_replace" style="padding-left:106px;"><label for="edit_input"
                                                                                        class="batch-label">替换：</label><input
                            id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control"
                            style="width:260px;"><span class="percent"></span></span>
                    <br/><span class="remind" style="padding-left:106px;"></span>
                    <br/><span class="sale_message" style="padding-left:106px;"></span>
                </div>
                <div class="modal-footer">
                    <input id="batch_edit_comfirm" value="确认" type="button" class="btn btn-success"
                           onclick="lazadaListing.batchEditSubmit()">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- 同步 -->
<div class="modal fade" id="Sync_product" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-style">
            <div class="modal-header header-style">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">商品同步</h4>
            </div>
            <form id="Sync-product">
                <div class="modal-body">
                    <span style="padding-left:55px"><label
                            for="Sync_lzd_uid">选择店铺：</label><?= Html::dropDownList('Sync_lzd_uid', @$_REQUEST['Sync_lzd_uid'], $shop_name, ['onchange' => "", 'class' => 'eagle-form-control', 'id' => 'Sync_lzd_uid', 'style' => 'width:260px;', 'prompt' => '请选择店铺']) ?></span>
                    <br/><span class="success_message" style="padding-left:55px;"></span>
                </div>
                <div class="modal-footer">
                    <input id="Sync_product_comfirm" value="手工同步" type="button" class="btn btn-success"
                           onclick="lazadaListing.SyncSubmit()">
                    <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function search_submit(val) {
        $('form[id="online_search"]').submit();
    }
    function dosearch(name, val) {
        $('#' + name).val(val);
        document.online_search.submit();
    }
</script>