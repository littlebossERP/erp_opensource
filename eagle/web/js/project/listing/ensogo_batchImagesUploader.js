/**
 * @ ImagesUploader v1.0
 * 
 */

// dzt20150216:重新讨论后确定不做默认report. 单个请求success , error回调 改为只出来一个最后回调包含了多少error信息，多少success信息；
// 插件为图片上传amazon s3服器使用，所以url是默认定死的，包括服务器处理都不需要调用者考虑
// 用一个类似container的东西将内容概括。


jQuery.fn.extend({
	/**
	 * $(_showBtn).batchImagesUploader(configs) ：初始化已有图片和上传控件。
	 * $(_showBtn).batchImagesUploader('show') ：显示所有图片。
	 * $(_showBtn).batchImagesUploader('hide') ：隐藏所有图片。
	 * $(_showBtn).batchImagesUploader('appendImage', imagesData ) ： 添加一个图片元素。
	 * 
	 * $(_showBtn).getAllImagesData() : 返回所有图片的缩略图和原图链接=> [{thumbnail:... , original:...} , ...] 
	 * 
	 */
	batchImagesUploader : function ( s , args){
		_uploader = this;
		if(typeof(s) == 'string'){
			var excFun = eval(s);
			excFun.call(_uploader , args , $(_uploader).getConfigs());
			return
		}
		s._uploader = _uploader;
		if( !s.imagesMaxNum )
			s.imagesMaxNum = 5;
		
		if( !s.fileMaxSize )
			s.fileMaxSize = 500 * 1024;
		else
			s.fileMaxSize = s.fileMaxSize * 1024;
		
		if( !s.fileFilter )
			s.fileFilter = ["jpg","jpeg","gif","pjpeg","png"];
		
		if( !s.maxHeight )
			s.maxHeight = 100;
		
		if( !s.maxWidth )
			s.maxWidth = 100;
		
		if( !s.uploadIndex )
			s.uploadIndex = 1;
		
		if( !s.name ){
			s.name = 'product_photo_file';
		}
		//支持本地上传。默认开
		if(typeof(s.localImageUploadOn) == 'undefined' )
			s.localImageUploadOn = true;
		
		// 支持通过url添加图片。默认开
		if(typeof(s.fromOtherImageLibOn) == 'undefined' )
			s.fromOtherImageLibOn = true;
		
		// get browser info
		var Sys = {};
		if(navigator.userAgent.indexOf("MSIE")>0) {
			Sys.ie = true;
			var version = navigator.userAgent.split(";"); 
			var trim_Version = version[1].replace(/[ ]/g,""); 
			Sys.ieVersion = trim_Version;
		}
		
		// mark a label for some funciton can only fire.
		if( ! $(_uploader).hasClass('img-uploader'))
			$(_uploader).addClass('img-uploader');
		else{// 已进行过初始化
			$(_uploader).saveConfigs([]);// 清空上次数据
			
			var oldImages = $(_uploader).getAllImagesData();
			for(var i=1; i<oldImages.length; i++){
				$(_uploader).deleteImageData(1);// 此处 图片数组会reindex 所以一直delete 1就好
			}
		}
		
		if($('#image-list').length <= 0){
			var imageList = $('<div class="row" id="image-list"></div>');
			$(_uploader).append(imageList);
		}else{
			var imageList = $('#image-list');
			$(imageList).html('');
			
		}
		
		s.imageList = imageList;
		// $(imageList).width( (s.maxWidth + 20 + 10) * s.imagesMaxNum );
		
		// 初始化已有图片
		$.initImages(s);
		$(imageList).children('.image-item').css({"width":(s.maxWidth + 20 + 10) + "px" , "height":(s.maxHeight + 20 + 10) + "px"});
		$(imageList).children('.image-item').children('.thumbnail').css({"width":(s.maxWidth + 10) + "px" , "height":(s.maxHeight + 10) + "px"});
		
		var _showBtn = $('#btn-uploader');
		s._showBtn = _showBtn;
		
		if( Sys.ie &&  Sys.ieVersion != "MSIE10.0"){// for lt IE 9
			var addUploadInputBtnLocalFile = $('<button type="button" style="" class="btn btn-info">'+Translator.t('继续添加本地图片')+'</button>');
			$(_uploader).prepend('<p><input class="batch-uploader-input" name="'+ s.name +'" type="file" id="product_photo_file_1" ></p>');
			$(_showBtn).before(addUploadInputBtnLocalFile);
			
			$(btnGpWapDiv).prepend(addUploadInputBtnLocalFile);
			addUploadInputBtnLocalFile.click(function() {
				var fileNum = $('input.batch-uploader-input[type="file"][name="'+ s.name +'"]').length + 1 ;
				$(_uploader).prepend($('<p><input class="batch-uploader-input" name="'+ s.name +'" type="file" id="product_photo_file_'+fileNum+'" ><span class="lnk-del-input" aria-hidden="true">&times;</span></p>'));
			});
			
			// post all images
			$(_showBtn).unbind('click').click(function(){
				try{
					var files =$('input[name='+ s.name +']').not('.done');
					var uploadIndex = $(_uploader).getUploadIndex();
					var config = $(_uploader).getConfigs();
					// 获取非空input 内容
					var toAddfileList = Array();
					
					for( var i=0 ; i<files.length; i++ ){
						if($(files[i]).val() != ""){
							$.myFileFilter($(files[i]).attr('id') , config);
							toAddfileList.push(files[i]);
						}
					}
					var uploadIndex = $(_uploader).getUploadIndex();// 获取下一张准备上传的图片index
					
					if( (uploadIndex - 1 + toAddfileList.length) <= config.imagesMaxNum ){
						for(var i=0; i<toAddfileList.length ; i++){
							var c = $.extend( config , {
								uploadIndex:uploadIndex,
								imageItemId:'image-item-'+uploadIndex,
								file:$(toAddfileList[i]).val() , 
								fileElementId:toAddfileList[i].id
							});
							$.myAjaxFileUpload(c);
							uploadIndex++;
						}
					}else{
						throw new Error(Translator.t('对不起，动态图片一次最多只能上传 ') + s.imagesMaxNum +Translator.t(" 张图片！"));
					}
				}catch(e){
					$.myHandleError( "error", e ,  s , null );
					return;
				}
			});
			
		}else{// for !IE or gte IE 10
			var uploadInput = $('<input class="hidden" name="fileUploader" type="file" multiple="multiple" id="fileUploader"> ');
			$(_uploader).before(uploadInput);
			
			
			// open the selection box
			$(_showBtn).unbind('click').click(function(){
				// show btn 每次click事件 重新绑定change事件 ，防止input 元素被删除后，事件绑定失效
//				$('body').on('change','input[type="file"][name="fileUploader"]',function(){
				$('input[type="file"][name="fileUploader"]').on('change',function(){
					try{
						var files = this.files; 
						var uploadIndex = $(_uploader).getUploadIndex();
						var config = $(_uploader).getConfigs();
						// 获取非空input 内容
						var toAddfileList = Array();
						for( var i=0 ; i<files.length; i++ ){
							if(files[i].size > 0){
								$.myFileFilter(files[i] , config);
								toAddfileList.push(files[i]);
							}
						}
						
						if((uploadIndex - 1  +  toAddfileList.length) <= config.imagesMaxNum){ 
							for(var i=0; i<toAddfileList.length ; i++){
								$.myFileFilter(toAddfileList[i] , config);
									var c = $.extend( config ,{
										uploadIndex:uploadIndex,
										imageItemId:'image-item-'+uploadIndex,
										file:toAddfileList[i],
										fileElementId:this.id
									});
									$.myAjaxFileUpload( c );
									uploadIndex++;
							}
						}else{
							throw new Error(Translator.t('对不起，动态图片一次最多只能上传 ') + config.imagesMaxNum +Translator.t(" 张图片！"));
						}
					}catch(e){
						$('input[type="file"][name="fileUploader"]').off('change');
						$.myHandleError( "error", e ,  config , null );
						return;
					}
					
					$('input[type="file"][name="fileUploader"]').off('change');
//					$('body').off('change','input[type="file"][name="fileUploader"]');//防止页面关闭被删除后，再打开会触发两次change事件  
				});
				
				$('input[type="file"][name="fileUploader"]').click();
			});
		}
		
		// 初始化 通过URL 添加图片 模态框
		var _showAddUrlBtn = $('#btn-upload-from-lib');
		s._showAddUrlBtn = _showAddUrlBtn;
		
		
		// add url box body content
		// var addBodyHtml = '<p>' + Translator.t(' Url 地址1：') +'<input name="add_image_url" type="text" id="add_image_url_1" ></p>';
		// addBodyHtml += '<p>' + Translator.t(' Url 地址2：') +'<input name="add_image_url" type="text" id="add_image_url_2" ></p>';
		// addBodyHtml += '<p>' + Translator.t(' Url 地址3：') +'<input name="add_image_url" type="text" id="add_image_url_3" ></p>';
		
		// add url box footer content
//		var addFooterHtml = '<button type="button" class="btn btn-default" data-dismiss="modal">' + Translator.t('取消') +'</button>';//dzt20150326 comment
//		addFooterHtml += '<button type="button" class="btn btn-primary">' + Translator.t('添加') +'</button>';//dzt20150326 comment
		
//		var modalId = 'addImagesBox';
		// dzt20150326 comment： 多层弹窗时，由于'添加图片 URL' 这个原生modal关闭后不会删除，导致第二次打开底层弹窗时，底层弹窗在'添加图片 URL' 这个原生modal 之上。
//		$.addBoostrapModal( modalId ,Translator.t('添加图片 URL'), addBodyHtml , addFooterHtml );
		
		var addBodyHtml = '';
		for(var i=1;i<=3;i++){
			addBodyHtml +='<p>' + Translator.t(' Url 地址：') +'<input name="add_image_url" type="text" id="add_image_url_'+ i +'" class="add_image_url" ></p>';
		}
		addBodyHtml += '</p>';
		s._showAddUrlBtn.click(function(){
			var _showAddUrlDialog = bootbox.dialog({
				title : Translator.t('添加图片 URL'),
				buttons: {  
					Cancel: {  
                        label: Translator.t('取消'),  
                        className: "btn-default",  
                    }, 
                    OK: {  
                        label: Translator.t('添加'),  
                        className: "btn-primary",  
                        callback: function () {// 添加Url 显示到图片元素
                        	var files = $(_showAddUrlDialog).find('input[name=add_image_url]');
                			var toAddfileList = Array();
                			var andUrlImages = Array();
                			
                			// 过滤value为空的input
                			for(var i = 0 ; i < files.length; i++ ){
                				if( $.trim($(files[i]).val()) != "" )
                					toAddfileList.push(files[i]);
                			}
                			
                			if((toAddfileList.length + $(_uploader).getUploadIndex() - 1) > s.imagesMaxNum){
                				$.myHandleError( "error", Translator.t('对不起，动态图片一次最多只能上传 ') + s.imagesMaxNum +Translator.t(" 张图片！") ,  s , null );
                				return false;
                			}
                			
                			// 记录Url data
                			for(var j in toAddfileList){
                    			// collect item data
                    			if(toAddfileList.hasOwnProperty(j)){
	                    			$(_uploader).addImageData({original:$.trim($(toAddfileList[j]).val()),thumbnail:$.trim($(toAddfileList[j]).val())} , $(_uploader).getUploadIndex() );
	                    			andUrlImages.push($(toAddfileList[j]).val());
                    			}
                			}
                			
                			// 重新组织所有图片元素
                			$.reInitImages( s , $(_uploader).getAllImagesData() );
//                        	return false;
                			
                			if(typeof s.onAddUrlImgFinish == "function")
                				s.onAddUrlImgFinish(andUrlImages,s);
                			
                        }  
                    }, 
				},
			    message: addBodyHtml,
			}).ready(function () {	
				// var addMoreUrlBtn = $('<button type="button" style="margin-right: 20px;" class="btn btn-info">' + Translator.t('继续添加图片') +'</button>');
				// $('#add_image_url_1').parent().parent().append(addMoreUrlBtn);
				// addMoreUrlBtn.click(function(){
				// 	var fileNum = $('input[type="text"][name="add_image_url"]').length + 1 ;
				// 	addMoreUrlBtn.before($('<p>' + Translator.t(' Url 地址'+fileNum+'：') +'<input name="add_image_url" type="text" id="add_image_url_'+fileNum+'" ><span class="lnk-del-input" aria-hidden="true">&times;</span></p>'));
				// });
				var uploadIndex = $('.image-item[upload-index]').length;
				var fileNum = $('input[name="add_image_url"]').length;
				console.log(uploadIndex);
				$('.bootbox-body').on('focus','input[name="add_image_url"]:last',function(){
					console.log(fileNum);
					if((fileNum + uploadIndex) <11){
						$(this).parent('p').after($('<p>' + Translator.t(' Url 地址：') +'</label><input name="add_image_url" type="text" id="add_image_url_'+fileNum+'" class="add_image_url" ><span class="lnk-del-input" aria-hidden="true">&times;</span></p>'));
					}
					fileNum += 1 ;
				});
			});	
			
			
		});
		
/*		// dzt20150326 comment
		var addMoreUrlBtn = $('<button type="button" style="margin-right: 20px;" class="btn btn-info">' + Translator.t('继续添加图片') +'</button>');
		$('#add_image_url_1').parent().after(addMoreUrlBtn);
		addMoreUrlBtn.click(function(){
			var fileNum = $('input[type="text"][name="add_image_url"]').length + 1 ;
			addMoreUrlBtn.before($('<p>' + Translator.t(' Url 地址：') +'<input name="add_image_url" type="text" id="add_image_url_'+fileNum+'" ><span class="lnk-del-input" aria-hidden="true">&times;</span></p>'));
		});
		
		// 添加Url 显示到图片元素
		$('#' + modalId + " button.btn-primary").click(function(){
			var files = $('#' + modalId + ' input[name=add_image_url]');
			var toAddfileList = Array();
			
			// 过滤value为空的input
			for(var i = 0 ; i < files.length; i++ ){
				if( $.trim($(files[i]).val()) != "" )
					toAddfileList.push(files[i]);
			}
			
			if((toAddfileList.length + $(_uploader).getUploadIndex() - 1) > s.imagesMaxNum){
				$.myHandleError( "error", Translator.t('对不起，动态图片一次最多只能上传 ') + s.imagesMaxNum +Translator.t(" 张图片！") ,  s , null );
				return;
			}
			
			// 记录Url data
			for(var j in toAddfileList){
    			// collect item data
    			$(_uploader).addImageData({original:$.trim($(toAddfileList[j]).val()),thumbnail:$.trim($(toAddfileList[j]).val())} , $(_uploader).getUploadIndex() );
			}
			
			// 重新组织所有图片元素
			$.reInitImages( s , $(_uploader).getAllImagesData() );
			$('#' + modalId).modal('hide');
		});
*/		
		if( !s.localImageUploadOn ){
			$(_showBtn).attr('disabled','disabled');
		}
		
		if( !s.fromOtherImageLibOn ){
			$(_showAddUrlBtn).attr('disabled','disabled');
		}
		
		// delete input element
		$('body').on( 'click' , 'p>span.lnk-del-input' , function(){
			$(this).parent('p').remove();
		});
		
		
		// delete image event
		$('body').on('click','#image-list > .image-item .lnk-del-img',function(e){
//			$(this).parent('li').remove();
			if($(this).parent().hasClass('select_photo')){
				$(this).parent().removeClass('select_photo').parent().find('.main_image_tips').remove();
			}
			var deleteIndex = $(this).parent().parent().attr('upload-index');
			var deletedImage = $(_uploader).deleteImageData(deleteIndex);
			$.reInitImages( s ,$(_uploader).getAllImagesData() );
			
			if(s.onDelete){
				s.onDelete(deletedImage);
			}
			
			e.stopPropagation();
		});


		$('body').on('click', '#image-list > .image-item',function(){
				if ($(this).find('a img').attr('src') != "/images/batchImagesUploader/no-img.png") {
					$('div[role=image-uploader-container] .image-item a').removeClass('select_photo');
					$(this).find('a').addClass('select_photo');
					$('div[role=image-uploader-container] .image-item .main_image_tips').remove();
					$(this).append('<span class="main_image_tips"></span>');

				}
		});
		// $('#image-list').on('click','.close',function(){
		// 	var imageNum = $('.image-item[upload-index]').length;
		// 	index = $(this).parents('.image-item').attr('upload-index');
		// 	// console.log(index);
		// 	$(this).parents('.img-uploader').deleteImageData($(this).parents('.image-item').attr('upload-index'));
		// 	$(this).parents('.image-item').html('');
		// 	var $imageList = [];
		// 	for(var i=1;i<=10;i++){
		// 		if($('#image-item-'+i).html() != ''){
		// 			$imageList.push($('#image-item-'+i).html());
		// 		}
		// 		$('#image-item-'+i).removeAttr('upload-index');
		// 	}
		// 	for(var j=1;j<=10;j++){
		// 		$('#image-item-'+j).html($imageList[j-1]);
		// 	}
		// 	for(var k=1;k<=imageNum-1;k++){
		// 		$('#image-item-'+k).attr('upload-index',k);
		// 	}
		// 	// console.log($imageList);
		// });
		// 如果batchImagesUploader传入参数为string , 则通过eval() 执行字符串执行下面这些functions 
		
		// show all images
		function show(){
			$(this).getImageList().show('slow');
		}
		
		// hide all images
		function hide(){
			$(this).getImageList().hide('slow');
		}
		
		function appendImage(imagesData , config){
			$.appendImage(imagesData , config);
		}
		
		$(_uploader).saveConfigs(s);
	},
	
	/** 以下function 只有被标记为 img-uploader的元素才能调用  */
	
	// 记录配置
	saveConfigs : function(s){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){ // 
			throw new Error('undefined is not a function')
		}
		
		$(_self).data('config' , s );
	},
	
	// 获取配置
	getConfigs : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){ // 
			throw new Error('undefined is not a function')
		}
		
		return $(_self).data('config')?$(_self).data('config'):{};
	},
	
	// 图片框
	getImageList :　function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){ // 
			throw new Error('undefined is not a function')
		}
		
		var config = $(_self).data('config')?$(_self).data('config'):{}
		return config.imageList;
	},
	
	// 包括已有图片的数据和新添加的图片的数据
	getAllImagesData :　function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){ // 
			throw new Error('undefined is not a function')
		}
		
		return $(_self).data('imageData')?$(_self).data('imageData'):[];
	},
	
	// 获取已添加的图片的数据的个数
	countNotNullData : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){ // 
			throw new Error('undefined is not a function')
		}
		
		var imageData = $(_self).data('imageData') ? $(_self).data('imageData') : [];
		
		// 因为imageData数组有可能 刻在第5个槽位导致imageData.length 数组不准返回 6
		var notNullNum = 0;
		for(var i in imageData){
			if(typeof(imageData[i]) == 'object'){// dzt20160226 for in 循环不知道为什么多了个remove function
				notNullNum++;
			}
		}
		
		return notNullNum;
	},
	
	// 添加的图片的数据
	addImageData :　function( data , index , isUpdateIndex){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		if(typeof(isUpdateIndex) == 'undefined')
			isUpdateIndex = true;
		
		var imageData = $(_self).data('imageData') ? $(_self).data('imageData') : [];
		
		if(index == null)
			imageData[imageData.length] = data;
		else
			imageData[index - 1] = data;
		
		$(_self).data('imageData' , imageData);
		
		
		// refresh the index
		var requestInfo = $(_self).getRequestInfo();
		if(isUpdateIndex)
			$(_self).setUploadIndex( $(_self).countNotNullData() + requestInfo['notDone'] + 1 )
	},
	
	// 删除图片的数据
	deleteImageData : function(index , isUpdateIndex){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		if(typeof(isUpdateIndex) == 'undefined')// re-index image 数组
			isUpdateIndex = true;
		
		var imageData = $(_self).data('imageData');
		
		var deleteElement = null;
		if(-1 != index)
			deleteElement = imageData[index - 1];
		if(deleteElement)
			delete imageData[index - 1];
		
		var newImageData = [];
		var newIndex = 0;
		if(imageData){
			for( var i = 0 ; i < imageData.length ; i++ ){
				if(typeof(imageData[i]) != 'undefined'){
					newImageData[newIndex++] = imageData[i];
				}
			}
		}
		
		$(_self).data('imageData' , newImageData);
		
		// refresh the index
		if(isUpdateIndex){
			var requestInfo = $(_self).getRequestInfo();
			$(_self).setUploadIndex( $(_self).countNotNullData() + requestInfo['notDone'] + 1 );
		}
		return deleteElement;
	},
	
	// 获取下一张准备上传的图片index
	setUploadIndex : function(index){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		$(_self).data('uploadIndex' , index );
	},
	
	
	// 获取下一张准备上传的图片index
	getUploadIndex : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		var requestInfo = $(_self).getRequestInfo();
		if(!$(_self).data('uploadIndex')){
			$(_self).setUploadIndex( 1 );
		}
		
		return $(_self).data('uploadIndex');
	},
	
	// 记录发出请求个数 
	markRequestSend : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}

		var requestInfo = $(_self).getRequestInfo();
		if(requestInfo['send'] && parseInt(requestInfo['send'])) 
			requestInfo['send'] = parseInt(requestInfo['send']) + 1;
		else
			requestInfo['send'] = 1;
		
		$(_self).data('requestInfo' , requestInfo );
	},
	
	// 记录请求成功信息 
	markRequestSuccess : function(infos){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		var requestInfo = $(_self).data('requestInfo')?$(_self).data('requestInfo'):{};
		if(requestInfo['success'] && parseInt(requestInfo['success'])) 
			requestInfo['success'] = parseInt(requestInfo['success']) + 1;
		else
			requestInfo['success'] = 1;
		
		if( !requestInfo['successInfo'] ){
			requestInfo['successInfo'] = [];
		}
		requestInfo['successInfo'].push(infos);
		
		$(_self).data('requestInfo' , requestInfo );
	},
	
	// 记录请求失败信息
	markRequestError : function(infos){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		var requestInfo = $(_self).data('requestInfo')?$(_self).data('requestInfo'):{};
		if(requestInfo['error'] && parseInt(requestInfo['error'])) 
			requestInfo['error'] = parseInt(requestInfo['error']) + 1;
		else
			requestInfo['error'] = 1;
		
		if( !requestInfo['errorInfo'] ){
			requestInfo['errorInfo'] = [];
		}
		requestInfo['errorInfo'].push(infos);
		
		$(_self).data('requestInfo' , requestInfo );
	},
	
	// 记录请求发出后 , 还没有响应的请求个数 。
	markRequestNotDone : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		var requestInfo = $(_self).getRequestInfo();
		
		requestInfo['notDone'] = requestInfo['send'] > (requestInfo['error'] + requestInfo['success']) ? (requestInfo['send'] - (requestInfo['error'] + requestInfo['success'])) : 0;
		$(_self).data('requestInfo' , requestInfo );
		
		//当请求发出后锁定一些按钮的发行为， 当响应返回时，解锁这些按钮
		if( requestInfo['notDone'] > 0)
			$.lockActionButtons($(_self).getConfigs());
		else
			$.unLockActionButtons($(_self).getConfigs());
		
		// refresh the upload index
		$(_self).setUploadIndex($(_self).countNotNullData() + requestInfo['notDone'] + 1);
	},
	
	// 获取请求情况结果
	getRequestInfo : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		var requestInfo = $(_self).data('requestInfo')?$(_self).data('requestInfo'):{};
		if(!requestInfo['success'])
			requestInfo['success'] = 0;
		
		if(!requestInfo['error'])
			requestInfo['error'] = 0;
		
		if(!requestInfo['send']) 
			requestInfo['send'] = 0;
		
		if(!requestInfo['notDone']) 
			requestInfo['notDone'] = 0;
		$(_self).data('requestInfo' , requestInfo );
		
		return requestInfo;
	},
	
	// 清除这批请求信息
	destroyRequestInfo : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
	
		$(_self).data('requestInfo' , null );
	},
	
	// 组合所有响应请求的结果 ， 写成报告。
	// 不再生成报告，只返回数据
	onUploadFinish : function(){
		var _self = this;
		if($(_self).hasClass('img-uploader') !== true){
			throw new Error('undefined is not a function')
		}
		
		var configs = $(_self).getConfigs();
		var requestInfo = $(_self).getRequestInfo();
//		var allSuccessInfo = '';
//		var allErrorInfo = '';
//
//		allSuccessInfo += '<p class="text-success">'+ Translator.t('上传成功') + ' ： '+ requestInfo.success + " " + Translator.t('个') + '</p>';
//		if(requestInfo.success){
//			allSuccessInfo += '<p class="bg-success">';
//			for(var i=0 ; i<requestInfo['successInfo'].length ; i++){
//				allSuccessInfo += requestInfo['successInfo'][i]['name'] + '<br>';
//			}
//			allSuccessInfo += '</p>';
//		}
//		
//		allErrorInfo += '<p class="text-danger">'+ Translator.t('上传失败') + '：' + requestInfo.error + ' ' + Translator.t('个') + '</p>' ;
//		if(requestInfo.error){
//			allErrorInfo += '<p class="bg-danger">';
//			for(var i=0 ; i<requestInfo['errorInfo'].length ; i++){
//				allErrorInfo += requestInfo['errorInfo'][i]['name'] + ': ' + requestInfo['errorInfo'][i]['msg'] + '<br>';
//			}
//			allErrorInfo += '</p>';
//		}
//		
//		bootbox.alert({
//			title: Translator.t("图片上传结果"),
//		    buttons: {
//		        ok: {
//		            label: Translator.t("确定"),
//		        },
//		    },
//		    message: allSuccessInfo + allErrorInfo,
//		});	
		$(_uploader).deleteImageData(-1);
		
		$.reInitImages( $(_self).getConfigs() , $(_uploader).getAllImagesData() );
		
		// 执行用户定义的回调（onUploadFinish） ：images data , request info 
		configs.onUploadFinish( requestInfo['successInfo'] , requestInfo['errorInfo'] ); 
		
		$(_uploader).destroyRequestInfo();
		
	},
	
	
	
	/** 以上function 只有被标记为 img-uploader的元素才能调用  */
});

