/**
 * +------------------------------------------------------------------------------
 * inventory stockchange的界面js
 * +------------------------------------------------------------------------------
 * 
 * @category 	js/project
 * @package 	inventory
 * @subpackage 	Exception
 * @author 		lzhil <zhiliang.lu@witsion.com>
 * @version 	1.0
 * +------------------------------------------------------------------------------
 */

if (typeof inventory === 'undefined')
	inventory = new Object();

//出入库object
inventory.stockchangeList = {
	'init' : function() {
		$('#btn_clear_stockchange').click(function() {
			url = global.baseUrl + "inventory/inventory/stockchange";
			window.location.href = url;
		});
		
		$( "#changelist_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#changelist_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
	},
	'showChangeDetail' : function(id, base64_id) {
		$.showLoading();
		$.get(global.baseUrl + 'inventory/inventory/showchangedetail?id='+base64_id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "viewStockChangeDetail",
				title : Translator.t("出入库单："+id +" 详情"),
				closeButton: true,
				buttons : {
					Cancel : {
						label : Translator.t("关闭"),
						className : "btn-default",
						callback : function() {
							$('.viewStockChangeDetail').modal('hide');
							$('.viewStockChangeDetail').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}
				},
				message : data,
			});
		});
	}
};
//库存数object
inventory.stockage = {
	getProdAllStockage : function(sku){
		var stockages = new Array();
		if(sku!==''){
			var url = global.baseUrl + 'inventory/inventory/product_all_stockage';
			$.showLoading();
			$.ajax({
				url:url,
				type:'get',
				data:{sku:sku},
				async : false,
				success:function(data){
					return data;
				},
				dataType:'json'
			});
		}
		else
			return stockages;
	},
};
//入库object
inventory.stockIn = {
	'prodStatus':'',
	'init' : function() {
		$( "#stockIn_date" ).datepicker({dateFormat:"yy-mm-dd 00:00:00",//datepicker 显示及editable折中方案
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
		$('#stock_change_id[name=stock_change_id]').formValidation({validType:['trim','length[1,30]'],tipPosition:'right',required:true});
		$('#btn_stockIn_import_excel').click(function(){
			if (typeof(importFile) != 'undefined'){
				var warehouse_id = $("#create_stockIn_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel?warehouse_id="+warehouse_id+"&changeType=stockin";
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
							$("#stockIn_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#btn_create_new_stockIn').removeAttr('disabled');
							inventory.stockIn.initCancelStockInProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockIn.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
		$('#btn_stockIn_import_text').click(function(){
			if (typeof(TextImport) != 'undefined'){
				var warehouse_id = $("#create_stockIn_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel-format-text?warehouse_id="+warehouse_id+"&changeType=stockin";
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
							$("#stockIn_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#btn_create_new_stockIn').removeAttr('disabled');
							inventory.stockIn.initCancelStockInProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockIn.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
		$('#open_purchase_arrived_list_win').click(function(){
			$.showLoading();
			$.get(global.baseUrl + 'inventory/inventory/arrived-purchase-list', function(data) {
				$.hideLoading();
				bootbox.dialog({
					className : "arrived_pruchaseOrder_win",
					closeButton: true,
					title : Translator.t("全部到货 的 采购单 列表"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								$('.arrived_pruchaseOrder_win').modal('hide');
								$('.arrived_pruchaseOrder_win').on('hidden.bs.modal', '.modal', function(event) {
									$(this).removeData('bs.modal');
								});
							}
						}
					},
					message : data,
				});
			});
			
		});
	},
	
	'initFormValidation':function(){
		$('input[name$="[stock_in_qty]"]').formValidation({validType:['trim','length[1,11]','amount'],tipPosition:'left',required:true});
		$('input[name$="[location_grid]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'left'});
		$('textarea[name="comment"]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'left'});
		$('[name="stock_change_id"]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'right',required:true});

	},
	
	'create_stockIn':function(){
		if (! $('#create_stockIn_data_form').formValidation('form_validate')){
				bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
				return;
			}
		
//		var info = $('#create_stockIn_data_form').serialize();
		var url = global.baseUrl + 'inventory/inventory/create_stockin';

		$.showLoading();
		$.ajax({
			type:'post',
			url:url,
//			data:info,
			data: {stock_in_json:JSON.stringify($('#create_stockIn_data_form').serializeJSON())},
//			data: {stock_in_json:$.toJSON($('#create_stockIn_data_form').serializeArray())},
			dataType:'json',
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "stockIn_created_result",
					title : Translator.t("操作结果"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								if(data.success==true){
									window.location.href = global.baseUrl+'inventory/inventory/stock_in';
								}else{
									$('.stockIn_created_result').modal('hide');
									$('.stockIn_created_result').on('hidden.bs.modal', '.modal', function(event) {
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
					className : "stockIn_created_result",
					title : Translator.t("操作结果"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								$('.stockIn_created_result').modal('hide');
								$('.stockIn_created_result').on('hidden.bs.modal', '.modal', function(event) {
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
	getStockInProds : function(){
		var prodList = new Array();
		var hasProds = $("#stockIn_prodList_tb tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var img = $("#stockIn_prodList_tb td[name$='[img]']").eq(i).attr('value');
				var sku = $("#stockIn_prodList_tb td[name$='[sku]']").eq(i)[0].innerHTML;
				var name = $("#stockIn_prodList_tb td[name$='[name]']").eq(i)[0].innerHTML;
				var status = $("#stockIn_prodList_tb td[name$='[status]']").eq(i).attr('value');
				var stock_in_qty = $("#stockIn_prodList_tb input[name$='[stock_in_qty]']").eq(i).val();
				var location_grid = $("#stockIn_prodList_tb input[name$='[location_grid]']").eq(i).val();
				prodList.push({'img':img,'sku':sku,'name':name,'status':status,'stock_in_qty':stock_in_qty,'location_grid':location_grid});
			}
		}
		return prodList;
	},
	selectStockInProd : function(){
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				inventory.stockIn.reflashProdListTable(prodList);
			},
		});
	},
	initCancelStockInProdBtn : function(){
		$("a.cancelStockInProd").unbind('click').click(function(){
			debugger;
			var prod_tr = $(this).parent().parent().parent();
			prod_tr.remove();
			var prodList = new Array();
			$.showLoading();
			inventory.stockIn.reflashProdListTable(prodList);
			$.hideLoading();
		});
	},
	reflashProdListTable : function(prods){	
		var HtmlStr = "<tr><th width='80px'>"+Translator.t('图片')+"</th>"+
						  "<th width='150px'>"+Translator.t('sku')+"</th>"+
						  "<th width='250px'>"+Translator.t('产品名称')+"</th>"+
						  //"<th width='100px'>"+Translator.t('状态')+"</th>"+
						  "<th width='100px'>"+Translator.t('在库数量')+"</th>"+
						  "<th width='100px'>"+Translator.t('入库数量')+"</th>"+
						  "<th width='100px'>"+Translator.t('货架位置')+"</th>"+
						  "<th width='70px'>"+Translator.t('操作')+"</th>"+
						"</tr>";
						
		var textarea_div_html="";
		var prodList = inventory.stockIn.getStockInProds();
		
		for (var j=0;j<prods.length;j++){
			var ishas = false;
			for (var k=0;k<prodList.length;k++){
				if (prods[j].sku == prodList[k].sku){
					ishas = true;
					if(typeof(prods[j].stock_in_qty)!=='undefined')
						prodList[k].stock_in_qty = prods[j].stock_in_qty;
					else
						prodList[k].stock_in_qty = 1;
					break;
				}
			}
			if (ishas)
				continue;
			else{
				var location_grid='';
				if(typeof(prods[j].location_grid)!=='undefined') location_grid=prods[j].location_grid;
				var stock_in_qty=1;
				if(typeof(prods[j].stock_in_qty)!=='undefined') stock_in_qty=prods[j].stock_in_qty;
				prodList.push({'img':prods[j].photo_primary,'sku':prods[j].sku,'name':prods[j].name,'status':prods[j].status,'stock_in_qty':stock_in_qty,'location_grid':location_grid});	
			}
		}

		if (prodList.length>0){
			$.showLoading();
			for(var l=0;l<prodList.length;l++){
				var status = prodList[l].status;
				for(var m=0;m<inventory.stockIn.prodStatus.length;m++){
					if (inventory.stockIn.prodStatus[m].key==prodList[l].status){
						status = inventory.stockIn.prodStatus[m].value;
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
				var qty_in_stock = 0;
				var location_grid='';
				var warehouse_id = $("#create_stockIn_data_form select[name='warehouse_id']").val();
				if (typeof(prodStockage)!=='undefined'){
					for(var n=0;n<prodStockage.length;n++){
						stockageHtml += ' warehouse_id_'+prodStockage[n].warehouse_id + '_qty="' + prodStockage[n].qty_in_stock +'" ';
						if (prodStockage[n].warehouse_id == warehouse_id){
							qty_in_stock = prodStockage[n].qty_in_stock;
							location_grid = prodStockage[n].location_grid;
						}
					}
				}

				var tmpNum = l/2;
				var striped_row = ' striped-row';
				if(parseInt(tmpNum) == tmpNum){
					striped_row='';
				}

				HtmlStr +="<tr class='prodList_tr"+striped_row+"'"+stockageHtml +">"+
							"<td name='prod["+l+"][img]' value='"+prodList[l].img+"' style='text-align:center'><img src='"+prodList[l].img+"' style='width:80px ! important;height:80px ! important'></td>"+
							"<td name='prod["+l+"][sku]'>"+prodList[l].sku+"</td>"+
							"<td name='prod["+l+"][name]' >"+prodList[l].name+"</td>"+
							//"<td name='prod["+l+"][status]' value='"+prodList[l].status+"'>"+status+"</td>"+
							"<td name='prod["+l+"][qty_in_stock]' value='"+qty_in_stock+"'>"+qty_in_stock+"</td>"+
							"<td><input name='prod["+l+"][stock_in_qty]' class='form-control' value='"+prodList[l].stock_in_qty+"'></td>"+
							"<td><input name='prod["+l+"][location_grid]' class='form-control' value='"+location_grid+"'></td>"+
							"<td><div><a class='cancelStockInProd'>"+Translator.t('取消')+"</a></div></td>"+
						"</tr>";
		
				textarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+prodList[l].sku+"</textarea>"+
							"<textarea class='hide' name='prod["+l+"][name]' style='display:none'>"+prodList[l].name+"</textarea>";
			}
			$('#btn_create_new_stockIn').removeAttr('disabled');
		}
		else{
			HtmlStr += "<tr>"+
							"<td colspan='7' style='text-align:center;'>"+
								"<b style='color:red;'>"+Translator.t('没有选择具体产品，不能保存')+
								"</b>"+
							"</td>"+
						"</tr>";
			$('#btn_create_new_stockIn').attr('disabled','disabled');
		}
		
		$("#stockIn_prodList_tb").html(HtmlStr);
		$(".sku_name_area").html(textarea_div_html);
		inventory.stockIn.initCancelStockInProdBtn();
		inventory.stockIn.initFormValidation();
		$.hideLoading();
	},
	//扫描商品入库
	scanningStockInProd : function(){
		$.modal({
			  url:'/inventory/inventory/scanning-stocklist',
			  method:'get',
			  data:{}
			},'扫描入库',{footer:false,inside:false}).done(function($modal){
				window.setTimeout(function () { 
					$("#code_input")[0].focus();
			    }, 1);
				//扫描自动匹配
				$('#code_input').on('keypress',function(e)
				{
					if(e.which == 13) 
					{
						scanning_input();
					}
				});
				//点击查找
				$(".scanning_search").on('click',function()
				{
					scanning_input();
				});
				
				//关闭页面
				$('.modal-close').click(function(){
					$('#over-lay').children().eq(1).show();
				});
				//确定
				$('.btn_enter').click(function(){
					var HtmlStrth = "<tr><th width='80px'>"+Translator.t('图片')+"</th>"+
					  "<th width='150px'>"+Translator.t('sku')+"</th>"+
					  "<th width='250px'>"+Translator.t('产品名称')+"</th>"+
					  "<th width='100px'>"+Translator.t('在库数量')+"</th>"+
					  "<th width='100px'>"+Translator.t('入库数量')+"</th>"+
					  "<th width='100px'>"+Translator.t('货架位置')+"</th>"+
					  "<th width='70px'>"+Translator.t('操作')+"</th>"+
					"</tr>";
					var HtmlStr="";
					var textarea_div_html="";
					
					var l=$("#stockIn_prodList_tb tbody ").find('.prodList_tr').length;
					$('.tab_order tbody').find("tr").each(function(){
						sku=$(this).find("td[id=sku]").html();
						prod_name=$(this).find("td[id=prod_name]").html();
						photo_primary=$(this).find("td[id=photo_primary]").find("img").attr('src');
						qty=$(this).find("td[id=qty]").html();
												
						var prodStockage = new Array();
						if(sku!==''){
							var url = global.baseUrl + 'inventory/inventory/product_all_stockage';
							$.ajax({
								url:url,
								type:'get',
								data:{sku},
								async : false,
								success:function(data){
									prodStockage = data;
								},
								dataType:'json'
							});
						}
						
						var stockageHtml = '';
						var qty_in_stock = 0;
						var location_grid='';
						var warehouse_id = $("#create_stockIn_data_form select[name='warehouse_id']").val();
						if (typeof(prodStockage)!=='undefined'){
							for(var n=0;n<prodStockage.length;n++){
								stockageHtml += ' warehouse_id_'+prodStockage[n].warehouse_id + '_qty="' + prodStockage[n].qty_in_stock +'" ';
								if (prodStockage[n].warehouse_id == warehouse_id){
									qty_in_stock = prodStockage[n].qty_in_stock;
									location_grid = prodStockage[n].location_grid;
								}
							}
						}
						
						var tmpNum = l/2;
						var striped_row = ' striped-row';
						if(parseInt(tmpNum) == tmpNum){
							striped_row='';
						}
						
						$leaveeach=0; //是否存在已经有的sku入库记录
						$("#stockIn_prodList_tb tbody").find('.prodList_tr').each(function(i){
							$compare=$(this).find('td[name="prod['+i+'][sku]"]').html();
							if($compare==sku){
								//叠加
//								$qty_compare=parseInt($(this).find('input[name="prod['+i+'][stock_in_qty]"]').val());
//			        			$qty_compare=$qty_compare+parseInt(qty);
			        			//覆盖
			        			$qty_compare=parseInt(qty);
			        			$(this).find('input[name="prod['+i+'][location_grid]"]').val(location_grid);
			        			
			        			$(this).find('input[name="prod['+i+'][stock_in_qty]"]').val($qty_compare);
			        			$leaveeach=1;
							}
						});
						
						if($leaveeach==0){
							HtmlStr +="<tr class='prodList_tr"+striped_row+"'"+stockageHtml +">"+
							"<td name='prod["+l+"][img]' value='"+photo_primary+"' style='text-align:center'><img src='"+photo_primary+"' style='width:80px ! important;height:80px ! important'></td>"+
							"<td name='prod["+l+"][sku]'>"+sku+"</td>"+
							"<td name='prod["+l+"][name]' >"+prod_name+"</td>"+
							"<td name='prod["+l+"][qty_in_stock]' value='"+qty_in_stock+"'>"+qty_in_stock+"</td>"+
							"<td><input name='prod["+l+"][stock_in_qty]' class='form-control' value='"+qty+"'></td>"+
							"<td><input name='prod["+l+"][location_grid]' class='form-control' value='"+location_grid+"'></td>"+
							"<td><div><a class='cancelStockInProd'>"+Translator.t('取消')+"</a></div></td>"+
							"</tr>";
							
							textarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+sku+"</textarea>"+
							"<textarea class='hide' name='prod["+l+"][name]' style='display:none'>"+prod_name+"</textarea>";
							
							l++;
						}
					});
					
					if($("#stockIn_prodList_tb tbody ").find('.prodList_tr').length<1 && l>0)
						$("#stockIn_prodList_tb").html(HtmlStrth);
					
					if(l>0){
						$('#btn_create_new_stockIn').removeAttr('disabled');
						$(".sku_name_area").append(textarea_div_html);
						$("#stockIn_prodList_tb").append(HtmlStr);
						inventory.stockIn.initCancelStockInProdBtn();
						inventory.stockIn.initFormValidation();
						$.hideLoading();
					}
					
					$modal.close();
				});
			});
	},
};
//匹配sku（扫描出入库）
function scanning_input(){
	var code = $('#code_input');
	var val = code.val().replace(/(^\s*)|(\s*$)/g, "");
	
	var Url=global.baseUrl +'inventory/inventory/sku-stock-save';
	$.ajax({
        type : 'post',
        cache : 'false',
        dataType: 'json',
        data : {'val':val},
		url: Url,
        success:function(response) 
        {		 
        	var HtmlStr = "";
        	
        	data = response['data'];
        	
        	if(data['sku']!='' && data['type']!='B' && data['type']!='C'){        		
        		$sku_td_q=0;
        		$sku_td='';
        		$('.tab_order').find("td[id=sku]").each(function(){
        			if($(this).html()==data['sku'])
        				$sku_td=$(this);
        		});
        		if($sku_td.length==0){      		
		        	HtmlStr +=
		        		"<tr>"+
		        			"<td id='sku' style='border:1px solid #ccc;display:none;'>"+ data['sku'] +"</td>"+
		        			"<td id='photo_primary' style='border:1px solid #ccc;'><img style='max-height: 50px; max-width: 50px;' src='"+data['photo_primary']+"'></td>"+
							"<td style='border:1px solid #ccc;'>"+ data['sku'] +"<br/>"+data['prod_name']+"</td>"+
							"<td id='qty' style='border:1px solid #ccc;'>1</td>"+
							"<td id='prod_name' style='border:1px solid #ccc;display:none;'>"+ data['prod_name'] +"</td>"+
							"<td name='scanning_status' style='border:1px solid #ccc;'>"+ 
							"<a href='javascript:;' onclick='saoMiaoCxNum(this)'>撤销</a><br/><a href='javascript:;' onclick='saoMiaoInRemoveNum(this)'>移除</a>" +
							"</td>"+
						"</tr>";
		        	$(".tab_order tbody").append(HtmlStr);
        		}
        		else{ 
        			$qty=parseInt($sku_td.parent().find('td[id=qty]').html());
        			$qty=$qty+1;
        			$sku_td.parent().find('td[id=qty]').html($qty)
        		}
        		
        		$('#scanning_detail').css("display","block");
        		$('#scanning_err').html('<p class="text-warn">&nbsp;</p>');
        	}
        	else{
        		$('#scanning_err').html('<p class="text-warn">错误提醒：没有找到对应的商品 或 捆绑商品和变参商品暂时不支持扫描！</p>');
        	}
        	
        	$('#code_input').select();
        }
    });
}
//扫描出入库-移除
function saoMiaoInRemoveNum(obj){
	$(obj).parent('td').parent('tr').remove();
}
//扫描出入库-撤销
function saoMiaoCxNum(obj){
	$qty_td=$(obj).parent('td').parent('tr').find("td[id=qty]");
	$qty=parseInt($qty_td.html());
	$qty=$qty-1;
	if($qty<1)
		$qty=0;
	$qty_td.html($qty);
}
//出库object
inventory.stockOut = {
	'prodStatus':'',
	'init' : function() {
		$( "#stockOut_date" ).datepicker({dateFormat:"yy-mm-dd 00:00:00",//datepicker 显示及editable折中方案
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
		$('#stock_change_id[name=stock_change_id]').formValidation({validType:['trim','length[1,30]'],tipPosition:'right',required:true});
		$('#btn_stockOut_import_excel').click(function(){
			if (typeof(importFile) != 'undefined'){
				var warehouse_id = $("#create_stockOut_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel?warehouse_id="+warehouse_id+"&changeType=stockout";
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
							$("#stockOut_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#btn_create_new_stockOut').removeAttr('disabled');
							inventory.stockOut.initCancelStockOutProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockOut.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
			
		});
		$('#btn_stockOut_import_text').click(function(){
			if (typeof(TextImport) != 'undefined'){
				var warehouse_id = $("#create_stockOut_data_form select[name='warehouse_id']").val();
				form_url = "/inventory/inventory/import-stock-change-prods-by-excel-format-text?warehouse_id="+warehouse_id+"&changeType=stockOut";
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
							$("#stockOut_prodList_tb").html(result.td_Html);
							$(".sku_name_area").html(result.textarea_div_html);
							$('#btn_create_new_stockOut').removeAttr('disabled');
							inventory.stockOut.initCancelStockOutProdBtn();
							$.hideLoading();
							bootbox.alert('<b>'+Translator.t('导入成功,请检查列表是否有误。')+'</b>'+addMsg);
							inventory.stockOut.initFormValidation();
						}
					}
				);
			}else{
				bootbox.alert("<b class='red-tips'>"+Translator.t("没有引入相应的文件!")+"</b>");
			}
		});
	},
	
	'initFormValidation':function(){
		$('input[name$="[stock_out_qty]"]').formValidation({validType:['trim','length[1,11]','amount'],tipPosition:'left',required:true});
		$('input[name$="[location_grid]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'left'});
		$('textarea[name="comment"]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'left'});
		$('[name="stock_change_id"]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'right',required:true});
		
	},
	
	'create_stockOut':function(){
		if (! $('#create_stockOut_data_form').formValidation('form_validate')){
				bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
				return;
			}
		
		var info =$('#create_stockOut_data_form ').serialize();
		var url = global.baseUrl + 'inventory/inventory/create_stockout';

		$.showLoading();
		$.ajax({
			type:'post',
			url:url,
			data:info,
			dataType:'json',
			success:function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "stockOut_created_result",
					title : Translator.t("操作结果"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								if(data.success==true){
									window.location.href = global.baseUrl+'inventory/inventory/stock_out';
								}else{
									$('.stockOut_created_result').modal('hide');
									$('.stockOut_created_result').on('hidden.bs.modal', '.modal', function(event) {
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
					className : "stockOut_created_result",
					title : Translator.t("操作结果"),
					buttons : {
						Cancel : {
							label : Translator.t("关闭"),
							className : "btn-default",
							callback : function() {
								$('.stockOut_created_result').modal('hide');
								$('.stockOut_created_result').on('hidden.bs.modal', '.modal', function(event) {
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
	getStockOutProds : function(){
		var prodList = new Array();
		var hasProds = $("#stockOut_prodList_tb tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var img = $("#stockOut_prodList_tb td[name$='[img]']").eq(i).attr('value');
				var sku = $("#stockOut_prodList_tb td[name$='[sku]']").eq(i)[0].innerHTML;
				var name = $("#stockOut_prodList_tb td[name$='[name]']").eq(i)[0].innerHTML;
				var status = $("#stockOut_prodList_tb td[name$='[status]']").eq(i).attr('value');
				var stock_out_qty = $("#stockOut_prodList_tb input[name$='[stock_out_qty]']").eq(i).val();
				var location_grid = $("#stockOut_prodList_tb input[name$='[location_grid]']").eq(i).val();
				prodList.push({'img':img,'sku':sku,'name':name,'status':status,'stock_out_qty':stock_out_qty,'location_grid':location_grid});
			}
		}
		return prodList;
	},
	selectStockOutProd : function(){
		$(this).selectProduct('open',{
			afterSelectProduct:function(prodList){
				inventory.stockOut.reflashProdListTable(prodList);
			},
		});
	},
	initCancelStockOutProdBtn : function(){
		$("a.cancelStockOutProd").unbind('click').click(function(){
			debugger;
			var prod_tr = $(this).parent().parent().parent();
			prod_tr.remove();
			var prodList = new Array();
			$.showLoading();
			inventory.stockOut.reflashProdListTable(prodList);
			$.hideLoading();
		});
	},
	reflashProdListTable : function(prods){	
		var HtmlStr = "<tr><th width='80px'>"+Translator.t('图片')+"</th>"+
						  "<th width='150px'>"+Translator.t('sku')+"</th>"+
						  "<th width='250px'>"+Translator.t('产品名称')+"</th>"+
						  //"<th width='100px'>"+Translator.t('状态')+"</th>"+
						  "<th width='100px'>"+Translator.t('在库数量')+"</th>"+
						  "<th width='100px'>"+Translator.t('出库数量')+"</th>"+
						  "<th width='100px'>"+Translator.t('货架位置')+"</th>"+
						  "<th width='70px'>"+Translator.t('操作')+"</th>"+
						"</tr>";
		var textarea_div_html="";
		var prodList = inventory.stockOut.getStockOutProds();
		
		for (var j=0;j<prods.length;j++){
			var ishas = false;
			for (var k=0;k<prodList.length;k++){
				if (prods[j].sku == prodList[k].sku){
					ishas = true;
					if(typeof(prods[j].stock_out_qty)!=='undefined')
						prodList[k].stock_out_qty = prods[j].stock_out_qty;
					else
						prodList[k].stock_out_qty = 1;
					break;
				}
			}
			if (ishas)
				continue;
			else{
				var location_grid='';
				if(typeof(prods[j].location_grid)!=='undefined') location_grid=prods[j].location_grid;
				var stock_out_qty=1;
				if(typeof(prods[j].stock_out_qty)!=='undefined') stock_out_qty=prods[j].stock_out_qty;
				prodList.push({'img':prods[j].photo_primary,'sku':prods[j].sku,'name':prods[j].name,'status':prods[j].status,'stock_out_qty':stock_out_qty,'location_grid':location_grid});
			}
		}

		if (prodList.length>0){
			$.showLoading();
			for(var l=0;l<prodList.length;l++){
				var status = prodList[l].status;
				for(var m=0;m<inventory.stockOut.prodStatus.length;m++){
					if (inventory.stockOut.prodStatus[m].key==prodList[l].status){
						status = inventory.stockOut.prodStatus[m].value;
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
				var qty_in_stock = 0;
				var warehouse_id = $("#create_stockOut_data_form select[name='warehouse_id']").val();
				if (typeof(prodStockage)!=='undefined'){
					for(var n=0;n<prodStockage.length;n++){
						stockageHtml += ' warehouse_id_'+prodStockage[n].warehouse_id + '_qty="' + prodStockage[n].qty_in_stock +'" ';
						if (prodStockage[n].warehouse_id == warehouse_id){
							qty_in_stock = prodStockage[n].qty_in_stock;
						}
					}
				}

				var tmpNum = l/2;
				var striped_row = ' striped-row';
				if(parseInt(tmpNum) == tmpNum){
					striped_row='';
				}

				HtmlStr +="<tr class='prodList_tr"+striped_row+"'"+stockageHtml +">"+
							"<td style='text-align:center;' name='prod["+l+"][img]' value='"+prodList[l].img+"'><img src='"+prodList[l].img+"' style='width:80px ! important;height:80px ! important'></td>"+
							"<td name='prod["+l+"][sku]'>"+prodList[l].sku+"</td>"+
							"<td name='prod["+l+"][name]'>"+prodList[l].name+"</td>"+
							//"<td name='prod["+l+"][status]' value='"+prodList[l].status+"'>"+status+"</td>"+
							"<td name='prod["+l+"][qty_in_stock]' value='"+qty_in_stock+"'>"+qty_in_stock+"</td>"+
							"<td><input name='prod["+l+"][stock_out_qty]' class='form-control' value='"+prodList[l].stock_out_qty+"'></td>"+
							"<td><input name='prod["+l+"][location_grid]' class='form-control' value='"+prodList[l].location_grid+"'></td>"+
							"<td><div><a class='cancelStockOutProd'>"+Translator.t('取消')+"</a></div></td>"+
						"</tr>";
				textarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+prodList[l].sku+"</textarea>"+
							"<textarea class='hide' name='prod["+l+"][name]' style='display:none'>"+prodList[l].name+"</textarea>";
			}
			$('#btn_create_new_stockOut').removeAttr('disabled');
		}
		else{
			HtmlStr += "<tr>"+
							"<td colspan='7' style='text-align:center;'>"+
								"<b style='color:red;'>"+Translator.t('没有选择具体产品，不能保存')+
								"</b>"+
							"</td>"+
						"</tr>";
			$('#btn_create_new_stockOut').attr('disabled','disabled');
		}
		$("#stockOut_prodList_tb").html(HtmlStr);
		$(".sku_name_area").html(textarea_div_html);
		inventory.stockOut.initCancelStockOutProdBtn();
		inventory.stockOut.initFormValidation();
		$.hideLoading();
	},
	'StockInByPurchase' : function (){
		
	},
	//扫描商品出库
	scanningStockOutProd : function(){
		$.modal({
			  url:'/inventory/inventory/scanning-stocklist',
			  method:'get',
			  data:{}
			},'扫描出库',{footer:false,inside:false}).done(function($modal){
				window.setTimeout(function () { 
					$("#code_input")[0].focus();
			    }, 1);
				//扫描自动匹配
				$('#code_input').on('keypress',function(e)
				{
					if(e.which == 13) 
					{
						scanning_input();
					}
				});
				//点击查找
				$(".scanning_search").on('click',function()
				{
					scanning_input();
				});
				
				//关闭页面
				$('.modal-close').click(function(){
					$('#over-lay').children().eq(1).show();
				});
				//确定
				$('.btn_enter').click(function(){
					var HtmlStrth = "<tr><th width='80px'>"+Translator.t('图片')+"</th>"+
					  "<th width='150px'>"+Translator.t('sku')+"</th>"+
					  "<th width='250px'>"+Translator.t('产品名称')+"</th>"+
					  "<th width='100px'>"+Translator.t('在库数量')+"</th>"+
					  "<th width='100px'>"+Translator.t('出库数量')+"</th>"+
					  "<th width='100px'>"+Translator.t('货架位置')+"</th>"+
					  "<th width='70px'>"+Translator.t('操作')+"</th>"+
					"</tr>";
					var HtmlStr="";
					var textarea_div_html="";
					
					var l=$("#stockOut_prodList_tb tbody ").find('.prodList_tr').length;
					$('.tab_order tbody').find("tr").each(function(){
						sku=$(this).find("td[id=sku]").html();
						prod_name=$(this).find("td[id=prod_name]").html();
						photo_primary=$(this).find("td[id=photo_primary]").find("img").attr('src');
						qty=$(this).find("td[id=qty]").html();
												
						//搜索在线库存
						var prodStockage = new Array();
						if(sku!==''){
							var url = global.baseUrl + 'inventory/inventory/product_all_stockage';
							$.ajax({
								url:url,
								type:'get',
								data:{sku},
								async : false,
								success:function(data){
									prodStockage = data;
								},
								dataType:'json'
							});
						}
						
						var stockageHtml = '';
						var qty_in_stock = 0;
						var location_grid='';
						var warehouse_id = $("#create_stockOut_data_form select[name='warehouse_id']").val();
						if (typeof(prodStockage)!=='undefined'){
							for(var n=0;n<prodStockage.length;n++){
								stockageHtml += ' warehouse_id_'+prodStockage[n].warehouse_id + '_qty="' + prodStockage[n].qty_in_stock +'" ';
								if (prodStockage[n].warehouse_id == warehouse_id){
									qty_in_stock = prodStockage[n].qty_in_stock;
									location_grid = prodStockage[n].location_grid;
								}
							}
						}
						
						var tmpNum = l/2;
						var striped_row = ' striped-row';
						if(parseInt(tmpNum) == tmpNum){
							striped_row='';
						}
						
						$leaveeach=0; //是否存在已经有的sku入库记录
						$("#stockOut_prodList_tb tbody").find('.prodList_tr').each(function(i){
							$compare=$(this).find('td[name="prod['+i+'][sku]"]').html();
							if($compare==sku){
								//叠加
//								$qty_compare=parseInt($(this).find('input[name="prod['+i+'][stock_in_qty]"]').val());
//			        			$qty_compare=$qty_compare+parseInt(qty);
			        			//覆盖
			        			$qty_compare=parseInt(qty);
			        			$(this).find('input[name="prod['+i+'][location_grid]"]').val(location_grid);
			        			
			        			$(this).find('input[name="prod['+i+'][stock_out_qty]"]').val($qty_compare);
			        			$leaveeach=1;
							}
						});
						
						if($leaveeach==0){
							HtmlStr +="<tr class='prodList_tr"+striped_row+"'"+stockageHtml +">"+
							"<td name='prod["+l+"][img]' value='"+photo_primary+"' style='text-align:center'><img src='"+photo_primary+"' style='width:80px ! important;height:80px ! important'></td>"+
							"<td name='prod["+l+"][sku]'>"+sku+"</td>"+
							"<td name='prod["+l+"][name]' >"+prod_name+"</td>"+
							"<td name='prod["+l+"][qty_in_stock]' value='"+qty_in_stock+"'>"+qty_in_stock+"</td>"+
							"<td><input name='prod["+l+"][stock_out_qty]' class='form-control' value='"+qty+"'></td>"+
							"<td><input name='prod["+l+"][location_grid]' class='form-control' value='"+location_grid+"'></td>"+
							"<td><div><a class='cancelStockOutProd'>"+Translator.t('取消')+"</a></div></td>"+
							"</tr>";
							
							textarea_div_html += "<textarea class='hide' name='prod["+l+"][sku]' style='display:none'>"+sku+"</textarea>"+
							"<textarea class='hide' name='prod["+l+"][name]' style='display:none'>"+prod_name+"</textarea>";
							l++;
							
						}
					});
					
					if($("#stockOut_prodList_tb tbody ").find('.prodList_tr').length<1 && l>0)
						$("#stockOut_prodList_tb").html(HtmlStrth);
					
					if(l>0){
						$('#btn_create_new_stockOut').removeAttr('disabled');
						$(".sku_name_area").append(textarea_div_html);
						$("#stockOut_prodList_tb tbody").append(HtmlStr);
						inventory.stockOut.initCancelStockOutProdBtn();
						inventory.stockOut.initFormValidation();
						$.hideLoading();
					}
					
					$modal.close();
				});
			});
	},
};

$("#create_stockIn_data_form select[name='warehouse_id']").change(function(){
	var warehouse_id = $(this).val();
	var hasProds = $("#stockIn_prodList_tb tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var wh_qty = $("#stockIn_prodList_tb tr.prodList_tr").eq(i).attr("warehouse_id_"+warehouse_id+"_qty");
				if (typeof(wh_qty)=='undefined' || wh_qty=='') wh_qty=0;
				$("#stockIn_prodList_tb td[name$='[qty_in_stock]']").eq(i).attr('value',wh_qty);
				$("#stockIn_prodList_tb td[name$='[qty_in_stock]']").eq(i).html(wh_qty);
			}
		}
});

$("#create_stockOut_data_form select[name='warehouse_id']").change(function(){
	var warehouse_id = $(this).val();
	var hasProds = $("#stockOut_prodList_tb tr.prodList_tr").length;
		if (typeof(hasProds)!=='undefined' && hasProds!==0){
			for(var i=0;i<hasProds;i++){
				var wh_qty = $("#stockOut_prodList_tb tr.prodList_tr").eq(i).attr("warehouse_id_"+warehouse_id+"_qty");
				if (typeof(wh_qty)=='undefined' || wh_qty=='') wh_qty=0;
				$("#stockOut_prodList_tb td[name$='[qty_in_stock]']").eq(i).attr('value',wh_qty);
				$("#stockOut_prodList_tb td[name$='[qty_in_stock]']").eq(i).html(wh_qty);
			}
		}
});

$("#disbundle_purchase_stockin").unbind('click').click(function(){
	bootbox.confirm(Translator.t('确定解除与采购单的绑定？'), function(result) {
		if (result) {
			$("#create_stockIn_data_form input[name='stock_change_id']").val('');
			$("#create_stockIn_data_form input[name='purchase_order_id']").val('');
			$("#create_stockIn_data_form input[name$='[stock_in_qty]']").removeAttr('readonly');
			$("#disbundle_purchase_stockin").css('display','none');
			$("#disbundle_purchase_stockin").attr('disabled','disabled');
			$("#select_prod_btn,#btn_stockIn_import_text,#btn_stockIn_import_excel").css('display','initial').removeAttr('disabled','disabled');
		}
	});
});