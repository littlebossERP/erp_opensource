(function($) {
	'use strict';

	$.fn.LevelLinkDropdown = function(param) {

		// 视图
		var $div = $(this),
			$view = param.view;
		$div.addClass("level-" + param.level)
			.append("<input type='hidden' name='" + param.name + "' />");
		// 加载一个下拉框
		var loadDiv = function(val, level) {
				val = val || -1;
				var data = {};
				data[param.paramKey] = val;
				// 移出下拉
				for (var i = level; i < param.level; i++) {
					$div.find(".level-link-child-dropdown[data-level=" + (i + 1) + "]").remove();
				}
				return $.ajax({
					method: param.method,
					url: param.url,
					data: data,
					success: function(res) {
						if (res.length) {
							var _cDiv = "<div class='level-link-child-dropdown' data-level='" + (level + 1) + "'><ul>";
							$.each(res, function(idx, item) {
								_cDiv += "<li data-val='" + item.id + "'>" + item.name_zh_tw;
								// console.log(level, param.level)
								if (level + 1 < param.level) {
									_cDiv += "<span class='glyphicon glyphicon-chevron-right pull-right'></span>";
								}
								_cDiv += "</li>";
							});
							_cDiv += "</ul></div>";
							$(_cDiv).appendTo($div);
						}
					}
				});
			};

		// 绑定事件
		$div.on('click', 'li', function() {
			var $li = $(this),
				val = $li.data('val'),
				level = parseInt($li.closest('div').data('level')),
				text = [];
			// 改值
			$div.find(":hidden").val(val);
			// 激活样式
			$li.addClass('active').siblings().removeClass('active');
			// 加载
			loadDiv(val, level).done(function(){
				// 修改文本
				$(".level-link-child-dropdown").each(function(){
					var lvl = parseInt($(this).data('level')),
					t = $(this).find('li.active').text();
					if(t){
						text[lvl-1] = t;
					}
				});
				$view.text(text.join(' > '));
			});
		});
		loadDiv(-1, 0);

	};


})(jQuery);