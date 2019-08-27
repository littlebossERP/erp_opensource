$.extend({
	myTime: {
		/**
		 * 日期 转换为 Unix时间戳
		 * @param <int> year    年
		 * @param <int> month   月
		 * @param <int> day     日
		 * @param <int> hour    时
		 * @param <int> minute  分
		 * @param <int> second  秒
		 * @return <int>        unix时间戳(秒)
		 */
		DateToUnix: function(year, month, day, hour, minute, second) {
			var oDate = new Date(Date.UTC(parseInt(year),
				parseInt(month),
				parseInt(day),
				parseInt(hour),
				parseInt(minute),
				parseInt(second)
			));
			return (oDate.getTime() / 1000);
		},
		/**
		 * 时间戳转换日期
		 * @param <int> unixTime    待时间戳(秒)
		 * @param <bool> isFull    返回完整时间(Y-m-d 或者 Y-m-d H:i:s)
		 * @param <int>  timeZone   时区
		 */
		UnixToDate: function(unixTime, isFull, timeZone) {
			if (typeof(timeZone) == 'number') {
				unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
			}
			var time = new Date(unixTime * 1000);
			var ymdhis = '';
			ymdhis += time.getUTCFullYear() + '-';
			ymdhis += time.getUTCMonth() + '-';
			ymdhis += time.getUTCDate();
			if (isFull === true) {
				ymdhis += '' + time.getUTCHours() + ':';
				ymdhis += time.getUTCMinutes() + ':';
				ymdhis += time.getUTCSeconds();
			}
			return ymdhis;
		}
	},
	cookie: {
		setCookie: function setCookie(c_name, value, expiredays) {
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + expiredays);
			document.cookie = c_name + '=' + escape(value) + ((expiredays == null) ? '' : ';expires=' + exdate.toGMTString())+';path=/';
		},
		getCookie: function(c_name) {
			if (document.cookie.length > 0) {
				c_start = document.cookie.indexOf(c_name + '=');
				if (c_start != -1) {
					c_start = c_start + c_name.length + 1
					c_end = document.cookie.indexOf(';', c_start)
					if (c_end == -1) {
						c_end = document.cookie.length;
					}
					return unescape(document.cookie.substring(c_start, c_end));
				}
			}
			return '';
		}
	},
	initQtip: function() {
		$.fn.qtip.zindex = 1041; // bootbox 弹窗设置了1040的z-index ，但是qtip的这个 z-index又不能挡住 bootbox alert的弹窗
		$('*[qtipkey]').each(function() {
			if (!$(this).hasClass('hasTip')) {
				if (!$(this).hasClass('no-qtip-icon')) {
					iconSrc = global.baseUrl + 'images/questionMark.png';
					var addHtml = "<img style='cursor: pointer;' width=16 src='" + iconSrc + "'  />";
					$(this).append(addHtml);
				}
				$(this).addClass('hasTip');
				//				var showPostionX1 = $(this).children('img').offset().left; // left 
				//				var showPostionX2 = $(this).children('img').offset().left + $(this).children('img').width(); // right
				//				var showPostionY1 = $(this).children('img').offset().top; // top 
				//				var showPostionY2 = $(this).children('img').offset().top + $(this).children('img').height(); // bottom
				var maxWidth = 440; // MyTootipClass set max-width: 440px;
				// 获取窗口宽度
				if (window.innerWidth)
					winWidth = window.innerWidth;
				else if ((document.body) && (document.body.clientWidth))
					winWidth = document.body.clientWidth;
				// 获取窗口高度
				if (window.innerHeight)
					winHeight = window.innerHeight;
				else if ((document.body) && (document.body.clientHeight))
					winHeight = document.body.clientHeight;

				position_at = 'top right';
				position_my = 'bottom left';
				// tooltip初始化时，根据触发位置与浏览器窗口对比,设置tooltip postion at,my 位置
				//				if((winWidth - showPostionX2) < maxWidth &&  showPostionY1 > winHeight * 0.3){
				//					position_at = 'top left';
				//					position_my = 'bottom right';
				//				}else if((winWidth - showPostionX2) < maxWidth && showPostionY1 < winHeight * 0.3 ){
				//					position_at = 'bottom left';
				//					position_my = 'top right';
				//				}else if((winWidth - showPostionX2) > maxWidth && showPostionY1 < winHeight * 0.3 ){
				//					position_at = 'bottom right';
				//					position_my = 'top left';
				//				}
				var initTipItem = null;
				if (!$(this).hasClass('no-qtip-icon')) {
					initTipItem = $(this).children('img');
				} else {
					if (initTipItem = $(this).children().length <= 0) {
						$(this).html("<span>" + $(this).text() + "</span>");
					}
					initTipItem = $(this).children().eq(0);
				}
				$(initTipItem).qtip({
					content: {
						text: function(event, api) {
							var tipkey = $(this).parent().attr("qtipkey");
							$(api.tooltip).data('get_content', false);
							$.get(global.baseUrl + 'util/qtip/gettip?tipkey=' + tipkey, {},
								function(data) {
									api.set('content.text', data);
									api.set('style.width', 440);
									$(api.tooltip).data('get_content', true);
								}
							);
							return 'Loading...'; // Set some initial text
						}
					},
					position: {
						at: position_at,
						my: position_my,
						viewport: $(window),
						adjust: {
							//							x: 0, y: 0,
							method: 'shift flip'
						},
					},
					show: {
						delay: 500,
						effect: function() {
							// dzt20160705 由于打开弹窗导致 body overflow-y: hidden;获取不到滚动条的 scrollTop值,计算位置结果不对
							// 所以这里强制body 为visible 显示qtip才正常
							// TODO 这种方法比较流氓，暂时没有找到不hidden而禁止滚动条的方法
							// TODO 或者可以研究下更改position.viewport ,position.container的设置能否解决这个问题
							$('body').css('overflow-y','visible');

							$(this).fadeIn(500, function() {
								// show tooltip 时,根据tooltip 宽高与浏览器窗口对比,调整postion at ,my位置  
								var tooltip = this;
								//			                	setTimeout(function(){
								//			                		var target = $(tooltip).qtip('api').target;
								//				    				var showPostionX1 = $(target).offset().left; // left 
								//				    				var showPostionX2 = $(target).offset().left + $(target).width(); // right
								//				    				var showPostionY1 = $(target).offset().top; // top 
								//				    				var showPostionY2 = $(target).offset().top + $(target).height(); // bottom
								//
								//				    				if((winWidth - showPostionX2) < $(tooltip).width() &&  showPostionY1 > ($(tooltip).height() + 8)){
								//				    					$(tooltip).qtip('api').set('position.at', 'top left');
								//				    					$(tooltip).qtip('api').set('position.my', 'bottom right');
								//				    				}else if((winWidth - showPostionX2) < $(tooltip).width() && showPostionY1 < ($(tooltip).height() + 8)){
								//				    					$(tooltip).qtip('api').set('position.at', 'bottom left');
								//				    					$(tooltip).qtip('api').set('position.my', 'top right');
								//				    				}else if((winWidth - showPostionX2) > $(tooltip).width() && showPostionY1 < ($(tooltip).height() + 8)){
								//				    					$(tooltip).qtip('api').set('position.at', 'bottom right');
								//				    					$(tooltip).qtip('api').set('position.my', 'top left');
								//				    				}
								//				    				$(tooltip).qtip('api').reposition();
								//				                	setTimeout(function(){
								if ($(tooltip).data('get_content')) {
									$(tooltip).css('width', 'auto');
									$(tooltip).qtip('api').reposition();
								}
								//				                	},200);
								//				                	
								//			                	},200);
								//			    			
							});
						},
					},
					hide: {
						effect: function() {
							$(this).fadeOut(500);
						}
					},
					style: {
						classes: 'qtip-dark qtip-rounded qtip-shadow MyTootipClass qtip-bootstrap',
					},
				});
			}
		});
	},

	showLoading: function() {
		var ajaxbg = $("#background,#progressBar");
		ajaxbg.show();
	},

	hideLoading: function() {
		var ajaxbg = $("#background,#progressBar");
		ajaxbg.hide();
	},
	showLongLoading: function() {
		var ajaxbg = $("#background,#longprogressBar");
		ajaxbg.show();
	},

	hideLongLoading: function() {
		var ajaxbg = $("#background,#longprogressBar");
		ajaxbg.hide();
	},
});



