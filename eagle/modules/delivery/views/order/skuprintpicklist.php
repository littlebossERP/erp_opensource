
<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\catalog\apihelpers\ProductApiHelper;
?>

<style>
body{
	font-size: 14px;
    color: #333333;
}
    p {
        font-size: 14px;
    }
</style>
	<div style="margin-left: 2%; margin-right: 2%;">
		<?php 
		foreach ($warehouselistArr as $k=>$warehouselistArrone){
		?>
        <table width="100%" >
            <tr>
            	<td width="220" >仓库:<?php echo $warehouselistArrone['warehouse'];?></td>
            	<td width="220" >总数：<?php echo $warehouselistArrone['quantitycount'];?></td>
            	<!--  
                <td width="100"  >拣货单号:</td>
                <td width=150">
                <img src="<?php /*echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$OrderArrone['order_id'],'font'=>0,'fontsize'=>0])*/?>">
                </td>
                <td width="300" >打印操作人:<?php /*=$OrderArrone['print_picking_operator']?> &nbsp;&nbsp; <?=$OrderArrone['print_picking_time']>0?date('Y/m/d H:i:s',$OrderArrone['print_picking_time']):''*/?>
                </td>
                <td width="220" >拣货人：</td>
                -->
            </tr>
        </table>
        <table width="100%" cellspacing="0" cellpadding="0" class="table table-condensed table-bordered" style="font-size:12px; border: 1px solid #CCCCCC;">
            <tr style="text-align: center; ">
                <th width="5%" style="text-align: center; border:1px solid #CCCCCC;"><b>序号</b></th>
                <?php if(ConfigHelper::getConfig('no_show_product_image')!='N') { ?>
                <th width="15%" style="text-align: center;border:1px solid #CCCCCC;"><b>图片</b></th>
                <?php } ?>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>中文配货名称</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>SKU</b></th>
                <th width="15%" style="text-align: center;border:1px solid #CCCCCC;"><b>属性</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>数量</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>货位</b></th>
            </tr>
            <?php 
            $num = 1;
				foreach ($warehouselistArr as $keys=>$warehouselistArrone){ 
            if (count($keys) && $keys==$k){
				$OrderArritem=$warehouselistArrone['skuproduct'];
                foreach ($OrderArritem as $odDeliveryOrderData){?>
                    <tr class="xiangqing">
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$num;?></p></td>
                        <?php 
                            if(ConfigHelper::getConfig('no_show_product_image')!='N') {
                            	?>
                        <td style="border:1px solid #CCCCCC; text-align:center">
                                <img src="<?=$odDeliveryOrderData['photo_primary']?>" width="60px" height="60px">
                        </td>
                         <?php }  ?>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['prod_name_ch']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['sku']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['product_attributes']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['quantity']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['location_grid']?></p></td>
                    </tr>
                <?php $num++;}
            } }?>
        </table><br/>
        <?php } ?>
        <table style='word-break:break-all; word-wrap:break-all;width:100%;'>
            <tr>
                <td style="font-size: 12pt;font-weight:bold;width:72%;">关联订单：<?php echo $orderIdlist; ?></td>
                <td width="350" style="font-size:14pt;font-weight:bold;"><p>&nbsp;总计：<?=count($OrderArr)?>个订单，<?php echo count($OrderItemArr);?>种SKU，<?=$productcount?>个商品</p></td>
            </tr>
        </table>
    </div>
<!--</div>-->
