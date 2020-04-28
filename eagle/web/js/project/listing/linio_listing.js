/**
 * 
 */
String.prototype.formatOther = function(args) { 
	if (arguments.length>0) { 
		var result = this; 
		if (arguments.length == 1 && typeof (args) == "object") { 
			for (var key in args) { 
				var reg=new RegExp ("(#{"+key+"})","g"); 
				result = result.replace(reg, args[key]); 
			} 
		} else { 
			for (var i = 0; i < arguments.length; i++) { 
				if(arguments[i]==undefined) 
				{ 
					return ""; 
				} 
				else 
				{ 
					var reg=new RegExp ("(#{["+i+"]})","g"); 
					result = result.replace(reg, arguments[i]); 
				} 
			} 
		} 
		return result; 
	}else { 
		return this; 
	} 
};
Date.prototype.format = function (fmt) { //author: meizz 
    var o = {
        "M+": this.getMonth() + 1, //月份 
        "d+": this.getDate(), //日 
        "h+": this.getHours(), //小时 
        "m+": this.getMinutes(), //分 
        "s+": this.getSeconds(), //秒 
        "q+": Math.floor((this.getMonth() + 3) / 3), //季度 
        "S": this.getMilliseconds() //毫秒 
    };
    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
    if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}

//页面滚动到指定位置
function goto(str){
	var winPos = $(window).scrollTop();
	var descriptionInfo = $('#description-info').offset().top;
//	var descriptionInfo2 = $('#description-info').offset().top;
	var shippingInfo = $('#shipping-info').offset().top-20;
	if(str == 'shipping-info'){
		if(winPos > descriptionInfo && winPos < shippingInfo){
			showscrollcss('shipping-info');
		}else{
			linioListing.str = '';
			linioListing.str = str;
			$('html,body').animate({scrollTop:$('#'+str).offset().top}, 800);
		}
	}else if(str == 'warranty-info'){
		if(winPos > descriptionInfo && winPos < shippingInfo){
			showscrollcss('warranty-info');
		}else{
			linioListing.str = '';
			linioListing.str = str;
			$('html,body').animate({scrollTop:$('#'+str).offset().top}, 800);
		}
	}else{
		linioListing.str = '';
		linioListing.str = str;
		$('html,body').animate({scrollTop:$('#'+str).offset().top}, 800);
	}
	
	
}

//处理页面右侧快捷栏的css显示
function showscrollcss(str){
	var _eqtmp = new Array;
	_eqtmp['store-info'] = 0;
	_eqtmp['base-info'] = 1;
	_eqtmp['variant-info'] = 2;
	_eqtmp['image-info'] = 3;
	_eqtmp['description-info'] = 4;
	_eqtmp['shipping-info'] = 5;
	_eqtmp['warranty-info'] = 6;
	//全部为默认黑
	$('.left_pannel p a').css('color','#333');
	$('.left_pannel p a').eq(_eqtmp[str]).css('color','rgb(165,202,246)');
	
	return false;
}

if (typeof linioListing === 'undefined')  linioListing = new Object();

