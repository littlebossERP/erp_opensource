manualOrder={
	/**************************  manual order start  *****************************/
	currentBtn:{},
	addManualOrderItem:function(){
		var addHtml = "<tr>"
			+'<td><div class="input-group">'
						+'<input type="text" name="item[sku][]" class="form-control" placeholder="指定商品SKU">'
					     +'<span class="input-group-btn">'
					        +'<button class="btn btn-important btn-sm" type="button" onclick="manualOrder.selectProd(this, \'one\')">指定商品SKU</button>'
					      +'</span>'
					    +'</div></td>'
			+"<td><input class=\"form-control\" type='text' name='item[qty][]' value='1' /></td>"
			+"<td><input class=\"form-control\" type='text' name='item[price][]' value='0'></td>"
			+"<td><a class=\"cursor_pointer text-danger\" onclick=\"manualOrder.delRow(this)\"><span class=\"glyphicon glyphicon-remove-circle\" aria-hidden=\"true\"></span>删除</a></td></tr>";
			$('#order_item_info').append(addHtml);
			
			$('[name="item[sku][]"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'left',required:true});
			$('[name="item[qty][]"]').formValidation({validType:['trim','integer','length[1,255]'],tipPosition:'right',required:true});
			$('[name="item[price][]"]').formValidation({validType:['trim','amount','length[1,255]'],tipPosition:'right',required:true});
			
	},
	
	delRow:function(obj){
		if ($('input[name="item[sku][]"]').length <=1){
			$.alert("必须有一个商品！");
			return false;
		}
		$(obj).parent().parent().remove();
		manualOrder.calcSubTotal();
	},
	
	selectProd : function(BtnObj, select_type){
		manualOrder.currentBtn = BtnObj;
		$.fn.selectProduct.settings = undefined;
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				 if(prodList.length == 0){
					$.alert("指定失败 ,没有 选择商品！");
					return false;
				}else if (select_type != 'one'){
					manualOrder.bathSetProdSku(prodList);
				}else if (prodList.length>1){
					$.alert("指定失败 ,选择了"+prodList.length+"个商品！");
					return false;
				}else{
					manualOrder.setProdSku(prodList[0].sku);
				}
				
			},
		}, select_type);
	},
	
	
	setProdSku:function(sku){
		$(manualOrder.currentBtn ).parents('td').find('input[name="item[sku][]"]').val(sku);
	},
	
	bathSetProdSku:function(prodList){
		var is_set_status = false;
		$.each(prodList, function(key, prod) {
			is_set_status = false;
			//搜索空的行，存在则直接赋值
			$('#order_item_info input[name="item[sku][]"]').each(function(){
				if($(this).val().replace(/(^\s*)|(\s*$)/g, "") == ''){
					$(this).val(prod.sku);
					is_set_status = true;
					return false;
				}
			});
			
			//当没有空行时，则新增一行，再复制
			if(!is_set_status){
				manualOrder.addManualOrderItem();
				
				//搜索空的行，存在则直接赋值
				$('#order_item_info input[name="item[sku][]"]').each(function(){
					if($(this).val().replace(/(^\s*)|(\s*$)/g, "") == ''){
						$(this).val(prod.sku);
						is_set_status = true;
						return;
					}
				});
			}
		});
		//$(manualOrder.currentBtn ).parents('td').find('input[name="item[sku][]"]').val(sku);
	},
	
	calcSubTotal:function(){
		totalPrice = 0;
		$('#order_item_info>tbody>tr').each(function(){
			var orderNum = $(this).find('input[name="item[qty][]"]').val();
			var price    = $(this).find('input[name="item[price][]"]').val();
			if(isNaN(orderNum)){
				orderNum = 1;
			}
			if(isNaN(price)){
				price = 0;
			}
			totalPrice += orderNum * price;
		});
		
		$("#div_order_subtotal").html(totalPrice);
	},
	
	init:function(){
		manualOrder.addManualOrderItem();
		
		$("select[name^='consignee_country_code']").combobox({removeIfInvalid:true});
		
		$(document).on('focusout','input[name="item[qty][]"],input[name="item[price][]"]',function(){
			manualOrder.calcSubTotal();
		});
		
		$('[name="order_source_order_id"]').formValidation({validType:['trim','length[1,50]'],tipPosition:'right',required:true});
		$('[name="consignee"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
		$('[name="consignee_postal_code"]').formValidation({validType:['trim','length[1,50]'],tipPosition:'left',required:true});
		$('[name="consignee_city"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
		$('[name="consignee_province"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
		$('[name="consignee_address_line1"]').formValidation({validType:['trim','length[1,255]'],tipPosition:'right',required:true});
		$('[name="desc"]').formValidation({validType:['trim','length[0,500]'],tipPosition:'bottom',required:false});
		
		$("#importManualOrder").on('click',function(){
			var paltform = $('[name=order_source]').val()

			manualOrder.importManualOrderModal(paltform);

		});
	},
	
	SaveManualOrder:function(){
		var from1 = $('#manual-order-form');
		if (! from1.formValidation('form_validate')){
			//bootbox.alert(Translator.t('录入格式不正确!'));
			return false;
		}
		
		$.ajax({
			url: "/order/order/save-manual-order",
			data: from1.serialize(),
			type: 'post',
			success: function(response){
				 if (response.indexOf('failure')>=0){
					 bootbox.alert(response.substring(response.indexOf('failure')+7));
				 }else{
					
					 bootbox.confirm({
						buttons: {  
							confirm: {  
								label: Translator.t("继续"),  
								className: 'btn-primary'  
							},  
							cancel: {  
								label: Translator.t("关闭"),
								className: 'btn-default'  
							}  
						}, 
						message: Translator.t("创建订单成功， 是否继续？"),
						callback: function(r){
							if(r){
								from1.find("input:text").val('');
								$('[name="desc"]').val('');
								$('#order_item_info>tbody>tr').remove();
								manualOrder.addManualOrderItem();
								$('[name="shipping_cost"]').val('0');
							}else{
								window.close();
							}
						}
					});
				 }
			},
			error: function(XMLHttpRequest, textStatus) {
				$.maskLayer(false);
				$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
			}
		})
	},
	
	/**************************  manual order end  *****************************/
	
	
	importManualOrderModal:function(platform){
		$.modal({
			url:'/order/order/import-manual-order-modal?platform='+platform
		}).then(function($modal){
			$modal.on('modal.action.resolve',function(){
				debugger;
				var btnObj = $(this).find('a[data-event=resolve]');
				btnObj.css("pointer-events","none");
				btnObj.html('上传中');
				
				if($("#import_orders").val()){
					var tmpSelleruserid = $modal.find('[name=selleruserid]').val();
					var tmpSite = $modal.find('[name=order_source_site_id]').val();
					var tmpPlatform = $modal.find('[name=platform]').val();
					if (tmpSelleruserid == undefined || tmpSelleruserid==''){
						$.alert(Translator.t('请指定账号'),'danger');
						return false;
					}
					
					$.ajaxFileUpload({
						 url:'/order/excel/import-manual-order?paltform='+tmpPlatform+'&selleruserid='+tmpSelleruserid+'&site='+tmpSite,
						 fileElementId:'import_orders',
						 type:'post',
						 dataType : 'text',
						 success: function (response){
							 var tagStr = 'ack-failure'; 
							 if (response.indexOf(tagStr)>=0){
								 $.alert(response.substring(response.indexOf(tagStr)+tagStr.length),'danger');
								
							 }else{
								 var tagStr = 'ack-success'; 
								 if (response.indexOf(tagStr)>=0){
									bootbox.alert(response.substring(response.indexOf(tagStr)+tagStr.length), function() {
										setTimeout(function(){
											$modal.close();
										},1000);
									});
									//$.alert(response.substring(response.indexOf(tagStr)+tagStr.length),'success');
								 }else{
									 $.alert(response,'danger');
								 }
							 }
						 },  
						 error: function ( xhr , status , messages ){
							 $.alert(messages,'danger');
						 }  
					});  
				}else{
					$.alert("请添加文件",'danger');
				}
				
				btnObj.removeAttr("style");
				btnObj.html('确定');
			});
		});
	},
	
	
}


/*
$.domReady(function($el) {
	
	'use strict';

	var $document = this;

	$el('.iv-editor').on('editor.ready',function(e,editor,KE){
		console.dir(this)
	});

	$el("#open-modal-test").on('click',function(){
		$.openModal('action1');
		// $.modal({
		// 	url:'action1'
		// })
	});

	$el("#importManualOrder").on('click',function(){

		

	});

	

	

});

*/