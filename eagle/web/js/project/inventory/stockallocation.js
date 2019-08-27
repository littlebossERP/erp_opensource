/**
 * +------------------------------------------------------------------------------
 * inventory stockAllocation的界面js
 * +------------------------------------------------------------------------------
 * 
 * @category 	js/project
 * @package 	inventory
 * @subpackage 	Exception
 * @author 		lzhil <zhiliang.lu@witsion.com>
 * @version	 	1.0
 * +------------------------------------------------------------------------------
 */

if (typeof inventory === 'undefined')
	inventory = new Object();
inventory.stockAllocation = {
	'init' : function() {
		$('#btn_clear_stockallocation').click(function() {
			url = global.baseUrl + "inventory/inventory/stock-allocation";
			window.location.href = url;
		});
		
		$( "#stockallocationlist_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#stockallocationlist_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
	},
	
	'initFormValidation':function(){
		$('input[name$="[qty]"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
	},
	
	'openCreateForm' : function(){
		$.showLoading();
		$.get(global.baseUrl + 'inventory/inventory/create-stock-allocation', function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "create_stockallocation_dialog",
				title : Translator.t("新建仓库调拨"),
				buttons : {
					Cancel : {
						label : Translator.t("关闭"),
						className : "btn-default",
						callback : function() {
							$('.create_stockallocation_dialog').modal('hide');
							$('.create_stockallocation_dialog').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}
				},
				message : data,
			});
		});
		
	},
	'showstockAllocationDetail' : function(allocatione_id, title) {
		$.showLoading();
		$.get(global.baseUrl + 'inventory/inventory/stock-allocation-detail?allocatione_id='+allocatione_id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "show_detail_dialog",
				title : Translator.t("仓库调拨："+ title +" 详情"),
				buttons : {
					Cancel : {
						label : Translator.t("关闭"),
						className : "btn-default",
						callback : function() {
							$('.show_detail_dialog').modal('hide');
							$('.show_detail_dialog').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}
				},
				message : data,
			});
		});

	},
	'newstockAllocation' : function(){
		//变更仓库时，对应变更库存信息
		$("#create_stockAllocation_data_form select[name='out_warehouse_id']").change(function(){
			var warehouse_id = $(this).val();
			var hasProds = $("#stockAllocation_prodList_tb tr.prodList_tr").length;
			if (typeof(hasProds)!=='undefined' && hasProds!==0){
				for(var i=0;i<hasProds;i++){
					var wh_qty = $("#stockAllocation_prodList_tb tr.prodList_tr").eq(i).attr("warehouse_id_"+warehouse_id+"_qty");
					if (typeof(wh_qty)=='undefined' || wh_qty=='') wh_qty=0;
					$("#stockAllocation_prodList_tb td[name$='[qty_shall_be]']").eq(i).attr('value',wh_qty);
					$("#stockAllocation_prodList_tb td[name$='[qty_shall_be]']").eq(i).html(wh_qty);
				}
			}
		});
		
		$("#save_newstockAllocation_btn").click(function(){
			if (! $('#create_stockAllocation_data_form').formValidation('form_validate')){
				bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
				return;
			}
			var info = $('#create_stockAllocation_data_form').serialize();
			var url = global.baseUrl + 'inventory/inventory/save-stock-allocation';

			$.showLoading();
			$.ajax({
				type:'post',
				url:url,
				data:info,
				dataType:'json',
				success:function(data){
					$.hideLoading();
					bootbox.dialog({
						className : "stockAllocation_save_result",
						title : Translator.t("操作结果"),
						buttons : {
							Cancel : {
								label : Translator.t("关闭"),
								className : "btn-default",
								callback : function() {
									if(data.success==true){
										window.location.href = global.baseUrl+'inventory/inventory/stock-allocation';
									}else{
										$('.stockAllocation_save_result').modal('hide');
										$('.stockAllocation_save_result').on('hidden.bs.modal', '.modal', function(event) {
											$(this).removeData('bs.modal');
										});
									}
								}
							}
						},
						message : (data['msg']=='')?Translator.t('保存成功！'):data['msg'],
					});
				},
				error:function(){
					$.hideLoading();
					bootbox.dialog({
						className : "stockAllocation_save_result",
						title : Translator.t("操作结果"),
						buttons : {
							Cancel : {
								label : Translator.t("关闭"),
								className : "btn-default",
								callback : function() {
									$('.stockAllocation_save_result').modal('hide');
									$('.stockAllocation_save_result').on('hidden.bs.modal', '.modal', function(event) {
										$(this).removeData('bs.modal');
									});
								}
							}
						},
						message : Translator.t("后台获取数据异常，请重试"),
					});
				},
			});
		});
		
		$('#btn_stockAllocation_import_product').click(function(){
			if (typeof(importFile) != 'undefined'){
				var warehouse_id = $("#create_stockAllocation_data_form select[name='out_warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel?warehouse_id="+warehouse_id+"&changeType=stockallocation";
				template_url = "/template/StockChange Template.xls";
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
								addMsg += "<br><b class='blue-tips'>"+Translator.t('提示：')+"</b><br><span class='blue-tips'>"+result.message+"</span>";
							}
							$("#stockAllocation_prodList_tb").html(result.td_Html);
							//$(".sku_name_area").html(result.textarea_div_html);
							$('#save_newstockAllocation_btn').removeAttr('disabled');
							inventory.stockAllocation.initcancelstockAllocationProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockAllocation.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
		$('#btn_stockAllocation_import_text').click(function(){
			if (typeof(TextImport) != 'undefined'){
				var warehouse_id = $("#create_stockAllocation_data_form select[name='out_warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel-format-text?warehouse_id="+warehouse_id+"&changeType=stockAllocation";
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
								addMsg += "<br><b class='blue-tips'>"+Translator.t('提示：')+"</b><br><span class='blue-tips'>"+result.message+"</span>";
							}
							$("#stockAllocation_prodList_tb").html(result.td_Html);
							//$(".sku_name_area").html(result.textarea_div_html);
							$('#save_newstockAllocation_btn').removeAttr('disabled');
							inventory.stockAllocation.initcancelstockAllocationProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockAllocation.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
	},
	
	//显示选择商品窗口
	selectstockAllocationProd : function(){
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				inventory.stockAllocation.reflashProdListTable(prodList);
			},
		});
	},
	
	//取消行
	initcancelstockAllocationProdBtn : function(){
		$("a.cancelstockAllocationProd").unbind('click').click(function(){
			var prod_tr = $(this).parent().parent().parent();
			prod_tr.remove();
			//当没有数据行时，则添加提示空
			if($('#stockAllocation_prodList_tb').find('tr').length < 2){
				$('#stockAllocation_prodList_tb').append( 
					"<tr id='none_product_tr'>"+
						"<td colspan='8' style='text-align:center;'>"+
							"<b style='color:red;'>"+Translator.t('没有选择具体产品，不能保存')+
							"</b>"+
						"</td>"+
					"</tr>");
				$('#save_newstockAllocation_btn').attr('disabled','disabled');
			}
		});
	},
	
	//确认选择商品
	reflashProdListTable : function(prods){
		var HtmlStr = '';
		if (prods.length > 0){
			$.showLoading();
			var warehouse_id = $("#create_stockAllocation_data_form select[name='out_warehouse_id']").val();
			
			//获取最大的序号
			var num = 0;
			$('#stockAllocation_prodList_tb tr').each(function(){
				if($(this).attr('index') != undefined && parseInt($(this).attr('index')) > num){
					num = parseInt($(this).attr('index'));
				}
			});
			num++;

			for(var l = 0; l < prods.length; l++){
				if(prods[l].sku == ''){
					continue;
				}
				
				//判断商品是否已存在，存在则跳过
				if($('input[name$="][sku]"][value="'+ prods[l].sku +'"]').length > 0){
					continue;
				}
				
				//获取对应库存信息
				var prodStockage = new Array();
				if(prods[l].sku !== ''){
					var url = global.baseUrl + 'inventory/inventory/product_all_stockage';
					$.ajax({
						url:url,
						type:'get',
						data:{sku:prods[l].sku},
						async : false,
						success:function(data){
							prodStockage = data;
						},
						dataType:'json'
					});
				}
				
				var stockageHtml = '';
				var qty_shall_be = 0;
				if (typeof(prodStockage)!=='undefined'){
					for(var n=0;n<prodStockage.length;n++){
						stockageHtml += ' warehouse_id_'+prodStockage[n].warehouse_id + '_qty="' + prodStockage[n].qty_in_stock +'" ';
						if (prodStockage[n].warehouse_id == warehouse_id){
							qty_shall_be = prodStockage[n].qty_in_stock;
						}
					}
				}
				
				var tmpNum = l/2;
				var striped_row = ' striped-row';
				if(parseInt(tmpNum) == tmpNum){
					striped_row = '';
				}

				var index = num + l;
				HtmlStr += 
					"<tr index="+index+" class='prodList_tr"+striped_row+"'"+stockageHtml +">"+
						"<td ><img src='"+prods[l].photo_primary+"' style='width:80px ! important;height:80px ! important'></td>"+							
						"<td ><input name='prod["+index+"][sku]' type='hidden' value='"+prods[l].sku+"'>"+prods[l].sku+"</td>"+
						"<td >"+prods[l].name+"</td>"+
						"<td style='' name='prod["+index+"][qty_shall_be]' >"+qty_shall_be+"</td>"+
						"<td ><input name='prod["+index+"][qty]' class='form-control' value='1'></td>"+
						"<td ><input name='prod["+index+"][location_grid]' class='form-control' value=''></td>"+
						"<td ><div><a class='cancelstockAllocationProd'>"+Translator.t('取消')+"</a></div></td>"+
					"</tr>";
			}
			$('#save_newstockAllocation_btn').removeAttr('disabled');
			$('#none_product_tr').remove();
			$("#stockAllocation_prodList_tb").append(HtmlStr);
		}
		
		//初始化事件
		inventory.stockAllocation.initcancelstockAllocationProdBtn();
		inventory.stockAllocation.initFormValidation();
		
		$.hideLoading();
	},

};

