
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
use eagle\modules\platform\models\CdiscountRegister;
?>

<?php $dataArr = CdiscountRegister::find()->select('desc')->where(['id'=>$id])->asArray()->all();
     foreach($dataArr as $data){
         $desc = $data['desc'];
     }
?>


<div style="width: 100%; height:30%; padding: 0px; text-align: center;">
    <form class="form-inline" id="form" name="form" action="" method="post">
        <table width="100%">
            <tr>
                <td width="20%" style="text-align: right;">备注：</td>
                <td width="60%"><textarea rows="6" cols="60" id="update_desc"><?=$desc?></textarea></td>
                <td width="20%">&nbsp;</td>
            </tr>
            <tr><td colspan="3">&nbsp;</td></tr>
            <tr>
                <td>&nbsp;</td>
                <td style="text-align: center;"><?=Html::button('确认',['class'=>"iv-btn btn-search",'onclick'=>"javascript:submitData($id);"])?>
                    <?=Html::button('返回',['class'=>"iv-btn ",'onclick'=>"javascript:goback();"])?></td>
                <td>&nbsp;</td>
            </tr>
        </table>
    </form>
</div>

<script>
    function submitData(id){

        var update_desc = $("#update_desc").val();
            $.ajax({
                type: "post",
                dataType: 'json',
                url: '/platform/platform/update-desc-page',
                data: {id:id, update_desc: update_desc},
                success: function (result) {
                    bootbox.alert(result.message);
                    $(".update-desc-page").modal("hide");
                    }
            });
    }

    function goback(){
        $(".update-desc-page").modal("hide");
    }

</script>
