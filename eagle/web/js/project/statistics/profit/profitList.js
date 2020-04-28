/**
 +------------------------------------------------------------------------------
 *利润 计算的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/statistics
 * @package		profit
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof profit === 'undefined')  profit = new Object();
profit.list=
{
	'init': function() 
	{
		$.initQtip();
		
		profit.list.profit_search(0);
		
		$("#profit_search").click(function()
		{
			profit.list.profit_search(0);
		});
		$("#calculat_profit").click(function()
		{
			profit.list.calculat_profit();
		});
		$("#select_all").click(function(){
			$(".profit_ck").prop('checked',this.checked);
		});
	},
	//搜索利润明细
	'profit_search': function(selectType, per_page, page) 
	{
		$("#select_all").prop('checked',false);
		
		var start_date = '';
		var end_date = '';
		var selectplatform = '';
		var selectstore = '';
		var search_type = '';
		var search_txt = '';
		var country = '';
		var date_type = '';
		var order_type = '';
		//换页、条/页 ,筛选条件不变
		if(selectType != 0)
		{
			//清除旧明细
			$('tr[class!="prodit_title"][class!="profit_sum"]').remove();
			
			start_date = $('#choose_start_date').val();
			end_date = $('#choose_end_date').val();
			selectplatform = $('#choose_selectplatform').val();
			selectstore = $('#choose_selectstore').val();
			search_type = $('#choose_search_type').val();
			search_txt = $('#choose_search_txt').val();
			country = $('#choose_country').val();
			date_type = $('#choose_date_type').val();
			order_type = $('#choose_order_type').val();
			
			//前页、后页
			if( selectType == 2 || selectType == 3)
			{
				page = $('#statistics_pager-group .btn-group  .pagination > .active a').first().html();
				if(selectType == 2 && page > 0)
					page--;
				else if(selectType == 3)
					page++;
			}
			
			if(page > 0)
				page--;
		}
		else
		{
			//清除旧明细
			$('tr[class!="prodit_title"]').remove();
			
			start_date = $('#statistics_startdate').val();
			end_date = $('#statistics_enddate').val();
			search_type = $('#search_type').val();
			search_txt = $('#search_txt').val();
			country = $('input[name="selected_country_code"]').val();
			date_type = $('#select_date_type').val();

			//已选平台
			$('input[name="select_platform"]:checked:visible').each(function()
			{
				selectplatform += $(this).val() +';';
			});
			//已选店铺
			$('input[name="select_store"]:checked:visible').each(function()
			{
				selectstore += $(this).val() +';';
			});
			//已选订单类型
			$('input[name="select_order_type"]:checked:visible').each(function()
			{
				order_type += $(this).val() +';';
			});
			
			//记录筛选条件
			$('#choose_start_date').val(start_date);
			$('#choose_end_date').val(end_date);
			$('#choose_selectplatform').val(selectplatform);
			$('#choose_selectstore').val(selectstore);
			$('#choose_search_type').val(search_type);
			$('#choose_search_txt').val(search_txt);
			$('#choose_country').val(country);
			$('#choose_date_type').val(date_type);
			$('#choose_order_type').val(order_type);
		}
		
		//获取条/页 
		if(per_page == null || per_page == 0)
			per_page = $('#statistics_pager-group .pageSize-dropdown-div .dropdown-menu > .active a').first().html();

		$.showLoading();
		var Url=global.baseUrl +'statistics/profit/get-order-statistics-info';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data : {
	        	'selectType' : selectType,
	        	'per-page' : per_page,
	        	'page' : page,
	        	'start_date' : start_date,
	        	'end_date' : end_date,
	        	'selectplatform' : selectplatform,
	        	'selectstore' : selectstore,
	        	'search_type' : search_type,
	        	'search_txt' : search_txt,
	        	'country' : country,
	        	'date_type' : date_type,
	        	'order_type' : order_type,
	        	},
			url: Url,
	        success:function(response){
	        	$.hideLoading();
	        	if(response['status'])
	        	{
        			if( response['data'].length > 0)
	        		{
        				var HtmlStr = '';
        				var row = 0;
        				if(selectType == 0)
        				{
	        				//合计行
	        				data = response['data'][0];
				        	HtmlStr +=
				        		"<tr class='profit_sum'>"+
				        			"<td style='border:1px solid #ccc;'></td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_source_create_time'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_id'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['grand_total'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['commission_total'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['paypal_fee'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['actual_charge'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['logistics_cost'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['purchase_cost'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['profit'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['profit_per'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['sales_per'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['logistics_weight'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_source_order_id'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_source'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['selleruserid'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['tracking_number'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['service_name'] +"</td>"+
								"</tr>";
				        	row++;
        				}
        				for(var n = row; n < response['data'].length; n++)
        				{
		    				data = response['data'][n];
				        	//明细信息
				        	HtmlStr +=
				        		"<tr>"+
				        			"<td style='border:1px solid #ccc;'>" +
				        				"<input type='checkbox' class='profit_ck' name='profit_order_id[]' value='"+ data['order_id'] +"' ></td>"+
									"<td style='border:1px solid #ccc;'>"+ data['order_source_create_time'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_id'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['grand_total'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['commission_total'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['paypal_fee'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['actual_charge'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['logistics_cost'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['purchase_cost'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['profit'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['profit_per'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['sales_per'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['logistics_weight'] +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ data['order_source_order_id'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_source'] +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ data['selleruserid'] +"</td>"+
									"<td style='border:1px solid #ccc;'text-align:center;'>"+ data['tracking_number'] +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ data['service_name'] +"</td>"+
								"</tr>";
        				}

        				$("#prodit_table").append(HtmlStr);
        				$("#statistics_pager-group").html(response['pagination']);
        				$('#statistics_pager-group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
						{
        					$(this).attr('href', 'javascript:profit.list.profit_search(1, '+ $(this).html() +', 0);');
						});
						$('#statistics_pager-group .btn-group > .pagination > li a').each(function()
						{
        					$(this).attr('href', 'javascript:profit.list.profit_search(1, 0, '+ $(this).html() +');');
						});
						
        				$('#statistics_pager-group .btn-group .prev a').attr('href', 'javascript:profit.list.profit_search(2);');
        				$('#statistics_pager-group .btn-group .next a').attr('href', 'javascript:profit.list.profit_search(3);');
	        		}
        			else{
        				$("#statistics_pager-group").html(response['pagination']);
        				
        				$('#statistics_pager-group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
						{
        					$(this).attr('href', 'javascript:profit.list.profit_search(1, '+ $(this).html() +', 0);');
						});
        			}
	        	}
	        	else{
	        		$("#statistics_pager-group").html(response['pagination']);
	        		
	        		$('#statistics_pager-group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
					{
    					$(this).attr('href', 'javascript:profit.list.profit_search(1, '+ $(this).html() +', 0);');
					});
	        	}
	        },
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('系统错误，请联系客服！'));
				return false;
			}
	    });
	},
	
	'calculat_profit': function() 
	{
		idstr='';
		$('input[name="profit_order_id[]"]:checked').each(function(){
			idstr+=','+$(this).val();
		});
		
		if(idstr == '')
		{
	    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
			return false;
	    }
		
		$.showLoading();
		$.ajax({
			type: "POST",
				//dataType: 'json',
				url:'/statistics/profit/profit-order', 
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
	},
	//导入物流成本、采购成本
	'importProfitData' : function (type) 
	{
		if(type=='product_cost'){
			var template_url = "/template/商品成本录入.xls";
			var form_url = "/statistics/profit/excel2-order-cost";
		}
		else if(type=='logistics_cost_ordersource'){
			var template_url = "/template/订单物流成本录入平台订单号.xls";
			var form_url = "/statistics/profit/excel2-order-cost";
		}
		else if(type=='logistics_cost_tracknumber'){
			var template_url = "/template/订单物流成本录入_跟踪号.xls";
			var form_url = "/statistics/profit/excel2-order-cost";
		}
		if (typeof(importFile) != 'undefined'){
			importFile.showImportModal(
				Translator.t('请选择导入的excel文件') , 
				form_url , 
				template_url , 
				type,
				function(result){
					var ErrorMsg = "";
					if(typeof(result)=='object'){
						if(result.success){
							var success = 1;
						}else
							var success = 0;
						
						if(typeof(result.message)!=='undefined'){
							ErrorMsg += result.message;
						}
					}else{
						ErrorMsg += result;
						var success = 0;
					}
					if (ErrorMsg != "" && success!==1){
						ErrorMsg= "<div style='height: 600px;overflow: auto;'>"+ErrorMsg+"</div>";
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: ErrorMsg,
							callback: function() {
								profit.list.profit_search(0);
							}
						});
					}else if (ErrorMsg != "" && success==1){
						ErrorMsg= "<div style='height: 600px;overflow: auto;'>部分导入成功,点击确认刷新窗口数据。"+ErrorMsg+"</div>";
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: ErrorMsg,
							callback: function() {
								profit.list.profit_search(0);
							}
						});
					}else{
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t('导入成功,点击确认刷新窗口数据'),
							callback: function() {
								profit.list.profit_search(0);
							}
						});
					}
				}
			);
		}else{ bootbox.alert(Translator.t("没有引入相应的文件!")); }
	},
	
	'exportExecl' : function()
	{
		if($('tr[class!="prodit_title"]').length == 0)
		{
			$.alertBox('<p class="text-warn">无数据导出！</p>');
			return false;
		}
		
		var start_date = $('#choose_start_date').val();
		var end_date = $('#choose_end_date').val();
		var selectplatform = $('#choose_selectplatform').val();
		var selectstore = $('#choose_selectstore').val();
		var search_type = $('#choose_search_type').val();
		var search_txt = $('#choose_search_txt').val();
		var country = $('#choose_country').val();
		var date_type = $('#choose_date_type').val();
		var order_type = $('#choose_order_type').val();
		
		var params = 'start_date='+ start_date +'&end_date='+ end_date +'&selectplatform='+ selectplatform +'&selectstore='+ selectstore +'&search_type='+ search_type +'&search_txt='+ search_txt +'&country='+ country +'&date_type='+ date_type +'&order_type='+ order_type;
		
		window.open("/statistics/profit/export-excel?"+params,'_blank');
	},
	
	//手动同步最新已完成订单
	'synchronizeOrder' : function () 
	{
		$.showLoading();
		
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
			url:'/statistics/profit/synchronize-order', 
			success: function (result) 
			{
				$.hideLoading();
				bootbox.alert("同步成功！");
				profit.list.profit_search(0);
			},
			error: function()
			{
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				profit.list.profit_search(0);
				return false;
			}
		});
	},
	
	//打开设置汇率界面
	'showSettingRate' : function () {
		$.showLoading();
		$.ajax({
			type: "POST",
			url: '/statistics/profit/show-setting-rate',
			data: {},
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "rate_setting_dialog",
					title: Translator.t("设置汇率"),//
					message: result,
					buttons:{
						OK: {
							label: Translator.t("保存"),  
							className: "btn-primary",
							callback: function () {
								profit.list.saveRate();
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
				return true;
				
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
	
	saveRate:function(){
		$.showLoading();
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data: $("#rate_form").serialize(),
			url:'/statistics/profit/save-rate', 
			success: function (result){
				$.hideLoading();
				bootbox.alert("保存成功！");
				return false;
			},
			error: function()
			{
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			}
		});
	},
	
	//手动更新未计算采购成本订单的采购成本
	'updateProductCostFromUnSet' : function () 
	{
		$.showLoading();
		
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
			url:'/statistics/profit/update-product-cost-from-un-set', 
			success: function (result) 
			{
				$.hideLoading();
				bootbox.alert("更新成功！");
				profit.list.profit_search(0);
			},
			error: function()
			{
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				profit.list.profit_search(0);
				return false;
			}
		});
	},
}
