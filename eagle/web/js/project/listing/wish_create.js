//goodsList各相关数据
var timeText = "",
    sku = "",
    msrp = "",
    price = "",
    goodsNum = "",
    sTime ="",
    bTime ="";
var sizeRel = "Man"; //尺寸分类,默认显示Man分类
//创建颜色和尺寸数组，创建一个商品列表的对象
var selColorArr = [],
    sizeType = "",
    selSizeArr = [];
//定义一个新增尺寸的数组
var newSizeData = [];
//定义页面右侧导航位置
var gotowhere = '';
/**
 * 字符串格式化
 * 用法1：
 *  var s = "成功删除&{successNum}个";
 *  s.format({successNum:20});
 * 用法2：
 *  var s = "成功删除&{0}个,错误&{1}个";
 *  s.format(20, "abc");
 * 
 */
String.prototype.format = function(args) {
    if (arguments.length>0) {
        var result = this;
        if (arguments.length == 1 && typeof (args) == "object") { 
            for(var key in args){ 
                var reg=new RegExp ("(&{"+key+"})","g"); 
                //alert(key);
                result = result.replace(reg, args[key]); 
                
            } 
        }else{
            for(var i = 0; i < arguments.length; i++){ 
                if(arguments[i]==undefined) 
                { 
                    return ""; 
                } 
                else{
                    /*RegExp 第一个参数是一个字符串，指定了正则表达式的模式或其他正则表达式，第二个是可选的字符串,包含属性"g"、"i"、"m",分别用于指定全局匹配、区分大小写和多行匹配*/
                    var reg=new RegExp ("(&{["+i+"]})","g"); 
                    result = result.replace(reg, arguments[i]); 
                } 
            } 
        } 
            return result; 
    }else{ 
        return this; 
    } 
};





function hide(){
    $('.modal').modal('hide');
}












function colorRun(){
   for (var i=0;i< goodsColorData.length;i++){
	    $('#goodsColor').append('<div class="col-xs-2 mTop10 h50"><label class="col-xs-12"><span class="checkboxSpanCss fWhite ' + goodsColorData[i]["class"] + '"style="background:' + goodsColorData[i]["rgb"] + '">' + goodsColorData[i]["name"] + '</span> <input type="checkbox" name="checkbox"  value="' + goodsColorData[i]["colorId"] + '"></label></div>');
    }
}





var newColor = [];


/**
 * 判断字符串中是否包含中文
 * @param str
 * @returns {Boolean}
 * 如果包含中文返回true，否则返回false
 */
function isContainChinese(str){ 
	return /.*[\u4e00-\u9fa5]+.*/.test(str);	
}


/*
*动态调整div#goodsColor的高度
*/
function ajustColorHeight(){
	$count = $('#goodsColor input[name="checkbox"]').length;
	$row = Math.ceil($count/6);
	$('#goodsColor').height($row*50);
}










//尺寸通过模板写入
function showSize(key){
        
        var tpl = sizeData[key]["head"];
        $("#size_content").html("");
        for (var i = 0;i<sizeData[key]["data"].length ;i++ )
        {
                //sizeData[key]["data"][i]["index"] = i;
                //用数据转换模板把数据结构里的data内容写到相应的tpl显示方法里并累记到tpl时里
                //sizeData[key]["data"][i]["key"] = key;
                sizeData[key]["data"][i]["code_size"] = escape(sizeData[key]["data"][i]["size"]);  //设置code_size = size;
                tpl += sizeData[key]["tpl"].format(sizeData[key]["data"][i]);
        };
        tpl += sizeData[key]["foot"];
        $("#size_content").append(tpl);
        	ajustSizeHeight(); //调整size div的高度
        	addBtn(key);//注册添加安钮事件 
        //endIptEnter();//注册阻止input回车提交方法
        //$('#goodsList').html('');//初始化产品变种列表
        //selSizeArr = [];
}

function ajustSizeHeight(){
	$('#commoditySize').height($('#size_content').height());
}


