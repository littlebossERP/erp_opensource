//重置
function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
}

//高级搜索
function mutisearch(){
	var status = $('.mutisearch').is(':hidden');
	if(status == true){
		//未展开
		$('.mutisearch').show();
		$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
		return false;
	}else{
		$('.mutisearch').hide();
		$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
		return false;
	}
	
}
//展开，收缩订单商品
function spreadorder(obj,id){
	if(typeof(id)=='undefined'){
		//未传参数进入，全部展开或收缩
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.xiangqing').hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.xiangqing').show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}else{
		//有传订单ID进入，处理单个订单相应的详情
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.'+id).hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.'+id).show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}
}
$(function(){
	OrderCommon.initCustomCondtionSelect();
});
//批量修改报关信息
function edit_customs_info(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$.openModal('/delivery/order/edit-customs-info',{type:'get'},'批量修改报关信息','get');
};
//打开手动匹配运输服务窗体
function showChangeWarehouseAndShipmentMethodBox(orderIdList){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$.openModal('/order/order/show-warehouse-and-shipment-method-box',{orderIdList:orderIdList , Platform:OrderCommon.CurrentPlatform},'1','get');
}
//批量修改报关信息保存
function saveCustomsInfo(obj){
	var checkOrderIds = '';
	var tmpCustomsName = '';
	var tmpCustomsEName = '';
	var tmpCustomsDeclaredValue = '';
	var tmpCustomsweight = '';
	
	var nums = 0;
	if ($.trim($('#customsName').val()).length != 0 ) {
		tmpCustomsName = $.trim($('#customsName').val());nums++;
	}
	if ($.trim($('#customsEName').val()).length != 0 ) {
		tmpCustomsEName = $.trim($('#customsEName').val());nums++;
	}
	if ($.trim($('#customsDeclaredValue').val()).length != 0 ) {
		tmpCustomsDeclaredValue = $.trim($('#customsDeclaredValue').val());nums++;
	}
	if ($.trim($('#customsweight').val()).length != 0 ) {
		tmpCustomsweight = $.trim($('#customsweight').val());nums++;
	}
	if(nums == 0){
		$e = $.alert('没有输入任何需要修改的数据！','danger');
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
		}
	});
	
	if($("#chk_isEditToSku").is(':checked')){
		$.post(global.baseUrl + "delivery/order/edit-customs-info", {
			orders: checkOrderIds,customsName:tmpCustomsName,customsEName:tmpCustomsEName,customsDeclaredValue:tmpCustomsDeclaredValue,customsweight:tmpCustomsweight
		}, function(result) {
			$e = $.alert(result,'danger');
			$e.then(function(){
				location.reload();
			});
		});
	}else{
		$e = $.alert('批量修改成功','success');
		$e.then(function(){
			$(obj).parent().find('.modal-close').click();
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
		var delivery = ($('#deliveryNow').prop('checked'))?1:0;
		$.maskLayer(true);
		$(".ck:checked").each(function() {
			var obj = this;
			var $form = $(this).parent().parent().next(".orderInfo").find('form'),
				$message = $form.closest('.orderInfo').prev().find('.message');
			$message.html(" 执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/get-data?delivery="+delivery,
				data: $form.serialize(),
				type: 'post',
				success: function(response) {
//					$.maskLayer(false);
					var result = JSON.parse(response);
					if (result.error) {
						$message.html(result.msg);
					} else {
						obj.remove();
						$form.html('<p class="text-success text-center h6">已上传成功！<br>'+result.msg+'</p>');
						$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
//					$.maskLayer(false);
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
//					$.maskLayer(false);
					var result = JSON.parse(response);
					if (result.error) {
						$message.html(result.msg);
					} else {
						$message.html('<label class="text-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$message.html('网络不稳定.请求失败,请重试');
//					$.maskLayer(false);
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
			$message = $(this).parent().parent().find('.message');
			$id = $(obj).parent().parent().attr('data'),
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/ajax-move-order-to-upload",
				data: {
					id:$id,
				},
				type: 'post',
				success: function(response) {
//					$.maskLayer(false);
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
					$message.html('网络不稳定.请求失败,请重试');
//					$.maskLayer(false);
//					$.alertBox('网络不稳定.请求失败,请重试!');
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
		$.alertBox('<p class="text-warn">请先选择需要生成拣货单的订单！</p>');
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
		        		$.alert('错误信息：'+result.data,'danger');
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
		    							print(result.data);
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
//打印
function print(deliveryid){
//	window.open('<?//=Url::to(['/delivery/order/printpicking'])?>//'+'?deliveryid='+deliveryid);

    $.ajax({
        type: "post",
        dataType: 'json',
        url:'/delivery/order/isexistorder',
        data: {deliveryid:deliveryid},
        success: function (result) {
            if(result.message == 'true')
            {
                window.open(global.baseUrl + 'delivery/order/printpicking?deliveryid='+deliveryid);
                location.reload();
            }
            else
            {
                $.ajax({
                    type: "post",
                    dataType: 'json',
                    url:'/delivery/order/printpicking',
                    data: {deliveryid:deliveryid},
                    success: function (result) {
                        $e = $.alert(result.message,'success');
                        $e.then(function(){
//                        	location.reload();
                        });
                    }
                });
            }
        }
    });
}
function changeExcelCarrier($excelCarrierCode){
	$('select[name=carrier_code]').selectVal($excelCarrierCode);
	$('select[name=default_carrier_code]').selectVal($excelCarrierCode);
	$('#form1').submit();
}
function changeShippingService(obj){
	$('#shipmethod').selectVal($(obj).val());
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
		        dataType:'json',
		        data : {
		        	orderid:$orderid,
		        },
				url: Url,
		        success:function(result) {
//		        	var result = response;
		        	if(result.error){
		        		alert(result.data);
		        	}
		        	else{
		        		$error = '';
		        		$success = '';
		        		$shows = '<h4 class="h4">所有订单都已分配完成！</h4>';
		        		$.each(result.data , function(index , value){
		        			$error += '<p >'+index+':'+value+'</p>';
		        		});
		        		if($error.trim() != ''){
		        			$error = '<h4 class="h4">有问题订单 ： </h4>'+$error;
		        			$shows = '<h4 class="h4">其他订单都已分配完成！</h4>';
		        		}
		        		$.each(result.success , function(index , value){
		        			$success += '<p >'+index+':'+value+'</p>';
		        		});
		        		bootbox.alert('<div class="col-xs-offset-1">'+$error+$shows+$success+"</div>",function() {if($success != '') location.reload();});
		        	}
		        }
			});
		});
	}
	
};

//获取跟踪号
function getTrackNo(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行获取跟踪号操作？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var $id = $(this).parent().parent().attr('data'),
			$message = $(this).parent().parent().find('.message');
			$message.html(" 执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "carrier/carrieroperate/gettrackingnoajax",
				data: {
					id:$id,
				},
				type: 'post',
				success: function(response) {
//					$.maskLayer(false);
					var result = JSON.parse(response);
					if (result.error == 1) {
						$message.html('<label class="text-danger">' + result.msg + '</label>');
					} else if (result.error == 0) {
						$message.html('<label class="text-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$message.html('<label class="text-warn">网络不稳定.请求失败,请重试</label>');
//					$.maskLayer(false);
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
			$.alert('<label class="text-success">操作已全部完成,错误原因请查看处理结果!</label>','success');
		});
	});
};
function setFinished(){
	var allRequests = [];
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	var idstr = [];
	$('input[name^="order_id"]:checked').each(function(){
		idstr.push($(this).val());
	});
	var ignore_inventory_processing = $('input[name="ignore_inventory_processing"]:checked').val();
	var is_shipped = $('input[name="is_shipped"]:checked').val();
	//遮罩
	$.maskLayer(true);
	$.ajax({
		url: global.baseUrl + "carrier/carrierprocess/setfinished",
		data: {
			orderids:idstr,
			a:ignore_inventory_processing,
			b:is_shipped
		},
		type: 'post',
		success: function(response) {
			 var r = $.parseJSON(response);
			 if(r.result){
				 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
			 }else{
				 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
			 } 
			 event.then(function(){
			  // 确定,刷新页面
			  location.reload();
			},function(){
			  // 取消，关闭遮罩
			  $.maskLayer(false);
			});
		},
		error: function(XMLHttpRequest, textStatus) {
			$.maskLayer(false);
			$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
		}
	})
		
}

function doprint(type){
	if(type==""){
		$.alert('请选择您的打印方式','info');
		return false;
    }
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	var Url = '';
	switch(type){
	case 'api':Url = global.baseUrl + "carrier/carrierprocess/doprintapi";break;
	case 'custom':Url = global.baseUrl + "carrier/carrierprocess/doprintcustom";break;
	case 'gaofang':Url = global.baseUrl + "carrier/carrierprocess/doprint";break;
	case 'integrationlabel':Url = global.baseUrl + "carrier/carrierprocess/doprint-integration-label";break;
	}
	if(Url == ''){
		$.alertBox('<p class="text-warn">不能识别的打印方式！</p>');
		return false;
	}
	$orderids = '';
	$(".ck:checked").each(function(){
		var orderid = $(this).parent().parent().attr('data');
		$orderids += orderid + ',';
	});
	window.open(Url+'?orders='+$orderids);
	
	switch(type){
	case 'custom':
	case 'gaofang':
	case 'integrationlabel':
		var event = $.confirmBox("是否将打印的订单标记为已打印？");
		event.then(function(){
				 $.maskLayer(true);
				 $.post('/carrier/carrier/carrier-print-confirm',{orders:$orderids},function(result){
					 var event = $.alert(result,'success');
					 event.then(function(){
					  location.reload();
					},function(){
					  $.maskLayer(false);
					});
				});
			},function(){
				$.maskLayer(false);
			});
		break;
	}
};
//保存自定义标签
function saveCustomCondition(modalbox , filter_name){
	var config = $('input[name=custom_condition_config]').val();
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/carrier/carrierprocess/append-custom-condition?custom_name='+filter_name+'&config='+config, 
			data: $('#form1').serialize(),
			success: function (result) {
				if (result.success == false){
					bootbox.alert(result.message);	
					return false
				}
				modalbox.modal('hide');
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
}

//更改运输服务
function changeServerToUpload(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	
	var thisOrderList =[];
	
	$(".ck:checked").each(function() {
		$id = $(this).parent().parent().attr('data');
		thisOrderList.push($id);
	});
	
	OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList, 1);
}

//移动物流操作状态
function movestatus(val){
	if(val==""){
		bootbox.alert("请选择您的操作");return false;
    }
	
	if($('.ck:checked').length==0){
		$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
	}
	
    var idstr='';
	$('.ck:checked').each(function(){
		idstr+=','+$(this).val();
	});
	
	$.maskLayer(true);
	
	$.ajax({
		url: global.baseUrl + "carrier/default/movestep",
		data: {
			orderids:idstr,status:val
		},
		type: 'post',
		success: function(response) {
			var result = JSON.parse(response);
			if(result.Ack ==1){
				bootbox.alert(result.msg);
			}
			window.location.reload();
			$.maskLayer(false);
		},
		error: function(XMLHttpRequest, textStatus) {
			$message.html('网络不稳定.请求失败,请重试');
		}
	});
}
