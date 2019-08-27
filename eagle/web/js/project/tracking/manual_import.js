/**
 +------------------------------------------------------------------------------
 *Tracking的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		yzq <zengqiang.yang@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof manual_import === 'undefined')  manual_import = new Object();
manual_import.list={
	init: function() {
		$.initQtip();
		//$("#tracking_result_message p").html($('#query_loading_message').val());
		// Boostrap modal data destroy , for 
		$('body').on('hidden.bs.modal', '.modal', function (event) {
			$(this).removeData('bs.modal');
		});
		
		$('#btn_query').click(function(){
			 $('#div_progress').removeClass('div_space_toggle');
			if (/\，|,/.test($("#txt_search_data").val())){
				var trackinglist_type = "string";
				var trackinglist = $("#txt_search_data").val();
			}else{
				var trackinglist_type = "json";
				TextImport.parseTextToTable($("#txt_search_data").val());
			
				if (TextImport.ConvertedArray.length ==0){
					bootbox.alert( $('#query_empty_message').val());
					return false;
				}else{
					var trackinglist = $.toJSON(TextImport.ConvertedArray);
					
				}
			}
			if ($('textarea').css('height') == '350px')
				$('textarea').animate({height:"-=250"},600);
			
			$('#div_list_tracking_result').html('');
			manual_import.list.clear_progress_bar();
			manual_import.list.show_checking_tips();
			//$("#tracking_result_message").toggle();
			$(this).prop('disabled','disabled');
			var btnobj = $(this)
			//var $btn = $(this).button('loading');
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/query_tracking_process', 
				data: {trackinglist : trackinglist , trackinglist_type : trackinglist_type},
				success: function (data) {
					//$btn.button('reset');
					btnobj.prop('disabled','');
					if (! data.success){
						bootbox.alert(data.message);
						return false;
					}
					//$("#tracking_result_message").toggle();
					if (data.TbHtml){
						$('#div_list_tracking_result').html(data.TbHtml);
						manual_import.list.updateProgressMessage();
					}
					manual_import.list.updateProgressBar(manual_import.list.show_checking_tips());
					$.initQtip();
					
					$('.btn-qty-memo').each(function(){
						//var track_id = $(this).data('track-id');
						TrackingTag.initMemoQtipEntryBtn(this);
					});
				},
				error :function () {
					//$btn.button('reset');
					return false;
				}
			});
		});
		
		$('#btn_clear').click(function(){
			$('#btn_query').button('reset');
			if ($('textarea').css('height') != '350px')
				$('textarea').animate({height:"+=250"},600);
			
			$("#txt_search_data").val('');
			$('#div_list_tracking_result').html('');
			manual_import.list.clear_progress_bar();
			
			$('.progress_p').html('0/0');
			
			if ($("#tracking_result_message").css('display') == 'block')
				$("#tracking_result_message").toggle("slow");
		});
		
		$('#btn_upload').click(function(){
			import_file.ShowExcelImport();
			
			$('#btn-import-file').unbind( "click" );
			$('#btn-import-file').bind("click",function(){
				import_file.importExcelFile(function(result){
					$('#import_excel_file').modal('hide');
					var tmp_msg = Translator.t('可以在本页面看到前100条记录，或者到“查询记录”-》“全部记录”查看所有的物流跟踪结果');
					bootbox.alert(result.message+'<br>'+tmp_msg);
					if (result.batch_no){
						$.ajax({
							type: "POST",
							dataType: 'json',
							url:'/tracking/tracking/query_tracking_process', 
							data: {batch_no : result.batch_no},
							success: function (data) {
								if (! data.success){
									bootbox.alert(data.message);
									return false;
								}
								//$("#tracking_result_message").toggle();
								if (data.TbHtml){
									$('#div_list_tracking_result').html(data.TbHtml);
									manual_import.list.updateProgressMessage();
								}
								manual_import.list.updateProgressBar(manual_import.list.show_checking_tips());
							},
							error :function () {
								return false;
							}
						});
					}
				});
			});
		});
		
		$('#btn_export').click(function(){
			if ($('#query_track_no_list').val() == "" || $('#query_track_no_list').val() ==undefined){
				bootbox.alert( $('#query_empty_message').val());
				return false;
			}
				
			url = "/tracking/tracking/export_manual_excel?track_no_list="+$('#query_track_no_list').val();
			window.open(url);
		});
		/*
		var watermarkHtml = Translator.t('物流单号之间请用 换行 或者 逗号 分隔,例如，“1501291533CZVI4，1501291533CZVI5”，每次可以查询最多100数量。（Excel 上传不限数量）');
		watermarkHtml += "<br>";
		watermarkHtml +=Translator.t('也可以通过复制excel多个列，然后粘贴，同时录入订单号和物流号，系统会记录订单号和物流号对应关系。');
		watermarkHtml += "<br>";
		watermarkHtml += "<br>";
		watermarkHtml +=Translator.t('所有录入过的单号，将会由系统自动定期复查，自动更新物流包裹状态，请在 “查询记录” 栏目中查看所有录入过的物流号最新状态。');
		watermarkHtml += "<br>";
		watermarkHtml += "<br>";
		watermarkHtml +=Translator.t('如下例子：');
		watermarkHtml += "<br>";
		watermarkHtml +='4PX &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896662794CN &nbsp&nbsp&nbsp&nbsp 1908991191CY60U &nbsp&nbsp&nbsp&nbsp 12.2535';
		watermarkHtml += "<br>";
		watermarkHtml +='4PX &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896663166CN &nbsp&nbsp&nbsp&nbsp 1908998921CYFU7 &nbsp&nbsp&nbsp&nbsp 16.4165';
		watermarkHtml += "<br>";
		watermarkHtml +='DHL &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896663260CN &nbsp&nbsp&nbsp&nbsp 1908991994D2999 &nbsp&nbsp&nbsp&nbsp 20.1270';
		watermarkHtml += "<br>";
		watermarkHtml +='DHL &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896663267CN &nbsp&nbsp&nbsp&nbsp 1908998939CYJ9G &nbsp&nbsp&nbsp&nbsp 14.1540'
		watermarkHtml += "<br>";
		watermarkHtml +='DHL &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896663300CN &nbsp&nbsp&nbsp&nbsp 1908991130CY2KI &nbsp&nbsp&nbsp&nbsp 14.6970'
		watermarkHtml += "<br>";
		watermarkHtml +=Translator.t('燕文')+' &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896663327CN &nbsp&nbsp&nbsp&nbsp 1908991720D0Q2B &nbsp&nbsp&nbsp&nbsp 16.2355';
		watermarkHtml += "<br>";
		watermarkHtml +=Translator.t('燕文')+' &nbsp&nbsp&nbsp&nbsp 2015-02-02 &nbsp&nbsp&nbsp&nbsp RG896663344CN &nbsp&nbsp&nbsp&nbsp 1908962234CVL0R &nbsp&nbsp&nbsp&nbsp 17.8645';
		*/
		
		var watermarkHtml =  '<p>在此录入订单或物流号进行查询</p>';
		watermarkHtml +="<p>① 请用 换行 或者 逗号 分隔，例如：“1501291533CZVI4，1501291533CZVI5”</p>";
		watermarkHtml +="<p>	每次最多查询 50 单(VIP则没有限制)。</p>";
		watermarkHtml +="每天积累手动录入或者excel上传订单总数请勿超过50个。";
		watermarkHtml +="如果每天订单数量过多，请通过绑定账号，让系统自动拉取订单的物流信息进行自动化查询";
		watermarkHtml +="<p>② 也可以通过复制excel多个列，然后粘贴，同时录入订单号和物流号，系统会记录订单号和物流号对应关系。</p>";
		watermarkHtml +="<p>例如:";
		watermarkHtml +="4PX      2015-02-02      RG896662794CN      1908991191CY60U     12.2535</p>";
		watermarkHtml +="<p>DHL     2015-02-02      RG896663260CN      1908991994D2999       20.1270</p>";
		watermarkHtml +="<p>燕文     2015-02-02      RG896663327CN      1908991720D0Q2B     16.2355</p>";
		watermarkHtml +="<p>所有录入过的单号，将会由系统自动定期复查，自动更新物流包裹状态，请在 <strong>“查询记录”</strong> 栏目中查看所有录入过的物流号最新状态。</p>";
		$('#txt_search_data').watermark(watermarkHtml, {fallback: false});
	},
	clear_progress_bar:function(){
		$(".progress-bar").css('width','0px');
	},
	manual_help:function(){
		$('#myModal').modal();
	},
	excel_help:function(){
		$('#myModal2').modal();
	},
	
	showDetailView:function(id,obj){
		if ($(obj).parents('tr').next('tr').find('.row').css('display') == "none"){
			//$(obj).html(Translator.t('收起'));
			if ($(obj).children('span').has('glyphicon-eye-open')){
				$(obj).children('span').removeClass('glyphicon-eye-open');
				$(obj).children('span').addClass('glyphicon-eye-close');
			}
		}
		else{
			//$(obj).html(Translator.t('详情'));
			if ($(obj).children('span').has('glyphicon-eye-close')){
				$(obj).children('span').removeClass('glyphicon-eye-close');
				$(obj).children('span').addClass('glyphicon-eye-open');
			}
		}
		
		$(obj).parents('tr').next('tr').find('.row').slideToggle("normal",function(){
		});
	},
	
	show_checking_tips:function(){
		var checking_list = $("table tr :contains('"+Translator.t('查询等候中')+"')");
		
		if (checking_list.length>0 || $('table').length==0){
			if ($("#tracking_result_message").css('display') != 'block')
				$("#tracking_result_message").toggle('slow');
			
			
		} else{
			if ($("#tracking_result_message").css('display') == 'block')
				$("#tracking_result_message").toggle('slow');
		}
	},
	
	updateProgressBar:function(callback){
		var allBar = new Array();
		allBar[0] = "-success";
		allBar[1] = "-primary";
		allBar[3] = "-danger";
		var totalcount = $('table tr[id*=tr_info]').length;
		for (var i=0 ; i<allBar.length;i++ ){
			if (totalcount > 0 ){
				var thiscount = $('table tr[id*=tr_info] td .label'+allBar[i]).length;
				tmp_width =  $('table tr[id*=tr_info] td .label'+allBar[i]).length / totalcount *100;
			}else{
				tmp_width = 0;
				var thiscount = 0;
			}
			
			$(".progress-bar"+allBar[i]+" span").html(thiscount);
			if (i==allBar.length){
				$(".progress-bar"+allBar[i]).css('width',function(index, value){
					value = tmp_width+'%';
					callback
				});
			}
				
			else{
				$(".progress-bar"+allBar[i]).css('width',tmp_width+'%');
			}
		}
		
		manual_import.list.updateProgressMessage();
	},
	
	updateProgressMessage:function(){
		var totalcount = $('table tr[id*=tr_info]').length;
		var checking_list = $("table tr span:contains('"+Translator.t('查询等候中')+"')");
		var restcount = totalcount - checking_list.length;
		$('.progress_p').html(restcount+"/"+totalcount);
	},
	
	AutoCheckTrackingProcess:function(){
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
			url:'/tracking/tracking/background_update_tracking_info', 
			data: {track_no_list : $.toJSON(track_no_list) , lang: $.toJSON(lang_list)},
			success: function (data) {
				if (data.success){
					if (data.TbHtml){
						for(var tmp_track_no in data.TbHtml ){
							var track_id = $('tr[track_no='+tmp_track_no+']').data('track-id');
							$('#div_more_info_'+track_id).html(data.TbHtml[tmp_track_no]);
						}
					}
					
					if (data.TrHtml){
						for(var tmp_track_no in data.TrHtml ){
							$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
							
							var track_id = $('tr[track_no='+tmp_track_no+']').data('track-id');
							TrackingTag.initMemoQtipEntryBtn($('.btn-qty-memo[data-track-id='+track_id+']'));
						}
					}
					
					
					manual_import.list.updateProgressBar();
					manual_import.list.show_checking_tips();
				}
			},
			error :function () {
				return false;
			}
		});
	},
	
	ShowExcelImport:function(){
		$('#import_excel_file').modal();
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
							label: Translator.t("添加备注"),  
							className: "btn-primary",  
							callback: function () { 
								
								if ($('#tracking_remark').val() == "" ){
									bootbox.alert(Translator.t('请填写备注!'));
									return false;
								}
								result = ListTracking.AppendRemark(track_no , $('#tracking_remark').val());
								
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
	
	AppendRemark:function(track_no , remark){
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/append-remark', 
				data: {track_no : track_no , remark :remark },
				success: function (result) {
					//bootbox.alert(result.message);
					if (result.sectionHtml)
						$('tr[track_no='+track_no+']').next('tr').find('section').html(result.sectionHtml);
					return result;
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	DelTrack:function(track_no){
		bootbox.confirm(Translator.t("是否删除"+track_no),function(r){
				if (! r) return;
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/tracking/tracking/delete-tracking',
					data: {track_no:track_no} , 
					success: function (result) {
						if (result.success){
							$('tr[track_no='+track_no+']').next('tr').remove();
							$('tr[track_no='+track_no+']').remove();
							manual_import.list.updateProgressBar();
						}else{
							bootbox.alert(result.message);
						}
					},
					error :function () {
						bootbox.alert("Internal Error");
						return false;
					}
				});
			});
	},
		
}

if (typeof TextImport === 'undefined')  var TextImport = new Object();
TextImport = {
	ConvertedArray:"",
	CurrentRow:'',
	parseTextToTable : function(str) {
		var lines = str.split("\n");	
		TextImport.ConvertedArray = new Array();
		lines.forEach(TextImport.processLine);
	},
	processLine : function(element, index, array) {
		if (element == '') return;
		var lineData =  element;
		var cols = lineData.split("\t");
		TextImport.CurrentRow = new Array();
		cols.forEach(TextImport.processLineEachField);
		TextImport.ConvertedArray.push(TextImport.CurrentRow );  
	},
	'processLineEachField' : function(element, index, array) {
		TextImport.CurrentRow.push(element);  
	},
};//end of object.TextImport

$(function() {
	manual_import.list.init();
	var int = self.setInterval("manual_import.list.AutoCheckTrackingProcess()",3000)
	
});
