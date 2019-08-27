

if (typeof Customertemplate === 'undefined')  Customertemplate = new Object();
Customertemplate = {
		currentTemplateId:0,
		init:function(){
			/* 触发table header 的select */
			$("[data-selname] > li").each(function(){
				Customertemplate.LiClick(this);
			});
//			$('select[name=language]').each(function(){
//				Customertemplate.LiChange(this);
//			});
//			$("[data-selname] > li").click(function(){
//				var selname = $(this).parent().attr('data-selname');
//				var id = $(this).parents("tr").attr('data-tr-id');
//				var language = $("tr[data-tr-id="+id+"]").find("select[name="+selname+"]").find("option").eq($(this).index()).val();
////				$("select[name="+selname+"]").val($("[name="+selname+"]").find("option").eq($(this).index()).val())
//				$(this).parents("tr").attr('data-language',language);
//				$("tr[data-tr-id="+id+"]").find("select[name="+selname+"]").change();
//			});
			//条件submit
//			$('select[name=language]').change(function(){
//				var tr_num=$(this).parents("tr").attr("data-tr-id");
//				var lang = $(this).parents("tr").attr("data-language");
////				alert(tr_num+"-"+lang);
//				Customertemplate.updateCustomerTemplateInfo(tr_num,lang);
////				alert(tr_num);
//				//$("form:first").submit();
//			});
			
			//发送窗口语言切换
			$("#template_id").change(function(){
				var template_id = $(this).val();
				$.ajax({
					type: "GET",
					dataType: 'text',
					url: '/message/all-customer/detail-message-language?template_id='+template_id, // Use href attribute as URL
					success:function(data){
						if(data){
							$('#template_language').html(data);
							Customertemplate.SentMessageLanguage();
						}else{
							bootbox.alert('数据错误');
						}
						
//						if (data.TrHtml){
//							for(var tmp_track_no in data.TrHtml ){
//								$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
//							}
//						}
						
					},
					error:function(){
						bootbox.alert('Error');
					}
				});
			});
			
			//语言窗口切换时，获取模版的值
			$("#template_language").change(function(){
				var template_id = $("#template_id").val();
				if(template_id == -1){
					bootbox.alert('请选择模版');
				}else{
					Customertemplate.SentMessageLanguage();
				}
			});
		},
		//获取模版的值
		SentMessageLanguage:function(){
			var template_id = $("#template_id").val();
			var language = $("#template_language").val();
			var relate_id = $("#language_related_id").val();
			var seller_id = $("#language_seller_id").val();
			var ticket_id = $("#language_ticket_id").val();
			$.ajax({
				type: "GET",
				dataType: 'json',
				url: '/message/all-customer/fill-template-data?template_id='+template_id+'&language='+language+'&relate_id='+relate_id+'&seller_id='+seller_id+'&ticket_id='+ticket_id, 
				success:function(data){
					if(data.success == true){
						$(".message-area").val(data.content);
					}else{
						bootbox.alert('数据错误');
					}
					
				},
				error:function(){
					bootbox.alert('Error');
				}
			});
		},
		LiClick:function(obj){//click事件
			$(obj).click(function(){
				var selname = $(this).parent().attr('data-selname');
				var id = $(this).parents("tr").attr('data-tr-id');
				var language = $("tr[data-tr-id="+id+"]").find("select[name="+selname+"]").find("option").eq($(this).index()).val();
				$(this).parents("tr").attr('data-language',language);
//				var change_obj =$("tr[data-tr-id="+id+"]").find("select[name="+selname+"]");
				var change_obj =$("tr[data-tr-id="+id+"]");
				Customertemplate.LiChange(change_obj);
			});
		},
		
		LiChange:function(obj){
//			$(obj).change(function(){
//				var tr_num=$(this).parents("tr").attr("data-tr-id");
//				var lang = $(this).parents("tr").attr("data-language");
//				Customertemplate.updateCustomerTemplateInfo(tr_num,lang);
//			});
			var tr_num=$(obj).attr("data-tr-id");
			var lang = $(obj).attr("data-language");
			Customertemplate.updateCustomerTemplateInfo(tr_num,lang);
		},
		//切换语言
		updateCustomerTemplateInfo:function(tr_num,language){
			$.ajax({
				type: "GET",
				dataType: 'text',
				url: '/message/all-customer/update-template-tr?tr_num='+tr_num+'&language='+language, // Use href attribute as URL
				success:function(data){
					if(data){
						$('#tr_template_info_'+tr_num).html(data);
						Customertemplate.init();
					}else{
						bootbox.alert('数据错误');
					}
					
//					if (data.TrHtml){
//						for(var tmp_track_no in data.TrHtml ){
//							$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
//						}
//					}
					
				},
				error:function(){
					bootbox.alert('Error');
				}
			});
		},
		
		//保存模版
		SaveTemplate:function(callback){
			$.ajax({
				type: "GET",
				dataType: 'json',
				url:'/message/all-customer/save-template', 
				data: $('#send-station-letter').serialize(),
				success:function(result){
					if(result.success == false){
						bootbox.alert(result.message);
					}else{
						bootbox.alert("模版保存成功");
						callback();
					}
				
				},
				error:function(){
					bootbox.alert("请联系客服！");
				}
			});
		},
		
		//新建模版
		NewCustomerTemplate:function(){
			$.showLoading();
			$.get('/message/all-customer/new-template',
			   function (data){
					$.hideLoading();
					var thisbox = bootbox.dialog({
						title:"新增模版",
						className: "xlbox", 
						message:data,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () { 
									Customertemplate.SaveTemplate(function(){
										$(thisbox).modal('hide');
									});
									return false;
								}
							}, 
							
							Cancel: {  
								label: Translator.t("返回"),  
								className: "btn-transparent",  
								callback: function () {  
								}
							}, 
						}
					});	
			});
		},
		
		//修改模版
		EditCustomerTemplate:function(title,id,language){
			Customertemplate.currentTemplateId = id;
			$.showLoading();
			$.get('/message/all-customer/edit-template?title='+title+'&id='+id+'&language='+language,
			   function (data){
					$.hideLoading();
					var thisbox = bootbox.dialog({
						title:"修改模版",
						className: "xlbox", 
						message:data,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () { 
									Customertemplate.SaveTemplate(function(){
										$(thisbox).modal('hide');
										Customertemplate.UpdateTemplateTr(Customertemplate.currentTemplateId,"update_one");
									});
									return false;
								}
							}, 
							
							Cancel: {  
								label: Translator.t("返回"),  
								className: "btn-transparent",  
								callback: function () {  
								}
							}, 
						}
					});	
			});
		},
		//新增同一模版的其他语言
		OtherLanguageTemplate:function(title,id,isupdate,obj){
			var language_array = eval("("+$(obj).prev("#language_array").val()+")");
			var cur_language_array = language_array.lang;
			Customertemplate.currentTemplateId = id;
			$.showLoading();
			$.get('/message/all-customer/other-language-template?title='+title+'&id='+id+'&isupdate='+isupdate,
			   function (data){
					$.hideLoading();
					var thisbox = bootbox.dialog({
						title:"新增语言模版",
						className: "xlbox", 
						message:data,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () { 
									Customertemplate.SaveTemplate(function(){
										$(thisbox).modal('hide');
										Customertemplate.UpdateTemplateTr(Customertemplate.currentTemplateId,"update_one");
									});
									
									return false;
								}
							}, 
							
							Cancel: {  
								label: Translator.t("返回"),  
								className: "btn-transparent",  
								callback: function () {  
								}
							}, 
						},
					
					});	
					
					$.each(cur_language_array, function(key,value){//过滤相关的选项
						$('select[name=letter_template_language]>option[value='+key+']').remove();
					});
					
			});
		},
		
		addLetterVariance:function(){
			textObj = $('#letter_template');
			textFeildValue = $('select[name=letter_template_variance]').val();
			 if (document.all) {
				if (textObj.createTextRange && textObj.caretPos) {
					var caretPos = textObj.caretPos;
					caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? textFeildValue + ' ' : textFeildValue;
				} else {
					textObj.value = textFeildValue;
				}
			} else {
				if (textObj.setSelectionRange == undefined)
					textObj = textObj[0];
				if (textObj.setSelectionRange) {
					var rangeStart = textObj.selectionStart;
					var rangeEnd = textObj.selectionEnd;
					var tempStr1 = textObj.value.substring(0, rangeStart);
					var tempStr2 = textObj.value.substring(rangeEnd);
					textObj.value = tempStr1 + textFeildValue + tempStr2;
				} else {
					//alert("This version of Mozilla based browser does not support setSelectionRange");
				}
			}

		},
		//预览模版
		PreviewCustomerTemplate:function(id,language){
			$.showLoading();
			$.get('/message/all-customer/preview-template?id='+id+'&language='+language,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						title:"预览模版",
						className: "preview-box", 
						message:data,
						buttons:{
							Cancel: {  
								label: Translator.t("关闭"),  
								className: "btn-transparent",  
								callback: function () {  
								}
							}, 
						}
					});	
			});
		},
		
		//保存模版或新增模版行刷新
		UpdateTemplateTr:function(tr_num,update_one){
			$.ajax({
				type: "GET",
				dataType: 'text',
				url: '/message/all-customer/update-template-tr?tr_num='+tr_num+'&update_one='+update_one, // Use href attribute as URL
				success:function(data){
					if(data){
						$('#tr_template_info_'+tr_num).html(data);
						Customertemplate.init();
					}else{
						bootbox.alert('数据错误');
					}
					
//					if (data.TrHtml){
//						for(var tmp_track_no in data.TrHtml ){
//							$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
//						}
//					}
					
				},
				error:function(){
					bootbox.alert('Error');
				}
			});
		},
		
		//删除模版
		DeleteTemplate:function(id,obj){
			bootbox.confirm("您确定要删除所有模版？",function(result){
				if(result){
					$.ajax({
						type: "GET",
						dataType: 'json',
						url: '/message/all-customer/delete-template?id='+id, // Use href attribute as URL
						success:function(data){
							if(data.success == true){
								$(obj).parents("tr").remove();
								bootbox.alert("删除成功！");
							}else{
								bootbox.alert("模版删除失败！");
							}
						},
						error:function(){
							bootbox.alert('Error');
						}
					});
				}
			});
		},
		
		
}