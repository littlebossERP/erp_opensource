if (typeof varRestore === 'undefined')  customProduct = new Object();

varRestore = {
	init:function(){
//		variation_json=window.opener.$('#variation').val();
		variation_json=$('#variation').val();
		if (variation_json != undefined && variation_json.length>1){
			//如果有默认值要加载，删除原先的赋值.
//			$('.hassku').remove();
			
//			var variationObj=window.opener.$.parseJSON(variation_json);
			var variationObj=$.parseJSON(variation_json);
			if (typeof(variationObj) != 'undefined'){
				$('#create').click();
				nvl=variationObj.VariationSpecificsSet.NameValueList;
				var siteid = $('select[name="siteid"]').val();
				var primaryid = $('input[name="primarycategory"]').val();
				for (var i = 0; i<nvl.length; i++){
//					add(null,nvl[i].Name);
					addNew('span[title="添加属性"]',primaryid,siteid);
					var pre_obj = $('span[title="添加属性"]').parents('th').prev();
					hideInput(pre_obj,'th',nvl[i].Name);
				}
				v_row=variationObj.Variation;
				for (var i = 0; i<v_row.length; i++){
//					pic_url=variationObj.Pictures[i].VariationSpecificPictureSet.PictureURL;
					addItem(v_row[i]['SKU'],v_row[i]['StartPrice'],v_row[i]['Quantity'],v_row[i].VariationSpecifics.NameValueList,v_row[i].VariationProductListingDetails);
				}
//				$('#variation_table').find('.deleteRow:first').trigger('click');
				//设置图片
				pic_key(); 
				$('input[name=assoc_pic_key]').each(function(){
					if ($(this).val() == variationObj.assoc_pic_key){
						$(this).attr('checked','checked');
					}
				});
				$('#setPic').click();
				
				for (var i = 0; i<v_row.length; i++){
					var pic_urls = variationObj.Pictures[i].VariationSpecificPictureSet.PictureURL;
					if($.isArray(pic_urls)){//新图片数据格式
						for(var k = 0;k < pic_urls.length; k++){
							AddVariationimgurl(pic_urls[k],i);
						}
					}else{
						AddVariationimgurl(pic_urls,i);
					}
				}
				
			}
			$('#variation_table tr:eq(1)').remove();
		}
	},

}; 


$(document).ready(function(){
//	var siteid= window.opener.document.getElementById('siteid').value;
	var siteid= document.getElementById('siteid').value;
	$("#siteidbyp").val(siteid);
});

function mulsetquantity(){
	var type=$('#mulset_quantity_type').val();
	$("input[name^=v_quantity]").each(function(a,b){
		oldvalue=$("input[name^=v_quantity]").eq(a).val();
		newvalue=calcul(oldvalue,$("input[name^=mulset_quantity]").val(),type);
		$("input[name^=v_quantity]").eq(a).val(newvalue);
	});
}
function mulsetprice(){
	var type=$('#mulset_price_type').val();
	$("input[name^=price]").each(function(a,b){
		oldvalue=$("input[name^=price]").eq(a).val();
		newvalue=calcul(oldvalue,$("input[name^=mulset_price]").val(),type);
		$("input[name^=price]").eq(a).val(newvalue);
	});
}

