/**
 +------------------------------------------------------------------------------
 *overseaWarehouseList的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		user_base
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof overseaWarehouseList === 'undefined')  overseaWarehouseList = new Object();
overseaWarehouseList={
	init: function() {
		$('#warehouseDropDownid').change(function(){
			var warehouse_id = $("#warehouseDropDownid").val();
			
			window.open("/configuration/warehouseconfig/oversea-warehouse-list?warehouse_id="+warehouse_id,"_self");
		});
		
		$('#address_save').click(function(){
			var info = $('#address_form').serialize();
			
			$.ajax({
				type: "POST",
				url: "/configuration/warehouseconfig/save-warehouse-info-byid",
				data: info,
				dataType: 'json',
				success: function(r) {
					if(!r.response.code){
						alert(r.response.msg);
					}else{
						alert(r.response.msg);
					}
				},
			});
		});
		
		$('#warehouse_active').change(function(){
			var warehouse_active = $("#warehouse_active :checked").val();
			var warehouse_id = $('#warehouse_id').val();
			
			$.ajax({
				type: "POST",
				url: "/configuration/warehouseconfig/warehouse-onoff-by-id",
				data: {warehouse_id:warehouse_id, warehouse_active:warehouse_active},
				dataType: 'json',
				success: function(r) {
					if(!r.response.code){
						alert(r.response.msg);
					}else{
						alert(r.response.msg);
					}
				},
			});
		});
		
		$('#warehouse_match_rule').change(function(){
			var match_rule_id = $(this).val();
			var warehouse_id = $('#warehouse_id').val();
			if((match_rule_id == '') || warehouse_id == '')
				return false;
			
			$.ajax({
		        type : 'get',
		        cache : 'false',
		        data : {
		        	match_rule_id:match_rule_id,warehouse_id:warehouse_id,
		        },
				url: "/configuration/warehouseconfig/get-warehouse-match-rule-info-byid",
		        success:function(response) {
		        	$('#myModal').html(response);
		        	$('#myModal').modal('show');
		        }
		    });
//			$(this).val();
		});
		
		$('#shipMethodList').change(function(){
			if($('#warehouse_id').val() == ''){
				return false;
			}else{
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : {
			        	server_id:$(this).val(),warehouse_id:$('#warehouse_id').val(),
			        },
					url: "/configuration/warehouseconfig/get-shipping-method-info",
			        success:function(response) {
			        	$('#all_shipping_shows_DIV').html(response);
			        }
			    });
			}
		});
		
		$('#ship_method_list_down_list').change(function(){
			if($('#warehouse_id').val() == ''){
				return false;
			}else{
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : {
			        	server_id:$(this).val(),warehouse_id:$('#warehouse_id').val(),type:'oversea',oversea_type:oversea_type,
			        },
					url: "/configuration/warehouseconfig/get-shipping-method-info",
			        success:function(response) {
			        	$('#all_shipping_shows_tbody').html(response);
			        }
			    });
			}
		});
		
		$('.change_warehouse').click(function(){
			$(this).parent().parent().find('li').removeClass("active");
			$(this).parent().attr('class','active');
			
			warehouse_id = $(this).attr("value");
			warehouse_data = $(this).attr("data");
			
			$('#search_warehouse_id').val(warehouse_id);
			
			if(warehouse_data == 0){
				carrier_type = 'syscarrier';
				$('#search_tab_active').val('');
			}
			else{
				carrier_type = 'selfcarrier';
				$('#search_tab_active').val('self');
			}
				
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	warehouse_id:warehouse_id,
		        	type:carrier_type,
		        },
				url: '/configuration/warehouseconfig/get-oversea-warehouse-info',
		        success:function(response) {
		        	if(warehouse_data == 0)
		        		$('#syscarrier_show_div').html(response);
		        	else
		        		$('#selfcarrier_show_div').html(response);
		        }
		    });
		});
		
//		$('.tablist_class').click(function(){
//			$('#search_tab_active').val($(this).attr("value"));
//		});
		
		loadWarehouseList();
	},
	
	init2:function(){
		$("select[name^='params[service_code][amazon]']").combobox({removeIfInvalid:false});
		$('input[name^="proprietary_warehouses"]').click(function(){
			if($(this).is(':checked')){
				$('.houses'+$(this).val()).val(1);
			}
			else{
				$('.houses'+$(this).val()).val(0);
			}
		});
		$('input[name="print[selected]"]').change(function(){
			var val = $(this).val();
			for(var i = 0; i < 3; i++){
				var vals = 0;
				if(val == i){
					vals = 1;
				}
				$('.print_selected_'+i).val(vals);
			}
		});
		$(".gaoji").click(function(){
			if($(".gaojiitem").is(":hidden")){
				$(".gaojiitem").show();
				$('#caretDown').html("- 收起");
			}
			else{
				$(".gaojiitem").hide();
				$('#caretDown').html("+ 展开");
			}
		});
	},
	
};

//切换获取物流代码和仓库代码
function notWarehouseDropDownchange(){
	$('[name="hidwarehouse"]').val($('[name="notWarehouseDropDownid"]').find("option:selected").text());
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {code_related:$('[name="notWarehouseDropDownid"]').val()},
		url: '/configuration/warehouseconfig/get-oversea-carrier-info',
		dataType: 'text',
        success:function(response) {
        	$('#add_account_div').html(response);
        }
    });
}

//海外仓仓库添加保存
function oversea_account_save(type){
	if(type == 0){
		if($('[name="notWarehouseDropDownid"]').val() == '') return false;
		if($('[name="nickname"]').val() == ''){
			alert('账号别名必填');
			return false;
		}
		
		$formdata = $('#oversea_account_form').serialize();
	}else{
		if($('[name="nicknameself"]').val() == ''){
			alert('仓库名必填');
			return false;
		}
		$formdata = $('#oversea_self_account_form').serialize();
	}

	re=new RegExp(":","g");
	carrier_code=$('[name="notWarehouseDropDownid"]').val().replace(re,"_");
	
	$.ajax({
        type : 'post',
        cache : 'false',
        data : $formdata,
		url: '/configuration/warehouseconfig/oversea-and-carrier-save',
		dataType: 'json',
        success:function(response) {
        	if(response.code == 0){
        		alert(response.msg);
//        		window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
        		window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+carrier_code;
	        }else{
        		alert(response.msg);
	        }
        }
    });
}

//修改仓库信息
function editWarehouseInfo(){
	var info = $('#address_form').serialize();
	
	$.ajax({
		type: "POST",
		url: "/configuration/warehouseconfig/save-warehouse-info-byid",
		data: info,
		dataType: 'json',
		success: function(r) {
			if(!r.response.code){
				var event = $.alert(r.response.msg,'success');
        		event.then(function(){
//        			window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+r.response.data; 
        		 	window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();; 
        		});
			}else{
				var event = $.alert(r.response.msg,'error');
			}
		},
	});
}

//关闭仓库
function closeWarehouse(warehouse_id, is_close,code){
	if(warehouse_id != 0){
		if(is_close == 'N')
			$event = $.confirmBox('您确认关闭此仓库吗？');
		else
			$event = $.confirmBox('您确认开启此仓库吗？');
		
		$event.then(function(){
			$.maskLayer(true);
			$.ajax({
		        type : 'POST',
		        cache : 'false',
		        data : {warehouse_id:warehouse_id, warehouse_active:is_close},
				url: "/configuration/warehouseconfig/warehouse-onoff-by-id",
				dataType: 'json',
		        success:function(response) {
			        var event = $.alert(response.response.msg,'success');
					 event.then(function(){
					  // 确定,刷新页面
//					  window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
					  window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+code; 
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
		        }
		    });
		});
	}else{
		var event = $.alert('默认仓库不能关闭','success');
		 event.then(function(){
		},function(){
		});
	}
}

//打开新建运输服务modal
function newShippingOver(code){
	$.ajax({
        type : 'get',
        cache : 'false',
        data : {
			id:0,
			code:code,
			type:'add',
			key:'custom_oversea',
            },
		url: '/configuration/warehouseconfig/oversea-shippingservice',
        success:function(response) {
        	$('#myModal').html(response);
        	$('#myModal').modal('show');
        }
    });
}

function ship_method_list_down_listonchange(warehouse_id,oversea_type){
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	server_id:$('#ship_method_list_down_list'+oversea_type).val(),warehouse_id:warehouse_id,type:'oversea',oversea_type:oversea_type,
        },
		url: "/configuration/warehouseconfig/get-shipping-method-info",
        success:function(response) {
        	if(oversea_type == 0)
        		$('#all_shipping_shows_tbody0').html(response);
        	else
        		$('#all_shipping_shows_tbody1').html(response);
        }
    });
}

function loadWarehouseList(){
	warehouse_id = $('#search_warehouse_id').val();
	warehouse_data = $('#search_tab_active').val();
	
	if(warehouse_data == '')
		carrier_type = 'syscarrier';
	else
		carrier_type = 'selfcarrier';
		
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	warehouse_id:warehouse_id,
        	type:carrier_type,
        },
		url: '/configuration/warehouseconfig/get-oversea-warehouse-info',
        success:function(response) {
        	if(warehouse_data == '')
        		$('#syscarrier_show_div').html(response);
        	else
        		$('#selfcarrier_show_div').html(response);
        }
    });
}

function saveAllRulesByAjaxOver(){
	if($('input[name=newname]').val() == ""){
		$('input[name=newname]').focus();
		alert('规则名不能为空');
	}
	else{
		$formdata = $('#rulesForm').serialize();
		var Url=global.baseUrl +'configuration/warehouseconfig/save-warehouse-match-rule-info';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $formdata,
			url: Url,
			dataType: 'json',
	        success:function(response) {
	        	if(response.code == 0){
	        		alert(response.msg);
	        		location.reload();
		        }else{
	        		alert(response.msg);
		        }
	        }
	    });
	}
}

//将所有的右侧窗口关闭
function closeAllRightModals(){
	$('.rightModal').each(function(){
		$(this).hide();
	});
}
function setMyProvince(){
	var val = $('textarea[name^=Myprovince]').val();
	$('input[name=items_location_provinces]').val(val);
	$('.myprovince_value').text(val);
}
function setMyCity(){
	var val = $('textarea[name^=Mycity]').val();
	$('input[name=items_location_city]').val(val);
	$('.mycity_value').text(val);
}
function setRecProvince(){
	var val = $('textarea[name^=Recprovince]').val();
	$('input[name=receiving_provinces]').val(val);
	$('.recprovince_value').text(val);
}
function setRecCity(){
	var val = $('textarea[name^=Reccity]').val();
	$('input[name=receiving_city]').val(val);
	$('.reccity_value').text(val);
}
function setSKU(){
	var val = $('textarea[name^=sku]').val();
	$('input[name=skus]').val(val);
	$('.sku_value').text(val);
}
//将买家支付运费显示出来
function set_freight(){
	var min = $('input[name^=minfreight]').val();
	var max = $('input[name^=maxfreight]').val();
	
	$('input[name="freight_amount[min]"]').val(min);
	$('input[name="freight_amount[max]"]').val(max);
	$('.freight_value').text(min+" USD~"+max+" USD");
}
//将所有已勾选的物品所在国家，显示出来
function setMyCountry(){
	var htm = "";
	$('input:checkbox[name^="my_country"]:checked').each(function(){
		obj = $(this);
		htm += '<label><input type="checkbox" name="items_location_country[]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	$('.mycountry_value').html(htm);
}
//将所有已勾选的收件人国家，显示出来
function setReceivingCountry(){
	var htm = "";
	$('input:checkbox[name^="receiving_countrys"]:checked').each(function(){
		obj = $(this);
		htm += '<label><input type="checkbox" name="receiving_country[]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	$('.receiving_value').html(htm);
}
//将已选择的平台、账号、站点显示出来
function setSources(){
	var sourceHtml = "<div>平台：", siteHtml = "<div>站点：", seleruseridHtml = "<div>账号：";
	$('input:checkbox[name^="sites"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		if(!objPa){
			siteHtml += '<label><input type="checkbox" name="sources[site]['+platform+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
		}
	});
	$('input:checkbox[name^="selleruserids"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		if(!objPa){
			seleruseridHtml += '<label><input type="checkbox" name="sources[selleruserid]['+platform+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
		}
	});
	$('input:checkbox[name^="sourceCheck"]:checked').each(function(){
		obj = $(this);
		sourceHtml += '<label><input type="checkbox" name="sources[source][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	siteHtml += "</div>";seleruseridHtml += "</div>";sourceHtml += "</div>";
	$('.sources_value').html(sourceHtml+siteHtml+seleruseridHtml);
}
function siteChange(){
	$('input[name^=site]').each(function(){
		var n = $(this).val();
		var platform = $(this).parent().parent().attr('platform');
		if($(this).prop('checked')){
			$("."+platform+n).removeClass('sr-only');
		}else{
			$("."+platform+n).addClass('sr-only');
		}
	})
}

/*
 * 仓库移除运输服务
 */
