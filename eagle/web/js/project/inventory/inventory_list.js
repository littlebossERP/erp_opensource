/**
 * +------------------------------------------------------------------------------
 * inventory index/list的界面js
 * +------------------------------------------------------------------------------
 * 
 * @category 	js/project
 * @package 	inventory
 * @subpackage	Exception
 * @author 		lzhil <zhiliang.lu@witsion.com>
 * @version 	1.0
 * +------------------------------------------------------------------------------
 */

if (typeof inventory === 'undefined')
	inventory = new Object();
inventory.list = {
	'init' : function() {
		
		// Boostrap modal data destroy , for
		$('body').on('hidden.bs.modal', '.modal', function(event) {
			$(this).removeData('bs.modal');
		});

		$('#btn_clear').click(function() {
			url = global.baseUrl + "inventory/inventory/index";
			window.location.href = url;
		});

		$('#btn_upload').click(function() {
			ListTracking.ShowExcelImport();
		});

		$('#btn_export').click(function() {
			if ($('#query_track_no_list').val() == ""
					|| $('#query_track_no_list').val() == undefined) {
				bootbox.alert($('#query_empty_message').val());
				return false;
			}

			url = "/tracking/tracking/export_manual_excel?track_no_list="
					+ $('#query_track_no_list').val();
			window.open(url);
		});
		
		$('#btn_historyDetail_search').unbind('click').click(function(){
			inventory.list.searchHistoryDetail();
		});
		$(".select_one").unbind('click').click(function(){
			$(this).prop('checked',this.checked);
		});
		$("#select_all").click(function(){
			$(".select_one").prop('checked',this.checked);
		});

		$('.form-horizontal:first select').change(function(){
			$('.form-horizontal:first').submit();
		});
	},
	//导出excel
	'exportExcelSelect':function(type){
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
		    	$.alertBox('<p class="text-warn">请勾选需导出的商品！</p>');
				return false;
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
			window.open("/inventory/inventory/straight-export-excel?type="+type+"&product_ids="+str,'_blank');
		}
		else{
			$.modal({
					  url:'/inventory/inventory/add-export-excel',
					  method:'POST',
					  data:{type : type, 
						    str : str},
				},
				'导出Excel',{footer:false,inside:false}).done(function($modal){
				});
		}
	},

	//查看历史
	'viewInventoryHistory' : function(obj,warehouse_id) {
		var sku= $(obj).parents("tr").find('td[name="chang_sku[]"]')[0].innerHTML;
		
		var warehousNmae = "";
		for (var i=0;i<warehouseInfo.length;i++){
			if (warehouseInfo[i]['key'] == warehouse_id){
				warehouseNmae = warehouseInfo[i]['value'];
				break;
			}
		}
		$.showLoading();
		$.get(global.baseUrl + 'inventory/inventory/getinventoryhistory?sku='+sku+'&warehouse_id='+warehouse_id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "viewHistory",
				title : Translator.t(sku +" 在仓库‘"+warehouseNmae+"’ 的库存变化历史"),
				closeButton: true,
				buttons : {
					Cancel : {
						label : Translator.t("关闭"),
						className : "btn-default",
						callback : function() {
							$('.viewHistory').modal('hide');
							$('.viewHistory').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}
				},
				message : data,
			});
		});
	},
	//根据条件显示历史
	'searchHistoryDetail' : function(){
		var sdate = $('input#history_startdate').val();
		var edate = $('input#history_enddate').val();
		var sku = $('#historyDetail_search_sku').val();
		var warehouse_id = $('#btn_historyDetail_search').attr('warehouse_id');
		$("#historyList_table").queryAjaxPage({
			'sku':sku,
			'warehouse_id':warehouse_id,
			'sdate':sdate,
			'edate':edate,
		});
	},
	//保存有变化的安全库存
	'saveSafetyStock' : function()
	{
		var errcount = 0;
		
		//清除错误提示信息
		$('[name="delivery_err_info"]').remove();
		
		$('input[name="safety_stock_info[]"]').each(function()
		{
			var obj = $(this);
			
			var safety_stock = obj.val();
			var value_old = obj.attr("value_old");
			var warehouse_id = obj.attr("warehouse_id");
			var sku = obj.attr("sku");
			
			if( safety_stock != value_old)
			{
				$.ajax(
				{
			        type : 'post',
			        cache : 'false',
			        dataType: 'json',
			        data : 
			        {
			        	safety_stock:safety_stock,
			        	warehouse_id:warehouse_id,
			        	sku:sku,
			        },
					url: global.baseUrl +'inventory/warehouse/save-safety-stock',
			        success:function(response) 
			        {
			        	if(response['status'] == 1)
			        	{
			        		obj.parent().parent().after('<tr name="delivery_err_info"><td colspan="11" style="text-align:left; color:red">'+ response['msg'] +'</tr></td>');
			        		errcount++;
			        	}
			        },
				});
			}
		});

		if(errcount == 0)
		{
			$.alert('保存成功！','danger');
			window.location.reload();
		}
	},
	
	//删除库存记录
	'deleteStock' : function(stock_id, info){
		bootbox.confirm(Translator.t("确定删除此库存信息？ "+info),function(r){
			if (! r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/inventory/inventory/delete-stock',
				data: {stock_id_list: stock_id} , 
				success: function (result) {
					//$.hideLoading();
					if (result.success){
						$.hideLoading();
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("成功删除库存信息!"),
							callback: function() {
								window.location.reload();
							}, 
						});
					}
					else{
						$.hideLoading();
						bootbox.alert(result.msg);
					}
				},
				error :function () {
					$.hideLoading();
					bootbox.alert('异常错误，请联系客服！');
				}
			});
		});
	},
	
	//批量删除库存记录
	'batchDeleteStock' : function(){
		var stock_id_list = '';
		$('input[name="orderSelected"]:checked').each(function(){
			stock_id_list += $(this).val() + ',';
		})
		if(stock_id_list.replace(',','') == ''){
			$.alertBox('<p class="text-warn">请勾选需删除的库存信息！</p>');
			return false;
		}
		
		inventory.list.deleteStock(stock_id_list, '');
	},
	
	//打开库存、单价编辑窗体
	"showEditStock" : function(stock_id){
		$.modal
		(
			{
			  url:'/inventory/inventory/show-edit-stock',
			  method:'post',
			  data:{stock_id: stock_id}
			},
			'编辑库存信息',
			{
				footer:false,inside:false
			}
		).done(function($modal){
			
			$('input[name="edit_stock_qty"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
			$('input[name="edit_stock_price"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
			$('input[name="edit_safety_stock"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
			
			$('#btn_edit_stock').click(function()
			{
				$.showLoading();
				var $show_time = $('#show_time').val();
				var stock_id = $('#edit_prod_stock_id').val();
				var location_grid = $('input[name="edit_location_grid"]').val();
				var stock_qty = $('input[name="edit_stock_qty"]').val();
				var stock_price = $('input[name="edit_stock_price"]').val();
				var safety_stock = $('input[name="edit_safety_stock"]').val();
				
				$.ajax({
					type : 'post',
				    cache : 'false',
				    dataType: 'json',
				    data: {
				    		'show_time': $show_time,
				    		'stock_id': stock_id,
				    		'location_grid': location_grid,
				    		'stock_qty': stock_qty,
				    		'stock_price': stock_price,
				    		'safety_stock': safety_stock,
				    	},
					url:'/inventory/inventory/save-one-stock', 
					success: function (result) 
					{
						$.hideLoading();
						if(result.success){
							$.alertBox('<p class="text-warn">更新成功！</p>');
							window.location.reload();
						}
						else{
							bootbox.alert(result.msg);
							return false;
						}
					},
					error: function()
					{
						bootbox.alert("出现错误，请联系客服求助...");
						$.hideLoading();
						return false;
					}
				});
			});
		});
	},
	
	//批量编辑库存信息
	ShowBatchEditStock : function () {
		var stock_id_list = '';
		$('input[name="orderSelected"]:checked').each(function(){
			stock_id_list += $(this).val() + ',';
		})
		if(stock_id_list.replace(',','') == ''){
			$.alertBox('<p class="text-warn">请勾选需编辑的库存信息！</p>');
			return false;
		}
		
		$.showLoading();
		$.ajax({
			type: "POST",
			url: '/inventory/inventory/show-batch-edit-stock',
			data: {'stock_id_list': stock_id_list},
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "stock_info",
					title: Translator.t("批量编辑库存信息"),//
					message: result,
					buttons:{
						OK: {
							label: Translator.t("保存"),  
							className: "btn-primary",
							callback: function () {
								var rtn = edit_stock.saveStock();
								if(rtn){
									window.location.reload();
									return true;
								}
								else return false;
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
	
	//更新商品采购价到库存列表
	'UpdateStockPrice' : function(){
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/inventory/inventory/update-stock-price',
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
	},
	
};
