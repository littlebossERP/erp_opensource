/**
 +------------------------------------------------------------------------------
 * 库存模块 品牌统计 brand.js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof brand === 'undefined')  brand = new Object();
brand={
		init: function() {
			
		},
		
		viewBrandInventoryDetail: function(brand_id, brand_name){
			$.showLoading();
			
			$.get(global.baseUrl+'report/inventory/get-brand-detail?brand_id='+brand_id+'&brand_name='+brand_name,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("品牌 \""+brand_name+"\" 的库存明细"),
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
			url = '/report/inventory/export-brand-excel';
//			url += '?'+$("form:first").serialize();
			window.open(url);
		},
}