linioListing ={
		existingImages:[],
		initReference:false,
		str:'',
		info_id:['store-info','base-info','variant-info','image-info','description-info','shipping-info','warranty-info'],
		init:function(){
			var attrStr = $("#productDataStr").val();
			var skuArr = $("#skus").val();

//			$(".categoryModalShow").onclick(function(){
//				linioListing.selectCategory();
//			});
			//一级目录下子项点击事件
			$(document).on('click','.categoryDiv',function(){
				linioListing.categoryClick(this);
			});
			//隐藏相关信息
			$(document).on('click','.glyphicon-chevron-up',function(){
				linioListing.hide(this);
			});
			//显示相关信息
			$(document).on('click','.glyphicon-chevron-down',function(){
				linioListing.show(this);
			});
			//skuRemove
			$(document).on('click','a[cid="remove"]',function(){
				$(this).closest('tr').remove();
			});
			
			//sku批量修改
			//批量弹层html生成
			$(document).on('click','a.lzdSkuBatchEdit',function(){
				linioListing.skuBatchEdit(this);
			});
			//弹层radio切换
			$(document).on('click','input[name="numRadio"]',function(){
				var val = $(this).attr('data-val');
				if(val == 1){
					$("#num2").attr('disabled',true);
					$("#num1").attr('disabled',false);
				}else if (val == 2){
					$("#num2").attr('disabled',false);
					$("#num1").attr('disabled',true);
				}
				
			});
			$(document).on('click','input[name="priceEditType"]',function(){
				$(this).closest('div.modal-body').find('input[type="text"],select').attr('disabled',true);
				if(this.checked){
					$(this).closest('div').find('input[type="text"],select').attr('disabled',false);
				};
			});
			
			//时间插件
			$(document).on('mouseenter', ".form-control.Wdate", function() {
				$(this).datepicker({
					dateFormat: 'yy-mm-dd'// 2015-11-11
				});
			})
			
			$(document).on('change','select[data-names="retailPrice"]',function(){
				var num = $(this).val();
				if (num == 1)
				{
					$('span.danwei').html('');
				};
				if (num == 2)
				{
					$('span.danwei').html('%');
				}
			});
			//产品标题计数
			$(document).on('keyup','div.lzdProductTitle input',function(){
				var num = $(this).val().length;
				$(this).closest('div.lzdProductTitle').find('span.unm').html(num);
			});
			
			//标题首大写转换
//			$('[data-toggle="tooltip"]').tooltip();
			$('.productTitTextSize').click(function(){
				var str = $(this).closest('div').find('input[type="text"]').val();
				var newStr = str.replace(/\s[a-z]/g,function($1){return $1.toLocaleUpperCase()}).replace(/^[a-z]/,function($1){return $1.toLocaleUpperCase()}).replace(/\sOr[^a-zA-Z]|\sAnd[^a-zA-Z]|\sOf[^a-zA-Z]|\sAbout[^a-zA-Z]|\sFor[^a-zA-Z]|\sWith[^a-zA-Z]|\sOn[^a-zA-Z]/g,function($1){return $1.toLowerCase()});
				$(this).closest('div').find('input[type="text"]').val(newStr);
			});
			
			//批量弹层确定按钮
			$(document).on('click','button.lzdSkuBatchEdit',function(){
				linioListing.skuBatchEditConfirm(this);
			});
			//各站点checkbox
			$(document).on('click','.label-check',function(){
				linioListing.labelCheck(this);
			});
			
			// 初始化 品牌autocomplete
			
			if($("#copyType").val() == "singleCopy"){//单产品初始品牌
				$('[cid="Brand"]>.secondTd>input').autocomplete({
					source: function(request,response){
						$.ajax({
						   type: "post",
						   url: "/listing/linio-listing/get-brands",
						   data: { lazada_uid : $("#lazadaUid").val(),name : request.term},
						   dataType:'json',
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
							
							if(data.code == 200){
								data.data.sort(by(request.term));
								response(data.data);
							}else{
								console.log(data.message);
							}
						});
					}

				});
			}
//			else if($("#copyType").val() == "mutiCopy"){//多产品初始品牌
//				$('[cid="Brand"]>.secondTd>input').each(function(){
//					$(this).autocomplete({
//						source: function(request,response){
//							$.ajax({
//							   type: "post",
//							   url: "/listing/linio-listing/get-brands",
//							   data: { lazada_uid : $("#lazadaUid").val(),name : request.term},
//							   dataType:'json',
//							}).done(function(data){
//								function cmp(a, b) {
//								    if (a > b) { return 1; }
//								    if (a < b) { return -1; }
//								    return 0;
//										}
//								function by(keyword) {
//									keyword = keyword.toLowerCase();
//								    return function(a, b) {
//								    	a = a.toLowerCase();
//								    	b = b.toLowerCase();
//								        var i = a.indexOf(keyword);
//								        var j = b.indexOf(keyword);
//								        if (i === 0) {
//								            if (j === 0) {
//								                return cmp(a, b);
//								            }
//								            else {
//								                return -1;
//								            }
//								        }
//								        else {
//								            if (j === 0) {
//								                return 1;
//								            }
//								            return cmp(a, b);
//								        }
//								    };
//								};
//								
//								if(data.code == 200){
//									data.data.sort(by(request.term));
//									response(data.data);
//								}else{
//									console.log(data.message);
//								}
//							});
//						}
//
//					});
//				});
//			}
			
			
			//加载页面编辑器
			KindEditor.ready(function(K) {
		            window.editor = K.create('',linioListing.dataSet.kdeOption);
		    });
			
			linioListing.pageBeginning();
			linioListing.mutPageBeginning();
			linioListing.dataSet.temporaryData.submitData = JSON.parse(attrStr || '{}');
			linioListing.dataSet.temporaryData.skuData = JSON.parse(skuArr || '[]');
			for(var i in linioListing.info_id){
				if(typeof linioListing.dataSet.temporaryData.submitData[linioListing.info_id[i]] == 'undefined'
					|| !linioListing.dataSet.temporaryData.submitData[linioListing.info_id[i]]
					|| linioListing.dataSet.temporaryData.submitData[linioListing.info_id[i]].length == 0){
					linioListing.dataSet.temporaryData.submitData[linioListing.info_id[i]] = JSON.parse('{}');
				}
			}
			if(attrStr == ''){
				linioListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
				linioListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
				linioListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
				linioListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
				linioListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
				linioListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
				linioListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
			}else{
				if($("#copyType").val() == "singleCopy"){//单个产复制
					linioListing.selectShop();//初始化商铺 
//					linioListing.pageBeginning();
					if(linioListing.initReference == true){// 引用商品数据初始化 与编辑/复制页面不同
						linioListing.lzdSkuBorn();
						linioListing.pushLinioData(linioListing.dataSet.temporaryData.submitData,linioListing.dataSet.temporaryData.skuData);
					}else{
						var categoryIdList = JSON.parse($('#productCategoryIds').val() || '[]');
						if(categoryIdList.length > 0)
						{
							var categoryId = categoryIdList[0];
							linioListing.initCategory(categoryIdList); //回填目录
							linioListing.initEditProduct(categoryId);  // 回填信息
						}else{
							bootbox.alert("选择目录初始化失败！");
							return ;
						};
					}
				}else if($("#copyType").val() == "mutiCopy"){//多站点回填
					if($("#linioId").val()==''){
						bootbox.alert("选择帐号初始化失败！");
						return ;
					}else{
						var linio_shop_id = $("#linioId").val();
//						$('input[data-name="shop_'+linio_shop_id+'"]').prop("checked",true);
						$('input[data-name="shop_'+linio_shop_id+'"]').click();
						linioListing.selectShop(linio_shop_id);//初始化商铺 
//						linioListing.pageBeginning();
						if(linioListing.initReference == true){// 引用商品数据初始化 与编辑/复制页面不同
							linioListing.lzdSkuBorn();
							linioListing.pushLinioData(linioListing.dataSet.temporaryData.submitData,linioListing.dataSet.temporaryData.skuData);
						}else{
							var categoryIdList = JSON.parse($('#productCategoryIds').val() || '[]');
							if(categoryIdList.length > 0)
							{
								var categoryId = categoryIdList[0];
								linioListing.initCategory(categoryIdList,linio_shop_id); //回填目录
								linioListing.mutinitEditProduct(categoryId,linio_shop_id,'firstPush');  // 回填信息
							}else{
								bootbox.alert("选择目录初始化失败！");
								return ;
							};
						}
					}
					
				}
				
			}
//			linioListing.initPhoto();
//			linioListing.selectedClik();
//			linioListing.initCategory();//初始化目录
//			linioListing.selectHistoryCategory();
			
			//滚动监听快捷
			$(window).scroll(function(event){
				//获取每个监听节点的高度
				var winPos = $(window).scrollTop();
				var storeInfo = $('#store-info').offset().top-20;
				var baseInfo = $('#base-info').offset().top-20;
				var variantInfo = $('#variant-info').offset().top-20;
				var imageInfo = $('#image-info').offset().top-20;
				var descriptionInfo = $('#description-info').offset().top-20;
				var shippingInfo = $('#shipping-info').offset().top-20;
				var warrantyInfo = $('#warranty-info').offset().top-20;
				
				if(winPos > storeInfo && winPos < baseInfo){
					showscrollcss('store-info');
				}else if(winPos > baseInfo && winPos < variantInfo){
					showscrollcss('base-info');
				}else if(winPos > variantInfo && winPos < imageInfo){
					showscrollcss('variant-info');
				}else if(winPos > imageInfo && winPos < descriptionInfo){
					showscrollcss('image-info');
				}else if(winPos > descriptionInfo && winPos < shippingInfo){
					if(linioListing.str=='shipping-info'){
						showscrollcss('shipping-info');
					}else if(linioListing.str=='warranty-info'){
						showscrollcss('warranty-info');
					}else{
						showscrollcss('description-info');
					}
				}else if(winPos > shippingInfo && winPos < warrantyInfo){
					showscrollcss('shipping-info');
				}else if(winPos > warrantyInfo){
					showscrollcss('warranty-info');
				}
			});
			
		},
		
		//类目属性展示
		dataSet : {
			brands:[],
			temporaryData:{
				submitData:{},
				skuData:[]
			},
			lzdAttrTit_1:'<tr cid="#{FeedName}" name="#{Label}" attrType="#{attrType}" isMust="#{isMust}"><td class="firstTd vAlignTop">',
			isMust:'<span class="fRed">*</span>',
			lzdAttrTit_2:'#{FeedName}:</td><td class="secondTd" cid="#{FeedName}Content" >',
			lzdAttrEnd:'</td></tr>',
			input:'<input type="text" class="form-control" value="" />',
			input_2:'<input type="text" class="form-control" value="" placeholder="..."/>',
			select:'<select><option>请选择</option>#{optionStr}</select>',
			skuSelect:'<select class="eagle-form-control" style="height:30px;width:125px;"><option>请选择</option>#{optionStr}</select>',
			checkbox:'<label><input type="checkbox" value="#{Name}" data-val="#{GlobalIdentifier}"/> #{Name}</Label>',
			kindeditor:'<div class="mBottom10" data-name="kdeOutDiv" cid="#{FeedName}">'+
					 '<textarea id="#{FeedName}" name="content" style="width:100%;height:100%;"></textarea>'+
					 '</div>'+
					 '<div id="#{FeedName}CacheDiv" style="display:none;">'+
					 '</div>',
			option:'<option value="#{Name}" data-val="#{GlobalIdentifier}">#{Name}</option>',
			kindeditorId:[],
			mutkindeditorId:[],
			otherAttr:["Color","ProductionCountry","ConditionType","Content","ProductLine","Season"],
			mutotherAttr:["ProductionCountry","ConditionType","Content","ProductLine","Season"],//多站点发布
			mutOutMustAtrr:["Name","Description","PackageWeight"],//多站点发布
			transportAttr:["DeliveryTimeSupplier","EligibleFreeShipping"],
			shippingTime:["MinDeliveryTime","MaxDeliveryTime"],
			packingSize:["PackageWidth","PackageLength","PackageHeight"],
			commonShowAttr:["Color","Name","ShortDescription","TaxClass","ProductMeasures","Brand","Model","PackageWeight","Description","ProductWeight","PackageContent"],
			commonAttr:["Video","NameMs","NameEn","ColorFamily","ProductWarranty","SupplierWarrantyMonths","DescriptionMs","DescriptionEn","WarrantyType","Warranty","ReturnPolicy","BuyerProtectionDetailsTxt","ManufacturerTxt"],
			mustShow:["SupplierWarrantyMonths","ProductWarranty"],
			isShowAttr:[],
			mutisShowAttr:[],
			// Gender,Note,ProductIdOld,ShipmentType为lazada有linio 没有. Certifications,MaterialFamily linio 为muti_option属性，但option里面没有选项，导致这个属性没得填
			dontShowAttr:['Gender','Note','ProductIdOld','ShipmentType','PrimaryCategory','ParentSku','TaxClass','Categories','SellerSku','ProductGroup','ProductId','Quantity','Price','SalePrice','SaleStartDate','SaleEndDate','BrowseNodes','PublishedDate','MaterialFamily','Certifications'],
			kdeOption:{
				items:[
					'bold','italic', 'underline','strikethrough','|', 'forecolor', 'hilitecolor', '|', 'justifyleft', 'justifycenter','justifyright','justifyfull','|', 'insertunorderedlist', 'insertorderedlist', '|', 'outdent', 'indent', '|', 'subscript', 'superscript', '|','selectall', 'removeformat', '|','undo', 'redo','/',
					'fontname','fontsize', 'formatblock','|','cut','copy', 'paste','plainpaste','wordpaste','|','link','unlink','|','moreImage','|'/*,'lazadaImgSpace','|'*/,'fullscreen','source'
				],                                           //功能按钮
				width:'100%',
				height:'120px',
				themeType:'default',                         //界面风格,可设置”default”、”simple”，指定simple时需要引入simple.css
				langType:'zh_CN',                            //按钮提示语言（en为英语）
				newlineTag:'br',                             //设置回车换行标签，“p” “br”
				dialogAlignType:'page',                      //设置弹出框(dialog)的对齐类型，指定page时按当前页面居中，指定空时按编辑器居中
				shadowMode:'true',                           //true时弹出层(dialog)显示阴影
				zIndex:'1039',                               //指定弹出层的基准z-index,默认值: 1040 ，覆盖了 kindeditorEdit.js里面的设置
				useContextmenu:'false',                       //true时使用右键菜单，false时屏蔽右键菜单
				colorTable:[								 //指定取色器里的颜色
					['#E53333', '#E56600', '#FF9900', '#64451D', '#DFC5A4', '#FFE500'],
					['#009900', '#006600', '#99BB00', '#B8D100', '#60D978', '#00D5FF'],
					['#337FE5', '#003399', '#4C33E5', '#9933E5', '#CC33E5', '#EE33EE'],
					['#FFFFFF', '#CCCCCC', '#999999', '#666666', '#333333', '#000000']
				],
				filterMode:false,
				cssData:'kse\\:widget {display:block;width:120px;height:120px;background:url(http://b.hiphotos.baidu.com/image/pic/item/e4dde71190ef76c666af095f9e16fdfaaf516741.jpg);}'
			},
			descImageAlignleft:'style="display: inline; float: left;"',
			descImageAlignright:'style="display: inline; float: right;"',
			descImageAligncenter:'style="clear: both; display: block; margin:auto;"',
			lzdSkuArr:[],
			isMustAttrArr:[],
			lzdSkuObjArr:{},
			mutlzdSkuArr:[],
			mutisMustAttrArr:[],
			mutlzdSkuObjArr:{},
			spanCache:[],
			spanDiffCache:[],
			lzdSkuTrStar:'<tr id="0">',
			lzdSkuTrEnd:'</tr>',
			lzdSkuTh_1:'<th>#{FeedName}<span cid="variationEditSpan"><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="variation">一键生成</a>)</span></th>',
			mutlzdSkuTh_1:'<th>#{FeedName}<span cid="variationEditSpan"></span></th>',
			lzdSkuTh_2:'<th class="bgTd">SKU<span class="fRed">*</span><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="sku">一键生成</a>)</th>'+
					   '<th class="bgTd">EAN/UPC/ISBN<span class="fRed">*</span></th>'+
					   '<th class="smTd">库存<span class="fRed">*</span><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="quantity">修改</a>)</th>'+
					   '<th class="smTd">价格<span class="fRed">*</span><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="price">修改</a>)</th>'+
					   '<th class="smTd">促销价<br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="salePrice">修改</a>)</th>'+
					   '<th colspan="3">促销时间<br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="promotionTime">修改</a>)</th>'+
					   '<th style="min-width:40px;">操作</th>',
			lzdSkuTd_1:'<td  style="width:90px;" data-id="sku" data-name="variation" data-type="#{attrType}">#{showHtml}</td>',
			lzdSkuTd_2:'<td data-name="sellerSku"><input type="text" class="form-control" name="" value="" placeholder=""/></td>'+
					   '<td data-name="productGroup" name="EAN"><input type="text" class="form-control" name="" value="" placeholder=""/></td>'+
					   '<td data-name="quantity" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="linioListing.replaceNumber(this);"/></td>'+
					   '<td data-name="price" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="linioListing.replaceFloat(this);"/></td>'+
					   '<td data-name="salePrice" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="linioListing.replaceFloat(this);"/></td>'+
					   '<td class="borderNo" style="width:90px;" data-name="saleStartDate"><input type="text" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="开始时间"/></td>'+
					   '<td class="text-center borderNo" data-name="no">-</td>'+
					   '<td class="borderNo" style="width:90px;" data-name="saleEndDate"><input type="text" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="结束时间"/></td>'+
					   '<td class="borderNo" data-name="no"><a href="javascript:;" cid="remove">移除</a></td>',
		},
//		test:function(){
//			var category_array = new Array();
//			$('#category-info span[data-level]').each(function(){
//				if($(this).html() != ""){
//					var la = $(this).find("span").eq(0).attr("id");
//					category_array.push(la);
//				}
//			});
//			bootbox.alert(category_array);
//		},
		list_init:function(){
			//检查checkbox
			$(document).on('click','#chk_all',function(){
				linioListing.checkAll(this);
			});
			$(document).on('click','input[name="parent_chk"]',function(){
				linioListing.check(this);
			});
			//变参商品选择
			$(document).on('click','input[name="productcheck"]',function(){
				linioListing.variationCheck(this);
			});
			//展示所有商品
			$(document).on('click','.product_all_show',function(){
				linioListing.productAllShow(this);
			});
			//展开显示
			$(document).on('click','.product_show',function(){
				linioListing.productShow(this);
			});
			$(document).on('mouseenter', ".eagle-form-control.Wdate", function() {
				$(this).datepicker({
					dateFormat: 'yy-mm-dd'// 2015-11-11
				});
			});
			if($('#search_status').val() == "search"){
				$('.product_all_show').click();
			};
//			$('input[name="parent_chk"]').each(function(){
//				linioListing.check(this);
//			});
			//搜索键
//			$('#search').click(function(){
//				if($('select[name="condition"]').val()==''){
//				    bootbox.alert('请选择搜索条件');
//					}
//			});
		},
		//各站点checkbox
		labelCheck:function(obj){
			var label_array = [];
			$('input[class="label-check"]').each(function(){
				if($(this).prop("checked") == true && $(this).val() != $(obj).val()){
					label_array.push($(this).data("site"));
				}
			});
			if($.inArray($(obj).data("site"),label_array) == -1){
				var station_no = $(obj).val();
				if($(obj).prop('checked')){
					var station_html = '<div id="'+station_no+'-store-info" data-name="'+$(obj).data("site")+'_shop" class="panel panel-default form-horizontal form-bordered min common-margin search-info">'+
						'<div class="panel-heading">'+
							'<div style="display: none;">'+
								'<input type="hidden" id="'+station_no+'_productId" value="0">'+
								'<input type="hidden" id="'+station_no+'_productCategoryIds" value="">'+
								'<input type="hidden" id="'+station_no+'_skus" value="" name="'+station_no+'_skus">'+
				                '<input type="hidden" id="'+station_no+'_productDataStr" value="" name="'+station_no+'_productDataStr">'+
				                '<input type="hidden" id="'+station_no+'_categoryId" name="'+station_no+'_categoryId" value="">'+
							'</div>'+
							'<h3 class="panel-title"><i class="ico-file4 mr5"></i>'+$(obj).data("site")+'站点<span class="glyphicon glyphicon-chevron-up"></span></h3>'+
						'</div>'+
						'<div class="panel-body show">'+
						'<table>'+
							'<tbody>'+
								'<tr>'+
									'<td class="firstTd">'+
										'<span class="fRed">*</span>'+
										'产品目录:'+
									'</td>'+
									'<td class="secondTd">'+
									'<select id="'+station_no+'_categoryHistoryId" class="form-control" name="'+station_no+'_categoryHistoryId" onchange="linioListing.selectHistoryCategory(this);" style="display:inline-block;width: 720px;">'+
				                        '<option value="">---- 请选择产品目录 ----</option>'+
				                    '</select>'+
				                        '<button class="btn btn-primary categoryModalShow" type="button" data-names="treeSelectBtn" id="fullCid" data-id="" onclick="linioListing.selectCategory('+station_no+')">选择产品目录 </button>'+
									'</td>'+
								'</tr>'+
								'<tr>'+
								    '<td class="firstTd"></td>'+
									'<td class="secondTd">'+
									    '<span id="'+station_no+'_select_info" style="color:red;"></span>'+
									'</td>'+
								'</tr>'+
								'<tr>'+
									'<td class="firstTd"></td>'+
									'<td id="'+station_no+'_category-info" class="secondTd category">'+
									    '<span class="'+station_no+'_category">未选择分类</span>'+
									'</td>'+
								'</tr>'+
								'<tr cid="Brand" attrtype="input" ismust="1" name="Brand" style="display: none;">'+
									'<td class="firstTd">'+
										'<span class="fRed">*</span>'+
										'Brand:'+
									'</td>'+
									'<td class="secondTd">'+
										'<input class="labelIpt ui-autocomplete-input form-control" type="text" id="labelIpt" value="" autocomplete="off">'+
									'</td>'+
								'</tr>'+
								'<tr cid="ColorFamily" attrtype="checkbox" ismust="0" style="display: none;">'+
									'<td class="firstTd vAlignTop">主颜色:</td>'+
									'<td class="secondTd"></td>'+
								'</tr>'+
								'<tr cid="TaxClass" attrtype="select" ismust="1" name="Taxes" style="display: none;">'+
									'<td class="firstTd">'+
										'<span class="fRed">*</span>'+
										'Taxes:'+
									'</td>'+
									'<td class="secondTd">'+
										'<select  class="form-control">'+
											'<option>请选择</option>'+
										'</select>'+
									'</td>'+
								'</tr>'+
								'<tr>'+
									'<td colspan="2" class="secondTd">'+
										'<div class="divModular ">'+
											'<table cid="lzdAttrShow"></table>'+
										'</div>'+
									'</td>'+
								'</tr>'+
								'<tr>'+
									'<td colspan="2" class="secondTd">'+
										'<div class="divModular ">'+
											'<table cid="lzdProductAttr"></table>'+
										'</div>'+
									'</td>'+
								'</tr>'+
								'<tr cid="shippingTime" style="display: none;">'+
								'<td class="firstTd"><span class="fRed">*</span>运输天数:</td>'+
									'<td class="secondTd">'+
										'<input cid="MinDeliveryTime" ismust="1" type="text" id=""name="最小天数" value="" placeholder="最小天数" onkeyup="linioListing.replaceNumber(this);">'+
										'&nbsp;&nbsp;-&nbsp;&nbsp;<input cid="MaxDeliveryTime" ismust="1" type="text" id="" name="最大天数" value="" placeholder="最大天数" onkeyup="linioListing.replaceNumber(this);">'+
									'</td>'+
								'</tr>'+
								'<tr>'+
									'<td colspan="2" class="secondTd">'+
										'<div class="divModular ">'+
											'<table cid="transportAttr"></table>'+
										'</div>'+
									'</td>'+
								'</tr>'+
								'<tr>'+
									'<td colspan="2" class="secondTd">'+
										'<div class="divModular ">'+
											'<table cid="mutAttrShow_'+station_no+'"></table>'+
										'</div>'+
									'</td>'+
								'</tr>'+
							'</tbody>'+
						'</table>'+
					    '<table cid="mutAttrShow_'+station_no+'"></table>'+
						'</div>'+
				     '</div>';
					var sku_html ='<div id="'+station_no+'-variant-info" data-skuName="'+$(obj).data("site")+'_shop" class="panel panel-default form-horizontal form-bordered min common-margin" data-id="main-variant">'+
						'<div class="panel-heading">'+
							'<h3 class="panel-title"><i class="ico-file4 mr5"></i>'+$(obj).data("site")+'站点</h3>'+
						'</div>'+
						'<div class="panel-body">'+
							'<table>'+
								'<tbody>'+
									'<tr>'+
										'<td class="firstTd vAlignTop" style="width:6%;"></td>'+
										'<td class="secondTd">'+
											'<div class="lzdSkuInfo">'+
												'<table class="myj-table var-table"></table>'+
											'</div>'+
											'<button class="btn btn-primary " cid="addOneSku" onclick="linioListing.addOneSku('+station_no+')">添加一个变体</button>'+
										'</td>'+
									'</tr>'+
								'</tbody>'+
							'</table>'+
						'</div>'+
					'</div>';
					$('div[id="diff-show"]').append(station_html);
					$('div[id="variant-info"]').append(sku_html);
					var float_window_str = $('div[data-name="linio_float"]').html();//复制类目录莫泰框
					var new_float_window_str = float_window_str.replace(/category/g,station_no+"_category");
					var new_float_window_str2 = new_float_window_str.replace('replace_shop',$(obj).data("site")+'_shop');
					$('div[data-name="linio_float"]').append(new_float_window_str2);
					$('div[id="'+station_no+'-store-info"]').find('[cid="Brand"]>.secondTd>input').autocomplete({//多产品初始化品牌
						source: function(request,response){
							$.ajax({
							   type: "post",
							   url: "/listing/linio-listing/get-brands",
							   data: { lazada_uid : station_no,name : request.term},
							   dataType:'json',
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
								
								if(data.code == 200){
									data.data.sort(by(request.term));
									response(data.data);
								}else{
									console.log(data.message);
								}
							});
						}

					});
					linioListing.selectShop(station_no);
				}else{
//					$('div[id="'+station_no+'-store-info"]').remove();
//					$('div[id="'+station_no+'_categoryChoose"]').remove();
//					$('div[id="'+station_no+'-variant-info"]').remove();
					$('div[data-name="'+$(obj).data("site")+'_shop"]').remove();
					$('div[data-shop="'+$(obj).data("site")+'_shop"]').remove();
					$('div[data-skuName="'+$(obj).data("site")+'_shop"]').remove();
				}
			}
		},
		//批量修改
		editTypeChange:function(obj){
			var type = $(obj).val();
			switch(type)
			{
				case 'quantity':
					$('#edit_method').html('');
					var str='';
					str = '<option value="">修改方式</option><option value="1">调整</option><option value="0">替换</option><option value="3">按条件加</option><option value="4">按条件补货</option>'
					$('#edit_method').append(str);
					break;
				case 'price':
					$('#edit_method').html('');
					var str='';
					str = '<option value="">修改方式</option><option value="1">调整</option><option value="2">按百分比调整</option><option value="0">替换</option>'
					$('#edit_method').append(str);	
					break;
				case 'sale_message':
					$('#edit_method').html('');
					var str='';
					str = '<option value="">修改方式</option><option value="1">调整</option><option value="2">按百分比调整</option><option value="0">替换</option>'
					$('#edit_method').append(str);
					break;
				default:
					$('#edit_method').html('');
					var str='';
					str = '<option value="">请选择修改方式</option>'
					$('#edit_method').append(str);
			}
		},
		methodChange:function(obj){
			var type = $('#edit_type').val()
			var method = $(obj).val();
			var normal_str2 = '<label for="edit_input">替换：</label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:260px;"><span class="percent"></span>';
			var normal_str = '<label for="edit_input" style="width: 42px;"></label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:260px;"><span class="percent"></span>';
			if(type == 'quantity'){
				switch(method)
				{
					case '1'://调整
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$(".remind").html('');
						$('.sale_message').html('');
						$(".remind").html('提示：如果减少，可输入负数。');
						$('#edit_input').attr('placeholder','示例：1');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9-]/g,'')");
						break;
					case '0'://替换
						$('.input_replace').html('');
						$('.input_replace').html(normal_str2);
						$(".remind").html('');
						$('.sale_message').html('');
						$('#edit_input').attr('placeholder','示例：1');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9]/g,'')");
						break;
					case '3'://按条件加
						$(".remind").html('');
						$('.sale_message').html('');
						$('.input_replace').html('');
						var input_str = '<label for="edit_input" data-type="add">库存少于</label><input style="width:60px;" type="text" id="less_than" name="less_than" class="eagle-form-control" onkeyup="value=value.replace(/[^0-9]/g,\'\')" placeholder="示例：1">'+
						'<label for="edit_input">则加</label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:157px;">';
						$('.input_replace').html(input_str);
						$('#edit_input').attr('placeholder','示例：1');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9]/g,'')");
						break;
					case '4'://按条件补货
						$(".remind").html('');
						$('.sale_message').html('');
						$('.input_replace').html('');
						var input_str = '<label for="edit_input" data-type="replace">库存少于</label><input style="width:60px;" type="text" id="less_than" name="less_than" class="eagle-form-control" onkeyup="value=value.replace(/[^0-9]/g,\'\')" placeholder="示例：1">'+
						'<label for="edit_input">补充到</label><input id="edit_input" name="edit_input" placehodler="" onkeyup="" class="eagle-form-control" style="width:143px;">';
						$('.input_replace').html(input_str);
						$('#edit_input').attr('placeholder','示例：1');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9]/g,'')");
						break;
					default:
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$('.sale_message').html('');
						$(".remind").html('');
						$('.sale_message').html('');
						break;
				}
			}else if(type == 'price'){
				switch(method)
				{
					case '1'://金额
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$(".remind").html('');
						$(".remind").html('提示：如果减少，可输入负数。');
						$('.sale_message').html('');
						$('#edit_input').attr('placeholder','示例：1.00');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9.-]/g,'')");
						break;
					case '0'://直接修改
						$('.input_replace').html('');
						$('.input_replace').html(normal_str2);
						$(".remind").html('');
						$('.sale_message').html('');
						$('#edit_input').attr('placeholder','示例：1.00');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9.]/g,'')");
						break;
					case '2'://百分比
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$(".remind").html('');
						$(".percent").html('%');
						$('.sale_message').html('');
						$(".remind").html('提示：如果减少，可输入负数。');
						$('#edit_input').attr('placeholder','示例：1.00');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9.-]/g,'')");
						break;
					default:
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$('.sale_message').html('');
						$(".remind").html('');
						$('.sale_message').html('');
						break;
				}
			}else if(type == 'sale_message'){
				switch(method)
				{
					case '1'://金额
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$(".remind").html('');
						$('.sale_message').html('');
						$(".remind").html('提示：如果减少，可输入负数。');
						$('#edit_input').attr('placeholder','此处输入促销价，示例：1.00');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9.-]/g,'')");
						break;
					case '0'://直接修改
						$('.input_replace').html('');
						$('.input_replace').html(normal_str2);
						$(".remind").html('');
						$('.sale_message').html('');
						$('#edit_input').attr('placeholder','此处输入促销价，示例：1.00');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9.]/g,'')");
						break;
					case '2'://百分比
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$(".remind").html('');
						$(".percent").html('%');
						$('.sale_message').html('');
						$(".remind").html('提示：如果减少，可输入负数。');
						$('#edit_input').attr('placeholder','此处输入促销价，示例：1.00');
						$('#edit_input').attr("onkeyup","value=value.replace(/[^0-9.-]/g,'')");
						break;
					default:
						$('.input_replace').html('');
						$('.input_replace').html(normal_str);
						$('.sale_message').html('');
						$(".remind").html('');
						$('.sale_message').html('');
						break;
				}
				var sale_str = '<label for="saleStartDate">促销起始时间：</label><input type="text" onkeyup="value=value.replace(/[^0-9-]/g,\'\')" class="eagle-form-control Wdate" style="padding-left:5px;width:234px;" id="saleStartDate" name="saleStartDate" placeholder="开始时间" onClick="linioListing.datePicker(this)"/>'+
				'<br /><label for="saleEndDate" style="padding-left:55px">促销结束时间：</label><input onkeyup="value=value.replace(/[^0-9-]/g,\'\')" type="text" class="eagle-form-control Wdate" style="padding-left:5px;width:234px;" id="saleEndDate" name="saleEndDate" placeholder="结束时间" onClick="linioListing.datePicker(this)"/>';
				$('.sale_message').html('');
				$('.sale_message').html(sale_str);
			}else{
				$('.input_replace').html('');
				$('.input_replace').html(normal_str);
				$('.sale_message').html('');
				$(".remind").html('');
//				$(".percent").html('');
				$('.sale_message').html('');
//				$('#edit_input').attr('placeholder','');
//				$('#edit_input').attr("onkeyup","");
//				$('#edit_input').val('');
			}
		},
		checkAll:function(obj){
			if($(obj).prop('checked')){
				$('input[type="checkbox"]').each(function(i){
					$(this).prop("checked",true);
				});
			}else{
				$('input[type="checkbox"]').each(function(i){
					$(this).prop("checked",false);
				});
			}
		},
		//展开显示
		productShow:function(obj){
	        var parentId = $(obj).parents("tr").data("id");
	        if($(obj).hasClass("glyphicon-plus")){
	            $(obj).removeClass("glyphicon-plus");
	            $(obj).addClass("glyphicon-minus");
	            $(".product_"+parentId).show();
	        } else {
	            $(obj).removeClass("glyphicon-minus");
	            $(obj).addClass("glyphicon-plus");
	            $(".product_"+parentId).hide();
	        }
	    },

	    //展示所有商品
		productAllShow:function(obj){
	        if($(obj).hasClass("glyphicon-plus")){
	            $(obj).removeClass("glyphicon-plus");
	            $(obj).addClass("glyphicon-minus");

	            $(".product_show").removeClass("glyphicon-plus");
	            $(".product_show").addClass("glyphicon-minus");

	            $(".variation_tr").show();
	        } else {
	            $(obj).removeClass("glyphicon-minus");
	            $(obj).addClass("glyphicon-plus");
	            $(".product_show").removeClass("glyphicon-minus");
	            $(".product_show").addClass("glyphicon-plus");
	            $(".variation_tr").hide();
	        }
	    },
		//变种选择
		variationCheck:function(obj){
	        var parentId = $(obj).attr("parentid");
	        if($(obj).is(":checked") && !$("#chk_one_"+parentId).is(":checked")){
	            $("#chk_one_" + parentId).prop("checked",true);
	        } else {
	            var type = false;
	            $('input[parentid="'+parentId+'"]').each(function(){
	                if($(this).is(":checked")){
	                    type = true;
	                }
	            });
	            if(!type){
	                $("#chk_one_" + parentId).removeAttr("checked");
	            }
	        }
	    },
		//批量修改确认
		batchEditSubmit:function(){
			var post_url = '';
			var condition = '';
			var ids = $("#productIds").val();//获取修改的商品id
			var operation = $('#edit_method').val();//获取商品的运算方式
			var type = $('#edit_type').val();//获取修改的类型
			var value = $.trim($("#edit_input").val());//获取修改的数值
			
			if(ids == ""){
				bootbox.alert("需要修改商品的信息获取失败！");
				return;
			}
			
			if(type == ""){
				bootbox.alert("请选择修改选项！");
				return;
			}else{
				switch(type){
					case 'price':
						post_url = "/listing/linio/batch-update-price";
						break;
					case 'sale_message':
						post_url = "/listing/linio/batch-update-sales-info";
						break;
					case 'quantity':
						post_url = "/listing/linio/batch-update-quantity";
						break;
				}
			}
			
			if(operation === ""){
				bootbox.alert("请选择修改方式！");
				return;
			}
			//库存控制
			if((type == 'quantity'&& operation =="4") || (type == 'quantity'&& operation =="3")){
				if($('input[id="less_than"]').val() == ""){
					bootbox.alert("库存少于的条件必须要填写");
					return;
				}else{
					condition = $('label[for="edit_input"]').data("type");
//					$('#condition').val(condition);
				}
			}
			//时间的比较
			if(type == 'sale_message'&& operation !=""){
				if($('#saleStartDate').val()==""){
					bootbox.alert("促销起始时间不能为空");
					return;
				}
				if($('#saleEndDate').val()==""){
					bootbox.alert("促销结束时间不能为空");
					return;
				}
				var nowDate = new Date().format("yyyy-MM-dd");
				var compare1 = linioListing.editCompareDate(nowDate,$('#saleEndDate').val());//这个function只用于批量修改时间时的比较
				var compare2 = linioListing.compareDate($('#saleStartDate').val(),$('#saleEndDate').val());
				if(compare1 == 1){
					bootbox.alert("促销结束时间需要大于当前时间！");
					return;
				}
				if(compare2 == 1){
					bootbox.alert("促销开始时间不能少于促销结束时间！");
					return;
				}
			}
			
			if(value == undefined || value == ""){
				bootbox.alert("请输入修改值");
				return;
			}
			// 判断是否为数字
			if(isNaN(value)){
				bootbox.alert("请输入数字");
				return;
			}
			$.showLoading();
			$.ajax({
				type: "POST",
				url: post_url,
				data: $('#edit-product').serialize(),
				dataType:'json',
				success: function(result){
					$.hideLoading();
					if(result.code == 200){//复制成功
						// bootbox.alert(result.message);
						// $('#edit_product').css("display","none");
						bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
							window.location.reload();
							$.showLoading();
						}
						});
					}else{
						bootbox.alert(result.message);
					}
				},
				error:function(){
					$.hideLoading();
					bootbox.alert("网络错误！");
				}
			});
		},
		//parent check选择时
		check:function(obj){
				var parent_id = $(obj).parents("tr").data("id");
				if($(obj).prop('checked')){
					$('input[parentid="'+parent_id+'"]').each(function(i){
						$(this).prop("checked",true);
					});
				}else{
					$('input[parentid="'+parent_id+'"]').each(function(i){
						$(this).prop("checked",false);
					});
				}
		},
		//批量修改的时候检查是否没有选商品
		checkBox:function(){
			var box = [];
			$('input[name="productcheck"]:checked').each(function(){
				box.push($(this).parents("tr").data("productid"));
			});
			if(box.length == 0){
				bootbox.alert("至少要选择一件商品！");
				return;
			}else{
				$("#productIds").val('');
				$("#productIds").val(box);
				$("#edit_product").modal('show');
			}

		},
		//批量下架
		batchPutOff:function(){
			var box = [];
			$('input[name="productcheck"]:checked').each(function(){
				box.push($(this).parents("tr").data("productid"));
			});
			if(box.length == 0){
				bootbox.alert("至少要选择一件商品！");
				return;
			}else{
				$.ajax({
					   type: "POST",
					   url: '/listing/linio/put-off',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
					     if(result.code == 200){
					    	 bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
					    		 	$.showLoading();
					    		 	window.location.reload();
								}
					    	 });
						 }else{
							 bootbox.alert(result.message);
						 }
					   },
					   error:function(){
						   bootbox.alert("网络错误！");
					   }
					});
			}
		},
		//主产品下架（包括所有变参）
		parentProductPutOff:function(obj){
			var box = [];
			var parent_id = $(obj).parents("tr").data("id");
			$('input[parentid="'+parent_id+'"]').each(function(i){
				box.push($(this).parents("tr").data("productid"));
			});
			if(box.length > 0){
				$.ajax({
					   type: "POST",
					   url: '/listing/linio/put-off',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
					     if(result.code == 200){
					    	 bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
					    		 	$.showLoading();
					    		 	window.location.reload();
								}
					    	 });
						 }else{
							bootbox.alert(result.message)
						 }
					   },
					   error:function(){
						   bootbox.alert("网络错误！");
					   }
					});
			}else{
				bootbox.alert("获取产品信息失败！");
				return;
			}
			
		},
		//单个产品的下架
		productPutOff:function(id){
			var box = [];
			box.push(id);
			if(box.length > 0){
				$.ajax({
					   type: "POST",
					   url: '/listing/linio/put-off',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
					     if(result.code == 200){
				    	 	bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
				    	 		$.showLoading();
				    	 		window.location.reload();
								}
					    	 });
						 }else{
							 bootbox.alert(result.message);
						 }
					   },
					   error:function(){
						   bootbox.alert("网络错误！");
					   }
					});
			}else{
				bootbox.alert("获取产品信息失败！");
				return;
			}
		},
		//批量上架
		batchPutOn:function(){
			var box = [];
			$('input[name="productcheck"]:checked').each(function(){
				box.push($(this).parents("tr").data("productid"));
			});
			if(box.length == 0){
				bootbox.alert("至少要选择一件商品！");
				return;
			}else{
				$.ajax({
					   type: "POST",
					   url: '/listing/linio/put-on',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
					     if(result.code == 200){
					    	 bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
					    		 	$.showLoading();
					    		 	window.location.reload();
								}
					    	 });
						 }else{
							 bootbox.alert(result.message);
						 }
					   },
					   error:function(){
						   bootbox.alert("网络错误！");
					   }
					});
			}
		},
		//主产品上架（包括所有变参）
		parentProductPutOn:function(obj){
			var box = [];
			var parent_id = $(obj).parents("tr").data("id");
			$('input[parentid="'+parent_id+'"]').each(function(i){
				box.push($(this).parents("tr").data("productid"));
			});
			if(box.length > 0){
				$.ajax({
					   type: "POST",
					   url: '/listing/linio/put-on',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
					     if(result.code == 200){
					    	 bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
					    		 	$.showLoading();
					    		 	window.location.reload();
								}
					    	 });
						 }else{
							 bootbox.alert(result.message);
						 }
					   },
					   error:function(){
						   bootbox.alert("网络错误！");
					   }
					});
			}else{
				bootbox.alert("获取产品信息失败！");
				return;
			}
			
		},
		//单个产品的上架
		productPutOn:function(id){
			var box = [];
			box.push(id);
			if(box.length > 0){
				$.ajax({
					   type: "POST",
					   url: '/listing/linio/put-on',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
					     if(result.code == 200){
					    	 bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
					    		 $.showLoading();	
					    		 window.location.reload();
								}
					    	 });
						 }else{
							 bootbox.alert(result.message);
						 }
					   },
					   error:function(){
						   bootbox.alert("网络错误！");
					   }
					});
			}else{
				bootbox.alert("获取产品信息失败！");
				return;
			}
		},
		//批量发布
		batch:function(obj){
			var ids = [];
			if($(obj).val()=="1"){
				$(".lzd_body>tr").each(function(i){
					var id = '';
					if($(this).find('input[id="chk_one"]').prop('checked')){
						id = $(this).data("id");
						ids.push(id);
					}
				});
				if(ids.length>0){
					$.ajax({
						type:'GET',
						async:false,
						url:'/listing/linio-listing/do-publish',
						data:{
							ids:ids.join(',')
						},
						dataType:'json',
						success:function(data){
							if(data.code == 200 ){
								bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
										$.showLoading();	
										window.location.reload();
									}
						    	});
							}
							if(data.code == 400 ){
								bootbox.alert(data.message);
							}
								
						},
						error:function(){
							$('#loading').modal('hide');
//							$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
							bootbox.alert("网络错误, 请稍后再试！");
						}
					});
				}else{
					$('select[name="batch"] option').eq(0).prop('selected',true);
					bootbox.alert("没有勾选选项");
					return null;
				};
				
			};
			if($(obj).val()=="2"){//批量删除
				$(".lzd_body>tr").each(function(i){
					var id = '';
					if($(this).find('input[id="chk_one"]').prop('checked')){
						id = $(this).data("id");
						ids.push(id);
					}
				});
				if(ids.length>0){
					$.ajax({
						type:'POST',
						async:false,
						url:'/listing/linio-listing/delete',
						data:{
							ids:ids.join(',')
						},
						dataType:'json',
						success:function(data){
							if(data.code == 200 ){
								bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
										window.location.reload();
										$.showLoading();
									}
							   });
							}
							if(data.code == 400 ){
								bootbox.alert(data.message);
							}
								
						},
						error:function(){
							$('#loading').modal('hide');
//							$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
							bootbox.alert("网络错误, 请稍后再试！");
						}
					});
				}else{
					$('select[name="batch"] option').eq(0).prop('selected',true);
					bootbox.alert("没有勾选选项");
					return null;
				};
			};
		},
		//单个删除产品
		deleteProduct:function(id){
			var ids = [];
			ids.push(id);
			if(ids.length>0){
				$.ajax({
					type:'POST',
					async:false,
					url:'/listing/linio-listing/delete',
					data:{
						ids:ids.join(',')
					},
					dataType:'json',
					success:function(data){
						if(data.code == 200 ){
							bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
									window.location.reload();
									$.showLoading();
								}
						   });
						}
						if(data.code == 400 ){
							bootbox.alert(data.message);
						}
							
					},
					error:function(){
						$('#loading').modal('hide');
//						$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
						bootbox.alert("网络错误, 请稍后再试！");
					}
				});
			}else{
				bootbox.alert("获取商品信息失败");
				return null;
			}
		},
		//同步商品
		SyncSubmit:function(){
			var remind = '';
			$(".success_message").html('');
			var lzd_uid = $("#Sync_lzd_uid").val();
			if(lzd_uid == ""){
				remind = "请选择店铺！";
				$(".success_message").html(remind);
			}else{
				$.showLoading();
				$.ajax({
					type:'GET',
					url:'/listing/linio/manual-sync',
					data:$('#Sync-product').serialize(),
					dataType:'json',
					success:function(data){
						$.hideLoading();
						if(data.success == true){
							remind = "成功同步商品"+data.num+"条";
//							$(".success_message").html(remind);
							bootbox.alert({title:Translator.t('提示'),message:remind,callback:function(){
									window.location.reload();
									$.showLoading();
								}
					    	 });
						}
						if(data.success == false){
							remind = data.message;
							$(".success_message").html(remind);
						}
					},
					error:function(){
						$.hideLoading();
//						$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
						bootbox.alert("网络错误, 请稍后再试！");
					}
				});
			}
		},
		
		hide:function(obj){
				$(obj).removeClass("glyphicon-chevron-up");
				$(obj).addClass("glyphicon-chevron-down");
				$(obj).parents('div[class="panel-heading"]').next().removeClass("show");
				$(obj).parents('div[class="panel-heading"]').next().addClass("hide");
		},