/* Usage sample: alert( Number(3000.5).toMoney('USD')  );
 *            =>  $ 3,000.500
 *
 currency: Currency of the amount, defautl is "CNY"
 decimals: number of decimal digits , default is "2". e.g. 100.00
 decimal_sep: character used as deciaml separtor, it defaults to '.' when omitted
 thousands_sep: char used as thousands separator, it defaults to ',' when omitted
 */
Number.prototype.toMoney = function(currency, decimals, decimal_sep, thousands_sep) {
	var n = this;
	var money_head = '';
	var money_tail = '';
	c = isNaN(decimals) ? 2 : Math.abs(decimals); //if decimal is zero we must take it, it means user does not want to show any decimal
	d = decimal_sep || '.'; //if no decimal separator is passed we use the dot as default decimal separator (we MUST use a decimal separator)
	currency = currency || 'CNY';

	switch (currency) {
		case 'CNY':
			money_head = '￥ ';
			break;
		case 'USD':
			money_head = '$ ';
			break;
		case 'EUR':
			money_tail = ' €';
			break;
		case 'GBP':
			money_head = '£ ';
			break;
		default: //e.g. HKD, etc
			money_head = '$ ';
			break;
	}

	/*
	   according to [http://stackoverflow.com/questions/411352/how-best-to-determine-if-an-argument-is-not-sent-to-the-javascript-function]
	   the fastest way to check for not defined parameter is to use typeof value === 'undefined'
	   rather than doing value === undefined.
	*/
	t = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep; //if you don't want to use a thousands separator you can pass empty string as thousands_sep value

	sign = (n < 0) ? '-' : '';
	//extracting the absolute value of the integer part of the number and converting to string
	i = parseInt(n = Math.abs(n).toFixed(c)) + '';
	j = ((j = i.length) > 3) ? j % 3 : 0;

	return money_head + sign + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '') + money_tail;
};



