var webSocket;
//备注：webSocket 是全局对象，不要每次发送请求丢去创建一个，做到webSocket对象重用，和打印组件保持长连接。
var doConnect = function(req) {
	webSocket = getWebSocket();
	//var reqData = JSON.stringify(req);
	//console.log(reqData);
	// 打开Socket
	webSocket.onopen = function(event) {
		// 监听消息
		webSocket.onmessage = function(event) {
			var data = $.parseJSON(event.data);
			if(data.cmd == 'getPrinters'){
				// 打印列表
				cainiaoPrinters(event);

			} else if(data.cmd == 'print'){
				// 打印（是否要预览）
				previewCainiaoUrl(data.previewURL);

			}
		};
		// 监听Socket的关闭
		webSocket.onclose = function(event) {
			console.log('Client notified socket has closed',event);
		};
		// 发送请求
		//webSocket.send(reqData);
	};

	// 错误，检测是否安装插件
	webSocket.onerror = function(event){
	};
};

var getWebSocket = function(){
	if(webSocket == undefined || webSocket == '' || webSocket == null){
		if(document.location.protocol == "https:"){//如果是https的话，端口是13529
			webSocket = new WebSocket('wss://localhost:13529');
		}else{
		webSocket = new WebSocket('ws://localhost:13528');
		}
	}
	return webSocket;
};

/***
 *
 * 获取请求的UUID，指定长度和进制,如
 * getUUID(8, 2)   //"01001010" 8 character (base=2)
 * getUUID(8, 10) // "47473046" 8 character ID (base=10)
 * getUUID(8, 16) // "098F4D35"。 8 character ID (base=16)
 *
 */
var getUUID = function(len, radix) {
	var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
	var uuid = [], i;
	radix = radix || chars.length;
	if (len) {
		for (i = 0; i < len; i++) uuid[i] = chars[0 | Math.random()*radix];
	} else {
		var r;
		uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
		uuid[14] = '4';
		for (i = 0; i < 36; i++) {
			if (!uuid[i]) {
				r = 0 | Math.random()*16;
				uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
			}
		}
	}
	return uuid.join('');
};

/***
 * 构造request对象
 */
var getRequestObject = function(cmd) {
	var request = {};
	request.requestID = getUUID(8, 16);
	request.version ="1.0";
	request.cmd = cmd;
	return request;
};

/**
 * 请求打印机列表
 */
var showCainiaoPrinters = function(){
	var req = getRequestObject("getPrinters");
	getWebSocket().send(JSON.stringify(req));
};

/** 菜鸟打印机列表 **/
var cainiaoPrinters = function(event){
	var responData = $.parseJSON(event.data);
	var defaultPrinter = responData.defaultPrinter;   // 默认打印机
	var printers       = responData.printers;         // 打印机列表
	$("#cainiaoPrinterSelect").html("");
	for(var i=0; i< printers.length; i++){
		var optionStr = "<option value='"+printers[i].name+"'>"+printers[i].name+"</option>";
		$("#cainiaoPrinterSelect").append(optionStr);
	}
	$("#cainiaoPrinterSelect").val(defaultPrinter);
	$("#cainiaoPrinterModal").modal("show");
};

/***
 * 获取电子面单Json 数据
 * waybillNO 电子面单号
 */
var getWaybillJson = function(orderIds){
	//获取waybill对应的json object
	var documents = new Array();
	$.ajax({
		type: "POST",
		url: '/carrier/carrierprocess/get-cloud-print-data',
		data: {"orderIds":orderIds},
		dataType: "json",
		async:false,
		success: function (data) {
			if(data.code == 0){
				bootbox.alert(data.msg);
			}else{
				var successList = data.data;
				for(i=0; i<successList.length; i++) {
					var doc = {};
					doc.documentID = successList[i].orderCode + "_" + i;
					
					var content = new Array();
					var waybillJson = successList[i].printData;
					
//					var waybillJson = $.parseJSON(successList[i].printData);
					content.push(waybillJson);
					doc.contents = content;
					documents.push(doc);
				}
			}
		},
		error: function () {
			bootbox.alert('网络异常，请联系小老板客服!');
		}
	});
	return documents;
};

/**
 * 打印电子面单
 * printer 指定要使用那台打印机
 */
var doCainiaoPrint = function(preview, orderIds) {
	var printer = $("#cainiaoPrinterSelect").val();
	
	if (orderIds == "") {
		$.fn.message({type: "error", msg: "请至少选择一个订单"});
		return;
	}

	// 执行菜鸟打印
	executeCainiaoPrint(preview, printer, orderIds);
};

/** 执行菜鸟打印 **/
var executeCainiaoPrint = function(preview, printer, orderIds){
	// 获取打印对象
	var req = getRequestObject("print");
	req.task = {};
	req.task.taskID = getUUID(8,10);
	req.task.preview = preview == 1 ? true : false;
	req.task.printer = printer;
	// 获取打印数据
	req.task.documents = getWaybillJson(orderIds);
	
	if(req.task.documents.length > 0){
		getWebSocket().send(JSON.stringify(req));
	}
};

/** 检测是否安装打印组件 **/
var checkCainiaoPlug = function(){
	webSocket = getWebSocket();
	if(webSocket.readyState == 3){
		// 检测是否启用或者安装菜鸟插件
		bootbox.alert('您尚未安装或未启用菜鸟打印组件 <a target="_blank" href="http://help.littleboss.com/word_list_96_505.html">安装说明</a>');
	}
};

/** 是否预览菜鸟打印pdf **/
var previewCainiaoUrl = function(url){
	if(url != undefined && url != ''){
		var wo = window.open('about:blank', '_blank');
		wo.location.href = url;
	}
};