function calcul(a,b,type){
	switch(type){
		case '=':
			return b;break;
		case '+':
			var baseNum,baseNum1,baseNum2;
			try {
				    baseNum1 = a.toString().split(".")[1].length;
				} catch (e) {
				    baseNum1 = 0;
				}
			try {
					baseNum2 = b.toString().split(".")[1].length;
				} catch (e) {
				    baseNum2 = 0;
				}
				baseNum = Math.pow(10, Math.max(baseNum1, baseNum2));
			 return (a * baseNum + b * baseNum) / baseNum;
//			return Number(a)+Number(b);break;
		case '-':
			var baseNum, baseNum1, baseNum2;
			    var precision;// 精度
			    try {
			        baseNum1 = b.toString().split(".")[1].length;
			    } catch (e) {
			        baseNum1 = 0;
			    }
			    try {
			        baseNum2 = a.toString().split(".")[1].length;
			    } catch (e) {
			        baseNum2 = 0;
			    }
			    baseNum = Math.pow(10, Math.max(baseNum1, baseNum2));
			    precision = (baseNum1 >= baseNum2) ? baseNum1 : baseNum2;
			    return ((a * baseNum - b * baseNum) / baseNum).toFixed(precision);
						
			//return Number(a)-Number(b);break;
		case '*':
			var baseNum = 0;
			    try {
			        baseNum += a.toString().split(".")[1].length;
			    } catch (e) {
			    }
			    try {
			        baseNum += b.toString().split(".")[1].length;
			    } catch (e) {
			    }
			    return Number(a.toString().replace(".", "")) * Number(b.toString().replace(".", "")) / Math.pow(10, baseNum);
			//return Number(a)*Number(b);break;
		case '/':
			var baseNum1 = 0, baseNum2 = 0;
			    var baseNum3, baseNum4;
			    try {
			        baseNum1 = a.toString().split(".")[1].length;
			    } catch (e) {
			        baseNum1 = 0;
			    }
			    try {
			        baseNum2 = b.toString().split(".")[1].length;
			    } catch (e) {
			        baseNum2 = 0;
			    }
			    with (Math) {
			        baseNum3 = Number(a.toString().replace(".", ""));
			        baseNum4 = Number(b.toString().replace(".", ""));
			        return (baseNum3 / baseNum4) * pow(10, baseNum2 - baseNum1);
			    }
						
			//return Number(a)/Number(b);break;
	}
}
/**
 * 恢复Variation
 */
//function restore(){
////	variation_json=window.opener.$('#variation').val();
//	variation_json=$('#variation').val();
//	if (variation_json != undefined && variation_json.length>1){
//		//如果有默认值要加载，删除原先的赋值.
////		$('.hassku').remove();
//		
////		var variationObj=window.opener.$.parseJSON(variation_json);
//		var variationObj=$.parseJSON(variation_json);
//		if (typeof(variationObj) != 'undefined'){
//			$('#create').click();
//			nvl=variationObj.VariationSpecificsSet.NameValueList;
//			var siteid = $('select[name="siteid"]').val();
//			var primaryid = $('input[name="primarycategory"]').val();
//			for (i in nvl){
////				add(null,nvl[i].Name);
//				addNew('span[title="添加属性"]',primaryid,siteid);
//				var pre_obj = $('span[title="添加属性"]').parents('th').prev();
//				hideInput(pre_obj,'th',nvl[i].Name);
//			}
//			v_row=variationObj.Variation;
//			for (i in v_row){
////				pic_url=variationObj.Pictures[i].VariationSpecificPictureSet.PictureURL;
//				addItem(v_row[i]['SKU'],v_row[i]['StartPrice'],v_row[i]['Quantity'],v_row[i].VariationSpecifics.NameValueList,v_row[i].VariationProductListingDetails);
//			}
////			$('#variation_table').find('.deleteRow:first').trigger('click');
//			//设置图片
//			pic_key(); 
//			$('input[name=assoc_pic_key]').each(function(){
//				if ($(this).val() == variationObj.assoc_pic_key){
//					$(this).attr('checked','checked');
//				}
//			});
//			$('#setPic').click();
//			
//			for (var i in v_row){
//				var pic_urls = variationObj.Pictures[i].VariationSpecificPictureSet.PictureURL;
//				if($.isArray(pic_urls)){//新图片数据格式
//					for(var k = 0;k < pic_urls.length; k++){
//						AddVariationimgurl(pic_urls[k],i);
//					}
//				}else{
//					AddVariationimgurl(pic_urls,i);
//				}
//			}
//			
//		}
//	}
//}
//restore();
/**
 * 增加属性名
 */

function hideInput(obj,type,name){
	if(type == 'th'){
		if($(obj).find('a[data-id="' + name + '"]').length > 0){
			$(obj).find('a[data-id="' + name + '"]').click();
		}else{//回填自定义的input框
			var tkey = $(obj).attr('tkey'); 
			$('td[tkey="' + tkey + '"] input').each(function(){//td赋值
				$(this).attr('name',nameStringEncode(name)+'[]');
			});
			$(obj).find('input').val(name);
			$(obj).find('input').css('display','none');
			$(obj).find('span[name="thName"]').html('&nbsp;&nbsp;' + name + '&nbsp;&nbsp;')
		}
	}else{
		$(obj).find('input').css('display','none');
		$(obj).find('span[name="tdName"]').html(name + '&nbsp;&nbsp;')
	}
}

