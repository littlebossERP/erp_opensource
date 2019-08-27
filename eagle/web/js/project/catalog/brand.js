/**
 +------------------------------------------------------------------------------
 * 品牌列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof brand === 'undefined')  brand = new Object();

brand.list={
	init:function(){
		$("input[name='chk_brand_all']").click(function(){
			$("input[name='chk_brand_info']").prop("checked", this.checked);
		});
	},
	innerInit:function(){
		$("#prodInBrand_list_table").on("click","input[name='chk_brandProd_all']",function(){
			$("input[name='chk_brandProd_info']").prop("checked", $("input[name='chk_brandProd_all']").prop('checked'));
		});
	},
	
	initFormValidate:function(){
		$("input[name='name']").formValidation({validType:['trim','length[1,100]','safeForHtml'],tipPosition:'left',required:true});
		$("input[name='comment'],input[name='addi_info']").formValidation({validType:['trim','length[0,255]','safeForHtml'],tipPosition:'left'});
	},

	deleteBrand:function(id,obj){
		console.log(typeof(obj));
		if(typeof(obj)=='object')
			var name = $(obj).parents("tr").find("td:eq(2)")[0].innerHTML;
		else
			var name=obj;
		if(id==''){
			bootbox.alert(Translator.t("指定品牌的id信息丢失，操作中止。"));
			return false;
		}
			
		bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确定删除品牌：")+"<span style='color:red;'>"+name+"</span>?</b></div>",function(r){
			if (! r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/catalog/brand/delete',
				data: {id:id} , 
				success: function (result) {
					$.hideLoading();
					if (result.success){
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t("删除成功!"),
							callback: function() {
								$.showLoading();
								window.location.reload();
							}, 
						});
					}
					else{
						$.hideLoading();
						bootbox.alert(Translator.t("删除失败:")+result.message);
					}
				},
				error :function () {
					$.hideLoading();
					bootbox.alert(Translator.t("数据传输失败，请稍后再试"));
				}
			});
		});
	},
	
	viewMethod:function(){
		$("button#toViewMethod-btn").css('display','none');
		$("button#toEditMethod-btn").css('display','block');
		$("button#brand-save-btn").css('display','none');
		$("button#brand-del-btn").css('display','inline-block');
    	$(".brand-view input").attr('readonly','readonly');
		$("input[name='capture_user_name'],input[name='create_time'],input[name='update_time']").attr('readonly','readonly').css('display','block');
		$("input[name='capture_user_name'],input[name='create_time'],input[name='update_time']").attr("data-content","").popover('destroy');
	},
	
	editMethod:function(){
		$("button#toViewMethod-btn").css('display','block');
		$("button#toEditMethod-btn").css('display','none');
		$("button#brand-save-btn").css('display','inline-block');
		$("button#brand-del-btn").css('display','none');
		$(".brand-view input").removeAttr('readonly');
		$("input[name='capture_user_name'],input[name='create_time'],input[name='update_time']").attr('readonly','readonly').css('display','block');
		
		$("input[name='update_time']").attr("data-content",Translator.t("系统按照当前时间来设置，您不能设置该项"));
		$("input[name='create_time']").attr("data-content",Translator.t("创建时间该项不能更改"));
		$("input[name='capture_user_name']").attr("data-content",Translator.t("系统按照当前用户名称来设置，您不能更改该项"));
											
    	$("input[name='capture_user_name'],input[name='create_time'],input[name='update_time']").attr("data-toggle","tooltip")
				.attr("data-placement","bottom")
				.popover({trigger:'hover '});
		brand.list.initFormValidate();
	},
	
	addBrand:function(){
		$.showLoading();
		var url='/catalog/brand/create';
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "brand_info",
					title: Translator.t("新建品牌"),//
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {
							}
						}, 
					}
				});
				brand.list.initFormValidate();
			}
		);
	},
	
	viewBrand:function(id){
		$.showLoading();
		var url='/catalog/brand/view?id='+id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "brand_info",
					title: Translator.t("品牌信息"),//
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			}
		);
	},
	
	viewProdInBrand:function(brand_id){
		$.showLoading();
		var url='/catalog/brand/view-brand-products?brand_id='+brand_id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "product_in_brand",
					title: Translator.t("品牌旗下产品"),//
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			}
		);
	},

	brandProdSearch:function(){
		var keyword = $("#prodInBrand_filter input[name='keyword']").val();
		$("#prodInBrand_list_table").queryAjaxPage({
			'keyword':keyword,
		});
		brand.list.innerInit();
	},
	
	batchProdRemoveBrand:function(){
		var idList = new Array();
		var skuList = '';
		$('input[name=chk_brandProd_info]:checked').each(function(){
			idList.push(this.value);
			var parentTr = $(this).parents('tr');
			if(skuList=='')
				skuList+=parentTr.find('td').eq(3).html();
			else
				skuList+=";"+parentTr.find('td').eq(3).html();
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要移出品牌的产品'));
			return false;
		}
		
		if (idList.length>0){
			var product_ids = idList.join(',');
			brand.list.prodRemoveBrand(product_ids,skuList,'batch');
		};
	},
	
	prodRemoveBrand:function(product_ids,obj,type){
		//obj is de td element or sku string
		//type=one or batch
		if(type=='one')
			skus = $(obj).parents("tr").find("td:eq(3)")[0].innerHTML;
		else
			skus=obj;
		bootbox.confirm(
			Translator.t('确认要把：')+"<br><b style='color:red'>"+skus+"</b>"+Translator.t(' 移出品牌?'),
			function(r){
				if(r){
					$.showLoading();
					var url='/catalog/brand/product-remove-brand?product_ids='+product_ids;
					$.ajax({type:'get',
						url:url,
						data:{},
						dataType:"json",
						success:function(data){
							$.hideLoading();
							if(data.success){
								bootbox.alert({
									buttons: {
										ok: {
											label: 'OK',
											className: 'btn-primary'
										}
									},
									message: Translator.t("操作成功!"),
									callback: function() {
										$("#prodInBrand_list_table").queryAjaxPage();
										brand.list.innerInit();
									}, 
								});
							}else{
								bootbox.alert( data.message );
								return false;
							}
						},
						error:function(){
							$.hideLoading();
							bootbox.alert( Translator.t("后台数据传输错误!") );
							return false;
						}
					});
				}
			}
		);
	},
	
	editBrand:function(id){
		$.showLoading();
		var url='/catalog/brand/edit?id='+id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "brand_info",
					title: Translator.t("编辑品牌"),//
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {
							}
						}, 
					}
				});
				brand.list.initFormValidate();
			}
		);
	},
	
	batchDeleteBrand:function(){
		var idList = new Array();
		var nameList = '';
		$('input[name=chk_brand_info]:checked').each(function(){
			idList.push(this.value);
			var parentTr = $(this).parents('tr');
			if(nameList=='')
				nameList+=parentTr.find('td').eq(2).html();
			else
				nameList+=";"+parentTr.find('td').eq(2).html();
			
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要删除的品牌'));
			return false;
		}
		
		if (idList.length>0){
			bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确认批量删除品牌: ")+"<span style='color:red'>"+nameList+"</span> ?</b></div>",function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/catalog/brand/batch-delete-brand', 
					data:{ids :  $.toJSON(idList) },
					dataType:"json",
					success: function (result) {
						$.hideLoading();
						if (result.success){
							bootbox.alert({
								buttons: {
									ok: {
										label: 'OK',
										className: 'btn-primary'
									}
								},
								message: Translator.t("删除成功!"),
								callback: function() {
									$.showLoading();
									window.location.reload();
								}, 
							});
						}else{
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

	saveBrand:function(){
		if (! $('#brand_info_form').formValidation('form_validate')){
			bootbox.alert(Translator.t('有必填项未填，或格式不正确!'));
			return false;
		}
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: '/catalog/brand/save',
			data: $('#brand_info_form').serialize(),
			success: function (result) {
				$.hideLoading();
				if (result.success==true){
					bootbox.alert({
						message:Translator.t('保存成功！'),
						callback: function(){  
							window.location.href = global.baseUrl+'catalog/brand/index';
						}
					});
				}else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('保存失败：数据传输错误！'));
				return false;
			}
		});
	},	
}

$(function() {
	brand.list.init();
	
});