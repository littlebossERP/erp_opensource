/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lolo <jiongqiang.xiao@witsion.com> 2014-02-27 eagle 1.0
+----------------------------------------------------------------------
| Copy by: lzhl <zhiliang.lu@witsion.com> 2015-04-10 eagle 2.0
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *采购单列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		purchase
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof purchaseOrder === 'undefined')  purchaseOrder = new Object();
purchaseOrder.list={
	'init': function() {
		$('#btn-create').unbind('click').click(function(){
			$.showLoading();
			$.get( global.baseUrl+'purchase/purchase/create',
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "create_or_edit_purchase_win", 
						title :  Translator.t('新建 采购单'),
						closeButton: true,
						message: data,
					});	
			});
		});
		$('#purchaselist_startdate').datepicker({dateFormat:"yy-mm-dd"});
		$('#purchaselist_enddate').datepicker({dateFormat:"yy-mm-dd"});
		
		$(".shoplist .select_one").unbind('click').click(function(){
			if ( typeof($(this).attr('checked'))=='undefined' || $(this).attr('checked')==false ){
				$(this).attr('checked','checked');
			}else{
				$(this).attr('checked',false);
			}
			purchaseOrder.list.checkBtnDisable();
		});
		$("#select_all").click(function(){
			$(".shoplist .select_one").prop('checked',this.checked);
			// $(".shoplist .select_one").attr('checked',this.checked);//jquery 问题
			purchaseOrder.list.checkBtnDisable()
		});
		$('#btn_clear').click(function() {
			url = global.baseUrl + "purchase/purchase/index";
			window.location.href = url;
		});
	},
	'checkBtnDisable' : function () {
		var selectCount = $(".select_one:checked").length;
		if (selectCount > 0) {
			$('#batch_purchase_stockin_btn').removeAttr('disabled');
			$('#batch_cancel_purchaseOrder_btn').removeAttr('disabled');
		} else {
			$('#batch_purchase_stockin_btn').attr('disabled','disabled');
			$('#batch_cancel_purchaseOrder_btn').attr('disabled','disabled');
		}
	},
	//修改采购单
	'editPurchaseOrder' : function (id) {
		$('.create_or_edit_purchase_win').modal('hide');
		$('.create_or_edit_purchase_win').on('hidden.bs.modal', '.modal', function(event) {
			$(this).removeData('bs.modal');
		});
		$.showLoading();
		$.get( global.baseUrl+'purchase/purchase/edit?id='+id,
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title :  Translator.t('修改 采购单'),
					closeButton: true,
					className: "create_or_edit_purchase_win", 
					message: data,
				});	
		});
	},
	//查看采购单详情
	'viewPurchaseOrder' : function (id) {
		$.showLoading();
		$.get( global.baseUrl+'purchase/purchase/view?id='+id,
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title :  Translator.t('查看 采购单'),
					closeButton: true,
					className: "create_or_edit_purchase_win",
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});	
		});
	},
	//删除采购单
	'deletePurchaseOrder' : function(purchase_order_id){
		$.showLoading();
		$.post( global.baseUrl+'purchase/purchase/delete?purchase_order_id='+purchase_order_id,
			function (data){
				$.hideLoading();
				bootbox.dialog({
					closeButton: false,
					className: "operation_result", 
					buttons: {
						OK: {  
							label: Translator.t("确认"),  
							className: "btn-primary",  
							callback: function () {
								if(data['success']){
									window.location.reload();
								}
							}  
						}, 
					},
					message:  (data['message']=='')?Translator.t('删除成功！'):data['message'],
				});	
			}
			,'json'
		);
	},
	//作废采购单
	'cancelPurchaseOrder' : function(id){
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
			message: Translator.t("确定作废采购单？"),
			callback: function(r){
				if(r){
					$.showLoading();
					$.post( global.baseUrl+'purchase/purchase/cancel?ids='+id,
						function (data){
							$.hideLoading();
							bootbox.dialog({
								closeButton: false,
								className: "operation_result", 
								buttons: {
									OK: {  
										label: Translator.t("确认"),  
										className: "btn-primary",  
										callback: function () {
											if(data['success']){
												window.location.reload();
											}
										}  
									}, 
								},
								message:  (data['message']=='')?Translator.t('作废成功！'):data['message'],
							});	
						}
						,'json'
					);
				}
			}
		});
	},
	//采购单入库，全部入库
	'purchaseStockIn' : function(ids){
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
			message: Translator.t("确定入库？"),
			callback: function(r){
				if(r){
					$.showLoading();
					$.post( global.baseUrl+'purchase/purchase/quick-purchsae-stock-in?ids='+ids,
						function (data){
							$.hideLoading();
							bootbox.dialog({
								closeButton: false,
								className: "operation_result", 
								buttons: {
									OK: {  
										label: Translator.t("确认"),  
										className: "btn-primary",  
										callback: function () {
											if(data['success']){
												window.location.reload();
											}
										}  
									}, 
								},
								message:  (data['message']=='')?Translator.t('入库成功！'):data['message'],
							});	
						}
						,'json'
					);
				}
			}
		});
	},
	//显示采购单入库，分批入库
	'purchaseChooseStockIn' : function(id){
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/purchase/purchase/stock-in-dialog',
			data: {id: id},
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "stock_in_dialog",
					title: Translator.t("采购入库"),//
					message: result,
					buttons:{
						OK: {
							label: Translator.t("保存"),  
							className: "btn-primary",
							callback: function () {
								purchaseOrder.list.savePurchaseInStock();
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
				return true;
				
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
	
	//批量作废
	'batchCancelPurchaseOrder' : function(){
		var selectCount = $(".select_one:checked").length;
		var orderIdStr = '';
		for(var i=0;i<selectCount; i++){
			var orderId = $(".select_one:checked").eq(i).val();
			var status = $("td[orderId="+orderId+"]").attr('value');
			/*if(status > 1 && status < 7){
				bootbox.alert({
					title:'无效操作',
					message:Translator.t('存在货品已发出的采购单，不能批量取消'),
				});
				return false;
			}*/
			if(status == 5){
				bootbox.alert({
					title:'无效操作',
					message:Translator.t('存在已全部入库的订单，不能批量取消'),
				});
				return false;
			}
			if(orderIdStr =='')
				orderIdStr += orderId;
			else
				orderIdStr += ','+ orderId;
		}
		if(orderIdStr.replace(/[ ]/g,"")==''){
			bootbox.alert({
				title:'无效操作',
				message:Translator.t('没有选择采购单'),
			});
			return false;
		}else{
			purchaseOrder.list.cancelPurchaseOrder(orderIdStr);
		}
	},
	//批量入库
	'batchPurchaseStockIn' : function(){
		var selectCount = $(".select_one:checked").length;
		var orderIdStr = '';
		/*
		for(var i=0;i<selectCount; i++){
			var order = $(".select_one:checked").eq(i).val();
			var status = $("td['order="+order+"']").attr('value');
			if(status >= 5){
				bootbox.alert({
					title:'无效操作',
					message:Translator.t('存在已入库采购单，不能重复入库操作'),
				});
				return false;
			}
			if(orderStr =='')
				orderStr += order;
			else
				orderStr += ','+ order;
		}*/
		var hasInStocked = false;
		$(".select_one:checked").each(function(){
			var orderId = this.value;
			var status = $(this).parents("tr").find("td[tag='status']").attr('value');
			if(status >= 5){
				hasInStocked = true;
			}
			else
				orderIdStr+=(orderIdStr =='')?orderId:','+orderId;
		});
		
		if(hasInStocked){
			bootbox.alert({
				title:'无效操作',
				message:Translator.t('存在已入库采购单，不能通过采购模块入库，请向仓库模块确认'),
			});
			return false;
		}
		if(orderIdStr==''){
			bootbox.alert({
				title:'无效操作',
				message:Translator.t('没有选择采购单'),
			});
			return false;
		}
		purchaseOrder.list.purchaseStockIn(orderIdStr);
	},
	'exportExecl' : function(){
		var purchaseIds='';
		$(".select_one:checked").each(function(){
			var purchaseId = this.value;
			purchaseIds+=(purchaseIds =='')?purchaseId:','+purchaseId;
		});
		if(purchaseIds==''){
			bootbox.alert({
				title:'无效操作',
				message:Translator.t('没有选择采购单'),
			});
			return false;
		}
		window.open("/purchase/purchase/export-excel?purchase_ids="+purchaseIds,'_blank');
	},
	//导出excel采购单
	'exportExeclSelect':function(type){
		var str = '';
		var count = 0;
		
		if(type == 0)
		{
			$('input[name="orderSelected"]:checked').each(function(){
				str += $(this).val() + ',';
				count++;
			});
			
			if(str.replace(',','') == '')
			{
				if(str.replace(',','') == '')
				{
			    	$.alertBox('<p class="text-warn">请勾选需导出的商品！</p>');
					return false;
			    }
		    }
		}
		else{
			//筛选条件
			str = $('#search_condition').val();
			//总数量
			var c = $('#search_count').val();
			if( c != null && c != '')
				count = c;
		}

		//当小于500行时，直接前台导出
		if(count > 0 && count < 500){
			window.open("/purchase/purchase/straight-export-excel?type="+type+"&purchase_ids="+str,'_blank');
		}
		else{
			$.modal({
					  url:'/purchase/purchase/add-export-excel',
					  method:'POST',
					  data:{type : type, 
						    str : str},
				},
				'导出Excel',{footer:false,inside:false}).done(function($modal){
				});
		}
	},
	
	//采购分批入库，保存
	'savePurchaseInStock' : function(purchase_id) {
		$.showLoading();
		$.ajax({
			type: 'post',
			url: '/purchase/purchase/save-in-stock',
			data: $('#update-purchase-in-stock-form').serialize(),
			dataType: 'json',
			success:function(result){
				$.hideLoading();
				bootbox.dialog({
					closeButton: false,
					className: "operation_result", 
					buttons: {
						OK: {  
							label: Translator.t("确认"),  
							className: "btn-primary",  
							callback: function () {
								if (result.success){
									window.location.reload();
								}
							}  
						}, 
					},
					message:  (result.msg=='')?Translator.t('入库成功！'):result.msg,
				});
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			},
		});
	},
	
	
	//打印
	'printPurchaseOrder' : function(){
		var selectCount = $(".select_one:checked").length;
		var orderIdStr = '';
		var count = 0;
		for(var i=0;i<selectCount; i++){
			var orderId = $(".select_one:checked").eq(i).val();
			if(orderIdStr =='')
				orderIdStr += orderId;
			else
				orderIdStr += ','+ orderId;
			count++;
		}
		if(count > 10){
			bootbox.alert({
				title:'无效操作',
				message:Translator.t('最多只能同时打印5张采购单'),
			});
			return false;
		}
		if(orderIdStr.replace(/[ ]/g,"")==''){
			bootbox.alert({
				title:'无效操作',
				message:Translator.t('没有选择采购单'),
			});
			return false;
		}

		window.open('/purchase/purchase/print'+'?order_ids='+orderIdStr);
	},
	
	//点击单个显示商品详情
	'showItems' : function(purchase_id){
		var item_tr = $('#table_list_tr_items_'+ purchase_id);
		//显示 / 隐藏
		if(item_tr.css('display') != 'none'){
			item_tr.css('display', 'none');
		}
		else{
			item_tr.css('display', 'table-row');
		}
		//判断商品详情是否需重新加载
		if(item_tr.find('table').length == 0){
			purchaseOrder.list.GetPurchaseItems(purchase_id);
		}
	},
	
	//查询 / 显示 采购商品详情
	'GetPurchaseItems' : function(purchase_ids){
		$.showLoading();
		$.ajax({
			type: 'post',
			url: '/purchase/purchase/get-purchase-items',
			data: {'purchase_ids' : purchase_ids},
			dataType: 'json',
			success: function(res){
				$.hideLoading();
				if(res.success){
					for(var purchase_id in res.list){
						var $obj = $('#table_list_tr_items_'+ purchase_id +' > td');
						$obj.html('');
						
						var items = res.list[purchase_id];
						for(var key in items){
							if(items[key].sku != undefined){
								var qty_style = '';
								if(items[key].qty > 1){
									qty_style = 'style="color: red; "';
								}
								var html = 
									'<table class="purchase_item_table">'+
										'<tr>'+
											'<td style="width: 68px; text-align: center; ">'+
												'<div style="border: 1px solid #ccc; width: 62px; height: 62px; ">'+
													'<img class="prod_img" style="max-width: 100%; max-height: 100%; width: auto; height: auto;" src="'+ items[key].photo_primary +'" data-toggle="popover" data-content="<img width=\'350px\' src=\''+ items[key].photo_primary +'\'>" data-html="true" data-trigger="hover">'+
												'</div>'+
											'</td>'+
											'<td>'+
												'<div class="div_pro_sku_name">'+ items[key].sku +'</div>'+
												'<div class="div_pro_sku_name" style="color: #999999;">'+ items[key].name +'</div>'+
											'</td>'+
											'<td '+ qty_style +'>x '+ items[key].qty +'</td>'+
											'<td>'+ items[key].price +'</td>'+
										'</tr>'+
									'</table>';
	
								$obj.append(html);
							}
						}
						
						$('.prod_img').popover();
					}
				}
				else{
					bootbox.alert(res.msg);
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
	
	//显示创建1688订单界面
	'show1688Purchase' : function(purchase_id){
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/purchase/purchase/show1688-dialog',
			data: {'purchase_id' : purchase_id},
			success: function(res){
				$.hideLoading();
				bootbox.dialog({
					className: "create_1688_dialog",
					title: "新建 / 关联 1688订单",
					message: res,
					buttons:{
						OK: {
							label: "保存",
							className: "btn-primary",
							callback: function(){
								var tab = $('.create_1688_tab .active');
								if(tab.attr("tab-type") == "1"){
									//创建订单
									purchase1688Create.list.create1688Purchase();
								}
								else if(tab.attr("tab-type") == "2"){
									//关联订单
									purchase1688Create.list.binding1688Order();
								}
								
								
								return false;
							}
						},
						Cancel: {
							label: "返回",
							className: "btn-default",
							callback: function(){
							}
						}
					}
				});
				$('.prod_img').popover();
				purchase1688Create.list.init();
				return true;
			},
			error: function(){
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
	
	//更新1688订单信息
	'update1688OrderInfo' : function(){
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/purchase/purchase/update1688-order-info',
			data: {},
			success: function(res){
				$.hideLoading();
				window.location.reload();
			},
			error: function(){
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	}
	
	
}
