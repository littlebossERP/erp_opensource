/**
 * 发货模块发货订单列表页面js
 * author million 
 * 88028624@qq.com
 * 2015-03-17 
 */
$(function(){
	//重置
	$('#btn_reset').click(function(){
		debugger;
		$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
		$('select[name=keys]').selectVal('order_id');
		$('select[name=timetype]').selectVal('soldtime');
		$('select[name=ordersort]').selectVal('soldtime');
		$('select[name=ordersorttype]').selectVal('desc');
		$('select[name=sel_tag]').selectVal('0');
		$('select[name=sel_custom_condition]').unbind();
		$('select[name=sel_custom_condition]').selectVal('0');
		OrderCommon.initCustomCondtionSelect();
	});
	//显示高级搜索
	$('#show-search').click(function(){
		$('#more-search').removeClass('sr-only');
		$(this).addClass('sr-only');
		$('#hide-search').removeClass('sr-only');
		$('input[name=is_show]').val(1);
	})
	 //隐藏高级搜索
	$('#hide-search').click(function(){
		$('#more-search').addClass('sr-only');
		$(this).addClass('sr-only');
		$('#show-search').removeClass('sr-only');
		$('input[name=is_show]').val(0);
	})
	//列表全选
	$('.select-all').click(function(){
		$('.order-id').prop("checked",$(this).prop('checked'));
	})
	//批量操作
	$('.do-action').change(function(){
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
			case 'checkorder':
				document.a.action=global.baseUrl+"order/aliexpressorder/checkorderstatus";
    			document.a.submit();
    			document.a.action="";
				break;
			default:
				return false;
				break;
		}
	}).mousedown(function(){$(this).val('');});
	
	//物流操作
	$('.do-carrier').change(function(){
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
				
				/**/
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
	
	//移动订单状态到其他状态
	$('.do-move').change(function(){
		var action = $(this).val();
		if(action==''){
			return false;
		}
		var count = $('.order-id:checked').length;
		if(count == 0){
			alert('请选择订单!');
			return false;
		}
		 var idstr='';
		$('.order-id:checked').each(function(){
			idstr+=','+$(this).val();
		});
		$.post(global.baseUrl+'order/order/movestatus',{orderids:idstr,status:action},function(result){
			bootbox.alert('操作已成功');
		});
	}).mousedown(function(){$(this).val('');});
	
})