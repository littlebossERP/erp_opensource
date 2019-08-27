/**
 +------------------------------------------------------------------------------
 *全部查询界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
//if (typeof OrderCommon === 'undefined')  OrderCommon = new Object();
OrderCommon = {
	CurrentPlatform:'',
	overseaList:{},
	delDetailIdList:[],
	batchMergeOrderIdList:{},
	
	ExternalDoprint:function(orderidList, externalV){
		if (externalV == undefined){
			externalV = '';
		}
		
		if(orderidList.length==0&&type!=''){
			bootbox.alert("请选择要操作的订单");return false;
		}
		idstr='';
		$.each(orderidList,function(index, value){
			if (idstr ==''){
				idstr+= value;
			}else{
				idstr+=','+value;
			}
			
		});
		
		$.ajax({
			type: "POST",
			url: '/carrier/carrierprocess/check-cainiao',
			data: {"order_ids":idstr},
			dataType: "json",
			async:false,
			success: function (data) {
				if(data.code == 0){
					bootbox.alert(data.msg);
					return false;
				} else if(data.code == 1){
					window.open('/carrier/carrierprocess/external-doprint'+'?order_ids='+idstr+'&externalV='+externalV);
					return true;
				} else if(data.code == 2){
					// 检测是否安装
					checkCainiaoPlug();
					
					// 显示打印列表
					$("#cainiaoPrePrintBtn").attr("onclick","doCainiaoPrint(1,'"+ idstr +"');");
					$("#cainiaoPrintBtn").attr("onclick","doCainiaoPrint(0,'"+ idstr +"');");
					showCainiaoPrinters();
					
					return false;
				}
			},
			error: function () {
				bootbox.alert('网络异常，请联系小老板客服!');
			}
		});
	},
	
	InvoiceDoprint:function(orderidList, externalV){
		if (externalV == undefined){
			externalV = '';
		}
		
		if(orderidList.length==0&&type!=''){
			bootbox.alert("请选择要操作的订单");return false;
		}
		idstr='';
		$.each(orderidList,function(index, value){
			if (idstr ==''){
				idstr+= value;
			}else{
				idstr+=','+value;
			}
			
		});
		window.open('/carrier/carrierprocess/invoice-doprint'+'?order_ids='+idstr);
		return true;
	},
	
	importTrackNoBox:function(){
		var tmpPlatform = arguments[0] ? arguments[0] : '';
		
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/order/order/import-trackno-box', 
				success: function (result) {
					//bootbox.alert(result.message);
					bootbox.dialog({
						title: Translator.t("导入物流单号"),
						className: "order_info", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () { 
									if ($('[name=importTrackNO_autoShip]').prop('checked')){
										var autoship = '1'
									}else{
										var autoship = '';
									}
									
									
									if ($('[name=importTrackNO_autoComplete]').prop('checked')){
										var autoComplete = '1'
									}else{
										var autoComplete = '';
									}
									
									return OrderCommon.importTrackNo(tmpPlatform , autoship , autoComplete);
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
	
	importTrackNo:function(tmpPlatform, autoship , autoComplete){
		if($("#order_tracknum").val()){
		$.ajaxFileUpload({  
			 url:'/order/order/importordertracknum?paltform='+tmpPlatform+'&autoship='+autoship+'&autoComplete='+autoComplete,
		     fileElementId:'order_tracknum',
		     type:'post',
		     dataType:'json',
		     success: function (result){
			     if(result.ack=='failure'){
					bootbox.alert(result.message);
				 }else{
					bootbox.alert('操作已成功');
				 }
		     },  
			 error: function ( xhr , status , messages ){
				 bootbox.alert(messages);
		     }  
		});  
	}else{
		bootbox.alert("请添加文件");
	}
	},
	
	DeliveryBatchOperation:function(obj){
		var thisOrderList =[];
			
		$('input[name="order_id[]"]:checked').each(function(){
			thisOrderList.push($(this).val());
		});
		
		//传进来的obj有可能不是对象而只是一个val所以这里需要做一下判断
		var tmpObjVal = obj.value;
		if(tmpObjVal == undefined){
			tmpObjVal = obj;
		}
		
		OrderCommon.commonOperation(thisOrderList,tmpObjVal);
	},
	
	commonOperation:function(orderidList,type){
		if(type==""){
			bootbox.alert("请选择您的操作");return false;
		}
		
		if(orderidList.length==0&&type!=''){
			bootbox.alert("请选择要操作的订单");return false;
		}
		
		switch(type){
			case 'ExportPurchaseImportExcel':
				idstr='';
				$.each(orderidList,function(index, value){
					idstr+=','+value;
				});
				window.open('/order/excel/export-purchase-import-excel'+'?orderids='+idstr);
				break;
			case 'ExportProductImportExcel':
				idstr='';
				$.each(orderidList,function(index, value){
					idstr+=','+value;
				});
				window.open('/order/excel/export-product-import-excel'+'?orderids='+idstr);
				break;
			
			case 'ExportTrackNoImportExcel':
				idstr='';
				$.each(orderidList,function(index, value){
					idstr+=','+value;
				});
				window.open('/order/excel/export-track-no-import-excel'+'?orderids='+idstr);
				break;
			case 'export_instock':
				idstr='';
				$.each(orderidList,function(index, value){
					idstr+=','+value;
				});
				window.open('/order/excel/export-instock-excel'+'?orderids='+idstr);
				break;
			case 'exportEubExcel':
				idstr='';
				$.each(orderidList,function(index, value){
					idstr+=','+value;
				});
				window.open('/order/excel/export-carrier-import-excel'+'?orderids='+idstr+'&type='+type);
				break;
			case 'signshipped':
				document.a.target="_blank";
				document.a.action="/order/order/signshipped";
				document.a.submit();
				document.a.action="";
				break;
			case 'changeWHSM':

				OrderCommon.showWarehouseAndShipmentMethodBox(orderidList);
				
				break;
			
			case 'signcomplete':
				OrderCommon.signcomplete(orderidList);
				break;
			
			case 'outOfStock':
				OrderCommon.outOfStock(orderidList);
				
				break;
			case 'suspendDelivery':
				OrderCommon.suspendDelivery(orderidList);
				break;
			case 'recovery':
				OrderCommon.recovery(orderidList);
				break;
		}
	},
	
	signcomplete:function(orderidList){
		//遮罩
		$.maskLayer(true);
		$.ajax({
			url: "/order/order/signcomplete",
			data: {
				orderids:orderidList,
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
	},
	
	suspendDelivery:function(orderidList){
		$.post('/order/order/suspenddelivery',{orders:orderidList},function(result){
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
		
	},
	
	outOfStock:function(orderidList){
		$.post('/order/order/outofstock',{orders:orderidList},function(result){
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
	},
	
	recovery:function(orderidList){
		var event = $.confirmBox('确认恢复发货？');
		event.then(function(){
			$.post('/order/order/recovery',{orders:orderidList},function(result){
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
	},
	
	showChangeItemDeclarationInfoBox:function(orderIdList , callback){
//		var isRefresh = (typeof(arguments[1])=="undefined")?'0':arguments[1];//isRefresh 的默认值
		var handle= $.openModal('/order/order/show-change-item-declaration-info-box',{orderIdList:orderIdList},'修改报关信息','GET');
		
		handle.done(function(winobj){
			 // 窗口载入完毕事件
			//winobj.find("input[type=date]").datepicker();    // 渲染日历选择框
			$.initQtip();
			winobj.find('a[name=ApplyToAll]').on('click',function(){
				
				var nameCN = $(this).parents('tr').find('[name="nameCN[]"]').val();
				var nameEN = $(this).parents('tr').find('[name="nameEN[]"]').val();
				var weight = $(this).parents('tr').find('[name="weight[]"]').val();
				var price = $(this).parents('tr').find('[name="price[]"]').val();
				var code = $(this).parents('tr').find('[name="code[]"]').val();
				
				winobj.find('input[name="nameCN[]"]').val(nameCN);
				winobj.find('input[name="nameEN[]"]').val(nameEN);
				winobj.find('input[name="weight[]"]').val(weight);
				winobj.find('input[name="price[]"]').val(price);
				winobj.find('input[name="code[]"]').val(code);
				
			});
			winobj.find("#btn_ok").on('click',function(){
				var selectedValues = [];    
				winobj.find('select[name="influencescope[]"]').each(function(){
				     if($(this).val()==1){
				    	 alert('相同SKU影响范围是"已付款与新订单"时,使用第一个为准');
				    	 return false;
				     }
				});
				
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/order/order/batch-save-declaration-info', 
					data: $('form[name=frm_declaration]').serialize(),
					success: function (result) {
						var  tmpMsg ;
						if (result.success == true){
							$.alertBox('<p class="text-success" style="font-size:24px;">修改成功</p>');
							winobj.close();
							if (typeof callback != 'undefined'){
								callback();
							}
						}else{
							tmpMsg = result.message;
//							if (typeof callback == 'undefined'){
								$.alert(tmpMsg);
//							}	
						}		
						return true;
					},
					error: function(){
						bootbox.alert("Internal Error");
						return false;
					}
				});
				
				
			 });
			winobj.find("#btn_cancel").on('click',function(){
				winobj.close();       // 关闭当前模态框
			 });
		});
		
	},
	//获取跟踪号
	getTrackNo:function(){
		ck_obj = $('.ck:checked');
		OrderCommon._getTrackNo(ck_obj);
		
	},
	_getTrackNo:function(ck_obj){
		if(ck_obj.length==0){
			$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
			return false;
		}
		$event = $.confirmBox('确认将选中的订单进行获取跟踪号操作？');
		$event.then(function(){
			var allRequests = [];
			$.maskLayer(true);
			//$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
			result_msg = "";
			ck_obj.each(function() {
				var obj = this;
				var $id = $(this).val();
				if ($(this).data('health') == 'noshipment'){
					
					
					result_msg += '<p class="text-danger" style="margin-bottom: 5px;">'+$id+'没有物流商信息</p>';
					return;
				}
				var obj = this;
				var $id = $(this).val();
				$message = $(this).parent().parent().find('.message');
				//$message.html(" 执行中,请不要关闭页面！");
				allRequests.push($.ajax({
					url: global.baseUrl + "carrier/carrieroperate/gettrackingnoajax",
					data: {
						id:$id,
					},
					type: 'post',
					success: function(response) {
						$.maskLayer(false);
						var result = JSON.parse(response);
						result_msg += result.msg;
						if (result.error == 1) {
							
							$message.html('<label class="text-danger">' + result.msg + '</label>');
						} else if (result.error == 0) {
							$message.html('<label class="text-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
						}
					},
					error: function(XMLHttpRequest, textStatus) {
						$message.html('<label class="text-warn">网络不稳定.请求失败,请重试</label>');
						$.maskLayer(false);
					}
				}));
			});
			$.when.apply($, allRequests).then(function() {
				$.maskLayer(false);
				$.alert(result_msg,'success');
			});
		});
	},

	shipOrder:function(orderid){
		OrderCommon.shipOrderOMS(orderid);
		window.open("/delivery/order/listnodistributionwarehouse",'_blank');
		
	},
	
	shipOrderOMS:function(orderid){
		$.post('/order/order/signwaitsend',{orders:orderid},function(result){
			
			if (result.indexOf('操作成功')>=0){
				$.alertBox(result,true,function(){
					location.reload();
				});
			}else{
				bootbox.alert({  
					buttons: {  
						ok: {  
							 label: '确认',  
							 className: 'btn-success'  
						 }  
					 },  
					 message: result,  
					 callback: function() {  
						 location.reload();
					 },  
				 });
			}
			
			/*
			var eventOBJ = $.alert(result,'success');
			eventOBJ.then(function(){
				// 确定事件
				location.reload();
			});
			*/
			
			
		});
	},
	
	setShipmentMethod:function(orderid){
		$.post('/order/order/signwaitsend',{orders:orderid},function(result){
			/*
			var eventOBJ = $.alert(result,'success');
			eventOBJ.then(function(){
				// 确定事件
				location.reload();
			});
			*/
			
			bootbox.alert({  
				buttons: {  
					ok: {  
						 label: '确认',  
						 className: 'btn-success'  
					 }  
				 },  
				 message: result,  
				 callback: function() {  
					 location.reload();
				 },  
			 });
			
			
		});
		window.open("/carrier/carrierprocess/waitingpost",'_blank');
			
	},
	
	// public 
	
	SuccessBox:function(tmpMsg, className){
		bootbox.alert({  
					buttons: {  
					   ok: {  
							label: '确定',  
							className: 'btn-myStyle'  
						}  
					},  
					message: tmpMsg,  
					callback: function() {  
						$("."+className).modal('hide');
					},  
					
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
			data: {orderList : orderList , Platform :OrderCommon.CurrentPlatform },
			success: function (result) {
				var  tmpMsg ;
				if (result.message){
					tmpMsg = result.message;
				}else{
					
					tmpMsg = '保存成功！';
				}
				
				OrderCommon.SuccessBox(tmpMsg,'order_info');
				/*
				bootbox.alert({  
					buttons: {  
					   ok: {  
							label: '确定',  
							className: 'btn-myStyle'  
						}  
					},  
					message: tmpMsg,  
					callback: function() {  
						$(".order_info").modal('hide');
					},  
					
				}); 
				*/				
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},
	
	//添加备注 - 批量设置备注
	batch_set_memo:function(){
		$("textarea[name=order_memo]").val($('#batch_memo').val());
	},
	
	//添加备注
	showAddMemoBox:function(orderIdList){
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/order/order/show-add-memo-box', 
				data: {orderIdList : orderIdList , Platform :OrderCommon.CurrentPlatform },
				success: function (result) {
					//bootbox.alert(result.message);
					bootbox.dialog({
						title: Translator.t("添加备注"),
						className: "order_info", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () { 
									return OrderCommon.batchSaveOrderDesc();
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
	//订单重发
	reorder:function(orderIdList){
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/order/order/reorder', 
				data: {orderIdList : orderIdList , Platform :OrderCommon.CurrentPlatform },
				success: function (result) {
					if (result.success == false){
						bootbox.alert(result.message);
					}else{
						bootbox.alert({  
							buttons: {  
								ok: {  
									 label: '确认',  
									 className: 'btn-myStyle'  
								 }  
							 },  
							 message: '操作成功！',  
							 callback: function() {  
								 location.reload();
							 },  
						 });
						
						
					}
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	//修改仓库和运输服务
	showWarehouseAndShipmentMethodBox:function(orderIdList, isUpload, callback){
		var isUpload = (typeof(arguments[1])=="undefined")?'0':arguments[1];//设置isupload 的默认值
		var handle = $.openModal('/order/order/show-warehouse-and-shipment-method-box',{orderIdList:orderIdList , Platform:OrderCommon.CurrentPlatform},'修改仓库和运输服务','get');
		handle.done(function($window){
			 // 窗口载入完毕事件
			 $window.find("#ware-ship-modal-save").on('click',function(){
				 var thisWarehouse = $('[name=change_warehouse]').val();
				 var thisShipmentMethod =  $('[name=change_shipping_method_code]').val();
				 var thisWarehouseName = $('[name=change_warehouse]').find('option:selected').text();
				 var thisShipmentMethodName = $('[name=change_shipping_method_code]').find('option:selected').text();

				 OrderCommon.changeWarehouseAndShipmentMethod(orderIdList ,thisWarehouse ,thisShipmentMethod,thisWarehouseName,thisShipmentMethodName , isUpload, callback , $window);
			 })
		});
		
	},
	//改变仓库时，拉取对应的运输服务
	load_shipping_method:function(obj){
		$warehouse_id = $(obj).selectVal();
		$change_shipping_method_code = $('select[name=change_shipping_method_code]');
		$change_shipping_method_code_val=$('select[name=change_shipping_method_code]').val();
		$.ajax({
			url: global.baseUrl + "order/order/get-shipping-method-by-warehouseid",
			data: {
				warehouse_id:$warehouse_id,
			},
			type: 'post',
			dataType: 'json',
			success: function(response) {
				$change_shipping_method_code.html('');
				var options = '';
				$.each(response , function(index , value){
					options += '<option value="'+index+'">'+value+'</option>';
				});
				$change_shipping_method_code.html(options);
				$change_shipping_method_code.val($change_shipping_method_code_val);
			}
		});
	},
	//保存仓库和运输服务
	changeWarehouseAndShipmentMethod:function(orderIdList , thisWarehouseId , thisShipmentMethod , thisWarehouseName , thisShipmentMethodName,isUpload, callback , windowObj,TrackArr){
		if(typeof thisWarehouseName == 'undefined' || thisWarehouseName == ""){
			return $.alert('请先选择仓库','danger');
		}
		if(typeof thisShipmentMethodName == 'undefined' || thisShipmentMethodName == "" ){
			return $.alert('请先选择运输服务','danger');
		}
		if(TrackArr == 'undefined' || TrackArr == "" ){
			TrackArr=Array();
		}

		$.ajax({
			type: "GET",
				dataType: 'json',
				url:'/order/order/change-warehouse-and-shipment-method', 
				data: {orderIdList : orderIdList ,  Platform :OrderCommon.CurrentPlatform , warehouse :thisWarehouseId , shipmentMethod :thisShipmentMethod , warehouseName : thisWarehouseName , shipmentMethodName : thisShipmentMethodName , isUpload:isUpload,trackArr:TrackArr},
				success: function (result) {
					if (result.success){
						$e = $.alert('修改成功','success');
						$e.then(function(){
							if (typeof callback != 'undefined'){
								callback();
								if (typeof windowObj != 'undefined'){								
									var thisAgentRadio=$("input[name='agentRadio']:checked").val();
									if(thisAgentRadio==1){
										$('#trackNumberDiv').html($('#trackingNumber').val());
										$('#showshippingmethod').html('<label data-key="'+thisShipmentMethod+'">'+thisShipmentMethodName+'</label>');
										
										$("#trackingNumber").attr("data-no",$('#trackingNumber').val());
										$("#trackingNumber").attr("data-service",thisShipmentMethod);
									}
									else if(thisAgentRadio==0){
										$('#trackNumberDiv').html($('#trackingNumber2').val());
										$shipping_method_code=$('[name="change_shipping_method_code2"]').val();
										$shipping_method_name=$('#change_othermethod2').val();
										if($shipping_method_name=='' || $shipping_method_name==null)
											$shipping_method_name=$('select[name="change_shipping_method_code2"] option:selected').text();
										if($shipping_method_name=='' || $shipping_method_name==null)
											$shipping_method_name=$shipping_method_code;
										$('#showshippingmethod').html('<label data-key="'+$shipping_method_code+'">'+$shipping_method_name+'</label>');
									}
									else{
										$('#showshippingmethod').html('<label data-key="'+thisShipmentMethod+'">'+thisShipmentMethodName+'</label>');
									}
									if(windowObj==null)								
										$(document).find('div[class="lb-alert"]').remove();
									else
										windowObj.close();
									
								}
							}else{
								location.reload();
							}
							//$('.order_info').modal('hide');
						});
					}else{
						$.alert(result.message,'danger');
					}
				},
				error: function(){
					$.alert("Internal Error",'danger');
					return false;
				}
		});
	},
	
	openNewShipmentmethodSetting:function(){
		var whid = $('[name=change_warehouse]').selectVal();
		if (OrderCommon.overseaList[whid] == 0){
			url = '/configuration/warehouseconfig/self-warehouse-list';
		}else{
			url = '/configuration/warehouseconfig/oversea-warehouse-list';
		}
		
		window.open(url+'?warehouse_id='+whid);
	},
	
	/***************  in stock start   *****************/
	
	showInStockBox:function(orderIdList){
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/order/order/show-in-stock-box', 
				data: {orderIdList : orderIdList ,  Platform :OrderCommon.CurrentPlatform},
				success: function (result) {
					//bootbox.alert(result.message);
					bootbox.dialog({
						title: Translator.t("库存处理"),
						className: "order_info", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("确定"),  
								className: "btn-primary",  
								callback: function () { 
									var thisWarehouse = $('[name=change_warehouse]').val();
									var	thisShipmentMethod =  $('[name=change_shipping_method_code]').val();
									var thisWarehouseName = $('[name=change_warehouse]').find('option:selected').text();
									var thisShipmentMethodName = $('[name=change_shipping_method_code]').find('option:selected').text();
									OrderCommon.changeWarehouseAndShipmentMethod(orderIdList ,thisWarehouse ,thisShipmentMethod,thisWarehouseName,thisShipmentMethodName);
									return false;
									//return changeShipMethod(thisOrderList , $('select[name=change_shipping_method_code]').val());
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
	},
	/***************  in stock end   *****************/
	
	/**********   nation start   ********/
	NationList:{},
	NationMapping:{},
	roleNationList:'',
	currentNation:{},
	selectAllNation:function(isSelect){
		$('input[name^=chk-nation]:visible').prop('checked',isSelect);
		$('input[name^=chk-nation]').each(function(){
			OrderCommon.setCurrentNation(this);
		});
		
	},
	selectGroupNation:function(obj,isSelect){
		$(obj).parent().find('input[type=checkbox]:visible').prop('checked',isSelect);
		$('input[name^=chk-nation]').each(function(){
			OrderCommon.setCurrentNation(this);
		});
		
	},
	setCurrentNation:function(obj){
		var code = $(obj).data('code');
		OrderCommon.currentNation[code]= $(obj).prop('checked');
	},
	
	setNationHtml:function(id){
		var html = '<input class="btn btn-success btn-xs" type="button" value="全部全选" onclick="OrderCommon.selectAllNation(true);">'
			+'<input class="btn btn-danger btn-xs" type="button" value="全部取消" onclick="OrderCommon.selectAllNation(false)">'
			+'<button type="button" class="btn btn-warning btn-xs display_all_country_toggle">全部展开/折叠</button>';
		var lastPlatform = "";
		
		$.each(OrderCommon.NationList,function(index,values){
			html += '<div><hr>';
			html += '<input class="btn btn-success btn-xs" type="button" value="'+values.name+Translator.t('全选')+'" onclick="OrderCommon.selectGroupNation(this,true);">';
			html += '<input class="btn btn-danger btn-xs" type="button" value="'+Translator.t('取消')+'" onclick="OrderCommon.selectGroupNation(this,false)">';
			html += '<button type="button" class="btn btn-warning btn-xs display_toggle">'+Translator.t('展开/折叠')+'</button>';
			html +='<div  class="region_country">'
			for(var code in values.value){
				if ( OrderCommon.currentNation[code] )
					var ischecked = 'checked="checked"';
				else
					var ischecked = '';
				
				html += '<label ><input name="chk-nation" class="role-checkbox" type="checkbox" '+ischecked+' data-code="'+
				code+'"  onclick="OrderCommon.setCurrentNation(this)"/><span class="qtip-checkbox-label">'+values.value[code]+'</span></label>';
			}
			html += '</div><div class="clearfix"></div></div>';
			
		});
		return '<div id="div_nation_list_background">'+html+'</div>';
		/*
		var html = "";
		var lastPlatform = "";
		$.each(OrderCommon.NationList,function(code,label){
			
			if (code == OrderCommon.roleNationList[id] )
				var ischecked = 'checked="checked"';
			else
				var ischecked = '';
			html += '<div  class="col-sm-4"><input name="chk-nation" class="role-checkbox" type="checkbox" '+ischecked+' data-code="'+
			code+'"  onclick="OrderCommon.setCurrentNation(this)"/><span class="qtip-checkbox-label">'+label+'</span></div>'
		});
		return '<div>'+html+'</div>';
		*/
		
	},
	
	initNationBox:function(obj){
		var thisObj = $(obj);
		var roleId  = thisObj.data('role-id');
		var contentHtml = OrderCommon.setNationHtml(roleId);
		thisObj.qtip({
			show: {
				event: 'click',
				solo: true,
			},
			hide: 'click',
			content: {
				button:true,
				text: contentHtml,
			},
			style: {
				classes: 'basic-qtip z-index-top nopadding',
				width: 750,
			},
			position: {
				my: 'center left',
				at: 'center right',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},
			events:{
				show:function(){
					
					$(".display_toggle").unbind().click(function(){
						obj=$(this).next();
						var hidden = obj.css('display');
						if(typeof(hidden)=='undefined' || hidden=='none'){
							obj.css('display','block');
						}else if( hidden=='block'){
							obj.css('display','none');
						}
						
					});
					
					$(".display_all_country_toggle").unbind().click(function(){
						$('.region_country').each(function(){
							obj=$(this);
							var hidden = obj.css('display');
							if(typeof(hidden)=='undefined' || hidden=='none'){
								obj.css('display','block');
							}else if( hidden=='block'){
								obj.css('display','none');
							}
							
						})
					});
				},
				hide:function(){
					if (roleId != "" && roleId != undefined && roleId != null){
						var roleselect = '[data-role-id='+roleId+']';
					}else{
						roleselect = '[data-role-id="0"]';
					}
					var thisText = "";
					$.each(OrderCommon.currentNation,function(i,v){
						if (v==true){
							
							
							if (thisText != "") thisText += ",";
							
							if (OrderCommon.NationMapping[i] != undefined)
								thisText += OrderCommon.NationMapping[i]; 
							else
								thisText += i;
						}
						
					});
					//$('div[name=div-select-nation]'+roleselect).text(thisText);
					$('[name=div-select-nation]'+roleselect+'>input').val(thisText);
				}
			}
		});
	},
	/**********   nation end   ********/
	
	
	
	/**********  custom condition start  ***********/
	customCondition:{},
	
	initCustomCondtionSelect:function(){
		$('select[name=sel_custom_condition]').unbind().change(function(){
			
			if ($('.mutisearch').css('display') == "none"){
				$('.mutisearch').toggle();
			}
				
			var thisIndex = $(this).find("option:selected").text();
			if (OrderCommon.customCondition[thisIndex] != undefined){
				for (var keyName in OrderCommon.customCondition[thisIndex] ){
					$('[name='+keyName+']').val(OrderCommon.customCondition[thisIndex][keyName]);
				}
			}
			//if ($(this).val() == 0) return;
			$('#search').click();
		});
	},
	
	
	
	/**********  custom condition end  ***********/
	
	/******************** stock manage start   ***********************/
	
	showStockManageBox:function(orderIdList){
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/order/order/show-stock-manage-box', 
				data: {orderIdList : orderIdList ,  Platform :OrderCommon.CurrentPlatform},
				success: function (result) {
					//bootbox.alert(result.message);
					bootbox.dialog({
						title: Translator.t("库存处理"),
						className: "largebox", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("确定"),  
								className: "btn-primary",  
								callback: function () { 
									var tmpWHID = '';
									var stockInList = {};
									$('table[data-warehouse-id]').each(function(){
										var tableObj = $(this);
										tmpWHID = $(this).data('warehouse-id');
										
										tableObj.find('tr').has('input').each(function(){
											var trObj = $(this);
											var tmpDataObj = { 
												sku:trObj.find('input[name=sku]').val() , 
												stock_in_qty:trObj.find('input[name=qty]').val() ,
												location_grid:trObj.find('input[name=location]').val() ,
												};
												
											if (stockInList[tmpWHID] == undefined) stockInList[tmpWHID] = [];
											stockInList[tmpWHID].push(tmpDataObj);
										});
									});
								
									OrderCommon.createStockIn(orderIdList,stockInList);
									return false;
									//return changeShipMethod(thisOrderList , $('select[name=change_shipping_method_code]').val());
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
	},
	
	createStockIn:function(orderIdList,stockInList){
		$.ajax({
			type: "GET",
				dataType: 'json',
				url:'/order/order/create-stock-in', 
				data: {orderIdList : orderIdList , Platform :OrderCommon.CurrentPlatform,stockInList:stockInList},
				success: function (result) {
					
					if (result.success){
						OrderCommon.SuccessBox('保存成功！','largebox');
					}else{
						bootbox.alert(result.message);
					}
					
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
		
	},
	
	/******************** stock manage  end    ***********************/
	
	
	
	/******************** reset carrier service start    ***********************/
	resetCarrierService:function(orderIdList){
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/carrier/default/matchshipping', 
				data: {orderIdList : orderIdList , Platform :OrderCommon.CurrentPlatform},
				success: function (result) {
					
					if (result.success){
						OrderCommon.SuccessBox('保存成功！','largebox');
					}else{
						bootbox.alert(result.message);
					}
					
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	/******************** reset carrier service end    ***********************/
	
	showAccountSyncInfo:function(btnObj){
		var url = $(btnObj).data('url');
		if (url !=undefined){
			var handle= $.openModal(url,{},'账号同步情况','post');  // 打开窗口命令
			handle.done(function($window){
				// 窗口载入完毕事件
				$window.find("#close").on('click',function(){
					$window.close();       // 关闭当前模态框
				});
			});
		}
		
		
	},
	
	/**************************  export order start  *****************************/
	//导出订单
	exportorder:function (type,orderIdList){
		if(type==""){
			bootbox.alert("请选择您的操作");return false;
		}
		if(orderIdList.length==0&&type!=''){
			bootbox.alert("请选择要操作的订单");return false;
		}
		var idstr='';
		$.each(orderIdList,function(index, value){
			idstr+=','+value;
		});
		window.open('/order/excel/export-excel'+'?orderids='+idstr+'&excelmodelid='+type);
		
	},
	
	exportorder2:function(obj ,orderIdList){
		var val = $(obj).val();
		if (val != ''){
			OrderCommon.exportorder(val,orderIdList);
			$(obj).val('');
		}
	},
	/**************************  export order end  *****************************/
	
	/**************************  reset left menu start  *****************************/
	
	resetMenuTabbar:function(LabelList , TabbarData){
		for(var key in TabbarData){
			if (LabelList[key] != undefined){
				if ($('.menu_label:contains("'+Translator.t(LabelList[key])+'")').next().length > 0){
					$('.menu_label:contains("'+Translator.t(LabelList[key])+'")').next().remove();
				}
				if (TabbarData[key] >0)
				$('.menu_label:contains("'+Translator.t(LabelList[key])+'")').parent().append('<span class="new"><p>'+TabbarData[key]+'</p></span>');
			}
		}
		$('.icon-iconfont42').load();
	},
	
	/**************************  reset left menu end  *****************************/
	
	/**************************  reset top menu start  *****************************/
	resetTopMenuTabbar:function(LabelList,TabbarData){
		for(var key in TabbarData){
			if (LabelList[key] != undefined){
				if ($('span[data-top-menu-badge='+key+']').length > 0){
					var spanObj = $('span[data-top-menu-badge='+key+']');
					spanObj.html("("+TabbarData[key]+")");
					
					
					if (TabbarData[key] >0 ){
						if (spanObj.parent().hasClass('a_disable')){
							spanObj.parent().removeClass('a_disable');
							if (spanObj.parent().data('href') != undefined){
								spanObj.parent().attr('href',spanObj.parent().data('href'));
							}
						}
					}else if(TabbarData[key] == 0){
						if (spanObj.parent().hasClass('a_disable') ==false){
							spanObj.parent().addClass('a_disable');
							
							if (spanObj.parent().data('href') == undefined){
								spanObj.parent().attr('data-href',spanObj.parent().attr('href'));
								spanObj.parent().removeAttr('href');
							}
						}
					} 
					
				}
				
			}
		}
	},
	/**************************  reset top menu end  *****************************/
	
	/**************************  sign ship start  *****************************/
	signOrdership:function(thisOrderList){
		var url = '/order/order/signshipped';
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
//		tempForm.submit(); 
//		document.body.removeChild(tempForm); 
		
		$.modal({
			  url:'/order/order/signshipped',
			  method:'post',
			  data:$(tempForm).serialize()
			},'虚拟发货',{footer:false,inside:false}).done(function($modal){
				$('.btn.colse_btn_signshipped').click(function(){$modal.close();});
				
				$('.btn.btn-success.save_btn_signshipped').click(function(){
					$formdata = $('#sigshipped_new_form').serialize();
					var Url=global.baseUrl +'order/order/signshippedsubmit';

					$.ajax({
				        type : 'post',
				        cache : 'false',
				        data : $formdata,
				        dataType: 'json',
						url: Url,
				        success:function(response) {
				        	if(response.code == 0){
				        		alert(response.msg);
				        		$modal.close();
				        	}else{
				        		alert(response.msg);
				        	}
				        }
				    });
				});
			}
		);
		
		document.body.removeChild(tempForm); 
	},
	/**************************  sign ship end  *****************************/
	
	/**************************  spread order start  *****************************/
	
	//展开，收缩订单商品
	spreadorder:function (obj,id){
		if(typeof(id)=='undefined'){
			//未传参数进入，全部展开或收缩
			var html = $(obj).parent().html();
			if(html.indexOf('minus')!=-1){
				//当前应该为处理收缩,'-'号存在
				$('.xiangqing').hide();
				$(obj).parent().html('<span class="glyphicon glyphicon-plus" onclick="spreadorder(this);">');
				$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus');
				return false;
			}else{
				//当前应该为处理收缩,'+'号存在
				$('.xiangqing').show();
				$(obj).parent().html('<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);">');
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
	},
	
	/**************************  spread order end  *****************************/	
	
	/**************************  ajax add memo start  *****************************/	
	//添加备注
	updatedesc:function (orderid,obj){
		var desc=$(obj).prev();
		var oiid=$(obj).attr('oiid');
		var html="<textarea name='desc' style='width:200xp;height:60px'>"+desc.text()+"</textarea><input type='button' onclick='OrderCommon.ajaxdesc(this)' value='修改' oiid='"+oiid+"'>";	
		desc.html(html);
		$(obj).toggle();
	},
	ajaxdesc:function (obj){
		 var obj=$(obj);
		 var desc=$(obj).prev().val();
		 var oiid=$(obj).attr('oiid');
		  $.post('/order/order/ajaxdesc',{desc:desc,oiid:oiid},function(r){
			  retArray=$.parseJSON(r);
			  if(retArray['result']){
				  obj.parent().next().toggle();
				  var html="<font color='red'>"+desc+"</font> <span id='showresult' style='background:yellow;'>"+retArray['message']+"</span>"
				  obj.parent().html(html);
				  setTimeout("OrderCommon.showresult()",3000);
			  }else{
				  alert(retArray['message']);
			  }
		  })
	},
	showresult:function (){
		$('#showresult').remove();
	},

	/**************************  ajax add memo end  *****************************/	
	
	/**************************  oms view tracker start  *****************************/	

	OmsViewTracker:function(obj,num,invoker){
		//var s_trackingNo = $(obj).has('.text-success');
		//if(typeof(s_trackingNo)!=='undefined' && s_trackingNo.length>0){
			var tracking_info_type=$(obj).data("info-type");
			if(tracking_info_type !== '17track'){
				var qtip = $(obj).find(".order-info").data('hasqtip');
				if(typeof(qtip)=='undefined')
					return false;
				var opened = $("#qtip-"+qtip).css("display");
				if(opened=='block')
					return true;
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/order/order/oms-view-tracker', 
					data: {invoker: invoker},
					success: function (result) {
						return true;
					},
					error :function () {
						return false;
					}
				});
			}else{
				$.showLoading();
				OrderCommon.show17Track(num);
				$.hideLoading();
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/order/order/oms-view-tracker', 
					data: {invoker: invoker},
					success: function (result) {
						return true;
					},
					error :function () {
						return false;
					}
				});
			}
		//}
	},

	show17Track:function (num){
		$.ajax({
			type: "GET",
			url:'/tracking/tracking/show17-track-tracking-info?num='+num,
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "17track-trackin-info-win",
					title: Translator.t('17track查询结果'),
					message: result,
				});
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('操作失败,后台返回异常'));
				return false;
			}
		});
	},

	doTrack:function (num) {
		if(num===""){
			alert("Enter your number."); 
			return;
		}
		YQV5.trackSingle({
			YQ_ContainerId:"YQContainer",       //必须，指定承载内容的容器ID。
			YQ_Height:400,      //可选，指定查询结果高度，最大高度为800px，默认撑满容器。
			YQ_Fc:"0",       //可选，指定运输商，默认为自动识别。
			YQ_Lang:"zh-cn",       //可选，指定UI语言，默认根据浏览器自动识别。
			YQ_Num:num     //必须，指定要查询的单号。
		});
	},

	ignoreTrackingNo:function (order_id,track_no){
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}  
			}, 
			message: Translator.t("确认忽略物流号？<br><br>一般对平邮或者已经确认投递失败的包裹，进行此操作。如果确认忽略该物流号物流助手将不会再查询该物流号信息。您确定要忽略当前物流号吗？"),
			callback: function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type: "GET",
						url:'/order/order/ignore-tracking-no?order_id='+order_id+'&track_no='+track_no,
						dataType:'json',
						success: function (result) {
							$.hideLoading();
							if(result.success===true){
								bootbox.alert({
									message: '操作成功',  
									callback: function() {  
										window.location.reload();
									},  
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
				}
			}
		});
	},
	/**************************  oms view tracker end  *****************************/	
	
	/**************************  merge order start  *****************************/
	mergeOrder:function(thisOrderList){
		var url = '/order/order/mergeorder';
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
	
	// 可以待合并订单用加粗border 框起来
	showMergeRow:function(){
		// 屏蔽合并展开，不然展示有bug
		if($('.glyphicon.glyphicon-minus:not(.orderspread)').length <= 0){// 如果订单列表界面不是全部展开，则 全部展开
			$('.glyphicon.glyphicon-plus:not(.orderspread)').click();
		}
		$('.glyphicon.glyphicon-minus:not(.orderspread)').addClass('hidden');
		$('.orderspread').addClass('hidden');
		
		var rowArr = [];
		$('table tr[merge-row-tag]').each(function(){
			if($.inArray($(this).attr('merge-row-tag'),rowArr) == -1)
				rowArr.push($(this).attr('merge-row-tag'));
		});
		
		for(var i=0;i<rowArr.length;i++){
			var rowSelect = 'table tr[merge-row-tag="'+rowArr[i]+'"]';
			$(rowSelect+':first td:last').attr('rowspan',$(rowSelect).length);
			$(rowSelect).css('border','2px solid #e68a00');
			$(rowSelect).css('border-width','0px 2px 0px 2px');
			$(rowSelect+':first').css('border-width','2px 2px 0px 2px');
			$(rowSelect+':last').css('border-width','0px 2px 2px 2px');
			$(rowSelect+':first td:last').css('border-bottom','2px solid #e68a00');// 合并按钮底部border
			
			// 最后一个订单 由多个item组成的话，需要补上底部border
			$(rowSelect+'.first-item:last td[rowspan]').css('border-bottom','2px solid #e68a00');
//			var tdHtml = '<td rowspan="'+$(rowSelect).length+'">合并</td>';
//			$(rowSelect+':first').append(tdHtml);
		}
		
		
	},
	
	// 合并该行订单
	mergeSameRowOrder:function(rowMd5){
		if(rowMd5 =='' || $('tr[merge-row-tag="'+rowMd5+'"] .ck:checked').length <= 1){
	    	bootbox.alert("请选择两个或以上订单进行合并");
	    	return false;
	    }
		
		var toMergeOrderIds = [];
		$('tr[merge-row-tag="'+rowMd5+'"] .ck:checked').each(function(){
			toMergeOrderIds.push($(this).val());
		});
		
		OrderCommon.mergeOrder(toMergeOrderIds);
	},
	
	// 取消合并
	cancelMergeOrder:function(orderIdList){
		$.showLoading();
		$.$ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/order/cancel-merge-order', 
			data: {order_id : orderIdList},
			success: function (result) {
				$.hideLoading();
			},
			error: function(){
				$.hideLoading();
				$.alertMsg({message:'Internal Error',type:'message',success:0,timeout:3});
			}
		});
	},	
	
	MergeAllOrderByGroup:function(){
		
		if ($('input[name="order_id[]"]:checked').length ==0){
			bootbox.alert(Translator.t('请选择订单'));
			return;
		}
		
		var lastMD5 = '';
		OrderCommon.batchMergeOrderIdList= {};
		$('tr[merge_row_tag_v3]').has('[name="order_id[]"]').each(function(){
			if (lastMD5 == ''){
				lastMD5 = $(this).attr('merge_row_tag_v3');
				//OrderCommon.batchMergeOrderIdList[lastMD5] = [];
			}
			
			if (lastMD5 ==  $(this).attr('merge_row_tag_v3')){
				//same group 
				if ($(this).find('[name="order_id[]"]').prop('checked')){
					if (OrderCommon.batchMergeOrderIdList[lastMD5] == undefined){
						OrderCommon.batchMergeOrderIdList[lastMD5] = [];
					}
					OrderCommon.batchMergeOrderIdList[lastMD5].push($(this).find('[name="order_id[]"]').val());
				}
			}else{
				//different group 
				lastMD5 = $(this).attr('merge_row_tag_v3');
				
				if ($(this).find('[name="order_id[]"]').prop('checked')){
					OrderCommon.batchMergeOrderIdList[lastMD5] = [];
					OrderCommon.batchMergeOrderIdList[lastMD5].push($(this).find('[name="order_id[]"]').val());
				}
				
			}
			/*
			console.log($(this).attr('merge_row_tag_v3'));
			console.log($(this).find('[name="order_id[]"]').val());
			console.log($(this).find('[name="order_id[]"]').prop('checked'));
			*/
		});
		
		if (OrderCommon.batchMergeOrderIdList )
		OrderCommon.batchMergeOrder(OrderCommon.batchMergeOrderIdList);
	},
	
	batchMergeOrder:function(orderIdList){
		
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'text',
			url:'/order/order/batch-merge-order', 
			data: {orderIdList : orderIdList},
			success: function (result) {
			
				if (result.indexOf('Success')>=0){
					bootbox.alert('合并成功',function(){
						location.reload();
					});
				}else{
					bootbox.alert(result);
					$.hideLoading();
				}
				
			},
			error: function(){
				$.hideLoading();
				$.alertMsg({message:'Internal Error',type:'message',success:0,timeout:3});
			}
		});
	},
	/**************************  merge order end  *****************************/
	
	/**************************  edit order start  *****************************/
	editOrder:function(orderid , obj, module_list){
		if (obj != undefined){
			$(obj).css('pointer-events','none');
		}

		//获取当前界面所有orderid
		var arr=Array();
		if(module_list == 'listplanceanorder'){
			$('#carrier-list-table').find('tr[style="background-color: #f4f9fc;border:1px solid #d1d1d1;"]').each(function(){
				$txt=$(this).attr('class').replace('line-','');
				arr.push($txt);
			});
		}
		else{
			$('#page-content').find('table[class=table_list_v3]').find('tr[class="table_list_v3_tr"]').each(function(){
				$txt=$(this).find('td').eq(0).find('div').eq(0).find('span[class="span_simsun_100"]').html().replace('「','').replace('」','');
				arr.push($txt);
			});
		}
		
		OrderCommon.delDetailIdList = [];//init
		$.modal({
			url:'/order/order/edit-order-modal?orderid='+orderid+'&upOrDownDivtxt='+arr 
		},'修改订单',{
			footer:false,
			
		}).then(function($modal){
			if (obj != undefined){
				$(obj).css('pointer-events','all');
			}
			$( "#edt-order-tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
			$( "#edt-order-tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
			$('#edt-order-tabs>ul>li>a').on('click',function(){
				$('[data-type=title]').text($(this).text());
			});
			$('[name=consignee_country_code]').on('change',function(){
				$('[name=consignee_country]').val($('[name=consignee_country_code] :selected').text());
			})
			
			$('[name="item[quantity][]"]').formValidation({validType:['trim','integer','length[1,4]'],tipPosition:'right',required:true});
			$('[name="item[sku][]"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
			
			$left1=($('#over-lay').width()-$('#over-lay').find('div[class="modal-box inside-modal"]').width())/2;
			$left2=$('#over-lay').find('div[class="modal-box inside-modal"]').width();
			$left=$left1+$left2;
			if($left<$left2)
				$('#upOrDownDiv').css('left',$left2);
			else
				$('#upOrDownDiv').css('left',$left);
			$temp=$('#upOrDownDiv');
			$('#upOrDownDiv').remove();
			$('#over-lay').prepend($temp);			
			
			$modal.unbind().on('click','.modal-close',function(){
//					location.reload();
//					$modal.close();
					$('#upOrDownDiv').remove();
					if(module_list == 'listplanceanorder'){
						deliveryImplantOmsPublic();
						$modal.close();
					}else{
						location.reload();
						$modal.close();
					}
					
			});
			
			//加上new 标签
			$('del.text-important').each(function(){
				if ($(this).hasClass('hidden') ==false && $(this).text() =='' ){
					$(this).parent().append('<span class="label label-danger">new</span>');
				}
			});
			
			$modal.on('modal.action.reject',function(){
				$('#upOrDownDiv').remove();
				if(module_list == 'listplanceanorder'){
					deliveryImplantOmsPublic();
					$modal.close();
				}else{
					location.reload();
					$modal.close();
				}
				
			});
			
			$(window).resize(function() {//上一个下一个的位置
				$left1=($('#over-lay').width()-$('#over-lay').find('div[class="modal-box inside-modal"]').width())/2;
				$left2=$('#over-lay').find('div[class="modal-box inside-modal"]').width();
				$left=$left1+$left2;
				if($left<$left2)
					$('#upOrDownDiv').css('left',$left2);
				else
					$('#upOrDownDiv').css('left',$left);
			});
			
			$("#trackingNumber").on("focus",function(){
				$('#reApplayTrackBtn').addClass('hidden');
			});
			
			$("#trackingNumber").on("blur",function(){
				if($('#trackingNumber').val()=='')
					$('#reApplayTrackBtn').removeClass('hidden');
			});
													
		});
		
		
		
	},

	editOrderCompareAddress:function(){
		var total = $('#consignee-info-view label[data-key]').length/2;
		for(i=0;i<total;i++){
			if ($('#consignee-info-view label[data-key]').eq(i+total).text() != "" ){
				if ($('#consignee-info-view label[data-key]').eq(i+total).text() != $('#consignee-info-view label[data-key]').eq(i).text()) {
					if ($('#consignee-info-view label[data-key]').eq(i).hasClass('text-danger') == false){
						$('#consignee-info-view label[data-key]').eq(i).addClass('text-danger');
					}
				}else{
					if ($('#consignee-info-view label[data-key]').eq(i).hasClass('text-danger') == true){
						$('#consignee-info-view label[data-key]').eq(i).removeClass('text-danger');
					}
				}
			}
		}
	},
	
	editOrderBtnGroupSetting:function(type){
		var hideList = [];
		var showList = [];
		if (type== 'view'){
			/*
			$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'_edit').find('input,select').each(function(){
			debugger
			console.log($(this).prop('name'));
			});
			
			$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'_edit').find('textarea[name=desc]').val()
			$('#edt-order-tabs [tabindex=0]').data('div-id')
			$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'_edit').find('input,select,textarea').serialize()
			$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'_edit').find('input,select').serialize()
			$('#div-tab-edit-button-list').hasClass('hidden')
			$('#tabs [tabindex=0]').data('div-id')
			*/
			/*
			if ($('#div-tab-edit-button-list').hasClass('hidden') ==false){
				$('#div-tab-edit-button-list').addClass('hidden');
			}
			
			if ($('#div-tab-normal-button-list').hasClass('hidden')){
				$('#div-tab-normal-button-list').removeClass('hidden');
			}
			
			if ($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'_view'))
				*/

			var divid=$('.ui-state-focus').data('div-id');
			var divID = $('#edt-order-tabs [tabindex=0]').data('div-id');
			if(divid=='order-declaration' || (divid==null&&divID=='order-declaration')){	
				hideList.push('#div-tab-normal-button-list');
				hideList.push('#div-tab-edit-button-list');
				hideList.push('#order-declaration-reset');
			}
			else{
				showList.push('#div-tab-normal-button-list');
			}
			hideList.push('#div-tab-declaration-edit-button-list');
			hideList.push('#div-tab-edit-button-list');
			showList.push('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-view');
			hideList.push('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit');
			//liang	2017-08-29 s
			hideList.push('#billing-to-consignee-button');
			//liang	2017-08-29 e
			$("#edt-order-tabs [id*=edit]").each(function(){
				obj = $(this);
				if (obj.hasClass('hidden') == false){
					obj.addClass('hidden');
				}
			});
			
			OrderCommon.editOrderCompareAddress();
		}else{
			/*
			if ($('#div-tab-edit-button-list').hasClass('hidden')){
				$('#div-tab-edit-button-list').removeClass('hidden');
			}
			
			if ($('#div-tab-normal-button-list').hasClass('hidden')==false){
				$('#div-tab-normal-button-list').addClass('hidden');
			}
			*/
			hideList.push('#div-tab-normal-button-list');
			showList.push('#div-tab-edit-button-list');
			showList.push('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit');
			hideList.push('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-view');
			//liang	2017-08-29 s
			if($('#edt-order-tabs [tabindex=0]').data('div-id')=='consignee-info')
				showList.push('#billing-to-consignee-button');
			//liang	2017-08-29 e
			
		}
		
		$.each(hideList, function(index, value){
			var obj = $(value);
			if (obj.hasClass('hidden') == false){
				obj.addClass('hidden');
			}
		});
		
		$.each(showList, function(index, value){
			var obj = $(value);
			if (obj.hasClass('hidden') ){
				obj.removeClass('hidden');
			}
		});
	
	},
	//liang	2017-08-29 s
	copyBillingInfoToShippingInfo: function(){
		var consignee = $("label[data-key='billing_info[name]'").text();
		var consignee_address_line1 = $("label[data-key='billing_info[address_line1]'").text();
		var consignee_address_line2 = $("label[data-key='billing_info[address_line2]'").text();
		var consignee_city = $("label[data-key='billing_info[city]'").text();
		var consignee_postal_code = $("label[data-key='billing_info[post_code]'").text();
		if(typeof(consignee)!=='undefined')
			$("#consignee-info-edit input[name='consignee']").val(consignee);
		if(typeof(consignee_address_line1)!=='undefined')
			$("#consignee-info-edit input[name='consignee_address_line1']").val(consignee_address_line1);
		if(typeof(consignee_address_line1)!=='undefined')
			$("#consignee-info-edit input[name='consignee_address_line2']").val(consignee_address_line2);
		if(typeof(consignee_city)!=='undefined')
			$("#consignee-info-edit input[name='consignee_city']").val(consignee_city);
	},
	//liang	2017-08-29 e
	editOrderSaveInfo:function(){
		var divID = $('#edt-order-tabs [tabindex=0]').data('div-id');
		
		if (divID == 'warehouse-shipservice'){
			// 修改仓库与运输方法沿用旧的方法
			var thisWarehouse = $('[name=change_warehouse]').val();
			var	thisShipmentMethod =  $('[name=change_shipping_method_code]').val();
			var thisWarehouseName = $('[name=change_warehouse]').find('option:selected').text();
			var thisShipmentMethodName = $('[name=change_shipping_method_code]').find('option:selected').text();
			var orderIdList  = [];
			orderIdList.push($('[name=orderid]').val());
			
			var thisAgentRadio=$("input[name='agentRadio']:checked").val();
			var TrackArr=Array();
			if(thisAgentRadio==1){
				var thisTrackNum=$('#trackingNumber').val();
				TrackArr.push(thisTrackNum);
				TrackArr.push('');
				TrackArr.push('');
				TrackArr.push(thisShipmentMethod2);
				TrackArr.push('automatic');
			}
			else{
				var thisTrackNum=$('#trackingNumber2').val();
				var thisOthermethod=$('#change_othermethod2').val();
				var thisChange_web=$('#change_web2').val();
				var thisShipmentMethod2=$('[name=change_shipping_method_code2]').val();
				TrackArr.push(thisTrackNum);
				TrackArr.push(thisOthermethod);
				TrackArr.push(thisChange_web);
				TrackArr.push(thisShipmentMethod2);
				TrackArr.push('Manual');
			}
			
			OrderCommon.changeWarehouseAndShipmentMethod(orderIdList ,thisWarehouse ,thisShipmentMethod,thisWarehouseName,thisShipmentMethodName,'0',function(){
				$('[data-key="default_warehouse_id"]').text($('[name=change_warehouse] :selected').text());
				$('[data-key="default_shipping_method_code"]').text($('[name=change_shipping_method_code] :selected').text());
				var spanObj = $('[data-div-id=warehouse-shipservice]').find('.glyphicon.glyphicon-remove.text-warn');
				spanObj.removeClass('glyphicon-remove');
				spanObj.removeClass('text-warn');
				
				spanObj.addClass('glyphicon-ok');
				spanObj.addClass('text-success');
				return OrderCommon.editOrderBtnGroupSetting('view');
			},null,TrackArr);
		}
		else if(divID == 'order-declaration'){
			var arr = [];
			arr.push($('#order_source').val());
			arr.push($('#order_itemid').val());
			arr.push($('#order_sku').val());
			arr.push($('#nameCh').val());
			arr.push($('#nameEn').val());
			arr.push($('#declaredValue').val());
			arr.push($('#weight').val());
			arr.push($('#detailHsCode').val());
					
			var ischange=0;
			var jsonTextInit=$('#changeold').val();
			var dataform = $(".horizontal").serializeArray();  
			var jsonText = JSON.stringify({ dataform: dataform });
			if(jsonTextInit==jsonText)  
				ischange=0;
			else  
				ischange=1;
			
			$('#warninglabel3').addClass('hidden');
			$('#titleRelation').html('是否确认修改报关信息？');
//			$('#warningRemoveRelation3').parent().removeClass('hidden');
			
			if ($('#warningRemoveRelation3').parent().hasClass("hidden") && $('#warningRemoveRelation2').parent().hasClass("hidden")){
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/order/order/order-save-declaration-info',
					data: {data:arr,type:$('input:radio[name="warningRemoveRelation"]:checked').val(),ischange:ischange},
					success: function (result) {
						var  tmpMsg ;
						if (result.success == true){
							$('#td'+$('#order_itemid').val()).html(result.data);
							$('#order-declaration-view').removeClass('hidden');
							$('#order-declaration-edit').addClass('hidden');
							$('#div-tab-edit-button-list').addClass('hidden');
							$('#order-declaration-reset').addClass('hidden');
						}
						else{
							bootbox.alert(result.message);
						}
						return true;
					},
					error: function(){
						bootbox.alert("Internal Error");
						return false;
					}
					});
			}
			else{
				bootbox.dialog({
					title: Translator.t("提醒"),
					className: "order_info", 
					message: $('#dialog').html(),
					buttons:{
						Ok: {  
							label: Translator.t("确认"),  
							className: "btn-success",  
							callback: function () { 
								$.ajax({
									type: "POST",
									dataType: 'json',
									url:'/order/order/order-save-declaration-info',
									data: {data:arr,type:$('input:radio[name="warningRemoveRelation"]:checked').val(),ischange:ischange},
									success: function (result) {
										var  tmpMsg ;
										if (result.success == true){
											$('#td'+$('#order_itemid').val()).html(result.data);
											$('#order-declaration-view').removeClass('hidden');
											$('#order-declaration-edit').addClass('hidden');
											$('#div-tab-edit-button-list').addClass('hidden');
											$('#order-declaration-reset').addClass('hidden');
										}
										else{
											bootbox.alert(result.message);
										}
										return true;
									},
									error: function(){
										bootbox.alert("Internal Error");
										return false;
									}
									});
							}
						},
				   		Cancel: {  
							label: Translator.t("取消"),  
							className: "btn-default",  
							callback: function () {  
							}
				   		}
					}
		   		});
			}
	   		
		}
		else{
			
			if (divID == 'consignee-info'){
				if ($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('[name=consignee]').val().length ==0){
					bootbox.alert('收件人不能为空！');
					return false;
				}
				
				if ($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('[name=consignee_country_code]').val().length ==0){
					bootbox.alert('国家不能为空！');
					return false;
				}
				
				if ($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('[name=consignee_address_line1]').val().length ==0){
					bootbox.alert('地址行1不能为空！');
					return false;
				}
				
				if ($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('[name=consignee_city]').val().length ==0){
					bootbox.alert('城市不能为空！');
					return false;
				}
			}
			
			$.ajax({
				url: "/order/order/save-"+$('#edt-order-tabs [tabindex=0]').data('div-id')+'?order_id='+$('[name=orderid]').val(),
				type: 'post',
				dataType:"json",
				data:$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('input,select,textarea').serialize(),
				success: function(result) {
					if(!result.success){
						bootbox.alert(result.message);
						return false;
					}
					//修改收件 人信息更新html代码
					$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('input').each(function(){
						$("[data-key='"+$(this).prop('name')+"']").text($(this).val());
						var delObj = $("[data-key='"+$(this).prop('name')+"']").next('del');
						if (delObj.text() != $(this).val()){
							if (delObj.hasClass('hidden')) {
								delObj.removeClass('hidden');
							}
						}else if (delObj.text() == $(this).val()){
							if (delObj.hasClass('hidden') == false) {
								delObj.addClass('hidden');
							}
						} 
						
						
						
						
					});
					if(divID=='billing-info'){
						$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find("select").each(function(){
							$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-view'+" [data-key='"+$(this).prop('name')+"']").text($(this).val());
							//$("[data-key='"+$(this).prop('name')+"']").text($(this).val());
						});
					}
					
					//加上new 标签
					$('del.text-important').each(function(){
						if ($(this).hasClass('hidden') ==false && $(this).text() ==''){
							if ($(this).parent().find('span.label-danger').length ==0){
								$(this).parent().append('<span class="label label-danger">new</span>');
							}
							
						}
						
						if ($(this).hasClass('hidden') == true){
							if ($(this).parent().find('span.label-danger').length > 0){
								$(this).parent().find('span.label-danger').remove();
							}
						}
						
					});
					
					//修改memo信息更新
					if ($('#edt-order-tabs [tabindex=0]').data('div-id') == "memo-info"){
						if ($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-view').text() != $('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('textarea').val()){
							$('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-view').html($('#'+$('#edt-order-tabs [tabindex=0]').data('div-id')+'-edit').find('textarea').val().replace(/\n/g,"<br>"));
						}
					}
					
					
					
					OrderCommon.editOrderBtnGroupSetting('view');
				},
				error: function() {
				}
			});
		}
	},
	
	generateProductBox:function(orderItemId){
		$.modal({
			url:'/order/order/generate-product-box?orderItemId='+orderItemId
		},'生成到商品库').then(function($modal){
			$modal.on('modal.action.resolve',function(){
				var sku = $modal.find('[name=sku]').val();
				OrderCommon.generateProduct(sku,orderItemId);
				setTimeout(function(){
					$modal.close();
				},1000);

			});
				
		});
		
		
		
	},
	
	generateProduct:function(sku, itemid){
		$.ajax({
			url: "/order/order/generate-product?sku="+sku+'&itemid='+itemid,
			type: 'post',
			dataType:"json",
			success:function(result){
				if (result.success == false){
					bootbox.alert(result.message);
				}else{
					bootbox.alert('保存成功！',function(){
						$('div[data-item-id='+itemid+']').find('button').remove();
						$('div[data-item-id='+itemid+']').append(sku);
					});
				}
				
			},
			error:function(){}
		});
	},
	
	addEditOrderItem:function(){
		var addHtml = "<tr>"
			+'<td></td>'
			+'<td><input type="text" name="item[product_name][]" class="form-control" placeholder="指定商品名称"></td>'
			+'<td></td>'
			/*
			+'<td><div class="input-group">'
						+'<input type="text" name="item[sku][]" class="form-control" placeholder="指定商品SKU">'
					     +'<span class="input-group-btn">'
					        +'<button class="btn btn-important btn-sm" type="button" onclick="manualOrder.selectProd(this)">指定商品SKU</button>'
					      +'</span>'
					    +'</div></td>'
						*/
			+"<td><input class=\"form-control\" type='text' name='item[quantity][]' value='1' /></td>"
			+"<td><input class=\"form-control\" type='hidden' name='item[price][]' value='0'>0</td>"
			+'<td><input type="text" name="item[sku][]" class="form-control" placeholder="指定商品SKU"></td>'
			+"<td><a class=\"cursor_pointer text-danger\" onclick=\"OrderCommon.editOrderDelRow(this,'')\"><span class=\"glyphicon glyphicon-remove-circle\" aria-hidden=\"true\"></span>删除</a></td></tr>";
			$('#order_item_info').append(addHtml);
			
			$('[name="item[sku][]"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
			$('[name="item[quantity][]"]').formValidation({validType:['trim','integer','length[1,255]'],tipPosition:'right',required:true});
			//$('[name="item[price][]"]').formValidation({validType:['trim','amount','length[1,255]'],tipPosition:'right',required:true});
			
	},
	
	
	editOrderDelRow:function(obj,detailid){
		/**/
		if ($('input[name="item[sku][]"]').length <=1){
			$.alert("必须有一个商品！");
			return false;
		}
		if (detailid != ''){
			OrderCommon.delDetailIdList.push(detailid);
		}
		$(obj).parents('tr').remove();
	},
	
	saveEditOrderItem:function(){
		var from1 = $('#form_order_item_info');
		if (! $('#form_order_item_info').formValidation('form_validate')){
			bootbox.alert(Translator.t('请先输入必填项，再保存!'));
			return false;
		}
		
		var delDetailIdStr = '';
		$.each(OrderCommon.delDetailIdList,function(index , value){
			if (delDetailIdStr =='') 
				delDetailIdStr = value;
			else
				delDetailIdStr += ','+value;
		});
		
		$('select[name=manual_status]').each(function(){
			$(this).parents('tr').find('input[name="item[manual_status][]"]').val($(this).val());
		});
		
		$.ajax({
			url: "/order/order/save-edit-order-item?order_id="+$('[name=orderid]').val()+'&deldetailstr='+delDetailIdStr,
			type: 'post',
			dataType:"json",
			data:$('#order_item_info').find('input').serialize(),
			success:function(result){
				if (result.success == false){
					bootbox.alert(result.message);
				}else{
					bootbox.alert('保存成功！',function(){
						$.ajax({
							url:"/order/order/refresh-edit-order-item-info?order_id="+$('[name=orderid]').val(),
							type: 'get',
							dataType:"html",
							success:function(htmlStr){
								$('#div-order-item-info').html(htmlStr);
							},
							error:function(){}
						});
						OrderCommon.refreshOrderdeclarationEdit();
					});
				}
				
			},
			error:function(){}
		});
	},
	
	showEditOrderEmialDialog:function(email){
		var orderid = $('#edit-modal-order-id').val();
		var html ='<div id="div_edit_email" >买家邮箱： <input type="text" name="email" value="'+email+'"></div>';
		$.modal(html,"修改邮箱").then(function($modal){
			$modal.css('width','500px')
			$modal.on('modal.action.resolve',function(){
				
				$.ajax({
					url: "/order/order/ajax-save-order?orderId="+orderid+'&consignee_email='+$modal.find('[name=email]').val(),
					type: 'post',
					dataType:"json",
					success: function(result) {
						if (result.success){
							
							bootbox.alert('保存成功！',function(){
								$('[data-key=consignee_email]').text($modal.find('[name=email]').val());
							});
						}
						
					},
					error: function() {
					}
				});
				
				setTimeout(function(){
					$modal.close();
				},1000);

			});
				
		});
	},
	
	
	showResetALiasDialog:function(){
		
		var html = '';
		
		$.modal({
			url:'/order/order/generate-product-box?orderItemId='+orderItemId
		},'生成到商品库').then(function($modal){
			$modal.on('modal.action.resolve',function(){
				var sku = $modal.find('[name=sku]').val();
				//OrderCommon.generateProduct(sku,orderItemId);
				setTimeout(function(){
					$modal.close();
				},1000);

			});
				
		});
	},
	
	resetProductAlias:function(sku, itemid){
		$.ajax({
			url: "/order/order/generate-product?sku="+sku+'&itemid='+itemid,
			type: 'post',
			dataType:"json",
			success:function(result){
				if (result.success == false){
					bootbox.alert(result.message);
				}else{
					bootbox.alert('保存成功！',function(){
						$('div[data-item-id='+itemid+']').find('button').remove();
						$('div[data-item-id='+itemid+']').append(sku);
					});
				}
				
			},
			error:function(){}
		});
	},
	
	unbindingProductAlias:function(orderItemId){
		var orderid = $('#edit-modal-order-id').val();
		$.ajax({
			url: "/order/order/unbinding-product-alias?order_id="+$('[name=orderid]').val(),
			type: 'post',
			dataType:"json",
			data:$('#order_item_info').find('input').serialize(),
			success:function(result){
				if (result.success == false){
					bootbox.alert(result.message);
				}else{
					bootbox.alert('保存成功！',function(){
						$.ajax({
							url:"/order/order/refresh-edit-order-item-info?order_id="+$('[name=orderid]').val(),
							type: 'get',
							dataType:"html",
							success:function(htmlStr){
								$('#div-order-item-info').html(htmlStr);
							},
							error:function(){}
						});
					});
				}
				
			},
			error:function(){}
		});
	},
	/**************************  edit order end  *****************************/
	
	/**************************  set order sync ship status condition start  *****************************/
	initSetOrderSyncShipStatusCondition:function(){
		$('#div_order_sync_ship_status_toolbar>a').on('click',function(){
			$('#div_order_sync_ship_status_toolbar>input[name=order_sync_ship_status]').val($(this).data('value'));
			$('#search').click();
		});
		
		
		$.ajax({
			url: "/order/order/get-order-ship-status-situation?platform="+$('#div_order_sync_ship_status_toolbar').data('platform')+'&order_status='+$('[name=order_status]').val(),
			type: 'post',
			dataType:"json",
			success: function(result) {
				for ( var key in result){
					if (result[key] >=0){
						$('#div_order_sync_ship_status_toolbar [data-key='+key+']').text("("+result[key]+")");
					}
				}
			},
			error: function() {
			}
		});
		
	},
	/**************************  set order sync ship status condition  end   *****************************/
	
	
	/**************************  set order sync ship status condition start  *****************************/
	setOrderSyncShipStatusComplete:function(orderIdList){
		$.ajax({
			url: "/order/order/set-order-sync-ship-status-complete",
			type: 'post',
			data: {orderIdList : orderIdList , platform :$('#div_order_sync_ship_status_toolbar').data('platform')},
			dataType:"json",
			success: function(result) {
				if (result.ack){
					bootbox.alert('操作成功',function(){
						location.reload();
					});
				}else{
					bootbox.alert(result.message);
				}
				
			},
			error: function() {
				bootbox.alert('内部错误！请联系客服');
			}
		});
	},
	/**************************  set order sync ship status condition end  *****************************/
	
	/**************************  set order verify pass start  *****************************/
	setOrderVerifyPass:function(orderIdList){
		$.ajax({
			url: "/order/order/set-order-verify-pass",
			type: 'post',
			data: {orderIdList : orderIdList , platform :$('#div_order_sync_ship_status_toolbar').data('platform')},
			dataType:"json",
			success: function(result) {
				if (result.ack){
					bootbox.alert('操作成功',function(){
						location.reload();
					});
				}else{
					bootbox.alert(result.message);
				}
				
			},
			error: function() {
				bootbox.alert('内部错误！请联系客服');
			}
		});
	},
	/**************************  set order verify pass end  *****************************/
	
	showManualSyncInfo:function(btnObj){
		var url = $(btnObj).data('url');
		if (url !=undefined){
			//$.showLoading();
			$.ajax({
				type: "GET",
				dataType: 'html',
				url:url, 
				success: function (result) {
					$.hideLoading();
					bootbox.dialog({
						title: Translator.t("账号同步情况"),
						className: "manual-sync-order-win", 
						message: result,
					});	
				},
				error: function(){
					$.hideLoading();
					bootbox.alert("后台获取数据错误，请联系客服");
					return false;
				}
			});
		}
	},
	
	
	selected_switch:function(){
		var selected = 0;
		var selected_profited = 0;
		var profitToalt = 0;
		
		$('input[name="order_id[]"]:checked').each(function(){
			selected++;
			//console.log($(this).parents("tr").find(".profit_detail")[0]);
			var profit = $(this).parents("tr").find(".profit_detail").attr('data-profit');
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
	},
	
	deleteManualOrder:function(orderid){
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}  
			},
			message: Translator.t("确认删除手工订单？"),
			callback: function(r){
				if(r){
					$.showLoading();
					$.post('/order/order/delete-manual-order',{orders:orderid},function(result){
						$.hideLoading();
						if (result.success==true){
							$.alertBox(result.message,true,function(){
								location.reload();
							});
						}else{
							bootbox.alert({
								buttons: {
									ok: {
										 label: '确认',  
										 className: 'btn-success'  
									 }
								 },
								 message: result.message,  
								 callback: function() {  
									 location.reload();
								 },
							 });
						}
					});
				}
			}
		});
	},
	
	//拆分订单
	splitOrder:function(orderid){
		$.modal({
			url:'/order/order/split-order-new',
			method:'POST',
			data:{orderid : orderid},
		},
		'拆分订单',{footer:false,inside:false}).done(function($modal){ 	
			$('.queding').click(function(){
				$splotOrderDelList=$('#deldata').val();
				$splotOrderqtyList=$('#splitqty').val();
				$.ajax({
					type: "POST",
						dataType: 'json',
						url:'/order/order/split-order-reorder', 
						data: {orderIdList : orderid , Platform :OrderCommon.CurrentPlatform ,splotOrderDelList:$splotOrderDelList,splotOrderqtyList:$splotOrderqtyList},
						success: function (result) {
							if (result.success == false){
								bootbox.alert(result.message);
							}else{
								bootbox.alert({  
									buttons: {  
										ok: {  
											 label: '确认',  
											 className: 'btn-myStyle'  
										 }  
									 },  
									 message: '操作成功！',  
									 callback: function() {  
										 location.reload();
									 },  
								 });
								
								
							}
						},
						error: function(){
							bootbox.alert("Internal Error");
							return false;
						}
				});
			});
			
			
			$('.modal-close').click(function(){	
				$modal.close();
			});
		});
	},
	//取消拆分订单
	splitOrderCancel:function(orderid){
		$.modal({
			url:'/order/order/split-order-cancel',
			method:'POST',
			data:{orderid : orderid},
		},
		'取消拆分订单',{footer:false,inside:false}).done(function($modal){ 
			$('.queding').click(function(){
				$bl=true;
				$('.orderstatus').each(function(){
					$status=$(this).html();
					if($status!='已付款'){
						bootbox.alert('只有已付款状态的订单才可以取消拆分');
						$bl=false;
						return false;
					}
				});
				if($bl==false)
					return false;
				
				
				$.ajax({
					type: "POST",
						dataType: 'json',
						url:'/order/order/splitorder-cancels', 
						data: {orderIdList : orderid},
						success: function (result) {
							if(result.code==0){
								$.alertBox('<p class="text-success" style="font-size:24px;">取消成功</p>');					
								location.reload();
							}
							else{
								bootbox.alert(result.message);
								return false;
							}
						},
						error: function(){
							bootbox.alert("Internal Error");
							return false;
						}
				});
			});
			
			
			$('.modal-close').click(function(){	
				$modal.close();
			});
		});
	},
	
	
//排序标签筛选
	sortModeBtnClick:function(obj,win_list){
	var sorttype = $(obj).attr("sorttype");
	if(sorttype == ''){
		sorttype = 'asc';
	}else{
		if(sorttype == 'asc')
			sorttype = 'desc';
		else
			sorttype = 'asc';
	}
	
	$('#customsort').val($(obj).attr("value"));
	$('#ordersorttype').val(sorttype);
	
	if(win_list == undefined){
		win_list = 'content-wrapper'
	}
	var Url=$("#form1").attr('action')+"?"+$("#form1").serialize();
	window.location.href=Url;
},
	
	//导出订单
	orderExcelprint:function(type){
		var str = '';
		var count = 0;
		$('select[name=orderExcelprint]').val('-1');
		$('select[name=orderExcelprint]').next().children().children().children().eq(0).attr('title','导出订单');
		$('select[name=orderExcelprint]').next().children().children().children().eq(0).html('导出订单');
		if(type == 0)
		{
			$('input[name="order_id[]"]:checked').each(function(){
				str += $(this).val() + ',';
				count++;
			});
			
			if(str.replace(',','') == '')
			{
				bootbox.alert("请选择要操作的订单");return false;
		    }
			
		}
		else if(type==1){
	 		//筛选条件
	 		str = $('#search_condition').val();
	 		//总数量
	 		var c = $('#search_count').val();
	 		if( c != null && c != '')
	 			count = c;
		}
		else
			return false;
		
		$.modal({ 
			url:'/order/excel/orderexcelmodel',
			method:'post',
			data:{
				}
		},'导出订单',{footer:false,inside:false}).done(function($modal){
			$('.exportPackage').click(function(){
				$exportTemplateSelect=$('#exportTemplateSelect').val();
				$isMaster=$('input[name=isMaster]:checked').val();
				$checkkey='';
				
				if($exportTemplateSelect==''){
					bootbox.alert("请选择要导出的范本");
					return false;
				}
				
				if($exportTemplateSelect==-1){				
					$('input[name="exportKey"]:checked').each(function(){
						$checkkey += $(this).val() + ',';
					});
					
					if($checkkey.replace(',','') == '')
					{
						bootbox.alert("请选择要导出的字段");return false;
				    }
				}

				//当小于500行时，直接前台导出
				if(count > 0 && count < 500){
					window.open("/order/excel/straight-export-excel?type="+type+"&str="+str+"&exportTemplateSelect="+$exportTemplateSelect+"&checkkey="+$checkkey+"&isMaster="+$isMaster+"&per-page=50000",'_blank');
				}
				else{
					$.modal({
							  url:'/order/excel/add-export-excel',
							  method:'POST',
							  data:{type : type, 
								    str : str,
								    exportTemplateSelect:$exportTemplateSelect,
								    checkkey:$checkkey,
								    isMaster:$isMaster,
								    },
						},
						'导出Excel',{footer:false,inside:false}).done(function($modal){
							$height=parseInt($(document.body).height());
							$('.modal-box').eq(0).after('<div id="modal-backdrop-level2" class="modal-backdrop  in" style="height:'+$height+'px">');//插入遮盖
						});
				}
			});
					
			$('.modal-close').click(function(){
				$modal.close();
			});
		});
	},
	//订单报关信息编辑
	EditOrderDeclaration:function(orderitemid,obj){
		if ($('#warningRemoveRelation3').parent().hasClass("hidden")){
			if ($('#warningRemoveRelation2').parent().hasClass("hidden")){
				bootbox.alert("编辑内容后，必须「重新上传」才能生效！");
			}
		}
		
		$('#order-declaration-view').addClass('hidden');
		$('#order-declaration-edit').removeClass('hidden');
		$('#editCustomsFormDiv').removeClass('hidden');
		$('#div-tab-edit-button-list').removeClass('hidden');
		$('#order-declaration-reset').removeClass('hidden');
		$('#nameCh').val($(obj).parent().prev().find('span[class=nameChSpan]').html());
		$('#nameEn').val($(obj).parent().prev().find('span[class=nameEnSpan]').html());
		$('#declaredValue').val($(obj).parent().prev().find('span[class=deValSpan]').html());
		$('#weight').val($(obj).parent().prev().find('span[class=weightSpan]').html());
		$('#detailHsCode').val($(obj).parent().prev().find('span[class=hsCodeSpan]').html());
		$('#order_itemid').val(orderitemid);
		$('#order_sku').val($(obj).parent().prev().prev().find('a[class=fBlue]').html());
//		$('#order_sku').val($(obj).parent().prev().prev().html());
		
		var dataformInit = $(".horizontal").serializeArray();
		var jsonTextInit = JSON.stringify({ dataform: dataformInit });
		$('#changeold').val(jsonTextInit);
	},
	//点击默认报关信息
	defaultCustomsForm:function(obj){
		$('#nameCh').val($(obj).data('ch'));
		$('#nameEn').val($(obj).data('en'));
		$('#declaredValue').val($(obj).data('decval'));
		$('#weight').val($(obj).data('weight'));
		$('#detailHsCode').val($(obj).data('hscode'));
	},
	//重置订单报关信息
	editOrderdeclarationresset:function(){
		$('#nameCh').val('');
		$('#nameEn').val('');
		$('#declaredValue').val('');
		$('#weight').val('');
		$('#detailHsCode').val('');
	},
	//配对sku
	PairProduct:function($orderitemid,$sku,$rootsku,$type){
		if($type==0){  //解除
			$('#warninglabel3').removeClass('hidden');
			$('#titlePairproduct').html('确认要解除配对关系？');		
	   		bootbox.dialog({
				title: Translator.t("解除"),
				className: "order_info", 
				message: $('#dialog2').html(),
				buttons:{
					Ok: {  
						label: Translator.t("确认"),  
						className: "btn-success",  
						callback: function () { 
							$.ajax({
								type: "POST",
								dataType: 'json',
								url:'/order/order/save-realtion',
								data:{orderitemid:$orderitemid,sku:$sku,rootsku:$rootsku,ordertype:$type,type:$('input:radio[name="warningPairproduct"]:checked').val()},
								success: function (result) {
									OrderCommon.refreshOrderdeclarationEdit();
									if (result.result.ack == true){
										$.alertBox('<p class="text-success" style="font-size:24px;">解除成功</p>');
										$('#order_item_info').find('div[data-item-id='+$orderitemid+']').html(result.html);
										
										if (result.result.success == false){
											bootbox.alert(result.message);
										}
										return true;
									}else{
										bootbox.alert(result.message);
										return false;
									}
								},
								error: function(){
									bootbox.alert("Internal Error");
									return false;
								}
							});
						}
					}, 
					Cancel: {  
						label: Translator.t("取消"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
				}
			});	
		}
		else{ //更换
			$.modal({
				url:'/order/order/pair-product',
				method:'POST',
				data:{orderitemid : $orderitemid,sku : $sku,type:$type},
			},
			'搜索',{footer:false,inside:false}).done(function($modal){
	 			$height=parseInt($(document.body).height());
	 			$('.modal-box').eq(0).after('<div id="modal-backdrop-level2" class="modal-backdrop  in" style="height:'+$height+'px;z-index:0;">');//插入遮盖
	 			
	 			OrderCommon.SelectWareHoseProducts(1);
	 			
	 			$('#btnSelectSearch').click(function(){
	 				$searchtext=$.trim($('#searchWareHoseProductsValue').val());
	 				$Searchtype=$('#searchWareHoseProductsType').val(); //搜索类型
	 				if($searchtext=='' || $searchtext==null){
							$('#productbody').addClass('hidden');
 							$('#productbody_nosearch').addClass('hidden');
 							$('#productbodylist').removeClass('hidden');
 							OrderCommon.SelectWareHoseProducts(1);
	 				}
	 				else{
							OrderCommon.SelectWareHoseProducts(1);
	 						return true;
	 				}
	 			});
	 			
	 			$('#searchWareHoseProductsValue').on('keypress',function(e)
	 			{
	 				if(e.which == 13) 
	 					$('#btnSelectSearch').click();
	 			});
	 				 			
				$('.modal-close').click(function(){	
					$modal.close();
				});
			});
		}
	},
	//配对sku搜索类型
	setSelectWareHoseProductsType:function(type,obj){
		$(obj).addClass('myj-active');
		if(type==1)
			$(obj).next().removeClass('myj-active');
		else
			$(obj).prev().removeClass('myj-active');
		$('#searchWareHoseProductsType').val(type);
	},
	//显示配对sku搜索页商品
	SelectWareHoseProducts:function(page){
		var condition=$.trim($('#searchWareHoseProductsValue').val()); //搜索条件
		var type=$('#searchWareHoseProductsType').val(); //搜索类型
		$.ajax({
			type: "POST",
			dataType: 'html',
			url:'/order/order/select-ware-hose-products',
			data:{page:page,condition:condition,type:type},
			success: function (result) {
				$('#productbodylist').html(result);
				$('#pagination > ul > li a').each(function()
				{
        			$(this).attr('href', 'javascript:OrderCommon.SelectWareHoseProducts('+$(this).html()+');');
				});
        		$('#pagination > ul .prev a').attr('href', 'javascript:OrderCommon.SelectWareHoseProducts('+(page-1)+');');
        		$('#pagination > ul .next a').attr('href', 'javascript:OrderCommon.SelectWareHoseProducts('+(page+1)+');');
			},
			error: function(){
				bootbox.alert("Internal Error");
			}
		});
	},
	
	//确认配对sku
	Choice:function(obj){
//		$('#warningRemoveRelation3').parent().addClass('hidden');
//		$('#warningRemoveRelation2').parent().removeClass('hidden');
		$orderitemid=$('#searchWareHoseProductsid').val();
		$arr=$orderitemid.split('-');
		$sku=$('#searchWareHosesku').val();
			$('#titlePairproduct').html('确认要更换配对关系？');			
			$searchtext=obj;
			bootbox.dialog({
				title: Translator.t("配对"),
				className: "order_info", 
				message: $('#dialog2').html(),
				buttons:{
					Ok: {  
						label: Translator.t("确认"),  
						className: "btn-success",  
						callback: function () { 
							$.ajax({
								type: "POST",
								dataType: 'json',
								url:'/order/order/save-realtion',
								data:{orderitemid:$arr[0],sku:$sku,rootsku:$searchtext,ordertype:$arr[1],type:$('input:radio[name="warningPairproduct"]:checked').val()},
								success: function (result) {
									OrderCommon.refreshOrderdeclarationEdit();
									if (result.result.ack == true){
										$.alertBox('<p class="text-success" style="font-size:24px;">配对成功</p>');
										$('#order_item_info').find('div[data-item-id='+$arr[0]+']').html(result.html);
										$('.modal-backdrop').remove();
										$('.modal-box').eq(1).remove();
										
										if (result.result.success == false){
											bootbox.alert(result.message);
										}
										
										return true;
									}else{
										alert(result.message);
										return false;
									}
								},
								error: function(){
									bootbox.alert("Internal Error");
									return false;
								}
							});
						}
					}, 
					Cancel: {  
						label: Translator.t("取消"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
				}
			});	
	},
	//编辑商品刷新报关信息页面
	refreshOrderdeclarationEdit:function(){
		$.ajax({
			url:"/order/order/refresh-order-declaration-edit?order_id="+$('[name=orderid]').val(),
			type: 'get',
			dataType:"html",
			success:function(htmlStr){
				if(htmlStr!='')
					$('#order-declaration-view').html(htmlStr);
			},
			error:function(){}
		});
	},
	
	//拆分订单移动商品
	splitOrderChildren:function(obj,$orderid,order_item_id,sign,signt){
		$sum=$('div[data-number='+$orderid+']').length;
		if($sum>1 && signt==-1){
			OrderCommon.choiceOrderChildren(obj,$orderid,order_item_id,$sum);
			return;
		}
		if(signt==-1)
			signt=0;
		
		$nowquantity=$('#qty'+order_item_id).html();
		$splotOrderDelList=$('#deldata').val();
		$splotOrderqtyList=$('#splitqty').val();
		
		if($('.css'+order_item_id).hasClass("display") && sign==0){
			bootbox.alert("不能增加数量，原始订单没有当前产品");
			return;
		}
		
		$.ajax({
			url:"/order/order/splot-order-children",
			type: 'post',
			dataType:"json",
			data:{order_item_id:order_item_id,splitqty:$splotOrderqtyList,dellist:$splotOrderDelList,signt:signt,sign:sign},
			success:function(htmlStr){
				if(htmlStr.code=='true'){
					$('#pk'+$orderid+'-'+htmlStr.signr).html(htmlStr.html);
					$('#deldata').val(htmlStr.dellist);
					$('#splitqty').val(htmlStr.splitqty);
					
					if(sign==2){
						var obj = eval('(' + htmlStr.splitqtylist + ')');
						$.each(obj, function(indexx, x) {		
							$.each(x, function(indexy,y) {	
								$.each(y, function(indexz,z) {
									$orderitemid=JSON.stringify(indexy).replace("\"","").replace("\"","");
									$qty=JSON.stringify(z).replace("\"","").replace("\"","");
									
									if($orderitemid==order_item_id){
										if($('#qty'+$orderitemid).html()==1 && $('.css'+$orderitemid).hasClass("display"))
											$('.css'+$orderitemid).removeClass('display');
										
										$('#qty'+$orderitemid).html($qty);
									}
								})
							})
						})
					}
					else if($nowquantity==1 && sign==0){
						$('.css'+order_item_id).addClass('display');
					}
					else if($('.css'+order_item_id).hasClass("display") && sign==1){
						$('.css'+order_item_id).removeClass('display');
					}
					else if(sign==1){
						$nowquantitynew=Number($nowquantity)+1;
						$('#qty'+order_item_id).html($nowquantitynew);
					}
					else{
						$nowquantitynew=Number($nowquantity)-1;
						$('#qty'+order_item_id).html($nowquantitynew);
					}
				}
				else{
					bootbox.alert(htmlStr.message);
					return;
				}
				
			},
			error:function(){}
		});
	},
	
	//拆分订单拆分包裹
	splitPackage:function($orderid){	
		$('#btnsplitPackage').attr("disabled","disabled");   //防止按太快数据出错
		$sum=$('div[data-number='+$orderid+']').length;
		$splotOrderDelList=$('#deldata').val();
		$.ajax({
			url:"/order/order/split-package",
			type: 'post',
			dataType:"json",
			data:{orderid:$orderid,sum:$sum,dellist:$splotOrderDelList},
			success:function(htmlStr){
				$('#deldata').val(htmlStr.dellist);
				
				if($sum<1){
					$('.delcss'+$orderid).append(htmlStr.html);
					$('.css'+$orderid).find('.glyphicon-chevron-right').removeClass('display');
				}
				else{
					$('#pk'+$orderid+'-'+(Number($sum)-1)).before(htmlStr.html);
				}
				$('#btnsplitPackage').removeAttr("disabled");
			},
			error:function(){$('#btnsplitPackage').removeAttr("disabled");}
		});
	},
	
	//拆分订单删除包裹
	splitPackageDel:function(obj,$orderid){
		$id=$(obj).parents().parents().parents().attr('id');
		$('#'+$id).remove();
		$sum_temp=$sum=$('div[data-number='+$orderid+']').length;
		$('div[data-number='+$orderid+']').each(function(){
			//修正订单号
			$sum_temp=Number($sum_temp)-1;
			$(this).attr('id','pk'+$orderid+'-'+$sum_temp);
			$txt=$orderid+'-'+(Number($sum_temp)+1)+'<div class="pull-right"><button type="button" class="btn btn-xs btn-danger" onclick="OrderCommon.splitPackageDel(this,\''+$orderid+'\')">删除</button></div>';
			$(this).find('.panel-heading').html($txt);
			
			//修正参数start
			$(this).find('.del').attr('data-index',$sum_temp);
			
			$btn_temp=$(this).find('#splitleft').attr('onclick');
			if($btn_temp!=null){
				var i = $btn_temp.lastIndexOf(',');
				$btn_temp=$btn_temp.substring(0,i);
				$btn_temp=$btn_temp+","+$sum_temp+")";
				$(this).find('#splitleft').attr('onclick',$btn_temp);
			}
			
			$btn_temp=$(this).find('#splitright').attr('onclick');
			if($btn_temp!=null){
				var i = $btn_temp.lastIndexOf(',');
				$btn_temp=$btn_temp.substring(0,i);
				$btn_temp=$btn_temp+","+$sum_temp+")";
				$(this).find('#splitright').attr('onclick',$btn_temp);
			}
			//修正参数end
		});
		
		$splotOrderDelList=$('#deldata').val();
		$splotOrderqtyList=$('#splitqty').val();
		
		//重新载入剩余的总数量
		$.ajax({
			url:"/order/order/split-package-del",
			type: 'post',
			dataType:"json",
			data:{divid:$id,dellist:$splotOrderDelList,splitqty:$splotOrderqtyList},
			success:function(htmlStr){
				$('#deldata').val(htmlStr.dellist);
				$('#splitqty').val(htmlStr.splitqty);
				var obj = eval('(' + htmlStr.splitqtylist + ')');
				$.each(obj, function(indexx, x) {		
					$.each(x, function(indexy,y) {	
						$.each(y, function(indexz,z) {
							$orderitemid=JSON.stringify(indexy).replace("\"","").replace("\"","");
							$qty=JSON.stringify(z);
							
							if($qty>0){
								if($('#qty'+$orderitemid).html()==1 && $('.css'+$orderitemid).hasClass("display"))
									$('.css'+$orderitemid).removeClass('display');
								
								$('#qty'+$orderitemid).html($qty);
							}
						})
					})
				})
				
				if(Number($sum)<1){
					$('.css'+$orderid).find('.glyphicon-chevron-right').addClass('display');
				}
			},
			error:function(){}
		});
	},
	
	//拆分订单选择包裹
	choiceOrderChildren:function(obj,$orderid,order_item_id,$sum){		
		event.stopPropagation();
		
		$htmls='<ul class="dropdown-menu">';
		for($i=$sum;$i>0;$i--){
			$htmls+='<li class="ng-scope"><a href="javascript:void(0);" class="ng-binding choice" data-order="'+$orderid+'" data-item="'+order_item_id+'" data-index="'+($i-1)+'">'+$orderid+'-'+$i+'</a></li>';
		}
		$htmls+='</ul>';
		$('#choiceOrderChildren').html($htmls);
		$chuankoutop=$(".modal-box").offset().top;
		$chuankouleft=$(".modal-box").offset().left;
		$temptop=$(obj).offset().top-$chuankoutop-$('.glyphicon-chevron-right').height()-12;
		$templeft=$(obj).offset().left-$chuankouleft+$('.glyphicon-chevron-right').width();
		$('#choiceOrderChildren').css("top",$temptop+'px');
		$('#choiceOrderChildren').css("left",$templeft+'px');
			
		$('#choiceOrderChildren').removeClass('display');
	},
	
	//订单界面下一个
	upOrDownOrder:function(type,orderid){
		$upOrDownDivtxt=$('#upOrDownDivtxt').val();
		$.ajax({
			type: "POST",
			url:'/order/order/edit-order-modal?orderid='+orderid+'&upOrDownDivtxt='+$upOrDownDivtxt , 
			dataType: 'html',
			success:function(html){		
				$('#upOrDownDiv').remove();
				$('.modal-main>.modal-body').html(html);
				
				$( "#edt-order-tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
				$( "#edt-order-tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
				$('#edt-order-tabs>ul>li>a').on('click',function(){
					$('[data-type=title]').text($(this).text());
				});
				$('[name=consignee_country_code]').on('change',function(){
					$('[name=consignee_country]').val($('[name=consignee_country_code] :selected').text());
				})
				
				$('[name="item[quantity][]"]').formValidation({validType:['trim','integer','length[1,4]'],tipPosition:'right',required:true});
				$('[name="item[sku][]"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
				
				$left1=($('#over-lay').width()-$('#over-lay').find('div[class="modal-box inside-modal"]').width())/2;
				$left2=$('#over-lay').find('div[class="modal-box inside-modal"]').width();
				$left=$left1+$left2;
				if($left<$left2)
					$('#upOrDownDiv').css('left',$left2);
				else
					$('#upOrDownDiv').css('left',$left);
				$temp=$('#upOrDownDiv');
				$('#upOrDownDiv').remove();
				$('#over-lay').prepend($temp);
				
				$("#trackingNumber").on("focus",function(){
					$('#reApplayTrackBtn').addClass('hidden');
				});
				
				$("#trackingNumber").on("blur",function(){
					if($('#trackingNumber').val()=='')
						$('#reApplayTrackBtn').removeClass('hidden');
				});
				
			},
			error :function () {
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
	},
	
	//订单编辑运输服务手动上传和自动上传切换
	agentRadioSelect:function(type){
		if(type==1){
			$('#change_ShippingType1').removeClass('hidden');
			$('#change_ShippingType2').addClass("hidden");
		}
		else{
			$('#change_ShippingType1').addClass('hidden');
			$('#change_ShippingType2').removeClass("hidden");
		}
	},
	
	//订单编辑运输服务获取跟踪号
	reApplyTrackNum:function(orderid){
		$shipping_method_code=$('select[name="change_shipping_method_code"]').val();
		$shipping_method_name=$('select[name="change_shipping_method_code"] option:selected').text();
		$change_warehouse=$('select[name="change_warehouse"]').val();
		$.modal({
			url:'/order/order/re-apply-track-num',
			method:'POST',
			data:{shipping_method_code:$shipping_method_code,orderid:orderid,change_warehouse:$change_warehouse},
		},
		'获取跟踪号',{footer:false,inside:false}).done(function($modal){
			$('#showshippingmethod').html('<label data-key="'+$shipping_method_code+'">'+$shipping_method_name+'</label>');
			
 			$height=parseInt($(document.body).height());
 			$('.modal-box').eq(0).after('<div id="modal-backdrop-level2" class="modal-backdrop  in" style="height:'+$height+'px;z-index:1;">');//插入遮盖
 			
			$('.queding').on("click",function(){
				$.showLoading();
				$.ajax({
					url: global.baseUrl + "delivery/order/get-data?delivery=1",
					data: $('#applyTrackNum').serialize(),
					type: 'post',
					success: function(response) {
						var result = JSON.parse(response);
						if (result.error) {
							 //失败
							$.hideLoading();
							bootbox.alert('订单'+orderid+"上传失败！"+result.msg);
							return false;
						 }else{
							 //成功
							 $.hideLoading();
							 bootbox.alert('订单'+orderid+'上传成功！'+result.msg);
							 $('#trackingNumber').val(result.data.tracking_number);
							 
							 //记录重新获取后的跟踪号
							 $("#trackingNumber").attr("data-no",result.data.tracking_number);
							 $("#trackingNumber").attr("data-service",$shipping_method_code);
							 
							 $('#trackNumberDiv').html(result.data.tracking_number);
							 if(result.data.tracking_number!='')
								 $('#reApplayTrackBtn').addClass('hidden');
							 $modal.close();
							 $('.modal-backdrop').remove();
							 return true;
						 } 
					},
					error: function(XMLHttpRequest, textStatus) {
						$.hideLoading();
						bootbox.alert("Internal Error");
						return false;
					}
				});
			});
			
			$('.sure_tracking_btn').click(function(){
//				OrderCommon.upOrDownOrder('', orderid);
//				$("#edt-order-tabs" ).tabs( "option", "active", 2);
				
				$.hideLoading();
//				bootbox.alert('订单'+orderid+'上传成功！'+result.msg);
				$('#trackingNumber').val($('#distribution_tracking_no').val());
				
				//记录重新获取后的跟踪号
				 $("#trackingNumber").attr("data-no",$('#distribution_tracking_no').val());
//				 $("#trackingNumber").attr("data-service",$shipping_method_code);
				
				$('#trackNumberDiv').html($('#distribution_tracking_no').val());
				if($('#distribution_tracking_no').val()!='')
					$('#reApplayTrackBtn').addClass('hidden');
				$modal.close();
				$('.modal-backdrop').remove();
				return true;
			});
 				 			
			$('.modal-close').click(function(){	
				$modal.close();
			});
		});
	},
	//订单编辑运输服务更改手动上传的物流商
	changeshippingmethodcode:function(obj,platForm){
		if(platForm=='aliexpress'){
			$.ajax({
					type: "POST",
					url:'/order/order/changeshippingmethodcode',
					data:{selectval:$(obj).val(),platForm:platForm},
					dataType: 'html',
					success:function(html){		 
						if(html=='' || html==null)
							$('#div_web2').remove();
						else
							$('#div_web2').html(html);
						return true;
					},
					error :function () {
						bootbox.alert("Internal Error");
						return false;
					}
				});
		}
		else if(platForm=='cdiscount'){
			if($(obj).val()=='Other'){
				$('#div_othermethod2').removeClass('hidden');
			}
			else{
				$('#div_othermethod2').addClass('hidden');
			}
		}
	},
	
	changeShippingCodeSetEmptyTracking:function(obj){
		if($(obj).val() == $("#trackingNumber").attr("data-service")){
			$('#trackingNumber').val($("#trackingNumber").attr("data-no"));
			$('#reApplayTrackBtn').addClass('hidden');
		}else{
			$('#trackingNumber').val('');
			$('#reApplayTrackBtn').removeClass('hidden');
		}
	},
	
	keys_change_find:function(obj){
		if($(obj).val() == 'sku'){
			$('#num').attr('placeholder', '多个请用分号隔开');
		}else{
			$('#num').attr('placeholder', '多个请用空格分隔或Excel整列粘贴');
		}
	},
	
	order_platform_find:function(type, platform, account_id){
		if(account_id == 'select_shops_xlb'){
			OrderCommon.open_platform_select(type,platform,
					function(){
						$('.selected_platform_save').click(function(){
							continentTypeArr = OrderCommon.sure_platform_seled();
							
							$('#selleruserid_combined').val(continentTypeArr[0]);
							
							if(type == 'delivery'){
								searchButtonClick();
							}else{
								$('#search').click();
							}
						});
					}
			);
		}else{
			$('#selleruserid_combined').val(account_id);
			$('#search').click();
		}
	},
	
	open_platform_select:function(type,platform,callback){
		$.modal({
			  url:'/order/order/get-platform-selected',
			  method:'get',
			  data:{type:type,platform:platform}
			},'选择账号',{footer:false,inside:false}).done(function($modal){
				callback();
				$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
		});
	},
	
	//添加到已选择的组合里
	platform_sel_click:function(obj){
		tmp_platform_code = $(obj).val();
		tmp_platform_account = $(obj).parent().text();
		
		if($(obj).prop("checked")==true){
			OrderCommon.setSelected_platform_div(tmp_platform_code, tmp_platform_account);
		}else{
			tmp_platform_code = tmp_platform_code.replace(/\@/g, '\\@');
			tmp_platform_code = tmp_platform_code.replace(/\./g, '\\.');
			
			$('#platform_selected_'+tmp_platform_code+'_div').remove();
		}
	},
	
	//添加已选中的Html代码
	setSelected_platform_div:function(tmp_platform_code, tmp_platform_account){
//		tmp_platform_code = tmp_platform_code.replace('.', '\\.');
		
		tmp_platform_code1 = tmp_platform_code.replace(/\@/g, '\\@');
		
		if($("#platform_selected_"+tmp_platform_code1+"_div").length <= 0){
			$('#selected_platform_div').append(
					'<div class="platform_label_div" id="platform_selected_'+tmp_platform_code+'_div" >'+
					'<label><input type="checkbox" value="'+tmp_platform_code+'" onclick="OrderCommon.platform_remove_click(this)" checked>'+tmp_platform_account+'</label>'+
					'</div>');
		}
	},
	
	//隐藏已选择的账号
	platform_remove_click:function(obj){
		tmp_platform_code = $(obj).val();
		
		tmp_platform_code = tmp_platform_code.replace(/\@/g, '\\@');
		tmp_platform_code = tmp_platform_code.replace(/\./g, '\\.');
		
		$("[value="+tmp_platform_code+"]").prop("checked",false);
//		$("#platform_label_div_"+tmp_platform_code).find('label').find('input').prop("checked",false);
		$(obj).parent().parent().remove();
	},
	
	//清空已选的账号
	selected_platform_clear:function(){
		$('#selected_platform_div').html('');
		$('.platform_label_div').find('label').find('input').prop("checked",false);
		$('.all_select_platform_div').find('label').find('input').prop("checked",false);
	},
	
	//全选事件
	all_platform_ck_click:function(obj){
		var div_id = $(obj).val();
		
		if($(obj).prop("checked")==true){
			$('#'+div_id).find('.platform_label_div:visible').each(function(){
				$(this).find('label').find('input').prop("checked",true);
				
				var tmp_platform_code = $(this).find('label').find('input').val();
				
				OrderCommon.setSelected_platform_div(tmp_platform_code, $(this).find('label').text());
			});
		}else{
			$('#'+div_id).find('.platform_label_div:visible').each(function(){
				$(this).find('label').find('input').prop("checked",false);
				
				tmp_platform_code = $(this).find('label').find('input').val();
				tmp_platform_code = tmp_platform_code.replace(/\./g, '\\.');
				tmp_platform_code = tmp_platform_code.replace(/\@/g, '\\@');
				
				$("#platform_selected_"+tmp_platform_code+"_div").remove();
			});
		}
	},
	
	//确定查找的账号
	sure_platform_seled:function(){
		var platform_code_selected = '';
		
		$('#selected_platform_div').find('.platform_label_div').each(function(){
			platform_code_selected += $(this).find('label').find('input').val() + ',';
		});
		
		platform_code_selected = platform_code_selected.substring(0,platform_code_selected.length-1);
		
		var continentTypeArr = new Array(platform_code_selected);
		return continentTypeArr;
	},
	
	//保存为常用账号
	selected_platform_common:function(type, platform){
		var platform_code_selected = [];
		
		$('#selected_platform_div').find('.platform_label_div').each(function(){
			platform_code_selected.push($(this).find('label').find('input').val());
		});
		
		if(platform_code_selected.length > 0){
			$tmp=$('#over-lay').children().eq(1).hide();
			
			$.modal({
			  url:'/order/order/platform-common-combination-list',
			  method:'get',
			  data:{}
			},'保存组合',{footer:false,inside:false}).done(function($modal){
				//确定
				$('.btn.btn-primary.platform_common').click(function(){
					com_name = $('#com_name_platform_id').val();
					
					if(com_name.length == 0){
						bootbox.alert('请先输入组合名称');
						return false;
					}
					
					if(com_name.length > 6){
						bootbox.alert('组合名称过长,不能超过6个字符');
						return false;
					}
					
					$.ajax({
						type: "POST",
						url:'/order/order/set-platform-common-combination',
						data:{type:type,platform:platform,platform_code_selected:platform_code_selected,com_name:com_name},
						dataType: 'json',
						success:function(response){
							if(response.error == 0){
								bootbox.alert(response.msg);
								
								$('#over-lay').children().eq(1).show();
								$modal.close();
							}else{
								bootbox.alert(response.msg);
								return false;
							}
						},
						error :function () {
							bootbox.alert("Internal Error");
							return false;
						}
					});
					
//					$('#over-lay').children().eq(1).show();
//					$modal.close();
					});
				//关闭页面
				$('.modal-close').click(function(){
					$('#over-lay').children().eq(1).show();
				});
			});
		}else{
			bootbox.alert("请先选择账号");
		}
		
//		$('#selected_platform_div').html('');
//		$('.platform_label_div').find('label').find('input').prop("checked",false);
//		$('.all_select_platform_div').find('label').find('input').prop("checked",false);
	},
	
	//账号常用组合清除
	platformcommon_remove:function(obj, type, platform, com_name){
//		alert(1);
		$.ajax({
			type: "POST",
			url:'/order/order/remove-platform-common-combination',
			data:{type:type,platform:platform,com_name:com_name},
			dataType: 'json',
			success:function(response){
				if(response.error == 0){
					bootbox.alert(response.msg);
					
					$(obj).parent().remove();
				}else{
					bootbox.alert(response.msg);
					return false;
				}
			},
			error :function () {
				bootbox.alert("Internal Error");
				return false;
			}
		});
		
	},
	
};

//lzhl	2017-10-19 	add	start
//用于物流事件翻译
if (typeof ListTracking === 'undefined')  ListTracking = new Object();
ListTracking.translateEventsToZh = function(obj,track_no,track_id){
	if($(obj).hasClass("translate-btn-checked"))
		return true;
	var to_lang = $(obj).attr("lang");
	$parent = $(obj).parents('div[id^="div_more_info"]');

	if(to_lang=='src' || to_lang==''){
		$parent.find("dl[lang='src']").show();
		$parent.find("dl[lang!='src']").hide();
		$(".translate-btn").removeClass("translate-btn-checked");
		$(obj).addClass("translate-btn-checked");
		return true;
	}

	$.showLoading();
	$.ajax({
		url:'/tracking/tracking/translate-events',
		dataType:'json',
		type:'POST',
		data:{track_no:track_no,track_id:track_id,to_lang:to_lang},
		success:function (data){
			if(data.success){
				$parent.find("dl[lang='"+to_lang+"']").remove();
				$parent.find("dl:last").after(data.html);
				$parent.find("dl[lang='"+to_lang+"']").show();
				$parent.find("dl[lang!='"+to_lang+"']").hide();
				$(".translate-btn").removeClass("translate-btn-checked");
				$(obj).addClass("translate-btn-checked");
				$.hideLoading();
			}else{
				$.hideLoading();
				bootbox.alert(Translator.t("翻译时遇到问题："+data.message));
			}
			return true;
		},
		error:function(){
			$.hideLoading();
			bootbox.alert(Translator.t("后台传输出错，请联系客服"));
			return false;
		}
	});
};
//lzhl	2017-10-19 	add	end 

$(document).ready(function(){
	if ($('#div_order_sync_ship_status_toolbar>a').length >0) {
		OrderCommon.initSetOrderSyncShipStatusCondition();
	}
	
	if ($(':checkbox[name=sys_unshipped_tag]').parent().length>0){
		if ($(':checkbox[name=sys_unshipped_tag]').parent().hasClass('hidden') == false){
			$(':checkbox[name=sys_unshipped_tag]').parent().addClass('hidden');
		}
	}
});

