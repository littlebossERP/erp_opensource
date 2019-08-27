/**
 +------------------------------------------------------------------------------
 * 工单列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		ticket
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof ticket === 'undefined')  ticket = new Object();

ticket.list={
	
	init:function(){
		$("input[name='chk_ticket_all']").click(function(){
			$("input[name='chk_ticket_one']").prop("checked", this.checked);
		});
	},
	
	
	initReplyBtn:function(){
		var canReply = $("input[name='canReply']").val();
		var status_cn = $("input[name='status_cn']").val();
		if(canReply!=='1'){
			$("button[data-bb-handler='Reply']").prop('disabled','disabled');
			$("button[data-bb-handler='Reply']").parent().prop('title',Translator.t("该订单处于")+Translator.t(status_cn+"状态,")+Translator.t("已不能回复"));
		}
	},
	
	
	initFormValidate:function(){
		$("input[name='subject']").formValidation({validType:['trim','length[1,255]','safeForHtml'],tipPosition:'left',required:true});
		$("textarea[name='message']").formValidation({validType:['trim','length[1,10000]','safeForHtml'],tipPosition:'left'});
		$("input[name='contact_info[qq]']").formValidation({validType:['trim','length[0,20]','integer'],tipPosition:'left'});
	},
	
	
	cancelTicket:function(id,number){
		if(id=='' || number==''){
			bootbox.alert(Translator.t("指定工单部分信息丢失，操作中止。"));
			return false;
		}
			
		bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确定撤销工单：")+"<span style='color:red;'>"+number+"</span>?</b></div>",function(r){
			if (!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/ticket/ticket/cancel?ticket_id='+id,
				data: {} , 
				success: function (result) {
					$.hideLoading();
					if (result.success){
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("撤销成功!"),
							callback: function() {
								$.showLoading();
								window.location.reload();
							}, 
						});
					}
					else{
						$.hideLoading();
						bootbox.alert(Translator.t("撤销失败:")+result.message);
					}
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t("数据传输失败，请稍后再试"));
				}
			});
		});
	},
	
	reopenTicket:function(id,number){
		if(id=='' || number==''){
			bootbox.alert(Translator.t("指定工单部分信息丢失，操作中止。"));
			return false;
		}
		
		bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确定重新激活工单：")+"<span style='color:red;'>"+number+"</span>?</b></div>",function(r){
			if (!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/ticket/ticket/reopen?ticket_id='+id,
				data: {}, 
				success: function (result) {
					$.hideLoading();
					if (result.success){
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("激活成功!"),
							callback: function() {
								$.showLoading();
								window.location.reload();
							}, 
						});
					}
					else{
						$.hideLoading();
						bootbox.alert(Translator.t("激活失败:")+result.message);
					}
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t("数据传输失败，请稍后再试"));
				}
			});
		});
	},
	
	deleteTicket:function(id,number){
		if(id=='' || number==''){
			bootbox.alert(Translator.t("指定工单部分信息丢失，操作中止。"));
			return false;
		}
		
		bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确定删除工单：")+"<span style='color:red;'>"+number+"</span>?</b></div>",function(r){
			if (!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/ticket/ticket/delete?ticket_id='+id,
				data: {}, 
				success: function (result) {
					$.hideLoading();
					if (result.success){
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("删除成功!"),
							callback: function() {
								$.showLoading();
								window.location.reload();
							}, 
						});
					}
					else{
						$.hideLoading();
						bootbox.alert(Translator.t("删除失败:")+result.message);
					}
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t("数据传输失败，请稍后再试"));
				}
			});
		});
	},

	addTicket:function(){
		$.showLoading();
		var url='/ticket/ticket/create';
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "creat_ticket_win",
					title: Translator.t("新建工单"),//
					message: data,
					buttons:{
						OK: {
							label: Translator.t("提交"),  
							className: "btn-success",  
							callback: function () {
								if(!ticket.list.saveTicket())
									return false;
							}  
						},
						Cancel: {
							label: Translator.t("取消"),  
							className: "btn-default",  
							callback: function () {
							}
						}, 
					}
				});
				ticket.list.initFormValidate();
			}
		);
	},
	
	viewTicket:function(id){
		$.showLoading();
		var url='/ticket/ticket/view?id='+id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "view_ticket_win",
					title: Translator.t("工单详情"),//
					message: data,
					buttons:{
						Cancel: {
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
						Reply: {
							label: Translator.t("回复"),  
							className: "btn-success",  
							callback: function () {
								ticket.list.replyTicket(id);
								return false;
							}
						},
					}
				});
			}
		);
	},
	
	
	replyTicket:function(id){
		$.get(
			'/ticket/ticket/reply?ticket_id='+id,
			function(data){
				bootbox.dialog({
					className : "reply_ticket_win",
					title: Translator.t("回复工单"),
					message: data,
					buttons:{
						Cancel: {
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
						OK: {
							label: Translator.t("确认回复"),  
							className: "btn-success",  
							callback: function () {
								$.showLoading();
								$.ajax({
									type: "POST",
									dataType: 'json',
									url: '/ticket/ticket/create-reply?ticket_id='+id,
									data: $("form.tick-reply-data").serialize(),
									success: function (result) {
										$.hideLoading();
										if (result.success==true){
											bootbox.alert({
												message:Translator.t('回复成功！'),
												callback: function(){ 
													$('.view_ticket_win').modal('hide');
													$('.view_ticket_win').on('hidden.bs.modal', '.modal', function(event) {
														$(this).removeData('bs.modal');
													});
													ticket.list.viewTicket(id);
													// var divHeight = $(".view_ticket_win .modal-body")[0].scrollHeight;
													// $(".view_ticket_win .modal-body")[0].scrollTop=divHeight;
												}
											});
										}else{
											bootbox.alert(result.message);
											return false;
										}
									},
									error :function () {
										$.hideLoading();
										bootbox.alert(Translator.t('回复失败：数据传输错误！'));
										return false;
									}
								});
							}
						},
					}
				});
			}
		);
	},

	batchCancelTicket:function(){
		var idList = new Array();
		$('input[name=chk_ticket_one]:checked').each(function(){
			idList.push(this.value);
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要撤销的工单'));
			return false;
		}
		
		if (idList.length>0){
			bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确认批量关闭工单？")+"</b></div>",function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/ticket/ticket/batch-cancel', 
					data:{ids :  $.toJSON(idList) },
					dataType:"json",
					success: function (result) {
						$.hideLoading();
						if (result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: Translator.t("批量关闭成功!"),
								callback: function() {
									$.showLoading();
									window.location.reload();
								}, 
							});
						}else{
							bootbox.alert(result.message);
						}
						return true;	
					},
					error :function () {
						return false;
					}
				});
			});
		}
	},
	
		batchReopenTicket:function(){
		var idList = new Array();
		$('input[name=chk_ticket_one]:checked').each(function(){
			idList.push(this.value);
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要开启的工单'));
			return false;
		}
		
		if (idList.length>0){
			bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确认批量开启工单？")+"</b></div>",function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/ticket/ticket/batch-reopen', 
					data:{ids :  $.toJSON(idList) },
					dataType:"json",
					success: function (result) {
						$.hideLoading();
						if (result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: Translator.t("批量开启成功!"),
								callback: function() {
									$.showLoading();
									window.location.reload();
								}, 
							});
						}else{
							bootbox.alert(result.message);
						}
						return true;	
					},
					error :function () {
						return false;
					}
				});
			});
		}
	},
	
	batchDeleteTicket:function(){
		var idList = new Array();
		var numberList = '';
		$('input[name=chk_ticket_one]:checked').each(function(){
			idList.push(this.value);
			var parentTr = $(this).parents('tr');
			if(numberList=='')
				numberList+=parentTr.find('td').eq(1).html();
			else
				numberList+=";"+parentTr.find('td').eq(1).html();
			
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要删除的工单'));
			return false;
		}
		
		if (idList.length>0){
			bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确认批量删除工单: ")+"<span style='color:red'>"+numberList+"</span> ?</b></div>",function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/ticket/ticket/batch-delete', 
					data:{ids :  $.toJSON(idList) },
					dataType:"json",
					success: function (result) {
						$.hideLoading();
						if (result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: Translator.t("删除成功!"),
								callback: function() {
									$.showLoading();
									window.location.reload();
								}, 
							});
						}else{
							bootbox.alert(result.message);
						}
						return true;	
					},
					error :function () {
						return false;
					}
				});
			});
		}
	},
	
	saveTicket:function(){
		if (! $('#ticket_info_form').formValidation('form_validate')){
			bootbox.alert(Translator.t('有必填项未填，或格式不正确!'));
			return false;
		}
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: '/ticket/ticket/create-ticket',
			data: $('#ticket_info_form').serialize(),
			success: function (result) {
				$.hideLoading();
				if (result.success==true){
					bootbox.alert({
						message:Translator.t('保存成功！'),
						callback: function(){  
							window.location.href = global.baseUrl+'ticket/ticket/index';
						}
					});
				}else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('保存失败：数据传输错误！'));
				return false;
			}
		});
	},
	
}

$(function() {
	ticket.list.init();
});