if (typeof cdiscountOrder === 'undefined')  cdiscountOrder = new Object();
cdiscountOrder = {	
	OMSLeftMenuAutoLoad:function(){
		$.ajax({
			url: "/order/cdiscount-order/left-menu-auto-load",
			type: 'post',
			dataType:"json",
			success: function(result) {
				var tmpLabelList = {'100':'未付款','200':'已付款','300':'发货中','500':'已完成','600':'已取消','601':'暂停发货','602':'缺货','all':'所有订单','issueorder':'纠纷钟的订单'};
				for(var key in result){
					if (tmpLabelList[key] != undefined){
						if ($('.menu_label:contains("'+Translator.t(tmpLabelList[key])+'")').next().length > 0){
							$('.menu_label:contains("'+Translator.t(tmpLabelList[key])+'")').next().remove();
						}
						if (result[key] >0)
						$('.menu_label:contains("'+Translator.t(tmpLabelList[key])+'")').parent().append('<span class="new"><p>'+result[key]+'</p></span>');
					}
				}
				$('.icon-iconfont42').load();
				
			},
			error: function() {
			}
		});
	},
	
	ajaxManualSyncInfo:function(){
		var dialog_active = $(".manual-sync-order-win").css('display');
		if(dialog_active!=='block'){
			return;
		}
		
		$.showLoading();
		$.ajax({
			url: "/order/cdiscount-order/ajax-check-sync-info",
			type: 'post',
			dataType:"json",
			async:true,
			success: function(result) {
				var sync_type_mapping = {'N':'初始化同步','R':'自动同步','M':'手工同步'};
				var sync_status_mapping = {'R':'运行中','C':'同步完成','F':'同步出错'};
				
				for(var id in result){
					if($("#sync_type-"+id)!=undefined && result[id]['sync_type']!=undefined ){
						var src_type = result[id]['sync_type']
						var sync_type = sync_type_mapping[src_type];
						$("#sync_type-"+id).html(sync_type);
					}
					if($("#sync_status-"+id)!=undefined && result[id]['sync_status']!=undefined ){
						var src_status = result[id]['sync_status']
						var sync_status = sync_status_mapping[src_status];
						$("#sync_status-"+id).html(sync_status);
					}
					if($("#error_log-"+id)!=undefined && result[id]['sync_info']['error_log']!=undefined ){
						var error_log = result[id]['sync_info']['error_log']
						$("#error_log-"+id).html(error_log);
					}
					if($("#start_time-"+id)!=undefined && result[id]['sync_info']['start_time']!=undefined ){
						var start_time = result[id]['sync_info']['start_time']
						$("#start_time-"+id).html(start_time);
					}
					if($("#end_time-"+id)!=undefined && result[id]['sync_info']['end_time']!=undefined ){
						var end_time = result[id]['sync_info']['end_time']
						$("#end_time-"+id).html(end_time);
					}
					if($("#begincreationdate-"+id)!=undefined && result[id]['sync_info']['begincreationdate']!=undefined ){
						var begincreationdate = result[id]['sync_info']['begincreationdate']
						$("#begincreationdate-"+id).html(begincreationdate);
					}
					if($("#endcreationdate-"+id)!=undefined && result[id]['sync_info']['endcreationdate']!=undefined ){
						var endcreationdate = result[id]['sync_info']['endcreationdate']
						$("#endcreationdate-"+id).html(endcreationdate);
					}
					if($("#order_count-"+id)!=undefined && result[id]['sync_info']['order_count']!=undefined ){
						var order_count = result[id]['sync_info']['order_count']
						$("#order_count-"+id).html(order_count);
					}
					if( result[id]['sync_status']!=undefined &&  result[id]['sync_status']=='R'){
						$("#sync_btn-"+id).css('display','none');
					}else{
						$("#sync_btn-"+id).css('display','block');
					}
				}
				$.hideLoading();
				return true;
			},
			error: function() {
				$.hideLoading();
				return false;
			}
		});
	},
	
	ManualSyncStoreOrder:function(id){
		var sync_order_time = $("#sync_order_time-"+id).val();
		if(typeof(sync_order_time)=='undefined' || sync_order_time==''){
			bootbox.alert('请选择需要获取的订单日期中间值！<br>手工同步会获取该日期(平台时间)前后两天的订单');
			return false;
		}
		
		$.showLoading();
		$.ajax({
			url: "/order/cdiscount-order/manual-sync-order-submit?id="+id+"&sync_order_time="+sync_order_time,
			type: 'post',
			dataType:"json",
			success: function(result) {
				$.hideLoading();
				if(result.success===true){
					bootbox.alert({
						message: '操作成功',  
						callback: function() {  
							cdiscountOrder.ajaxManualSyncInfo();
						},  
					});
				}else{
					bootbox.alert({
						message: '操作失败<br>'+result.message,  
						callback: function() {  
							cdiscountOrder.ajaxManualSyncInfo();
						},  
					});
				}
			},
			error: function() {
				$.hideLoading();
				bootbox.alert(Translator.t('操作失败,后台返回异常'));
				return false;
			}
		});
	},
};

