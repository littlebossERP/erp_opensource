<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoInventory */

$this->title = 'itemid: ' . ' ' . $model->itemid;
$this->params['breadcrumbs'][] = ['label' => 'Ebay Auto Inventories', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="ebay-auto-inventory-update">
    <div style="padding: 7px">
        <?= $this->render('_editUpdate', [
            'model' => $model,
        ]) ?>
    </div>


</div>
