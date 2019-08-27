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
 *采购建议的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		suggestion
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */



if(typeof purchaseSug === 'undefined')
	purchaseSug = new Object();

purchaseSug.list = {
	'printInfo' : [],
	'generatePurchaseWinHtml':'',
	'init' : function (){
		$('#purchaselist_startdate').datepicker({dateFormat:"yy-mm-dd"});
		$('#purchaselist_enddate').datepicker({dateFormat:"yy-mm-dd"});
		
		// 生成采购单
    	$('#list_generate_purchase_order').click(function(){
    		purchaseSug.list.listGeneratePurchaseOrder(); 
    	});
		// 打印采购建议
		$('#print_selected_suggestion').click(function(){
    		purchaseSug.list.printSug(); 
    	});
		//打印见单采购
		$('#print_meet_order').click(function(){
    		purchaseSug.list.printMeetOrder(); 
    	});
		//更新缺货采购建议
		$('#refresh_suggestion').click(function(){
			purchaseSug.list.RefreshSuggestion();
		});
		
		//checkbox
		$(".sugList .select_one, .meet_order_list .select_one").unbind('click').click(function(){
				if ( typeof($(this).attr('checked'))=='undefined' || $(this).attr('checked')==false ){
					$(this).attr('checked','checked');
				}else{
					$(this).attr('checked',false);
				}
				purchaseSug.list.checkBtnDisable();
			});
		$("#select_all").click(function(){
			$(".sugList .select_one").prop('checked',this.checked);
			$(".meet_order_list .select_one").prop('checked',this.checked);
			// $(".sugList .select_one").attr('checked',this.checked);//jquery 问题
			purchaseSug.list.checkBtnDisable();
		});
	},
	//切换显示的建议类型
	'switchShowSuggestType' : function(suggest_type){
		window.location.href = global.baseUrl+'purchase/purchasesug/sugindex?suggest_type='+suggest_type;
	},
	//动态检查，决定功能键是否应该可用
	'checkBtnDisable' : function () {
		var selectCount = $(".select_one:checked").length;
		if (selectCount > 0) {
			$('#print_selected_suggestion').removeAttr('disabled');
			$('#list_generate_purchase_order').removeAttr('disabled');
			$('#print_meet_order').removeAttr('disabled');
		} else {
			$('#print_selected_suggestion').attr('disabled','disabled');
			$('#list_generate_purchase_order').attr('disabled','disabled');
			$('#print_meet_order').attr('disabled','disabled');
		}
	},
	'listGeneratePurchaseOrder' : function(){
		var selectCount = $(".select_one:checked").length;
		if(selectCount>0){
			var prodsData ='';//所选建议data，string类型
			for(var i=0;i<selectCount; i++){
				var parent_Tr = $(".select_one:checked").eq(i).parent().parent();
				var sku =parent_Tr.find("[name='sku[]']").attr('value');
				if(typeof(sku) =='undefined' || sku ==''){
					bootbox.alert({
						title:'Warning',
						message:Translator.t('网页有错误，选中的行 suk 丢失'),
					});
					return false;
				}
				else{
					var aProd = new Object();
					aProd['sku'] = sku;
					aProd['purchase_link'] = parent_Tr.find("[name='sku[]']").find("a").attr('href');
					aProd['img'] = parent_Tr.find("[name='photo_primary[]']").attr('value');
					aProd['name'] =parent_Tr.find("[name='name[]']")[0].innerHTML;
					aProd['qty'] = parent_Tr.find("[name='purchaseSug[]']").attr('value');
					aProd['price'] = parent_Tr.find("[name='purchase_price[]']").attr('value');
					if(aProd['price']=='')
						aProd['price']=0;
					aProd['amount'] = aProd['qty']*aProd['price'];
					if(prodsData=='')
						prodsData += JSON.stringify(aProd);//数组转成jsonStr
					else
						prodsData += '-explode-'+JSON.stringify(aProd);//手工拼接,-explode-为'分隔符'
				}
			}
			if(prodsData.length<=0){
				bootbox.alert({
					title:'Warning',
					message:Translator.t('请选择至少一条建议'),
				});
				return false;
			}
			//默认以第一选中行的仓库
			var warehouseid = $(".select_one:checked").eq(0).parent().parent().find("[name='warehouse_id[]']").attr('value');

			//getUrlHtml
			var win_view_rul = global.baseUrl+'purchase/purchasesug/suggestion-to-purchase-order';
			purchaseSug.list.getViewHtml(win_view_rul,prodsData, warehouseid);
			var win_view = purchaseSug.list.generatePurchaseWinHtml;

			bootbox.dialog({
				className : "selected_suggestion_generate_purchase_order_win",
				title : Translator.t("采购建议 生成 采购单"),
				closeButton: true,
				buttons : {
					Cancel : {
						label : Translator.t("取消"),
						className : "btn-default",
						callback : function() {
							$('.selected_suggestion_generate_purchase_order_win').modal('hide');
							$('.selected_suggestion_generate_purchase_order_win').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					},
					save : {
						label : Translator.t("保存"),
						className : "btn-primary",
						callback : function() {
							$.showLoading();
							var info = $('#create-purchase-form').serialize();
							var url = global.baseUrl + 'purchase/purchase/save';
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
														//window.location.href = global.baseUrl+'purchase/purchasesug/sugindex';
														window.location.reload();
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
					}
				},
				message : win_view,
			});
		} else {
			 bootbox.alert({
				title:'Warning',
				message:Translator.t('请勾选要生成采购单的采购建议！'),
			});
			return false;
		 }
	},
	'getViewHtml' : function(url,data,warehouseid){
		var html='';
		$.ajax({
			url:url, 
			data:{data:data, warehouseid:warehouseid},
			type:'post',
			dataType:'html',
			async:false,
			success:function(result){
				purchaseSug.list.generatePurchaseWinHtml = result;
				return true;
			},
			error:function(){
				purchaseSug.list.generatePurchaseWinHtml = '<b style="color:red;">'+Translator.t("后台获取数据异常，请重试")+"</b>";
				return true;
			}
		});
	},
	'printSug' : function(){
    	var selectCount = $(".select_one:checked").length;
		if(selectCount>0){
			var prodsData =new Array();//选择的建议的产品数据
			for(var i=0;i<selectCount; i++){
				var parent_Tr = $(".select_one:checked").eq(i).parent().parent();
				var sku =parent_Tr.find("td:eq(2)").html();
				if(typeof(sku) =='undefined' || sku ==''){
					bootbox.alert({
						title:'Warning',
						message:Translator.t('网页有错误，选中的行 suk 丢失'),
					});
					return false;
				}
				else{
					var aProd = new Object();
					aProd['sku'] = sku;
					aProd['img'] = parent_Tr.find("[name='photo_primary[]']").attr('value');
					aProd['name'] =parent_Tr.find("[name='name[]']").html();
					aProd['warehouse'] =parent_Tr.find("[name='warehouse_id[]']").html();
					aProd['qty'] = parent_Tr.find("[name='purchaseSug[]']").attr('value');
					aProd['price'] = parent_Tr.find("[name='purchase_price[]']").attr('value');
					if(aProd['price']=='')
						aProd['price']=0;
					aProd['reason'] = parent_Tr.find("[name='purchaseReasonStr[]']").attr('value');
					aProd['supplier'] = parent_Tr.find("[name='primary_supplier_name[]']").html();
					
					prodsData.push(aProd);
				}
			}
			
			if(prodsData.length<=0){
				bootbox.alert({
					title:'Warning',
					message:Translator.t('请选择至少一条建议'),
				});
				return false;
			}
			var columns =new Array();//表格标题及样式信息
			for(var i=0;i<$(".sugList table tr th").length;i++){
				var column = new Object();
				var tag = $(".sugList table tr th").eq(i).attr('tag');
				if(tag=='ck')
					continue;
				column['field'] = tag;
				switch (column['field'])
				{
				case 'img':
				  column['title'] = Translator.t('图片');
				  break;
				case 'sku':
				  column['title'] = 'sku';
				  break;
				case 'name':
				   column['title'] = Translator.t('产品名称');
				  break;
				case 'warehouse':
				   column['title'] = Translator.t('仓库名称');
				  break;
				case 'reason':
				   column['title'] = Translator.t('采购原因');
				  break;
				case 'qty':
				   column['title'] = Translator.t('建议采购量');
				  break;
				case 'supplier':
				   column['title'] = Translator.t('首选供应商');
				  break;
				case 'price':
				  column['title'] = Translator.t('上次报价');
				  break;
				}
				column['rowspan'] = $(".sugList table tr th").eq(i).attr('rowspan');
				column['colspan'] = $(".sugList table tr th").eq(i).attr('colspan');
				column['align'] = 'center';
				column['width'] = 0;
				if(typeof($(".sugList table tr th").eq(i)[0].clientWidth) !=='undefined')
					column['width'] = $(".sugList table tr th").eq(i)[0].clientWidth;//td实际宽度
				columns.push(column);
			}
			//写入全局变量
			purchaseSug.list.printInfo.dataRows = prodsData;
			purchaseSug.list.printInfo.columns = columns;
			window.open(global.baseUrl+'purchase/purchasesug/print-sug');
		}
		else {
			 bootbox.alert({
				title:'Warning',
				message:Translator.t('请勾选要生成采购单的采购建议！'),
			});
			return false;
		 }
	},
	'printMeetOrder' : function(){
    	var selectCount = $(".select_one:checked").length;
		if(selectCount>0){
			var prodsData =new Array();//选择的产品数据
			for(var i=0;i<selectCount; i++){
				var parent_Tr = $(".select_one:checked").eq(i).parent().parent();
				var sku =parent_Tr.find("td:eq(1)").html();
				if(typeof(sku) =='undefined' || sku ==''){
					bootbox.alert({
						title:'Warning',
						message:Translator.t('网页有错误，选中的行 suk 丢失'),
					});
					return false;
				}
				else{
					var aProd = new Object();
					aProd['sku'] = sku;
					aProd['img'] = parent_Tr.find("td:eq(2)").attr('value');
					aProd['name'] =parent_Tr.find("td:eq(3)").html();
					aProd['product_attributes'] =parent_Tr.find("td:eq(4)").html();
					aProd['prod_name_ch'] =parent_Tr.find("td:eq(5)").html();
					aProd['qty'] = parent_Tr.find("td:eq(6)").html();
					aProd['supplier'] = parent_Tr.find("td:eq(7)").html();
					prodsData.push(aProd);
				}
			}
			
			if(prodsData.length<=0){
				bootbox.alert({
					title:'Warning',
					message:Translator.t('请选择至少一种商品'),
				});
				return false;
			}
			var columns =new Array();//表格标题及样式信息
			for(var i=0;i<$(".meet_order_list table tr th").length;i++){
				var column = new Object();
				var tag = $(".meet_order_list table tr th").eq(i).attr('tag');
				if(tag=='ck')
					continue;
				column['field'] = tag;
				switch (column['field'])
				{
				case 'img':
				  column['title'] = Translator.t('图片');
				  column['width'] = 100;
				  break;
				case 'sku':
				  column['title'] = 'sku';
				  column['width'] = 150;
				  break;
				case 'name':
				   column['title'] = Translator.t('产品名称');
				   column['width'] = 300;
				  break;
				case 'product_attributes':
					   column['title'] = Translator.t('商品属性');
					   column['width'] = 150;
					  break;
				case 'prod_name_ch':
				   column['title'] = Translator.t('中文配货名');
				   column['width'] = 250;
				  break;
				case 'qty':
				   column['title'] = Translator.t('需求数量');
				   column['width'] = 100;
				  break;
				case 'supplier':
				   column['title'] = Translator.t('供应商');
				   column['width'] = 180;
				  break;
				}
				column['rowspan'] = 1;
				column['colspan'] = 1;
				column['align'] = 'center';
				
				columns.push(column);
			}
			//写入全局变量
			purchaseSug.list.printInfo.dataRows = prodsData;
			purchaseSug.list.printInfo.columns = columns;
			window.open(global.baseUrl+'purchase/purchasesug/print-sug');
		}
		else {
			 bootbox.alert({
				title:'Warning',
				message:Translator.t('请勾选要进行见单采购的商品！'),
			});
			return false;
		 }
	},
	
	//更新缺货采购建议
	"RefreshSuggestion" : function(){
		$.showLoading();
		
		$.ajax({
			type: 'post',
			cache: 'false',
			dataType: 'json',
			url: '/purchase/purchasesug/refresh-suggestion',
			success: function(result){
				$.hideLoading();
				bootbox.alert("更新成功！");
				window.location.reload();
			},
			error: function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			}
		});
	}
};










