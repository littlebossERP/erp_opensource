
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
        <table width="100%" >
            <tr>
                <td width="100"  >拣货单号:
                <?=$odDeliveryDataArr['deliveryid']?></td>
                <td width=150">
                <img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$odDeliveryDataArr['deliveryid'],'font'=>0,'fontsize'=>0])?>">
                </td>
                <td width="220" >仓库:<?=$warehouseName?></td>
                <td width="300" >打印操作人:<?=$odDeliveryDataArr['print_picking_operator']?> &nbsp;&nbsp; <?=$odDeliveryDataArr['print_picking_time']>0?date('Y/m/d H:i:s',$odDeliveryDataArr['print_picking_time']):''?>
                </td>
                <td width="220" >拣货人：<?=$odDeliveryDataArr['picking_operator']?></td>
            </tr>
        </table>
        <table width="100%" cellspacing="0" cellpadding="0" class="table table-condensed table-bordered" style="font-size:12px; border: 1px solid #CCCCCC;">
            <tr style="text-align: center; ">
                <th width="5%" style="text-align: center; border:1px solid #CCCCCC;"><b>序号</b></th>
                <th width="15%" style="text-align: center;border:1px solid #CCCCCC;"><b>图片</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>商品名</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>SKU</b></th>
                <th width="15%" style="text-align: center;border:1px solid #CCCCCC;"><b>属性</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>数量</b></th>
                <th width="10%" style="text-align: center;border:1px solid #CCCCCC;"><b>货位</b></th>
            </tr>
            <?php $num = 1;
            if (count($odDeliveryOrderDataArr)){
                foreach ($odDeliveryOrderDataArr as $odDeliveryOrderData){?>
                    <tr class="xiangqing">
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$num;?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center">
                            <?php 
                           
                            if(ConfigHelper::getConfig('no_show_product_image')!='N') {
                            	if (strpos($odDeliveryOrderData['image_adress'], 'cdscdn') !== false){
									$img_url = ImageCacherHelper::getImageCacheUrl($odDeliveryOrderData['image_adress']);
								}else{
									$img_url = $odDeliveryOrderData['image_adress'];
								}
								if (strlen($img_url)<4){
									$product = ProductApiHelper::getProductInfo($odDeliveryOrderData['sku']);
									if($product != null){$img_url = $product['photo_primary'];}
								}
                            	?>
                                <img src="<?=$img_url?>" width="60px" height="60px">
                            <?php } else { ?>
                                <span>&nbsp;</span>
                            <?php }?>
                        </td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['good_name']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['sku']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['good_property']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['count']?></p></td>
                        <td style="border:1px solid #CCCCCC; text-align:center"><p><?=$odDeliveryOrderData['location_grid']?></p></td>
                    </tr>
                <?php $num++;}
            }?>

        </table>
        <table width="100%">
            <tr>
                <td style="font-size: 12pt;font-weight:bold;">关联订单：<?php echo implode(', ', $orderids); ?></td>
                <td width="350" style="font-size:14pt;font-weight:bold;"><p>总计：<?=$odDeliveryDataArr['ordercount']?>个订单，<?=$odDeliveryDataArr['skucount']?>种SKU，<?=$odDeliveryDataArr['goodscount']?>个商品</p></td>
            </tr>
        </table>
    </div>


<!--</div>-->
