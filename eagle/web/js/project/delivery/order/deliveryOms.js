//state: function(url,title,param,timeout,method,state)

function changeCarrierType(carrier_type,default_warehouse_id,carrier_step,use_mode){
	order_abnormal = 0;
	if(carrier_step == 'FINISHED1'){
		carrier_step = 'FINISHED';
		order_abnormal = 1;
	}
	
	var Url=global.baseUrl +'delivery/order/listplanceanorder';
	
	$.location.state(Url,'小老板',{carrier_type:carrier_type,default_warehouse_id:default_warehouse_id,
    	carrier_step:carrier_step,order_abnormal:order_abnormal,use_mode:use_mode,warehouse_id:default_warehouse_id},0,'post',false);
}

//搜索button查找事件
function searchButtonClick(win_list){
	if(win_list == undefined){
		win_list = 'listplanceanorder'
	}
	
	var Url=global.baseUrl +'delivery/order/'+win_list;
	
	$.location.state(Url,'小老板',$("#searchForm").serialize(),0,'post',false);
}

//切换国家筛选
function countryOnChange(obj,win_list){
	$('#consignee_country_code').val($(obj).val());
	searchButtonClick(win_list);
}

//打印标签筛选
function carrierPrintBtnClick(obj,win_list){
	$('#carrierPrintType').val($(obj).attr("value"));
	
	if(win_list == undefined){
		win_list = 'listplanceanorder'
	}
	
	searchButtonClick(win_list);
}

//仓库标签筛选
function warehouseBtnClick(obj,win_list){
	$('#warehouse_search').val($(obj).attr("value"));
	
	$('#default_shipping_method_code_hide').val('');
	
	if(win_list == undefined){
		win_list = 'listplanceanorder'
	}
	
	searchButtonClick(win_list);
}

//可以复用的调用按钮ajax方法
function searchBtnPubChange(obj,type,win_list){
	$('#'+type).val($(obj).attr("value"));
	
	if(win_list == undefined){
		win_list = 'listplanceanorder'
	}
	
	searchButtonClick(win_list);
}

function ck_allOnClick(obj){
	if($(obj).prop("checked")==true){
		$(".ck").prop("checked",true);
	}else{
		$(".ck").prop("checked",false);
	}
}

//排序标签筛选
function sortModeBtnClick(obj,win_list){
	var sorttype = $(obj).attr("sorttype");
	if(sorttype == ''){
		sorttype = 'acs';
	}else{
		sorttype = '';
	}
	
	$('#customsort_search').val($(obj).attr("value"));
	$('#ordersorttype_search').val(sorttype);
	
	if(win_list == undefined){
		win_list = 'listplanceanorder'
	}
	
	searchButtonClick(win_list);
}

//手动报关设置
function setCustomsFormSpan(obj, carrier_code){
	var form = $(obj).parents('form').first();
	if(obj.checked){
		$(form).find('[name="items_param"]').css("border","2px solid red");
		//新增一个手动报关信息
		var item = $(form).find('[name="item_param"]').first();
		var CustomsFormSpanItem = $(form).find('[name="CustomsFormSpanItem"]')
		var html = '';
		html = '<hr style="margin-top:1px;margin-bottom:2px;clear: both;"/><h5 style="text-align:left; color:red; ">报关信息设置为：</h5>';
		html += '<div style="float:left; width:100%;">'+ item.html();
		//添加报关数量
		if(carrier_code == 'lb_anjun')
			html += '<div class=" prod-param-group"><div style="float: right"><input type="text" class="eagle-form-control" name="qty[]" style="width:150px;"></div><div style="width:120px; float: right;margin-top:9px;margin-right:4px;"><label>报关数量</label></div></div>';
		html += '</div><h5 style="text-align:left; color:red; ">以下报关信息失效：</h5>';
		CustomsFormSpanItem.html(html);
				
		//清空所有手动报关内input的值
		CustomsFormSpanItem.find('input').val('');
		//旧的报关信息只读
		$(form).find('[name="items_param"]').find('input,select').attr("disabled", "true");
		//旧报关信息转移属性name，为了form表单不提交
		$(form).find('[name="items_param"]').find('input,select').each(function(){
			$(this).attr('ole_name', $(this).attr('name'));
			$(this).removeAttr('name');
		});
		
	}
	else{
		//清除手动报关信息
		$(form).find('[name="items_param"]').css("border","none");
		$(form).find('[name="CustomsFormSpanItem"]').html("");
		//旧的报关信息可编辑
		$(form).find('[name="items_param"]').find('input,select').removeAttr("disabled");
		//旧报关信息转移属性name，为了form表单提交
		$(form).find('[name="items_param"]').find('input,select').each(function(){
			$(this).attr('name', $(this).attr('ole_name'));
			$(this).removeAttr('ole_name');
		});
	}
};
