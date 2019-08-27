/**
 * 
 */
$(function(){

	//列表全选
	$('.select-all').click(function(){
		$('.order-id').prop("checked",$(this).prop('checked'));
	})
	$('.search').change(function(){
		var id_name = $(this).attr('name');
		$('#'+id_name).val($(this).val());
		document.form1.submit();
	})
	//物流操作
	$('#do-carrier').on('click',function(){
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
			case 'completecarrier':
				document.a.action=global.baseUrl+"carrier/default/completecarrier";
				document.a.submit();
				break;
			default:
				return false;
				break;
		}
	})
	
	//物流操作
	$('.do-carrier2').on('change',function(){
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
	$('.do').mousedown(function(){$(this).val('');});
	
	
	//开启关闭
	$(".onoff").on("click",function(){
		$.showLoading();
		var url = $(this).attr('url');
		$.get(url,function (data){
				$.hideLoading();
				var retinfo = eval('(' + data + ')');
				if (retinfo["code"]=="fail")  {
					bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
					return false;
				}else{
					bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
						window.location.reload();
						$.showLoading();
					}});
				}
			}
		);
	})
	//删除
	$(".del").on("click",function(){
		$.showLoading();
		var url = $(this).attr('url');
		$.get(url,function (data){
				$.hideLoading();
				var retinfo = eval('(' + data + ')');
				if (retinfo["code"]=="fail")  {
					bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
					return false;
				}else{
					bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
						window.location.reload();
						$.showLoading();
					}});
				}
			}
		);
	})
	
	$(".completeprint").on("click",function(){
		$.showLoading();
		var url = $(this).attr('url');
		$.get(url,function (data){
				$.hideLoading();
				var retinfo = eval('(' + data + ')');
				if (retinfo["code"]=="fail")  {
					bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
					return false;
				}else{
					bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
						window.location.reload();
						$.showLoading();
					}});
				}
			}
		);
	})
	
	$(".carriercancel").on("click",function(){
		$.showLoading();
		var url = $(this).attr('url');
		$.get(url,function (data){
				$.hideLoading();
				var retinfo = eval('(' + data + ')');
				if (retinfo["code"]=="fail")  {
					bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
					return false;
				}else{
					bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
						window.location.reload();
						$.showLoading();
					}});
				}
			}
		);
	})
	//开启关闭
	$(".completecarrier").on("click",function(){
		$.showLoading();
		var url = $(this).attr('url');
		$.get(url,function (data){
				$.hideLoading();
				var retinfo = eval('(' + data + ')');
				if (retinfo["code"]=="fail")  {
					bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
					return false;
				}else{
					bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
						window.location.reload();
						$.showLoading();
					}});
				}
			}
		);
	})
	
	
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
	
	$('.refreshButton').click(function(){
		var account_id = $(this).attr('carrierid');
		$.showLoading();
		$.ajax({
			url:freshUrl,
			type:'GET',
			data:'carrier_account_id='+account_id,
			success:function(result){
				$.hideLoading();
				if(result == 'success'){
					bootbox.alert({title:Translator.t('提示'),message:'更新成功，请到运输服务中查看',callback:function(){
						window.location.reload();
						$.showLoading();
					}});
				}else{
					bootbox.alert({title:Translator.t('错误提示') , message:'更新失败，请重试' });	
				}
			}
		})
	})
})