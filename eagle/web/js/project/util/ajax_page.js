(function($){
	'use strict';

	/**
	 * ajax分页
	 * @author hqf
	 * @version 2016-05-18
	 * @param {option} perPage,url,$pageBar
	 */
	$.fn.ajaxPage = function(option){
		if(option.ajaxpage1==null){
			var temp_ajaxpage1='_ajaxPage';
		}
		else{  //图片库让它每次都进入
			var temp_ajaxpage1=option.ajaxpage1;
		}
		if(!(String(temp_ajaxpage1) in this[0])){       
			var self = this[0],
				$self = $(self),
				_total,
				_page = 1,
				$prev = $('<a class="ajax-page-bar-prev iconfont icon-shangyiye"></a>'),
				$next = $('<a class="ajax-page-bar-next iconfont icon-xiayiye"></a>'),
				$more = $('<span class="ajax-page-bar-more">...</span>'),
				$getItem = function(id){
					return $.promise(function(resolve){
						var $item = $self.children("[data-id="+id+"]");
						if(!$item.size()){
							load(id,1).then(function(_item){
								$.modalReady($(_item[0]));
								resolve(_item[0]);
							});
						}else{
							resolve($item[0]);
						}
					});
				},
				_remove = function($item){
					_total--;
					var id = parseInt($item.data('id'));
					$item.remove();
					$self.children().each(function(){
						var $this = $(this),
							i = parseInt($this.data('id'));
						if(i > id){
							$this.attr('data-id',i-1).data('id',i-1);
						}
					});
				},
				$removeItem = function(ids){
					var queue = [];
					ids.forEach(function(id,i){
						(function(id){
							queue.push(function(){
								return $.promise(function(resolve){
									$getItem(id).then(function(item){
										// 掉接口
										$.post(option.url,{
											id:$(item).find('[name=id]').val()
										},function(res){
											if(res.success){
												_remove($(item));
												// $(item).remove();
												// _total--;
											}else{
												$.alertBox('操作失败');
											}
											resolve();
										});
									});
								});
							});
						})(id);
					});
					return $.asyncQueue(queue).then(function(){
						showPage(_page);
					});
				},
				// $cover = $("<div class='cover' style='width:100%;height:100%;position:absolute;top:0;left:0;background-color:white;z-index:1;'>加载中...</div>"),
				load = function(start,limit){
					return $.promise(function(resolve,reject){
						$.get(option.url,{ 		// 加载页面
							start:start,
							limit:limit
						},function(response){
							_total = response.total;
							var $items = $(response.html);
							$items.each(function(){
								appendItem($(this),start++);
							});
							resolve($items);
						});
					}); 
				},
				showPage = function(page){
					// 先列出当前页码的items
					var _loadQueue = [],
						end = page * option.perPage,
						$items = [];
					option.$pageBar.attr('disabled',true); 	// 锁定页码
					return $.promise(function(resolve){
						$self.children().hide();
						$self.trigger('ajaxPage.change',[])
						end = end > _total ? _total : end;
						for(var i = (page - 1) * option.perPage; i < end; i++){
							(function(i){
								_loadQueue.push(function(){
									return $getItem(i);
								});
							})(i);
						}
						$.asyncQueue(_loadQueue,function(idx,item){
							$items.push(item);
						}).then(function(){
							$items.forEach(function(item,idx){
								$(item).show();
							});
							_page = page;
							$self.trigger('ajaxPage.change',[]);
							renderPageBar();
						});
					});
				},
				appendItem = function($item,id){
					$item.attr('data-id',id).hide().appendTo(self); 	// 给每个item加编号
				},
				renderPageBar = function(){
					option.$pageBar.empty();
					// 计算页数
					var pageCount = Math.ceil(_total / option.perPage),
						hasMore = false;
					option.$pageBar.append($prev);
					for(var i = 1; i<= pageCount; i++){
						var $page = $('<a data-page="'+i+'" class="ajax-page-bar-num">'+i+'</a>');
						option.$pageBar.append($page);
						if(i==_page){
							$page.addClass('ajax-page-bar-current');
						}else{
							// 显示...
							if(Math.abs(i-_page)>3 && i != pageCount && i != 1){
								$page.hide();
								if(!hasMore){
									option.$pageBar.append($more.clone());
									hasMore = true;
								}
							}else{
								hasMore = false;
							}
							// 绑定事件
							$page.on('click',function(e){
								var $this = $(this);
								if(!option.$pageBar.attr('disabled')){
									showPage($this.data('page'));
								}
							});
						}
					}
					option.$pageBar.removeAttr('disabled').append($next); 		// 解锁页码
					// 绑定prev和next事件
					_page == 1 ? $prev.addClass('ajax-page-bar-current') : $prev.removeClass('ajax-page-bar-current').on('click',function(){
						showPage(_page - 1);
					});
					_page == pageCount ? $next.addClass('ajax-page-bar-current') : $next.removeClass('ajax-page-bar-current').on('click',function(){
						showPage(_page + 1);
					});
				};
			$self.css({
				position:'relative'
			});
			self._ajaxPage = {
				load:load,
				remove:$removeItem,
				toPage:showPage,
				onchange:function(fn){
					// 刷新翻页
					$self.on('ajaxPage.change',fn);
				}
			};
			showPage(1);
		}
		return this[0]._ajaxPage;
	}

})(jQuery);