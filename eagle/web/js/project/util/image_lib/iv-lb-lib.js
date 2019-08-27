(function(){
	window.ivLbLib = function(ImageLib){
		var btn = '<a target="_modal" title="图片库" class="iv-btn btn-success" href="/util/image/select-image-lib" btn-resolve btn-reject>从图片库选择图片</a>';
		ImageLib.on('ready',function(){
			ImageLib.createBtn(btn).on('modal.submit',function(e,data){
				if(data.img && data.img.length>0)
					ImageLib.toAdd = data.img.length;
				('img' in data) && data.img.forEach(function(src){
					// dzt20170110 图片库的图片可通过拼接获取缩略图
					ImageLib.m('base').addOne(src,src + '?imageView2/1/w/210/h/210');
				});
				if(ImageLib.toAdd < data.img.length)
					$.alertBox('成功添加'+(data.img.length - ImageLib.toAdd)+'张图片');
				
			});
		});
	};
})();