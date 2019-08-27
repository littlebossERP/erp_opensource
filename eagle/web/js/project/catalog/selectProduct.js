;(function($) {
	selectProduct_selectData = new Array();
	selectProduct_productData = new Object;
	selectProduct_bootbox = new Object;
	
	var CHKcheckall = function(){
		$("input[type='checkbox'][name=chk_select_product_product_list]").prop("checked", $('#chk_select_product_check_all').prop('checked'));
		if($('#chk_select_product_check_all').prop('checked'))
			$("input[type='checkbox'][name=chk_select_product_product_list]").parents('tr').css("background-color", "rgba(130, 175, 111, 0.2)");
		else
			$("input[type='checkbox'][name=chk_select_product_product_list]").parents('tr').css("background-color", "rgba(0, 0, 0, 0)");
	};
	/**/
	var initProductData = function(){
		$('#select-product-table tr').has('td').each(function(){
			var Obj = new Object;
			Obj['sku'] = $(this).find("textarea[name=select_product_"+"sku"+"]").val();
			Obj['name'] = $(this).find("textarea[name=select_product_"+"name"+"]").val();
			Obj['status'] = $(this).find("input[name=select_product_"+"status"+"]").val();
			Obj['brand_id'] = $(this).find("input[name=select_product_"+"brand_id"+"]").val();
			Obj['supplier_id'] = $(this).find("input[name=select_product_"+"supplier_id"+"]").val();
			Obj['purchase_price'] = $(this).find("input[name=select_product_"+"purchase_price"+"]").val();
			Obj['purchase_by'] = $(this).find("input[name=select_product_"+"purchase_by"+"]").val();
			Obj['prod_weight'] = $(this).find("input[name=select_product_"+"prod_weight"+"]").val();
			Obj['update_time'] = $(this).find("input[name=select_product_"+"update_time"+"]").val();
			Obj['photo_primary'] = $(this).find("img").attr("src");
			Obj['product_id'] = $(this).find("input[name=select_product_"+"product_id"+"]").val();
			
			selectProduct_productData[$(this).find('textarea[name=select_product_'+'sku'+']').val()] = Obj;
		});
	};
	
	var methods = {
        open: function(options, select_type) {
			var settings = $.fn.selectProduct.settings;//$this.data('selectProduct');
			if(typeof(settings) == 'undefined') {
				var defaults = {
					afterSelectProduct: function(){} , 
					
				};
				settings = $.extend({}, defaults, options);
				$.fn.selectProduct.settings = settings;
				//$this.data('selectProduct', settings);
			}
			var excludeSkuList = '';
			if ($.fn.selectProduct.settings.excludeSkuList !='undefined'){
				excludeSkuList = $.fn.selectProduct.settings.excludeSkuList;
			}
			$.ajax({
				type: "GET",
				dataType: 'html',
				url: '/catalog/product/select_product',
				data:{excludeSkuList:excludeSkuList},
				success: function (html) {
					selectProduct_bootbox = bootbox.dialog({
						className : "select_product",
						title: Translator.t('请选择产品'),//
						message: html,
						//show : false,
						buttons:{
							Cancel: {  
								label: Translator.t("返回"),  
								className: "btn-default btn-select-product-return",  
								callback : function(){
									selectProduct_bootbox = undefined;
									$.fn.selectProduct.settings = undefined;
								}
							}, 
							OK: {  
								label: Translator.t("保存"),  
								className: "btn-primary btn-select-product-save",  
								callback : function(){
									initProductData();
									var newDatas = new Array();
									selectProduct_bootbox.find('input[name=chk_select_product_product_list]:checkbox').each(function(){
										if ($(this).prop('checked')){
											var sku = $(this).parents('tr').find('textarea[name=select_product_sku]').val()
											newDatas.push(selectProduct_productData[sku] );
										}
									});
									selectProduct_selectData = newDatas;
									$.fn.selectProduct.settings.afterSelectProduct(selectProduct_selectData);
								}
							}, 
						}
					});
					
					//单选时，去掉全选功能，lrq20170926
					if(select_type == 'one'){
						$('#chk_select_product_check_all').css('display', 'none');
					}
					
					$('#select-product-table').on('click' , 'tr' , function(){
						var checked = true;
						var eve = event.srcElement ? event.srcElement : event.target;
						if(eve.type == 'checkbox'){
							checked = eve.checked;
						}
						else{
							checked = $(this).find("input[type='checkbox'][name=chk_select_product_product_list]").first().is(":checked");
							if(checked != undefined)
								checked = !checked;
						}

						if(select_type == 'one'){
							//只能单选
							$("input[type='checkbox'][name=chk_select_product_product_list]").prop("checked", false);
							$('tr').parent().find('tr').css("background-color", "rgba(0, 0, 0, 0)");
						}
						
						$(this).find("input[type='checkbox'][name=chk_select_product_product_list]").first().prop("checked", checked);
						if(checked)
							$(this).css("background-color", "rgba(130, 175, 111, 0.2)");
						else{
							$(this).css("background-color", "rgba(0, 0, 0, 0)");
						}
					});
					
					$('#select-product-table').on('click' , '#chk_select_product_check_all' , function(){
						CHKcheckall();
					});
					
					
					
					
					
					$("#btn_select_product_search").click(function(){
						//if ($.trim($('#select_product_search').val())== "") return;
						var excludeSkuList = '';
						if ($.fn.selectProduct.settings.excludeSkuList !='undefined'){
							excludeSkuList = $.fn.selectProduct.settings.excludeSkuList;
						}
						var class_id = $('#search_class_id').attr('class_id');
						$("#select-product-table").queryAjaxPage({
							'txt_search':$('#select_product_search').val(),
							'excludeSkuList':excludeSkuList,
							'class_id':class_id,
						});
					});
					
					$(".changeClass").click(function(){
						//if ($.trim($('#select_product_search').val())== "") return;
						var excludeSkuList = '';
						if ($.fn.selectProduct.settings.excludeSkuList !='undefined'){
							excludeSkuList = $.fn.selectProduct.settings.excludeSkuList;
						}
						var class_id = $(this).attr('class_id');
						var name = $(this).html().replace("|-", "");
						if(name != ''){
							$('#search_class_id').html(name);
							$('#search_class_id').attr('class_id', class_id);
						}
						
						$("#select-product-table").queryAjaxPage({
							'txt_search':$('#select_product_search').val(),
							'excludeSkuList':excludeSkuList,
							'class_id':class_id,
						});
					});
					
					$('#select_product_search').keypress(function(){
						if(event.keyCode == "13")  
							$("#btn_select_product_search").click();
					});
					
					//selectProduct_bootbox.modal('show');
					//selectProduct_bootbox.find('.bootbox-body').html(html);
				} , 
				error : function(){} , 
				
			});
			return ;
		},
    };
	$.fn.selectProduct = function(options) {
		var method = arguments[0];
        if(methods[method]) {
            method = methods[method];
            arguments = Array.prototype.slice.call(arguments, 1);
        } else if( typeof(method) == 'object' || !method ) {
            method = methods.init;
			
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.pluginName' );
            return this;
        }
 
        return method.apply(this, arguments);
	};
})(jQuery);