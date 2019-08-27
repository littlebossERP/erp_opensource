/**
 +------------------------------------------------------------------------------
 * 库存模块 商品数量及价值 worth.js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof worth === 'undefined')  worth = new Object();
worth={
		init: function() {
			
			$('#report-inventory-worth-warehouse-select').change(function () {
//				$('#form-select-worth-warehouse').attr("action", global.baseUrl + 'report/inventory/worth').submit();
				
				var wh_id = $(this).val();
				$("#worth-list-table").queryAjaxPage({
					'wh_id':wh_id,
				});
				
				
			});
		},
		
		exportExcel: function() {
//			url = '/report/inventory/export-worth-excel';
////			url += '?'+$("form:first").serialize();
//			window.open(url);
			$('#form-select-worth-warehouse').attr("action", global.baseUrl + 'report/inventory/export-worth-excel').submit();
		},
}