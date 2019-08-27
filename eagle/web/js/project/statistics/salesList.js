/**
 +------------------------------------------------------------------------------
 *销售统计js
 +------------------------------------------------------------------------------
 * @category	js/project/statistics
 * @package		sales
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof sales === 'undefined')  sales = new Object();
sales.list=
{
	'init': function() 
	{
		sales.list.sales_search(0);
		
		$("#sales_search").click(function()
		{
			sales.list.sales_search(0);
		});
		
		$("#select_choose_type").change(function()
		{
			if($(this).val() == 'title'){
				$('#select_choose_value').attr('placeholder','请输入 产品标题');
				$('#select_choose_value').attr('title','根据输入的产品标题查询');
			}
			else{
				$('#select_choose_value').attr('placeholder','请输入  SKU');
				$('#select_choose_value').attr('title','根据输入的SKU查询');
			}
		});
	},
	
	//搜索报表明细
	'sales_search': function(selectType, per_page, page) 
	{
		$("#select_all").prop('checked',false);
		
		var start_date = '';
		var end_date = '';
		var selectplatform = '';
		var selectstore = '';
		var currency = '';
		var choose_type = '';
		var choose_value = '';
		var country = '';
		var sort = $('.lb_sort').attr('value');
		var sorttype = $('.lb_sort').attr('sorttype');
		
		//换页、条/页 ,筛选条件不变
		if(selectType != 0)
		{
			//清除旧明细
			$('tr[class!="sales_title"][class!="sales_sum"]').remove();
			
			start_date = $('#choose_start_date').val();
			end_date = $('#choose_end_date').val();
			selectplatform = $('#choose_selectplatform').val();
			selectstore = $('#choose_selectstore').val();
			currency = $('#choose_currency').val();
			choose_type = $('#choose_type').val();
			choose_value = $('#choose_value').val();
			country = $('#choose_country').val();

			//前页、后页
			if( selectType == 2 || selectType == 3)
			{
				page = $('#statistics_pager_group .btn-group  .pagination > .active a').first().html();
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
			$('tr[class!="sales_title"]').remove();
			
			start_date = $('#statistics_startdate').val();
			end_date = $('#statistics_enddate').val();
			currency = $('#select_currency').val();
			choose_type = $('#select_choose_type').val();
			choose_value = $('#select_choose_value').val();
			country = $('input[name="selected_country_code"]').val();

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
			
			//记录筛选条件
			$('#choose_start_date').val(start_date);
			$('#choose_end_date').val(end_date);
			$('#choose_selectplatform').val(selectplatform);
			$('#choose_selectstore').val(selectstore);
			$('#choose_currency').val(currency);
			$('#choose_type').val(choose_type);
			$('#choose_value').val(choose_value);
			$('#choose_country').val(country);
		}

		//获取条/页 
		if(per_page == null || per_page == 0)
			per_page = $('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > .active a').first().html();

		$.showLoading();
		$("#search_count").val(0);
		
		var Url=global.baseUrl +'statistics/sales/get-sales-info';
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
	        	'currency' : currency,
	        	'choose_type' : choose_type,
	        	'choose_value' : choose_value,
	        	'country' : country,
	        	'sort' : sort,
	        	'sorttype': sorttype,
	        	},
			url: Url,
	        success:function(response) 
	        {
	        	if(response['status'])
	        	{
        			if( response['data'].length > 0)
	        		{
        				var HtmlStr = '';
        				for(var n = 0; n < response['data'].length; n++)
        				{
		    				data = response['data'][n];
				        	//明细信息
				        	HtmlStr +=
				        		"<tr>"+
									"<td style='border:1px solid #ccc; text-align:center;'>" +
										"<div style='height: 50px;'><img style='max-height: 50px; max-width: 80px' src='"+ data['photo_primary'] +"' /></div>"+
									"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_source'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['selleruserid'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['sku'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:left;'>"+ data['product_name'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:left;'>"+ data['product_attributes'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['order_count'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['total_qty'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['total'] +"</td>"+
								"</tr>";
        				}

        				$("#sales_table").append(HtmlStr);
        				$("#statistics_pager_group").html(response['pagination']);
        				$('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
						{
        					$(this).attr('href', 'javascript:sales.list.sales_search(1, '+ $(this).html() +', 0);');
						});
						$('#statistics_pager_group .btn-group > .pagination > li a').each(function()
						{
        					$(this).attr('href', 'javascript:sales.list.sales_search(1, 0, '+ $(this).html() +');');
						});
						
        				$('#statistics_pager_group .btn-group .prev a').attr('href', 'javascript:sales.list.sales_search(2);');
        				$('#statistics_pager_group .btn-group .next a').attr('href', 'javascript:sales.list.sales_search(3);');
        				
        				//总数量
        				$("#search_count").val(response['count']);
	        		}
        			else{
        				$("#statistics_pager_group").html(response['pagination']);
        				
        				$('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
						{
        					$(this).attr('href', 'javascript:sales.list.sales_search(1, '+ $(this).html() +', 0);');
						});
        			}
	        	}
	        	else{
	        		$("#statistics_pager_group").html(response['pagination']);
	        		
	        		$('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
					{
    					$(this).attr('href', 'javascript:sales.list.sales_search(1, '+ $(this).html() +', 0);');
					});
	        	}
	        	$.hideLoading();
	        },
        	error: function()
			{
				$.hideLoading();
				return false;
			}
	    });
	},
	
	'exportExecl' : function()
	{
		if($('tr[class!="sales_title"]').length == 0)
		{
			$.alertBox('<p class="text-warn">无数据导出！</p>');
			return false;
		}
		
		var start_date = $('#choose_start_date').val();
		var end_date = $('#choose_end_date').val();
		var selectplatform = $('#choose_selectplatform').val();
		var selectstore = $('#choose_selectstore').val();
		var currency = $('#choose_currency').val();
		var choose_type = $('#choose_type').val();
		var choose_value = $('#choose_value').val();
		var country = $('#choose_country').val();
		var sort = $('.lb_sort').attr('value');
		var sorttype = $('.lb_sort').attr('sorttype');
		
		var params = 'start_date='+ start_date +'&end_date='+ end_date +'&selectplatform='+ selectplatform +'&selectstore='+ selectstore +'&currency='+ currency +'&choose_type='+ choose_type +'&choose_value='+ choose_value +'&country='+ country +'&sort='+ sort +'&sorttype='+ sorttype;
		
		//查询导出数量
		var count = 0;
		count = $('#search_count').val();

		//当小于300行时，直接前台导出
		if(count > 0 && count < 300){
			window.open("/statistics/sales/downstage-export-excel?"+params,'_blank');
		}
		else{
			$.modal({
					  url:'/statistics/sales/backstage-export-excel',
					  method:'POST',
					  data:{
				        	'start_date' : start_date,
				        	'end_date' : end_date,
				        	'selectplatform' : selectplatform,
				        	'selectstore' : selectstore,
				        	'currency' : currency,
				        	'choose_type' : choose_type,
				        	'choose_value' : choose_value,
				        	'country' : country,
				        	'sort' : sort,
				        	'sorttype' : sorttype,
				        	},
				},
				'导出Excel',{footer:false,inside:false}).done(function($modal){
				});
		}
	},
	
	//排序
	'refresh_sort' : function(obj){
		//记录排序类型
		var sorttypehtml = ''
		if($(obj).attr('sorttype') != 'desc'){
			$('.lb_sort').attr('sorttype', '');
			$(obj).attr('sorttype', 'desc');
			sorttypehtml = '-alt';
		}
		else{
			$('.lb_sort').attr('sorttype', '');
		}
		//清除之前选中的记录
		$('.lb_sort').find('span').remove();
		$('.lb_sort').removeClass('lb_sort');
		//标记本label为选中
		$(obj).addClass('lb_sort');
		$(obj).append('<span class="glyphicon glyphicon-sort-by-attributes'+ sorttypehtml +'"></span>');
		
		
		sales.list.sales_search(0);
	},
}