function warehouse_shipping_remove(shipping_id, warehouse_id){
	$.ajax({
        type : 'post',
        cache : 'false',
        dataType: 'json',
        data : {
        	shipping_id:shipping_id,warehouse_id:warehouse_id,
        },
		url: "/configuration/warehouseconfig/remove-shipping-bywarehouseid",
        success:function(response) {
        	if(response.code == 0){
	        	alert(response.msg);
	    		location.reload();
        	}else{
        		alert(response.msg);
        	}
        }
    });
}

function saveAddWarehouseShipping(){
	var is_continue = false;
	
	$('.selectShip').each(function(){
		if($(this).prop('checked') == true)
			is_continue = true;
	});
	
	if(is_continue == false){
		alert('请先选中对应的运输服务');
	}else{
		$formdata = $('#warhouse_shipping_add_from').serialize();
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $formdata,
			url: '/configuration/warehouseconfig/save-warehouse-shipping',
			dataType: 'json',
	        success:function(response) {
	        	if(response.code == 0){
	        		alert(response.msg);
	        		location.reload();
		        }else{
	        		alert(response.msg);
		        }
	        }
	    });
	}
}

function warehouseShippingCheck(type){
	is_check = $('input[name=check_all'+type+']').prop('checked');
	$('.selectShip'+type).each(function(){
		$(this).prop('checked',is_check);
	});
}

