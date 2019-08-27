<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoInventory */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Ebay Auto Inventories', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ebay-auto-inventory-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'selleruserid',
            'draft_id',
            'itemid',
            'status',
            'status_process',
            'type',
            'less_than_equal_to',
            'inventory',
            'success_cnt',
            'ebay_uid',
            'created',
            'updated',
        ],
    ]) ?>

</div>
