<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper; 

?>
<style>
.p0 {
    padding: 0;
}
.pTop10 {
    padding-top: 10px;
}
.w80 {
    width: 80px;
}
.mRight20 {
    margin-right: 20px;
}
.myj-active {
    background-color: #ff9900;
    padding: 5px 5px;
    color: #fff;
}
.minW345 {
    min-width: 345px;
}
.form-control {
	font-size: 13px;
    display: block;
    width: 100%;
    height: 34px;
    padding: 6px 12px;
    font-size: 14px;
    line-height: 1.42857143;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    -webkit-transition: border-color ease-in-out .15s,-webkit-box-shadow ease-in-out .15s;
    -o-transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
    transition: border-color ease-in-out .15s,box-shadow ease-in-out .15s;
}
.selectYhPage .myj-table table, .selectYhPage .myj-table table tr td, .embedTable, .embedTable tr td {
    border: none;
    text-align: left;
}
.quoteImgDivIn {
    position: relative;
    width: 61px;
    height: 61px;
    display: inline-block;
    background-color: white;
    border: 1px solid #cccccc;
	width: 28px;
    height: 28px;
}
.selectYhPage .myj-table table .quoteImgDivIn {
    width: 28px;
    height: 28px;
}
.quoteImgDivOut {
    width: 30px;
    height: 30px;
}
.myj-table {
    width: 100%;
    line-height: 22px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
}
.text-center {
    text-align: center;
}
.imgCss {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    padding: 1px;
    margin: auto;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    position: absolute;
}
.vAlignTop {
    vertical-align: top;
}
.mTop10 {
    margin-top: 10px;
}
.myj-table tr td {
    border: 0px;
}
.txtleft {
    text-align: left;
}
</style>
<div class="col-xs-12 p0">
<div role="tabpanel" class="tab-pane active col-xs-12">
<div class="col-xs-12 p0">
<div class="col-xs-12 p0 mTop10">
<div class="col-xs-1 w80 p0 pTop10">搜索类型：</div>
<div class="col-xs-10 pTop10">
<a href="javascript:;" class="mRight20 myj-active" onclick="OrderCommon.setSelectWareHoseProductsType('1', this)">商品SKU</a>
<a href="javascript:;" class="mRight20" onclick="OrderCommon.setSelectWareHoseProductsType('2', this)">标题</a>
</div>
</div>
<div class="col-xs-12 p0 mTop10">
<div class="col-xs-1 w80 p0 pTop10" style="margin-top: 4px;">搜索内容：</div>
<div class="col-xs-4 minW345">
<input id="searchWareHoseProductsValue" type="text" class="form-control" placeholder="" value="">
<input id="searchWareHoseProductsType" type="hidden" class="form-control" value="1">
<input id="searchWareHoseProductsid" type="hidden" class="form-control" value="<?=$orderitemid ?>-<?=$type?>">
<input id="searchWareHosesku" type="hidden" class="form-control" value="<?=$sku ?>">
</div>
<div class="col-xs-1 minW100">
<button id="btnSelectSearch" class="btn btn-primary" type="submit" style="margin-top: 2px;">搜索</button>
</div>
</div>
</div>
</div>
</div>
<div id="productbody_nosearch" class="modal-body tab-content col-xs-12 hidden"><span>找不到相应的商品,需要<a href="/catalog/product/index" target="_blank">创建商品</a></span></div>
<div id="productbody" class="modal-body tab-content col-xs-12 hidden">
<table class="table table-condensed table-bordered myj-table">
            <thead>
            <tr class="text-center">
                <th>商品信息</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                        <td style="width:160px;">
                            <table >
                                <tbody><tr>
                                    <td>
                                        <div class="quoteImgDivOut" style="margin-left: 6px;">
                                            <div class="quoteImgDivIn">
                                                        <img id="search_photo" src="" class="imgCss" style="cursor: wait">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="vAlignTop" style="padding-left: 6px;">
                                                <p class="m0 txtleft">
                                                    <span id="search_name" data-placement="right" data-toggle="popover" data-content="" data-original-title="" title="">
                                                    </span>
                                                </p>
                                                <p class="m0 txtleft">
                                                    <span id="search_sku" data-placement="right" data-toggle="popover" data-content="" data-original-title="" title="">
                                                    </span>
                                                </p>
                                    </td>
                                </tr>
                            </tbody></table>
                        </td>
                        <td style="width:40px;">
                            <a class="Choice" href="javascript:OrderCommon.Choice(this);" >选择</a>
                        </td>

                </tr>
            </tbody>
        </table>
</div>

<div id="productbodylist" class="modal-body tab-content col-xs-12">
</div>
<script>
$('.modal-close').click(function(){
	$('.modal-backdrop').remove();
});
</script>