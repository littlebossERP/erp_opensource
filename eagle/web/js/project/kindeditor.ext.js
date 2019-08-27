KindEditor.plugin('imglib', function(K) {
    var editor = this, name = 'imglib';
    // 点击图标时执行
    editor.clickToolbar(name, function() {
    	$.openModal('/util/image/select-image-lib',{},'图片库', 'get', null,{}, {
    		resolve:true,
    		reject:true
    	}).then(function($modal){
    		$modal.on('modal.then',function(e,data){
    			$.each(data.img,function(idx,src){
	    			editor.insertHtml('<img src="'+src+'" />');
    			});
    		});
    	});
    });
    editor.lang({
	    'imglib' : '从图片库选择'
    });
});
