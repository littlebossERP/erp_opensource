(function(){

	window.copyLib = function(ImageLib){
		var self = this;	
		this.btnStr = '<a title="复制产品图片" class="iv-btn btn-default">复制产品图片</a>';
		ImageLib.on('ready',function(){
			ImageLib.createBtn(self.btnStr).on('click',function(){
				var imgs = $(this).closest('.product-image-lib').find('form.product-image-list').serializeArray();
				for(var i=0,len = imgs.length;i<len; i++){
					var thumb = imgs[i].value + '?imageView2/1/w/210/h/210';
					ImageLib.m('base').addOne(imgs[i].value,thumb);
				}
			});
		});
	}
})();