// 表单默认验证  
// 目前支持非空，字符长检测，trim字符处理 , 正则
// form_validate 整个表单form 的验证目前只包括regist 过的 type=text and not(:disabled)的input 或textarea
jQuery.fn.extend({
	formValidation: function(options) {
		var defaults = jQuery.fn.formValidation.defaults;

		if (typeof(options) == 'string') {

			if ('form_validate' != options)
				throw ('undefined "' + options + '" is not a function.');
			if (!$(this).is('form'))
				throw ('target is not a form.');

			options = {};
		}

		if ($(this).is('form') === true) {
			options.form = this;
			options.elements = $(this).find(':text:not(:disabled) , textarea:not(:disabled)');
			options.runAllValide = true;
			options = $.extend({}, options);
			$(this).data('validate', true);
		} else {
			options.elements = this;
			options.runAllValide = false;
			options = $.extend({}, defaults, options);

			// 记下可执行验证规则名称
			options.valideRules = [];
			for (var i in options.rules) {
				options.valideRules.push(i);
			}
		}



		var showTip = function(target, content, options) {
			$(target).parent().removeClass('has-success');
			$(target).parent().addClass('has-error'); // 配合boostrap 提醒样式

			var atTipPosition, myTipPosition;
			if (!options.tipPosition)
				atTipPosition = 'right';
			else
				atTipPosition = options.tipPosition;
			if ('right' == atTipPosition) {
				myTipPosition = 'left';
			} else {
				myTipPosition = 'right';
			}

			$(target).qtip({
				content: {
					text: content
				},
				position: {
					my: myTipPosition + ' center',
					at: atTipPosition + ' center',
					container: $(target).parents('.modal'), // 关闭modal时可以把qtip也remove掉
				},
				hide: {
					event: 'unfocus'
				},
				style: {
					classes: 'qtip-bootstrap bg-danger text-danger',
				},
			});
			$(target).qtip("show");
		}

		// 隐藏其他qtip,删除当前target的qtip
		var destroyTip = function(options) {
			$(options.elements).qtip("hide");
			$(options.targetElement).qtip("destroy");
		}

		var excuteAllValidate = function(options) {
			destroyTip(options);

			// 验证是否required
			if (true == options.required) {
				if ('' == $(this).val()) {
					showTip(this, options.missingMessage, options);
					return false;
				}
			}


			if (options.validType) {
				if (typeof options.validType == "string") {
					if (!execValid(options.validType)) {
						return false;
					}
				} else {
					for (var i = 0; i < options.validType.length; i++) {
						// focus or blur trim input value
						if ('trim' == options.validType[i]) {
							if ('keyup' != eventType)
								$(this).val($.trim($(this).val()));
							continue
						}

						if (!execValid.call(this, options.validType[i])) {
							return false;
						}
					}
				}

				$(this).parent().addClass('has-success');
				return true;
			}

			function execValid(userValidType) {
				var execData = /([a-zA-Z_]+)(.*)/.exec(userValidType); // 拆分调用时的validate type 的name和params, 然后执行对应这个name的rules 里面的validate function
				var validRule = options.rules[execData[1]];
				if (validRule) {
					var params = eval(execData[2]);
					if (!validRule["validator"]($(this).val(), params)) { // 验证失败

						var message = validRule["message"];
						if (params) { // 如果输入了params 参数 , 将会以这个里面的参数填充 返回message 模板的占位符。详细请参考 length 规则的用法
							for (var i = 0; i < params.length; i++) {
								message = message.replace(new RegExp("\\{" + i + "\\}", "g"), params[i]);
							}
						}

						showTip(this, options.invalidMessage || message, options);
						return false;
					}
				} else {
					throw ('ValidType ' + userValidType + ' is not defined.');
				}
				return true;
			}

		}

		var eventType = ""
		$(options.elements).each(function() {
			if (options.runAllValide) {
				// 获取每个validate target 要进行的验证规则validType
				if ($.data(this, 'options')) {
					var targetOptions = $.extend({}, $.data(this, 'options'), options);
					targetOptions.targetElement = this;
					eventType = 'all';

					if (excuteAllValidate.call(targetOptions.targetElement, targetOptions)) {
						$(targetOptions.targetElement).parent().removeClass('has-error');
					} else {
						$(targetOptions.form).data('validate', false);
					}
				}
			} else {
				// 记录每个validate target 要进行的验证规则validType，等form validate时获取
				if (!$.data(this, 'options')) {
					$.data(this, 'options', options);
				} else {
					$.data(this, 'options', $.extend({}, $.data(this, 'options'), options));
				}

				$(this).on('focus keyup', function() {
					options.targetElement = this;
					eventType = event.type;
					if (excuteAllValidate.call(options.targetElement, options)) {
						$(options.targetElement).parent().removeClass('has-error');
					}
				});
			}
		});

		if (options.runAllValide) {
			if ($(options.form).data('validate') === false)
				return false;
			else
				return true;
		}
	}
});

