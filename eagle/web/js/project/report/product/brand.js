/**
 +------------------------------------------------------------------------------
 * 销售商品统计 品牌统计brand.js
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
			
			if (brand.firstLoad==false){
				var site = $("#report-product-brand-select").val().split('-');
				
				$("#brand-list-table").queryAjaxPage({
					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
				});
			}
		},
}

if (typeof brand === 'undefined')  brand = new Object();
brand={
		firstLoad : true,
		init: function() {
			report.init(function(){
			});
			
			DateSelectWidgetObj.init(function(){
			});
			
			brand.firstLoad = false;
			
			$('#report-product-brand-select').change(function () {
				var dateParams = report.getParams();
				
				var site = this.value.split('-');
				
				$("#brand-list-table").queryAjaxPage({
					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
				});
			});
			
		},
		
		viewBrandSaleDetail : function(brand_id,brand_name){
			var dateParams = report.getParams();
			var site = $("#report-product-brand-select").val().split('-');
			
			var date = $('#date strong')[0].innerHTML;
			
			var source = '';
			var sitename = '';
			
			if($("#report-product-brand-select").val() == "0-0"){
				source = "于 所有销售店";
			}else{
				source = '于销售店 '+site[0];
				sitename = '-'+site[1];
			}
			
			$.showLoading();
			
			$.get(global.baseUrl+'report/product/get-brand-sale-detail?brand_id='+brand_id
					+'&brand_name='+brand_name+'&start='+dateParams['start']+'&end='+dateParams['end']
					+'&source='+site[0]+'&site='+site[1],
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("品牌 \""+brand_name+"\" "+date+' '+source+sitename+" 的销售明细"),
						buttons: {  
							Cancel: {  
		                        label: ("返回"),
		                        className: "btn-default",  
		                        callback: function () {  
		                        }
		                    }, 
						},
					    message: data,
					});	
			});
		},
		
		
		exportExcel : function(){
			var dateParams = report.getParams();
			var site = $("#report-product-brand-select").val().split('-');
			
			url = '/report/product/export-brand-excel?start='+dateParams['start']+'&end='+dateParams['end']
				+'&source='+site[0]+'&site='+site[1];
			window.open(url);
		},
		
}