//		//创建商品的帐号检查
//		createProduct:function(obj){
//			if($(obj).val()){
//				var type = $(obj).parents('span').attr('id');
//				$('#'+type+'_comfirm').attr("disabled",false);
////				var text = $('#built_account').find("option:selected").text();
////				var account_value = $(obj).val();
////				var link = '/listing/linio-listing/create-product?account_value='+account_value+'&text='+text;
////				$('#account_comfirm').parent('a').attr("href",link);
//			}else{
//				var type = $(obj).parents('span').attr('id');
//				$('#'+type+'_comfirm').attr("disabled","disabled");
////				$('#account_comfirm').parent('a').attr("href","#");
//			}
//		},
		//选择框重置
		reset:function(){
			$('.success_message').html('');
			$("#Sync_lzd_uid").find("option[value='']").attr("selected",true);
		},
//		//复制产品
//		copy:function(obj){
//			$('#copy_lzd_uid').val('');
//			$('#copy_comfirm').attr("disabled","disabled");
//			$('#product_id').val('');
//			var id = $(obj).parents('tr').data('id');
//			if(id == ''){
//				bootbox.alert("获取产品id失败！");
//			}else{
//				$('#product_id').val(id);
//			};
//		},
		
		// 发布产品
		publishOne:function(id){
			$.ajax({
			   type: "get",
			   url: "/listing/linio-listing/do-publish",
			   data: {ids:id},
			   dataType:'json',
			   success: function(result){
			     if(result.code == 200){//发布成功
			    	 bootbox.alert({title:Translator.t('提示'),message:result.message,callback:function(){
			    		 window.location.reload();
			    		 	$.showLoading();
						 }
				    });
				 }else{
	                bootbox.alert(result.message);
				 }
			   },
			   error:function(){
				    bootbox.alert("网络错误！");
			   }
			});
		},
		
		show:function(obj){
				$(obj).removeClass("glyphicon-chevron-down");
				$(obj).addClass("glyphicon-chevron-up");
				$(obj).parents('div[class="panel-heading"]').next().removeClass("hide");
				$(obj).parents('div[class="panel-heading"]').next().addClass("show");
		},
		//时间插件
