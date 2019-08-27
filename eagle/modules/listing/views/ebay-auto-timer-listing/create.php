<?php

use yii\helpers\Html;
use \yii\widgets\LinkPager;
use \eagle\widgets\SizePager;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoTimerListing */
$this->registerCssFile(\Yii::getAlias('@web').'/css/listing/ebay/ebayautotimerlisting.css');
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ebaylistingv2/timerlisting.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->title = '创建定时刊登';
?>

<div class="col-xs-12 tracking-index col2-layout">
    <?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>
    <!-- search Create -->
    <?=$this->render('_search',['sellers'=>$sellers]);?>
    <!-- item table -->
    <div class="ebay-auto-timing-listing-create">

        <?= $this->render('_tableCreate', [
            'models' => $models,
        ]) ?>
        <!-- 页码跳转 -->
        <div class="btn-group" >
            <?=LinkPager::widget(['pagination' => $pages,]);?>
        </div>
        <?=SizePager::widget([
            'pagination'=>$pages ,
            'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ),
            'class'=>'btn-group dropup'])?>
    </div>
</div>