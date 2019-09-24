/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lolo <jiongqiang.xiao@witsion.com>
+----------------------------------------------------------------------
| Copy by: lzhl <zhiliang.lu@witsion.com> 2015-04-10 eagle 2.0
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *查看/修改指定的采购单的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		purchase
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof purchaseOrder === 'undefined')  purchaseOrder = new Object();
purchaseOrder.updateorview={
	'setting':{	'purchaseDetail':"",'comments':"",'purchaseItems' : "",'shippingModes':"",'mode':"",'statusMap':"",'paymentStatusMap':"",'suppliers':"",'warehouseIdNameMap':"",'purchaseId':"",'purchaseOrderId':""},
	'initWidget': function() {
		$('input[required="required"]').formValidation({validType:['trim','length[1,250]'],tipPosition:'left',required:true});
		$('input#purchase_order_id').formValidation({validType:['trim','length[1,255]'],tipPosition:'left',required:true});
		$('#capture_user_name').formValidation({validType:['trim','length[1,250]'],tipPosition:'left'});
		
		$("select[name='supplier_name']").combobox();
		$("select[name='supplier_name']").parent().find('input:eq(0)').formValidation({validType:['trim','safeForHtml'],tipPosition:'left',required:true});
		$("select[name='supplier_name']").parent().find('input:eq(0)').css('width','85%');
		
		$('select#delivery_method').combobox();
		$('select#delivery_method').parent().find('input:eq(0)').formValidation({validType:['trim','safeForHtml'],tipPosition:'left',required:true});
		$('select#delivery_method').parent().find('input:eq(0)').css('width','85%');
		
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
	},
	'updatePurchaseOrder':function(purchase_order) {	//编辑采购单，点击 保存
		if (! $('#update-purchase-form').formValidation('form_validate')){
			return;
		}
		var info = $('#update-purchase-form').serialize();
		var url = global.baseUrl + 'purchase/purchase/update?id='+purchase_order;

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
									$('.create_or_edit_purchase_win').modal('hide');
									$('.create_or_edit_purchase_win').on('hidden.bs.modal', '.modal', function(event) {
										$(this).removeData('bs.modal');
									});
									$.showLoading();
									$('.create_or_edit_purchase_win').modal('hide');
									$('.create_or_edit_purchase_win').on('hidden.bs.modal', '.modal', function(event) {
										$(this).removeData('bs.modal');
									});
									window.location.reload();
									/*不刷新住页面，打开新view窗口
									$.get( global.baseUrl+'purchase/purchase/view?id='+purchase_order,
									   function (data){
											$.hideLoading();
											bootbox.dialog({
												closeButton: false,
												className: "create_or_edit_purchase_win", 
												message: data,
											});	
									});
									*/
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
/**
	'popOperationLogView':function() {			
	},
	// 查看或新增备注
	'memoViewOrSave':function() { 
	},
	
	'statusFormatter':function(value) {
		return purchaseOrder.updateorview.setting.statusMap[value]["label"];
	},
	'paymentStatusFormatter':function(value) {
		//alert("statusFormatter");
		return purchaseOrder.updateorview.setting.paymentStatusMap[value];
	},
	'warehouseFormatter':function(value) {
		//alert("statusFormatter");
		return purchaseOrder.updateorview.setting.warehouseIdNameMap[value];
	},
**/	
		
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
