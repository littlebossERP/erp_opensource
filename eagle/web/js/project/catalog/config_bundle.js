/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof productList === 'undefined')  productList = new Object();

productList.ConfigAndBundle={
	existingImages:'',
	totalTry:0,
	existingprod:'',
	configField:new Array(),
	productStatus:new Array(),
	initFormValidateInput:function(){
		$('#Product_sku').formValidation({validType:['trim','length[1,30]'],tipPosition:'left',required:true});
		$('#Product_name,#Product_prod_name_ch,#Product_prod_name_en,#Product_declaration_ch,#Product_declaration_en').formValidation({validType:['trim','length[2,255]'],tipPosition:'left',required:true});
		$('#Product_declaration_value_currency').formValidation({validType:['trim','length[1,3]'],tipPosition:'left'});
		$('#Product_declaration_value').formValidation({validType:['trim','amount'],tipPosition:'left'});
		$('#Product_sku').formValidation({tipPosition:'left',required:true});
		$("th input[name^='children[config_field'],input[name^='children[config_field_value']").formValidation({validType:['trim','prod_field'],tipPosition:'left'});
		$('#Product_declaration_value').formValidation({tipPosition:'left',validType:['trim','amount']});
		$('#Product_weight,#Product_length , #Product_width,#Product_height ').formValidation({tipPosition:'left',validType:['trim','amount']});
		$("td input[name='children[bundle_qty][]']").formValidation({validType:['trim','amount'],tipPosition:'left'});
		$('#Product_declaration_code').formValidation({validType:['trim','length[0,100]'],tipPosition:'left'});
		//$('#Product_weight,#Product_length , #Product_width,#Product_height ,#Product_declaration_value').keyup
	},
	
	initSkuChange:function(){
		$('#Product_sku').change(function(){
			var sku=$('#Product_sku').val();
			for(var i=0; i<$(".children_product_list_tr").length; i++){
				$nowSku = $(".children_product_list_tr input[name^='children[sku]']").eq(i).val();
				if($nowSku!==''){	
				}else{
					$(".children_product_list_tr input[name^='children[sku]']").eq(i).val(sku+'_'+(i+1));
				}
			}
		});
	},
	
	initPhotosSetting:function(){
		$('div[role="image-uploader-container"]').batchImagesUploader({
					localImageUploadOn : true,   
					fromOtherImageLibOn: true , 
					imagesMaxNum : 10,
					fileMaxSize : 1024 , 		
					fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
					maxHeight : 100,
					maxWidth : 100,
					initImages : productList.ConfigAndBundle.existingImages, 
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
	
	initBtn : function(){
		$(".delete_child_prod").unbind('click').click(function(){
			var parentTr = $(this).parent().parent();
			parentTr.remove();
		});
	},
	
	checkSkuExisting : function (sku) {
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/catalog/product/check-sku-existing', 
			data:{sku:sku},
			dataType:'json',
			async : false,
			success: function (result) {
				$.hideLoading();
				productList.ConfigAndBundle.existingprod = result;
			},
			error :function () {
				$.hideLoading();
				productList.ConfigAndBundle.existingprod = '';
			}
		});
	},
	
	initConfigChildSkuInput : function(){
		$("#product_under_config_tb input[name='children[sku][]']").unbind('change').bind('change',function(){
			var obj=$(this);
			var sku=$(this).val();
			productList.ConfigAndBundle.checkSkuExisting(sku);
			var result = productList.ConfigAndBundle.existingprod;
			if(result['existing']){
				if(result.info.type=='S'){
					bootbox.confirm(
						"sku:"+sku+Translator.t("是已存在商品！系统其它功能可能已经有该产品处于业务流程中。")+"<br>"+
						Translator.t("为避免信息混乱，该sku产品作为变参子产品时，仅可以编辑其变参属性，其它属性均会保留旧有属性。")+"<br>"+
						Translator.t("且如果其原本属性在存在变参属性，会自动引用该值，但不能更改该值。")+"<br>"+
						Translator.t("保存后，该sku商品将会转换成‘变参子产品’类型。")+"<br>"+
						Translator.t("是否确定以其为其中一个变参子产品？"),
						function(r){
							if (! r){
								$(this).val('');
								return true;
							}else{
								$.showLoading();
								obj.parent().parent().find("input[name='children[photo_primary][]']").val(result.info.photo_primary);
								obj.parent().parent().find("img[id^='children_try']").attr('src',result.info.photo_primary);
								var attr1 = $("input[name='children[config_field_1]']").val();
								var attr2 = $("input[name='children[config_field_2]']").val();
								var attr3 = $("input[name='children[config_field_3]']").val();
								obj.parent().parent().attr('attrStr',result.info.other_attributes);
								for(key in result.info.attrArr){
									if(key==attr1){
										obj.parent().parent().find("input[name='children[config_field_value_1][]']").val(result.info.attrArr[key]);
										obj.parent().parent().find("input[name='children[config_field_value_1][]']").attr('readonly','readonly');
									}else if(key==attr2){
										obj.parent().parent().find("input[name='children[config_field_value_2][]']").val(result.info.attrArr[key]);
										obj.parent().parent().find("input[name='children[config_field_value_2][]']").attr('readonly','readonly');
									}else if(key==attr3){
										obj.parent().parent().find("input[name='children[config_field_value_3][]']").val(result.info.attrArr[key]);
										obj.parent().parent().find("input[name='children[config_field_value_3][]']").attr('readonly','readonly');
									}
								}
								//同步子产品已有状态
								for(i in productList.ConfigAndBundle.productStatus){
									if(productList.ConfigAndBundle.productStatus[i][0]==result.info.status){
									obj.parent().parent().find("td").eq(2).html(productList.ConfigAndBundle.productStatus[i][1]);
									break;
									}
								}
								//验证源sku是否在子产品列表
								var srcSku=$("#product-create-form input[name='fromSku']").val();
								if(typeof(srcSku)!=='undefined' && srcSku!==''){
									var hasSrcSku = false;
									$(".children_product_list_tr").each(function(){
										var child_sku = $(this).find("input[name='children[sku][]']").val();
										if (srcSku == child_sku){
											hasSrcSku = true;
										}
									});
									if (!hasSrcSku){
										$(".have_no_srcSku_alert").css({'display':'block','color':'red','font-weight':'700','background-color':'rgb(135, 225, 255)'});
									}else{
										$(".have_no_srcSku_alert").css('display','none');
									}
								}
								$.hideLoading();
							}
						}
					);
				}
				if(result.info.type=='L'){
					bootbox.alert("sku:"+sku+Translator.t("是另一个变参商品的子产品，不能作为本变参商品的子产品使用！"));
					$(this).val('');
					return false;
				}
				if(result.info.type=='C' || result.info.type=='B'){
					bootbox.alert("sku:"+sku+Translator.t("是已存在的变参或者捆绑商品，不能作为变参子产品使用！"));
					$(this).val('');
					return false;
				}
			}else{
				if(result.message=='sku not existing'){
					obj.parent().parent().find("input[name^='children[config_field_value']").removeAttr('readonly');
					//验证源sku是否在子产品列表
					var srcSku=$("#product-create-form input[name='fromSku']").val();
					if(typeof(srcSku)!=='undefined' && srcSku!==''){
						var hasSrcSku = false;
						$(".children_product_list_tr").each(function(){
							var child_sku = $(this).find("input[name='children[sku][]']").val();
							if (srcSku == child_sku){
								hasSrcSku = true;
							}
						});
						if (!hasSrcSku){
							$(".have_no_srcSku_alert").css({'display':'block','color':'red','font-weight':'700','background-color':'rgb(135, 225, 255)'});
						}else{
							$(".have_no_srcSku_alert").css('display','none');
						}
					}
				}
				else 
					return true;
			}
		});
	},
	
	initBundleChildSkuInput : function(){
		$("#product_under_bundle_tb input[name='children[sku][]']").unbind('change').bind('change',function(){
			$.showLoading();
			var obj=$(this);
			var sku=$(this).val();
			productList.ConfigAndBundle.checkSkuExisting(sku);
			var result = productList.ConfigAndBundle.existingprod;
			if(result['existing']){
				if(result.info.type=='S'||result.info.type=='L'){
					obj.parent().parent().find("img[id^='children_try']").attr('src',result.info.photo_primary);
					obj.parent().parent().find("td.child_prod_name").html(result.info.name);
					//同步子产品状态
					for(i in productList.ConfigAndBundle.productStatus){
						if(productList.ConfigAndBundle.productStatus[i][0]==result.info.status){
						obj.parent().parent().find("td.child_prod_status").html(productList.ConfigAndBundle.productStatus[i][1]);
						break;
						}
					}
					//验证源sku是否在子产品列表
					var srcSku=$("#product-create-form input[name='fromSku']").val();
					if(typeof(srcSku)!=='undefined' && srcSku!==''){
						var hasSrcSku = false;
						$(".children_product_list_tr").each(function(){
							var child_sku = $(this).find("input[name='children[sku][]']").val();
							if (srcSku == child_sku){
								hasSrcSku = true;
							}
						});
						if (!hasSrcSku){
							$(".have_no_srcSku_alert").css({'display':'block','color':'red','font-weight':'700','background-color':'rgb(135, 225, 255)'});
						}else{
							$(".have_no_srcSku_alert").css('display','none');
						}
					}
					$.hideLoading();
				}
				if(result.info.type=='C' || result.info.type=='B'){
					$.hideLoading();
					bootbox.alert("sku:"+sku+Translator.t("是已存在的变参或者捆绑商品，不能作为变参子产品使用！"));
					$(this).val('');
					return true;
				}
			}else{
				$.hideLoading();
				bootbox.alert("sku:"+sku+Translator.t("不是已有商品，请录入已有sku商品作为捆绑商品的子商品！"));
				$(this).val('');
				return false;
			}
		});
	},
	
	setFieldsIfExistingProdHas : function(){
		$("#product_under_config_tb th input[name^='children[config_field_']").unbind('change').bind('change',function(){
			var field_this=$(this).val();
			var field_index = $("th input[name^='children[config_field']").index( $(this) ) +1;
			
			var freq=0;
			for(var i=0;i<3;i++){
				if (field_this!=='' && field_this==$("#product_under_config_tb th input[name^='children[config_field_']").eq(i).val()){
					freq++;
				}
			}
			if(freq>1){//出现次数多于1次，则为非唯一
				bootbox.alert("属性:"+field_this+Translator.t("已经存在，不能重复使用！"));
				$(this).val('');
				return true;
			}
			
			if(typeof(productList.list.fieldValues[field_this])!=='undefined'){
				$("input[name='children[config_field_value_"+field_index+"][]']").autocomplete({source:productList.list.fieldValues[field_this]});
			}
			
			if ($.inArray(field_this,productList.ConfigAndBundle.configField) == -1){
				$("#product_under_config_tb input[name^='children[config_field_value_"+field_index+"]']").removeAttr('readonly');
			}
			
			var hasChildren = $(".children_product_list_tr").length;
			for( var j=0;j<hasChildren;j++ ){
				var attrStr = $(".children_product_list_tr").eq(j).attr('attrStr');
				if(typeof(attrStr)=='undefined' || attrStr=='')
					continue;
				else{
					var attrArr=new Array();
					var attrK_V = attrStr.split(';');
					for (var k=0;k<attrK_V.length;k++){
						var aAttr = attrK_V[k].split(':');
						if(typeof(aAttr[0])!=='undefined' && typeof(aAttr[1])!=='undefined' && field_this==aAttr[0])
						{
							$("#product_under_config_tb input[name^='children[config_field_value_"+field_index+"]']").eq(j).val(aAttr[1]);
							$("#product_under_config_tb input[name^='children[config_field_value_"+field_index+"]']").eq(j).attr('readonly','readonly');
						}
					}
				}
			}
		});
	},
	/*
	fieldValueAutocomplete:function(){
		$("th input[name^='children[config_field']").each(function(){
			var index = $("th input[name^='children[config_field']").index( $(this) );
			var name = $(this).val();
			if(typeof(productList.list.fieldValues[name])!=='undefined'){
				$("input[name^='children[config_field_value_"+(index+1)+"']").autocomplete({source:productList.list.fieldValues[name]});
			}
			$(this).unbind('change').bind('change',function(){
				var field_name = $(this).val();
				$("input[name^='children[config_field_value_"+(index+1)+"']").autocomplete({source:productList.list.fieldValues[field_name]});
			});
		});
	},
	*/
	checkProductUnderConfigList : function(){
		if($(".delete_prod_under_config").length<=1){
			$(".delete_prod_under_config").eq(0).attr('disabled','disabled');
			$(".delete_prod_under_config").eq(0).parent().attr('title',Translator.t("创建变参产品至少需要有一个子产品"));
		}else{
			for(var i=0;i<$(".delete_prod_under_config").length;i++){
				$(".delete_prod_under_config").eq(0).removeAttr('disabled');
				$(".delete_prod_under_config").eq(0).parent().removeAttr('title');
			}
		}
	},
	
	
	addConfigChild : function(){
		productList.ConfigAndBundle.totalTry ++;
		var sku=$('#Product_sku').val();
		var hasChildren = $(".children_product_list_tr").length;
		var childrenSkuList = new Array();
		for(var i=0; i<hasChildren; i++){
			childrenSkuList.push( $(".children_product_list_tr input[name^='Product[children][sku]']").eq(i).val() );
		}
		if (typeof(sku)=='undefined' || sku=='')
			var newSku = '';
		else
			var newSku = productList.ConfigAndBundle.autoChlidrenSku(sku,childrenSkuList,hasChildren);
		var newTr = "<tr class=\"children_product_list_tr\">"+
						"<td>"+
							"<div style=\"position:relative;\">"+
								"<input type=\"hidden\" id=\"children_try_"+productList.ConfigAndBundle.totalTry+"\" name=\"children[photo_primary][]\" value=\"\">"+
								"<img style=\"width:100%;height:100%;\" src=\"/images/batchImagesUploader/no-img.png\" id=\"children_try_"+productList.ConfigAndBundle.totalTry+"_img\">"+
								"<a \"javascript:void(0)\" onclick=\"productList.ConfigAndBundle.selectChildPhoto("+productList.ConfigAndBundle.totalTry+")\" style=\"cursor:pointer;position:absolute;top:0px;right:0px;background-color:rgb(135, 225, 255);padding:0px 3px;\"><span class=\"glyphicon glyphicon-repeat\" aria-hidden=\"true\"></span></a>"+
							"</div>"+
						"</td>"+
						"<td id=\"\"><input type=\"text\" name=\"children[sku][]\" value=\""+newSku+"\" class=\"form-control\"/></td>"+
						"<td id=\"\">"+Translator.t('在售')+"</td>"+
						"<td id=\"\"><input type=\"text\" name=\"children[config_field_value_1][]\" value=\"\" class=\"form-control\"/></td>"+
						"<td id=\"\"><input type=\"text\" name=\"children[config_field_value_2][]\" value=\"\" class=\"form-control\"/></td>"+
						"<td id=\"\"><input type=\"text\" name=\"children[config_field_value_3][]\" value=\"\" class=\"form-control\"/></td>"+
						"<td id=\"\"><input type=\"button\" value=\""+Translator.t('删除')+"\" class=\"btn btn-danger delete_child_prod\" /></td>"+
					"</tr>";
		
		$("#product_under_config_tb tbody").append(newTr);
		
		productList.ConfigAndBundle.initFormValidateInput();
		productList.ConfigAndBundle.initBtn();
		productList.ConfigAndBundle.initConfigChildSkuInput();
		productList.ConfigAndBundle.setFieldsIfExistingProdHas();
		//productList.ConfigAndBundle.fieldValueAutocomplete();
	},
	
	addBundleChild : function(){
		var hasChildren = $(".children_product_list_tr").length;

		var newTr = "<tr class=\"children_product_list_tr\">"+
						"<td>"+
							"<div style=\"position:relative;\">"+
								"<img style=\"width:100%;height:100%;\" src=\"/images/batchImagesUploader/no-img.png\" id=\"children_try_"+productList.ConfigAndBundle.totalTry+"_img\">"+
							"</div>"+
						"</td>"+
						"<td><input type=\"text\" name=\"children[sku][]\" value=\"\" placeholder=\""+Translator.t('输入已存在的普通商品sku')+"\" class=\"form-control\"/></td>"+
						"<td class=\"child_prod_name\"></td>"+
						"<td class=\"child_prod_status\"></td>"+
						"<td><input type=\"text\" name=\"children[bundle_qty][]\" value=\"1\" class=\"form-control\"/></td>"+
						"<td><input type=\"button\" value=\""+Translator.t('删除')+"\" class=\"btn btn-danger delete_child_prod\" /></td>"+
					"</tr>";
		
		$("#product_under_bundle_tb tbody").append(newTr);
		
		productList.ConfigAndBundle.initFormValidateInput();
		productList.ConfigAndBundle.initBtn();
		productList.ConfigAndBundle.initBundleChildSkuInput();
	},
	
	autoChlidrenSku : function(sku,childrenSkuList,index){
		var expectedSku = sku +'_'+ (index+1);
		for(var i=0; i<childrenSkuList.length; i++){
			if(expectedSku == childrenSkuList[i])
				return productList.ConfigAndBundle.autoChlidrenSku(sku,childrenSkuList,index+1);
			else {
				productList.ConfigAndBundle.checkSkuExisting(expectedSku);
				var prod = productList.ConfigAndBundle.existingprod;
				if(prod['existing']){
					return productList.ConfigAndBundle.autoChlidrenSku(sku,childrenSkuList,index+1);
				}
				else
					continue;
			}
			
		}
		return expectedSku;
	},
	
	selectChildPhoto : function(id){
		var uploaded_photo = new Array();
		var photoCount = $("#image-list img").length;
		for(var i=0; i<photoCount; i++ ){
			var src = $("#image-list img").eq(i).attr("src");
			if ( typeof(src)!=='undefined' && src!=='/images/batchImagesUploader/no-img.png' ){
				uploaded_photo.push(src);
			}
		}
		dialogHtml = "<div id=\"slecest_child_photo_list_div\" style=\"clear:both;width:100%;display:inline-block;\">";
		for(var j=0; j<uploaded_photo.length; j++){
			dialogHtml +=
				"<div style=\"float:left;width:120px;height:100px;border:1px solid rgb(200,200,200);margin-right:10px;\">"+
					"<label for=\"uploaded_photo_"+j+"\" style=\"width:100px;height:100px;float:left;padding:2px;\">"+
					"<div style=\"width:100%;height:100%;background:url("+uploaded_photo[j]+") #FFF no-repeat center;background-size:100px;\" ></div>"+
					"</label>"+
					"<input type=\"radio\" name=\"select_child_photo_radio\" value=\""+uploaded_photo[j]+"\" id=\"uploaded_photo_"+j+"\" style=\"margin-top:40%;float:right;\"/>"+
			"</div>"
		}
		dialogHtml += "</div>";
		bootbox.dialog({
			className : "slecest_child_photo_dialog",
			title: Translator.t("选择已上传的图片"),
			message: dialogHtml,
			buttons:{
				Cancel: {  
					label: Translator.t("取消"),  
					className: "btn-default",  

				}, 
				OK: {  
					label: Translator.t("选择"),  
					className: "btn-primary",  
					callback: function () {
						var selected_photo = $("#slecest_child_photo_list_div input[name='select_child_photo_radio']:checked").val();
						$("#children_try_"+id).attr('value',selected_photo);
						$("#children_try_"+id+"_img").attr('src',selected_photo);                 
					}  
				}, 
			}
		});
		
	},
	setChildPhott : function(){
		
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
						var rtn =   productList.ConfigAndBundle.SaveProduct();
						if(rtn) return true;
						else return false;
					}  
				}, 
			}
		});
		productList.ConfigAndBundle.initFormValidateInput();
		productList.ConfigAndBundle.initPhotosSetting();
		//productList.ConfigAndBundle.initSkuChange();
		productList.ConfigAndBundle.initBtn();
		productList.ConfigAndBundle.initConfigChildSkuInput();
		productList.ConfigAndBundle.setFieldsIfExistingProdHas();
		
		productList.ConfigAndBundle.initBundleChildSkuInput();
		productList.list.initAutocomplete();
		//productList.ConfigAndBundle.fieldValueAutocomplete();
	},

	addConfigProd:function(){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/catalog/product/get_create_product_form?tt=add&type=C', 
			success: function (result) {
				$.hideLoading();
				
				productList.ConfigAndBundle.CreateProductBox(
					Translator.t("添加变参商品") ,
					result
				);
				return true;
			},
			error :function () {
				$.hideLoading();
				return false;
			}
		});
		
	},
	copyToConfigProd : function(sku){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/catalog/product/get_create_product_form?tt=add&type=C&fromSku='+sku, 
			success: function (result) {
				$.hideLoading();
				
				productList.ConfigAndBundle.CreateProductBox(
					Translator.t("复制信息并创建变参商品") ,
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
	
	addBundleProd:function(){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/catalog/product/get_create_product_form?tt=add&type=B', 
			success: function (result) {
				$.hideLoading();
				
				productList.ConfigAndBundle.CreateProductBox(
					Translator.t("添加捆绑商品") ,
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
	
	copyToBundleProd : function(sku){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/catalog/product/get_create_product_form?tt=add&type=B&fromSku='+sku, 
			success: function (result) {
				$.hideLoading();
				
				productList.ConfigAndBundle.CreateProductBox(
					Translator.t("复制信息并创建捆绑商品") ,
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
	
	SaveProduct:function(callback){
		$.showLoading();
		if (! $('#product-create-form').formValidation('form_validate')){
			$.hideLoading();
			bootbox.alert("<b style='color:red'>"+Translator.t('录入格式不正确!')+"</b>");
			return false;
		}
		
		if (productList.list.checkSupplierRepeatOrNot()){
			$.hideLoading();
			bootbox.alert("<b style='color:red'>"+Translator.t('供应商重复!')+"</b>");
			return false; 
		}
		
		productList.list.setOtherAttr();
		
		//验证config是否有子产品
		if($("#product_under_config_tb").length>0){
			if ($("#product_under_config_tb .children_product_list_tr").length<1){
				$.hideLoading();
				bootbox.alert("<b style='color:red'>"+Translator.t('变参产品必须有子产品，请于子产品列表处添加子产品!')+"</b>");
				return false;
			}
		}

		//验证config子产品field是否有未填
		var unBlank = true;
		var configHaveField=true;
		if ($("#product_under_config_tb .children_product_list_tr").length>0){
			configHaveField=false;
			$("th input[name^='children[config_field']").each(function(){
				if (!unBlank)
					return false;
				if ($(this).val()!==''){
					configHaveField=true;
					var index = $("th input[name^='children[config_field']").index( $(this) );
					$("input[name^='children[config_field_value_"+(index+1)+"']").each(function(){
						if (typeof($(this).val())=='undefined' || $(this).val()==''){
							unBlank=false;
							return false;
						}
					});
				}
			});
		}
		
		if (!configHaveField){
			$.hideLoading();
			bootbox.alert("<b style='color:red'>"+Translator.t('变参产品必须为子产品设置1-3个变参属性！')+"</b>");
			return false; 
		}
		if (!unBlank){
			$.hideLoading();
			bootbox.alert("<b style='color:red'>"+Translator.t('已经设置变参属性，所有子产品必须有对应值!')+"</b>");
			return false; 
		}
		//验证子产品sku和父sku是否有重复(config 和 bundle) 及是否有子产品SKU未填写
		var hasSkuEq = false;
		var hasSkuEmpty = false;
		if ($(".children_product_list_tr").length >0) {
			var sku_list = new Array();//父SKU和子SKU数组
			sku_list.push($("#product-create-form #Product_sku").val());
			$(".children_product_list_tr").each(function(){
				var child_sku = $(this).find("input[name='children[sku][]']").val();
				if(child_sku==''){
					hasSkuEmpty = true;
				}
				sku_list.push(child_sku);
			});
			var sku_list_comparison=sku_list.sort(); //简单测重复
			for(var i=0;i<sku_list.length;i++){ 
				if (sku_list_comparison[i]==sku_list_comparison[i+1]){ 
					hasSkuEq = true;
					break;
				} 
			}
		}
		if(hasSkuEmpty){
			$.hideLoading();
			bootbox.alert("<b style='color:red'>"+Translator.t('有子产品sku未填写!')+"</b>");
			return false;
		}
		if (hasSkuEq){
			$.hideLoading();
			bootbox.alert("<b style='color:red'>"+Translator.t('父sku和子sku、或子sku之间，存在重复，不能保存!')+"</b>");
			return false; 
		}
		
		//验证config子产品field_value是否有完全重复
		if ($("#product_under_config_tb .children_product_list_tr").length>1){
			var hasAllAttrEq = false;
			var child_attrStr = new Array();
			$(".children_product_list_tr").each(function(){
				var field_value_1 = $(this).find("input[name='children[config_field_value_1][]']").val();
				var field_value_2 = $(this).find("input[name='children[config_field_value_2][]']").val();
				var field_value_3 = $(this).find("input[name='children[config_field_value_3][]']").val();
				child_attrStr.push(field_value_1+';'+field_value_2+';'+field_value_3);
			});

			var child_attrStr_comparison=child_attrStr.sort(); //简单测重复
			for(var i=0;i<child_attrStr.length;i++){ 
				if (child_attrStr_comparison[i]==child_attrStr_comparison[i+1]){ 
					hasAllAttrEq = true;
					break;
				} 
			}

			
			if (hasAllAttrEq){
				$.hideLoading();
				bootbox.alert("<b style='color:red'>"+Translator.t('不允许有子产品的全部变参属性值都相同，不能保存!')+"</b>");
				return false; 
			}
			//检测变参属性和额外属性是否有重复
			var attrs=new Array();
			$("input[name='other_attr_key[]']").each(function(){
				var field_name = $(this).val();
				if (typeof(field_name)!=='undefined' && field_name!=='')
					attrs.push(field_name);
			});
			$("th input[name^='children[config_field']").each(function(){
				var field_name = $(this).val();
				if (typeof(field_name)!=='undefined' && field_name!=='')
					attrs.push(field_name);
			});
			var attrs_comparison=attrs.sort(); //简单测重复
			for(var i=0;i<attrs.length;i++){ 
				if (attrs_comparison[i]==attrs_comparison[i+1]){
					$.hideLoading();
					bootbox.alert("<b style='color:red'>"+Translator.t('商品同一个变参属性只能设置一个值，请修改!')+"</b>");
					return false; 
				} 
			}
			
		}
		
		//检测是否有bundle子产品未符合条件
		if ($("#product_under_bundle_tb").length>0){
			if ($("#product_under_bundle_tb .children_product_list_tr").length<1){
				$.hideLoading();
				bootbox.alert("<b style='color:red'>"+Translator.t('捆绑产品必须有子产品，请于子产品列表处添加有效子产品!')+"</b>");
				return false;
			}else{
				$("input[name='children[bundle_qty][]']").each(function(){
					var qty = $(this).val();
					if(typeof(qty)!=='undefined' && (qty==''||qty==0) ){
						$.hideLoading();
						bootbox.alert("<b style='color:red'>"+Translator.t('捆绑产品所有子产品的捆绑数量必须大于0!')+"</b>");
						return false;
					}
				});
			}
		}
		
		productList.list.setPicAttr();
		
		if (productList.list.checkProductAlias()){
				return false; 
			}
					
		if(productList.list.newAliasExisting!==''){
			$.hideLoading();
			bootbox.confirm(
				Translator.t("下列别名是已存在的商品:<br>")+"<span style='color:red'>"+productList.list.newAliasExisting+"</span><br>"+Translator.t("。保存则会将这些商品的所有相关操作合并到此商品！（如订单，发货，库存等等）")+
				"<br><span style='color:red'>"+Translator.t("合并后别名产品的原数据将被替换或删除，且此操作不可逆！")+"</span><br>"+
				Translator.t("是否继续保存？"),
				function(r){
					if(r){
						productList.ConfigAndBundle.ajaxSave();
						return true;
					}
					else{
						$('.product_info').modal('hide');
						$('.product_info').on('hidden.bs.modal', '.modal', function(event) {
							$(this).removeData('bs.modal');
						});
					}
				}
			);
		}else{
			$.hideLoading();
			productList.ConfigAndBundle.ajaxSave();
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
							Message += key +':'+ Translator.t('保存成功') +'<br/>';
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
						bootbox.alert(result);
						return false;
					}
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('失败：服务器无返回结果。'));
				return false;
			}
		});
	},
}