function btn_search_warehouse_shipping(){
	var warehouse_shipping_name = $('input[name=warehouse_shipping_name]').val();
	var warehouse_carrier_code = $('select[name=warehouse_carrier_code]').val();
	
	$("#warehouse_shipping-list-table").queryAjaxPage({
		'warehouse_shipping_name':warehouse_shipping_name,warehouse_carrier_code:warehouse_carrier_code,
	});
}

function enble_or_crate_warehouse(){
	$formdata = $('#warehouse_enable_or_create_from').serialize();
	
	$.ajax({
        type : 'post',
        cache : 'false',
        data : $formdata,
		url: '/configuration/warehouseconfig/save-warehouse-enable-or-create',
		dataType: 'json',
        success:function(response) {
        	if(response.code == 0){
        		alert(response.msg);
        		location.reload();
	        }else{
        		alert(response.msg);
	        }
        }
    });
}

function carrierOverseaDropChange(){
	var carrier_code = $('select[name=carrierOverseaDropDownid]').val();
	
	if(carrier_code == ''){
		return false;
	}else{
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {
	        	carrier_code:carrier_code,
	        },
			url: "/configuration/warehouseconfig/get-oversea-warehouse-by-carriercode",
	        success:function(response) {
	        	$('#overseaWarehousediv').html(response);
	        }
	    });
	}
}

