/**
 +------------------------------------------------------------------------------
 * 库存模块 标签统计Tag.js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		inventory
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof tag === 'undefined')  tag = new Object();
tag={
		init: function() {
			
		},
		
		viewTagInventoryDetail : function(tag_id, tag_name){
			$.showLoading();
			
			$.get(global.baseUrl+'report/inventory/get-tag-detail?tag_id='+tag_id+"&tag_name="+tag_name,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("标签 \""+tag_name+"\" 的库存明细"),
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
			url = '/report/inventory/export-tag-excel';
//			url += '?'+$("form:first").serialize();
			window.open(url);
		},
}