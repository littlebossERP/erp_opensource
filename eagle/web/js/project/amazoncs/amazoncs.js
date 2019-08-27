/**
 +------------------------------------------------------------------------------
 * amazon cs js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		amazoncs
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof amazoncs === 'undefined') amazoncs= new Object();

amazoncs.EmailList = {
	'store_info':{},
	'countdown' : 300,
	'newEmailAddressVerifyed' : false,
	'initBtn': function() {
	
	},
	
	'disabledSaveBtn' : function(){
		$('.bind-email-win button.btn.btn-success').html('检查邮箱地址授权中，请等待...').prop('disabled',true);
	},
	'enableSaveBtn' : function(){
		$('.bind-email-win button.btn.btn-success').html('保存').removeAttr('disabled');
	},
	
	'openCreateEmailBindWin' : function(){
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/create-email-bind-win', 
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("添加邮箱"),
					className: "bind-email-win", 
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {
								
							}
						}, 
						Ok:{
							label: Translator.t("添加"),  
							className: "btn-success",  
							callback: function () {
								rtn = amazoncs.EmailList.saveBindingEmailAddress();
								if(!rtn)
									return false;
								else
									window.location.reload();
							}
						}
					}
				});
			},
			error :function () {
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
	},
	'selectionChange' :  function(){
		var selection = $("select[name='merchant_id']").val();
		if(amazoncs.EmailList.store_info[selection]==undefined)
			return false;
		
		var store_info = amazoncs.EmailList.store_info[selection]['market_places'];
		var tmp_html = "";
		for(var c_code in store_info){
			tmp_html+='<option value="'+store_info[c_code]+'">'+c_code+'</option>';
		}
		$("select[name='market_places_id']").html(tmp_html);
	},
	
	'sendVerifyeEmail' : function(obj){
		var email_address = $("input[name='email_address']").val();
		if(email_address==undefined || email_address==''){
			bootbox.alert("请输入有效的邮箱地址!");
			return false;
		}
		var myreg = new RegExp("^[A-Za-z0-9]+([-_.][A-Za-z0-9]+)*@([A-Za-z0-9]+[-.])+[A-Za-z0-9]{2,5}$"); 
		if(myreg.test(email_address)!==true){
			bootbox.alert("请输入有效的邮箱地址!");
			$("input[name='email_address']").focus();
			return false;
		}
		
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/send-verifye-email', 
			data:{email_address:email_address},
			dataType:'json',
			success:function(result){
				$.hideLoading();
				if(result.success){
					amazoncs.EmailList.settime(obj);
					return true;
				}else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert("发送验证邮件失败，请联系客服");
				return false;
			}
		});
	},
	
	'checkVerifye' : function(){
		var email_address = $("input[name='email_address']").val();
		if(email_address==undefined || email_address==''){
			bootbox.alert("请输入有效的邮箱地址!");
			return false;
		}
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/check-verifye', 
			data:{email_address:email_address},
			dataType:'json',
			success:function(result){
				$.hideLoading();
				if(result.success){
					$("#verifye_success").show();
					$("#verifye_failed").hide();
					$(".bind-email-win button.btn.btn-success").removeAttr('disabled');
					return true;
				}else{
					$("#verifye_success").hide();
					$("#verifye_failed").show();
					$("#verifye_failed").html(result.message);
					$(".bind-email-win button.btn.btn-success").prop('disabled',true);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert("后台传输出错，请联系客服");
				return false;
			}
		});
		
	},
	
	'settime' : function(obj) {
		if (amazoncs.EmailList.countdown == 0) { 
			$(obj).removeAttr("disabled");    
			$(obj).val("发送验证码到该邮箱"); 
			amazoncs.EmailList.countdown = 300;
			return true;
		}else{ 
			$(obj).attr("disabled", true); 
			$(obj).val("重新发送("+amazoncs.EmailList.countdown+")"); 
			amazoncs.EmailList.countdown--; 
		}
		setTimeout(function(){
			amazoncs.EmailList.settime($(obj));
		},1000);
	},
	
	'saveBindingEmailAddress' : function(){
		var email_address = $("input[name='email_address']").val();
		if(email_address=='' || email_address==undefined){
			bootbox.alert('请输入邮箱');
			return false;
		}
		var seller_id = $("select[name='merchant_id']").val();
		if(seller_id=='' || seller_id==undefined){
			bootbox.alert('请选择店铺');
			return false;
		}
		var site_id = $("select[name='market_places_id']").val();
		if(site_id=='' || site_id==undefined){
			bootbox.alert('请选择站点');
			return false;
		}
		
		/*
		var verifye_success = $("#verifye_success").css('display');
		if(verifye_success=='none'){
			bootbox.alert('邮箱未获授权');
			return false;
		}
		*/
		var verifyed = $("input#verifyed").val();
		if(verifyed=='' || verifyed=='0' || verifyed==0)
			verifyed=false;
		else
			verifyed=true;
		
		$.showLoading();
		amazoncs.EmailList.disabledSaveBtn();
		
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/save-binding-email', 
			data:{email_address:email_address, seller_id:seller_id, site_id:site_id, verifyed:verifyed },
			dataType:'json',
			success:function(result){
				$.hideLoading();
				if(result.success && !result.error_code){
					bootbox.alert({
						buttons: {
							ok: {
								label: 'OK',
								className: 'btn-primary'
							}
						},
						message: '保存成功',
						callback: function() {
							window.location.reload();
						}
					});
				}else{
					if(typeof(result.error_code)!=='undefined'){
						if(result.error_code!=='3'){
							bootbox.alert(result.message);
							amazoncs.EmailList.enableSaveBtn();
							return false;
						}else{
							amazoncs.EmailList.newEmailAddressVerifyed = false;
							amazoncs.EmailList.ajaxCheckVerifyed(email_address);
							
						}
					}else{
						bootbox.alert("未知错误导致保存失败，请联系客服");
						amazoncs.EmailList.enableSaveBtn();
						return false;
					}
				}
			},
			error :function () {
				amazoncs.EmailList.enableSaveBtn();
				$.hideLoading();
				bootbox.alert("保存失败，请联系客服");
				return false;
			}
		});	
	},
	
	'ajaxCheckVerifyed' : function(email_address){
		if(!amazoncs.EmailList.newEmailAddressVerifyed){
			$.showLongLoading();
			$.ajax({
				type: "GET",
				url:'/amazoncs/amazoncs/ajax-check-verifye', 
				data:{email_address:email_address},
				dataType:'json',
				success:function(check){
					if(check.success){
						amazoncs.EmailList.newEmailAddressVerifyed = true;
						$.hideLongLoading();
						$("input#verifyed").val('1');
						$("#verifye_success").show();
						amazoncs.EmailList.enableSaveBtn();
						return;
					}
				},
				error :function(){
					$.hideLongLoading();
					bootbox.alert("保存失败，请联系客服");
					return false;
				}
			});
		}
		
		setTimeout(function(){
			amazoncs.EmailList.ajaxCheckVerifyed(email_address);
		},10000);		
	},
	
	'switchEmailAddressStatus' : function(id, active_status){
		if(active_status=='unActive'){
			var confirmTitle = "确认关闭该邮箱绑定?<br>(待发送中的任务不会由于弃用该绑定而取消发送，需要手动取消。)";
		}
		else if (active_status=='active'){
			var confirmTitle = "确认启用该邮箱绑定?<br>(已取消的任务不会由于启用该绑定而重新发送)";
		}
			
		bootbox.confirm(confirmTitle,function(r){
			if(r){
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/amazoncs/amazoncs/switch-email-address-status', 
					data:{id:id,active_status:active_status},
					dataType:'json',
					success:function(result){
						$.hideLoading();
						if(result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: '切换成功',
								callback: function() {
									window.location.reload();
								}
							});
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert("保存失败，请联系客服");
						return false;
					}
				});
			}
		});
	},
	
	'unbindEmailAddress' : function(id){
		bootbox.confirm('确定要删除该绑定？',function(r){
			if(r){
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/amazoncs/amazoncs/unbing-email-address', 
					data:{id:id},
					dataType:'json',
					success:function(result){
						$.hideLoading();
						if(result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: '解绑成功',
								callback: function() {
									window.location.reload();
								}
							});
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert("操作失败，请联系客服");
						return false;
					}
				});
			}
		});
	},
},
amazoncs.QuestList={
	'seller_site_list':{},
	'template_list':{},
	'initSelectDom' : function (){
		$("#seller_id_select").change(function(){
			var selected_seller = $(this).val();
			var site_list = amazoncs.QuestList.seller_site_list[selected_seller];
			var selected_site = $("#site_id_select").val();
			var selected_template = $("#quest_template_id").val();
			
			$("#site_id_select").html('');
			var site_select_html = '<option value="">销售站点</option>';
			if(site_list!==undefined && site_list!==''){
				for(var site in site_list){
					if(i=="site")
						return;
					var is_selected = '';
					if(selected_site==site)
						is_selected='selected';
					site_select_html += '<option value="'+site+'" '+is_selected+'>'+site_list[site]+'</option>';
				}
			}
			$("#site_id_select").html(site_select_html);
			
			var template_list = amazoncs.QuestList.template_list;
			$("#quest_template_id").html('');
			var template_select_html = '<option value="">模板名称</option>';
			if(template_list!==undefined && template_list!==''){
				for(var i in template_list){
					if(i!=="remove"){
						var match_seller = false;
						var match_site = false;
						
						if(selected_seller=='' || selected_seller==undefined)
							match_seller = true;
						else if(template_list[i].seller_id==selected_seller)
							match_seller = true;
						
						if(selected_site=='' || selected_site==undefined)
							match_site = true;
						else if(template_list[i].site_id==selected_site)
							match_site = true;
						
						if(match_seller && match_site){
							var is_selected = '';
							if(selected_template==template_list[i].id)
								is_selected='selected';
							template_select_html += '<option value="'+template_list[i].id+'" '+is_selected+'>'+template_list[i].name+'</option>';
						}
					}					
				}
			}
			$("#quest_template_id").html(template_select_html);
			
		});
		
		$("#site_id_select").change(function(){
			var selected_seller = $("#seller_id_select").val();
			var selected_site = $("#site_id_select").val();
			var selected_template = $("#quest_template_id").val();
			
			var template_list = amazoncs.QuestList.template_list;
			$("#quest_template_id").html('');
			var template_select_html = '<option value="">模板名称</option>';
			if(template_list!==undefined && template_list!==''){
				for(var i in template_list){
					if(i!=="remove"){
						var match_seller = false;
						var match_site = false;
						
						if(selected_seller=='' || selected_seller==undefined)
							match_seller = true;
						else if(template_list[i].seller_id==selected_seller)
							match_seller = true;
						
						if(selected_site=='' || selected_site==undefined)
							match_site = true;
						else if(template_list[i].site_id==selected_site)
							match_site = true;
						
						if(match_seller && match_site){
							var is_selected = '';
							if(selected_template==template_list[i].id)
								is_selected='selected';
							template_select_html += '<option value="'+template_list[i].id+'" '+is_selected+'>'+template_list[i].name+'</option>';
						}
					}					
				}
			}
			//console.log(template_select_html);
			$("#quest_template_id").html(template_select_html);
		});
	},
	
	'ViewQuestContent' : function (quest_id){
		$.showLoading();
		$.get('/amazoncs/amazoncs/preview-quest-mail?quest_id='+quest_id, 
			function(data){
				$.hideLoading();
				bootbox.dialog({
					title: "邮件内容",
					className: "pre-view-win", 
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
			}
		);
	},
	
	'EditQuestMailContent' : function(){
		$("#quest-mail-data .editable").each(function(){
			$(this).removeAttr("readonly");
		});
		$("#edit-btn").hide();
		$("#save-edit-btn").show();
	},
	
	'SaveQuestMailContentEditting' : function (){
		var quest_id = $("#quest-mail-data input[name='quest_id']").val();
		if(quest_id=='' || quest_id==undefined){
			bootbox.alert(Translator.t('任务编号丢失!'));
			return false;
		}
		
		var subject = $("#quest-mail-data input[name='subject']").val();
		if(subject=='' || subject==undefined){
			bootbox.alert(Translator.t('标题不能为空!'));
			$("#quest-mail-data input[name='subject']").focus();
			return false;
		}
			
		var body = $("#quest-mail-data textarea[name='body']").val();
		if(body=='' || body==undefined){
			bootbox.alert(Translator.t('内容不能为空'));
			$("#quest-mail-data textarea[name='body']").focus();
			return false;
		}
		
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/ajax-save-quest-editting', 
			data:{quest_id:quest_id,subject:subject,body:body},
			dataType:'json',
			success:function(result){
				if(result.success){
					$.hideLoading();
					$("#quest-mail-data input[name='subject']").val(result.subject).attr('readonly',true);
					$("#quest-mail-data textarea[name='body']").html(result.body).attr('readonly',true);
					$("#edit-btn").show();
					$("#save-edit-btn").hide();
					bootbox.alert(Translator.t('修改成功'));
					return true;
				}else{
					$.hideLoading();
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert("保存失败，请联系客服");
				return false;
			}
		});
	},
	
	'CancelPendingSend' : function(ids){
		if(ids==undefined || ids==''){
			bootbox.alert("请选择要取消的任务");
			return false;
		}
		bootbox.confirm("确认要取消的任务？",function(r){
			if(r){
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/amazoncs/amazoncs/cancel-pending-send', 
					data:{quest_ids:ids},
					dataType:'json',
					success:function(result){
						$.hideLoading();
						if(result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: '取消成功',
								callback: function() {
									window.location.reload();
								}
							});
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert("后台传输出错，请联系客服");
						return false;
					}
				});
			}
		});
	},
	
	'batchCancelPendingSend' : function(){
		var ids = '';
		$(".ck:checked").each(function(){
			if(ids=='')
				ids = $(this).val();
			else
				ids += ';'+$(this).val();
			
		});
		amazoncs.QuestList.CancelPendingSend(ids);
	},
	
	'SendImmediately' : function(ids){
		if(ids==undefined || ids==''){
			bootbox.alert("请选择要立即发送的任务");
			return false;
		}
		bootbox.confirm("确认要立即发送？<br>(请确认包含的订单过往有无发过邮件，避免重复发送导致买家投诉)",function(r){
			if(r){
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/amazoncs/amazoncs/send-immediately', 
					data:{quest_ids:ids},
					dataType:'json',
					success:function(result){
						$.hideLoading();
						if(result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: '提交成功',
								callback: function() {
									window.location.reload();
								}
							});
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert("后台传输出错，请联系客服");
						return false;
					}
				});
			}
		});
	},
	
	'batchSendImmediately' : function(){
		var ids = '';
		$(".ck:checked").each(function(){
			if(ids=='')
				ids = $(this).val();
			else
				ids += ';'+$(this).val();
			
		});
		amazoncs.QuestList.SendImmediately(ids);
	},
	
	'delQuest' : function(ids){
		if(ids==undefined || ids==''){
			bootbox.alert("请选择要删除的任务记录");
			return false;
		}
		bootbox.confirm("确认要删除任务记录？(删除后不可恢复！)",function(r){
			if(r){
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/amazoncs/amazoncs/del-quest', 
					data:{quest_ids:ids},
					dataType:'json',
					success:function(result){
						$.hideLoading();
						if(result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: '操作成功',
								callback: function() {
									window.location.reload();
								}
							});
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert("后台传输出错，请联系客服");
						return false;
					}
				});
			}
		});
	},
	
	'batchDelQuest' : function(){
		var ids = '';
		$(".ck:checked").each(function(){
			if(ids=='')
				ids = $(this).val();
			else
				ids += ';'+$(this).val();
			
		});
		amazoncs.QuestList.delQuest(ids);
	},
	
	'showOrderInfo' : function(platform,order){
		$.showLoading();
		$.get( '/amazoncs/amazoncs/get-order-info?platform='+platform+'&order='+order,
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
	},
},


