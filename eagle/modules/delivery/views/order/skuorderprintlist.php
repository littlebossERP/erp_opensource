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
		$count=0;
		foreach ($OrderArr as $OrderArrone){

		?>
        <table width="100%" cellspacing="0" cellpadding="0" class="table table-condensed table-bordered" style="font-size:12px; border: 1px solid #CCCCCC;">
        		<tr>
	                <td width="80" rowspan="2">小老板单号:</td>
	                <td width="200">
	                <img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$OrderArrone['order_id'],'font'=>0,'fontsize'=>0])?>">
	                </td>
	                <td width="45%" >仓库:<?php echo empty($warehouseArr)?'':$warehouseArr[$OrderArrone['order_id']]; ?></td>
	                <td>运单号:<?=$TrackingNumberArr[$OrderArrone['order_id']]?></td>
	            </tr>
	            <tr>
	            	<td  width="200"><?=$OrderArrone['order_id']?></td>
	                <td>订单备注:<?=$OrderArrone['desc']?></td>
	                <td>收件人地址: <?php echo $OrderArrone['consignee_address_line1'].' '.$OrderArrone['consignee_address_line2'].' '.$OrderArrone['consignee_address_line3'].' '.$OrderArrone['consignee_county'].' '.$OrderArrone['consignee_district'].' '.$OrderArrone['consignee_city'].' '.$OrderArrone['consignee_province'].' '.$OrderArrone['consignee_country']?></td>
	            </tr>
        </table>
        	<?php 
        		$OrderArritem=$OrderArrone['items'];
        		foreach ($OrderArritem as $OrderArroneitem){
        	?>
			        <table width="100%" cellspacing="5" cellpadding="0" class="table table-condensed table-bordered" style="font-size:12px;border-width:0 1px 1px 1px;border-style: solid;border-color: #CCCCCC;word-break:break-all; word-wrap:break-all;">
			        		<tr>
			        			<td width="40" rowspan="2"><img src="<?=$OrderCountArr[$OrderArrone['order_id']]['sku'][$OrderArroneitem['sku']]['photo_primary']?>" width="40px" height="40px"></td>
				                <td width="40%" colspan=2>订单号:<?=$OrderArroneitem['order_source_order_id']?></td>
				                <td colspan=2 style="width:40%;font-size:12px;"><?=$OrderArroneitem['product_name']?></td>
				                <td rowspan="2" style="font-size:13pt;font-weight:bold;">&nbsp;数量:<?=$OrderArroneitem['qty']?></td>
				            </tr>
				            <tr>
				                <td width="15%;">SKU:<?=$OrderArroneitem['sku']?></td>
				                <td>&nbsp;<?php echo $warehouselistArr[$OrderArrone['default_warehouse_id']]['skuproduct'][$OrderArroneitem['sku']]['product_attributes'];?></td>
				                <td width="28%"></td>
				                <td width="150">货架:<?=$locationgridArr[$OrderArroneitem['order_item_id']]['location_grid']?></td>
				            </tr>
			        </table>
			<?php } ?>
			<br/>
        <?php } ?>
        
         <table width="100%">
            <tr>
                <td width="350" style="font-size:15pt;font-weight:bold;"><p>总计：<?php echo $quantitycount; ?></p></td>
            </tr>
        </table>
        
        <?php 

        foreach ($OrderItemArr as $OrderArroneitemone){
        ?>
        			<table width="100%" cellspacing="5" cellpadding="0" class="table table-condensed table-bordered" style="font-size:10pt;font-weight:bold;word-break:break-all; word-wrap:break-all;">
			        		<tr>
				                <td width="60px" rowspan="2"><img src="<?=$OrderArroneitemone['photo_primary']?>" width="60px" height="60px"></td>
				                <td width="25%">SKU:<?=$OrderArroneitemone['sku']?></td>
				                <td>&nbsp;<?php //echo $OrderArroneitemone['product_attributes'];?></td>
				                <td rowspan="2">&nbsp;&nbsp;数量:<?=$OrderArroneitemone['quantity']?></td>
				            </tr>
				            <tr>
				                <td width="70%" colspan=2>商品名称:<?=$OrderArroneitemone['product_name']?></td>
				            </tr>
			        </table>
        <?php } ?>
</div>
