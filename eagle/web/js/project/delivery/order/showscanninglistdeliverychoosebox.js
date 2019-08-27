if (typeof showscanninglistdeliverychoosebox === 'undefined')  showscanninglistdeliverychoosebox = new Object();

showscanninglistdeliverychoosebox = 
{
	init:function()
	{
		//扫描完跟踪号后，自动匹配对应订单
		$('#code_input').on('keypress',function(e)
		{
			if(e.which == 13) 
			{
				scanning_input(0);
			}
		});
		
		//点击查找
		$(".scanning_search").on('click',function()
		{
			scanning_input(0);
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
		
		//显示订单信息，isweighing为是否称重，0否1是
		function scanning_input( isweighing)
		{
			$('#scanning_err').css("display","none");
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
			        		
			        		//显示商品信息table
			        		$('#scanning_detail').css("display","block");
	        				
	        				//判断是否已扫，已扫就直接进行称重
	        				var isScanning = 0;
	        				$('input[name="scanning_order_id[]"]').each(function()
	        				{
	        					var orderid = $(this).val();
	        					if( data['order_id'] == orderid)
	        					{
	        						isScanning = 1;
	        						$(this).parent().parent().find('span[name="scanning_seller_weight"]').html(data['seller_weight']);
		        		        	return false;
	        					}
	        				});
	        				if( isScanning == 1)
        					{
	        					//重新统计总重量
		   						 var sum_weight = 0;
		   						 $('td>span[name="scanning_seller_weight"]').each(function(){
		   							 if(!isNaN($(this).html())){
		   								sum_weight = sum_weight + parseFloat($(this).html());
		   							 }
		   						 });
		   						$('#scanning_weight').html(sum_weight);
	        					
	        					if(isweighing == 1)
	        					{
	        						code.select();
	        						return true;
	        					}
	        					
	        					if( $('#weighing_enable').prop("checked"))
		        		        	$('#weigh_input').focus();
	        					else
	        						code.select();
	        		        	
	        		        	return false;
        					}
	        				
	        				var HtmlStr = "";
	        				
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
				        			"<td style='border:1px solid #ccc;'><input type='checkbox' class='ck' name='scanning_order_id[]' value='"+ data['order_id'] +"' ></td>"+
					        		"<td style='border:1px solid #ccc;'><b style='color:#ff9900;'>"+ data['order_id'] +"</b><br>"+
										"<b>"+ data['track_number'] +"</b>"+
										"<input type='hidden' name='scanning_carrier_type' value='"+ data['carrier_type'] +"' /></td>"+
									"<td style='border:1px solid #ccc;'>"+ html_id +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ html_sku +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ html_pic +"</td>"+
									"<td style='border:1px solid #ccc;'>"+ data['logistics_weight'] +"</td>"+
									"<td style='border:1px solid #ccc;'>"+
										"<span name='scanning_seller_weight'>"+ data['seller_weight'] +"</span>" +
					        			"<a href='javascript:;' onclick='again_weighing("+ data['order_id'] +");'>" +
					        				"<span class='glyphicon glyphicon-repeat mLeft5 f15' style='color:#000; margin-left:10px;' title='重新称重'></span></a>" +
					        		"</td>" +
									"<td style='border:1px solid #ccc;'>"+ data['desc'] +"</td>"+
									"<td name='scanning_delivery_status' style='border:1px solid #ccc;'>"+ data['delivery_status'] +"</td>"+
									"<td name='scanning_carrier_step' style='border:1px solid #ccc;'>"+ data['carrier_step'] +"</td>"+
									"<td name='scanning_status' style='border:1px solid #ccc;'></td>"+
								"</tr>";

				        	$("#scanning_th").after(HtmlStr);
				        	
				        	if( isweighing != 1 && $('#weighing_enable').prop("checked"))
				        	{
					        	//继续称重
					        	$('#weigh_input').focus();
				        	}
				        	else
				        	{
				        		code.select();
				        	}
				        	
				        	//包裹数累计
				        	var scanning_count = parseInt($('#scanning_count').html()) + 1;
				        	$('#scanning_count').html(scanning_count);
				        	
				        	//重新统计总重量
	   						 var sum_weight = 0;
	   						 $('td>span[name="scanning_seller_weight"]').each(function(){
	   							 if(!isNaN($(this).html())){
	   								sum_weight = sum_weight + parseFloat($(this).html());
	   							 }
	   						 });
	   						$('#scanning_weight').html(sum_weight);
				        	
				        	return true;
		        		}
		        	}
		        	else
		        	{
		        		code.select();
	        			$('#scanning_err').css("display","block");
		        		$('#scanning_err').html("小老板单号或物流追踪号不存在！");
		        		return false;
		        	}
		        }
		    });
		}
		
		//批量发货
		$("#scanning_delivery").on('click',function()
		{
			$('input[name="scanning_order_id[]"]:checked').each(function()
    		{
				var h_ck = $(this);
				
				//判断此订单是否处于发货中、已交运
				var delivery_status = h_ck.parent().nextAll('td[name="scanning_delivery_status"]').html();
				var carrier_step = h_ck.parent().nextAll('td[name="scanning_carrier_step"]').html();
				//物流商类型，1api物流，2Excel对接，3无数据对接
				var carrier_type = '已交运'; 
				if(h_ck.parent().parent().find('input[name="scanning_carrier_type"]').val() == '2')
					carrier_type = '已导出';
				else if(h_ck.parent().parent().find('input[name="scanning_carrier_type"]').val() == '3')
					carrier_type = '已分配';
				
				if(delivery_status != "发货中")
				{
					h_ck.parent().parent().next('[name="delivery_info"]').remove();
					h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">错误提醒：订单状态为“发货中”的订单才能进行此操作！</tr></td>');
					return true;
				}
				if(carrier_step != carrier_type)
				{
					h_ck.parent().parent().next('[name="delivery_info"]').remove();
					h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">错误提醒：物流状态为“'+ carrier_type +'”的订单才能进行此操作！</tr></td>');
					return true;
				}
				
				if( $('#weighing_enable').prop("checked"))
				{
					//判断称重重量是否大于零
					var scanning_seller_weight = h_ck.parent().parent().find('span[name="scanning_seller_weight"]').html();
					if(scanning_seller_weight == '' || scanning_seller_weight == 0)
					{
						h_ck.parent().parent().next('[name="delivery_info"]').remove();
						h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">错误提醒：称重重量必须大于零！</tr></td>');
						h_ck.parent().nextAll('td[name="delivery_status"]').css("color","red");
						h_ck.parent().nextAll('td[name="delivery_status"]').html("发货失败");
						return true;
					}
				}

				var orderid = h_ck.val();
				var orderids = [];
				orderids.push(orderid);
				
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
										 h_ck.parent().nextAll('td[name="scanning_delivery_status"]').html("已完成");
										 h_ck.parent().nextAll('td[name="scanning_carrier_step"]').html("已完成");
										 h_ck.parent().parent().next('[name="delivery_info"]').remove();
										 h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:green">'+ r.message +'</tr></td>');
										 h_ck.parent().nextAll('td[name="scanning_status"]').css("color","green");
										 h_ck.parent().nextAll('td[name="scanning_status"]').html("发货成功");
										 
										//已发货数累计
										 /*var scanning_delivery_count = parseInt($('#scanning_delivery_count').html()) + 1;
								         $('#scanning_delivery_count').html(scanning_delivery_count);*/
										 
										 //重新统计已发货数
										 var deliv_count = 0;
										 $('td[name="scanning_status"]').each(function(){
											 if($(this).html() == '发货成功')
												 deliv_count = deliv_count + 1;
										 });
										 if(deliv_count > 0)
											 $('#scanning_delivery_count').html(deliv_count);
									 }
									 else
									 {
										 h_ck.parent().parent().next('[name="delivery_info"]').remove();
										 h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">'+ r.message +'</tr></td>');
										 h_ck.parent().nextAll('td[name="scanning_status"]').css("color","red");
										 h_ck.parent().nextAll('td[name="scanning_status"]').html("发货失败");
									 } 
								},
								error: function(XMLHttpRequest, textStatus) 
								{
									h_ck.parent().parent().next('[name="delivery_info"]').remove();
									h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">网络异常，请联系客服!</tr></td>');
									h_ck.parent().nextAll('td[name="scanning_status"]').css("color","red");
									h_ck.parent().nextAll('td[name="scanning_status"]').html("发货失败");
								}
							});
						}
						else{
							h_ck.parent().parent().next('[name="delivery_info"]').remove();
							h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">'+ r.message +'</tr></td>');
							h_ck.parent().nextAll('td[name="scanning_status"]').css("color","red");
							h_ck.parent().nextAll('td[name="scanning_status"]').html("发货失败");
						}
					},
					error: function(XMLHttpRequest, textStatus) {
						h_ck.parent().parent().next('[name="delivery_info"]').remove();
						h_ck.parent().parent().after('<tr name="delivery_info"><td colspan="11" style="text-align:left; color:red">网络异常，请联系客服!</tr></td>');
						h_ck.parent().nextAll('td[name="scanning_status"]').css("color","red");
						h_ck.parent().nextAll('td[name="scanning_status"]').html("发货失败");
					}
				});
    		});
		});
		
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
						$.alertBox('<p class="text-warn">设置称重功能失败！</p>');
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

//重新称重
function again_weighing(orderid)
{
	$('#code_input').val( orderid);
	$('#weigh_input').val("");
	$('#weigh_input').focus();
}

//全部勾选
function scanning_ck_allOnClick(obj)
{
	if($(obj).prop("checked")==true){
		$('input[name="scanning_order_id[]"]').prop("checked",true);
	}else{
		$('input[name="scanning_order_id[]"]').prop("checked",false);
	}
}
