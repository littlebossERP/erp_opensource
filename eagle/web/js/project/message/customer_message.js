/**
 * 
 */	
//标签添加初始化
if (typeof CustomerTag === 'undefined'){
	CustomerTag = new Object();
} 
CustomerTag = {
		TagClassList:'',
		TagList:'',
		SelectTagData:'',
		isChange:false,
		msg_obj:'',
		big_dialog:'',
		small_dialog:'',
		init:function(){ //初始化所有的qtip按钮(cs_message)
			$("#chk_all").click(function(){
				$(".chk_one").prop('checked', this.checked);  
			});
			$('.btn_tag_qtip').each(function(){
				CustomerTag.initQtipEntryBtn(this);
			});
			$(".message_list").each(function(){
				CustomerTag.initMessageListBtn(this);
			});
			$(".order_list").each(function(){
				CustomerTag.initOrderListBtn(this);
			});
		},
	
		initTicket:function(){ //初始化所有的qtip按钮(cs_message_ticket )
			$('.btn_tag_qtip').each(function(){
				CustomerTag.initTicketQtipEntryBtn(this);
			});
		},
		
		initAllQtipEntryBtn:function(customer_id){//(cs_message)
			var newBtn = $('.btn_tag_qtip[data-customer-id='+customer_id+']');
			CustomerTag.initQtipEntryBtn(newBtn);
			var newMessageBtn = $('.message_list[data-customer-id='+customer_id+']');
			if(newMessageBtn.length>0)
				CustomerTag.initMessageListBtn(newMessageBtn);
			var newOrderBtn = $('.order_list[data-customer-id='+customer_id+']');
			if(newOrderBtn.length>0)
				CustomerTag.initOrderListBtn(newOrderBtn);
		},
		
		initAllTicketQtipEntryBtn:function(customer_id){//(cs_message_ticket )
			var newBtn = $('.btn_tag_qtip[data-customer-id='+customer_id+']');
			if(newBtn.length>0)
				CustomerTag.initTicketQtipEntryBtn(newBtn);
		},
		
		//消息记录
		initMessageListBtn:function(obj){
			var list = $(obj);
		    var msg = eval($(obj).data("id"));//将数据以json形式保存到一个属性中
		    var platform_source=msg[0].source;
		    var customer_id=msg[0].buyid;
		    var seller_id=msg[0].sellid;
		    list.qtip({   	
	//		$("#sum").qtip({
		        show:{
		        	event: 'click',
					solo: true,
				},
				hide: 'click',
		        content: {
		           button:true,
		           title:"消息记录",
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/message-record?platform_source="+platform_source+"&customer_id="+customer_id+"&seller_id="+seller_id, // Use href attribute as URL
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'top center',
		    	   at: 'bottom center',
		           viewport: $("#page-content"),
		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift flip' // Requires Viewport plugin
					},
		       },
		       style: {
		    	   classes: 'people-qtip nopadding',
		    	   width:600
		       },
		    });
		},
		
		//订单历史
		initOrderListBtn:function(obj){
			var orderlist = $(obj);
		    var message = eval($(obj).data("id"));//将数据以json形式保存到一个属性中
		    var platform_source=message[0].source;
		    var customer_id=message[0].id;
		    var style_list=message[0].list_style;
		    orderlist.qtip({   	
	//		$("#sum").qtip({
		        show:{
		        	event: 'click',
					solo: true,
				},
				hide: 'click',
		        content: {
		           button:true,
		           title:"订单历史",
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/order-list?platform_source="+platform_source+"&customer_id="+customer_id+"&style_list="+style_list, // Use href attribute as URL
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'top center',
		    	   at: 'bottom center',
		           viewport: $("#page-content"),
		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift flip' // Requires Viewport plugin
					},
		       },
		       style: {
		    	   classes: 'people-qtip nopadding',
		    	   width:600
		       },
		    });
		},
		
		initQtipEntryBtn:function(obj){
			var btnObj = $(obj);
			var customer_id = $(obj).data('customer-id');
			var num = $(obj).data('order-num');
			btnObj.qtip({
				show: {
					event: 'click',
					solo: true
				},
				hide: 'click',
				content:{
					button: true,
					text:$('#div_tag_'+customer_id) ,
				},
				position: {
					my: 'top left',
					at: 'bottom left',
					viewport: $("#page-content"),
					adjust: {
						method: 'shift flip' // Requires Viewport plugin 
					},
				},
				
				style: {
					classes :'basic-qtip' , 
					
				},
				events:{
					show:function(){
						$.ajax({
							type: "GET",
							dataType: 'json',
							url: '/message/all-customer/get-customer-tag-info?customer_id='+customer_id, // Use href attribute as URL
							success:function(content){
								var html = CustomerTag.fillTagContentHtml(customer_id,content);
								$('#div_tag_'+customer_id).html(html);
								CustomerTag.initQtipBtn(customer_id);
								
							},
							error:function(){
								bootbox.alert('Error');
							}
						});
					},
					hide:function(){
						if (CustomerTag.isChange){
							CustomerTag.updateCustomerInfo(customer_id , num, function(){
								CustomerTag.initAllQtipEntryBtn(customer_id);
								TrObj = $('tr[data-customer-id='+customer_id+']')
								TrObj.unbind();
								
								TrObj.has('.egicon-flag-gray').mouseover(function(){
									if ($(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
									$(this).find('.btn_tag_qtip.div_space_toggle').removeClass('div_space_toggle');
								});
								
								TrObj.has('.egicon-flag-gray').mouseleave(function(){
									if (! $(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
										$(this).find('.btn_tag_qtip').addClass('div_space_toggle');
								});
								TrObj.has('.btn_customer_reply_msg').on('mouseover',function(){
									$(this).find('.btn_customer_reply_msg').addClass('egicon-reply-msg-hover');
									$(this).find('.btn_customer_reply_msg').removeClass('egicon-reply-msg');	
								});
								TrObj.has('.btn_customer_reply_msg').on('mouseleave',function(){
										$(this).find('.btn_customer_reply_msg').addClass('egicon-reply-msg');
										$(this).find('.btn_customer_reply_msg').removeClass('egicon-reply-msg-hover');		
								});
								//历史订单按钮
								TrObj.has('.btn_customer_history_order').on('mouseover',function(){
									$(this).find('.btn_customer_history_order').addClass('egicon-history-order-hover');
									$(this).find('.btn_customer_history_order').removeClass('egicon-history-order');	
								});
								TrObj.has('.btn_customer_history_order').on('mouseleave',function(){
										$(this).find('.btn_customer_history_order').addClass('egicon-history-order');
										$(this).find('.btn_customer_history_order').removeClass('egicon-history-order-hover');		
								});
								//历史消息按钮
								TrObj.has('.btn_customer_history_msg').on('mouseover',function(){
									$(this).find('.btn_customer_history_msg').addClass('egicon-history-msg-hover');
									$(this).find('.btn_customer_history_msg').removeClass('egicon-history-msg');	
								});
								TrObj.has('.btn_customer_history_msg').on('mouseleave',function(){
										$(this).find('.btn_customer_history_msg').addClass('egicon-history-msg');
										$(this).find('.btn_customer_history_msg').removeClass('egicon-history-msg-hover');		
								});
								//没有信封的hover
								TrObj.has('.egicon-envelope-hover').on('mouseover',function(){
									if ($(this).find('.customer_btn_tag_bootbox').hasClass('div_space_toggle')){
										$(this).find('.customer_btn_tag_bootbox').removeClass('div_space_toggle');
									}
									
								});
								TrObj.has('.egicon-envelope-hover').on('mouseleave',function(){
									if (! $(this).find('.customer_btn_tag_bootbox').hasClass('div_space_toggle')){
										$(this).find('.customer_btn_tag_bootbox').addClass('div_space_toggle');
									}			
								});
							});
							CustomerTag.isChange = false;
						}
					}
				}
			});
		},
		
		//ticket_message页面init
		initTicketQtipEntryBtn:function(obj){
			var btnObj = $(obj);
			var customer_id = $(obj).data('customer-id');
			var session_id = $(obj).data('session-id');
			var cs_type = '';
			if(typeof($(obj).data('message-type'))!=='undefined')
				cs_type = $(obj).data('message-type');
			btnObj.qtip({
				show: {
					event: 'click',
					solo: true
				},
				hide: 'click',
				content:{
					button: true,
					text:$('#div_tag_'+customer_id) ,
				},
				position: {
					my: 'top left',
					at: 'bottom left',
					viewport: $("#page-content"),
					adjust: {
						method: 'shift flip' // Requires Viewport plugin 
					},
				},
				
				style: {
					classes :'basic-qtip' , 
				},
				events:{
					show:function(){
						if(!session_id || session_id=='0'){
							$('#div_tag_'+customer_id).html('<span style="color:red">此会话未成功和平台同步，请同步后再添加标签</span>');
						}else{
							$.ajax({
								type: "GET",
								dataType: 'json',
								data:{cs_type:cs_type},
								url: '/message/all-customer/get-customer-tag-info?customer_id='+customer_id, // Use href attribute as URL
								success:function(content){
									var html = CustomerTag.fillTagContentHtml(customer_id,content);
									$('#div_tag_'+customer_id).html(html);
									CustomerTag.initQtipBtn(customer_id,'ticket');
									
								},
								error:function(){
									bootbox.alert('Error');
								}
							});
						}
					},
					hide:function(){
						if (CustomerTag.isChange){
							CustomerTag.updateTicketInfo(customer_id , function(){
								CustomerTag.initAllTicketQtipEntryBtn(customer_id);
//								TrObj = $('tr[data-customer-id='+customer_id+']');
								TrObj = $('#letter_no_'+customer_id);
								TrObj.unbind();
								
								TrObj.has('.egicon-flag-gray').mouseover(function(){
									if ($(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
									$(this).find('.btn_tag_qtip.div_space_toggle').removeClass('div_space_toggle');
								});
								
								TrObj.has('.egicon-flag-gray').mouseleave(function(){
									if (! $(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
										$(this).find('.btn_tag_qtip').addClass('div_space_toggle');
								});
								TrObj.has('.btn_customer_reply_msg').on('mouseover',function(){
									$(this).find('.btn_customer_reply_msg').addClass('egicon-reply-msg-hover');
									$(this).find('.btn_customer_reply_msg').removeClass('egicon-reply-msg');	
								});
								TrObj.has('.btn_customer_reply_msg').on('mouseleave',function(){
										$(this).find('.btn_customer_reply_msg').addClass('egicon-reply-msg');
										$(this).find('.btn_customer_reply_msg').removeClass('egicon-reply-msg-hover');		
								});
							});
							CustomerTag.isChange = false;
						}
					}
				}
			});
		},
		
		 initQtipBtn:function(customer_id){//cs_message,ticket_message 公用
			var type = arguments[1] ? arguments[1] : false;
			$('input[name=tag_customer_id]').val(customer_id);
							
			$('.span-click-btn').click(function(){
				inputObj = $(this).prev('input[name=select_tag_name]');
				if (inputObj.val().length== 0){
					bootbox.alert(Translator.t('请输入标签'));
					
					return ;
				}
				obj = $(this).children('span');
				isAdd = obj.hasClass('glyphicon-plus');
				if (isAdd){
					obj.removeClass('glyphicon-plus');
					obj.addClass('glyphicon-remove');
					inputObj.prop('readonly','readonly');
					if(type=='ticket')
						CustomerTag.addTag(obj,type);
					else
						CustomerTag.addTag(obj);
				}else{
					obj.removeClass('glyphicon-remove');
					obj.addClass('glyphicon-plus');
					if(type=='ticket')
						CustomerTag.delTag(obj,type);
					else
						CustomerTag.delTag(obj);
				}
			});
		},
		
		 fillTagContentHtml:function(customer_id , data){//cs_message,ticket_message 公用
			var Html = '';
			
			var select_html = "";
			var rest_html = "";
			var tag_mapping = new Object();
			var existColor = new Object();
			
			$.each(data.all_tag , function(){
				tag_mapping[this.tag_id] = this;
			});
			
			$.each(data.all_select_tag_id, function(i,value){
				
				ColorClassName = tag_mapping[value].classname ; 
				TagName =tag_mapping[value].tag_name;
				BtnClassName="glyphicon-remove"; 
				ReadonlyStr = '  readonly="readonly" ';
				color = tag_mapping[value].color;
				select_html += CustomerTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
				
				existColor[color] = 1;
				
			});
			
			$.each(data.all_tag, function(){
				if (existColor[this.color]!='1' && this.color != 'gray'){
					ColorClassName = this.classname; 
					TagName = this.tag_name;
					BtnClassName="glyphicon-plus"; 
					ReadonlyStr = '  readonly="readonly" ';
					color = this.color;
					rest_html += CustomerTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
					
					existColor[color] = 1;
				} 
			});
			
			for( var color in this.TagClassList){
					if (color == 'gray') continue;
					if (existColor[color]=='1') continue;
					
					ColorClassName = this.TagClassList[color] ; 
					TagName ="";
					BtnClassName="glyphicon-plus"; 
					ReadonlyStr = ' ';
					rest_html += CustomerTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr,color);
								
				}
					
			Html =  '<div name="div_select_tag" class="div_select_tag">'+
					'<input name="tag_customer_id" type="hidden" readonly="readonly" value="'+customer_id+'"/>'+
					select_html+
					'</div>'+
					'<div name="div_new_tag" class="div_new_tag">'+
					rest_html+
					'</div>';
			return Html;
		},
		
		generateHtml:function(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color){
		
		return '<div class="input-group">'+
				'<span class="input-group-addon"><span class="'+ColorClassName+'"></span></span>'+
				'<input name="select_tag_name" type="text" class="form-control" placeholder="" aria-describedby="basic-addon1" value="'+TagName+'" '+ReadonlyStr+' data-color="'+color+'">'+
				'<span class="input-group-addon span-click-btn"><span class="glyphicon '+BtnClassName+'" aria-hidden="true"></span></span>'+
				'</div>';
		},
		
		addTag:function(obj){
			var cs_type = arguments[1] ? arguments[1] : false;
			thisobj = $(obj);
			$('div[name=div_select_tag]').append(thisobj.parents('.input-group'));
			
			customer_id  = $('div[name=div_select_tag] input[name=tag_customer_id]').val(); 
			tag_name = thisobj.parent().prev('input[name=select_tag_name]').val(); 
			operation = 'add' ; 
			color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
			if(cs_type=='ticket')
				this.saveTag(customer_id , tag_name , operation , color , cs_type);
			else
				this.saveTag(customer_id , tag_name , operation , color);
		},
		
		delTag:function(obj){
			thisobj = $(obj);
			var cs_type = arguments[1] ? arguments[1] : false;
			$('div[name=div_new_tag]').append(thisobj.parents('.input-group'));
			customer_id  = $('div[name=div_select_tag] input[name=tag_customer_id]').val(); 
			tag_name = thisobj.parent().prev('input[name=select_tag_name]').val(); 
			operation = 'del' ; 
			color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
			if(cs_type=='ticket')
				this.saveTag(customer_id , tag_name , operation , color , cs_type);
			else
				this.saveTag(customer_id , tag_name , operation , color);
		},
		
		saveTag:function(customer_id , tag_name , operation , color){//tracking_id即为customer的id
			var cs_type = arguments[4] ? arguments[4] : false;
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/message/all-customer/save-tags', 
				data: {customer_id : customer_id  ,cs_type : cs_type , tag_name :  tag_name , operation :  operation , color : color},
				success: function (result) {
					if (result.success == false){
						bootbox.alert(result.message);
					}
					return true;
				},
				error :function () {
					return false;
				}
			});	
			CustomerTag.isChange = true;
		},
		
		updateCustomerInfo:function(customer_id , num, callback){
			$.ajax({
				type: "GET",
				dataType: 'text',
				url: '/message/all-customer/update-one-tr-info?customer_id='+customer_id+'&num='+num, // Use href attribute as URL
				success:function(data){
					$('#tr_customer_info_'+customer_id).html(data);
//					if (data.TrHtml){
//						for(var tmp_track_no in data.TrHtml ){
//							$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
//						}
//					}
					
					callback();
				},
				error:function(){
					bootbox.alert('Error');
				}
			});
		},
		
		updateTicketInfo:function(ticket_id , callback){
			$.ajax({
				type: "GET",
				dataType: 'text',
				url: '/message/all-customer/update-one-ticket-tr-info?ticket_id='+ticket_id,
				success:function(data){
					$('#letter_no_'+ticket_id).html(data);
					callback();
				},
				error:function(){
					bootbox.alert('Error');
				}
			});
		},
		
		//更新重发信息
		updateResentInfo:function(obj,ticket_id,msg_id,nick_name){
			$.ajax({
				type: "GET",
				dataType: 'text',
				url: '/message/all-customer/get-one-info?ticket_id='+ticket_id+'&msg_id='+msg_id+'&nick_name='+nick_name, // Use href attribute as URL
				success:function(data){
					$(obj).parents(".right-message").remove();//先删除原有的message
					var content=$(".all-chat");
					content.append(data);
				},
				error:function(){
					bootbox.alert('Error');
				}
			});
		},
		
		batchUpateToHasRead:function(){
			var selected_ticket_id_list = new Array();
		
			$(".chk_one:checkbox").each(function(){
				if (this.checked){
					var ticket_id = $(this).parents('tr').data('id');
					selected_ticket_id_list.push(ticket_id);
				}
			});
			
			if (selected_ticket_id_list.length==0){
				$.alert(Translator.t('请选择未读信息！'),'danger');
				return;
			}
			bootbox.confirm(Translator.t("是否确认标记为已读？"),function(r){
				if(!r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/message/all-customer/upate-to-has-read', 
					data: {ticket_ids : $.toJSON(selected_ticket_id_list)},
					success: function (result) {
						$.hideLoading();
						if (result.message!=""){
							var msg = result.message;
						}else{
							var msg = Translator.t('标记成功!');
						}
						var e = $.alert(msg,'success');
						e.then(function(){
							if (result.success){
								location.reload();
							}
						});
						return true;
					},
					error :function () {
						$.hideLoading();
						return false;
					}
				});
			});
		},
		
}	
	
		

		
		
		
		
		


		
		
		
	//标记已处理
		function TabMessage(platform_source,ticket_id){
			$.ajax({
				type:"GET",
				url:"/message/all-customer/tab-session?platform_source="+platform_source+"&ticket_id="+ticket_id,
				success:function(data){
					bootbox.alert(data);
				}
			});
		};
 //客服message
	function ShowDetailMessage(seller_id,customer_id,platform_source,ticket_id,related_type,os_flag,msg_sent_error,status){
		var related_id = arguments[8] ? arguments[8] : '';//!!!!!!!!!可选参数：related_id
//		$(".chat-message").scrollTop(100);
		var letter_no = $(this).parent().parent("tr").data("id");
		$('#letter_no_'+letter_no).css("font-weight","900");
		$.showLoading();
		$upOrDownText = $("input#upOrDownText").val();
		$.ajax({
			url:'/message/all-customer/show-message?seller_id='+seller_id+'&customer_id='+customer_id+'&platform_source='+platform_source+'&ticket_id='+ticket_id+'&related_type='+related_type+'&os_flag='+os_flag+'&msg_sent_error='+msg_sent_error+'&status='+status+'&related_id='+related_id,
			type:'GET',
			data:{upOrDownText:$upOrDownText},
			success:function(data){
				$.hideLoading();
				// if(CustomerTag.big_dialog!==''){
					// CustomerTag.big_dialog.modal('hide');
					$(".bootbox.modal.detail_letter.in .bootbox-close-button.close").click();
					$(".detail_letter").empty();
				// }
				$.showLoading();
				CustomerTag.big_dialog = bootbox.dialog({
					title:"订单留言",
					className: "detail_letter", 
					message:data,
				});
				
				// $(CustomerTag.big_dialog).init(function(){
					// history_order();
				// });
				$.hideLoading();
			},
			error:function(){
				bootbox.alert("Error");
			}
		});
		// $.get('/message/all-customer/show-message?seller_id='+seller_id+'&customer_id='+customer_id+'&platform_source='+platform_source+'&ticket_id='+ticket_id+'&related_type='+related_type+'&os_flag='+os_flag+'&msg_sent_error='+msg_sent_error+'&status='+status+'&related_id='+related_id,
			// {upOrDownText:$upOrDownText},
			// function (data){
				// $.hideLoading();
				// if(CustomerTag.big_dialog!==''){
					// CustomerTag.big_dialog.modal('hide');
				// }
				// CustomerTag.big_dialog = bootbox.dialog({
					// title:"订单留言",
					// className: "detail_letter", 
					// message:data,
				// });	
			// }
		// );
	}
	//客服获取订单信息	
	function GetOrderid(order_no){
		$.showLoading();
		$.get( '/message/all-customer/get-order-id?order_no='+order_no,
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("订单详细"),
					className: "order_info", 
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
		
	}
	//编辑后重新发送调用的界面
	function EditReSent(ticket_id,msg_id,obj,platform_source,seller_id,ticket_id,buyer_id,nick_name){
		var msg = $(obj).parent().next(".seller_msg").html();
		CustomerTag.msg_obj = obj; //将一个mssage设为全局对象
		$.showLoading();
		$.get( '/message/all-customer/edit-resent-message?ticket_id='+ticket_id+'&msg_id='+msg_id+'&msg='+msg+'&platform_source='+platform_source+'&seller_id='+seller_id+'&ticket_id='+ticket_id+'&buyer_id='+buyer_id+'&nick_name='+nick_name,
		   function (data){
				$.hideLoading();
				CustomerTag.small_dialog = bootbox.dialog({
					title: Translator.t("重发信息"),
					className: "resent_info", 
					message: data,
				});	
		});
		
	};
	//重发所有信息
	function ResentAllMessage(platform_source,seller_id,ticket_id,buyer_id){
//		bootbox.alert(platform_source+"-"+seller_id);
		$.ajax({
			type:"GET",
	 		dataType:"json",
			url:'/message/all-customer/resend-all-failure-message?platform_source='+platform_source+'&seller_id='+seller_id+'&ticket_id='+ticket_id+'&buyer_id='+buyer_id,
			success:function(data){
				if(data.success== true){
					bootbox.alert("重发所有信息成功");
					CustomerTag.big_dialog.modal('hide');
				}else{
					bootbox.alert(data.message);
				}
			},
			error:function(){
				bootbox.alert("Error");
			}
		});
	};
	//处理编辑后重新发送的消息
	function HandleEditReSent(ticket_id,msg_id,platform_source,seller_id,ticket_id,buyer_id,nick_name){
		if($(".edit-message-area").val() == ''){
			   bootbox.alert('发送消息不能为空！');
	            return;
			}
		var content=$(".edit-message-area").val();
		$.ajax({
			type:"GET",
	 		dataType:"json",
			url:'/message/all-customer/handle-edit-resent-message?platform_source='+platform_source+'&ticket_id='+ticket_id+'&msg_id='+msg_id+'&contet='+content+'&seller_id='+seller_id+'&ticket_id='+ticket_id+'&buyer_id='+buyer_id,
			success:function(data){
				if(data.success == true){
					CustomerTag.small_dialog.modal('hide');
					CustomerTag.updateResentInfo(CustomerTag.msg_obj,ticket_id,data.msg_id,nick_name);
				}else{
					bootbox.alert(data.error);
				}
			},
			error:function(){
				bootbox.alert("Error");
			}
		});
//		bootbox.alert(session_id+"-"+msg_id+"-"+content+"-"+platform_source);
	}
	
	//所有订单历史的JQ弹出location的初始化
	function initial(){
		//发送消息
//		$(".chat-message").scrollTop( $(".chat-message").height());
		//客服时间
		$( "#customer_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#customer_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
		//站内信时间
		$( "#letterstartdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#letterenddate" ).datepicker({dateFormat:"yy-mm-dd"});
	
		//详细地址
		$(".detail-location").each(function() {
			var track_no = $(this).data("id");
		    $(this).qtip({
		        show:{
					event: 'click',
					solo: false,
				},
				hide: {
					event: 'unfocus',
//		 			distance: 200
				},
		        content: {
		           button:true,
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/detail-location?track_no="+track_no // Use href attribute as URL
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'top center',
		    	   at: 'bottom right',
		           viewport: $("#page-content"),
		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift flip' // Requires Viewport plugin
		        	   
					},

		       },
		       style: {
		    	   classes: 'basic-qtip nopadding z-index',
		    	   width:600
		       },
		    });
		});

	//订单信息的历史的location
		$(".history-detail-location").each(function() {
			var track_no = $(this).data("id");
		    $(this).qtip({
		        show:{
					event: 'click',
					solo: false,
				},
				hide: {
					event: 'unfocus',
//		 			distance: 200
				},
		        content: {
		           button:true,
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/detail-location?track_no="+track_no // Use href attribute as URL
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'top center',
		    	   at: 'bottom left',
		           viewport: $("#letter-message").parents('.modal'),
		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift flip' // Requires Viewport plugin
		        	   
					},

		       },
		       style: {
		    	   classes: 'basic-qtip nopadding z-index-detail',
		    	   width:600
		       },
		    });
		});



	}
	
	
	
	//customer-list的订单历史初始化
	function orderList(){
		//标签添加初始化
	
		//客服时间
		$( "#customer_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#customer_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
		//站内信时间
		$( "#letterstartdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#letterenddate" ).datepicker({dateFormat:"yy-mm-dd"});
		//条件submit
		$('select[name=accounts],select[name=countrys],select[name=type],select[name=account],select[name=read],select[name=session_type],select[name=session_status]').change(function(){
			$("form:first").submit();
		});
		/* 触发table header 的select */
		$("[data-selname] > li").click(function(){
			var selname = $(this).parent().attr('data-selname');
			$("select[name="+selname+"]").val($("[name="+selname+"]").find("option").eq($(this).index()).val())
			$("select[name="+selname+"]").change();
		});
		//客服界面的hoverJQ
		$('tr').has('.egicon-flag-gray').on('mouseover',function(){
			if ($(this).find('.btn_tag_qtip').hasClass('div_space_toggle')){
				$(this).find('.btn_tag_qtip').removeClass('div_space_toggle');
			}
			
		});
		$('tr').has('.egicon-flag-gray').on('mouseleave',function(){
			if (! $(this).find('.btn_tag_qtip').hasClass('div_space_toggle')){
				$(this).find('.btn_tag_qtip').addClass('div_space_toggle');
			}
				
		});
		//没有信封的hover
		$('tr').has('.egicon-envelope-hover').on('mouseover',function(){
			if ($(this).find('.customer_btn_tag_bootbox').hasClass('div_space_toggle')){
				$(this).find('.customer_btn_tag_bootbox').removeClass('div_space_toggle');
			}
			
		});
		$('tr').has('.egicon-envelope-hover').on('mouseleave',function(){
			if (! $(this).find('.customer_btn_tag_bootbox').hasClass('div_space_toggle')){
				$(this).find('.customer_btn_tag_bootbox').addClass('div_space_toggle');
			}			
		});
		//客服的操作hoverJQ  
		//发送按钮
		$('tr').has('.btn_customer_reply_msg').on('mouseover',function(){
				$(this).find('.btn_customer_reply_msg').addClass('egicon-reply-msg-hover');
				$(this).find('.btn_customer_reply_msg').removeClass('egicon-reply-msg');	
		});
		$('tr').has('.btn_customer_reply_msg').on('mouseleave',function(){
				$(this).find('.btn_customer_reply_msg').addClass('egicon-reply-msg');
				$(this).find('.btn_customer_reply_msg').removeClass('egicon-reply-msg-hover');		
		});
		//历史订单按钮
		$('tr').has('.btn_customer_history_order').on('mouseover',function(){
			$(this).find('.btn_customer_history_order').addClass('egicon-history-order-hover');
			$(this).find('.btn_customer_history_order').removeClass('egicon-history-order');	
		});
		$('tr').has('.btn_customer_history_order').on('mouseleave',function(){
				$(this).find('.btn_customer_history_order').addClass('egicon-history-order');
				$(this).find('.btn_customer_history_order').removeClass('egicon-history-order-hover');		
		});
		//历史消息按钮
		$('tr').has('.btn_customer_history_msg').on('mouseover',function(){
			$(this).find('.btn_customer_history_msg').addClass('egicon-history-msg-hover');
			$(this).find('.btn_customer_history_msg').removeClass('egicon-history-msg');	
		});
		$('tr').has('.btn_customer_history_msg').on('mouseleave',function(){
				$(this).find('.btn_customer_history_msg').addClass('egicon-history-msg');
				$(this).find('.btn_customer_history_msg').removeClass('egicon-history-msg-hover');		
		});
		//最近订单的location
		$(".lastest-detail-location").each(function() {
			var track_no = $(this).data("id");
			var info_type = $(this).data("info-type");
			if(info_type!=='17track'){
				$(this).qtip({
					show:{
						event: 'click',
						solo: true,
					},
					hide: {
						event: 'click',
	//		 			distance: 200
					},
					content: {
					   button:true,
					   text: function(event, api) {
						   $.ajax({
							   url: "/message/all-customer/detail-location?track_no="+track_no // Use href attribute as URL
						   })
						   .then(function(content) {
							   // Set the tooltip content upon successful retrieval
							   api.set('content.text', content);
						   }, function(xhr, status, error) {
							   // Upon failure... set the tooltip content to error
							   api.set('content.text', status + ': ' + error);
						   });
			   
						   return 'Loading...'; // Set some initial text
					   }
				   },
				   position: {
					   my: 'top center',
					   at: 'bottom center',
					   viewport: $("#page-content"),
					   container: $("#page-content"),// 关闭modal时可以把qtip也remove掉
					   adjust: {
							method: 'shift flip' // Requires Viewport plugin
						   
						},

				   },
				   style: {
					   classes: 'basic-qtip nopadding',
					   width:600
				   },
				});
			}
		});
		
//		//消息记录
//		$(".message_list").each(function(){
//		    var msg = eval($(this).data("id"));//将数据以json形式保存到一个属性中
//		    var platform_source=msg[0].source;
//		    var customer_id=msg[0].buyid;
//		    var seller_id=msg[0].sellid;
//			$(this).qtip({   	
//	//		$("#sum").qtip({
//		        show:{
//		        	event: 'click',
//					solo: true,
//				},
//				hide: 'click',
//		        content: {
//		           button:true,
//		           text: function(event, api) {
//		               $.ajax({
//		                   url: "/message/all-customer/message-record?platform_source="+platform_source+"&customer_id="+customer_id+"&seller_id="+seller_id, // Use href attribute as URL
//		               })
//		               .then(function(content) {
//		                   // Set the tooltip content upon successful retrieval
//		                   api.set('content.text', content);
//		               }, function(xhr, status, error) {
//		                   // Upon failure... set the tooltip content to error
//		                   api.set('content.text', status + ': ' + error);
//		               });
//		   
//		               return 'Loading...'; // Set some initial text
//		           }
//		       },
//		       position: {
//		    	   my: 'top center',
//		    	   at: 'bottom center',
//		           viewport: $("#page-content"),
//		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
//		           adjust: {
//						method: 'shift flip' // Requires Viewport plugin
//					},
//		       },
//		       style: {
//		    	   classes: 'basic-qtip nopadding',
//		    	   width:600
//		       },
//		    });
//		});
//		
//		//订单历史
//		$(".order_list").each(function(){
//		    var message = eval($(this).data("id"));//将数据以json形式保存到一个属性中
//		    var platform_source=message[0].source;
//		    var customer_id=message[0].id;
//		    var style_list=message[0].list_style;
//			$(this).qtip({   	
//	//		$("#sum").qtip({
//		        show:{
//		        	event: 'click',
//					solo: true,
//				},
//				hide: 'click',
//		        content: {
//		           button:true,
//		           text: function(event, api) {
//		               $.ajax({
//		                   url: "/message/all-customer/order-list?platform_source="+platform_source+"&customer_id="+customer_id+"&style_list="+style_list, // Use href attribute as URL
//		               })
//		               .then(function(content) {
//		                   // Set the tooltip content upon successful retrieval
//		                   api.set('content.text', content);
//		               }, function(xhr, status, error) {
//		                   // Upon failure... set the tooltip content to error
//		                   api.set('content.text', status + ': ' + error);
//		               });
//		   
//		               return 'Loading...'; // Set some initial text
//		           }
//		       },
//		       position: {
//		    	   my: 'top center',
//		    	   at: 'bottom center',
//		           viewport: $("#page-content"),
//		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
//		           adjust: {
//						method: 'shift flip' // Requires Viewport plugin
//					},
//		       },
//		       style: {
//		    	   classes: 'basic-qtip nopadding',
//		    	   width:600
//		       },
//		    });
//		});
		//buyer消息
		$(".people_message").each(function(){
			var message = eval($(this).data("id"));//将数据以json形式保存到一个属性中
		    var platform_source=message[0].source;
		    var customer_id=message[0].buyid;
		    var seller_id=message[0].sellid;
			$(this).qtip({   	
	//		$("#sum").qtip({
		        show:{
		        	event: 'click',
					solo: true,
				},
				hide: 'click',
		        content: {
		           button:true,
		           title:"发言人信息",
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/people-message?platform_source="+platform_source+"&customer_id="+customer_id+"&seller_id="+seller_id, // Use href attribute as URL
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'top center',
		    	   at: 'bottom center',
		           viewport: $("#page-content"),
		           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift flip' // Requires Viewport plugin
					},
		       },
		       style: {
		    	   classes: 'people-qtip nopadding ',
		    	   width:600
		       },
		    });
		});
	}
	
	//弹出message里的订单历史的初始化
	function history_order(){
	//订单信息的订单历史
		$(".message_order_list").each(function(){
		    var message = eval($(this).data("id"));//将数据以json形式保存到一个属性中
		    var platform_source=message[0].source;
		    var customer_id=message[0].id;
		    var style_list=message[0].list_style;
			$(this).qtip({   	
	//		$("#sum").qtip({
		        show:{
		        	event: 'click',
					solo: true,
				},
				hide: 'click',
		        content: {
		           button:true,
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/order-list?platform_source="+platform_source+"&customer_id="+customer_id+"&style_list="+style_list, //sku_no:yes需要传入sku
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'top center',
		    	   at: 'bottom center',
		    	   viewport: $("#letter-message").parents('.modal'),
		           container: $("#letter-message").parents('.modal'),
//		           viewport: $("#page-content"),
	//	           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift shift' // Requires Viewport plugin
					},
		       },
		       style: {
		    	   classes: 'basic-qtip nopadding z-index',
		    	   width:600
		       },
		    });
		});
		
		$(".sku_order_list").each(function(){
		    var message = eval($(this).data("id"));//将数据以json形式保存到一个属性中
		    var platform_source=message[0].source;
		    var customer_id=message[0].id;
		    var style_list=message[0].list_style;
		    var sku_no=message[0].sku;
			$(this).qtip({   	
	//		$("#sum").qtip({
		        show:{
		        	event: 'click',
					solo: true,
				},
				hide: 'click',
		        content: {
		           button:true,
		           text: function(event, api) {
		               $.ajax({
		                   url: "/message/all-customer/sku-list?platform_source="+platform_source+"&customer_id="+customer_id+"&style_list="+style_list+"&sku_no="+sku_no, //sku_no:yes需要传入sku
		               })
		               .then(function(content) {
		                   // Set the tooltip content upon successful retrieval
		                   api.set('content.text', content);
		               }, function(xhr, status, error) {
		                   // Upon failure... set the tooltip content to error
		                   api.set('content.text', status + ': ' + error);
		               });
		   
		               return 'Loading...'; // Set some initial text
		           }
		       },
		       position: {
		    	   my: 'bottom center',
		    	   at: 'top center',
		    	   viewport: $("#letter-message").parents('.modal'),
		           container: $("#letter-message").parents('.modal'),
//		           viewport: $("#page-content"),
	//	           container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
		           adjust: {
						method: 'shift shift' // Requires Viewport plugin
					},
		       },
		       style: {
		    	   classes: 'basic-qtip nopadding z-index',
		    	   width:600
		       },
		    });
		});
		
		//订单信息的location
		$(".message-detail-location").each(function() {
			var track_no = $(this).data("id");
			var info_type = $(this).data("info-type");
			if(info_type!=='17track'){
				$(this).qtip({
					show:{
						event: 'click',
						solo: true,
					},
					hide: {
						event: 'click',
	//		 			distance: 200
					},
					content: {
					   button:true,
					   text: function(event, api) {
						   $.ajax({
							   url: "/message/all-customer/detail-location?track_no="+track_no // Use href attribute as URL
						   })
						   .then(function(content) {
							   // Set the tooltip content upon successful retrieval
							   api.set('content.text', content);
						   }, function(xhr, status, error) {
							   // Upon failure... set the tooltip content to error
							   api.set('content.text', status + ': ' + error);
						   });
			   
						   return 'Loading...'; // Set some initial text
					   }
				   },
				   position: {
					   my: 'top center',
					   at: 'bottom right',
					   viewport: $("#letter-message").parents('.modal'),
					   container: $("#letter-message").parents('.modal'),// 关闭modal时可以把qtip也remove掉
					   adjust: {
							method: 'shift flip' // Requires Viewport plugin
						   
						},

				   },
				   style: {
					   classes: 'basic-qtip nopadding',
					   width:600
				   },
				});
			}
		});
		//撤销发送
		$(".remove-message").each(function(){
			var obj=$(this);
			$(this).click(function(){
				var remove = eval($(this).data("id"));//将数据以json形式保存到一个属性中
			    var ticket_id=remove[0].ticket_id;
			    var msg_id=remove[0].msg_id;
			    var platform_source=remove[0].source;
			    var seller_id=remove[0].seller_id;
			    var ticket_id=remove[0].ticket_id;
			    var buyer_id=remove[0].buyer_id;
//			    $(this).click(function(){
//					bootbox.alert(session_id+"-"+msg_id+"-"+platform_source);
//				});
			    $.ajax({
					type:"GET",
			 		dataType:"json",
					url:'/message/all-customer/cancel-failure-message?platform_source='+platform_source+'&ticket_id='+ticket_id+'&msg_id='+msg_id+'&seller_id='+seller_id+'&ticket_id='+ticket_id+'&buyer_id='+buyer_id,
					success:function(data){
						if(data.success == true){
							obj.parents(".right-message").remove();
						}else{
							bootbox.alert("重发失败");
						}
							
					},
					error:function(){
						bootbox.alert('Error');
					}
				});
			});
		});
		//重新发送信息
		$(".resent-message").each(function(){
			var obj=$(this);
			$(this).click(function(){
				var remove = eval(obj.data("reid"));//将数据以json形式保存到一个属性中
			    var ticket_id=remove[0].ticket_id;
			    var msg_id=remove[0].msg_id;
			    var platform_source=remove[0].source;
			    var seller_id=remove[0].seller_id;
			    var ticket_id=remove[0].ticket_id;
			    var buyer_id=remove[0].buyer_id;
			    var nick_name=remove[0].nick_name;
//				$(this).click(function(){
//					bootbox.alert(session_id+"-"+msg_id+"-"+platform_source);
//				});
			    $.ajax({
					type:"GET",
			 		dataType:"json",
					url:'/message/all-customer/re-sent-message?platform_source='+platform_source+'&ticket_id='+ticket_id+'&msg_id='+msg_id+'&seller_id='+seller_id+'&ticket_id='+ticket_id+'&buyer_id='+buyer_id,
					success:function(data){
						if(data.success == true){
							CustomerTag.updateResentInfo(obj,ticket_id,msg_id,nick_name);
						}else{
							bootbox.alert("重发失败");
						}
					},
					error:function(){
						bootbox.alert('Error');
					}
				});
			});
		});
		
		
		

	}
	
	//翻译语言
	function translateLanguage(translate_language,obj){
		changeColor(obj,'trans_button');//按钮颜色激活转换
		var need = false,
			message_id = [];
		var language = translate_language;
		if(language == 'fra'){
			$('input[name="turn_button"]').each(function(){//清空对照的样式
				if($(this).hasClass("language_translate")){
					$(this).removeClass("language_translate");
					$(this).addClass("language_not_translate");
				}
			});
				
			$('.message-content').each(function(){
				$(this).find('span[name="newline"]').html("");//初始化换行
			})
			showTranslateHtml(language);
		}else{
			$('.message-content').each(function(){
				$(this).find('span[name="newline"]').html("");
				if($(this).find('p[name="'+language+'"]').html() == ''){
					need = true;
					message_id.push($(this).data("id"));
				}
			});
			
			if(need){
				$.showLoading();
				$.ajax({
					type: "POST",
					url: '/message/all-customer/translate-message',
					data: {
						"translateIds":message_id,
						"toLanguage":language,
						},
					dataType:'json',
					success: function(result){
						$.hideLoading();
						if(result.code == 400){
							bootbox.alert(result.message);
						}else{
							bornTranslateHtml(result);
							showTranslateHtml(language);
						}
					},
					error:function(){
					   $.hideLoading();
					   bootbox.alert("网络错误！");
					}
				});
			}else{
				showTranslateHtml(language);
			}
		}

		
	};
	//组织语言
	function bornTranslateHtml(transObj){
		for(var i=0;i<transObj.length;i++){
			$('div[data-id="'+ transObj[i].msg_id +'"]').find('p[name="'+ transObj[i].language +'"]').html(transObj[i].trans_content);
		}
	};
	
	function showTranslateHtml(language){
		$('.message-content').each(function(){
			$(this).find('p').each(function(){
				if($(this).attr("name") == language){
					$(this).css("display","block");
				}else{
					$(this).css("display","none");
				}
			})
		});
	};
	
	function compareContent(value,obj){
		if(value == 1){
			if($('.message-content:eq(0)').find('p[name="fra"]').css("display") == "none"){
				changeColor(obj,'turn_button');
				$('.message-content').each(function(){
					$(this).find('span[name="newline"]').html("<br />译文：");
					$(this).find('p[name="fra"]').css("display","block");
				});
			}
		}else if(value == 2){
			var fra = $('.message-content:eq(0)').find('p[name="fra"]').css("display") == "block"?true:false;
			var en = $('.message-content:eq(0)').find('p[name="en"]').css("display") == "block"?true:false;
			var zh = $('.message-content:eq(0)').find('p[name="zh"]').css("display") == "block"?true:false;
			if(fra&&(en||zh)){
				changeColor(obj,'turn_button');
				$('.message-content').each(function(){
					$(this).find('span[name="newline"]').html("");
					$(this).find('p[name="fra"]').css("display","none");
				});
			}
			
		}
	};
	
	//按钮颜色激活转换
	function changeColor(obj,name){
		var value = $(obj).val();
		
		if($(obj).hasClass("language_not_translate")){
			$(obj).removeClass("language_not_translate");
			$(obj).addClass("language_translate");
		}
		
		$('input[name="'+ name +'"]').each(function(){
			if($(this).val() != value){
				if($(this).hasClass("language_translate")){
					$(this).removeClass("language_translate");
					$(this).addClass("language_not_translate");
				}
			}
		});
	}
	
	function showExtendsBuyerAcceptGoodsTimeBox(orderId,Platform){
		var orderIdList = [];
		orderIdList.push(orderId);
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/message/all-customer/show-extends-buyer-accept-goods-time-box', 
				data: {orderIdList : orderIdList ,  Platform :Platform},
				success: function (result) {
					//bootbox.alert(result.message);
					bootbox.dialog({
						title: Translator.t("延长买家收货时间"),
						className: "order_info", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("确定"),  
								className: "btn-primary",  
								callback: function () { 
									ExtendsBuyerAcceptGoodsTime();
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
	}
	
	function ExtendsBuyerAcceptGoodsTime(){
		var extenddataList = [];
		$('[name=extend_days]').each(function(){
			tmpextenddata = {'order_id': $(this).data('order-id'), 'extendday': $(this).val(), 'selleruserid':$(this).data('selleruserid') }; 
			extenddataList.push(tmpextenddata);
		});

		if (extenddataList.length == 0){
			return ;
		}

		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/message/all-customer/extends-buyer-accept-goods-time', 
				data: {extenddataList : extenddataList ,  Platform :OrderCommon.CurrentPlatform},
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
	}
	

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
function sleep(numberMillis){
	var now = new Date();
	var exitTime = now.getTime() + numberMillis;
	while(true){
		now = new Date();
		if (now.getTime() > exitTime)
		return;
	}
} 