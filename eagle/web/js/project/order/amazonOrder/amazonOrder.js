amazonOrder = {
	sellerShipment:function(op_type){
		$.maskLayer(true);
		var orderIdList = [];
		var order_source_order_id = [];
		var description =[];
		var tracking_website = [];
		var tracking_number =[];
		var sendType=[];
		var serviceName=[];
		$(".order-id:checked").each(function(){
			var _this = $(this).parent().parent().next();
//                    var _this_logisticsNo = $(this).parent().parent().find('#logisticsNo');
			$(_this).find('.shipment_message').html(" 执行中,请不要关闭页面！");
			orderid = $(this).val(),
			orderIdList.push(orderid);
			order_source_order_id.push($(this).parent().parent().find('.order_source_order_id').html()) ;
			description.push(_this.find(".description").val());
			tracking_website.push( _this.find(".trackingWebsite").val());
			tracking_number.push(_this.find(".logisticsNo").val());
			sendType.push(_this.find("#sendType-"+orderid).val());
			serviceName.push(_this.find("#serviceName-"+orderid).val());
		});

		$.ajax({
			url: "/order/informdelivery/signshippedsubmit",
			data: {
				order_id:orderIdList,
				order_source_order_id:order_source_order_id,
				message:description,
				trackurl:tracking_website,
				tracknum:tracking_number,
				signtype:sendType,
				shipmethod:serviceName,
				op_type:op_type,
			},
			type: 'post',
			dataType:"html",
			success: function(result) {
				$.maskLayer(false);
				$.alertBox('操作成功');
			},
			error: function(XMLHttpRequest, textStatus) {
				$.maskLayer(false);
				$.alertBox('<p class="text-warn">网络不稳定.请求失败,请重试!</p>');


			}
		});
	},

	// OMSLeftMenuAutoLoad:function(){
	// 	$.ajax({
	// 		url: "/order/aliexpressorder/left-menu-auto-load",
	// 		type: 'post',
	// 		dataType:"json",
	// 		success: function(result) {
	// 			var tmpLabelList = {'100':'未付款','200':'已付款','300':'发货中','601':'暂停发货','602':'缺货','shipping_status_0':'未通知平台发货','shipping_status_2':'通知平台发货中','shipping_status_1':'已通知平台发货'};
	// 			for(var key in result){
	// 				if (tmpLabelList[key] != undefined){
	// 					if ($('.menu_label:contains("'+Translator.t(tmpLabelList[key])+'")').next().length > 0){
	// 						$('.menu_label:contains("'+Translator.t(tmpLabelList[key])+'")').next().remove();
	// 					}
	// 					if (result[key] >0)
	// 					$('.menu_label:contains("'+Translator.t(tmpLabelList[key])+'")').parent().append('<span class="new"><p>'+result[key]+'</p></span>');
	// 				}
	// 			}
	// 			$('.icon-iconfont42').load();
				
	// 		},
	// 		error: function() {
	// 		}
	// 	});
	// },
};