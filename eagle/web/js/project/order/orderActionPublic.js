/**
 * 订单相关操作公共js文件
 */

/**
 * 订单备注添加修改
 */
/*************************************************订单备注添加修改start***********************************************************/
function updatedesc(orderid,obj){
	var desc=$(obj).prev();
    var oiid=$(obj).attr('oiid');
	var html="<textarea name='desc' style='width:200xp;height:60px'>"+desc.text()+"</textarea><input type='button' onclick='ajaxdesc(this)' value='修改' oiid='"+oiid+"'>";	
    desc.html(html);
    $(obj).toggle();
}
function ajaxdesc(obj){
	 var obj=$(obj);
	 var desc=$(obj).prev().val();
	 var oiid=$(obj).attr('oiid');
	  $.post(global.baseUrl+'order/aliexpressorder/ajaxdesc',{desc:desc,oiid:oiid},function(r){
		  retArray=$.parseJSON(r);
		  if(retArray['result']){
		      obj.parent().next().toggle();
		      var html="<font color='red'>"+desc+"</font> <span id='showresult' style='background:yellow;'>"+retArray['message']+"</span>"
		      obj.parent().html(html);
		      setTimeout("showresult()",3000);
		  }else{
		      alert(retArray['message']);
		  }
	  })
}
function showresult(){
  $('#showresult').remove();
}


/*************************************************搜索***********************************************************/
//高级搜索
function mutisearch(){
	var status = $('.mutisearch').is(':hidden');
	if(status == true){
		//未展开
		$('.mutisearch').show();
		$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
		$('#showsearch').val('1');
		return false;
	}else{
		$('.mutisearch').hide();
		$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
		$('#showsearch').val('0');
		return false;
	}
	
}

//重置
function cleform(){
	$(':input','#searchForm').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
}

/*************************************************展开 收起***********************************************************/

//展开，收缩订单商品
function spreadorder(obj,id){
	if(typeof(id)=='undefined'){
		//未传参数进入，全部展开或收缩
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.xiangqing').hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.xiangqing').show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}else{
		//有传订单ID进入，处理单个订单相应的详情
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.'+id).hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.'+id).show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}
}

/*************************************************物流操作***********************************************************/
//上传至物流商
function uploadSubmitNew(delivery) {
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	
	$event = $.confirmBox('确认将选中的订单上传至物流商？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderid = $(obj).val();
			var form = $("#formline-"+orderid).find('form');
			$message = $('#message-'+orderid);
			$message.html("订单"+orderid+"执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/get-data?delivery="+delivery,
				data: $(form).serialize(),
				type: 'post',
				success: function(response) {
					var result = JSON.parse(response);
					if (result.error) {
						 //失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+"上传失败！"+result.msg+'</div>');
					 }else{
						 //成功
							$('.line-'+orderid).remove();//移除订单行
							$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'上传成功！'+result.msg+'</div>');
					 } 
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});
	});
};