function addNew(obj,primarycategory,siteid){
	if($('th[tkey]').length < 5){
		var check_empty = true;
		$('th[tkey] input').each(function(){
			if($(this).val().replace(/ /g,'').length == 0){
				check_empty = false;//检查是否有空的属性列
			}
		});
		if(check_empty){
			if($('th[tkey]').length == 4){//只允许添加5个属性
				$('span[title="添加属性"]').css('display','none');
			}
			var attrArray = [];
			$('th[tkey]').each(function(){
				var value = $(this).find('input').val();
				if(value.replace(/ /g,'').length > 0){
					attrArray.push(value);
				}
			})
			$.showLoading();
			$.ajax({
		        type: 'POST',
		        url: "/listing/ebaymuban/get-attribute?primarycategory="+ primarycategory + "&siteid=" + siteid,
		        async:false,
		        dataType: 'json',
		        success: function (data) {
		        	$.hideLoading();
		            if (data.code == 200) {
		            	var name = '<a href="javascript:void(0);" data-id="0">重新选择</a>';
		            	for(var i = 0; i<data.data.length; i++){
		            		if($.inArray(data.data[i].name,attrArray) == -1){//不存在则添加选项，保证不重复
		            			name += '<a href="javascript:void(0);" data-id="' + data.data[i].name + '">' + data.data[i].name + '</a>';
		            		}
		            	}
//		            	$.alertBox(name);
		            } else if(data.code == 220){
		            	var name = '<a href="javascript:void(0);" data-id="0">重新选择</a>';
		            }
		            var len = $(obj).parents('tr').find('th').length - 1;
		            $('#variation_table tr').find('td:last').before('<td tkey="' + len + '"><div style="float:left"><input onblur="tdSetName(this.value,this)" name="" class="iv-input" style="width:75px"><span name="tdName"></span></div><div style="float:left"><a class="on_click" onclick="showDetail(this)"><span>>></span></a><div class="deSelect"><a href="javascript:void(0);" data-id="0">输入属性</a></div></div></td>')
		        	$('#variation_table tr').find('th:last').before('<th tkey="' + len + '"><div style="float:left"><span style="cursor: pointer;" onclick="deleteCol(this)" class="glyphicon glyphicon-remove" title="删除该属性"></span><input style="width:75px;color:black;" required="required" name="nvl_name[]" class="iv-input" style="color:black;" size=15 value="" aname="" onblur="setnametmp(this.value,this)"><span name="thName"></span></div><div style="float:left"><a class="on_click" onclick="showAttribute(this)"><span>>></span></a><div class="ddSelect">' + name +'</div></div></th>')

		        },
		        error: function () {
		        	$.hideLoading();
		        	$.alertBox("网络错误！");
		        }
		    });
		}else{
			$.alert('请先填写空的属性列');
		}
	}
//	var len = $(obj).parents('tr').find('th').length - 1;
//	$('#variation_table tr').find('td:last').before('<td><input size=15 name="'+nameStringEncode(name)+'[]" class="iv-input"></td>')
//	$('#variation_table tr').find('th:last').before('<th><input required="required" name="nvl_name[]" class="iv-input"  style="color:black;" size=15 value="'+name+'" aname="'+name+'" onfocus="$(\'#nametmp\').val(this.value)" onkeyup="updata($(this))" onblur="setnametmp(this.value)"><span style="cursor: pointer;" onclick="deleteCol(this)" class="glyphicon glyphicon-remove" title="删除该属性"></span></th>')
//	$('#variation_table tr').find('td:last').before('<td tkey="' + len + '"><input name="" class="iv-input" style="width:100%"></td>')
//	$('#variation_table tr').find('th:last').before('<th tkey="' + len + '"><div style="float:left"><span style="cursor: pointer;" onclick="deleteCol(this)" class="glyphicon glyphicon-remove" title="删除该属性"></span><input style="width:75px;color:black;" required="required" name="nvl_name[]" class="iv-input" style="color:black;" size=15 value="" aname="" onblur="setnametmp(this.value,this)"></div><div style="float:left"><a onclick="showAttribute(this)"><span>>></span></a><div class="ddSelect">' + name +'</div></div></th>')
}

