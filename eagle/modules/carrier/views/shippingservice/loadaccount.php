<?php
use yii\helpers\Html;
?>
<?=Html::dropDownList('carrier_account_id',$service->carrier_account_id,$accounts,['style'=>'width:150px;','class'=>'eagle-form-control'])?>