//交运
function dodispatch() {
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行交运操作？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderid = $(obj).val();
			$message = $('#message-'+orderid);
			$message.html("订单"+orderid+"执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/dodispatchajax",
				data: {
					id:orderid,
				},
				type: 'post',
				success: function(response) {
					var result = JSON.parse(response);
					if (result.error) {
						 //失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+"交运失败！"+result.msg+'</div>');
					} else {
						 //成功
						$('.line-'+orderid).remove();//移除订单行
						$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'交运成功！'+result.msg+'</div>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});
	});
};
//重新上传
function moveToUpload(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行重新上传操作？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderid = $(obj).val();
			$message = $('#message-'+orderid);
			$message.html("订单"+orderid+"执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/ajax-move-order-to-upload",
				data: {
					id:orderid,
				},
				type: 'post',
				success: function(response) {
					if (response) {
						 //成功
						$('.line-'+orderid).remove();//移除订单行
						$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'重新上传成功，请到待上传重新上传！</div>');
					} else {
						 //失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'重新上传失败！</div>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});
	});
}
//重新导出
function moveToUpload2(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行重新导出操作？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderid = $(obj).val();
			$message = $('#message-'+orderid);
			$message.html("订单"+orderid+"执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/ajax-move-order-to-upload2",
				data: {
					id:orderid,
				},
				type: 'post',
				success: function(response) {
					if (response) {
						 //成功
						$('.line-'+orderid).remove();//移除订单行
						$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'重新导出操作成功！请到未导出重新导出！</div>');
					} else {
						 //失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'重新导出操作失败！</div>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});
	});
}
//重新分配
function moveToUpload3(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行重新分配操作？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderid = $(obj).val();
			$message = $('#message-'+orderid);
			$message.html("订单"+orderid+"执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "delivery/order/ajax-move-order-to-upload3",
				data: {
					id:orderid,
				},
				type: 'post',
				success: function(response) {
					if (response) {
						 //成功
						$('.line-'+orderid).remove();//移除订单行
						$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'重新分配操作成功，请到未分配重新分配跟踪号！</div>');
					} else {
						 //失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'重新分配操作失败！</div>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});
	});
}
//获取跟踪号
function getTrackNo(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$event = $.confirmBox('确认将选中的订单进行获取跟踪号操作？');
	$event.then(function(){
		var allRequests = [];
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderid = $(obj).val();
			$message = $('#message-'+orderid);
			$message.html("订单"+orderid+"执行中,请不要关闭页面！");
			allRequests.push($.ajax({
				url: global.baseUrl + "carrier/carrieroperate/gettrackingnoajax",
				data: {
					id:orderid,
				},
				type: 'post',
				success: function(response) {
//					$.maskLayer(false);
					var result = JSON.parse(response);
					if (result.error == 1) {
						//失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+"获取跟踪号失败！"+result.msg+'</div>');
					} else if (result.error == 0) {
						 //成功
						$('.line-'+orderid).remove();//移除订单行
						$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'获取跟踪号成功！'+result.msg+'</div>');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});
	});
};
//分配跟踪号
function setTrackNum(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
		$event = $.confirmBox('确认将选中的订单进行分配跟踪号操作？');
		$event.then(function(){
			var allRequests = [];
			$.maskLayer(true);
			$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
			$(".ck:checked").each(function() {
				var obj = this;
				var orderid = $(obj).val();
				$message = $('#message-'+orderid);
				$message.html("订单"+orderid+"执行中,请不要关闭页面！");
				allRequests.push($.ajax({
				        type : 'post',
				        cache : 'false',
				        //dataType:'json',
				        data : {
				        	orderid:orderid,
				        },
						url: global.baseUrl +'delivery/order/set-track-no-to-order',
				        success:function(response) {
				        	 var r = $.parseJSON(response);
							 if(r.success){
								 //成功
								$('.line-'+orderid).remove();//移除订单行
								$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">'+r.message+'</div>');//页面添加成功信息
							 }else{
								$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">'+r.message+'</div>'); 
							 } 
				        },
						error: function(XMLHttpRequest, textStatus) {
							$.maskLayer(false);
							$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
						}
					}));
			});
			$.when.apply($, allRequests).then(function() {
				$.maskLayer(false);
			});
		});
	
};

/*************************************************确认发货完成***********************************************************/

function setFinished(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	
	var is_cancel_int = 0;
	$(".ck:checked").each(function(){
		var obj = this;
		if($(obj).attr('is_cancel') == 1){
			is_cancel_int++;
		}
	});
	
	var prompt_str = '确认发货完成？';
	
	if(is_cancel_int > 0){
		prompt_str = '勾选的订单存在'+is_cancel_int+'张已取消，是否继续确认发货完成？';
	}
	
	$event = $.confirmBox(prompt_str);
	$event.then(function(){
		var allRequests = [];
		//遮罩
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderids = [];
			var orderid = $(obj).val();
			orderids.push(orderid);
			$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">执行中,请不要关闭页面!</div>'); 
			allRequests.push($.ajax({
				url: global.baseUrl + "carrier/carrierprocess/setfinished",
				data: {
					orderids:orderids,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.success){
						 //成功
						$('.line-'+orderid).remove();//移除订单行
						$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">'+r.message+'</div>');//页面添加成功信息
					 }else{
						$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">'+r.message+'</div>'); 
					 } 
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});	
	});
}
/*************************************************打印物流单***********************************************************/
$(function(){
	$('select[name=shipmethod]').change(function(){
		$('#shipmethod').selectVal($(this).val());
		$('#searchForm').submit();
	});
});
function doprint(type, list_mode){
	if(type==""){
		$.alert('请选择您的打印方式','info');
		return false;
    }
	if(($("#shipmethod").val() == '') && ($("#shipmethod option").size() > 2) && (type == 'custom')){
		$.alertBox('<p class="text-warn">请先选择运输服务！</p>');
		return false;
	}
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	var Url = '';
	var url_params = '';
	switch(type){
	case 'api':Url = global.baseUrl + "carrier/carrierprocess/doprintapi";break;
	case 'custom':Url = global.baseUrl + "carrier/carrierprocess/doprintcustom";break;
	case 'gaofang':Url = global.baseUrl + "carrier/carrierprocess/doprint";break;
	case 'integrationlabel':Url = global.baseUrl + "carrier/carrierprocess/doprint-integration-label";break;
	case 'picking_product_sum':Url = global.baseUrl + "delivery/order/sku-order-printlist";url_params='&type=1';break;
	case 'picking_product_order_sum':Url = global.baseUrl + "delivery/order/sku-order-printlist";url_params='&type=2';break;
	case 'printDistribution_new':Url = global.baseUrl + "delivery/order/sku-order-printlist";url_params='&type=3';break;
//	case 'dhlinvoice':Url = global.baseUrl + "delivery/order/dhl-invoice-print";break;
	case 'dhlinvoice':Url = global.baseUrl + "delivery/order/dhl-invoice-img-print";break;
	case 'thermal_picking_label':Url = global.baseUrl + "carrier/carrierprocess/thermal-picking-print";break;
	case 'invoiced_label':Url = global.baseUrl + "order/order/order-invoice";break;
	case 'jumia_invoice':Url = global.baseUrl + "carrier/carrierprocess/invoice-doprint";break;
	}
	if(Url == ''){
		$.alertBox('<p class="text-warn">不能识别的打印方式！</p>');
		return false;
	}
	var orderids = '';
	$(".ck:checked").each(function(){
		var orderid = $(this).val();
		
		if(type=='invoiced_label'){
			if(orderids==''){
				orderids=orderid;
			}else{
				orderids+=','+orderid;
			}
		}else{
			orderids += orderid + ',';
		}
	});
	
	if(type == 'invoiced_label'){
		window.open(Url+'?order_id='+orderids+'&random='+getRandom(100)+url_params);
	}else if(type == 'jumia_invoice'){
		window.open(Url+'?order_ids='+orderids+'&random='+getRandom(100)+url_params);
	}else{
		window.open(Url+'?orders='+orderids+'&random='+getRandom(100)+url_params);
	}
	
	switch(type){
	case 'custom':
	case 'gaofang':
	case 'integrationlabel':
		var event = $.confirmBox("是否将打印的订单标记为已打印？");
		event.then(function(){
				 $.maskLayer(true);
				 $.post('/carrier/carrier/carrier-print-confirm',{orders:orderids},function(result){
					 var event = $.alert(result,'success');
					 event.then(function(){
						 if(list_mode == undefined){
							 location.reload();
						 }else{
							 $.maskLayer(false);
							 deliveryImplantOmsPublic(list_mode);
						 }
					},function(){
					  $.maskLayer(false);
					});
				});
			},function(){
				$.maskLayer(false);
			});
		break;
	case 'picking_product_sum':
	case 'picking_product_order_sum':
	case 'thermal_picking_label':
		var event = $.confirmBox("是否将打印的订单标记为已打印？");
		event.then(function(){
				 $.maskLayer(true);
				 $.post('/delivery/order/picking-print-confirm',{orders:orderids},function(result){
					 var event = $.alert(result,'success');
					 event.then(function(){
						 if(list_mode == undefined){
							 location.reload();
						 }else{
							 $.maskLayer(false);
							 deliveryImplantOmsPublic(list_mode);
						 }
					},function(){
					  $.maskLayer(false);
					});
				});
			},function(){
				$.maskLayer(false);
			});
		break;
	}
	
}
/*************************************************导出物流表格***********************************************************/
function changeExcelCarrier($excelCarrierCode){
//	$('select[name=default_carrier_code]').selectVal($excelCarrierCode);
//	$('#searchForm').submit();
	$('#default_carrier_code').val($excelCarrierCode);
	
	deliveryImplantOmsPublic();
}
function exportExcel($excelCarrierCode){
	if($excelCarrierCode.trim() == ''){
    	$.alertBox('<p class="text-warn">请先选择需要导出订单的物流!</p>');
		return false;
    }
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	var orderids = '';
	$(".ck:checked").each(function(){
		var orderid = $(this).val();
		orderids += orderid + ',';
	});
	$name = $('select[name=excelCarriers]').find('option:selected').text();
	var Url=global.baseUrl +'delivery/order/export-excel-file?';
	$event = $.confirmBox('<h4 class="text-center row">物流：'+$name+'格式表格导出</h4>');
	$event.then(function(){
		Url+= "excelCarrierCode="+$excelCarrierCode;
		Url+= "&orderid="+orderids;
		window.open(Url);
		location.reload();
	});
}
/*************************************************标记为已完成***********************************************************/
function signcomplete(){
	var event = $.confirmBox('确认标记为已完成？');
	event.then(function(){
		var allRequests = [];
		//遮罩
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderids = [];
			var orderid = $(obj).val();
			orderids.push(orderid);
			$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">执行中,请不要关闭页面!</div>'); 
			allRequests.push($.ajax({
				url: "/order/order/signcomplete",
				data: {
					orderids:orderids,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 //成功
							$('.line-'+orderid).remove();//移除订单行
							$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+'标记为已完成操作成功！订移动到已完成。</div>');//页面添加成功信息
					 }else{
						 //失败
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">订单'+orderid+"标记为已完成操作失败！"+r.message+'</div>'); 
					 } 
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});	
	})
	
	
}
/*************************************************匹配发货仓库和运输服务***********************************************************/
function matchshipping(reset){
	var event = $.confirmBox('确认匹配发货仓库和运输服务？');
	event.then(function(){
		var allRequests = [];
		//遮罩
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderids = [];
			var orderid = $(obj).val();
			orderids.push(orderid);
			$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">执行中,请不要关闭页面!</div>'); 
			allRequests.push($.ajax({
				url: global.baseUrl +"carrier/default/matchshipping",
				data: {
					orderids:orderids,reset:reset
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.success){
						 //成功
							$('.line-'+orderid).remove();//移除订单行
							$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">' + r.message+ '</div>');//页面添加成功信息
					 }else{
						 //失败 location.reload();
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">' + r.message+ '</div>'); 
					 } 
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
					return false;
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});	
	})
}
/*************************************************批量修改报关信息***********************************************************/
//批量修改报关信息
function edit_customs_info(){
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	$.openModal('/delivery/order/edit-customs-info',{type:'get'},'批量修改报关信息','get');
};

/*************************************************批量修改报关信息保存***********************************************************/
//批量修改报关信息保存
function saveCustomsInfo(obj){
	var checkOrderIds = '';
	var tmpCustomsName = '';
	var tmpCustomsEName = '';
	var tmpCustomsDeclaredValue = '';
	var tmpCustomsweight = '';
	
	var nums = 0;
	if ($.trim($('#customsName').val()).length != 0 ) {
		tmpCustomsName = $.trim($('#customsName').val());nums++;
	}
	if ($.trim($('#customsEName').val()).length != 0 ) {
		tmpCustomsEName = $.trim($('#customsEName').val());nums++;
	}
	if ($.trim($('#customsDeclaredValue').val()).length != 0 ) {
		tmpCustomsDeclaredValue = $.trim($('#customsDeclaredValue').val());nums++;
	}
	if ($.trim($('#customsweight').val()).length != 0 ) {
		tmpCustomsweight = $.trim($('#customsweight').val());nums++;
	}
	if(nums == 0){
		$.alertBox('<p class="text-warn">没有输入任何需要修改的数据！</p>');
		return false;
	}
	$(".ck").each(function(){
		if($(this).is(':checked')){
			checkOrderIds += $(this).val()+',';
			var orderid = $(this).val();
			if(tmpCustomsName != ''){
				$('#formline-'+orderid).find(".form-inline .prod-param-group label").find(".customs_cn").parent().next().val(tmpCustomsName);
				$('#formline-'+orderid).find(".form-inline .prod-param-group [name='Name[]']").val(tmpCustomsName);
			}
			if(tmpCustomsEName != ''){
				$('#formline-'+orderid).find(".form-inline .prod-param-group label").find(".customs_en").parent().next().val(tmpCustomsEName);
				$('#formline-'+orderid).find(".form-inline .prod-param-group [name='EName[]']").val(tmpCustomsEName);
			}
			if(tmpCustomsDeclaredValue != ''){
				$('#formline-'+orderid).find(".form-inline .prod-param-group label").find(".customs_declaredValue").parent().next().val(tmpCustomsDeclaredValue);
				$('#formline-'+orderid).find(".form-inline .prod-param-group [name='DeclaredValue[]']").val(tmpCustomsDeclaredValue);
			}
			if(tmpCustomsweight != ''){
				$('#formline-'+orderid).find(".form-inline .prod-param-group label").find(".customs_weight").parent().next().val(tmpCustomsweight);
				$('#formline-'+orderid).find(".form-inline .prod-param-group [name='weight[]']").val(tmpCustomsweight);
			}
		}
	});
	
	if($("#chk_isEditToSku").is(':checked')){
		$.post(global.baseUrl + "delivery/order/edit-customs-info", {
			orders: checkOrderIds,customsName:tmpCustomsName,customsEName:tmpCustomsEName,customsDeclaredValue:tmpCustomsDeclaredValue,customsweight:tmpCustomsweight
		}, function(result) {
			$e = $.alert(result,'danger');
			$e.then(function(){
				location.reload();
			});
		});
	}else{
		$e = $.alert('批量修改成功','success');
		$e.then(function(){
			$(obj).parent().find('.modal-close').click();
		});
	}
}
/*************************************************导出自定义订单***********************************************************/
function exportorder(val){
	if(val==""){
    	$.alertBox('<p class="text-warn">请选择您的操作！</p>');
		return false;
    }
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
    var idstr='';
	$(".ck:checked").each(function(){
		idstr+=','+$(this).val();
	});
	window.open(global.baseUrl + 'order/excel/export-excel'+'?orderids='+idstr+'&excelmodelid='+val);
}
/*************************************************移到拣货***********************************************************/
function moveToPacking(){
	var event = $.confirmBox('确认移到打包出库？');
	event.then(function(){
		var allRequests = [];
		//遮罩
		$.maskLayer(true);
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$(".ck:checked").each(function() {
			var obj = this;
			var orderids = [];
			var orderid = $(obj).val();
			orderids.push(orderid);
			$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">执行中,请不要关闭页面!</div>'); 
			allRequests.push($.ajax({
				url: global.baseUrl +"delivery/order/movetopacking",
				data: {
					orderids:orderids
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.success){
						 //成功
							$('.line-'+orderid).remove();//移除订单行
							$('#dataline-'+orderid).html('<div class="alert-success" id="message-'+orderid+'" role="alert" style="text-align:left;">' + r.message+ '</div>');//页面添加成功信息
					 }else{
						 //失败 location.reload();
						 $('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">' + r.message+ '</div>'); 
					 } 
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$('#dataline-'+orderid).html('<div class="alert-danger" id="message-'+orderid+'" role="alert" style="text-align:left;">系统错误或网络不稳定,请联系我们!</div>'); 
					return false;
				}
			}));
		});
		$.when.apply($, allRequests).then(function() {
			$.maskLayer(false);
		});	
	})
}
/*************************************************生成拣货单***********************************************************/
function buildDeliveryId(orderids){
		$event = $.confirmBox('确认将选中的订单进行生成拣货单操作？');
		$event.then(function(){
			var warehouseid = $('input[name=warehouse_id]').val();
			var Url=global.baseUrl +'delivery/order/ajax-bulid-delivery-id';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	orderids:orderids,
		        	warehouseid:warehouseid,
		        },
				url: Url,
		        success:function(response) {
		        	 var r = $.parseJSON(response);
		        	bootbox.alert({  
			            buttons: {  
			                ok: {  
			                     label: '确认',  
			                     className: 'btn-myStyle'  
			                 }  
			             },  
			             message: r.message,  
			             callback: function() {  
			                 location.reload();
			             },  
			         });
		        },
				error: function(XMLHttpRequest, textStatus) {
					$.alertBox('<p class="text-warn">系统错误或网络不稳定，请联系我们！</p>');
					$.maskLayer(false);
					return false;
				}
		    });
		});
}
/*************************************************批量操作***********************************************************/
function doactionnew(obj, list_mode){
	if($(obj).val() == undefined){
		val = obj;
	}else{
		val = $(obj).val();
		list_mode = $(obj).attr('data');
	}
	
	if(val == 'autocarrierservice_btn'){
		val = 'autocarrierservice';
	}else if(val == 'changeWHSM_btn'){
		val = 'changeWHSM';
	}else if(val == 'uploadSubmitNew_btn'){
		val = 'uploadSubmitNew';
	}else if(val == 'editCustomsInfo_btn'){
		val = 'editCustomsInfo';
	}else if(val == 'dodispatch_btn'){
		val = 'dodispatch';
	}else if(val == 'moveToUpload_btn'){
		val = 'moveToUpload';
	}else if(val == 'getTrackNo_btn'){
		val = 'getTrackNo';
	}else if(val == 'setFinished_btn'){
		val = 'setFinished';
	}else if(val == 'setTrackNum_btn'){
		val = 'setTrackNum';
	}else if(val == 'uploadAndDispatch_btn'){
		val = 'uploadAndDispatch';
	}else if(val == 'buildDeliveryId_btn'){
		val = 'buildDeliveryId';
	}else if(val == 'scanningtrackingnumber_btn'){
		val = 'scanningtrackingnumber';
	}else if(val == 'addMemo_btn'){
		val = 'addMemo';
	}else{
		$(obj).val('');
	}
	
	var externalV = '';
	if(val == 'ExternalDoprint_0'){
		val = 'ExternalDoprint';
		externalV = 0;
	}else if(val == 'ExternalDoprint_1'){
		val = 'ExternalDoprint';
		externalV = 1;
	}
	
	if(val == 'batchcarrierprint_1'){
		val = 'batchcarrierprint';
		externalV = 1;
	}else if(val == 'batchcarrierprint_0'){
		val = 'batchcarrierprint';
		externalV = 0;
	}
	
	if(val == 'batchpickingprint_1'){
		val = 'batchpickingprint';
		externalV = 1;
	}else if(val == 'batchpickingprint_0'){
		val = 'batchpickingprint';
		externalV = 0;
	}
	
	if((val == 'scanningtrackingnumber')  ){  //|| (val == 'ExternalDoprint')
		if(($("#shipmethod").val() == '') && ($("#shipmethod option").size() > 2)){
			$.alertBox('<p class="text-warn">请先选择运输服务！</p>');
			return false;
		}
	}
	
	if(val==""){
    	$.alertBox('<p class="text-warn">请选择您的操作！</p>');
		return false;
    }
	if($('.ck:checked').length==0){
    	$.alertBox('<p class="text-warn">请选择要操作的订单！</p>');
		return false;
    }
	var orderids =[];
	$(".ck:checked").each(function() {
		var orderid = $(this).val();
		orderids.push(orderid);
	});
	
	switch(val){
		case 'printDistribution'://打印配货单
//			document.a.target="_blank";
//			document.a.action = global.baseUrl +'delivery/order/print-list';
//			document.a.submit();
//			document.a.action="";
			
			$("[name='order-related-additional-operation-form'] > [name='order_id']").val(JSON.stringify(orderids));
			$("[name='order-related-additional-operation-form']").attr("target", "_blank");
			$("[name='order-related-additional-operation-form']").attr("action", global.baseUrl + 'delivery/order/print-list').submit();
			break;
		case 'setFinished'://确认发货完成
			setFinished();
			break;
		case 'outOfStock'://标记缺货将订单移到缺货流程
			var event = $.confirmBox('确认标记缺货？');
			event.then(function(){
				$.post(global.baseUrl + 'order/order/outofstock',{orders:orderids},function(result){
					bootbox.alert({  
			            buttons: {  
			                ok: {  
			                     label: '确认',  
			                     className: 'btn-myStyle'  
			                 }  
			             },  
			             message: result,  
			             callback: function() {  
			                if(list_mode == undefined){
			                	 location.reload();
			 				}else{
			 					deliveryImplantOmsPublic(list_mode);
			 				}
			             },  
			         });
					
				});
			});
			break;
		case 'suspendDelivery'://暂停发货将订单移到暂停发货流程
			var event = $.confirmBox('确认暂停发货？');
			event.then(function(){
				$.post(global.baseUrl + 'order/order/suspenddelivery',{orders:orderids},function(result){
					bootbox.alert({  
			            buttons: {  
			                ok: {  
			                     label: '确认',  
			                     className: 'btn-myStyle'  
			                 }  
			             },  
			             message: result,  
			             callback: function() {  
			            	 if(list_mode == undefined){
			                	 location.reload();
			 				}else{
			 					deliveryImplantOmsPublic(list_mode);
			 				}
			             },  
			         });
					
				});
			});
			break;
		case 'recovery'://恢复发货至原有状态
			var event = $.confirmBox('确认恢复发货？');
			event.then(function(){
				$.post(global.baseUrl + 'order/order/recovery',{orders:orderids},function(result){
					bootbox.alert({  
			            buttons: {  
			                ok: {  
			                     label: '确认',  
			                     className: 'btn-myStyle'  
			                 }  
			             },  
			             message: result,  
			             callback: function() {  
			            	 if(list_mode == undefined){
			                	 location.reload();
			 				}else{
			 					deliveryImplantOmsPublic(list_mode);
			 				}
			             },  
			         });
					
				});
			});
			break;
		case 'repulse_paid':
			var event = $.confirmBox('确认打回已付款？');
			event.then(function(){
				$.post(global.baseUrl + 'order/order/repulse-paid',{orders:orderids},function(result){
					bootbox.alert({  
			            buttons: {  
			                ok: {  
			                     label: '确认',  
			                     className: 'btn-myStyle'  
			                 }  
			             },  
			             message: result,  
			             callback: function() {  
			            	 if(list_mode == undefined){
			                	 location.reload();
			 				}else{
			 					deliveryImplantOmsPublic(list_mode);
			 				}
			             },  
			         });
					
				});
			});
			break;
		case 'moveStatusToOut'://配货完成将订单移到出库流程
			document.a.target="_self";
			document.a.action="/delivery/order/move-order-to-out",
			document.a.submit();
			document.a.action="";
			break;
		case 'signshipped'://通知平台发货，或者虚假发货
//			document.a.target="_blank";
//			document.a.action="/order/order/signshipped";
//			document.a.submit();
//			document.a.action="";
			
			$("[name='order-related-additional-operation-form'] > [name='order_id']").val(JSON.stringify(orderids));
//			$("[name='order-related-additional-operation-form']").attr("target", "_blank");
//			$("[name='order-related-additional-operation-form']").attr("action", '/order/order/signshipped').submit();
			
			$.modal({
				  url:'/order/order/signshipped',
				  method:'post',
				  data:$("[name='order-related-additional-operation-form']").serialize()
				},'虚拟发货',{footer:false,inside:false}).done(function($modal){
					$('.btn.colse_btn_signshipped').click(function(){$modal.close();});
					
					$('.btn.btn-success.save_btn_signshipped').click(function(){
						$formdata = $('#sigshipped_new_form').serialize();
						var Url=global.baseUrl +'order/order/signshippedsubmit';

						$.ajax({
					        type : 'post',
					        cache : 'false',
					        data : $formdata,
					        dataType: 'json',
							url: Url,
					        success:function(response) {
					        	if(response.code == 0){
					        		alert(response.msg);
					        		$modal.close();
					        	}else{
					        		alert(response.msg);
					        	}
					        }
					    });
					});
				}
			);
			
			
			break;
		case 'signcomplete'://标记为已完成，这个动作不会减库存
			signcomplete();
			break;
		case 'changeWHSM'://指定发货仓库和运输服务
		case 'changeWHSM2':	
			OrderCommon.showWarehouseAndShipmentMethodBox(orderids, 1, function(){
				if(list_mode == undefined){
					location.reload();
				}else{
					deliveryImplantOmsPublic(list_mode);
				}
			});	//重新修改订单状态需要把物流的状态也要做调整
			break;
		case 'autocarrierservice'://匹配发货仓库和运输服务
			matchshipping(0);
			break;
		case 'reautocarrierservice'://重新匹配发货仓库和运输服务
			matchshipping(1);
			break;
		case 'uploadSubmitNew'://上传
			uploadSubmitNew(0);
			break;
		case 'uploadAndDispatch'://上传且交运
			uploadSubmitNew(1);
			break;	
		case 'editCustomsInfo'://批量修改报关信息
			OrderCommon.showChangeItemDeclarationInfoBox(orderids, function(){
				if(list_mode == undefined){
					location.reload();
				}else{
					deliveryImplantOmsPublic(list_mode);
				}
			});
			//edit_customs_info();
			break;
		case 'dodispatch'://交运
			dodispatch();
			break;
		case 'moveToUpload'://重新上传
			moveToUpload();
			break;
		case 'moveToUpload2'://重新导出
			moveToUpload2();
			break;
		case 'moveToUpload3'://重新分配
			moveToUpload3();
			break;
		case 'getTrackNo'://获取跟踪号
			getTrackNo();
			break;
		case 'setTrackNum'://分配跟踪号
			setTrackNum();
			break;
		case 'moveToPacking'://移到拣货
			moveToPacking();
			break;
		case 'buildDeliveryId'://生成拣货单
			buildDeliveryId(orderids);
			break;
		case 'completepicking'://完成拣货
			document.a.target="_self";
			document.a.action="/delivery/order/completepicking";
			document.a.submit();
			document.a.action="";
			break;
		case 'reorder':
			OrderCommon.reorder(orderids);
			break;	
		case 'scanningtrackingnumber'://扫描绑定跟踪号
			showScanningTrackingNumberBox(orderids, 1);
			break;
		case 'printGaoqingInvoice'://打印高青发票
			window.open(global.baseUrl + "order/order/orderlist-invoice"+'?orderids='+orderids);
			break;
		case 'ExternalDoprint':
			OrderCommon.ExternalDoprint(orderids, externalV);
			
			var event = $.confirmBox("是否将打印的订单标记为已打印？");
			event.then(function(){
					 $.maskLayer(true);
					 $.post('/carrier/carrier/carrier-print-confirm',{orders:idstr},function(result){
						 var event = $.alert(result,'success');
						 event.then(function(){
							 if(list_mode == undefined){
								 location.reload();
							 }else{
								 $.maskLayer(false);
								 deliveryImplantOmsPublic(list_mode);
							 }
						},function(){
						  $.maskLayer(false);
						});
					});
				},function(){
					$.maskLayer(false);
				});
			
			break;
		case 'batchcarrierprint':
			var tip_str = '';
			if(externalV == 1){
				tip_str = "是否标记面单为已打印？";
			}else{
				tip_str = "是否标记面单为未打印？";
			}
			
			var event = $.confirmBox(tip_str);
			event.then(function(){
					 $.maskLayer(true);
					 
					 tmpidstr='';
					 $.each(orderids,function(index, value){
						if (tmpidstr ==''){
							tmpidstr+= value;
						}else{
							tmpidstr+=','+value;
						}
					 });
					 
					 $.post('/carrier/carrier/carrier-print-confirm',{orders:tmpidstr,printed:externalV},function(result){
						 var event = $.alert(result,'success');
						 event.then(function(){
							 if(list_mode == undefined){
								 location.reload();
							 }else{
								 $.maskLayer(false);
								 deliveryImplantOmsPublic(list_mode);
							 }
						},function(){
						  $.maskLayer(false);
						});
					});
				},function(){
					$.maskLayer(false);
				});
			break;
		case 'batchpickingprint':
			var tip_str = '';
			if(externalV == 1){
				tip_str = "是否标记拣货单为已打印？";
			}else{
				tip_str = "是否标记拣货单为未打印？";
			}
			var event = $.confirmBox(tip_str);
			event.then(function(){
					 $.maskLayer(true);
					 
					 tmpidstr='';
					 $.each(orderids,function(index, value){
						if (tmpidstr ==''){
							tmpidstr+= value;
						}else{
							tmpidstr+=','+value;
						}
					 });
					 
					 $.post('/delivery/order/picking-print-confirm',{orders:tmpidstr,printed:externalV},function(result){
						 var event = $.alert(result,'success');
						 event.then(function(){
							 if(list_mode == undefined){
								 location.reload();
							 }else{
								 $.maskLayer(false);
								 deliveryImplantOmsPublic(list_mode);
							 }
						},function(){
						  $.maskLayer(false);
						});
					});
				},function(){
					$.maskLayer(false);
				});
			break;
		case 'addMemo':
			$.ajax({
				type: "GET",
					dataType: 'html',
					url:'/order/order/show-add-memo-box', 
					data: {orderIdList : orderids },
					success: function (result) {
						bootbox.dialog({
							title: Translator.t("添加备注"),
							className: "order_info", 
							message: result,
							buttons:{
								Ok: {  
									label: Translator.t("保存"),  
									className: "btn-success",  
									callback: function () {
										return batchSaveOrderDesc();
									}
								}, 
								Cancel: {  
									label: Translator.t("关闭"),  
									className: "btn-default",  
									callback: function () {  
									}
								}, 
							}
						});	
					},
					error: function(){
						bootbox.alert("Internal Error");
						return false;
					}
			});
			break;
		case 'addProductUrl':
			$.ajax({
				type: "GET",
				dataType: 'html',
				url:'/order/order/show-product-url-add-box',
				data: {orderIdList : orderids },
				success: function (result) {
					bootbox.dialog({
						title: Translator.t("添加商品链接"),
						className: "order_info",
						message: result,
						buttons:{
							Ok: {
								label: Translator.t("保存"),
								className: "btn-success",
								callback: function () {
									return batchSaveOrderProductUrl();
								}
							},
							Cancel: {
								label: Translator.t("关闭"),
								className: "btn-default",
								callback: function () {
								}
							},
						}
					});
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
			});
			break;
		default:
			return false;
			break;
	}
}

function doactiononenew(val, orderid){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	if(orderid == ""){ bootbox.alert("订单号错误");return false;}
	
	switch(val){
		case 'changeWHSM':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
			break;
		default:
			return false;
			break;
	}
}

//控制标签选项只能单选
function label_check_radio(obj){
	if($(obj).prop("checked") == true){
		$(obj).parent().parent().find('label').find('input').prop("checked",false);
		$(obj).prop("checked",true);
	}else{
		$(obj).prop("checked",false);
	}
}

function deliveryImplantOmsPublic(win_list){
	if(win_list == undefined)
		win_list = 'listplanceanorder';
	
	if(win_list == 'delivery')
		win_list = 'listplanceanorder';
	
	var Url=global.baseUrl +'delivery/order/'+win_list;
	
	$.location.state(Url,'小老板',$("#searchForm").serialize(),0,'post',false);
}

//扫描绑定跟踪号
function showScanningTrackingNumberBox(orderIdList , isRefresh)
{
	var isRefresh = (typeof(arguments[1])=="undefined")?'0':arguments[1];//isRefresh 的默认值
	var handle= $.openModal('/order/order/show-scanning-tracking-number-box',{orderIdList:orderIdList},'订单扫描绑定跟踪号','GET');
	
	handle.done(function(winobj)
	{
		//跟踪号输入框得到焦点
		winobj.find('#code_input').on('focus',function()
		{
			$(this).val('');
		});
		//跟踪号输入框失去焦点
		winobj.find('#code_input').on('blur',function()
		{
			$(this).val('扫描跟踪号条码或手动输入或直接按确定绑定');
		});
		//扫描完跟踪号后，自动匹配对应订单
		winobj.find('#code_input').on('keypress',function(e)
		{
			if(e.which == 13) 
			{
				var code = winobj.find('#code_input');
				var val = code.val().replace(/(^\s*)|(\s*$)/g, "");
				//判断是否已
				$('input[name="trackingnumber[]"][value!=""]').each(function()
				{
					if($(this).val() == val)
					{
						$.alertBox('<p class="text-warn">此跟踪号已扫！</p>');
						code.val('');
					}
				});
				if(code.val() != '')
				{
					$('input[name="trackingnumber[]"][value=""]').each(function()
					{
						$(this).before(val);
						$(this).val(val);
						return false
					});
					code.val('');
				}
			}
		});
		//确定绑定
		winobj.find("#btn_ok").on('click',function()
		{
			$event = $.confirmBox('确认绑定跟踪号操作？');
			$event.then(function()
			{
				if($('input[name="trackingnumber[]"][class="1"][value!=""][value!="-"],[class="0"][value!="-"]').length == 0)
				{
			    	$.alertBox('<p class="text-warn">未存在可绑定的订单！</p>');
					return false;
			    }
				var allRequests = [];
				$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
				$('input[name="trackingnumber[]"][class="1"][value!=""][value!="-"],[class="0"][value!="-"]').each(function()
				{
					
					var obj = this;
					var orderid = $(obj).parent().parent().find('input[name="orderid[]"]').val();
					var trackingnumber = $(obj).val();
					allRequests.push($.ajax(
					{
				        type : 'post',
				        cache : 'false',
				        data : 
				        {
				        	orderid:orderid,
				        	trackingnumber:trackingnumber,
				        },
						url: global.baseUrl +'delivery/order/scanning-tracking-number',
				        success:function(response) 
				        {
				        	 var r = $.parseJSON(response);
				        	 //清除之前的错误信息
				        	 $(obj).parent().parent().find('#lmes').remove();
				        	 //状态信息
				        	 $(obj).parent().parent().find('input[name="status[]"]').before("<span id='lmes'>"+r.message+"</span>");
				        	 //设置成功标志
				        	 if(r.message == '已绑定')
				        		 $(obj).val('-');
				        },
						error: function(XMLHttpRequest, textStatus) 
						{
							$(obj).parent().parent().find('input[name="status[]"]').before("<span id='lmes'>绑定失败</span>");
						},
					}));
				});
				$.when.apply($, allRequests).then(function() 
				{
					//$.maskLayer(false);
				});
			});
		});
		//删除某一行
		winobj.find('a[name="delete[]"]').on('click',function()
		{
			var obj = this;
			$(obj).parent().parent().remove();
			
		});
		//打印面单
		winobj.find('#btn_print').on('click',function()
		{
			if($('input[name="trackingnumber[]"][class="1"][value="-"],[class="0"][value="-"]').length == 0)
			{
		    	$.alertBox('<p class="text-warn">未存在可打印的订单！</p>');
				return false;
		    }

			var Url = global.baseUrl + "carrier/carrierprocess/doprintcustom";
			var orderids = '';
			$('input[name="trackingnumber[]"][value="-"]').each(function()
			{
				var orderid = $(this).parent().parent().find('input[name="orderid[]"]').val();
				orderids += orderid + ',';
			});
			window.open(Url+'?orders='+orderids);
			
			var event = $.confirmBox("是否将打印的订单标记为已打印？");
			event.then(function()
			{
				$.post('/carrier/carrier/carrier-print-confirm',{orders:orderids},function(result)
				{
					var event = $.alert(result,'success');
					event.then(function()
					{
						$('input[name="trackingnumber[]"][value="-"]').each(function()
						{
							//清除之前的错误信息
				        	 $(this).parent().parent().find('#lmes').remove();
				        	 //状态信息
				        	 $(this).parent().parent().find('input[name="status[]"]').before("<span id='lmes'>已打印</span>");
						});
					},
					function()
					{
					  //$.maskLayer(false);
					});
				});
			},
			function()
			{
				//$.maskLayer(false);
			});
		});
		//发票
		winobj.find('#btn_invoice').on('click',function()
		{
			if($('input[name="trackingnumber[]"][value="-"]').length == 0)
			{
		    	$.alertBox('<p class="text-warn">未存在可打印的订单！</p>');
				return false;
		    }

			var Url = global.baseUrl + "order/order/orderlist-invoice";
			var orderids = '';
			$('input[name="trackingnumber[]"][value="-"]').each(function()
			{
				var orderid = $(this).parent().parent().find('input[name="orderid[]"]').val();
				orderids += orderid + ',';
			});
			window.open(Url+'?orderids='+orderids);
		});
		winobj.find("#btn_cancel").on('click',function()
		{			
			winobj.close();       // 关闭当前模态框
//			location.reload();    //刷新界面
			deliveryImplantOmsPublic();
		});
		winobj.find(".modal-close").on('click',function()
		{			
			winobj.close();       // 关闭当前模态框
//			location.reload();    //刷新界面
			deliveryImplantOmsPublic();
		});
	});
}

//扫描分拣包裹
function showScanningListDistributionBox(win_list)
{
	$.modal
	(
		{
		  url:'/delivery/order/show-scanning-list-distribution-box',
		  method:'get',
		  data:{}
		},
		'扫描分拣包裹',
		{footer:false,inside:false}).done(function($modal)
		{
			//延时，并且扫描框获取焦点
			window.setTimeout(function () { 
				$('#code_input1').focus();
			}, 1);
			
			$('#btn_cancel').click(function()
			{
				deliveryImplantOmsPublic(win_list);
			});
		});
}

//扫描逐单发货
function showScanningDeliveryOneBox(win_list)
{
	$.modal
	(
		{
		  url:'/delivery/order/show-scanning-delivery-one-box',
		  method:'get',
		  data:{}
		},
		'扫描逐单发货',
		{footer:false,inside:false}).done(function($modal)
			{
				//延时，并且扫描框获取焦点
				window.setTimeout(function () { 
					$('#code_input').focus();
				}, 1);
				
				$('#btn_cancel').click(function()
				{
					deliveryImplantOmsPublic(win_list);
				});
			});
}

//扫描统一发货
function showScanningDeliveryChooseBox(win_list)
{
	$.modal
	(
		{
		  url:'/delivery/order/show-scanning-delivery-choose-box',
		  method:'get',
		  data:{}
		},
		'扫描统一发货',
		{footer:false,inside:false}).done(function($modal)
		{
			//延时，并且扫描框获取焦点
			window.setTimeout(function () { 
				$('#code_input').focus();
			}, 1);
			
			$('#btn_cancel').click(function()
			{
				deliveryImplantOmsPublic(win_list);
			});
		});
}

//生成随机数
function getRandom(n){
	return Math.floor(Math.random()*n+1)
}

//postpony应用到所有
function PostponyUseall(obj){
	$Height=$(obj).parent('div').parent('div').parent('div').find('input[name=PostponyHeight]').val();
	$Width=$(obj).parent('div').parent('div').parent('div').find('input[name=PostponyWidth]').val();
	$Length=$(obj).parent('div').parent('div').parent('div').find('input[name=PostponyLength]').val();
	$Weight=$(obj).parent('div').parent('div').parent('div').parent('form').find('input[name="postponyweight[]"]').val();
	$SelectWeight=$(obj).parent('div').parent('div').parent('div').parent('form').find('select[name="Selectweight[]"]').val();
	
	$('#carrier-list-table').find('input[name="PostponyLength"]').val($Length);
	$('#carrier-list-table').find('input[name="PostponyHeight"]').val($Height);
	$('#carrier-list-table').find('input[name="PostponyWidth"]').val($Width);
	$('#carrier-list-table').find('input[name="postponyweight[]"]').val($Weight);
	$('#carrier-list-table').find('select[name="Selectweight[]"]').val($SelectWeight);
	
//	$('#carrier-list-table').find('.ck:checked').parent('td').parent('tr').nextUntil('.appatch').each(function(i){
//		$(this).find('input[class="eagle-form-control PostponyLength"]').val($Length);
//		$(this).find('input[class="eagle-form-control PostponyHeight"]').val($Height);
//		$(this).find('input[class="eagle-form-control PostponyWidth"]').val($Width);
//	});
}


function smt_editdomestic(){
	$.modal({
		  url:'/delivery/order/smt-edit-domestic-list',
		  method:'post',
		  data:{}
		},'批量修改国内单号',{footer:false,inside:false}).done(
			function($modal){
				$('.iv-btn.btn-primary.btn-sm.smt').click(function(){
					if ($('#import_text_data_smt').val().length ==0){
						bootbox.alert(Translator.t("无输入文本!"));
						return false;
					}
					
					var smt_str = $('#import_text_data_smt').val();
					$.showLoading();
					$.ajax({
						url: '/delivery/order/smt-edit-domestic-save',//请求路径
						data:{smt_str : smt_str},
						type:'post',
						dataType: 'json',//返回数据的类型
						success: function (result){
							if(result.success == 0){
								$.hideLoading();
								bootbox.alert( "<b class='red-tips'>"+Translator.t(result.msg)+"</b>" );
							}else if(result.success == 1){
								$.hideLoading();
								bootbox.alert( "<b class='red-tips'>"+Translator.t(result.msg)+"</b>" );
							}else{
//								$.hideLoading();
								bootbox.alert( "<b class='green-tips'>"+Translator.t('成功')+"</b>" );
								location.reload();
							}
						},  
						error: function (){
							$.hideLoading();
							bootbox.alert( "<b class='red-tips'>"+Translator.t("后台无返回任何数据,请重试或联系客服")+"</b>" );
						}  
					});
				});
				
				$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
			}
	);
}

function batchSaveOrderDesc(){
	var orderList = [];
	$("textarea[name=order_memo]").each(function(){
		$('.xiangqing.'+$(this).data('order-id')).find('font[color="red"]').html($(this).val());
		orderList.push({"order_id":$(this).data('order-id'),"memo":$(this).val()});
	});
	
	$.ajax({
		type: "POST",
		dataType: 'json',
		url:'/order/order/batch-save-order-desc', 
		data: {orderList : orderList},
		success: function (result) {
			var  tmpMsg ;
			if (result.message){
				tmpMsg = result.message;
			}else{
				tmpMsg = '保存成功！';
				
				//不刷新界面直接修改
				$.each(orderList,function(index,value){
//					console.log(value.order_id);
					
					$('input[name="order_id[]"]').each(function(){
						if(Number(value.order_id) == Number($(this).val())){
//							if(value.memo == ''){
//								$(this).parent().find('.label_memo_custom').parent().hide();
//								$(this).parent().parent().next().find('.div_item_momo').hide();
//							}else{
////								$(this).parent().find('.label_memo_custom').attr('data-content', value.memo);
//								$(this).parent().find('.label_memo_custom').parent().show();
//								
//								$(this).parent().parent().next().find('.div_item_momo').html('订单备注：'+value.memo);
//								$(this).parent().parent().next().find('.div_item_momo').show();
//							}
							
							$('#desc-'+Number(value.order_id)).html(value.memo);
						}
					});
				});
			}
			
//			OrderCommon.SuccessBox(tmpMsg,'order_info');
			return true;
		},
		error: function(){
			bootbox.alert("Internal Error");
			return false;
		}
	});
}


function batchSaveOrderProductUrl(){
	var orderList = [];
	$("textarea[name=order_memo]").each(function(){
		$('.xiangqing.'+$(this).data('order-id')).find('font[color="red"]').html($(this).val());
		orderList.push({"order_id":$(this).data('order-id'),"memo":$(this).val()});
	});

	$.ajax({
		type: "POST",
		dataType: 'json',
		url:'/order/order/batch-save-order-product-url',
		data: {orderList : orderList},
		success: function (result) {
			var  tmpMsg ;
			if (result.message){
				tmpMsg = result.message;
			}else{
				window.location.reload();
				tmpMsg = '保存成功！';

				//不刷新界面直接修改
				$.each(orderList,function(index,value){
//					console.log(value.order_id);

					$('input[name="order_id[]"]').each(function(){
						if(Number(value.order_id) == Number($(this).val())){
//							if(value.memo == ''){
//								$(this).parent().find('.label_memo_custom').parent().hide();
//								$(this).parent().parent().next().find('.div_item_momo').hide();
//							}else{
////								$(this).parent().find('.label_memo_custom').attr('data-content', value.memo);
//								$(this).parent().find('.label_memo_custom').parent().show();
//								
//								$(this).parent().parent().next().find('.div_item_momo').html('订单备注：'+value.memo);
//								$(this).parent().parent().next().find('.div_item_momo').show();
//							}

							//$('#desc-'+Number(value.order_id)).html(value.memo);
						}
					});
				});
			}

//			OrderCommon.SuccessBox(tmpMsg,'order_info');
			return true;
		},
		error: function(){
			bootbox.alert("Internal Error");
			return false;
		}
	});
}