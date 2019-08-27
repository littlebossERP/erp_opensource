<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoTimerListingSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div>
<form action="" method="post" class="form-inline">
    <div class="dianpusearch form-inline">
        <span class="iconfont icon-dianpu"></span><?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($sellers,'selleruserid','selleruserid'),['class'=>"iv-input",'prompt'=>'我的eBay账号'])?>
        <button type="submit" class="iv-btn btn-search">
            GO  
        </button>
    </div>
    <br>
    <div class="mutisearch">
        <?=Html::textInput('sku',@$_REQUEST['sku'],['placeholder'=>'SKU','class'=>"iv-input"])?>
        <?=Html::textInput('paypal',@$_REQUEST['paypal'],['placeholder'=>'PayPal','class'=>"iv-input"])?>
        <?=Html::textInput('itemtitle',@$_REQUEST['itemtitle'],['placeholder'=>'标题','class'=>"iv-input"])?>
        <!-- <?//=Html::textInput('desc',@$_REQUEST['desc'],['placeholder'=>'备注','class'=>"iv-input"])?> -->
        <?=Html::dropDownList('listingtype',@$_REQUEST['listingtype'],['FixedPriceItem'=>'一口价','IsVariation'=>'多属性','Chinese'=>'拍卖'],['prompt'=>'刊登类型','class'=>"iv-input"])?>
        <?=Html::dropDownList('siteid',@$_REQUEST['siteid'],Helper_Siteinfo::getEbaySiteIdList('no','en'),['prompt'=>'平台','class'=>"iv-input"])?>
        <!-- <?//=Html::dropDownList('isvariation',@$_REQUEST['isvariation'],['0'=>'否','1'=>'是'],['prompt'=>'多属性','class'=>"iv-input"])?> -->
        <?=Html::dropDownList('outofstockcontrol',@$_REQUEST['outofstockcontrol'],['0'=>'否','1'=>'是'],['prompt'=>'永久在线','class'=>"iv-input"])?>
        <?=Html::submitButton('搜索',['class'=>'iv-btn btn-search'])?>
    </div>
</form>
<br>
</div>
