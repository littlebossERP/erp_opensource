
<?php
use yii\helpers\Html;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/order/orderActionPublic.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>

<br>
<form class="form-inline" id="form" name="form" action="" method="post">
    <div>
        小老板单号：<input type="text" name="order_id" id="order_id" class="iv-input" value="<?php echo isset($_REQUEST['order_id'])?$_REQUEST['order_id']:'';?>" />
  SKU：<input type="text" name="sku" id="sku" class="iv-input" value="<?php echo isset($_REQUEST['sku'])?$_REQUEST['sku']:'';?>" />
<input type="submit" name="searchval" id="searchval" class="iv-btn btn-search btn-spacing-middle" value="筛选"/>
    </div><br>
    <?=Html::dropDownList('do','',array('suspendDelivery'=>'暂停发货','outOfStock'=>'标记缺货'),['prompt'=>'批量操作','onchange'=>"doactionnew(this);",'class'=>'iv-input do','style'=>'width:150px;margin:0px']);?> 
        <br><br>
        <div style="margin-left: 0%; margin-right: 0%;">
            <table width="100%" class="table table-condensed table-bordered">
                <tr >
                    <th width="100" style="text-align: center;border:1px solid #d9effc;"><input type="checkbox" check-all="e1" />小老板单号</th>
                    <th width="100" style="text-align: center;border:1px solid #d9effc;">图片</th>
                    <th width="300" style="text-align: center;border:1px solid #d9effc;">商品名</th>
                    <th style="text-align: center;border:1px solid #d9effc;">SKU</th>
                    <th style="text-align: center;border:1px solid #d9effc;">数量</th>
                    <th style="text-align: center;border:1px solid #d9effc;">属性</th>
                </tr>
                <?php
    foreach($odDeliveryOrderDataFinal as $orderID=>$data) {?>
                <?php
                $i =1;
                $deliveryid ='';
                foreach($data as $odDeliveryOrder){
                    $deliveryid = $odDeliveryOrder['delivery_id'];
                    ?>

                    <tr>
                        <?php if($i == 1) {?>
                        <td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;" rowspan="<?=count($data);?>"><input type="checkbox" class="ck" name="order_id[]" value="<?=$orderID?>" data-check="e1" ><?=$orderID?></td>
                        <?php }?>
                        <td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
                            <img src="<?=$odDeliveryOrder['image_adress']?>" width="60px" height="60px">
                        </td>
                        <td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
                            <?=$odDeliveryOrder['good_name']?>
                        </td>
                        <td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
                            <?=$odDeliveryOrder['sku']?>
                        </td>
                         <td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
                            <?=$odDeliveryOrder['count']?>
                        </td>
                        <td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
                            <?=$odDeliveryOrder['good_property']?>
                        </td>
                    </tr>
                <?php $i++;} ?>
                 <?php }?>
            </table>
        </div>
   
    <input type="hidden" id="deliveryid" name="deliveryid" value="<?=$_REQUEST['deliveryid']?>" />
</form>
