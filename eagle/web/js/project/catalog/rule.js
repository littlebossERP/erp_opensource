/**
 * sku解析规则js
 */

	$(function () {
		var elems = Array.prototype.slice.call(document.querySelectorAll(".switchery-colored"));
		elems.forEach(function(html) {
			var color = $(html).data("switchery-color");
			var switchery = new Switchery(html, { color: color });
		});
		
		$(".skugrouplabel .switchery").on("click",function(){
			$(".skugrouplabel .moreinfo").toggle();
		});
		
		//组合sku编号示例
		$(".skugrouplabel .dropdown-menu:eq(0) li:eq(0)").on("click",function(){
			$(".skugroupexample .label-first").removeClass("text-orange").addClass("text-primary");
			$(".skugroupexample .label-first:eq(0)").html("A00001");
			$(".skugroupexample .label-first:eq(1)").html("B00002");
		});
		$(".skugrouplabel .dropdown-menu:eq(0) li:eq(1)").on("click",function(){
			$(".skugroupexample .label-first").removeClass("text-primary").addClass("text-orange");
			$(".skugroupexample .label-first:eq(0)").html("1");
			$(".skugroupexample .label-first:eq(1)").html("3");
		});
		$(".skugrouplabel .dropdown-menu:eq(1) li:eq(0)").on("click",function(){
			$(".skugroupexample .label-last").removeClass("text-orange").addClass("text-primary");
			$(".skugroupexample .label-last:eq(0)").html("A00001");
			$(".skugroupexample .label-last:eq(1)").html("B00002");
		});
		$(".skugrouplabel .dropdown-menu:eq(1) li:eq(1)").on("click",function(){
			$(".skugroupexample .label-last").removeClass("text-primary").addClass("text-orange");
			$(".skugroupexample .label-last:eq(0)").html("1");
			$(".skugroupexample .label-last:eq(1)").html("3");
		});
		$(".skugrouplabel .form-control.text-center:eq(0)").on("keyup",function(){
			$(".skugroupexample .connector-first").html($(this).val());
		});
		$(".skugrouplabel .form-control.text-center:eq(1)").on("keyup",function(){
			$(".skugroupexample .connector-last").html($(this).val());
		});
		pageInit();

		function pageInit()
		{
			var isManageWarehouse = '1';
			var isAutoAnalyzeComboSku = '1';
			var isAllowNegativeStock = '1';
			var analyzeComboSkuFormula = 'sku||*||quantity||+';
			var quantityConnector = $('#quantityConnector').val();
			var skuConnector = $('#skuConnector').val();
			var firstKey = $('#firstKey').val();
			var secondKey = $('#secondKey').val();
			var stopDownloadOrderEndTime = "";
			var stopDownloadOrderStartTime = "";
			//console.log('firstKey:'+firstKey,'secondKey:'+secondKey);
			if(firstKey == 'quantity'){
				$(".skugrouplabel .dropdown-menu:eq(0) li:eq(1)").trigger("click");
			}

			if(secondKey == 'sku'){
				$(".skugrouplabel .dropdown-menu:eq(1) li:eq(0)").trigger("click");
			}

			if(isManageWarehouse == 1){
				$("span[name=isManageWarehouse]").trigger('click');
			}
			if(isAutoAnalyzeComboSku == 1){
				$("span[name=isAutoAnalyzeComboSku]").trigger('click');
			}
			if(isAllowNegativeStock == 1){
				$("span[name=isAllowNegativeStock]").trigger('click');
			}
			if(stopDownloadOrderEndTime != ""){
				$("select[name=stopDownloadOrderEndTime]").val(stopDownloadOrderEndTime);
			}
			if(stopDownloadOrderStartTime != ""){
				$("select[name=stopDownloadOrderStartTime]").val(stopDownloadOrderStartTime);
			}
			if(firstKey == 'sku'){
				$("#firstKeyTest").html('SKU');
				$("#secondKeyTest").html('数量');
			}else if(firstKey == 'quantity'){
				$("#firstKeyTest").html('数量');
				$("#secondKeyTest").html('SKU');
			}
		}
		
		
	});

