
$(function(){
	$('#myModal').modal({backdrop: 'static', keyboard: false, show:false});
	ShippingJS.init();
		
//	loadcustomList();
});

//打开‘编辑excel格式’的modal
function openExcelFormatModal(){
	carrier_code = $('input[name="carrier_code"]').val();
	var Url=global.baseUrl +'configuration/carrierconfig/open_excel_format';
	$.ajax({
      type : 'post',
      cache : 'false',
      data : {
    	  carrier_code:carrier_code,
      },
		url: Url,
      success:function(response) {
      	$('#myModal').html(response);
      	$('#myModal').modal('show');
      }
  });
}
//下拉改变当前物流
function changeSelectedCarrier(){
	key = $('select[name="open_custom_carrier_list"]').val();
	var Url=global.baseUrl +'configuration/carrierconfig/custom?codes_key='+key;
	self.location = Url;
}

//启用或关闭当前物流
function openOrCloseCustomCarrier(carrier_code, is_active){
	var Url=global.baseUrl +'configuration/carrierconfig/open_or_close_custom_carrier';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	carrier_code:carrier_code,
        	is_active:is_active,
        },
		url: Url,
        success:function(response) {
        	if(response[0] == 0){
        		alert(response.substring(2));

        		if(undefined == $('#search_tab_active').val()){
        			location.reload();
        		}else{
        			if(($('#search_tab_active').val() == 'customexcel') || ($('#search_tab_active').val() == 'customtracking')){
        				var Url=global.baseUrl +'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+carrier_code;
        				window.location=Url;	
        			}
        		}
	        }
        	else{
        		alert(response.substring(2));
	        }
        }
    });
}

//打开新建物流的modal
function openNewCarrierModal(){
	var Url=global.baseUrl +'configuration/carrierconfig/newcarrier';
	$.ajax({
      type : 'get',
      cache : 'false',
      data : {},
		url: Url,
      success:function(response) {
      	$('#myModal').html(response);
      	$('#myModal').modal('show');
      }
  });
}
/*****************************************************************************************/
/*_newCarrier.php*/
//检查必填项
function checkREQ(){
	var ok = true;
	$('.req').each(function(){
		type = $(this).prop("type");
		val = '';
		if(type == 'text'){
			val = $(this).val();
			text = $(this).prev().text();
		}
		else if(type == 'radio'){
			val = $('input[name="'+$(this).prop('name')+'"]:checked').val();
			text = $(this).parent().prev().text();
		}
		else if(type == 'select-one'){
			val = $(this).val();
			text = $(this).prev().text();
		}
		else{
			alert(type+':未做此类型的<空>判断!请补充');
			ok = false;
			return false;
		}
		if(typeof(val) == "undefined" || val == ""){
			$(this).focus();
			alert(text+"不能为空");
			ok = false;
			return false;
		}
	});
	return ok;
}

//获取用户填写的数据，进行新建物流，并返回结果
function newCarrierAjax(){
	vals = $('input[name="carrier_name"]').val();
	ok = checkREQ();
	if(ok){
		$form = $('#newcarrierFORM').serialize();
		var Url=global.baseUrl +'configuration/carrierconfig/savecustomcarrier';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $form,
			url: Url,
	        success:function(response) {
	        	if(response[0] == 0){
	        		alert(response.substring(2));
	        		location.href = global.baseUrl + 'configuration/carrierconfig/custom?carrier_name=' + vals;
		        }
	        	else{
	        		alert(response.substring(2)); 
		        }
	        }
	    });
	}
}

function self_carrier_save(carrier_type){
	vals = $('input[name="carrier_name'+carrier_type+'"]').val();
	
	var Url=global.baseUrl +'configuration/carrierconfig/savecustomcarrier';
	$.ajax({
        type : 'post',
        cache : 'false',
        dataType: 'json',
        data : {carrier_name:vals,carrier_type:carrier_type},
		url: Url,
        success:function(response) {
        	if(response.code == 0){
        		alert(response.msg);
        		location.href = global.baseUrl + 'configuration/carrierconfig/custom?tab_active=' + $('#search_tab_active').val()+'&tcarrier_code='+response.data;
	        }
        	else{
        		alert(response.msg); 
	        }
        }
    });
}

