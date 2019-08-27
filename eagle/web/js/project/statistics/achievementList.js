/**
 +------------------------------------------------------------------------------
 *业绩汇总js
 +------------------------------------------------------------------------------
 * @category	js/project/statistics
 * @package		achievement
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof achievement === 'undefined')  achievement = new Object();
achievement.list=
{
	'init': function() 
	{
		achievement.list.achievement_search(0);
		
		$("#achievement_search").click(function()
		{
			achievement.list.achievement_search(0);
		});
		
		statistics.list.CheckShowOrderType();
	},
	
	//搜索报表明细
	'achievement_search': function(selectType, per_page, page) 
	{
		$("#select_all").prop('checked',false);
		
		var start_date = '';
		var end_date = '';
		var selectplatform = '';
		var selectstore = '';
		var period = '';
		var currency = '';
		var order_type = '';
		//换页、条/页 ,筛选条件不变

		if(selectType != 0)
		{
			//清除旧明细
			$('tr[class!="achievement_title"][class!="achievement_sum"]').remove();
			
			start_date = $('#choose_start_date').val();
			end_date = $('#choose_end_date').val();
			selectplatform = $('#choose_selectplatform').val();
			selectstore = $('#choose_selectstore').val();
			period = $('#choose_period').val();
			currency = $('#choose_currency').val();
			order_type = $('#choose_order_type').val();

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
			$('tr[class!="achievement_title"]').remove();
			
			start_date = $('#statistics_startdate').val();
			end_date = $('#statistics_enddate').val();
			period = $('#select_period').val();
			currency = $('#select_currency').val();

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
			$('#choose_period').val(period);
			$('#choose_currency').val(currency);
			$('#choose_order_type').val(order_type);
		}

		//获取条/页 
		if(per_page == null || per_page == 0)
			per_page = $('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > .active a').first().html();

		var Url=global.baseUrl +'statistics/achievement/get-achievement-info';
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
	        	'period' : period,
	        	'currency' : currency,
	        	'order_type' : order_type,
	        	},
			url: Url,
	        success:function(response) 
	        {
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
				        		"<tr class='achievement_sum'>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['thedate'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['total_sales_count'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['total_sales_amount_USD'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['total_profit_cny'] +"</td>"+
								"</tr>";
				        	row++;
        				}
        				for(var n = row; n < response['data'].length; n++)
        				{
		    				data = response['data'][n];
				        	//明细信息
				        	HtmlStr +=
				        		"<tr>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['thedate'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:center;'>"+ data['total_sales_count'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['total_sales_amount_USD'] +"</td>"+
									"<td style='border:1px solid #ccc; text-align:right;'>"+ data['total_profit_cny'] +"</td>"+
								"</tr>";
        				}

        				$("#achievement_table").append(HtmlStr);
        				$("#statistics_pager_group").html(response['pagination']);
        				$('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
						{
        					$(this).attr('href', 'javascript:achievement.list.achievement_search(1, '+ $(this).html() +', 0);');
						});
						$('#statistics_pager_group .btn-group > .pagination > li a').each(function()
						{
        					$(this).attr('href', 'javascript:achievement.list.achievement_search(1, 0, '+ $(this).html() +');');
						});
						
        				$('#statistics_pager_group .btn-group .prev a').attr('href', 'javascript:achievement.list.achievement_search(2);');
        				$('#statistics_pager_group .btn-group .next a').attr('href', 'javascript:achievement.list.achievement_search(3);');
	        		}
        			else{
        				$("#statistics_pager_group").html(response['pagination']);
        				
        				$('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
						{
        					$(this).attr('href', 'javascript:achievement.list.achievement_search(1, '+ $(this).html() +', 0);');
						});
        			}
	        	}
	        	else{
	        		$("#statistics_pager_group").html(response['pagination']);
	        		
	        		$('#statistics_pager_group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
					{
    					$(this).attr('href', 'javascript:achievement.list.achievement_search(1, '+ $(this).html() +', 0);');
					});
	        	}
	        }
	    });
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
		var period = $('#choose_period').val();
		var currency = $('#choose_currency').val();
		var order_type = $('#choose_order_type').val();
		
		var params = 'start_date='+ start_date +'&end_date='+ end_date +'&selectplatform='+ selectplatform +'&selectstore='+ selectstore +'&period='+ period +'&currency='+ currency +'&order_type='+ order_type;
		
		window.open("/statistics/achievement/export-excel?"+params,'_blank');
	},
}