function oversea_carrier_save(){
	$formdata = $('#carrier_account_from').serialize();
	
	$.ajax({
        type : 'post',
        cache : 'false',
        data : $formdata,
		url: '/configuration/warehouseconfig/edit-orversea-carrier-account-save',
		dataType: 'json',
        success:function(response) {
        	if(response.code == 0){
        		if(response.carrier_code == "lb_chukouyiOversea"){
    				$id = response.msg;
    				//进入v2环境模拟登陆
    				var Url = global.baseUrl +'platform/wish-postal-v2/get-url';
    				$.ajax({
    					type : 'post',
    					dataType: 'json',
    					data : { wish_account_id:$id },
    					url: Url,
    					success:function(response) 
    					{
    						if(response['status'] == 0)
    						{
    							$.ajax({
    								type:'get',
    								url:response['url'],
    					          	dataType: 'json',
    					           	xhrFields: {
    					           		withCredentials: true
    					           	},
    					           	success: function(result) 
    					           	{
    					           		//alert(result);
    					           		if(result == 1){
    					           			window.open (global.baseUrl+"\carrier/carrier/chukouyi-auth?account_id="+$id);
    					    				window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
    					           		}
    					           		else
    					           			alert('登陆失败！');
    					            }
    					        });
    						}
    						else
    						{
    							window.open (global.baseUrl+"\carrier/carrier/chukouyi-auth?account_id="+$id);
    		    				window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
    						}
    					}
    				});
    				
        		}
        		else{
	        		alert(response.msg);
	        		window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();;
        		}
	        }else{
        		alert(response.msg);
	        }
        }
    });
	
}