//		datePicker:function(obj){
////			$(obj).datepicker({dateFormat:"yy-mm-dd"});
//		},
		//初始化店铺相关信息，服务模板、运费模板、分组
		initShopInfo:function(shopId){
			if($.trim(shopId) != ""){
				$("#lazadaUid").val(shopId);
				linioListing.selectShop();
			}
		},
		//addOneSku
		addOneSku:function(shop_id){
			if(shop_id == undefined){
				var shopId = $("#lazadaUid").val();
				//判断有没有选择店铺和类目
				if($.trim(shopId) == ""){
//					$.fn.message({type:"error", msg:"请选择Linio店铺!"});
					$('#select_shop_info').html("请选择Linio店铺!");
					$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
					return;
				}
				//保存选中前的类目数据
				var data = linioListing.dataSet.lzdSkuObjArr.Variation,
				str = linioListing.lzdSkuTdHtmlBorn(data);
				if(+$.trim($("#categoryId").val())){
					$('div.lzdSkuInfo table').append(str);
					if($('div.lzdSkuInfo table tr').length > 2){//复制第一行
//						var first_tr = '';
//						first_tr = $('div.lzdSkuInfo table tr:eq(1) input');
						var tr_length = $('div.lzdSkuInfo table tr').length-1;
						$('div.lzdSkuInfo table tr:eq('+tr_length+') input').each(function(i){
							$(this).val($('div.lzdSkuInfo table tr:eq(1) input:eq('+i+')').val())
						});
					}
				}else{
//					$.fn.message({type:"error", msg:"请选择产品分类!"});
//					bootbox.alert("请选择产品分类!");
					$('#select_info').html("请选择产品分类!");
					$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				}
			}else{//多站点发布
				var shop_key = "shop_" + shop_id;
				var var_info = "#" + shop_id + "-variant-info";
				//保存选中前的类目数据
				var data = linioListing.dataSet.mutlzdSkuObjArr[shop_key].Variation,
				str = linioListing.mutlzdSkuTdHtmlBorn(data,shop_key);
				if(+$.trim($("#"+shop_id+"_categoryId").val())){
					$(var_info).find('div.lzdSkuInfo table').append(str);
					if($(var_info).find('div.lzdSkuInfo table tr').length > 2){//复制第一行
//						var first_tr = '';
//						first_tr = $('div.lzdSkuInfo table tr:eq(1) input');
						var tr_length = $(var_info).find('div.lzdSkuInfo table tr').length-1;
						$(var_info).find('div.lzdSkuInfo table tr:eq('+tr_length+') input').each(function(i){
							$(this).val($(var_info).find('div.lzdSkuInfo table tr:eq(1) input:eq('+i+')').val())
						});
					}
				}else{
//					$.fn.message({type:"error", msg:"请选择产品分类!"});
//					bootbox.alert("请选择产品分类!");
					$('#'+shop_id+'_select_info').html("请选择产品分类!");
					$('html,body').animate({scrollTop:$('div[id="'+shop_id+'-store-info"]').offset().top}, 800);
				}
			}
			

		},
		//sku批量修改
		//批量弹层html生成
		skuBatchEdit:function(edit_obj){
			var varriation_id = "#" + $(edit_obj).parents('div[data-id="main-variant"]').attr('id');
			var type = '',
			aName = $(edit_obj).attr('data-names');
			aName == 'price' || aName == 'salePrice' ? type = 'priceEdit' : type = aName ;
			if(aName == 'variation'){
				$('div.lzdSkuInfo table tr').each(function(){
					$(this).find('td[data-name="variation"] input[type="text"]').val($(this).index());
				})
				return;
			}
			$('#lzdSkuBatchEdit').find('.modal-body').empty();
			$('#lzdSkuBatchEdit').find('#myModalLabel').html('');
			switch (type)
			{
				case 'priceEdit'://价格||促销价
					aName == 'price' ? $('#lzdSkuBatchEdit').find('#myModalLabel').html('修改价格') : $('#lzdSkuBatchEdit').find('#myModalLabel').html('修改促销价') ;
					$('#lzdSkuBatchEdit').find('.modal-dialog').css('width','400px');
					var str = '<div style="width:85%;margin:0 auto;">'+
							  '<div class="mTop10">'+
							  '<input type="radio" name="priceEditType" value="1" checked style="margin-right:27px;position:relative;top:2px;">'+
							  '<input type="text" class="form-control" onkeyup="value=value.replace(/[^0-9.]/g,\'\')" style="display:inline-block;width:90px;margin-right:10px;">'+
							  '<span class="fColor2">(直接修改价格)</span>'+
							  '</div>'+
							  '<div class="mTop10"><input type="radio" name="priceEditType"  value="2" style="margin-right:10px;position:relative;top:2px;">按 '+
							  '<select class="form-control" disabled data-names="retailPrice" style="display:inline-block;width:90px;">'+
							  '<option value="1">金额</option>'+
							  '<option value="2">百分比</option>'+
							  '</select> 增加'+
							  '&nbsp;&nbsp;&nbsp;<input type="text" disabled onkeyup="value=value.replace(/[^0-9.-]/g,\'\')" class="form-control" style="display:inline-block;width:90px;"/>'+
							  '&nbsp;<span class="danwei"></span>'+
							  '</div>'+
							  '<div class="mTop10 fColor2">小提示：如果减少，可以输入负数</div>'+
						      '</div>';
					break;
				case 'promotionTime'://促销时间
					$('#lzdSkuBatchEdit').find('#myModalLabel').html('修改促销时间');
					$('#lzdSkuBatchEdit').find('.modal-dialog').css('width','340px');
					var str = '<div style="width:85%;margin:0 auto;">'+
							  '<table>'+
							  '<tr>'+
						      '<td>开始时间：</td>'+
							  '<td><input type="text" data-name="saleStartDate" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="开始时间" onClick="linioListing.datePicker(this)"/></td>'+
							  '</tr>'+
							  '<tr>'+
							  '<td>结束时间：</td>'+
							  '<td><input type="text" data-name="saleEndDate" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="结束时间" onClick="linioListing.datePicker(this)"/></td>'+
							  '</tr>'+
							  '</table>'+
						      '</div>';
					break;
				case 'quantity'://库存
					$('#lzdSkuBatchEdit').find('#myModalLabel').html('修改库存');
					$('#lzdSkuBatchEdit').find('.modal-dialog').css('width','400px');
					var str = '<div style="width:95%;margin:0 auto;">'+
							  '<div class="numDiv">'+
							  '<input type="radio" name="numRadio" checked="true" class="radioIpt" data-val="2" style="margin-right:27px;position:relative;top:2px;" >'+
							  '<input id="num2" class="form-control text" type="text" onkeyup="value=value.replace(/[^0-9]/g,\'\')" placeholder="示例：1" style="display:inline-block;width:90px;margin-right:10px;">'+
							  '(直接修改库存量)'+
							  '</div>'+
							  '<div class="numDiv mTop10">'+
							  '<input type="radio" name="numRadio" class="radioIpt" data-val="1" style="margin-right:27px;position:relative;top:2px;"时>'+
							  '按现有库存量  增加'+
							  '<input id="num1" class="form-control text" type="text" disabled="true" onkeyup="value=value.replace(/[^0-9-]/g,\'\')" placeholder="示例：1" style="display:inline-block;width:90px;margin-right:10px;" >'+
							  '</div>'+
						      '<div style="clear:both;" class="fColor2"><span>提示：如果减少，可输入负数。</span></div>'+
						      '</div>';
					break;
				case 'sku'://sku
					$('#lzdSkuBatchEdit').find('#myModalLabel').html('SKU生成规则');
					$('#lzdSkuBatchEdit').find('.modal-dialog').css('width','540px');
					var str = '<div style="width:510px;margin:0 5px;">'+
							  '<table style="width:100%;"><tr>'+
							  '<td style="width:50%;" class="vAlignTop">'+
							  '<p class="mBottom10">前缀：<input data-names="star" class="modalCommNavIpt" type="text" placeholder="示例：GX"/></p>'+
							  '<p class="m0">后缀：<input type="text" data-names="end" class="modalCommNavIpt" placeholder="示例：US"/></p>'+
							  '</td>'+
							  '<td style="width:50%;">'+
							  '<p class="mBottom10 fColor2">SKU生成格式=[前缀]-[Variation]-[后缀]</p>'+
							  '<p class="mBottom10 fColor2">生成示例：</p>'+
							  '<p class="m0 fColor2">前缀=BG0001，Variation=XL，后缀=CN</p>'+
							  '<p class="mBottom10 fColor2">生成：BG0001-XL-CN</p>'+
							  '</td>'+
							  '</tr></table>'+
						      '</div>';
					break;
			};
			$('#lzdSkuBatchEdit').find('.modal-body').html(str);
			$('#lzdSkuBatchEdit').find('button.lzdSkuBatchEdit').attr('data-names',aName);
			$('#lzdSkuBatchEdit').find('button.lzdSkuBatchEdit').attr('data-id',varriation_id);
			$('#lzdSkuBatchEdit').modal('show');
		},
		skuBatchEditConfirm:function(confirm_obj){
			var varriation_id = $(confirm_obj).attr('data-id');
			var type = '',
			btnName = $(confirm_obj).attr('data-names');//sku\quantity\price\salePrice\promotionTime
			btnName == 'price' || btnName == 'salePrice' ? type = 'priceEdit' : type = btnName ;
			switch (type)
			{
			case 'priceEdit'://价格||促销价
				$(confirm_obj).closest('.modal-content').find('input[name="priceEditType"]').each(function(){
					if(this.checked){
						var type = $(this).attr('value');
						if (type == 1){
							var str = $(this).closest('div').find('input[type="text"]').val();
							if(str != '' && str != undefined){
								if(!isNaN(str)){
									str = parseFloat(str);
									$(varriation_id).find('div.lzdSkuInfo table td[data-name="'+btnName+'"]').each(function(){
										if(str>0){
											$(this).find('input').val(str.toFixed(2));
										}else{
											return;
										};
									});
								}
							}
						}else if(type == 2){
							var num = $(this).closest('div').find('select[data-names="retailPrice"]').val(),
								str = $(this).closest('div').find('input[type="text"]').val();
							if (str != '' && str != undefined)
							{
								if (!isNaN(str))
								{
									if (num == 1)//金额
									{
										$(varriation_id).find('div.lzdSkuInfo table td[data-name="'+btnName+'"]').each(function(){
											var priceCell = $(this).find('input').val();
											if($.trim(priceCell) == ""){
												priceCell = "0.00";
											}											
											var priceCellArr = priceCell.split('-');//分离减号
											var priceStr = "";
											for(var i=0;i<priceCellArr.length;i++){
												var cell = (parseFloat(priceCellArr[i])+parseFloat(str)).toFixed(2);
												if(i>0){
													priceStr = priceStr+"-"+cell;
												}else{
													priceStr = cell;
												}
											}
											$(this).find('input').val(priceStr);
										});
	
									};
									if (num == 2)//百分比
									{
										$(varriation_id).find('div.lzdSkuInfo table td[data-name="'+btnName+'"]').each(function(){
											var t = parseFloat($(this).html()) * (100 + parseFloat(str)) / 100;
											var priceCell = $(this).find('input').val();
											if($.trim(priceCell) == ""){
												priceCell = "0.00";
											}											
											var priceCellArr = priceCell.split('-');
											var priceStr = "";
											for(var i=0;i<priceCellArr.length;i++){
												var cell = (parseFloat(priceCellArr[i]) * (100 + parseFloat(str)) / 100).toFixed(2);
												if(i>0){
													priceStr = priceStr+"-"+cell;
												}else{
													priceStr = cell;
												}
											}
											
											$(this).find('input').val(priceStr);
										});
									};
								};
							};
						};
					}
				});
				break;
			case 'promotionTime'://促销时间
				var starDate = $(confirm_obj).closest('.modal-content').find('input[data-name="saleStartDate"]').val(),
					endDate = $(confirm_obj).closest('.modal-content').find('input[data-name="saleEndDate"]').val(),
					value = '';
				if(starDate == '' || endDate == ''){
//					$.fn.message({type:"error", msg:"开始时间和结束时间不能为空!"});
					bootbox.alert("开始时间和结束时间不能为空!");
					return;
				}
				if(starDate != '' && endDate != ''){
					value = linioListing.compareDate(starDate,endDate);
					if(value == 1){
//						$.fn.message({type:"error", msg:"结束时间必须大于等于开始时间!"});
						bootbox.alert("结束时间必须大于等于开始时间!");
						return;
					}else{
						$(varriation_id).find('div.lzdSkuInfo table td[data-name="saleStartDate"] input').each(function(){$(this).val(starDate);})
						$(varriation_id).find('div.lzdSkuInfo table td[data-name="saleEndDate"] input').each(function(){$(this).val(endDate);})
					}
				}
				break;
			case 'quantity'://库存
				var type = '',num = '';
				$('#lzdSkuBatchEdit').find('input[name="numRadio"]').each(function(){
					if($(this).is(':checked')){
						type = $(this).attr('data-val');
						num = $(this).closest('div.numDiv').find('input[type="text"]').val();
					}
				});
				if(type == 1){
					if(num != ''){
						$(varriation_id).find('div.lzdSkuInfo table').find('tr').each(function(){
							var newNum = Number($(this).find('td[data-name="quantity"] input[type="text"]').val())+Number(num);
							$(this).find('td[data-name="quantity"] input[type="text"]').val(newNum);
						});
					}else{
//						$.fn.message({type:"error",msg:"请输入库存增加数"});
						bootbox.alert("请输入库存增加数");
						return;
					};
				}
				if(type == 2){
					if(num != ''){
						$(varriation_id).find('div.lzdSkuInfo table').find('td[data-name="quantity"] input[type="text"]').val(num);
					}else{
//						$.fn.message({type:"error",msg:"请输入库存数"});
						bootbox.alert("请输入库存数");
						return;
					};
				};
				break;
			};
			//sku特殊处理
			if(type == 'sku'){
				var star = $('#lzdSkuBatchEdit').find('input[data-names="star"]').val(),
					end = $('#lzdSkuBatchEdit').find('input[data-names="end"]').val(),
					type = null,
					value = null;
				star != '' ? star = star + '-' : star = '' ;
				end != '' ? end = '-' + end : end = '' ;
				var isKong = linioListing.kong(varriation_id);
				if(isKong == 0){
//					$.fn.message({type:"error",msg:"不能生成SKU,variation值不能为空！"});
					bootbox.alert("不能生成SKU,variation值不能为空！");
					return;
				}
				if(linioListing.variationIsRepeat(varriation_id) == 1){
//					$.fn.message({type:"error",msg:"不能生成SKU,重复的variation值！"});
					bootbox.alert("不能生成SKU,重复的variation值！");
					return;
					}
				$(varriation_id).find('div.lzdSkuInfo table tr').each(function(){
					if($(this).index() != 0){
						type = $(this).find('td[data-name="variation"]').attr('data-type');
						value = $(this).find('td[data-name="variation"]').find(type).val();
						$(this).find('td[data-name="sellerSku"] input[type="text"]').val(star.trim().replace(/(\s+|　+)/g,'_')+value.trim().replace(/(\s+|　+)/g,'_')+end.trim().replace(/(\s+|　+)/g,'_'));
					}
				});
			}
			$('#lzdSkuBatchEdit').modal('hide');
		},
		kong:function(varriation_id){
			var typeA = 1;
			$(varriation_id).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var type = $(this).find('td[data-name="variation"]').attr('data-type');
					var value = $(this).find('td[data-name="variation"]').find(type).val();
					if (value == '' || value == '请选择'){
						typeA = 0;
					}
				}
			});
			return typeA;
		},
		//variation是否重复
		variationIsRepeat:function(varriation_id){
			var typeA = 0,arr = [];
			$(varriation_id).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var type = $(this).find('td[data-name="variation"]').attr('data-type');
					var value = $(this).find('td[data-name="variation"]').find(type).val();
					if ($.inArray(value,arr) != -1){
						typeA = 1;
					}else{
						arr.push(value);
					}
				}
			});
			return typeA;
		},
		//选择类目
		selectCategory:function(station_id){
			if(station_id == undefined){
				var shopId = $("#lazadaUid").val();
				if($.trim(shopId) == ""){
//					$.fn.message({type:"error", msg:"请选择Linio店铺!"});
//					bootbox.alert("请选择Linio店铺!");
					$('#select_shop_info').html("请选择Linio店铺!");
					$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
					return;
				}
				//保存选中前的类目数据
//				if($.trim($("#categoryId").val())){
//					editCategoryPreservationData();
//					editCategoryGetSkuListData();
//				}
				
				$('#categoryChoose').modal('show');
			}else{
				$('#'+station_id+'_categoryChoose').modal('show');
			}
			
		},
		
		//选择店铺或初始化店铺
		selectShop:function(flag){
			if(flag == undefined){
				var shopId = $("#lazadaUid").val();
				var pre = "";
				//切换店铺
				if($.trim(shopId) == ""){
					bootbox.alert("获取帐号失败！");
					return ;
				}else{
					//切换店铺，由于站点不同，所以初始化类目和属性
					$('#select_shop_info').html("");
					$("#categoryId").val("");
					$("#categoryHistoryId").html('<option value="">---- 请选择分类 ----</option>');
					$(".category").html("未选择分类");
					
//					linioListing.pageBeginning();
					
					//初始化第一级类目
					linioListing.initCategory(null);
				}
			}else{
				//切换店铺，由于站点不同，所以初始化类目和属性
				var shopId = flag;
				var pre = flag + "_";
				$('#'+flag+'_select_shop_info').html("");
				$("#"+flag+"_categoryId").val("");
				$("#"+flag+"_categoryHistoryId").html('<option value="">---- 请选择分类 ----</option>');
				$("."+flag+"_category").html("未选择分类");
				linioListing.initCategory(null,flag);
			}
			
			
			//历史记录类目
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/linio-listing/get-selected-category-history',
				data:{
					lazada_uid:shopId
				},
				dataType:'json',
				success:function(data){
					if(data.code == 200){
						var historyList = data.data;
						var categoryHistoryId = "";
						if(historyList != undefined){
							categoryHistoryId = '<option value="">---- 请选择分类 ----</option>';
							for(var i=0;i<historyList.length;i++){
								var ch = historyList[i];
								if(ch != undefined){								
									var checkInfo = '';
									categoryHistoryId += '<option value="'+ch.categoryId+'" '+checkInfo+'>'+ch.categoryName+'</option>';
								}
							}
							$("#"+pre+"categoryHistoryId").html(categoryHistoryId);						
						}
						//切换店铺时已选中的类目自动选中
						var historyCategoryId = $("#"+pre+"categoryId").val();
						if($.trim(historyCategoryId) != ""){
							linioListing.getNewCategoryHistory(historyCategoryId,'',pre);
						}
					}else{
						console.log(data.message);
					}
					
				}
			});
		},
		
		//选中类目
		getNewCategoryHistory:function(categoryId,categoryName,pre){
			var obj = $('#'+pre+'categoryHistoryId option[value="'+categoryId+'"]');
			
			//如果历史列表存在则直接选中
			if(obj.text() != undefined && obj.text() != ""){
				$('#'+pre+'categoryHistoryId option[value=""]').remove();
				$("#"+pre+"categoryHistoryId").val(categoryId);
				return;
			}
			
			var categoryHistoryId = '';
			categoryHistoryId += '<option value="'+categoryId+'">'+categoryName+'</option>';
			$("#"+pre+"categoryHistoryId").append(categoryHistoryId);
			$("#"+pre+"categoryHistoryId").val(categoryId);
			$('#'+pre+'categoryHistoryId option[value=""]').remove();
//			$.ajax({
//				type:'POST',
//				async:false,
//				url:'lazadaCategory/getByCategoryId.json',
//				data:{
//					shopId:$("#shopId").val(),
//					categoryId:categoryId
//				},
//				dataType:'json',
//				success:function(data){
//					if(data != null){
//						categoryHistoryId += '<option value="'+categoryId+'">'+data.categoryName+'</option>';
//						$("#categoryHistoryId").append(categoryHistoryId);
//						$("#categoryHistoryId").val(categoryId);
//						$('#categoryHistoryId option[value=""]').remove();
//					}
//				}
//			});				
		},
		/**
		 * 选择产品分类历史记录
		 */
		selectHistoryCategory:function(obj){
			var select_array = $(obj).attr('id').split('_');//多站点发布
			var id_length = select_array.length;
			if(id_length>1){
				var pre = select_array[0] + "_";
				var lazada_uid = select_array[0];
			}else{
				var pre = "";
				var lazada_uid = $("#lazadaUid").val();
			}
			var primaryCategory = $(obj).val();
			if($.trim(primaryCategory) == ""){
				
			}else{
				//清除请选择
				$('#'+pre+'categoryHistoryId option[value=""]').remove();
				
				$.ajax({
					type:'POST',
					async:false,
					url:'/listing/linio-listing/get-all-categoryids',
					data:{
						lazada_uid:lazada_uid,
						primaryCategory:primaryCategory
					},
					dataType:'json',
					success:function(data){
						if(data.code == 200){
							if(id_length>1){//多站点发布
								linioListing.mutinitEditProduct(primaryCategory,lazada_uid,'selectHistory');
								linioListing.initCategory(data.data,lazada_uid);
							}else{
								linioListing.initEditProduct(primaryCategory);
								linioListing.initCategory(data.data);
							}
							$('#'+pre+'select_info').html("");
						}
					}
				});
			}
		},
		
		//初始化属性(多站点发布)
		mutinitEditProduct:function(categoryId,shop_id,type){//type防止多站点复制的时候，数据回填
			//加载属性
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/linio-listing/get-category-attrs',
				data:{
					lazada_uid:shop_id,
					primaryCategory:categoryId
				},
				dataType:'json',
				success:function(data){
					$("#"+shop_id+"_categoryId").val(categoryId);
					
					//填充产品属性
					linioListing.linioDataHandle(data,shop_id,type);
					
				}
			});
		},
		//初始化属性
		initEditProduct:function(categoryId){
			//加载属性
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/linio-listing/get-category-attrs',
				data:{
					lazada_uid:$("#lazadaUid").val(),
					primaryCategory:categoryId
				},
				dataType:'json',
				success:function(data){
					$("#categoryId").val(categoryId);
					
					//填充产品属性
					linioListing.linioDataHandle(data);	
				}
			});
		},
		
		//获取第一级
		initCategory:function(categoryList,shop_id){
			var pcid = "0";
			if(shop_id == undefined){//多站点发布
				var pre = "";
				var lazadaUid = $("#lazadaUid").val();
			}else{
				var pre = shop_id + "_";
				var lazadaUid = shop_id;
			}
			
			//获取第一级
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/linio-listing/get-category-tree',
				data:{
					lazada_uid:lazadaUid,
					parentCategoryId:pcid
				},
				dataType:'json',
				success:function(data){
					if(data.code == 200){
						if(shop_id == undefined){//多站点发布
							linioListing.categoryShow(data);
						}else{
							linioListing.categoryShow(data,null,shop_id);
						}
						$("."+pre+"category").html($("."+pre+"categoryChooseCrumbs").html());
						categoryList = eval(categoryList);
						if(categoryList != undefined){
							for(var i=categoryList.length-1;i>=0;i--){
								var categoryId = categoryList[i];
								$("."+pre+"categoryChooseOutDiv ."+pre+"categoryChooseInDiv span[categoryId="+categoryId+"]").parent().click();
								if(i==0){
									$("."+pre+"category").html($("."+pre+"categoryChooseCrumbs").html());	
									linioListing.getNewCategoryHistory(categoryId,'',pre);
								}
							}
						}
						
						
					}else{
						bootbox.alert(data.message);
					}
				},
				error:function(){
					bootbox.alert("网络错误！");
				}
			});
		},
		//类目显示,创建每一级项目调用，obj为空时，创建第一级目录
		categoryShow:function(arr,obj,shop_id){//多站点发布的时候,需要传入店铺id
			var str = linioListing.categoryHtml(arr);
			if (obj == '' || obj == undefined)
			{
				if(shop_id == undefined){
					var pre = "";
				}else{
					var pre = shop_id + "_";
				}
				$('#'+pre+'categoryChoose').find('div.'+pre+'categoryChooseInDiv').hide();
				$('#'+pre+'categoryChoose').find('div.'+pre+'categoryChooseInDiv').empty();
				obj = $('#'+pre+'categoryChoose').find('div.'+pre+'categoryChooseInDiv:first').show();
				obj = $('#'+pre+'categoryChoose').find('div.'+pre+'categoryChooseInDiv:first').html(str);
				$('.'+pre+'categoryChooseCrumbs').find('span').each(function(){
					if ($(this).attr('data-level') != 1)
					{
						$(this).empty();
					}else{
						$(this).html('未选择分类');
					}
				});
				
			}else{
				$(obj).next().empty();
				$(obj).next().nextAll().hide();
				$(obj).next().nextAll().empty();
				$(obj).next().html(str);
				$(obj).next().show();
			}
		},
		
		//一级目录下子项点击事件
		categoryClick:function(click_obj){
				var id_array = $(click_obj).parents('div[data-name="linio_float_class"]').attr('id').split('_');
				var id_length = id_array.length;
				if(id_length>1){
					var pre = id_array[0]+"_";
					var shop_id = id_array[0];
				}else{
					var pre = "";
					var shop_id = $("#lazadaUid").val();
				}
				//添加背景色
				$(click_obj).closest('div.'+pre+'categoryChooseInDiv').find('.categoryDiv').removeClass('bgColor5');
				$(click_obj).addClass('bgColor5');

				var isleaf = $(click_obj).find('span.glyphicon').attr('data-isleaf'),//判断有没子集，字符创
					obj = $(click_obj).closest('div.'+pre+'categoryChooseInDiv'),
					nameZh = $(click_obj).find('span.categoryNames').text(),
					id =  $(click_obj).find('span.categoryNames').attr('categoryId'),
					level = $(obj).attr('data-level');
				//判断有没子集
				if (isleaf == "false")//有子集
				{
					$('.selectCategoryId').removeClass('selectCategoryId');//
					$(click_obj).addClass('selectCategoryId');
					//加载类目列表
					$.ajax({
						type:'POST',
						async:false,
						url:'/listing/linio-listing/get-category-tree',
						data:{
							lazada_uid:shop_id,//站点
							parentCategoryId:id
						},
						dataType:'json',
						success:function(data){
							if(data.code == 200){
								if(id_length>1){
									linioListing.categoryShow(data,obj,shop_id);
								}else{
									linioListing.categoryShow(data,obj);
								}
							}else{
								bootbox.alert(data.message);
							}
						},
						error:function(){
							bootbox.alert("网络错误！");
						}
					});
					
				}else{
					$('.selectCategoryId').removeClass('selectCategoryId');
					$(click_obj).addClass('selectCategoryId');
					$(obj).nextAll().hide();
					$(obj).nextAll().empty();
				}
				//生成路径
				if (level == 1)
				{
					var str = '<span id="'+id+'">'+nameZh+'</span>';
				}else{
					var str = '<span id="'+id+'">&nbsp;>&nbsp;'+nameZh+'</span>';
				}
				$('.'+pre+'categoryChooseCrumbs').find('span[data-level="'+level+'"]').html(str);
				$('.'+pre+'categoryChooseCrumbs').find('span[data-level="'+level+'"]').nextAll().empty();
				
				if (level <= 3)
				{
					$('.'+pre+'categoryChooseMiddleDiv').css('width','auto');
				}else if (level == 4)
				{
					$('.'+pre+'categoryChooseMiddleDiv').css('width','1320px');
				}else{
					$('.'+pre+'categoryChooseMiddleDiv').css('width','1570px');
				}
				//设滚动条位置 fuyi
				var width = $('.'+pre+'categoryChooseMiddleDiv').width();
				$('.'+pre+'categoryChooseOutDiv').scrollLeft('1300');
		},
		//选择键的funtion
		selectedClik:function(click_obj){
			//多商品发布
				var id_array = $(click_obj).parents('div[data-name="linio_float_class"]').attr('id').split('_');
				var id_length = id_array.length;
				if(id_length>1){
					var pre = id_array[0]+"_";
					var shop_id = id_array[0];
				}else{
					var pre = "";
					var shop_id = $("#lazadaUid").val();
				}
				
//				var categoryId = $(".selectCategoryId .categoryNames").attr("categoryId");//选中目录的ID
				var categoryId = $(click_obj).parents('div[data-name="linio_float_class"]').find(".selectCategoryId .categoryNames").attr("categoryId");//选中目录的ID
				var oldCategoryId = $("#"+pre+"categoryId").val();
				var categoryName = $(click_obj).parents('div[data-name="linio_float_class"]').find(".selectCategoryId .categoryNames").text();//选中目录的name
				
				if(categoryId == oldCategoryId){
					$('#'+pre+'categoryChoose').modal('hide');//确定选择的为最后一项时关闭窗口
				}else{
					//判断是否是叶子节点
					var obj = $('#'+pre+'categoryChoose .'+pre+'categoryChooseOutDiv .'+pre+'categoryChooseInDiv span[categoryid="'+categoryId+'"]');
					var isleaf = $(obj).next().attr('data-isleaf');
					if(isleaf == "false"){
						var cateName = $(obj).text();
//						$.fn.message({type:"error",msg:"您选择的类目 \“"+cateName+"\” 还有子类目,请选择子类目！"});
						bootbox.alert("您选择的类目 \“"+cateName+"\” 还有子类目,请选择子类目！");
						return;
					}
					$.showLoading();
					$.ajax({
						type:'POST',
//						async:false,
						url:'/listing/linio-listing/get-category-attrs',
						data:{
							lazada_uid:shop_id,//站点
							primaryCategory:categoryId
						},
						dataType:'json',
						success:function(data){
							$.hideLoading();
							if(data.code == 200){
								$('#'+pre+'categoryChoose').modal('hide');
								$("#"+pre+"categoryId").val(categoryId);
								
								$("."+pre+"category").html($("."+pre+"categoryChooseCrumbs").html());
//								var cateName = $(obj).text();
//								var option_str = "<option value='"+cateName+"'>"+cateName+"</option>";
//								$("#categoryHistoryId").html(option_str);
								//显示类目属性
								if(id_length>1){
									linioListing.linioDataHandle(data,shop_id);
								}else{
									linioListing.linioDataHandle(data);
								}
								
								$('#'+pre+'select_info').html(" ");
								//选中下拉
								linioListing.getNewCategoryHistory(categoryId,categoryName,pre);
							}else{
								bootbox.alert(data.message);
							}
						},
						error:function(){
							$.hideLoading();
							bootbox.alert("网络错误！");
						}
					});
				};
		
		},
		
		
		//类目html生成
		categoryHtml:function(arr){
			var str = '';
			for (var i in arr.data)
			{
				if(arr.data.hasOwnProperty(i)){
					str += '<div class="categoryDiv"><span class="categoryNames" categoryId="'+arr.data[i].categoryId+'">'+arr.data[i].categoryName+'</span>';
					if (arr.data[i].isLeaf == 0)//数据库的bool值
					{
						str += '<span class="glyphicon glyphicon-chevron-right" data-isleaf="'+arr.data[i].isLeaf+'"></span></div>';
					}else{
						str += '<span class="glyphicon glyphicon-chevron-right" data-isleaf="'+arr.data[i].isLeaf+'" style="display:none;"></span></div>';
					}
				}
			};
			return str;
		},
		
