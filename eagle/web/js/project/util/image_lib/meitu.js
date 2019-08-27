(function(){

	var meitu = function(ImageLib){
		var self = this;
		this.btnStr = '<a title="在线美图" class="iv-image-box-footer-icon iconfont icon-kebianji"></a>';

		ImageLib.on('ready',function(){
			ImageLib.m('base').on('addOne',function($li,data){
				var $btn = $(self.btnStr);
				$li.find('.iv-image-box-footer').$append($btn);
				$btn.on('click',function(){
					var src = $(this).parent().prev().find('img').attr('src');
					window.open($.meituUrl(src));
				});
			});
		});
	};

	window.meitu = meitu;

})();