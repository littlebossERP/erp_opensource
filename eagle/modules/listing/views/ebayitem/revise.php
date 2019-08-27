<?php 

use eagle\modules\listing\models\EbayItemDetail;
use yii\helpers\Html;
?>
<style>
.piliang{
	margin:10px 0px; 
}
</style>
<script type="text/javascript">
function revise(){
	　　$(".result").each( function(){
		if($(this).text()=='Success' || $(this).text()=='Warning'){
			return;
		}
		$(this).html("");
		$(this).next().html(" 正在修改，请不要关闭页面...");
		var itemid=$(this).parent().find('#itemid').val();
		var sku=$(this).parent().find('#sku').val();
		var quantity=$(this).parent().find('#quantity').val();
		var startprice=$(this).parent().find('#startprice').val();
		var obj=this;
		$(document).queue('ajaxRequests',function(){
			$.ajax({
				type:'post',
				url:global.baseUrl+"listing/ebayitem/ajaxrevise",
				data:{itemid:itemid,sku:sku,quantity:quantity,startprice:startprice},
				timeout: 35000,
				dataType:'json',
				success:function(r){
				 $(obj).html(r.Ack);
				 $(obj).next().html(r.show);
				 $(document).dequeue("ajaxRequests");
			}});
		});
	 });
	 $(document).dequeue("ajaxRequests");
}; 

//批量进行数量与价格的处理
function dopiliang(str){
	var val = $('input[name=pi_'+str+']').val();
	if(val == ''){
		bootbox.alert('请先填写相应数据');return false;
	}
	if(isNaN(val)){
		bootbox.alert('请先填写数字');return false;
	}
	if(str == 'quantity'){
		var role = "^\\d+$";
		var re = new RegExp(role);
		if(val.match(re) == null){
			bootbox.alert('数量仅接受正整数或0');
		}
		$('input[name=quantity]').each(function(){
			$(this).val(val);
		});
	}else{
		$('input[name=startprice]').each(function(){
			$(this).val(val);
		});
	}
	
}
</script>
<body>
<br/>
<div class=".container" style="width:98%;margin-left:1%;">
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="piliang">
  	<?=Html::textInput('pi_quantity','',['class'=>'iv-input'])?>
  	<button class="iv-btn btn-search" onclick="dopiliang('quantity')">批量设置数量</button>
  	<?=Html::textInput('pi_price','',['class'=>'iv-input'])?>
  	<button class="iv-btn btn-search" onclick="dopiliang('price')">批量设置价格</button>
  	</div>
<table class="table table-bordered table-hover">
<tr class="active">
<th width='100px'>ItemID</th><th width='400px'>标题</th><th width="80px">SKU</th><th width="80px">数量</th><th width="80px">价格</th><th width='80px'>刊登结果</th><th>详细信息</th>
</tr>
<?php 
foreach ($items as $item){
?>
<?php if ($item->isvariation):?>
<?php $detail=EbayItemDetail::findOne(['itemid'=>$item->itemid]);?>
<?php if (count($detail->variation['Variation'])):foreach($detail->variation['Variation'] as $dv):?>
<form action="" method="post">
<tr>
<td><?php echo $item->itemid;?><?=Html::hiddenInput('itemid',$item->itemid,['id'=>'itemid'])?></td>
<td><?php echo $item->itemtitle;?>
<?php 
	if (isset($dv['VariationSpecifics']['NameValueList'])){
		echo '<strong>['.@$dv['VariationSpecifics']['NameValueList']['0']['Name'].':'.@$dv['VariationSpecifics']['NameValueList']['0']['Value']['0'].']</strong>';
	}
?>
</td>
<td><?php echo $dv['SKU'];?><?=Html::hiddenInput('sku',$dv['SKU'],['id'=>'sku'])?></td>
<td><?=Html::textInput('quantity',$dv['Quantity']-@$dv['SellingStatus']['QuantitySold'],array('size'=>8,'id'=>'quantity'))?></td>
<td>
<?php if (is_array($dv['StartPrice'])){
	$startprice = $dv['StartPrice']['Value'];
}else{
	$startprice = $dv['StartPrice'];
}?>
<?=Html::textInput('startprice',$startprice,array('size'=>8,'id'=>'startprice'))?></td>
<td class="result" tid="<?php  echo $item->id;?>"></td>
<td style="text-align:left;"></td>
</tr>
</form>
<?php endforeach;endif;?>
<?php else:?>
<form action="" method="post">
<tr>
<td><?php echo $item->itemid;?><?=Html::hiddenInput('itemid',$item->itemid,['id'=>'itemid'])?></td>
<td><?php echo $item->itemtitle;?></td>
<td><?php echo $item->sku;?><?=Html::hiddenInput('sku',$item->sku,['id'=>'sku'])?></td>
<td><?=Html::textInput('quantity',$item->quantity-$item->quantitysold,array('size'=>8,'id'=>'quantity'))?></td>
<td>
<?php $startprice = !is_null($item->startprice)?$item->startprice:$item->currentprice?>
<?=Html::textInput('startprice',$startprice,array('size'=>8,'id'=>'startprice'))?></td>
<td class="result" tid="<?php  echo $item->id;?>"></td>
<td style="text-align:left;"></td>
</tr>
</form>
<?php endif;?>
<?php 
}
?>
</table>
<div class="row">
	  <div class="col-lg-12">
	  	<input type="button" value="批量修改 " onclick="revise()">
		</div>
	</div>
</div>
</div>
</div>
</body>
</html>

