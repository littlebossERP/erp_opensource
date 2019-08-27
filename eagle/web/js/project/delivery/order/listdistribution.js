$(function(){
	$('select[name=shipmethod]').change(function(){
		$('#searchForm').submit();
	});
});
//打印配货单
function printDistribution(obj,is_not_print){
	var n = 0;
	var orderid = '';
	$(".ck:checked").each(function() {
		n++;
		$id = $(this).parent().parent().attr('data');
		orderid += $id+',';
	});
	if(n == 0){
		bootbox.alert('<div class="h4">请先选择需要打印配货单的订单!</div>');
	}
	else{
		$.maskLayer(true);
		if(is_not_print){
			var Url=global.baseUrl +'delivery/order/ajax-set-order-is-print-distribution';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	orderid:orderid,
		        },
				url: Url,
				async : false,
		        success:function(response) {
		        	$.maskLayer(false);
		        	if(!response){
		        		$shows = '<h4 class="h4">标记已打印配货单状态的过程中出错！</h4>';
		        		bootbox.alert('<div class="col-xs-offset-1">'+$shows+"</div>");
		        	}else{
//		        		$shows = '<h4 class="h4">所有订单都已经标记为：已打印配货单！</h4>'
//		        					+'<h4 class="h4">点击‘确定’按钮，打开打印页面！</h4>';
//		        		$event = $.confirmBox($shows);
//		        		$event.then(function(){
		        			window.open('/delivery/order/print-list?orderId='+orderid);
//		        		});
		        	}
		        }
			});
		}
		else{
			$.maskLayer(false);
//			$shows = '<h4 class="h4">点击‘立即打印’按钮，打开打印页面！</h4>';
//			$event = $.confirmBox($shows);
//    		$event.then(function(){
    			window.open('/delivery/order/print-list?orderId='+orderid);
//    		});
		}
		
	}
}
function moveStatusToListOut(){
	var n = 0;
	var orderid = '';
	$(".ck:checked").each(function() {
		n++;
		$id = $(this).parent().parent().attr('data');
		orderid += $id+',';
	});
	if(n == 0){
		bootbox.alert('<div class="h4">请先选择已配货完成的订单!</div>');
	}
	else{
		data = '<p class="h3">选中的订单：</p>'
			+'<div class="h4">'+orderid+'</div>';
		$event = $.confirmBox(data);
		$event.then(function(){
			$.maskLayer(true);
			var Url=global.baseUrl +'delivery/order/ajax-move-order-to-list-out';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	orderid:orderid,
		        },
				url: Url,
		        success:function(response) {
		        	var result = JSON.parse(response);
		        	$error = '';
	        		$shows = '<h4 class="h4">所有订单都已分配完成！</h4>';
		        	if(!result.success){
		        		for(key in result.data){
		        			$error += '<p >'+key+':'+result.data[key]+'</p>';
		        		}
		        		if($error.trim() != ''){
		        			$error = '<h4 class="h4">有问题订单 ： </h4>'+$error;
		        			$shows = '<h4 class="h4">其他订单都已分配完成！</h4>';
		        		}
		        	}
		        	$.maskLayer(false);
		        	bootbox.alert('<div class="col-xs-offset-1">'+$error+$shows+"</div>",function() {location.reload();});
		        }
			});
		});
	}
}