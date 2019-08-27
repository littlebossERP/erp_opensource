<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoTimerListing */
?>
<div class="ebay-auto-timing-listing-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