function doSaveSystemlConfig()
{
	$('.has-error').removeClass('has-error').find('#pErrorMessage').remove();
	$('.loading_large').show();
	//获取到用户要存储的系统配置参数
	var $form = $("form[name=systemConfig]");
	//传送给php action来处理
	$.ajax({
		type: "POST",
		dataType: 'json',
		url: "http://www.mabangerp.com/index.php?mod=system.doSaveSystemConfig",
		data: $form.serialize(),
		success: function(r){
			$('.loading_large').fadeOut();
			if(r.success) {	
				successMessage("保存成功", '')
			}
			else {
				failMessage("保存失败",r.message,r.id);
			}
		}
	});
}

function failMessage(title, text,id){
	$.gritter.add({
		class_name:'gritter-error',
		title: title,
		text: text,
		time: 2000,
		sticky: false
	});
	$('#'+id).parent().addClass('has-error');
	return true;
}
function successMessage(title, text)
{
	$.gritter.add({
		class_name:'gritter-success',
		title: title,
		text: text,
		time: 2000,
		sticky: false
	});
	return true;
}

function setKeys2(SkuId1, QutId2){
	setKey2Sku(SkuId1);
	setKey2Quantity(QutId2);
	$("#"+SkuId1).parent().find("button").find("span[class=text]").html('SKU');
	$("#"+QutId2).parent().find("button").find("span[class=text]").html('数量');
	if(SkuId1 == 'firstKey'){
		$(".skugroupexample .label-first").removeClass("text-orange").addClass("text-primary");
		$(".skugroupexample .label-first:eq(0)").html("A00001");
		$(".skugroupexample .label-first:eq(1)").html("B00002");
		$(".skugroupexample .label-last").removeClass("text-primary").addClass("text-orange");
		$(".skugroupexample .label-last:eq(0)").html("1");
		$(".skugroupexample .label-last:eq(1)").html("3");
	}else{
		$(".skugroupexample .label-last").removeClass("text-orange").addClass("text-primary");
		$(".skugroupexample .label-last:eq(0)").html("A00001");
		$(".skugroupexample .label-last:eq(1)").html("B00002");
		$(".skugroupexample .label-first").removeClass("text-primary").addClass("text-orange");
		$(".skugroupexample .label-first:eq(0)").html("1");
		$(".skugroupexample .label-first:eq(1)").html("3");
	}
}

function setKey2Sku(id)
{
	$('#'+id).val('sku');
}

function setKey2Quantity(id)
{
	$('#'+id).val('quantity');	
}
if (typeof productList === 'undefined')  productList = new Object();

productList.list={
		addTagHtml:function(obj){
			var thisObj = $(obj).parents(".form-group");
			//alert($(obj).text());
			var addContent = "<div class=\"form-group\" style=\"float:left;width:100px;vertical-align:middle;margin:0px;\">"+
			"<label for=\"Product_tag\" class=\"col-sm-3 control-label\"  style=\"float:left;width:20%;padding:6px 0px;\">"+
			"<a onclick=\"productList.list.delete_form_group(this)\"><span class=\"glyphicon glyphicon-remove-circle\"  class=\"text-danger\" aria-hidden=\"true\"></span></a>"+
			"</label>"+
			"<input type=\"text\" class=\"form-control\" name=\"keyword[]\" value=\"\" style=\"float:left;width:80%;\"/>"+
			"</div>";
			thisObj.after(addContent);
			//$("input[name*=Tag]").formValidation({tipPosition:'left',required:true});
			$("input[name='keyword[]']").autocomplete({source:productList.list.tagNames});
			
		},
		delete_form_group:function(obj){
			$(obj).parents(".form-group").remove();
		},
		saveSkuRule:function(){
				$.showLoading();
				var values = $('#skuruleform').serialize();
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/catalog/rule/index',
					data: values , 
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
								message: Translator.t("保存SKU解析规则成功！"),
								callback: function() {
									window.location.reload();
								}, 
							});
						}
						else{
							$.hideLoading();
							bootbox.alert(Translator.t("保存SKU解析规则失败！"));
						}
					},
					error :function () {
						$.hideLoading();
						bootbox.alert(result.message);
					}
				});
		},
}