function setDefaultAccount($id ,$obj){
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {id:$id},
		url: '/configuration/warehouseconfig/set-carrier-account-default',
        success:function(response) {
        	$('.def_account').html('设为默认');
        	$('.def_account').attr('style','');
        	$('.def_account').attr('onclick','setDefaultAccount('+$('.def_account').attr('data')+',this)');
        	$('.def_account').attr('class','iv-btn');
        	$($obj).html('默认账号');
        	$($obj).attr('style','color:#FF9900');
        	$($obj).attr('class','iv-btn def_account');
        	$($obj).attr('onclick','');
        }
    });
}

function delCarrierAccount($id){
	$event = $.confirmBox('您确定要删除该条物流账号信息吗？');
	$event.then(function(){
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {id:$id},
			url: '/configuration/warehouseconfig/del-carrier-account',
	        success:function(response) {
		        $a = $.alert(response,'success');
		        $a.then(function(){
		        	location.reload();
		        })
	        }
	    });
	});
}

//第三方仓库运输服务开启、编辑、关闭
function openOrCloseShippingOver($id,$code,$type){
	if(($type == 'open') || ($type == 'edit')){
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {
				id:$id,
				code:$code,
				type:$type,
				key:'',
	            },
			url: '/configuration/warehouseconfig/oversea-shippingservice',
	        success:function(response) {
	        	$('#myModal').html(response);
	        	$('#myModal').modal('show');
	        }
	    });
	}else if($type == 'close'){
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {
	        	id:$id,
	        	is_used:0,
	        },
			url: '/configuration/warehouseconfig/open-or-close-shipping',
	        success:function(response) {
	        	if(response[0] == 0){
	        		alert(response.substring(2));
//	        		location.reload();
//	        		window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
	        		window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();;
		        }
	        	else{
	        		alert(response.substring(2));
		        }
	        }
	    });
	}
}

//第三方自定义仓库运输服务开启、编辑、关闭
function openOrCloseSelfShipping($id,$code,$type){
	if(($type == 'open') || ($type == 'edit')){
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {
				id:$id,
				code:$code,
				type:$type,
				key:'custom_oversea',
	            },
			url: '/configuration/warehouseconfig/oversea-shippingservice',
	        success:function(response) {
	        	$('#myModal').html(response);
	        	$('#myModal').modal('show');
	        }
	    });
	}else if($type == 'close'){
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {
	        	id:$id,
	        	is_used:0,
	        },
			url: '/configuration/warehouseconfig/open-or-close-shipping',
	        success:function(response) {
	        	if(response[0] == 0){
	        		alert(response.substring(2));
	        		window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
		        }
	        	else{
	        		alert(response.substring(2));
		        }
	        }
	    });
	}
}

