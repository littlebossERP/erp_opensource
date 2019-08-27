<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoInventory */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="ebay-auto-timing-listing-edit-create">
    <?=$this->render('_form',
                    [
                    'model' => $model,
                    'draft_model'=>$draft_model,
                    ]);?>
</div>