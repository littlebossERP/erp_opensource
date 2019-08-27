$.parseUrl = function(str){
    var a = document.createElement('a');
    a.href = str;
    return a;
}

// window.onerror = function(sMsg,sUrl,sLine){
// 	// 查看Host是否一致
// 	if($.parseUrl(sUrl).host === location.host){

// 		// 发送错误信息
// 		$.post('/util/error/js',{
// 			sUrl:window.location.href,
// 			sMsg:sMsg,
// 			sFile:sUrl,
// 			sLine:sLine
// 		},function(rs){
// 			console.log(rs)
// 		});
// 	}
// };

// 连杠格式转驼峰式
String.prototype.toCamelCase = function() {
	if ('replace' in this) {
		return this.replace(/\-(\w)/g, function(x) {
			return x.slice(1).toUpperCase();
		});
	} else {
		return this;
	}
};
// html转义字符
String.prototype.html_encode = function() {
	if(this instanceof String){
		var s = "";
		if (this.length == 0) return "";
		s = this.replace(/&/g, "&amp;");
		s = s.replace(/</g, "&lt;");
		s = s.replace(/>/g, "&gt;");
		s = s.replace(/ /g, "&nbsp;");
		s = s.replace(/\'/g, "&#39;");
		s = s.replace(/\"/g, "&quot;");
		s = s.replace(/\n/g, "<br>");
		return s;
	}
	return this;
};
String.prototype.html_decode = function() {
	if(this instanceof String){
		var s = "";
		if (this.length == 0) return "";
		s = this.replace(/&amp;/g, "&");
		s = s.replace(/&lt;/g, "<");
		s = s.replace(/&gt;/g, ">");
		s = s.replace(/&nbsp;/g, " ");
		s = s.replace(/&#39;/g, "\'");
		s = s.replace(/&quot;/g, "\"");
		s = s.replace(/<br>/g, "\n");
		return s;
	}
	return this;
};

Array.prototype.remove = function(val) {
	if (this instanceof Array) {
		var index = this.indexOf(val);
		index > -1 && this.splice(index, 1);
	}
	return this;
};

// 遍历array方法
if(!Array.prototype.forEach){
	Array.prototype.forEach = function(fn){
		if (typeof fn != "function")
			throw new TypeError('Array.forEach argument[0] must be a function');
		for (var i = 0; i < this.length; i++)
			if (i in this)
				fn.call(arguments[1], this[i], i, this);
	};
}


(function($) {
	// 'use strict';

	// 插件
	var _pluginDeps = {
		'Highcharts': ['/js/lib/highcharts/highcharts.js'],
		'KindEditor': [
			'/js/lib/kindeditor/kindeditor.js',
			'/js/project/kindeditor.ext.js',
			// '/js/lib/kindeditor/kindeditorEdit.js',
			// '/js/lib/kindeditor/kindeditorEdit.css'
		],
		'LevelLinkDropdown': ['/js/project/level_link_dropdown.js'],
		'Progress':['/js/project/util/progress.js'],
		'ImageLib':['/js/project/util/image_lib.js'],
		'SelectImageLib':['/js/project/util/image_lib/core.js'],
		'AjaxPage':['/js/project/util/ajax_page.js'],
		'Clipboard':['/js/lib/clipboard.min.js'],
		'IvSelect':['/js/project/util/iv_select.js'],
		'LBSyncProducts':['/js/project/util/sync_products.js'],
		'IvModel':['/js/lib/iv_model.js'],
	};

	/****** 定义事件区   *****/
	$.modalCount = 0;
	var _cache = {
		dfd: {},
		variable: {},
		loadingCount:0
	};
	$.$$cache = _cache;

	// 建立一个空壳对象继承jq对象
	$.$createObj = {};
	$.fn.instance = function(Fn){
		if(!(Fn in $.$createObj)){
			$.$createObj[Fn] = function(){};
		}
		return $.extend(new $.$createObj[Fn],this);
	};
	$.fn.instanceof = function(Fn){
		return (Fn in $.$createObj) && (this instanceof $.$createObj[Fn]);
	};

	// promise包裹
	$.promise = function(fn,that) {
		var dfd = $.Deferred(),
			result = fn(function(e,cb){
				typeof cb =='function' && cb.call(that,e);
				return dfd.resolve.call(that,e);
			}, function(e,cb){
				typeof cb =='function' && cb.call(that,e);
				return dfd.reject(e);
			});
		return typeof result === 'object' && ('then' in result) ? result : dfd.promise();
	};

	// 对类增加观察者模式
	$.classObserver = function(ClassName){
		if(!('on' in ClassName.prototype)){
			ClassName.prototype.on = function(eventName,fn){
				var self = this;
				if(!('__observerFn' in this)){
					this.__observerFn = {};
				}
				if(!(eventName in this.__observerFn)){
					this.__observerFn[eventName] = [];
				}
				this.__observerFn[eventName].push(fn);
			};
			// 触发模块事件
			ClassName.prototype.trigger = function(eventName,data){
				var self = this;
				if(!('__observerFn' in this)){
					this.__observerFn = {};
				}
				if(eventName in this.__observerFn){
					$.each(this.__observerFn[eventName],function(idx,fn){
						return fn.apply(self,data)
					});
				}
			};
		}
	}

	// 反序列化 $.param
	$.unserialize = function(str){
		var data = {};
		str && str.split('&').forEach(function(item){
			item = item.split('=');
			var key = decodeURIComponent(item[0]),
			val = decodeURIComponent(item[1]);
			if(key.indexOf("[]")>=0){
				key = key.replace("[]",'');
				if(!(key in data)){
					data[key] = [];
				}
				data[key].push(val);
			}else{
				data[key] = val;
			}
		});
		return data;
	}

	$.translate = function(str){
		return $.promise(function(resolve,reject){
			$.post('/collect/collect/translate',{
				str:str
			},function(res){
				res = $.parseJSON(res).response;
				if(res.code){
					reject(res);
				}else{
					resolve(res.data);
				}
			});
		});
	};


	$.postMessage = function(data){
		window.opener.postMessage(data,location.origin);
		return this;
	};

	//  jq.tmpl 模板
    $.$tmplPromise = {};
	$.fn.loadTmpl = function(src,data){
		var $this = this;
		return $.promise(function(_resolve){
			var render = function($tmpl){
					var content = $tmpl.tmpl(data),
						tmp = $("body").append(content),
						size = {
							width:content.width(),
							height:content.height()
						};
					$content = $this.$append(content);
					$content.tmplSize = size;
					$this.attr('tmpl-url',src);
					_resolve($content);
				};
			if(!(src in $.$tmplPromise)){
				$.$tmplPromise[src] = $.Deferred();
				$.get(src+'.html').done(function(html){
					var $tmpl = $('<script type="text/x-jquery-tmpl" tmp-url="'+src+'">' + html + '<\/script>').appendTo(document.body);
					$.$tmplPromise[src].resolve($tmpl)
				});
			}
			$.$tmplPromise[src].then(render);
		});
	};


	$.fn.serializeObject = function(){
		var $form = $('<form></form>');
		$(this).children().clone().appendTo($form);
		return $.unserialize($form.serialize());
	};

	// 模块加载器
	$.require = function(srcs, fn, variableName, globalVar) {
		if (typeof srcs === 'string') {
			srcs = [srcs];
		}
		variableName = variableName || srcs[0];
		var dfd = $.Deferred(),
			load = dfd.promise(),
			allCount = 0,
			count = 0,
			getType = function(src) {
				return src.split('.').pop();
			};
		dfd.resolve();
		if(!(variableName in _cache.dfd)){
			_cache.dfd[variableName] = $.Deferred();
			$.each(srcs, function(k, src) {
				if (getType(src) === 'css') {
					$.loadCss(src);
					return true;
				}
				++allCount;
				load = load.then(function() {
					return $.loadJs(src).then(function() {
						if (typeof variableName === 'string') {
							_cache.variable[variableName] = window[variableName];
						}
						if (++count == allCount) {
							_cache.dfd[variableName].resolve(_cache.variable[variableName] || null);
							if (!globalVar) {
								window[variableName] = undefined;
								try {
									delete window[variableName];
								} catch (e) {};
							}
						}
					});
				});
			});
		}
		_cache.dfd[variableName].done(function(v) {
			typeof fn === 'function' && fn(variableName && v);
		});
		return this;

		// if(!(src in _cache.dfd)){
		// 	_cache.dfd[src] = $.Deferred();
		// 	$.loadJs(src).done(function(){
		// 		if(typeof variableName === 'string'){
		// 			_cache.variable[src] = window[variableName];
		// 			if(!globalVar){
		// 				window[variableName] = undefined;
		// 				delete window[variableName];
		// 			}
		// 		}
		// 		_cache.dfd[src].resolve(_cache.variable[src] || null);
		// 	});
		// }
		// _cache.dfd[src].done(function(v){
		// 	typeof fn ==='function' && fn(variableName && v);
		// });
		// return this;
	}

	$.loadJs = function(src, fn) {
		return $.promise(function(resolve, reject) {
			var script = document.createElement("script");
			script.type = 'text/javascript';
			script.onload = function() {
				typeof fn === 'function' && fn.call(window);
				resolve();
			};
			script.onerror = reject;
			script.src = src;
			document.body.appendChild(script);
		});
	}

	$.loadCss = function(src, fn) {
		return $.promise(function(resolve, reject) {
			var link = document.createElement("link");
			link.rel = 'stylesheet';
			link.onload = function() {
				typeof fn === 'function' && fn.call(window);
				resolve();
			};
			link.onerror = reject;
			link.href = src;
			document.body.appendChild(link);
		});
	}

	// promise形式的 setTimeout
	$.timeout = function(t){
		return $.promise(function(resolve,reject){
			setTimeout(resolve,t);
		});
	}

	$.modalReady = function($this) {
		var $el = function(selector) {
			// 等同于 $(document).find(selector) 
			return $this.find.call($this, selector);
		};
		$.each($.readyEvent, function(k, evt) {
			evt.call($this, $el);
		});
	}

	// 全选
	$.checkAll = function(name, context, filter) {
			context = context || $(document);
			var $allBtn = context.find("[check-all=" + name + "]"),
				$btn = context.find("[data-check=" + name + "]");
			if (typeof filter === 'function') {
				$btn = $btn.filter(filter);
			}
			$allBtn.off('change.checkAll')
				.on('change.checkAll', function() {
					var _name = $(this).attr('check-all'),
						_status = $(this).is(':checked');
					$btn.prop('checked', _status).trigger('change');
				});
			$btn.off('change.checkAll')
				.on('change.checkAll', function() {
					// 获取所有的状态
					var _name = $(this).data('check'),
						length = $btn.size(),
						checkCount = 0;
					$btn.each(function() {
						if ($(this).is(":checked")) {
							checkCount++;
						}
					});

					$allBtn.checkedStatus(checkCount, length);
				});
		}
		// 全选end

	$.modalCache = {};


	/**
	 * 在当前DOM上jQuery单例
	 */
	$.fn.$singleton = function(key,fn){
		var $this = this;
		if(!this.data('$$'+key)){
			fn.prototype.$$desctruct = function(){
				this.__desctruct();
				$this.data('$$'+key,null);
				delete this;
			};
			var obj = new fn(this[0]);
			this.data('$$'+key,obj);
		}
		return this.data('$$'+key);
	};

	// 序列化
	$.fn.exSerializeArray = function(){
		var $form = $('<form></form>');
		$(this).children().clone().appendTo($form);
		return $form.serializeArray();
	};

	// 获取当前元素相对某屏幕的方向位置 ()
	// {
	//   direction:'NE',  // NE,SE,NW,SW,NC,SC,CE,CW,CC
	//   position:{       // 根据方向的定点坐标
	//     top:100,
	//     left:100
	//   }
	// }
	var direction = function(direction,position){
		this.direction = direction;
		this.position = position;
	};

	$.fn.getDirection = function(){
		// 获取当前元素的x轴中间点横坐标
		var centerPoint = {},
			dir = '',
			self = $(this),
			parent = $(window),
			doc = $(document);
		centerPoint.left = self.outerWidth() / 2 + self.offset().left;
		centerPoint.top = self.outerHeight() / 2 + ( self.offset().top - doc.scrollTop() );
		// 判断方向
		var perWidth = parent.width() / 3,
			perHeight = parent.height() / 3;
		if(centerPoint.top < perHeight){
			dir += 'N';
		}
		if(centerPoint.top > perHeight && centerPoint.top < perHeight * 2){
			dir += 'C';
		}
		if(centerPoint.top > perHeight * 2 ){
			dir += 'S';
		}
		if(centerPoint.left < perWidth){
			dir += 'W';
		}
		if(centerPoint.left > perWidth && centerPoint.left < perWidth * 2){
			dir += 'C';
		}
		if(centerPoint.left > perWidth * 2 ){
			dir += 'E';
		}
		return new direction(dir,centerPoint);
	};

	/**
	 * 实例化 jquery.form 对象
	 * @return {[type]} [description]
	 */
	$.fn.form = function(option){
		var FN = function(ele){
			var self = this;
			self.item = {};
			self.ele = ele;
			$.each(ele.elements,function(k,input){
				self.item[input.name] = input;
			});
		};
		FN.prototype.set = function(name,val){
			if(name in this.item){
				this.item[name].value = val;
			}else{ 		// 需要创建dom
				var ele = document.createElement('input');
				ele.type = 'hidden';
				ele.name = name;
				ele.value = val;
				$(ele).appendTo(this.ele);
				this.item[name] = ele;
			}
			return this;
		};
		FN.prototype.get = function(name){
			if(!this.item[name]){
				return undefined;
			}
			return this.item[name].value;
		};
		FN.prototype.submit = function(){
			this.ele.submit();
			return this;
		};
		FN.prototype.__desctruct = function(){
		};
		return this.$singleton('form',FN);
	};

	$.fn.showTips = function(html,promiseFn,cache_id){
		var $this = $(this);
		return $.promise(function(resolve,reject){
			if(!html){
				$this.data('showTips',false);
				return $this.find('.iv-tooltip').remove();
			}
			if($this.data('showTips')){
				resolve($this.find('.iv-tooltip'));
			}else{
				$this.data('showTips',true);
				var dir = $this.getDirection(),
					$tip = $("<div class='iv-tooltip'></div>"),
					render = function(){
						var dir = $this.getDirection(),_css = {};
						// top
						if(['NW','NC','NE','CC'].indexOf(dir.direction)>=0){
							_css.top = 'calc(100% + 3px)';
							_css.bottom = 'auto';
						}
						if(['CW','CE'].indexOf(dir.direction)>=0){
							_css.top = - ($tip.outerHeight() - $this.outerHeight()) / 2;
							_css.bottom = 'auto';
						}
						if(['SW','SC','SE'].indexOf(dir.direction)>=0){
							_css.top = - $tip.outerHeight() - 10;
							_css.bottom = 'auto';
						}
						// left
						if(['NW','SW'].indexOf(dir.direction)>=0){
							_css.left = 0;
							_css.right = 'auto';
						}
						// 中间右侧
						if(['CE'].indexOf(dir.direction)>=0){
							_css.left = - $tip.outerWidth() - 10;
							_css.right = $tip.outerWidth() -10;
						}
						// 中间左侧
						if(['CW'].indexOf(dir.direction)>=0){
							_css.left = $this.outerWidth() + 10;
							_css.right = 'auto';
						}
						// right
						if(['SE','NE'].indexOf(dir.direction)>=0){
							_css.left = 'auto';
							_css.right = 0;
						}
						// 垂直中间方向
						if(['CC','NC','SC'].indexOf(dir.direction)>=0){
							_css.left = ($this.outerWidth() - $tip.outerWidth() ) /2;
							_css.right = 'auto';
						}
						$tip.css(_css).removeClass([
							'tooltip-nw',
							'tooltip-nc',
							'tooltip-ne',
							'tooltip-cw',
							'tooltip-cc',
							'tooltip-ce',
							'tooltip-sw',
							'tooltip-sc',
							'tooltip-se',
						].join(' ')).addClass('tooltip-'+dir.direction.toLowerCase());
					};
				if($this.css('position')){
					$this.css('position','relative');
				}
				$tip.html(html);
				$("body").append($tip);
				$tip.width($tip.width());
				$tip.height($tip.height());
				$this.$append($tip);
				$(window).on('resize scroll',render).resize();
				promiseFn && promiseFn.then(function(){
					resolve($tip);
				}) || resolve($tip);
			}
		});
	};

	/**
	 * 模态框终极版
	 * @version 2016-07-19 
	 */
	$.$modal = {};
	$.requestCacheUrl = function(obj){
		var str = '';
		if(typeof obj === 'string'){
			str = obj;
		}else{
			obj = $.extend({},{
				url:'',
				data:{}
			},obj);
			str = obj.url + '?' + $.param(obj.data);
		}
		return str;
	};
	$.$modalZindex = 0;
	$.modal = function(content,title,options){
		// 传入boolean类型时候等同于 $.overLay
		if(typeof content === 'boolean' || typeof content === 'undefined'){
			return $.overLay(content);
		}
		var $overLay = $("#over-lay");
		options = $.extend(true,{},{ 	// 深拷贝 footer 对象
			footer:{
				resolve:{
					label:'确定',
					className:['btn-success'],
					click:function(e,$modal,data){}
				},
				reject:{
					label:'取消',
					className:['btn-info'],
					click:function(e,$modal){
						$modal.close();
					}
				}
			},
			tmpl:'/tmpl/modal',
			// footerSort:['resolve','reject'],
			inside:true, 		// false:滚动条在window上,true:在模态框内
			cache: false
		},options);
		if(! ('footerSort' in options)){
			options.footerSort = ['resolve','reject'];
		}
		var open = function(){
				var $this = $(this);
				$this.is(":hidden") && $.overLay(true);
				$this.show().trigger('modal.open');
			},
			close = function(){
				if(options.cache){
					$(this).hide();
				}else{
					$(this).remove();
				}
				if(options.inside){
					$.$modalZindex--;
				}
				$(this).trigger('modal.close');
				$.overLay(false);
			},
			resize = function(){
				var $modal = $(this),
					_h = $modal.find(".modal-title").outerHeight() + $modal.find(".modal-body").outerHeight() + $modal.find(".modal-footer").outerHeight();
				if(options.inside){
					$modal.height(_h);
					$modal.css('top',($(window).height()-$modal.outerHeight()) /2);
					$modal.find('.modal-main').height($modal.height()-$modal.find('.modal-title').height());
				}
				// console.log($modal.find(".modal-body").outerHeight(),$modal.find(".modal-footer").outerHeight());
				
				// $modal.resize = function(){
				// 	// console.log($modal.find(".modal-content").outerHeight());
				// 	$modal.css({
				// 		width: $modal.width(),
						
				// 	});
				// };
			},
			render = function(html){
				return $.promise(function(resolve,reject){
					$overLay.loadTmpl(options.tmpl,$.extend({
						html:html,
						title:title,
						type:options.inside?'inside-modal':'over-lay-modal'
					},options)).then(function($modal){
						var $main = $modal.find('.modal-main'),
							$footer = $modal.find('.modal-footer');
						if(options.footer){
							$modal.find('.modal-body').after($footer);
							$.each(options.footerSort,function(idx,key){
								var item = options.footer[key];
								if(item.label){
									var className = 'iv-btn '+item.className.join(' ');
									$modal.find('.modal-footer').append("<a data-event='"+key+"' class='"+className+"' >"+item.label+"</a>");
								}
							});
						}else{
							$modal.find('.modal-footer:eq(1)').remove();
						}
						$modal.css({
							width:$modal.tmplSize.width
						});
						$modal.removeClass('state-ready');
						$modal.close = close;
						$modal.open = open;
						$modal.resize = resize;
						$modal
							.on('click','.modal-close',function(){
								$modal.close();
							})
							.on('modal.open',function(){
								options.inside && $(this).css('zIndex',$.$modalZindex++);
							});
						$footer.on('click','.iv-btn',function(e){
							var eventName = $(this).data('event'),
							data = $modal.serializeObject();
							$modal.trigger('modal.action.'+eventName,[$modal,data]);
							options.footer && 'click' in options.footer[eventName] && typeof options.footer[eventName].click === 'function' && options.footer[eventName].click.apply(this,[e,$modal,data]);
						});
						$modal.trigger('modal.open');
						resolve($modal);
						$.overLay(true);
						if(options.inside){
							$modal.resize();
							$(window).resize(function(){
								$modal.resize()
							});
						}
					});
				});
			};
		return $.promise(function(resolve,reject){
			var key = $.requestCacheUrl(content),
				afterRender = function($modal){
					return $.promise(function(resolve){
						if(options.cache){
							$.$modal[key] = $modal;
						}
						resolve($modal);
					});
				};
			if(options.cache && key in $.$modal){
				$.$modal[key].open();
			}else{
				if(typeof content == 'object'){
					var obj = $.extend({
						url:'',
						method:'get',
						data:{}
					},content);
					$.$ajax(obj).then(function(responseText,status,$xhr){
						if(!title){
							title = $xhr.lbTitle;
						}
						return render(responseText);
					}).then(afterRender).then(resolve);
				}else{
					render(content).then(afterRender).then(resolve);
				}	
			}
		});
	};

	// 模态框事件
	$.showModal = function(html, title, promiseFn, cache_id, style, options) {
		// $singleton
		options = $.extend({
			resolve:false,
			reject:false
		},options);
		return $.promise(function(resolve, reject) {
			var $modal,modalHtml;
			$.overLay(true);
			if(cache_id && (cache_id in $.modalCache) ){
				$modal = $.modalCache[cache_id];
				$modal.show();
			}else{
				var footer = '';
				if(options.resolve || options.reject){
					footer += '<div class="modal-footer">';
					if(options.resolve){
						footer += '<a class="iv-btn btn-success">确定</a>';
					}
					if(options.reject){
						footer += '<a class="iv-btn btn-default modal-close">取消</a>';
					}
					footer += '</div>';
				}
				
				$modal = $("<div class='iv-modal'></div>"),
					modalHtml = '<h2 class="modal-title">' + title + '</h2>' +
					'<div class="modal-action">' +
					// '<span class="iconfont icon-jianhao"></span>'+
					'<span class="iconfont icon-guanbi modal-close"></span>' +
					'</div>' +
					'<div class="modal-content"><div class="modal-body">' +
					html + '</div>' +
					footer + 
					'</div>';
				$(modalHtml).appendTo($modal);
				$modal.appendTo("body");
				$modal.close = function() {
					$modal.trigger('close', [$modal]);
					if(cache_id){
						$modal.hide();
					}else{
						$modal.remove();
					}
					$.overLay(false);
				};
				$modal.resize = function(){
					// console.log($modal.find(".modal-content").outerHeight());
					$modal.css({
						width: $modal.width(),
						height: $modal.find(".modal-content").outerHeight() + 30
					});
				};
				$modal.on('resize',$modal.resize);
				$modal
					.css($.extend({
						width: $modal.width(),
						height: $modal.height()
					},style))
					.addClass('fixed-center')
					.find('.modal-close').on('click', function(){
						$modal.close();
					});

				// 数据传输 -- 待废弃
				$modal.find('[type=submit]').on('click',function(){
					var $form = $(this).closest('[role=form]'),
					data = $form.serializeObject();
					$modal.trigger('modal.then',[data]);
					$modal.trigger('modal.action.resolve',[$modal,data]);
				});
				// 新版
				$modal.find('.modal-footer .btn-success').on('click',function(){
					var data = $modal.serializeObject();
					$modal.trigger('modal.then',[data]);
					$modal.trigger('modal.action.resolve',[$modal,data]);
					$modal.close();
				});

				$.modalReady($modal);
				$modal.data('$$modal',$modal);
				if(cache_id){
					$.modalCache[cache_id] = $modal;
				}
			}
			$.modalCount += 1;
			if (promiseFn) {
				promiseFn.then(function() {
					resolve($modal);
				});
			} else {
				resolve($modal);
			}
			
			// return $modal;
		});
	}

	$.$overLayCount = 0;

	$.overLay = $.maskLayer = function(i) {
		if (i) {
			$.$overLayCount++;
			$("#over-lay").show();
			$("body").css('overflow-y', 'hidden');
		} else {
			if(--$.$overLayCount <= 0){
				$.$overLayCount = 0;
				$("#over-lay").hide()
				$("body").css('overflow-y', 'initial');
			}
		}
		return this;
	}

	$.readyEvent = [];

	$.domReady = function(readyEvent) {
		$.readyEvent.push(readyEvent);
		return this;
	}

	$.openModal = function(url, params, title, method, cache_id,style, options) {
		options = $.extend({
			resolve:false,
			reject:false
		},options);
		if(!options.resolve && !options.reject){
			options.footer = false;
		}else{
			options = {
				footer:{}
			};
			if(!options.resolve){
				options.footer.resolve = false;
			}
			if(!options.reject){
				options.footer.reject = false;
			}
		}
		// 转到新街口上
		return $.modal({
			url:url,
			data:params,
			method:method
		},title,options);
		//// 以下是废弃部分
		// var dfd = $.Deferred(),ajax,
		// ready = function(html) {
		// 	$.showModal(html, title, ajax, cache_id,style, options).then(function($modal) {
		// 		dfd.resolve($modal);
		// 	});
		// };
		// params = params || {};
		// if(cache_id && (cache_id in $.modalCache)){
		// 	ajax = $.promise(function(resolve){
		// 		resolve();
		// 		ready();
		// 	});
		// }else{
		// 	ajax = $.$ajax({
		// 		url: url,
		// 		method: method || 'get',
		// 		data: typeof params ==='object'? $.param(params) : params
		// 	}).done(function(response){
		// 		if(typeof response ==='object'){
		// 			console.log(response)
		// 		}else{
		// 			ready(response);
		// 		}
		// 	});
		// }

		// return dfd.promise();
	}


	// 同步队列
	$.asyncQueue = function(fns, cb) {
		var dfd = $.Deferred(),
			done = $.Deferred(),
			d = dfd.promise(),
			stop = false,
			i = 0;
		$.each(fns, function(k, fn) {
			// console.log(fn)
			var fffn = function() {
				var ffn = function() {
					Array.prototype.unshift.call(arguments, k);
					stop = typeof cb === 'function' && cb.apply(this, arguments) === false;
					fns.length === ++i && done.resolve();
				};
				return stop || fn().then(ffn,ffn);
			};
			d = d.then(fffn,fffn);
		});
		dfd.resolve();
		return done.promise();
	}

	// 性能更好地动画计算方式
	// $.raf = function(fn,timeout){
	// 	var et = new Date().getTime()+timeout,
	// 	dfd = $.Deferred(),
	// 	eventLoop = function(st){
	// 		console.log(st)
	// 		fn();
	// 		if(new Date().getTime()<et){
	// 			requestAnimationFrame(eventLoop);
	// 		}else{
	// 			dfd.resolve();
	// 		}
	// 	};
	// 	eventLoop();
	// 	return dfd.promise();
	// }


	$.pluginReady = function(pluginName, Fn, global) {
		return $.require(_pluginDeps[pluginName], Fn, pluginName, global);
	}

	$.alertBox = function(html, autoHide , callback) {
		var $box = $("#alert-box"),
			autoHide = autoHide!==undefined ?autoHide : true;
		$box._switchClass(['hide', 'flex'])
			.find('.content')
			.html(html);
		$box.animate({
			opacity: 0.9
		}, 1000);
		autoHide && setTimeout(function() {
			$box.remove();
		}, 2000);
		$box.remove = function() {
			$box.animate({
				opacity: 0
			}, 2000, function() {
				$box._switchClass(['fex', 'hide']);
				if (typeof callback === 'function'){
					callback();
				}
			});
		};
		return $box;
	}


	/**
	 * [tips description]
	 * type:  info,success等等
	 */
	$.message = function(message,type){
		var icons = {
			success:['chenggong','success'],
			loading:['dengdai18 rotate','success'],
			warn:['jinggao','warn']
		};
		return $.promise(function(resolve,reject){
			var $tips = $("<div class='iv-tips'><p class='tips-"+icons[type][1]+"'><span class='iconfont icon-"+icons[type][0]+"'></span>" + message + "<i class='tips-close iconfont icon-guanbi'></i></p></div>");
			$tips.appendTo(".right_content").fadeIn(500);
			$tips.close = function(){
				$tips.trigger('close').fadeOut(500,function(){
					$tips.remove();
				});
			};
			$tips.find('.tips-close').on('click',$tips.close);
			resolve($tips);
		});
	}

	/**
	 * type:
	 * info 详情 可带上具体list信息 	// detail:[]
	 * confirm 确认框
	 * fail 失败
	 * message 提示框
	 * progress 进度 
	 * _modal 模态框新窗口 
	 */

	$.alertMsg = function(err){
		return $.promise(function(resolve){
			var $alert;
			switch(err.type){
				case 'success':
					err.success = true;
				default:
				case 'message':
					var type = err.success ? 'success':'warn';
					$alert = $.message(err.message, type);
					break;
				case 'box':
					$alert = $.modal(err.message, err.title || '提示');
					break;
				case 'tips':
					break;
			}
			$alert.then(function($m){
				if('timeout' in err){
					$.timeout(err.timeout * 1000).then(function(){
						resolve();
						$m.close();
					});
				}else{
					resolve();
				}	
			});
		}).then(function(){
			if('redirect' in err){
				$.location.href(err.redirect);
			}
			if('reload' in err){
				$.location.reload();
			}
		});
	};


	// loading 2016-07-29
	var __Loading = function(){
		var self = this;
		$.message('加载中...','loading').then(function($box){
			self.$box = $box;
		});
	};

	__Loading.prototype.close = function(){
		this.$box.remove();
	};

	$.loading = function(_global){
		if(typeof _global === 'undefined'){
			return new __Loading();
		}else if(_global){
			$("#loading").show();
			$.overLay(true);
		}else{
			$("#loading").hide();
			$.overLay(false);
		}
	};
	// loading

	$.confirmBox = function(html, trueEvent, falseEvent) {
		return $.promise(function(resolve,reject){
			// html = '<div class="modal-center"><span style="font-size:48px;color:#f80;" class="iconfont icon-jinggao"></span><span style="font-size:24px;">'+html+'</span></div>';
			$.modal(html,null,{
				tmpl:'/tmpl/alert/warn',
				alertType:'confirm',
				icon:'dengdai18'
			}).then(function($modal){
				$modal.on('modal.action.resolve',function(){
					trueEvent && trueEvent();
					$modal.close();
					resolve();
				});
				$modal.on('modal.action.reject',function(){
					falseEvent && falseEvent();
					reject();
				});
			});
		});
	}

	// 添加一行
	$.addFormElement = function(id, maxNum, callback, self) {
		var self = self || document,
			dfd = $.Deferred(),
			rtnData = [],
			$modal = $(self).closest('.iv-modal'),
			$parent = $modal.size()?$modal:$('body'),
			$element = $parent.find("[iv-template=" + id + "]"),
			$countEle = $parent.find("[iv-template-added=" + id + "]");
		if (maxNum && $countEle.size() >= maxNum) {
			rtnData.push('数量已达上限');
			dfd.reject(rtnData);
		} else {
			$element.$after($element.clone());
			$element.removeAttr('iv-template').attr('iv-template-added', id);
			rtnData.push($element)
			dfd.resolve(rtnData);
		}
		typeof callback === 'function' && callback.apply($, rtnData);
		return dfd.promise();
	};

	$.mousehover = function($elements, fn1, fn2) {
		$(document).on('mousemove', function(e) {
			var tmp = false;
			$.each($elements, function(k, $element) {
				$.each($element, function(k, $target) {
					if (e.target !== $target && Array.prototype.indexOf.call($(e.target).parents(), $target) !== -1) {
						fn1.call(document, e);
						tmp = true;
					}
				});
			});
			if (!tmp) {
				typeof fn2 === 'function' && fn2.call(document, e);
			}
		});
	};




	$.fn.addFormElement = function(id, maxNum, callback){
		return $.addFormElement(id, maxNum,callback,this);
	}

	$.fn.getModal = function(){
		var $modal = $(this).closest('.iv-modal'),
			$parent = $modal.size()?$modal:$('body');
		if($parent.data('$$modal')){
			return $parent.data('$$modal');
		}else{
			return $parent;
		}
	}


	// find + filter 遍历器结合
	$.fn.$query = function(selector){
		var ele = [];
		$([this.find(selector),this.filter(selector)]).each(function(){
			this.each(function(){
				ele.push(this)
			});
		});
		return $(ele);
	}

	// append事件
	$.each(['append','html','after','before'],function(i,evt){
		$.fn['$'+evt] = function(t){
			var $el,$this = $(t);
			$.fn[evt].call(this,$this);
			$el = function(selector) {
				return $.fn.$query.call($this,selector)
			};
			$.each($.readyEvent, function(k, evt) {
				evt.call($this, $el);
			});
			return $this;
		}
	});

	// $.each(['appendTo'],function(i,evt){
	// 	$.fn['$'+evt] = function($this){
	// 		var self = this;
	// 		$.fn[evt].call(self,$this);
	// 		var $el = function(selector) {
	// 			return self.filter.call(self, selector);
	// 		};
	// 		$.each($.readyEvent, function(k, evt) {
	// 			evt.call(self, $el);
	// 		});
	// 		return self;
	// 	}
	// });


	//--------   过滤器   ----

	$.fn.sift = function(keys) {
		var fn = {
			':checked-all': function(index) {
				return $(this).checkedStatus() === 1;
			},
			':checked-part': function(index) {
				return $(this).checkedStatus() === 0;
			},
			':checked-none': function(index) {
				return $(this).checkedStatus() === -1;
			}
		}
		return $(this).filter(function(index) {
			var rtn = false,
				self = this;
			$.each(keys.split(','), function(idx, key) {
				if (key in fn && fn[key].call(self, index)) {
					rtn = true;
					return false;
				}
			});
			return rtn;
		});
	}

	//--------   过滤器 end  ----


	// 获取dom节点下的text
	$.fn.nodeText = function(idx, val) {
		// console.log(this[0].childNodes[idx])
		if (typeof val !== 'undefined') {
			this[0].childNodes[idx].data = val;
			return this;
		} else {
			return this[0].childNodes[idx || 0].data;
		}
	};

	// 三种状态的checkbox
	$.fn.checkedStatus = function(count, all) {
		if (!arguments.length) {
			var self = this[0];
			return self.checked ? 1 : (self.indeterminate ? 0 : -1);
		} else {
			if (typeof all === 'undefined') {
				return $(this).each(function() {
					switch (count) {
						case 1:
							this.checked = true;
							this.indeterminate = false;
							break;
						case 0:
							this.indeterminate = true;
							this.checked = false;
							break;
						case -1:
							this.checked = false;
							this.indeterminate = false;
							break;
					}
				});
			} else {
				if (count == all) {
					return $(this).checkedStatus(1);
				} else if (!count) {
					return $(this).checkedStatus(-1);
				} else {
					return $(this).checkedStatus(0);
				}
			}
		}
	}



	// 获取设置在dom上的自定义属性
	$.fn.getParams = function(evalLists) {
		var attr = this[0].attributes,
			params = {},
			evalLists = evalLists || [];
		for (var i in attr) {
			if (typeof attr[i].name === 'string' && attr.hasOwnProperty(i)) {
				var _name = attr[i].name.toCamelCase();
				if (evalLists.indexOf(_name) >= 0) {
					// console.log('params.' + _name + ' = ' + attr[i].value);
					eval('params.' + _name + ' = ' + attr[i].value);
				} else {
					params[_name] = attr[i].value;
				}
			}
		}
		return params;
	}

	// 绑定事件到堆中，倒序执行
	$.fn.unshiftEvent = function(event, foo) {
		var self = this,
			_type = event.split('.')[0];
		$.each(self, function() {
			var events = $._data(this, 'events');
			$.event.add(this, event, foo, self.selector);
			events && _type in events && events[_type].unshift(Array.prototype.pop.call(events[_type]));
		})
		return this;
	};

	$.fn.selectVal = function(val) {
		if (typeof val === 'undefined') {
			return $(this).val();
		} else {
			$(this).val(val).trigger('change');
			return this;
		}
	}

	/**
	 * 暂停事件
	 * @param  {[type]} event     [事件类型]
	 * @param  {[type]} promiseFn [暂停条件，必须返回promise对象]
	 * @param  {[type]} namespace [命名空间，可选参数]
	 */
	$.fn.pauseEvents = function(event, promiseFn, namespace) {
		var namespace = namespace || 'pauseEvents',
			_type = event.split('.')[0];
		this.unshiftEvent(event + '.' + namespace, function(e) {
			e.stopImmediatePropagation(); // 中断事件
			e.preventDefault();
			var args = arguments,
				self = this;
			promiseFn.apply(this, args).then(function() {
				// 重新执行事件
				$.each($._data(self, 'events')[_type], function(key, e) {
					if (('handler' in e) && e.namespace !== namespace) {
						e.handler.apply(self, args);
					}
				});
			});
		});
	}


	$.fn.bind_hideScroll = function() {
		return $(this).each(function() {
			var $this = $(this);
			$this.css({
				overflow: 'hidden'
			}).on('mousewheel', function(e, delta, deltaX, deltaY) {
				var top = $this.scrollTop();
				$this.scrollTop(top - deltaY);
			});
		});
	}

	// 切换事件轮流执行
	$.fn.toggleEvent = function(event) {
		var eventQueueLength = arguments.length,
			events = arguments,
			$this = $(this);
		$this
			.data('toggleQueueId', 1)
			.on(event, function(e) {
				var id = $this.data('toggleQueueId'),
					nextId = id + 1;
				events[id].call(this, e);
				if (nextId >= eventQueueLength) {
					nextId = 1;
				}
				$this.data('toggleQueueId', nextId);
			});
		return this;
	};

	// 点击其他区域事件
	$.fn._else = function(event, foo) {
		var $target = $(this)[0],
			$document = $(document),
			_e = event + '.else' + Math.random();
		$document.on(_e, function(e) {
			if (e.target !== $target && Array.prototype.indexOf.call($(e.target).parents(), $target) === -1) {
				foo.apply($target, [e, _e]);
				$document.off(_e);
			}
		});
	};

	// 切换class
	$.fn._switchClass = function(classNames, status) {
		return $(this).each(function() {
			var $this = $(this);
			switch (classNames.length) {
				case 1:
					break;
				case 2:
					if (status) {
						$this.removeClass(classNames[1]).addClass(classNames[0]);
					} else {
						$this.removeClass(classNames[0]).addClass(classNames[1]);
					}
					break;
				default:
					break;
			}
		});
	}

	// 进度条
	$.fn.progress = function(total){
		var self = this[0],
			$this = $(self),
			progress = function(){
				this.total = total;
			};
		progress.add = function(){
			self.value += 100/total;
		}
		self.max = 100;
		self.value = 0;
		return progress;
	};


	$.fn.$ajax = function(opt){
		var $this = $(this),ajax;
		if(!$this.attr('disabled')){
			$this.attr('disabled','disabled');
			ajax = $.$ajax(opt);
			ajax.always(function(){
				$this.removeAttr('disabled');
			});
			return ajax;
		}else{
			return $.promise(function(resolve,reject){
				console.warn('请不要重复操作');
				reject([err,'请不要重复操作']);
			});
		}
	};

	// 加载插件
	$.fn.registerPlugin = function(name, Fn, global) {
		if($(this).size()){
			var self = this;
			$.require(_pluginDeps[name], function(v) {
				$(self).each(function() {
					Fn.call(this, v);
				});
			}, name, global);
		}
	}

	$.fn.open = function(o){
		var $this = $(this),
		o = $.extend({
			url:'',
			method:'get',
			data:null,
			// title:'小老板',
			inside:true,
			btn:{
				resolve:false,
				reject:false
			}
		},o);
		return $.promise(function(resolve,reject){
			switch (o.target) {
				case '_blank':
					resolve();
					break;
				case '_state':
					$.location.state(o.url,o.title,o.data,0,o.method).done(resolve);
					break;
				case '_norefresh':
					$.location.state(o.url,o.title,o.data,0,o.method,false).done(resolve);
					break;
				case '_modal':
					var footerSort = [];
					'btn' in o && o.btn && $.each(o.btn,function(k,v){
						if(v){
							footerSort.push(k)
						}
					})
					$.modal({
						url:o.url,
						method:o.method,
						data:o.data,
					},o.title,{
						inside:o.inside,
						footerSort:footerSort
					}).done(function($modal){
						resolve($modal);
						$this.trigger('modal.ready', [$modal, o.title]);
						$modal.on('modal.action.resolve',function(e,$modal,data){
							$this.trigger('modal.submit',[data,$modal]);
						});
					});
					break;
				case '_request':
					$this.$ajax({
						method:o.method,
						url:o.url,
						data:o.data
					}).done(function(s){
						// if(s.response)
						// resolve();
					});
					break;
				default:
					var $t;
					if(o.target instanceof $){
						$t = o.target;
					}
					if (o.target.charAt(0) == '#') {
						$t = $(o.target);
					}
					$t && $.showModal($t.html(), o.title, undefined, undefined,{},o.btn).done(function($modal){
						resolve($modal);
						$this.trigger('modal.ready', [$modal, o.title]);
						$modal.on('modal.then',function(e,data){
							$this.trigger('modal.submit',[data,$modal]);
						});
					}) || resolve();
					break;
			}

		});
	};

	/****** 定义事件区  end *****/


	/****** 辅助功能区   *****/


	// 兼容localStorage
	$.localStorage = function(k, v) {
		if (!('localStorage' in window)) {
			if (typeof v === 'undefined') {
				return $.cookie.getCookie(k, v);
			} else {
				$.cookie.setCookie(k, v);
				return $;
			}
		} else {
			if (typeof v === 'undefined') {
				var val = localStorage.getItem(k);
				if (val === 'true') {
					val = true;
				} else if (val === 'false') {
					val = false;
				}
				return val;
			} else {
				localStorage.setItem(k, v);
				return $;
			}
		}
	};

	// 判断是否开启控制台
	$.isConsoleOpen = function(msg){
		var __checkStatus = false,
			element = new Image();
		element.__defineGetter__('id', function() {
			__checkStatus = true;
		});
		console.info(msg || 'console', element);
		return __checkStatus;
	}

	// location辅助
	// 
	// host
	// pathName
	// sciprtName
	// query
	$.location = {
		host: function(k, v) {
			if (typeof v === 'undefined') {

			} else {

			}
		},
		pathName: function(k, v) {
			if (typeof v === 'undefined') {
				return location.pathname;
			} else {

			}
		},
		sciprtName: function(k, v) {
			if (typeof v === 'undefined') {

			} else {

			}
		},
		query: function(k, v) {
			if (typeof v === 'undefined' && typeof k === 'string') {
				if (location.search) {
					return $.urlToJson(location.search)[k];
				}
			} else {
				// 先解析成json
				var obj = $.urlToJson(location.search);
				if (typeof k === 'object') {
					obj = $.extend(obj, k);
				} else {
					obj[k] = v;
				}
				return '//' + location.host + location.pathname + '?' + $.param(obj);
			}
		},
		reload: function(timeout) {
			if(!$.isConsoleOpen('reload is prevent')){
				setTimeout(function() {
					location.reload();
				}, timeout || 0);
			}
		},
		href: function(url, timeout) {
			if(!$.isConsoleOpen('reload is prevent ' + url)){
				setTimeout(function() {
					location.href = url;
				}, timeout || 0);
			}
		},
		open: function(url,fn){
			$(window).on('message',function(e,data){
				fn.apply(window,[e,e.originalEvent.data]);
			});
		},
		state: function(url,title,param,timeout,method,state){
			if(typeof state === 'undefined'){
				state = true;
			}
			return $.promise(function(resolve){
				setTimeout(function() {
					$.$ajax({
						url:url,
						method:method || 'get',
						data:param || {},
						beforeSend:function(){
							$.loading(true);
						},
						complete:function(){
						}
					}).done(function(html,status,$xhr){
						$.loading(false);
						title = title || $xhr.lbTitle || '小老板';
						if(state){
							history.pushState({
								href:location.href,
								title:title
							},title,url);
						}
						$("title").html(title);
						$("main.main-view").$html(html);
						resolve($("main.main-view")[0]);
					});
				}, timeout || 0);
			});
		},
	};

	// pushState相关
	window.addEventListener('popstate',function(e){
		if(e.state){
			$.$ajax({
				url:location.href,
				method:'get'
			}).done(function(html){
				$("title").html(e.state.title);
				$(".right_content").$html(html);
			});
		}
	});

	$.urlToJson = function(str) {
		str = str.indexOf("?") === 0 ? str.substr(1) : str;
		var arr = str.split('&'),
			obj = {};
		if (str) {
			for (var i in arr) {
				if(arr.hasOwnProperty(i)){
					var tmp = arr[i].split('=');
					obj[tmp[0]] = tmp[1];
				}
			}
		}
		return obj;
	};

	$.ivStatus = function(k, v) {
		var _s;
		try {
			_s = $.parseJSON($.localStorage('iv-status')) || {};
		} catch (e) {
			_s = {};
		}
		if (!(k in _s)) {
			_s[k] = undefined;
		}
		if (typeof v === 'undefined') {
			return _s[k] || false;
		} else {
			_s[k] = v;
			$.localStorage('iv-status', JSON.stringify(_s));
			$(document).trigger('ivStatusChange', [k, v]);
			return $;
		}

	}

	$.toggleStatus = function(k) {
		return $.ivStatus(k, !$.ivStatus(k));
	}

	$.ivStatusOn = function(k, foo) {
		$(document).on('ivStatusChange', function(e, _k, _v) {
			if (k == _k) {
				foo.apply($, [_v]);
			}
		}).trigger('ivStatusChange', [k, $.ivStatus(k)]);
	}

	$.ajaxSetup({
		success:function(responseText,status,$xhr){
			if($xhr.getResponseHeader('X-Title')){
				var title = decodeURI($xhr.getResponseHeader('X-Title'));
				$xhr.lbTitle = title;
			}else{
				$xhr.lbTitle = '小老板';
			}
		}
	});

	$.$ajax = function(options){
		var $load;
		return $.ajax($.extend({},{
			beforeSend:function(){
				$load = $.loading();
			},
			complete:function(){
				$load.close();
			}
		},options)).done(function(data,status,$xhr){
			var target = $xhr.getResponseHeader('X-Target');
			switch(target){
				default:
					if(typeof data === 'object' && 'response' in data ){
						var rs = data.response;
						if(typeof rs[0] !== 'object'){
							rs = [rs];
						}
						rs.forEach(function(err,k){
							$.alertMsg(err);
						});
					}
					break;
				case '_modal':
					var option = $.parseJSON($xhr.getResponseHeader('X-Modal-Option'));
					$.modal(data,$xhr.lbTitle,{
						inside:true
					});
					break;
			}
		}).fail(function($xhr,status,error){
			$.modal($xhr.responseText,error);
		});
	}

	$.meituUrl = function(src){
		return "/imageeditor/default/edit?url=" + encodeURIComponent(src);
	}

	$.domReady(function($el) {

		// 确认事件，中断其他事件
		$el("[confirm]").pauseEvents('click', function() {
			var text = $(this).attr('confirm');
			return $.confirmBox(text);
		});

		// 设置onload事件
		$el("[onload]").each(function() {
			eval($(this).attr('onload'));
		});

		// 状态事件初始化
		setTimeout(function() {
			try {
				var ivStatus = $.parseJSON($.localStorage('iv-status'));
				$.each(ivStatus, function(k, v) {
					$(document).trigger('ivStatusChange', [k, v]);
				})
			} catch (e) {}
		}, 100);
	});


	/****** 辅助功能区 end  *****/

	

	/***********************  dom 就绪事件  ***********************************/

	$.domReady(function($el) {

		var $document = this;


		// 初始化事件请在此处编写
		'initQtip' in $ && $.initQtip();

		var $doc = $(document),
			$window = $(window),
			w_height = $window.height();

		// lazy-load
		$el('[lazy-src]').each(function(){
			var $this = $(this),
				fn = function(){
					var offset = $this.offset().top;
					if( $doc.scrollTop() + w_height + 20 > offset){
						$this.attr('src',$this.attr('lazy-src')).removeAttr('lazy-src');
						$window.off('scroll',fn);
					}
				};
			$window.on('scroll',fn);
		});

		$window.trigger('scroll');

		// slide指令
		$el("[slide-toggle]").each(function() {
			var $this = $(this);
			$(this).on('click', function() {
				var $target;
				// console.log("$target = "+$this.attr("slide-toggle"));
				eval("$target = " + $(this).attr("slide-toggle"));
				$target.toggle();
			});
		});


		// 显示隐藏  计划废弃
		$el("[status-show]").each(function() {
			var $this = $(this),
				key = $this.attr('status-show');
			$.ivStatusOn(key, function(s) {
				$this.toggle(s);
			});
		});
		$el("[status-hide]").each(function() {
			var $this = $(this),
				key = $this.attr('status-hide');
			$.ivStatusOn(key, function(s) {
				$this.toggle(!s);
			});
		});

		// 美图
		$el(".iv-select-img-lib").on('click','.icon-kebianji',function(){
			var src = $.meituUrl($(this).closest('.iv-select-img-lib-item').find('img').attr('src'));
			window.open(src);
		});

		// main-tab
		$el(".main-tab").on('change','input',function(){
			var $form = $(this).closest('form');
			$form.trigger('submit');
		});

		// 根据status切换class  计划废弃
		$el("[status-class][status]").each(function() {
			var $this = $(this),
				param,
				key = $this.attr("status");
			try {
				eval("param = " + $this.attr("status-class"));
			} catch (e) {
				eval("param = '" + $this.attr("status-class") + "'");
			}
			// 判断param是否为数组
			if (Array.prototype.isPrototypeOf(param)) {
				// 0 表示removeClass，1表示addClass
				$.ivStatusOn(key, function(val) {
					$this._switchClass(param, !val);
				});
			} else if (typeof param === 'object') {
				$.ivStatusOn(key, function(val) {
					$.each(param, function(k, v) {
						if (val == k) {
							$this.addClass(v)
						} else {
							$this.removeClass(v)
						}
					});
				});
				// 是json
			} else {
				// 字符串
				$.ivStatusOn(key, function(val) {
					$this._switchClass([param, ''], val);
				});
			}

		});

		/**
		 * ivIpts = "string"
		 * data-url
		 */
		$el("[iv-tips]").on('click',function(){
			var $this = $(this),
			show = function(html){
				// console.log(html)
				$this.showTips(html).then(function($tip){
					$this.trigger('ivTips',[$tip]);
					$this._else('click',function(){
						$this.showTips(false);
					});
				});
			};
			if($this.data('url')){
				$.get($this.data('url')).done(show);
			}else{
				show($this.attr('iv-tips'));
			};
		});


		// target=_modal指令
		var _open = function(e){
			var $this = $(this),o = {
				url:$this.attr('action') || $this.attr('href') || $this.attr('url') || '',
				method:$this.attr('method') || 'get',
				data:$this.getParams(['param']).param || $this.serialize() || $this.serializeObject() || {},
				btn:{
					resolve:$this.attr('btn-resolve') !== 'false',
					reject:$this.attr('btn-reject') !== 'false'
				},
				inside:$this.getParams(['modalOptionInside']).modalOptionInside,
				title:$this.attr('title'),
				target:$this.attr('target') || '_blank'
			};
			console.log($this.attr('btn-resolve'),o);
			if($this.attr('disabled') !== undefined){
				return;
			}
			if(['_blank'].indexOf(o.target) === -1){
				e.preventDefault();
				if(o.method.toLowerCase() == 'get'){
					o.data = typeof o.data === 'object' ? $.param(o.data):o.data;
					if(o.data){
						o.url += '?' + o.data;
						o.data = {};
					}
				}
				$this.open(o);
			}
		};

		$el('a[target]').on('click',_open);
		$el('form[target]').on('submit',_open);


		$el('.add-column[data-id]').on('click',function(){
			var $this = $(this);
			$this.addFormElement($this.data('id'),8,function(){
				$this.getModal().resize();
			});
		});

		$el("#sel_ensogo_site").on('change', function() {
			$el(".level-link-dropdown").html('').off('click');
			var site_id = $(this).val();
			$el(".level-link-dropdown").registerPlugin('LevelLinkDropdown', function(LevelLinkDropdown) {
				var $this = $(this),
					param = {
						name: $this.attr('name'),
						level: $this.attr('level') || 2,
						url: $this.attr('url') + '?site_id=' + site_id,
						paramKey: $this.attr('paramKey'),
						method: $this.attr('method') || 'get',
						view: $($this.attr('view'))
					};
				$(this).LevelLinkDropdown(param);
			});

		});

		// 关闭模态框
		// $el('.modal-close').on('click', function() {
		// 	$document.close();
		// });

		/********* 指令区 end *********/

		// option onclick事件
		$el("option[onclick]").closest('select').on('change', function() {
			var $this = $(this).find(":selected"),
				foo;
			eval("foo = function(){" + $this.attr('onclick') + "};");
			eval($this.attr('onclick'));
		});
		$el("select[redirect-location][name]").on('change', function() {
			var $this = $(this),
				query = {};
			query[$this.attr('name')] = $this.val();
			location.href = $.location.query(query);
		});


		// 复制到剪切板
		$el("[data-clipboard-text]").registerPlugin('Clipboard',function(){
			var cli;
			$(this).hover(function(){
				cli = new Clipboard(this);
				cli.on('success',function(){
					$.alertBox('已复制到剪切板');
				});
			},function(){
				cli.listener.destroy();
			})
		},'Clipboard');

		setTimeout(function() {
			// top_nav控件
			$el(".top_nav div.dropdownlist").each(function() {
				var $this = $(this),
					$ul = $(this).find('ul'),
					$a = $(this).children("a"),
					href = $a.attr('href'),
					$target,
					position = $this.offset(),
					_pos = {};

				$ul.appendTo("body"); // 先重新定义下拉菜单的定位以实现宽度不被父元素影响而自适应
				$target = $this;
				if ($ul.hasClass('align-right')) {
					_pos = {
						left: parseInt(position.left) + $this.width() - $ul.innerWidth()
					}
				} else {
					_pos = {
						left: position.left
					}
				}
				if ($this.hasClass('info')) {
					_pos = $.extend(_pos, {
						backgroundColor: '#fff'
					});
				}
				$ul
					.css($.extend({}, {
						top: position.top + $this.height(),
						backgroundColor: $this.css('background-color'),
						color: $this.css('color')
					}, _pos))
				$ul.find("li")
					.on('click', function() {
						console.log('click')
						var $li = $(this),
							val;
						$this.find("a.select span").text($li.text());
						val = $li.attr('value');
						// console.log($this.find(":hidden"))
						$this.find(":hidden").val(val);
						$this.triggerHandler('change', val);
					});
				$.mousehover([$target, $ul], function(e) {
					$ul.show();
					$this.toggleClass('active');
				}, function() {
					$ul.hide();
					$this.removeClass('active');
				});
			});
		}, 500);
		// location超级链接
		var locationQuery = function() {
			var $this = $(this),
				data;
			eval('data = ' + $this.attr('location-query'));
			// location.href=
			location.href = $.location.query(data);
		};

		var locationHref = function() {
			var $this = $(this),
				url;
			eval('url = ' + $this.attr('location-href'));
			if (url !== '#')
				window.location.href = url;
		}


		$el("a[location-pathname]").on('click', function() {
			var $this = $(this),
				data;
			eval('data = ' + $this.attr('location'));
			location.href = $.location.pathName(data);
		});

		$el("a[location-query]").on('click', locationQuery);
		$el("select[location-query]").on('change', locationQuery);

		$el("a[location-href]").on('click', locationHref);
		$el("select[location-href]").on('change', locationHref);

		// location超级链接 end

		// ajax form
		$el("[ajax-form][action]").on('submit', function(e) {
			var $this = $(this);
			$.$ajax({
				url: $this.attr('action'),
				data: $this.serialize(),
				method: $this.attr('method'),
				success: function(result) {
					if ($this.attr('target')) {
						$($this.attr('target')).html(result);
					}
				},
				error: function($xhr, type, throwText) {
					// console.log(arguments)
					if ($this.attr('target')) {
						$($this.attr('target')).html($xhr.responseText);
					}
				},
				complete: function() {
					$this.trigger('ajaxForm', arguments);
				}
			});
			return false;
		});
		
		$el("[ajax-form] [type=submit][action]").on('click',function(){
			var $submit = $(this),
				$form = $(this).closest("[ajax-form]");
			$submit.$ajax({
				url:$submit.attr('action'),
				method:$submit.attr('method') || 'get',
				data:$form.serializeObject()
			}).then(function(){
				$submit.trigger('ajaxForm.done',arguments);
			},function(){
				$submit.trigger('ajaxForm.fail',arguments);
			});
		});

		// tab页
		$el("[tab-show]").on('click', function(e) {
			e.preventDefault();
			var id = $(this).attr('tab-id'),
				name = $(this).attr('tab-show');
			$(this).addClass('active').siblings().removeClass('active');
			$el("[tab-view=" + name + "][tab-id]").removeClass('active');
			$el("[tab-view=" + name + "][tab-id=" + id + "]").addClass('active');
			$el("[tab-value=" + name + "]").val(id);
		});
		$el("[tab-view]").each(function(idx) {
			var $this = $(this);
			if (!idx) {
				$this.addClass('active');
				$el("[tab-show=" + $this.attr('tab-view') + "][tab-id=" + $this.attr('tab-id') + "]").addClass('active');
			}
		});
		// tab页 end

		// include
		$el("[iv-include]").each(function() {
			var $this = $(this);
			var ajax = $.get($this.attr('iv-include'));
			ajax.success(function(html) {
				$this.html(html);
				$this.trigger('ready', [html]);
				$.modalReady($this, ajax);
			});
		});
		// include end

		// qq交谈折叠
		$el(".about_qq").each(function() {
			var $this = $(this),
				view = function(status) {
					$this.find(".cert")._switchClass(['right', 'left'], status);
				};
			$this.on('click', '.slide-toggle', function() {
				var status = $.ivStatus('qqtalk');
				$.ivStatus('qqtalk', !status);
				view(status);
			});
			view(!$.ivStatus('qqtalk'));
		});

		// $el('.iv-select').registerPlugin('IvSelect',function(){
		// 	var $select = $(this).ivSelect();

		// 	console.log($select);
		// });

		// table选中行
		$el(".table-default tr td.checktd input:checkbox").on('change', function() {
			var $this = $(this),
				$tr = $this.closest('tr');
			$tr.toggleClass('active', $this.is(":checked"));
		});

		// 全选
		$el("[check-all]").each(function() {
			var name = $(this).attr('check-all');
			$.checkAll(name, $document);
		});
		// 全选end

		// tableGroup 折叠
		$el(".table-group .iconfont").on('click', function() {
			$(this).closest('tr').nextUntil(".title").toggle();
		});

		// select样式
		$el("select.iv-input").each(function() {
			var $this = $(this).wrap("<div class='iv-select'></div>"),
				$wrap = $this.parent(),
				classNames = $this.attr('class').split(' ');
			$this.removeAttr('class');
			for (var i in classNames) {
				$wrap.addClass(classNames[i]);
			}
			$this.select2({
				minimumResultsForSearch: Infinity,
				placeholder: $this.attr('placeholder')
			});
		});


		// form辅助
		$el(".iv-form").each(function() {
			var $form = $(this),
				$label = $form.find("[required]").closest('.form-group').find("label.row");
			if (!$label.find("i").size())
				$label.append("<i>*</i>");
		});

		// datetime
		$el("input.iv-input[type=date]").on('click', function(e) {
			if (navigator.userAgent.indexOf("Firefox")== -1){
				//20161219 Firefox 日期控件闪退问题
				e.preventDefault();
			}
		}).on('mouseenter', function(e) {
			if (navigator.userAgent.indexOf("Firefox")== -1){
				//20161219 Firefox 日期控件闪退问题
				var $this = $(this),
					$min = $this.parent().find("[name=" + $this.attr('min') + "]"),
					$max = $this.parent().find("[name=" + $this.attr('max') + "]");
				$this.datepicker("option", "maxDate", $max.size()? $max.val() : $this.attr('max'));
				$this.datepicker("option", "minDate", $min.size()? $min.val() : $this.attr('min'));
			}
		}).each(function() {
			var $this = $(this),
				val = $this.val(),
				format = $(this).attr('format') || 'yy-mm-dd',
				option = $.extend({
					changeMonth: true,
					changeYear: true,
					onClose: function(d) {
						$this.trigger("datepicker", d);
					}
				});

			$this
				.datepicker(option)
				.datepicker('option', 'dateFormat', format);
			$this.datepicker('setDate', val);
		});
		
		
		// upload
		$el(".iv-upload").each(function() {
			var $this = $(this);
			$this.on('change', "input[type=file]", function() {
				var $file = $(this);
				$this.find('.placeholder p').text('正在上传...');
				$file.attr('disabled', true);
				$.uploadOne({
					fileElementId: $file.attr('id'),
					onUploadSuccess: function(data) {
						$this.find('input[type=file]').removeAttr('disabled');
						$this.find('.choose').hide().find('.placeholder p').text('添加图片');
						$this.find('.view').css({
							backgroundImage: 'url(' + data.thumbnail + ')'
						}).attr('original', data.original).show();
						$this.find("input[type=hidden]").val(data.original);
						$this.find('.src').attr('title', data.original).text(data.original.split('/').pop());
					},
					onError: function(xhr, status, e) {
						$.alertBox('上传错误', 'error');
						console.error(status);
					}
				});
			});
			$this.find(".icon-shanchu").on('click', function() {
				$this.find('.choose').show();
				$this.find('.view').hide();
				$this.find("input[type=hidden]").val('');
				$this.find('.src').removeAttr('title').text('');
			});
		});

		// ajax分页
		$el("[role=ajax-page]").registerPlugin('AjaxPage',function(){
			var $this = $(this);
			$this.ajaxPage({
				perPage:$this.attr('perpage'),
				url:$this.attr('pageurl'),
				total:$this.attr('count'),
				$pageBar:$("[role=ajax-page-bar][pagename=" + $this.attr('pagename') + "]")
			}).onchange(function(){
				$document.resize();
			});
			// window.$page = $this.ajaxPage();

		});

		// trigger属性
		$("[trigger]").each(function() {
			var type = 'click',
				$target = $(this).getParams(['trigger']).trigger;
			$(this).on(type, function() {
				$target.trigger(type);
			});
		});

		$el(".sortable").sortable({
			cursor:"move",
			items:"li:not([iv-template])",
			cancel:".iv-image-box-cover2,li[iv-template]",
			revert:true
		});

		// 图片库
		$el(".iv-image-upload:not(.iv-v2)").registerPlugin('ImageLib',function(){
			$(this).imageLib();
		});

		// 新版图片库
		$el(".iv-image-lib").registerPlugin("SelectImageLib",function(ImageLib){
			var $this = $(this),
				data=[],
				option={
					primary:'main_image'
				};
			$this.children().each(function(){
				var self = $(this);
				data.push({
					src:self.attr('image'),
					thumb:self.attr('thumb')
				});
				if(self.attr('primary')!==undefined){
					option.primary = {
						name:'main_image',
						val:self.attr('image')
					};
				}
			}).remove();
			var imgLib = new ImageLib(this,data,$.extend({
				modules:[
					'iv-local-upload',
					'iv-online-url',
					'iv-lb-lib',
					'meitu',
					'copy-url',
					'remove',
					'primary',
				]
			},option));

			console.log(imgLib);
		});

		// 商品通用同步
		$el("[role=sync-product]").registerPlugin('LBSyncProducts',function(Sync){
			new Sync(this);
		});

		$.upload = function(file){
			var url = '/util/image/upload',
				name = 'product_photo_file',
				formData = new FormData();
			formData.append(name,file);
			return $.ajax({
				url:url,
				type:'POST',
				cache:false,
				data:formData,
				processData:false,
				contentType:false,
				dataType:'json'
			});
		};
		
		$.uploadByUrl = function(file,url){
			if(typeof url == "")
				url = '/util/image/upload';
			var name = 'product_photo_file',
				formData = new FormData();
			formData.append(name,file);
			return $.ajax({
				url:url,
				type:'POST',
				cache:false,
				data:formData,
				processData:false,
				contentType:false,
				dataType:'json'
			});
		};

		// 自定义upload按钮
		$el(".btn-upload:not([iv-uploaded])").each(function(){
			var $btn = $(this),
				text = $btn.attr('value'),
				cssNames = $btn.attr('class').split(' ').remove('btn-upload').join(' '),
				$a = $('<a class="btn-upload-text '+cssNames+'">'+text+'</a>');
			$btn.attr('iv-uploaded','iv-uploaded').wrap($a);
			// dzt20170804 本来是抓取'change input' 事件的，然后发现Firefox是必然触发两次回调，chrome问题不大偶尔有一次重复，所以改成只抓change事件
			$btn.on('change',function(e){
				var uploadQueue = [],files = this.files;
				if(files.length < 1) return false;
				Array.prototype.forEach.call(files,function(file){ 			// 把选择的图片都加入上传队列中
					uploadQueue.push(function(){
						if(typeof $btn.data('url') != "undefined" && "" != $btn.data('url'))
							return $.uploadByUrl(file,$btn.data('url'));
						else
							return $.upload(file);
					});
				});
				$.showLoading();
				$btn.trigger('iv-upload.start',[files,uploadQueue]);
				$.asyncQueue(uploadQueue,function(idx,res){			// 执行队列并绑定事件到jq
					$btn.trigger('iv-upload',[res]);
				}).then(function(res){
					$btn.trigger('iv-upload-done',[res]);
					$.hideLoading();
				});
			});
		});

		// category-group
		$el('.filter-group').on('click','a',function(){
			var $this = $(this),
				$li = $this.closest('li'),
				isChecked = $li.hasClass('active');
			$li.toggleClass('active',!isChecked);
		}).on('change','input',function(){
			var $checkBox = $(this),
				$ul = $checkBox.closest('.group-items'),
				isChecked = $checkBox.is(":checked");
			$ul.find('input:checked').closest('li').addClass('active');
			$ul.find('input:not(:checked)').closest('li').removeClass('active');
			// $li.toggleClass('active',isChecked);
		}).on('click','.toggle-more',function(){
			$(this).closest('.group-items').toggleClass('more');
		});


		// 图片库信息刷新
		$el(".iv-select-img-lib").on('click','.check',function(){
			$(this).next().prop('checked',$(this).prop('checked'));
		}).on('click','.iv-select-img-lib-body .icon-shanchu',function(){ 		// 删除单个图片
			var $this = $(this);
			$.confirmBox('确定要删除？').then(function(){
				var $root = $this.closest('.iv-select-img-lib'),
					id = $this.closest('.iv-select-img-lib-item').data('id');
				$root.find('ul').ajaxPage().remove([id]);
			});
		}).on('click','.iv-select-img-lib-batch-del',function(){ 		// 批量删除
			var $root = $(this).closest('.iv-select-img-lib');
			$.confirmBox('确定要删除？').then(function(){
				var ids = [];
				$root.find(".iv-select-img-lib-body [role=ajax-page-key]:checked").each(function(){
					ids.push( $(this).closest('.iv-select-img-lib-item').data('id') );
				});
				$root.find('ul').ajaxPage().remove(ids);
			});
		}).on('click','.chooseTreeName',function(){ 		// 图片库图片选择重新查询
			$groupid=$(this).attr('data-groupid');
			$('.select-image-re').html('');
		
			$el("[role=ajax-page]").registerPlugin('AjaxPage',function(){
				var $this = $(this);
				$this.ajaxPage({
					perPage:$this.attr('perpage'),
					url:'/util/image/select-image-lib-list?classification='+$groupid, 
					total:$this.attr('count'),
					$pageBar:$("[role=ajax-page-bar]"),
					ajaxpage1:'_ajaxPage1',
				}).onchange(function(){
					$document.resize();
				});
			});
			
			$el("div").removeClass('bgColor');
			if($groupid!=0)
				$el("li[groupid="+$groupid+"]").children().eq(0).addClass('bgColor');			
		}).on('click','.gly',function(){ 		// 图片库图片类型三角形的操作
			if($(this).attr('data-isleaf')=='open'){
				$(this).removeClass('glyphicon-triangle-bottom');
				$(this).addClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','close');
//				$('ul').find('[data-cid=0]').css('display','none');
				$(this).parent().next().css('display','none');
			}
			else{
				$(this).addClass('glyphicon-triangle-bottom');
				$(this).removeClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','open');
//				$('ul').find('[data-cid=0]').css('display','block');
				$(this).parent().next().css('display','block');
			}
		}).on('click','.iv-select-item-footer .icon-link',function(){ 		// 复制链接
			var $item = $(this).closest('.iv-select-img-lib-item');
			$item.find(".iv-select-item-cover").animate({
				top:0
			},500);
		}).on('click','.iv-select-item-cover .icon-guanbi',function(){
			$(this).parent().animate({
				top:120
			},200);
		}).find('ul').on('ajaxPage.change',function(){
			var $rt = $el(".iv-select-img-lib")
			$.get('/util/image/get-lib-info',{},function(info){
				// var toSize = function(str){
				// 	return parseInt((info.totalSize - info.usedSize) / 1024 /1024);
				// };
				$rt.find(".data-img-lib-totalSize").text(parseInt(info.totalSize / 1024 / 1024));
				$rt.find(".data-img-lib-leftSize").text(parseInt((info.totalSize - info.usedSize) / 1024 /1024));
				$rt.find(".data-img-lib-usedSize").text(parseInt(info.usedSize / 1024 / 1024));
				$rt.find(".data-img-lib-count").text(info.count);
			});
		});

		// $el(".iv-select-img-lib").registerPlugin('SelectImageLib',function(){
		// 	$(this).selectImageLib();
		// });

		// 选择标签
		// $el('.select-tags input').on('change', function() {
		// 	var checked = $(this).is(":checked");
		// 	$(this).parent().toggleClass('active', checked)
		// });
		
		// kindeditor
		$el("textarea.iv-editor").registerPlugin('KindEditor', function(KE) {
			var self = this,
				options = {
					minWidth: 400,
					afterBlur: function() {
						$self.val(editor.html());
						this.sync();
					},
					items: [
						'source', '|', 'undo', 'redo', '|', 'preview', 'cut', 'copy', 'paste',
						'plainpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
						'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'selectall', '|', '/',
						'formatblock', 'fontname', 'fontsize', '|', 'imglib', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', 'moreImage', '|', 'link', 'unlink'
					],
					uploadJson: '/util/image/upload',
					filePostName: 'product_photo_file'
				};
			var $self = $(self),
				_options = $.extend({}, options,
					$self.getParams([
						'resizeType', 'items','filterMode','wellFormatMode'
					])
				),
				editor = KE.create(self, _options);
				// console.log(_options);
			$self.trigger('editor.ready', [editor, KE]);
		}, true);

		// highcharts
		$el(".charts[data-url][type]").registerPlugin('Highcharts', function(Highcharts) {
			var $this = $(this);
			$.get($this.data('url'), function(data) {
				var categories = [],
					series = [];

				$.each(data, function(k, item) {
					var s = {
						name: item.name,
						data: []
					};
					$.each(item.data, function(k, v) {
						if (!(categories.indexOf(k) >= 0)) {
							categories.push(k);
						}
						s.data.push(v);
					});
					series.push(s);
				});
				var hc = $this.highcharts({
					chart: {
						type: $this.attr('type') || 'line'
					},
					title: {
						text: $this.attr('title')
					},
					subtitle: {
						text: $this.attr('subtitle')
					},
					xAxis: {
						categories: categories,
						crosshair: true
					},
					yAxis: {
						min: 0,
						title: {
							text: $this.attr('y-axis')
						}
					},
					series: series
				});
				$this.trigger('Highcharts.ready', [$this.highcharts()]);
			});

		});

		// 数据模型
		// $el("[iv-model-controller]").registerPlugin('IvModel',function(IvModel){
		// 	var $this = $(this);
		// 	$.require($this.attr('iv-model-controller'),function(){
		// 		IvModel.$trigger('ready',[$this]);
		// 	});
		// },'Model',1);

	});

	$(function() {
		$.modalReady($(document));

		// 左侧菜单
		$(".menu_v2").on('click','a[target=_state],a[target=_norefresh]',function(){
			var $this = $(this);
			$(".menu_v2").find('a.active').removeClass('active');
			$this.addClass('active');
		});

		// top_nav自动菜单排序
		(function($nav) {
			// return false;
			var status = $.ivStatus('topNavStatus') || {};
			$nav.find('li').each(function() {
				var $li = $(this);
				if ($li.find('.cert').size()) { // 对含有下拉菜单的进行检索
					var data = [],
						pLabel = $li.find('a').nodeText();
					$li.find('ul li').each(function() {
						var $a = $(this).find('a'),
							label = $a.nodeText();
						// data[label] = $a.attr('href');
						if ($a.attr('selected')) {
							status[pLabel] = label;
						}
					});
					$li.find('ul li').each(function() {
						var $a = $(this).find('a'),
							label = $a.nodeText();
						// 判断status中，进行排序
						if (label == status[pLabel]) {

							$li.find('>div>a')
								.attr('href', $a.attr('href'))
								.nodeText(0, label);
							$a.closest('li').remove();
						}
					});
				}
			});
			$.ivStatus('topNavStatus', status);
		})($("nav ul.flex-row").eq(0));
		// top_nav自动菜单排序 end

	
		// 头部的小铃铛CMS新消息提醒
		('qtip' in $.fn) && $('.sys_new span').qtip({
			content:{
				text: function(event,api){
					$.ajax({
						url: '/ajax/ajax-announce-list',
						dataType:'json',	
						data:''
					}).then(function(data){
						if(data['success'] == true){
							
							var spec_str = 'href="http://www.littleboss.com/announce_info_value_id.html"';
							
							var $str = '';
							var str_2 = '';
							$str += '<ul>';	
							for(var k in data['data']){
								if(data['data'].hasOwnProperty(k)){
									str_2 = spec_str.replace('value_id', data['data'][k]['id']);
									$str += '<li><a target="_blank" '+ str_2 +'><span>'+data['data'][k]['time']+' </span>'+ data['data'][k]['title']+'</a></li>';
								}
							}
							
							$str += '</ul>';
					
							api.set('content.text',$str);
						}
						 
					},function(xhr,status,error){
						console.log(status);
						console.log(xhr);
						console.log(error);
						api.set('content.text',status + ':' + error);
						api.set('style.width',420);
					});

					return 'Loading...';
				}
			},
			hide:{
				fixed: true,
				delay: 300
			},
			position:{
				at:'bottomMiddle',
				my:'topMiddle',
				viewport:$(window)
			},
			style: {
				classes:'announce_list',
				def:false,
				width:420,
			}
		});
		// 头部的小铃铛CMS新消息提醒
	});

	//消息中心弹窗，lrq20170717
	$(document).on('click','.show_notification' , function(){
		var obj = $(this);
		var id = obj.attr('value');
		
		$.ajax({
			url: '/ajax/ajax-notification-one',
			dataType: 'json',
			data: {id:id}
		}).then(function(res){
			if(res['success'] == true){
				$('.announce_list').css('display', 'none');
				var style_html = '<style>.notification_dialog .modal-dialog{width:60%} .notification_dialog p{margin:0 0 10px; font-size: 14px; font: initial; } .notification_dialog div{font: initial; } /*.notification_dialog img{max-width: 100%; }*/ .notification_dialog .modal-body{max-height: 600px; overflow-y: auto;}</style>';
				
				//标记为已读
				$.ajax({
					url: '/prestashop/message-management/set-readed',
					dataType: 'json',
					data: {noti_id: id},
				}).then(function(res){
					if(res['success'] == true){
						if(res['Count'] != undefined){
							$('.message_unread_count').html(res['Count']);
							$(obj).parent().parent().find('.eye').css('display', 'none');
						}
					}
				});
				
				bootbox.dialog({
					backdrop: true,
					className : "notification_dialog",
					title: res['title'],
					message: style_html + res['html'],
					buttons:{
						OK: {  
							label: Translator.t("关闭"),  
							className: "btn-primary",  
							callback: function () {
								
							}  
						}, 
					},
				});
				
				$('.notification_dialog .modal-footer').append('<span style="float: left; ">时间：'+ res['create_time'] +'<span>');
				$('.notification_dialog .modal-footer button').css('margin-left', '-150px');
			}
		});
	});
	
	//所有消息标记为已读，lrq20170717
	$(document).on('click','.notification_selAllReaded' , function(){
		$.ajax({
			url: '/prestashop/message-management/set-all-readed',
			dataType: 'json',
			data: {}
		}).then(function(res){
			$('.message_unread_count').html('0');
		});
	});
	
	//erp超额信息弹窗，lrq20170907
	$(document).on('click','.check_erp_vip_info' , function(){
		$.ajax({
			url: '/prestashop/message-management/check-erp-vip-info',
			dataType: 'json',
			data: {}
		}).then(function(res){
			if(res.success == true){
				table_html = "<style>#erp_vip_out_table td{border:1px solid #ccc; text-align: center;} #erp_vip_out_table th{text-align: center;}</style>" +
						"<table class='table' id='erp_vip_out_table'>" +
						"<tr>" +
							"<th>项目</th>" +
							"<th>免费额度</th>" +
							"<th>在用额度</th>" +
						"</tr>";
				$.each(res.data, function(n, match){
					table_html += "<tr>" +
								"<td>"+n+"</td>" +
								"<td>"+match.free_count+"</td>" +
								"<td>"+match.count+"</td>" +
							"</tr>";
				});
				table_html += "</table>";
				
				bootbox.dialog({
					title: Translator.t("已超免费额度信息"),
					className: "check_erp_vip_dialog",
					message: table_html,
					buttons: {
						OK: {
							label: Translator.t("确定"),
							className: "btn-success",
							callback: function(){
								//关闭窗体
								return true;
							}
						},
					}
				});
			}
		});
	});
	
})(jQuery);


