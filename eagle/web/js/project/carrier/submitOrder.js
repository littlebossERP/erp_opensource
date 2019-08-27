/*
 * 当操作进行完成后,更新进度条显示数字
 */
function refreshNums() {
	var ids = operate_ids;
	$.ajax({
		url: 'getcountnums',
		type: 'get',
		data: {
			order_id: ids
		},
		success: function(response) {
			var nums = JSON.parse(response);
			var numsLength = nums.length;
			//更新数字
			for (var i = 0; i < numsLength; i++) {
				$('#jindutiao_count' + i).html(nums[i]);
			}
		},
	})
}

function checkRequestStatus(i,count,info){
	if(i==count){
		layer.closeAll('loading');
		layer.msg(info, {icon: 6});
 		refreshNums();
	}
}

/*
 * 添加已成功div
 */
function createSuccessDiv(obj) {
	//如果div不存在,则创建
	if ($('#success_order').length == 0) {
		$('#getOrderNo_main').append('<div><div class="success_order_title">已成功订单</div><table id="success_order" class="table lb-grid table-striped table-bordered table-hover table-responsive" style="width:100%;"></table></div>');
		$('#success_order').append($('.danger').clone());
		$('#success_order .danger input[type="checkbox"]').remove();
	}
	var $form = $(obj);
	var orderContent = $form.closest('.orderInfo'),
		orderTitle = orderContent.prev();
	orderContent.remove();
	orderTitle.find('input[type="checkbox"]').remove();
	$('#success_order').append(orderTitle.remove());
}
/*
 * 除订单上传页面使用，成功之后将成功的div添加到下面
 */
function createSuccessDiv2(obj){
	//如果div不存在,则创建
	if ($('#success_order').length == 0) {
		$('#getOrderNo_main').append('<div id="success_order" style="margin-top:50px;"><div class="success_order_title">已成功订单</div></div>');
	}
	var button_span = $(obj).find('span[class="glyphicon glyphicon-minus"]');
	if (button_span.length) button_span.click();
	$('#success_order').append($(obj).remove());
}

/*
 * 判断当前状态是否完成 给出相应提示
 */
$(function() {
//	window.opener.location.reload();
});

