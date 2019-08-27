/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lzhl <zhiliang.lu@witsion.com> 2015-04-20 eagle 2.0
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *cdiscount在线商品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/order
 * @package		suggestion
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */
 
if (typeof cdOffer === 'undefined')  cdOffer = new Object();

cdOffer.list = {
	printInfo:[],
	init:function(){
		//checkbox
		$(".offer-list .ck_one").unbind('click').click(function(){
			if ( typeof($(this).attr('checked'))=='undefined' || $(this).attr('checked')==false ){
				$(this).attr('checked','checked');
			}else{
				$(this).attr('checked',false);
			}
		});
		$("#ck_all").click(function(){
			$(".offer-list .ck_one").prop('checked',this.checked);
		});
	},
	view_offer:function(id){
		$.showLoading();
		var url='/listing/cdiscount/view-offer?id='+id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "show-offer-detail",
					title: Translator.t("商品详情"),
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			}
		);
	},
	print_offers:function(){
		var offerData =new Array();//选择的商品数据
		$("input#ck_one:checked").each(function(){
			var tr = $(this).parent().parent();
			var aOffer = new Object();
			aOffer['img'] = tr.find("td:eq(1)").html();
			aOffer['ean'] = tr.find("td:eq(2)").html();
			aOffer['id'] = tr.find("td:eq(3)").html();
			aOffer['name'] = tr.find("td:eq(4)").html();
			aOffer['stock'] = tr.find("td:eq(5)").html();
			aOffer['price'] = tr.find("td:eq(6)").html();
			aOffer['brand'] = tr.find("td:eq(7)").html();
			aOffer['isActive'] = tr.find("td:eq(8)").html();
			aOffer['creation_date'] = tr.find("td:eq(9)").html();
			aOffer['seller_id'] = tr.find("td:eq(10)").html();
			aOffer['is_bestseller'] = tr.find("td:eq(11)").html();
			aOffer['bestseller_name'] = tr.find("td:eq(12)").html();
			aOffer['bestseller_price'] = tr.find("td:eq(13)").html();

			offerData.push(aOffer);

		});

		if(offerData.length<=0){
			bootbox.alert({
				title:'Warning',
				message:Translator.t('请选择需要打印的商品！'),
			});
		}else{
			cdOffer.list.printInfo=offerData;
			window.open(global.baseUrl+'order/cdiscount-order/print-offers');
		}
	},
	HotSale:function(offer_ids){
		$.showLoading();
		var url='/listing/cdiscount/set-concerned';
		$.ajax({
			url:url,
			data:{offer_ids:offer_ids,type:'H'},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					bootbox.alert(result.message);
					return false;
				}else{
					var remaining = (result.remaining!=='undefined')?result.remaining:'';
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定',  
								className: 'btn-myStyle'  
							}  
						},  
						message: '爆款监视成功！'+'<br>'+remaining,  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	batchHotSale:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')!=='H'){
				if(idstr=='')
					idstr+=$(this).val()+'@@'+$(this).data('seller');
				else
					idstr+=';'+$(this).val()+'@@'+$(this).data('seller');
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.HotSale(idstr);
			});
		}
	},
	unHotSale:function(offer_ids){
		$.showLoading();
		var url='/listing/cdiscount/set-concerned';
		$.ajax({
			url:url,
			data:{offer_ids:offer_ids,type:'N'},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					bootbox.alert(result.message);
					return false;
				}else{
					var remaining = (result.remaining!=='undefined')?result.remaining:'';
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定',  
								className: 'btn-myStyle'  
							}  
						},  
						message: '取消爆款监视成功！',  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	batchUnHotSale:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')=='H'){
				if(idstr=='')
					idstr+=$(this).val();
				else
					idstr+=';'+$(this).val();
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}	
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.unHotSale(idstr);
			});
		}
	},
	Concerned:function(offer_ids){
		$.showLoading();
		var url='/listing/cdiscount/set-concerned';
		$.ajax({
			url:url,
			data:{offer_ids:offer_ids,type:'F'},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					bootbox.alert(result.message);
					return false;
				}else{
					var remaining = (result.remaining!=='undefined')?result.remaining:'';
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定',  
								className: 'btn-myStyle'  
							}  
						},  
						message: '关注成功！'+'<br>'+remaining,  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	batchConcerned:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')!=='F'){
				if(idstr=='')
					idstr+=$(this).val()+'@@'+$(this).data('seller');
				else
					idstr+=';'+$(this).val()+'@@'+$(this).data('seller');
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}	
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.Concerned(idstr);
			});
		}
	},
	unConcerned:function(offer_ids){
		$.showLoading();
		var url='/listing/cdiscount/set-concerned';
		$.ajax({
			url:url,
			data:{offer_ids:offer_ids,type:'N'},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					bootbox.alert(result.message);
					return false;
				}else{
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定',  
								className: 'btn-myStyle'  
							}  
						},  
						message: '取消关注成功！',  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	batchUnConcerned:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')=='F'){
				if(idstr=='')
					idstr+=$(this).val();
				else
					idstr+=';'+$(this).val();
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}	
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.unConcerned(idstr);
			});
		}
	},
	Ignore:function(offer_ids){
		bootbox.confirm("忽略商品后，小老板后台将不会再自动获取该商品的信息，确定忽略？",function(r){
			if (! r) return;
			$.showLoading();
			var url='/listing/cdiscount/set-concerned';
			$.ajax({
				url:url,
				data:{offer_ids:offer_ids,type:'I'},
				type: "POST",
				dataType: 'json',
				success: function (result) {
					$.hideLoading();
					if (result.success == false){
						bootbox.alert(result.message);
						return false;
					}else{
						bootbox.alert({  
							buttons: {  
							   ok: {  
									label: '确定',  
									className: 'btn-myStyle'  
								}  
							},  
							message: '忽略成功！',  
							callback: function() {  
								window.location.reload();
							},  
						}); 
					}
				},
				error: function(){
					$.hideLoading();
					bootbox.alert('页面错误，请联系客服。');
					return false;
				}
			});
		});
	},
	batchIgnore:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')!=='I'){
				if(idstr=='')
					idstr+=$(this).val();
				else
					idstr+=';'+$(this).val();
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}	
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.Ignore(idstr);
			});
		}
	},
	unIgnore:function(offer_ids){
		$.showLoading();
		var url='/listing/cdiscount/set-concerned';
		$.ajax({
			url:url,
			data:{offer_ids:offer_ids,type:'N'},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					bootbox.alert(result.message);
					return false;
				}else{
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定',  
								className: 'btn-myStyle'  
							}  
						},  
						message: '取消忽略成功！',  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	batchUnIgnore:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')=='I'){
				if(idstr=='')
					idstr+=$(this).val();
				else
					idstr+=';'+$(this).val();
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}	
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.unIgnore(idstr);
			});
		}
	},
	reActive:function(offer_ids){
		$.showLoading();
		var url='/listing/cdiscount/re-active-t-status';
		$.ajax({
			url:url,
			data:{offer_ids:offer_ids},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					var remaining = (result.remaining!=='undefined')?result.remaining:'';
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定', 
								className: 'btn-myStyle'  
							}  
						},  
						message: result.message + remaining,  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}else{
					var remaining = (result.remaining!=='undefined')?result.remaining:'';
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定', 
								className: 'btn-myStyle'  
							}  
						},  
						message: '恢复成功！'+'<br>'+remaining,  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	batchReActive:function(){
		if($('.ck_one:checked').length==0){
			bootbox.alert("请选择要操作的商品");return false;
		}
		idstr='';
		skustr='';
		$('input[name="offer_id[]"]:checked').each(function(){
			if($(this).data('concerned')=='H'){
				if(idstr=='')
					idstr+=$(this).val()+'@@'+$(this).data('seller')+'@@'+'H';
				else
					idstr+=';'+$(this).val()+'@@'+$(this).data('seller')+'@@'+'H';
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}
			if($(this).data('concerned')=='F'){
				if(idstr=='')
					idstr+=$(this).val()+'@@'+$(this).data('seller')+'@@'+'F';
				else
					idstr+=';'+$(this).val()+'@@'+$(this).data('seller')+'@@'+'F';
				if(skustr=='')
					skustr+=$(this).data('sku');
				else
					skustr+=';<br>'+$(this).data('sku');
			}
		});
		if(idstr!==''){
			bootbox.confirm("有以下商品符合批量操作类型：<br>"+skustr+"<br>是否确认操作？",function(r){
				if (!r) return;
				cdOffer.list.reActive(idstr);
			});
		}
	},
	openAccountList:function(){
		$.showLoading();
		var url='/listing/cdiscount/account-list';
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "show-account-list",
					title: Translator.t("设置获取商品列表"),
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			}
		);
	},
	getFullOfferList:function(){
		$.showLoading();
		var seller = '';
		$('input[name="seller_id"]:checked').each(function(){
			if(seller=='')
				seller+=$(this).val();
			else
				seller+=';'+$(this).val();
		});
		if(seller==''){
			$.hideLoading();
			bootbox.alert('没有选择有效的账号。');
			return false;
		}
		$.ajax({
			url:'/listing/cdiscount/get-full-offer-list',
			data:{seller_id:seller},
			type: "POST",
			dataType: 'json',
			success: function (result) {
				$.hideLoading();
				if (result.success == false){
					bootbox.alert(result.message);
					return false;
				}else{
					bootbox.alert({  
						buttons: {  
						   ok: {  
								label: '确定',  
								className: 'btn-myStyle'  
							}  
						},  
						message: '操作成功！',  
						callback: function() {  
							window.location.reload();
						},  
					}); 
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert('页面错误，请联系客服。');
				return false;
			}
		});
	},
	viewQuota:function(){
		$.showLoading();
		var url='/listing/cdiscount/view-quota';
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "view-quota",
					title: Translator.t("用户额度"),
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			}
		);
	},
	checkHistroy : function (product_id){
		if(product_id==''){
			bootbox.alert("请选择要查看的商品");return false;
		}
		$.showLoading();
		var url='/listing/cdiscount/view-history?product_id='+product_id;
		$.get(
			url,
			function(data){
				$.hideLoading();
				bootbox.dialog({
					className : "show-bestseller-histroy",
					title: Translator.t("Best Seller获取历史"),
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
			}
		);
	},
	
	openExcelUpload : function(){
		$.showLoading();
		$.ajax({
			type: "GET",
				dataType: 'html',
				url:'/listing/cdiscount/excel-upload', 
				success: function (result) {
					$.hideLoading();
					bootbox.dialog({
						title: Translator.t("Excel导入商品"),
						className: "excel-upload-win", 
						message: result,
						buttons:{
							Ok: {  
								label: Translator.t("保存"),  
								className: "btn-success",  
								callback: function () { 
									return cdOffer.list.importSku();
								}
							}, 
							Cancel: {  
								label: Translator.t("关闭"),  
								className: "btn-default",  
								callback: function () {  
								}
							}, 
						}
					});	
				},
				error: function(){
					$.hideLoading();
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	importSku:function(){
		if($("#sku_list_excel").val()){
			$.showLoading();
			$.ajaxFileUpload({
				 url:'/listing/cdiscount/import-sku',
				 fileElementId:'sku_list_excel',
				 type:'post',
				 dataType:'json',
				 success: function (result){
					 $.hideLoading();
					 if(result.ack=='failure'){
						bootbox.alert(result.message);
					 }else{
						bootbox.alert('操作已成功');
					 }
				 },  
				 error: function ( xhr , status , messages ){
					 $.hideLoading();
					 bootbox.alert(messages);
				 }  
			});  
		}else{
			bootbox.alert("请添加文件");
		}
	},
};
cdOffer.excel = {
	init : function(){
		var watermarkHtml =  '<p>在此录入商品关键编码(SKU/EAN/平台ID)进行筛选</p>';
		watermarkHtml +="<p>① 请用 分号 分隔，例如：“SKU001;SKU002”；</p>";
		watermarkHtml +="<p>② 也可以通过复制excel多个列，然后粘贴，例如:";
		watermarkHtml +="<p>SKU001</p>";
		watermarkHtml +="<p>SKU002</p>";
		watermarkHtml +="<p>SKU003</p>";
		$('textarea#keyword').watermark(watermarkHtml, {fallback: false});
	},
};



cdOffer.printPage = {
	init : function(){
		printInfo = opener.cdOffer.list.printInfo;
		
		if(typeof(printInfo) != 'undefined' && printInfo ){
			printHtml = '<table cellspacing="0" border="1" width="1280px">';

			//title	
			printHtml+='<tr>'+
				'<th width="8%" style="text-align:center;"><b>图片</b></th>'+
				'<th width="8%" style="text-align:center;"><b>EAN</b></th>'+
				'<th width="10%" style="text-align:center;"><b>Product Id/<br>Seller SKU</b></th>'+
				'<th width="17%" style="text-align:center;"><b>产品名称</b></th>'+
				'<th width="5%" style="text-align:center;"><b>库存</b></th>'+
				'<th width="5%" style="text-align:center;"><b>售价</b></th>'+
				'<th width="10%" style="text-align:center;"><b>品牌</b></th>'+
				'<th width="7%" style="text-align:center;"><b>是否在售</b></th>'+
				'<th width="10%" style="text-align:center;"><b>创建日期</b></th>'+
				'<th width="15%" style="text-align:center;"><b>店铺账号</b></th>'+
				'<th width="8%" style="text-align:center;"><b>是否BestSeller</b></th>'+
				'<th width="10%" style="text-align:center;"><b>BestSeller name</b></th>'+
				'<th width="8%" style="text-align:center;"><b>Best Seller Price</b></th>'+
			'</tr>';
			 // 载入内容
			for (var i = 0; i < printInfo.length; i++) {
				printHtml += '<tr>';
				for(var j in printInfo[i]){
					printHtml += '<td style="text-align:center;font-size:12px;">';
					printHtml += printInfo[i][j];
					printHtml += '</td>';
				}
				printHtml += '</tr>';
			}
			printHtml += '</table>';
			
			$('body').html(printHtml);
		}else{
			bootbox.alert({
				title:'Warning',
				message:Translator.t('没有选择需要打印的商品'),
			});
		}	
	},
};

cdOffer.TerminatorMailSetting = {
	init:function(){
		$("input[name='user_valid_mail_address']").on('change',function(){
			var mail = $(this).val();
			var myreg =  /^[A-Za-z0-9]+([-_.][A-Za-z0-9]+)*@([A-Za-z0-9]+[-.])+[A-Za-z0-9]{2,5}$/;
			if(myreg.test(mail)){
				$("#setting input[name!='user_valid_mail_address']").attr('disabled',false);
				$("#setting button[type='submit']").attr('disabled',false);
				$("#setting tr[data-row!='valid_mail_address']").css('background','#f4f9fc');
			}else{
				$("#setting input[name!='user_valid_mail_address']").attr('disabled',true);
				$("#setting button[type='submit']").attr('disabled',true);
				$("#setting tr[data-row!='valid_mail_address']").css('background','#a3a1a1');
				bootbox.alert({
					title:'Error',
					message:Translator.t('必须填入有效的邮箱地址'),
				});
				return false;
			}
		});
	},
};

$(function(){
	if($(".left_menu.menu_v2 .iconfont.icon-jinggao").length>0){
		$(".left_menu.menu_v2 .iconfont.icon-jinggao").attr('title','有设置需要更新');
	}
});