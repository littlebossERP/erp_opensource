<?php 
use eagle\modules\tracking\models\Tracking;
use eagle\models\QueueDhgateGetorder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\StandardConst;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("initial()", \yii\web\View::POS_READY);
foreach ($all_list as $detail){
    break;
}

?>

<div id="order-list">
    <table class="table people_table" style="margin-bottom: 0px;">
        <thead>
            <tr>
                <th style="vertical-align: middle;"><?php echo empty($detail['order_source'])?null:$detail['order_source'];?>订单号</th>
                <th style="vertical-align: middle;">付款日期</th>
                <th style="vertical-align: middle;">总价（含运费）</th>
                <th style="vertical-align: middle;"><?php echo empty($detail['order_source'])?null:$detail['order_source'];?>状态</th>
                <th style="vertical-align: middle;">收件国家</th>
                <th style="vertical-align: middle;">物流状态</th>
            </tr>
        </thead>
        <tbody>
        <?php if(!empty($all_list)):?>
            <?php $num=0;foreach ($all_list as $list):?>
                <tr <?php echo $num%2==0?"class='striped-row'":null;$num++;?>>
                    <td style="vertical-align: middle;"><a onclick=" GetOrderid('<?php echo $list['order_source_order_id']?>')"><?php echo $list['order_source_order_id']?></a></td>
                    <td style="vertical-align: middle;"><?php echo date("Y-m-d",$list['paid_time']);?></td>
                    <td style="vertical-align: middle;"><?php echo $list['currency'];?> <?php echo $list['grand_total'];?></td>
                    <td style="vertical-align: middle;">
                    <?php
                        switch ($detail['order_source']) 
                        {
                            case "dhgate":
                                echo QueueDhgateGetorder::$orderStatus[$list['order_source_status']];
                                break;
                            case "aliexpress":
                                echo OdOrder::$aliexpressStatus[$list['order_source_status']];
                                break;
                            default:
                                echo $list['order_source_status'];
                        }
                        
                    ?>
                    </td>
                    <td style="vertical-align: middle;<?php echo $list['consignee_country_code']=="--"?null:"color:#02ce59";?>"><?php echo !empty($list['consignee_country_code'])?StandardConst::$COUNTRIES_CODE_NAME_CN[$list['consignee_country_code']]:null;?></td>
                    <td style="vertical-align: middle;<?php echo Tracking::getChineseStatus($list['status'])=="--"?null:"color:#0ec0f0";?>"><?php echo Tracking::getChineseStatus($list['status'])?><br /><a class="<?php echo $style_list;?>" data-id="<?php echo $list['track_no']?>"><?php echo $list['track_no']?></a></td>
                </tr>
            <?php endforeach;?>
        <?php endif;?>
        </tbody>
    </table>
</div>
