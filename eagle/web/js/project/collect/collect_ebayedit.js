//模板格式转换(图转标)
var transition = function(obj){
	if (obj == '' ||obj == undefined)
	{
		obj = '#cacheDiv';
	}
	var str = $('#itemdescription').val();
	
	var reg=/<script[^>]*>(.|\n)*?(?=<\/script>)<\/script>/gi;     
	str = str.replace(reg,'');
	
	$(obj).empty();
	$(obj).html(str);
	//smt模板
//	$(obj).find('.noImg').each(function(){
//		var newStr = unescape($(this).attr('data-kse'));
//		$(this).replaceWith(newStr);
//	});
//	//自定义模板
//	$(obj).find('.dxmImg').each(function(){
//		var dxmStr = unescape($(this).attr('data-kse'));
//		$(this).replaceWith(dxmStr);
//	})
	str = $(obj).html();
	
	return str;
};

var unTransitionHtml = function(str){
	var obj = '#cacheDiv';
	$(obj).html(str);
	//smt模板
	$(obj).find('[data-widget-type="relatedProduct"]').each(function(){
	//得到对象后，把属性拿到，拼个string再写到img里，替换原来的对象
		var type = $(this).attr('type'),
			newStr = $(this).prop('outerHTML'),
			custom = "http://style.aliexpress.com/js/5v/lib/kseditor/plugins/widget/images/widget1.png?t=AEO9LPV",
			relation = "http://style.aliexpress.com/js/5v/lib/kseditor/plugins/widget/images/widget2.png?t=AEO9LPV";
		if(type == 'relation'){
			custom = relation;
		} 
		newStr = '<img class="noImg" data-kse="'+escape(newStr)+'" src="'+custom+'">';
		$(this).replaceWith(newStr);
	});
	$(obj).find('[data-widget-type="customText"]').each(function(){
		//得到对象后，把属性拿到，拼个string再写到img里，替换原来的对象
			var type = $(this).attr('type'),
				newStr = $(this).prop('outerHTML'),
				custom = "http://style.aliexpress.com/js/5v/lib/kseditor/plugins/widget/images/widget1.png?t=AEO9LPV",
				relation = "http://style.aliexpress.com/js/5v/lib/kseditor/plugins/widget/images/widget2.png?t=AEO9LPV";
			if(type == 'relation'){
				custom = relation;
			} 
			newStr = '<img class="noImg" data-kse="'+escape(newStr)+'" src="'+custom+'">';
			$(this).replaceWith(newStr);
		});
	//自定义模板
	$(obj).find('[dxm-data-widget-type="dxmRelatedProduct"]').each(function(){
	//得到对象后，把属性拿到，拼个string再写到img里，替换原来的对象
		var moduleType = $(this).attr('type');
		var dxmStr = $(this).prop('outerHTML');
		var custom = "http://www.dianxiaomi.com/static/img/moban_custom.png";
		var relation = "http://www.dianxiaomi.com/static/img/moban.png";
		if(moduleType == 1){
			custom = relation;
		}
		var newStr = '<img class="noImg" data-kse="'+escape(dxmStr)+'" src="'+custom+'"/>';
		$(this).replaceWith(newStr);
	});
	str = $(obj).html();
	return str;
};

var setDetailTransate = function(s){
	var now = s;
//	baiDuTranslate(s, function(data){
//		if(data != ""){
//			detailT = detailT.replace(now,data);
//			setKindeDetail(detailT);
//		}
//	});
	$.post(global.baseUrl+"collect/collect/translate",{str:s},function(r){
		res = eval("("+r+")");
		if(res.response.code == 0){
			detailT = detailT.replace(now,res.response.data);
			setKindeDetail(detailT);
		}
	});
};

