/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof matching === 'undefined')  matching = new Object();

matching.list={
	init:function(){
		$('input[name="start_date"').datepicker({dateFormat:"yy-mm-dd"});
		$('input[name="end_date"').datepicker({dateFormat:"yy-mm-dd"});
	},
	
	searchButtonClick:function(win_list, SettingPage){
		if(win_list == undefined || win_list == ''){
			win_list = 'index'
		}

		//设置page、per_page
		if(SettingPage != undefined && SettingPage){
			var page = $('#carrier-list-header').attr('page');
			$('input[name="page"]').val(page);
		}
		else{
			$('input[name="page"]').val('');
		}
		var per_page = $('#carrier-list-header').attr('per_page');
		$('input[name="per-page"]').val(per_page);
		
		var Url=global.baseUrl +'catalog/matching/'+ win_list;
		$.location.state(Url,'小老板',$("#searchForm").serialize(),0,'post',false);
	},
	
	searchBtnPubChange:function(obj,type,win_list){
		$('input[name="'+type+'"]').val($(obj).attr('value'));
		
		//当选筛选平台时，清空店铺账号
		if(type == 'platform'){
			$('input[name="selleruserid"]').val('');
		}
		//$("#searchForm").submit();
		matching.list.searchButtonClick(win_list);
	},

	//手工配对、更换配对
	changge_matching:function(orderitemid,sku,rootsku){
		$.modal({
			url:'/order/order/pair-product',
			method:'POST',
			data:{orderitemid : orderitemid, sku : sku, type : 1},
		},
		'搜索',{footer:false,inside:false}).done(function($modal){
			///显示配对sku搜索页商品
			matching.list.SelectWareHoseProducts(1);
 			
 			$('#btnSelectSearch').click(function(){
 				var searchtext=$.trim($('#searchWareHoseProductsValue').val());
 				var Searchtype=$('#searchWareHoseProductsType').val(); //搜索类型
 				if(searchtext=='' || searchtext==null){
					$('#productbody').addClass('hidden');
					$('#productbody_nosearch').addClass('hidden');
					$('#productbodylist').removeClass('hidden');
					matching.list.SelectWareHoseProducts(1);
 				}
 				else{
 					matching.list.SelectWareHoseProducts(1);
					return true;
 				}
 			});
 			
 			$('#searchWareHoseProductsValue').on('keypress',function(e)
 			{
 				if(e.which == 13) 
 					$('#btnSelectSearch').click();
 			});
 				 			
			$('.modal-close').click(function(){	
				$modal.close();
			});
		});
	},
	
	//解绑配对
	delete_matching:function(orderitemid,sku,rootsku){
   		bootbox.dialog({
			title: Translator.t("解除"),
			className: "order_info", 
			message: $('#dialog2').html(),
			buttons:{
				Ok: {  
					label: Translator.t("确认"),  
					className: "btn-success",  
					callback: function () {
						$.showLoading();
						$.ajax({
							type: "POST",
							dataType: 'json',
							url:'/order/order/save-realtion',
							data:{orderitemid:orderitemid,sku:sku,rootsku:rootsku,ordertype:0,type:$('input:radio[name="warningPairproduct"]:checked').val()},
							success: function (result) {
								if (result.result.ack == true){
									$.alertBox('<p class="text-success" style="font-size:24px;">解除成功</p>');
									
									if (result.result.success == false){
										bootbox.alert(result.message);
									}
									
									matching.list.searchButtonClick('', true);
									$.hideLoading();
									return true;
								}else{
									bootbox.alert(result.message);
									$.hideLoading();
									return false;
								}
							},
							error: function(){
								bootbox.alert("Internal Error");
								$.hideLoading();
								return false;
							}
						});
					}
				}, 
				Cancel: {  
					label: Translator.t("取消"),  
					className: "btn-default",  
					callback: function () {  
					}
				}, 
			}
		});	
	},
	
	//显示配对sku搜索页商品
	SelectWareHoseProducts:function(page){
		var condition=$.trim($('#searchWareHoseProductsValue').val()); //搜索条件
		var type=$('#searchWareHoseProductsType').val(); //搜索类型
		$.ajax({
			type: "POST",
			dataType: 'html',
			url:'/order/order/select-ware-hose-products',
			data:{page:page,condition:condition,type:type},
			success: function (result) {
				//替换function路劲
				var re = new RegExp("OrderCommon.Choice", "g");
				result = result.replace(re, "matching.list.Choice");

				$('#productbodylist').html(result);
				$('#pagination > ul > li a').each(function()
				{
        			$(this).attr('href', 'javascript:matching.list.SelectWareHoseProducts('+$(this).html()+');');
				});
        		$('#pagination > ul .prev a').attr('href', 'javascript:matching.list.SelectWareHoseProducts('+(page-1)+');');
        		$('#pagination > ul .next a').attr('href', 'javascript:matching.list.SelectWareHoseProducts('+(page+1)+');');
			},
			error: function(){
				bootbox.alert("Internal Error");
			}
		});
	},
	
	//商品搜索框，确认配对
	Choice:function(obj){
		var orderitemid = $('#searchWareHoseProductsid').val();
		var arr = orderitemid.split('-');
		var sku=$('#searchWareHosesku').val();
		$('#titlePairproduct').html('确认要更换配对关系？');			
		var searchtext=obj;
		bootbox.dialog({
			title: Translator.t("配对"),
			className: "order_info", 
			message: $('#dialog2').html(),
			buttons:{
				Ok: {  
					label: Translator.t("确认"),  
					className: "btn-success",  
					callback: function () {
						$.showLoading();
						$.ajax({
							type: "POST",
							dataType: 'json',
							url:'/order/order/save-realtion',
							data:{orderitemid:arr[0],sku:sku,rootsku:searchtext,ordertype:arr[1],type:$('input:radio[name="warningPairproduct"]:checked').val()},
							success: function (result) {
								if (result.result.ack == true){
									$.alertBox('<p class="text-success" style="font-size:24px;">配对成功</p>');
									
									if (result.result.success == false){
										bootbox.alert(result.message);
									}
									
									$('.modal-close').click();
									matching.list.searchButtonClick('', true);
									$.hideLoading();
									return true;
								}else{
									bootbox.alert(result.message);
									$.hideLoading();
									return false;
								}
							},
							error: function(){
								bootbox.alert("Internal Error");
								$.hideLoading();
								return false;
							}
						});
					}
				}, 
				Cancel: {  
					label: Translator.t("取消"),  
					className: "btn-default",  
					callback: function () {  
					}
				}, 
			}
		});	
	},
	
	//待确认，点击确认配对
	ConfirmMatching:function(obj){
		var row = $(obj).parent().parent();
		var name = $(row).attr("name");
		var itemid = name.replace('matching_item_', '');
		if(name == itemid)
			itemid = '0';
		var sku = $(row).find('span[name="order_sku[]"]').html();
		var root_sku = $(obj).prev().html();

		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/order/save-realtion',
			data:{orderitemid:itemid, sku:sku, rootsku:root_sku, ordertype:1,type:0},
			success: function (result) {
				if (result.result.ack == true){
					$.alertBox('<p class="text-success" style="font-size:24px;">配对成功</p>');
					
					if (result.result.success == false){
						bootbox.alert(result.message);
					}
					
					$('.modal-close').click();
					matching.list.searchButtonClick('', true);
					$.hideLoading();
					return true;
				}else{
					bootbox.alert(result.message);
					$.hideLoading();
					return false;
				}
			},
			error: function(){
				bootbox.alert("Internal Error");
				$.hideLoading();
				return false;
			}
		});
	},
	
	//批量确认配对
	BathConfirmMatching : function(){
		var msg = '';
		$.showLoading();
		//循环本页待确定，一个个确认配对
		$('tr[name^="matching_item_"').each(function(){
			var name = $(this).attr("name");
			var itemid = name.replace('matching_item_', '');
			if(name == itemid)
				itemid = '0';
			var sku = $(this).find('span[name="order_sku[]"]').html();
			//查询待确认SKU
			var root_sku = '';
			$(this).find('span[name="to_be_confirmed_sku"]').each(function(){
				root_sku = $(this).html();
				return true;
			});

			if(root_sku != ''){
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/order/order/save-realtion',
					data:{orderitemid:itemid, sku:sku, rootsku:root_sku, ordertype:1,type:0},
					success: function (result) {
						if (result.result.ack == true){
							if (result.result.success == false){
								bootbox.alert(result.message);
							}
							return true;
						}else{
							bootbox.alert(result.message);
							return false;
						}
					},
					error: function(){
						return false;
					}
				});
			}
		});
		
		if(msg == ''){
			$.alertBox('<p class="text-success" style="font-size:24px;">操作成功</p>');
			matching.list.searchButtonClick('', true);
		}
		else{
			bootbox.alert(msg);
		}
		$.hideLoading();
	},
	
	//更新未配对订单
	RefreshMatching:function(){
		//$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$.showLoading();
		
		$.ajax({
			type: 'post',
			cache: 'false',
			dataType: 'json',
			url: '/catalog/matching/refresh-matching',
			success: function(result){
				$.hideLoading();
				bootbox.alert("更新成功！");
				matching.list.searchButtonClick('', true);
			},
			error:function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			}
		});
	},
	
	//打开自动识别SKU界面
	ShowAutomaticMatchingBox : function () {
		$.modal
		(
			{
			  url:'/catalog/matching/show-automatic-matching-box',
			  method:'post',
			  data:{}
			},
			'自动识别SKU',
			{
				footer:false,inside:false
			}
		).done(function($modal){
			$('#btn_automaticM').click(function()
			{
				$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
				$.showLoading();
				
				var automaticMType = $('input[name="automaticMType[]"]:checked').val();
				var startStr = $('input[name="startStr"]').val();
				var endStr = $('input[name="endStr"]').val();
				var startLen = $('input[name="startLen"]').val();
				var endLen = $('input[name="endLen"]').val();
				
				$.ajax({
					type : 'post',
			        cache : 'false',
			        dataType: 'json',
			        data: {
			        		'automaticMType': automaticMType,
			        		'startStr': startStr,
			        		'endStr': endStr,
			        		'startLen': startLen,
			        		'endLen': endLen,
			        		'formData': $("#searchForm").serialize(),
			        	},
					url:'/catalog/matching/automatic-matching', 
					success: function (result) 
					{
						var msg = '';
						if(result['status'] == 0)
							msg = result['msg'];
						else
							msg = '<p style="font-size:16px; line-height:20px;">识别成功：本次识别 <span style="color:#91c854;">'+ result['Qty'] +'</span> 个订单SKU配对。<br>点击确认刷新窗口数据</p>';

						$.hideLoading();
						bootbox.alert({
							buttons: {
								ok: {
									label: '确认',
									className: 'btn-primary'
								}
							},
							message: Translator.t(msg),
							callback: function() {
								matching.list.searchButtonClick('', true);
							}
						});
					},
					error: function()
					{
						bootbox.alert("出现错误，请联系客服求助...");
						$.hideLoading();
						return false;
					}
				});
			});
			$('input[name="startLen"],input[name="endLen"]').keyup(function()
			{
				var val = $(this).val();
				$(this).val(val.replace(/\D|^0/g,''));
			});
		});
	},
	
	//一键生成商品
	ShowCreateProductBox : function () {
		/*$.modal
		(
			{
			  url:'/catalog/matching/show-create-product-box',
			  method:'post',
			  data:$("#searchForm").serialize(),
			},
			'一键生成商品',
			{
				footer:false,inside:false
			}
		).done(function($modal){
			
		});
		*/
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/catalog/matching/show-create-product-box',
			data: $("#searchForm").serialize(),
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "product_info",
					title: Translator.t("一键生成商品"),//
					message: result,
					buttons:{
						OK: {
							label: Translator.t("确定生成"),  
							className: "btn-primary",
							callback: function () {
								var rtn = matching.create_pro.createProBtn();
								if(rtn){
									window.location.reload();
									return true;
								}
								else return false;
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
				return true;
				
			},
			error :function () {
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
}