<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoInventory */
/* @var $form yii\widgets\ActiveForm */
?>

<div>
<form action="" method="post" name="searchCreat" id="c">
    <div class="dianpusearch form-inline" style="padding: 7px">
        <span class="iconfont icon-dianpu"></span>
        <?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($sellers,'selleruserid','selleruserid'),['class'=>"iv-input",'prompt'=>'我的eBay账号'])?>
        <button type="submit" class="iv-btn btn-search">
            GO  
        </button>
    </div>

    <div class="mutisearch" style="padding: 7px">
        <?=Html::textInput('itemid',@$_REQUEST['itemid'],['placeholder'=>'ItemID','class'=>"iv-input"])?>
        <?=Html::textInput('sku',@$_REQUEST['sku'],['placeholder'=>'SKU','class'=>"iv-input"])?>
        <?=Html::textInput('itemtitle',@$_REQUEST['itemtitle'],['placeholder'=>'刊登标题','class'=>"iv-input"])?>
        <?=Html::dropDownList('listingtype',@$_REQUEST['listingtype'],['FixedPriceItem'=>'一口价','IsVariation'=>'多属性'],['prompt'=>'刊登类型','class'=>"iv-input"])?>
        <?=Html::dropDownList('site',@$_REQUEST['site'],Helper_Siteinfo::getEbaySiteIdList('en','en'),['prompt'=>'eBay站点','class'=>"iv-input"])?>
        <?=Html::dropDownList('hassold',@$_REQUEST['hassold'],['0'=>'否','1'=>'是'],['prompt'=>'有售出','class'=>"iv-input"])?>
        <?=Html::dropDownList('outofstockcontrol',@$_REQUEST['outofstockcontrol'],['0'=>'否','1'=>'是'],['prompt'=>'永久在线','class'=>"iv-input"])?>
        <?=Html::submitButton('搜索',['class'=>'iv-btn btn-search'])?>
        </div>
        <?=Html::hiddenInput('xu',@$_REQUEST['xu'],['id'=>'xu'])?>
        <?=Html::hiddenInput('xusort',@$_REQUEST['xusort'],['id'=>'xusort'])?>
</form>
</div>