// 2015-07-18 by huaqingfeng
$(function() {
	'use strict';

	// lb-grid 全选
	$(".lb-grid-select-all input[type=checkbox]").on('click', function() {
		var $this = $(this),
			checked = $this.is(':checked');
		$this.closest('table').find(".lb-grid-select input[type=checkbox]").prop('checked', checked);
	});

	// tr折叠
	$(".lb-grid tr.lb-grid-toggle td:not(:first)").on('click', function() {
		$(this).closest('tr').nextUntil('.lb-grid-toggle').toggle();
	});

	// tr折叠所有
	$(".lb-grid tr.lb-grid-toggle-all th:not(:first)").on('click', function() {
		var $tr = $(this).closest('tr').nextAll('tr.lb-grid-toggle').nextUntil('.lb-grid-toggle'),
			toggle = $(this).data('toggle');
		if (toggle) {
			$tr.show();
		} else {
			$tr.hide();
		}
		$(this).data('toggle', !toggle);
	});

	// 自动选中
	$("[data-selected=true]").each(function(){
		$(this).find('input:checkbox').click();
	});

	// 上传物流，提交表单
	$('.carrier-operate-action .submit').on('click', function() {
		var $forms = $('.lb-grid-select').find(':checked').closest('tr').next().find('form'),
			allRequests = [];
		layer.msg('上传中,请稍等', {
			icon: 16,
			time: 40000,
			shade: 0.5
		});

		$forms.each(function() {
			var obj = this;
			var $form = $(this),
				$message = $form.closest('.orderInfo').prev().find('.message');
			$message.html(" 执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "carrier/carrieroperate/get-data",
				data: $form.serialize(),
				type: 'post',
				success: function(response) {
					var result = JSON.parse(response);
					if (result.error) {
						$message.html(result.msg);
					} else {
						$message.html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
						createSuccessDiv(obj);
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$message.html('网络不稳定.请求失败,请重试');
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			layer.closeAll('loading');
			layer.msg('操作已全部完成,错误原因请查看处理结果', {
				icon: 6
			});
			refreshNums();
		});

	});

});
// 2015-07-18 end


/*
 * 物流上传前 用户最后确认数据页面
 */
//后台开始上传数据


//匹配物流服务
$('.match').click(function() {
	$.post(global.baseUrl + "carrier/default/matchshipping", {
		orders: operate_ids
	}, function(result) {
		bootbox.alert(result);
		location.reload();
	});
});

//批量修改报关信息
$('.edit_customs_info').click(function(){
	$.showLoading();
	$.get(global.baseUrl+'carrier/default/edit-customs-info',
	   function (data){
			$.hideLoading();
			var thisbox = bootbox.dialog({
				className: "myClass", 
				title : ("批量修改报关信息"),
			    message: data,
			    buttons:{
					Ok: {  
						label: Translator.t("保存"),  
						className: "btn-success",  
						callback: function () { 
							saveCustomsInfo( function(){
								$(thisbox).modal('hide');
							});
							return false;
						}
					}, 
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-transparent",  
						callback: function () {  
						}
					}, 
				}
			});	
	});
});

//批量修改报关信息保存
function saveCustomsInfo(callback){
	var checkOrderIds = '';
	var tmpCustomsName = '';
	var tmpCustomsEName = '';
	var tmpCustomsDeclaredValue = '';
	var tmpCustomsweight = '';
	
	var tmpCustomsextraid = '';
	
	if ($.trim($('#customsName').val()).length != 0 ) {
		tmpCustomsName = $.trim($('#customsName').val());
	}
	if ($.trim($('#customsEName').val()).length != 0 ) {
		tmpCustomsEName = $.trim($('#customsEName').val());
	}
	if ($.trim($('#customsDeclaredValue').val()).length != 0 ) {
		tmpCustomsDeclaredValue = $.trim($('#customsDeclaredValue').val());
	}
	if ($.trim($('#customsweight').val()).length != 0 ) {
		tmpCustomsweight = $.trim($('#customsweight').val());
	}
	
	if ($.trim($('#customsextra_id').val()).length != 0 ) {
		tmpCustomsextraid = $.trim($('#customsextra_id').val());
	}
	
	$(".orderTitle.lb-grid-toggle.info .lb-grid-select :checkbox").each(function(){
		if($(this).is(':checked')){
			checkOrderIds += $(this).parent().parent().find('td:eq(1)').html()+',';
			
			if(tmpCustomsName != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_cn").parent().next().val(tmpCustomsName);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='Name[]']").val(tmpCustomsName);
			}
			if(tmpCustomsEName != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_en").parent().next().val(tmpCustomsEName);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='EName[]']").val(tmpCustomsEName);
			}
			if(tmpCustomsDeclaredValue != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_declaredValue").parent().next().val(tmpCustomsDeclaredValue);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='DeclaredValue[]']").val(tmpCustomsDeclaredValue);
			}
			if(tmpCustomsweight != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>label").find(".customs_weight").parent().next().val(tmpCustomsweight);
				$(this).parent().parent().next(".orderInfo").find(".form-inline .form-group>[name='weight[]']").val(tmpCustomsweight);
			}
			
			if(tmpCustomsextraid != ''){
				$(this).parent().parent().next(".orderInfo").find(".form-inline").find(".hasTip").next().val(tmpCustomsextraid);
			}
		}
	});
	
	if($("#chk_isEditToSku").is(':checked')){
		$.post(global.baseUrl + "carrier/default/edit-customs-info", {
			orders: checkOrderIds,customsName:tmpCustomsName,customsEName:tmpCustomsEName,customsDeclaredValue:tmpCustomsDeclaredValue,customsweight:tmpCustomsweight
		}, function(result) {
			bootbox.alert(result);
			location.reload();
		});
	}else{
		bootbox.alert({
			message:'批量修改成功',
			callback:function(){
				callback();
			}
		});
	}
}


