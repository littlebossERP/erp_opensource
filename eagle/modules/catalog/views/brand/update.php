<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model eagle\modules\catalog\models\Product */

$this->title = 'Update Product: ' . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Brand', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'brand_id' => $model->brand_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="product-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
