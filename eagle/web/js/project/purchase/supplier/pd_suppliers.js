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

if (typeof pd_suppliers === 'undefined')  pd_suppliers = new Object();

pd_suppliers.list = {
	'countrys' : new Array(),
	'init': function() {
		$('#btn_clear').click(function() {
			url = global.baseUrl + "purchase/supplier/pd-suppliers";
			window.location.href = url;
		});
		/*
		$("#select_all").click(function(){
			$(".supplier_list .select_one").prop('checked',this.checked);
			// $(".shoplist .select_one").attr('checked',this.checked);//jquery 问题
		});
		*/
		$("#create_new_pdSupplier").click(function () {
			$.showLoading();
			$.get(global.baseUrl + 'purchase/supplier/create-pd-suppliers', function(data) {
				$.hideLoading();
				bootbox.dialog({
					className : "create_new_pd_suppliers",
					title: Translator.t("新建供应商报价"),
					closeButton : true,
					buttons:{
						Cancel: {
							label: Translator.t("取消"),
							className: "btn-default",
							callback : function() {
								$('.create_new_pd_suppliers').modal('hide');
								$('.create_new_pd_suppliers').on('hidden.bs.modal', '.modal', function(event) {
									$(this).removeData('bs.modal');
								});
							}
						},
						Save: {
							label: Translator.t("保存"),
							className: "btn-primary",
							callback : function() {
								if (! $('#create_new_pd_suppliers').formValidation('form_validate')){
									bootbox.alert(Translator.t('有必填项未填或格式不正确!'));
									return false;
								}
								if(!$('#create_new_pd_suppliers tr.prodList_tr').length>0){
									bootbox.alert(Translator.t('没有新增报价，请添加产品和选择供应商，然后写入相关报价信息。'));
									return false;
								}
								$.showLoading();
								$.ajax({
									type:'post',
									data:$("#create_new_pd_suppliers").serialize(),
									url:global.baseUrl + 'purchase/supplier/save-pd-suppliers', 
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
		/*
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
		*/
	},
	
	
	'innerInit':function(){
		$("select[name='supplier_id']").combobox({removeIfInvalid:true});
	},
	
	'initFormValidateInput' : function(){
		$("#create_new_pd_suppliers .table input[name$='[new_price]']").formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
	
		$("a.tableCancelProd").unbind('click').click(function(){
			var prod_tr = $(this).parent().parent().parent();
			prod_tr.remove();
			var prodList = new Array();
			$.showLoading();
			pd_suppliers.list.reflashListTable(prodList);
			$.hideLoading();
		});
	},
	
	
	/*
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
	*/
	
	/*
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
	*/
	
	/*
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
	*/
	
	/*
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
	*/
	

	'selectPurchaseProd' : function(){
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				pd_suppliers.list.reflashListTable(prodList);
			},
		});
	},
	
	
	'getProdsInList' : function(){	//get product data in table
		var prodList = new Array();
		var hasProds = $("#create_new_pd_suppliers .table tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var img = $("#create_new_pd_suppliers .table td[name$='[img]']").eq(i).attr('value');
				var sku = $("#create_new_pd_suppliers .table td[name$='[sku]']").eq(i)[0].innerHTML;
				var name = $("#create_new_pd_suppliers .table td[name$='[name]']").eq(i)[0].innerHTML;
				var purchase_price = $("#create_new_pd_suppliers .table td[name$='[purchase_price]']").eq(i)[0].innerHTML;
				var priority = $("#create_new_pd_suppliers .table select[name$='[priority]']").eq(i).val();
				var new_price = $("#create_new_pd_suppliers .table input[name$='[new_price]']").eq(i).val();
				prodList.push({'img':img,'sku':sku,'name':name,'purchase_price':purchase_price,'priority':priority,'new_price':new_price});
			}
		}
		return prodList;
	},
	'reflashListTable' : function(prods){
		var HtmlStr = "<tr><th width='80px'>"+Translator.t('图片')+"</th>"+
						  "<th width='150px'>sku</th>"+
						  "<th width='250px'>"+Translator.t('商品名称')+"</th>"+
						  "<th width='120px'>"+Translator.t('首选供应商报价')+"</th>"+
						  "<th width='200px'>"+Translator.t('添加报价')+"</th>"+
						  "<th width='100px'>"+Translator.t('操作')+"</th>"+
						"</tr>";
		var taxtarea_div_html="";
		var prodList = pd_suppliers.list.getProdsInList();
		
		for (var j=0;j<prods.length;j++){
			var ishas = false;
			for (var k=0;k<prodList.length;k++){
				if (prods[j].sku == prodList[k].sku){
					ishas = true;
					break;
				}
			}
			if (ishas)
				continue;
			else
				prodList.push({'img':prods[j].photo_primary,'sku':prods[j].sku,'name':prods[j].name,'purchase_price':prods[j].purchase_price,priority:'',new_price:''});	
		}

		if (prodList.length>0){
			$.showLoading();
			for(var l=0;l<prodList.length;l++){
				var tmpNum = l/2;
				var striped_row = ' striped-row';
				if(parseInt(tmpNum) == tmpNum){
					striped_row='';
				}

				HtmlStr +="<tr class='prodList_tr"+striped_row+"'>"+
							"<td name='prod["+l+"][img]' value='"+prodList[l].img+"'><img src='"+prodList[l].img+"' style='width:80px ! important;height:80px ! important'></td>"+
							"<td name='prod["+l+"][sku]'>"+prodList[l].sku+"</td>"+
							"<td name='prod["+l+"][name]'>"+prodList[l].name+"</td>"+
							"<td name='prod["+l+"][purchase_price]'>"+prodList[l].purchase_price+"</td>"+
							
							"<td ><span class='eagle-form-control'>"+Translator.t('优先级')+"</span><select name='prod["+l+"][priority]' class='eagle-form-control' required='required' style='width:60px'>"+
									"<option value='0'"+((prodList[l].priority==0)?'selected':'')+">"+Translator.t('首选')+"</option>"+
									"<option value='1'"+((prodList[l].priority==1)?'selected':'')+">"+Translator.t('备选1')+"</option>"+
									"<option value='2'"+((prodList[l].priority==2)?'selected':'')+">"+Translator.t('备选2')+"</option>"+
									"<option value='3'"+((prodList[l].priority==3)?'selected':'')+">"+Translator.t('备选3')+"</option>"+
									"<option value='4'"+((prodList[l].priority==4)?'selected':'')+">"+Translator.t('备选4')+"</option>"+"</select>"+
								"<span class='eagle-form-control'>"+Translator.t('价格')+"</span><input name='prod["+l+"][new_price]' class='eagle-form-control' required='required' value='"+prodList[l].new_price+"'style='width:60px'>"+"</td>"+
							"<td ><div><a class='tableCancelProd'>"+Translator.t('取消')+"</a></div></td>"+
						"</tr>";
				taxtarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+prodList[l].sku+"</textarea>";
			}
		}
		else{
			HtmlStr += "";
		}
		$("#create_new_pd_suppliers .table").html(HtmlStr);
		$(".sku_area").html(taxtarea_div_html);
		pd_suppliers.list.initFormValidateInput();
		$.hideLoading();
	},
	
	
	'batchProductRemovePdSupplier':function(){
		var idList = new Array();
		var skuList = new Array();
		$('input.chk_pd_supplier:checked').each(function(){
			idList.push(this.value);
			skuList.push($(this).attr('sku'));
			
		});

		if (idList.length == 0 ){
			bootbox.alert(Translator.t('请选择需要删除的报价'));
			return false;
		}
		
		if (idList.length>0){
			var product_supplier_ids = idList.join(',');
			var skus_encode = skuList.join(',');
			pd_suppliers.list.productRemovePdSupplier(skus_encode,product_supplier_ids);
		};
	},

	'productRemovePdSupplier':function(skus_encode,product_supplier_ids){
		bootbox.confirm(
			"<br>"+Translator.t('确认删除选定的报价？')+"</b>",
			function(r){
				if(r){
					$.showLoading();
					var url='/purchase/supplier/remove-product-supplier';
					$.ajax({type:'get',
						url:url,
						data:{skus_encode:skus_encode,product_supplier_ids:product_supplier_ids},
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
										window.location.reload();
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
	
	'pdSuppliers_excel_import' : function(){
		if (typeof(importFile) != 'undefined'){
			form_url = "/purchase/supplier/import-pd-suppliers-by-excel";
			template_url = "/template/供应商报价导入格式.xls";
			importFile.showImportModal(
				Translator.t('请选择需要导入的Excel文件') , 
				form_url , 
				template_url , 
				function(result){
					if (result.success==false){
						$.hideLoading();
						bootbox.alert(result.message);
					}else{
						var addMsg = '';
						if(result.message!==''){
							addMsg += "<br><b style='color:blue'>"+Translator.t('提示：')+"</b><br><span style='color:blue''>"+result.message+"</span>";
						}
						$.hideLoading();
						bootbox.alert('<b>'+Translator.t('导入成功。')+'</b>'+addMsg);
					}
				}
			);
		}else{
			bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
		}
	},
	
	'pdSuppliers_text_import' : function(){
		if (typeof(TextImport) != 'undefined'){
			form_url = "/purchase/supplier/import-pd-suppliers-by-excel-format-text";
			TextImport.showImportTextModal(
				Translator.t('复制粘贴Excel格式文本') , 
				form_url , 
				function(result){
					if (result.success==false){
						$.hideLoading();
						bootbox.alert(result.message);
					}else{
						var addMsg = '';
						if(result.message!==''){
							addMsg += "<br><b style='color:blue'>"+Translator.t('提示：')+"</b><br><span style='color:blue'>"+result.message+"</span>";
						}
						$.hideLoading();
						bootbox.alert('<b>'+Translator.t('导入成功!')+'</b>'+addMsg);
					}
				}
			);
		}else{
			bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
		}
	},
};
