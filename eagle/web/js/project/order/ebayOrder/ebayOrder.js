ebayOrder = {
	/**************************   do action start  ****************************/
	doActionList:function(val , thisOrderList){
		switch(val){
			case 'orderverifypass':
				OrderCommon.setOrderVerifyPass(thisOrderList);
				break;
			case 'setSyncShipComplete':
				OrderCommon.setOrderSyncShipStatusComplete(thisOrderList);
				break;
			case 'cancelMergeOrder':
				OrderCommon.cancelMergeOrder(thisOrderList);
				break;
			case 'ExternalDoprint':
				OrderCommon.ExternalDoprint(thisOrderList);
				break;
			case 'changeItemDeclarationInfo':
				OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList);
				break;
			case 'stockManage':
				OrderCommon.showStockManageBox(thisOrderList);
				break;
			case 'addMemo':
				OrderCommon.showAddMemoBox(thisOrderList);
				break;
			case 'reorder':
				OrderCommon.reorder(thisOrderList);
				break;
			case 'changeWHSM':
				OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
				break;
				
			case 'editOrder':
				var url = '/order/ebay-order/edit?orderid=';
				$.each(thisOrderList , function(index,value){
					OrderCommon.editOrder(value,'');
					return;
					//window.open(url+value);
				});
				break;
			case 'history':
				var url = '/order/logshow/list?orderid=';
				
				$.each(thisOrderList , function(index,value){
					window.open(url+value);
				});
				break;
			case 'skipMerge':
				$.post('/order/order/skipmerge',{orders:thisOrderList},function(result){
					bootbox.alert({  
						buttons: {  
							ok: {  
								 label: '确认',  
								 className: 'btn-myStyle'  
							 }  
						 },  
						 message: result,  
						 callback: function() {  
							 location.reload();
						 },  
					 });
					
				});
				break;
			case 'signcomplete':
				
				//遮罩
				$.maskLayer(true);
				$.ajax({
					url: "/order/order/signcomplete",
					data: {
						orderids:thisOrderList,
					},
					type: 'post',
					success: function(response) {
						 var r = $.parseJSON(response);
						 if(r.result){
							 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
						 }else{
							 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
						 } 
						 event.then(function(){
						  // 确定,刷新页面
						  location.reload();
						},function(){
						  // 取消，关闭遮罩
						  $.maskLayer(false);
						});
					},
					error: function(XMLHttpRequest, textStatus) {
						$.maskLayer(false);
						$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
					}
				})
				break;
			case 'outOfStock':
				$.post('/order/order/outofstock',{orders:thisOrderList},function(result){
					bootbox.alert({  
						buttons: {  
							ok: {  
								 label: '确认',  
								 className: 'btn-myStyle'  
							 }  
						 },  
						 message: result,  
						 callback: function() {  
							 location.reload();
						 },  
					 });
					
				});
				break;
			case 'suspendDelivery':
				$.post('/order/order/suspenddelivery',{orders:thisOrderList},function(result){
					bootbox.alert({  
						buttons: {  
							ok: {  
								 label: '确认',  
								 className: 'btn-myStyle'  
							 }  
						 },  
						 message: result,  
						 callback: function() {  
							 location.reload();
						 },  
					 });
					
				});
				break;
			case 'cancelorder':
				$.post('/order/order/cancelorder',{orders:thisOrderList},function(result){
					bootbox.alert(result);
					location.reload();
				});
				break;
			case 'checkorder':
				var idstr = '';
				$.each(thisOrderList,function(index,value){
					idstr+=','+value;
				});
				
				$.post('/order/order/checkorderstatus',{orders:idstr },function(result){
					bootbox.alert(result);
					location.reload();
				});
				break;
			case 'signshipped':
				OrderCommon.signOrdership(thisOrderList);
				break;
			case 'signwaitsend':
				var idstr = '';
				$.each(thisOrderList,function(index,value){
					idstr+=','+value;
				});
				OrderCommon.shipOrderOMS(idstr);
				break;
			
			case 'mergeorder':
				OrderCommon.mergeOrder(thisOrderList);
				
				/*
				document.a.target="_blank";
				document.a.action='/order/order/mergeorder';
				document.a.submit();
				document.a.action="";
				*/
				break;
			case 'givefeedback':
				
				ebayOrder.givefeedback(thisOrderList);
				break;
			case 'changeshipmethod':
				var html  = '以下未发货订单已经被选中<br>'+idstr +'<br><br>请选择批量使用物流运输方式 ：<select name="change_shipping_method_code">'+$('select[name=demo_shipping_method_code]').html()+'</select>';
				bootbox.dialog({
					title: Translator.t("订单详细"),
					className: "order_info", 
					message: html,
					buttons:{
						Ok: {  
							label: Translator.t("确定"),  
							className: "btn-primary",  
							callback: function () { 
								return changeShipMethod(thisOrderList , $('select[name=change_shipping_method_code]').val());
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
				
				break;
				
			case 'dispute':
				ebayOrder.dispute(thisOrderList);
				break;
			case 'signpayed':
				idstr='';
				$.each(thisOrderList,function(index,value){
					idstr+=','+value;
				});
				$.post('/order/ebay-order/signpayed',{orders:idstr},function(result){
					bootbox.alert(result);
					location.reload();
				});
				break;
			default:
				return false;
				break;
		}
		
	},
	/**************************   do action end  ****************************/
	
	doaction:function(val){
		var thisOrderList =[];
		switch(val){
			case 'changeWHSM':
				var thisOrderList =[];
				
				$('input[name="order_id[]"]:checked').each(function(){
					
					if ($(this).parents("tr:contains('已付款')").length == 0) return;
					
					thisOrderList.push($(this).val());
					
				});
				break;
			case 'changeshipmethod':
				var thisOrderList =[];
				
				$('input[name="order_id[]"]:checked').each(function(){
					
					if ($(this).parents("tr:contains('已付款')").length == 0) return;
					
					thisOrderList.push($(this).val());
					
				});
				break;
			default:
				$('input[name="order_id[]"]:checked').each(function(){
					thisOrderList.push($(this).val());
				});
			break;
		}
		
		if (thisOrderList.length ==0) {
			bootbox.alert(Translator.t('请选择订单'));
			return;
		}
		
		ebayOrder.doActionList(val,thisOrderList);
	},
	
	doaction2:function(obj){
		var val = $(obj).val();
		if (val != ''){
			ebayOrder.doaction(val);
			$(obj).val('');
		}
	},
	
	
	doactionone:function(val,orderid){
		var thisOrderList = [];
		thisOrderList.push(orderid);
		
		if (thisOrderList.length ==0) {
			bootbox.alert(Translator.t('请选择订单'));
			return;
		}
		
		ebayOrder.doActionList(val,thisOrderList);
	},
	
	doactionone2:function(obj,orderid){
		var val = $(obj).val();
		if (val != ''){
			ebayOrder.doactionone(val,orderid);
			$(obj).val('');
		}
	},
	
	exportorder:function (type){
		var thisOrderList =[];
		$('input[name="order_id[]"]:checked').each(function(){
			thisOrderList.push($(this).val());
		});
		
		if (thisOrderList.length ==0) {
			bootbox.alert(Translator.t('请选择订单'));
			return;
		}
		
		OrderCommon.exportorder2(type,thisOrderList);
	},
	
	OMSLeftMenuAutoLoad:function(){
		var selleruserid = $("#selleruserid_combined").val();
		if (selleruserid =='') selleruserid = 'all';
		$.ajax({
			url: "/order/ebay-order/left-menu-auto-load?selleruserid="+selleruserid,
//			data: {selleruserid:selleruserid},
			type: 'post',
			dataType:"json",
			success: function(result) {
				var tmpLabelList = {'100':'未付款','200':'已付款','300':'发货中','601':'暂停发货','602':'缺货'};
				OrderCommon.resetMenuTabbar(tmpLabelList,result);
				if ($('.nav.nav-pills').length>0){
					var tmpLabelList = {'200':'已付款','reorder':'重新发货','223':'待合并','ship':'可发货','merged':'已合并','split':'已拆分'};
					OrderCommon.resetTopMenuTabbar(tmpLabelList,result);
				}
				
			},
			error: function() {
			}
		});
	},
	
	sendmessage:function(orderid){
		var Url='/order/ebay-order/sendmessage';
		
		/**/
		$.ajax({
			type : 'post',
			cache : 'false',
			data : {orderid : orderid},
			url: Url,
			success:function(response) {
				$('#myMessage .modal-content').html(response);
				$('#myMessage').modal('show');
				
			}
		});
		
	},
	
	OmsViewTracker:function(obj,num,invoker){
		OrderCommon.OmsViewTracker(obj,num,'Ebay-Oms');
	},
	
	showDashBoard:function (autoShow){
		$.showLoading();
		$.ajax({
			type: "GET",
			url:'/order/ebay-order/user-dash-board?autoShow='+autoShow, 
			success: function (result) {
				$.hideLoading();
				$("#dash-board-enter").toggle();
				bootbox.dialog({
					className : "dash-board",
					title: Translator.t('ebay订单监控面板'),
					message: result,
					closeButton:false,
					buttons:{
						
						Cancel: {  
							label: Translator.t("收起"),  
							className: "btn-default",  
							callback: function () {  
								hideDashBoard();
							}
						}, 
					}
				});
				return true;
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('打开ebay订单监测面板失败'));
				return false;
			}
		});
	},

	requestGenerateDashBoardData:function (){
		if ($('#dash-board-enter').css('display') != 'none'){
			$('#dash-board-enter').toggle(1000);
		}
		
		$.ajax({
			type: "GET",
			url:'/order/ebay-order/genrate-user-dash-board', 
			success: function (result) {
				if ($('#dash-board-enter').css('display') == 'none'){
					$('#dash-board-enter').toggle(1000);
				}
				return true;
			},
			error :function () {
				return false;
			}
		});
		
	},
	hideDashBoard:function (){
		$("#dash-board-enter").toggle();
		var dash_board_top = $("#dash-board-enter").offset().top;
		var  dash_board_height= $("#dash-board-enter").height();
		if(typeof(dash_board_height)=='undefined')
			dash_board_height = 0;
		if(typeof(dash_board_top)=='undefined')
			var dash_board_top = 800;
		else 
			top = dash_board_top + dash_board_height/2;
	},
	
	/******************** feed back start    ***********************/
	givefeedback:function(orderIdList){
		$.showLoading();
		$.post('/order/ebay-order/feedback',{order_id:orderIdList},
		   function (data){
				$.hideLoading();
				bootbox.dialog({
					title: Translator.t("买家评价"),
					className: "order_info", 
					message: data,
					
				});	
		});
	},
	/******************** feed back  end    ***********************/
	
	/**************************  dispute start  *****************************/
	dispute:function(thisOrderList){
		var url = '/order/ebay-order/dispute';
		var tempForm = document.createElement("form"); 
		tempForm.id="tempForm1"; 
		tempForm.method="post"; 
		tempForm.target="_blank";
		tempForm.action=url; 
		
		$.each(thisOrderList,function(index,value){
			var hideInput = document.createElement("input"); 
			hideInput.type="hidden"; 
			hideInput.name= "order_id[]" 
			hideInput.value= value; 
			tempForm.appendChild(hideInput); 
		});
		
		document.body.appendChild(tempForm); 
		tempForm.submit(); 
		document.body.removeChild(tempForm); 
	},
	/**************************  dispute end  *****************************/
	
	
	//手动同步订单
	dosyncorder:function (){
		var Url='/order/ebay-order/syncmt';
		$.ajax({
			type : 'get',
			cache : 'false',
			data : {},
			url: Url,
			success:function(response) {
				$('#syncorderModal .modal-content').html(response);
				$('#syncorderModal').modal('show');
			}
		});
	},
	
	dosearch:function(name,val){
		$('#'+name).val(val);
		$('form:first').submit();
	},
	
	
	/*******************************   update ebay item start  ********************************/
	retrieveEbayOrderItem:function (){
		$.ajax({
			type: "GET",
			url:'/order/ebay-order/retrieve-order-item', 
			success: function (result) {
				if (result.indexOf('ack=success')>0){
					bootbox.alert(result.substring(result.indexOf('ack=success')+12),function(){
						location.reload();
					});
				}
				
				if (result.indexOf('ack=fail')>0){
					bootbox.alert(result.substring(result.indexOf('ack=fail')+9),function(){
						location.reload();
					});
				}
				
				return true;
			},
			error :function () {
				return false;
			}
		});
		
	},
	/*******************************   update ebay item end  ********************************/
}