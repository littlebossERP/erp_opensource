(function(window,$){
	'use strict';

	$.fn.uploadImage = function(){
	};

	// 耦合性比较强
	$.fn.imageLib = function(){
		return $(this).each(function(){

			var $root = $(this),
				id = $root.find('ul').attr('id'),
				maxLength = $root.find('ul').data('max') || false,
				addOne = function(src,title,thumb){
					// console.log(maxLength-size)
					return $.promise(function(resolve,reject){
						$.addFormElement("iv-image-upload-"+id, maxLength).then(function($ele){
							$ele[0].find("img").attr('src',thumb || src).attr('title',title || '');
							$ele[0].find('.image-src').text(src);
							$ele[0].find('.iv-image-box-header input[type=checkbox]').val(src).prop('chekced',true).attr('checked','checked');
							$ele[0].find('[name=main_image]').val(src);
							$ele[0].find('.copy-url').attr('data-clipboard-text',src);
							resolve($ele);
							$root.find('ul').sortable('refresh');
						},function(){
							$.alertBox('图片不能超过 '+maxLength+' 张');
							reject({
								error:400,
								error_message:'图片不能超过 '+maxLength+' 张'
							});
						});
					});
				};


			$root
			.on('a',function(e){
				e.preventDefault();
			})
			// 弹出遮罩层
			.on('click','.iv-image-box-footer .icon-link',function(){
				$(this).closest('.iv-image-box').find('.iv-image-box-cover,.iv-image-box-cover2').animate({
					top:0
				},300);
			})
			// 关闭层
			.on('click','.cover-close',function(){
				$(this).closest('.iv-image-box').find('.iv-image-box-cover,.iv-image-box-cover2').animate({
					top:120
				},200);
			})
			// 删除一张图片
			.on('click','.iv-image-box .icon-shanchu',function(){
				var $this = $(this);
				$.confirmBox('确定要删除?').then(function(){
					$this.closest('.iv-image-box').remove();
				});
			})
			// 批量删除
			.on('click','.iv-image-header .pull-left',function(){
				$.confirmBox('确定要删除选择的图片?').then(function(){
					$root.find('.iv-image-box-header input:checked').closest('li.iv-image-box').remove();
				});
			})
			// 在线美图
			.on('click','.icon-kebianji',function(){
				var src = $(this).parent().prev().find('img').attr('src');
				window.open($.meituUrl(src));
			});

			// 从网络URL选择图片按钮交互
			$root.find('.select-url').on('modal.submit',function(e,data,$modal){
				data.url.forEach(function(url){
					url && addOne(url);
				});
				// $modal.close();
			});

			// 从本地选择图片
			$root.find('.btn-upload').on('iv-upload',function(e,res){
				res.status ? addOne(res.data.original,res.name) : $.alertBox(res.rtnMsg);
			});

			// 从图片库选择
			$root.find('.select-lib')
				.on('modal.submit',function(e,data,$modal){
					var addQueue = [];
					if(typeof data.img != 'undefined'){
						data.img.forEach(function(url,i){
							addQueue.push(function(){
								// dzt20170116 图片库的图片可通过拼接获取缩略图
								return addOne(url,'',url + '?imageView2/1/w/210/h/210');
							});
						});
						$.asyncQueue(addQueue).done(function(){
							$modal.close();
						});
					}else{
						$.alertBox('请选择图片！');
					}
					// $modal.close();
				});



		});
	}

})(window,jQuery);
