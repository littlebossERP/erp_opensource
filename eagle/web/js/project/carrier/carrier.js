//全选
function selectall(obj){
		if($(obj).prop('checked')==true){
			$('.order-id').prop('checked',true);
		}else{
			$('.order-id').prop('checked',false);
		}	
}
//批量操作
function doaction(val){
    if($('.order-id:checked').length==0&&val!=''){
    	$.alert('请选择要操作的订单','info');
		return false;
    }
    switch(val){
	    case 'getorderno':
			$event = $.confirmBox('确认将选中的订单上传至物流商？');
			$event.then(function(){
				var allRequests = [];
				var n = 0;
				var delivery = ($('#deliverysoon').prop('checked'))?1:0;
				$.maskLayer(true);
				$(".order-id:checked").each(function() {n++;		
					var obj = this;
					var $form = $(this).parent().parent().next(".orderInfo").find('form'),
						$message = $form.closest('.orderInfo').prev().find('.message');
					$message.html(" 执行中,请不要关闭页面！");
					allRequests.push($.ajax({
						url: global.baseUrl + "carrier/carrieroperate/get-data?delivery="+delivery,
						data: $form.serialize(),
						type: 'post',
						success: function(response) {
							var result = JSON.parse(response);
							if (result.error) {
								$message.html(result.msg);
							} else {
								$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
								createSuccessDiv(obj);
							}
						},
						error: function(XMLHttpRequest, textStatus) {
							$message.html('网络不稳定.请求失败,请重试');
						}
					}));
				});
				$.when.apply($, allRequests).then(function() {
					$.maskLayer(false);
					if(n == 0){
						bootbox.alert('请先选择需要上传的订单!');
					}
					else bootbox.alert('操作已全部完成,错误原因请查看处理结果!');
				});
			});
			break;
    	case 'dodispatch':
    		$event = $.confirmBox('确认将选中的订单进行交运操作？');
    		$event.then(function(){
    			var allRequests = [];
    			var n = 0;
    			$.maskLayer(true);
    			$(".order-id:checked").each(function() {
    				n++;
    				var obj = this;
    				var $id = $(this).parent().parent().attr('data'),
    				$message = $(this).parent().parent().find('.message');
    				$message.html(" 执行中,请不要关闭页面！");
    				allRequests.push($.ajax({
    					url: global.baseUrl + "carrier/carrieroperate/dodispatchajax",
    					data: {
    						id:$id,
    					},
    					type: 'post',
    					success: function(response) {
    						var result = JSON.parse(response);
    						if (result.error) {
    							$message.html(result.msg);
    						} else {
    							$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
    							createSuccessDiv(obj);
    						}
    					},
    					error: function(XMLHttpRequest, textStatus) {
    						$message.html('网络不稳定.请求失败,请重试');
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
    				else bootbox.alert('操作已全部完成,错误原因请查看处理结果!');
    			});
    		});
			break;
    	case 'reupload':
    		var thisOrderList =[];	
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			$.ajax({
        		type:'post',
        		url:global.baseUrl + "carrier/default/reupload",
        		data:{orderids:thisOrderList},
        		dataType:'json',
        		success:function(result){
	        			$.alert(result['msg']);
	    				location.reload();
	        		}
        		});
        	break;
    	case 'gettrackingno':
    		$event = $.confirmBox('确认将选中的订单进行交运操作？');
    		$event.then(function(){
    			var allRequests = [];
    			var n = 0;
    			$.maskLayer(true);
    			$(".order-id:checked").each(function() {
    				n++;
    				var obj = this;
    				var id = $(this).parent().parent().attr('data'),
    				$message = $(this).parent().parent().find('.message');
    				$message.html(" 执行中,请不要关闭页面！");
    				allRequests.push($.ajax({
    					url: global.baseUrl + "carrier/carrieroperate/gettrackingnoajax",
    					data: {
    						id:id,
    					},
    					type: 'post',
    					success: function(response) {
    						var result = JSON.parse(response);
    						if (result.error) {
    							$message.html(result.msg);
    						} else {
    							$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
    							createSuccessDiv(obj);
    						}
    					},
    					error: function(XMLHttpRequest, textStatus) {
    						$message.html('网络不稳定.请求失败,请重试');
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
    				else bootbox.alert('操作已全部完成,错误原因请查看处理结果!');
    			});
    		});
        	break;
    	case 'doprint':
    		var orders = "";
    		$(".order-id:checked").each(function(a, me) {
    			orders = $(this).parent().parent().attr('data');
    			ems = $(this).parent().parent().attr('ems');
    			window.open(global.baseUrl + "carrier/carrieroperate/doprint2?orders=" + orders + "&ems=" + ems);
    		})
        	break;
    	case 'completecarrier':
    		 var idstr='';
    			$('input[name="order_id[]"]:checked').each(function(){
    				idstr+=','+$(this).val();
    			});
    			$.showLoading();
    			$.post('/carrier/default/completecarrier',{orderids:idstr},function(response){
    				$.hideLoading();
    				var result = JSON.parse(response);
    				if(result.code =='fail'){
    					bootbox.alert({title:Translator.t('错误提示') , message:result.message  });	
    					return false;
    				}else{
    					bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
    						window.location.reload();
    						$.showLoading();
    					}});
    				}
    			});
			break;
        default:
            break;
    }
}
//批量修改报关信息
function edit_customs_info(){
	if($('.order-id:checked').length==0){
    	$.alert('请选择要操作的订单','info');
		return false;
    }
	$.maskLayer(true);
	$.get(global.baseUrl+'carrier/default/edit-customs-info',
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
	
	$(".ordedr-id").each(function(){
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
		$.post(global.baseUrl + "carrier/default/edit-customs-info", {
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