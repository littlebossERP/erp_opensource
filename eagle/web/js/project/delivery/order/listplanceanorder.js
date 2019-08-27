$(function(){
	OrderCommon.initCustomCondtionSelect();
});
//批量修改报关信息
function edit_customs_info(){
	$.maskLayer(true);
	$.get(global.baseUrl+'delivery/order/edit-customs-info',
	   function (data){
			$.maskLayer(false);
			var thisbox = bootbox.dialog({
				className: "myClass", 
				title : ("批量修改报关信息"),
			    message: data,
			    buttons:{
					Ok: {  
						label: Translator.t("保存"),  
						className: "btn-success",  
						callback: function () { 
							saveCustomsInfo( function(){
								$(thisbox).modal('hide');
							});
							return false;
						}
					}, 
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-transparent",  
						callback: function () {  
						}
					}, 
				}
			});	
	});
};

//批量修改报关信息保存
function saveCustomsInfo(callback){
	var checkOrderIds = '';
	var tmpCustomsName = '';
	var tmpCustomsEName = '';
	var tmpCustomsDeclaredValue = '';
	var tmpCustomsweight = '';
	
	var tmpCustomsextraid = '';
	
	if ($.trim($('#customsName').val()).length != 0 ) {
		tmpCustomsName = $.trim($('#customsName').val());
	}
	if ($.trim($('#customsEName').val()).length != 0 ) {
		tmpCustomsEName = $.trim($('#customsEName').val());
	}
	if ($.trim($('#customsDeclaredValue').val()).length != 0 ) {
		tmpCustomsDeclaredValue = $.trim($('#customsDeclaredValue').val());
	}
	if ($.trim($('#customsweight').val()).length != 0 ) {
		tmpCustomsweight = $.trim($('#customsweight').val());
	}
	
	if ($.trim($('#customsextra_id').val()).length != 0 ) {
		tmpCustomsextraid = $.trim($('#customsextra_id').val());
	}
	
	$(".ck").each(function(){
		if($(this).is(':checked')){
			checkOrderIds += $(this).parent().parent().find('td:eq(1)').html()+',';
			
			if(tmpCustomsName != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_cn").parent().next().val(tmpCustomsName);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='Name[]']").val(tmpCustomsName);
			}
			if(tmpCustomsEName != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_en").parent().next().val(tmpCustomsEName);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='EName[]']").val(tmpCustomsEName);
			}
			if(tmpCustomsDeclaredValue != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_declaredValue").parent().next().val(tmpCustomsDeclaredValue);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='DeclaredValue[]']").val(tmpCustomsDeclaredValue);
			}
			if(tmpCustomsweight != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_weight").parent().next().val(tmpCustomsweight);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='weight[]']").val(tmpCustomsweight);
			}
			
			if(tmpCustomsextraid != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline").find(".hasTip").next().val(tmpCustomsextraid);
			}
		}
	});
	
	if($("#chk_isEditToSku").is(':checked')){
		$.post(global.baseUrl + "delivery/order/edit-customs-info", {
			orders: checkOrderIds,customsName:tmpCustomsName,customsEName:tmpCustomsEName,customsDeclaredValue:tmpCustomsDeclaredValue,customsweight:tmpCustomsweight
		}, function(result) {
			bootbox.alert(result);
			location.reload();
		});
	}else{
		bootbox.alert({
			message:'批量修改成功',
			callback:function(){
				callback();
			}
		});
	}
}
//上传至物流商
function uploadSubmit() {
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单上传至物流商？');
	$event.then(function(){
		var allRequests = [];
		var n = 0;
		var delivery = ($('#deliveryNow').prop('checked'))?1:0;
		$.maskLayer(true);
		$(".ck:checked").each(function() {n++;
			var obj = this;
			var $form = $(this).parent().parent().next(".orderInfo").find('form'),
				$message = $form.closest('.orderInfo').prev().find('.message');
			$message.html(" 执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/get-data?delivery="+delivery,
				data: $form.serialize(),
				type: 'post',
				success: function(response) {
					$.maskLayer(false);
					var result = JSON.parse(response);
					if (result.error) {
						$message.html(result.msg);
					} else {
						$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$message.html('网络不稳定.请求失败,请重试');
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
			$.alert('<label class="text-success">操作已全部完成,错误原因请查看处理结果!</label>','success');
		});
	});
};

//交运
function dodispatch() {
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行交运操作？');
	$event.then(function(){
		var allRequests = [];
		var n = 0;
		$.maskLayer(true);
		$(".ck:checked").each(function() {
			n++;
			var obj = this;
			var $id = $(this).parent().parent().attr('data'),
			$message = $(this).parent().parent().find('.message');
			$message.html(" 执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/dodispatchajax",
				data: {
					id:$id,
				},
				type: 'post',
				success: function(response) {
					$.maskLayer(false);
					var result = JSON.parse(response);
					if (result.error) {
						$message.html(result.msg);
					} else {
						$message.html('<label class="text-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$message.html('网络不稳定.请求失败,请重试');
					$.maskLayer(false);
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
			$.alert('<label class="text-success">操作已全部完成,错误原因请查看处理结果!</label>','success');
		});
	});
};
//重新上传
function moveToUpload(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行重新上传操作？');
	$event.then(function(){
		var allRequests = [];
		var n = 0;
		var error_id = '';
		$.maskLayer(true);
		$(".ck:checked").each(function() {
			n++;
			var obj = this;
			$id = $(obj).parent().parent().attr('data'),
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/ajax-move-order-to-upload",
				data: {
					id:$id,
				},
				type: 'post',
				success: function(response) {
					if (response) {
						$tr = $(obj).parent().parent();
						$tr2 = $tr.next('.appatch');
						$tr.remove();
						$tr2.remove();
					} else {
						$id = $(obj).parent().parent().attr('data');
						error_id += $id+',\n';
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					bootbox.alert('网络不稳定.请求失败,请重试!');
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
			if(n == 0){
				bootbox.alert('请先选择需要交运的订单!');
			}
			else if(error_id != ''){
				bootbox.alert('存在修改失败订单：\n'+error_id);
			}
			else{
				bootbox.alert('操作已全部完成!');
			}
		});
	});
}
//生成拣货单
function buildDeliveryId(){
	var n = 0;
	var orderid = '';
	$(".ck:checked").each(function() {
		n++;
		$id = $(this).parent().parent().attr('data');
		orderid += $id+',';
	});
	if(n == 0){
		bootbox.alert('请先选择需要生成拣货单的订单!');
	}
	else{
		$event = $.confirmBox('确认将选中的订单进行生成拣货单操作？');
		$event.then(function(){
			$warehouseid = $('input[name=warehouseid]').val();
			var Url=global.baseUrl +'delivery/order/ajax-bulid-delivery-id';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	orderid:orderid,
		        	warehouseid:$warehouseid,
		        },
				url: Url,
		        success:function(response) {
		        	var result = JSON.parse(response);
		        	if(result.error){
		        		alert(result.data);
		        	}
		        	else{
		        		var thisbox = bootbox.dialog({
		    				className: "myClass", 
		    				title : ("生成拣货单成功"),
		    			    message: '<div class="text-center row">拣货单号：'+result.data+'</div>',
		    			    buttons:{
		    					Ok: {  
		    						label: Translator.t("打印"),  
		    						className: "btn-success",  
		    						callback: function () { 
	//	    							$(thisbox).modal('hide');
		    							return false;
		    						}
		    					}, 
		    					Cancel: {  
		    						label: Translator.t("返回"),  
		    						className: "btn-default",  
		    						callback: function () {  
		    						}
		    					}, 
		    				}
		    			});
		        	}
		        }
		    });
		});
	}
};
function changeExcelCarrier($excelCarrierCode){
	$('select[name=carrier_code]').selectVal($excelCarrierCode);
	$('#form1').submit();
}
function exportExcel($excelCarrierCode){
	if($excelCarrierCode.trim() != '')
	{
		n = 0;
		$orderid = '';
		$(".ck:checked").each(function() {
			n++;
			$id = $(this).parent().parent().attr('data');
			$orderid += $id+',';
		});
		if(n == 0){
			bootbox.alert('请先选择需要导出的订单!');
		}
		else{
			$name = $('select[name=excelCarriers]').find('option:selected').text();
			var Url=global.baseUrl +'delivery/order/export-excel-file?';
			$event = $.confirmBox('<h2 class="text-center row h2">物流：'+$name+'</h2>'+'<h4 class="text-center row h4">导出订单：'+$orderid+'</h4>');
			$event.then(function(){
				Url+= "excelCarrierCode="+$excelCarrierCode;
				Url+= "&orderid="+$orderid;
				window.open(Url);
				location.reload();
			});
		}
	}
	else{
		bootbox.alert('请先选择需要导出订单的物流!');
	}
}
function setTrackNum(){
	n = 0;
	$orderid = '';
	$(".ck:checked").each(function() {
		n++;
		$id = $(this).parent().parent().attr('data');
		$orderid += $id+',';
	});
	if(n == 0){
		bootbox.alert('请先选择需要分配跟踪号的订单!');
	}
	else{
		var Url=global.baseUrl +'delivery/order/set-track-no-to-order';
		$event = $.confirmBox('<h4 class="text-center row h4">需要分配跟踪号的订单：'+$orderid+'</h4>');
		$event.then(function(){
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	orderid:$orderid,
		        },
				url: Url,
		        success:function(response) {
		        	var result = JSON.parse(response);
		        	if(result.error){
		        		alert(result.data);
		        	}
		        	else{
		        		$error = '';
		        		$success = '';
		        		$shows = '<h4 class="h4">所有订单都已分配完成！</h4>';
		        		for(key in result.data){
		        			$error += '<p >'+key+':'+result.data[key]+'</p>';
		        		}
		        		if($error.trim() != ''){
		        			$error = '<h4 class="h4">有问题订单 ： </h4>'+$error;
		        			$shows = '<h4 class="h4">其他订单都已分配完成！</h4>';
		        		}
		        		for(key in result.success){
		        			$success += '<p >'+key+':'+result.success[key]+'</p>';
		        		}
		        		bootbox.alert('<div class="col-xs-offset-1">'+$error+$shows+$success+"</div>",function() {if($success != '') location.reload();});
		        	}
		        }
			});
		});
	}
	
};
