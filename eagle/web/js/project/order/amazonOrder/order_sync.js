/**
 * [OrderSync amazon订单同步界面js]
 * @author		willage
 * @DateTime 	2016-06-28T14:52:45+0800
 * @type 		
 * @version 	1.0
 */
if (typeof OrderSync === 'undefined')  OrderSync = new Object();
OrderSync = {
	ShowSearchBox:function(account_key){
		var  html = Translator.t("订单号")+"：<input type='text' name='search_order_id' value/> <div id='searchbox_detail'></div>";
		bootbox.dialog({
			title: Translator.t("查询订单同步详细"),
			className: "large-box", 
			message: html,
			buttons:{
				ok: {  
					label: Translator.t("查询"),  
					className: "btn-default",  
					callback: function () {  
						if ($("input[name=search_order_id]").val() == '' ){
							bootbox.alert(Translator.t('请输入订单号'));
						}else{
							OrderSync.refreshOrderResult($("input[name=search_order_id]").val(),account_key);
						}
							
						
						return false;
						
					}
				},
				Cancel: {  
					label: Translator.t("返回"),  
					className: "btn-default",  
					callback: function () {  
					}
				}, 
			}
		});	
	},
	
	refreshOrderResult:function(order_id , account_key){
		$.ajax({
			type: "GET",
			dataType: 'json',
			url:'/order/aliexpressorder/get-one-order-sync-result', 
			data: {order_id : order_id , account_key:account_key},
			success: function (result) {
				if (result.result){
					var htmlStr = "<table class='table'><thead><tr>"
					+"<th>"+Translator.t('账号')+"</th>"
					+"<th>"+Translator.t('订单号')+"</th>"
					+"<th>"+Translator.t('同步状态（失败次数）')+"</th>"
					+"<th>"+Translator.t('订单状态')+"</th>"
					+"<th>"+Translator.t('订单产生时间')+"</th>"
					+"<th>"+Translator.t('最近同步时间')+"</th>"
					+"<th>"+Translator.t('错误信息')+"</th>"
					+"<th>"+Translator.t('操作')+"</th>"
					+"</tr></thead><tbody>";
					
					
					for(var i=0; i <result.result.result.length;i++){
						htmlStr += "<tr>"
						+"<td>"+result.result.result[i].sellerloginid+"</td>"
						+"<td>"+result.result.result[i].orderid+"</td>"
						+"<td>"+result.result.result[i].status+"("+result.result.result[i].times+")"+"</td>"
						+"<td>"+result.result.result[i].order_status+"</td>"
						+"<td>"+result.result.result[i].create_time+"</td>"
						+"<td>"+result.result.result[i].last_time+"</td>"
						+"<td>"+result.result.result[i].message+"</td>"
						+"<td>"+"</td>"
						+"</tr>";
					}
					
					htmlStr += "</tbody></table>";
					
					$('#searchbox_detail').html(htmlStr);
					
				}
				return true;
			},
			error :function () {
				return false;
			}
		});
	},
	
	
	
}