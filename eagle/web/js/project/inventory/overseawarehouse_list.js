/**
 +------------------------------------------------------------------------------
 * 仓库列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		inventory/warehouse
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof Overseawarehouse === 'undefined')  OverseaWarehouse = new Object();
OverseaWarehouse.list = {
	'init': function() 
	{
		//换页
		$('#matching-pager-group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
		{
			$(this).attr('href', 'javascript:OverseaWarehouse.list.changePage(1, '+ $(this).html() +', 0);');
		});
		$('#matching-pager-group .btn-group > .pagination > li a').each(function()
		{
			$(this).attr('href', 'javascript:OverseaWarehouse.list.changePage(1, 0, '+ $(this).html() +');');
		});
		$('#matching-pager-group .btn-group .prev a').attr('href', 'javascript:OverseaWarehouse.list.changePage(2);');
		$('#matching-pager-group .btn-group .next a').attr('href', 'javascript:OverseaWarehouse.list.changePage(3);');
	},
	
	'refresh' : function(){
		 document.getElementById("overseawarehouse_list_params_form").submit();
	},
	
	'selectStock' : function(warehouse_id, carrier_code, third_party_code){
		$('input[name="warehouse_id"]').val(warehouse_id);
		$('input[name="carrier_code"]').val(carrier_code);
		$('input[name="third_party_code"]').val(third_party_code);
		$('input[name="accountid"]').val('');
		$('input[name="per_page"]').val('');
		$('#search_sku').val('');
		$('input[name="page"]').val('');
			
		 OverseaWarehouse.list.refresh();
	},
	
	'selectAccount' : function(accountid){
		 $('input[name="accountid"]').val(accountid);
		 $('input[name="per_page"]').val('');
		 $('input[name="page"]').val('');
		 $('#search_sku').val('');
		 OverseaWarehouse.list.refresh();
	},
	
	'changePage' : function(selectType, per_page, page){
		
		//前页、后页
		if( selectType == 2 || selectType == 3)
		{
			page = $('#matching-pager-group .btn-group  .pagination > .active a').first().html();
			if(selectType == 2 && page > 0)
				page--;
			else if(selectType == 3)
				page++;
		}
		
		if(page > 0)
			page--;
		
		//获取条/页 
		if(per_page == null || per_page == 0)
			per_page = $('#matching-pager-group .pageSize-dropdown-div .dropdown-menu > .active a').first().html();
		
		$('input[name="per_page"]').val(per_page);
		$('input[name="page"]').val(page);

		OverseaWarehouse.list.refresh();
	},
	
	//同步海外仓SKU和库存
	'synchronizeSku' : function () 
	{
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$.showLoading();
		
		var warehouse_id = $('input[name="warehouse_id"]').val();
		var third_party_code = $('input[name="third_party_code"]').val();
		var accountid = $('input[name="accountid"]').val();
		
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data: {'warehouse_id':warehouse_id, 'third_party_code':third_party_code, 'accountid' : accountid},
			url:'/inventory/oversea-warehouse/synchronize-oversea-w-sku', 
			success: function (result) 
			{
				var msg = '同步成功,点击确认刷新窗口数据';
				if(result['status'] == 0)
					msg = result['msg'];
				$.hideLoading();
				bootbox.alert({
					buttons: {
						ok: {
							label: 'OK',
							className: 'btn-primary'
						}
					},
					message: Translator.t(msg),
					callback: function() {
						OverseaWarehouse.list.refresh();
					}
				});
				//Overseawarehouse.list.refresh();
			},
			error: function()
			{
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				//Overseawarehouse.list.refresh();
				return false;
			}
		});
	},
	
	//手动配对
	'matchingOne' : function (val, type) {
		var tr = $(val).parent().parent().parent();
		var stock_id = tr.attr("stock_id");
		var skuText = tr.find('input[name="sku[]"]')
		var sku = skuText.val();
		var btnMatching = tr.find('button[name="btnMatching[]"]');
		
		if(type == 1)
		{
			//本地sku可编辑
			skuText.removeAttr("disabled");
			//变更按钮信息
			btnMatching.html("配对");
			btnMatching.attr("onclick","OverseaWarehouse.list.matchingOne(this, 0)");
		}
		else
		{
			$.ajax({
				type : 'post',
		        cache : 'false',
		        dataType: 'json',
		        data: {'stock_id':stock_id, 'sku':sku, 'type':type},
				url:'/inventory/oversea-warehouse/matching-one', 
				success: function (result) 
				{
					if(result['status'] == 1 || result['status'] == 2)
					{
						//当本地SKU为空时，取消配对状态
						if(sku == '')
						{
							//配对状态
							tr.find('td[name="matchingStatus[]"]').html("<p style='color:#ed5466;'>未配对</p>");
							if(result['status'] != 2)
							{
								//汇总数变更
								$('#Ymatching').html($('#Ymatching').html() - 1);
								$('#Nmatching').html($('#Nmatching').html() * 1 + 1);
							}
						}
						else
						{
							//本地sku不可编辑
							skuText.attr("disabled","false");
							//变更按钮信息
							btnMatching.html("修改");
							btnMatching.attr("onclick","OverseaWarehouse.list.matchingOne(this, 1)");
							//配对状态
							tr.find('td[name="matchingStatus[]"]').html("<p style='color:#91c854;'>已配对</p>");
							if(result['status'] != 2)
							{
								//汇总数变更
								$('#Ymatching').html($('#Ymatching').html() * 1 + 1);
								$('#Nmatching').html($('#Nmatching').html() - 1);
							}
						}
					}
					else
					{
						bootbox.alert({
							buttons: {
								ok: {
									label: 'OK',
									className: 'btn-primary'
								}
							},
							message: Translator.t(result['msg']),
							callback: function() {}
						});
					}
				},
				error: function()
				{
					bootbox.alert("出现错误，请联系客服求助...");
					$.maskLayer(false);
					//Overseawarehouse.list.refresh();
					return false;
				}
			});
		}
	},
	
	//打开自动配对SKU界面
	'showAutomaticMatchingBox' : function () {
		$.modal
		(
			{
			  url:'/inventory/oversea-warehouse/show-automatic-matching-box',
			  method:'post',
			  data:{}
			},
			'自动配对本地SKU',
			{footer:false,inside:false}).done(function($modal)
			{
				$('#btn_matching').click(function()
				{
					$.maskLayer(true);
					$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
					
					var warehouse_id = $('input[name="warehouse_id"]').val();
					var accountid = $('input[name="accountid"]').val();
					var matchingType = $('input[name="matchingType[]"]:checked').val();
					var startStr = $('input[name="startStr"]').val();
					var endStr = $('input[name="endStr"]').val();
					var startLen = $('input[name="startLen"]').val();
					var endLen = $('input[name="endLen"]').val();
					
					$.ajax({
						type : 'post',
				        cache : 'false',
				        dataType: 'json',
				        data: {
				        		'warehouse_id':warehouse_id,
				        		'accountid':accountid,
				        		'matchingType':matchingType,
				        		'startStr':startStr,
				        		'endStr':endStr,
				        		'startLen':startLen,
				        		'endLen':endLen,
				        	},
						url:'/inventory/oversea-warehouse/automatic-matching', 
						success: function (result) 
						{
							var msg = '';
							if(result['status'] == 0)
								msg = result['msg'];
							else
								msg = '<p style="font-size:16px; line-height:20px;">匹配成功：本次完成 <span style="color:#91c854;">'+ result['Qty'] +'</span> 个海外仓SKU配对。<br>点击确认刷新窗口数据</p>';
							$.maskLayer(false);
							bootbox.alert({
								buttons: {
									ok: {
										label: '确认',
										className: 'btn-primary'
									}
								},
								message: Translator.t(msg),
								callback: function() {
									OverseaWarehouse.list.refresh();
								}
							});
						},
						error: function()
						{
							bootbox.alert("出现错误，请联系客服求助...");
							$.maskLayer(false);
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
		
};

