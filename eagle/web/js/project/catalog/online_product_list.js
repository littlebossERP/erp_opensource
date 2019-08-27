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

if (typeof online_product === 'undefined')  online_product = new Object();

online_product.list={
	init:function(){

	},
	
	searchButtonClick:function(win_list, SettingPage){
		if(win_list == undefined || win_list == ''){
			win_list = 'product-list'
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
		
		online_product.list.searchButtonClick(win_list);
	},

	//手工配对、更换配对
	changge_matching:function(pro_id, sku){
		$.modal({
			url:'/order/order/pair-product',
			method:'POST',
			data:{orderitemid : '', sku : '', type : 1},
		},
		'搜索',{footer:false,inside:false}).done(function($modal){
			//添加在线商品信息
			$('#searchWareHoseProductsValue').after('<input id="matching_pro_id" type="hidden" class="form-control" value="'+ pro_id +'">');
			$('#searchWareHoseProductsValue').after('<input id="matching_sku" type="hidden" class="form-control" value="'+ sku +'">');
			///显示配对sku搜索页商品
			online_product.list.SelectWareHoseProducts(1);
 			
 			$('#btnSelectSearch').click(function(){
 				var searchtext=$.trim($('#searchWareHoseProductsValue').val());
 				if(searchtext=='' || searchtext==null){
					$('#productbody').addClass('hidden');
					$('#productbody_nosearch').addClass('hidden');
					$('#productbodylist').removeClass('hidden');
					online_product.list.SelectWareHoseProducts(1);
 				}
 				else{
 					online_product.list.SelectWareHoseProducts(1);
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
	delete_matching:function(pro_id, sku, rootsku){
		$.showLoading();
		var platform = $('#searchForm input[name="platform"]').val();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/catalog/matching/change-matching-product',
			data:{
				platform:platform,
				pro_id:pro_id,
				sku:sku,
				rootsku:rootsku,
				matching_type:2,
			},
			success: function (result) {
				if (result.success == true){
					$.alertBox('<p class="text-success" style="font-size:24px;">解绑成功</p>');
					
					online_product.list.searchButtonClick('', true);
					$.hideLoading();
					return true;
				}else{
					bootbox.alert(result.msg);
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
				result = result.replace(re, "online_product.list.Choice");

				$('#productbodylist').html(result);
				$('#pagination > ul > li a').each(function()
				{
        			$(this).attr('href', 'javascript:online_product.list.SelectWareHoseProducts('+$(this).html()+');');
				});
        		$('#pagination > ul .prev a').attr('href', 'javascript:online_product.list.SelectWareHoseProducts('+(page-1)+');');
        		$('#pagination > ul .next a').attr('href', 'javascript:online_product.list.SelectWareHoseProducts('+(page+1)+');');
			},
			error: function(){
				bootbox.alert("Internal Error");
			}
		});
	},
	
	//商品搜索框，确认配对
	Choice:function(obj){
		var pro_id = $('#matching_pro_id').val();
		var sku = $('#matching_sku').val();
		var rootsku = obj;
		
		var platform = $('#searchForm input[name="platform"]').val();
		
		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/catalog/matching/change-matching-product',
			data:{
				platform:platform,
				pro_id:pro_id,
				sku:sku,
				rootsku:rootsku,
				matching_type:1,
			},
			success: function (result) {
				if (result.success == true){
					$.alertBox('<p class="text-success" style="font-size:24px;">配对成功</p>');
					
					$('.modal-close').click();
					online_product.list.searchButtonClick('', true);
					$.hideLoading();
					return true;
				}else{
					bootbox.alert(result.msg);
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
	
	//待确认，点击确认配对
	ConfirmMatching:function(obj){
		var row = $(obj).parent().parent();
		var name = $(row).attr("name");
		var pro_id = name.replace('matching_item_', '');
		if(name == pro_id)
			pro_id = '0';
		var sku = $(row).find('span[name="online_sku[]"]').html();
		var root_sku = $(obj).prev().html();
		var platform = $('#searchForm input[name="platform"]').val();

		$.showLoading();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/catalog/matching/change-matching-product',
			data:{
				platform:platform,
				pro_id:pro_id,
				sku:sku,
				rootsku:root_sku,
				matching_type:1,
			},
			success: function (result) {
				if (result.success == true){
					$.alertBox('<p class="text-success" style="font-size:24px;">配对成功</p>');
					
					$('.modal-close').click();
					online_product.list.searchButtonClick('', true);
					$.hideLoading();
					return true;
				}else{
					bootbox.alert(result.msg);
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
				
				var platform = $('#searchForm input[name="platform"]').val();
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
			        		'type' : platform,
			        	},
					url:'/catalog/matching/automatic-matching', 
					success: function (result) 
					{
						var msg = '';
						if(result['status'] == 0)
							msg = result['msg'];
						else
							msg = '<p style="font-size:16px; line-height:20px;">识别成功：本次识别 <span style="color:#91c854;">'+ result['Qty'] +'</span> 个平台SKU配对。<br>点击确认刷新窗口数据</p>';

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
								online_product.list.searchButtonClick('', true);
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
		$.showLoading();
		$.ajax({
			type: "GET",
			url: '/catalog/matching/show-create-online-product-box',
			data: $("#searchForm").serialize(),
			success: function (result) {
				$.hideLoading();
				bootbox.dialog({
					className : "product_info",
					title: Translator.t("一键生成商品<span style='color: red'>( 一次性最多100条 )</span>"),//
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