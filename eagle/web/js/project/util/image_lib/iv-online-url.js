(function(){


	var ivOnlineUrl = function(ImageLib){


		var btn = '<a title="从网络地址（URL）选择图片" class="iv-btn btn-success" >从网络地址（URL）选择图片</a>';

		ImageLib.on('ready',function(){
			var $btn = ImageLib.createBtn(btn);
			$btn.on('click',function(){
				$.modal('<a class="add-column text-info input-area"style="display:block;margin:10px 0px;width:100%;"><i class="iconfont icon-jiahao2"></i>添加一个新URL地址</a>','从网络地址（URL）选择图片').then(function($modal){
					$modal
						.on('modal.action.resolve',function(e,$modal,data){
							data.url.forEach(function(src){
								ImageLib.m('base').addOne(src);
							});
							$modal.close();
						})
						.find(".modal-body a")
						.on('click',function(){
							$modal.find('.modal-body').loadTmpl('/tmpl/ImageLibOnline').then(function(){
								$modal.resize();
							});
						}).click();
				});
			});
		});
	};

	window.ivOnlineUrl = ivOnlineUrl;

})();