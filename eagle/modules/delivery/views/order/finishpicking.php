
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
        <input type="hidden" id="deliveryid" value="<?=$deliveryid?>">
        <div>
            拣货人：<?=Html::textInput('picking_operator',@$_REQUEST['picking_operator'],['class'=>'iv-input','id'=>'picking_operator'])?>
        </div>
        <br>
        <div style="float: right; margin-top:-20px;">
            <?=Html::button('确认',['class'=>"iv-btn btn-search",'onclick'=>"javascript:submitData($deliveryid,$warehouse_id);"])?>
            <?=Html::button('返回',['class'=>"iv-btn ",'onclick'=>"javascript:goback();"])?>
        </div>

    </form>
</div>

<script>
    function submitData(deliveryid,warehouse_id){
        var picking_operator = $("#picking_operator").val();
        if(picking_operator.length==0){
            window.location.reload();
        }
        else
        {
            $.ajax({
                type: "GET",
                dataType: 'html',
                url: '/delivery/order/finishpicking',
                data: {picking_operator: picking_operator, deliveryid: deliveryid,warehouse_id,warehouse_id},
                success: function (result) {
                    bootbox.dialog({
                        title: Translator.t("确认拣货完成"),
                        className: "finishpickingsearch",
                        message: result
                    });


                }
            });
        }
    }

    function goback(){
        $(".isfinishpicking").modal("hide");
    }

</script>