jQuery.extend({
	myAjaxFileUpload : function(customConfig){
		var c = customConfig;
		$.ajaxFileUpload({  
			 url:"/util/image/upload", // 服务器上传图片到amazon s3的链接
		     imageItemId:c.imageItemId, // show image element id
		     uploadIndex:c.uploadIndex, // image data 所在槽位
		     fileElementId:c.fileElementId, // image file id
		     fileName: c.fileName,// file控件的name属性值，所有批量上传的file控件name必须一致
		     uploadFile:c.file,//通过input 元素 change事件获取的 file 对象 或 上传文件的文件名
		     dataType: 'json',//返回数据的类型
		     data:{fileMaxSize:c.fileMaxSize},//上传文件时可同时附带其他数据
		     success: function (data , status , s){ 
	    		if(data && data.status){
	    			var itemId = "#"+s.imageItemId;
	    			var imageHtml = '<img style="max-width: '+c.maxWidth+'px;max-height: '+c.maxHeight+'px;" src="'+data.data.thumbnail+'" id="">';
	    			imageHtml += '<button type="button" class="lnk-del-img close"><span aria-hidden="true">&times;</span></button>';
	    			$(itemId+' .thumbnail').html(imageHtml);
	    			$(itemId).attr('upload-index',s.uploadIndex);
	    			 
	    			// collect item data
	    			$(c._uploader).addImageData(data.data , s.uploadIndex , false);
	    			$(c._uploader).markRequestSuccess($.extend( data.data ,{name:data.name}));
	    		 
	    		}else{
    				if(data.length <= 0){
    					$(c._uploader).markRequestError({name:data.name , msg:Translator.t("对不起，服务器返回失败，请稍候再试！") });
//    					$.myHandleError( 'error' , Translator.t("对不起，服务器返回失败，请稍候再试！") , c , null );
	    			}
	    			 
	    			if(!data.status){
	    				if(!data.rtnMsg)
	    					$(c._uploader).markRequestError({name:data.name , msg:Translator.t("图片上传失败！") });
//	    					$.myHandleError( 'error' , Translator.t("图片上传失败！") , c , null );
	    				else{
	    					$(c._uploader).markRequestError({name:data.name , msg:data.rtnMsg.join("<br>") });
//	    					$.myHandleError( 'error' , data.rtnMsg.join("<br>") , c , null ); 
	    				}
	    			}
	    		}
	    		
	    		$(c._uploader).markRequestNotDone();
	    		
	    		var requestInfo = $(c._uploader).getRequestInfo();
	    		var allDone = ( requestInfo.notDone <= 0 );
	    		if(allDone)
	        		$(c._uploader).onUploadFinish();

			},  
		     
		     // image xhr2 object loading progress
		    progress : function(evt , s){
		    	if(c.progress)
		    		 c.progress(data , status , $.extend( s , c));
		    	else{
		    		var imageItemId = "#"+s.imageItemId;
					if (evt.lengthComputable) {
						var percentComplete = Math.round(evt.loaded * 100 / evt.total);
						$(imageItemId+' .thumbnail').html( "<font>"+percentComplete.toString() + '%'+"</font>");
						if(percentComplete == 100){
							$(imageItemId+' .thumbnail').html( "<font>loading...</font>");
						}
					}
					else {
						//document.getElementById('status').innerHTML = 'unable to compute';
					}
		    	}
			},
			error : function( xhr , status , e , s ) {
				var originFileName = '';
				if('string' == typeof(this.uploadFile)){
					if( this.uploadFile.lastIndexOf('/') == -1 )
						originFileName = this.uploadFile.substring(this.uploadFile.lastIndexOf('\\') + 1 , this.uploadFile.length);
					else
						originFileName = this.uploadFile.substring(this.uploadFile.lastIndexOf('/') + 1 , this.uploadFile.length);
				}else	
					originFileName = this.uploadFile.name;
					
				$(c._uploader).markRequestError({name:originFileName , msg:xhr.responseText?xhr.responseText:xhr.responseXML });
				$(c._uploader).markRequestNotDone();
	    		
	    		var requestInfo = $(c._uploader).getRequestInfo();
	    		var allDone = ( requestInfo.notDone <= 0 );
	    		if(allDone)
	        		$(c._uploader).onUploadFinish();

				
//				$.myHandleError( 'error' , e , $.extend( s , c) , xhr );
//				return;
			}
		}); 
		
		// 记录请求情况
		$(c._uploader).markRequestSend();
		$(c._uploader).markRequestNotDone();
	},
	
	// 过滤出不符合条件的文档
	myFileFilter : function(file , s){
		
		//检查图片格式
		if('string' == typeof(file))
			var fileName = $("#"+file).val();
		else	
			var fileName = file.name;
		var fileExtend = fileName.substring( fileName.lastIndexOf('.') + 1 , fileName.length );
		if( fileExtend == ""){
			throw new Error(fileName + Translator.t(":对不起，我们只支持上传") + s.fileFilter.join(',') + Translator.t("格式的图片！"));
		}
		fileExtend = fileExtend.toLowerCase();
		if( $.inArray(fileExtend , s.fileFilter) == -1 ){
			throw new Error(fileName + Translator.t(":对不起，我们只支持上传") + s.fileFilter.join(',') + Translator.t("格式的图片！"));
		}
		
		//检查图片大小
		if('object' == typeof(file) && file.size > s.fileMaxSize){
			throw new Error(fileName + Translator.t(":图片") + Math.round(file.size / 1024) + Translator.t(" K , 超出规定大小 ") + (s.fileMaxSize / 1024) + Translator.t(" K ， 请重新上传图片!"));
		}
		
	},
	
	// show errors happened in this plug-in 
	myHandleError : function( status, e , s , xhr ) 		{
		bootbox.alert({
			title : status,
		    message: e,
		});		
	},
	
	// initial the image element
	initImages : function(s){
		if(s && s.initImages && s.initImages.length > 0){
			for(var i in s.initImages){
				if(typeof( s.initImages ) == 'object' && s.initImages[i].thumbnail){
					var uploadIndex = $(s._uploader).getUploadIndex();
					var imageItemId = 'image-item-' + uploadIndex;
					$(s.imageList).append('<div id="'+imageItemId+'" class="image-item col-xs-2"></div>');
					var itemHtml = '';
					if(i == 0){
		    			itemHtml += '<a class="thumbnail select_photo">';
					}else{
		    			itemHtml += '<a class="thumbnail">';
					}
	    			itemHtml += '<img style="max-width:'+ s.maxWidth +'px; max-height:'+ s.maxHeight +'px; " src="'+s.initImages[i].thumbnail+'" id="">'
	    			itemHtml += '<button type="button" class="lnk-del-img close"><span aria-hidden="true">&times;</span></button></a>';
	    			if(i == 0){
	    				itemHtml += '<span class="main_image_tips"></span>';
	    			}
	    			$('#'+imageItemId).html(itemHtml);
	    			$('#'+imageItemId).attr('upload-index',uploadIndex);
	    			 
	    			$(s._uploader).addImageData(s.initImages[i]);
				}
			}
		}
		
		for (var i = $(s._uploader).getUploadIndex() ; i <= s.imagesMaxNum ; i++ ){
			var imageItemId = 'image-item-' + i;
			$(s.imageList).append('<div id="' + imageItemId + '" class="image-item col-xs-2"></div>');
			var itemHtml = '';
			itemHtml += '<a class="thumbnail">';
			itemHtml += '<img style="max-width:'+ s.maxWidth +'px; max-height:'+ s.maxHeight +'px; " src="/images/batchImagesUploader/no-img.png" id="">'
			itemHtml += '</a>';
			$('#'+imageItemId).html(itemHtml);
		}
	},
	
	// 插入图片到及图片数据到 list显示
	appendImage : function(imageData , s){
		var index = $(s._uploader).getUploadIndex();
		if( index <= s.imagesMaxNum){
			var imageItemId = 'image-item-' + index;
			$(s.imageList).append('<div id="'+imageItemId+'" class="image-item col-xs-2"></div>');
			var itemHtml = '';
			itemHtml += '<a class="thumbnail">';
			itemHtml += '<img style="max-width:'+ s.maxWidth +'px; max-height:'+ s.maxHeight +'px; " src="'+imageData.thumbnail+'" id="">'
			itemHtml += '<button type="button" class="lnk-del-img close"><span aria-hidden="true">&times;</span></button></a>';
			$('#'+imageItemId).html(itemHtml);
			$('#'+imageItemId).attr('upload-index',index);
			 
			$(s._uploader).addImageData(imageData , index);
		}else{
			$.myHandleError( "error", Translator.t('对不起，动态图片一次最多只能上传 ') + s.imagesMaxNum +Translator.t(" 张图片！") ,  s , null );
		}
	},
	
	// 根据imageData，重新组织所有图片元素
	reInitImages : function(s , imageData){
		if(imageData.length > 0){
			for(var i in imageData){
				if(typeof(imageData[i]) == "object"){
					var itemId = "#image-item-" + (parseInt(i) + 1);
					var imageHtml = '<img style="max-width: '+s.maxWidth+'px;max-height: '+s.maxHeight+'px;" src="'+imageData[i].thumbnail+'" id="">';
	    			imageHtml += '<button type="button" class="lnk-del-img close"><span aria-hidden="true">&times;</span></button>';
	    			$(itemId+' .thumbnail').html(imageHtml);
	    			$(itemId).attr('upload-index', parseInt(i) + 1 );
				}
			}
		}
		for (var i = $(s._uploader).getUploadIndex() ; i <= s.imagesMaxNum ; i++ ){
			var imageItemId = 'image-item-' + i;
			$('#'+imageItemId).removeAttr('upload-index');
			
			var itemHtml = '';
			itemHtml += '<img style="max-width:'+ s.maxWidth +'px; max-height:'+ s.maxHeight +'px; " src="/images/batchImagesUploader/no-img.png" id="">'
			$('#'+imageItemId+'>.thumbnail').html(itemHtml);
		}
	},
	
	// @param : title,body,footer: 相关部分html
	addBoostrapModal : function(id , title , body , footer){
		var AddImagesBoxHtml = '';
		AddImagesBoxHtml += '<div class="modal fade" id="'+id+'" tabindex="-1" role="dialog" aria-labelledby="addImagesBox" aria-hidden="true" data-backdrop="static" >';
		AddImagesBoxHtml += '<div class="modal-dialog">';
		AddImagesBoxHtml += '<div class="modal-content">';
		AddImagesBoxHtml += '<div class="modal-header">';
		AddImagesBoxHtml += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
		AddImagesBoxHtml += '<h4 class="modal-title">'+title+'</h4>';//AddImagesBox title
		AddImagesBoxHtml += '</div>';
		AddImagesBoxHtml += '<div class="modal-body">';//AddImagesBox body
		AddImagesBoxHtml += body;
		AddImagesBoxHtml += '</div>';
		AddImagesBoxHtml += '<div class="modal-footer">';//AddImagesBox footer
		AddImagesBoxHtml += footer;
		AddImagesBoxHtml += '</div>';
		AddImagesBoxHtml += '</div><!-- /.modal-content -->';
		AddImagesBoxHtml += '</div><!-- /.modal-dialog -->';
		AddImagesBoxHtml += '</div><!-- /.modal -->';
		
		var AddImagesBox = $(AddImagesBoxHtml);
		$('body').append(AddImagesBox);
		return AddImagesBox;
	},
	
	// 进行相关动作时，封锁其他行为
	lockActionButtons : function(s){
		$(s._showBtn).attr('disabled' , 'disabled');
		$(s._showAddUrlBtn).attr('disabled' , 'disabled');
		$('button.lnk-del-img.close').attr('disabled' , 'disabled');
	},
	
	// 进行相关动作时，封锁其他行为
	unLockActionButtons : function(s){
		$(s._showBtn).removeAttr('disabled');
		$(s._showAddUrlBtn).removeAttr('disabled');
		$('button.lnk-del-img.close').removeAttr('disabled');
	},
	
	/**当服务器正常返回数据时，触发success回调函数，否则将触发error回调函数
	 * @params url , fileElementId , dataType , success callback function , error callback.
	 * $.uploadOne(configs): 上传一张图片到指定url ，调用时必须将input 的id 赋值到fileElementId。 
	 *                       success 回调函数是在ie10 或非ie浏览器的 收到响应为 httpcode >=200 , <300时触发的， 在ie9或以下是通过 iframe.onload事件触发的。
	 *                       
	 */
	uploadOne : function(s){
		// get browser info
		var Sys = {};
		if(navigator.userAgent.indexOf("MSIE")>0) {
			Sys.ie = true;
			var version = navigator.userAgent.split(";"); 
			var trim_Version = version[1].replace(/[ ]/g,""); 
			Sys.ieVersion = trim_Version;
		}
		
		if(!s.fileElementId && s.file){
			var uploadFile = s.file;
		}else{
			var file = $('#'+s.fileElementId).not('.done');
			if( Sys.ie &&  Sys.ieVersion != "MSIE10.0"){// for lt IE 9
				var uploadFile = $(file).val();
			}else{
				var uploadFile = file[0].files[0];
			}
		}
		
		// 获取非空input 内容
		if(uploadFile){
			$.ajaxFileUpload({  
				 url:"/util/image/upload", // 服务器上传图片到amazon s3的链接
			     uploadFile : uploadFile,//通过input 元素 change事件获取的 file 对象 或 上传文件的文件名
			     fileName:'product_photo_file',
			     
			     fileElementId:s.fileElementId, // input 元素 id
			     dataType: 'json',//返回数据的类型
			     isNotCheckFile : true,
			     success: function (data , status , context){ 
			    	 if(data && data.status){
			    		 s.onUploadSuccess(data.data);
			    	 }else{
	    				if(data.length <= 0){
	    					 s.onError(null , "对不起，服务器返回失败，请稍候再试！");
		    			}
		    		 	if(!data.status){
		    				if(!data.rtnMsg)
		    					s.onError({name:data.name , msg:Translator.t("图片上传失败！") });
		    				else{
		    					s.onError({name:data.name , msg:data.rtnMsg });
		    				}
		    			}
			    	}
			     },
			     error: function( xhr, status, e ){
			    	 s.onError(xhr , e);
			     }
			});
		}
	},
		
});
