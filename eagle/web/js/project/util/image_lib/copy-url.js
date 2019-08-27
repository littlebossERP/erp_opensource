(function(){

	var copyUrl = function(ImageLib){
		var self = this;
		this.btnStr = '<a title="复制链接" class="iv-image-box-footer-icon iconfont icon-link"></a>';

		ImageLib.on('ready',function(){
			ImageLib.m('base').on('addOne',function($li,data){
				var $btn = $(self.btnStr);
				$li.find('.iv-image-box-footer').$append($btn);

				$btn.on('click',function(){
					$(this).closest('.iv-image-box').find('.iv-image-box-cover,.iv-image-box-cover2').animate({
						top:0
					},300);
				});

				// // 关闭层
				$li.find('.cover-close').on('click',function(){
					$(this).closest('.iv-image-box').find('.iv-image-box-cover,.iv-image-box-cover2').animate({
						top:120
					},200);
				});

			});

		});


	};



	window.copyUrl = copyUrl;

})();