<?php

use yii\helpers\Html;
use \yii\widgets\LinkPager;
use \eagle\widgets\SizePager;
$this->registerCssFile(\Yii::getAlias('@web').'/css/listing/ebay/ebayautoinventory.css');
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ebaylistingv2/inventoryIndex.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->title = '创建自动补货';
?>

<div class="col-xs-12 tracking-index col2-layout">
    <?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>
    <!-- search Create -->
    <?=$this->render('_searchItem',['sellers'=>$sellers]);?>
    <!-- item table -->
    <div class="ebay-auto-inventory-create">

        <?= $this->render('_tableCreate', [
            'models' => $models,
            'details'=> $details,
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
<!-- end  tracking-index col2-layout-->
