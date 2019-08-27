// kindeditor 通过url 添加图片
if (typeof DecImg === 'undefined')  DecImg = new Object();
DecImg = {
	modal : "",
	editor : {},
	init: function () {
		KindEditor.plugin('moreImage',function(k){ //图片添加点击事件
			var editor = this,
				name = 'moreImage';
			
			editor.clickToolbar(name,function(){
				DecImg.addDecriptionPic(editor);
			});
		});
	},
	
	addDecriptionPic: function (editorPic) {
		DecImg.editor = editorPic;
        $.get( global.baseUrl+'js/lib/kindeditor/kindeditor-decimg.html', function (data){
			DecImg.modal = bootbox.dialog({
				title : Translator.t("添加图片描述"),
				className: "kindeditorDecImg",
				buttons: {  
					Cancel: {  
				        label: Translator.t("取消"),  
				        className: "btn-default btn-kindeditor-decimg-return",  
				    }, 
				    OK: {  
				        label: Translator.t("确定"),  
				        className: "btn-primary btn-kindeditor-decimg-create",  
			            callback: function () {  
			            	DecImg.showDecriptionPic();
			            	$(DecImg.modal).modal('hide');
			            	return false;
			            }  
			        }  
				},
			    message: data,
			});	
			
			$(".kindeditorDecImg > .modal-dialog").width(750);
        });

    },
    
    showDecriptionPic: function () {
        var imgHtml = '';
        // 设置 对齐和宽度
        var localPicWidth = $("#localPicWidth").val();
        var localPicAlign = $("input[name='localPicAlign']:checked").val();
        var width = $.trim(localPicWidth) != "" ? ' width="' + localPicWidth + '"' : '';

        // align: center 不起效，所以这里希望统一改水平 排位
//			if(localPicAlign == 'center'){
//				var align = lazadaListing.dataSet.descImageAligncenter;
//			}else if(localPicAlign == 'right'){
//				var align = lazadaListing.dataSet.descImageAlignright
//			}else{
//				var align = lazadaListing.dataSet.descImageAlignleft;
//			}
        // 图片描述的图片不并排展示的话 ，这个居中就没问题
//			if(localPicAlign == 'center'){
//				var align = lazadaListing.dataSet.descImageAligncenter;
//			}else{
//				var align = $.trim(localPicAlign)!=""?' align="'+localPicAlign+'"':'';
//			}

        // kindeditor编辑器里面对图片右键修改图片 居中什么的 也是通过修改align属性，如果这里要修改，kindeditor 右键的修改图片也要修改对齐的逻辑。
        // 所以不建议独立fix align: center 不起效 问题，客户选align: center 后可以通过空格居中
        var align = $.trim(localPicAlign) != "" ? ' align="' + localPicAlign + '"' : '';
        $(DecImg.modal).find('#divimgurl>div>img').each(function () {
            var src = $(this).attr("src");
            if (src)
                imgHtml += '<img src="' + src + '"' + width + align + ' />';
        });
        
        if (DecImg.editor)
        	DecImg.editor.insertHtml(imgHtml);

//        $(DecImg.modal).modal('hide');
    },
    localUpOneImg: function (obj) {
        var tmp = '';
        $('#img_tmp').unbind('change').on('change', function () {
            $.showLoading();
            $.uploadOne({
                fileElementId: 'img_tmp', // input 元素 id
                //当获取到服务器数据时，触发success回调函数 
                //data: 上传图片的原图和缩略图的amazon图片库链接{original:... , thumbnail:.. } 
                onUploadSuccess: function (data) {
                    $.hideLoading();
                    tmp = data.original;
                    $(obj).parent().children('input[type="text"]').val(tmp);
                    $(obj).parent().children('img').attr('src', tmp);
                },

                // 从服务器获取数据失败时，触发error回调函数。  
                onError: function (xhr, status, e) {
                    $.hideLoading();
                    alert(e);
                }
            });
        });
        $('#img_tmp').click();
    },
    addImageUrlInput: function (src) {
        if (typeof (src) == 'undefined') {
            src = '';
        }
        $('#divimgurl')
            .append(
                "<div><img src='"
                + src
                + "' width='50' height='50'> <input type='text' id='imgurl"
                + (Math.random() * 10000).toString()
                    .substring(0, 4)
                + "' name='imgurl[]' size='80' style='width: 300px;' onblur='javascript:lazadaListing.blurImageUrlInput(this)' value="
                + src
                + "> <input type='button' value='删除' onclick='javascript:lazadaListing.delImageUrlInput(this)'> <input type='button' value='本地上传' onclick='javascript:lazadaListing.localUpOneImg(this)' ></div>");
    },
    delImageUrlInput: function (imgdiv) {
        $(imgdiv).parent().empty();
    },
    blurImageUrlInput: function (obj) {
        var t = $(obj).val();
        $(obj).parent().children('img').attr('src', t);
    },
    
}