//交运
$('#dodispatch').click(function() {
		var count = $('.result').length;
		var i = 0;
		layer.msg('上传中,请稍等', {
			icon: 16,
			time: 40000
		});
		$(".result").queue('ajaxRequests', function() {
			if ($(this).attr('result') == 'Success') {
				return false;
			}
			$(this).find('.message2').html(" 执行中,请不要关闭页面！");
			var values = $(this).find('form').serialize();
			var obj = this;
			$.ajax({
				url: global.baseUrl + "carrier/carrieroperate/dodispatchajax",
				data: values,
				type: 'post',
				success: function(response) {
					var result = JSON.parse(response);
					if (result.error == 1) {
						$(obj).find('.message2').html(result.msg);
					} else if (result.error == 0) {
						$(obj).attr('result', 'Success')
						$(obj).find('.message2').html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
						createSuccessDiv2(obj);
					}
					i++;
					checkRequestStatus(i, count, '操作已全部完成,错误原因请查看处理结果');
				},
				error: function(XMLHttpRequest, textStatus) {
					$(obj).find('.message2').html('网络不稳定.请求失败,请重试');
					i++;
					checkRequestStatus(i, count, '操作已全部完成,错误原因请查看处理结果');
				}
			})
		})
		$(".result").dequeue("ajaxRequests");
	})
	//获取跟踪号
$('#gettrackingno').click(function() {
		var count = $('.result').length;
		var i = 0;
		layer.msg('上传中,请稍等', {
			icon: 16,
			time: 40000
		});
		$(".result").queue('ajaxRequests', function() {
			if ($(this).attr('result') == 'Success') {
				return false;
			}
			$(this).find('.message2').html(" 执行中,请不要关闭页面！");
			var values = $(this).find('form').serialize();
			var obj = this;
			$.ajax({
				url: global.baseUrl + "carrier/carrieroperate/gettrackingnoajax",
				data: values,
				type: 'post',
				success: function(response) {
					var result = JSON.parse(response);
					if (result.error == 1) {
						$(obj).find('.message2').html(result.msg);
					} else if (result.error == 0) {
						$(obj).attr('result', 'Success')
						$(obj).find('.message2').html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
						createSuccessDiv2(obj);
					}
					i++;
					checkRequestStatus(i, count, '操作已全部完成,错误原因请查看处理结果');
				},
				error: function(XMLHttpRequest, textStatus) {
					$(obj).find('.message2').html('网络不稳定.请求失败,请重试');
					i++;
					checkRequestStatus(i, count, '操作已全部完成,错误原因请查看处理结果');
				}
			})

		})
		$(".result").dequeue("ajaxRequests");
	})
	//取消
$('#cancelorderno').click(function() {
		var count = $('.result').length;
		var i = 0;
		layer.msg('上传中,请稍等', {
			icon: 16,
			time: 40000
		});
		$(".result").queue('ajaxRequests', function() {
			if ($(this).attr('result') == 'Success') {
				return false;
			}
			$(this).find('.message2').html(" 执行中,请不要关闭页面！");
			var values = $(this).find('form').serialize();
			var obj = this;
			$.ajax({
				url: global.baseUrl + "carrier/carrieroperate/cancelordernoajax",
				data: values,
				type: 'post',
				success: function(response) {
					var result = JSON.parse(response);
					if (result.error == 1) {
						$(obj).find('.message2').html(result.msg);
					} else if (result.error == 0) {
						$(obj).attr('result', 'Success')
						$(obj).find('.message2').html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.msg + '</label>');
						createSuccessDiv2(obj);
					}
					i++;
					checkRequestStatus(i, count, '操作已全部完成,已取消订单将进入[待上传],错误原因请查看处理结果');
				},
				error: function(XMLHttpRequest, textStatus) {
					$(obj).find('.message2').html('网络不稳定.请求失败,请重试');
					i++;
					checkRequestStatus(i, count, '操作已全部完成,已取消订单将进入[待上传],错误原因请查看处理结果');
				}
			})

		})
		$(".result").dequeue("ajaxRequests");
	})
	//确认发货完成