//编辑运输服务相关设置
function saveShipingServiceOver(){
	$formdata = $('#shippingserviceForm').serialize();

	$.ajax({
        type : 'post',
        cache : 'false',
        data : $formdata,
        dataType: 'json',
		url: '/configuration/warehouseconfig/save-oversea-shippingservice',
        success:function(response) {
        	if(response.code == 0){
        		alert(response.msg);
//        		window.location.search='tab_active='+$('#search_tab_active').val();
//        		window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
        		window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
	        }
        	else{
        		alert(response.msg);
	        }
        }
    });
}

function selectRulessOver(obj,sid,warehouse_id){
	var val = $(obj).val();
	if(val >= 0){
		$.openModal('/configuration/warehouseconfig/shippingrules',{id:val, sid:sid, sname:'', warehouse_id:warehouse_id,},'编辑','get');
		$(obj).val(-1);
	}
};

function saveAllRulesByAjaxShipping(){
	if($('input[name=newname]').val() == ""){
		$('input[name=newname]').focus();
		alert('规则名不能为空');
	}
	else{
		$formdata = $('#rulesForm').serialize();
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $formdata,
			url: '/configuration/warehouseconfig/saveshippingrules',
	        success:function(response) {
	        	if(response[0] == 0){
	        		alert(response.substring(2));
//	        		window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
	        		window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
		        }
	        	else{
	        		alert(response.substring(2));
		        }
	        }
	    });
	}
}

//更新运输服务
function updateShippingOver($carrier_code){
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	carrier_code:$carrier_code,
        },
		url: '/configuration/warehouseconfig/update-oversea-shipping-service',
        success:function(response) {
        	if(response[0] == 0){
        		alert(response.substring(2));
//        		window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
        		window.location.href = global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();;
	        }
        	else{
        		alert(response.substring(2)); 
	        }
        }
    });
};

function editWarehouseRule(match_rule_id, warehouse_id){
	if((match_rule_id == '') || warehouse_id == '')
		return false;
	$.ajax({
        type : 'get',
        cache : 'false',
        data : {
        	match_rule_id:match_rule_id,warehouse_id:warehouse_id,
        },
		url: "/configuration/warehouseconfig/get-warehouse-match-rule-info-byid",
        success:function(response) {
        	$('#myModal').html(response);
        	$('#myModal').modal('show');
        }
    });
}

function searchWarehouseRuleListBtn(){
	$warehouse_id = $('select[name=warehouse_name_rulelist]').val();
	$warehouse_type = $('select[name=warehouse_type_rulelist]').val();
	$warehouse_state = $('select[name=warehouse_state_rulelist]').val();
	
	$t = false;
	var Url = '/configuration/warehouseconfig/warehouse-rule-list?';
	
	if($warehouse_id != -1){
		Url += 'warehouse_id=' + $warehouse_id;
		$t = true;
	}
	
	if($warehouse_type != -1){
		if($t) Url += '&';
		Url += 'warehouse_type=' + $warehouse_type;
		$t = true;
	}
	
	if($warehouse_state != -1){
		if($t) Url += '&';
		Url += 'warehouse_state=' + $warehouse_state ;
	}
	
	self.location = Url;
}

//将所有的右侧窗口关闭
function closeAllRightModals(){
	$('.rightModal').each(function(){
		$(this).hide();
	});
}
function setMyProvince(){
	var val = $('textarea[name^=Myprovince]').val();
	$('input[name=items_location_provinces]').val(val);
	$('.myprovince_value').text(val);
}
function setMyCity(){
	var val = $('textarea[name^=Mycity]').val();
	$('input[name=items_location_city]').val(val);
	$('.mycity_value').text(val);
}
function setRecProvince(){
	var val = $('textarea[name^=Recprovince]').val();
	$('input[name=receiving_provinces]').val(val);
	$('.recprovince_value').text(val);
}
function setRecCity(){
	var val = $('textarea[name^=Reccity]').val();
	$('input[name=receiving_city]').val(val);
	$('.reccity_value').text(val);
}
function setSKU(){
	var val = $('textarea[name^=sku]').val();
	$('input[name=skus]').val(val);
	$('.sku_value').text(val);
}
//将买家支付运费显示出来
function set_freight(){
	var min = $('input[name^=minfreight]').val();
	var max = $('input[name^=maxfreight]').val();
	$('input[name="freight_amount[min]"]').val(min);
	$('input[name="freight_amount[max]"]').val(max);
	$('.freight_value').text(min+" USD~"+max+" USD");
}
//将总金额显示出来
function set_totalamount(){
	var min = $('input[name^=minamount]').val();
	var max = $('input[name^=maxamount]').val();
	$('input[name="total_amount[min]"]').val(min);
	$('input[name="total_amount[max]"]').val(max);
	$('.totalamount_value').text(min+" USD~"+max+" USD");
}
//将总重量显示出来
function set_totalweight(){
	var min = $('input[name^=minweight]').val();
	var max = $('input[name^=maxweight]').val();
	$('input[name="total_weight[min]"]').val(min);
	$('input[name="total_weight[max]"]').val(max);
	$('.totalweight_value').text(min+" g~"+max+" g");
}

