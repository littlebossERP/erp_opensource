
// ajax 分页获取页面 初始化
jQuery.fn.extend({
	initGetPageEvents: function(options) { //初始化 触发ajax 获取分页页面的事件
		_curTable = this; //.modal-body?
		$(_curTable).data('pagerId', options.pagerId)
		$(_curTable).data('action', options.action);
		$(_curTable).data('page', parseInt(options.page) + 1);
		$(_curTable).data('per-page', options['per-page']);
		$(_curTable).data('sort', options.sort);

		$(_curTable).data('site_id',options.site_id);
		$(_curTable).data('select_status',options.select_status);
		$(_curTable).data('search_key',options.search_key);
		// 点击页面翻页 获取页面
		$(document).on('click', '#' + options.pagerId + ' .pagination li:not(.disabled,.active) a', function() {
			var page = parseInt($(this).attr('data-page')) + 1;
			$(_curTable).queryAjaxPage({
				'page': page
			});
			return false; // 阻止<a>标签默认跳转
		});

		// 点击分页个数，获取页面刷新html
		$(document).on('click', '#' + options.pagerId + ' .pageSize-dropdown-div .dropdown-menu li:not(.disabled,.active) a', function() {
			var pageSize = $(this).attr('data-per-page');
			$(_curTable).queryAjaxPage({
				'per-page': pageSize
			});
			return false; // 阻止<a>标签默认跳转
		});

		// 点击排序，获取页面刷新html
		$(document).on('click', '#' + $(_curTable).attr('id') + ' tr:first a', function() {
			var sort = $(this).attr('data-sort');
			$(_curTable).queryAjaxPage({
				'sort': sort
			});
			return false; // 阻止<a>标签默认跳转
		});
		$(document).on('click','#cite_goods_list_table tr',function(){
			$('#cite_goods_list_table tr input[type="radio"][name="cite_goods_id"]').removeAttr('checked');
			$(this).find('input[type="radio"][name="cite_goods_id"]').prop('checked','true');
			console.log($(this).find('input[type="radio"][name="cite_goods_id"]').val());
		});
	},

	// 请求页面
	queryAjaxPage: function(options) {
		_curTable = this;

		var queryParams = {}; // 过滤条件参数
		if ($(_curTable).data('queryParams'))
			var queryParams = $(_curTable).data('queryParams');

		var isMarkQueryParams = true;
		if (!options || options['page'] || options['per-page'] || options['sort']) {
			isMarkQueryParams = false;
		}

		// 记录其他查询条件。（换页，排序，选择分页个数的参数不能作为过滤参数）
		if (isMarkQueryParams)
			$(_curTable).data('queryParams', options);

		// 本来整个页面载入会重新初始化执行initGetPageEvent，更新page，per-page,sort参数
		// 但现在是通过获取部分html而不重新执行initGetPageEvent,这样就要在触发请求之更新当前page，per-page,sort
		if (options) {
			if (options['page'])
				$(_curTable).data('page', parseInt(options['page']));
			if (options['per-page'])
				$(_curTable).data('per-page', options['per-page']);
			if (options['sort'])
				$(_curTable).data('sort', options['sort']);
		}

		var action = $(_curTable).data('action');
		var defaults = {};
		if ($(_curTable).data('page'))
			defaults['page'] = $(_curTable).data('page');
		if ($(_curTable).data('per-page'))
			defaults['per-page'] = $(_curTable).data('per-page');
		if ($(_curTable).data('sort'))
			defaults['sort'] = $(_curTable).data('sort');
		if ($(_curTable).data('site_id'))
			defaults['site_id'] = $(_curTable).data('site_id');
		if ($(_curTable).data('select_status'))
			defaults['select_status'] = $(_curTable).data('select_status');
		if ($(_curTable).data('search_key'))
			defaults['search_key'] = $(_curTable).data('search_key');


		var options = $.extend({}, defaults, queryParams, options);
		$.showLoading();
		$.ajax({
			type: 'get',
			url: global.baseUrl + action,
			data: options,
			cache: false,
			success: function(data) {
				$.hideLoading();
				var tableHtml = $(data).find('#' + $(_curTable).attr('id')).html();
				var pagerHtml = $(data).find('#' + $(_curTable).data('pagerId')).html();
				$(_curTable).html(tableHtml);
				$('#' + $(_curTable).data('pagerId')).html(pagerHtml);

			},
		});
	}

});