/**
 * 
 */	
//标签添加初始化
if (typeof showEbayDisputes === 'undefined')  showEbayDisputes = new Object();
showEbayDisputes={
		big_dialog:'',
		init: function() {
			$('#manual_syn_disputes').click(function(){
				$('#myMessage').modal('show');
			});
			
			
			$( "#open_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
			$( "#open_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
		}
}



//高级搜索
function mutisearch(){
	var status = $('.mutisearch').is(':hidden');
	if(status == true){
		//未展开
		$('.mutisearch').show();
		$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
		return false;
	}else{
		$('.mutisearch').hide();
		$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
		return false;
	}
}

//同步AJAX
function manualSync(startdate,enddate,ebay_user){
	if(startdate == 'undefined' || startdate == ''){
		bootbox.alert('请输入开始日期');return false;
	}
	if(enddate == 'undefined' || enddate == ''){
		bootbox.alert('请输入结束日期');return false;
	}
	if(ebay_user =='undefined' || ebay_user ==''){
		bootbox.alert('请选择同步用户');return false;
	}
	$.showLoading();
	$.post('/message/all-customer/ebay-manual-sync',{startdate:startdate,enddate:enddate,ebay_user:ebay_user},function(result){
		
		$.hideLoading();
//		if(result.msg == 'Synchronous success') {
//			//成功
//			bootbox.alert('同步成功');
//			document.location.reload();
//		}else if(result.msg == 'Today has been synchronized!') {
//			//每天手动同步次数：一次
//			bootbox.alert('已同步，请稍后再次操作');
////			document.location.reload();
//		}else {
//			//失败
//			bootbox.alert('同步异常'+result.msg);
//		}
		bootbox.alert(result.msg);

	}, 'json');
}

//
function ShowDetailDisputes(caseid){
	var letter_no = $(this).parent().parent("tr").data("id");
	$('#letter_no_'+letter_no).css("font-weight","900");
	$.showLongLoading();
	$.get('/message/all-customer/show-ebay-disputes-details?caseid='+caseid,
	   function (data){
			$.hideLongLoading();
			showEbayDisputes.big_dialog = bootbox.dialog({
				title:"纠纷处理",
				className: "detail_letter", 
				message:data,
			});	
	});
}

//清楚搜索项
function cleform(){
	$('#caseid').val('');
	$('#srn').val('');
	$('#buyeruserid').val('');
	$('#itemid').val('');
	$('#startdate').val('');
	$('#enddate').val('');
	$('#case_status').val('');
	$('#case_type').val('');
}


