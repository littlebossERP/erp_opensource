/**
 +------------------------------------------------------------------------------
 * 销售商品统计 商品数量及价值worth.js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if(typeof report === 'undefined')
	var report = new Object();

if (typeof DateSelectWidgetObj === 'undefined')  DateSelectWidgetObj = new Object();
DateSelectWidgetObj = {
		init: function() {
		},
		
		selectAftClose : function(){
			var dateParams = report.getParams();
			
			if (worth.firstLoad==false){
				var site = $("#report-product-worth-select").val().split('-');
				
				$("#worth-list-table").queryAjaxPage({
					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
				});
			}
		},
}

if (typeof worth === 'undefined')  worth = new Object();
worth={
		firstLoad : true,
		init: function() {
			report.init(function(){
			});
			
			DateSelectWidgetObj.init(function(){
			});
			
			worth.firstLoad = false;
			
			
			$('#report-product-worth-select').change(function () {
				var dateParams = report.getParams();
				
				var site = this.value.split('-');
				
				$("#worth-list-table").queryAjaxPage({
					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
				});
			});
			
		},
		
		exportExcel : function(){
			var dateParams = report.getParams();
			var site = $("#report-product-worth-select").val().split('-');
			
			url = '/report/product/export-worth-excel?start='+dateParams['start']+'&end='+dateParams['end']
				+'&source='+site[0]+'&site='+site[1];
			window.open(url);
		},
}