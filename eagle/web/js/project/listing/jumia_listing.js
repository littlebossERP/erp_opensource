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
			jumiaListing.str = '';
			jumiaListing.str = str;
			$('html,body').animate({scrollTop:$('#'+str).offset().top}, 800);
		}
	}else if(str == 'warranty-info'){
		if(winPos > descriptionInfo && winPos < shippingInfo){
			showscrollcss('warranty-info');
		}else{
			jumiaListing.str = '';
			jumiaListing.str = str;
			$('html,body').animate({scrollTop:$('#'+str).offset().top}, 800);
		}
	}else{
		jumiaListing.str = '';
		jumiaListing.str = str;
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

if (typeof jumiaListing === 'undefined')  jumiaListing = new Object();

jumiaListing ={
		existingImages:[],
		initReference:false,
		str:'',
		info_id:['store-info','base-info','variant-info','image-info','description-info','shipping-info','warranty-info'],
		init:function(){
			var attrStr = $("#productDataStr").val();
			var skuArr = $("#skus").val();

//			$(".categoryModalShow").onclick(function(){
//				jumiaListing.selectCategory();
//			});
			//一级目录下子项点击事件
			$(document).on('click','.categoryDiv',function(){
				jumiaListing.categoryClick(this);
			});
			//一级目录下子项点击事件
			$(document).on('click','.browseNodeCategoryDiv',function(){
				jumiaListing.browseNodeCategoryClick(this);
			});
			
			//删除browseNode当前行
			$(document).on('click','.glyphicon-trash',function(){
				jumiaListing.deleteBrowseNode(this);
			});
			
			//隐藏相关信息
			$(document).on('click','.glyphicon-chevron-up',function(){
				jumiaListing.hide(this);
			});
			//显示相关信息
			$(document).on('click','.glyphicon-chevron-down',function(){
				jumiaListing.show(this);
			});
			//skuRemove
			$(document).on('click','a[cid="remove"]',function(){
				$(this).closest('tr').remove();
			});
			
			//sku批量修改
			//批量弹层html生成
			$(document).on('click','a.lzdSkuBatchEdit',function(){
				jumiaListing.skuBatchEdit(this);
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
				jumiaListing.skuBatchEditConfirm(this);
			});
			
			
			// 初始化 品牌autocomplete
			$('[cid="Brand"]>.secondTd>input').autocomplete({
				source: function(request,response){
					$.ajax({
					   type: "post",
					   url: "/listing/jumia-listing/get-brands",
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
			
			//加载页面编辑器
			KindEditor.ready(function(K) {
		            window.editor = K.create('',jumiaListing.dataSet.kdeOption);
		    });
			
			jumiaListing.pageBeginning();
			jumiaListing.dataSet.temporaryData.submitData = JSON.parse(attrStr || '{}');
			jumiaListing.dataSet.temporaryData.skuData = JSON.parse(skuArr || '[]');
			for(var i in jumiaListing.info_id){
				if(typeof jumiaListing.dataSet.temporaryData.submitData[jumiaListing.info_id[i]] == 'undefined'
					|| !jumiaListing.dataSet.temporaryData.submitData[jumiaListing.info_id[i]]
					|| jumiaListing.dataSet.temporaryData.submitData[jumiaListing.info_id[i]].length == 0){
					jumiaListing.dataSet.temporaryData.submitData[jumiaListing.info_id[i]] = JSON.parse('{}');
				}
			}
			if(attrStr == ''){
				jumiaListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
				jumiaListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
				jumiaListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
				jumiaListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
				jumiaListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
				jumiaListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
				jumiaListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
			}else{
				jumiaListing.selectShop();//初始化商铺 
//				jumiaListing.pageBeginning();
				if(jumiaListing.initReference == true){// 引用商品数据初始化 与编辑/复制页面不同
					jumiaListing.lzdSkuBorn();
					jumiaListing.pushJumiaData(jumiaListing.dataSet.temporaryData.submitData,jumiaListing.dataSet.temporaryData.skuData);
				}else{
					var categoryIdList = JSON.parse($('#productCategoryIds').val() || '[]');
					if(categoryIdList.length > 0)
					{
						var categoryId = categoryIdList[0];
						jumiaListing.initCategory(categoryIdList); //回填目录
						jumiaListing.initEditProduct(categoryId);  // 回填信息
					}else{
						bootbox.alert("选择目录初始化失败！");
						return ;
					};
					var browseNodeIdList = JSON.parse($('#productBrowseNodeCategoryIds').val() || '[]');
					if(browseNodeIdList.length > 0){
						for(var i in browseNodeIdList){
							if(browseNodeIdList.hasOwnProperty(i)){
								var browseNodeCategoryIdList = browseNodeIdList[i];
								jumiaListing.browseNodeInitCategory(browseNodeCategoryIdList);
							}
						}
					}
					if($("div[class='nodeRow']").length == 2){
						$('button[data-names="nodesBtn"]').css("display","none");
					}
				}
			}
			jumiaListing.initPhoto();
//			jumiaListing.selectedClik();
//			jumiaListing.initCategory();//初始化目录
//			jumiaListing.selectHistoryCategory();
			
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
					if(jumiaListing.str=='shipping-info'){
						showscrollcss('shipping-info');
					}else if(jumiaListing.str=='warranty-info'){
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
			lzdAttrTit_2:'#{Label}:</td><td class="secondTd" cid="#{FeedName}Content" >',
			lzdAttrEnd:'</td></tr>',
			input:'<input type="text" class="form-control" value="" />',
			input_2:'<input type="text" class="form-control" value="..." placeholder="..."/>',
			select:'<select><option>请选择</option>#{optionStr}</select>',
			skuSelect:'<select class="eagle-form-control" style="height:30px;width:125px;"><option value="">请选择</option>#{optionStr}</select>',
			checkbox:'<label><input type="checkbox" value="#{Name}" data-val="#{GlobalIdentifier}"/> #{Name}</Label>',
			kindeditor:'<div class="mBottom10" data-name="kdeOutDiv" cid="#{FeedName}">'+
					 '<textarea id="#{FeedName}" name="content" style="width:100%;height:100%;"></textarea>'+
					 '</div>'+
					 '<div id="#{FeedName}CacheDiv" style="display:none;">'+
					 '</div>',
			option:'<option value="#{Name}" data-val="#{GlobalIdentifier}">#{Name}</option>',
			kindeditorId:[],
			otherAttr:["Color","MainMaterial","MaterialFamily","ProductLine","Certifications","ProductionCountry"],
			descriptionAttr:["YoutubeId","Note"],
			shippingTime:["MinDeliveryTime","MaxDeliveryTime"],
			packingSize:["PackageWidth","PackageLength","PackageHeight"],
			commonShowAttr:["Name","ShortDescription","ProductMeasures","Brand","Model","ProductWeight","Description","PackageContent"],
			commonAttr:["Video","NameMs","NameEn","DescriptionMs","DescriptionEn","ManufacturerTxt","CareLabel"],
			isShowAttr:[],
			// Note,ProductIdOld,ShipmentType为lazada有jumia 没有. Certifications,MaterialFamily jumia 为muti_option属性，但option里面没有选项，导致这个属性没得填
			dontShowAttr:['ProductIdOld','ShipmentType','PrimaryCategory','ParentSku','Categories','SellerSku','ProductGroup','ProductId','Quantity','Price','SalePrice','SaleStartDate','SaleEndDate','PublishedDate'],
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
			spanCache:[],
			lzdSkuTrStar:'<tr id="0">',
			lzdSkuTrEnd:'</tr>',
			lzdSkuTh_1:'<th>#{FeedName}<span cid="variationEditSpan"><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="variation">一键生成</a>)</span></th>',
			lzdSkuTh_2:'<th class="bgTd">SKU<span class="fRed">*</span><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="sku">一键生成</a>)</th>'+
					   '<th class="bgTd">EAN/UPC/ISBN</th>'+
					   '<th class="smTd">库存<span class="fRed">*</span><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="quantity">修改</a>)</th>'+
					   '<th class="smTd">价格<span class="fRed">*</span><br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="price">修改</a>)</th>'+
					   '<th class="smTd">促销价<br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="salePrice">修改</a>)</th>'+
					   '<th colspan="3">促销时间<br/>(<a href="javascript:;" class="lzdSkuBatchEdit" data-names="promotionTime">修改</a>)</th>'+
					   '<th style="min-width:40px;">操作</th>',
			lzdSkuTd_1:'<td  style="width:90px;" data-id="sku" data-name="variation" data-type="#{attrType}">#{showHtml}</td>',
			lzdSkuTd_2:'<td data-name="sellerSku"><input type="text" class="form-control" name="" value="" placeholder=""/></td>'+
					   '<td data-name="productGroup" name="EAN"><input type="text" class="form-control" name="" value="" placeholder=""/></td>'+
					   '<td data-name="quantity" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="jumiaListing.replaceNumber(this);"/></td>'+
					   '<td data-name="price" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="jumiaListing.replaceFloat(this);"/></td>'+
					   '<td data-name="salePrice" style="width:50px;"><input type="text" class="form-control" name="" value="" placeholder="" onkeyup="jumiaListing.replaceFloat(this);"/></td>'+
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
				jumiaListing.checkAll(this);
			});
			$(document).on('click','input[name="parent_chk"]',function(){
				jumiaListing.check(this);
			});
			//变参商品选择
			$(document).on('click','input[name="productcheck"]',function(){
				jumiaListing.variationCheck(this);
			});
			//展示所有商品
			$(document).on('click','.product_all_show',function(){
				jumiaListing.productAllShow(this);
			});
			//展开显示
			$(document).on('click','.product_show',function(){
				jumiaListing.productShow(this);
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
//				jumiaListing.check(this);
//			});
			//搜索键
//			$('#search').click(function(){
//				if($('select[name="condition"]').val()==''){
//				    bootbox.alert('请选择搜索条件');
//					}
//			});
		},
		//browse node类目开始
		//删除browseNode当前行
		deleteBrowseNode:function(obj){
			$(obj).parents('div[class="nodeRow"]').remove();
			if($('button[data-names="nodesBtn"]').css("display") == "none"){
				$('button[data-names="nodesBtn"]').css("display","inline-block");
			}
		},
		//选择类目
		browseNodeSelectCategory:function(){
			var shopId = $("#lazadaUid").val();
			if($.trim(shopId) == ""){
//				$.fn.message({type:"error", msg:"请选择Jumia店铺!"});
//				bootbox.alert("请选择Jumia店铺!");
				$('#select_shop_info').html("请选择Jumia店铺!");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return;
			}
			//保存选中前的类目数据
//			if($.trim($("#categoryId").val())){
//				editCategoryPreservationData();
//				editCategoryGetSkuListData();
//			}
			
			$('#browseNodeCategoryChoose').modal('show');
		},
		browseNodeInitCategory:function(categoryList){
			var pcid = "0";
			//获取第一级
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/jumia-listing/get-category-tree',
				data:{
					lazada_uid:$("#lazadaUid").val(),
					parentCategoryId:pcid
				},
				dataType:'json',
				success:function(data){
					if(data.code == 200){
						jumiaListing.browseNodeCategoryShow(data);
//						$(".nodecategory").html($(".browseNodeCategoryChooseCrumbs").html());
						categoryList = eval(categoryList);
						if(categoryList != undefined){
							for(var i=categoryList.length-1;i>=0;i--){
								var categoryId = categoryList[i];
								$(".browseNodeCategoryChooseOutDiv .browseNodeCategoryChooseInDiv span[categoryId="+categoryId+"]").parent().click();
								if(i==0){
									$(".nodecategory").append("<div class='nodeRow'>"+$(".browseNodeCategoryChooseCrumbs").html()+"<span class='glyphicon glyphicon-trash' style='cursor: pointer;'></span></div>");
//									jumiaListing.getNewCategoryHistory(categoryId);
//									$("#categoryHistoryId").val(categoryId);
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
		browseNodeCategoryShow:function(arr,obj){
			var str = jumiaListing.browseNodeCategoryHtml(arr);
			if (obj == '' || obj == undefined)
			{
				$('#browseNodeCategoryChoose').find('div.browseNodeCategoryChooseInDiv').hide();
				$('#browseNodeCategoryChoose').find('div.browseNodeCategoryChooseInDiv').empty();
				obj = $('#browseNodeCategoryChoose').find('div.browseNodeCategoryChooseInDiv:first').show();
				obj = $('#browseNodeCategoryChoose').find('div.browseNodeCategoryChooseInDiv:first').html(str);
				$('.browseNodeCategoryChooseCrumbs').find('span').each(function(){
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
		
		browseNodeCategoryHtml:function(arr){
			var str = '';
			for (var i in arr.data)
			{
				str += '<div class="browseNodeCategoryDiv"><span class="categoryNames" categoryId="'+arr.data[i].categoryId+'">'+arr.data[i].categoryName+'</span>';
				if (arr.data[i].isLeaf == 0)//数据库的bool值
				{
					str += '<span class="glyphicon glyphicon-chevron-right" data-isleaf="'+arr.data[i].isLeaf+'"></span></div>';
				}else{
					str += '<span class="glyphicon glyphicon-chevron-right" data-isleaf="'+arr.data[i].isLeaf+'" style="display:none;"></span></div>';
				}
					   
			};
			return str;
		},
		
		//一级目录下子项点击事件
		browseNodeCategoryClick:function(click_obj){
				//添加背景色
				$(click_obj).closest('div.browseNodeCategoryChooseInDiv').find('.browseNodeCategoryDiv').removeClass('bgColor5');
				$(click_obj).addClass('bgColor5');

				var isleaf = $(click_obj).find('span.glyphicon').attr('data-isleaf'),//判断有没子集，字符创
					obj = $(click_obj).closest('div.browseNodeCategoryChooseInDiv'),
					nameZh = $(click_obj).find('span.categoryNames').text(),
					id =  $(click_obj).find('span.categoryNames').attr('categoryId'),
					level = $(obj).attr('data-level');
				//判断有没子集
				if (isleaf == "false")//有子集
				{
					$('.selectBrowseNodeCategoryId').removeClass('selectBrowseNodeCategoryId');//
					$(click_obj).addClass('selectBrowseNodeCategoryId');
					//加载类目列表
					$.ajax({
						type:'POST',
						async:false,
						url:'/listing/jumia-listing/get-category-tree',
						data:{
							lazada_uid:$("#lazadaUid").val(),//站点
							parentCategoryId:id
						},
						dataType:'json',
						success:function(data){
							if(data.code == 200){
								jumiaListing.browseNodeCategoryShow(data,obj);
							}else{
								bootbox.alert(data.message);
							}
						},
						error:function(){
							bootbox.alert("网络错误！");
						}
					});
					
				}else{
					$('.selectBrowseNodeCategoryId').removeClass('selectBrowseNodeCategoryId');
					$(click_obj).addClass('selectBrowseNodeCategoryId');
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
				$('.browseNodeCategoryChooseCrumbs').find('span[data-level="'+level+'"]').html(str);
				$('.browseNodeCategoryChooseCrumbs').find('span[data-level="'+level+'"]').nextAll().empty();
				
				if (level <= 3)
				{
					$('.browseNodeCategoryChooseMiddleDiv').css('width','auto');
				}else if (level == 4)
				{
					$('.browseNodeCategoryChooseMiddleDiv').css('width','1320px');
				}else{
					$('.browseNodeCategoryChooseMiddleDiv').css('width','1570px');
				}
				//设滚动条位置 fuyi
				var width = $('.browseNodeCategoryChooseMiddleDiv').width();
				$('.browseNodeCategoryChooseOutDiv').scrollLeft('1300');
		},
		
		browseNodeSelectedClik:function(){
			if($(".selectBrowseNodeCategoryId").length == 0){
				bootbox.alert("至少选择一个目录名");
				return null;
			}else{
				$('#browseNodeCategoryChoose').modal('hide');
				if($("div[class='nodeRow']").length < 2){//最多只能显示是两条
					$(".nodecategory").append("<div class='nodeRow'>"+$(".browseNodeCategoryChooseCrumbs").html()+"<span class='glyphicon glyphicon-trash' style='cursor: pointer;'></span></div>");
				}
				if($("div[class='nodeRow']").length == 2){
					$('button[data-names="nodesBtn"]').css("display","none");
				}
			}
//			var categoryId = $(".selectBrowseNodeCategoryId .categoryNames").attr("categoryId");//选中目录的ID
//			var oldCategoryId = $("#categoryId").val();
//			var categoryName = $(".selectCategoryId .categoryNames").text();//选中目录的name
//			
//			if(categoryId == oldCategoryId){
//				$('#categoryChoose').modal('hide');//确定选择的为最后一项时关闭窗口
//			}else{
//				//判断是否是叶子节点
//				var obj = $('#categoryChoose .categoryChooseOutDiv .categoryChooseInDiv span[categoryid="'+categoryId+'"]');
//				var isleaf = $(obj).next().attr('data-isleaf');
//				if(isleaf == "false"){
//					var cateName = $(obj).text();
////					$.fn.message({type:"error",msg:"您选择的类目 \“"+cateName+"\” 还有子类目,请选择子类目！"});
//					bootbox.alert("您选择的类目 \“"+cateName+"\” 还有子类目,请选择子类目！");
//					return;
//				}
//				$.showLoading();
//				$.ajax({
//					type:'POST',
////					async:false,
//					url:'/listing/jumia-listing/get-category-attrs',
//					data:{
//						lazada_uid:$("#lazadaUid").val(),//站点
//						primaryCategory:categoryId
//					},
//					dataType:'json',
//					success:function(data){
//						$.hideLoading();
//						if(data.code == 200){
//							$('#categoryChoose').modal('hide');
//							$("#categoryId").val(categoryId);
//							
//							$(".category").html($(".categoryChooseCrumbs").html());
////							var cateName = $(obj).text();
////							var option_str = "<option value='"+cateName+"'>"+cateName+"</option>";
////							$("#categoryHistoryId").html(option_str);
//							//显示类目属性
//							jumiaListing.jumiaDataHandle(data);
//							$('#select_info').html(" ");
//							//选中下拉
//							jumiaListing.getNewCategoryHistory(categoryId,categoryName);
//						}else{
//							bootbox.alert(data.message);
//						}
//					},
//					error:function(){
//						$.hideLoading();
//						bootbox.alert("网络错误！");
//					}
//				});
//			};
//	
		},
		//browse node类目结束
		
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
				var sale_str = '<label for="saleStartDate">促销起始时间：</label><input type="text" onkeyup="value=value.replace(/[^0-9-]/g,\'\')" class="eagle-form-control Wdate" style="padding-left:5px;width:234px;" id="saleStartDate" name="saleStartDate" placeholder="开始时间" onClick="jumiaListing.datePicker(this)"/>'+
				'<br /><label for="saleEndDate" style="padding-left:55px">促销结束时间：</label><input onkeyup="value=value.replace(/[^0-9-]/g,\'\')" type="text" class="eagle-form-control Wdate" style="padding-left:5px;width:234px;" id="saleEndDate" name="saleEndDate" placeholder="结束时间" onClick="jumiaListing.datePicker(this)"/>';
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
						post_url = "/listing/jumia/batch-update-price";
						break;
					case 'sale_message':
						post_url = "/listing/jumia/batch-update-sales-info";
						break;
					case 'quantity':
						post_url = "/listing/jumia/batch-update-quantity";
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
				var compare1 = jumiaListing.editCompareDate(nowDate,$('#saleEndDate').val());//这个function只用于批量修改时间时的比较
				var compare2 = jumiaListing.compareDate($('#saleStartDate').val(),$('#saleEndDate').val());
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
					if(result.code == 200){
						// bootbox.alert(result.message);
						// $('#edit_product').css("display","none");
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
				$.showLoading();
				$.ajax({
					   type: "POST",
					   url: '/listing/jumia/put-off',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
						 $.hideLoading();
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
						   $.hideLoading();
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
				$.showLoading();
				$.ajax({
					   type: "POST",
					   url: '/listing/jumia/put-off',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
						 $.hideLoading();
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
						   $.hideLoading();
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
				$.showLoading();
				$.ajax({
					   type: "POST",
					   url: '/listing/jumia/put-off',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
						 $.hideLoading();
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
						   $.hideLoading();
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
				$.showLoading();
				$.ajax({
					   type: "POST",
					   url: '/listing/jumia/put-on',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
						 $.hideLoading();
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
						   $.hideLoading();
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
				$.showLoading();
				$.ajax({
					   type: "POST",
					   url: '/listing/jumia/put-on',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
						 $.hideLoading();
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
						   $.hideLoading();
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
				$.showLoading();
				$.ajax({
					   type: "POST",
					   url: '/listing/jumia/put-on',
					   data: {"productIds":box},
					   dataType:'json',
					   success: function(result){
						 $.hideLoading();
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
						   $.hideLoading();
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
					$.showLoading();
					$.ajax({
						type:'GET',
//						async:false,
						url:'/listing/jumia-listing/do-publish',
						data:{
							ids:ids.join(',')
						},
						dataType:'json',
						success:function(data){
							$.hideLoading();
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
							$.hideLoading();
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
					$.showLoading();
					$.ajax({
						type:'POST',
//						async:false,
						url:'/listing/jumia-listing/delete',
						data:{
							ids:ids.join(',')
						},
						dataType:'json',
						success:function(data){
							$.hideLoading();
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
							$.hideLoading();
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
				$.showLoading();
				$.ajax({
					type:'POST',
//					async:false,
					url:'/listing/jumia-listing/delete',
					data:{
						ids:ids.join(',')
					},
					dataType:'json',
					success:function(data){
						$.hideLoading();
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
						$.hideLoading();
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
					url:'/listing/jumia/manual-sync',
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
		//搜索商品
		serachSubmit:function(){
			var remind = '';
			$(".success_message").html('');
			var lzd_uid = $("#lazada_uid").val();
			if(lzd_uid == ""){
				remind = "请选择店铺！";
				$(".success_message").html(remind);
			}else{
				$.showLoading();
				$.ajax({
					type:'post',
					url:'/listing/jumia/search-save-product',
					data:$('#search-product').serialize(),
					dataType:'json',
					success:function(data){
						$.hideLoading();
						if(data.code == 200){
							bootbox.alert({title:Translator.t('提示'), message:data.message, callback:function(){
									window.location.reload();
									$.showLoading();
								}
						   });
						}else{
							remind = data.message;
							$(".success_message").html(remind);
							
						}
					},
					error:function(){
						$.hideLoading();
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
////				var link = '/listing/jumia-listing/create-product?account_value='+account_value+'&text='+text;
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
		//导入选择框重置
		reset2:function(){
			$("#lazada_countryCode_select").val('').change();
//			$("#lazada_uid_select").val('');
			$("#import_file").val('');
			$("#excute_time").val('');
			$('#import_file_name').text('');
		},
		
		//导入选择站点显示账号
		reShowAccounts:function(_thisSel){
			$('.js-checkbox-mp input').prop('checked', false);
			$('.js-checkbox-mp').addClass('hidden');
			if($(_thisSel).val() != ""){
				$('.js-checkbox-mp.site-'+$(_thisSel).val()).removeClass('hidden');
			}
		},
		
		//导入excel
		importSubmit:function(){
			// get browser info
			var Sys = {};
			if(navigator.userAgent.indexOf("MSIE")>0) {
				Sys.ie = true;
				var version = navigator.userAgent.split(";"); 
				var trim_Version = version[1].replace(/[ ]/g,""); 
				Sys.ieVersion = trim_Version;
			}
			
			var file = $("#import_file").not('.done');
			if( Sys.ie &&  Sys.ieVersion != "MSIE10.0"){// for lt IE 9
				var uploadFile = $(file).val();
			}else{
				var uploadFile = file[0].files[0];
			}

			$("#import_file").attr('disabled',true);
			$('#import_file_btn').attr('disabled',true);
			
			var accounts = [];
			$('.js-checkbox-mp').find('input:checked').each(function(){
				accounts.push($(this).val());
			});
			
			if(accounts.length == 0){
				$('#import_file').attr('disabled',false);
			    $('#import_file_btn').attr('disabled',false);
				bootbox.alert("请选择要上传的账号");
				return ;
			}
			
			// 获取非空input 内容
			if(uploadFile){
				$.showLoading();
				$.ajaxFileUpload({  
					url:"/listing/jumia/import-listing-from-excel", 
					uploadFile : uploadFile,//通过input 元素 change事件获取的 file 对象 或 上传文件的文件名
					fileName:'input_import_file',
					data:{import_type:$('#import_type_select').val(), lazada_uid:JSON.stringify(accounts), excute_time:$('#excute_time').val()},// ie10以下的可能不支持这个字段
					dataType: 'json',//返回数据的类型
					isNotCheckFile : true,
					success: function (result , status , context){ 
						$.hideLoading();
						if(result.code == 200){
							jumiaListing.reset2();
							bootbox.alert({title:Translator.t('提示'),message:"上传成功",callback:function(){
					    		 window.location.reload();
					    		 	$.showLoading();
								 }
						    });
						}else{
							$('#import_file').attr('disabled',false);
							$('#import_file_btn').attr('disabled',false);
							$('.ret_msg').html(result.message);
						}
				    },
				    error: function( xhr, status, e ){
				    	$.hideLoading();
					    $('.ret_msg').html('');
					    $('#import_file').attr('disabled',false);
					    $('#import_file_btn').attr('disabled',false);
					    bootbox.alert("网络错误！");
				    }
				});
			}else{
				$('#import_file').attr('disabled',false);
			    $('#import_file_btn').attr('disabled',false);
				bootbox.alert("请上传文件");
			}
		},
		// 删除导入任务
		deleteImportJob:function(jobId){
			bootbox.confirm({  
		        title : Translator.t('确认删除任务'),
				message : Translator.t('确认删除任务？'),  
		        callback : function(r) {  
		        	if (r){
		        		$.showLoading();
		        		$.ajax({
		    				type:'POST',
		    				url:'/listing/jumia/delete-import-job',
		    				data:{jobId:jobId},
		    				dataType:'json',
		    				success:function(data){
		    					$.hideLoading();
		    					if(data.code == 200){
		    						bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
		    								window.location.reload();
		    								$.showLoading();
		    							}
		    					   });
		    					}else{
		    						bootbox.alert(data.message);
		    					}
		    				},
		    				error:function(){
		    					$.hideLoading();
		    					bootbox.alert("网络错误, 请稍后再试！");
		    				}
		    			});
					}
		        },  
	        });
			
		},
		// 中止导入任务
		stopImportJob:function(jobId){
			bootbox.confirm({  
		        title : Translator.t('确认中止任务'),
				message : Translator.t('确认中止任务？'),  
		        callback : function(r) {  
		        	if (r){
		        		$.showLoading();
		        		$.ajax({
		    				type:'POST',
		    				url:'/listing/jumia/stop-import-job',
		    				data:{jobId:jobId},
		    				dataType:'json',
		    				success:function(data){
		    					$.hideLoading();
		    					if(data.code == 200){
		    						bootbox.alert({title:Translator.t('提示'),message:data.message,callback:function(){
		    								window.location.reload();
		    								$.showLoading();
		    							}
		    					   });
		    					}else{
		    						bootbox.alert(data.message);
		    					}
		    				},
		    				error:function(){
		    					$.hideLoading();
		    					bootbox.alert("网络错误, 请稍后再试！");
		    				}
		    			});
					}
		        },  
	        });
			
		},
		
		// 显示导入任务报错信息
		showImportErrMsg:function(jobId){
			bootbox.dialog({
				title : Translator.t("报错信息"),
				className: "jumiaImportErrMsg",
				buttons: {  
					Cancel: {  
						label: Translator.t("关闭"),  
						className: "btn-default",  
					}, 
				},
				message: $("#err-msg-"+jobId).html(),
			});	
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
			$.showLoading();
			$.ajax({
			   type: "get",
			   url: "/listing/jumia-listing/do-publish",
			   data: {ids:id},
			   dataType:'json',
			   success: function(result){
				 $.hideLoading();
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
				   $.hideLoading();
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
				jumiaListing.selectShop(1);
			}
		},
		//addOneSku
		addOneSku:function(){
			var shopId = $("#lazadaUid").val();
			//判断有没有选择店铺和类目
			if($.trim(shopId) == ""){
//				$.fn.message({type:"error", msg:"请选择Jumia店铺!"});
				$('#select_shop_info').html("请选择Jumia店铺!");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return;
			}
			//保存选中前的类目数据
			var data = jumiaListing.dataSet.lzdSkuObjArr.Variation,
			str = jumiaListing.lzdSkuTdHtmlBorn(data);
			if(+$.trim($("#categoryId").val())){
				$('div.lzdSkuInfo table').append(str);
				if($('div.lzdSkuInfo table tr').length > 2){//复制第一行
//					var first_tr = '';
//					first_tr = $('div.lzdSkuInfo table tr:eq(1) input');
					var tr_length = $('div.lzdSkuInfo table tr').length-1;
					$('div.lzdSkuInfo table tr:eq('+tr_length+') input').each(function(i){
						$(this).val($('div.lzdSkuInfo table tr:eq(1) input:eq('+i+')').val())
					});
				}
			}else{
//				$.fn.message({type:"error", msg:"请选择产品分类!"});
//				bootbox.alert("请选择产品分类!");
				$('#select_info').html("请选择产品分类!");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
			}
		},
		//sku批量修改
		//批量弹层html生成
		skuBatchEdit:function(edit_obj){
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
							  '<td><input type="text" data-name="saleStartDate" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="开始时间" onClick="jumiaListing.datePicker(this)"/></td>'+
							  '</tr>'+
							  '<tr>'+
							  '<td>结束时间：</td>'+
							  '<td><input type="text" data-name="saleEndDate" class="form-control Wdate" style="padding-left:5px;" id="" name="" value="" placeholder="结束时间" onClick="jumiaListing.datePicker(this)"/></td>'+
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
			$('#lzdSkuBatchEdit').modal('show');
		},
		skuBatchEditConfirm:function(confirm_obj){
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
									$('div.lzdSkuInfo table td[data-name="'+btnName+'"]').each(function(){
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
										$('div.lzdSkuInfo table td[data-name="'+btnName+'"]').each(function(){
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
										$('div.lzdSkuInfo table td[data-name="'+btnName+'"]').each(function(){
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
					value = jumiaListing.compareDate(starDate,endDate);
					if(value == 1){
//						$.fn.message({type:"error", msg:"结束时间必须大于等于开始时间!"});
						bootbox.alert("结束时间必须大于等于开始时间!");
						return;
					}else{
						$('div.lzdSkuInfo table td[data-name="saleStartDate"] input').each(function(){$(this).val(starDate);})
						$('div.lzdSkuInfo table td[data-name="saleEndDate"] input').each(function(){$(this).val(endDate);})
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
						$('div.lzdSkuInfo table').find('tr').each(function(){
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
						$('div.lzdSkuInfo table').find('td[data-name="quantity"] input[type="text"]').val(num);
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
				var isKong = jumiaListing.kong();
				if(isKong == 0){
//					$.fn.message({type:"error",msg:"不能生成SKU,variation值不能为空！"});
					bootbox.alert("不能生成SKU,variation值不能为空！");
					return;
				}
				if(jumiaListing.variationIsRepeat() == 1){
//					$.fn.message({type:"error",msg:"不能生成SKU,重复的variation值！"});
					bootbox.alert("不能生成SKU,重复的variation值！");
					return;
					}
				$('div.lzdSkuInfo table tr').each(function(){
					if($(this).index() != 0){
						type = $(this).find('td[data-name="variation"]').attr('data-type');
						value = $(this).find('td[data-name="variation"]').find(type).val();
						$(this).find('td[data-name="sellerSku"] input[type="text"]').val(star.trim().replace(/(\s+|　+)/g,'_')+value.trim().replace(/(\s+|　+)/g,'_')+end.trim().replace(/(\s+|　+)/g,'_'));
					}
				});
			}
			$('#lzdSkuBatchEdit').modal('hide');
		},
		kong:function(){
			var typeA = 1;
			$('div.lzdSkuInfo table tr').each(function(){
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
		variationIsRepeat:function(){
			var typeA = 0,arr = [];
			$('div.lzdSkuInfo table tr').each(function(){
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
		selectCategory:function(){
			var shopId = $("#lazadaUid").val();
			if($.trim(shopId) == ""){
//				$.fn.message({type:"error", msg:"请选择Jumia店铺!"});
//				bootbox.alert("请选择Jumia店铺!");
				$('#select_shop_info').html("请选择Jumia店铺!");
				$('html,body').animate({scrollTop:$('div[id="store-info"]').offset().top}, 800);
				return;
			}
			//保存选中前的类目数据
//			if($.trim($("#categoryId").val())){
//				editCategoryPreservationData();
//				editCategoryGetSkuListData();
//			}
			
			$('#categoryChoose').modal('show');
		},
		
		//选择店铺或初始化店铺
		selectShop:function(flag){
			var shopId = $("#lazadaUid").val();
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
				
//				jumiaListing.pageBeginning();
				
				//初始化第一级类目
				jumiaListing.initCategory(null);
				jumiaListing.browseNodeInitCategory(null);
			}
			
			//历史记录类目
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/jumia-listing/get-selected-category-history',
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
							$("#categoryHistoryId").html(categoryHistoryId);						
						}
						//切换店铺时已选中的类目自动选中
						var historyCategoryId = $("#categoryId").val();
						if($.trim(historyCategoryId) != ""){
							jumiaListing.getNewCategoryHistory(historyCategoryId);
						}
					}else{
						console.log(data.message);
					}
					
				}
			});
		},
		
		//选中类目
		getNewCategoryHistory:function(categoryId,categoryName){
			var obj = $('#categoryHistoryId option[value="'+categoryId+'"]');
			
			//如果历史列表存在则直接选中
			if(obj.text() != undefined && obj.text() != ""){
				$('#categoryHistoryId option[value=""]').remove();
				$("#categoryHistoryId").val(categoryId);
				return;
			}
			
			var categoryHistoryId = '';
			categoryHistoryId += '<option value="'+categoryId+'">'+categoryName+'</option>';
			$("#categoryHistoryId").append(categoryHistoryId);
			$("#categoryHistoryId").val(categoryId);
			$('#categoryHistoryId option[value=""]').remove();
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
			var primaryCategory = $(obj).val();
			var lazada_uid = $("#lazadaUid").val();
			if($.trim(primaryCategory) == ""){
				
			}else{
				//清除请选择
				$('#categoryHistoryId option[value=""]').remove();
				
				$.ajax({
					type:'POST',
					async:false,
					url:'/listing/jumia-listing/get-all-categoryids',
					data:{
						lazada_uid:lazada_uid,
						primaryCategory:primaryCategory
					},
					dataType:'json',
					success:function(data){
						if(data.code == 200){
							jumiaListing.initEditProduct(primaryCategory);
							jumiaListing.initCategory(data.data);
							$('#select_info').html("");
						}
					}
				});
			}
		},
		
		//初始化属性
		initEditProduct:function(categoryId){
			//加载属性
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/jumia-listing/get-category-attrs',
				data:{
					lazada_uid:$("#lazadaUid").val(),
					primaryCategory:categoryId
				},
				dataType:'json',
				success:function(data){
					$("#categoryId").val(categoryId);
					
					//填充产品属性
					jumiaListing.jumiaDataHandle(data);	
				}
			});
		},
		
		//获取第一级
		initCategory:function(categoryList){
			var pcid = "0";
			//获取第一级
			$.ajax({
				type:'POST',
				async:false,
				url:'/listing/jumia-listing/get-category-tree',
				data:{
					lazada_uid:$("#lazadaUid").val(),
					parentCategoryId:pcid
				},
				dataType:'json',
				success:function(data){
					if(data.code == 200){
						jumiaListing.categoryShow(data);
						
						$(".category").html($(".categoryChooseCrumbs").html());
						categoryList = eval(categoryList);
						if(categoryList != undefined){
							for(var i=categoryList.length-1;i>=0;i--){
								var categoryId = categoryList[i];
								$(".categoryChooseOutDiv .categoryChooseInDiv span[categoryId="+categoryId+"]").parent().click();
								if(i==0){
									$(".category").html($(".categoryChooseCrumbs").html());
									jumiaListing.getNewCategoryHistory(categoryId);
//									$("#categoryHistoryId").val(categoryId);
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
		categoryShow:function(arr,obj){
			var str = jumiaListing.categoryHtml(arr);
			if (obj == '' || obj == undefined)
			{
				$('#categoryChoose').find('div.categoryChooseInDiv').hide();
				$('#categoryChoose').find('div.categoryChooseInDiv').empty();
				obj = $('#categoryChoose').find('div.categoryChooseInDiv:first').show();
				obj = $('#categoryChoose').find('div.categoryChooseInDiv:first').html(str);
				$('.categoryChooseCrumbs').find('span').each(function(){
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
				//添加背景色
				$(click_obj).closest('div.categoryChooseInDiv').find('.categoryDiv').removeClass('bgColor5');
				$(click_obj).addClass('bgColor5');

				var isleaf = $(click_obj).find('span.glyphicon').attr('data-isleaf'),//判断有没子集，字符创
					obj = $(click_obj).closest('div.categoryChooseInDiv'),
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
						url:'/listing/jumia-listing/get-category-tree',
						data:{
							lazada_uid:$("#lazadaUid").val(),//站点
							parentCategoryId:id
						},
						dataType:'json',
						success:function(data){
							if(data.code == 200){
								jumiaListing.categoryShow(data,obj);
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
				$('.categoryChooseCrumbs').find('span[data-level="'+level+'"]').html(str);
				$('.categoryChooseCrumbs').find('span[data-level="'+level+'"]').nextAll().empty();
				
				if (level <= 3)
				{
					$('.categoryChooseMiddleDiv').css('width','auto');
				}else if (level == 4)
				{
					$('.categoryChooseMiddleDiv').css('width','1320px');
				}else{
					$('.categoryChooseMiddleDiv').css('width','1570px');
				}
				//设滚动条位置 fuyi
				var width = $('.categoryChooseMiddleDiv').width();
				$('.categoryChooseOutDiv').scrollLeft('1300');
		},
		//选择键的funtion
		selectedClik:function(){
			
				var categoryId = $(".selectCategoryId .categoryNames").attr("categoryId");//选中目录的ID
				var oldCategoryId = $("#categoryId").val();
				var categoryName = $(".selectCategoryId .categoryNames").text();//选中目录的name
				
				if(categoryId == oldCategoryId){
					$('#categoryChoose').modal('hide');//确定选择的为最后一项时关闭窗口
				}else{
					//判断是否是叶子节点
					var obj = $('#categoryChoose .categoryChooseOutDiv .categoryChooseInDiv span[categoryid="'+categoryId+'"]');
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
						url:'/listing/jumia-listing/get-category-attrs',
						data:{
							lazada_uid:$("#lazadaUid").val(),//站点
							primaryCategory:categoryId
						},
						dataType:'json',
						success:function(data){
							$.hideLoading();
							if(data.code == 200){
								$('#categoryChoose').modal('hide');
								$("#categoryId").val(categoryId);
								
								$(".category").html($(".categoryChooseCrumbs").html());
//								var cateName = $(obj).text();
//								var option_str = "<option value='"+cateName+"'>"+cateName+"</option>";
//								$("#categoryHistoryId").html(option_str);
								//显示类目属性
								jumiaListing.jumiaDataHandle(data);
								$('#select_info').html(" ");
								//选中下拉
								jumiaListing.getNewCategoryHistory(categoryId,categoryName);
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
		
		initPhoto:function(){
			$('div[role="image-uploader-container"]').batchImagesUploader({
				localImageUploadOn : true,   
				fromOtherImageLibOn: true , 
				imagesMaxNum : 8,
				fileMaxSize : 1024 , 		
				fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
				maxHeight : 100,
				maxWidth : 100,
				initImages : jumiaListing.existingImages,
				url: "/util/image/upload-to-ali-oss",
//				initImages : '[{thumbnail:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg",original:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg"},{thumbnail:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg",original:"http://littleboss-image.s3.amazonaws.com/1/20151114/thumb_150_20151114154206-61d0a2d.jpg"}]',
				fileName: 'product_photo_file',
				onUploadFinish : function(imagesData , errorInfo){
					if(errorInfo != undefined){
						var error_msg = '';
						for(var i = 0;i < errorInfo.length;i++){
							var msg = $.parseJSON(errorInfo[i].msg);
							error_msg += "图片" + msg.name + "上传失败，" + msg.rtnMsg + ";<br>";
						}
						bootbox.alert(error_msg);
					}
					//debugger
				},
				
				onDelete : function(data){
		//			debugger
					$('.select_photo').each(function(){
						if ($(this).parent('div').attr('upload-index') == undefined){
							$(this).removeClass('select_photo');
							$(this).next('span[class="main_image_tips"]').remove()
						}
					});
				}
			});
			
			$('div[role=image-uploader-container] .image-item').click(function(e){
				if($(e.target).attr("src") != undefined){//区别点击的是选择图片还是删除按钮
					if($(this).find('a img').attr('src') != "/images/batchImagesUploader/no-img.png"){
						$('div[role=image-uploader-container] .image-item a').removeClass('select_photo');
						$(this).find('a').addClass('select_photo');
						$('div[role=image-uploader-container] .image-item span[class="main_image_tips"]').remove();
						$(this).append('<span class="main_image_tips"></span>');
						
					}
				}
				
			});
//			$('div[role=image-uploader-container] .image-item img').click(function(){
//				if($(this).attr('src') != "/images/batchImagesUploader/no-img.png"){
//					$('div[role=image-uploader-container] .image-item a').removeClass('select_photo');
//					$(this).find('a').addClass('select_photo');
//					$('div[role=image-uploader-container] .image-item span[class="main_image_tips"]').remove();
//					$(this).append('<span class="main_image_tips"></span>');
//					
//				}
//			});
			
			if($("#image-list div[upload-index]").length != 0){
				$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
				$('div[role=image-uploader-container] .image-item:eq(0)').append('<span class="main_image_tips"></span>');
			}
//			if ($('.select_photo img').length ==0){
//				$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
//			}
		},
		//页面初始化方法
		pageBeginning:function(){
			$(jumiaListing.dataSet.commonAttr).each(function(i){
				$('tr[cid="'+jumiaListing.dataSet.commonAttr[i]+'"]').hide();
			});
//			$('tr[cid="shippingTime"]').hide();
			$('table[cid="lzdAttrShow"]').empty();
			$('table[cid="lzdProductAttr"]').empty();
			$('table[cid="descriptionAttr"]').empty();
			$('div.lzdSkuInfo table').empty();
			jumiaListing.dataSet.lzdSkuArr=[];
			jumiaListing.dataSet.lzdSkuObjArr={};
			jumiaListing.dataSet.kindeditorId = [];
			jumiaListing.dataSet.isShowAttr = [];// dzt20160104 站点更换要清空这个数组，例如NameMs 在my站点加在这数组里面，更换站点没有清空数组导致 非my站点提示 NameMs不能为空。
			var newArr = ["ShortDescription","PackageContent","Description","ProductWarranty"];
			for(var i = 0 ; i < newArr.length ; i++){
				jumiaListing.showOneKindeditor(newArr[i]);
			}
			//pushJumiaData(jumiaListing.dataSet.temporaryData.submitData,jumiaListing.dataSet.temporaryData.skuData);
		},
		//多个编辑器加载
		showKindeditor:function(arr){
			var kdeId = '';
			$(arr).each(function(i){kdeId == '' ? kdeId += ('#'+arr[i]) : kdeId += (', #'+arr[i]);});
			KindEditor.ready(function(K) {
	                window.editor = K.create(kdeId,jumiaListing.dataSet.kdeOption);
	        });
		},
		//单个编辑器加载
		showOneKindeditor:function(id){
			var kdeId = '#'+id;
			var options = JSON.parse(JSON.stringify(jumiaListing.dataSet.kdeOption));
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
	        if (data.InputType == 'textfield' || data.InputType == 'numberfield' || data.InputType == 'datetime') {
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
	        if (data.InputType == 'textarea') {
	            data.attrType = 'kindeditor';
	            attrType = 'kindeditor';
	        }
	        //checkbox
	        if (data.InputType == 'checkbox' || data.InputType == 'multiselect') {
	        	if(data.FeedName == "JumiaLocal"){//这个选项特殊处理
					data.Options = {Option: [{GlobalIdentifier: "", Name: "YES", isDefault: "0"}]};
				}
	            data.attrType = 'checkbox';
	            attrType = 'checkbox';
	        }
	        //select
	        if (data.InputType == 'dropdown' || data.AttributeType == "option") {
	            data.attrType = 'select';
	            attrType = 'select';
	        }
	        
			data.isMandatory == 1 ? isMust = 1 : isMust = 0;
			type = {attrType:attrType,isMust:isMust}
			data.isMust = isMust
			return type;
		},
		//处理数据
		jumiaDataHandle:function(data){
			jumiaListing.pageBeginning();
			var type = null,arr = null,kdeId = '';
			$(data.data).each(function(i){
				if ($.inArray(data.data[i].FeedName,jumiaListing.dataSet.commonAttr) != -1 || $.inArray(data.data[i].FeedName,jumiaListing.dataSet.shippingTime) != -1 ){
					jumiaListing.commonAttrHandle(data.data[i]);
				}else if($.inArray(data.data[i].FeedName,jumiaListing.dataSet.dontShowAttr) != -1 || $.inArray(data.data[i].FeedName,jumiaListing.dataSet.commonShowAttr) != -1 || $.inArray(data.data[i].FeedName,jumiaListing.dataSet.packingSize) != -1){
//					if(data.data[i].FeedName == 'TaxClass'){
////						var optionStr = '';
//						var optionStr = '<option value="">请选择</option>';
//						$(data.data[i].Options.Option).each(function(j){
//							optionStr += jumiaListing.dataSet.option.formatOther(data.data[i].Options.Option[j]);
//						});
//						$('tr[cid="'+data.data[i].FeedName+'"] select').html("");
//						$('tr[cid="'+data.data[i].FeedName+'"] select').append(optionStr);
////						$('tr[cid="'+data.data[i].FeedName+'"] select').val('IVA 0%');
//					}
					return;
				}else{
					//获得展示类型及是否为必填项
					type = jumiaListing.lzdGetType(data.data[i]);
					//去单条数据处理
					if(data.data[i].FeedName == 'Variation'){
						var skuName = data.data[i].FeedName;
						jumiaListing.dataSet.lzdSkuArr.push(skuName);
						jumiaListing.dataSet.lzdSkuObjArr[skuName] = data.data[i];
					}else if($.inArray(data.data[i].FeedName,jumiaListing.dataSet.otherAttr) != -1){
						jumiaListing.lzdOneDataHandle(data.data[i],type,"lzdAttrShow");//过滤部分信息
					}else if($.inArray(data.data[i].FeedName,jumiaListing.dataSet.descriptionAttr) != -1){
						jumiaListing.lzdOneDataHandle(data.data[i],type,"descriptionAttr");
					}else{
						if(data.data[i].FeedName != "BrowseNodes"){
							jumiaListing.lzdOneDataHandle(data.data[i],type,"lzdProductAttr");
						}
					}
				}
			});
			$(jumiaListing.dataSet.isShowAttr).each(function(i){
				var type = $('tr[cid="'+jumiaListing.dataSet.isShowAttr[i]+'"]').attr('attrtype');
				if(type == 'kindeditor'){
					jumiaListing.dataSet.kindeditorId.push(jumiaListing.dataSet.isShowAttr[i]);
				}
			});
			arr = jumiaListing.dataSet.kindeditorId;
			/*if (arr.length > 0)
			{
				jumiaListing.showKindeditor(arr);
			}*/
			if(arr.length > 0){
				for(var i = 0 ; i < arr.length ; i++){
					jumiaListing.showOneKindeditor(arr[i]);
				}
			}
			
			jumiaListing.dataSet.kindeditorId.push("ShortDescription");
			jumiaListing.dataSet.kindeditorId.push("PackageContent");
			jumiaListing.dataSet.kindeditorId.push("Description");
			jumiaListing.dataSet.kindeditorId.push("ProductWarranty");
			jumiaListing.lzdSkuBorn();
			jumiaListing.pushJumiaData(jumiaListing.dataSet.temporaryData.submitData,jumiaListing.dataSet.temporaryData.skuData);
		},
		//单条数据处理
		lzdOneDataHandle:function(data,type,location){
			var obj = jumiaListing.lzdAttrHtmlBorn(data,type),arr = obj.defaultValue;
			$('table[cid="'+location+'"]').append(obj.str);
			if (arr != null && arr.length > 0)
			{
				jumiaListing.lzdDefaultValueHandle(data,type,obj.defaultValue);
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
		//html生成
		lzdAttrHtmlBorn:function(data,type){
			var str = '',arr = data.Options,optionStr = '',defaultValue = null;
			str += jumiaListing.dataSet.lzdAttrTit_1.formatOther(data);
			if (type.isMust == 1)
			{
				str += jumiaListing.dataSet.isMust;
			}
			str += jumiaListing.dataSet.lzdAttrTit_2.formatOther(data);
			switch(type.attrType){
				case 'input'://对DeliveryTimeSupplier做默认处理
					if(data.FeedName=='DeliveryTimeSupplier'){
						defaultValue = [];
						defaultValue.push('2');
					}
					str += jumiaListing.dataSet.input;
					break;
				case 'kindeditor':
					str += jumiaListing.dataSet.kindeditor.formatOther(data);
					jumiaListing.dataSet.kindeditorId.push(data.FeedName);
					break;
				case 'checkbox':
					defaultValue = [];
					if(typeof arr.Option != "undefined" && typeof arr.Option.Name != "undefined"){// dzt20160425 发现linio option只有一个的时候arr.Option的结构不是二维数组，而是一维
						var tempOptionArr = [];
						tempOptionArr.push(arr.Option);
						arr.Option = tempOptionArr;
					}
					
					$(arr.Option).each(function(i){
						str += jumiaListing.dataSet.checkbox.formatOther(arr.Option[i]);
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
						optionStr += jumiaListing.dataSet.option.formatOther(arr.Option[i]);
						if (arr.Option[i].isDefault == 1)
						{
							defaultValue.push(arr.Option[i].Name)
						}
					})
					data.optionStr = optionStr
					str += jumiaListing.dataSet.select.formatOther(data);
					break;
			}
			str += jumiaListing.dataSet.lzdAttrEnd;
			return {str:str,defaultValue:defaultValue};
		},
		//常用属性处理
		commonAttrHandle:function(data){
			if($.inArray(data.FeedName,jumiaListing.dataSet.commonAttr) != -1){
				$('tr[cid="'+data.FeedName+'"]').show();
				if(data.FeedName == 'ColorFamily'){
					var str = '';
					$(data.Options.Option).each(function(j){
						str += jumiaListing.dataSet.checkbox.formatOther(data.Options.Option[j]);
					})
					$('tr[cid="'+data.FeedName+'"] .secondTd').empty();
					$('tr[cid="'+data.FeedName+'"] .secondTd').append(str);
				}
				jumiaListing.commonAttrOptionHandle(data);
				jumiaListing.dataSet.isShowAttr.push(data.FeedName);
			};
			if($.inArray(data.FeedName,jumiaListing.dataSet.shippingTime) != -1){
				$('tr[cid="shippingTime"]').show();
				if($.inArray('shippingTime',jumiaListing.dataSet.isShowAttr) == -1){
					jumiaListing.dataSet.isShowAttr.push('shippingTime');
				}
			};
			if($.inArray(data.FeedName,jumiaListing.dataSet.packingSize) != -1){
				$('tr[cid="packingSize"]').show();
				if($.inArray('packingSize',jumiaListing.dataSet.isShowAttr) == -1){
					jumiaListing.dataSet.isShowAttr.push('packingSize');
				}
			};
		},
		//常用属性select的option处理
		commonAttrOptionHandle:function(data){
			if(data.FeedName == 'Warranty' || data.FeedName == 'WarrantyType' || data.FeedName == 'TaxClass' || data.FeedName == 'ColorFamily'){
				var optionStr = '';
				$(data.Options.Option).each(function(i){
					optionStr += jumiaListing.dataSet.option.formatOther(data.Options.Option[i]);
				});
				$('tr[cid="'+data.FeedName+'"] select').append(optionStr);
			}
		},
		//变种列表生成格式判断并生成
		lzdSkuBorn:function(){
			var skuData = null,str = null,showType = null;
			if(jumiaListing.dataSet.lzdSkuArr.length > 0){
				showType = 1;
				skuData = jumiaListing.dataSet.lzdSkuObjArr.Variation;
				skuData.showType = showType;
			}else{
				showType = 0;
			}
			//if(skuData == null){return;};
			jumiaListing.dataSet.lzdSkuObjArr.skuShowType = showType;
			str = jumiaListing.lzdSkuThHtmlBorn(skuData);
			str += jumiaListing.lzdSkuTdHtmlBorn(skuData);
			$('div.lzdSkuInfo table').append(str);
			//console.log(dataSet.lzdSkuObjArr.Variation);
			if(jumiaListing.dataSet.lzdSkuObjArr.Variation != undefined){
				if(jumiaListing.dataSet.lzdSkuObjArr.Variation.attrType == 'select'){
					$('div.lzdSkuInfo table').find('[cid="variationEditSpan"]').hide();
				};
			};
		},
		//变种列表ThHtml生成
		lzdSkuThHtmlBorn:function(skuData){
			var str = '',showType = jumiaListing.dataSet.lzdSkuObjArr.skuShowType;
			str += jumiaListing.dataSet.lzdSkuTrStar;
			showType == 1 ? str += jumiaListing.dataSet.lzdSkuTh_1.formatOther(skuData) + jumiaListing.dataSet.lzdSkuTh_2 : str += jumiaListing.dataSet.lzdSkuTh_2 ;
			str += jumiaListing.dataSet.lzdSkuTrEnd;
			return str;
		},
		//变种列表TdHtml生成
		lzdSkuTdHtmlBorn:function(skuData){
			var str = '',showHtml = '',optionStr = '',dataType = '',showType = jumiaListing.dataSet.lzdSkuObjArr.skuShowType;
			if(skuData != null){
				dataType = skuData.attrType;
				if(dataType == 'input'){
					showHtml = jumiaListing.dataSet.input_2;
					skuData.showHtml = showHtml;
					
				};
				if(dataType == 'select'){
					// dzt20160425 发现lazada也是属性 option只有一个的时候Option的结构不是二维数组，而是一维
					if(typeof skuData.Options.Option != "undefined" && typeof skuData.Options.Option.Name != "undefined"){
						var tempOptionArr = [];
						tempOptionArr.push(skuData.Options.Option);
						skuData.Options.Option = tempOptionArr;
					}
					
					$(skuData.Options.Option).each(function(i){optionStr += jumiaListing.dataSet.option.formatOther(skuData.Options.Option[i])});
					skuData.optionStr = optionStr;
					showHtml = jumiaListing.dataSet.skuSelect.formatOther(skuData);
					skuData.showHtml = showHtml;
				};
			}
			str += jumiaListing.dataSet.lzdSkuTrStar;
			showType == 1 ? str += jumiaListing.dataSet.lzdSkuTd_1.formatOther(skuData) + jumiaListing.dataSet.lzdSkuTd_2 : str += jumiaListing.dataSet.lzdSkuTd_2 ;
			str += jumiaListing.dataSet.lzdSkuTrEnd;
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
		pushJumiaData:function(submitData,skuData){
			var all_attrObj = submitData,
				skuAttrArr = skuData,
				type = null,
				kindeditorArr = [];
			jumiaListing.existingImages = [];
			//属性回填
			for(var attrObj in all_attrObj){
				for(var name in all_attrObj[attrObj]){
					if($.inArray(name,jumiaListing.dataSet.packingSize) != -1 || $.inArray(name,jumiaListing.dataSet.shippingTime) != -1){
						$('input[cid="'+name+'"]').val(all_attrObj[attrObj][name]);
					}else if(name != 'Product_photo_primary_thumbnail' && name != 'Product_photo_others_thumbnail'){
						if(name == 'Product_photo_primary'){
							var photo = {};
							$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
//							$('div[role=image-uploader-container] .image-item:eq(0) a img').attr('src',all_attrObj[attrObj][name]);
							if(typeof(all_attrObj[attrObj]['Product_photo_primary_thumbnail']) == "undefined"){//判断是否有缩略图
								photo.thumbnail = all_attrObj[attrObj][name];
							}else{//没有缩略图
								photo.thumbnail = all_attrObj[attrObj]['Product_photo_primary_thumbnail'];
							}
							photo.original = all_attrObj[attrObj][name];
							jumiaListing.existingImages.push(photo);
						}else if(name == 'Product_photo_others'){
							var other_address = all_attrObj[attrObj][name];
							var address_array = other_address.split('@,@');
							if(typeof(all_attrObj[attrObj]['Product_photo_others_thumbnail']) != "undefined"){//判断是否有有缩略图
								var other_address_thumbnail = all_attrObj[attrObj]['Product_photo_others_thumbnail'];
								var address_array_thumbnail = other_address_thumbnail.split('@,@');
								if(address_array.length != address_array_thumbnail.length){
									bootbox.alert("图片获取信息有误！");
									return null;
								}else{
									for(var t=0;t<address_array.length;t++){
										var photo = {};
										photo.thumbnail = address_array_thumbnail[t];
										photo.original = address_array[t];
										jumiaListing.existingImages.push(photo);
									}
								}
							}else{//没有缩略图
								for(var t=0;t<address_array.length;t++){
									var photo = {};
									photo.thumbnail = address_array[t];
									photo.original = address_array[t];
									jumiaListing.existingImages.push(photo);
								}
							}
							
							
//							$('.image-item a').not('.select_photo').each(function(i){
//								$(this).find('img').attr('src',address_array[i]);
//							});
						}else{
							if(name != "BrowseNodes"&& name != "TaxClass"){
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
							}
						}
						
					}
					
				};
			};
			jumiaListing.multiplekindeditorPush(kindeditorArr);
			//变种回填
			var trLength = $('div.lzdSkuInfo table').find('tr').length,
				skuLength = skuAttrArr.length;
			if(trLength - 1 < skuLength){
				var data = jumiaListing.dataSet.lzdSkuObjArr.Variation,
					str = jumiaListing.lzdSkuTdHtmlBorn(data);
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
			jumiaListing.iptValLength();
		},
		//页面计数方法
		iptValLength:function(){
			var num = '';
			$('div.lzdProductTitle').each(function(){
				num = $(this).find('input[type="text"]').val().length;
				$(this).find('span.unm').html(num);
			})
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
				$('#select_shop_info').html("请选择Jumia店铺!");
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
			jumiaListing.dataSet.temporaryData.submitData['store-info'] = JSON.parse('{}');
			jumiaListing.dataSet.temporaryData.submitData['base-info'] = JSON.parse('{}');
			jumiaListing.dataSet.temporaryData.submitData['variant-info'] = JSON.parse('{}');
			jumiaListing.dataSet.temporaryData.submitData['image-info'] = JSON.parse('{}');
			jumiaListing.dataSet.temporaryData.submitData['description-info'] = JSON.parse('{}');
			jumiaListing.dataSet.temporaryData.submitData['shipping-info'] = JSON.parse('{}');
			jumiaListing.dataSet.temporaryData.submitData['warranty-info'] = JSON.parse('{}');
			
			//产品图片
			var returnVal = jumiaListing.setPicAttr();
			if(returnVal == false){
//				bootbox.alert("没有设置主图片！");
				$('#upload_image_info').html("没有设置主图片！");
				$('html,body').animate({scrollTop:$('div[id="image-info"]').offset().top}, 800);
				return ;
			}else{
				jumiaListing.dataSet.temporaryData.submitData['image-info']['Product_photo_primary']=$('#Product_photo_primary').val();
				jumiaListing.dataSet.temporaryData.submitData['image-info']['Product_photo_others']=$('#Product_photo_others').val();
				jumiaListing.dataSet.temporaryData.submitData['image-info']['Product_photo_primary_thumbnail']=$('#Product_photo_primary_thumbnail').val();
				jumiaListing.dataSet.temporaryData.submitData['image-info']['Product_photo_others_thumbnail']=$('#Product_photo_others_thumbnail').val();
			}
			
			//类目属性
			var productDataStr = jumiaListing.getSubmitData();
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
			
			jumiaListing.validBrand = false;
			// 检查品牌是否存在品牌库
			$.showLoading();
			$.ajax({
				async: false,
				type: "post",
				url: "/listing/jumia-listing/get-brands",
				data: { lazada_uid : $("#lazadaUid").val(),name : $('[cid="Brand"]>.secondTd>input').val() , mode:'eq'},
				dataType:'json',
				success:function(data){
					$.hideLoading();
					if(data.code == 400){
						bootbox.alert("您添加的品牌不在Jumia规定范围内，请发到网址：https://goo.gl/Hw8vma 添加。");
						jumiaListing.validBrand = false;
					}else{//刊登成功
						// 拼接提示框信息
						jumiaListing.validBrand = true;
					}
				},
				error:function(){
					$.hideLoading();
					bootbox.alert("网络错误, 请稍后再试！");
				}
			});
			
			if(jumiaListing.validBrand == false) return false;
			
			//类目顺序
			var category_array = new Array();
			$('.category span[data-level]').each(function(){
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
			//Browse Nodes类目录顺序
			var browseNodeCategory_array = new Array();
			if($('div[class="nodeRow"]').length > 0){
				$('div[class="nodeRow"]').each(function(){
					var detail_array = new Array();
					$(this).find('span[data-level]').each(function(){
						if($(this).html() != ""){
							var attr_id = $(this).find("span").eq(0).attr("id");
							detail_array.push(attr_id);
						}
					});
					browseNodeCategory_array.push(detail_array.reverse()); //倒序
				});
				var browseNodeCategories = JSON.stringify(browseNodeCategory_array);
			}else{
				var browseNodeCategories = "";
			}
			
			//变种
			var skus = jumiaListing.getSkuListData();
			if($.trim(skus) == ""){
				//$.fn.message({type:"error",msg:"产品变种不能为空！"});
				return ;
			}
			$.showLoading();
			$.ajax({
				type:'POST',
				url: "/listing/jumia-listing/save-product",
				data:{
					id:productId,
					categories:categories,
					browseNodeCategories:browseNodeCategories,
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
									window.location.href="/listing/jumia-listing/publish";
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
			//检查图片格式
			
			var imageObj = $('div[role="image-uploader-container"]').getAllImagesData();
			var photoOthers = new Array();
			var photoOthers_thumbnail = new Array();
//			if ($('.select_photo img').length == 0){
//				$('div[role=image-uploader-container] .image-item:eq(0) a').addClass('select_photo');
//			}
			
			var returnVal = false;
			if(imageObj.length > 0){
				var select_img = $('.select_photo img').attr('src');
//				$('#Product_photo_primary').val($('.select_photo img').attr('src'));
				for(var i in imageObj){
					if(imageObj.hasOwnProperty(i)){
						if(imageObj[i].thumbnail == select_img){
							$('#Product_photo_primary').val(imageObj[i].original);
							$('#Product_photo_primary_thumbnail').val(imageObj[i].thumbnail);
						}else{
							photoOthers.push(imageObj[i].original);
							photoOthers_thumbnail.push(imageObj[i].thumbnail);
						}
					}
				}
				returnVal = true;
			} else {
				$('#Product_photo_primary').val('');
				$('#Product_photo_primary_thumbnail').val('');
			}
			
//			var photoOthers = new Array();
//			$('.image-item a').not('.select_photo').parent('div[upload-index]').each(function(i){
//				var tmpSrc = $(this).find('a img').attr('src');
//				if ($.inArray(tmpSrc, photoOthers) == -1) {
//					photoOthers.push(tmpSrc);
//				}
//			});
			
			$('#Product_photo_others').val(photoOthers.join('@,@'));
			$('#Product_photo_others_thumbnail').val(photoOthers_thumbnail.join('@,@'));
			return returnVal;
		},
		getSubmitData:function(){
			var attrType = null;
//			jumiaListing.dataSet.temporaryData.submitData = {};
			jumiaListing.dataSet.temporaryData.skuData = [];
			jumiaListing.dataSet.isMustAttrArr = [];
			if(jumiaListing.dataSet.spanCache.length > 0){
				$('span[id="'+jumiaListing.dataSet.spanCache[0]+'"]').parents('tr').remove();
				jumiaListing.dataSet.spanCache = [];
			};
			//常用属性性ger
			$(jumiaListing.dataSet.isShowAttr).each(function(i){
				if(jumiaListing.dataSet.isShowAttr[i] == 'shippingTime'){
					$(jumiaListing.dataSet.shippingTime).each(function(j){
//						jumiaListing.dataSet.temporaryData.submitData[jumiaListing.dataSet.shippingTime[j]] = $('input[cid="'+jumiaListing.dataSet.shippingTime[j]+'"]').val();
						jumiaListing.dataSet.temporaryData.submitData['shipping-info'][jumiaListing.dataSet.shippingTime[j]] = $('input[cid="'+jumiaListing.dataSet.shippingTime[j]+'"]').val();
					})
					
				}else{
					attrType = $('tr[cid="'+jumiaListing.dataSet.isShowAttr[i]+'"]').attr('attrtype');
					jumiaListing.subimtDataBorn(attrType,$('tr[cid="'+jumiaListing.dataSet.isShowAttr[i]+'"]'));
				}
			});
//			$(jumiaListing.dataSet.packingSize).each(function(i){
//				var cid = $('input[cid="'+jumiaListing.dataSet.packingSize[i]+'"]').attr('cid');
//				if($('input[cid="'+jumiaListing.dataSet.packingSize[i]+'"]').val() == ''){
//					jumiaListing.dataSet.isMustAttrArr.push(cid);
//				}
////				jumiaListing.dataSet.temporaryData.submitData[jumiaListing.dataSet.packingSize[i]] = $('input[cid="'+jumiaListing.dataSet.packingSize[i]+'"]').val();
//				jumiaListing.dataSet.temporaryData.submitData['shipping-info'][jumiaListing.dataSet.packingSize[i]] = $('input[cid="'+jumiaListing.dataSet.packingSize[i]+'"]').val();
//
//			});
			$(jumiaListing.dataSet.commonShowAttr).each(function(i){
				attrType = $('tr[cid="'+jumiaListing.dataSet.commonShowAttr[i]+'"]').attr('attrtype');
				jumiaListing.subimtDataBorn(attrType,$('tr[cid="'+jumiaListing.dataSet.commonShowAttr[i]+'"]'));
			});
			//非常用属性get
			$('table[cid="lzdAttrShow"] tr').each(function(){
				attrType = $(this).attr('attrType');
				jumiaListing.subimtDataBorn(attrType,$(this));
			});
			$('table[cid="lzdProductAttr"] tr').each(function(){
				attrType = $(this).attr('attrType');
				jumiaListing.subimtDataBorn(attrType,$(this));
			});
			//运输方式的保存
			if($('table[cid="descriptionAttr"] tr').length != 0){
				$('table[cid="descriptionAttr"] tr').each(function(){
					attrType = $(this).attr('attrType');
					jumiaListing.subimtDataBorn(attrType,$(this));
				});
			}
			
			//验证
			if(jumiaListing.dataSet.isMustAttrArr.length > 0){
//				var arrorName = '';
				$(jumiaListing.dataSet.isMustAttrArr).each(function(k){
//					arrorName == '' ? arrorName += jumiaListing.dataSet.isMustAttrArr[k] : arrorName += '，'+jumiaListing.dataSet.isMustAttrArr[k] ;
						if(jumiaListing.dataSet.isMustAttrArr[k]=='PackageLength' || jumiaListing.dataSet.isMustAttrArr[k]=='PackageWidth'  || jumiaListing.dataSet.isMustAttrArr[k]=='PackageHeight' ){
							var tr_parent = $('input[cid="'+jumiaListing.dataSet.isMustAttrArr[k]+'"]').parents('tr');
							var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+jumiaListing.dataSet.isMustAttrArr[k]+'" style="color:red;"></span></td></tr>';
							$(tr_parent).after(alert_message);
							$('span[id="'+jumiaListing.dataSet.isMustAttrArr[k]+'"]').html($('input[cid="'+jumiaListing.dataSet.isMustAttrArr[k]+'"]').attr("name") + "不能为空！");
							$('html,body').animate({scrollTop:$(tr_parent).offset().top}, 800);
							jumiaListing.dataSet.spanCache.push(jumiaListing.dataSet.isMustAttrArr[k]);
							return false;
						}else{
							aa = true;
							var tr_parent = $('tr[cid="'+jumiaListing.dataSet.isMustAttrArr[k]+'"]');
							var alert_message = '<tr><td class="firstTd"></td><td class="secondTd"><span id="'+jumiaListing.dataSet.isMustAttrArr[k]+'" style="color:red;"></span></td></tr>';
							$(tr_parent).after(alert_message);
							$('span[id="'+jumiaListing.dataSet.isMustAttrArr[k]+'"]').html(tr_parent.attr("name") + "不能为空！");
							$('html,body').animate({scrollTop:$(tr_parent).offset().top}, 800);
							jumiaListing.dataSet.spanCache.push(jumiaListing.dataSet.isMustAttrArr[k]);
							return false;
						}
						
				})
//				$.fn.message({type:"error", msg:arrorName+"的值不能为空!"});
//				bootbox.alert(arrorName+"的值不能为空!");
				return null;
			}
			if(jumiaListing.dataSet.temporaryData.submitData.Description != undefined){
				if(jumiaListing.isContainChinese(jumiaListing.dataSet.temporaryData.submitData.Description) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品描述中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述中不能包含中文字符!");
					return null;
				}
			}
			if(jumiaListing.dataSet.temporaryData.submitData.DescriptionMs != undefined){
				if(jumiaListing.isContainChinese(jumiaListing.dataSet.temporaryData.submitData.DescriptionMs) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品描述（马来语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述（马来语）中不能包含中文字符!");
					return null;
				}
			}
			if(jumiaListing.dataSet.temporaryData.submitData.DescriptionEn != undefined){
				if(jumiaListing.isContainChinese(jumiaListing.dataSet.temporaryData.submitData.DescriptionEn) == 1){
					$.fn.message({type:"error", msg:"保存失败！产品描述（英语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品描述（英语）中不能包含中文字符!");
					return null;
				}			
			}
			if(jumiaListing.dataSet.temporaryData.submitData.Name != undefined){
				if(jumiaListing.isContainChinese(jumiaListing.dataSet.temporaryData.submitData.Name) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品标题中不能包含中文字符!"});
					bootbox.alert("保存失败！产品标题中不能包含中文字符!");
					return null;
				}
			}
			if(jumiaListing.dataSet.temporaryData.submitData.NameMs != undefined){
				if(jumiaListing.isContainChinese(jumiaListing.dataSet.temporaryData.submitData.NameMs) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品标题（马来语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品标题（马来语）中不能包含中文字符!");
					return null;
				}
			}
			if(jumiaListing.dataSet.temporaryData.submitData.NameEn != undefined){
				if(jumiaListing.isContainChinese(jumiaListing.dataSet.temporaryData.submitData.NameEn) == 1){
//					$.fn.message({type:"error", msg:"保存失败！产品标题（英语）中不能包含中文字符!"});
					bootbox.alert("保存失败！产品标题（英语）中不能包含中文字符!");
					return null;
				}
			}
			//验证end
			return JSON.stringify(jumiaListing.dataSet.temporaryData.submitData);
			
		},
		subimtDataBorn:function(type,obj){
			var name = '',value = null,attrObj = {},isMust = $(obj).attr('isMust'),label = $(obj).attr('cid');
			switch(type){
				case 'input':
					name = $(obj).attr('cid');
					if($.inArray(name,jumiaListing.dataSet.descriptionAttr) != -1){
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
					if($.inArray(name,jumiaListing.dataSet.descriptionAttr) != -1){//为了将这两个数据保存在base-info
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
					if($.inArray(name,jumiaListing.dataSet.descriptionAttr) != -1){
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
					if($.inArray(name,jumiaListing.dataSet.descriptionAttr) != -1){
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
				jumiaListing.dataSet.isMustAttrArr.push(label);
				//$.fn.message({type:"error", msg:label+"的值不能为空!"});
			} 
			jumiaListing.dataSet.temporaryData.submitData[info_id][name] = value;
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
		getSkuListData:function(){
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
//			if(jumiaListing.variationIsNullVerification() == 1){
//				bootbox.alert("保存失败！Variation不能为空！");
//				return null;
//			}
			if(jumiaListing.quantityIsNullVerification() == 1){
				bootbox.alert("保存失败！库存不能为空！");
				return null;
			}
			if(jumiaListing.priceIsNullVerification() == 1){
				bootbox.alert("保存失败！价格不能为空！");
				return null;
			}
//			if(jumiaListing.productIdIsNullVerification() == 1){
//				bootbox.alert("保存失败！EAN/UPC/ISBN不能为空！");
//				return null;
//			}
			if(jumiaListing.variationIsRepeat() == 1){
				bootbox.alert("保存失败！variation不能为空！");
				return null;
			}
			if(jumiaListing.skuIsNullVerification() == 1){
//				$.fn.message({type:"error", msg:"保存失败！SKU不能为空！"});
				bootbox.alert("保存失败！SKU不能为空！");
				return null;
			}
			if(jumiaListing.skuIsRepeat() == 1){
//				$.fn.message({type:"error", msg:"保存失败！产品SKU不能重复！"});
				bootbox.alert("保存失败！产品SKU不能重复！");
				return null;
			}
			if(jumiaListing.priceComparisonSalePrice() == 1){
//				$.fn.message({type:"error", msg:"保存失败！促销价必须小于价格！"});
				bootbox.alert("保存失败！促销价必须小于价格！");
				return null;
			}
			if(jumiaListing.skuIsContainChinese() == 1){
//				$.fn.message({type:"error", msg:"保存失败！SKU中不能包含中文字符！"});
				bootbox.alert("保存失败！SKU中不能包含中文字符！");
				return null;
			}
			if(jumiaListing.dateVerificationA() == 1){
//				$.fn.message({type:"error", msg:"保存失败！有促销价的促销时间不能为空！"});
				bootbox.alert("保存失败！有促销价的促销时间不能为空！");
				return null;
			}
			if(jumiaListing.dateVerificationB() == 1){
//				$.fn.message({type:"error", msg:"保存失败！促销开始时间不能小于当前时间！"});
				bootbox.alert("保存失败！促销开始时间不能小于当前时间！");
				return null;
			}
			if(jumiaListing.dateVerificationC() == 1){
//				$.fn.message({type:"error", msg:"保存失败！促销结束时间不能小于促销开始时间！"});
				bootbox.alert("保存失败！促销结束时间不能小于促销开始时间！");
				return null;
			}
			$('div.lzdSkuInfo table tr').each(function(i){
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
					jumiaListing.dataSet.temporaryData.skuData.push(obj);
				}
			})
			return JSON.stringify(jumiaListing.dataSet.temporaryData.skuData);
			//console.log(jumiaListing.dataSet.temporaryData)
			
		},
//		//variation是否为空
//		variationIsNullVerification:function(){
//			var typeA = 0;
//			$('div.lzdSkuInfo table tr').each(function(){
//				if($(this).index() != 0){
//					var num = $(this).find('td[data-name="variation"] input[type="text"]').val();
//					if (num == '' || num == undefined){
//						typeA = 1;
//					}
//				}
//			});
//			return typeA;
//		},
		//库存是否为空
		quantityIsNullVerification:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var num = $(this).find('td[data-name="quantity"] input[type="text"]').val();
					if (num == '' || num == undefined){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		priceIsNullVerification:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var num = $(this).find('td[data-name="price"] input[type="text"]').val();
					if (num == '' || num == undefined){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
//		//productID是否为空
//		productIdIsNullVerification:function(){
//			var typeA = 0;
//			$('div.lzdSkuInfo table tr').each(function(){
//				if($(this).index() != 0){
//					var num = $(this).find('td[data-name="productGroup"] input[type="text"]').val();
//					if (num == '' || num == undefined){
//						typeA = 1;
//					}
//				}
//			});
//			return typeA;
//		},
		//sku是否为空
		skuIsNullVerification:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
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
		skuIsRepeat:function(){
			var typeA = 0,arr = [];
			$('div.lzdSkuInfo table tr').each(function(){
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
		//价格和促销价比对
		priceComparisonSalePrice:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
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
		skuIsContainChinese:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var value = $(this).find('td[data-name="sellerSku"] input[type="text"]').val();
					if (jumiaListing.isContainChinese(value) == 1){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		//促销时间验证
		dateVerificationA:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var salePrice = $(this).find('td[data-name="salePrice"] input[type="text"]').val(),
						saleStartDate = $(this).find('td[data-name="saleStartDate"] input[type="text"]').val(),
						saleEndDate = $(this).find('td[data-name="saleEndDate"] input[type="text"]').val();
					if (salePrice != ''){
						if (saleStartDate == '' || saleEndDate == ''){
							typeA = 1;
						}
					}
				}
			});
			return typeA;
		},
		dateVerificationB:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var saleStartDate = $(this).find('td[data-name="saleStartDate"] input[type="text"]').val(),
						nowDate = new Date().format("yyyy-MM-dd"),
						verificationVal = jumiaListing.compareDate(nowDate,saleStartDate);
					if (verificationVal == 1){
						typeA = 1;
					}
				}
			});
			return typeA;
		},
		dateVerificationC:function(){
			var typeA = 0;
			$('div.lzdSkuInfo table tr').each(function(){
				if($(this).index() != 0){
					var saleStartDate = $(this).find('td[data-name="saleStartDate"] input[type="text"]').val(),
						saleEndDate = $(this).find('td[data-name="saleEndDate"] input[type="text"]').val(),
						verificationVal = jumiaListing.compareDate(saleStartDate,saleEndDate)
					if (verificationVal == 1){
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
		    }else if(start > end){
		    	return 1;
		    }else if(start < end){
		    	return -1;
		    }
		},
		editCompareDate:function(startTime, endTime){
		    var start=new Date(startTime.replace("-", "/").replace("-", "/"));
		    var end=new Date(endTime.replace("-", "/").replace("-", "/"));
		    if(start < end){  
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
				url: "/listing/jumia-listing/list-references",
				timeout:60000,
				success:function(data){
					$.hideLoading();
					if(data != null){
						bootbox.dialog({
							title : Translator.t("引用商品"),
							className: "jumia-listing-reference",
							buttons: {  
								Cancel: {  
							        label: Translator.t("取消"),  
							        className: "btn-default",  
							    }, 
							    OK: {  
							        label: Translator.t("确定"),  
							        className: "btn-primary",  
						            callback: function () {
						            	jumiaListing.userReference();
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
			window.location.href="/listing/jumia-listing/use-reference?listing_id="+listingId;
		},
		addDecriptionPic:function(editorPic){
			editor = editorPic;
			$('#lazada-add-decs-pic #divimgurl').empty();
			jumiaListing.addImageUrlInput();
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
//				var align = jumiaListing.dataSet.descImageAligncenter;
//			}else if(localPicAlign == 'right'){
//				var align = jumiaListing.dataSet.descImageAlignright
//			}else{
//				var align = jumiaListing.dataSet.descImageAlignleft;
//			}
			// 图片描述的图片不并排展示的话 ，这个居中就没问题
//			if(localPicAlign == 'center'){
//				var align = jumiaListing.dataSet.descImageAligncenter;
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
									+ "' name='imgurl[]' size='80' style='width: 300px;' onblur='javascript:jumiaListing.blurImageUrlInput(this)' value="
									+ src
									+ "> <input type='button' value='删除' onclick='javascript:jumiaListing.delImageUrlInput(this)'> <input type='button' value='本地上传' onclick='javascript:jumiaListing.localUpOneImg(this)' ></div>");
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
							url: "/listing/jumia-listing/confirm-uploaded-product",
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