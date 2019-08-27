(function(){

	var ivLocalUpload = function(ImageLib){

		var btn = '<input type="file" class="iv-btn btn-success btn-upload" multiple="multiple" value="从本地选择图片" />';

		ImageLib.on('ready',function(){

			var $btn = ImageLib.createBtn(btn);
			
			// dzt20170824 for自建站修改本地上传图片上传到的图片库 
			// 支持10张图片并第一张为首图的时候 忘了改本地上传图片的链接
			if(ImageLib.$option.postLocalFileUrl){
				$btn.data('url',ImageLib.$option.postLocalFileUrl);
			}
			try{
				$btn.on('iv-upload',function(e,res){
					res.status ? ImageLib.m('base').addOne(res.data.original,res.data.thumbnail) : $.alertBox(res.rtnMsg);
				});
			}catch(e){
				$.modal('上传失败，服务器错误，请联系管理员');
			}

		});

	};



	window.ivLocalUpload = ivLocalUpload;

})();