//将所有已勾选的物品所在国家，显示出来
function setMyCountry(){
	var htm = "";
	$('input:checkbox[name^="my_country"]:checked').each(function(){
		obj = $(this);
		htm += '<label><input type="checkbox" name="items_location_country[]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	$('.mycountry_value').html(htm);
}

//将所有已勾选的收件人国家，显示出来
function setReceivingCountry(){
	var htm = "";
	$('input:checkbox[name^="receiving_countrys"]:checked').each(function(){
		obj = $(this);
		htm += '<label><input type="checkbox" name="receiving_country[]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	$('.receiving_value').html(htm);
}

//将所有买家选择的运输服务显示出来
function setbuyer_transportation_service(){
	var htm = "";
	$('input:checkbox[name^="buyer_transportation_services"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().parent().hasClass('sr-only');
		objPaPa = $(this).parent().parent().parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		site = $(this).parent().parent().attr('site');
		
		if(!objPa && !objPaPa){
			htm += '<label><input type="checkbox" name="buyer_transportation_service['+platform+']['+site+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
		}
	});
	$('.buyer_transportation_service_value').html(htm);
}

//将已选择的平台、账号、站点显示出来
function setSources(){
	var sourceHtml = "<div>平台：", siteHtml = "<div>站点：", seleruseridHtml = "<div>账号：";
	$('input:checkbox[name^="sites"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		if(!objPa){
			siteHtml += '<label><input type="checkbox" name="sources[site]['+platform+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
		}
	});
	$('input:checkbox[name^="selleruserids"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		if(!objPa){
			seleruseridHtml += '<label><input type="checkbox" name="sources[selleruserid]['+platform+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
		}
	});
	$('input:checkbox[name^="sourceCheck"]:checked').each(function(){
		obj = $(this);
		sourceHtml += '<label><input type="checkbox" name="sources[source][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	siteHtml += "</div>";seleruseridHtml += "</div>";sourceHtml += "</div>";
	$('.sources_value').html(sourceHtml+siteHtml+seleruseridHtml);
	setbuyer_transportation_service();
}

//将已选择的商品标签显示出来
function setTag(){
	var htm = "";
	$('input:checkbox[name^="product_tags"]:checked').each(function(){
		obj = $(this);
		htm += '<label><input type="checkbox" name="product_tag[]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	$('.product_tag_value').html(htm);
}

function siteChange(){
	$('input[name^=site]').each(function(){
		var n = $(this).val();
		var platform = $(this).parent().parent().attr('platform');
		if($(this).prop('checked')){
			$("."+platform+n).removeClass('sr-only');
		}else{
			$("."+platform+n).addClass('sr-only');
		}
	})
}

function createOverseaWarehouseType(){
	$oversea_type_radio = $('input[name="oversea_type_radio"]:checked ').val();
	
	if($oversea_type_radio == 1){
		$('#create_oversea_div').hide();
	}else{
		$('#create_oversea_div').show();
	}
}

function openExcelFormatModal(warehouse_id){
	$.ajax({
      type : 'post',
      cache : 'false',
      data : {
    	  warehouse_id:warehouse_id,
      },
		url: '/configuration/warehouseconfig/edit-warehouse-excel-format',
      success:function(response) {
      	$('#myModal').html(response);
      	$('#myModal').modal('show');
      }
  });
}

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

function doshippingaction(obj, val, carrier_type, carrier_code){
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	
	if($('.selectShip'+carrier_type+':checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的运输服务");return $(obj).val('');
    }
	
	idstr='';
	$('input[name="check_all'+carrier_type+'[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	
	switch(val){
		case 'shipping_close':
			$.post('/configuration/carrierconfig/openorcloseshipping',{shippings:idstr},function(result){
				bootbox.alert(result);
				window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
			});
			break;
		case 'shipping_account':
			$.openModal('/configuration/carrierconfig/batch-shipping-carrieraccount-list',{carrier_code:carrier_code, edit_type:'shipping_account'},'批量修改物流账号','post');
			break;
	}
	
	$(obj).val('');
}

//批量修改保存代码
function savebatchEditCarrierinfo(edit_type){
	if($('.selectShip0:checked').length==0){
    	bootbox.alert("请选择要操作的运输服务");return $(obj).val('');
    }
	
	if(edit_type == 'shipping_account'){
		common_id = $('select[name=accountID]').val();
		
		if((common_id == '') || (common_id == undefined)){
	    	bootbox.alert("请选择有效的账号");return $(obj).val('');
	    }
	}
	
	idstr='';
	$('.selectShip0:checked').each(function(){
		idstr+=','+$(this).val();
	});
	
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	edit_type:edit_type,
        	shippings:idstr,
        	common_id:common_id,
        },
		url: '/configuration/carrierconfig/save-batch-edit-carrierinfo',
        success:function(response) {
        	bootbox.alert(response);
//			window.location.search='&tcarrier_code='+$('#search_carrier_code').val();
			window.location.href = global.baseUrl+'configuration/warehouseconfig/oversea-warehouse-list?tab_active='+$('#search_tab_active').val()+'&twarehouseid='+$('#search_warehouse_id').val();
        }
    });
}

function UpdatePlatform($platform, obj){
	$.showLoading();
	$select = $(obj).prev();
	$val = $select.val();
	var Url=global.baseUrl +'configuration/carrierconfig/updateplatform';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	platform:$platform,
        },
		url: Url,
        success:function(response) {
        	$.hideLoading();
        	if(response[0] == 0){
        		$select.html(response.substr(1));
        		$select.val($val);
        		alert('更新成功');
        	}
        	else{
	        	alert(response.substr(2));
        	}
        }
    });
}