//		initPhoto:function(){
//			$('div[role="image-uploader-container"]').batchImagesUploader({
//				localImageUploadOn : true,   
//				fromOtherImageLibOn: true , 
//				imagesMaxNum : 8,
//				fileMaxSize : 1024 , 		
//				fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
//				maxHeight : 100,
//				maxWidth : 100,
//				initImages : linioListing.existingImages, 
////				initImages : '[{thumbnail:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg",original:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg"},{thumbnail:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg",original:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg"}]',
//				fileName: 'product_photo_file',
//				onUploadFinish : function(imagesData , errorInfo){
//					//debugger
//				},
//				
//				onDelete : function(data){
//		//			debugger
//					$('.select_photo').each(function(){
//						if ($(this).parent('div').attr('upload-index') == undefined){
//							$(this).removeClass('select_photo');
//						}
//					});
//				}
//			});
//			
//			$('div[role=image-uploader-container] .image-item').click(function(){
//				$('div[role=image-uploader-container] .image-item a').removeClass('select_photo');
//				$(this).find('a').addClass('select_photo');
//			});
//			
//			if ($('.select_photo img').length ==0){
//				$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
//			}
//		},
		//多产品发布页面初始化
		mutPageBeginning:function(shop_id){
			if(shop_id == undefined){
				$(".label-check").each(function(){
					var shop_key = "shop_" + $(this).val();
					linioListing.dataSet.spanDiffCache[shop_key] = [];
					linioListing.dataSet.mutlzdSkuArr[shop_key]=[];
					linioListing.dataSet.mutlzdSkuObjArr[shop_key]={};
					linioListing.dataSet.mutkindeditorId[shop_key] = [];
					linioListing.dataSet.mutisShowAttr[shop_key] = [];// dzt20160104 站点更换要清空这个数组，例如NameMs 在my站点加在这数组里面，更换站点没有清空数组导致 非my站点提示 NameMs不能为空。
				});
			}else{
				var shop_key = "shop_" + shop_id;
				var store_info = "#" + shop_id + "-store-info";
				var var_info = "#" + shop_id + "-variant-info";
				$(store_info).find('table[cid="mutAttrShow_'+shop_id+'"]').empty();
				$(store_info).find('tr[cid="shippingTime"]').hide();
				$(store_info).find('table[cid="lzdAttrShow"]').empty();
				$(store_info).find('table[cid="lzdProductAttr"]').empty();
				$(store_info).find('table[cid="transportAttr"]').empty();
				$(var_info).find('div.lzdSkuInfo table').empty();
				linioListing.dataSet.spanDiffCache[shop_key] = [];
				linioListing.dataSet.mutlzdSkuArr[shop_key]=[];
				linioListing.dataSet.mutlzdSkuObjArr[shop_key]={};
				linioListing.dataSet.mutkindeditorId[shop_key] = [];
				linioListing.dataSet.mutisShowAttr[shop_key] = [];// dzt20160104 站点更换要清空这个数组，例如NameMs 在my站点加在这数组里面，更换站点没有清空数组导致 非my站点提示 NameMs不能为空。
			}
			var newArr = ["ShortDescription","PackageContent","Description","ProductWarranty"];
			for(var i = 0 ; i < newArr.length ; i++){
				linioListing.showOneKindeditor(newArr[i]);
			}
		},
		//页面初始化方法
		pageBeginning:function(){
			$(linioListing.dataSet.commonAttr).each(function(i){
				if($.inArray(linioListing.dataSet.commonAttr[i],linioListing.dataSet.mustShow) == -1){
					$('tr[cid="'+linioListing.dataSet.commonAttr[i]+'"]').hide();
				}
			});
			$('tr[cid="shippingTime"]').hide();
			$('table[cid="lzdAttrShow"]').empty();
			$('table[cid="lzdProductAttr"]').empty();
			$('table[cid="transportAttr"]').empty();
			$('div.lzdSkuInfo table').empty();
			linioListing.dataSet.lzdSkuArr=[];
			linioListing.dataSet.lzdSkuObjArr={};
			linioListing.dataSet.kindeditorId = [];
			linioListing.dataSet.isShowAttr = [];// dzt20160104 站点更换要清空这个数组，例如NameMs 在my站点加在这数组里面，更换站点没有清空数组导致 非my站点提示 NameMs不能为空。
			var newArr = ["ShortDescription","PackageContent","Description","ProductWarranty"];
			for(var i = 0 ; i < newArr.length ; i++){
				linioListing.showOneKindeditor(newArr[i]);
			}
			//pushLinioData(linioListing.dataSet.temporaryData.submitData,linioListing.dataSet.temporaryData.skuData);
		},
		//多个编辑器加载
		showKindeditor:function(arr){
			var kdeId = '';
			$(arr).each(function(i){kdeId == '' ? kdeId += ('#'+arr[i]) : kdeId += (', #'+arr[i]);});
			KindEditor.ready(function(K) {
	                window.editor = K.create(kdeId,linioListing.dataSet.kdeOption);
	        });
		},
		//单个编辑器加载
		showOneKindeditor:function(id){
			var kdeId = '#'+id;
			var options = JSON.parse(JSON.stringify(linioListing.dataSet.kdeOption));
			if(id == 'Description' || id == 'DescriptionMs' || id == 'DescriptionEn'){
				options.height = '360px';
			}
			
			KindEditor.ready(function(K) {
				window.editor = K.create(kdeId,options);
	        });
		},
		//数据处理
		lzdGetType:function(data){
			var type = null,attrType = null,isMust = null;
			//input
			if (data.AttributeType == 'value')
			{
				if(data.FeedName == "EligibleFreeShipping"){//这个选项特殊处理
					data.attrType = 'checkbox';
					data.Options = {Option: [{GlobalIdentifier: "", Name: "YES", isDefault: "1"}]};
					attrType = 'checkbox';
				}else{
					data.attrType = 'input';
					attrType = 'input';
				}
			}
			//kindeditor
			if (data.AttributeType == 'system')
			{
				data.attrType = 'kindeditor';
				attrType = 'kindeditor';
			}
			//checkbox
			if (data.AttributeType == 'multi_option')
			{
				data.attrType = 'checkbox';
				attrType = 'checkbox';
			}
			//select
			if (data.AttributeType == 'option')
			{
				data.attrType = 'select';
				attrType = 'select';
			}
			data.isMandatory == 1 ? isMust = 1 : isMust = 0;
			type = {attrType:attrType,isMust:isMust}
			data.isMust = isMust
			return type;
		},
		//处理数据
		linioDataHandle:function(data,shop_id,select_type){//select_type防止多站点复制，数据回填
			if(shop_id == undefined){
				linioListing.pageBeginning();
				var store_info = "#base-info";
			}else{
				linioListing.mutPageBeginning(shop_id);
				var store_info = "#" + shop_id + "-store-info";
				var shop_key = "shop_" + shop_id;
			}
			
			var type = null,arr = null,kdeId = '';
			$(data.data).each(function(i){
				if ($.inArray(data.data[i].FeedName,linioListing.dataSet.commonAttr) != -1 || $.inArray(data.data[i].FeedName,linioListing.dataSet.shippingTime) != -1 ){
					if(shop_id == undefined){
						linioListing.commonAttrHandle(data.data[i]);
					}else{
						linioListing.mutCommonAttrHandle(data.data[i],shop_id);
					}
				}else if($.inArray(data.data[i].FeedName,linioListing.dataSet.dontShowAttr) != -1 || $.inArray(data.data[i].FeedName,linioListing.dataSet.commonShowAttr) != -1 || $.inArray(data.data[i].FeedName,linioListing.dataSet.packingSize) != -1){
					if(data.data[i].FeedName == 'TaxClass'){
//						var optionStr = '';
						$(store_info).find('tr[cid="'+data.data[i].FeedName+'"]').show();
						var optionStr = '<option>请选择</option>';
						$(data.data[i].Options.Option).each(function(j){
							optionStr += linioListing.dataSet.option.formatOther(data.data[i].Options.Option[j]);
						});
						$(store_info).find('tr[cid="'+data.data[i].FeedName+'"] select').html("");
						$(store_info).find('tr[cid="'+data.data[i].FeedName+'"] select').append(optionStr);
						$(store_info).find('tr[cid="'+data.data[i].FeedName+'"] select').val('IVA 0%');
					}else if(data.data[i].FeedName == "Brand"){
						$(store_info).find('tr[cid="'+data.data[i].FeedName+'"]').show();
					}
					return;
				}else{
					//获得展示类型及是否为必填项
					type = linioListing.lzdGetType(data.data[i]);
					//去单条数据处理
					if(shop_id == undefined){
						if(data.data[i].FeedName == 'Variation'){
							var skuName = data.data[i].FeedName;
							linioListing.dataSet.lzdSkuArr.push(skuName);
							linioListing.dataSet.lzdSkuObjArr[skuName] = data.data[i];
						}else if($.inArray(data.data[i].FeedName,linioListing.dataSet.otherAttr) != -1){
							linioListing.lzdOneDataHandle(data.data[i],type,"lzdAttrShow");//过滤部分信息
						}else if($.inArray(data.data[i].FeedName,linioListing.dataSet.transportAttr) != -1){
							linioListing.lzdOneDataHandle(data.data[i],type,"transportAttr");
						}else{
							linioListing.lzdOneDataHandle(data.data[i],type,"lzdProductAttr");
						}
					}else{
						if(data.data[i].FeedName == 'Variation'){
							var skuName = data.data[i].FeedName;
							linioListing.dataSet.mutlzdSkuArr[shop_key].push(skuName);
							linioListing.dataSet.mutlzdSkuObjArr[shop_key][skuName] = data.data[i];
						}else if($.inArray(data.data[i].FeedName,linioListing.dataSet.mutotherAttr) != -1){
							linioListing.mutlzdOneDataHandle(data.data[i],type,"lzdAttrShow",shop_id);//过滤部分信息
						}else if($.inArray(data.data[i].FeedName,linioListing.dataSet.transportAttr) != -1){
							linioListing.mutlzdOneDataHandle(data.data[i],type,"transportAttr",shop_id);
						}else{
							linioListing.mutlzdOneDataHandle(data.data[i],type,"lzdProductAttr",shop_id);
						}
					}
					
				}
			});
			
			if(shop_id == undefined){
				$(linioListing.dataSet.isShowAttr).each(function(i){
					var type = $('tr[cid="'+linioListing.dataSet.isShowAttr[i]+'"]').attr('attrtype');
					if(type == 'kindeditor'){
						linioListing.dataSet.kindeditorId.push(linioListing.dataSet.isShowAttr[i]);
					}
				});
				arr = linioListing.dataSet.kindeditorId;
				/*if (arr.length > 0)
				{
					linioListing.showKindeditor(arr);
				}*/
				if(arr.length > 0){
					for(var i = 0 ; i < arr.length ; i++){
						linioListing.showOneKindeditor(arr[i]);
					}
				}
				
				linioListing.dataSet.kindeditorId.push("ShortDescription");
				linioListing.dataSet.kindeditorId.push("PackageContent");
				linioListing.dataSet.kindeditorId.push("Description");
				linioListing.dataSet.kindeditorId.push("ProductWarranty");
				linioListing.lzdSkuBorn();
				linioListing.pushLinioData(linioListing.dataSet.temporaryData.submitData,linioListing.dataSet.temporaryData.skuData);
			}else{//多站点发布
				$(linioListing.dataSet.mutisShowAttr[shop_key]).each(function(i){
					var type = $(store_info).find('tr[cid="'+linioListing.dataSet.mutisShowAttr[shop_key][i]+'"]').attr('attrtype');
					if(type == 'kindeditor'){
						linioListing.dataSet.mutkindeditorId[shop_key].push(linioListing.dataSet.mutisShowAttr[shop_key][i]);
					}
				});
				arr = linioListing.dataSet.mutkindeditorId[shop_key];
				/*if (arr.length > 0)
				{
					linioListing.showKindeditor(arr);
				}*/
				if(arr.length > 0){
					for(var i = 0 ; i < arr.length ; i++){
						linioListing.showOneKindeditor(arr[i]);
					}
				}
				linioListing.dataSet.mutkindeditorId[shop_key].push("ShortDescription");
				linioListing.dataSet.mutkindeditorId[shop_key].push("PackageContent");
				linioListing.dataSet.mutkindeditorId[shop_key].push("Description");
				linioListing.dataSet.mutkindeditorId[shop_key].push("ProductWarranty");
				linioListing.mutlzdSkuBorn(shop_id);
				if(select_type == undefined){//type防止多站点复制，数据回填
					
				}else if(select_type == 'firstPush'){
					linioListing.pushLinioData(linioListing.dataSet.temporaryData.submitData,linioListing.dataSet.temporaryData.skuData,shop_id);
				}
				
			}
			
		},
		//单条数据处理(多产品发布)
		mutlzdOneDataHandle:function(data,type,location,shop_id){
			var store_info = "#" + shop_id + "-store-info"; 
			var obj = linioListing.lzdAttrHtmlBorn(data,type,shop_id),arr = obj.defaultValue;
			$(store_info).find('table[cid="'+location+'"]').append(obj.str);
			if (arr != null && arr.length > 0)
			{
				linioListing.mutlzdDefaultValueHandle(data,type,obj.defaultValue,store_info);
			}
		},
		//单条数据处理
		lzdOneDataHandle:function(data,type,location){
			var obj = linioListing.lzdAttrHtmlBorn(data,type),arr = obj.defaultValue;
			$('table[cid="'+location+'"]').append(obj.str);
			if (arr != null && arr.length > 0)
			{
				linioListing.lzdDefaultValueHandle(data,type,obj.defaultValue);
			}
		},
		//默认值处理(多站点发布)
		mutlzdDefaultValueHandle:function(data,type,value,store_info){
			switch(type.attrType){
				case 'input':
					$(store_info).find('tr[cid="'+data.FeedName+'"]').find('input[type="text"]').val(value[0]);
					break;
				case 'kindeditor':
					break;
				case 'checkbox':
					$(value).each(function(i){
						$(store_info).find('tr[cid="'+data.FeedName+'"]').find('input[value="'+value[i]+'"]').prop('checked',true);
					})
					break;
				case 'select':
					$(store_info).find('tr[cid="'+data.FeedName+'"]').find('select').val(value[0]);
					break;
			}
		},
		//默认值处理
		lzdDefaultValueHandle:function(data,type,value){
			switch(type.attrType){
				case 'input':
					$('tr[cid="'+data.FeedName+'"]').find('input[type="text"]').val(value[0]);
					break;
				case 'kindeditor':
					break;
				case 'checkbox':
					$(value).each(function(i){
						$('tr[cid="'+data.FeedName+'"]').find('input[value="'+value[i]+'"]').prop('checked',true);
					})
					break;
				case 'select':
					$('tr[cid="'+data.FeedName+'"]').find('select').val(value[0]);
					break;
			}
		},
		//html生成(多站点发布)
		lzdAttrHtmlBorn:function(data,type,shop_id){
			var str = '',arr = data.Options,optionStr = '',defaultValue = null;
			str += linioListing.dataSet.lzdAttrTit_1.formatOther(data);
			if (type.isMust == 1)
			{
				str += linioListing.dataSet.isMust;
			}
			str += linioListing.dataSet.lzdAttrTit_2.formatOther(data);
			switch(type.attrType){
				case 'input'://对DeliveryTimeSupplier做默认处理
					if(data.FeedName=='DeliveryTimeSupplier'){
						defaultValue = [];
						defaultValue.push('2');
					}
					str += linioListing.dataSet.input;
					break;
				case 'kindeditor':
					str += linioListing.dataSet.kindeditor.formatOther(data);
					if(shop_id != undefined){
						var shop_key = "shop_" + shop_id;
						linioListing.dataSet.mutkindeditorId[shop_key].push(data.FeedName);
					}else{
						linioListing.dataSet.kindeditorId.push(data.FeedName);
					}
					
					break;
				case 'checkbox':
					defaultValue = [];
					if(typeof arr.Option != "undefined" && typeof arr.Option.Name != "undefined"){// dzt20160425 发现linio option只有一个的时候arr.Option的结构不是二维数组，而是一维
						var tempOptionArr = [];
						tempOptionArr.push(arr.Option);
						arr.Option = tempOptionArr;
					}
					
					$(arr.Option).each(function(i){
						str += linioListing.dataSet.checkbox.formatOther(arr.Option[i]);
						if (arr.Option[i].isDefault == 1)
						{
							defaultValue.push(arr.Option[i].Name)
						}
					})
					break;
				case 'select':
					defaultValue = [];
					if(typeof arr.Option != "undefined" && typeof arr.Option.Name != "undefined"){// dzt20160425 发现linio option只有一个的时候arr.Option的结构不是二维数组，而是一维
						var tempOptionArr = [];
						tempOptionArr.push(arr.Option);
						arr.Option = tempOptionArr;
					}
					
					$(arr.Option).each(function(i){
						optionStr += linioListing.dataSet.option.formatOther(arr.Option[i]);
						if (arr.Option[i].isDefault == 1)
						{
							defaultValue.push(arr.Option[i].Name)
						}
					})
					data.optionStr = optionStr
					str += linioListing.dataSet.select.formatOther(data);
					break;
			}
			str += linioListing.dataSet.lzdAttrEnd;
			return {str:str,defaultValue:defaultValue};
		},
		//常用属性处理(多产品发布)
		mutCommonAttrHandle:function(data,shop_id){
			var store_info = "#" + shop_id + "-store-info";
			var shop_key = "shop_" + shop_id;
			if($.inArray(data.FeedName,linioListing.dataSet.commonAttr) != -1){
				$(store_info).find('tr[cid="'+data.FeedName+'"]').show();
				if(data.FeedName == 'ColorFamily'){
					var str = '';
					$(data.Options.Option).each(function(j){
						str += linioListing.dataSet.checkbox.formatOther(data.Options.Option[j]);
					})
					$(store_info).find('tr[cid="'+data.FeedName+'"] .secondTd').empty();
					$(store_info).find('tr[cid="'+data.FeedName+'"] .secondTd').append(str);
				}
				linioListing.mutCommonAttrOptionHandle(data,shop_id);
				linioListing.dataSet.mutisShowAttr[shop_key].push(data.FeedName);
			};
			if($.inArray(data.FeedName,linioListing.dataSet.shippingTime) != -1){
				$(store_info).find('tr[cid="shippingTime"]').show();
				if($.inArray('shippingTime',linioListing.dataSet.mutisShowAttr) == -1){
					linioListing.dataSet.mutisShowAttr[shop_key].push('shippingTime');
				}
			};
			if($.inArray(data.FeedName,linioListing.dataSet.packingSize) != -1){
				$(store_info).find('tr[cid="packingSize"]').show();
				if($.inArray('packingSize',linioListing.dataSet.mutisShowAttr) == -1){
					linioListing.dataSet.mutisShowAttr[shop_key].push('packingSize');
				}
			};
		},
		//常用属性处理
		commonAttrHandle:function(data){
			if($.inArray(data.FeedName,linioListing.dataSet.commonAttr) != -1){
				$('tr[cid="'+data.FeedName+'"]').show();
				if(data.FeedName == 'ColorFamily'){
					var str = '';
					$(data.Options.Option).each(function(j){
						str += linioListing.dataSet.checkbox.formatOther(data.Options.Option[j]);
					})
					$('tr[cid="'+data.FeedName+'"] .secondTd').empty();
					$('tr[cid="'+data.FeedName+'"] .secondTd').append(str);
				}
				linioListing.commonAttrOptionHandle(data);
				linioListing.dataSet.isShowAttr.push(data.FeedName);
			};
			if($.inArray(data.FeedName,linioListing.dataSet.shippingTime) != -1){
				$('tr[cid="shippingTime"]').show();
				if($.inArray('shippingTime',linioListing.dataSet.isShowAttr) == -1){
					linioListing.dataSet.isShowAttr.push('shippingTime');
				}
			};
			if($.inArray(data.FeedName,linioListing.dataSet.packingSize) != -1){
				$('tr[cid="packingSize"]').show();
				if($.inArray('packingSize',linioListing.dataSet.isShowAttr) == -1){
					linioListing.dataSet.isShowAttr.push('packingSize');
				}
			};
		},
		//常用属性select的option处理(多产品发布)
		commonAttrOptionHandle:function(data){
			if(data.FeedName == 'Warranty' || data.FeedName == 'WarrantyType' || data.FeedName == 'TaxClass' || data.FeedName == 'ColorFamily'){
				var optionStr = '';
				$(data.Options.Option).each(function(i){
					optionStr += linioListing.dataSet.option.formatOther(data.Options.Option[i]);
				});
				$('tr[cid="'+data.FeedName+'"] select').append(optionStr);
			}
		},
		mutCommonAttrOptionHandle:function(data,shop_id){
			var store_info = "#" + shop_id + "-store-info";
			if(data.FeedName == 'Warranty' || data.FeedName == 'WarrantyType' || data.FeedName == 'TaxClass' || data.FeedName == 'ColorFamily'){
				var optionStr = '';
				$(data.Options.Option).each(function(i){
					optionStr += linioListing.dataSet.option.formatOther(data.Options.Option[i]);
				});
				$(store_info).find('tr[cid="'+data.FeedName+'"] select').append(optionStr);
			}
		},
		//变种列表生成格式判断并生成(多站点发布)
		mutlzdSkuBorn:function(shop_id){
			var shop_key = "shop_" + shop_id; 
			var store_info = "#" + shop_id + "-store-info";
			var skuData = null,str = null,showType = null;
			if(linioListing.dataSet.mutlzdSkuArr[shop_key].length > 0){
				showType = 1;
				skuData = linioListing.dataSet.mutlzdSkuObjArr[shop_key].Variation;
				skuData.showType = showType;
			}else{
				showType = 0;
			}
			//if(skuData == null){return;};
			linioListing.dataSet.mutlzdSkuObjArr[shop_key].skuShowType = showType;
			str = linioListing.mutlzdSkuThHtmlBorn(skuData,shop_key);
			str += linioListing.mutlzdSkuTdHtmlBorn(skuData,shop_key);
			$('div[id="'+shop_id+'-variant-info"]').find('div.lzdSkuInfo table').append(str);
			//console.log(dataSet.lzdSkuObjArr.Variation);
			if(linioListing.dataSet.mutlzdSkuObjArr[shop_key].Variation != undefined){
				if(linioListing.dataSet.mutlzdSkuObjArr[shop_key].Variation.attrType == 'select'){
					$('div[id="'+shop_id+'-variant-info"]').find('div.lzdSkuInfo table').find('[cid="variationEditSpan"]').hide();
				};
			};
		},
		//变种列表生成格式判断并生成
		lzdSkuBorn:function(){
			var skuData = null,str = null,showType = null;
			if(linioListing.dataSet.lzdSkuArr.length > 0){
				showType = 1;
				skuData = linioListing.dataSet.lzdSkuObjArr.Variation;
				skuData.showType = showType;
			}else{
				showType = 0;
			}
			//if(skuData == null){return;};
			linioListing.dataSet.lzdSkuObjArr.skuShowType = showType;
			str = linioListing.lzdSkuThHtmlBorn(skuData);
			str += linioListing.lzdSkuTdHtmlBorn(skuData);
			$('div.lzdSkuInfo table').append(str);
			//console.log(dataSet.lzdSkuObjArr.Variation);
			if(linioListing.dataSet.lzdSkuObjArr.Variation != undefined){
				if(linioListing.dataSet.lzdSkuObjArr.Variation.attrType == 'select'){
					$('div.lzdSkuInfo table').find('[cid="variationEditSpan"]').hide();
				};
			};
		},
		//变种列表ThHtml生成(多站点发布)
		mutlzdSkuThHtmlBorn:function(skuData,shop_key){
			var str = '',showType = linioListing.dataSet.mutlzdSkuObjArr[shop_key].skuShowType;
			str += linioListing.dataSet.lzdSkuTrStar;
			showType == 1 ? str += linioListing.dataSet.mutlzdSkuTh_1.formatOther(skuData) + linioListing.dataSet.lzdSkuTh_2 : str += linioListing.dataSet.lzdSkuTh_2 ;
			str += linioListing.dataSet.lzdSkuTrEnd;
			return str;
		},
		//变种列表ThHtml生成
		lzdSkuThHtmlBorn:function(skuData){
			var str = '',showType = linioListing.dataSet.lzdSkuObjArr.skuShowType;
			str += linioListing.dataSet.lzdSkuTrStar;
			showType == 1 ? str += linioListing.dataSet.lzdSkuTh_1.formatOther(skuData) + linioListing.dataSet.lzdSkuTh_2 : str += linioListing.dataSet.lzdSkuTh_2 ;
			str += linioListing.dataSet.lzdSkuTrEnd;
			return str;
		},
		//变种列表TdHtml生成(多站点发布)
		mutlzdSkuTdHtmlBorn:function(skuData,shop_key){
			var str = '',showHtml = '',optionStr = '',dataType = '',showType = linioListing.dataSet.mutlzdSkuObjArr[shop_key].skuShowType;
			if(skuData != null){
				dataType = skuData.attrType;
				if(dataType == 'input'){
					showHtml = linioListing.dataSet.input_2;
					skuData.showHtml = showHtml;
					
				};
				if(dataType == 'select'){
					// dzt20160425 发现lazada也是属性 option只有一个的时候Option的结构不是二维数组，而是一维
					if(typeof skuData.Options.Option != "undefined" && typeof skuData.Options.Option.Name != "undefined"){
						var tempOptionArr = [];
						tempOptionArr.push(skuData.Options.Option);
						skuData.Options.Option = tempOptionArr;
					}
					$(skuData.Options.Option).each(function(i){optionStr += linioListing.dataSet.option.formatOther(skuData.Options.Option[i])});
					skuData.optionStr = optionStr;
					showHtml = linioListing.dataSet.skuSelect.formatOther(skuData);
					skuData.showHtml = showHtml;
				};
			}
			str += linioListing.dataSet.lzdSkuTrStar;
			showType == 1 ? str += linioListing.dataSet.lzdSkuTd_1.formatOther(skuData) + linioListing.dataSet.lzdSkuTd_2 : str += linioListing.dataSet.lzdSkuTd_2 ;
			str += linioListing.dataSet.lzdSkuTrEnd;
			return str;
		},
		//变种列表TdHtml生成
		lzdSkuTdHtmlBorn:function(skuData){
			var str = '',showHtml = '',optionStr = '',dataType = '',showType = linioListing.dataSet.lzdSkuObjArr.skuShowType;
			if(skuData != null){
				dataType = skuData.attrType;
				if(dataType == 'input'){
					showHtml = linioListing.dataSet.input_2;
					skuData.showHtml = showHtml;
					
				};
				if(dataType == 'select'){
					// dzt20160425 发现lazada也是属性 option只有一个的时候Option的结构不是二维数组，而是一维
					if(typeof skuData.Options.Option != "undefined" && typeof skuData.Options.Option.Name != "undefined"){
						var tempOptionArr = [];
						tempOptionArr.push(skuData.Options.Option);
						skuData.Options.Option = tempOptionArr;
					}
					
					$(skuData.Options.Option).each(function(i){optionStr += linioListing.dataSet.option.formatOther(skuData.Options.Option[i])});
					skuData.optionStr = optionStr;
					showHtml = linioListing.dataSet.skuSelect.formatOther(skuData);
					skuData.showHtml = showHtml;
				};
			}
			str += linioListing.dataSet.lzdSkuTrStar;
			showType == 1 ? str += linioListing.dataSet.lzdSkuTd_1.formatOther(skuData) + linioListing.dataSet.lzdSkuTd_2 : str += linioListing.dataSet.lzdSkuTd_2 ;
			str += linioListing.dataSet.lzdSkuTrEnd;
			return str;
		},
		//数据回填  start 
	 	//多个富文本同时赋值arr为数组对象
		multiplekindeditorPush:function(arr){
			KindEditor.ready(function(K) {
				$(arr).each(function(i){
					window.editor = K.html(arr[i].id,arr[i].value);
				})
			});
		},
		//单个富文本赋值id和value为int型(没有用到)
		singlekindeditorPush:function(id,value){
			KindEditor.ready(function(K) {
				window.editor = K.html(arr[i].id,arr[i].value);
			});
		},
		pushLinioData:function(submitData,skuData,shop_id){
			var all_attrObj = submitData,
				skuAttrArr = skuData,
				type = null,
				kindeditorArr = [];
			linioListing.existingImages = [];
			//属性回填
			for(var attrObj in all_attrObj){
				for(var name in all_attrObj[attrObj]){
					if($.inArray(name,linioListing.dataSet.packingSize) != -1 || $.inArray(name,linioListing.dataSet.shippingTime) != -1){
						$('input[cid="'+name+'"]').val(all_attrObj[attrObj][name]);
					}else if(name != 'Product_photo_primary_thumbnail' && name != 'Product_photo_others_thumbnail'){
//						if(name == 'Product_photo_primary'){
//							var photo = {};
//							$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
////							$('div[role=image-uploader-container] .image-item:eq(0) a img').attr('src',all_attrObj[attrObj][name]);
//							if(typeof(all_attrObj[attrObj]['Product_photo_primary_thumbnail']) == "undefined"){//判断是否有缩略图
//								photo.thumbnail = all_attrObj[attrObj][name];
//							}else{//没有缩略图
//								photo.thumbnail = all_attrObj[attrObj]['Product_photo_primary_thumbnail'];
//							}
//							photo.original = all_attrObj[attrObj][name];
//							linioListing.existingImages.push(photo);
//						}else if(name == 'Product_photo_others'){
//							var other_address = all_attrObj[attrObj][name];
//							var address_array = other_address.split('@,@');
//							if(typeof(all_attrObj[attrObj]['Product_photo_others_thumbnail']) != "undefined"){//判断是否有有缩略图
//								var other_address_thumbnail = all_attrObj[attrObj]['Product_photo_others_thumbnail'];
//								var address_array_thumbnail = other_address_thumbnail.split('@,@');
//								if(address_array.length != address_array_thumbnail.length){
//									bootbox.alert("图片获取信息有误！");
//									return null;
//								}else{
//									for(var t=0;t<address_array.length;t++){
//										var photo = {};
//										photo.thumbnail = address_array_thumbnail[t];
//										photo.original = address_array[t];
//										linioListing.existingImages.push(photo);
//									}
//								}
//							}else{//没有缩略图
//								for(var t=0;t<address_array.length;t++){
//									var photo = {};
//									photo.thumbnail = address_array[t];
//									photo.original = address_array[t];
//									linioListing.existingImages.push(photo);
//								}
//							}
//							
//							
////							$('.image-item a').not('.select_photo').each(function(i){
////								$(this).find('img').attr('src',address_array[i]);
////							});
//						}
//						else{
							type = $('tr[cid="'+name+'"]').attr('attrtype');
							switch(type){
								case 'input':
									$('tr[cid="'+name+'"] input').val(all_attrObj[attrObj][name]);
									break;
								case 'kindeditor':
									var obj = {};
									obj.id = '#'+name;
									obj.value = all_attrObj[attrObj][name];
									kindeditorArr.push(obj);
									break;
								case 'checkbox':
									var valueArr = all_attrObj[attrObj][name].split(',');
									if(name == 'EligibleFreeShipping'){//对这个属性的默认处理
										if(valueArr[0] == ''){
											$('tr[cid="'+name+'"] input[value="YES"]').prop('checked',false);
										}
									}else{
										$(valueArr).each(function(i){$('tr[cid="'+name+'"] input[value="'+valueArr[i]+'"]').prop('checked',true);});
									}
									break;
								case 'select':
									var value = '';
									all_attrObj[attrObj][name] == '' ? value = '请选择' : value = all_attrObj[attrObj][name] ;
									$('tr[cid="'+name+'"] select').val(value);
									break;
							}
//						}
						
					}
					
				};
			};
			linioListing.multiplekindeditorPush(kindeditorArr);
			//变种回填
			var trLength = $('div.lzdSkuInfo table').find('tr').length,
				skuLength = skuAttrArr.length;
			if(trLength - 1 < skuLength){
				if(shop_id == undefined){
					var data = linioListing.dataSet.lzdSkuObjArr.Variation,
					str = linioListing.lzdSkuTdHtmlBorn(data);
				}else{
					var shop_key = "shop_" + shop_id;
					var data = linioListing.dataSet.mutlzdSkuObjArr[shop_key].Variation,
					str = linioListing.mutlzdSkuTdHtmlBorn(data,shop_key);
				}
				
				for(var i = 0 ; i < (skuLength - trLength + 1) ; i++){
					$('div.lzdSkuInfo table').append(str);
				}
			};
			$(skuAttrArr).each(function(i){
				var index = i + 1,
					trObj = $('div.lzdSkuInfo table tr').get(index);
				$(trObj).find('td[data-name="variation"] input').val(skuAttrArr[i].Variation);
				skuAttrArr[i].Variation == '' ? $(trObj).find('td[data-name="variation"] select').val('请选择') : $(trObj).find('td[data-name="variation"] select').val(skuAttrArr[i].Variation);
				$(trObj).find('td[data-name="sellerSku"] input').val(skuAttrArr[i].SellerSku);
				$(trObj).find('td[data-name="quantity"] input').val(skuAttrArr[i].Quantity);
				$(trObj).find('td[data-name="price"] input').val(skuAttrArr[i].Price);
				skuAttrArr[i].SalePrice != 0 ? $(trObj).find('td[data-name="salePrice"] input').val(skuAttrArr[i].SalePrice) : $(trObj).find('td[data-name="salePrice"] input').val() ;
				$(trObj).find('td[data-name="saleStartDate"] input').val(skuAttrArr[i].SaleStartDate);
				$(trObj).find('td[data-name="saleEndDate"] input').val(skuAttrArr[i].SaleEndDate);
				$(trObj).find('td[data-name="productGroup"] input').val(skuAttrArr[i].ProductId);
				$(trObj).attr('id',skuAttrArr[i].Id);
				skuAttrArr[i].ProductGroup != null ? $('div.lzdSkuInfo table select[cid="productGroupSelect"]').val(skuAttrArr[i].ProductGroup) : $('div.lzdSkuInfo table select[cid="productGroupSelect"]').val('EAN') ;
				skuAttrArr[i].ProductGroup != null ? $(trObj).find('td[data-name="productGroup"]').attr('name',skuAttrArr[i].ProductGroup) : $(trObj).find('td[data-name="productGroup"]').attr('name','EAN') ;
				if(skuAttrArr[i].dxmState == 'online'){
					$(trObj).find('td[data-name="sellerSku"] input').attr('disabled',true);
				}
			});
			//ipt计数
			linioListing.iptValLength();
		},
		//页面计数方法
		iptValLength:function(){
			var num = '';
			$('div.lzdProductTitle').each(function(){
				num = $(this).find('input[type="text"]').val().length;
				$(this).find('span.unm').html(num);
			})
		},
		//保存产品(多站点发布)
		mutsave:function(op){
			
			//店铺id
			if($('#productId').val()==''){
				var productId = 0;
			}else{
				var productId = $('#productId').val();
			}
			var shopId_array = [];
			var shop_station = {};//站点
			$('div[data-id="main-variant"]').each(function(){//保存数据以站点为单位保存数据
				var save_array = $(this).attr('id').split('-');
				if(save_array.length != 0){
					var shop_site ='';
					shop_site = $(this).data('skuname').split('_');//站点
					shop_station[save_array[0]] = shop_site[0];
					shopId_array.push(save_array[0]);
				}
			});
			
//			var other_shopId_array = [];//同站点多个帐号
//			$('.label-check').each(function(){//数据组织，同一战点其他帐号+'@'+ 有数据填写的站点
//				if(($(this).prop('checked'))&&($.inArray($(this).val(),shopId_array) == -1)){
//					for(var i in shopId_array){
//						if($(this).data("site") == $('div[data-name="shop_'+shopId_array[i]+'"]').data("site")){
//							other_shopId_array.push($(this).val() + '@' +  shopId_array[i]);
//						}
//					}
//				}
//			});
			
//			var shopId = $("#lazadaUid").val();
			if(shopId_array.length == 0){
//				$.fn.message({type:"error",msg:"请先选择店铺！"});
				$('#select_shop_info').html("请勾选linio店铺!");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return ;
			}
			
			//类目
			var categoryId_array = {};
			var categoryId_length = 0;
			for(var i=0;i<shopId_array.length;i++){
				var station_key = shop_station[shopId_array[i]];
				categoryId_array[station_key] = $("#"+shopId_array[i]+"_categoryId").val();
				categoryId_length = categoryId_length + 1;
			};
//			var categoryId = $("#categoryId").val();
			if(categoryId_length == 0 ||categoryId_length != shopId_array.length){//其中一个目录没选上
//				$.fn.message({type:"error",msg:"请选择类目！"});
				bootbox.alert("有站点没有请选择类目，请检查！");
//				$('#select_info').html("请选择类目！");
//				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return;
			}
			
			// dzt20160115 for 复制产品有原来目录属性的值留在submitData，导致最后也保存到复制后的目录里面，所以这里保存之前先清空数组
			linioListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
			
			//产品图片
			var returnVal = linioListing.setPicAttr();
			if(returnVal == false){
//				bootbox.alert("没有设置主图片！");
				$('#upload_image_info').html("没有设置主图片！");
				$('html,body').animate({scrollTop:$('div[id="image-info"]').offset().top}, 800);
				return ;
			}else{
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_primary']=$('#Product_photo_primary').val();
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_others']=$('#Product_photo_others').val();
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_primary_thumbnail']=$('#Product_photo_primary_thumbnail').val();
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_others_thumbnail']=$('#Product_photo_others_thumbnail').val();
			}
			
			//类目属性
			var productDataStr_array = {};
			var productDataStr_length = 0;
			for(var i=0;i<shopId_array.length;i++){
				var return_productDataStr ="";
				var station_key = shop_station[shopId_array[i]];
				return_productDataStr = linioListing.mutgetSubmitData(shopId_array[i]);
				if($.trim(return_productDataStr) != ""){
					productDataStr_array[station_key] = return_productDataStr;
					productDataStr_length = productDataStr_length + 1;
				}
				
			};
			if(productDataStr_length == 0 || productDataStr_length != shopId_array.length){//其中有产品没有保存成功
				//$.fn.message({type:"error",msg:"产品属性不能为空！"});
				return ;
			}
			
			$('div.lzdProductTitle').parents('tr').each(function(){//检查标题字数是否超过255
				var tr_name = $(this).attr('name');
				$('span[id="'+tr_name+'"]').parents('tr').remove();
			})
			var title_check = "NO";
			$('div.lzdProductTitle').parents('tr').each(function(){//检查标题字数是否超过255
				if($(this).css('display') != "none"){
					if($(this).find('input').val().length > 255){
						var tr_name = $(this).attr('name');
						var alert_title_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+tr_name+'" style="color:red;"></span></td></tr>';
						$(this).after(alert_title_message);
						$('span[id="'+tr_name+'"]').html(tr_name + "长度超过255！");
						$('html,body').animate({scrollTop:$(this).offset().top}, 800);
						title_check = "YES";
						return false;
					}
				}
			});
			if(title_check == "YES"){//检查标题字数是否超过255
				return ;
			}
			
//			linioListing.validBrand = false;
			linioListing.validBrand = true;
			// 检查品牌是否存在品牌库
			$.showLoading();
			var err_message = [];
			for(var i=0;i<shopId_array.length;i++){
				var store_info = "#" + shopId_array[i] + "-store-info";
				$.ajax({
					async: false,
					type: "post",
					url: "/listing/linio-listing/get-brands",
					data: { lazada_uid : shopId_array[i],name : $(store_info).find('[cid="Brand"]>.secondTd>input').val() , mode:'eq'},
					dataType:'json',
					success:function(data){
//						$.hideLoading();
						if(data.code == 400){
							err_message.push(shopId_array[i] + "店铺添加的品牌不在Linio规定范围内，请与Linio官方联系");
							linioListing.validBrand = false;
						}else{//刊登成功
							// 拼接提示框信息
//							linioListing.validBrand = true;
						}
					},
					error:function(){
						err_message.push("网络错误, 请稍后再试！");
						linioListing.validBrand = false;
//						$.hideLoading();
//						bootbox.alert("网络错误, 请稍后再试！");
					}
				});
			}
			
			$.hideLoading();
			if(err_message.length > 0){
				bootbox.alert(err_message[0]);
			}
			if(linioListing.validBrand == false) return false;
			
			//类目顺序
			var category_all = {};
			var category_length = 0;
			for(var i=0;i<shopId_array.length;i++){
				var category_array = new Array();
				$('#'+shopId_array[i]+'_category-info span[data-level]').each(function(){
					if($(this).html() != ""){
						var la = $(this).find("span").eq(0).attr("id");
						category_array.push(la);
					}
				});
				if(category_array.lenght == 0){
					$('#'+shopId_array[i]+'_select_info').html("没有选择类目顺序！");
					$('html,body').animate({scrollTop:$('#'+shopId_array[i]+'_select_info').offset().top}, 800);
					return;
				}else{
					var re_category_array = category_array.reverse(); //倒序
					var categories = JSON.stringify(re_category_array);
				};
				var station_key = shop_station[shopId_array[i]];
				category_all[station_key] = categories;
				category_length = category_length + 1;
			};
			
			//变种
			var skus_array = {};
			var skus_length = 0;
			for(var i=0;i<shopId_array.length;i++){
				var return_skusArray ="";
				return_skusArray = linioListing.getSkuListData(shopId_array[i]);
				if($.trim(return_skusArray) != ""){
					var station_key = shop_station[shopId_array[i]];
					skus_array[station_key] = return_skusArray;
					skus_length = skus_length + 1;
				}else{
					return;
				}
				
//				skus_array[shopId_array[i]] = linioListing.getSkuListData(shopId_array[i]);
			};
//			var skus = linioListing.getSkuListData();
			if(skus_length == 0 ||skus_length != shopId_array.length){
				//$.fn.message({type:"error",msg:"产品变种不能为空！"});
				return ;
			}
			//组织数据
			if((shopId_array.length == skus_length)&&(shopId_array.length == productDataStr_length)&&(category_length == categoryId_length)){
				var allSiteData = [];
				$('.label-check').each(function(){
					if($(this).prop('checked')){
						var oneSiteShopId ='';//uid
						var oneSiteStation = '';//站点
						var oneSiteData = {};
						oneSiteShopId = $(this).val();
						oneSiteStation = $(this).data('site');
						oneSiteData = {
							categories:category_all[oneSiteStation],
							lazada_uid:oneSiteShopId,
							primaryCategory:categoryId_array[oneSiteStation],
							productDataStr:productDataStr_array[oneSiteStation],
							skus:skus_array[oneSiteStation]
						};
						allSiteData.push(oneSiteData);
					}
				});
//				for(var i in shopId_array){
//					var oneSiteData = {};
//					oneSiteData = {
//						categories:category_all[shopId_array[i]],
//						lazada_uid:shopId_array[i],
//						primaryCategory:categoryId_array[shopId_array[i]],
//						productDataStr:productDataStr_array[shopId_array[i]],
//						skus:skus_array[shopId_array[i]]
//					};
//					allSiteData.push(oneSiteData);
//				};
			}else{
				return ;
			}
//			debugger;
			$.showLoading();
			$.ajax({
				type:'POST',
				url: "/listing/linio-listing/save-muti-site",
				data:{
//					id:productId,
//					categories:categories,
//					lazada_uid:shopId,
//					fullCid:$.trim($("#fullCid").data("id")),
//					primaryCategory:categoryId,
//			        sourceUrl:$("#sourceUrl").val(),
//			        mainImage:mainImage,
//			        extraImages:extraImages,
//					imgUrl:imgUrl,
//					productDataStr:productDataStr,
//					skus:skus,
					allSiteData:allSiteData,
					op:op
				},
				dataType:'json',
				timeout:60000,
				success:function(data){
					$.hideLoading();
					if(data != null){
						if(data.code == 400){//刊登失败
//							if(op == 1){
//								$("#msgText").text("您已成功添加产品。");
//							}else{
//								$("#msgText").text("您的产品已成功放入发布队列中，稍后请刷新查看！");
//								$("#msgBtnDel").click(function(){
//									window.close();
//								});
//							}
//							$("#msgBtnAdd").show();
//							$("#msgModal").modal("show");\
							bootbox.alert(data.message);
						}else{//刊登成功
							// 拼接提示框信息
//							var msg = "<span style='color:red'>" + data.msg + "</span><br/>";
//							$("#msgBtnAdd").hide();
//							$("#msgText").html(msg);
//							$("#msgModal").modal("show");
							bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
									window.location.href="/listing/linio-listing/publish";
									$.showLoading();
								}
						   });
						}
					}
				},
				error:function(){
					$.hideLoading();
//					$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
					bootbox.alert("网络错误, 请稍后再试！");
				}
			});
		},
		//保存产品
		save:function(op){
			//店铺id
			if($('#productId').val()==''){
				var productId = 0;
			}else{
				var productId = $('#productId').val();
			}
			var shopId = $("#lazadaUid").val();
			if($.trim(shopId) == ""){
//				$.fn.message({type:"error",msg:"请先选择店铺！"});
				$('#select_shop_info').html("请选择Linio店铺!");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return ;
			}
			
			//类目
			var categoryId = $("#categoryId").val();
			if($.trim(categoryId) == "" || $.trim(categoryId) == 0){
//				$.fn.message({type:"error",msg:"请选择类目！"});
//				bootbox.alert("请选择类目！");
				$('#select_info').html("请选择类目！");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return;
			}
			
			// dzt20160115 for 复制产品有原来目录属性的值留在submitData，导致最后也保存到复制后的目录里面，所以这里保存之前先清空数组
			linioListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
			
			//产品图片
			var returnVal = linioListing.setPicAttr();
			if(returnVal == false){
//				bootbox.alert("没有设置主图片！");
				$('#upload_image_info').html("没有设置主图片！");
				$('html,body').animate({scrollTop:$('div[id="image-info"]').offset().top}, 800);
				return ;
			}else{
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_primary']=$('#Product_photo_primary').val();
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_others']=$('#Product_photo_others').val();
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_primary_thumbnail']=$('#Product_photo_primary_thumbnail').val();
				linioListing.dataSet.temporaryData.submitData['image-info']['Product_photo_others_thumbnail']=$('#Product_photo_others_thumbnail').val();
			}
			
			//类目属性
			var productDataStr = linioListing.getSubmitData();
			if($.trim(productDataStr) == ""){
				//$.fn.message({type:"error",msg:"产品属性不能为空！"});
				return ;
			}
			
			$('div.lzdProductTitle').parents('tr').each(function(){//检查标题字数是否超过255
				var tr_name = $(this).attr('name');
				$('span[id="'+tr_name+'"]').parents('tr').remove();
			})
			var title_check = "NO";
			$('div.lzdProductTitle').parents('tr').each(function(){//检查标题字数是否超过255
				if($(this).css('display') != "none"){
					if($(this).find('input').val().length > 255){
						var tr_name = $(this).attr('name');
						var alert_title_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+tr_name+'" style="color:red;"></span></td></tr>';
						$(this).after(alert_title_message);
						$('span[id="'+tr_name+'"]').html(tr_name + "长度超过255！");
						$('html,body').animate({scrollTop:$(this).offset().top}, 800);
						title_check = "YES";
						return false;
					}
				}
			});
			if(title_check == "YES"){//检查标题字数是否超过255
				return ;
			}
			
			linioListing.validBrand = false;
			// 检查品牌是否存在品牌库
			$.showLoading();
			$.ajax({
				async: false,
				type: "post",
				url: "/listing/linio-listing/get-brands",
				data: { lazada_uid : $("#lazadaUid").val(),name : $('[cid="Brand"]>.secondTd>input').val() , mode:'eq'},
				dataType:'json',
				success:function(data){
					$.hideLoading();
					if(data.code == 400){
						bootbox.alert("您添加的品牌不在Linio规定范围内，请与Linio官方联系");
						linioListing.validBrand = false;
					}else{//刊登成功
						// 拼接提示框信息
						linioListing.validBrand = true;
					}
				},
				error:function(){
					$.hideLoading();
					bootbox.alert("网络错误, 请稍后再试！");
				}
			});
			
			if(linioListing.validBrand == false) return false;
			
			//类目顺序
			var category_array = new Array();
			$('#category-info span[data-level]').each(function(){
				if($(this).html() != ""){
					var la = $(this).find("span").eq(0).attr("id");
					category_array.push(la);
				}
			});
			if(category_array.lenght == 0){
				$('#select_info').html("没有选择类目顺序！");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return;
			}else{
				var re_category_array = category_array.reverse(); //倒序
				var categories = JSON.stringify(re_category_array);
			};
			//变种
			var skus = linioListing.getSkuListData();
			if($.trim(skus) == ""){
				//$.fn.message({type:"error",msg:"产品变种不能为空！"});
				return ;
			}
			$.showLoading();
			$.ajax({
				type:'POST',
				url: "/listing/linio-listing/save-product",
				data:{
					id:productId,
					categories:categories,
					lazada_uid:shopId,
//					fullCid:$.trim($("#fullCid").data("id")),
					primaryCategory:categoryId,
//			        sourceUrl:$("#sourceUrl").val(),
//			        mainImage:mainImage,
//			        extraImages:extraImages,
//					imgUrl:imgUrl,
					productDataStr:productDataStr,
					skus:skus,
					op:op
				},
				dataType:'json',
				timeout:60000,
				success:function(data){
					$.hideLoading();
					if(data != null){
						if(data.code == 400){//刊登失败
//							if(op == 1){
//								$("#msgText").text("您已成功添加产品。");
//							}else{
//								$("#msgText").text("您的产品已成功放入发布队列中，稍后请刷新查看！");
//								$("#msgBtnDel").click(function(){
//									window.close();
//								});
//							}
//							$("#msgBtnAdd").show();
//							$("#msgModal").modal("show");\
							bootbox.alert(data.message);
						}else{//刊登成功
							// 拼接提示框信息
//							var msg = "<span style='color:red'>" + data.msg + "</span><br/>";
//							$("#msgBtnAdd").hide();
//							$("#msgText").html(msg);
//							$("#msgModal").modal("show");
							bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
									window.location.href="/listing/linio-listing/publish";
									$.showLoading();
								}
						   });
						}
					}
				},
				error:function(){
					$.hideLoading();
//					$.fn.message({type:"error",msg:"网络错误, 请稍后再试！"});
					bootbox.alert("网络错误, 请稍后再试！");
				}
			});
		},
		//设置图片的地址
		setPicAttr:function (){
			
			var imageObj = $('.ui-sortable-handle');
			var photoOthers = new Array();
			var photoOthers_thumbnail = new Array();
			
			var returnVal = false;
			if(imageObj.length > 0){
				imageObj.each(function(){
					if($(this).find('input[type=radio]').prop("checked") == true){
						returnVal = true;
						var main_src = $(this).find('input[name="extra_images[]"]').val();
						var main_thumb_src = $(this).find('img').attr('src');
						$('#Product_photo_primary').val(main_src);
						$('#Product_photo_primary_thumbnail').val(main_thumb_src);
					}else{
						var other_src = $(this).find('input[name="extra_images[]"]').val();
						var other_thumb_src = $(this).find('img').attr('src');
						photoOthers.push(other_src);
						photoOthers_thumbnail.push(other_thumb_src);
					}
				});
			} else {
				$('#Product_photo_primary').val('');
				$('#Product_photo_primary_thumbnail').val('');
			}
			
			$('#Product_photo_others').val(photoOthers.join('@,@'));
			$('#Product_photo_others_thumbnail').val(photoOthers_thumbnail.join('@,@'));
			return returnVal;
		},
		//提取数据(多站点发布)
		mutgetSubmitData:function(shopId){
			linioListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');//除了图片初始化
			linioListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
			linioListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
			
			var shop_key = "shop_" + shopId;
			var store_info = "#"+ shopId + "-store-info"; 
			var attrType = null;
//			linioListing.dataSet.temporaryData.submitData = {};
			linioListing.dataSet.temporaryData.skuData = [];
			linioListing.dataSet.isMustAttrArr = [];
			linioListing.dataSet.spanCache = [];
			if(linioListing.dataSet.spanCache.length > 0){
				$(store_info).find('span[id="'+linioListing.dataSet.spanCache[0]+'"]').parents('tr').remove();
				linioListing.dataSet.spanCache = [];
			};
			if(linioListing.dataSet.spanDiffCache[shop_key].length > 0){//不是公共信息移除警告提示
				$(store_info).find('span[id="'+linioListing.dataSet.spanDiffCache[shop_key][0]+'"]').parents('tr').remove();
				linioListing.dataSet.spanDiffCache[shop_key] = [];
			};
			//常用属性性ger
			$(linioListing.dataSet.mutisShowAttr[shop_key]).each(function(i){
				if(linioListing.dataSet.mutisShowAttr[shop_key][i] == 'shippingTime'){
					$(linioListing.dataSet.shippingTime).each(function(j){
//						linioListing.dataSet.temporaryData.submitData[linioListing.dataSet.shippingTime[j]] = $('input[cid="'+linioListing.dataSet.shippingTime[j]+'"]').val();
						linioListing.dataSet.temporaryData.submitData['shipping-info'][linioListing.dataSet.shippingTime[j]] = $('input[cid="'+linioListing.dataSet.shippingTime[j]+'"]').val();
					})
					
				}else if(linioListing.dataSet.mutisShowAttr[shop_key][i] == "ProductWarranty" || linioListing.dataSet.mutisShowAttr[shop_key][i] == "SupplierWarrantyMonths"){//多站点发布中这两个是公告属性
					attrType = $('tr[cid="'+linioListing.dataSet.mutisShowAttr[shop_key][i]+'"]').attr('attrtype');
					linioListing.mutsubimtDataBorn(attrType,$('tr[cid="'+linioListing.dataSet.mutisShowAttr[shop_key][i]+'"]'),shopId);
				}else{
					attrType = $(store_info).find('tr[cid="'+linioListing.dataSet.mutisShowAttr[shop_key][i]+'"]').attr('attrtype');
					linioListing.mutsubimtDataBorn(attrType,$(store_info).find('tr[cid="'+linioListing.dataSet.mutisShowAttr[shop_key][i]+'"]'),shopId);
				}
			});
			$(linioListing.dataSet.packingSize).each(function(i){
				var cid = $('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').attr('cid');
				if($('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').val() == ''){
					linioListing.dataSet.isMustAttrArr.push(cid);
				}
//				linioListing.dataSet.temporaryData.submitData[linioListing.dataSet.packingSize[i]] = $('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').val();
				linioListing.dataSet.temporaryData.submitData['shipping-info'][linioListing.dataSet.packingSize[i]] = $('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').val();

			});
			$(linioListing.dataSet.commonShowAttr).each(function(i){
				if(linioListing.dataSet.commonShowAttr[i] == "TaxClass" || linioListing.dataSet.commonShowAttr[i] == "Brand"){//多站点发布中这两个不是公告属性
					attrType = $(store_info).find('tr[cid="'+linioListing.dataSet.commonShowAttr[i]+'"]').attr('attrtype');
					linioListing.mutsubimtDataBorn(attrType,$(store_info).find('tr[cid="'+linioListing.dataSet.commonShowAttr[i]+'"]'),shopId);
				}else{
					attrType = $('tr[cid="'+linioListing.dataSet.commonShowAttr[i]+'"]').attr('attrtype');
					linioListing.mutsubimtDataBorn(attrType,$('tr[cid="'+linioListing.dataSet.commonShowAttr[i]+'"]'),shopId);
				}
				
			});
			//非常用属性get
			$(store_info).find('table[cid="lzdAttrShow"] tr').each(function(){
				attrType = $(this).attr('attrType');
				linioListing.mutsubimtDataBorn(attrType,$(this),shopId);
			});
			$(store_info).find('table[cid="lzdProductAttr"] tr').each(function(){
				attrType = $(this).attr('attrType');
				linioListing.mutsubimtDataBorn(attrType,$(this),shopId);
			});
			//运输方式的保存
			if($(store_info).find('table[cid="transportAttr"] tr').length != 0){
				$(store_info).find('table[cid="transportAttr"] tr').each(function(){
					attrType = $(this).attr('attrType');
					linioListing.mutsubimtDataBorn(attrType,$(this),shopId);
				});
			}
			
			//验证
			if(linioListing.dataSet.isMustAttrArr.length > 0){
//				var arrorName = '';
				$(linioListing.dataSet.isMustAttrArr).each(function(k){
//					arrorName == '' ? arrorName += linioListing.dataSet.isMustAttrArr[k] : arrorName += '，'+linioListing.dataSet.isMustAttrArr[k] ;
						if(linioListing.dataSet.isMustAttrArr[k]=='PackageLength' || linioListing.dataSet.isMustAttrArr[k]=='PackageWidth'  || linioListing.dataSet.isMustAttrArr[k]=='PackageHeight' ){
							var tr_parent = $('input[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]').parents('tr');
							var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+linioListing.dataSet.isMustAttrArr[k]+'" style="color:red;"></span></td></tr>';
							$(tr_parent).after(alert_message);
							$('span[id="'+linioListing.dataSet.isMustAttrArr[k]+'"]').html($('input[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]').attr("name") + "不能为空！");
							$('html,body').animate({scrollTop:$(tr_parent).offset().top}, 800);
							linioListing.dataSet.spanCache.push(linioListing.dataSet.isMustAttrArr[k]);
							return false;
						}else{
							aa = true;
							if($.inArray(linioListing.dataSet.isMustAttrArr[k],linioListing.dataSet.mutOutMustAtrr) != -1){//可能有些必填消息不在多站点中
								var tr_parent = $("#all_info").find('tr[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]');
							}else{
								var tr_parent = $(store_info).find('tr[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]');
							}
							var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+linioListing.dataSet.isMustAttrArr[k]+'" style="color:red;"></span></td></tr>';
							$(tr_parent).after(alert_message);
							
							if($.inArray(linioListing.dataSet.isMustAttrArr[k],linioListing.dataSet.mutOutMustAtrr) != -1){
								$("#all_info").find('span[id="'+linioListing.dataSet.isMustAttrArr[k]+'"]').html(tr_parent.attr("name") + "不能为空！");
							}else{
								$(store_info).find('span[id="'+linioListing.dataSet.isMustAttrArr[k]+'"]').html(tr_parent.attr("name") + "不能为空！");
							}
							
							$('html,body').animate({scrollTop:$(tr_parent).offset().top}, 800);
							linioListing.dataSet.spanDiffCache[shop_key].push(linioListing.dataSet.isMustAttrArr[k]);
							return false;
						}
						
				})
//				$.fn.message({type:"error", msg:arrorName+"的值不能为空!"});
//				bootbox.alert(arrorName+"的值不能为空!");
				return null;
			}
			if(linioListing.dataSet.temporaryData.submitData.Description != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.Description) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品描述中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述中不能包含中文字符!");
					return null;
				}
			}