//function add(obj,name){
////	if (name == null){
////		bootbox.prompt('请输入属性名',function(result){
////			name = result;
////			if(name != null){
////				handleName(name);
////			}
////			
////		});
////	}
//	if(name != undefined){
//		duplicate=false;
//		$('input[name^=nvl_name]').each(function(){
//			if (name.toLowerCase() == $(this).val().toLowerCase()){
//				duplicate=true;
//			}
//		});
//		if (duplicate ){
//			bootbox.alert('属性名重名！');
//			return;
//		}
//
//		if (name.length>0 && name != 'null'){
//			$('#variation_table tr').find('td:last').before('<td><input size=15 name="'+nameStringEncode(name)+'[]" class="iv-input"></td>')
//			$('#variation_table tr').find('th:last').before('<th><input required="required" name="nvl_name[]" class="iv-input"  style="color:black;" size=15 value="'+name+'" aname="'+name+'" onfocus="$(\'#nametmp\').val(this.value)" onkeyup="updata($(this))" onblur="setnametmp(this.value)"><span style="cursor: pointer;" onclick="deleteCol(this)" class="glyphicon glyphicon-remove" title="删除该属性"></span></th>')
//		}
//		pic_key();
//	}
//}


//图片关联时时更新
function updata(obj){
	var name=obj.attr('aname')
		$('input[name=assoc_pic_key]').each(function(){
			var a=$(this).next().text();
			if(a==name){
				$(this).next().text(obj.val())
				$(this).val(obj.val())
				obj.attr('aname',obj.val())
			}
		})
}
/**
 *  增加行
 */
function addItem(sku,price,quantity,nvl,select_val = null){
	row=$('#variation_table').find('tr:last').clone().find(':input').val('').end();
	//需要设置lable的id值，和选择商品按钮的vvid值，这两者的值都是一样的
	
	
	//获取编号
	labelid = parseInt(row.find('label').attr('id').replace('displaybp', ''));
	labelid++;
	
	if (quantity == null){
		row.find('label').attr('id', 'displaybp'+labelid.toString());
		row.find('input[type=button]').attr('vvid', 'displaybp'+labelid.toString());
		row.appendTo('#variation_table');
	}else {
		row.find('label').attr('id', 'displaybp'+labelid.toString());
		row.find('input[type=button]').attr('vvid', 'displaybp'+labelid.toString());
		
		row.find('input[name^=v_sku]').val(sku).end()
		.find('input[name^=v_quantity]').val(quantity).end()
		.find('input[name^=price]').val(price).end();
		
		if(select_val != null){
			for(var t in select_val){
				row.find('input[name^=v_productid_val]').val(select_val[t]).end();
			}
		}
		
		if(nvl != null){
			for (var i = 0;i < nvl.length;i++){
				row.find('input[name^='+nameStringEncode(nvl[i].Name)+']').val(nvl[i].Value).end();
				var tdObj = row.find('input[name^='+nameStringEncode(nvl[i].Name)+']').parents('td');
				hideInput(tdObj,'td',nvl[i].Value);
			}
		}
		row.appendTo('#variation_table');
	}
}
/**
 * 删除属性
 */
function deleteCol(obj){
//	colname=$(obj).prev().val();
	colname=$(obj).next().val();
//	$(obj.parentNode).remove();
	$(obj).parents("th").remove();
	if(colname.replace(/ /g,'').length > 0){
		$('#variation_table').find('input[name^='+nameStringEncode(colname)+']').each(function(){
			$(this).parents('td').remove();
		});
	}else{//无属性名的时候删除
		var id = $(obj).parents('th').attr('tkey');
		$('#variation_table td[tkey="' + id + '"]').each(function(){
			$(this).remove();
		});
	}
	if($('th[tkey]').length < 5){
		$('span[title="添加属性"]').css('display','block');
	}
	pic_key();
}
/**
 * 组织图片关联
 */
