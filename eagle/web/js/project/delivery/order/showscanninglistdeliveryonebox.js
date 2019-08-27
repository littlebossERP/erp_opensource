if (typeof showscanninglistdeliveryonebox === 'undefined')  showscanninglistdeliveryonebox = new Object();

showscanninglistdeliveryonebox = 
{
	init:function()
	{
		//扫描完跟踪号后，自动匹配对应订单
		$('#code_input').on('keypress',function(e)
		{
			if(e.which == 13) 
			{
				oninput(0);
			}
		});
		
		//点击查找
		$(".scanning_search").on('click',function()
		{
			if( $("#scanning_search").val() == "确认发货完成")
				oninput(1);
			else
				oninput(0);
		});
		
		//称重
		$('#weigh_input').on('keypress',function(e)
		{
			if(e.which == 13) 
			{
				//查询订单信息，并称重
				scanning_input(1);
			}
		});
		
		//称重
		function weighingSetting(orderid)
		{
			var h_weight = $('#weigh_input');
			var val = h_weight.val().replace(/(^\s*)|(\s*$)/g, "");
			
			if( val != '')
			{
				//当选择单位为kg时，需要把重量 *1000
				if($('.select_weigh').val() == 'kg')
					val = val * 1000;
					
				$.ajax({
					url: global.baseUrl + "delivery/order/update-seller-weight",
					data: {'order_id':orderid, 'seller_weight':val},
					type: 'post',
					dataType: 'json',
					success: function(response) 
					{
						if(response['success'])
						{
							$.alertBox('<p class="text-success">称重成功</p>');
							h_weight.val("");
						}
						else
						{
							$.alertBox('<p class="text-success">称重失败</p>');
						}
					},
					error: function(XMLHttpRequest, textStatus) 
					{
						$.alertBox('<p class="text-success">称重失败</p>');
					}
				});
			}
			return val;
		}
		
		//进行查询功能，isdelivery，是否直接发货，0否1是
		function oninput(isdelivery)
		{
			var code = $('#code_input');
			var val = code.val().replace(/(^\s*)|(\s*$)/g, "");
			if(val != '')
			{
				var old_orderid = $('#old_orderid').val();
				var old_tracknum = $('#old_tracknum').val();
				
				//当重复扫描同一小老板单号或跟踪号时，进行发货操作
				if( isdelivery == 1 || parseInt(val) == old_orderid || val == old_tracknum)
				{
					//判断此订单是否处于发货中、已交运
					var delivery_status = $('td[name="scanning_delivery_status"]').html();
					var carrier_step = $('td[name="scanning_carrier_step"]').html();
					//物流商类型，1api物流，2Excel对接，3无数据对接
					var carrier_type = '已交运'; 
					if($('input[name="scanning_carrier_type"]').val() == '2')
						carrier_type = '已导出';
					else if($('input[name="scanning_carrier_type"]').val() == '3')
						carrier_type = '已分配';
					
					if(delivery_status != "发货中")
					{
						$.alertBox('<p class="text-warn">错误提醒：订单状态为“发货中”的订单才能进行此操作！</p>');
						return false;
					}
					if(carrier_step != carrier_type)
					{
						$.alertBox('<p class="text-warn">错误提醒：物流状态为“'+ carrier_type +'”的订单才能进行此操作！</p>');
						return false;
					}
					
					if( $('#weighing_enable').prop("checked"))
					{
						//判断称重重量是否大于零
						var scanning_seller_weight = $('td[name="scanning_seller_weight"]').html();
						if(scanning_seller_weight == '' || scanning_seller_weight == 0)
						{
							$.alertBox('<p class="text-warn">错误提醒：称重重量必须大于零！</p>');
							//继续称重
				        	$('#weigh_input').focus();
							return false;
						}
					}
					
					//查询明细对应的order_id
					var orderid = $('b[name="order_id"]').html();
					
					code.val("");
					var orderids = [];
					orderids.push(orderid);
					
					set_Finished(orderids);
				}
				else
					scanning_input(0);
			}
			else
			{
				$.alertBox('<p class="text-warn">错误提醒：小老板单号或跟踪号不能为空！</p>');
				return false;
			}
		}
		
		//显示订单信息，isweighing为是否称重，0否1是
		function scanning_input( isweighing)
		{
			$('#scanning_err').css("display","none");
			$('#old_orderid').val("");
			$('#old_tracknum').val("");
			
			var code = $('#code_input');
			var val = code.val().replace(/(^\s*)|(\s*$)/g, "");
			
			if(val == '')
			{
				$.alertBox('<p class="text-warn">错误提醒：小老板单号或跟踪号不能为空！</p>');
				return false;
			}
			
			var Url=global.baseUrl +'order/order/get-order-list-by-condition';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        dataType: 'json',
		        data : {'val':val, 'type':1},
				url: Url,
		        success:function(response) 
		        {
		        	if(response['code'] == "0")
		        	{
	        			if( response['data'].length > 0)
		        		{
	        				
	        				data = response['data'][0];
	        				
	        				if(isweighing == 1)
			        		{
			        			//称重
	        					var rel = weighingSetting(data['order_id']);
	        					
	        					data['seller_weight'] = rel;
			        		}
	        				
	        				$('#scanning_detail').css("display","block");
	        				$('#old_orderid').val(data['order_id']);
	        				$('#old_tracknum').val(data['track_number']);
	        				
	        				var HtmlStr = 
					        	"<tr>" +
									"<th style='width: 100px;'>小老板单号 / 跟踪号</th>"+
					        		"<th style='width: 130px;'>订单号</th>"+
					        		"<th style='width: 130px;'>SKU</th>"+
					        		"<th style='width: 130px;'>图片</th>"+
					        		"<th style='width: 70px;'>商品净重（g）</th>"+
					        		"<th style='width: 70px;'>称重重量（g）</th>"+
					        		"<th style='width: 100px;'>备注/提货说明</th>"+
					        		"<th style='width: 50px;'>订单状态</th>"+
					        		"<th style='width: 50px;'>物流状态</th>"+
					        		"<th style='width: 50px;'>状态</th>"+
								"</tr>";
	        				
	        				var html_id = '', html_sku = '', html_pic = '';
				        	
				        	for( var j = 0; j < data['items'].length; j++)
				        	{
				        		item = data['items'][j];
				        		
				        		//订单号
				        		if(html_id != '')
				        			html_id += "<br>";
				        		html_id += "<b style='color:#ff9900;'>"+ data['order_source_order_id'] +"</b>";
				        		
				        		//SKU
				        		if(html_sku != '')
				        			html_sku += "<br>";
				        		html_sku += item['sku'] +" * "+ item['quantity'];
				        		
				        		//图片
				        		html_pic += "<img src='"+ item['photo_primary'] +"' style='width:50px ! important;height:50px ! important'>";
				        	}
				        	
				        	//添加明细信息
				        	HtmlStr +=
				        		"<tr>"+
					        		"<td style='border:1px solid #ccc;'>" +
					        			"<b name='order_id' style='color:#ff9900;'>"+ data['order_id'] +"</b><br>"+
										"<b>"+ data['track_number'] +"</b>"+
										"<input type='hidden' name='scanning_carrier_type' value='"+ data['carrier_type'] +"' /></td>"+
									"<td style='border:1px solid #ccc;'>"+ html_id +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ html_sku +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ html_pic +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ data['logistics_weight'] +"</td>"+
									"<td name='scanning_seller_weight' style='border:1px solid #ccc;'>"+ data['seller_weight'] +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ data['desc'] +"</td>"+
									"<td name='scanning_delivery_status' style='border:1px solid #ccc;'>"+ data['delivery_status'] +"</td>"+
									"<td name='scanning_carrier_step' style='border:1px solid #ccc;'>"+ data['carrier_step'] +"</td>"+
									"<td name='scanning_status' style='border:1px solid #ccc;'></td>"+
								"</tr>";

				        	$(".tab_order").html(HtmlStr);
				        	$("#scanning_search").val("确认发货完成");
				        	
				        	if( isweighing != 1 && $('#weighing_enable').prop("checked"))
				        	{
					        	//继续称重
					        	$('#weigh_input').focus();
				        	}
				        	else
				        	{
				        		code.select();
				        	}
		        		}
		        	}
		        	else
		        	{
		        		code.select();
		        		$("#scanning_search").val("查找");
	        			$('#scanning_detail').css("display","none");
	        			$('#scanning_err').css("display","block");
		        		$('#scanning_err').html("小老板单号或物流追踪号不存在！");
		        	}
		        }
		    });
		}
		
		//确认发货完成
		function set_Finished(orderids)
		{
			//判断是否允许负库存出库
			$.ajax({
				url: global.baseUrl + "delivery/order/check-order-stock-out",
				data: { 'order_id' : orderids },
				type: 'post',
				success: function(response) {
					var r = $.parseJSON(response);
					if(r.success)
					{
						//确认发货完成
					 	$.ajax({
							url: global.baseUrl + "carrier/carrierprocess/setfinished",
							data: { orderids:orderids },
							type: 'post',
							success: function(response) {
								 var r = $.parseJSON(response);
								 if(r.success)
								 {
									 $('td[name="scanning_delivery_status"]').html("已完成");
									 $('td[name="scanning_carrier_step"]').html("已完成");
									 $('.tab_order').append('<tr><td colspan="11" style="text-align:left; color:green">'+ r.message +'</tr></td>');
									 $('td[name="scanning_status"]').css("color","green");
									 $('td[name="scanning_status"]').html("发货成功");
									 $('td[name="scanning_status"]').html("发货成功");
								 }
								 else
								 {
									 $('.tab_order').append('<tr><td colspan="11" style="text-align:left; color:red">'+ r.message +'</tr></td>');
									 $('td[name="scanning_status"]').css("color","red");
									 $('td[name="scanning_status"]').html("发货失败");
								 } 
							},
							error: function(XMLHttpRequest, textStatus) 
							{
								$('.tab_order').append('<tr><td colspan="11" style="text-align:left; color:red">网络异常，请联系客服!</tr></td>');
								$('td[name="scanning_status"]').css("color","green");
								$('td[name="scanning_status"]').html("发货失败");
							}
						});
					}
					else{
						$('.tab_order').append('<tr><td colspan="11" style="text-align:left; color:red">'+ r.message +'</tr></td>');
						$('td[name="scanning_status"]').css("color","red");
						$('td[name="scanning_status"]').html("发货失败");
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$('.tab_order').append('<tr><td colspan="11" style="text-align:left; color:red">网络异常，请联系客服!</tr></td>');
					$('td[name="scanning_status"]').css("color","green");
					$('td[name="scanning_status"]').html("发货失败");
				}
			});
		}
		
		//是否显示称重功能
		$('#weighing_enable').on('click',function()
		{
			var weighing_enable = 0;
			if( $(this).prop("checked"))
				weighing_enable = 1;
			
			$.ajax({
				url: global.baseUrl + "delivery/order/weighing-enable",
				data: {'weighing_enable':weighing_enable},
				type: 'post',
				dataType: 'json',
				success: function(response)
				{
					if(response['success'])
					{
						$.alertBox('<p class="text-success">设置称重功能成功</p>');
						if( weighing_enable)
							$('#weighing_enable_detail').css("display","block");
						else
							$('#weighing_enable_detail').css("display","none");
						
						$('#code_input').select();
					}
					else
					{
						$.alertBox('<p class="text-warn">设置称重功能失败</p>');
					}
						
				},
				error: function(XMLHttpRequest, textStatus) 
				{
					$.alertBox('<p class="text-warn">网络不稳定.请求失败,请重试！</p>');
				}
			});
		});
	}
}