//开启或关闭指定账号
function openOrCloseAccountOver(id,is_used,code,warehousid,account,third_party_code){
	if(is_used == 1 && code == "lb_chukouyiOversea"){
		window.open (global.baseUrl+"\carrier/carrier/chukouyi-auth?account_id="+id);
	}
	else{
		var Url=global.baseUrl +'configuration/carrierconfig/open-or-close-account';
		var thsi=$(this);
		$.ajax({
			type : 'post',
			cache : 'false',
			data : {
				aid:id,
				is_used:is_used
			},
			url: Url,
			success:function(response) {
				var res = JSON.parse(response);
				if(res.code == 1){
					$e = $.alert(res.msg,'danger');
				}
				else{
					if(is_used==1)
						$.openModal('/configuration/warehouseconfig/add-or-edit-orversea-carrier-account',{type:'add',carrier_code:code,warehouse_id:warehousid,third_party_code:third_party_code,id:id,account:account},'编辑物流账号','get')
					else{
						$e = $.alert(res.msg,'success');
						window.location=global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
					}
				}
			}
		});
	}

}

//获取速卖通账号的线上发货地址
function updateAliexpressAddressInof(){
	$.showLoading();
	var Url=global.baseUrl +'configuration/carrierconfig/update-aliexpress-address-inof';
	$.ajax({
        type : 'post',
        cache : 'false',
        dataType: 'json',
        data : {
//        	platform:$platform,
        },
		url: Url,
        success:function(response) {
        	$.hideLoading();
        	alert(response.msg);
        }
    });
}
