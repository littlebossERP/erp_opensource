/**
 +------------------------------------------------------------------------------
 *selfWarehouseList的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		user_base
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof selfwarehouselist === 'undefined')  selfwarehouselist = new Object();
selfwarehouselist={
	init: function() {
		$('#warehouseDropDownid').change(function(){
			var warehouse_id = $("#warehouseDropDownid").val();
			
			window.open("/configuration/warehouseconfig/self-warehouse-list?warehouse_id="+warehouse_id,"_self");
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
			
			if(warehouse_id != 0){
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
			}else{
				alert('默认仓库不能关闭');
				$(":radio[name=warehouse_active]:not(:checked)").prop("checked", true);
			}
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
//			alert($(this).val());
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
	}
};
		 
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

function warehouseShippingCheck(){
	is_check = $('input[name=check_all]').prop('checked');
	$('.selectShip').each(function(){
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
//	alert('b');
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

//关闭仓库
function closeWarehouse(warehouse_id){
	if(warehouse_id != 0){
		$event = $.confirmBox('您确认关闭此仓库吗？');
		$event.then(function(){
			$.maskLayer(true);
			$.ajax({
		        type : 'POST',
		        cache : 'false',
		        data : {warehouse_id:warehouse_id, warehouse_active:'N'},
				url: "/configuration/warehouseconfig/warehouse-onoff-by-id",
				dataType: 'json',
		        success:function(response) {
			        var event = $.alert(response.response.msg,'success');
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
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

//修改仓库信息
function editWarehouseInfo(){
	var save_val = $('#address_form').serialize();
	
	//当关闭仓库时，判断是否存在库存，存在则弹出提示
	var is_stock = false;
	if($('#warehouse_activeN:checked')){
		var warehouse_id = $('#address_form #warehouse_id').val();
		$.ajax({
			type:'GET',
			data:{'warehouse_id' : warehouse_id},
			url:'/inventory/warehouse/check-stock', 
			dataType:'json',
			async : false,
			success: function(result) {
				is_stock = result.success;
			},
			error:function(){
				return false;
			},
		});
	}
	
	if(is_stock){
		bootbox.confirm(
		Translator.t("<span style='color:red'>此仓库存在库存或者在途数量，是否继续关闭？</span>"),
			function(r){
				if(r){
					saveWarehouse(save_val);
					return true;
				}
				else{
				}
			}
		);
	}
	else{
		saveWarehouse(save_val);
	}
}

function saveWarehouse(val){
	$.ajax({
		type: "POST",
		url: "/configuration/warehouseconfig/save-warehouse-info-byid",
		data: val,
		dataType: 'json',
		success: function(r) {
			if(!r.response.code){
				var event = $.alert(r.response.msg,'success');
        		event.then(function(){
        			location.reload();
        		});
			}else{
				var event = $.alert(r.response.msg,'error');
			}
		},
	});
}

//添加/编辑仓库匹配规则
function warehouseMatchRuleEdit(match_rule_id,warehouse_id){
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

//删除仓库
function deleteSelfWarehouse(warehouse_id){
	bootbox.confirm("<b>"+Translator.t('确认删除？<div style="color: red; line-height: 18px;"><br>1、不能再恢复；<br>2、已完成/已取消的订单仓库也会替换为“无”；</div>')+'</b>',function(r){
		if (!r) return;
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: '/inventory/warehouse/delete-warehouse',
			data: {warehouse_id:warehouse_id} , 
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
						message: Translator.t("成功删除!"),
						callback: function() {
							window.location.reload();
						}, 
					});
				}
				else{
					$.hideLoading();
					bootbox.alert(result.msg);
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
			}
		});
		
	});
}
