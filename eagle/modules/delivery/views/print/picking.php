<div style="text-align:center;font-size:20pt;font-weight:bold;">拣货单</div>
<div>
<span>操作人：<?php echo $operator;?></span>
<span style="float:right;">打印时间:<?php echo date('Y-m-d H:i:s',$time)?></span>
</div>
<table style="font-size:8pt;width:100%;" cellSpacing=0>
<tr style='border-top:1px black solid;text-align:left;'>
<th style='border-top:1px black solid;width:10%;'>SKU</th>
<th style='border-top:1px black solid;width:10%;'>货位号</th>
<th style='border-top:1px black solid;width:10%;'>数量</th>
<th style='border-top:1px black solid;'>产品名</th>
</tr>

<?php foreach ($products as $product){?>
<tr style='border-top:1px black solid;width:100%;'>
<td style='border-top:1px black solid;width:10%;'><?php echo $product['sku'];?></td>
<td style='border-top:1px black solid;width:10%;'><?php echo $product['location'];?></td>
<td style='border-top:1px black solid;width:10%;'><?php echo $product['quantity'];?></td>
<td style='border-top:1px black solid;'><?php echo $product['name']?></td>
</tr>
<?php }?>
</table>