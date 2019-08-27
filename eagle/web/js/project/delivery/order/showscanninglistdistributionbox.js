if (typeof showscanninglistdistributionbox === 'undefined')  showscanninglistdistributionbox = new Object();

showscanninglistdistributionbox = 
{
	init:function()
	{
		//切换扫描包裹分拣、SKU分拣
		$('#tab1').on('click',function()
		{
			$('#scanng_tab2').css("display","none");
			$('#scanng_tab1').css("display","block");
			$('#code_input1').focus();
		});
		$('#tab2').on('click',function()
		{
			$('#scanng_tab1').css("display","none");
			$('#scanng_tab2').css("display","block");
			$('#code_input2').focus();
			
			//判断是否需要开启连接cs端
			if($('#automatic_print_enable').prop("checked")){
				cs_print.openWebSocket();
				
				if(cs_webSocket.readyState == 0){
					$('#automatic_print_enable').prop("checked", false);
					
					$('#code_input2').focus();
					$('#code_input2').select();
				}
			}
			
		});
		
		//扫描完跟踪号后，自动匹配对应订单
		$('.code_input').on('keypress',function(e)
		{
			if(e.which == 13) 
			{
				var code = $(this);
				scanning_input(code);
			}
		});
		
		//点击查找按钮
		$(".scanning_search").on('click',function()
		{
			var code = $(this).parent().find('.code_input');
			scanning_input(code);
		});
		
		//打印面单
		$(".btn-print_canning").on('click',function()
		{
			var num = $(this).attr('name');
			var orderids = $('#orderid'+ num).html();
			print_scanning(orderids, num);
		});
		
		//跳过某单
		$("#scanning_skip").on('click',function()
		{
			var val = $('#scanning_skip_val').val() + $('#orderid2').html() + ',';
			$('#scanning_skip_val').val(val);
			
			//清空内容
			$('#orderid2').html("");
			$('#tracknumber2').html("");
			$('#scanning_detail2').css("display","none");
			$('#scanning_err2').css("display","none");
			
			$('#code_input2').select();
		});
		
		//显示订单信息
		function scanning_input(code)
		{
			code.select();
			var val = code.val().replace(/(^\s*)|(\s*$)/g, "");
			var t_id = code.attr('id');
			var num = t_id.charAt(t_id.length - 1);
			
			//当扫描SKU时，已存在商品明细，则不进行查询操作，进行检验数量操作
			if(num == 2 && $('#scanning_detail2').css("display") == "block")
			{
				//匹配SKU检验数量
				var ischeck = 0;
				$('b[name="scanning_sku_2[]"]').each(function()
				{
					var sku = $(this).html();
					if( sku == val)
					{
						var h_scanning_quantity = $(this).parent().nextAll('td[name="scanning_quantity"]');
						var h_scanning_check = $(this).parent().nextAll('td[name="scanning_check"]');
						var checkqty = h_scanning_check.html();
						
						if(h_scanning_quantity.html() == checkqty || h_scanning_quantity.html() == 0)
						{
							ischeck = 1;   //已满足条件
							return true;
						}
						else
						{
							ischeck = 2;  //可匹配
							checkqty = Number(checkqty) + 1;
							h_scanning_check.html(checkqty);
							
							//当数量与检验数量匹配时，变更检验状态
							if(h_scanning_quantity.html() == checkqty)
							{
								$(this).parent().parent().find('td[name="scanning_status"]').css("color","green");
								$(this).parent().parent().find('td[name="scanning_status"]').html("√");
							}
							
							return false;
						}
					}
				});
				
				if( ischeck == 2){
					$('#scanning_err2').css("display","none");
					
					//延时0.5秒执行判断打印
		        	window.setTimeout(function () { 
		        		automatic_print();
		        	}, 500);
				}
				else{
					$('#scanning_err2').css("display","block");
	        		if(ischeck == 0){
	        			$('#scanning_err2').html("错误：包裹内无此SKU！");
	        		}
	        		if(ischeck == 1){
	        			$('#scanning_err2').html("错误：包裹内此SKU已满足条件！");
	        			
	        			//延时0.5秒执行判断打印
			        	window.setTimeout(function () { 
			        		automatic_print();
			        	}, 500);
	        		}
				}
			}
			else
			{
				$('#scanning_err'+num).css("display","none");
				var skip_val = $('#scanning_skip_val').val();
				var Url=global.baseUrl +'order/order/get-order-list-by-condition';
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        dataType: 'json',
			        data : {'val':val, 'type':num, 'skip_val':skip_val},
					url: Url,
			        success:function(response) 
			        {
			        	if(response['code'] == "0")
			        	{
			        		if( response['data'].length > 0)
			        		{
				        		if( num == 1)
					        	{
				        			data = response['data'][0];
				        			
				        			//判断只显示发货中，并且已交运的订单
									//物流商类型，1api物流，2Excel对接，3无数据对接
									var carrier_type = '已交运'; 
									if(data['carrier_type'] == '2')
										carrier_type = '已导出';
									else if(data['carrier_type'] == '3')
										carrier_type = '已分配';
									
									if(data['delivery_status'] != "发货中")
									{
										$('#scanning_detail'+num).css("display","none");
					        			$('#scanning_err'+num).css("display","block");
						        		$('#scanning_err1').html("错误提醒：此订单状态为“"+ data['delivery_status'] +"”，只有“发货中”的订单才能进行此操作！");
										return false;
									}
									if(data['carrier_step'] != carrier_type)
									{
										$('#scanning_detail'+num).css("display","none");
					        			$('#scanning_err'+num).css("display","block");
						        		$('#scanning_err1').html("错误提醒：此订单物流状态为“"+ data['carrier_step'] +"”，只有“"+ carrier_type +"”的订单才能进行此操作！");
										return false;
									}
									
				        			$('#scanning_detail'+num).css("display","block");
			        				var HtmlStr = 
							        	"<tr>" +
											"<th width='350px' colspan='2' >商品信息</th>"+
											"<th width='50px'>数量</th>"+
										 	"<th width='200px'>备注/拣货说明</th>"+
										"</tr>";
			        				
						        	$("#orderid1").html(data['order_id']);
						        	$("#tracknumber1").html(data['track_number']);
						        	
						        	for( var j = 0; j < data['items'].length; j++)
						        	{
						        		item = data['items'][j];
							        	//添加明细信息
							        	HtmlStr +=
							        	"<tr>"+
											"<td width='70px' style='border:1px solid #ccc;'><img src='"+ item['photo_primary'] +"' style='width:50px ! important;height:50px ! important'></td>"+
											"<td style='text-align:left; border:1px solid #ccc;'>订单号:<b style='color:#ff9900;'>"+ data['order_source_order_id'] +"</b><br>"+
												"跟踪号:<b>"+ data['track_number'] +"</b><br>"+
												"sku:<b>"+ item['sku'] +"</b></br>";
							        	
							        	if(item['product_url'] == null || item['product_url'] == '')
							        		HtmlStr += item['product_name'] +"</td>";
							        	else
							        		HtmlStr += '<a href="'+ item['product_url'] +'" target="_blank">'+ item['product_name'] +'</a></td>';
							        	
							        	HtmlStr +=
											"<td style='border:1px solid #ccc;'>"+ item['quantity'] +"</td>";
							        	
							        	if( j == 0)
							        		HtmlStr += "<td rowspan="+ data['items'].length +" style='border:1px solid #ccc;'>"+ data['desc'] +"</td>";
										HtmlStr += "</tr>";
						        	}
		
						        	$(".tab_order_1").html(HtmlStr);
				        		}
				        		else if( num == 2)
				        		{
				        			$('#scanning_detail'+num).css("display","block");
			        				var checkqty = 1;
				        			data = response['data'][0];
				        			
				        			var HtmlStr = 
							        	"<tr>" +
											"<th width='350px' colspan='2' >商品信息</th>"+
											"<th width='50px'>数量</th>"+
										 	"<th width='50px'>校验数量</th>"+
										 	"<th width='50px'>校验状态</th>"+
										 	"<th width='50px'>打印状态</th>"+
										 	"<th width='100px'>备注/拣货说明</th>"+
										"</tr>";
				        			
						        	$("#orderid2").html(data['order_id'].replace(/\b(0+)/gi,""));
						        	$("#tracknumber2").html(data['track_number']);
						        	
						        	for( var j = 0; j < data['items'].length; j++)
						        	{
						        		item = data['items'][j];
							        	//添加明细信息
							        	HtmlStr +=
							        	"<tr>"+
											"<td width='70px' style='border:1px solid #ccc;'><img src='"+ item['photo_primary'] +"' style='width:50px ! important;height:50px ! important'></td>"+
											"<td style='text-align:left; border:1px solid #ccc;'>订单号:<b style='color:#ff9900;'>"+ data['order_source_order_id'] +"</b><br>"+
												"跟踪号:<b>"+ data['track_number'] +"</b><br>"+
												"sku:<b name='scanning_sku_2[]'>"+ item['sku'] +"</b></br>";
							        	
							        	if(item['product_url'] == null || item['product_url'] == '')
							        		HtmlStr += item['product_name'] +"</td>";
							        	else
							        		HtmlStr += '<a href="'+ item['product_url'] +'" target="_blank">'+ item['product_name'] +'</a></td>';
							        	
							        	HtmlStr +=
											"<td name='scanning_quantity' style='border:1px solid #ccc;'>"+ item['quantity'] +"</td>";
							        	if(checkqty == 1 && item['sku'] == val){
							        		HtmlStr += "<td name='scanning_check' style='border:1px solid #ccc;'>1</td>";
							        		if(item['quantity'] == 1)
							        			HtmlStr += "<td name='scanning_status' style='font-size:20px; color:green; border:1px solid #ccc;'>√</td>";
							        		else
							        			HtmlStr += "<td name='scanning_status' style='font-size:20px; color:red; border:1px solid #ccc;'>×</td>";
								        	
								        	checkqty = 0;
							        	}
							        	else if(item['quantity'] == 0){
							        		HtmlStr += "<td name='scanning_check' style='border:1px solid #ccc;'>0</td>";
							        		HtmlStr += "<td name='scanning_status' style='font-size:20px; color:green; border:1px solid #ccc;'>√</td>";
							        	}
							        	else{
							        		HtmlStr += "<td name='scanning_check' style='border:1px solid #ccc;' >0</td>"+
							        			"<td name='scanning_status' style='font-size:20px; color:red; border:1px solid #ccc;'>×</td>";
							        	}
							        	
							        	HtmlStr += "<td name='scanning_print_status' style='font-size:20px; color:red; border:1px solid #ccc;'>×</td>";
							        	
							        	if( j == 0)
							        		HtmlStr += "<td rowspan="+ data['items'].length +" style='border:1px solid #ccc;'>"+ data['desc'] +"</td>";
										HtmlStr += "</tr>";
						        	}
		
						        	$(".tab_order_2").html(HtmlStr);
						        	
						        	//延时1秒执行判断打印
						        	window.setTimeout(function () { 
						        		automatic_print();
						        	}, 1000);
			        			}
			        		}
			        	}
			        	else
			        	{
		        			$('#scanning_detail'+num).css("display","none");
		        			$('#scanning_err'+num).css("display","block");
			        		if(num == 1)
			        			$('#scanning_err1').html("错误：小老板单号或物流追踪号不存在！");
			        		if(num == 2)
			        			$('#scanning_err2').html("错误：无法查到此SKU！");
			        	}
			        }
			    });
			}
		}
		
		function print_scanning(orderid, num)
		{
			var ischeckde = 0;
			if(num == 2)
			{
				//扫描SKU，判断是否检验完毕
				$('b[name="scanning_sku_2[]"]').each(function()
				{
					var h_scanning_quantity = $(this).parent().nextAll('td[name="scanning_quantity"]');
					var h_scanning_check = $(this).parent().nextAll('td[name="scanning_check"]');
					
					if(h_scanning_quantity != 0 && h_scanning_quantity.html() != h_scanning_check.html())
					{
						ischeckde = 1;
						return false;
					}
				});
			}

			if( ischeckde == 1)
			{
				$.alertBox('<p class="text-warn">错误提醒：请全部完成「校验状态」后，再进行「打印面单」！</p>');
			}
			else
			{
				var orderids =[];
				orderids.push(orderid);
				
				OrderCommon.ExternalDoprint(orderids);
				/*var Url = global.baseUrl + "carrier/carrierprocess/doprintcustom";
				window.open(Url+'?orders='+ orderid);*/
				
				var event = $.confirmBox("是否将打印的订单标记为已打印？");
				event.then(function()
				{
					$.post('/carrier/carrier/carrier-print-confirm',{orders:orderid},function(result)
					{
						var event = $.alert(result,'success');
						event.then(function()
						{
							var val = $('#scanning_skip_val').val() + $('#orderid2').html() + ',';
							$('#scanning_skip_val').val(val);
							
							//清空内容
							$('#orderid'+ num).html("");
							$('#tracknumber'+ num).html("");
							$('#scanning_detail'+ num).css("display","none");
							$('#scanning_err'+ num).css("display","none");
							
							$('#code_input'+ num).select();
						});
					});
				});
			}
		}
		
		function automatic_print(){
			//是否开通自动打印
			if( $('#automatic_print_enable').prop("checked")){
				//判断是否所有商品都已经检测完毕
				var check_suc_count = 0;
				var all_count = 0;
				$('#scanning_detail2 td[name="scanning_quantity"]').each(function()
				{
					all_count++;
					var h_scanning_quantity = $(this).html();
					var h_scanning_check = $(this).nextAll('td[name="scanning_check"]').html();
					if(h_scanning_quantity == h_scanning_check || h_scanning_quantity == 0)
					{
						check_suc_count++;
					}
				});
				if(all_count > 0 && all_count == check_suc_count){
	    			$('td[name="scanning_print_status"]').css("color","black");
	    			$('td[name="scanning_print_status"]').css("font-size","17px");
	    			$('td[name="scanning_print_status"]').html("wait...");
	    			
	    			//打印面单
	    			var orderid = $("#scanning_detail2").find("#orderid2").html();
	    			cs_print.printOrder(orderid);
				}
			}
		}
	},

	editPrintStatus : function(){
		//设置订单为已打印
		var orderid = $("#scanning_detail2").find("#orderid2").html();
		$.ajax({
			type: 'post',
			dataType: 'json',
			cache: false,
			async: false,
			data: {'orders': orderid},
			url: '/carrier/carrier/carrier-print-confirm',
			success: function(ret){
				return true;
			},
			error: function(){
				return true;
			}
		});
		
		$('td[name="scanning_print_status"]').css("color","green");
		$('td[name="scanning_print_status"]').css("font-size","20px");
		$('td[name="scanning_print_status"]').html("√");
		
		//延时2秒自动跳转下一张
    	window.setTimeout(function () { 
    		$("#scanning_skip").click();
    	}, 2000);
	},
	
	PrintErr : function(){
		$('td[name="scanning_print_status"]').css("color","red");
		$('td[name="scanning_print_status"]').css("font-size","20px");
		$('td[name="scanning_print_status"]').html("×");
	},
}
