orderCommonV3 = {
	doActionList:function(val, thisOrderList, order_source){
		switch(val){
			case 'copyOrder':
				$.ajax({
					type : 'post',
					cache : 'false',
					data : {orderIDList : thisOrderList},
					url: '/order/order/copy-order',
					dataType: 'json',
					success:function(response) {
						if (response.success ){
							bootbox.alert("操作成功！",function(){
								location.reload();
							});
						}else{
							bootbox.alert(response.message);
						}
						
					}
				});
				break;
			case 'prestashopSetPaymentError':
				$.ajax({
					type : 'post',
					cache : 'false',
					data : {orderIDList : thisOrderList},
					url: '/order/prestashop-order/set-payment-error',
					dataType: 'json',
					success:function(response) {
						if (response.success ){
							bootbox.alert("操作成功！",function(){
								location.reload();
							});
						}else{
							bootbox.alert(response.message);
						}
						
					}
				});
				break;
			
			case 'ebayUpdateItemPhoto':
				$.ajax({
					type : 'post',
					cache : 'false',
					data : {orderIDList : thisOrderList},
					url: '/order/ebay-order/update-ebay-photo',
					dataType: 'json',
					success:function(response) {
						if (response.effect >0){
							bootbox.alert("成功更新"+response.effect+"张图片！",function(){
								location.reload();
							});
						}else{
							bootbox.alert("没有发现需要更新的图片！");
						}
						
					}
				});
				break;
			case 'changeWHSM':
				OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList, 0, function(){
					//不刷新界面直接修改
					$.each(thisOrderList,function(index,value){
//						console.log(value.order_id);
						
						$('input[name="order_id[]"]').each(function(){
							if(Number(value) == Number($(this).val())){
								$(this).parent().parent().next().find('.p_transport_service_unset').hide();
								
								$(this).parent().parent().next().find('.p_transport_service_set').show();
								$(this).parent().parent().next().find('.p_transport_service_set').html('运输服务：' + $('select[name="change_shipping_method_code"]').find("option:selected").text());
							}
						});
					});
				});
				break;
			case 'history':
				var url = '/order/logshow/list?orderid=';
				$.each(thisOrderList , function(index,value){
					window.open(url+value);
				});
				break;
			case 'editOrder':
				$.each(thisOrderList , function(index,value){
					OrderCommon.editOrder(value,'');
					return;
				});
				return false;
				break;
			case 'addMemo':
				orderCommonV3.showAddMemoBox(thisOrderList);
				break;
			case 'addPointOrigin':
				orderCommonV3.showAddPointOrigin(thisOrderList);
				break;
			case 'signcomplete':
				//遮罩
				$.maskLayer(true);
				$.ajax({
					url: "/order/order/signcomplete",
					data: {
						orderids:thisOrderList,
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
				});
				break;
			case 'setSyncShipComplete':
				OrderCommon.setOrderSyncShipStatusComplete(thisOrderList);
				break;
			case 'checkorder':
				var idstr = '';
				$.each(thisOrderList,function(index,value){
					idstr+=','+value;
				});
				$.post('/order/order/checkorderstatus',{orders:idstr},function(result){
					bootbox.alert(result);
					location.reload();
				});
				break;
			case 'suspendDelivery':
				$.post('/order/order/suspenddelivery',{orders:thisOrderList},function(result){
					bootbox.alert({
						buttons: {
							ok: {
								 label: '确认',  
								 className: 'btn-myStyle'  
							 }
						 },
						 message: result,  
						 callback: function() {  
							 location.reload();
						 },
					 });
				});
				break;
			case 'recovery':
				var event = $.confirmBox('确认恢复发货？');
				event.then(function(){
					$.post('/order/order/recovery',{orders:thisOrderList},function(result){
						bootbox.alert({
							buttons: {
								ok: {
									 label: '确认',  
									 className: 'btn-myStyle'  
								 }
							 },
							 message: result,  
							 callback: function() {  
								 location.reload();
							 },
						 });
					});
				});
				break;
			case 'reorder':
				OrderCommon.reorder(thisOrderList);
				break;
			case 'signshipped':
				OrderCommon.signOrdership(thisOrderList);
				break;
			case 'givefeedback':
				orderCommonV3.givefeedback(thisOrderList);
				break;
			case 'cancelorder':
				bootbox.confirm("是否确认取消订单？",function(r){
				if (!r) return;
					$.post('/order/order/cancelorder',{orders:thisOrderList},function(result){
						bootbox.alert(result,function(){
							location.reload();
						});
						
					});
				});
				
				break;
			case 'delete_manual_order':
				OrderCommon.deleteManualOrder(thisOrderList);
				break;
			case 'orderverifypass':
				OrderCommon.setOrderVerifyPass(thisOrderList);
				break;
			case 'ExternalDoprint':
				OrderCommon.ExternalDoprint(thisOrderList);
				break;
			case 'InvoiceDoprint':
				OrderCommon.InvoiceDoprint(thisOrderList);
				break;
			case 'changeItemDeclarationInfo':
				OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList, function(){
					//不刷新界面直接修改
					$.each(thisOrderList,function(index,value){
//						console.log(value.order_id);
						
						$('input[name="order_id[]"]').each(function(){
							if(Number(value) == Number($(this).val())){
								$(this).parent().parent().next().find('.p_declaration_info').hide();
							}
						});
					});
				});
				break;
			case 'stockManage':
				OrderCommon.showStockManageBox(thisOrderList);
				break;
			case 'skipMerge':
				$.post('/order/order/skipmerge',{orders:thisOrderList},function(result){
					bootbox.alert({  
						buttons: {  
							ok: {  
								 label: '确认',  
								 className: 'btn-myStyle'  
							 }  
						 },  
						 message: result,  
						 callback: function() {  
							 location.reload();
						 },  
					 });
				});
				break;
			case 'outOfStock':
				$.post('/order/order/outofstock',{orders:thisOrderList},function(result){
					bootbox.alert({  
						buttons: {  
							ok: {  
								 label: '确认',  
								 className: 'btn-myStyle'  
							 }  
						 },  
						 message: result,  
						 callback: function() {  
							 location.reload();
						 },  
					 });
				});
				break;
			case 'repulse_paid':
				var event = $.confirmBox('确认打回已付款？');
				event.then(function(){
					$.post(global.baseUrl + 'order/order/repulse-paid',{orders:thisOrderList},function(result){
						bootbox.alert({  
				            buttons: {  
				                ok: {  
				                     label: '确认',  
				                     className: 'btn-myStyle'  
				                 }  
				             },  
				             message: result,  
				             callback: function() {
				            	 location.reload();
				             },  
				         });
						
					});
				});
				break;
			case 'signwaitsend':
				var idstr = '';
				$.each(thisOrderList,function(index,value){
					idstr+=','+value;
				});
				OrderCommon.shipOrderOMS(idstr);
				break;
			case 'mergeorder':
				OrderCommon.mergeOrder(thisOrderList);
				break;
			case 'dispute':
				orderCommonV3.dispute(thisOrderList);
				break;
			case 'signpayed':
				idstr='';
				$.each(thisOrderList,function(index,value){
					idstr+=','+value;
				});
				
				if(order_source == 'ebay'){
					$.post('/order/ebay-order/signpayed',{orders:idstr},function(result){
						bootbox.alert(result);
						location.reload();
					});
				}else if(order_source == 'aliexpress'){
					$.post('/order/aliexpressorder/signpayed',{orders:idstr},function(result){
						bootbox.alert(result);
						location.reload();
					});
				}else if(order_source == 'prestashop'){
					$.post('/order/prestashop-order/signpayed',{orders:idstr},function(result){
						if (result.success ){
							bootbox.alert("操作成功！",function(){
								location.reload();
							});
						}else{
							bootbox.alert(result.message);
						}
					},"json");
				}else if(order_source == 'shopee'){
					$.post('/order/shopee-order/signpayed',{orders:idstr},function(result){
						bootbox.alert(result);
						location.reload();
					});
				}
				break;
			case 'invoiced':
				var orderid = '';
				$.each(thisOrderList,function(index,value){
//					orderid=value;
					
					if(orderid==''){
						orderid=value;
					}else{
						orderid+=','+value;
					}
				});
				window.open("/order/order/order-invoice"+"?order_id="+orderid,'_blank');
				break;
			case 'cancelMergeOrder':
				OrderCommon.cancelMergeOrder(thisOrderList);
				break;
			case 'updateImage':
				if(order_source == 'amazon'){
					$.showLoading();
					var orderid = '';
					$.each(thisOrderList,function(index,value){
						orderid=value;
					});
					var Url=global.baseUrl +'order/amazon-order/update-image';
					$.ajax({
				        type : 'post',
				        cache : 'false',
				        data : {
				        	order_id:orderid,
				        },
						url: Url,
						dataType : 'json',
				        success:function(response) {
				        	$.hideLoading();
				        	if(response.code == 200){
								$e = $.alert(response.message,'success');
				        	}else if(response.code == 400){
				        		$e = $.alert(response.message,'danger');
				        	}else{
				        		$e = $.alert('异常','danger');
				        	}
				        	$e.then(function(){
				        		if(response.code == 200){
									location.reload();
				        		}
				        	});
				        }
				    });
				}else if(order_source == 'lazada'){
					$.showLoading();
					var orderid = '';
					
					var Url = global.baseUrl +'order/lazada-order/update-image';
					
					var is_code = 200;
					var is_msg = '更新成功';
					
					$.each(thisOrderList,function(index,value){
						orderid=value;
						
						$.ajax({
					        type : 'post',
					        data : {
					        	order_id:orderid,
					        },
							url: Url,
							dataType : 'json',
					        success:function(response) {
//					        	$.hideLoading();
//					        	if(response.code == 200){
//									$e = $.alert(response.message,'success');
//					        	}else if(response.code == 400){
//					        		$e = $.alert(response.message,'danger');
//					        	}else{
//					        		$e = $.alert('异常','danger');
//					        	}
//					        	$e.then(function(){
//					        		if(response.code == 200){
//										location.reload();
//					        		}
//					        	});
					        	is_msg = response.message;
					        	
					        	if(response.code == 400){
					        		is_code = response.code;
					        		is_msg = response.message;
					        	}
					        },
					        error: function () {
//								$.hideLoading();
//								$e = $.alert('网络错误！','danger');
//								$e.then(function(){
//									location.reload();
//					 			});
					        	is_code = 600;
					        	is_msg = '网络错误！';
							 }
					    });
					});
					
					if(is_code == 200){
						$.alert(is_msg,'success');
						location.reload();
					}else if((is_code == 400) || is_code == 600){
						$e = $.alert(is_msg,'danger');
						$e.then(function(){
							location.reload();
			 			});
					}
				}else if(order_source == 'linio'){
					$.showLoading();
					var orderid = '';
					$.each(thisOrderList,function(index,value){
						orderid=value;
					});
					var Url = global.baseUrl +'order/linio-order/update-image';
					$.ajax({
				        type : 'post',
				        data : {order_id:orderid},
						url: Url,
						dataType : 'json',
				        success:function(response) {
				        	$.hideLoading();
				        	if(response.code == 200){
								$e = $.alert(response.message,'success');
				        	}else if(response.code == 400){
				        		$e = $.alert(response.message,'danger');
				        	}else{
				        		$e = $.alert('异常','danger');
				        	}
				        	$e.then(function(){
				        		if(response.code == 200){
									location.reload();
				        		}
				        	});
				        },
				        error: function () {
							$.hideLoading();
							$e = $.alert('网络错误！','danger');
							$e.then(function(){
								location.reload();
				 			});
						 }
				    });
				}else if(order_source == 'jumia'){
					$.showLoading();
					var orderid = '';
					$.each(thisOrderList,function(index,value){
						orderid=value;
					});
					var Url = global.baseUrl +'order/jumia-order/update-image';
					$.ajax({
				        type : 'post',
				        data : {order_id:orderid},
						url: Url,
						dataType : 'json',
				        success:function(response) {
				        	$.hideLoading();
				        	if(response.code == 200){
								$e = $.alert(response.message,'success');
				        	}else if(response.code == 400){
				        		$e = $.alert(response.message,'danger');
				        	}else{
				        		$e = $.alert('异常','danger');
				        	}
				        	$e.then(function(){
				        		if(response.code == 200){
									location.reload();
				        		}
				        	});
				        },
				        error: function () {
							$.hideLoading();
							$e = $.alert('网络错误！','danger');
							$e.then(function(){
								location.reload();
				 			});
						 }
				    });
				}else if(order_source == 'newegg'){
					$.showLoading();
					var orderid = '';
					var tmp_idstr = '';
					$.each(thisOrderList,function(index,value){
//						orderid=value;
						tmp_idstr += ','+value;
					});
					var Url = global.baseUrl +'order/newegg-order/update-image';
					$.ajax({
				        type : 'post',
				        data : {order_id:tmp_idstr},
						url: Url,
						dataType : 'json',
				        success:function(response) {
				        	$.hideLoading();
				        	if(response.code == 200){
								$e = $.alert(response.message,'success');
				        	}else if(response.code == 400){
				        		$e = $.alert(response.message,'danger');
				        	}else{
				        		$e = $.alert('异常','danger');
				        	}
				        	$e.then(function(){
				        		if(response.code == 200){
									location.reload();
				        		}
				        	});
				        },
				        error: function () {
							$.hideLoading();
							$e = $.alert('网络错误！','danger');
							$e.then(function(){
								location.reload();
				 			});
						 }
				    });
				}
				else if(order_source == 'cdiscount'){
					$.showLoading();
					var orderid = '';
					var tmp_idstr = '';
					$.each(thisOrderList,function(index,value){
//						orderid=value;
						tmp_idstr += ','+value;
					});
					var Url = global.baseUrl +'order/order/del-item-img-cacher';
					$.ajax({
				        type : 'post',
				        data : {'order_ids':tmp_idstr},
						url: Url,
						dataType : 'json',
				        success:function(response) {
				        	$.hideLoading();
				        	if(response.success){
								$e = $.alert('操作成功','success');
				        	}else {
				        		$e = $.alert(response.message,'danger');
				        	}
				        	
				        	$e.then(function(){
				        		if(response.success){
									location.reload();
				        		}
				        	});
				        },
				        error: function () {
							$.hideLoading();
							$e = $.alert('网络错误！','danger');
						 }
				    });
				}
				break;
			case 'extendsBuyerAcceptGoodsTime':
				//if(order_source == 'aliexpress'){        //速卖通特有，不需加标志，lrq20171006
					$.ajax({
						type: "GET",
							dataType: 'html',
							url:'/order/aliexpressorder/show-extends-buyer-accept-goods-time-box', 
							data: {orderIdList : thisOrderList},
							success: function (result) {
								bootbox.dialog({
									title: Translator.t("延长买家收货时间"),
									className: "order_info", 
									message: result,
									buttons:{
										Ok: {  
											label: Translator.t("确定"),  
											className: "btn-primary",  
											callback: function () { 
												orderCommonV3.ExtendsBuyerAcceptGoodsTimeOMS();
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
							},
							error: function(){
								bootbox.alert("Internal Error");
								return false;
							}
					});
				//}
				break;
			case 'updateShipping':
				if(order_source == 'lazada'){
					$.showLoading();
					var orderid = '';
					$.each(thisOrderList,function(index,value){
						orderid=value;
					});
					$.ajax({
						url:global.baseUrl +'order/lazada-order/update-lazada-shipping',
						type:'post',
						data:{order_id:orderid},
						dataType:'json',
						success:function(res){
							$.hideLoading();
							if(res.code == 200){
								$e = $.alert(res.message,'success');
							}
							else if(res.code == 400){
								if(res.data.length>0){
									$e = $.alert(res.data.join(','),'danger');
								}else{
									$e = $.alert(res.message,'danger');
								}
							}else{
								$e = $.alert('失败','danger');
							}
							$e.then(function(){
								if(res.code == 200){
									location.reload();
								}
							});
						},
						 error: function () {
							$.hideLoading();
							$e = $.alert('网络错误！','danger');
							$e.then(function(){
								location.reload();
				 			});
						 }
					});
				}else if(order_source == 'linio'){
					$.showLoading();
					var orderid = '';
					$.each(thisOrderList,function(index,value){
						orderid=value;
					});
					$.ajax({
						url:global.baseUrl +'order/linio-order/update-linio-shipping',
						type:'post',
						data:{order_id:orderid},
						dataType:'json',
						success:function(res){
							$.hideLoading();
							if(res.code == 200){
								$e = $.alert(res.message,'success');
							}
							else if(res.code == 400){
								if(res.data.length>0){
									$e = $.alert(res.data.join(','),'danger');
								}else{
									$e = $.alert(res.message,'danger');
								}
							}else{
								$e = $.alert('失败','danger');
							}
							$e.then(function(){
								if(res.code == 200){
									location.reload();
								}
							});
						},
						error: function () {
							$.hideLoading();
							$e = $.alert('网络错误！','danger');
							$e.then(function(){
								location.reload();
				 			});
						 }
					});
				}
				break;
			case 'split_order':
				OrderCommon.splitOrder(thisOrderList);
				break;
			case 'split_order_cancel':
				OrderCommon.splitOrderCancel(thisOrderList);
				break;
			default:
				return false;
				break;
		}
	},
	
	doactionone:function(val, orderid, order_source){
		var thisOrderList = [];
		thisOrderList.push(orderid);
		
		if (thisOrderList.length ==0) {
			bootbox.alert(Translator.t('请选择订单'));
			return;
		}
		
		orderCommonV3.doActionList(val, thisOrderList, order_source);
	},
	
	doactionone2:function(obj, orderid, order_source){
		var val = $(obj).val();
		if (val != ''){
			orderCommonV3.doactionone(val, orderid, order_source);
			$(obj).val('');
		}
	},
	
	doaction:function(val, order_source){
		var thisOrderList =[];
		switch(val){
			
			case 'changeWHSM':
				var thisOrderList =[];
				
				$('input[name="order_id[]"]:checked').each(function(){
//					if ($(this).parents("tr:contains('已付款')").length == 0) return;
					if($(this).parents().next().find('.xlb_status').text() != '已付款') return ;
					thisOrderList.push($(this).val());
				});
				break;
			case 'changeshipmethod':
				var thisOrderList =[];
				
				$('input[name="order_id[]"]:checked').each(function(){
//					if ($(this).parents("tr:contains('已付款')").length == 0) return;
					if($(this).parents().next().find('.xlb_status').text() != '已付款') return ;
					thisOrderList.push($(this).val());
				});
				break;
			case 'calculat_profit':
				idstr='';
				$('input[name="order_id[]"]:checked').each(function(){
					idstr+=','+$(this).val();
				});
				$.showLoading();
				$.ajax({
					type: "POST",
						//dataType: 'json',
						url:'/order/order/profit-order', 
						data: {order_ids : idstr},
						success: function (result) {
							$.hideLoading();
							bootbox.dialog({
								className : "profit-order",
								//title: ''
								message: result,
							});
						},
						error: function(){
							$.hideLoading();
							bootbox.alert("出现错误，请联系客服求助...");
							return false;
						}
				});
				return ;
				break;
			case 'mergeorder':
				OrderCommon.MergeAllOrderByGroup();
				/*
				document.a.target="_blank";
				document.a.action="/order/order/mergeorder";
				document.a.submit();
				document.a.action="";
				*/
				return ;
				break;
				
			default:
				$('input[name="order_id[]"]:checked').each(function(){
					thisOrderList.push($(this).val());
				});
			break;
		}
		
		if (thisOrderList.length ==0) {
			bootbox.alert(Translator.t('请选择订单'));
			return ;
		}
		
		orderCommonV3.doActionList(val,thisOrderList, order_source);
	},
	
	doaction2:function(obj, order_source){
		var val = $(obj).val();
		if (val != ''){
			orderCommonV3.doaction(val, order_source);
			$(obj).val('');
		}
	},
	
	doaction3:function(val, order_source){
		if (val != ''){
			orderCommonV3.doaction(val, order_source);
		}
	},
	
	exportorder:function (type){
		var thisOrderList =[];
		$('input[name="order_id[]"]:checked').each(function(){
			thisOrderList.push($(this).val());
		});
		
		if (thisOrderList.length ==0) {
			bootbox.alert(Translator.t('请选择订单'));
			return;
		}
		
		OrderCommon.exportorder2(type,thisOrderList);
	},
	
	sendmessage:function(orderid, order_source){
		if(order_source == 'ebay'){
			var Url='/order/ebay-order/sendmessage';
			
			$.ajax({
				type : 'post',
				cache : 'false',
				data : {orderid : orderid},
				url: Url,
				success:function(response) {
					$('#myMessage .modal-content').html(response);
					$('#myMessage').modal('show');
				}
			});
		}
	},
	
	selected_switch:function(obj){
		var selected = 0;
		var selected_profited = 0;
		var profitToalt = 0;
		
		$('input[name="order_id[]"]:checked').each(function(){
			selected++;
			//console.log($(this).parents("tr").find(".profit_detail")[0]);
			var profit = $(this).parents("tr").find(".profit_detail_v3").attr('data-profit');
			//console.log(profit);
			
			if(typeof(profit)!=='undefined' && profit!==''){
				profit = parseFloat(profit);
				selected_profited++;
				profitToalt += profit;
			}
		});
		$("#selected_profit_calculated_rate").val(selected_profited +'/'+selected);
		profitToalt = Math.round(profitToalt*Math.pow(10,2))/Math.pow(10,2);
		$("#selected_profit_total").val(profitToalt);
		
//		alert(selected);
		if(selected > 0){
			$('.table_list_v3').find('#showSelChboxNum').find('td').text('已选中'+selected+'条数据');
			$('.table_list_v3').find('#showSelChboxNum').show();
		}else{
			$('.table_list_v3').find('#showSelChboxNum').hide();
		}
	},
	
	givefeedback:function(orderIdList){
		$.showLoading();
		$.post('/order/ebay-order/feedback',{order_id:orderIdList},
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("买家评价"),
					className: "order_info", 
					message: data,
					
				});	
		});
	},
	
	dispute:function(thisOrderList){
		var url = '/order/ebay-order/dispute';
		var tempForm = document.createElement("form"); 
		tempForm.id="tempForm1"; 
		tempForm.method="post"; 
		tempForm.target="_blank";
		tempForm.action=url; 
		
		$.each(thisOrderList,function(index,value){
			var hideInput = document.createElement("input"); 
			hideInput.type="hidden"; 
			hideInput.name= "order_id[]" 
			hideInput.value= value; 
			tempForm.appendChild(hideInput); 
		});
		
		document.body.appendChild(tempForm); 
		tempForm.submit(); 
		document.body.removeChild(tempForm); 
	},
	
	dosearch:function(name,val){
		$('#'+name).val(val);
//		$('form:first').submit();
		document.form1.submit();
	},
	
	ExtendsBuyerAcceptGoodsTimeOMS:function(){
		var extenddataList = [];
		$('[name=extend_days]').each(function(){
			tmpextenddata = {'order_id' :$(this).data('order-id') , 'extendday':$(this).val()  , 'selleruserid':$(this).data('selleruserid') }; 
			extenddataList.push(tmpextenddata);
		});

		if (extenddataList.length == 0){
			bootbox.alert('找不到订单');
			return ;
		}
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/order/aliexpressorder/extends-buyer-accept-goods-time', 
				data: {extenddataList : extenddataList},
				success: function (result){
					if (result.success == false){
						bootbox.alert(result.message);
					}else{
						bootbox.alert('操作成功');
					}
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	//添加备注
	showAddMemoBox:function(orderIdList){
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/order/order/show-add-memo-box', 
				data: {orderIdList : orderIdList },
				success: function (result) {
					bootbox.dialog({
						title: Translator.t("添加备注"),
						className: "order_info", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () {
									return orderCommonV3.batchSaveOrderDesc();
								}
							}, 
							Cancel: {  
								label: Translator.t("关闭"),  
								className: "btn-default",  
								callback: function () {  
								}
							}, 
						}
					});	
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},


	
	//添加备注 - 批量保存备注
	batchSaveOrderDesc:function(){
		var orderList = [];
		$("textarea[name=order_memo]").each(function(){
			$('.xiangqing.'+$(this).data('order-id')).find('font[color="red"]').html($(this).val());
			orderList.push({"order_id":$(this).data('order-id'),"memo":$(this).val()});
		});
		
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/order/batch-save-order-desc', 
			data: {orderList : orderList},
			success: function (result) {
				var  tmpMsg ;
				if (result.message){
					tmpMsg = result.message;
				}else{
					tmpMsg = '保存成功！';
					
					//不刷新界面直接修改
					$.each(orderList,function(index,value){
//						console.log(value.order_id);
						
						$('input[name="order_id[]"]').each(function(){
							if(Number(value.order_id) == Number($(this).val())){
								if(value.memo == ''){
									$(this).parent().find('.label_memo_custom').parent().hide();
									$(this).parent().parent().next().find('.div_item_momo').hide();
								}else{
//									$(this).parent().find('.label_memo_custom').attr('data-content', value.memo);
									$(this).parent().find('.label_memo_custom').parent().show();
									
									$(this).parent().parent().next().find('.div_item_momo').html('订单备注：'+value.memo);
									$(this).parent().parent().next().find('.div_item_momo').show();
								}
							}
						});
					});
				}
				
				OrderCommon.SuccessBox(tmpMsg,'order_info');
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},


	//添加发货地
	showAddPointOrigin:function(orderIdList){
		$.ajax({
			type: "GET",
			dataType: 'html',
			url:'/order/order/show-add-point-origin',
			data: {orderIdList : orderIdList },
			success: function (result) {
				bootbox.dialog({
					title: Translator.t("添加发货地"),
					className: "order_info",
					message: result,
					buttons:{
						Ok: {
							label: Translator.t("保存"),
							className: "btn-success",
							callback: function () {
								return orderCommonV3.batchSaveOrderPointOrigin();
							}
						},
						Cancel: {
							label: Translator.t("关闭"),
							className: "btn-default",
							callback: function () {
							}
						},
					}
				});
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},

	//添加备注 - 批量保存备注
	batchSaveOrderPointOrigin:function(){
		var orderList = [];
		$("select[name=select_country]").each(function(){
			
			$('.xiangqing.'+$(this).data('order-id')).find('font[color="red"]').html($(this).val());
			orderList.push({"order_id":$(this).data('order-id'),"select_country":$(this).val()});
		});

		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/order/batch-save-order-point-origin',
			data: {orderList : orderList},
			success: function (result) {
				var  tmpMsg ;
				if (result.message){
					tmpMsg = result.message;
				}else{
					tmpMsg = '保存成功！';

					//不刷新界面直接修改
					$.each(orderList,function(index,value){
//						console.log(value.order_id);

						$('input[name="order_id[]"]').each(function(){
							if(Number(value.order_id) == Number($(this).val())){
								if(value.memo == ''){
									$(this).parent().find('.label_memo_custom').parent().hide();
									$(this).parent().parent().next().find('.div_item_momo').hide();
								}else{
//									$(this).parent().find('.label_memo_custom').attr('data-content', value.memo);
									$(this).parent().find('.label_memo_custom').parent().show();

									$(this).parent().parent().next().find('.div_item_momo').html('订单备注：'+value.memo);
									$(this).parent().parent().next().find('.div_item_momo').show();
								}
							}
						});
					});
				}

				OrderCommon.SuccessBox(tmpMsg,'order_info');
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},
	
	//priceminister 平台用到的立即更新
	syncOneOrderStatus:function(order_id){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/order/priceminister-order/sync-one-order-status?order_id='+order_id,
			dataType:'json',
			success: function (result) {
				$.hideLoading();
				if(result.success===true){
					bootbox.alert({
			            message: '操作成功',  
			            callback: function() {  
			            	window.location.reload();
			            }
					});
				}else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('操作失败,后台返回异常'));
				return false;
			}
		});
	},
	
	// 可以待合并订单用加粗border 框起来
	showMergeRow:function(){
		var rowArr = [];
		$('table tr[merge_row_tag_v3]').each(function(){
			if($.inArray($(this).attr('merge_row_tag_v3'),rowArr) == -1)
				rowArr.push($(this).attr('merge_row_tag_v3'));
		});
		
		for(var i=0;i<rowArr.length;i++){
			var rowSelect = 'table tr[merge_row_tag_v3="'+rowArr[i]+'"]';
			
			$(rowSelect+':first').append('<td rowspan="'+$(rowSelect).length+'" style="text-align: center;vertical-align: middle;">'
					+'<button type="button" class="iv-btn btn-important" style="width: 78px;" onclick="orderCommonV3.mergeSameRowOrder(\''+rowArr[i]+'\')">合并</button>'
					+'</td>');
			
			$(rowSelect).css('border','2px solid #e68a00');
			$(rowSelect).css('border-width','0px 2px 0px 2px');
			$(rowSelect+':first').css('border-width','2px 2px 0px 2px');
			$(rowSelect+':last').css('border-width','0px 2px 2px 2px');
			$(rowSelect+':first td:last').css('border-bottom','2px solid #e68a00');// 合并按钮底部border
		}
	},
	
	// 合并该行订单
	mergeSameRowOrder:function(rowMd5){
		if(rowMd5 =='' || $('tr[merge_row_tag_v3="'+rowMd5+'"] .ck_v3:checked').length <= 1){
	    	bootbox.alert("请选择两个或以上订单进行合并");
	    	return false;
	    }
		
		var toMergeOrderIds = [];
		$('tr[merge_row_tag_v3="'+rowMd5+'"] .ck_v3:checked').each(function(){
			toMergeOrderIds.push($(this).val());
		});
		
		OrderCommon.mergeOrder(toMergeOrderIds);
	},
}

$("#ck_all_v3").click(function(){
	if($(this).prop("checked")==true){
		$(".ck_v3").prop("checked",true);
	}else{
		$(".ck_v3").prop("checked",false);
	}
});