$('#finishedOrder').click(function() {
	var count = $('.result').length;
	var i = 0;
	layer.msg('上传中,请稍等', {
		icon: 16,
		time: 40000
	});
	$(".result").queue('ajaxRequests', function() {
		if ($(this).attr('result') == 'Success') {
			return false;
		}
		$(this).find('.message2').html(" 执行中,请不要关闭页面！");
		var values = $(this).find('form').find('input[name="order_id"]').val();
		var obj = this;
		$.ajax({
			url: submitOrderUrl,
			data: 'order_id=' + values,
			type: 'get',
			success: function(response) {
				var result = JSON.parse(response);
				if (result.code == 'fail') {
					$(obj).find('.message2').html(result.message);
				} else if (result.code == 'ok') {
					$(obj).attr('result', 'Success')
					$(obj).find('.message2').html('<label class="orderUploadSuccess"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span>' + result.message + '</label>');
					createSuccessDiv2(obj);
				}
				i++;
				checkRequestStatus(i, count, '操作已全部完成,错误原因请查看处理结果');
			},
			error: function(XMLHttpRequest, textStatus) {
				$(obj).find('.message2').html('网络不稳定.请求失败,请重试');
				i++;
				checkRequestStatus(i, count, '操作已全部完成,错误原因请查看处理结果');
			}
		})

	})
	$(".result").dequeue("ajaxRequests");
})
$(function() {
	$.initQtip();
	$('form').find('input,select').attr('class', 'form-control');
	$('form').find('input[type="checkbox"]').attr('class', 'checkbox');
	layer.config({
		skin: 'layer-ext-moon', //一旦设定，所有弹层风格都采用此主题。
		extend: ['skin/mono/style.css'], //加载新皮肤
	});
})

$(function() {
		//全选功能
		$("#ck_all").click(function() {
			var state = $(this).prop("checked");
			$(".ck").prop("checked", state);
		})

	})
	//批量打印
function print_all() {
	var orders = "";
	if ($('.ck:checked').length == 0) {
		alert('请选择需要打印的运输服务');
		return false;
	}
	$(".ck:checked").each(function(a, me) {
		orders = $(me).attr("ids");
		ems = $(me).attr("ems");
		window.open(global.baseUrl + "carrier/carrieroperate/doprint2?orders=" + orders + "&ems=" + ems);
	})
}

function print_one(ids, ems) {
	if (ids != "") {
		window.open(global.baseUrl + "carrier/carrieroperate/doprint2?orders=" + ids + "&ems=" + ems);
	} else {
		alert("无法找到该服务下的订单");
	}

}

function changeicon(me, changeAll) {
	$(me).hasClass('glyphicon-minus') ? $(me).attr('class', 'glyphicon glyphicon-plus') : $(me).attr('class', 'glyphicon glyphicon-minus');
	if (typeof(changeAll) != 'undefined') {
		$('span[data-toggle="collapse"]').attr('class', $(me).attr('class'));
		var status = $(me).hasClass('glyphicon-minus') ? 'show' : 'hide';
		$('.collapse').collapse(status);
		return false;
	}
}

function set_print(ids, ems, obj) {
	$.ajax({
		type: "GET",
		url: global.baseUrl + "carrier/carrieroperate/setprint?orders=" + ids + "&ems=" + ems,
		success: function(msg) {
			if (msg == "OK") {
				layer.msg('操作成功，订单已进入其他步骤', {
					icon: 6,
					time: 2000
				});
				$(obj).attr("disabled", "disabled");
				refreshNums();
			} else {
				layer.msg('操作失败' + msg, {
					icon: 5,
					time: 2000
				});
				refreshNums();
			}
		}
	});
}

function print_saitu_all() {
	var orders = "";
	if ($('.ck:checked').length == 0) {
		alert('请选择需要打印的运输服务');
		return false;
	}
	$(".ck:checked").each(function(a, me) {
		orders = $(me).attr("ids");
		ems = $(me).attr("ems");
		window.open(global.baseUrl + "carrier/carrieroperate/doprint-saitu?orders=" + orders + "&ems=" + ems);
	})
}

function print_saitu_one(ids, ems) {
	if (ids != "") {
		window.open(global.baseUrl + "carrier/carrieroperate/doprint-saitu?orders=" + ids + "&ems=" + ems);
	} else {
		alert("无法找到该服务下的订单");
	}
}