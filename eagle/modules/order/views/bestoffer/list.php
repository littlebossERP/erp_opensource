<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\EbayBestoffer;
use yii\widgets\LinkPager;

?>
<style>
a{
	text-decoration:none;
}
a:hover{
	text-decoration:none;
} 
</style>
<!-- 搜索 -->
<div class="tracking-index col2-layout">
<?=$this->render('/order/_leftmenu',['counter'=>$counter]);?>
<div class="content-wrapper" >
<br>
<form action="" method="get" class="form-inline">
	<table class="table table-bordered ">
		<tr>
			<td>
				<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$selleruserids,['prompt'=>'卖家账号','class'=>'form-control'])?>
			</td>
			<td>
				<?=Html::textInput('buyerid',@$_REQUEST['buyerid'],['class'=>'form-control','placeholder'=>'买家账号'])?>
			</td>
			<td>
				<?=Html::dropDownList('bestofferstatus',@$_REQUEST['bestofferstatus'],EbayBestoffer::$bestofferstatus,['prompt'=>'状态','class'=>'form-control'])?>
			</td>
			<td><?=Html::label('发起时间')?>
				<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'form-control'])?><?=Html::label('至')?>
				<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'form-control'])?>
			</td>
			<td>
				<?=Html::submitButton('搜索',['class'=>'btn btn-success'])?>
			</td>
		</tr>
	</table>
</form>
<?=Html::button('同步议价',['class'=>'btn btn-success','onclick'=>"window.open(['/order/bestoffer/synclist']);"])?><br>
<table class="table table-condensed table-striped" style="font-size:12px;">
	<tr>
		<th>卖家账号</th><th>ItemID</th><th>offer时间</th><th>offer有效期</th><th>买家</th><th>bestoffer价格</th><th>数量</th><th>处理状态</th><th>offer状态</th>
		<th>最后一次还价</th><th>操作</th>
	</tr>
	<?php if (count($bestoffers)):foreach ($bestoffers as $bo):?>
	<tr>
		<td>
			<?=$bo->selleruserid?>
		</td>
		<td>
			<?=$bo->itemid?>
		</td>
		<td>
			<?=date('Y-m-d H:i:s',$bo->createtime)?>
		</td>
		<td>
			<?=$bo->bestoffer['ExpirationTime']?>
		</td>
		<td>
			<?=$bo->bestoffer['Buyer']['UserID'].'('.$bo->bestoffer['Buyer']['FeedbackScore'].')'?>
		</td>
		<td>
			<?=$bo->bestoffer['Price']?>
		</td>
		<td>
			<?=$bo->bestoffer['Quantity']?>
		</td>
		<td>
			<?=$bo->status=='0'?'未处理':'已处理'?>
		</td>
		<td>
			<?=$bo->bestofferstatus?>
		</td>
		<td>
			<?=$bo->counterofferprice?>
		</td>
		<td>
			<a href="#" onclick="javascript:doaction('<?=$bo->bestofferid?>','accpect')">接受</a>|
			<a href="#" onclick="javascript:editbestoffer('<?=$bo->bestofferid?>')">议价</a>|
			<a href="#" onclick="javascript:doaction('<?=$bo->bestofferid?>','decline')">拒绝</a>|
			<a href="#" onclick="javascript:doaction('<?=$bo->bestofferid?>','sync')">同步</a>
			<div class="dropdown hidden"><!-- hidden 修改显示样式屏蔽 -->
			  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
			    操作
			    <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
			    <li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:doaction(<?=$bo->bestofferid?>,'accpect')">接受</a></li>
			    <li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:editbestoffer(<?=$bo->bestofferid?>)">议价</a></li>
			    <li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:doaction(<?=$bo->bestofferid?>,'decline')">拒绝</a></li>
			    <li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:doaction(<?=$bo->bestofferid?>,'sync')">同步</a></li>
			  </ul>
			</div>
		</td>
	</tr>
	<?php endforeach;endif;?>
</table>
<?=LinkPager::widget(['pagination'=>$pages])?>
</div>
</div>
<!-- 议价的modal -->
<!-- 模态框（Modal） -->
<div class="modal fade" id="bestofferModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<script>
function doaction(bestofferid,act,price){
	switch(act){
		case 'accpect':
			$.post("<?=Url::to(['/order/bestoffer/ajaxresponse'])?>",{bestofferid:bestofferid,action:'Accept'},function(result){
				if(result=='success'){
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert('操作失败:'+result);
				}
			});
			break;
		case 'counter':
			if(price==0){
				$('#bestofferModal').modal('hide');
				bootbox.alert('议价的价格必须大于0');return false;
			}
			$.post("<?=Url::to(['/order/bestoffer/ajaxresponse'])?>",{bestofferid:bestofferid,action:'Counter',price:price},function(result){
				if(result=='success'){
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert('操作失败:'+result);
				}
			});
			break;
		case 'decline':
			$.post("<?=Url::to(['/order/bestoffer/ajaxresponse'])?>",{bestofferid:bestofferid,action:'Decline'},function(result){
				if(result=='success'){
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert('操作失败:'+result);
				}
			});
			break;
		case 'sync':
			$.post("<?=Url::to(['/order/bestoffer/ajaxsync'])?>",{bestofferid:bestofferid},function(result){
				if(result=='success'){
					bootbox.alert('同步已完成');
				}else{
					bootbox.alert('同步失败:'+result);
				}
			});
			break;
		default:
			break;
	}
}

function editbestoffer(bestofferid){
	var Url='<?=Url::to(['/order/bestoffer/countview'])?>';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {bestofferid : bestofferid},
		url: Url,
        success:function(response) {
        	$('#bestofferModal .modal-content').html(response);
        	$('#bestofferModal').modal('show');
        }
    });
}
</script>