//加载自定义界面数据
function loadcustomList(){
	carrier_code = $('#search_carrier_code').val();
	custom_data = $('#search_tab_active').val();
	
	if(custom_data == 'customexcel')
		carrier_type = 1;
	else
		carrier_type = 0;
		
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	carrier_code:carrier_code,
        	carrier_type:carrier_type,
        },
		url: '/configuration/carrierconfig/get-self-carrier-info',
        success:function(response) {
        	if(custom_data == 'customexcel')
        		$('#excelcarrier_show_div').html(response);
        	else
        		$('#trackingcarrier_show_div').html(response);
        }
    });
}

//自定义物流运输服务批量全选/全不选
function customShippingCheck(type){
	is_check = $('input[name=check_all'+type+']').prop('checked');
	$('.selectShip'+type).each(function(){
		$(this).prop('checked',is_check);
	});
}

//自定义物流切换查询运输服务
function ship_method_list_down_listonchange(carrier_code,oversea_type){
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	server_id:$('#ship_method_list_down_list'+oversea_type).val(),carrier_code:carrier_code,oversea_type:oversea_type,
        },
		url: "/configuration/carrierconfig/get-custom-shipping-method-info",
        success:function(response) {
        	if(oversea_type == 0)
        		$('#all_shipping_shows_tbody0').html(response);
        	else
        		$('#all_shipping_shows_tbody1').html(response);
        }
    });
}

//自定义物流跟踪号批量全选/全不选
function customTrackingCheck(){
	is_check = $('input[name=check_tracking_all]').prop('checked');
	$('.selectTracking').each(function(){
		$(this).prop('checked',is_check);
	});
}

//跟踪号库批量操作
function dotracknoaction(obj, val){
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	
	if($('.selectTracking:checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的跟踪号");return $(obj).val('');
    }
	
	switch(val){
		case 'trackno_allocated':
			idstr='';
			$('input[name="check_tracking[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('/configuration/carrierconfig/batch-mark-track-number',{tracknos:idstr},function(result){
				bootbox.alert(result);
				window.location.search='tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
			});
			$(obj).val('');
			break;
		case 'trackno_del':
			idstr='';
			$('input[name="check_tracking[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('/configuration/carrierconfig/batch-del-tracksno',{tracknos:idstr},function(result){
				bootbox.alert(result);
				window.location.search='tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
			});
			break;
		case 'trackno_remove':
			idstr='';
			$('input[name="check_tracking[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('/configuration/carrierconfig/batch-mark-track-number',{tracknos:idstr,remove:1},function(result){
				bootbox.alert(result);
				window.location.search='tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
			});
			break;
	}
	
	$(obj).val('');
}

function doshippingaction(obj, val, carrier_type){
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	
	if($('.selectShip'+carrier_type+':checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的运输服务");return $(obj).val('');
    }
	
	switch(val){
	case 'shipping_close':
		idstr='';
		$('input[name="check_all'+carrier_type+'[]"]:checked').each(function(){
			idstr+=','+$(this).val();
		});
		$.post('/configuration/carrierconfig/openorcloseshipping',{shippings:idstr},function(result){
			bootbox.alert(result);
			window.location.search='tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
		});
		break;
	}
	
	$(obj).val('');
}

//增加物流商
function self_carrier_save_new(){
	carrier_type=$('select[name="customselect"]').val();
	vals = $('input[name="carrier_name"]').val();
	
	var Url=global.baseUrl +'configuration/carrierconfig/savecustomcarrier';
	$.ajax({
        type : 'post',
        cache : 'false',
        dataType: 'json',
        data : {carrier_name:vals,carrier_type:carrier_type},
		url: Url,
        success:function(response) {
        	if(response.code == 0){
        		alert(response.msg);
        		location.href = global.baseUrl + 'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val();
	        }
        	else{
        		alert(response.msg); 
	        }
        }
    });
}
