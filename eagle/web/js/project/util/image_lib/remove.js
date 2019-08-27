(function(){

	var remove = function(ImageLib){
		var self = this;
		this.btnStr = '<a title="删除" class="iv-image-box-footer-icon iconfont icon-shanchu"></a>';

		ImageLib.on('ready',function(){
			ImageLib.m('base').on('addOne',function($li,data){
				var $btn = $(self.btnStr);
				$li.find('.iv-image-box-footer').$append($btn);

				$btn.on('click',function(){
					$.confirmBox('确定要删除？').then(function(){
						ImageLib.m('base').remove($li);
					});
				});
			});
		});
	};

	window.remove = remove;

})();