
<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\delivery\models\OdDelivery;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\models\carrier\SysCarrierParam;
use eagle\models\OdOrderShipped;
use eagle\modules\delivery\models\OdDeliveryOrder;

?>

<br>
<div style="width: 100%; height:10%; padding: 0px; text-align: center;">
    <form class="form-inline" id="form" name="form" action="" method="post">
        <div >
            <?php
                if(isset($order_id_arr) && !empty($order_id_arr)){
                    echo '拣货已完成！订单已经移到分拣配货';
                }
                else{
                    echo '拣货失败！该拣货单不存在任何订单！';
                }
            ?>

            <br>
            <br>
            <?=Html::button('确定',['class'=>"iv-btn btn-search",'onclick'=>"javascript:confirm();"])?>
        </div>
    </form>
</div>

<script>
    function confirm(){
        bootbox.hideAll();
        location.reload();
    }
</script>
