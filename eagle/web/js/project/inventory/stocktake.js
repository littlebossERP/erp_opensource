/**
 * +------------------------------------------------------------------------------
 * inventory stockTake的界面js
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
inventory.stockTake = {
	'prodStatus':'',
	'init' : function() {
		$('#btn_clear_stocktake').click(function() {
			url = global.baseUrl + "inventory/inventory/stocktake";
			window.location.href = url;
		});
		
		$( "#stocktakelist_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#stocktakelist_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
	},
	
	'initFormValidation':function(){
		$('input[name$="[qty_actual]"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
	},
	
	'openCreateForm' : function(){
		
		
		$.showLoading();
		$.get(global.baseUrl + 'inventory/inventory/create_stocktake', function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "create_stocktake_dialog",
				title : Translator.t("新建盘点"),
				buttons : {
					Cancel : {
						label : Translator.t("关闭"),
						className : "btn-default",
						callback : function() {
							$('.create_stocktake_dialog').modal('hide');
							$('.create_stocktake_dialog').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}
				},
				message : data,
			});
		});
		
	},
	'showStockTakeDetail' : function(id) {
		$.showLoading();
		$.get(global.baseUrl + 'inventory/inventory/stocktake_detail?id='+id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "show_detail_dialog",
				title : Translator.t("库存盘点单："+id +" 详情"),
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
	'newStockTake' : function(){
		$("#create_stockTake_data_form select[name='warehouse_id']").change(function(){
			var warehouse_id = $(this).val();
			var hasProds = $("#stockTake_prodList_tb tr.prodList_tr").length;
				if (typeof(hasProds)!=='undefined' && hasProds!==0){
					for(var i=0;i<hasProds;i++){
						var wh_qty = $("#stockTake_prodList_tb tr.prodList_tr").eq(i).attr("warehouse_id_"+warehouse_id+"_qty");
						if (typeof(wh_qty)=='undefined' || wh_qty=='') wh_qty=0;
						$("#stockTake_prodList_tb td[name$='[qty_shall_be]']").eq(i).attr('value',wh_qty);
						$("#stockTake_prodList_tb td[name$='[qty_shall_be]']").eq(i).html(wh_qty);
					}
				}
		});
		$( "#stockTake_date" ).datepicker({dateFormat:"yy-mm-dd 00:00:00",//datepicker 显示及editable折中方案
										beforeShow:function(input,inst){
											var mydate = new Date();
											var timeNow = mydate.getHours()+":"+mydate.getMinutes()+":"+mydate.getSeconds();
											inst.settings.dateFormat="yy-mm-dd "+timeNow;
										},
										onSelect:function(dateText, inst){
											var mydate = new Date();
											var timeNow = mydate.getHours()+":"+mydate.getMinutes()+":"+mydate.getSeconds();
											inst.settings.dateFormat="yy-mm-dd "+timeNow;
											}
										});
		$("#save_newStockTake_btn").click(function(){
			if (! $('#create_stockTake_data_form').formValidation('form_validate')){
				bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
				return;
			}
			var info = $('#create_stockTake_data_form').serialize();
			var url = global.baseUrl + 'inventory/inventory/save_stocktake';

			$.showLoading();
			$.ajax({
				type:'post',
				url:url,
				data:info,
				dataType:'json',
				success:function(data){
					$.hideLoading();
					bootbox.dialog({
						className : "stockTake_save_result",
						title : Translator.t("操作结果"),
						buttons : {
							Cancel : {
								label : Translator.t("关闭"),
								className : "btn-default",
								callback : function() {
									if(data.success==true){
										window.location.href = global.baseUrl+'inventory/inventory/stocktake';
									}else{
										$('.stockTake_save_result').modal('hide');
										$('.stockTake_save_result').on('hidden.bs.modal', '.modal', function(event) {
											$(this).removeData('bs.modal');
										});
									}
								}
							}
						},
						message : (data['message']=='')?Translator.t('保存成功！'):data['message'],
					});
				},
				error:function(){
					$.hideLoading();
					bootbox.dialog({
						className : "stockTake_save_result",
						title : Translator.t("操作结果"),
						buttons : {
							Cancel : {
								label : Translator.t("关闭"),
								className : "btn-default",
								callback : function() {
									$('.stockTake_save_result').modal('hide');
									$('.stockTake_save_result').on('hidden.bs.modal', '.modal', function(event) {
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
		
		$('#btn_stockTake_import_sellertools_product').click(function(){
			if (typeof(importFile) != 'undefined'){
				var warehouse_id = $("#create_stockTake_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-sellertool-stock-change-prods-by-excel?warehouse_id="+warehouse_id+"&changeType=stocktake";
				template_url = "";
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
							$("#stockTake_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#save_newStockTake_btn').removeAttr('disabled');
							inventory.stockTake.initcancelStockTakeProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockTake.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
		
		$('#btn_stockTake_import_product').click(function(){
			if (typeof(importFile) != 'undefined'){
				var warehouse_id = $("#create_stockTake_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel?warehouse_id="+warehouse_id+"&changeType=stocktake";
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
							$("#stockTake_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#save_newStockTake_btn').removeAttr('disabled');
							inventory.stockTake.initcancelStockTakeProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockTake.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
		$('#btn_stockTake_import_text').click(function(){
			if (typeof(TextImport) != 'undefined'){
				var warehouse_id = $("#create_stockTake_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel-format-text?warehouse_id="+warehouse_id+"&changeType=stockTake";
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
							$("#stockTake_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#save_newStockTake_btn').removeAttr('disabled');
							inventory.stockTake.initcancelStockTakeProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockTake.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
	},
	getStockTakeProds : function(){
		var prodList = new Array();
		var hasProds = $("#stockTake_prodList_tb tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var img = $("#stockTake_prodList_tb td[name$='[img]']").eq(i).attr('value');
				var sku = $("#stockTake_prodList_tb td[name$='[sku]']").eq(i)[0].innerHTML;
				var name = $("#stockTake_prodList_tb td[name$='[name]']").eq(i)[0].innerHTML;
				var status = $("#stockTake_prodList_tb td[name$='[status]']").eq(i).attr('value');
				var qty_actual = $("#stockTake_prodList_tb input[name$='[qty_actual]']").eq(i).val();
				var location_grid = $("#stockTake_prodList_tb input[name$='[location_grid]']").eq(i).val();
				prodList.push({'img':img,'sku':sku,'name':name,'status':status,'qty_actual':qty_actual,'location_grid':location_grid});
			}
		}
		return prodList;
	},
	selectStockTakeProd : function(){
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				inventory.stockTake.reflashProdListTable(prodList);
			},
		});
	},
	initcancelStockTakeProdBtn : function(){
		$("a.cancelStockTakeProd").unbind('click').click(function(){
			debugger;
			var prod_tr = $(this).parent().parent().parent();
			prod_tr.remove();
			var prodList = new Array();
			$.showLoading();
			inventory.stockTake.reflashProdListTable(prodList);
			$.hideLoading();
		});
	},
	reflashProdListTable : function(prods){	
		var HtmlStr = "<tr><th width='10%' style=''>"+Translator.t('图片')+"</th>"+
						  "<th width='20%' style=''>sku</th>"+
						  "<th width='30%' style=''>"+Translator.t('产品名称')+"</th>"+
						  //"<th width='10%' style=''>"+Translator.t('状态')+"</th>"+
						  "<th width='10%' style=''>"+Translator.t('应有库存')+"</th>"+
						  "<th width='10%' style=''>"+Translator.t('实际盘点数')+"</th>"+
						  "<th width='10%' style=''>"+Translator.t('货架位置')+"</th>"+
						  "<th width='8%' style=''>"+Translator.t('操作')+"</th>"+
						"</tr>";
		
		var taxtarea_div_html="";
		var prodList = inventory.stockTake.getStockTakeProds();
		
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
				prodList.push({'img':prods[j].photo_primary,'sku':prods[j].sku,'name':prods[j].name,'status':prods[j].status,'qty_actual':'','location_grid':''});	
		}

		if (prodList.length>0){
			$.showLoading();
			for(var l=0;l<prodList.length;l++){
				var status = prodList[l].status;
				for(var m=0;m<inventory.stockTake.prodStatus.length;m++){
					if (inventory.stockTake.prodStatus[m].key==prodList[l].status){
						status = inventory.stockTake.prodStatus[m].value;
						break;
					}
				}

				var prodStockage = new Array();
				if(prodList[l].sku!==''){
					var url = global.baseUrl + 'inventory/inventory/product_all_stockage';
					$.ajax({
						url:url,
						type:'get',
						data:{sku:prodList[l].sku},
						async : false,
						success:function(data){
							prodStockage = data;
						},
						dataType:'json'
					});
				}
				
				var stockageHtml = '';
				var qty_shall_be = 0;
				var warehouse_id = $("#create_stockTake_data_form select[name='warehouse_id']").val();
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
					striped_row='';
				}

				HtmlStr +="<tr class='prodList_tr"+striped_row+"'"+stockageHtml +">"+
							"<td style='' name='prod["+l+"][img]' value='"+prodList[l].img+"'><img src='"+prodList[l].img+"' style='width:80px ! important;height:80px ! important'></td>"+
							
							"<td style='' name='prod["+l+"][sku]'>"+prodList[l].sku+"</td>"+
							"<td style='' name='prod["+l+"][name]'>"+prodList[l].name+"</td>"+
							//"<td style='' name='prod["+l+"][status]' value='"+prodList[l].status+"'>"+status+"</td>"+
							"<td style='' name='prod["+l+"][qty_shall_be]' value='"+qty_shall_be+"'>"+qty_shall_be+"</td>"+
							"<td style=''><input name='prod["+l+"][qty_actual]' class='form-control' value='"+prodList[l].qty_actual+"'></td>"+
							"<td style=''><input name='prod["+l+"][location_grid]' class='form-control' value='"+prodList[l].location_grid+"'></td>"+
							"<td style=''><div><a class='cancelStockTakeProd'>"+Translator.t('取消')+"</a></div></td>"+
						"</tr>";
				taxtarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+prodList[l].sku+"</textarea>"+
							"<textarea class='hide' name='prod["+l+"][name]' style='display:none'>"+prodList[l].name+"</textarea>";
			}
			$('#save_newStockTake_btn').removeAttr('disabled');
		}
		else{
			HtmlStr += "<tr>"+
							"<td colspan='8' style='text-align:center;'>"+
								"<b style='color:red;'>"+Translator.t('没有选择具体产品，不能保存')+
								"</b>"+
							"</td>"+
						"</tr>";
			$('#save_newStockTake_btn').attr('disabled','disabled');
		}
		$("#stockTake_prodList_tb").html(HtmlStr);
		$(".sku_name_area").html(taxtarea_div_html);
		inventory.stockTake.initcancelStockTakeProdBtn();
		inventory.stockTake.initFormValidation();
		$.hideLoading();
	},

};

