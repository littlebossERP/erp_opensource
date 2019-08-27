/**
 +------------------------------------------------------------------------------
 * 标签列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof tag === 'undefined')  tag = new Object();

tag.list={
	init:function(){
		$("input[name='chk_tag_all']").click(function(){
			$("input[name='chk_tag_info']").prop("checked", this.checked);
		});
	},
	innerInit:function(){
		$("#prodInTag_list_table").on("click","input[name='chk_tagProd_all']",function(){
			$("input[name='chk_tagProd_info']").prop("checked", $("input[name='chk_tagProd_all']").prop('checked'));
		});
	},
	
	initFormValidate:function(){
		$("input[name='tag_name']").formValidation({validType:['trim','length[1,100]','safeForHtml'],tipPosition:'left',required:true});
	},

	deleteTag:function(id,obj){
		var name=$(obj).parents("tr").find("td:eq(2)")[0].innerHTML;
		if(id==''){
			bootbox.alert(Translator.t("指定标签的id信息丢失，操作中止。"));
			return false;
		}
			
		bootbox.confirm("<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确定删除标签：")+"<span style='color:red;'>"+name+"</span>?</b></div>",function(r){
			if (! r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/catalog/tag/delete',
				data: {id:id} , 
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
							callback: function() { window.location.reload() }, 
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
	
	addTag:function(){
		var htmlStr="<form id='tag_info_form'>"
		htmlStr+="<p><span>"+Translator.t("标签名称：")+"</span><input type='text' name='tag_name' class='form-control' value='' />"+"</p>";
		htmlStr+="</form>";
		
		bootbox.dialog({
			className : "tag_info",
			title: Translator.t("添加标签"),//
			message: htmlStr,
			buttons:{
				Cancel: {
					label: Translator.t("返回"),  
					className: "btn-default",  
					callback: function () {
					}
				}, 
				Submit: {
					label: Translator.t("保存"),  
					className: "btn-success",  
					callback: function (){
						if (! $('#tag_info_form').formValidation('form_validate')){
							bootbox.alert(Translator.t('标签名称不符合要求!'));
							return false;
						}
						var tagNmae = $("input[name='tag_name']").val();
						tag.list.saveTag(0,tagNmae);
						
					}
				},
			}
		});
		tag.list.initFormValidate();		
	},
	
	viewProdInTag:function(tag_id){
		$.showLoading();
		var url='/catalog/tag/view-tag-products?tag_id='+tag_id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "product_in_tag",
					title: Translator.t("包含该标签的产品"),//
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

	tagProdSearch:function(){
		var keyword = $("#prodInTag_filter input[name='keyword']").val();
		$("#prodInTag_list_table").queryAjaxPage({
			'keyword':keyword,
		});
		tag.list.innerInit();
	},

	batchProdRemoveTag:function(tag_id){
		var tag_name = $("#tag_name_span").html();
		var idList = new Array();
		var skuList = '';
		$('input[name=chk_tagProd_info]:checked').each(function(){
			idList.push(this.value);
			var parentTr = $(this).parents('tr');
			if(skuList=='')
				skuList+=parentTr.find('td').eq(3).html();
			else
				skuList+=";"+parentTr.find('td').eq(3).html();
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要删除标签的产品'));
			return false;
		}
		
		if (idList.length>0){
			var product_ids = idList.join(',');
			tag.list.prodRemoveTag(product_ids,tag_id,skuList,tag_name,'batch');
		};
	},

	prodRemoveTag:function(product_ids,tag_id,obj1,obj2,type){
		//obj1 is td element or sku string
		//obj1 is '' or tag_name string
		//type=one or batch
		if(type=='one'){
			var skus = $(obj1).parents("tr").find("td:eq(3)")[0].innerHTML;
			var tag_name = $(obj1).parents("tr").find("td:eq(1)")[0].innerHTML;
		}
		else{
			var skus=obj1;
			var tag_name=obj2;
		}
		bootbox.confirm(
			"<div style='word-break:break-word;overflow:auto'>"+Translator.t('确认要把标签 ')+"<b style='color:red;'>"+tag_name+"</b>"+Translator.t('从产品：')+"<br><b style='color:red'>"+skus+"</b>"+Translator.t(' 里删除?')+"</div>",
			function(r){
				if(r){
					$.showLoading();
					var url='/catalog/tag/product-remove-tag';
					$.ajax({type:'get',
						url:url,
						data:{product_ids:product_ids,tag_id:tag_id},
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
										$("#prodInTag_list_table").queryAjaxPage();
										tag.list.innerInit();
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
	
	editTag:function(id,name){
		var htmlStr="<form id='tag_info_form'>"
		htmlStr+="<p><span>"+Translator.t("原标签：")+"</span><span class='alert alert-success' role='alert' style='padding:2px'>"+name+"</span></p>";
		htmlStr+="<p><span>"+Translator.t("新标签名称：")+"</span><input type='text' name='tag_name' class='form-control' value='' />"+"</p>";
		htmlStr+="</form>";
		
		bootbox.dialog({
			className : "tag_info",
			title: Translator.t("编辑标签"),//
			message: htmlStr,
			buttons:{
				Cancel: {  
					label: Translator.t("返回"),  
					className: "btn-default",  
					callback: function () {
					}
				}, 
				Submit: {  
					label: Translator.t("保存"),  
					className: "btn-success",  
					callback: function (){
						if (! $('#tag_info_form').formValidation('form_validate')){
							bootbox.alert(Translator.t('标签名称不符合要求!'));
							return false;
						}
						var newTagNmae = $("input[name='tag_name']").val();
						tag.list.saveTag(id,newTagNmae);
						
					}
				},
			}
		});
		tag.list.initFormValidate();
	},

	batchDeleteTag:function(){
		var idList = new Array();
		var nameList = '';
		$('input[name=chk_tag_info]:checked').each(function(){
			idList.push(this.value);
			var parentTr = $(this).parents('tr');
			if(nameList=='')
				nameList+=parentTr.find('td').eq(2).html();
			else
				nameList+=";"+parentTr.find('td').eq(2).html();
			
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要删除的标签'));
			return false;
		}
		
		if (idList.length>0){
			var confirmStr="<div style='word-break:break-word;overflow:auto;'><b>"+Translator.t("确认批量删除标签？: ")+"<span style='color:red'>"+nameList+"</span></b></div>"
			bootbox.confirm(confirmStr,function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					url:'/catalog/tag/batch-delete-tag', 
					data:{ids :  $.toJSON(idList) },
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
								callback: function() { window.location.reload() }, 
							});
						}else{
							$.hideLoading();
							bootbox.alert(result.message);
						}	
					},
					error :function () {
						$.hideLoading();
						bootbox.alert( Translator.t("后台数据传输错误!") );
						return false;
					}
				});
			});
		}
	},

	saveTag:function(id,name){
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: '/catalog/tag/save',
			data: {tag_id:id,tag_name:name},
			success: function (result) {
				if (result.success==true){
					$.hideLoading();
					bootbox.alert({
						buttons: {
							ok: {
								label: 'OK',
								className: 'btn-primary'
							}
						},
						message: Translator.t("保存成功!"),
						callback: function() { window.location.reload() }, 
					});
				}else{
					$.hideLoading();
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
	tag.list.init();
	
});