var setKindeDetail = function(detail){
	var reg=/<script[^>]*>(.|\n)*?(?=<\/script>)<\/script>/gi;     
    detail = detail.replace(reg,'').replace(/<script.*?>.*?<\/script>/g,'').replace(/<span class=\"ebay\">On.*?information:<\/span>/gi,'');

    KindEditor.html('#itemdescription',unTransitionHtml(detail));
//	editor.html(unTransitionHtml(detail));
};

//获取标签中间的值
function trimLabel(s,splitStr){	
    var reg=/(<.*?>)/i;
    while(reg.test(s)){
    	var mStr = RegExp.$1;  
        s=s.replace(mStr,splitStr);
    }
    return s;
}

function test( testName, expected, callback, async ) {
	var test,
		nameHtml = "<span class='test-name'>" + escapeText( testName ) + "</span>";

	if ( arguments.length === 2 ) {
		callback = expected;
		expected = null;
	}

	if ( config.currentModule ) {
		nameHtml = "<span class='module-name'>" + escapeText( config.currentModule ) + "</span>: " + nameHtml;
	}

	test = new Test({
		nameHtml: nameHtml,
		testName: testName,
		expected: expected,
		async: async,
		callback: callback,
		module: config.currentModule,
		moduleTestEnvironment: config.currentModuleTestEnvironment,
		stack: sourceFromStacktrace( 2 )
	});

	if ( !validTest( test ) ) {
		return;
	}

	test.queue();
}

//更换旧图
function refresholdImage(oldSrc,newSrc){
	$('img[src="'+oldSrc+'"]').each(function(){
		$(this).attr('src',newSrc);
		if($(this).attr('rel')){
			$(this).attr('rel',newSrc);
		}
	});
	$('input[value="'+oldSrc+'"]').each(function(){
		$(this).val(newSrc);
	});
//	$('img[src="'+oldSrc+'"]',$('iframe.ke-edit-iframe').contents()).each(function(){
//		$(this).attr('src',newSrc);
//		if($(this).attr('rel')){
//			$(this).attr('rel',newSrc);
//		}
//		if($(this).attr('data-ke-src')){
//			$(this).attr('data-ke-src',newSrc);
//		}
//	});
}


///**
// * 百度翻译
// * @param str
// */
//var BAIDU_TRANSLATE_URL = "http://openapi.baidu.com/public/2.0/bmt/translate?client_id=f2iK6uIQm8YcOqZO0WcSmVxm&from=zh&to=en&q=";
//
//function baiDuTranslate(str, callback){
//	dxmTranslate(str, callback);
//}
///**
// * 判断字符串中是否包含中文
// * @param str
// * @returns {Boolean}
// * 如果包含中文返回true，否则返回false
// */
//function isContainChinese(str){ 
//	return /.*[\u4e00-\u9fa5]+.*/.test(str);	
//}
//
//function baiDuTranslateBak(str, callback){
//	var s = "";
//	if(str != undefined && str != ""){
//		str = $.trim(str);
//		if(str.length > 5000){
//			$.fn.message({type:"error",msg:"超过了翻译的最大字数限制"});
//			return callback(s);
//		}
//		// 包含中文的时候才进行翻译
//		if(!isContainChinese(str)){
//			return callback(s);
//		}
//		str = encodeURIComponent(str);
//		var url = BAIDU_TRANSLATE_URL + str;
//		
//		$.ajax({
//			async : false,
//	        type : "get",
//	        url : url,
//	        dataType : "jsonp",
//	        jsonp : "callback",
//	        data : {},
//	        success : function(data){
//	        	if(data != null){
//	        		// 如果error_code属性，证明请求失败
//	        		if(data.hasOwnProperty("error_code")){
//	        			$.fn.message({type:"error",msg:data.error_msg});
//	        		}else if(data.hasOwnProperty("trans_result")){
//	        			for(var i in data.trans_result){
//	        				if(s == ""){
//	        					s = data.trans_result[i].dst;
//	        				}else{
//	        					s += "\n" + data.trans_result[i].dst;
//	        				}
//	        			}
//	        			callback(s);
//	        		}else{
//	        			$.fn.message({type:"error",msg:"网络连接超时，请稍后再试！"});
//	        		}
//	        	}
//	        },
//	        error:function(err){
//	        	$.fn.message({type:"error",msg:"网络连接超时，请稍后再试！"});
//	        }
//	    });
//	}
//}
//
//var DXM_TRANSLATE_URL = "http://translate.dianxiaomi.com/translate.json";
//function dxmTranslate(str, callback){
//	var s = "";
//	if(str != undefined && str != ""){
//		str = $.trim(str);
//		if(str.length > 5000){
//			$.fn.message({type:"error",msg:"超过了翻译的最大字数限制"});
//			return callback(s);
//		}
//		// 包含中文的时候才进行翻译
//		if(!isContainChinese(str)){
//			return callback(s);
//		}
//		//str = encodeURIComponent(str);
//		var url = DXM_TRANSLATE_URL;
//		
//		$.ajax({
//	        type : "post",
//	        url : url,
//	        dataType : "json",
//	        data : {q:str},
//	        success : function(data){
//	        	if(data != null){
//	        		data = eval(data);
//	        		// 如果属性，证明请求失败
//	        		if(data.code == 0){
//	        			data.data && callback(data.data);
//	        		}else{
//	        			$.fn.message({type:"error",msg:data.msg});
//	        		}
//	        	}
//	        },
//	        error:function(err){
//	        	$.fn.message({type:"error",msg:"网络连接超时，请稍后再试！"});
//	        }
//	    });
//	}
//}