/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof productList === 'undefined')  productList = new Object();

productList.list={
		existingImages:'',
		existtingAliasList:'',
		supplierNames:'',
		brandNames:'',
		fieldNames:'',
		fieldValues:'',
		tagNames:'',
		newAliasExisting:'',
		platformAccount:'',
		existingMatchTable:'',
		init:function(){
			$('input[name=chk_product_all]:checkbox').click(function(){
				$("input[type='checkbox'][name=chk_product_info]").prop("checked", $(this).prop('checked'));
			});
			
			$('.form-horizontal:first [name="product_type"]').change(function(){
				$('.form-horizontal:first').submit();
			});
			
			$('.form-horizontal:first [name="search_type"]').change(function(){
				if($(this).val() != 'undefined' && $(this).val() != ''){
					$('[name="txt_search"]').attr('placeholder','输入产品'+ $(this).find("option:selected").text());
				}
				else{
					$('[name="txt_search"]').attr('placeholder','输入产品sku或名称或描述字段');
				}
			});
			
			$("select[name='txt_tag'],select[name='txt_brand'],select[name='txt_supplier']").combobox({removeIfInvalid:true});
			
			
			$('#btn_import_sellertool_bundle_product').click(function(){
				if (typeof(importFile) != 'undefined'){
					form_url = "/catalog/product/sellertool-excel2-bundle-product";
					//template_url = "/template/商品导入格式-普通商品.xls";
					template_url ='';
					importFile.showImportModal(
						Translator.t('请选择导入的excel文件') , 
						form_url , 
						template_url , 
						function(result){
							var ErrorMsg = "";
							if(typeof(result)=='object'){
								if(typeof(result.error)!=='undefined'){
									ErrorMsg += result.error;
								}else{
									for (var i in result){
										if (typeof(result[i].insert)!=='undefined' && result[i].insert == false){
											ErrorMsg += "<b style='color:red'>第"+i+"行：</b><br>";
											var rtnNum = result[i].length;
											for(var j in result[i]){
												var val1 = result[i][j];
												
												if(j!=='sku' && j!=='insert')
													ErrorMsg += "&nbsp;&nbsp;&nbsp;&nbsp;"+result[i][j]+"<br>";
											}
										}
									}
								}
							}else{
								ErrorMsg += result;
							}
							if (ErrorMsg != ""){
								ErrorMsg= "<div style='height: 600px;overflow: auto;'>"+ErrorMsg+"</div>";
								bootbox.alert(ErrorMsg);
							}else{
								bootbox.alert(Translator.t('导入成功'));
							}
							
					});
				}else{
					bootbox.alert(Translator.t("没有引入相应的文件!"));
				}
				
			});
			
			$('#btn_import_sellertool_product').click(function(){
				if (typeof(importFile) != 'undefined'){
					form_url = "/catalog/product/sellertool-excel2-product";
					//template_url = "/template/商品导入格式-普通商品.xls";
					template_url ='';
					importFile.showImportModal(
						Translator.t('请选择导入的excel文件') , 
						form_url , 
						template_url , 
						function(result){
							var ErrorMsg = "";
							if(typeof(result)=='object'){
								if(typeof(result.error)!=='undefined'){
									ErrorMsg += result.error;
								}else{
									for (var i in result){
										if (typeof(result[i].insert)!=='undefined' && result[i].insert == false){
											ErrorMsg += "<b style='color:red'>第"+i+"行：</b><br>";
											var rtnNum = result[i].length;
											for(var j in result[i]){
												var val1 = result[i][j];
												
												if(j!=='sku' && j!=='insert')
													ErrorMsg += "&nbsp;&nbsp;&nbsp;&nbsp;"+result[i][j]+"<br>";
											}
										}
									}
								}
							}else{
								ErrorMsg += result;
							}
							if (ErrorMsg != ""){
								ErrorMsg= "<div style='height: 600px;overflow: auto;'>"+ErrorMsg+"</div>";
								bootbox.alert(ErrorMsg);
							}else{
								bootbox.alert(Translator.t('导入成功'));
							}
							
					});
				}else{
					bootbox.alert(Translator.t("没有引入相应的文件!"));
				}
				
			});
			
			$('#btn_create_product').click(function(){
				productList.list.addProduct();
				//productList.list.CreateProductBox();
			});
			
			$('#btn_select_product').click(function(){
				$(this).selectProduct('open',{
					afterSelectProduct:function(dataList){
						alert('import'+dataList.length);
						return false;
					},
				});
			});
			
			$('#batch_del').click(function(){
				productList.list.batchDeleteProduct();
			});
			//商品条码打印
			$('#btn_bcode').click(function(){
				var skulist = new Array();
				var configSkuList = '';
				$('input[name=chk_product_info]:checked').each(function(){
					var list = new Array();
					$pd=$(this).parent().nextAll('td[name=chk_product_name]').html();
					list.push(this.value,$pd);
					skulist.push(list);
				});
				if(skulist.length < 1){
					bootbox.alert(Translator.t('请选择商品！'));
					return false;
				}
				$.modal({
					  url:'/catalog/product/skubarcode',
					  method:'post',
					  data:{skulist :  $.toJSON(skulist) }
					},'打印sku条码',{footer:false,inside:false}).done(function($modal){
						//打印
						$('.btn.btn-primary').click(function(){
							var print_list= new Array();
							$('tr[name=listone]').each(function(){
								var print_list_one = new Array();
								$number=$(this).find('input[name="number"]').val();
								$skul=$(this).find('td[name=sku]').html();
								$skuname=$(this).find('td[name=skuname]').html();
								print_list_one.push($skul,$skuname,$number);
								print_list.push(print_list_one);
							});
							$height=$('select[name="height"]').val();
							$width=$('select[name="width"]').val();
							$url=window.location.host;
							window.open( "http://"+$url+"/catalog/product/sku-barcode-print"+'?width='+$width+'&height='+$height+'&skulist='+$.toJSON(print_list));
						});
						//应用所有
						$('a[name=ApplyToAll]').click(function(){
							$qty=$(this).parent().find('input[name=number]').val();
							$('input[name=number]').each(function(){
								$(this).val($qty);
							});
						});
						//删除
						$('button[name=skuprintdelete]').click(function(){
							$t=$(this).parent().parent().parent().find("tr").size();
							if($t==1)
								$modal.close();
							else
								$(this).parent().parent().remove();
						});
						//取消
						$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
					});
			});
			//合并商品
			$('#batch_merge').click(function(){
				productList.list.batchMergeProduct();
			});
			//移动商品到指定分类
			$('.changeClass').click(function(){
				var class_id = $(this).attr('class_id');
				var name = $(this).html().replace("|-", "");

				var skulist = new Array();
				$('input[name=chk_product_info]:checked').each(function(){
					skulist.push(this.value);
				});
				
				if (skulist.length == 0 ){
					bootbox.alert(Translator.t('请选择需要移入分类的商品'));
					return false;
				}
				
				bootbox.confirm("<b>"+Translator.t('确定把商品移入分类"'+ name +'"？')+"</b>",function(r){
					if (!r) return;
					$.showLoading();
					$.ajax({
						type: "POST",
						url:'/catalog/product/change-classifica', 
						data:{class_id: class_id, skulist: $.toJSON(skulist) },
						dataType:"json",
						success: function (result) {
							$.hideLoading();
							if (result.success){
								window.location.reload();
							}else{
								bootbox.alert(result.msg);
							}
						},
						error :function () {
							$.hideLoading();
							bootbox.alert(Translator.t('数据传输错误！'));
							return false;
						}
					});
				});
			});
		} ,
		
		checkAliaExisting : function (skus) {
			$.ajax({
				type: "GET",
				url:'/catalog/product/check-alias-is-existing-sku', 
				data:{skus:skus},
				dataType:'json',
				async : false,
				success: function (result) {
					if(result.existing){
						for(key in result.existingList)
						{
							if(productList.list.newAliasExisting=='')
								productList.list.newAliasExisting = key;
							else
								productList.list.newAliasExisting += ","+key;
						}
					}
					return;
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
		},
		
		initAutocomplete:function(){
			$("select[name^='ProductSuppliers[supplier_id][']").combobox();
			$("select[name='Product[brand_id]']").combobox();
			$("input[name='Tag[tag_name][]']").autocomplete({source:productList.list.tagNames});
			$("th input[name^='children[config_field']").autocomplete({source:productList.list.fieldNames});
			//$("td input[name^='children[config_field_value']").autocomplete({source:productList.list.fieldNames});
		
		},
		
		initFormValidateInput:function(){
			$('#Product_sku').formValidation({validType:['trim','length[1,255]'],tipPosition:'left',required:true});
			$('#Product_name,#Product_prod_name_ch,#Product_prod_name_en,#Product_declaration_ch,#Product_declaration_en').formValidation({validType:['trim','length[2,255]'],tipPosition:'left',required:true});
			$('#Product_declaration_value_currency').formValidation({validType:['trim','length[1,3]'],tipPosition:'left'});
			$('#Product_declaration_value').formValidation({validType:['trim','amount'],tipPosition:'left'});
			$('#Product_sku').formValidation({tipPosition:'left',required:true});
			$("input[name='ProductAliases[alias_sku][]']").formValidation({validType:['trim','length[1,255]'],tipPosition:'left',required:true});
			$("input[name='ProductAliases[pack][]']").formValidation({validType:['trim','length[1,11]','integer'],tipPosition:'left',required:true});
			
			$('#Product_weight,#Product_length , #Product_width,#Product_height ').formValidation({tipPosition:'left',validType:['trim','amount']});
			$('#Product_declaration_code').formValidation({validType:['trim','length[0,100]'],tipPosition:'left'});
			
			//$('#Product_weight,#Product_length , #Product_width,#Product_height ,#Product_declaration_value').keyup
		},
		
		initPhotosSetting:function(){
			$('div[role="image-uploader-container"]').batchImagesUploader({
						localImageUploadOn : true,   
						fromOtherImageLibOn: true , 
						imagesMaxNum : 6,
						fileMaxSize : 1024 , 		
						fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
						maxHeight : 100, 		maxWidth : 100,
						initImages : productList.list.existingImages, 
						fileName: 'product_photo_file',
						onUploadFinish : function(imagesData , errorInfo){
					    	//debugger
						},
						
						onDelete : function(data){
				//			debugger
							$('.select_photo').each(function(){
								if ($(this).parent('div').attr('upload-index') == undefined){
									$(this).removeClass('select_photo');
								}
							});
							
							
						}
						
					});
			
			$('div[role=image-uploader-container] .image-item').click(function(){
				$('div[role=image-uploader-container] .image-item a').removeClass('select_photo');
				$(this).find('a').addClass('select_photo');
			});
			
			if ($('.select_photo img').length ==0){
				$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
			}
		},
		
		CreateProductBox:function(title , message){
			bootbox.dialog({
				className : "product_info",
				title: title,//Translator.t("添加商品")
				message: message,
				buttons:{
					Cancel: {  
						label: Translator.t("返回"),  
						className: "btn-default",  
						callback: function () {  
						}
					}, 
					OK: {  
						label: Translator.t("保存"),  
						className: "btn-primary",  
						callback: function () {
							var rtn = productList.list.SaveProduct();        
							if(rtn){
								window.location.reload();
								return true;
							}
							else return false;
						}  
					}, 
				}
			});
			productList.list.initFormValidateInput();
			productList.list.initPhotosSetting();
			productList.list.initAutocomplete();
		},
		
		
		
		
		
		delete_form_group:function(obj){
			$(obj).parents(".form-group").remove();
		},
		
		delete_commission_group:function(obj){
			$(obj).parents("tr").remove();
		},
		
		showDetailView:function(obj){
			if ($(obj).hasClass('glyphicon-plus')){
				$(obj).removeClass('glyphicon-plus');
				$(obj).addClass('glyphicon-minus');
			}else if($(obj).hasClass('glyphicon-minus')){
				$(obj).removeClass('glyphicon-minus');
				$(obj).addClass('glyphicon-plus');
			}
			
			$(obj).parents('tr').next('tr').find('div').slideToggle("normal",function(){
				
			});
			
		} , 
		
		
		/**
		 +----------------------------------------------------------
		 * 添加商品的属性集
		 +----------------------------------------------------------
		 * @access	public
		 +----------------------------------------------------------
		 * @param	k	属性名
		 * @param 	v	属性值
		 * @return  	是否设置成功
		 +----------------------------------------------------------
		 * log		name	date					note
		 * @author	ouss	2014/02/27 13:33:00		初始化
		 +----------------------------------------------------------
		**/
		addOtherAttrHtml : function (k, v, type) {
			//if (type == 'C') return true;
			var thisObj = $('#catalog-product-list-attributes-panel');
			var addContent = $($('#other_attribute').html());
			thisObj.append(addContent);
			$("#catalog-product-list-attributes-panel input[name*=other_attr_]").formValidation({tipPosition:'left',validType:['trim','prod_field'],required:true});
			
		},
		
		//添加商品的佣金比例    lrq20170707
		addCommissionPer : function (k, v, type) {
			//if (type == 'C') return true;
			var thisObj = $('#catalog-product-list-commission-table');
			var addContent = '<tr>'+ $('#commission_per').find('tr').html() +"</tr>";
			thisObj.append(addContent);
			$('#catalog-product-list-commission-table input[name="commission_value[]"]').formValidation({tipPosition:'left',validType:['trim','amount']});
			
		},
		
		
		
		addTagHtml:function(obj){
			var thisObj = $(obj).parents(".form-group");
			//alert($(obj).text());
			var addContent = "<div class=\"form-group\" style=\"float:left;width:200px;vertical-align:middle;margin:0px;\">"+
			"<label for=\"Product_tag\" class=\"col-sm-3 control-label\"  style=\"float:left;width:10%;padding:6px 0px;\">"+
			"<a onclick=\"productList.list.delete_form_group(this)\"><span class=\"glyphicon glyphicon-remove-circle\"  class=\"text-danger\" aria-hidden=\"true\"></span></a>"+
			"</label>"+
			"<input type=\"text\" class=\"form-control\" name=\"Tag[tag_name][]\" value=\"\" style=\"float:left;width:85%;\"/>"+
			"</div>";
			thisObj.after(addContent);
			//$("input[name*=Tag]").formValidation({tipPosition:'left',required:true});
			$("input[name='Tag[tag_name][]']").autocomplete({source:productList.list.tagNames});
			
		},
		
		deleteProduct:function(sku_encode,sku_html,type){
			var tip ='';
			if(type=='L')
				tip = Translator.t("该商品为变参产品的子产品。删除后，该产品与其父产品的变参关系也会删除。");
			if(type=='C')
				tip = Translator.t("该商品为变参产品。删除后，该产品包含的子产品会自动转化成普通商品。");
			bootbox.confirm(Translator.t("确定删除商品 ")+"<b style='color:red;'>"+sku_html+"</b>?<br><br><span style='color:blue;'>"+tip+"</span>",function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/catalog/product/delete',
					data: {sku:sku_encode} , 
					success: function (result) {
						//$.hideLoading();
						if (result.success){
							$.hideLoading();
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: Translator.t("成功删除商品!"),
								callback: function() {
									window.location.reload();
								}, 
							});
						}
						else{
							$.hideLoading();
							bootbox.alert(result.message);
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert(result.message);
					}
				});
				
			});
			
		},
		
		addProduct:function(){
			$.showLoading();
			$.ajax({
				type: "GET",
				url:'/catalog/product/get_create_product_form?tt=add&type=S', 
				success: function (result) {
					$.hideLoading();
					productList.list.CreateProductBox(
						Translator.t("添加商品") ,
						result
					);
					return true;
					
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
			
		},
		
		viewProduct:function(sku){
			$.showLoading();
			$.ajax({
				type: "GET",
				url:'/catalog/product/view-product', 
				data:{sku:sku},
				success: function (result) {
					$.hideLoading();
					bootbox.dialog({
						className : "product_info",
						title: Translator.t("查看商品"),//
						message: result,
						buttons:{
							Cancel: {  
								label: Translator.t("返回"),  
								className: "btn-default",  
								callback: function () {  
								}
							}, 
						}
					});
					//productList.list.initPhotosSetting();
					$('.lnk-del-img').css('display','none');
					return true;
					
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
			
			
		},

		editProduct:function(sku,type){
			$.showLoading();
			$.ajax({
				type: "GET",
				url:'/catalog/product/get_create_product_form?tt=edit&type='+type, 
				data:{sku:sku},
				success: function (result) {
					$.hideLoading();
					if(type=='C' || type=='B'){
						productList.ConfigAndBundle.CreateProductBox(
							Translator.t("修改商品") ,
							result
						);
					}
					else{
						productList.list.CreateProductBox(
							Translator.t("修改商品") ,
							result
						);
					}
					return true;
					
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
		},
		
		copyProduct:function(sku){
			$.showLoading();
			$.ajax({
				type: "GET",
				url:'/catalog/product/get_create_product_form?sku='+sku+'&tt=copy', 
				success: function (result) {
					$.hideLoading();
					
					productList.list.CreateProductBox(
						Translator.t("复制商品") ,
						result
					);
					return true;
					
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
			
		},
		
		updateProductStatus:function(sku, status){
			$.showLoading();
			$.ajax({
				type: "POST",
				url:'/catalog/product/update-status', 
				data:{sku : sku , status :status },
				dataType:"json",
				success: function (result) {
					if (result.success){
						$.hideLoading();
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("更新成功!"),
							callback: function() {
								window.location.reload();
							}, 
						});
					}else{
						$.hideLoading();
						bootbox.alert(Translator.t("更新失败"));
					}
					return true;
					
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
			
		},
		
		BatchUpdateProductStatus:function(status){
			var skulist = new Array();
			$('input[name=chk_product_info]:checked').each(function(){
				skulist.push(this.value);
			});
			
			if (skulist.length == 0 ){
				bootbox.alert(Translator.t('请选择需要批量修改状态的商品'));
				return false;
			}
			
			if (skulist.length>0){
				bootbox.confirm(Translator.t("是否批量修改状态"),function(r){
					if (! r) return;
					$.showLoading();
					$.ajax({
						type: "POST",
						url:'/catalog/product/batch-update-status', 
						data:{skulist :  $.toJSON(skulist) , status :status },
						dataType:"json",
						success: function (result) {
							if (result.success){
								$.hideLoading();
								bootbox.alert({
									buttons: {
										ok: {
											label: 'OK',
											className: 'btn-primary'
										}
									},
									message: result.message,
									callback: function() {
										window.location.reload();
									}, 
								});
							}else{
								$.hideLoading();
								bootbox.alert(result.message);
							}
							return true;
						},
						error :function () {
							$.hideLoading();
							bootbox.alert(Translator.t('数据传输错误！'));
							return false;
						}
					});
				});
				
				
			}
			
		},
		
		batchDeleteProduct:function(){
			var skulist = new Array();
			var configSkuList = '';
			$('input[name=chk_product_info]:checked').each(function(){
				skulist.push(this.value);
				if($(this).attr('prodType')=='C'){
					var thisSku = $(this).parents("tr").find("td:eq(2)").html();
					configSkuList+=(configSkuList=='')?thisSku:'，'+thisSku;
				}
			});
			if(configSkuList!==''){
				configSkuList="<br><br><span style='color:blue'>"+Translator.t("含有变参产品(")+"<b style='color:red'>"+configSkuList+"</b>"+Translator.t(")删除它们的话，而其下的子产品会各自变成普通产品")+"</span><br><br>";
			}
			
			if (skulist.length == 0 ){
				bootbox.alert(Translator.t('请选择需要删除的商品'));
				return false;
			}
			
			if (skulist.length>0){
				bootbox.confirm("<b>"+Translator.t("确认批量删除?")+"</b>"+configSkuList,function(r){
					if (! r) return;
					$.showLoading();
					$.ajax({
						type: "POST",
						url:'/catalog/product/batch-delete-product', 
						data:{skulist :  $.toJSON(skulist) },
						dataType:"json",
						success: function (result) {
							if (result.success){
								$.hideLoading();
								bootbox.alert({
									buttons: {
										ok: {
											label: 'OK',
											className: 'btn-primary'
										}
									},
									message: Translator.t("删除成功!"),
									callback: function() {
										window.location.reload();
									}, 
								});
							}else{
								$.hideLoading();
								bootbox.alert(result.message);
							}
							return true;	
						},
						error :function () {
							$.hideLoading();
							bootbox.alert(Translator.t('数据传输错误！'));
							return false;
						}
					});
				});
			}
		},
		
		SaveProduct:function(){
			$.showLoading();
			var Obj = this;
			if (! $('#product-create-form').formValidation('form_validate')){
				$.hideLoading();
				bootbox.alert(Translator.t('录入格式不正确!'));
				return false;
			}
			
			if (productList.list.checkSupplierRepeatOrNot()){
				$.hideLoading();
				bootbox.alert(Translator.t('供应商重复!'));
				return false; 
			}
			
			if(productList.list.setOtherAttr()){
				
			}else{
				
			}
			productList.list.setPicAttr();
			
			if(this.checkMatchingExist())
				return false;

			//检测别名是否存在自身对应的配对关系
			if(productList.list.existingMatchTable != ''){
				$.hideLoading();
				bootbox.confirm(
					Translator.t("<span style='color:red'>已存在别名关系，是否继续覆盖？</span><br><br>"+productList.list.existingMatchTable),
					function(r){
						if(r){
							productList.list.ajaxSave();
							return true;
						}
						else{
							/*$('.product_info').modal('hide');
							$('.product_info').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});*/
						}
					}
				);
			}else{
				$.hideLoading();
				productList.list.ajaxSave();
			}
		},
		
		ajaxSave : function(){
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/catalog/product/save_product',
				data: $('#product-create-form').serialize(),
				success: function (result) {
					if (typeof(result)=='object'){
						var resultAll = true;
						var Message = '';
						for (key in result){
							var keyvalue = result[key];
							if (keyvalue==true||keyvalue=='已经存在，信息更新成功！'){
								Message += key +':'+ '保存成功' +'<br/>';
							}
							else {
								resultAll = false;
								Message += key +':'+ keyvalue +'<br/>';
							}
						}
						$.hideLoading();
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Message,
							callback: function() {
								if(resultAll===true){
									$('.product_info').modal('hide');
									$('.product_info').on('hidden.bs.modal', '.modal', function(event) {
										$(this).removeData('bs.modal');
									});
									window.location.reload();
								}
							}
						});
						return resultAll;
					}else{
						$.hideLoading();
						if (result === true){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: '保存成功',
								callback: function() {
									$('.product_info').modal('hide');
									$('.product_info').on('hidden.bs.modal', '.modal', function(event) {
										$(this).removeData('bs.modal');
									});
									window.location.reload();
								}
							});
							return true;
						}else {
							//catalog.page.showErrorMessage(result);
							bootbox.alert(result);
							return false;
						}
					}
					/*
					$.messager.progress('close');
					product.page.isProgressing = false;
					*/
					//return false; //test kh
				},
				error :function () {
					$.hideLoading();
					bootbox.alert('保存失败！请联系客服提交问题。');
					return false;
					/*
					catalog.page.showMessage('错误', '错误：保存失败！');
					$.messager.progress('close');
					product.page.isProgressing = false;
					*/
				}
			});
		},
		
		/**
		 +----------------------------------------------------------
		 * 设置商品的主副图片信息
		 +----------------------------------------------------------
		 * @access	public
		 +----------------------------------------------------------
		 * @return  是否设置成功
		 +----------------------------------------------------------
		 * log		name	date					note
		 * @author	ouss	2014/02/27 13:33:00		初始化
		 +----------------------------------------------------------
		**/
		setPicAttr : function (){
			
			if ($('.select_photo img').length ==0){
				$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
			}
			
			var returnVal = false;
			var selectObj = $('.select_photo img');
			if(selectObj.length > 0){
				$('#Product_photo_primary').val($('.select_photo img').attr('src'));
				returnVal = true;
			} else {
				$('#Product_photo_primary').val('');
			}
			
			var photoOthers = new Array();
			$('.image-item a').not('.select_photo').parent('div[upload-index]').each(function(i){
				var tmpSrc = $(this).find('a img').attr('src');
				if ($.inArray(tmpSrc, photoOthers) == -1) {
					photoOthers.push(tmpSrc);
				}
			});
			
			$('#Product_photo_others').val(photoOthers.join('@,@'));
			return returnVal;
		},
		
		
		/**
		 +----------------------------------------------------------
		 * 设置商品的属性集，编辑为字符串
		 +----------------------------------------------------------
		 * @access	public
		 +----------------------------------------------------------
		 * @return  是否设置成功
		 +----------------------------------------------------------
		 * log		name	date					note
		 * @author	ouss	2014/02/27 13:33:00		初始化
		 +----------------------------------------------------------
		**/
		setOtherAttr : function () {
			$('#Product_other_attributes').val('');
			var str = "";
			$("#catalog-product-list-attributes-panel input[name='other_attr_key[]']").each(function(){
				if (str != "") str += ';';
				str += this.value+":"+$(this).parents(".form-group").find("input[name='other_attr_value[]']").val();
			});
			$('#Product_other_attributes').val(str);
			return true;
			/*
			if (product.page.createType=='S'|| product.page.createType=='L'){
				var listAttr = $('#catalog-product-index-attributes-panel').find('div');
				if(listAttr.length > 0){
					var attrArray = new Array();
					var keyArray = new Array();
					for(var i = 0; i < listAttr.length; i++){
						var keyValueItem = $(listAttr[i]);
						var keySelect = $.trim(keyValueItem.find('select:eq(0)').combobox('getText'));
						var valueSelect = $.trim(keyValueItem.find('select:eq(1)').combobox('getText'));
						if (keySelect != '' && valueSelect != '' && $.inArray(keySelect, keyArray) == -1){
							attrArray.push(keySelect + ':' + valueSelect);
							keyArray.push(keySelect);
							keyValueItem.css('border', 'none');
						}else {
							keyValueItem.css('border','1px solid #FF0000');
							catalog.page.showMessage('错误', '设置属性集错误：存在重复值或值为空！');
							return false;
						}
					}
					var attributesTxt = attrArray.join(';');
					if (attributesTxt.length > 255) {
						//catalog.page.showMessage('错误', '属性集字符过长，请删除一个或多个属性集！');
						$('#catalog-product-index-createproduct-form-tab').tabs('select', 3);
						return false;
					}else {
						$('#Product_other_attributes').val(attributesTxt);
					}
				}else {
					$('#Product_other_attributes').val(attributesTxt);
				}
			}
			// if (product.page.createType=='C'){
				// C_productAttr_ShowTo_AttrTab();
			// }
			return true;
			*/
		},
		
		checkSupplierRepeatOrNot:function(){
			var checkSupplier = new Array();
			$('input[name*=supplier_id]').each(function(){
				if ($.trim(this.value) != "")
				checkSupplier.push(this.value);
			});
			
			if (checkSupplier.length == 0) return false;
			var hash = {};
			for(var i in checkSupplier) {
				if(hash[checkSupplier[i]])
					return true;
				hash[checkSupplier[i]] = true;
			}
			
			return false;
		},
		
	
	fillAliasData:function(){
		$.each(productList.list.existtingAliasList,function(i , n){
			var addaliasHtml = "<tr>"
			
			+"<td><input class=\"form-control\" type='hidden' name='ProductAliases[alias_sku][]' value='"+this.alias_sku+"' />"+this.alias_sku+"</td>"
			+"<td><input class=\"form-control\" type='hidden' name='ProductAliases[pack][]' value='"+this.pack+"' />"+this.pack+"</td>"
			+"<td><input class=\"form-control\" type='hidden' name='ProductAliases[platform][]' value='"+this.platform+"'>"+this.platform+"</td>"
			+"<td><input class=\"form-control\" type='hidden' name='ProductAliases[selleruserid][]' value='"+this.selleruserid+"'>"+this.shopname+"</td>"
			+"<td><input class=\"form-control\" type='hidden' name='ProductAliases[comment][]' value='"+this.comment+"'/>"+this.comment+"</td>"
			
			//+"<td>"+this.alias_sku+"</td>"
			//+"<td>"+this.pack+"</td>"
			//+"<td>"+this.forsite+"</td>"
			//+"<td>"+this.comment+"</td>"
			+"<td><a class=\"cursor_pointer text-danger\" onclick=\"productList.list.delRow(this)\"><span class=\"glyphicon glyphicon-remove-circle\" aria-hidden=\"true\"></span></a></td>"
			+"<td style=\"display:none\"><input name='ProductAliases[AliasStatus][]' value='on'/></td></tr>";
			$('#product_alias_table').append(addaliasHtml);
		});
	},
	
	addaliasHtml:function(){
		var addaliasHtml = "<tr>"
			+"<td><input class=\"form-control\" type='text' name='ProductAliases[alias_sku][]' value='' /></td>"
			+"<td><input class=\"form-control\" type='text' name='ProductAliases[pack][]' value='1' /></td>"
		
		//平台下拉
		var td_platform = "<td><select name=\"ProductAliases[platform][]\" class=\"form-control\" onchange=\"productList.list.change_shop(this)\">";
		$.each(productList.list.platformAccount,function(platform, shop){
			if(platform == '所有平台')
				td_platform += "<option value=\"\">"+ platform +"</option>";
			else
				td_platform += "<option value=\""+ platform +"\">"+ platform +"</option>";
		});
		td_platform += '</select><tc>';

		addaliasHtml += td_platform
			+"<td><select name=\"ProductAliases[selleruserid][]\" class=\"form-control\" ><option value=\"\">所有店铺</option></select><tc>"
			+"<td><input class=\"form-control\" type='text' name='ProductAliases[comment][]' value=''/></td>"
			+"<td><a class=\"cursor_pointer text-danger\" onclick=\"productList.list.delRow(this)\"><span class=\"glyphicon glyphicon-remove-circle\" aria-hidden=\"true\"></span></a></td>"
			+"<td style=\"display:none\"><input name='ProductAliases[AliasStatus][]' value='add'/></td></tr>";
			$('#product_alias_table').append(addaliasHtml);
			productList.list.initFormValidateInput();
	},
	
	//切换平台
	change_shop:function(obj){
		var val = obj.value;
		
		//店铺下拉
		var html = "";
		$.each(productList.list.platformAccount,function(platform, shop){
			if(val == platform){
				$.each(shop,function(shop_id, shop_name){
					if(shop_id == 0)
						shop_id = '';
					html += "<option value=\""+ shop_id +"\">"+ shop_name +"</option>";
				});
			}			
		});

		$(obj).parent().parent().find('select[name="ProductAliases[selleruserid][]"]').html(html);
	},
	
	delRow:function(obj){
		$(obj).parent().parent().find('input[name="ProductAliases[AliasStatus][]"]').val('del');
		$(obj).parent().parent().css("display","none");
	},
	
	/**
	 +----------------------------------------------------------
	 * 检查商品的SKU别名是否有效
	 +----------------------------------------------------------
	 * @access	public
	 +----------------------------------------------------------
	 * @return  boolen true/false
	 +----------------------------------------------------------
	 * log		name	date					note
	 * @author	ouss	2014/02/27 13:33:00		初始化
	 +----------------------------------------------------------
	**/
	checkProductAlias : function () {
		/* sku 有特殊符号的情况下不能这样使用
		if ($("input[name=alias_sku]").filter("input[value="+$('#Product_sku').val()+"]").length > 0) {
			bootbox.alert(Translator.t("商品sku不是与别名重复"));
			return false;
		}
		*/
		var has = {};
		var rt = false;
		var alias_list = '';
		productList.list.newAliasExisting='';
		$.showLoading();
		$('input[name="ProductAliases[alias_sku][]"]').each(function(){
			/*if (this.value==$('#Product_sku').val()) {
				bootbox.alert(Translator.t("商品sku不能与别名重复"));
				rt = true;
				return true;
			}*/
			
			if (has[this.value] == '1' ){
				bootbox.alert(this.value + Translator.t("重复录入"));
				rt = true;
				return true;
			}else{
				has[this.value] = 1; 
				if(alias_list==''){
					alias_list = this.value;
				}else{
					alias_list+="@@&@@"+this.value;
				}
			}
		});
		
		productList.list.checkAliaExisting(alias_list);
		
		$.hideLoading();
		return rt;
	},
	
	//检测配对关系是否存在，返回信息json
	checkMatchingExist : function () {
		var has = {};
		var alias_list = '';
		var rt = false;
		productList.list.existingMatchTable = '';
		$.showLoading();
		$sku = $('#Product_sku').val();
		$('input[name="ProductAliases[alias_sku][]"]').each(function(){
			var aliasstatus = $(this).parent().parent().find('[name="ProductAliases[AliasStatus][]"]').val();
			if(aliasstatus != 'del'){
				/*if (this.value==$sku) {
					bootbox.alert(Translator.t("商品sku不能与别名重复"));
					rt = true;
					return false;
				}*/
				
				var platform = $(this).parent().parent().find('[name="ProductAliases[platform][]"]').val();
				var selleruserid = $(this).parent().parent().find('[name="ProductAliases[selleruserid][]"]').val();
				var shopname = $(this).parent().parent().find('[name="ProductAliases[selleruserid][]"] option:selected').text();
				if(shopname == '所有店铺')
					shopname = '';
				var info = this.value +'_'+ platform +'_'+ selleruserid;

				if (has[info] == '1' ){
					bootbox.alert(this.value +'，平台"'+ platform +'"，店铺"'+ shopname + '"，重复录入');
					rt = true;
					return false;
				}else{
					has[info] = 1;
					
					//查询哪些是新增
					if(aliasstatus == 'add'){
						if(alias_list == ''){
							alias_list = this.value +'@@#@@'+ platform +'@@#@@'+ selleruserid +'@@#@@'+ shopname;
						}else{
							alias_list += "@@&@@" + this.value +'@@#@@'+ platform +'@@#@@'+ selleruserid +'@@#@@'+ shopname;
						}
					}
				}
			}
		});
		
		var table_html = "";
		if(!rt && alias_list != ''){
			$.ajax({
				type: "GET",
				url:'/catalog/product/check-match-existing', 
				data:{aliaslist:alias_list, sku:$sku},
				dataType:'json',
				async : false,
				success: function (result) {
					if(result.success){
						table_html = "<style>#match_exist_table td{border:1px solid #ccc;}</style>" +
								"<table class='table' id='match_exist_table'>" +
								"<tr>" +
									"<th>sku</th>" +
									"<th>别名</th>" +
									"<th>平台</th>" +
									"<th>店铺</th>" +
								"</tr>";
						$.each(result.matchExisting, function(n, match){
							table_html += "<tr>" +
										"<td>"+match.sku+"</td>" +
										"<td>"+match.alias_sku+"</td>" +
										"<td>"+match.platform+"</td>" +
										"<td>"+match.shopname+"</td>" +
									"</tr>";
						});
						table_html += "</table>";
					}
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t('数据传输错误！'));
					rt = true;
				}
			});
		}

		productList.list.existingMatchTable = table_html;
		$.hideLoading();
		return rt;
	},
	
	exportExcelSelect:function(type){
		var str = '';
		var count = 0;
		
		if(type == 0)
		{
			$('input[name="chk_product_info"]:checked').each(function(){
				str += $(this).attr('product_id') + ',';
				count++;
			});
			
			if(str.replace(',','') == '')
			{
		    	$.alertBox('<p class="text-warn">请勾选需导出的商品！</p>');
				return false;
		    }
		}
		else{
			//筛选条件
			str = $('#search_condition').val();
			//总数量
			var c = $('#search_count').val();
			if( c != null && c != '')
				count = c;
		}

		//当小于500行时，直接前台导出
		if(count > 0 && count < 500){
			window.open("/catalog/product/straight-export-excel?type="+type+"&product_ids="+str,'_blank');
		}
		else{
			$.modal({
					  url:'/catalog/product/export-excel',
					  method:'POST',
					  data:{type : type, 
						    str : str},
				},
				'导出Excel',{footer:false,inside:false}).done(function($modal){
				});
		}
	},
	
	initShowAsskuTip : function(){
		$('.as_sku_tip').each(function(){
			productList.list.initMemoQtipEntryBtn(this);
		});
	},
	
	initMemoQtipEntryBtn:function(obj){
		var btnObj = $(obj);
		var tipkey = $(obj).attr("value");
		$('.as_sku_tip_detail').css("display", "none");
		$("tr[name='"+ tipkey +"']").css("display", "");
		btnObj.qtip({
			show: {
				event: 'mouseover',
				//event: 'click',
				solo: true,
			},
			hide: {
				event: 'mouseout',	
				//event: 'click',
			},
			content: {
			    button:false,
				text: $('#div_as_sku_tip').html(),
		    },
			position:{
				at:'rightMiddle',
				my:'leftMiddle',
				viewport:$(window)
			},
			style: {
				classes:'product_pending_list',
				// def:false,
				//width:1000,
			}
		});
		//btnObj.prop('title','点击查看相关说明');
	},
	
	import_product_excel : function(itype){
		if (typeof(importFile) != 'undefined'){
			form_url = "/catalog/product/excel2-product";
			if(itype=='B'){
				stitle = '请选择导入捆绑商品的excel文件';
				template_url = "/template/商品导入格式-捆绑商品.xls";
			}
			else if(itype=='L'){
				stitle = '请选择导入变参商品的excel文件';
				template_url = "/template/商品导入格式-变参商品.xls";
			}
			else{
				stitle = '请选择导入普通商品的excel文件';
				template_url = "/template/商品导入格式-普通商品.xls";
			}
			importFile.showImportModal(
				Translator.t(stitle),
				form_url,
				template_url,
				itype,
				function(result){
					var ErrorMsg = "";
					if(typeof(result)=='object'){
						if(typeof(result.error)!=='undefined' && result.error!==''){
							ErrorMsg += result.error;
						}else{
							ErrorMsg += "<p style='font-size:18px;line-height:25px;'>成功新增商品：<span style='color:#91c854;'>"+ result.successInsertQty +"</span> 个，";
							ErrorMsg += "更新商品：<span style='color:#91c854'>"+ result.successUpdateQty +"</span> 个<br>";
							ErrorMsg += "导入失败商品：<span style='color:#ed5466'>"+ result.failQty +"</span> 个</p>";
							for (var i in result){
								if (typeof(result[i].insert)!=='undefined' && result[i].insert == false){
									ErrorMsg += "<b style='color:red;line-height:25px;'>第"+i+"行：</b><br>";
									var rtnNum = result[i].length;
									for(var j in result[i]){
										var val1 = result[i][j];
										
										if(j!=='sku' && j!=='insert')
											ErrorMsg += "&nbsp;&nbsp;&nbsp;&nbsp;"+result[i][j]+"<br>";
									}
								}
							}
						}
					}else{
						ErrorMsg += result;
					}
					if (ErrorMsg != ""){
						ErrorMsg= "<div style='height: 600px;overflow: auto;'>"+ErrorMsg+"</div>";
						bootbox.alert(ErrorMsg);
					}else{
						bootbox.alert(Translator.t('导入成功'));
					}
					
			});
		}else{
			bootbox.alert(Translator.t("没有引入相应的文件!"));
		}
	},
	
	//打开合并商品窗口
	batchMergeProduct:function(){
		$.modal
		(
			{
			  url:'/catalog/product/show-merge-product-box',
			  method:'post',
			  data:{},
			},
			'合并商品',
			{
				footer:false,inside:false
			}
		).done(function($modal){
			
		});
	},
	
	//添加被合并SKU
	addSkuTab:function(){
		var sku = $('#add_sku_val').val().trim();
		if(sku == undefined || sku == ''){
			$('#add_sku_val').focus();
			return false;
		}
		//检查是否有效SKU
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: '/catalog/product/check-be-merge-sku',
			data: {
				sku: sku,
				} , 
			success: function (result) {
				if (result.success){
					$.hideLoading();
					//加入SKU节点
					var html = '<div class="skuDivCont" style="float: left">'+
				        	'<span class="be_merge_sku" sku="'+ sku +'">'+sku +'</span>'+
				        	'<span class="glyphicon glyphicon-remove removeSku" onclick="productList.list.removeSkuTab(this)"></span>'+
				        '</div>';
					$('.on_merge_sku_group').append(html);
					$('#add_sku_val').val('');
					$('#add_sku_val').focus();
				}
				else{
					$.hideLoading();
					bootbox.alert(result.msg);
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert('添加被合并商品异常！');
			}
		});
	},
	
	removeSkuTab:function(obj){
		$(obj).parent().remove();
	},
	
	mergeProduct:function(){
		var merge_sku = $('#merge_sku').val();
		if(merge_sku == 'undefined' || merge_sku == ''){
			bootbox.alert('目标本地SKU不能为空！');
			return false;
		}
		
		var be_sku_list = '';
		$('.be_merge_sku').each(function(){
			be_sku_list += $(this).attr('sku') +'@#@';
		});
		if(be_sku_list == ''){
			bootbox.alert('被合并SKU不能为空！');
			return false;
		}
		
		bootbox.confirm("<b style='color:red;'>确定合并商品？</b>",function(r){
			if (!r) return;
			$.showLoading();
			
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/catalog/product/merge-product',
				data: {
					merge_sku: merge_sku,
					be_sku_list: be_sku_list,
					} , 
				success: function (result) {
					if (result.success){
						$.hideLoading();
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("成功合并商品!"),
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
					bootbox.alert('合并商品异常！');
				}
			});
			
		});
	},
	
	changeClass:function(obj){
		var class_id = $(obj).attr('class_id');
		var name = $(obj).html().replace("|-", "");
		if(name != ''){
			$('#search_class_id').html(name);
			$('[name="edit_class_id"]').attr('value', class_id);
		}
	},
	
	//批量编辑商品
	BathEdit : function (edit_type) {
		var product_id_list = new Array();
		$('input[name=chk_product_info]:checked').each(function(){
			product_id_list.push($(this).attr('product_id'));
		});
		
		if (product_id_list.length <= 0 ){
			bootbox.alert(Translator.t('请选择需要编辑的商品'));
			return false;
		}
		
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/catalog/product/show-bath-edit-dialog',
			data: {
				edit_type: edit_type,
				product_id_list: product_id_list,
			},
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "product_info",
					title: Translator.t("批量编辑"),//
					message: result,
					buttons:{
						OK: {
							label: Translator.t("保存"),  
							className: "btn-primary",
							callback: function () {
								productList.bath_edit.saveProduct();
								return false;
							}  
						}, 
						Cancel: {  
							label: Translator.t("取消"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
}

$(function() {
	productList.list.init();
	
});

//function productbcodeprint(skulist){
//	alert('2222');
//	$height=$('select[name="height"]').val();
//	$width=$('select[name="width"]').val();
//	$number=$('input[name="number"]').val();
//	window.open( "http://carriersetting.com/catalog/product/sku-barcode-print"+'?skulist='+skulist);
//}