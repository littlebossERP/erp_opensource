<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
body{
	font-size: 14px;
    color: #333333;
  	font-family:Microsoft YaHei;
}
    p {
        font-size: 14px;
    }
    .red{
    	font-size: 15px;
    	color: red;
    	font-weight:700;
    }
    .red-14{
    	font-size: 14px;
    	color: red;
    }
    .tdbottom-solid{
    	border-bottom:1px solid #000000;
    }
    .tdbottom-dashed{
    	border-bottom:1px dashed #000000;
    }
    .tdleft-solid{
    	border-left:1px solid #000000;
    }
    .font8{
    	font-size:14px;
    }
</style>
<?php
$pagesize=1;  //当前页数
$pagecount=1;   //总页数 
?>
 <div style="width:210mm;height:297mm;margin:auto;padding:5px;border:0px solid #000000;">
<!-- <div style="width:912px;height:1290px;margin:auto;padding:5px;border:0px solid #000000">-->
 		<table style="width:100%;text-align:right;"><tr><td>第<?php echo $pagesize;?>页,共<label class="pagecount"></label>页</td></tr></table>
        <table id="items2" border=0 width="100%"  cellspacing="0" cellpadding="0" class="table table-condensed table-bordered font8">
        		<tr>
	                <td colspan="2">采购员:</td>
	            </tr>
	            <tr>
	            	<!-- <td  width="200" style="height:30px;">订单截止时间:</td>-->
	                <td>总计:<?=count($OrderArr)?>个订单，<?php echo count($OrderItemArr);?>种SKU，<?=$productcount?>个商品</td>
	            </tr>
        </table>
        <table id="items" width="100%" cellspacing="0" cellpadding="0" class="table table-condensed table-bordered font8" style="border:1px solid #000000;border-bottom:hidden;word-break:break-word; word-wrap:break-word;">
        <?php
        $pagelist=0;   //多少订单信息分一页
        $leftitem=0;     //多少商品分一页
        $ordercount=0;    //记录运行了的订单数
        foreach ($OrderArr as $OrderArrone){
			 foreach($OrderCountArr as $key=>$OrderCountArrone){ 
				if($key==$OrderArrone['order_id']){

				if(count($OrderCountArrone['sku'])==1)
					$leftitem+=2;
				if(count($OrderCountArrone['sku'])==2)
					$leftitem+=1;
				$lastlist=0;   //每张订单的商品记录数
				$N=count($OrderCountArrone['sku']);       //多少商品配一个订单信息
				$tiaojian=count($OrderCountArrone['sku'])-$lastlist>$N?$N+1:count($OrderCountArrone['sku'])-$lastlist+1;
				?>
				<tr>
				<td width="50%" height="60px" class='tdbottom-solid' valign="top">订单sku种类数:<span class="red"><?php echo count($OrderCountArrone['sku']);?></span>&nbsp;&nbsp;订单产品总件数:<span class="red"><?php echo $OrderCountArrone['quantity'];?>件</span><br/>订单备注:<span class="red"><?php echo $OrderArrone['desc'];?></span></td>
				<td width="5%" class='tdbottom-solid tdleft-solid' style="text-align:center;">标记完成</td>
				<td width="45%" class='tdbottom-solid tdleft-solid' rowspan=<?php echo count($OrderCountArrone['sku'])-$lastlist>$N?$N+1:count($OrderCountArrone['sku'])-$lastlist+1;?> valign="top" style="height: 350px;">
				<table class="font8" border=0 style="word-break:break-word; word-wrap:break-word;">
				<tr><td colspan=2>订单日期：<?php echo date("Y-m-d H:i:s",$OrderArrone['order_source_create_time']);?></td></tr>
				<tr><td rowspan=2><img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'45','text'=>$OrderArrone['order_id'],'font'=>0,'fontsize'=>0])?>"></td><td>小老板单号：<?php echo $OrderArrone['order_id'];?></td></tr>
				<tr><td>平台订单号：<span class="red-14"><?php echo $OrderArrone['order_source_order_id'];?></span></td></tr>
				<tr><td colspan=2>To:<?php echo $OrderArrone['consignee'];?>&nbsp;Phone:<?php echo $OrderArrone['consignee_phone'];?> / <?php echo $OrderArrone['consignee_mobile'];?></td></tr>
				<tr><td colspan=2>Address:<?php echo $OrderArrone['consignee_address_line1'].' '.$OrderArrone['consignee_address_line2'].' '.$OrderArrone['consignee_address_line3'].' '.$OrderArrone['consignee_county'].' '.$OrderArrone['consignee_district'].' '.$OrderArrone['consignee_city'].' '.$OrderArrone['consignee_province'].' '.$OrderArrone['consignee_country'];?> </td></tr>
				<tr><td colspan=2>Phone:<?php echo $OrderArrone['consignee_phone'];?> / <?php echo $OrderArrone['consignee_mobile'];?></td></tr>
				</table>
				<table class="font8" border=0 style="word-break:break-word; word-wrap:break-word;">
				<tr><td rowspan=2><?php if(empty($TrackingNumberArr[$OrderArrone['order_id']])){ echo '<img>'; }else{ ?><img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'45','text'=>$TrackingNumberArr[$OrderArrone['order_id']],'font'=>0,'fontsize'=>0]);?>"><?php } ?></td><td><?php echo isset($services[$OrderArrone['default_shipping_method_code']])?$services[$OrderArrone['default_shipping_method_code']]:$OrderArrone['default_shipping_method_code'];?></td></tr>
				<tr><td><?php echo $TrackingNumberArr[$OrderArrone['order_id']];?></td></tr>
				<tr><td colspan=2>少货囗&nbsp;少配件囗&nbsp;有损坏囗&nbsp;待处理囗</td></tr>
				<tr><td colspan=2>情况说明：</td></tr>
				</table>
				</td>
				</tr>
				<?php 
				$pagelist++;
				$ordercount++;
				foreach($OrderCountArrone['sku'] as $Arronekey=>$OrderCountArronevalue){ 
if(array_key_exists('sku',$OrderCountArronevalue)){     //当不存在sku时，可能代表该商品的sku和订单上相同产品的sku不对应
				$lastlist++; 
				?>
				<tr><td valign="top" class='<?php echo $lastlist==count($OrderCountArrone['sku'])||$lastlist==$N?"tdbottom-solid":"tdbottom-dashed"; ?>'>
				<table class="font8">
				<tr><td rowspan=3 width="70px"><img height="70px" width="70px" src='<?php echo $OrderCountArronevalue['photo_primary'];?>'></td><td colspan=2><?php echo isset($OrderCountArronevalue['prod_name_ch'])?$OrderCountArronevalue['prod_name_ch']:$OrderCountArronevalue['product_name'];?></td></tr>
				<tr><td width="30%" class="red-14">数量:<?php echo $OrderCountArronevalue['quantity'];?></td><td class="red-14">SKU:<?php echo $OrderCountArronevalue['sku'];?></td></tr>
				<tr><td width="30%">属性:<?php echo $OrderCountArronevalue['product_attributes'];?></td><td class="red-14">货位:<?php echo $OrderCountArronevalue['location_grid'];?></td></tr>
				</table>
				</td>
				<td class='tdleft-solid <?php echo $lastlist==count($OrderCountArrone['sku'])||$lastlist==$N?"tdbottom-solid":"tdbottom-dashed"; ?>'></td>
				</tr>
							<?php 							
							$leftitem++;
							if(($pagelist>=3 || $leftitem>=9)&&$ordercount<count($OrderCountArr)){
									$pagecount++;
									$pagesize++;
									
									?>
									</table></div>
									 <div style="width:210mm;height:297mm;margin:auto;padding:5px;border:0px solid #000000;margin-top:75px;">
									 <table style="width:100%;text-align:right;"><tr><td>第<?php echo $pagesize;?>页,共<label class="pagecount"></label>页</td></tr></table>
									<table id="items2" border=0 width="100%"  cellspacing="0" cellpadding="0" class="table table-condensed table-bordered font8" >
							        		<tr>
								                <td colspan="2">采购员:</td>
								            </tr>
								            <tr>
								            	<!-- <td  width="200" style="height:30px;">订单截止时间:</td>-->
								                <td>总计:<?=count($OrderArr)?>个订单，<?php echo count($OrderItemArr);?>种SKU，<?=$productcount?>个商品</td>
								            </tr>
							        </table>
							        <table id="items" width="100%" cellspacing="0" cellpadding="0" class="table table-condensed table-bordered font8" style="border:1px solid #000000;border-bottom:hidden;word-break:break-word; word-wrap:break-word;">
									<?php 
									$pagelist=0;
									$leftitem=0;

							}
							
							if(($lastlist%$N==0 && $lastlist!=count($OrderCountArrone['sku'])) ){
							?>
									<tr>
									<td width="50%" height="35px" class='tdbottom-solid'>订单sku种类数:<span class="red"><?php echo count($OrderCountArrone['sku']);?></span>&nbsp;&nbsp;订单产品总件数:<span class="red"><?php echo $OrderCountArrone['quantity'];?>件</span><br/>订单备注:<span class="red"><?php echo $OrderArrone['desc'];?></span></td>
									<td width="5%" class='tdbottom-solid tdleft-solid' style="text-align:center;">标记完成</td>
									<td width="45%" class='tdbottom-solid tdleft-solid' rowspan=<?php echo count($OrderCountArrone['sku'])-$lastlist>$N?$N+1:count($OrderCountArrone['sku'])-$lastlist+1;?> valign="top" style="height: 350px;">
									<table class="font8" border=0 style="word-break:break-word; word-wrap:break-word;">
									<tr><td colspan=2>订单日期：<?php echo date("Y-m-d H:i:s",$OrderArrone['order_source_create_time']);?></td></tr>
									<tr><td rowspan=2><img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'45','text'=>$OrderArrone['order_id'],'font'=>0,'fontsize'=>0])?>"></td><td>小老板单号：<?php echo $OrderArrone['order_id'];?></td></tr>
									<tr><td>平台订单号：<span class="red-14"><?php echo $OrderArrone['order_source_order_id'];?></span></td></tr>
									<tr><td colspan=2>To:<?php echo $OrderArrone['consignee'];?>&nbsp;Phone:<?php echo $OrderArrone['consignee_phone'];?> / <?php echo $OrderArrone['consignee_mobile'];?></td></tr>
									<tr><td colspan=2>Address:<?php echo $OrderArrone['consignee_address_line1'].' '.$OrderArrone['consignee_address_line2'].' '.$OrderArrone['consignee_address_line3'].' '.$OrderArrone['consignee_county'].' '.$OrderArrone['consignee_district'].' '.$OrderArrone['consignee_city'].' '.$OrderArrone['consignee_province'].' '.$OrderArrone['consignee_country'];?> </td></tr>
									<tr><td colspan=2>Phone:<?php echo $OrderArrone['consignee_phone'];?> / <?php echo $OrderArrone['consignee_mobile'];?></td></tr>
									</table>
									<table class="font8" border=0 style="word-break:break-word; word-wrap:break-word;">
									<tr><td rowspan=2><?php if(empty($TrackingNumberArr[$OrderArrone['order_id']])){ echo '<img>'; }else{ ?><img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'45','text'=>$TrackingNumberArr[$OrderArrone['order_id']],'font'=>0,'fontsize'=>0]); ?>"><?php } ?></td><td><?php echo isset($services[$OrderArrone['default_shipping_method_code']])?$services[$OrderArrone['default_shipping_method_code']]:$OrderArrone['default_shipping_method_code'];?></td></tr>
									<tr><td><?php echo $TrackingNumberArr[$OrderArrone['order_id']];?></td></tr>
									<tr><td colspan=2>少货囗&nbsp;少配件囗&nbsp;有损坏囗&nbsp;待处理囗</td></tr>
									<tr><td colspan=2>情况说明：</td></tr>
									</table>
									</td>
									</tr>
							<?php 
							$pagelist++;
							}
							}
			}
			if($leftitem>3)
				$pagelist++;
			}} 
		} 
        ?>
        </table>
</div>
<input id='int_pagecount' type="hidden" value=<?php echo $pagecount;?>>

<script>
window.onload=function(){
	var x=document.getElementsByClassName("pagecount");
	for (i = 0; i < x.length; i++) {
		x[i].innerHTML=document.getElementById("int_pagecount").value;
	}
}
</script>