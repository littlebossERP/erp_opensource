/**
 +------------------------------------------------------------------------------
 * 库存模块 多标签统计Tags.js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof tags === 'undefined')  tags = new Object();
tags={
		init: function() {
			
		},
		
		addQueryCountWin: function(){
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
					url: '/report/inventory/get-tags-inventory-data',
//					data: $('#form-CrmUser').serialize(),
					data: {tagIds:checkTagArray},
					success: function (result) {
						
						$("#table-report-tags").append("<tr><td nowrap>"+tab_nRow+"</td><td nowrap width='500px'>"+checkTagName.join(' + ')+"</td><td nowrap>"
								+result['sku']+"</td><td nowrap>"+result['stock']+"</td><td nowrap>"+result['brands']+"</td><td nowrap>"+result['stock_value']
								+"</td><td nowrap>"
								+'<button onclick="tags.delRow('+tab_nRow+')">删除此统计对象</button>'
								+'<button onclick="tags.viewTagsInventoryDetail('+tab_nRow+',\''+checkTagArray.join(',')+'\')" style=\'margin-left: 10px;\'>显示明细</button>'
								+"</td></tr>");
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
				$(this).find("td:eq(0)").each(function(){
//					alert($(this).text());
//					alert($(this).html());
					
					$(this).html(tab_nRow);
				});
				
				$(this).find("td:eq(6)").each(function(){
					var tmpTagArr=$(this).html().split("'");
					
					$(this).html('<button onclick="tags.delRow('+tab_nRow+')">删除此统计对象</button>'
							+'<button onclick="tags.viewTagsInventoryDetail('+tab_nRow+',\''+tmpTagArr[1]+'\')" style=\'margin-left: 10px;\'>显示明细</button>');
				});
				
				tab_nRow++;
			});
		},
		
		viewTagsInventoryDetail :function(index,tagIDs){
			var tagNames=$("#table-report-tags tr:eq("+(index)+") td:eq(1)").text();
			
			if(tagNames.length > 30){
				tagNames=tagNames.substring(0,30)+"...";
			}
			
			$.showLoading();
			
			$.get(global.baseUrl+"report/inventory/tags-inventory-detail?tag_id="+tagIDs+"&tag_name="+tagNames,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						className: "myClass", 
						title : ("标签 \""+tagNames+"\" 的库存明细"),
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
			$('#report-inventory-tags-get-excel-form').attr("action", global.baseUrl + 'report/inventory/export-tags-excel').submit();
		},
}