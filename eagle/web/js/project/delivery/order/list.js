/**
 * 发货模块发货订单列表页面js
 * author million 
 * 88028624@qq.com
 * 2015-03-17 
 */
$(function(){
	//列表全选
	$('.select-all').click(function(){
		$('.order-id').prop("checked",$(this).prop('checked'));
	})
	//批量操作
	$('.do').change(function(){
		var action = $(this).val();
		if(action==''){
			return false;
		}
		var count = $('.order-id:checked').length;
		if(count == 0){
			alert('请选择订单!');
			return false;
		}
		switch(action){
			case 'confirmdelivery':
				//document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/order/confirm-delivery";
    			document.a.submit();
    			document.a.action="";
				break;
			case 'completepicking':
				//document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/order/complete-picking";
    			document.a.submit();
    			document.a.action="";
				break;
			case 'completedistribution':
				//document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/order/complete-distribution";
    			document.a.submit();
    			document.a.action="";
				break;
			default:
				return false;
				break;
		}
	}).mousedown(function(){$(this).val('');});
	
	//打印
	$('.do_print').change(function(){
		var action = $(this).val();
		if(action==''){
			return false;
		}
		var count = $('.order-id:checked').length;
		if(count == 0){
			alert('请选择订单!');
			return false;
		}
		switch(action){
			case 'picking':
				document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/print/picking";
    			document.a.submit();
    			document.a.action="";
				break;
			case 'picking2':
				document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/print/picking2";
    			document.a.submit();
    			document.a.action="";
				break;
			case 'distribution':
				document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/print/distribution";
    			document.a.submit();
    			document.a.action="";
				break;
			case 'carrier':
				document.a.target="_blank";
    			document.a.action=global.baseUrl+"delivery/print/distribution";
    			document.a.submit();
    			document.a.action="";
				break;
			default:
				return false;
				break;
		}
	}).mousedown(function(){$(this).val('');});

	//物流操作
	$('.do-carrier').on('change',function(){
		var action = $(this).val();
		if(action==''){
			return false;
		}
		var count = $('.order-id:checked').length;
		if(count == 0){
			alert('请选择订单!');
			return false;
		}
		switch(action){
			case 'getorderno':
				document.a.target="_blank";
				document.a.action=global.baseUrl+"carrier/carrierprocess/waitingpost";
				document.a.submit();
				document.a.action="";
				break;
			case 'dodispatch':
				document.a.target="_blank";
				document.a.action=global.baseUrl+"carrier/carrieroperate/dodispatch";
				document.a.submit();
				document.a.action="";
				break;
			case 'gettrackingno':
				document.a.target="_blank";
				document.a.action=global.baseUrl+"carrier/carrieroperate/gettrackingno";
				document.a.submit();
				document.a.action="";
				break;
			case 'doprint':
				document.a.target="_blank";
				document.a.action=global.baseUrl+"carrier/carrieroperate/doprint";
				document.a.submit();
				document.a.action="";
				break;
			case 'cancelorderno':
				document.a.target="_blank";
				document.a.action=global.baseUrl+"carrier/carrieroperate/cancelorderno";
				document.a.submit();
				document.a.action="";
				break;
			case 'recreate':
				document.a.target="_blank";
				document.a.action=global.baseUrl+"carrier/carrieroperate/recreate";
				document.a.submit();
				document.a.action="";
				break;
			default:
				return false;
				break;
		}
	}).mousedown(function(){$(this).val('');});
	
	//切换仓库
	$('.warehouse').click(function(){
		var warehouse_id = $(this).attr('warehouse-id');
		location.href = global.baseUrl+"delivery/order/waiting-picking?warehouse_id="+warehouse_id;
	});
	//加载订单信息
	$(".order-info").each(function(){
		var id = $(this).text();
		$(this).qtip({ 
			content: {
				text: $("#div_more_info_"+id),
				
			},
			style: {
	            classes: 'qtip qtip-default basic-qtip nopadding',
	            width:'600px'
	        },
	        
		});  
	})
});

//取消发货
function cancelpicking(orderid){
	$.post(global.baseUrl+"delivery/order/ajaxcancelpicking",{orderid:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			//window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}

//单订单进行发货处理
function dopicking(orderid,warehouseid){
	$.post(global.baseUrl+"delivery/order/ajaxdopicking",{orderid:orderid,warehouseid:warehouseid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}
//批量进行发货处理
function mutidopicking(warehouseid){
	var count = $('.order-id:checked').length;
	if(count == 0){
		alert('请选择订单!');
		return false;
	}
	var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.post(global.baseUrl+"delivery/order/ajaxdopicking",{orderid:idstr,warehouseid:warehouseid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}

//取消拣货单
function cancelpickingorder(orderid){
	$.post(global.baseUrl+"delivery/order/ajaxcancelpickingorder",{id:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}

//单拣货单拣货完成
function dopickingorder(orderid){
	$.post(global.baseUrl+"delivery/order/ajaxdopickingorder",{id:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}
//批量拣货单完成
function mutidopickingorder(){
	var count = $('.order-id:checked').length;
	if(count == 0){
		alert('请选择订单!');
		return false;
	}
	var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.post(global.baseUrl+"delivery/order/ajaxdopickingorder",{id:idstr},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}

//单配货单配货完成
function dopeihuoorder(orderid){
	$.post(global.baseUrl+"delivery/order/ajaxdopeihuoorder",{id:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}

//批量配货单完成
function mutidopeihuoorder(){
	var count = $('.order-id:checked').length;
	if(count == 0){
		alert('请选择订单!');
		return false;
	}
	var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.post(global.baseUrl+"delivery/order/ajaxdopeihuoorder",{id:idstr},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert(result);
		}
	});
}