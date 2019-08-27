/**
 +------------------------------------------------------------------------------
 * 销售商品统计 标签统计tag.js
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
//			alert(dateParams['start']+" "+dateParams['end']);
			
			if (tag.firstLoad==false){
				var site = $("#report-product-tag-select").val().split('-');
				
				$("#tag-list-table").queryAjaxPage({
					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
				});
			}
		},
}


if (typeof tag === 'undefined')  tag = new Object();
tag={
		firstLoad : true,
		init: function() {
			report.init(function(){
			});
			
			DateSelectWidgetObj.init(function(){
			});
			
			tag.firstLoad = false;
			
			$("#btn-test").click(function(){
//				var dateHtml = $('#date strong')[0].innerHTML;
//				alert(dateHtml);
				
				var dateParams = report.getParams();
				alert(dateParams['start']+" "+dateParams['end']);
			});
			
			
			$('#report-product-tag-select').change(function () {
				var dateParams = report.getParams();
				
				var site = this.value.split('-');
				
				$("#tag-list-table").queryAjaxPage({
					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
				});
			});
			
		},
		
		viewTagSaleDetail : function(tag_id,tag_name){
			var dateParams = report.getParams();
			var site = $("#report-product-tag-select").val().split('-');
			
			var date = $('#date strong')[0].innerHTML;
			
			var source = '';
			var sitename = '';
			
			if($("#report-product-tag-select").val() == "0-0"){
				source = "于 所有销售店";
			}else{
				source = '于销售店 '+site[0];
				sitename = '-'+site[1];
			}
			
			$.showLoading();
			
			$.get(global.baseUrl+'report/product/get-tag-sale-detail?tag_id='+tag_id
					+'&tag_name='+tag_name+'&start='+dateParams['start']+'&end='+dateParams['end']
					+'&source='+site[0]+'&site='+site[1],
//					{'tag_id':tag_id,'tag_name':tag_name,'start':dateParams['start'],'end':dateParams['end'],'source':site[0],'site':site[1]},
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("标签 \""+tag_name+"\" "+date+' '+source+sitename+" 的销售明细"),
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
			var site = $("#report-product-tag-select").val().split('-');
			
			url = '/report/product/export-tag-excel?start='+dateParams['start']+'&end='+dateParams['end']
				+'&source='+site[0]+'&site='+site[1];
//			url += '?'+$("form:first").serialize();
			window.open(url);
		},
		
}
