
/**
 +------------------------------------------------------------------------------
 * 生成1688采购单的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		purchase
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if(typeof purchase1688Create === 'undefined') purchase1688Create = new Object();
purchase1688Create.list = {
	'init': function() {
		$('#select_1688_user').unbind('change').change(function(){
			//更新下拉收货地址
			purchase1688Create.list.setRecAddSelect();
		});
		
		//切换tab
		$('#tab1').on('click',function(){
			$('#purchase_1688_tab2').css("display","none");
			$('#purchase_1688_tab1').css("display","block");
		});
		$('#tab2').on('click',function(){
			$('#purchase_1688_tab1').css("display","none");
			$('#purchase_1688_tab2').css("display","block");
		});
		
		//更新下拉收货地址
		purchase1688Create.list.setRecAddSelect();
	},
		
	//显示获取1688商品界面
	'showMatching1688Product' : function(obj, purchase_item_id, $sku, $index){
		//判断是否已关联1688供应商
		var al1688_user_id = $('input[name="al1688_user_id"]').val();
		if(al1688_user_id == undefined || al1688_user_id == '' || al1688_user_id == 0){
			bootbox.alert("请先关联1688供应商！");
			return false;
		}
		
		bootbox.dialog({
			title: "获取1688商品 -- 匹配本地SKU "+ $sku,
			className: "matching_1688_product",
			message: $('#dialog_matching_1688_product').html(),
			buttons: {
				Ok: {
					label: "保存",
					className: "btn-success",
					callback: function(){
						$.showLoading();
						var radio = $('.matching_1688_product input[name="radio_1688_product"]:checked');
						var sku_1688 = radio.attr('data-sku');
						var sku_image_url = radio.attr('data-sku_image_url');
						var attributes = radio.attr('data-attributes');
						var spec_id = radio.attr('data-spec_id');
						var product_id = $('.matching_1688_product input[name="product_id_1688"]').val();
						var name = $('.matching_1688_product input[name="name_1688"]').val();
						var pro_link = $('.matching_1688_product input[name="pro_link"]').val();
						//保存配对关系
						$.ajax({
							type: "POST",
							url: "/purchase/purchase/save1688-matching",
							data: {
								'purchase_item_id' : purchase_item_id,
								'sku_1688' : sku_1688,
								'sku_image_url' : sku_image_url,
								'attributes' : attributes,
								'product_id' : product_id,
								'name' : name,
								'pro_link' : pro_link,
								'spec_id' : spec_id,
							},
							dataType: "json",
							success : function(res){
								$.hideLoading();
								if(res.success){
									//是否是否已存在配对的，是则删除
									var pro_table = $('table[name="'+ (product_id + '_' + sku_1688) +'"]').parent().html('<a href="javascript: " onclick="purchase1688Create.list.showMatching1688Product(this, \''+ purchase_item_id +'\', \''+ $sku +'\', \''+ $index +'\')">请先点击这里匹配1688商品</a>');
									//替换1688商品信息
									$html = 
										'<table style="width: 100%; " class="purchase_prods" name="'+ (product_id + '_' + sku_1688) +'">'+
											'<tr>'+
												'<td style="width: 68px; text-align: center; ">'+
													'<div style="border: 1px solid #ccc; width: 62px; height: 62px">'+
														'<img class="prod_img" style="max-width:100%; max-height: 100%; width:auto; height: auto; " src="'+ sku_image_url +'" data-toggle="popover" data-content="<img width=\'250px\' src=\''+ sku_image_url +'\'>" data-html="true" data-trigger="hover">'+
													'</div>'+
												'</td>'+
												'<td><a target="_blank" href="'+ pro_link +'">' + name + '</a></br>' + attributes + '</td>'+
											'</tr>'+
										'</table>'+
										'<a href="javascript: " onclick="purchase1688Create.list.showMatching1688Product(this, \''+ purchase_item_id +'\', \''+ $sku +'\', \''+ $index +'\')">更换1688商品</a>'+
										'<input type="hidden" name="prod['+ $index +'][product_id]" value="'+ product_id +'">'+
										'<input type="hidden" name="prod['+ $index +'][sku_1688]" value="'+ sku_1688 +'">'+
										'<input type="hidden" name="prod['+ $index +'][spec_id]" value="'+ spec_id +'">';
									$(obj).parent().html($html);
									
									$('.prod_img').popover();
								}
								else{
									bootbox.alert(res.msg);
								}
							},
							error: function(){
								$.hideLoading();
								bootbox.alert(Translator.t('数据传输错误！'));
								return false;
							}
						});
					}
				},
				Cancel: {
					label: "取消",
					className: "btn-default",
					callback: function(){
						
					}
				}
			}
		});
	},
	
	//获取1688商品信息
	'Get1688Product' : function(){
		var pro_url = $('.matching_1688_product input[name="get_url_1688_product"]').val();
		var aliId = $('.create_1688_dialog #select_1688_user').val();
		
		$.showLoading();
		$.ajax({
			type: "POST",
			url: "/purchase/purchase/get1688-product",
			data: {'pro_url': pro_url, 'aliId' : aliId},
			dataType: "json",
			success : function(res){
				$.hideLoading();
				if(res.success){
					//表头
					$('.matching_1688_product .tr_1688_product_title1').hide();
					$('.matching_1688_product .tr_1688_product_title2').show();
					//商品信息
					var item_html = 
						'<td style="width: 65px; text-align: center; border: 1px solid #ddd; ">'+
							'<div style="border: 1px solid #ccc; width: 62px; height: 62px">'+
								'<img style="max-width: 100%; max-height: 100%; width: auto; height: auto; " src="'+ res.product.image_url +'">'+
							'</div>'+
						'</td>'+
						'<td style="border: 1px solid #ddd; width: 40%; ">'+ res.product.name +'</td>'+
						'<td style="border: 1px solid #ddd; width: 40%; ">'+
							'<input type="hidden" name="product_id_1688" value="'+ res.product.product_id +'" />'+
							'<input type="hidden" name="name_1688" value="'+ res.product.name +'" />'+
							'<input type="hidden" name="pro_link" value="'+ res.product.pro_link +'" />';
					//商品属性明细
					for(var $key in res.product.skus){
						var style = "";
						if($key == 0){
							style = "checked";
						}
						if(res.product.skus[$key].sku != undefined){
							item_html += 
								'<label style="margin-right: 10px; ">'+
	                            	'<input type="radio" name="radio_1688_product" data-sku="'+ res.product.skus[$key].sku +'" data-sku_image_url="'+ res.product.skus[$key].sku_image_url +'" data-attributes="'+ res.product.skus[$key].attributes +'" data-spec_id="'+ res.product.skus[$key].spec_id +'" '+ style +'>'+
									res.product.skus[$key].attributes+
		                        '</label>';
						}
					}
					$('.matching_1688_product tr[name="tr_1688_product_items"]').html(item_html);
				}
				else{
					bootbox.alert(res.msg);
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
	
	//显示获取1688店铺信息
	'showMatching1688Supplier' : function(obj){
		bootbox.dialog({
			title: "获取1688店铺 ",
			className: "matching_1688_supplier",
			message: $('#dialog_matching_1688_supplier').html(),
			buttons: {
				Ok: {
					label: "确定",
					className: "btn-success",
					callback: function(){
						$.showLoading();
						//获取1688店铺信息
						var supplier_url = $('.matching_1688_supplier input[name="get_url_1688_supplier"]').val();
						var supplier_id = $('.create_1688_dialog input[name="supplier_id"]').val();
						var aliId = $('.create_1688_dialog #select_1688_user').val();
						$.ajax({
							type: "POST",
							url: "/purchase/purchase/get1688-supplier",
							data: {
								'aliId' : aliId,
								'supplier_url' : supplier_url,
								'supplier_id' : supplier_id,
							},
							dataType: "json",
							success : function(res){
								$.hideLoading();
								if(res.success){
									//替换1688店铺信息
									$html = 
										'<input type="hidden" name="al1688_user_id" value="'+ res.userId +'" />'+
									    '<a target="_blank" href="'+ res.al1688_url +'" style="margin-right: 20px;">'+ res.companyName +'</a>'+
									    '<a href="javascript: " onclick="purchase1688Create.list.showMatching1688Supplier(this)" >替换</a>';
									$(obj).parent().html($html);
								}
								else{
									bootbox.alert(res.msg);
								}
							},
							error: function(){
								$.hideLoading();
								bootbox.alert(Translator.t('数据传输错误！'));
								return false;
							}
						});
					}
				},
				Cancel: {
					label: "取消",
					className: "btn-default",
					callback: function(){
						
					}
				}
			}
		});
	},
	
	//创建1688采购单
	'create1688Purchase' : function(){
		//判断是否已关联1688供应商
		var al1688_user_id = $('input[name="al1688_user_id"]').val();
		if(al1688_user_id == undefined || al1688_user_id == '' || al1688_user_id == 0){
			bootbox.alert("请先关联1688供应商！");
			return false;
		}
		
		$.showLoading();
		$.ajax({
			type: 'post',
			url: '/purchase/purchase/create1688-purchase',
			data: $('#create_1688_form').serialize(),
			dataType: 'json',
			success:function(result){
				$.hideLoading();
				bootbox.dialog({
					closeButton: false,
					className: "operation_result", 
					buttons: {
						OK: {  
							label: Translator.t("确认"),  
							className: "btn-primary",  
							callback: function () {
								if (result.success){
									window.location.reload();
								}
							}  
						}, 
					},
					message:  (result.msg == '') ? Translator.t('下单成功， 1688订单号：' + result.order_id_1688) : result.msg,
				});
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			},
		});
	},
	
	//获取历史收货地址
	'get1688Add' : function(){
		$.showLoading();
		var aliId = $('.create_1688_dialog #select_1688_user').val();
		$.ajax({
			type: 'post',
			url: '/purchase/purchase/get1688-add',
			data: {'aliId': aliId},
			dataType: 'json',
			success:function(res){
				$.hideLoading();
				if(res.success){
					//更新下拉收货地址
					purchase1688Create.list.setRecAddSelect();
				}
				else{
					bootbox.alert(res.msg);
					return false;
				}
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			},
		});
	},
	
	//更新收货地址下拉
	'setRecAddSelect' : function(){
		$.showLoading();
		var aliId = $('.create_1688_dialog #select_1688_user').val();
		$.ajax({
			type: 'POST',
			url: '/purchase/purchase/get-receive-add',
			data: {'aliId': aliId},
			dataType: 'json',
			success:function(res){
				$.hideLoading();
				if(res.success){
					var html = '';
					var val = '';
					for(var key in res.adds){
						if(key != 'remove'){
							if(key == 'common'){
								html += '<option value="'+ key +'" >(本地) '+ res.adds[key].key +'</option>';
							}else{
								html += '<option value="'+ key +'" >(1688) '+ res.adds[key].key +'</option>';
							}
							if(val == ''){
								val = key;
							}
						}
					}
					$('.create_1688_dialog #select_receive_add').html(html);
					$('.create_1688_dialog #select_receive_add').val(val);
				}
				else{
					$('.create_1688_dialog #select_receive_add').html('');
					$('.create_1688_dialog #select_receive_add').val('');
				}
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			},
		});
	},
	
	//显示设置本地地址
	'showSetRecAdd' : function(){
		var aliId = $('.create_1688_dialog #select_1688_user').val();
		$ali_name = $('.create_1688_dialog #select_1688_user').find("option:selected").text();
		$('input[name="set_rec_add_aliId"]').val(aliId);
		
		$.ajax({
			type: 'POST',
			url: '/purchase/purchase/get-receive-add',
			data: {'aliId': aliId},
			dataType: 'json',
			success:function(res){
				$.hideLoading();
				if(res.success){
					for(var key in res.adds){
						if(key == 'common'){
							$('input[name="fullName"]').attr('value', res.adds[key].fullName);
							$('input[name="mobile"]').attr('value', res.adds[key].mobile);
							$('input[name="phone"]').attr('value', res.adds[key].phone);
							$('input[name="postCode"]').attr('value', res.adds[key].postCode);
							$('input[name="province"]').attr('value', res.adds[key].provinceText);
							$('input[name="city"]').attr('value', res.adds[key].cityText);
							$('input[name="area"]').attr('value', res.adds[key].areaText);
							$('input[name="town"]').attr('value', res.adds[key].townText);
							$('input[name="address"]').attr('value', res.adds[key].address);
							break;
						}
					}
				}
					
				bootbox.dialog({
					title: "设置"+ $ali_name +"本地收货地址 ",
					className: "set_rec_add",
					message: $('#dialog_set_rec_add').html(),
					buttons: {
						Ok: {
							label: "保存",
							className: "btn-success",
							callback: function(){
								$.showLoading();
								$.ajax({
									type: 'post',
									url: '/purchase/purchase/save-rec-add',
									data: $('.set_rec_add #set_rec_add_form').serialize(),
									dataType: 'json',
									success:function(res){
										$.hideLoading();
										if(res.success){
											//更新下拉收货地址
											purchase1688Create.list.setRecAddSelect();
										}
										else{
											bootbox.alert(res.msg);
											return false;
										}
									},
									error:function(){
										$.hideLoading();
										bootbox.alert("出现错误，请联系客服求助...");
										return false;
									},
								});
							}
						},
						Cancel: {
							label: "取消",
							className: "btn-default",
							callback: function(){
								
							}
						}
					}
				});
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			},
		});
		
	},
	
	'binding1688Order' : function(){
		var purchase_id = $('#purchase_1688_tab2 input[name="purchase_id"]').val();
		var aliId = $('#purchase_1688_tab2 #select_1688_user_2').val();
		var order_id = $('#purchase_1688_tab2 #binding_1688_order_id').val();
		
		$.showLoading();
		$.ajax({
			type: 'POST',
			url: '/purchase/purchase/binding1688-order',
			data: {'purchase_id': purchase_id, 'aliId': aliId, 'order_id': order_id},
			dataType: 'json',
			success:function(res){
				$.hideLoading();
				if(res.success){
					window.location.reload();
				}
				else{
					bootbox.alert(res.msg);
				}
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			},
		});
	}
	
	
}
