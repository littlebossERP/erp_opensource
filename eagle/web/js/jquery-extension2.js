(function($, window, undefined, document) {
	'use strict';

	$.data(window, 'modalFn', []); // 初始化模态框事件队列

	$.extend({
		alert: function(msg, type, options) {
			var $alertBox = $('<div class="lb-alert"><button type="button" class="close" ><span>×</span></button><div class="lb-alert-body">' + msg + '</div><div class="lb-alert-footer"></div></div>'),
				$cover = $("<div class='lb-alert-cover'></div>"),
				$footer = $alertBox.find(".lb-alert-footer"),
				dfd = $.Deferred(),
				o = { // 默认按钮
					success: ['确定'],
					primary: ['好的'],
					warn: ['确定', '取消'],
					danger: ['确定'],
					info: []
				},
				__close = function() { // 关闭事件
					$cover.remove();
					$alertBox.fadeOut(300, function() {
						$alertBox.remove();
					});
				};

			type = type || 'info'; // 设置默认type为info
			options = $.extend(o[type], options);
			$.each(options, function(key, text) { // 添加按钮组
				var $b = $("<button class='btn btn-sm btn-" + (!key ? 'primary' : 'default') + "'>" + text + "</button>");
				$b.appendTo($footer).on('click', function(e) { // 绑定按钮事件到 dfd 对象
					if (key) {
						dfd.reject(e);
					} else {
						dfd.resolve(e);
					}
					__close();
				});
			});
			$cover.appendTo("body");
			$alertBox
				.appendTo("body")
				.animate({
					opacity: 1
				}, 300)
				.find(".close")
				.on('click', function() {
					__close();
					dfd = undefined;
				});

			$alertBox.css({ // 定位居中及自适应宽高度
				width: $alertBox.outerWidth() + 50,
				height: $alertBox.outerHeight(),
				top: 0,
				left: 0,
				bottom: 0,
				right: 0
			});
			if (type == 'danger') {
				$alertBox.addClass('text-danger');
			}
			return dfd.promise();
		},
		modalOn: function(foo) {
			$.data(window, 'modalFn').push(foo);
			return this;
		},
		modalTrigger: function($modal) {
			$.each($.data(window, 'modalFn'), function(k, fn) {
				fn.apply($modal, []);
			});
			return this;
		}

	});

	$.fn.extend({
		_ready: function(event, promise) {
			return $(this).each(function() {
				var $this = $(this);
				$this.data('_ready', $this.data('_ready') || {});
				if (!(event in $this.data('_ready'))) {
					$this.data('_ready')[event] = [];
				}
				$this.data('_ready')[event].push(promise);
			});
		},
		_on: function(event, fn) {
			return $(this).each(function() {
				var $this = $(this);
				$this.data('events', $this.data('events') || {});
				if (!(event in $this.data('events'))) {
					$this.data('events')[event] = [];
				}
				$this.data('events')[event].push(fn);
				$this.off(event).on(event, function() {
					var e = arguments,
						queue = [];
					if ($this.data('_ready')) {
						queue = $this.data('_ready')[event].map(function(fn) {
							return fn.apply($this[0], e);
						});
					}
					$.when.apply($, queue).done(function() {
						$.each($this.data('events')[event], function(key, fn) {
							fn.apply($this[0], e);
						});
					});
				});
			});
		},
	});


	$.fn.textOverflow = function(line) {
		return $(this).each(function() {
			var $this = $(this),
				paddingBottom = parseInt($this.css('padding-bottom')),
				// _height = $this.height(),
				// height = parseInt($this.css('line-height')) * line + parseInt($this.css('padding-top')) + paddingBottom,
				_height = $this.height(),
				height = parseInt($this.css('line-height')) * line,
				$paddingBottom = $('<div></div>'),
				$ellipsis = $('<div class="ellipsis-symbol">...</div>'),
				_text = $this.html(),
				$text = $("<div></div>"),
				_trigger = $this.attr('text-overflow-trigger') || 'click';

			if (_height > height && !$this.data('bindTextOverflow')) {
				$this.css({
					position: 'relative'
				}).text('').data('bindTextOverflow', true);
				$text.css({
					height: parseInt($this.css('line-height')) * line,
					overflow: 'hidden',
					display: 'block',
					transition: 'all .2s ease 0s'
				}).appendTo($this).html(_text);
				$ellipsis.attr('title', '显示全部').css({
					cursor: 'pointer',
					width: 20,
					position: 'absolute',
					right: 0,
					bottom: paddingBottom - 4
				}).on(_trigger, function(e) {
					$text.height(_height);
					$(this).remove();
					return false;
				}).appendTo($(this));

			}
		});
	}

	/**
	 * tagView
	 * @param  {[type]} key [description]
	 * @param  {[type]} val [description]
	 */
	$.fn.pushTag = function(key, val) {
		var dfd = $.Deferred();
		if (key) {
			var $this = $(this),
				$btn = $this.find('button').eq(0).clone(),
				$$keys = $this.data('$$keys') || [];
			// 检查重复
			if ($$keys.indexOf(key) < 0) {
				$btn.data('key', key).find('span:eq(0)').text(val);
				$btn.find('input').attr('checked', 'checked').prop('checked', true).val(key);
				$$keys.push(key);
				$this.data('$$keys', $$keys);
				$btn.appendTo($this).show();
				dfd.resolve($$keys);
			} else {
				dfd.reject({
					code: 400,
					message: '已存在相同的选项'
				});
			}
		} else {
			dfd.reject({
				code: 404,
				message: '缺少对应的值'
			});
		}
		return dfd.promise();
	}

	$.fn._onElse = function(event, foo) {
		var $this = $(this);
		$(document).on(event, function(e) {
			var $eParents = $(e.target).parents();
			if (Array.prototype.indexOf.call($eParents, $this[0]) == -1) {
				foo.apply($this, [event, e]);
			}
		});
	}

	$.fn._offElse = function(event) {
		var $this = $(this);
		$(document).off(event);
	}

	$.fn.lbSelectRender = function(data) {
		return $(this).each(function() {
			var html = '';
			$.each(data, function(k, v) {
				html += "<li data-val='" + k + "'>" + v + "</li>";
			});
			$(this).find('ul').html(html),
				$(this).lbSelect();
		});
	}

	$.fn.lbSelect = function(callback) {
		return $(this).each(function() {
			var $this = $(this),
				$input = $this.find('input:hidden'),
				$option = $this.find('li'),
				$viewer = $this.find('.select-viewer'),
				__opened = false,
				__val,
				line = $this.attr('overflow'),
				status = function(val) {
					if (val === undefined) {
						return __opened;
					} else {
						if (val) {
							$this.addClass('_open');
							$option.textOverflow(line)
						} else {
							$this.removeClass('_open');
						}
						__opened = val;
						return this;
					}
				};

			$this.val = $input.val;
			$viewer.on('click.lb-select', function(e) {
				status(!status());
			});
			$option.on('click.lb-select', function() {
				var $self = $(this);
				if ($self.data('val') != $this.val()) {
					$self
						.attr({
							selected: true
						}).addClass('active')
						.siblings()
						.removeAttr('selected')
						.removeClass('active');
					$this.val($self.data('val'));
					$viewer.text($(this).text());
					$this.trigger('change');
				}
				// 单选的话直接隐藏选项
				status(false);
			});

			$this._onElse('click.lb-select', function() {
				status(false);
			});

			typeof callback === 'function' && callback.apply($this, []);
			return this;
		});
	}

	// 插入文本到当前光标所在
	$.fn.insertToText = function(schr, echr) {
		return $(this).each(function() {
			var $area = this;
			echr = echr || '';
			var start = $area.selectionStart;　　 //获取焦点前坐标 
			var end = $area.selectionEnd;　　 //获取焦点后坐标 
			　　　　 //以下这句，应该是在焦点之前，和焦点之后的位置，中间插入我们传入的值 .然后把这个得到的新值，赋给文本框 
			$area.value = $area.value.substring(0, start) + schr + echr + $area.value.substring(end, $area.value.length);
			$area.setSelectionRange(start + schr.length, start + schr.length);
		});
	};


	// ajax表单防止重复提交
	$.ajaxSetup({
		beforeSend: function() {
			$("form input:submit:not([disabled])").attr('ajax-d','1').prop('disabled', true);
		},
		complete: function() {
			$("form input[ajax-d=1]").removeAttr('ajax-d').prop('disabled', false);
		}
	});


	// 普通事件绑定
	var documentReady = function() {
			// 超出部分...
			$("[text-overflow]").each(function() {
				var $this = $(this),
					line = $this.attr('text-overflow');
				$this.textOverflow(line);
			});

			$("body")
				.on('mouseenter', '.tips,[tips]', function() {
					var $this = $(this),
						$tips = $("<div class='tips-tool arrow arrow-left'>" + $this.attr('tips') + "</div>");
					$tips
						.css({
							top: $this.offset().top + $this.height() / 2 - 5,
							left: $this.offset().left + $this.width() / 2 + 25
						})
						.appendTo($("body"));
					$this.one('mouseout', function() {
						$(".tips-tool").remove();
					});
				})
				.on('click', '.tags-view button', function(e) {
					$(this).remove();
					return false;
				})
				/**
				 * ajax提交表单，回调监听事件为：'submitDone'
				 * @param  {[type]} e){		var $form         [description]
				 * @return {[type]}           [description]
				 */
				//.on('submit', "form[ajax-form]", function(e) {
				//	var $form = $(this);
				//	$.ajax({
				//		url: $form.attr('action'),
				//		method: $form.attr('method'),
				//		data: $form.serialize()
				//	}).done(function(result, xhr) {
				//		$form.trigger('submitDone', result, xhr);
				//		if ($form.attr('ajax-form') == 'normal') {
				//			if (result.error) {
				//				$.alert('操作失败' + ('message' in result ? '<br /><font class="text-danger">' + result.message + '</font>' : ''));
				//			} else {
				//				$.alert('操作成功', function() {
				//					$form.closest('.modal').remove();
				//					if ($form.attr('ajax-reload')) {
				//						location.reload();
				//					}
				//				});
				//			}
				//		}
				//	});
				//	return false;
				//})
				// datetimepicker
				.on('mouseenter', ".js_datetimepicker", function() {
					var $this = $(this);
					$this.datepicker({
						dateFormat: $this.data('picker-format')
					});
				})
				.on('focus', '[dynamic-min],[dynamic-max]', function() {
					var $this = $(this),
						$form = $this.closest('form'),
						min = $this.attr('dynamic-min'),
						max = $this.attr('dynamic-max'),
						$min = $form.find('[name=' + min + ']'),
						$max = $form.find('[name=' + max + ']');

					if (min && $min.val()) {
						$this.attr('min', $min.val());
					}
					if (max && $max.val()) {
						$this.attr('max', $max.val());
					}
				})



			// 超出部分...
			$("[text-overflow]").each(function() {
				var $this = $(this),
					line = $this.attr('text-overflow'),
					_height = $this.height(),
					paddingBottom = parseInt($this.css('padding-bottom')),
					height = parseInt($this.css('line-height')) * line + parseInt($this.css('padding-top')) + paddingBottom,
					$paddingBottom = $('<div></div>'),
					$ellipsis = $('<div class="ellipsis-symbol">...</div>'),
					_text = $this.html(),
					$text = $("<div></div>"),
					_trigger = $this.attr('text-overflow-trigger') || 'click';

				if ($this.height() > height) {
					$this.css({
						position: 'relative'
					}).text('');
					$text.css({
						height: parseInt($this.css('line-height')) * line,
						overflow: 'hidden',
						display: 'block',
						transition: 'all .2s ease 0s'
					}).appendTo($this).html(_text);
					$ellipsis.attr('title', '显示全部').css({
						cursor: 'pointer',
						width: 20,
						position: 'absolute',
						right: 0,
						bottom: paddingBottom - 4
					}).on(_trigger, function() {
						$text.height(_height);
						$(this).remove();
					}).appendTo($(this));
				}
			});


			// 左侧菜单收起
			$("#sidebar").find(".resize").on('click', function() {
				$.cookie.setCookie('sidebar-status', 1);
				$("#sidebar").css('left', -215);
				$(".content-wrapper").css('margin-left', 40);
				$(".lb-left-menu-min").css('left', 0);
			});
			$(".lb-left-menu-min").find(".resize").on('click', function() {
				$.cookie.setCookie('sidebar-status', 0);
				$(".lb-left-menu-min").css('left', -60);
				$(".content-wrapper").css('margin-left', 200);
				$(".lb-left-menu").css('left', 0);
			});

			$(".glyphicon-checkbox")._on('click', function() {
				var $this = $(this),
					_open = $this.data('open') || false;
				if (!_open) {
					$this.text('-').addClass('glyphicon-checkbox-close').removeClass('glyphicon-checkbox-open');
				} else {
					$this.text('+').addClass('glyphicon-checkbox-open').removeClass('glyphicon-checkbox-close');
				}
				$this.data('open', !_open);
			});


		},
		// 普通+ajax模态框事件绑定
		documentModalReady = function() {
			var $document = $(this);
			// checkall
			$document.find("[data-check-all]")._on('change', function() {
				var status = $(this).is(':checked'),
					target = $(this).data('check-all');
				if(target == 'check_All'){
					$('.lb-countries :checkbox').prop('checked',status);
					return false;
				}
				console.log($("[data-check=" + target + "]"));
				$("[data-check=" + target + "]").prop('checked', status).trigger('change');
			});
			/* 模拟select */
			$document.find(".lb-select").lbSelect();

			/* modalButton */
			$document.find('[data-toggle=modal][data-href]').on('click', function() {
				var $this = $(this),
					$modal = $($this.data('target')),
					_href = $this.data('href');
				$.get(_href, $this.data('param')).success(function(html) {
					$modal.find('.modal-content').html(html);
					$modal.trigger('modalReady');
					$.modalTrigger($modal);
				});
			});

			$document.find(".btn-group.toggle-replace").each(function(){
				var $this = $(this);
				$this.find("li").on('click',function(){
					var val = $(this).text();
					$this.find('button').html(val+'<span class="caret"></span>');
					$this.trigger('change',[val]);
				});
			}).on('change',function(e,val){
				console.log(val)
			});

			//// 确认
			//$document.find('[click-confirm]')._ready('click', function(e) {
			//	var $this = $(this),
			//		dfd = $.Deferred();
			//	bootbox.alert($this.attr('click-confirm') + "<br />确定要继续吗？", 'warn').then(function(e) {
			//		dfd.resolve();
			//	}, function(e) {
			//		dfd.reject();
			//	})
			//	return dfd.promise();
			//});

			$document.find(".iosSwitch")._on('click', function(e) {
				var $self = $(this),
					$this = $(this).find("input"),
					opt = $.parseJSON(decodeURIComponent($this.data('val'))),
					val = $this.is(':checked') ? opt[1] : opt[0];
					if($self.attr("actionKey")==undefined){
						var actionKey="iosswitch";
					}else {
						var actionKey=$self.attr("actionKey");
					}


					$.post('/ajax/'+actionKey, {
						className: $this.data('class'),
						pk: $this.data('pk'),
						field: $this.attr('name'),
						val: val,
						trackerKey: $self.attr('tracker-key'),
						trackerRemark: $self.attr('tracker-remark'),
						active: $this.is(':checked')
					})
					.success(function(rs) {
						if (rs.error) {
							// $this.prop('checked', false);
						} else {
							$this.prop('checked', !$this.is(':checked'));
						}
						$self.val(val);
						$this.trigger('iosSwitch', [e, rs]);
					});
			});
			

		};


	$(documentReady);
	$(documentModalReady);
	$.modalOn(documentModalReady);
	$.modalOn(function() {


		/*  模态框拖拽  */
		$(this).find(".modal-dialog").draggable({
			opacity: 0.7,
			handler: ".modal-header"
		}).find(".modal-header").css('cursor', 'move');





	});



})(jQuery, window, undefined, document);

$.fn.datetimepicker = function(){
	return this;
};