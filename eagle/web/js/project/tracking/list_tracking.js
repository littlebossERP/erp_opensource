
/**
 +------------------------------------------------------------------------------
 *全部查询界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof ListTracking === 'undefined')  ListTracking = new Object();
ListTracking = {
	currentBackgroundRequestCount : 0 ,
	MaxBackgroundRequestCount : 50,
	TagList:"",
	TrackingTagList:"",
	TagClassList:"",
	TagClassHtml:"",
	init:function(){
		$.initQtip();
		ListTracking.initNewAccountBinding();
		$( "#startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#enddate" ).datepicker({dateFormat:"yy-mm-dd"});
		
		$("#chk_all").click(function(){
			$("[name='chk_tracking_record']:checkbox").prop('checked', this.checked);  
		});
		
		$('select[name=to_nations],select[name=select_parcel_classification] ,select[name=is_has_tag] ,select[name=sellerid] ,select[name=ship_by] , select[name=is_send] ,select[name=is_handled] , select[name=is_remark]').change(function(){
			$("form:first").submit();
		});
		
		$('tr').has('.egicon-flag-gray').on('mouseover',function(){
			if ($(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
			$(this).find('.btn_tag_qtip').removeClass('div_space_toggle');
		});
		
		$('tr').has('.egicon-flag-gray').on('mouseleave',function(){
			if (! $(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
				$(this).find('.btn_tag_qtip').addClass('div_space_toggle');
		});
		
		/* 触发table header 的select */
		$("[data-selname] > li").click(function(){
			var selname = $(this).parent().attr('data-selname');
			$("select[name="+selname+"]").val($("[name="+selname+"]").find("option").eq($(this).index()).val())
			$("select[name="+selname+"]").change();
		});
	},
	
	batchUpdateUnshipParcel:function(){
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/batch-update-unship-parcel', 
			data: {},
			success: function (data) {
				if (data.success == false){
					bootbox.alert(data.message);
				}else{
					if (data.message != 'undefined'){
						bootbox.alert(data.message);
					}
				}
				return true;
			},
			error :function () {
				
				return false;
			}
		});
	},
	
	ShowDetailView:function(obj){
		
		/*
		if ($(obj).parents('tr').next('tr').find('.row').css('display') == "none"){
			//$(obj).html(Translator.t('收起'));
			if ($(obj).children('span').has('glyphicon-eye-open')){
				$(obj).children('span').removeClass('glyphicon-eye-open');
				$(obj).children('span').addClass('glyphicon-eye-close');
			}
				
			//already expanded
			$(obj).parents('tr').css('background','#eaf8ff');
			$(obj).parents('tr').next('tr').css('background','#eaf8ff');
			}
		else{
			//$(obj).html(Translator.t('详情'));
			if ($(obj).children('span').has('glyphicon-eye-close')){
				$(obj).children('span').removeClass('glyphicon-eye-close');
				$(obj).children('span').addClass('glyphicon-eye-open');
			}
			//not expanded
			$(obj).parents('tr').next('tr').css('background','white');
			$(obj).parents('tr').css('background','');
		}
		
		$(obj).parents('tr').next('tr').find('.row').slideToggle("normal",function(){
			
		});
		*/
	},
	
	translateContent:function(track_no , lang , obj){
		var $btn = $(obj).button('loading');
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/translate_content', 
			data: {track_no : track_no , lang : lang},
			success: function (data) {
				if (! data.success){
					$btn.button('reset')
					bootbox.alert(data.message);
					return false;
				}
				
				if (data.TbHtml){
					if (data.TbHtml){
						for(var tmp_track_no in data.TbHtml ){
							var track_id = $('tr[track_no='+tmp_track_no+']').data('track-id');
							$('#div_more_info_'+track_id).html(data.TbHtml[tmp_track_no]);
						}
					}
				}
				$btn.button('reset')
				return true;
			},
			error :function () {
				$btn.button('reset')
				return false;
			}
		});
	},
	
	UpdateTrackRequest:function(track_no , obj){
		$(obj).css('display','none');
		$(obj).parents("tr").children('td[data-status]').children('strong').text(Translator.t("查询等候中"))
		//$(obj).parents("tr").children('td').has('.status_label').children('span').attr('class','label status_label label-default');
		//$(obj).parents("tr").children('td').has('.status_label').children('span').text(Translator.t("查询中"))
		//$(obj).parents("tr").find('td span').css('display','none')
		//$(obj).parents("tr").find('td').has('span').addClass('bg_loading');
		var lang = $('#tsl_'+track_no).attr('data-translate-code')
		var track_id = $(obj).parents("tr").data('track-id');
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/generate_request_for_tracking', 
			data: {track_no : track_no , lang : lang , parcel_classification : $('#parcel_classification').val() , platform :$('#platform').val() },
			success: function (data) {
				if (! data.success){
					bootbox.alert(data.message);
				}
				if (data.TbHtml){
					if (data.TbHtml){
						for(var tmp_track_no in data.TbHtml ){
							$('#div_more_info_'+track_id).html(data.TbHtml[tmp_track_no]);
						}
					}
				}
				if (data.TrHtml){
					for(var tmp_track_no in data.TrHtml ){
//						var thischeck = $('#tr_info_'+tmp_track_no+' [name=chk_tracking_record]').prop('checked');
//						$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
//						$('#tr_info_'+tmp_track_no+' [name=chk_tracking_record]').prop('checked',thischeck );
						var thischeck = $('tr[data-track-id="'+track_id+'"] [name=chk_tracking_record]').prop('checked');
						$('tr[data-track-id="'+track_id+'"]').html(data.TrHtml[tmp_track_no]);
						$('tr[data-track-id="'+track_id+'"] [name=chk_tracking_record]').prop('checked',thischeck );
					}
				}
				TrackingTag.initAllQtipEntryBtn(track_id);
				return true;
			},
			error :function () {
				return false;
			}
		});
		
	},
	
	batchUpdateTrackRequest:function(){
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_no = $(this).parents('tr').attr('track_no');
				selected_tracking_no_list.push(track_no);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/batch_generate_request', 
			data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
			success: function (result) {
				$.hideLoading();
				if (result.message!=""){
					var msg = result.message;
				}else{
					var msg = Translator.t('操作成功，点击确认刷新页面');
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
	},
	
	initNewAccountBinding:function(){
		if ($('#div_show_progress_bar').length>0){
			$.ajax({
				type: "GET",
				dataType: 'json',
				url:'/tracking/tracking/get-new-account-init-process', 
				data:{platform:$('#platform').val()},
				success: function (data) {
					if (data.code == '200'){
						ListTracking.updateProgressBar(data.data);
						$('.progress_p').html(data.barMessage);
					}
					
					if (data.visible == false){
						$('.div_progress').slideToggle('slow', function(){
							$(this).remove();
						})
					}
				},
				error :function () {
					return false;
				}
			});
		}
	},
	
	updateProgressBar:function(data , callback){
		if ($('#div_show_progress_bar .progress').length>0){
			var addProgressBarHtml = "";
			for(var i=0 ; i< data.length ; i++){
				if ($('#div_show_progress_bar .progress').find('.progress-bar-'+data[i].type).length == 0){
					addProgressBarHtml += '<div class="progress-bar progress-bar-'+data[i].type+' progress-bar-striped active" style="width: 0%">'+
								'<span></span>'+
							'</div>';
				}
				//$(".progress-bar-"+data[i].type).css('width',data[i].width+"%");
				//$(".progress-bar-"+data[i].type+' span').html(data[i].count);
			}
			
			if (addProgressBarHtml != ""){
				$('#div_show_progress_bar .progress').append(addProgressBarHtml);
			}
			
			for(var i=0 ; i< data.length ; i++){
				$(".progress-bar-"+data[i].type).css('width',data[i].width+"%");
				$(".progress-bar-"+data[i].type+' span').html(data[i].count);
			}
			
			
		}
		
	},
	
	
	
	AutoCheckTrackingProcess:function(){
		ListTracking.currentBackgroundRequestCount += 1 ;
		if (ListTracking.MaxBackgroundRequestCount < ListTracking.currentBackgroundRequestCount) return;
		
		var track_no_list = new Array();
		var lang_list = new Object();
		$('tr[track_no]').each(function(){
			if ($.trim($(this).children('td[data-status]').children('strong').text()) ==  Translator.t("查询等候中") ){
				if ($(this).attr('track_no') != undefined && $(this).attr('track_no')!='' && $(this).attr('track_no')!=null ){
					var track_no = $(this).attr('track_no');
					track_no_list.push(track_no);
					lang_list[track_no] = $('#tsl_'+track_no).attr('data-translate-code')
				}
				
			} 
			
		});
		
		if (track_no_list.length==0){
			return false;
		}
		
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/background_update_list_tracking_info', 
			data: {track_no_list : $.toJSON(track_no_list) , lang: $.toJSON(lang_list) , platform : $('#platform').val()},
			success: function (data) {
				if (data.success){
					if (data.TbHtml){
						for(var tmp_track_no in data.TbHtml ){
							var track_id = $('tr[track_no='+tmp_track_no+']').data('track-id');
							$('#div_more_info_'+track_id).html(data.TbHtml[tmp_track_no]);
						}
					}
					
					if (data.TrHtml){
						for(var track_id in data.TrHtml ){
//							var track_id = $('tr[track_no='+tmp_track_no+']').data('track-id');
//							var thischeck = $('#tr_info_'+tmp_track_no+' [name=chk_tracking_record]').prop('checked');
//							$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
							var thischeck = $('tr[data-track-id="'+track_id+'"] [name=chk_tracking_record]').prop('checked');
							$('tr[data-track-id="'+track_id+'"]').html(data.TrHtml [track_id]);
							TrackingTag.initAllQtipEntryBtn(track_id);
							$('tr[data-track-id="'+track_id+'"] [name=chk_tracking_record]').prop('checked',thischeck );
//							$('#tr_info_'+tmp_track_no+' [name=chk_tracking_record]').prop('checked',thischeck );
						}
					}
					
					
				}
			},
			error :function () {
				return false;
			}
		});
	},
	
	SearchTracking:function(ParcelType){
		$("#parcel_classification").val(ParcelType);
		$("form:first").submit();
		
	},
	
	ShowOrderInfo:function(track_no){
		
		$.showLoading();
		$.get( '/tracking/tracking/get_order_info?track_no='+track_no,
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
	
	MarkHandled:function(){
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_no_list.push(track_id);
			}
		});
		
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/mark-tracking-handled', 
			data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
			success: function (result) {
				if (result.TrHtml){
					for(var track_id in result.TrHtml ){
						$('tr[data-track-id='+track_id+']').html(result.TrHtml[track_id]);
						TrackingTag.initAllQtipEntryBtn(track_id);
					}
				}
				return true;
			},
			error :function () {
				return false;
			}
		});
		
		//bootbox.alert($.toJSON(selected_tracking_record_id));
	},
	
	MarkOneHndled:function(track_id){
		
		if (track_id == ""){
			bootbox.alert(Translator.t('请选择物流号'));
			return;
		}
		
		var selected_tracking_no_list = new Array();
		selected_tracking_no_list.push(track_id);
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/mark-tracking-handled', 
			data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
			success: function (result) {
				if (result.TrHtml){
					for(var track_id in result.TrHtml ){
						$('tr[data-track-id='+track_id+']'+' [name=chk_tracking_record]').prop('checked',thischeck );
						$('tr[data-track-id='+track_id+']').html(result.TrHtml[track_id]);
						TrackingTag.initAllQtipEntryBtn(track_id);
						$('tr[data-track-id='+track_id+']'+' [name=chk_tracking_record]').prop('checked',thischeck );
					}
				}
				
				/*
				if (result.success){
					bootbox.alert(result.message);
				}else{
					bootbox.alert(result.message);
				}
				*/
				return true;
			},
			error :function () {
				return false;
			}
		});
	},
	
	exportExcel:function(){
		
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				selected_tracking_no_list.push(this.value);
			}
		});
		
		if (selected_tracking_no_list.length ==0){
			bootbox.alert(Translator.t("请选择需要导出的物流号?"));
			return;
		}
		var parcel_classification = $("#parcel_classification").val();
		if(parcel_classification=='no_info_parcel')
			url = '/tracking/tracking/export-tracking-excel-autosubmit?status=no_info';
		else
			url = '/tracking/tracking/export-tracking-excel-autosubmit';
		
		window.open(url);
	} , 
	exportReportExcel:function(){
		if ($.trim($( "#startdate" ).val())==""){
			bootbox.alert(Translator.t('请选择起始日期'));
		}else{
			var url = "/tracking/tracking/export-tracking-report-excel?startdate="+$( "#startdate" ).val();
			window.open(url);
		}
	} , 
	DelTrack:function(track_no){
		bootbox.confirm(Translator.t("是否删除"+track_no),function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/tracking/tracking/delete-tracking',
					data: {track_no:track_no} , 
					success: function (result) {
						if (result.success){
							window.location.reload();
							
						}else{
							bootbox.alert(result.message);
							$.hideLoading();
						}
					},
					error :function () {
						bootbox.alert("Internal Error");
						$.hideLoading();
						return false;
					}
				});
				
			});
	},
	 
	BatchDelTrack:function(){
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				selected_tracking_no_list.push(this.value);
			}
		});
		
		if (selected_tracking_no_list.length ==0){
			bootbox.alert(Translator.t("请选择需要删除的物流号?"));
			return;
		}
		
		bootbox.confirm(Translator.t("是否删除选中的?"),function(r){
			if (! r) return;
			$.showLoading();
				$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/delete-tracking', 
				data: {track_no : selected_tracking_no_list , is_decode: true},
				success: function (result) {
					if (result.success){
						window.location.reload();
					}else{
						bootbox.alert(result.message);
						$.hideLoading();
					}
					return true;
				},
				error :function () {
					bootbox.alert("Internal Error");
					$.hideLoading();
					return false;
				}
			});

		});
		
		
	},
	
	//overdue
	showTagBox:function(tracking_id,track_no){
		$.showLoading();
		$.get( '/tracking/tracking/get-tracking-tags?tracking_id='+tracking_id,
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("物流号:")+track_no,
					className: "order_info", 
					message: data,
					buttons:{
						Ok: {  
							label: Translator.t("保存"),  
							className: "btn-primary",  
							callback: function () { 
								return ListTracking.saveTag(tracking_id);
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
		});
		
	},
	
	showRemarkBox:function(track_no){
		$.showLoading();
		$.get( '/tracking/tracking/get-tracking-remark?track_no='+track_no,
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("物流号:")+track_no,
					className: "order_info", 
					message: data,
					buttons:{
						Ok: {  
							label: Translator.t("添加"),  
							className: "btn-success btn-sm",  
							callback: function () { 
								
								if ($('#tracking_remark').val() == "" ){
									bootbox.alert(Translator.t('请填写备注!'));
									return false;
								}
								result = ListTracking.AppendRemark(track_no , $('#tracking_add_remark').val());
								
							}
						}, 
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default btn-sm",  
							callback: function () {  
							}
						}, 
					}
				});	
		});
		
	},
	
	AppendRemark:function(track_no , remark){
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/append-remark', 
				data: {track_no : track_no , remark :remark },
				success: function (result) {
					//bootbox.alert(result.message);
					if (result.sectionHtml){
						$('tr[track_no='+track_no+']').next('tr').find('section').html(result.sectionHtml);
						$('tr[track_no='+track_no+']').find('.div_space_toggle').html(result.sectionHtml);
					}
					
					if (result.TrHtml){
						for(var track_id in result.TrHtml ){
							var thischeck = $('tr[data-track-id="'+track_id+'"] [name=chk_tracking_record]').prop('checked');
							$('tr[data-track-id="'+track_id+'"]').html(data.TrHtm [track_id]);
//							var thischeck = $('#tr_info_'+tmp_track_no+' [name=chk_tracking_record]').prop('checked');
//							$('#tr_info_'+tmp_track_no).html(result.TrHtml[tmp_track_no]);
							
//							var track_id = $('tr[track_no='+tmp_track_no+']').data('track-id');
							TrackingTag.initAllQtipEntryBtn(track_id);
//							$('#tr_info_'+tmp_track_no+' [name=chk_tracking_record]').prop('checked',thischeck );
							$('tr[data-track-id="'+track_id+'"] [name=chk_tracking_record]').prop('checked',thischeck );
						}
					}
					return result;
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	/*
	batchSendLetter:function(){
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				selected_tracking_no_list.push(this.value);
			}
		});
		
		if (selected_tracking_no_list.length ==0){
			bootbox.alert(Translator.t("请选择需要发信的物流号?"));
			return;
		}
		
		window.location.href = '/tracking/tracking/station-letter?is_decode=true&track_no='+selected_tracking_no_list;
	},
	*/
	
	delete_form_group:function(obj){
		$(obj).parents(".form-group").remove();
	},
	
	refreshTrackingTr : function(id){
		$.ajax({
			type: "GET",
			url:'/tracking/tracking/refresh-one-tracking?id='+id,
			dataType:'json',
			success : function (rtn) {
				if (rtn.TrHtml) {
					for (var track_id in rtn.TrHtml) {
						$('tr[data-track-id=' + track_id + ']').html(rtn.TrHtml[track_id]);
						TrackingTag.initAllQtipEntryBtn(track_id);
					}
				}
				return true;
			},
			error : function () {
				return false;
			}
		});
	},
	
	batchMarkCompleted:function(){
		
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_no_list.push(track_id);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/mark-tracking-completed', 
			data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
			success: function (result) {
				$.hideLoading();
				if (result.message!=""){
					var msg = result.message;
				}else{
					var msg = Translator.t('选中的物流号已经标记为已完成');
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
	},
	
	batchMarkShipping:function(){
		
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_no_list.push(track_id);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/mark-tracking-shipping', 
			data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
			success: function (result) {
				$.hideLoading();
				if (result.message!=""){
					var msg = result.message;
				}else{
					var msg = Translator.t('选中的物流号已经标记为运输途中');
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
	},
	
	batchMarkIgnore:function(){
				
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_no_list.push(track_id);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		bootbox.confirm(Translator.t("是否确认忽略？"),function(r){
			if(!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/mark-tracking-ignore', 
				data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
				success: function (result) {
					$.hideLoading();
					if (result.message!=""){
						var msg = result.message;
					}else{
						var msg = Translator.t('选中的物流号已经标记为忽略(不再查询)');
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
	
	batchMarkIsSent:function(){
				
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_no_list.push(track_id);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		bootbox.confirm(Translator.t("是否确认标记为已发送当前种类提醒？"),function(r){
			if(!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/mark-tracking-is-sent', 
				data: {tracking_no_list : $.toJSON(selected_tracking_no_list),pos : $("input[name='pos']").val()},
				success: function (result) {
					$.hideLoading();
					if (result.message!=""){
						var msg = result.message;
					}else{
						var msg = Translator.t('选中的物流号已经标记为已发送当前种类提醒');
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
	
	ignoreShipType:function(ship_by){
		bootbox.confirm(Translator.t("是否确认将该物流服务方式设置为自动忽略查询？"),function(r){
			if(!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/ignore-ship-type', 
				data: {ship_by : ship_by},
				success: function (result) {
					$.hideLoading();
					if (result.message!=""){
						var msg = result.message;
					}else{
						var msg = Translator.t('设置成功');
					}
					var e = $.alert(msg,'success');
					e.then(function(){
						if (result.success){
							location.reload();
						}
					});
					return true;
				},
				error : function(){
					$.alert(Translator.t('小老板页面错误，请联系客服'),'error');
				}
			});
		});
	},
	
	reActiveShipType:function(ship_by){
		bootbox.confirm(Translator.t("是取消该物流服务方式的自动忽略查询设置(不再自动忽略)？"),function(r){
			if(!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/re-active-ship-type', 
				data: {ship_by : ship_by},
				success: function (result) {
					$.hideLoading();
					if (result.message!=""){
						var msg = result.message;
					}else{
						var msg = Translator.t('设置成功');
					}
					var e = $.alert(msg,'success');
					e.then(function(){
						if (result.success){
							location.reload();
						}
					});
					return true;
				},
				error : function(){
					$.alert(Translator.t('小老板页面错误，请联系客服'),'error');
				}
			});
		});
	},
	
	
	setCarrierType : function(track_ids,act){
		$.showLoading();
		$.get( '/tracking/tracking/set-carrier-type-win?track_ids='+track_ids+'&act='+act,
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("手动设置查询使用的物流渠道"),
					className: "set-carrier-type-win", 
					message: data,
					buttons:{
						Ok: {  
							label: Translator.t("设置"),  
							className: "btn-success btn-sm",  
							callback: function () { 
								$.showLoading();
								$.ajax({
									type: "POST",
									dataType: 'json',
									url:'/tracking/tracking/save-tracking-carrier-type', 
									data: $('#set-carrier-type-data').serialize(),
									success: function (result) {
										$.hideLoading();
										if (result.message!=""){
											var msg = result.message;
										}else{
											var msg = Translator.t('设置成功');
										}
										var e = $.alert(msg,'success');
										e.then(function(){
											if (result.success){
												location.reload();
											}
										});
										return true;
									},
									error : function(){
										$.alert(Translator.t('小老板页面错误，请联系客服'),'error');
									}
								});
							}
						}, 
						Cancel: {  
							label: Translator.t("取消"),  
							className: "btn-default btn-sm",  
							callback: function () {  
							}
						}, 
					}
				});	
		});
	},
	batchSetCarrierType : function(){	
		var selected_tracking_id_list = '';
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_id_list += track_id+',';
			}
		});
		
		if (selected_tracking_id_list==''){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		var act = 'batch';
		ListTracking.setCarrierType(selected_tracking_id_list,act);
	},
	
	SetIgnoreCarriers : function(){
		$.showLoading();
		$.get( '/tracking/tracking/get-ignore-carriers-win',
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("设置不查询的物流商"),
					className: "set-ignore-carriers-win", 
					message: data,
					buttons:{
						Ok: {  
							label: Translator.t("设置"),  
							className: "btn-success btn-sm",  
							callback: function () {
								var checkedList = '';
								$("#set-ignore-carriers ul li input[type='checkbox']:checked").each(function(){
									if(checkedList=='')
										checkedList=$(this).val();
									else
										checkedList += '<br>'+$(this).val();
								});
								if(checkedList!==''){
									var fromData = $('#set-ignore-carriers').serialize();
									bootbox.confirm(
										Translator.t("是否自动忽略查询以下物流商的物流号？<br>"+checkedList),
										function(r){
											if(!r)
												return;
											$.showLoading();
											$.ajax({
												type: "POST",
												dataType: 'json',
												url:'/tracking/tracking/set-ignore-carriers', 
												data: fromData,
												success: function (result) {
													$.hideLoading();
													if (result.message!=""){
														var msg = result.message;
													}else{
														var msg = Translator.t('设置成功');
													}
													var e = $.alert(msg,'success');
													e.then(function(){
														if (result.success){
															location.reload();
														}
													});
													return true;
												},
												error : function(){
													$.alert(Translator.t('小老板页面错误，请联系客服'),'error');
												}
											});
										}
									);
								}else{
									$.showLoading();
									$.ajax({
										type: "POST",
										dataType: 'json',
										url:'/tracking/tracking/set-ignore-carriers', 
										data: $('#set-ignore-carriers').serialize(),
										success: function (result) {
											$.hideLoading();
											if (result.message!=""){
												var msg = result.message;
											}else{
												var msg = Translator.t('设置成功');
											}
											var e = $.alert(msg,'success');
											e.then(function(){
												if (result.success){
													location.reload();
												}
											});
											return true;
										},
										error : function(){
											$.alert(Translator.t('小老板页面错误，请联系客服'),'error');
										}
									});
								}
							}
						}, 
						Cancel: {  
							label: Translator.t("取消"),  
							className: "btn-default btn-sm",  
							callback: function () {  
							}
						}, 
					}
				});	
		});
	},
	
	batchQuotaInsufficeientReSearch :function (){
		var selected_tracking_no_list = new Array();
		
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_no = $(this).parents('tr').attr('track_no');
				selected_tracking_no_list.push(track_no);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		bootbox.confirm(Translator.t("是否重新查询？(将会消耗配额)"),function(r){
			if(!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/quota-insufficeient-re-search', 
				data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
				success: function (result) {
					$.hideLoading();
					if (result.message!=""){
						var msg = result.message;
					}else{
						var msg = Translator.t('设置成功');
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
	
	showQuotaInfo:function(){
		$.showLoading();
		var url='/tracking/tracking/view-quota';
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "view-quota",
					title: Translator.t("用户物流跟踪助手配额信息"),
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
	
	ignoredReSearch:function(){
		var selected_tracking_no_list = new Array();
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				var track_id = $(this).parents('tr').data('track-id');
				selected_tracking_no_list.push(track_id);
			}
		});
		
		if (selected_tracking_no_list.length==0){
			$.alert(Translator.t('请选择物流号！'),'danger');
			return;
		}
		bootbox.confirm(Translator.t("选定的物流号将重新回到待查询状态，同时此操作会将<span style='color:red'>所包含的物流商</span>设置为<span style='color:red'>非自动忽略</span>(如果之前用户设置了自动忽略的)。<br>并且将<span style='color:red'>有可能消耗查询配额</span>!<br>是否确认将已忽略的物流号重新查询？"),function(r){
			if(!r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/ignored-re-search', 
				data: {tracking_no_list : $.toJSON(selected_tracking_no_list)},
				success: function (result) {
					$.hideLoading();
					if (result.message!=""){
						var msg = result.message;
					}else{
						var msg = Translator.t('操作成功');
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
	
	translateEventsToZh:function(obj,track_no,track_id){
		if($(obj).hasClass("translate-btn-checked"))
			return true;
		var to_lang = $(obj).attr("lang");
		$parent = $(obj).parents('#div_more_info');
		
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
	}
};

$(function(){
	ListTracking.init();
	
});