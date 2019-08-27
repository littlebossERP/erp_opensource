<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
.container-fluid{
	margin-right: inherit;
    margin-left: inherit;
}
</style>


<!------------------------------ oms 2.1 左侧菜单  start  ----------------------------------------->
<?php if ($warehouseCount>1) echo $this->render('_leftmenu',['counter'=>$counter]);?>
<!------------------------------ oms 2.1 左侧菜单   end  ----------------------------------------->
<div class="content-wrapper container-fluid" >
<!---------------------------------------- oms 2.1 nav start  --------------------------------------------------->
<?php echo $order_nav_html;?>
<!---------------------------------------- oms 2.1 nav end  ------------------------------------------------------>
<div style="height:10px"></div>





<div style="font-size: 24px;font-weight:bold;font-family:'Applied Font Bold', 'Applied Font'';">缺货扫描</div>
<div style="width: 100%;height:70px;margin:20px 60px;">
	<form>
		<span style="font-size: 14px;font-weight:bold;">小老板单号 : </span><input type="text" name="dh" class="dh"  onkeyup="ajaxshow()"  placeholder="扫描取货订单配货单上的单号条码"  style="width: 500px;height:64px;font-size:24px;font-weight:bold;"/>
		<input type='text' style='display:none'/>
	</form>
</div>
<div class="message" style="font-size:18px;font-weight:bold;width:60%;height:100px;margin:100px 0;text-align:center">
	
</div>
</div>
<script>
function ajaxshow(){
	if(event.keyCode==13){
		var orderid=$('.dh').val();
		if(orderid==""){
			$.alert('未输入订单号','info');
			return false;
	    }
		var idstr = [];
		idstr.push(orderid);
			//遮罩
			 $.maskLayer(true);
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:idstr,m:'delivery',a:'缺货扫描->标记缺货'},function(result){
				//var r = $.parseJSON(result);
				 var event = $.alert(result,'success');
				 //var event = $.confirmBox(r.message);
				 event.then(function(){
				  // 确定,刷新页面
				  location.reload();
				},function(){
				  // 取消，关闭遮罩
					location.reload();
				});
				
			});
	}
}
</script>