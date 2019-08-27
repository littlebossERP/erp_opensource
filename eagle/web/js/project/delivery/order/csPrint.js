
var cs_webSocket;

var cs_print = {
	init:function(){
		//开启自动打印
		$('#automatic_print_enable').on('click',function()
		{
			var automatic_print_enable = 0;
			if( $(this).prop("checked"))
				automatic_print_enable = 1;
			
			$.ajax({
				url: global.baseUrl + "delivery/order/automatic-print-enable",
				data: {'automatic_print_enable':automatic_print_enable},
				type: 'post',
				dataType: 'json',
				success: function(response)
				{
					if(response['success'])
					{
						$.alertBox('<p class="text-success">设置成功</p>');
						if( automatic_print_enable){
							cs_print.openWebSocket();
						}
						else{
							
						}
						
						$('#code_input').select();
					}
					else
					{
						$.alertBox('<p class="text-warn">设置失败！</p>');
					}
						
				},
				error: function(XMLHttpRequest, textStatus) 
				{
					$.alertBox('<p class="text-warn">网络不稳定.请求失败,请重试！</p>');
				}
			});
		});
	},
	
	//开启webSocket
	openWebSocket : function(){
		cs_webSocket = cs_print.getWebSocket();
		
		// 打开Socket
		cs_webSocket.onopen = function(event) {
			// 监听消息
			cs_webSocket.onmessage = function(event) {
				var data = $.parseJSON(event.data);
				if(data.Success){
					//更新打印状态
					showscanninglistdistributionbox.editPrintStatus();
				}
			};
			// 监听Socket的关闭
			cs_webSocket.onclose = function(event) {
				console.log('Client notified socket has closed',event);
			};

			// 发送请求
			//webSocket.send(reqData);
		};
		
		// 错误，检测是否安装插件
		cs_webSocket.onerror = function(event){
		};
	},
	
	//打印面单
	printOrder : function(orderid){
		if(orderid == ''){
			bootbox.alert("请选择要操作的订单");
			return false;
		}
		
		$.showLoading();
		//生成pdf
		$.ajax({
			type: "GET",
			url: '/carrier/carrierprocess/external-doprint',
			data: {'is_generate_pdf': 1, 'order_ids': orderid},
			dataType: "json",
			//async : false,
			success: function (ret) {
			},
			error: function () {
			}
		});
		//获取面单对应的pdf url
		$.ajax({
			type: "POST",
			url: '/carrier/carrierprocess/get-print-url',
			data: {'order_id': orderid},
			dataType: "json",
			success: function (ret) {
				$.hideLoading();
				if(ret.success){
					cs_print.getWebSocket().send(JSON.stringify(ret));
				}
				else{
					showscanninglistdistributionbox.PrintErr();
					alert(ret.msg);
				}
				return false;
			},
			error: function () {
				$.hideLoading();
				showscanninglistdistributionbox.PrintErr();
				bootbox.alert('网络异常，请联系小老板客服!');
			}
		});
	},
	
	getWebSocket : function(){
		if(cs_webSocket == undefined || cs_webSocket == '' || cs_webSocket == null){
			cs_webSocket = new WebSocket('ws://localhost:13570');
		}

		if(cs_webSocket.readyState == 3){
			bootbox.alert('请确认服务端是否已打开，然后再重试!');
			//关闭连接
			cs_webSocket.close();
			//重新连接
			cs_webSocket = new WebSocket('ws://localhost:13570');
			
			$('#code_input2').focus();
			$('#code_input2').select();
		}

		return cs_webSocket;
	},
}