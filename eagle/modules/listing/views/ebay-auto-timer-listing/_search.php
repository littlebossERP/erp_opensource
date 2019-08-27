<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoTimerListingSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ebay-auto-timer-listing-search">
    <?=$this->render('../ebay-listing-common/_searchDraft',['sellers'=>$sellers]);?>
</div>
