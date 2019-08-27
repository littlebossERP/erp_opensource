/**
 +------------------------------------------------------------------------------
 * 销售商品统计 多标签统计tags.js
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
//			var dateParams = report.getParams();
//			
//			if (tag.firstLoad==false){
//				var site = $("#report-product-tag-select").val().split('-');
//				
//				$("#tag-list-table").queryAjaxPage({
//					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
//				});
//			}
		},
}


if (typeof tags === 'undefined')  tags = new Object();
tags={
		firstLoad : true,
		init: function() {
			report.init(function(){
			});
			
			DateSelectWidgetObj.init(function(){
			});
			
			tags.firstLoad = false;
			
			$('#report-product-tag-select').change(function () {
//				var dateParams = report.getParams();
//				
//				var site = this.value.split('-');
//				
//				$("#tag-list-table").queryAjaxPage({
//					'source':site[0],'site':site[1],'start':dateParams['start'],'end':dateParams['end']
//				});
			});
			
		},
		
		addQueryCountWin : function(){
			$.showLoading();
			
			$.get(global.baseUrl+'report/inventory/tags-add-query-win',
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("添加统计对象"),
						buttons: {  
							Cancel: {  
		                        label: ("返回"),
		                        className: "btn-default",  
		                        callback: function () {  
		                        }
		                    }, 
		                    OK: {  
		                        label: ("添加"),  
		                        className: "btn-primary",  
		                        callback: function () {
		                        	
		                        	tags.addTagObjects();
		                        	
//		                        	return false;
		                        }  
		                    }, 
						},
					    message: data,
					});	
			});
		},
		
		addTagObjects :function(){
			var checkTagArray = new Array();
			var checkTagName = new Array();
			
			var dateParams = report.getParams();
			var site = $("#report-product-tag-select").val().split('-');
			
			$(".class-tagsList").each(function(){
				if($(this).is(':checked')==true){
					tmpArr = $(this).val().split(',');
					
					checkTagArray.push(tmpArr[0]);
					checkTagName.push(tmpArr[1]);
				}
			});
			
			if(checkTagArray.length > 1) {
				var tab_nRow = $("#table-report-tags tr").length;
				
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/report/product/get-tags-sales-data',
					data: {tagIds:checkTagArray,start:dateParams['start'],end:dateParams['end'],
						source:site[0],site:site[1]},
					success: function (result) {
						
						$("#table-report-tags").append("<tr><td nowrap>"+tab_nRow+"</td><td nowrap width='500px'>"+checkTagName.join(' + ')+"</td><td nowrap>"
								+result['skuSoldCount']+"</td><td nowrap>"+result['volume']+"</td><td nowrap>"+result['brands']+"</td><td nowrap>"+result['prices']
								+"</td><td nowrap>"
								+'<button onclick="tags.delRow('+tab_nRow+')">删除此统计对象</button>'
								+'<button onclick="tags.viewTagsSaleDetail('+tab_nRow+',\''+checkTagArray.join(',')+'\')" style=\'margin-left: 10px;\'>显示明细</button>'
								+"</td></tr>");
						
						$("#table-report-tags").data("trStart"+tab_nRow, dateParams['start']);
						$("#table-report-tags").data("trEnd"+tab_nRow, dateParams['end']);
						$("#table-report-tags").data("trSource"+tab_nRow, site[0]);
						$("#table-report-tags").data("trSite"+tab_nRow, site[1]);
						$("#table-report-tags").data("trTitle"+tab_nRow, $('#date strong')[0].innerHTML);
					},
				});
				
			}else{
				bootbox.alert("选择标签个数必须大于1！");
			}
		},
		
		delRow :function(index){
			$("#table-report-tags tr:eq("+(index)+")").remove();
			
			var tab_nRow = 0;
			
			$("#table-report-tags").find("tr").each(function(){
				
				if (index <= tab_nRow){
					$(this).find("td:eq(0)").each(function(){
						
						$(this).html(tab_nRow);
					});
					
					$(this).find("td:eq(6)").each(function(){
						var tmpTagArr=$(this).html().split("'");
						
						$(this).html('<button onclick="tags.delRow('+tab_nRow+')">删除此统计对象</button>'
								+'<button onclick="tags.viewTagsSaleDetail('+tab_nRow+',\''+tmpTagArr[1]+'\')" style=\'margin-left: 10px;\'>显示明细</button>');
					});
					
					var tab_nRowTmp = tab_nRow+1;
					
					$("#table-report-tags").data("trStart"+tab_nRow, $("#table-report-tags").data("trStart"+tab_nRowTmp ));
					$("#table-report-tags").data("trEnd"+tab_nRow, $("#table-report-tags").data("trEnd"+tab_nRowTmp ));
					$("#table-report-tags").data("trSource"+tab_nRow, $("#table-report-tags").data("trSource"+tab_nRowTmp ));
					$("#table-report-tags").data("trSite"+tab_nRow, $("#table-report-tags").data("trSite"+tab_nRowTmp ));
					$("#table-report-tags").data("trTitle"+tab_nRow, $("#table-report-tags").data("trTitle"+tab_nRowTmp ));
				}
				
				tab_nRow++;
			});
		},
		
		viewTagsSaleDetail :function(index,tagIDs){
			var dateParams = report.getParams();
			var site = $("#report-product-tag-select").val().split('-');
			
			dateParams['start'] = $("#table-report-tags").data("trStart"+index);
			dateParams['end'] = $("#table-report-tags").data("trEnd"+index);
			site[0] = $("#table-report-tags").data("trSource"+index);
			site[1] = $("#table-report-tags").data("trSite"+index);
			
			var date = $('#date strong')[0].innerHTML;
			
			date = $("#table-report-tags").data("trTitle"+index);
			
			var source = '';
			var sitename = '';
			
			if($("#report-product-tag-select").val() == "0-0"){
				source = "于 所有销售店";
			}else{
				source = '于销售店 '+site[0];
				sitename = '-'+site[1];
			}
			
			var tagNames=$("#table-report-tags tr:eq("+(index)+") td:eq(1)").text();
			
			if(tagNames.length > 30){
				tagNames=tagNames.substring(0,30)+"...";
			}
			
			$.showLoading();
			
			$.get(global.baseUrl+"report/product/tags-sale-detail?tag_id="+tagIDs+"&tag_name="+tagNames
					+'&start='+dateParams['start']+'&end='+dateParams['end']
					+'&source='+site[0]+'&site='+site[1],
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("多标签 \""+tagNames+"\" "+ date+' '+source+site+' 的销售明细'),
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
		
		exportExcel :function(){
			var TableTagArray = new Array();
			
			$("#table-report-tags").find("tr").each(function(){
				TableTagArray.push(new Array($(this).find("td:eq(0)").text(),
						$(this).find("td:eq(1)").text(),$(this).find("td:eq(2)").text(),
						$(this).find("td:eq(3)").text(),$(this).find("td:eq(4)").text(),
						$(this).find("td:eq(5)").text()));
			});
			
			$('#report-inventory-tags-get-excel-form :hidden').val(JSON.stringify(TableTagArray));
			$('#report-inventory-tags-get-excel-form').attr("action", global.baseUrl + 'report/product/export-tags-excel').submit();
		},
		
}
