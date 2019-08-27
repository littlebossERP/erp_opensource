<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoInventory */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ebay-auto-inventory-form">
    <div style="padding-bottom: 14px;font-size: 14px;color: blue">
        <?= Html::encode($this->title) ?>
    </div>


    <?php $form = ActiveForm::begin(); ?>
    <?= Html::hiddenInput("id",$model->id)?>
    <?= Html::label('自动补货数量 :',"inventory") ?>
    <?= Html::textInput("inventory",$model->inventory,['style'=>'padding:5px'])?>
    <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'iv-btn btn-success']) ?>
    <?php ActiveForm::end(); ?>

</div>
