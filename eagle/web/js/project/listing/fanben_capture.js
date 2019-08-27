/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		list
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof fanbenCapture === 'undefined')  fanbenCapture = new Object();

fanbenCapture ={
	existingImages: '',
	existtingVarianceList:'',
	existingSizeType:'',
	existingMainImage:'',
	init:function(){
		$('#fanben-wish-add-variance').click(function(){
			fanbenCapture.addVariance();
		});
		
		$('#submitFormData').click(function(){
			//$(this).prop('disabled','disabled');
			$('#wish_fanben_status').val('editing');
			fanbenCapture.submitFormData();
		});
		
		$('#submitFormDataAndPost').click(function(){
			$(this).prop('disabled','disabled');
			$('#wish_fanben_status').val('posting');
			fanbenCapture.submitFormData();
		});
		
		$('#cancelFormData').click(function(){
			$(this).prop('disabled','disabled');
			fanbenCapture.cancelFormData();
		});
		
		$('#wish_product_description').focus(function(){
			$(this).attr('rows','15');
		});

		$('#wish_product_description').blur(function(){
			$(this).attr('rows','6');
		});


		if($('.wish_site_id option:selected').val() == ''){
			$('#wish_product_parentSku').prop('disabled','true');
			$('.sku_tips').html('<span style="color:red;line-height:40px;">请选择刊登店铺</span>');
		}

		$('.wish_site_id').change(function(){
			if($('.wish_site_id option:selected').val() != ''){
				$('#wish_product_parentSku').removeAttr('disabled');
				$('.sku_tips').html('');
			}else{
				$('#wish_product_parentSku').prop('disabled','true');
				$('.sku_tips').html('<span style="color:red;line-height:40px;">请选择刊登店铺</span>');
			}
		});

		// $('#msrp').formValidation({tipPosition:'left',validType:['trim','amount']});
		//$('#wish_product_description').formValidation({tipPosition:'left',validType:['trim','wish_description'],required:true});
		//$('#site_id').formValidation({tipPosition:'left',validType:['trim','length[3,50]'],required:true});
		// $('#wish_parent_sku').formValidation({tipPosition:'left',validType:['trim','length[3,50]'],required:true});
		// $('#wish_product_name').formValidation({tipPosition:'left',validType:['trim','length[5,255]'],required:true});
		// $('#wish_product_tags').formValidation({tipPosition:'left',validType:['trim'],required:true});
		// $('#wish_shipping_time').formValidation({tipPosition:'left',validType:['trim'],required:true});
		
	} ,
	
	delRow:function(obj){
		$(obj).parents("tr").remove();
	},
	
	addVariance:function(){
		var addVarianceHtml = "<tr>"
		+"<td><input class=\"form-control\" type='text' name='variance_sku'/></td>"
		+"<td><input class=\"form-control\" type='text' name='variance_color'/></td>"
		+"<td><input class=\"form-control\" type='text' name='variance_size'/></td>"
		+"<td><input class=\"form-control\" type='text' name='variance_price' value='0'/></td>"
		+"<td><input class=\"form-control\" type='text' name='variance_shipping' value='0'/></td>"
		+"<td><input class=\"form-control\" type='text' name='variance_inventory' value='0'/></td>"
		+"<td><a class=\"cursor_pointer text-danger\" onclick=\"fanbenCapture.delRow(this)\"><span class=\"glyphicon glyphicon-remove-circle\" aria-hidden=\"true\"></span></a></td></tr>";
		$('#fanben_variance_table').append(addVarianceHtml);
		$('input[name=variance_price],input[name=variance_shipping] , input[name=variance_shipping] , input[name=variance_inventory]').formValidation({tipPosition:'left',validType:['trim','amount'],required:true});
		$('input[name=variance_size]').formValidation({tipPosition:'left',validType:['trim','wish_size']});
	},
	
	setVariance:function(){
		var variancelist = ""; 
		var varianceArr = new Array();
		$('#fanben_variance_table tr').has('td').each(function(){
			var tmpObj = new Object;
			tmpObj['sku'] = $(this).find('input[name=variance_'+'sku'+']').val();
			tmpObj['color'] = $(this).find('input[name=variance_'+'color'+']').val();
			tmpObj['size'] = $(this).find('input[name=variance_'+'size'+']').val();
			tmpObj['price'] = $(this).find('input[name=variance_'+'price'+']').val();
			tmpObj['shipping'] = $(this).find('input[name=variance_'+'shipping'+']').val();
			tmpObj['inventory'] = $(this).find('input[name=variance_'+'inventory'+']').val();
			varianceArr.push(tmpObj); 
			var tmpStr = "";
			
			tmpStr += '"sku":"'+$(this).find('input[name=variance_'+'sku'+']').val()+'",';
			tmpStr += '"color":"'+$(this).find('input[name=variance_'+'color'+']').val()+'",';
			tmpStr += '"size":"'+$(this).find('input[name=variance_'+'size'+']').val()+'",';
			tmpStr += '"price":"'+$(this).find('input[name=variance_'+'price'+']').val()+'",';
			tmpStr += '"shipping":"'+$(this).find('input[name=variance_'+'shipping'+']').val()+'",';
			tmpStr += '"inventory":"'+$(this).find('input[name=variance_'+'inventory'+']').val()+'"';
			
			if (variancelist != "") variancelist += ","; 
			variancelist += "{"+tmpStr+"}";
		});
		
		$('#variance').val("["+variancelist+"]");
		//$('#variance').val($.toJson(varianceArr));
	},
	
	checkMainPhoto:function(){
		if ($('.select_photo img').length ==0){
			bootbox.alert(Translator.t('请选择主图!'));
			return true;
		}
		
		if ($('div[upload-index]').length ==0){
			bootbox.alert(Translator.t('请上传图片!'));
			return true;
		}
	},
	
	fillPhotoData:function(){
		$('#main_image').val($('.select_photo img').attr('src'));
		$('input[name*=extra_image_]').val('');
		
		$('div[upload-index]').children('a').not('.select_photo').each(function(i){
			$('#extra_image_'+(i+1)).val($(this).find('img').attr('src'));
			//alert(i+1+"  "+ $(this).find('img').attr('src'));
		});
		
		
	},
	
	submitFormData:function(){
		if (fanbenCapture.checkMainPhoto()){
			return false;
		}
		$('form button').prop('disabled','disabled')
		/*
		if ($('#fanben_capture').formValidation('form_validate')){
			bootbox.alert(Translator.t('录入格式不正确!'));
			return false;
		}
		*/
		if ($('input[name=variance_sku]').length == 0){
			bootbox.alert(Translator.t('variance 不能为空!'));
			return false;
		}
		
		if (this.checkVarianceRepeatOrNot()){
			bootbox.alert(Translator.t('variance sku已经重复!'));
			return false;
		}
		var tmpbreak = false;
		var tipMsg = '';
		$("input[name=variance_sku]").each(function(){
			if (this.value=="" && $("input[name=variance_sku]").length>1){
				tipMsg += Translator.t('一个商品中不能存在两个variance的SKU同时为空!');
				tmpbreak = true;
				return false;
			}
		});
		
		var tagsStr = $('#wish_product_tags').val();
		var tagsArr = tagsStr.split(",");
		if (tagsArr.length<2){
			formValidate =false;
			tipMsg += Translator.t('标签不能少于2个，用,号分开。')+'<br />';
		}
	
		var descriptionStr = $('wish_product_description').val();
		var re_d = /\<|\>/;
		if (re_d.test(descriptionStr)){
			formValidate =false;
			tipMsg += Translator.t('描述中不能含有“<”“>”符号。')+'<br />';
		}
		
		var shippingtimeStr = $('#wish_shipping_time').val();
		var re_st = /^\d+\-\d+$/;
		if (!re_st.test(shippingtimeStr)){
			formValidate =false;
			tipMsg += Translator.t('递送时间格式有误，最短天数和最长天数之间用“-”隔开,天数需为整数。')+'<br />';
		}else{
			var timeArr = shippingtimeStr.split("-");
			if (timeArr.length>2){
				formValidate =false;
				tipMsg += Translator.t('递送时间格式有误，只能输入最短天数和最长天数两个天数，中间用“-”隔开。')+'<br />';
			}
			for(var i=0;i<timeArr.length;i++){
				if(timeArr[i]<2){
					formValidate =false;
					tipMsg += Translator.t('递送时间最短必须为2天。')+'<br />';
					break;
				}
			}
			// console.log(timeArr);
			if (parseFloat(timeArr[0])>parseFloat(timeArr[1])){//lzhl 2015-1-27
				$('wish_shipping_time').val(timeArr[1]+'-'+timeArr[0]);
			}
		}
		
		if (tipMsg!==''){
			bootbox.alert(tipMsg);
			$('form button').prop('disabled','');
			return false;
		}
		
		fanbenCapture.setVariance();
		$('input[name=site_id]').val($('#site_id').val())
		fanbenCapture.fillPhotoData();
		
		$.showLoading();
		$.ajax({
			type: "POST",
			url:$('#fanben_capture').attr('action'),
			data:$('#fanben_capture').serialize() , 
			dataType:"json",
			success: function (result) {
				if (result.success){
					fanbenCapture.cancelFormData();
				}else{
					$.hideLoading();
					$('form button').prop('disabled','');
					bootbox.alert(result.message);
				}
				return true;
				
			},
			error :function () {
				$('form button').prop('disabled','');
				return false;
			}
			
		});
		//$('#fanben_capture').submit();
	} , 
	
	cancelFormData:function(){
		var url = '/listing/wish/fan-ben-data-list';
		window.location.href = url;
	} , 
	
	fillVarianceData:function(){
		var colorArr = [],
			sizeArr = [];		
		var sizeList = ['Numbers','Area','Length','Volume','Volume','Weight'];
		$('#mytab a').removeClass('noCss').find('size_tips').remove();
		$('#mytab a[rel="'+fanbenCapture.existingSizeType+'"]').addClass('noCss').append('<span class="size_tips"></span>');	
		sizeType = fanbenCapture.existingSizeType;
		showSize(fanbenCapture.existingSizeType);
		$('#goodsColor input[name="checkbox"]').each(function(){
			colorArr.push($(this).val());
		});
		$('#size_content input[type="checkbox"]').each(function(){
			sizeArr.push($(this).val());
		});
		$.each(fanbenCapture.existtingVarianceList,function(i , n){
			// var addVarianceHtml = "<tr>"
			// +"<td><input class=\"form-control\" type='text' name='variance_sku' value='"+this.sku+"' /></td>"
			// +"<td><input class=\"form-control\" type='text' name='variance_color' value='"+this.color+"' /></td>"
			// +"<td><input class=\"form-control\" type='text' name='variance_size' value='"+this.size+"'></td>"
			// +"<td><input class=\"form-control\" type='text' name='variance_price' value='"+this.price+"'/></td>"
			// +"<td><input class=\"form-control\" type='text' name='variance_shipping' value='"+this.shipping+"'/></td>"
			// +"<td><input class=\"form-control\" type='text' name='variance_inventory' value='"+this.inventory+"'/></td>"
			// +"<td><a class=\"cursor_pointer text-danger\" onclick=\"fanbenCapture.delRow(this)\"><span class=\"glyphicon glyphicon-remove-circle\" aria-hidden=\"true\"></span></a></td></tr>";
			$('#goodsColor input[value="'+this.color+'"]').attr('checked','checked');
			// $('#mytab a').addClass('noCss');
			// $('#mytab a[rel="'+this.size_type+'"]').removeClass('noCss');
			$('#sizeAdd input[value="'+this.size+'"]').attr('checked','true');

			// var colorId = "C"+this.color+"C";
			// var sizeId = "num"+this.size+"num";
			// var addVarianceHtml = "<tr id='"+colorId+"_"+sizeId+"' data-val='"+colorId+"_"+sizeId+"'>"+
			// "<td style='text-align:center;'>"+this.color+"<input type='hidden' name='color' value='"+ this.color +"'/></td>"+
			// "<td style='text-align:center;'>"+this.size+"<input type='hidden' name='color' value='"+ this.size +"'/></td>"+
			// "<td><input type='text' class='form-control' name='sku' value='"+this.sku+"'></td>"+	
			// "<td><input type='text' class='form-control' name='price' value='"+this.price+"'/></td>"+
			// "<td><input type='text' class='form-control' name='inventory' value='"+this.inventory+"'/></td>"+
			// "<td><input type='text' class='form-control' name='shipping' value='"+this.shipping+"'/></td>"+
			// "<td style='text-align:center' onclick='selectpic(this)'>";
			// if(!this.image_url){
			// 	addVarianceHtml += "<span class='glyphicon glyphicon-plus'></span>";
			// }else{
			// 	addVarianceHtml += "<img name='image_url' src='"+this.image_url+"' style='height:50px;'/>";
			// }
			// addVarianceHtml += "</td><td>"+removeBtn+"</td><input type='hidden' name='parent_sku' value='"+this.parent_sku+"'/><input type='hidden' name='fanben_id' type='hidden' value='"+this.fanben_id+"'/></tr>";

			// $('#goodsList').append(addVarianceHtml);

			var color = this.color,
				size = this.size,
				colorId = color.toLowerCase(),
				sizeId = "num" + size.replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "num",
				price = this.price,
				sku = this.sku,
				inventory = this.inventory,
				shipping = this.shipping,
				parent_sku = this.parent_sku,
				image_url = this.image_url;
			var type = fanbenCapture.existingSizeType;
				if(size!=""){
					if($.inArray(escape(size),selSizeArr) == '-1'){
						selSizeArr.push(escape(size));
						if($.inArray(escape(size),sizeList) != '-1'){
							newSizeData.push(escape(size));
						}
					};
				};
				if(color!=""){	
					for (var k = 0;k<otherColorDataB.length;k++){					
						if (otherColorDataB[k].toLowerCase().replace(/ /g,"") == color.toLowerCase().replace(/ /g,"")){
							colorId = otherColorDataB[k];
						}
					};
					var colorId = "C" + unescape(colorId).replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "C";
					if ($.inArray(escape(color).toLowerCase(),selColorArr)=='-1'){
						selColorArr.push(escape(color).toLowerCase());						
					};
					if (size == ''){
						tId = colorId;
					}else{
						tId = colorId + '_' + sizeId;
					}
					
					//回填里size和color数组写入及内容重复判断，创建ID //end
					var str = 
					'<tr id="' + tId +'" data-val="'+tId+'">'+
					'<td>'+color.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input class="form-control" type="hidden" name="color" value="'+ color +'"/></td>'+
					'<td>'+size.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input class="form-control" type="hidden" name="size" value="'+ size +'"/></td>'+
					'<td><input type="text" class="form-control" name="sku" value="'+sku.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'"></td>'+
					'<td><input type="text" class="form-control" name="price" value="'+price+'"/></td>'+			
					'<td><input type="text" class="form-control" name="inventory" value="' + inventory + '"></td>'+
					'<td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"></td>'+
					'<td class="pointer" onclick="selectpic(this)">';
					if(!image_url){
						str += "<span class='glyphicon glyphicon-plus'></span>";
					}else{
						str += "<img name='image_url' src='"+image_url+"' style='height:50px;'/>";
					}
					str +='</td><td >' + removeBtn + '</td></tr>';
					//table里数据回填
					$('#goodsList').append(str);
					//回填非列表内的颜色
					if($.inArray(escape(color).toLowerCase(),colorArr)=='-1' && color != ''){
						colorArr.push(escape(color).toLowerCase());
						$('#goodsColor').append('<div class="col-xs-2 h50"><label class="col-xs-12">' + color.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;") + ' <input type="checkbox" checked="true" name="checkbox" value="'+ escape(color).toLowerCase() +'"></label></div>');
					}else{
						$('#goodsColor input[value="'+ escape(color).toLowerCase() +'"]').attr('checked','checked');
					};	
				}else{//新增加，匹配没有颜色的--------------------------------------------
					tId = 'num'+size+'num';
					//回填里size和color数组写入及内容重复判断，创建ID //end
					var str = '<tr id="' + tId +'" data-val="'+tId+'">'+
					'<td>'+color.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input class="form-control" type="hidden" name="color" value=""/></td>'+
					'<td>'+size.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input class="form-control" type="hidden" name="size" value="'+ size +'"/></td>'+
					'<td><input type="text" class="form-control" name="sku" value="'+sku.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'"></td>'+
					'<td><input type="text" class="form-control" name="price" value="'+price+'"/></td>'+			
					'<td><input type="text" class="form-control" name="inventory" value="' + inventory + '"></td>'+
					'<td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"></td>'+
					'<td class="pointer" onclick="selectpic(this)">';
					if(!image_url){
						str += '<span class="glyphicon glyphicon-plus"></span>';
					}else{

						str +='<img name="image_url" src="'+image_url+'" style="height:50px;"/>';
					}
					str +='</td><td >' + removeBtn + '</td></tr>';
					//table里数据回填
					$('#goodsList').append(str);
				}

				//回填非列表内的颜色end
				//回填非列表内的尺寸
					//console.log(sizeArr);
					//console.log(escape(size));
				if($.inArray(escape(size),sizeArr)=='-1' && size != ''){
					sizeArr.push(escape(size));
					$('#sizeAdd').append('<div class="col-xs-4"><label><input type="checkbox" name="'+type+'" checked="true" value="'+ escape(size) +'">&nbsp' + size.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;") +'</label></div>');
					ajustSizeHeight();
				}else{
					$('#size_content input[value="'+escape(size)+'"]').attr('checked','checked');
				};
		
				$('.goodsList').height($('#goodsList').height() + $('.bgColor1').height()+40);
			});
		},
	
	fillOnlineVarianceData:function(){
		console.log(fanbenCapture.existtingVarianceList);
		$.each(fanbenCapture.existtingVarianceList,function(i , n){
			var $sku = this.sku;
			// var $addinfo = eval('('+ this.addinfo +')');
			// var $msrp = $addinfo['msrp'];
			// var $product_id = $addinfo['product_id'];
			// var $shipping_time = $addinfo['shipping_time'];
			var $color = this.color;
			var $fanben_id = this.fanben_id;
			var $image_url = this.image_url;
			var $inventory = this.inventory;
			var $parent_sku = this.parent_sku;
			var $price = this.price;
			var $shipping = this.shipping;
			var $size = this.size;
			var $enable= this.enable;
			var colorId = "C" + unescape($color.toLowerCase()).replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "C";
			var sizeId = "num" + $size.replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "num";
			var tId;
			if($color !== ''){
				if ($size == ''){
					tId = colorId;
				}else{
					tId = colorId + '_' + sizeId;
				}
				var str = '<tr id="' + tId +'" data-val="'+tId+'">'+
				'<td>'+$color.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input type="hidden" name="color" value="'+ $color +'"/></td>'+
				'<td>'+$size.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input type="hidden" name="size" value="'+ $size +'"/></td>'+
				'<td><input type="text" class="form-control" name="sku" value="'+$sku.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'" disabled></td>'+
				'<td><input type="text" class="form-control" name="price" value="'+$price+'"/></td>'+			
				'<td><input type="text" class="form-control" name="inventory" value="' + $inventory + '"></td>'+
				'<td><input type="text" class="form-control" name="shipping" value="'+ $shipping +'"></td>'+
				'<td class="pointer" onclick="selectpic(this)">';
				if(!$image_url){
						str += "<span class='glyphicon glyphicon-plus'></span>";
					}else{
						str += "<img name='image_url' src='"+$image_url+"' style='width:50px;'/>";
					}
				str +='</td><td><input type="checkbox" name="enable" onclick="enablePost(this)" value="'+$enable+'"';
				if($enable == 'Y'){
					str +='checked';
				}
				str +='></td></tr>';
				//table里数据回填
				$('#goodsList').append(str);
			}else{
					tId = 'num'+$size+'num';

					//回填里size和color数组写入及内容重复判断，创建ID //end
					var str = '<tr id="' + tId +'" data-val="'+tId+'">'+
					'<td>'+$color.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input type="hidden" name="color" value=""/></td>'+
					'<td>'+$size.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'<input type="hidden" name="size" value="'+ $size +'"/></td>'+
					'<td><input type="text" class="form-control" name="sku" value="'+$sku.replace(/[\"]+/g,"&quot;").replace(/[\']+/g,"&apos;")+'" disabled></td>'+
					'<td><input type="text" class="form-control" name="price" value="'+$price+'"/></td>'+			
					'<td><input type="text" class="form-control" name="inventory" value="' +$inventory+ '"></td>'+
					'<td><input type="text" class="form-control" name="shipping" value="'+ $shipping +'"></td>'+
					'<td class="pointer" onclick="selectpic(this)">';
					if(!$image_url){
						str += '<span class="glyphicon glyphicon-plus"></span>';
					}else{

						str +='<img name="image_url" src="'+$image_url+'" style="width:50px;"/>';
					}
					str +='</td><td><input type="checkbox" name="enable" onclick="enablePost(this)" value="'+$enable+'"';
					if($enable == 'Y'){
						str += 'checked';
					}
					str +='></td></tr>';
					//table里数据回填
					$('#goodsList').append(str);
			}
			$('.goodsList').height($('#goodsList').height() + $('.bgColor1').height()+40);	
		});
	},

	fillDemoData:function(key){
		if (key != ""){
			$('#msrp').val(1);
			$('#wish_product_description').val(key)
			$('#site_id').val(123);
			$('#wish_parent_sku').val(key);
			$('#wish_product_name').val(key);
			$('#wish_product_tags').val(key+','+key+key);
			$('#wish_shipping_time').val('11-22');
			$('#wish_fanben_status').val('editing');
			$('#main_image').val('http://img.1985t.com/uploads/attaches/2012/03/4622-MaRwwp.jpg');
			$('input[name=variance_'+'sku'+']').val('sku'+key);
			$('input[name=variance_'+'color'+']').val('red');
			$('input[name=variance_'+'size'+']').val('XXL');
			$('input[name=variance_'+'price'+']').val(5);
			$('input[name=variance_'+'shipping'+']').val(6);
			$('input[name=variance_'+'inventory'+']').val(7);
		}
	},
	
	checkVarianceRepeatOrNot:function(){
		return this.checkRepeatOrNot('input[name=variance_sku]');
	},
	
	checkRepeatOrNot:function(selector){
		var checkArr = new Array();
		$(selector).each(function(){
			if ($.trim(this.value) != "")
			checkArr.push(this.value);
		});
		
		if (checkArr.length == 0) return false;
		var hash = {};
		for(var i in checkArr) {
			if(hash[checkArr[i]])
				return true;
			hash[checkArr[i]] = true;
		}
		
		return false;
	},
	
	editMethod:function(){
		$('#wish_product_id ,#wish_parent_sku ,  #wish_fanben_status').prop('readonly','readonly');	
		$('#site_id').prop('disabled','disabled');
	},
	
	initPhotosSetting:function(){
		$('div[role="image-uploader-container"]').batchImagesUploader({
			localImageUploadOn : true,   
			fromOtherImageLibOn: true , 
			imagesMaxNum : 11,
			fileMaxSize : 500 , 		
			fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
			maxHeight : 100, 		
			maxWidth : 100,
			initImages : fanbenCapture.existingImages, 
			fileName: 'product_photo_file',
			onUploadFinish : function(imagesData , errorInfo){
				//debugger
			},
			
			onDelete : function(data){
	//			debugger
			}

		});

	
	}
	
}

$(function() {
	fanbenCapture.init();
});