function pic_key(){
	var check = false;//检查是否有效值
	$('#assoc_pic_key').empty();
	$('input[name^=nvl_name]').each(function(){
		if($(this).val().replace(/ /g,'').length > 0){
			$('<span><input type=radio name=assoc_pic_key value="'+$(this).val()+'"><span>'+$(this).val()+'</span></span>')
			.appendTo($('#assoc_pic_key'));
			check = true;
		}
	});
	$('input[name=assoc_pic_key]:eq(0)').attr('checked','checked');
	if($('th[tkey]').length > 0 && check){
		$('#setPic').css('display','inline');
	}else{
		$('#setPic').css('display','none');
	}
}
/**
 * 删除行
 */
function deleteRow(obj){
	if($('#variation_table tr').length <3){
		alert('不能删除最后一项');
		return false;
	}
	$(obj.parentNode.parentNode).remove();
}
/**
 * 对含有特殊字符的name字符串进行编码
 */
function nameStringEncode(name){
	return name.replace(/ /g,'#space#')
		.replace(/'/g,'#squote#')
		.replace(/"/g,'#dquote#')
		.replace(/&/g,'#and#')
		.replace(/:/g,'#colon#')
		.replace(/\(/g,'#leftbrackets#')
		.replace(/\)/g,'#rightbrackets#')
		.replace(/\//g,'#xie#')
		.replace(/\*/g,'#mi#');
}
var selecSKUobj=null;
function goodselected(sku,lid,imgurl){
	if (isDuplicateSKU(sku)){
		alert('SKU不能重复选择')
		return false;
	}
	$(selecSKUobj).prev().val(sku);
	$(selecSKUobj).parents('tr').find('input[name^=img]').val(imgurl);
}
function skucustomlable(sku,some,imgurl){
	if (isDuplicateSKU(sku)){
		alert('SKU不能重复选择')
		return false;
	}
	$(selecSKUobj).prev().val(sku);
	$(selecSKUobj).parents('tr').find('input[name^=img]').val(imgurl);
}
function isDuplicateSKU(sku){
	d=false;
	$('input[name^=v_sku]').each(function(){
		if ($(this).val() ==sku){
			d=true;
		}
	});
	return d;
}
//function saveVariation(obj){
//	if($('#variation_table tr th').length <7){
//		$.alert('请添加属性项');
//		return false;
//	}
//	check=true;
//	$('#variation_table input').each(function(){
//		if($(this).attr('name') !='sku[]' && $(this).attr('name') !='img[]' && $(this).val().replace(/ /g,'').length <1){
//			check=false;
////			$(this).css('backgroundColor','red');
//		}
//	});
//	if (check == false){
//		$.alert('请填写多属性项');
//		return false;
//	}
	
	//去掉填写内容的前后空格
//	$('#variation_table input').each(function(){
//		$(this).attr('value', $.trim($(this).val()));
//	});
	
//	$(obj).parents('form').submit();
//	$.ajax({//与下面的保存一样
//        type: 'POST',
//        url: '/listing/ebaymuban/save-variation',
//        data:$('#save-variation').serialize(),
//        dataType: 'json',
//        success: function (data) {
//            $.alertBox(data);
//        },
//        error: function () {
//        	$.alertBox("网络错误！");
//        }
//    });
	
//}
function setnametmp(value,obj){
//	inputname=$('#nametmp').val();
//	$('input[name^='+inputname+']').each(function(){
//		$(this).attr('name',value+'[]');
//	});
//	$('input[name=assoc_pic_key]'.val(value));
	var tkey = $(obj).parents('th').attr('tkey');
	if(value.replace(/ /g,'').length > 0){
		
		var duplicate = false;//检查属性名重复
		$('input[name^=nvl_name]').each(function(){
			if (value.toLowerCase() == $(this).val().toLowerCase() && $(obj).parents("th").attr("tkey") != $(this).parents("th").attr("tkey")){
				duplicate=true;
			}
		});
		
		if (duplicate ){
			bootbox.alert('属性名重名！');
			return;
		}
		
		$(obj).css('display','none');
		$(obj).next().html('&nbsp&nbsp' + value + '&nbsp&nbsp');
		
		$('td[tkey="' + tkey + '"] input').each(function(){
			$(this).attr('name',nameStringEncode(value)+'[]');
		});
	}
	pic_key();
}

function tdSetName(value,obj){
//	var tkey = $(obj).parents('th').attr('tkey');
	if(value.replace(/ /g,'').length > 0){
		$(obj).css('display','none');
		$(obj).val(value);
		$(obj).next().html(value + '&nbsp&nbsp');
	}
}

function showAttribute(obj){
	//清空其他下拉框
	$('div[class="deSelect"]').each(function(){
		if($(this).css('display') == 'block'){
			$(this).css('display','none');
		}
	});
	$('div[class="ddSelect"]').each(function(){
		if($(this).css('display') == 'block'){
			$(this).css('display','none');
		}
	});
	
	if($(obj).next().css('display') == 'none'){
		$(obj).next().css('display','block');
	}else{
		$(obj).next().css('display','none');
	}
//	$(obj).parents('th').siblings('th[tkey]').each(function(){//所有同级th下拉关闭
//		$(this).find('div[class="ddSelect"]').css('display','none');
//	});
	event.stopPropagation();//防止触发全局事件
}

function showDetail(obj){
	$('div[class="deSelect"]').each(function(){
		if($(this).css('display') == 'block'){
			$(this).css('display','none');
		}
	});
	$('div[class="ddSelect"]').each(function(){
		if($(this).css('display') == 'block'){
			$(this).css('display','none');
		}
	});
	if($(obj).next().css('display') == 'none'){
		$(obj).next().css('display','block');
	}else{
		$(obj).next().css('display','none');
	}
	event.stopPropagation();
}
//替换显示属性值名
$(document).on('click', 'div[class="ddSelect"] a', function () {
	var selectName = $(this).html();
	if($(this).data('id') == 0){
		$(this).parents('div[class="ddSelect"]').css('display','none');
		$(this).parents('th').find('input').css('display','');
		$(this).parents('th').find('input').focus();
		$(this).parents('th').find('span[name="thName"]').html('');
	}else if(selectName != $(this).parents('th').find('input').val()){
		var input_obj = $(this).parents('th').find("input");
		//选定后隐藏input，show th name
		input_obj.val(selectName);
		input_obj.css('display','none');
		input_obj.next().html('&nbsp&nbsp' + selectName + '&nbsp&nbsp');
		$(this).parents('div[class="ddSelect"]').css('display','none');
		//对应所有td的处理
		var tkey = $(this).parents('th').attr('tkey');
//		var id  = $(this).data('id');
		var siteid = $('select[name="siteid"]').val();
		var primaryid = $('input[name="primarycategory"]').val();
		$('td[tkey="' + tkey + '"] input').each(function(){//td赋值
			$(this).attr('name',nameStringEncode(selectName)+'[]');
			$(this).val('');
			$(this).next('span[name="tdName"]').html('');
			$(this).css('display','');
		});
		
		$.showLoading();
		$.ajax({
	        type: 'POST',
//	        url: "/listing/ebaymuban/get-detail-attribute?id="+ id,
	        url: "/listing/ebaymuban/get-detail-attribute?selectName="+ selectName + "&siteid="+ siteid + "&primaryid=" + primaryid,
	        dataType: 'json',
	        success: function (data) {
	        	$.hideLoading();
	            if (data.code == 200) {
	            	var name = '<a href="javascript:void(0);" data-value="">输入属性</a>';
	            	for(var i = 0; i<data.data.length; i++){
	            		name += '<a href="javascript:void(0);" data-value="' + data.data[i] + '">' + data.data[i] + '</a>';
	            	}
//	            	name += '<a href="javascript:void(0);" data-value="">输入属性</a>';
//	            	$.alertBox(name);
	            } else if(data.code == 220){
	            	var name = '<a href="javascript:void(0);" data-value="">输入属性</a>';
	            }
	            
	            $('td[tkey="' + tkey + '"]').each(function(){//td赋值
	        		$(this).find('div[class="deSelect"]').html(name);
	        	});

	        },
	        error: function () {
	        	$.hideLoading();
	        	$.alertBox("网络错误！");
	        }
	    });
		pic_key();
	}
});

$(document).on('click', 'div[class="deSelect"] a', function () {
	var selectName = $(this).html();
	if(selectName == '输入属性'){
		$(this).parents('div[class="deSelect"]').css('display','none');
		$(this).parents('td').find('input').css('display','');
		$(this).parents('td').find('input').focus();
		$(this).parents('td').find('span[name="tdName"]').html('');
	}else{
		var input_obj = $(this).parents('td').find("input");
		//选定后隐藏input，show th name
		input_obj.val(selectName);
		input_obj.css('display','none');
		input_obj.next().html(selectName + '&nbsp&nbsp');
		$(this).parents('div[class="deSelect"]').css('display','none');
	}
});

//点击其他地方隐藏其他下拉
$('.main-box').click(function(event){
	$('div[class="deSelect"]').each(function(){
		if($(this).css('display') == 'block'){
			$(this).css('display','none');
		}
	});
	$('div[class="ddSelect"]').each(function(){
		if($(this).css('display') == 'block'){
			$(this).css('display','none');
		}
	});
});



function AddVariationimgurl(src,key) {
	if (typeof (src) == 'undefined' || src == null) {
		src = '';
	}
	$('#Variation_imgurl_' + key)
			.append(
					"<div><img src='"
							+ src
							+ "' width='50' height='50'> <input type='text' class='iv-input' id='img"
							+ (Math.random() * 10000).toString()
									.substring(0, 4)
							+ "' name='img["+ key +"][]' size='60'  onblur='javascript:imgurl_input_blur(this)' value="
							+ src
							+ "> <input class='iv-btn btn-search' type='button' value='删除' onclick='delImgUrl_input(this)'> <input class='iv-btn btn-search' type='button' value='本地上传' onclick='javascript:Variation_localimgup(this)' ></div>");
}
//本地上传图片
//function imgurl_input_blur(obj) {
//	var t = $(obj).val();
//	$(obj).parent().children('img').attr('src', t);
//}

//本地上传图片
function Variation_localimgup(obj){
	var tmp='';
	$('#variation_img_tmp').unbind('change').on('change',function(){
		$.showLoading();
		$.uploadOne({
		     fileElementId:'variation_img_tmp', // input 元素 id
			 //当获取到服务器数据时，触发success回调函数 
			 //data: 上传图片的原图和缩略图的amazon图片库链接{original:... , thumbnail:.. } 
			 onUploadSuccess: function (data){
				 $.hideLoading();
		    	 tmp = data.original;
		    	 $(obj).parent().children('input[type="text"]').val(tmp);
		    	 $(obj).parent().children('img').attr('src',tmp);
		     },
		     		     
		     // 从服务器获取数据失败时，触发error回调函数。  
		     onError: function(xhr, status, e){
		    	 $.hideLoading();
				 alert(e);
		     }
		});
	});
	$('#variation_img_tmp').click();
}

function setPics(){
	var name = $('input[name="assoc_pic_key"]:checked').val();
	if(name != ''&&name != undefined){
		if($('input[name^='+ nameStringEncode(name) +']').length > 0){
			var varHtml = '',i = 0;
			var valArray = [];
			$('input[name^='+ nameStringEncode(name) +']').each(function(){
				if($(this).val().replace(/ /g,'').length > 0 && $.inArray($(this).val(),valArray) == -1){
					valArray.push($(this).val());
					varHtml += '<tr class="trKey">' +
							      '<td>' + 
							      	'<div style="margin-bottom: 10px;padding-top: 10px;"><span>'+ name +'</span>：'+ $(this).val() +'</div>' + 
							        '<div id="Variation_imgurl_' + i + '"></div>' + 
							    	'<button type="button" style="margin: 5px 0px;" class="iv-btn btn-search" onclick="javascript:AddVariationimgurl(null,' + i + ');return false;">添加一张图片</button>' +
							        '<input type="file" id="variation_img_tmp" class="hidden" name="variation_product_photo_file" value="">' +
							    '</td>' +
							  '</tr>';
					i = i + 1;
				}
			});
			$('#varTable tr[class="trKey"]').remove();
			$('#varTable').append(varHtml);
		}
	}
}

$('input[name="isMuti"]').click(function(){
	if($(this).val() == 1){
		$('#create').css('display','block');
		$('#create').click();
		$('#mutiattribute').css('display','block');
	}else{
		$('#create').css('display','none');
		$('#mutiattribute').css('display','none');
	}
});