amazoncs.Template={
	//shop_list : {},
	shop_recommended_product_groups : {},
	addi_url_title : '',
	
	'initBtn':function(){
		
	},
	'initSelectDom' : function (){
		$("#template-data-form select[name='seller_site']").on('change',function(){
			var selected_seller_site = $(this).val();
			var strs= new Array();
			strs=selected_seller_site.split("-");
			var shop = strs[0];
			if(typeof(amazoncs.Template.shop_recommended_product_groups[shop])!=='undefined')
				var recommended_groups = amazoncs.Template.shop_recommended_product_groups[shop];
			else
				var recommended_groups = new Object();
			
			var tmp_html = '<option value="0"></option>';
			for(var i in recommended_groups){
				if(i!=="remove"){
					tmp_html += '<option value="'+i+'">'+recommended_groups[i]['group_name']+'</option>';
				}
			}
			$("#template-data-form #recommended_group").html(tmp_html);
		});
	},
	
	initFormValidateInput:function(){
		$('#name,#subject,#Product_prod_name_en').formValidation({validType:['trim','length[2,255]'],tipPosition:'right',required:true});
		//$('#contents').formValidation({validType:['trim','length[100,10000]'],tipPosition:'right',required:true});
		$('#send_after_order_created_days,#send_after_order_created_days,#order_in_howmany_days,#send_one_pre_howmany_days').formValidation({validType:['trim','length[1,2]','integer'],tipPosition:'right',required:true});
	},
		
	'createOrEditTmplate' : function(act,id){
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/create-or-edit-template?act='+act+'&id='+id, 
			//async : false,
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("编辑模板"),
					className: "create-or-edit-win", 
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-important",  
							callback: function () {
								
							}
						}, 
						Ok:{
							label: Translator.t("保存"),  
							className: "btn-success",  
							callback: function () {
								var rtn =amazoncs.Template.saveTmplate();
								if(rtn)
									window.location.reload();
								else
									return false;
							}
						},
						PreView:{
							label: Translator.t("预览"),  
							className: "btn-info",  
							callback: function () {
								amazoncs.Template.previewTmplate('editing','');
								return false;
							}
						},
					}
				});
			},
			error :function () {
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
	},
	
	'saveTmplate' : function(act,id){
		$.showLoading();
		if (! $('#template-data-form').formValidation('form_validate')){
			$.hideLoading();
			bootbox.alert(Translator.t('录入格式不正确!'));
			return false;
		}
		
		if($("#seller_site").val()==''){
			$.hideLoading();
			bootbox.alert(Translator.t('必须选择店铺!'));
			return false;
		}
		
		if( $("input[name='for_order_type[]']:checked").length<1 ){
			$.hideLoading();
			bootbox.alert(Translator.t('必须订单类型!'));
			return false;
		}
		if( $("input[name='order_in_howmany_days']").val()<7 || $("input[name='order_in_howmany_days']").val()>30 ){
			$.hideLoading();
			bootbox.alert(Translator.t('“规则将套用在过去多少天内产生的订单”超出范围!'));
			return false;
		}
		
		var addi_title_html = '';
		var mail_contents = $("textarea#contents").val();
		//var myreg = /\[\u4e70\u5bb6\u67e5\u770b\u5305\u88f9\u8ffd\u8e2a\u53ca\u5546\u54c1\u63a8\u8350\u94fe\u63a5\]/;//买家查看包裹追踪及商品推荐链接
		var myreg = /\[买家查看包裹追踪及商品推荐链接\]/;
		if(myreg.test(mail_contents)){
			addi_title_html += '<div class="addi_title_div"><label>买家查看包裹追踪及商品推荐链接  title ： </label><input type="text" name="query_url_title" value="';
			if(amazoncs.Template.addi_url_title.query_url!==undefined){
				addi_title_html += amazoncs.Template.addi_url_title.query_url
			}
			addi_title_html += '" class="iv-input url_addi_tittle"></div>';
		}
		/*
		//var myreg = /\[\u5546\u54c1\u94fe\u63a5\]/;//商品链接
		var myreg = /\[商品链接\]/;
		if(myreg.test(mail_contents)){
			addi_title_html += '<div class="addi_title_div"><label>商品链接  title ： </label><input type="text" name="product_url_title" value="';
			if(amazoncs.Template.addi_url_title.product_url!==undefined){
				addi_title_html += amazoncs.Template.addi_url_title.product_url
			}
			addi_title_html += '" class="iv-input url_addi_tittle"></div>';
		}
		//var myreg = /\[\u5e26\u56fe\u7247\u7684\u5546\u54c1\u94fe\u63a5\]/;//带图片的商品链接
		var myreg = /\[带图片的商品链接\]/;
		if(myreg.test(mail_contents)){
			addi_title_html += '<div class="addi_title_div"><label>带图片的商品链接  title ： </label><input type="text" name="product_img_and_url_title" value="';
			if(amazoncs.Template.addi_url_title.product_img_and_url!==undefined){
				addi_title_html += amazoncs.Template.addi_url_title.product_img_and_url
			}
			addi_title_html += '" class="iv-input url_addi_tittle"></div>';
		}
		*/
		//var myreg = /\[\u8054\u7cfb\u5356\u5bb6\u94fe\u63a5\]/;//联系卖家链接
		var myreg = /\[联系卖家链接\]/;
		if(myreg.test(mail_contents)){
			addi_title_html += '<div class="addi_title_div"><label>联系卖家链接  title ： </label><input type="text" name="contact_seller_url_title" value="';
			if(amazoncs.Template.addi_url_title.contact_seller!==undefined){
				addi_title_html += amazoncs.Template.addi_url_title.contact_seller
			}
			addi_title_html += '" class="iv-input url_addi_tittle"></div>';
		}
		/*
		//var myreg = /\[review\u94fe\u63a5\]/;//review链接
		var myreg = /\[review链接\]/;
		if(myreg.test(mail_contents)){
			addi_title_html += '<div class="addi_title_div"><label>review链接  title ： </label><input type="text" name="review_url_title" value="';
			if(amazoncs.Template.addi_url_title.review_url!==undefined){
				addi_title_html += amazoncs.Template.addi_url_title.review_url
			}
			addi_title_html += '" class="iv-input url_addi_tittle"></div>';
		}
		*/
		//var myreg = new RegExp("/^\[feedback\u94fe\u63a5\]$/");//feedback链接
		var myreg = /\[feedback链接\]/;
		if(myreg.test(mail_contents)){
			addi_title_html += '<div class="addi_title_div"><label>feedback链接  title ： </label><input type="text" name="feedback_url_title" value="';
			if(amazoncs.Template.addi_url_title.feedback_url!==undefined){
				addi_title_html += amazoncs.Template.addi_url_title.feedback_url
			}
			addi_title_html += '" class="iv-input url_addi_tittle"></div>';
		}
		
		if(addi_title_html!==''){
			addi_title_html = '<form id="url_addi_title_form"><div class="alert alert-info">检测到您邮件内容中加入了非商品相关的链接变量。为使内容更美观 / 更符合收件人地区语言，可以对链接添加title，使其变为超链接而不是直接显示链接地址。</div>'+addi_title_html+'</form>';
			
		}
		
		if(addi_title_html!==''){
			$.hideLoading();
			bootbox.dialog({
				title: "额外设置",
				className: "parma_url_addi_tittle", 
				message: addi_title_html,
				buttons:{
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-default",  
						callback: function () {
						}
					}, 
					Ok:{
						label: Translator.t("确定"),  
						className: "btn-success",  
						callback: function () {
							$.showLoading();
							console.log($("#url_addi_title_form").serialize());
							$.ajax({
								type: "POST",
								url:'/amazoncs/amazoncs/save-template?act='+act, 
								data:$("#template-data-form").serialize()+'&'+$("#url_addi_title_form").serialize(),
								dataType:'json',
								async : false,
								success:function(data){
									$.hideLoading();
									if(data.success){
										bootbox.alert({
											buttons: {
												ok: {
													label: 'OK',
													className: 'btn-primary'
												}
											},
											message: '保存成功',
											callback: function() {
												window.location.reload();
											}
										});
										return true;
									}else{
										alert(data.message);
										return false;
									}
										
								},
								error :function () {
									bootbox.alert("Internal Error");
									$.hideLoading();
									return false;
								}
							});
						}
					}
				}
			});
		}else{
			$.showLoading();
			$.ajax({
				type: "POST",
				url:'/amazoncs/amazoncs/save-template?act='+act, 
				data:$("#template-data-form").serialize(),
				dataType:'json',
				async : false,
				success:function(data){
					$.hideLoading();
					if(data.success){
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: '保存成功',
							callback: function() {
								window.location.reload();
							}
						});
						return true;
					}else{
						alert(data.message);
						return false;
					}
						
				},
				error :function () {
					bootbox.alert("Internal Error");
					$.hideLoading();
					return false;
				}
			});
		}
	},
	
	'previewTmplate' : function(tmplate_status,tmplate_id){
		if(tmplate_status=='saved'){
			$.showLoading();
			$.get('/amazoncs/amazoncs/preview-template?tmplate_status=saved&id='+tmplate_id, 
				function(data){
					$.hideLoading();
					bootbox.dialog({
						title: "内容预览",
						className: "pre-view-win", 
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
				}
			);
		}else if(tmplate_status=='editing'){
			$.showLoading();
			$.ajax({
				type: "POST",
				url:'/amazoncs/amazoncs/preview-template?tmplate_status=editing', 
				data:$("#template-data-form").serialize(),
				success:function(data){
					$.hideLoading();
					bootbox.dialog({
						title: "内容预览",
						className: "pre-view-win", 
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
				},
				error :function () {
					bootbox.alert("Internal Error");
					$.hideLoading();
					return false;
				}
			});
		}else{
			bootbox.alert("预览失败！请于编辑时 或 列表状态下进行预览");
			return false;
		}
		
	},
	
	'openSysTemplatesWin' : function(){
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/sys-template', 
			data:{},
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					title: "选择模板",
					className: "sys-templates-win", 
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
				return true;
			},
			error :function () {
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
		
	},
	
	'replaceTmeplateContentBySysTemplateContent' : function(id){
		var subject_id = '#sys_template_'+id+'_subject';
		var contents_id = '#sys_template_'+id+'_contents';
		subject = ($(subject_id).html()===undefined)?'':$(subject_id).html();
		contents = ($(contents_id).html()===undefined)?'':$(contents_id).html();
		
		$("input#subject[name='subject']").val(subject);
		$("textarea#contents[name='contents']").html(contents);
		
		$('.sys-templates-win').modal('hide');
		$('.sys-templates-win').on('hidden.bs.modal', '.modal', function(event) {
			$(this).removeData('bs.modal');
		});
		return true;
	},
	
	'delTemplate' : function(id){
		bootbox.confirm(
			"确定删除模板？",
			function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type: "POST",
						url:'/amazoncs/amazoncs/del-template?id='+id, 
						data:{},
						dataType:'json',
						success:function(data){
							$.hideLoading();
							if(data.success){
								bootbox.alert({
									buttons: {
										ok: {
											label: 'OK',
											className: 'btn-primary'
										}
									},
									message: '删除成功',
									callback: function() {
										window.location.reload();
									}
								});
								return true;
							}else{
								alert(data.message);
								return false;
							}
						},
						error :function () {
							bootbox.alert("Internal Error");
							$.hideLoading();
							return false;
						}
					});
				}
				else{
					$('.bootbox-confirm').modal('hide');
					$('.bootbox-confirm').on('hidden.bs.modal', '.modal', function(event) {
						$(this).removeData('bs.modal');
					});
					return false;
				}
			}
		);
	},
	
	'addContentVariance' : function(obj){
		textObj = $('#contents');
		textFeildValue = $(obj).data('value');
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
		
		//
		var contents = $("#contents").val();
		var myreg = /\[买家查看包裹追踪及商品推荐链接\]/;
		if(myreg.test(contents)){
			$("#recommended_product_setting").css("display","");
		}else{
			$("#recommended_group option").each(function(){
				$("#recommended_group").find("option[value='0']").attr("selected",true); 
			});
			$("#recommended_group").attr("value",'0');
			$("#recommended_product_setting").css("display","none");
		}
	},
	
	'generateMailQuest' : function(template_ids){
		bootbox.confirm(
			"确定生成发送任务？",
			function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type: "POST",
						url:'/amazoncs/amazoncs/generate-quest', 
						data:{template_ids:template_ids},
						//dataType:'json',
						success:function(data){
							$.hideLoading();
							bootbox.dialog({
								title: "生成任务",
								className: "generate-win", 
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
						},
						error :function () {
							bootbox.alert("Internal Error");
							$.hideLoading();
							return false;
						}
					});
				}
				else{
					$('.bootbox-confirm').modal('hide');
					$('.bootbox-confirm').on('hidden.bs.modal', '.modal', function(event) {
						$(this).removeData('bs.modal');
					});
					return false;
				}
			}
		);
	},
	'generateMailQuestConfirmed' : function(template_ids){		
		$.showLoading();
		$.ajax({
			type: "POST",
			url:'/amazoncs/amazoncs/generate-quest', 
			data:{template_ids:template_ids,confirmed:true},
			//dataType:'json',
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					title: "生成任务",
					className: "generate-win", 
					message: data,
					buttons:{
						Cancel: {
							label: Translator.t("确认"),  
							className: "btn-default",  
							callback: function () { 
								window.location.reload();
							}
						}, 
					}
				});
			},
			error :function () {
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
	},
	
	'viewQuestGenerateLog' : function(tmplate_id){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/amazoncs/amazoncs/template-quest-generate-log?tmplate_id='+tmplate_id, 
			data:{},
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					title: "查看上次任务生成日志",
					className: "generate-log-win", 
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
			},
			error :function () {
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
	},
};

function sleep(numberMillis){
	var now = new Date();
	var exitTime = now.getTime() + numberMillis;
	while(true){
		now = new Date();
		if (now.getTime() > exitTime)
		return;
	}
} 