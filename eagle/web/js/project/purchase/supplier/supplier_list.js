/**
 +------------------------------------------------------------------------------
 * 仓库列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase/supplier
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof supplier === 'undefined')  supplier = new Object();

supplier.list = {
	'countrys' : new Array(),
	'init': function() {
		$('#btn_clear').click(function() {
			url = global.baseUrl + "purchase/supplier/index";
			window.location.href = url;
		});
		$("#select_all").click(function(){
			$(".supplier_list .select_one").prop('checked',this.checked);
			// $(".shoplist .select_one").attr('checked',this.checked);//jquery 问题
		});
		$("#create_new_supplier").click(function () {
			$.showLoading();
			$.get(global.baseUrl + 'purchase/supplier/create-view?tt=create', function(data) {
				$.hideLoading();
				bootbox.dialog({
					className : "supplier_detail_win",
					title: Translator.t("新建供应商"),
					closeButton : true,
					buttons:{
						Cancel: {
							label: Translator.t("取消"),
							className: "btn-default",
							callback : function() {
								$('.supplier_detail_win').modal('hide');
								$('.supplier_detail_win').on('hidden.bs.modal', '.modal', function(event) {
									$(this).removeData('bs.modal');
								});
							}
						},
						Save: {
							label: Translator.t("保存"),
							className: "btn-primary",
							callback : function() {
								if (! $('#supplier_model_form').formValidation('form_validate')){
									bootbox.alert(Translator.t('有必填项未填或格式不正确!'));
									return false;
								}
								$.showLoading();
								$.ajax({
									type:'post',
									data:$("#supplier_model_form").serialize(),
									url:global.baseUrl + 'purchase/supplier/create', 
									dataType:'json',
									success: function(data) {
										$.hideLoading();
										bootbox.alert({
											title:Translator.t('操作结果'),
											message:(data.message=='')?Translator.t("保存成功！"):data.message,
											callback: function() {
												if(data.success)
													window.location.reload();
											}
										});
									},
									error:function(){
										$.hideLoading();
										bootbox.alert({
											message : '<b style="color:red;">'+Translator.t("后台获取数据异常，请重试")+"</b>",
										});
										return false;
									},
								});
							}
						}
					},
					message: data,
				});
			});
		});
		$("#batch_inactivate_supplier").click(function () {
			var IdStr = supplier.list.getSelectedSupplierIds();
			if(IdStr!==''){
				var names = supplier.list.getSelectedSupplierNames();
				supplier.list.inactivateSupplier(IdStr,names,'batch');
			}else{
				bootbox.alert(Translator.t("请选择需要停用的供应商"));
			}
		});
		$("#batch_activate_supplier").click(function () {
			var IdStr = supplier.list.getSelectedSupplierIds();
			if(IdStr!==''){
				var names = supplier.list.getSelectedSupplierNames();
				supplier.list.activateSupplier(IdStr,names,'batch');
			}else{
				bootbox.alert(Translator.t("请选择需要启用的供应商"));
			}
		});
		$("#batch_del_supplier").click(function () {
			var IdStr = supplier.list.getSelectedSupplierIds();
			if(IdStr!==''){
				var names = supplier.list.getSelectedSupplierNames();
				supplier.list.deleteSupplier(IdStr,names,'batch');
			}else{
				bootbox.alert(Translator.t("请选择需要删除的供应商"));
			}
		});
		
	},
	
	'innerInit':function(){
		$("#supplier_prod_list_table").on("click","input[name='chk_ps_all']",function(){
			$("input[name='chk_ps_info']").prop("checked", $("input[name='chk_ps_all']").prop('checked'));
		});
	},
	
	'initFormValidateInput' : function(){
		$('#supplier_model_form #name').formValidation({validType:['trim','length[2,30]','safeForHtml'],tipPosition:'left',required:true});
		$('#supplier_model_form #comment,#supplier_model_form #address_street').formValidation({validType:['trim','length[0,255]','safeForHtml'],tipPosition:'left'});
		$("#supplier_model_form #address_state,#supplier_model_form #address_city,#supplier_model_form #phone_number,#supplier_model_form #fax_number,#supplier_model_form #mobile_number,#supplier_model_form #qq,#supplier_model_form #ali_wanwan,#supplier_model_form #msn,#supplier_model_form #email,#supplier_model_form #payment_mode,#supplier_model_form #payment_account")
		.formValidation({validType:['trim','length[0,100]','safeForHtml'],tipPosition:'left'});
		$('#supplier_model_form #url').formValidation({validType:['trim','length[0,1000]','url'],tipPosition:'left'});
		$("select[name='address_nation']").combobox({removeIfInvalid:true});
	},

	'getSelectedSupplierIds':function(){
		var selectCount = $(".select_one:checked").length;
		var IdStr = '';
		for(var i=0;i<selectCount; i++){
			var supplierId = $(".select_one:checked").eq(i).val();
			if(IdStr =='')
				IdStr += supplierId;
			else
				IdStr += ','+ supplierId;
		}
		return IdStr;
	},
	'getSelectedSupplierNames':function(){
		var names = '';
		$(".select_one:checked").each(function(){
			var parentTr = $(this).parents('tr');
			if(names=='')
				names+=parentTr.find('td').eq(1).html();
			else
				names+=";"+parentTr.find('td').eq(1).html();
		});
		return names;
	},
	
	'viewSupplier' : function (id){
		$.showLoading();
		$.get(global.baseUrl + 'purchase/supplier/view-detail?tt=view&supplier_id='+id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "supplier_detail_win",
				title: Translator.t("供应商详情"),
				closeButton : true,
				buttons:{
					Cancel: {
						label: Translator.t("关闭"),
						className: "btn-default",
						callback : function() {
							$('.supplier_detail_win').modal('hide');
							$('.supplier_detail_win').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}, 
				},
				message: data,
			});
		});
	},
	'editSupplier' : function(id){
		$.showLoading();
		$.get(global.baseUrl + 'purchase/supplier/update-view?tt=edit&supplier_id='+id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "supplier_detail_win",
				title: Translator.t("修改供应商信息"),
				closeButton : true,
				buttons:{
					Cancel: {
						label: Translator.t("取消"),
						className: "btn-default",
						callback : function() {
							$('.supplier_detail_win').modal('hide');
							$('.supplier_detail_win').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					},
					Save: {
						label: Translator.t("保存"),
						className: "btn-primary",
						callback : function() {
							if (!$('#supplier_model_form').formValidation('form_validate')){
								bootbox.alert(Translator.t('有必填项未填或格式不正确!'));
								return false;
							}
							$.showLoading();
							$.ajax({
								type:'post',
								data:$("#supplier_model_form").serialize(),
								url:global.baseUrl + 'purchase/supplier/update-supplier', 
								dataType:'json',
								success: function(rtn) {
									$.hideLoading();
									bootbox.alert({
										title:Translator.t('操作结果'),
										message:(rtn.message=='')?Translator.t("保存成功！"):rtn.message,
										callback: function() {
											if(rtn.success)
												window.location.reload();
										}
									});
								},
								error:function(){
									$.hideLoading();
									bootbox.alert({
										message : '<b style="color:red;">'+Translator.t("后台获取数据异常，请重试")+"</b>",
									});
									return false;
								},
							});
						}
					}
				},
				message: data,
			});
		});
	},
	
	'inactivateSupplier' : function(supplier_ids,obj,type){
		if(type=='one')
			names = $(obj).parents("tr").find("td:eq(1)")[0].innerHTML;
		else
			names=obj;
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}
			}, 
			message: Translator.t("确定停用供应商")+"<b style='color:red'>"+names+"</b>?",
			callback: function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type:'get',
						url:global.baseUrl + 'purchase/supplier/inactivate-supplier',
						data:{supplierIds:supplier_ids},
						dataType:'json',
						success:function(data) {
							$.hideLoading();
							bootbox.alert({
								title:Translator.t('操作结果'),
								message:(data.message=='')?Translator.t("修改成功！"):data.message,
								callback: function() {
									if(data.success)
										window.location.reload();
								}
							});
						},
						error:function(){
							bootbox.alert({
								title:Translator.t('出错了！'),
								message:Translator.t("后台数据传输出现问题，请联系客服！"),
							});
							return false;
						}
					});
				}
			}
		});
	},
	'activateSupplier' : function(supplier_ids,obj,type){
		if(type=='one')
			names = $(obj).parents("tr").find("td:eq(1)")[0].innerHTML;
		else
			names=obj;
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}  
			}, 
			message: Translator.t("确定启用供应商")+"<b style='color:red'>"+names+"</b>?",
			callback: function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type:'get',
						url:global.baseUrl + 'purchase/supplier/activate-supplier',
						data:{supplierIds:supplier_ids},
						dataType:'json',
						success:function(data) {
							$.hideLoading();
							bootbox.alert({
								title:Translator.t('操作结果'),
								message:(data.message=='')?Translator.t("修改成功！"):data.message,
								callback: function() {
									if(data.success)
										window.location.reload();
								}
							});
						},
						error:function(){
							bootbox.alert({
								title:Translator.t('出错了！'),
								message:Translator.t("后台数据传输出现问题，请联系客服！"),
							});
							return false;
						}
					});
				}
			}
		});
	},
	
	'deleteSupplier' : function(supplier_ids,obj,type){
		if(type=='one')
			names = $(obj).parents("tr").find("td:eq(1)")[0].innerHTML;
		else
			names=obj;
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}  
			}, 
			message: Translator.t("确定删除供应商")+"<b style='color:red'>"+names+"</b>?",
			callback: function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type:'post',
						url:global.baseUrl + 'purchase/supplier/delete',
						data:{supplierIds:supplier_ids},
						dataType:'json',
						success:function(data) {
							$.hideLoading();
							bootbox.alert({
								title:Translator.t('操作结果'),
								message:(data.message=='')?Translator.t("删除成功！"):data.message,
								callback: function() {
									if(data.success)
										window.location.reload();
								}
							});
						},
						error:function(){
							bootbox.alert({
								title:Translator.t('出错了！'),
								message:Translator.t("后台数据传输出现问题，请联系客服！"),
							});
							return false;
						}
					});
				}
			}
		});
	},
	
	'supplierProducts' : function(supplier_id,obj){
		if(supplier_id===''){
			bootbox.alert(Translator.t("无指定供应商，请选择供应商"));
			return false;
		}
		
		var name = $(obj).parents("tr").find("td:eq(1)")[0].innerHTML;
		$.showLoading();
		$.get(global.baseUrl + 'purchase/supplier/list-supplier-products?supplier_id='+supplier_id,function(data){
			$.hideLoading();
			bootbox.dialog({
				className : "supplier_prod_win",
				title: Translator.t("供应商 ")+name+Translator.t(" 产品列表"),
				closeButton : true,
				buttons:{
					Cancel: {
						label: Translator.t("取消"),
						className: "btn-default",
						callback : function() {
							$('.supplier_prod_win').modal('hide');
							$('.supplier_prod_win').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}
				},
				message: data,
			});
		});
	},

	'supplierProductSearch' : function(){
		var keyword = $("#supplier_prod_filter input[name='keyword']").val();
		$("#supplier_prod_list_table").queryAjaxPage({
			'keyword':keyword,
		});
		supplier.list.innerInit();
	},
	
	supplierBatchRemoveProds:function(supplier_id){
		var sku_E = new Array();
		var skuList = '';
		$('input[name=chk_ps_info]:checked').each(function(){
			sku_E.push(this.value);
			var parentTr = $(this).parents('tr');
			if(skuList=='')
				skuList+=parentTr.find('td').eq(3).html();
			else
				skuList+=";"+parentTr.find('td').eq(3).html();
		});

		if (sku_E.length == 0 ){
			bootbox.alert(Translator.t('请选择需要删除供应的产品'));
			return false;
		}
		
		if (sku_E.length>0){
			var skus_encode = sku_E.join(',');
			supplier.list.supplierRemoveProd(skus_encode,supplier_id,skuList,'batch');
		};
	},

	supplierRemoveProd:function(skus_encode,supplier_id,obj,type){
		if(type=='one')
			skus = $(obj).parents("tr").find("td:eq(3)")[0].innerHTML;
		else
			skus=obj;
		bootbox.confirm(
			Translator.t('确认要把：')+"<br><b style='color:red'>"+skus+"</b>"+Translator.t(' 移出供应?'),
			function(r){
				if(r){
					$.showLoading();
					var url='/purchase/supplier/supplier-remove-product';
					$.ajax({type:'get',
						url:url,
						data:{skus_encode:skus_encode,supplier_id:supplier_id},
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
										$("#supplier_prod_list_table").queryAjaxPage();
										supplier.list.innerInit();
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
};
