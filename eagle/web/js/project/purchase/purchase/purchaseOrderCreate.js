/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lolo <jiongqiang.xiao@witsion.com> 2014-02-27 eagle 1.0
+----------------------------------------------------------------------
| Copy by: lzhl <zhiliang.lu@witsion.com> 2015-04-16 eagle 2.0
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *新建采购单的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		purchase
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */


//var purchase = new Object();
if (typeof purchaseOrder === 'undefined')  purchaseOrder = new Object();
purchaseOrder.create={
	'setting':{	'purchaseDetail':"",'comments':"",'purchaseItems' : "",'shippingModes':"",'mode':"",'statusMap':"",'paymentStatusMap':"",'suppliers':"",'warehouseIdNameMap':"",'purchaseId':"",'purchaseOrderId':""},
	'initWidget': function() {
		$('input[required="required"]').formValidation({validType:['trim','length[1,250]'],tipPosition:'left',required:true});
		$('input#purchase_order_id').formValidation({validType:['trim','length[1,255]'],tipPosition:'left',required:true});
		$('select#supplier_name').combobox();
		$('select#supplier_name').parent().find('input:eq(0)').formValidation({validType:['trim','safeForHtml'],tipPosition:'left',required:true});
		$('select#supplier_name').parent().find('input:eq(0)').css('width','90%');
		
		$('select#delivery_method').combobox();
		$('select#delivery_method').parent().find('input:eq(0)').formValidation({validType:['trim','safeForHtml'],tipPosition:'left',required:true});
		$('select#delivery_method').parent().find('input:eq(0)').css('width','90%');
		
		$('#purchase_order_id').formValidation({validType:['trim','safeForHtml'],tipPosition:'right',required:true});
		$('#delivery_fee').formValidation({validType:['trim','amount'],tipPosition:'right'});
		$('#comment').formValidation({validType:['trim','safeForHtml'],tipPosition:'right'});
		$('#delivery_number').formValidation({validType:['trim','safeForHtml'],tipPosition:'right'});
		$('#purchase_source_id').formValidation({validType:['trim','safeForHtml'],tipPosition:'right'});
		
		$('input[name$="[qty]"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
		$('input[name$="[price]"]').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
		$('input[name$="[remark]"]').formValidation({validType:['trim','safeForHtml'],tipPosition:'left'});
		
		$('input#pay_date').datepicker({dateFormat:"yy-mm-dd 00:00:00",//datepicker 显示及editable折中方案
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
		$('input#create_time').datepicker({dateFormat:"yy-mm-dd 00:00:00",//datepicker 显示及editable折中方案
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
		$('input#expected_arrival_date').datepicker({dateFormat:"yy-mm-dd 00:00:00",//datepicker 显示及editable折中方案
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
		
		//auto amount
		$('input[name$="[qty]"]').unbind('change').change(function(){
			var index=$(this).attr('index');
			var qty=$(this).val();
			var price=$('input[name$="prod['+index+'][price]"]').val();
			var amount = changeTwoDecimal(qty*price);
			$('input[name$="prod['+index+'][amount]"]').val(amount);
		});
		$('input[name$="[price]"]').unbind('change').change(function(){
			var index=$(this).attr('index');
			var price=$(this).val();
			var qty=$('input[name$="prod['+index+'][qty]"]').val();
			var amount = changeTwoDecimal(qty*price);
			$('input[name$="prod['+index+'][amount]"]').val(amount);
		});
		
		$('#btn_purchase_import_product').unbind('click').click(function(){
			if (typeof(importFile) != 'undefined'){
				form_url = "/purchase/purchase/import-purchase-prods-by-excel";
				template_url = "/template/Purchase Prod Template.xls";
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
							$("#purchase_order_prods_tb").html(result.td_Html);
							$(".sku_name_area").html(result.taxtarea_div_html);
							purchaseOrder.create.initWidget();
							$('#save_purchase_btn').removeAttr('disabled');
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
		});
		$('#btn_purchase_import_text').unbind('click').click(function(){
			if (typeof(TextImport) != 'undefined'){
				form_url = "/purchase/purchase/import-purchase-prods-by-excel-format-text";
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
							$("#purchase_order_prods_tb").html(result.td_Html);
							$(".sku_name_area").html(result.taxtarea_div_html);
							purchaseOrder.create.initWidget();
							$('#save_purchase_btn').removeAttr('disabled');
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
		});
		$("a.cancelPurchaseProd").unbind('click').click(function(){
			var prod_tr = $(this).parent().parent().parent();
			prod_tr.remove();
			var prodList = new Array();
			$.showLoading();
			purchaseOrder.create.reflashProdListTable(prodList);
			$.hideLoading();
		});
	},
	'createPurchaseOrder':function() {	//编辑采购单，点击 保存
		if (! $('#create-purchase-form').formValidation('form_validate')){
			bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
			return;
		}
		var info = $('#create-purchase-form').serialize();
		var url = global.baseUrl + 'purchase/purchase/save';

		$.showLoading();
		$.ajax({
			type:'post',
			url:url,
			data:info,
			dataType:'json',
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "operation_result",
					title : Translator.t("操作结果"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								if(data.success==true){
									window.location.href = global.baseUrl+'purchase/purchase/index';
								}else{
									$('.operation_result').modal('hide');
									$('.operation_result').on('hidden.bs.modal', '.modal', function(event) {
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
					className : "operation_result",
					title : Translator.t("操作结果"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								$('.operation_result').modal('hide');
								$('.operation_result').on('hidden.bs.modal', '.modal', function(event) {
									$(this).removeData('bs.modal');
								});
							}
						}
					},
					message : '<b style="color:red;">'+Translator.t("后台获取数据异常，请重试")+"</b>",
				});
			},
		});
	},
	'selectPurchaseProd' : function(){	//import product by btn 'selectProduct'
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				purchaseOrder.create.reflashProdListTable(prodList);
			},
		});
	},

	'getPurchaseProds' : function(){	//get product data in table
		var prodList = new Array();
		var hasProds = $("#purchase_order_prods_tb tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var img = $("#purchase_order_prods_tb td[name$='[img]']").eq(i).attr('value');
				var sku = $("#purchase_order_prods_tb td[name$='[sku]']").eq(i).attr('value');
				if($("#purchase_order_prods_tb td[name$='[name]']").eq(i).find("input").length > 0)
				{
					var name = $("#purchase_order_prods_tb td[name$='[name]']").eq(i).find("input").val();
				}
				else
				{
					var name = $("#purchase_order_prods_tb td[name$='[name]']").eq(i)[0].innerHTML;
				}
				var qty = $("#purchase_order_prods_tb input[name$='[qty]']").eq(i).val();
				var price = $("#purchase_order_prods_tb input[name$='[price]']").eq(i).val();
				var amount = $("#purchase_order_prods_tb input[name$='[amount]']").eq(i).val();
				var remark = $("#purchase_order_prods_tb input[name$='[remark]']").eq(i).val();
				prodList.push({'img':img,'sku':sku,'name':name,'qty':qty,'price':price,'amount':amount,'remark':remark});
			}
		}
		return prodList;
	},
	'reflashProdListTable' : function(prods){	//reflash table after SelectProduct,cancelProduct,importFile/Text
		var HtmlStr = "<tr><td width='50px'>"+Translator.t('图片')+"</td>"+
						  "<td width='150px'>"+Translator.t('货品sku')+"</td>"+
						  "<td width='250px'>"+Translator.t('货品名称')+"</td>"+
						  "<td width='100px'>"+Translator.t('采购数量')+"</td>"+
						  "<td width='80px'>"+Translator.t('单价(人民币)')+"</td>"+
						  "<td width='100px'>"+Translator.t('总成本(人民币)')+"</td>"+
						  "<td width='100px'>"+Translator.t('备注')+"</td>"+
						  "<td width='80px'>"+Translator.t('操作')+"</td>"+
						"</tr>";
		var taxtarea_div_html="";
		var prodList = purchaseOrder.create.getPurchaseProds();
		
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
				prodList.push({'img':prods[j].photo_primary,'sku':prods[j].sku,'name':prods[j].name,'qty':'1','price':'','amount':'0','remark':''});	
		}

		if (prodList.length>0){
			$.showLoading();
			for(var l=0;l<prodList.length;l++){
				var tmpNum = l/2;
				var striped_row = ' striped-row';
				if(parseInt(tmpNum) == tmpNum){
					striped_row='';
				}
				
				//查询报价信息、采购地址
				var purchaselink = '';
				var supplierInfo = new Array();
				if(prodList[l].sku!==''){
					var url = global.baseUrl + 'purchase/purchase/get-sku-supplier-info';
					$.ajax({
						url:url,
						type:'get',
						data:{sku:prodList[l].sku},
						async : false,
						success:function(data){
							if(data['status'] == 1){
								supplierInfo = data['supplierInfo'];
								//purchaselink = data['purchase_link'];
							}
						},
						dataType:'json'
					});
				}
				
				var supplier_name = $("select#supplier_name").val();
				var supplier_price = 0;
				if(prodList[l].price == '')
				{
					if (typeof(supplierInfo)=='object'){
						for(var n=0;n<supplierInfo.length;n++){
							if (supplierInfo[n].supplier_name == supplier_name){
								supplier_price = supplierInfo[n].purchase_price;
								purchaselink = supplierInfo[n].purchase_link;
								break;
							}
						}
					}
				}
				else
					supplier_price = prodList[l].price;
				
				HtmlStr += "<tr class='prodList_tr"+striped_row+"'>"+
							"<td name='prod["+l+"][img]' value='"+prodList[l].img+"'><img src='"+prodList[l].img+"' style='width:50px ! important;height:50px ! important'></td>"+
							"<td name='prod["+l+"][sku]' value='"+prodList[l].sku+"'>";
				
				if(purchaselink == '')
					HtmlStr += prodList[l].sku+"</td>";
				else 
					HtmlStr += "<a href='"+purchaselink+"' target='_blank'>"+prodList[l].sku+"</a></td>";
				
				HtmlStr += "<td name='prod["+l+"][name]'>"+prodList[l].name+"</td>"+
							"<td ><input type='text' name='prod["+l+"][qty]' required='required' index='"+l+"' class='eagle-form-control' value='"+prodList[l].qty+"'>"+"</td>"+
							"<td ><input type='text' name='prod["+l+"][price]' required='required' index='"+l+"' class='eagle-form-control' value='"+supplier_price+"'>"+"</td>"+
							"<td ><input type='text' name='prod["+l+"][amount]' value='"+prodList[l].qty*supplier_price+"' readonly class='eagle-form-control'></td>"+
							"<td ><input type='text' name='prod["+l+"][remark]' index='"+l+"' class='eagle-form-control' value='"+prodList[l].remark+"'></td>"+
							"<td ><div><a class='cancelPurchaseProd'>"+Translator.t('取消')+"</a></div></td>"+
						"</tr>";
				taxtarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+prodList[l].sku+"</textarea>"+
							"<textarea class='hide' name='prod["+l+"][name]' style='display:none'>"+prodList[l].name+"</textarea>";
			}
			$('#save_purchase_btn').removeAttr('disabled');
		}
		else{
			HtmlStr += "<tr>"+
							"<td colspan='8' style='text-align:center;'>"+
								"<b style='color:red;'>"+Translator.t('没有选择具体产品，不能保存')+
								"</b>"+
							"</td>"+
						"</tr>";
			$('#save_purchase_btn').attr('disabled','disabled');
		}
		$("#purchase_order_prods_tb").html(HtmlStr);
		$(".sku_name_area").html(taxtarea_div_html);
		purchaseOrder.create.initWidget();
		$.hideLoading();
	},

	'popOperationLogView':function() {			

	},
	/* 1.0 function 2.0未调用
	// 查看或新增备注
	'memoViewOrSave':function() { 
	},
	'statusFormatter':function(value) {
		return purchaseOrder.create.setting.statusMap[value]["label"];
	},
	'paymentStatusFormatter':function(value) {
		//alert("statusFormatter");
		return purchaseOrder.create.setting.paymentStatusMap[value];
	},
	'warehouseFormatter':function(value) {
		//alert("statusFormatter");
		return purchaseOrder.create.setting.warehouseIdNameMap[value];
	},
	*/	
};

 function changeTwoDecimal(floatvar)
{
	var f_x = parseFloat(floatvar);
	if (isNaN(f_x))
	{
		alert('function:changeTwoDecimal->parameter error');
		return false;
	}
	var f_x = Math.round(floatvar*100)/100;
	return f_x;
}