function addBtn(key){
        //回车方法
        function inputEnter(e,that){
                var keyCode = e.keyCode ? e.keyCode : e.which ? e.which : e.charCode; 
                if (keyCode == 13) {
                $(that).next().click();
                };
        };
        //重量btn处理
        $('#newWeightSize').keyup(function(e){
                var price = $(this).val();
                if (validateWeight(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        //长度btn处理
        $('#newLengthSize').keyup(function(e){
                var price = $(this).val();
                if (validateLength(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        //面积或体积btn处理
        $('#newAreaSize').keyup(function(e){
                var price =$(this).val();
                if (validateArea(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        //电压btn处理
        $('#newVoltageSize').keyup(function(e){
                var price = $(this).val();
                if (validateVoltage(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        //容量btn处理
        $('#newVolumeSize').keyup(function(e){
                var price = $(this).val();
                if (validateVolume(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                        
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        //鞋子btn处理
        $('#newShoesSize').keyup(function(e){
                var price = $(this).val();
                $('#size_content').find('input[type=checkbox]').each(function(){
                        var val = $(this).val();
                        newSizeData.push(escape(val));
                });
                if ($.isNumeric(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        //数字btn处理
        $('#newNumbersSize').keyup(function(e){
                var price = $(this).val();
                  
                $('#size_content').find('input[type=checkbox]').each(function(){
                        var val = $(this).val();
                        newSizeData.push(escape(val));
                });
                if ($.isNumeric(price))
                {                               
                        for (var i=0;i<newSizeData.length ;i++ )
                        {       
                                if (price==unescape(newSizeData[i]))
                                {
                                        $(this).next().addClass("disabled");
                                        return;
                                }       
                        }
                        
                        $(this).next().removeClass("disabled");
                        inputEnter(e,this);
                }else 
                {
                        $(this).next().addClass("disabled");
                }
        });
        
function addSizeHtml(size){
    $('#sizeAdd').append('<div class="col-xs-4"><label><input type="checkbox" name="' + key + '" checked="true" value="'+ escape(size) +'">&nbsp' + size +'   </label></div>');
        
};

//sizeAddBtn处理
$('.new'+key+'Size').click(function(){
    var size = ($('#new'+key+'Size').val());
    $('#new'+key+'Size').val("");
    sizeComparison(escape(size));
    addSizeHtml(size);
    ajustSizeAddHeight();
    $(this).addClass("disabled");
    if (key != sizeType)
    {       
        sizeType = key;
        newSizeData=[];
        $('#goodsList').html('');
        newSizeData.push(escape(size));
        selSizeArr=[];
    }else{
        newSizeData.push(escape(size));
    }
    selSize(escape(size));
    });
}

//调整#sizeAdd高度
function ajustSizeAddHeight(){
	$count = $('#sizeAdd input[type="checkbox"]').length;
	$row =Math.ceil($count/3);
	$('#sizeAdd').height($row*27);
	$('#commoditySize').height($('#size_content').height());
}

//数组中删除某个对象的方法
function arrDel (arr,delVal){
		for (var i=0;i<arr.length ;i++ ){			
			if (arr[i] == delVal){   
				 arr.splice(i,1);
				return arr;
			}
		}
	}

//goodsList里移除Btn样式及点击事件
var removeBtn = '<button type="button" class="btn btn-default" onclick="goodsRemove(this)" >移除</button>';


function enablePost(obj){
    if($(obj).val() == 'Y'){
        $(obj).val('N');
    }else{
        $(obj).val('Y');
    }
}

function AddVariance(){
    $('#goodsList').append('<tr><td><input type="text" class="form-control" name="color" value=""></td><td><input type="text" class="form-control" name="size" value=""></td><td style="text-align:center"><input type="text" class="form-control" name="sku" value=""></td><td><input type="text" class="form-control" name="price" value=""></td><td><input type="text" class="form-control" name="inventory" value=""></td><td><input type="text" class="form-control" name="shipping" value=""></td><td class="pointer" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>');
    ajustGoodsListHeight();
}

function AddOnlineVariance(){
    $('#goodsList').append('<tr><td><input type="text" class="form-control" name="color" value=""></td><td><input type="text" class="form-control" name="size" value=""></td><td><input type="text" class="form-control" name="sku" value=""></td><td><input type="text" class="form-control" name="price" value=""></td><td><input type="text" class="form-control" name="inventory" value=""></td><td><input type="text" class="form-control" name="shipping" value=""></td><td style="text-align: center;cursor:pointer;" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td style="text-align:center;"><input type="checkbox" name="enable" onclick="enablePost(this)"/></td><td><span class="glyphicon glyphicon-remove red pointer" onclick="goodsRemove(this)"></span></td></tr>');
    ajustGoodsListHeight();
}

function goodsRemove(ipt){
		var id = $(ipt).closest('tr');
		$(id).remove();
        ajustGoodsListHeight();
        $('input[name="opt_method"]').val('all');
		//得到删除tr的colorId及sizeId
		var color = $(id).find('input[name="color"]').val(),
			size = $(id).find('input[name="size"]').val(),
			colorTd = $('#goodsList').find('input[name="color"]'),
			sizeTd = $('#goodsList').find('input[name="size"]'),
			colorTdArr = [],
			sizeTdArr = [];

		for(var i = 0;i<colorTd.length;i++){
			colorTdArr.push(escape($(colorTd[i]).val()));
		};
		for(var j = 0;j<sizeTd.length;j++){
			sizeTdArr.push(escape($(sizeTd[j]).val()));
		};
		//新色删除完以后数组及checkbox处理
		if($.inArray(escape(color),colorTdArr)=='-1'){
			var colorId = escape(color.toLowerCase().replace(/ /g,""));
			$('#goodsColor').find('input[type="checkbox"]').each(function(){
				if($(this).val() == colorId){
					this.checked = false;
					arrDel(selColorArr,colorId);
				}
			});
		};
		if($.inArray(escape(size),sizeTdArr)=='-1'){
			var sizeId = escape(size);
			$('#sizeAdd').find('input[type="checkbox"]').each(function(){
				if($(this).val() == sizeId){
					this.checked = false;
					arrDel(selSizeArr,sizeId);
				}
			});
		};
};

//input正则方法
//容量
function validateVolume(str){
	var volume_format=/^(\d+(\.\d+)?)\s*(ml|l|oz\.?|m\^3|cm\^3|gallon|quart|cup|qt\.?|pt\.?|litre|liter|pint|fl\.?\s?oz\.?)s?$/gi;
	if(str.match(volume_format))return true;
	return false;
}
//长度
function validateLength(str){
	var length_format_1=/^(\d+(\.\d+)?)\s*(mm|cm|m|in\.?|inch(es)?|\"|\'|ft\.?|feet)$/gi;var length_format_2=/^(\d+(\.\d+)?)\s*(ft.?|feet|\')\s*(\d+(\.\d+)?)\s*(in\.?|inche(es)?|\")$/gi;
	if(str.match(length_format_1)||str.match(length_format_2))return true;
	return false;
}
//面积或体积
function validateArea(str){
	var area_format_1=/^(\d+(\.\d+)?)\s*(mm|cm|m|in\.?|inch(es)?|\"|\'|ft\.?|feet)\s*(\*|x|by)\s*(\d+(\.\d*)?)\s*(mm|cm|m|in\.?|inch(es)?|\"|\'|ft\.?|feet)$/gi;
	var area_format_2=/^(\d+(\.\d+)?)\s*(mm|cm|m|in\.?|inch(es)?|\"|\'|ft\.?|feet)\s*(\*|x|by)\s*(\d+(\.\d*)?)\s*(mm|cm|m|in\.?|inch(es)?|\"|\'|ft\.?|feet)\s*(\*|x|X|by)\s*(\d+(\.\d*)?)\s*(mm|cm|m|in\.?|inch(es)?|\"|\'|ft\.?|feet)$/gi;
	if(str.match(area_format_1)||str.match(area_format_2))return true;
	return false;
}
//电压
function validateVoltage(str){
	var voltage_format=/^(\d+(\.\d+)?)\s*v$/gi;
	if(str.match(voltage_format))return true;
	return false;
}
//重量
function validateWeight(str){
	var weight_format=/^(\d+(\.\d+)?)\s*(mg|g|kg|oz\.?|ounce|gram|pound|lb)s?$/gi;
	if(str.match(weight_format))return true;
	return false;
}

//定义一个新增尺寸的数组
var newSizeData = [];
//size添加比对
function sizeComparison(price){
	for (var i=0;i<newSizeData.length ;i++ )
	{
		if (price==newSizeData[i])
		{
			return;
		}	
	}
};

//添加颜色方法
function selColor (colorId){
        selColorArr.push(colorId); //将colorId放入selColorArr数组中
        var color = unescape(colorId);
        for (var k = 0;k<otherColorDataA.length;k++){

               //判断colorId是否在otherColorDataA中,若在，则将otherColorDataA中的匹配值赋给color
                if (otherColorDataA[k].toLowerCase().replace(/ /g,"") == colorId.toLowerCase()){                
                        color = otherColorDataA[k];             
                }
        };

        // ‘CwhiteC','CblackC'
        colorId = "C" + unescape(colorId).replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "C";
        if (selSizeArr.length != 0)
        {
                
            price = $('#wish_product_price').val();
            goodsNum = $('#wish_product_count').val();
            parent_sku = $('#wish_product_parentSku').val();
           	shipping = $('#wish_product_shipping').val();
            for (var i = 0;i < selSizeArr.length ;i++ )
            {          
               var size = unescape(selSizeArr[i]);  //获取size

               //'numLnum', 'numSnum' 
               var sizeId = "num" + size.replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "num";
               //$('#'+sizeId).remove();
              var sku = createSku(parent_sku,color,size);
               //移除只有size 的tr
               $('tr[data-val="'+sizeId+'"]').remove(); 

              //tr的id = 'CblackC_numLnum'
              var str = '<tr id="' + colorId + '_' + sizeId +'" data-val="' + colorId + '_' + sizeId +'"><td style="text-align:center;" name="color">'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'<input type="hidden" name="color" value="'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'"></td><td name="size" style="text-align:center;">' + unescape(size)+ '<input type="hidden" name="size" value="'+ unescape(size) +'"></td><td><input type="text" class="form-control" name="sku" value="'+ sku +'"></td><td><input type="text" class="form-control" name="price" value="' + price + '"></td><td><input type="text" class="form-control" name="inventory" value="' + goodsNum + '"></td><td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"/></td><td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>';
               //插入goodsList中
                $('#goodsList').append(str);
                ajustGoodsListHeight();
                    
            };
        }else{  //若selSizeArr为空，则插入尺寸为空的tr
            parent_sku = $('#wish_product_parentSku').val();
            price = $('#wish_product_price').val();
            goodsNum = $('#wish_product_count').val();
            shipping = $('#wish_product_shipping').val();
            var sku = createSku(parent_sku,color,'');
            var str = '<tr id="'  + colorId  +'" data-val="'  + colorId  +'"><td style="text-align:center;">'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'<input type="hidden" name="color" value="'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'" ></td><td name="size"><input type="hidden" name="size" value=""></td><td><input type="text" class="form-control" name="sku" value="'+ sku +'"></td><td><input type="text" class="form-control" name="price" value="' + price + '"></td><td><input type="text" class="form-control" name="inventory" value="' + goodsNum + '"></td><td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"/></td><td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>';
            $('#goodsList').append(str);
            ajustGoodsListHeight();
        };
};





//生成子sku方法
function createSku(parent_sku,color,size){
	var sku ='';
	if(sku !== undefined){
		sku = parent_sku;
	}
	if(color!=='' && color !== undefined){
		 console.log(color);
		sku +='-'+ color;
	}
	if(size!=='' && size!== undefined){
		console.log(size);
		sku +='-' + unescape(size);
	}
	return sku;
};


//删除颜色方法
function unSelColor (colorId){
	arrDel(selColorArr,colorId);
	colorId = "C" + unescape(colorId).replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "C";
	if(selSizeArr.length != 0)
	{
		for(var i = 0;i<selSizeArr.length;i++)
		{
			var size = unescape(selSizeArr[i]);
			var sizeId = "num" + size.replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "num";
			//$('#'+ colorId.replace(/[\\']/g,"") + '_' + sizeId).remove();
			$('tr[data-val="'+ colorId.replace(/[\\']/g,"") + '_' + sizeId +'"]').remove();
            ajustGoodsListHeight();
			if (selColorArr.length == 0)
			{
				price = $('#wish_product_price').val();
				goodsNum = $('#wish_product_count').val();
                parent_sku = $('#wish_product_parentSku').val();
               	shipping = $('#wish_product_shipping').val();
				var sku = createSku(parent_sku,'',size);
				var str = '<tr id="' + sizeId +'" data-val="'+ sizeId+'"><td style="text-align:center;"><input type="hidden" name="color" value=""></td><td name="size" style="text-align:center;">' + unescape(size)+ '<input type="hidden" name="size" value="'+ unescape(size) +'"></td><td><input type="text" class="form-control" name="sku" value="'+ sku +'"></td><td><input type="text" class="form-control" name="price" value="' + price + '"></td><td><input type="text" class="form-control" name="inventory" value="' + goodsNum + '"></td><td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"/></td><td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>';
				$('#goodsList').append(str);
                ajustGoodsListHeight();
			}
		}
	}else
	{
			//$('#'+colorId.replace(/[\\']/g,"")).remove();
			$('tr[data-val="'+colorId.replace(/[\\']/g,"")+'"]').remove();
            ajustGoodsListHeight();
	}
}




//添加尺寸方法
function selSize (size){
        
        selSizeArr.push(size);
        var sizeId = "num" + unescape(size).replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "num";
        // var shippingTime = $.trim($("#productDeliveryTime").text());
        // if(shippingTime != null && shippingTime != "" && shippingTime != "选择运送时间"){
        //         timeText = shippingTime;
        // }
        if (selColorArr.length != 0)
        {       
                // console.log('not empty:'); 
                // console.log(selColorArr);
                // console.log(selSizeArr);
               	price = $('#wish_product_price').val();
                goodsNum = $('#wish_product_count').val();
                parent_sku = $('#wish_product_parentSku').val();
               	shipping = $('#wish_product_shipping').val();
                for (var i=0;i<selColorArr.length;i++ )
                {       
                        var color = unescape(selColorArr[i]);
                        var colorId = "C" + color.replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "C";
                        for (var k = 0;k<otherColorDataA.length;k++){
                                if (otherColorDataA[k].toLowerCase().replace(/ /g,"") == color.toLowerCase()){  
                                        color = otherColorDataA[k];
                                }
                        };
                        //$('#'+colorId.replace(/[\\']/g,"")).remove();
                        $('tr[data-val="'+colorId.replace(/[\\']/g,"")+'"]').remove();      
                        var sku = createSku(parent_sku,color,size);
                        var str = '<tr id="' + colorId + '_' + sizeId +'" data-val="'+colorId + '_' + sizeId+'"><td style="text-align:center;">'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'<input type="hidden" name="color" value="'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'"></td><td style="text-align:center;">' + unescape(size)+ '<input type="hidden" name="size" value="'+ unescape(size) +'"></td><td><input type="text" class="form-control" name="sku" value="'+ sku +'"></td><td><input type="text" class="form-control" name="price" value="' + price + '"></td><td><input type="text" class="form-control" name="inventory" value="' + goodsNum + '"></td><td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"/></td><td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>';
                        $('#goodsList').append(str);
                        ajustGoodsListHeight();
				
                };
        }else{
                // console.log('empty:');
                // console.log(selColorArr);
                price = $('#wish_product_price').val();
                goodsNum = $('#wish_product_count').val();
                parent_sku = $('#wish_product_parentSku').val();
               	shipping = $('#wish_product_shipping').val();
                var sku = createSku(parent_sku,'',size);
                var str = '<tr id="' + sizeId +'" data-val="' + sizeId + '"><td name="color"><input type="hidden" name="color" value=""></td><td name="size" style="text-align:center;">' + unescape(size)+ '<input type="hidden" name="size" value="'+ unescape(size) +'"></td><td><input type="text" class="form-control" name="sku" value="'+ sku +'"></td><td><input type="text" class="form-control" name="price" value="' + price + '"></td><td><input type="text" class="form-control" name="inventory" value="' + goodsNum + '"></td><td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"/></td><td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>';
                $('#goodsList').append(str);
                ajustGoodsListHeight();
        }
}





//删除尺寸方法
function unSelSize (size){
        arrDel(selSizeArr,size);
        var sizeId = "num" + unescape(size).replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "num";
        if(selColorArr.length != 0)
        {
                price = $('#wish_product_price').val();
                goodsNum = $('#wish_product_count').val();
                parent_sku = $('#wish_product_parentSku').val();
               	shipping = $('#wish_product_shipping').val();
                for (var i=0;i<selColorArr.length; i++)
                {
                    var color = unescape(selColorArr[i]);
                    var colorId  = "C" + color.replace(/[\s\.\\'*]+/g,"_").replace(/[\\"]+/g,"-") + "C";
                    var id = colorId + '_' + sizeId;
                    //$('#'+id).remove();
                    $('tr[data-val="'+id+'"]').remove();
                    ajustGoodsListHeight();
                    if (selSizeArr.length == 0){
                        for (var k = 0;k<otherColorDataA.length;k++){
                           if (otherColorDataA[k].toLowerCase().replace(/ /g,"") == colorId.toLowerCase()){
                                 color = otherColorDataA[k];                             
                           }
                        };
                        var sku = createSku(parent_sku,color.replace(/(\w)/,function(v){return v.toUpperCase()}),'');
                        var str = '<tr id="' + colorId +'" data-val="'+colorId+'"><td name="color" style="text-align:center;">'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'<input type="hidden" name="color" value="'+ color.replace(/(\w)/,function(v){return v.toUpperCase()}) +'"></td><td name="size" ><input type="hidden" name="size" value=""></td><td><input type="text" class="form-control" name="sku" value="'+ sku +'"></td><td><input type="text" class="form-control" name="price" value="' + price + '"></td><td><input type="text" class="form-control" name="inventory" value="' + goodsNum + '"></td><td><input type="text" class="form-control" name="shipping" value="'+ shipping +'"/></td><td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td><td>' + removeBtn + '</td></tr>';
                        $('#goodsList').append(str);
                        ajustGoodsListHeight();
                    };
                };
        }else{
                   //$('#'+sizeId).remove();
                   $('tr[data-val="'+sizeId+'"]').remove();
                    ajustGoodsListHeight();
                    
                    
        }
}
//选择变参商品的图片
function selectpic(obj){
	$('.modal-content').width(130*7);
    $position = $(obj).parents('tr').index();
    var imageList = [];
    $('#image-list .image-item[upload-index]').each(function(){
        imageList.push($(this).find('img').attr('src'));
    });
    var $num = imageList.length;
    if($num == undefined || $num == '0'){
        tips({type:"error",msg:"请上传产品图片！"});
        return;
    }
    $('#common_modal .modal-body').html('<div class="row" id="product_image_list"><div class="col-xs-11 col-xs-offset-1 mTop50 product_image_list_1"></div><div class="col-xs-11 col-xs-offset-1 mTop20 product_image_list_2"></div></div>');
    var str;
    for(var i=1; i<= $num; i++){
        str ='<div id="product_image_'+i+'" class="product_image col-xs-2" style="width:130px;height:130px;margin-left:10px;border:1px solid #999999;"><a class="thumnail col-xs-12"style="width:110px;min-height:110px;padding:10px 2px 0 2px;display:block;"><img style="max-width:100px;max-height:100px;" name="product_image_'+i+'" src="'+ imageList[i-1]+'">';
        str +='</a><div class="col-xs-12" style="margin-left:70px;"><input type="radio" class="col-xs-5" name="product_image" value="'+i+'" onclick="ensure('+$position+')"></div>';
        str +='</div>';
        if(i <=5){
            $('#product_image_list .product_image_list_1').append(str);
        }else{
            $('#product_image_list .product_image_list_2').append(str);
        }

    }
    $('#common_modal').modal('show');
    $left = ($(window).width()-$('.modal-content').width())*0.5;
    $('.modal-content').css({left:-$left});

}

//确定变参商品图片，并将图片移至表格中
function ensure(obj){
	// console.log($('input[type="radio"]:checked').attr('value'));
	var $id = $('#product_image_list input[type="radio"]:checked').val();
    // console.log($id);
    //console.log($id);
	// var $pic = $('#product_image_'+$id+' img').attr('src'); 	
    //console.log($pic);
    //console.log($('#'+obj));
    var $pic = $('#product_image_list img[name="product_image_'+$id+'"]').attr('src');
    // console.log($pic);
	$('#goodsList tr').eq(obj).children('td').eq(6).html('<img name="image_url" src="'+$pic+'" style="width:50px;">');
    ajustGoodsListHeight();
}


//调整商品列表的高度
function ajustGoodsListHeight(){
	// alert($('.goodsList').height());
	// alert($('#goodsList').height());
	// $row = $('#goodsList tr').length;
	// alert($row);
	// alert($('.bgColor1').height());
	$('.goodsList').height($('#goodsList').height() + $('.bgColor1').height()+40);
	// alert($('.goodsList').height());
}




// 检测是否为仿品
function checkReplica(){
	
	var name = $("#wish_product_name").val();
	var desc = $("#wish_product_description").val();
    var tags = '';
    $('#goods_tags .tag span').each(function(){
        tags += $(this).html()+ " ,";
    });
	if(name == "" && desc == ""){
		tips({type:"error",msg:"请先填写产品标题或产品描述后，再检测！"});
		return ;
	}
	var msg = "";
	var res = checkWishBrand(name + " ," + desc + ' ,' + tags);
	if(res != ""){	
		msg += res;
	}
	if(msg == ""){
		tips({type:"success",msg:"恭喜！您的产品检测通过!",existTime:8000});
	}else{
		tips({type:"error",msg:"<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;检测未通过！您可能侵害的品牌为："+ msg +"，有可能会被官方认定为仿品！"});
	}
}
// 检测内容中是否包含wish的保护品牌
function checkWishBrand(content){
    console.log(content);
	var brand = "";
	if(content != ""){
		content = content.replace(/\n/g, ",").toLowerCase();
		for(var i in wishBrands){
			var wishBrandLower = wishBrands[i].toString().toLowerCase();
			if(content.indexOf(" "+wishBrandLower) != -1 || content.indexOf(wishBrandLower+" ") != -1){
				if(brand == ""){
					brand = wishBrands[i];
				}else{
					brand += "、" + wishBrands[i];
				}
			}
		}
	}
	return brand;
}


function tips(args){
	var tips = args['type'];
	var tips_content= args['msg'];
    if(args['existTime'] != undefined){
        var tips_time = args['existTime'];
    }
	// alert(tips_content);
	// alert(tips);
	if(tips == 'error'){
		$warning = '错误提醒:';
		$colorclass = 'alert-danger';
	}else{
		$warning = '温馨提示:';
		$colorclass = 'alert-success';
	}
	$content = ' <div class="alert '+ $colorclass +'" role="alert" style="z-index: 9999999; width: 680px; left: 30%; right: 30%; margin: auto; top: 8%; position: fixed;"><button type="button" class="close" data-dismiss="alert">×</button>';
	$tip = '<div class="pull-left mLeft10"><strong>'+ $warning+'</strong></div>';
	$tip_content = '<div class="pull-left mLeft10"><span>'+ tips_content +'</span></div>'
	$content += $tip + $tip_content;
	$('.right_content').append($content);
    if(args['existTime'] != undefined){
        setTimeout(function(){
            $('.alert').remove();
        },tips_time);
    }
}



//产品标签模糊搜索
/****tag清除恢复写入***/
var memoryData = [];//定义一个收集标签的数组
//产品标签处效果的方法


function createTag(that,iptOne){
    if (iptOne.replace(/[\s]/g,'') != "")//判断输入的是不是空值，不是空的就进行写入
        {
        var iptTwo = iptOne.replace(/[.]/g,''); 
        if (iptTwo != "")
            {
                memoryData.push(iptOne);//给数组memoryData写入input输入的值
                $(that).before('<span class="tag label label-info pull-left" style="margin-left:3px;"><span>' + iptOne +'</span><a href="javascript:void(0);" onclick="removeTag(this);" class="fWhite m3 glyphicon glyphicon-remove glyphicon-tag-remove"></a></span>');//写入统一格式的span
                $('#wish_product_tags').val("");//清空输入框
            };
        };
        $('.tags_num').html($('#goods_tags span.tag').length);
};


//删除清空的方法
function removeTag(obj){
    var tag = $.trim($(obj).parent().text());
    var text = $(obj).parent().text();
    // 从数组中删除
    memoryData.splice($.inArray(tag, memoryData),1);
    $(obj).parent().remove(); //移除当前焦点的父元素及子元素
    $('.tags_num').html($('#goods_tags span.tag').length);
};









$(document).ready(function(){

    colorRun();

    $( "#otherColor" ).autocomplete({
            source: otherColorDataA ,
            limit : 15
    });

    setTimeout(function(){
        $('.slide-toggle').click();
    },5000);    

    //尺寸初始显示内容
    showSize("Man");

    //颜色点击事件
    $('.colorAdd').click(function(){
         var color  = $('#otherColor').val(); 
         if(isContainChinese(color)){
              tips({type:"error",msg:"请输入英文"});
             return;
         }

    //判断input.otherColor中新加的颜色在不在goodsColorData;若在，则清空input.otherColor中内容，并且在goodsColor中寻找匹配的颜色，将其选中
         for (var n=0 ; n<goodsColorData.length ; n++){
            if(color.toLowerCase().replace(/ /g,"") == goodsColorData[n]['colorId'].toLowerCase()){
                   $('#otherColor').val("");
                   $('#goodsColor').find('input[type="checkbox"]').each(function(){
                        if($(this).val() == escape(color.toLowerCase().replace(/ /g,""))){
                             $(this).click();
                        }
                   });
                   return;
              };
         };

         
          if ( color != '' ){//若input.otherColor中的内容不为空，则将input.otherColor中的颜色和otherColorDataB中的颜色进行比较判断，若相等，则和newColor数组比较，若newColor中有则返回；将input.otherColor和otherColorDataA比较，若相等则在goodsColor中插入该颜色的div 并且将input.otherColor添加到newColor数组中,清空input.otherColor中的内容，运行selColor方法;

           for (var k=0;k<otherColorDataB.length;k++){
                   if (otherColorDataB[k].toLowerCase() == color.toLowerCase().replace(/ /g,"")){
                          var iptColorId = otherColorDataB[k];
                        for (var i=0;i<newColor.length ;i++ ){
                              if (color.toLowerCase().replace(/ /g,"") == newColor[i].toLowerCase()){       

                            return;
                            };
                        };
                        for (var j=0;j<otherColorDataA.length ;j++ ){       
                             if (color.toLowerCase() == otherColorDataA[j].toLowerCase()){
                                 //新加颜色写到新数组里便于下次添加判断
                                 newColor.push(iptColorId);
                                //写入样式
                                $('#goodsColor').append('<div class="col-xs-2 h50 mTop10"><label class="col-xs-12">' + otherColorDataA[j] + '<input type="checkbox" checked="true" name="checkbox" value="'+ iptColorId +'"></label></div>');
                                ajustColorHeight();
                                //运行添加颜色方法
                                selColor(iptColorId);
                                //清空输入框
                                $('#otherColor').val("");
                            }
                        };
                    }
                // }else{//若input.otherColor中的内容为空，则将input.otherColor和newColor数组进行比较，若newColor中有则返回,否则将input.otherColor插入newColor数组中，并且在goodsColor中插入该颜色的div,清空input.otherColor内容,运行selColor方法;
                //      var iptColorId = escape(color.replace(/ /g,"")).toLowerCase();
                //     for (var i=0;i<newColor.length;i++ ){
                //         if (iptColorId == newColor[i].toLowerCase()){
                //             return;
                //         };
                //     };
                //     newColor.push(iptColorId);
                //     //写入样式
                //     $('#goodsColor').append('<div class="col-xs-2 h50 mTop10"><label class="col-xs-12">' + color + ' <input type="checkbox" checked="true" name="checkbox" value="'+ iptColorId +'"></label></div>');
                //     ajustColorHeight();
                //     //运行添加颜色方法
                //     selColor(iptColorId);
                //       //清空输入框
                //     $('#otherColor').val("");
                // };
            };
         };
    });
 
    $(".sizeBtn").click(function(){
        var key = $(this).attr("rel");
        // console.log(selSizeArr);
        // console.log(newSizeData);
        // console.log(sizeType);
        if(sizeRel != undefined) sizeRel = key;
        showSize(key);
        if (key == sizeType)
        {
            for (var i=0; i<newSizeData.length ;i++ )
                {
                    $('#sizeAdd').append('<div class="col-xs-4"><label><input type="checkbox" name="' + key + '" value="'+ newSizeData[i] +'">&nbsp' + unescape(newSizeData[i]) +'  </label></div>');
                }
            for (var j =0;j<selSizeArr.length ;j++ )
            {   
                size = selSizeArr[j];
                // console.log(size);
                $('#size_content input[value="'+size+'"]').attr('checked',true);
            }
        }
        $(".sizeBtn").each(function(){
            $(this).removeClass('noCss').find('.size_tips').remove();
        });
        $(this).addClass('noCss').append('<span class="size_tips"></span>');
        
    });


    //颜色check点击方法
    $(document).on('click','#goodsColor input[type=checkbox]',function(event){
          //触发当前事件的源对象 target是firefox下的属性，srcElement是IE下的属性

          var target = event.target || event.srcElement,   

            colorId = $(target).val();
            !!target.checked?selColor(colorId):unSelColor(colorId);  //！！相当于boolean()

    });



    //尺寸check点击方法
    $(document).on('click','#size_content input[type=checkbox]',function(event){
            var target = event.target || event.srcElement,
                    sizeName = $(target).attr('name'),
                    size = $(target).val();
                    // console.log(sizeType);
                    // console.log(sizeName);
            if (sizeName != sizeType)
            {
                    sizeType = sizeName ;
                    $('#goodsList').html('');
                    selSizeArr = [];
                    newSizeData=[];
            }
            !!target.checked?selSize(size):unSelSize(size);
    });


    //触发时注册以上方法
    $('#wish_product_tags').blur(function(){
        var agginMemory = [];
        var tagsNum = $('#goods_tags span.tag').length;
        var currentTxt = $(this).val();
        var tagArr = currentTxt.split(',');
        for(var j = 0 ; j<tagArr.length;j++){
            var isAgin = 0 ;
            for(var i = 0 ; i < memoryData.length ; i++ ){
                if(memoryData[i].toLowerCase() == tagArr[j].toLowerCase()){
                    isAgin = 1;
                }
            }
            if(isAgin == 0 && tagsNum < 10){
                createTag(this,tagArr[j]);   //运行写入span方法
            }else{
                agginMemory.push(tagArr[j]);
            }
            //removeTag();   //注册删除事件
        };
        if(tagsNum >= 10){
            tips({type:"error",msg:"产品标签最多可添加 10个"});
            return false;
        }

        if(agginMemory.length > 0){
            var str = '';
            for(var i = 0 ; i < agginMemory.length ; i++){
                if(str != ''){
                    var newStr = ','+agginMemory[i];
                    str += newStr;
                }else{
                    str += agginMemory[i];
                }
            }
            tips({type:"error",msg:str+"为重复的标签"});
        }
    });


    $('#wish_product_tags').keyup(function(event){
       
        event = event || window.event;
        var currentTxt = $(this).val();
        var tagsNum = $('#goods_tags span.tag').length;
        //console.log(event.which);
        if(event.which == "13" || event.which == "188") {  //判断是不是回车键或","号键
            event.keyCode = '13';
            //把键值改为0防止其他事件发生
            var agginMemory = [];
            var tagArr = currentTxt.split(',');
            // console.log(tagArr);
            for(var j = 0 ; j<tagArr.length;j++){
                var isAgin = 0 ;
                for(var i = 0 ; i < memoryData.length ; i++ ){
                    if(memoryData[i].toLowerCase() == tagArr[j].toLowerCase()){
                        isAgin = 1;
                    }
                }
                if(isAgin == 0 && tagsNum < 10){
                    createTag(this,tagArr[j]);   //运行写入span方法
                }else{
                    agginMemory.push(tagArr[j]);
                }
            }
            if(agginMemory.length > 0){
                var str = '';
                for(var i = 0 ; i < agginMemory.length ; i++){
                    if(str != ''){
                        var newStr = ','+agginMemory[i];
                        str += newStr;
                    }else{
                        str += agginMemory[i];
                    }
                }
                tips({type:"error",msg:str+"为重复的标签"});
            }
        };
    });

    $( "#wish_product_tags" ).autocomplete({
        source: function(request,response){
            $.ajax({
               type: "post",
               url: "/listing/wish/ajax-tags",
               data: { q : request.term},
            }).done(function(data){
                function cmp(a, b) {
                            if (a > b) { return 1; }
                            if (a < b) { return -1; }
                            return 0;
                        }
                function by(keyword) {
                    keyword = keyword.toLowerCase();
                    return function(a, b) {
                        a = a.toLowerCase();
                        b = b.toLowerCase();
                        var i = a.indexOf(keyword);
                        var j = b.indexOf(keyword);

                        if (i === 0) {
                            if (j === 0) {
                                return cmp(a, b);
                            }
                            else {
                                return -1;
                            }
                        }
                        else {
                            if (j === 0) {
                                return 1;
                            }
                            return cmp(a, b);
                        }
                    };
                };
                // console.log(data);
                data = eval(data);
                data.sort(by(request.term));
                //console.log(eval(data));
                response(data);
                
            });
        }

    });

    $('span[data-toggle="tooltip"]').hover(function(){
        $('.product_title').append('<div class="product_tips ">'+$(this).attr('data-original-title')+'</div>');
    },function(){ 
        $('.product_title .product_tips').remove();
    });
    
    if($('#wish_product_tags').val()!= ''){
        var agginMemory = [];
        var currentTxt = $('#wish_product_tags').val();
        var tagArr = currentTxt.split(',');
        for(var j = 0 ; j<tagArr.length;j++){
            var isAgin = 0 ;
            for(var i = 0 ; i < memoryData.length ; i++ ){
                if(memoryData[i].toLowerCase() == tagArr[j].toLowerCase()){
                    isAgin = 1;
                }
            }
            if(isAgin == 0){
                createTag($('#wish_product_tags'),tagArr[j]);   //运行写入span方法
            }else{
                agginMemory.push(tagArr[j]);
            }
            //removeTag();   //注册删除事件
        };
        if(agginMemory.length > 0){
            var str = '';
            for(var i = 0 ; i < agginMemory.length ; i++){
                if(str != ''){
                    var newStr = ','+agginMemory[i];
                    str += newStr;
                }else{
                    str += agginMemory[i];
                }
            }
            tips({type:"error",msg:str+"为重复的标签"});
        }
    }       

    $('#wish_product_parentSku').keyup(function(){
            OneKeyCreateSku($(this));   
    });
    $('#wish_product_price').blur(function(){
            OneKeyCreateSku($('#wish_product_parentSku'));
    });
    $('#wish_product_count').blur(function(){
            OneKeyCreateSku($('#wish_product_parentSku'));
    });
    $('#wish_product_shipping').blur(function(){
            OneKeyCreateSku($('#wish_product_parentSku'));
    });


    $('input[name="shipping_time"]').on('click',function(){
        if($(this).is(":checked") && $(this).val() == 'other'){
            $('input[name="shipping_short_time"]').removeAttr('disabled');
            $('input[name="shipping_long_time"]').removeAttr('disabled');
        }else{
            $('input[name="shipping_short_time"]').attr('disabled','true');
            $('input[name="shipping_long_time"]').attr('disabled','true');
        }
    });

    //fix bootstrap模态框遮罩层高度
    $('#cite_modal').scroll(function(){
        // console.log($(this).find('.modal-content').height());
        $modal = $(this).find('.modal-content').height();
        if($modal > $(window).height()){
            $('.modal-backdrop').height($(window).height()+$(this).scrollTop());
        }else{
            $('.modal-backdrop').height($(window).height());
        }
      });




    $(window).scroll(function(event){
        var winPos = $(window).scrollTop();
        var $wish_product_baseinfo = $('.wish_product_baseinfo').offset().top;            
        var $wish_product_image = $('.wish_product_image').offset().top-3;
        var $wish_product_variance = $('.wish_product_variance').offset().top-3;
        // console.log(winPos);
        // console.log($wish_product_baseinfo);
        // console.log($wish_product_image);
        // console.log($wish_product_variance);
        // console.log(gotowhere);
        if(isClick == false){
            if(winPos < $wish_product_image){
                    showscrollcss('wish_product_baseinfo');
            }else if(winPos >= $wish_product_image && winPos < $wish_product_variance){
                    showscrollcss('wish_product_image');
            }else if(winPos >= $wish_product_variance){
                    showscrollcss('wish_product_variance');
            }
        }
    });

});

//一键生成   obj = price inventory shipping 
function createNum(obj){
    var str = "<div class='modal fade' id='create_modal'><div class='modal-dialog'><div class='modal-content'><div class='modal-header' style='background-color:#364655'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button><h4 class='modal-title' style='color:white;'>自定义参数</h4></div><div class='modal-body'><label for='create_"+obj+"'>请输入要一键生成的数值:</label><input type='text' id='create_"+obj+"'></div><div class='modal-footer'><button type='button' class='btn btn-primary' data-dismiss='modal' onclick='ensureNum(this)'>确定</button></div></div></div>"
    $('.goodsList').after(str);
    $('#create_modal').modal('show');
}




function ensureNum(obj){
    var $num = $(obj).parent().parent().find('input[type="text"]').val();
    var $type = $(obj).parent().parent().find('input[type="text"]').attr('id');
    $type = $type.split('_')[1];    
    $('#goodsList input[name="'+$type+'"]').val($num);
    var parent_sku = $('#wish_product_parentSku');
    OneKeyCreateSku(parent_sku);

}

function OneKeyCreateSku(obj){
    var parent_sku = $(obj).val();
            $('#goodsList tr').each(function(){
                var color = $(this).find('input[name="color"]').val();
                var size = $(this).find('input[name="size"]').val();
                // var price = $(this).find('input[name="price"]').val();
                // var inventory = $(this).find('input[name="inventory"]').val();
                // var shipping = $(this).find('input[name="shipping"]').val();
                // console.log(color+size+price+inventory+shipping);
                sku = createSku(parent_sku,color,size);
                $(this).find('input[name="sku"]').val(sku);
            })
}


//1.保存  2.保存并发布
function save(args){
    var is_post;
    if(args == 1){
        is_post = 1;   // alert(1);
    }else{
        is_post = 2;   // alert(2);
    }
    var site_id = $('.wish_site_id option:selected').val(); 
    if(site_id == '' || site_id == undefined ){
        tips({type:"error",msg:'请选择商品要保存到的店铺',existTime:3000});
        $(window).scrollTop(0);

        $('.wish_site_id').focus();
        return false;
    }

    var name = $('#wish_product_name').val();   
    if(name == undefined || name == ''){
        tips({type:"error",msg:'请填写产品标题!',existTime:3000});
        $(window).scrollTop($('#wish_product_name').offset().top);
        $('#wish_product_name').focus();
        // alert($('#wish_product_name').scrollTop());
        return false;
    }
    var tags = [];
    $('#goods_tags .tag span').each(function(){
        tags.push($(this).html());
    });
    tags = tags.join(',');
    if(tags == undefined || tags == ''){
        $(window).scrollTop($('#wish_product_tags').offset().top);
        $('#wish_product_tags').focus();
        tips({type:"error",msg:'请填写产品标签!',existTime:3000});
        return false;
    }
    var description = $('#wish_product_description').val();
    if(description == undefined || description == ''){
        $(window).scrollTop($('#wish_product_description').offset().top);
        $('#wish_product_description').focus();
        tips({type:"error",msg:'请填写产品描述!',existTime:3000});
        return false;
    }
    var parent_sku = $('#wish_product_parentSku').val();
    if(parent_sku == undefined || parent_sku == ''){
        $(window).scrollTop($('#wish_product_parentSku').offset().top);
        $('#wish_product_parentSku').focus();
        return false;
        tips({type:"error",msg:'请填写产品主SKU!',existTime:3000});
    }else{
        var $skuIsExist = CheckSkuExist();
        if($skuIsExist == false){
            return false;
        }
    }
    var price = $('#wish_product_price').val();
    if(price == undefined || price == ''){
        $(window).scrollTop($('#wish_product_price').offset().top);
        $('#wish_product_price').focus();
        tips({type:"error",msg:'请填写产品的价格!',existTime:3000});
        return false;
    }
    var inventory  = $('#wish_product_count').val();
    if(inventory == undefined || inventory == '' || inventory == '0'){
        $(window).scrollTop($('#wish_product_count').offset().top);
        $('#wish_product_count').focus();
        tips({type:"error",msg:'请填写产品范本的库存!',existTime:3000});
        return false;
    }
    var shipping = $('#wish_product_shipping').val();
    if(shipping == undefined || shipping == ''){
        $(window).scrollTop($('#wish_product_shipping').offset().top);
        $('#wish_product_shipping').focus();
        tips({type:"error",msg:'请填写产品运费!',existTime:3000});
        return false;
    }
    var shipping_time = $('input[name="shipping_time"]:checked').val();
    if(shipping_time == 'other'){
        shipping_time = $('input[name="shipping_short_time"]').val() + '-' + $('input[name="shipping_long_time"]').val();
    }
   if(shipping_time == undefined || shipping == ''){
        $(window).scrollTop($('#wish_product_shipping_time').offset().top);
        tips({type:"error",msg:'请填写产品运输时间!',existTime:3000});
        return false;
   }
   
    var msrp = $('#wish_product_sale_price').val();
    if(msrp == undefined || msrp == ''){
        msrp = 0;
    }

    var brand = $('#wish_product_brand').val();
    var upc = $('#wish_product_upc').val();
    var landing_page_url = $('#wish_product_ladding_page_url').val();
    var extra_image = [];
    var main_image;
    var upindex = 1;
    var main_image_selected = 0;
    $('#image-list .image-item a').each(function(){
        if($(this).hasClass('select_photo')){
            main_image_selected = 1;
        }
    });
    console.log(main_image_selected);
    if(main_image_selected){
        for(var i =1 ; i<=11 ; i++){
            if($('#image-list #image-item-'+i+' .thumbnail').hasClass('select_photo') && ($('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png')){
                // console.log($('#image-list #image-item-'+i+' img').attr('src'));
                main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
            }else{
                if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
                    extra_image['extra_image_'+upindex] = '';
                }else{
                    extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
                }
                upindex += 1;
            }
        }
    }else{
        for(var i=1;i<=11; i++){
            if(i==1 && ($('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png')){
                main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
            }else{
               if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
                    extra_image['extra_image_'+upindex] = '';
                }else{
                    extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
                }
                upindex += 1; 
            }
        }
    }
    
    // if(typeof(main_image) == 'undefined'){
    //     if(extra_image['extra_image_1'] != ''){
    //        main_image = Array.prototype.shift.call(extra_image);
    //     }else{
    //         tips({type:'error',msg:'请上传产品图片!',existTime:3000});
    //     }
    // }
    console.log(extra_image);
    console.log(main_image);
    
    if(main_image == undefined || main_image == ''){
        // alert($(window).scrollTop());
        tips({type:"error",msg:'请上传产品图片!',existTime:3000});
        return false;
    }

    var size_type=$('#mytab a.noCss').attr('rel');
    // console.log(size_type);
    var variance = []; 

    // console.log($('#goodsList tr').length);
    for(var j=0;j<$('#goodsList tr').length; j++){
        variance.push({'color':$('#goodsList input[name="color"]').eq(j).val(),
            'size': $('#goodsList input[name="size"]').eq(j).val(),
            'sku': $('#goodsList input[name="sku"]').eq(j).val(),
            'price': $('#goodsList input[name="price"]').eq(j).val(),
            'inventory': $('#goodsList input[name="inventory"]').eq(j).val(),
            'shipping': $('#goodsList input[name="shipping"]').eq(j).val(),
            'image_url': $('#goodsList img[name="image_url"]').eq(j).attr('src'),
            'enable': 'Y'
        });
    }

    // console.log(variance);
    if(variance == undefined || variance == ''){
        variance.push({'price': price,
            'inventory': inventory,
            'sku': parent_sku,
            'shipping': shipping,
            'enable': 'Y'
        });
    }

    //console.log(variance.length);
    for(var i=0; i<variance.length;i++){
        if(variance[i]['inventory'] >= 10000){
            $('#goodsList input[name="inventory"]').eq(i).val('').focus();
            tips({'type':'error','msg':'库存量不能超过10000',existTime:3000});
            return false;
        }
    }
    // console.log(variance);
    if(!checkvarianceSkuExist()){
        return false;
    }

    // console.log(variance);
    $.showLoading();
    $.ajax({
        type:"post",
        data:{'name':name,
            'tags':tags,
            'site_id':site_id,
            'description':description,
            'parent_sku':parent_sku,
            'shipping':shipping,
            'shipping_time':shipping_time,
            'price': price,
            'inventory': inventory,
            'msrp': msrp,
            'brand': brand,
            'upc': upc,
            'type':2,
            'fanben_id': $('input[name="wish_fanben_id"]').val()==""? 0 : $('input[name="wish_fanben_id"]').val(),
            'opt_method': $('input[name="opt_method"]').val() == ""? "" : $('input[name="opt_method"]').val(),
            'landing_page_url': landing_page_url,
            'size': size_type,
            'main_image':main_image,
            'extra_image_1':extra_image['extra_image_1'],
            'extra_image_2':extra_image['extra_image_2'],
            'extra_image_3':extra_image['extra_image_3'],
            'extra_image_4':extra_image['extra_image_4'],
            'extra_image_5':extra_image['extra_image_5'],
            'extra_image_6':extra_image['extra_image_6'],
            'extra_image_7':extra_image['extra_image_7'],
            'extra_image_8':extra_image['extra_image_8'],
            'extra_image_9':extra_image['extra_image_9'],
            'extra_image_10':extra_image['extra_image_10'],
            'variance': variance
        },
        url:'/listing/wish/save-fan-ben?is_post='+is_post,
        success: function(data){
            $.hideLoading();
            data = eval('('+ data +')');
            console.log(data);
            if(data['success'] == true){
                tips({type:"success",msg:"请在在线商品或者刊登待发布中查看刊登结果!",existTime:3000});
                if(is_post == 1){
                    $.location.href('/listing/wish/wish-list?type=2&lb_status=1',1500);
                }else{
                   $.location.href('/listing/wish-online/wish-product-list',1500);
                }                
            }else{
                tips({type:"error",msg:data['message']});
                if(is_post == 2){
                    $.location.href('/listing/wish/wish-list?type=2&lb_status=4',1500);
                }
            }
        }
    }); 
}



function OnlineSave(){
    var $site_id = $('select[name="site_id"] option:selected').val();
    var $name = $('#wish_product_name').val();
    var $tags = [];
    $('#goods_tags .tag span').each(function(){
        $tags.push($(this).html());
    });
    $tags = $tags.join(',');
    var $description = $('#wish_product_description').val();
    var $parent_sku = $('#wish_product_parentSku').val();
    var $shipping_time = $('input[name="shipping_time"]:checked').val();
    if($shipping_time == 'other'){
        $shipping_time = $('input[name="shipping_short_time"]').val() + '-' + $('input[name="shipping_long_time"]').val();
    }
    var $brand = $('#wish_product_brand').val();
    var $upc = $('#wish_product_upc').val();
    var $landing_page_url = $('#wish_product_ladding_page_url').val();
    var $extra_image = [];
    var $main_image = '';
    var upindex = 1;
    // for(var i =1 ; i<=11 ; i++){
    //     if($('#image-list #image-item-'+i+' .thumbnail').hasClass('select_photo')){
    //         $main_image = $('#image-list #image-item-'+i+' img').attr('src');
    //     }else{
    //         if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
    //             $extra_image['extra_image_'+upindex] = '';
    //         }else{
    //             $extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src');
    //         }
    //         upindex += 1;
    //     }
    // }
    // if(typeof($main_image) == 'undefined'){
    //     for(var i=1 ; i<=10; i++){
    //         if($extra_image['extra_image_'+i] != ''){
    //             $main_image = $extra_image['extra_image_'+i];
    //             for(var j=i;j<10;j++){
    //                 if(j<9){
    //                     $extra_image['extra_image_'+j] = $extra_image['extra_image_'+j+1];
    //                 }else{
    //                     $extra_image['extra_image_'+j] = '';
    //                 }
    //             }
    //             break;
    //         }
    //     }
    // }
    var main_image_selected = 0;
    $('#image-list .image-item a').each(function(){
        if($(this).hasClass('select_photo')){
            main_image_selected = 1;
        }
    });
    if(main_image_selected){
        for(var i =1 ; i<=11 ; i++){
            if($('#image-list #image-item-'+i+' .thumbnail').hasClass('select_photo')  && ($('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png')){

                // console.log($('#image-list #image-item-'+i+' img').attr('src'));
                $main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
            }else{
                if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
                    $extra_image['extra_image_'+upindex] = '';
                }else{
                    $extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
                }
                upindex += 1;
            }
        }
    }else{
        for(var i=1;i<=11; i++){
            if(i==1  && ($('#image-list #image-item-'+i+' img').attr('src') != '/images/batchImagesUploader/no-img.png')){
                $main_image = $('#image-list #image-item-'+i+' img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
            }else{
               if($('#image-list #image-item-'+i).find('img').attr('src') == '/images/batchImagesUploader/no-img.png'){
                    $extra_image['extra_image_'+upindex] = '';
                }else{
                    $extra_image['extra_image_'+upindex] = $('#image-list #image-item-'+i).find('img').attr('src').replace(/\?imageView2\/1\/w\/210\/h\/210$/g,'');
                }
                upindex += 1; 
            }
        }
    }


    if($main_image == undefined || $main_image == ''){
        // alert($(window).scrollTop());
        tips({type:"error",msg:'请上传产品图片!',existTime:3000});
        return false;
    }
    var variance = []; 
    for(var j=0;j<$('#goodsList tr').length; j++){
        variance.push({'color':$('#goodsList input[name="color"]').eq(j).val(),
            'size': $('#goodsList input[name="size"]').eq(j).val(),
            'sku': $('#goodsList input[name="sku"]').eq(j).val(),
            'price': $('#goodsList input[name="price"]').eq(j).val(),
            'inventory': $('#goodsList input[name="inventory"]').eq(j).val(),
            'shipping': $('#goodsList input[name="shipping"]').eq(j).val(),
            'image_url': $('#goodsList img[name="image_url"]').eq(j).attr('src'),
            'enable':$('#goodsList input[name="enable"]').eq(j).val() == "Y" ? "Y":"N"
        });
    }
    // console.log(variance.length);
    for(var i=0; i<variance.length;i++){
        if(variance[i]['inventory'] >= 10000){
            $('#goodsList input[name="inventory"]').eq(i).val('').focus();
            tips({'type':'error','msg':'库存量不能超过10000'});
            return false;
        }
    }
    if(!checkvarianceSkuExist()){
        return false;
    }
    $.showLoading();
    $.ajax({
        type:"post",
        data:{
            'site_id': $site_id,
            'name':$name,
            'tags':$tags,
            'description':$description,
            'parent_sku':$parent_sku,
            'shipping_time':$shipping_time,
            'brand': $brand,
            'upc': $upc,
            'fanben_id': $('input[name="wish_fanben_id"]').val()==""? 0 : $('input[name="wish_fanben_id"]').val(),
            'landing_page_url': $landing_page_url,
            'main_image':$main_image,
            'extra_image_1':$extra_image['extra_image_1'],
            'extra_image_2':$extra_image['extra_image_2'],
            'extra_image_3':$extra_image['extra_image_3'],
            'extra_image_4':$extra_image['extra_image_4'],
            'extra_image_5':$extra_image['extra_image_5'],
            'extra_image_6':$extra_image['extra_image_6'],
            'extra_image_7':$extra_image['extra_image_7'],
            'extra_image_8':$extra_image['extra_image_8'],
            'extra_image_9':$extra_image['extra_image_9'],
            'extra_image_10':$extra_image['extra_image_10'],
            'variance': variance
        },
        url:'/listing/wish/online-save-fan-ben',
        success: function(data){
            $.hideLoading();
            data = eval('('+ data +')');
            console.log(data);
            if(data['success'] == true){
                tips({type:"success",msg:"恭喜你！产品保存成功！",existTime:2000});
                $.location.href('/listing/wish-online/wish-product-list',1500);
            }else{
                tips({type:"error",msg:data['message']});
            }
        }
    });  
}

function CheckSkuExist(){
   var parent_sku = $('#wish_product_parentSku').val();
   var fanben_id = $('input[name="wish_fanben_id"]').val();
   var site_id = $('.wish_site_id option:selected').val();
   var isExist = true;
   if(site_id == ''){
        tips({type:'error',msg:'请选择商品要保存到的店铺'});
        return false;
   }
   if(parent_sku == ''){
        $('.sku_tips').html('<span style="color:red;line-height:40px;">请填写产品主SKU</span>');
        return false;
   }
    $.ajax({
        async: false,
        type:'post',
        data:'parent_sku='+parent_sku+'&fanben_id='+fanben_id+'&site_id='+site_id,
        url: '/listing/wish/ajax-sku-exist',
        success:function(data,json){
           console.log(data);
           if(data['success'] == false){
                tips({type:'error',msg:data['message']});
                isExist = false;
           } 
        }
    });
    return isExist;
}


function checkvarianceSkuExist(){
    var site_id = $('.wish_site_id option:selected').val();
    var fanben_id = $('input[name="wish_fanben_id"]').val();   
    var isExist = true;
    if(site_id == ''){
        tips({type:'error',msg:'请选择商品要保存到的店铺'});
        return false;
    };
    var varianceSku = $('#goodsList input[name="sku"]:first').val()
    // $('#goodsList input[name="sku"]').each(function(){
    //         varianceSku.push($(this).val());
    // });
    console.log(varianceSku);
    $.ajax({
        async: false,
        type:'post',
        data: 'variance_sku='+varianceSku+'&site_id='+site_id+'&fanben_id='+fanben_id,
        url :'/listing/wish/ajax-variance-sku-exist',
        success:function(data){
             data =eval("("+ data +")");
             if(data['success'] == false){
                tips({type:'error',msg:data['message']});
                isExist = false;
             }
        }
    });
    return isExist;

}

function cite(){
    var site_id = [];
    var store_name = [];
    $('.wish_site_id option').each(function(){
        if($(this).val() != 0){
           site_id.push($(this).val());
           store_name.push($(this).html());
        }
    });
    var str;
    for(var i=0;i<site_id.length;i++){
        if(i==0){
            str += '<option value="'+site_id[i]+'" selected>'+store_name[i]+'</option>';
        }else{
            str += '<option value="'+site_id[i]+'">'+store_name[i]+'</option>';
        }
    }
    // $('.wish_id_list .wish_cite_site_id').find('option').each(function(){
    //     $(this).remove();
    // });
    // console.log($('.wish_id_list .wish_cite_site_id').find('option').length);
    $('.wish_cite_site_id').html('');
    $('.wish_cite_site_id').append(str);
    AjaxCite();
    $('#cite_modal').modal('show');
    $('#cite_modal .modal-content').width(130*7);
    $('.cite_goods_box').css('min-height','400px');
    $('#cite_modal').scrollTop(0);
}

function AjaxCite(){
    $site_id = $(' .cite_goods_box .wish_cite_site_id option:selected').val();
    $select_status = $('.cite_goods_menu select option:selected').val();
    $search_key = $('input[name="search_key"]').val();
    $.ajax({
        type:'get',
        data:'site_id='+$site_id+'&select_status='+$select_status+'&search_key='+$search_key,
        url:'cite-fan-ben',
        success:function(data){
                $(document).attr('overflow','hidden');
                $('.cite_goods_box .container .cite_goods_list').remove();

                $('.cite_goods_box .container').append(data); 
                // $('#cite_goods_list').scrollTop(0);
                // if($('.modal-content').height() < $(window).height()){
                    
                // }
        }
    });
} 

$('.wish_cite_site_id').change(function(){
    AjaxCite();  
});

$('.cite_goods_box input[name="search_key"]').keyup(function(event){
      event = event || window.event;
        //console.log(event.which);
        if(event.which == "13") {  //判断是不是回车键
            AjaxCite();
    }
});

//填充引用商品信息
function fillCiteGoodInfo(){
    $tr =$('input[name="cite_goods_id"]:checked').parents('tr');
    $id = $tr.find('input[name="cite_goods_id"]').val();
    // window.location.href = '/listing/wish/copy-fan-ben?id='+$id+'&type=2&lb_status=1';
    $.ajax({
        type:'post',
        data:"id="+$id,
        url:"/listing/wish/cite-a-fan-ben",
        success:function(data){
            data = eval("("+ data +")");
            // console.log(data);
            $('#wish_product_name').val(data['fanben']['name']);
            $('#wish_product_tags').val(data['fanben']['tags']).focus().blur();
            $('#wish_product_description').val(data['fanben']['description']);
            $('#wish_product_parentSku').val(data['fanben']['parent_sku']);
            $('#wish_product_price').val(data['fanben']['price']);
            $('#wish_product_count').val(data['fanben']['inventory']);
            $('#wish_product_shipping').val(data['fanben']['shipping']);
            $('#wish_product_shipping_time input[name="shipping_time"]').each(function(){
                if($(this).val() == data['fanben']['shipping_time']){
                    $(this).attr('checked','true');     
                }
            });            
            if($('#wish_product_shipping_time input[name="shipping_time"]:checked').length == 0){
                // $('input[name="shipping_time"][value="other"]').click();
                var shipping_time = data['fanben']['shipping_time'].split('-');
                var short_time = shipping_time[0];
                var long_time = shipping_time[1];
                $('input[name="shipping_time"][value="other"]').attr('checked','true');
                $('input[name="shipping_short_time"]').val(short_time).removeAttr('disabled');
                $('input[name="shipping_long_time"]').val(long_time).removeAttr('disabled');
            } 
            $('#wish_product_sale_price').val(data['fanben']['msrp']);
            $('#wish_product_brand').val(data['fanben']['brand']);
            $('#wish_product_upc').val(data['fanben']['upc']);
            $('#wish_product_ladding_page_url').val(data['fanben']['lading_page_url']);
            for(var j=1; j<=10;j++){
                if(data['fanben']['extra_image_'+j] != '' && data['fanben']['extra_image_'+j] != null){
                    $('.thumbnail img').eq(j-1).attr('src',data['fanben']['extra_image_'+j]);
                    $('.thumbnail').eq(j-1).append('<button type="button" class="lnk-del-img close"><span aria-hidden="true">&times;</span></button>');
                    $('.thumbnail').eq(j-1).parents('.image-item').attr('upload-index',j);
                    var $row ={'thumbnail':data['fanben']['extra_image_'+j],'original':data['fanben']['extra_image_'+j]};
                    $('.img-uploader').addImageData($row,j);
                }
            }
            var str;
            for(var i=0;i<data['variance'].length;i++){
                if(i==0){
                   $('#wish_product_price').val(data['variance'][i]['price']); 
                   $('#wish_product_count').val(data['variance'][i]['inventory']);
                   $('#wish_product_shipping').val(data['variance'][i]['shipping']);
                }
                str += '<tr>';
                str += '<td><input class="form-control" type="text" name="color" value="'+ data['variance'][i]['color']+'" style="width:50px;"></td>';
                str += '<td><input class="form-control" type="text" name="size" value="'+ data['variance'][i]['size'] +'" style="width:50px;"></td>';
                str += '<td><input class="form-control" type="text" name="sku" value="'+ data['variance'][i]['sku'] +'"></td>';
                str += '<td><input class="form-control" type="text" name="price" value="'+ data['variance'][i]['price']+'"></td>';
                str += '<td><input class="form-control" type="text" name="inventory" value="'+ data['variance'][i]['inventory']+'"></td>';
                str += '<td><input class="form-control" type="text" name="shipping" value="'+ data['variance'][i]['shipping']+'"></td>';
                if(!data['variance'][i]['image_url']){
                    str += '<td style="text-align: center" onclick="selectpic(this)"><span class="glyphicon glyphicon-plus"></span></td>';
                }else{
                    str += '<td><img name="image_url" src="'+data['variance'][i]['image_url']+'" style="width:50px;"/></td>';
                }
                if(!data['variance'][i]['addinfo'] && data['variance'][i]['addinfo'] != ""){
                    // console.log(data['variance'][i]['addinfo']);
                    data['variance'][i]['addinfo'] = $.parseJSON(data['variance'][i]['addinfo']);

                    // console.log(data['variance'][i]['addinfo']['shipping_time']);
                    data['fanben']['shipping_time'] = data['variance'][i]['addinfo']['shipping_time'];
                }
                str += '<td style="text-align:center;">'+ removeBtn +'</td>';
                str += '</tr>';
            }
            $('#wish_product_shipping_time input[name="shipping_time"]').each(function(){
                if($(this).val() == data['fanben']['shipping_time']){
                    $(this).attr('checked','true');     
                }
            });           
            $('#goodsList').append(str);
            ajustGoodsListHeight();
        }
    });  
    // $img = $tr.find('img').attr('src');
    // $name = $tr.find('td').eq(2).html();
    // $parent_sku =$tr.find('td').eq(3).html();
    // $('#wish_product_name').val($name);
    // $('#wish_product_parentSku').val($parent_sku);
    // $length = $('.thumbnail').parents('.image-item[upload-index]').length;
    // // alert($length);
    // $('.thumbnail img').eq($length).attr('src',$img);
    // $('.thumbnail').eq($length).append('<button type="button" class="lnk-del-img close"><span aria-hidden="true">&times;</span></button>');
    // $('.thumbnail').eq($length).parents('.image-item').attr('upload-index',$length+1);
    // var $row ={'thumbnail':$img,'original':$img}
    // // console.log($row);
    // $('.img-uploader').addImageData($row,$length+1);

}
var isClick = false;

function goto(str){
    var winPos = $(window).scrollTop();
    var $wish_product_baseinfo = $('.wish_product_baseinfo').offset().top;
    var $wish_product_image = $('.wish_product_image').offset().top;
    var $wish_product_variance = $('.wish_product_variance').offset().top;
    // console.log($wish_product_image);
    // console.log($wish_product_variance);
    // console.log($wish_product_baseinfo);
    // console.log(str);
    isClick = true;
    $('html,body').animate({scrollTop:$('.'+str).offset().top}, 800,function(){
        isClick =false;
    });
     gotowhere = str;
    showscrollcss(str);
}



function showscrollcss(str){
    var eqtmp = new Array;
    eqtmp['wish_product_baseinfo'] =  0;
    eqtmp['wish_product_image'] = 1;
    eqtmp['wish_product_variance'] = 2;
    // console.log(eqtmp[str]);
    // console.log($('.left-panel p a').eq(eqtmp[str]).html());    
    $('.left-panel p a').css('color','#333');
    $('.left-panel p a').eq(eqtmp[str]).css('color','#FF9A00');
}