//			if(linioListing.dataSet.temporaryData.submitData.DescriptionMs != undefined){
//				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.DescriptionMs) == 1){
////					$.fn.message({type:"error", msg:"保存失败！产品描述（马来语）中不能包含中文字符!"});
//					bootbox.alert("保存失败！产品描述（马来语）中不能包含中文字符!");
//					return null;
//				}
//			}
//			if(linioListing.dataSet.temporaryData.submitData.DescriptionEn != undefined){
//				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.DescriptionEn) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品描述（英语）中不能包含中文字符!"});
//					bootbox.alert("保存失败！产品描述（英语）中不能包含中文字符!");
//					return null;
//				}			
//			}
//			if(linioListing.dataSet.temporaryData.submitData.Name != undefined){
//				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.Name) == 1){
////					$.fn.message({type:"error", msg:"保存失败！产品标题中不能包含中文字符!"});
//					bootbox.alert("保存失败！产品标题中不能包含中文字符!");
//					return null;
//				}
//			}
//			if(linioListing.dataSet.temporaryData.submitData.NameMs != undefined){
//				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.NameMs) == 1){
////					$.fn.message({type:"error", msg:"保存失败！产品标题（马来语）中不能包含中文字符!"});
//					bootbox.alert("保存失败！产品标题（马来语）中不能包含中文字符!");
//					return null;
//				}
//			}
//			if(linioListing.dataSet.temporaryData.submitData.NameEn != undefined){
//				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.NameEn) == 1){
////					$.fn.message({type:"error", msg:"保存失败！产品标题（英语）中不能包含中文字符!"});
//					bootbox.alert("保存失败！产品标题（英语）中不能包含中文字符!");
//					return null;
//				}
//			}
			//验证end
			return JSON.stringify(linioListing.dataSet.temporaryData.submitData);
			
		},
		//提取数据
		getSubmitData:function(){
			var attrType = null;
//			linioListing.dataSet.temporaryData.submitData = {};
			linioListing.dataSet.temporaryData.skuData = [];
			linioListing.dataSet.isMustAttrArr = [];
			if(linioListing.dataSet.spanCache.length > 0){
				$('span[id="'+linioListing.dataSet.spanCache[0]+'"]').parents('tr').remove();
				linioListing.dataSet.spanCache = [];
			};
			//常用属性性ger
			$(linioListing.dataSet.isShowAttr).each(function(i){
				if(linioListing.dataSet.isShowAttr[i] == 'shippingTime'){
					$(linioListing.dataSet.shippingTime).each(function(j){
//						linioListing.dataSet.temporaryData.submitData[linioListing.dataSet.shippingTime[j]] = $('input[cid="'+linioListing.dataSet.shippingTime[j]+'"]').val();
						linioListing.dataSet.temporaryData.submitData['shipping-info'][linioListing.dataSet.shippingTime[j]] = $('input[cid="'+linioListing.dataSet.shippingTime[j]+'"]').val();
					})
					
				}else{
					attrType = $('tr[cid="'+linioListing.dataSet.isShowAttr[i]+'"]').attr('attrtype');
					linioListing.subimtDataBorn(attrType,$('tr[cid="'+linioListing.dataSet.isShowAttr[i]+'"]'));
				}
			});
			$(linioListing.dataSet.packingSize).each(function(i){
				var cid = $('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').attr('cid');
				if($('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').val() == ''){
					linioListing.dataSet.isMustAttrArr.push(cid);
				}
//				linioListing.dataSet.temporaryData.submitData[linioListing.dataSet.packingSize[i]] = $('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').val();
				linioListing.dataSet.temporaryData.submitData['shipping-info'][linioListing.dataSet.packingSize[i]] = $('input[cid="'+linioListing.dataSet.packingSize[i]+'"]').val();

			});
			$(linioListing.dataSet.commonShowAttr).each(function(i){
				attrType = $('tr[cid="'+linioListing.dataSet.commonShowAttr[i]+'"]').attr('attrtype');
				linioListing.subimtDataBorn(attrType,$('tr[cid="'+linioListing.dataSet.commonShowAttr[i]+'"]'));
			});
			//非常用属性get
			$('table[cid="lzdAttrShow"] tr').each(function(){
				attrType = $(this).attr('attrType');
				linioListing.subimtDataBorn(attrType,$(this));
			});
			$('table[cid="lzdProductAttr"] tr').each(function(){
				attrType = $(this).attr('attrType');
				linioListing.subimtDataBorn(attrType,$(this));
			});
			//运输方式的保存
			if($('table[cid="transportAttr"] tr').length != 0){
				$('table[cid="transportAttr"] tr').each(function(){
					attrType = $(this).attr('attrType');
					linioListing.subimtDataBorn(attrType,$(this));
				});
			}
			
			//验证
			if(linioListing.dataSet.isMustAttrArr.length > 0){
//				var arrorName = '';
				$(linioListing.dataSet.isMustAttrArr).each(function(k){
//					arrorName == '' ? arrorName += linioListing.dataSet.isMustAttrArr[k] : arrorName += '，'+linioListing.dataSet.isMustAttrArr[k] ;
						if(linioListing.dataSet.isMustAttrArr[k]=='PackageLength' || linioListing.dataSet.isMustAttrArr[k]=='PackageWidth'  || linioListing.dataSet.isMustAttrArr[k]=='PackageHeight' ){
							var tr_parent = $('input[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]').parents('tr');
							var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+linioListing.dataSet.isMustAttrArr[k]+'" style="color:red;"></span></td></tr>';
							$(tr_parent).after(alert_message);
							$('span[id="'+linioListing.dataSet.isMustAttrArr[k]+'"]').html($('input[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]').attr("name") + "不能为空！");
							$('html,body').animate({scrollTop:$(tr_parent).offset().top}, 800);
							linioListing.dataSet.spanCache.push(linioListing.dataSet.isMustAttrArr[k]);
							return false;
						}else{
							aa = true;
							var tr_parent = $('tr[cid="'+linioListing.dataSet.isMustAttrArr[k]+'"]');
							var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+linioListing.dataSet.isMustAttrArr[k]+'" style="color:red;"></span></td></tr>';
							$(tr_parent).after(alert_message);
							$('span[id="'+linioListing.dataSet.isMustAttrArr[k]+'"]').html(tr_parent.attr("name") + "不能为空！");
							$('html,body').animate({scrollTop:$(tr_parent).offset().top}, 800);
							linioListing.dataSet.spanCache.push(linioListing.dataSet.isMustAttrArr[k]);
							return false;
						}
						
				})
//				$.fn.message({type:"error", msg:arrorName+"的值不能为空!"});
//				bootbox.alert(arrorName+"的值不能为空!");
				return null;
			}
			if(linioListing.dataSet.temporaryData.submitData.Description != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.Description) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品描述中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述中不能包含中文字符!");
					return null;
				}
			}
			if(linioListing.dataSet.temporaryData.submitData.DescriptionMs != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.DescriptionMs) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品描述（马来语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述（马来语）中不能包含中文字符!");
					return null;
				}
			}
			if(linioListing.dataSet.temporaryData.submitData.DescriptionEn != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.DescriptionEn) == 1){
					$.fn.message({type:"error", msg:"保存失败！产品描述（英语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述（英语）中不能包含中文字符!");
					return null;
				}			
			}
			if(linioListing.dataSet.temporaryData.submitData.Name != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.Name) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品标题中不能包含中文字符!"});
					bootbox.alert("保存失败！产品标题中不能包含中文字符!");
					return null;
				}
			}
			if(linioListing.dataSet.temporaryData.submitData.NameMs != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.NameMs) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品标题（马来语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品标题（马来语）中不能包含中文字符!");
					return null;
				}
			}
			if(linioListing.dataSet.temporaryData.submitData.NameEn != undefined){
				if(linioListing.isContainChinese(linioListing.dataSet.temporaryData.submitData.NameEn) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品标题（英语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品标题（英语）中不能包含中文字符!");
					return null;
				}
			}
			//验证end
			return JSON.stringify(linioListing.dataSet.temporaryData.submitData);
			
		},
		//submit数据(多站点发布)
		mutsubimtDataBorn:function(type,obj,shop_id){
			var shop_key = "shop_" + shop_id;
			var store_info = shop_id + "-store-info";
			var name = '',value = null,attrObj = {},isMust = $(obj).attr('isMust'),label = $(obj).attr('cid');
			switch(type){
				case 'input':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == store_info){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					value = '';
					value = $(obj).find('input[type="text"]').val();
					break;
				case 'kindeditor':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){//为了将这两个数据保存在base-info
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == store_info){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					KindEditor.ready(function(K) {
						window.editor = K.sync('#'+name);
					});
					value = '';
					value = $('#'+name).val();
					break;
				case 'checkbox':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == store_info){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					value = '';
					$(obj).find('input[type="checkbox"]').each(function(){
						if (this.checked)
						{
							value == '' ? value += $(this).val() : value += "," + $(this).val();
						}
					});
					break;
				case 'select':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == store_info){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					value = '';
					$(obj).find('select').val() == '请选择' ? value = '' : value = $(obj).find('select').val();
					break;
			}
			if(isMust == 1 && value == ''){
				linioListing.dataSet.isMustAttrArr.push(label);
				//$.fn.message({type:"error", msg:label+"的值不能为空!"});
			} 
			linioListing.dataSet.temporaryData.submitData[info_id][name] = value;
		},
		//submit数据
		subimtDataBorn:function(type,obj){
			var name = '',value = null,attrObj = {},isMust = $(obj).attr('isMust'),label = $(obj).attr('cid');
			switch(type){
				case 'input':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == "product-info"){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					value = '';
					value = $(obj).find('input[type="text"]').val();
					break;
				case 'kindeditor':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){//为了将这两个数据保存在base-info
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == "product-info"){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					KindEditor.ready(function(K) {
						window.editor = K.sync('#'+name);
					});
					value = '';
					value = $('#'+name).val();
					break;
				case 'checkbox':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == "product-info"){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					value = '';
					$(obj).find('input[type="checkbox"]').each(function(){
						if (this.checked)
						{
							value == '' ? value += $(this).val() : value += "," + $(this).val();
						}
					});
					break;
				case 'select':
					name = $(obj).attr('cid');
					if($.inArray(name,linioListing.dataSet.transportAttr) != -1){
						info_id = "base-info";
					}else{
						info_id = $(obj).parents(".search-info").attr("id");
						if(info_id == "product-info"){//将商品属性保存到base-info中
							info_id = "base-info";
						}
					}
					value = '';
					$(obj).find('select').val() == '请选择' ? value = '' : value = $(obj).find('select').val();
					break;
			}
			if(isMust == 1 && value == ''){
				linioListing.dataSet.isMustAttrArr.push(label);
				//$.fn.message({type:"error", msg:label+"的值不能为空!"});
			} 
			linioListing.dataSet.temporaryData.submitData[info_id][name] = value;
		},
		//中文判断
		isContainChinese:function(value){
			var typeA = 0,validate = /[^\x00-\xff]/ig;//中文和全角字符 
			if(value.match(validate)){
				typeA = 1;
			}
			return typeA;
		},
		//属性get
		//sku列表get
		getSkuListData:function(shop_id){
			if(shop_id == undefined){
				var varit_info = "#variant-info"; 
			}else{
				var varit_info = "#" + shop_id + "-variant-info";
				linioListing.dataSet.temporaryData.skuData = []; 
			}
			var variation = null,
				dataType = null,
				sellerSku = null,
				productGroup = null,
				productId = null,
				quantity = null,
				price = null,
				salePrice = null,
				saleStartDate = null,
				saleEndDate = null,
				id = null,
				arr = [];
			var nowDate = new Date().format("yyyy-MM-dd");
			if(linioListing.priceIsNullVerification(varit_info) == 1){
				bootbox.alert("保存失败！价格不能为空！");
				return null;
			}
			if(linioListing.quantityIsNullVerification(varit_info) == 1){
				bootbox.alert("保存失败！库存不能为空！");
				return null;
			}
			if(linioListing.productIdIsNullVerification(varit_info) == 1){
				bootbox.alert("保存失败！EAN/UPC/ISBN不能为空！");
				return null;
			}
			if(linioListing.skuIsNullVerification(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！SKU不能为空！"});
				bootbox.alert("保存失败！SKU不能为空！");
				return null;
			}
			if(linioListing.skuIsRepeat(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！产品SKU不能重复！"});
				bootbox.alert("保存失败！产品SKU不能重复！");
				return null;
			}
			if(linioListing.variationRepeat(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！产品SKU不能重复！"});
				bootbox.alert("保存失败！产品Variation不能重复！");
				return null;
			}
			if(linioListing.priceComparisonSalePrice(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！促销价必须小于价格！"});
				bootbox.alert("保存失败！促销价必须小于价格！");
				return null;
			}
			if(linioListing.skuIsContainChinese(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！SKU中不能包含中文字符！"});
				bootbox.alert("保存失败！SKU中不能包含中文字符！");
				return null;
			}
			if(linioListing.dateVerificationA(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！有促销价的促销时间不能为空！"});
				bootbox.alert("保存失败！请完整输入促销价格以及促销时间！");
				return null;
			}
			if(linioListing.dateVerificationB(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！促销开始时间不能小于当前时间！"});
				bootbox.alert("保存失败！促销开始时间不能小于当前时间！");
				return null;
			}
			if(linioListing.dateVerificationC(varit_info) == 1){
//				$.fn.message({type:"error", msg:"保存失败！促销结束时间不能小于促销开始时间！"});
				bootbox.alert("保存失败！促销结束时间不能小于促销开始时间！");
				return null;
			}
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(i){
				if(i != 0){
					if($(this).find('td[data-id="sku"]').length != 0){
						dataType = $(this).find('td[data-id="sku"]').attr('data-type');
						dataType == 'input' ? variation = $(this).find('td[data-id="sku"] input').val() : variation = $(this).find('td[data-id="sku"] select').val();
					} 
					sellerSku = $(this).find('td[data-name="sellerSku"] input').val();
					productGroup = $(this).find('td[data-name="productGroup"]').attr('name');
					productId = $(this).find('td[data-name="productGroup"] input').val();
					quantity = $(this).find('td[data-name="quantity"] input').val();
					price = $(this).find('td[data-name="price"] input').val();
					salePrice = $(this).find('td[data-name="salePrice"] input').val();
					saleStartDate = $(this).find('td[data-name="saleStartDate"] input').val();
					saleEndDate = $(this).find('td[data-name="saleEndDate"] input').val();
					id = $(this).attr('id');
					var obj = {};
					if(variation == '请选择'){variation = ''};
					obj.Variation = variation;
					obj.SellerSku = sellerSku;
					obj.ProductGroup = productGroup;
					obj.ProductId = productId;
					obj.Quantity = quantity;
					obj.Price = price;
					obj.SalePrice = salePrice;
					obj.SaleStartDate = saleStartDate;
					obj.SaleEndDate = saleEndDate;
					obj.Id = id;
					linioListing.dataSet.temporaryData.skuData.push(obj);
				}
			})
			return JSON.stringify(linioListing.dataSet.temporaryData.skuData);
			//console.log(linioListing.dataSet.temporaryData)
			
		},
		//价格是否为空
		priceIsNullVerification:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var num = $(this).find('td[data-name="price"] input[type="text"]').val();
					if (num == '' || num == undefined){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//库存是否为空
		quantityIsNullVerification:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var num = $(this).find('td[data-name="quantity"] input[type="text"]').val();
					if (num == '' || num == undefined){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//productID是否为空
		productIdIsNullVerification:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var num = $(this).find('td[data-name="productGroup"] input[type="text"]').val();
					if (num == '' || num == undefined){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//sku是否为空
		skuIsNullVerification:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var sku = $(this).find('td[data-name="sellerSku"] input[type="text"]').val();
					if (sku == '' || sku == undefined){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//sku是否重复
		skuIsRepeat:function(varit_info){
			var typeA = 0,arr = [];
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var value = $(this).find('td[data-name="sellerSku"] input[type="text"]').val();
					if ($.inArray(value,arr) != -1){
						typeA = 1;
					}else{
						arr.push(value);
					}
				}
			});
			return typeA;
		},
		//variation
		variationRepeat:function(varit_info){
			var typeA = 0,arr = [];
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var type = $(this).find('td[data-name="variation"]').attr('data-type');
	                var value = $(this).find('td[data-name="variation"]').find(type).val();
					if ($.inArray(value,arr) != -1){
						typeA = 1;
					}else{
						arr.push(value);
					}
				}
			});
			return typeA;
		},
		//价格和促销价比对
		priceComparisonSalePrice:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var price = $(this).find('td[data-name="price"] input[type="text"]').val(),
						salePrice = $(this).find('td[data-name="salePrice"] input[type="text"]').val();
					if (Number(salePrice) > Number(price)){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//sku是否有中文
		skuIsContainChinese:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var value = $(this).find('td[data-name="sellerSku"] input[type="text"]').val();
					if (linioListing.isContainChinese(value) == 1){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//促销时间验证
		dateVerificationA:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var salePrice = $(this).find('td[data-name="salePrice"] input[type="text"]').val(),
						saleStartDate = $(this).find('td[data-name="saleStartDate"] input[type="text"]').val(),
						saleEndDate = $(this).find('td[data-name="saleEndDate"] input[type="text"]').val();
					
					// 有SalePrice SaleStartDate SaleEndDate 三个必须同时存在,否则报错
					if((!(salePrice || saleStartDate || saleEndDate)) || (salePrice && saleStartDate && saleEndDate)){
						return typeA;
					}
					
					typeA = 1;
				}
			});
			return typeA;
		},
		dateVerificationB:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var saleStartDate = $(this).find('td[data-name="saleStartDate"] input[type="text"]').val(),
						nowDate = new Date().format("yyyy-MM-dd"),
						verificationVal = linioListing.compareDate(nowDate,saleStartDate);
					if (verificationVal == 1){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		dateVerificationC:function(varit_info){
			var typeA = 0;
			$(varit_info).find('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var saleStartDate = $(this).find('td[data-name="saleStartDate"] input[type="text"]').val(),
						saleEndDate = $(this).find('td[data-name="saleEndDate"] input[type="text"]').val(),
						 verificationVal = 0;
		                
		                if (saleStartDate == '' && saleEndDate == '') {
		                	return typeA;
		                }
		                verificationVal = linioListing.compareDate(saleStartDate, saleEndDate)
					if (verificationVal != -1){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		///////////////////引用相关//////////////////
		replaceFloat:function(e){
			var value = $(e).val().replace(/[^0-9.]/g,'');		        
	        $(e).val(value);
		},
		
		replaceNumber:function(e){
			var value = $(e).val().replace(/[^0-9]/g,'');		        
	        $(e).val(value);
		},
		replaceSize:function(e){
			var value = $(e).val().replace(/[^0-9.x]/g,'');		        
	        $(e).val(value);
		},
		//时间比较
		compareDate:function(startTime, endTime){
		    var start=new Date(startTime.replace("-", "/").replace("-", "/"));
		    var end=new Date(endTime.replace("-", "/").replace("-", "/"));
		    if(start == end){  
		        return 0;  
		    }else if(start.getTime() > end.getTime()){
		    	return 1;
		    }else if(start.getTime() < end.getTime()){
		    	return -1;
		    }
		},
		editCompareDate:function(startTime, endTime){
		    var start=new Date(startTime.replace("-", "/").replace("-", "/"));
		    var end=new Date(endTime.replace("-", "/").replace("-", "/"));
		    if(start.getTime() < end.getTime()){  
		        return -1;  
		    }else{
		    	return 1;
		    }
		},
		// 引用商品弹窗
		listReferences:function(){
			$.showLoading();
			if($("#select-reference-table").parents('.modal').length>0){
				$("#select-reference-table").parents('.modal').remove();
			}
			$.ajax({
				type:'get',
				url: "/listing/linio-listing/list-references",
				timeout:60000,
				success:function(data){
					$.hideLoading();
					if(data != null){
						bootbox.dialog({
							title : Translator.t("引用商品"),
							className: "linio-listing-reference",
							buttons: {  
								Cancel: {  
							        label: Translator.t("取消"),  
							        className: "btn-default",  
							    }, 
							    OK: {  
							        label: Translator.t("确定"),  
							        className: "btn-primary",  
						            callback: function () {
						            	linioListing.userReference();
						            	return false;
						            }  
						        }  
							},
						    message: data,
						});
						
						$("#btn-select-reference-search").click(function(){
							var search_type = $('[name="search_type"]').val();
							var search_val = $('[name="search_val"]').val();
							$("#select-reference-table").queryAjaxPage({"search_type":search_type,"search_val":search_val});
						});
						
						$('[name="search_val"]').keypress(function(){
							if(event.keyCode == "13")  
								$("#btn-select-reference-search").click();
						});
					}
				},
				error:function(){
					$.hideLoading();
					bootbox.alert("网络请求错误, 请稍后再试！");
				}
			});
		},
		// 引用商品搜索
		queryReferences:function(obj){
			var o = {}; //动态的参数o
			var name = $(obj).attr("name");
		    var value = $(obj).val();
		    o[name] = value; 
			$("#select-reference-table").queryAjaxPage(o);
		},
		
		// 确认引用商品
		userReference:function(){
			var listingId = "";
			$('[name="listing_id"]').each(function(){
				if($(this).prop("checked") == true){
					listingId = $(this).val();
				}
			});
			
			if(listingId == ""){
				bootbox.alert("请选择要引用的产品");
				return false;
			}
			
			$.showLoading();
			window.location.href="/listing/linio-listing/use-reference?listing_id="+listingId;
		},
		addDecriptionPic:function(editorPic){
			editor = editorPic;
			$('#lazada-add-decs-pic #divimgurl').empty();
			linioListing.addImageUrlInput();
			$('#lazada-add-decs-pic').modal('show');
			
		},
		showDecriptionPic:function(){		
			var imgHtml = '';
			// 设置 对齐和宽度
			var localPicWidth = $("#localPicWidth").val();
			var localPicAlign = $("input[name='localPicAlign']:checked").val();
			var width = $.trim(localPicWidth)!=""?' width="'+localPicWidth+'"':'';
			
			// align: center 不起效，所以这里希望统一改水平 排位
//			if(localPicAlign == 'center'){
//				var align = linioListing.dataSet.descImageAligncenter;
//			}else if(localPicAlign == 'right'){
//				var align = linioListing.dataSet.descImageAlignright
//			}else{
//				var align = linioListing.dataSet.descImageAlignleft;
//			}
			// 图片描述的图片不并排展示的话 ，这个居中就没问题
//			if(localPicAlign == 'center'){
//				var align = linioListing.dataSet.descImageAligncenter;
//			}else{
//				var align = $.trim(localPicAlign)!=""?' align="'+localPicAlign+'"':'';
//			}
			
			// kindeditor编辑器里面对图片右键修改图片 居中什么的 也是通过修改align属性，如果这里要修改，kindeditor 右键的修改图片也要修改对齐的逻辑。
			// 所以不建议独立fix align: center 不起效 问题，客户选align: center 后可以通过空格居中
			var align = $.trim(localPicAlign)!=""?' align="'+localPicAlign+'"':'';
			$('#lazada-add-decs-pic #divimgurl>div>img').each(function(){
				var src = $(this).attr("src");
				if(src)
					imgHtml += '<img src="'+src+'"'+width+align+' />';
			});
			if(editor)
				editor.insertHtml(imgHtml);
			
			$('#lazada-add-decs-pic').modal('hide');
		} ,
		localUpOneImg:function(obj){
			var tmp='';
			$('#img_tmp').unbind('change').on('change',function(){
				$.showLoading();
				$.uploadOne({
					 fileElementId:'img_tmp', // input 元素 id
					 //当获取到服务器数据时，触发success回调函数 
					 //data: 上传图片的原图和缩略图的amazon图片库链接{original:... , thumbnail:.. } 
					 onUploadSuccess: function (data){
						 $.hideLoading();
						 tmp = data.original;
						 $(obj).parent().children('input[type="text"]').val(tmp);
						 $(obj).parent().children('img').attr('src',tmp);
					 },
								 
					 // 从服务器获取数据失败时，触发error回调函数。  
					 onError: function(xhr, status, e){
						 $.hideLoading();
						 alert(e);
					 }
				});
			});
			$('#img_tmp').click();
		},
		addImageUrlInput:function(src) {
			if (typeof (src) == 'undefined') {
				src = '';
			}
			$('#divimgurl')
					.append(
							"<div><img src='"
									+ src
									+ "' width='50' height='50'> <input type='text' id='imgurl"
									+ (Math.random() * 10000).toString()
											.substring(0, 4)
									+ "' name='imgurl[]' size='80' style='width: 300px;' onblur='javascript:linioListing.blurImageUrlInput(this)' value="
									+ src
									+ "> <input type='button' value='删除' onclick='javascript:linioListing.delImageUrlInput(this)'> <input type='button' value='本地上传' onclick='javascript:linioListing.localUpOneImg(this)' ></div>");
		}, 
		delImageUrlInput:function(imgdiv) {
				$(imgdiv).parent().empty();
		},
		blurImageUrlInput:function(obj) {
			var t = $(obj).val();
			$(obj).parent().children('img').attr('src', t);
		} ,
		
		confirmUploaded:function(id) {
			bootbox.confirm({  
		        title : Translator.t('确认产品已发布'),
				message : Translator.t('此操作之后，系统将把该产品设置为已发布的产品，当产品再发布的时候会直接覆盖已在线的产品数据，您确定操作?'),  
		        callback : function(r) {  
		        	if (r){
						$.ajax({
							type:'post',
							url: "/listing/linio-listing/confirm-uploaded-product",
							data: {id:id},
							timeout:60000,
							dataType:'json',
							success:function(data){
								$.hideLoading();
								bootbox.alert(data.message);
							},
							error:function(){
								$.hideLoading();
								bootbox.alert("网络请求错误, 请稍后再试！");
							}
						});
					}
		        },  
	        });
		}
		
		
		
		
}