jQuery.fn.extend(jQuery.fn.formValidation, {
	defaults: {
		required: false,
		validType: [],
		missingMessage: "该项为必填项",
		invalidMessage: "",
		tipPosition: "right",

		rules: {
			// 字符串长度验证
			length: {
				validator: function(val, limit) {
					var len = $.trim(val).length;
					return len >= limit[0] && len <= limit[1];
				},
				message: "输入内容长度必须介于{0}和{1}之间",
			},

			//保留两位小数的正数 正则表达式的检验
			amount: {
				validator: function(val) {
					if (val == "")
						return true;
					else
						return /^(0|([1-9][0-9]*))(\.[0-9]{1,2})?$/.test(val);
				},
				message: "请输入小数位不多于2位的非负数",
			},

			//正整数 正则表达式的检验
			integer: {
				validator: function(val) {
					if (val == "")
						return true;
					else
						return /^(0|([1-9][0-9]*))$/.test(val);
				},
				message: "请输入非负整数",
			},

			//wish size 正则表达式的检验
			wish_size: {
				validator: function(val) {
					if (val == "")
						return true;
					else
						return /^([a-zA-Z]|[0-9]|\.|\'|\"|\:)*$/.test(val);
				},
				message: "size必须为英文或数字，可以特殊符号 ." + "'" + ':"',
			},

			//wish description 正则表达式的检验
			wish_description: {
				validator: function(val) {
					if (val == "")
						return true;
					else
						return /\<|\>/.test(val);
				},
				message: "描述中不能含有“<”“>”符号。",
			},

			wish_shippingtime: {
				validator: function(val) {
					if (val == "")
						return true;
					else {
						return /^\d+\-\d+$/.test(val);
					}

				},
				message: "描述中不能含有“<”“>”符号。",
			},

			wish_color: {
				validator: function(val) {
					if (val == "")
						return true;
					else {
						return /^([a-zA-Z]+\s*\&?\s*)+$/.test(val);
					}

				},
				message: "颜色必须为英文，多个颜色之间用“&”隔开。",

			},
			prod_field: {
				validator: function(val) {
					if (val == "")
						return true;
					else {
						if (/\<|\>|\:|\;/.test(val)) return false;
						else return true;
					}

				},
				message: "属性名称、属性值不能含有':',';','<','>'等符号。",
			},
			safeForHtml: {
				validator: function(val) {
					if (val == "")
						return true;
					else {
						if (/\<|\>|\'|\"/.test(val)) return false;
						else return true;
					}

				},
				message: "不能含有" + '",' + '\',' + '<,' + '>' + "等符号。",

			},
			url : {
				validator: function(val) {
					if (val == "")
						return true;
					else {
						if (/^(http|https|ftp)\:\/\/\S+$/i.test(val)) return true;
						else return false;
					}

				},
				message: "网址必须以'http://' 或 'https://' 开头",
			}
		},
	}
});

// ajax 分页获取页面 初始化
jQuery.fn.extend({
	initGetPageEvent: function(options) { //初始化 触发ajax 获取分页页面的事件
		_curTable = this; //.modal-body?
		$(_curTable).data('pagerId', options.pagerId);
		typeof options.pagerId2 != 'undefined'?$(_curTable).data('pagerId2', options.pagerId2):"";// dzt20160829 出现两个pager 另一个无法更新问题，估计不会有3个了吧，所以暂不考虑支持多个
		$(_curTable).data('action', options.action);
		$(_curTable).data('page', parseInt(options.page) + 1);
		$(_curTable).data('per-page', options['per-page']);
		$(_curTable).data('sort', options.sort);

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
		
		if(typeof options.pagerId2 != 'undefined'){
			$(document).on('click', '#' + options.pagerId2 + ' .pagination li:not(.disabled,.active) a', function() {
				var page = parseInt($(this).attr('data-page')) + 1;
				$(_curTable).queryAjaxPage({
					'page': page
				});
				return false; // 阻止<a>标签默认跳转
			});
			
			$(document).on('click', '#' + options.pagerId2 + ' .pageSize-dropdown-div .dropdown-menu li:not(.disabled,.active) a', function() {
				var pageSize = $(this).attr('data-per-page');
				$(_curTable).queryAjaxPage({
					'per-page': pageSize
				});
				return false; // 阻止<a>标签默认跳转
			});
		}
		

		// 点击排序，获取页面刷新html
		$(document).on('click', '#' + $(_curTable).attr('id') + ' tr:first a', function() {
			var sort = $(this).attr('data-sort');
			$(_curTable).queryAjaxPage({
				'sort': sort
			});
			return false; // 阻止<a>标签默认跳转
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
				
				// dzt20160829 出现两个pager 另一个无法更新问题，估计不会有3个了吧，所以暂不考虑支持多个
				if(typeof $(_curTable).data('pagerId2') != 'undefined'){
					var pager2Html = $(data).find('#' + $(_curTable).data('pagerId2')).html();
					$('#' + $(_curTable).data('pagerId2')).html(pager2Html);
				}
			},
		});
	}

});


$.widget("ui.combobox", {
	/*期望传入一个参数，可以控制后面的_removeIfInvalid生效与否
	 *如果removeIfInvalid为true,输入结果不在可选项中则移除输入的内容，并浮出提示
	 *提示依赖bootstrap popover插件
	 */
	options: {
		removeIfInvalid: null,
		allNull: false,
	},
	//
	_create: function() {
		this.wrapper = $("<span>")
			.addClass("ui-combobox")
			.insertAfter(this.element);

		this.element.hide();
		this._createAutocomplete();
		this._createShowAllButton();
	},

	_createAutocomplete: function() {
		var selected = this.element.children(":selected"),
			value = selected.val() ? selected.text() : "";

		this.input = $("<input>")
			.appendTo(this.wrapper)
			.val(value)
			.addClass("ui-combobox-input")
			.autocomplete({
				delay: 0,
				minLength: 0,
				source: $.proxy(this, "_source"),
			});

		this._on(this.input, {
			autocompleteselect: function(event, ui) {
				ui.item.option.selected = true;
				this._trigger("select", event, {
					item: ui.item.option
				});
			},

			autocompletechange: "_removeIfInvalid",
		});
	},

	_createShowAllButton: function() {
		var input = this.input,
			wasOpen = false;

		$("<a></a>")
			.attr("tabIndex", -1)
			.attr("title", Translator.t("显示所有选项")) //hover tip
			.appendTo(this.wrapper)
			.html("<span class='ui-button-icon-primary ui-icon ui-icon-triangle-1-s'></span>")
			.removeClass("ui-corner-all")
			.addClass("ui-combobox-toggle")
			.mousedown(function() {
				wasOpen = input.autocomplete("widget").is(":visible");
			})
			.click(function() {
				input.focus();

				// 如果已经可见则关闭
				if (wasOpen) {
					return;
				}

				// 传递空字符串作为搜索的值，显示所有的结果
				input.autocomplete("search", "");
			});
	},

	_source: function(request, response) {
		var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
		var options = this.options;
		response(this.element.children("option").map(function() {
			var text = $(this).text();
			// dzt20160830 通过配置插件options allNull为true 来支持combox 展示select option value为空的项
			if((typeof options.allNull != "undefined" && options.allNull && typeof this.value != "undefined" || this.value) 
					&& (!request.term || matcher.test(text)))
				return {
					label: text,
					value: text,
					option: this
				};
		}));
	},

	_removeIfInvalid: function(event, ui) {
		var value = this.input.val();

		// 选择一项，移除弹出提示，不执行其他动作
		if (ui.item) {
			this.input
				.attr("data-content", "").popover('destroy');
			return;
		}

		// 搜索一个匹配（不区分大小写）
		valueLowerCase = value.toLowerCase(),
			valid = false;
		this.element.children("option").each(function() {
			if ($(this).text().toLowerCase() === valueLowerCase) {
				this.selected = valid = true;
				return false;
			}
		});

		// 找到一个匹配，移除弹出提示，不执行其他动作
		if (valid) {
			this.input
				.attr("data-content", "").popover('destroy');

			return;
		}

		//不许验证时，不执行其他动作
		if (this.options.removeIfInvalid !== true) {
			this.element.children("select").attr("value", value);
			//为了让select的值有效，需要新建一个option然后选中
			$("<option></option>")
				.attr("value", value)
				.attr("type", "hidden")
				.attr("selected", "selected")
				.appendTo(this.element)
				.html(value);
			//debugger;
			return;
		}

		// 移除无效的值
		this.input
			.val("")
			.attr("data-toggle", "tooltip")
			.attr("data-content", "'" + value + "'" + Translator.t(" 不在选项列表中"))
			.attr("data-placement", "bottom")
			//.attr( "title", "'"+value+"'" + Translator.t(" didn't match any item"))
			.popover({
				trigger: 'click'
			}).popover('show');
		this.element.val("");
		this.input.data("ui-autocomplete").term = "";
	},

	_destroy: function() {
		this.wrapper.remove();
		this.element.show();
	}
});


$(function() {
	'use strict';


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
				$$keys = $this.data('$$keys') || ['*'];
			if (!val) {
				if ($$keys.length < 2) {
					dfd.reject($$keys);
				} else {
					var idx = $$keys.indexOf(key);
					$$keys.splice(idx, 1);
					$this.data('$$keys', $$keys);
					dfd.resolve($$keys);
				}
			} else {
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
			}
		} else {
			dfd.reject({
				code: 404,
				message: '缺少对应的值'
			});
		}
		return dfd.promise();
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

	$.fn._on = $.fn._on || $.fn.on;

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
			}).appendTo($(this))
		}
	});

	$.fn.hasH5 = function(ele, attr, val) {
		if (val) {
			var dom = document.createElement(ele);
			dom.setAttribute(attr, val);
			return dom[attr] == val;
		}
	};

	$("body")
		.on('click','form [type=reset]',function(){
			var $form = $(this).closest('form');
			$form.find("input,textarea,select").val('');
			$form.submit();
		})
		.on('click', '.tags-view button', function(e) {
			var $this = $(this),
				key = $this.find("input").val();
			$this.closest(".tags-view")
				.pushTag(key)
				.then(function() {
					$this.remove();
				}, function() {
					bootbox.alert('必须选择一个国家');
				})
			return false;
		})
		/**
		 * ajax提交表单，回调监听事件为：'submitDone'
		 * @param  {[type]} e){		var $form         [description]
		 * @return {[type]}           [description]
		 */
		.on('submit', "form[ajax-form]", function(e) {
			var $form = $(this);
			$.ajax({
				url: $form.attr('action'),
				method: $form.attr('method'),
				data: $form.serialize()
			}).done(function(result, xhr) {
				$form.trigger('submitDone', result, xhr);
				if ($form.attr('ajax-form') == 'normal') {
					if (result.error) {
						bootbox.alert('操作失败' + ('message' in result ? '<br /><font class="text-danger">' + result.message + '</font>' : ''));
					} else {
						bootbox.alert('操作成功', function() {
							$form.closest('.modal').remove();
							if ($form.attr('ajax-reload')) {
								location.reload();
							}
						});
					}
				}
			});
			return false;
		})
		// datetimepicker
		//.on('mouseenter', ".js_datetimepicker", function() {
		//	var $this = $(this);
		//	$this.datepicker({
		//		format: $this.data('picker-format')
		//	});
		//})
		.on('mouseenter', "[type=date]", function() {
			var $input = $(this);
			if (!$input.hasH5('input', 'type', 'date')) {
				$input.datepicker({
					format: 'yyyy-mm-dd'
				});
			}
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
		/* modalButton */
		.on('click','[data-toggle=modal][data-href]',function(){
			var $this = $(this),
				$modal = $($this.data('target')),
				_href = $this.data('href');
			$.get(_href,$this.data('param')).success(function(html){
				$modal.find('.modal-content').html(html);
				$modal.trigger('modalReady');
			});
		});

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
			}).appendTo($(this))
		}
	});



});