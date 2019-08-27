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
inventory.purchase2stockin = {
	'init' : function() {
		$('#filter_arrived_purchase_order_btn').click(function(){
			inventory.purchase2stockin.filterArrivedPurchaseOrder();
		});

	},
	'filterArrivedPurchaseOrder' : function() {
		var status = 3;//hardcode
		var keyword = $("#filter_arrived_purchase_order_form input[name='keyword']").val();
		var warehouse_id = $("#filter_arrived_purchase_order_form select[name='warehouse_id']").val();
		$("#arrived_purchase_order_tb").queryAjaxPage({
			'status':status,
			'warehouse_id':warehouse_id,
			'keyword':keyword,
		});
	},
	'passPurchaseDataToStockInWin' : function(id){
		$.showLoading();
		$.ajax({
			url:global.baseUrl + 'inventory/inventory/arrived-purchase-info?id='+id,
			type:'get',
			dataType:'json',
			async : false,
			success:function(data) {
				$.hideLoading();
				if(data.message!==''){
					bootbox.alert( data.message );
					return false;
				}
				else{
					if(data.items.length <=0){
						bootbox.alert( Translator.t("采购单没有包含产品，不能选择") );
						return false;
					}
					$.showLoading();
					$('.arrived_pruchaseOrder_win').modal('hide');
					$('.arrived_pruchaseOrder_win').on('hidden.bs.modal', '.modal', function(event) {
						$(this).removeData('bs.modal');
					});
					$("#create_stockIn_data_form select[name='warehouse_id']").val(data.data.warehouse_id);
					$("#create_stockIn_data_form input[name='stock_change_id']").val(data.data.purchase_order_id+'_1_1');
					$("#create_stockIn_data_form input[name='purchase_order_id']").val(data.data.purchase_order_id);
					$("#stockIn_prodList_tb").empty();
					inventory.stockIn.reflashProdListTable(data.items);
					$("#create_stockIn_data_form input[name$='[stock_in_qty]']").attr('readonly','readonly');
					$("#disbundle_purchase_stockin").css('display','initial');
					$("#disbundle_purchase_stockin").removeAttr('disabled');
					$(".cancelStockInProd").each(function(){$(this).remove();});
					$("#select_prod_btn,#btn_stockIn_import_text,#btn_stockIn_import_excel").css('display','none').attr('disabled','disabled');
					$.hideLoading();
				}
			},
			error:function(){
				$.hideLoading();
				bootbox.alert( Translator.t("后台获取数据异常，请重试") );
				return false;
			},
		});
	},
};