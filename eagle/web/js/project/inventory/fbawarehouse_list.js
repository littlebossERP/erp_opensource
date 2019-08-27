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

if (typeof Fbawarehouse === 'undefined')  Fbawarehouse = new Object();
Fbawarehouse.list = {
	'init': function() 
	{
		//换页
		$('#matching-pager-group .pageSize-dropdown-div .dropdown-menu > li a').each(function()
		{
			$(this).attr('href', 'javascript:Fbawarehouse.list.changePage(1, '+ $(this).html() +', 0);');
		});
		$('#matching-pager-group .btn-group > .pagination > li a').each(function()
		{
			$(this).attr('href', 'javascript:Fbawarehouse.list.changePage(1, 0, '+ $(this).html() +');');
		});
		$('#matching-pager-group .btn-group .prev a').attr('href', 'javascript:Fbawarehouse.list.changePage(2);');
		$('#matching-pager-group .btn-group .next a').attr('href', 'javascript:Fbawarehouse.list.changePage(3);');
		
		$('#profit_search').click(function() {
			$('input[name="page"]').val('');
		});
	},
	
	'refresh' : function(){
		 document.getElementById("fbawarehouse_list_params_form").submit();
	},
	
	'selectSite' : function(merchant_id, marketplace_id){
		$('input[name="merchant_id"]').val(merchant_id);
		$('input[name="marketplace_id"]').val(marketplace_id);
		$('input[name="per_page"]').val('');
		$('#search_sku').val('');
		$('input[name="page"]').val('');
			
		Fbawarehouse.list.refresh();
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

		Fbawarehouse.list.refresh();
	},
	
	//同步fba SKU和库存
	'synchronizeSku' : function () 
	{
		$.alertBox('<p class="text-success">执行中,请不要关闭页面！</p>');
		$.showLoading();
		
		var merchant_id = $('input[name="merchant_id"]').val();
		var marketplace_id = $('input[name="marketplace_id"]').val();
		
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data: {'merchant_id':merchant_id, 'marketplace_id':marketplace_id},
			url:'/inventory/fba-warehouse/synchronize-fba-sku', 
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
						Fbawarehouse.list.refresh();
					}
				});
			},
			error: function()
			{
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			}
		});
	},
		
};

