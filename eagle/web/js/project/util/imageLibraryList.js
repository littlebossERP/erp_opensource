/*+----------------------------------------------------------------------
  | 小老板
  +----------------------------------------------------------------------
  | Copyright (c) 2014-2015 http://www.littleboss.com All rights reserved.
  +----------------------------------------------------------------------
  | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
  +----------------------------------------------------------------------
  | Author: dzt
  +----------------------------------------------------------------------
  | Create Date: 2015-12-31
  +----------------------------------------------------------------------
  */

/**
  +------------------------------------------------------------------------------
 * 图片库 js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		util
 * @subpackage  Exception
 * @author		dzt
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof util === 'undefined') util = new Object();

util.imageLibrary = {	
	'listInit': function() {
		//tab跳转
		$(document).on('show.bs.tab', '#file-list', function (e) {
			var url = $(this).attr('href');
			window.location.href = url;
		});
		//图片分类移入移出
		$(".chooseTreeName").mouseover(function(){
			$(this).addClass('fColor1');
		});
		$(".chooseTreeName").mouseout(function(){
			$(this).removeClass('fColor1');
		});
		//图片分类查询
		$(".chooseTreeName").click(function(){
			$groupid=$(this).attr('data-groupid');
			window.location.href=global.baseUrl+'util/image/show-library?classification='+$groupid; 
		});
		//图片分类缩放
		$(".gly").click(function(){
			if($(this).attr('data-isleaf')=='open'){
				$(this).removeClass('glyphicon-triangle-bottom');
				$(this).addClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','close');
				
				$(this).parent().next().css('display','none');
			}
			else{
				$(this).addClass('glyphicon-triangle-bottom');
				$(this).removeClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','open');
				$(this).parent().next().css('display','block');
			}
		});
		//图片分类
		$(".aClick").click(function(){
			$.modal({
				url:'/util/image/images-classifica',
				method:'POST',
				data:{orderitemid : 1},
			},
			'分类',{footer:false,inside:false}).done(function($modal){
				$('.modal-box').find('nav').remove();
				$('.modal-box').find('footer').remove();
				
				$(".queding").click(function(){
					var arr=[];  //原节点新增的
					var arr_new=[];  //新节点新增的
					var arr_new2=[];  //新节点新增的
					var arr_edit=[];     //修改的
					var arr_dongtai=[];
					$('span[id=categoryTreeB_-_span]').each(function(t){
						$datapar=$(this).attr('data-par');
						if(!isNaN($datapar)){
							if(arr[$datapar]==null)
								arr[$datapar]=[];
							$i=$(this).parent().parent().attr('name').replace('li_', '');;
							arr[$datapar][$i]=$(this).html();
						}
						else{				
							$i=$(this).attr('data-par').replace('li_', '');
							$x=$("li[name=li_"+$i+"]").attr("class").replace('level', '');
							if(arr_dongtai[$x]==null)
								arr_dongtai[$x]=[];
							if(arr_dongtai[$x][$i]==null)
								arr_dongtai[$x][$i]=[];
							$j=$(this).attr("name").replace('span_', '');
							arr_dongtai[$x][$i][$j]=$(this).html();
							
						}
					});

					$('.editspan').each(function(){
						if($(this).attr('id')!=null){
							$id=$(this).attr('id').replace('categoryTreeB_', '').replace('_span', '');
							arr_edit[$id]=$(this).html();
						}
					});

					$.ajax({
						url:"/util/image/images-classifica-save",
						type: 'post',
						dataType:"json",
						data:{newname:arr,editname:arr_edit,delname:$('#removeli').val(),newnewname:arr_new,newnewname2:arr_new2,arr_dongtai:arr_dongtai},
						success:function(result){
							if(result.code=='true'){
								$modal.close();
								window.location.href=global.baseUrl+'util/image/show-library'; 	
								return true;
							}
							else{
								bootbox.alert(result.message);
								return false;
							}
						},
						error: function(){
							bootbox.alert("Internal Error");
							return false;
						}
					});
				});
	 				 				 			
				$('.modal-close').click(function(){	
					$modal.close();
				});
			});
		
		});
				
	},
	
	'showUpload':function() {
		if($('div[role="image-uploader-container"]').css('display') == 'none'){
			$('div[role="image-uploader-container"]').batchImagesUploader({
				localImageUploadOn : true,   
				fromOtherImageLibOn: true , 
				imagesMaxNum : 8,
				fileMaxSize : 1024 , 		
				fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
				maxHeight : 100,
				maxWidth : 100,
				initImages : [], 
				fileName: 'product_photo_file',
				onUploadFinish : function(imagesData , errorInfo){
					if(typeof imagesData == 'undefined'){
						imagesData = [];
					}
					
					if(typeof errorInfo == 'undefined'){
						errorInfo = [];
					}
					
					var allSuccessInfo = '';
					var allErrorInfo = '';
					allSuccessInfo += '<p class="text-success">'+ Translator.t('上传成功') + ' ： '+ imagesData.length + " " + Translator.t('个') + '</p>';
					
					allErrorInfo += '<p class="text-danger">'+ Translator.t('上传失败') + '：' + errorInfo.length + ' ' + Translator.t('个') + '</p>' ;
					if(errorInfo.length>0){
						allErrorInfo += '<p class="bg-danger">';
						for(var i=0 ; i<errorInfo.length ; i++){
							var errorDetail = eval('('+errorInfo [i].msg+')');
							allErrorInfo += errorDetail['name'] + ': ' + errorDetail['rtnMsg'] + '<br>';
						}
						allErrorInfo += '</p>';
					}
					
					bootbox.alert({
						title: Translator.t("图片上传结果"),
					    buttons: {
					        ok: {
					            label: Translator.t("确定"),
					        },
					    },
					    message: allSuccessInfo + allErrorInfo,
						callback:function(){
							if(imagesData.length>0){
								window.location.reload();
								$.showLoading();
							}
						}
					});	
					
				},
				onAddUrlImgFinish : function(urls,config){
					var allImagesInfo = $(config._uploader).getAllImagesData();
					if(urls.length != allImagesInfo.length){//如果第一次添加url 则自动上传到后台
						return false;
					}
					
					$.showLoading();
					$.post(
						global.baseUrl+'util/image/upload-image-url',{urls:urls},
						function (result){
							$.hideLoading();
							if (result["code"]==400)  {
								$('#re-add-url').show();
								bootbox.alert({title:Translator.t('错误提示') , message:result["message"] });	
							}else{
								bootbox.alert({title:Translator.t('提示'),message:result["message"],
									callback:function(){
										window.location.reload();
										$.showLoading();
									}
								});
							}
						},'json'
					);
				},
				onDelete : function(data){
					
				}
			});
			
			$('div[role="image-uploader-container"]').show();
		}
		
		
	},
	
	// 批量操作
	'batchDo':function (obj){
		action = $(obj).attr("data-val");// $(obj).val();
		if(action==''){
			return false;
		}
		
		switch(action){
			case 'check-all':
				util.imageLibrary.checkAll(true);
				break;
			case 'uncheck':
				util.imageLibrary.checkAll(false);
				break;	
			case 'batch-delete':
				var ids = [];
				$('.image-library input[name="is_check"]').each(function(){
					if($(this).prop('checked') == true){
						ids.push($(this).val());
					}
				});
				
				if(ids.length == 0){
					bootbox.alert({title:Translator.t('错误提示') , message:"请选择图片"});	
					return false;
				}
				util.imageLibrary.batchDelete (ids)
				break;
			default:
				return false;
				break;
		}
	},
	
	'checkAll':function (value){
		var checkVal = true;
		if(false == value)checkVal = false;
			
		$('.image-library input[name="is_check"]').each(function(){
			$(this).prop('checked',checkVal);
		});
	},

	'batchDelete':function (ids){
		bootbox.confirm({  
	        title : Translator.t('确认'),
			message : Translator.t('您确定要删除图片吗?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'util/image/delete',{ids:ids.join(',')},
						function (result){
							$.hideLoading();
							if (result["code"]==400)  {
								bootbox.alert({title:Translator.t('错误提示') , message:result["message"] });	
								return false;
							}else{
								bootbox.alert({title:Translator.t('提示'),message:result["message"],
									callback:function(){
										window.location.reload();
										$.showLoading();
									}
								});
							}
						},'json'
					);
				}
	        },  
	    });
	},
	
	'deleteImg':function (id){
		bootbox.confirm({  
	        title : Translator.t('确认'),
			message : Translator.t('您确定要删除图片吗?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'util/image/delete',{ids:id},
						function (result){
							$.hideLoading();
							if (result["code"]==400)  {
								bootbox.alert({title:Translator.t('错误提示') , message:result["message"] });	
								return false;
							}else{
								bootbox.alert({title:Translator.t('提示'),message:result["message"],
									callback:function(){
										window.location.reload();
										$.showLoading();
									}
								});
							}
						},'json'
					);
				}
	        },  
	    });
	},
	
	'showUrl':function(obj){
		$(obj).children('font').hide();
		$(obj).children('input').show();
	},
	
	'upUrlImg':function(){
		var allImagesInfo = $('div[role="image-uploader-container"]').getAllImagesData();
		var urls = [];
		for(var i in allImagesInfo)
			urls.push(allImagesInfo[i]['original']);
		
		if(urls.length <= 0){
			bootbox.alert({title:Translator.t('错误提示') , message:"请输入要上传的Url" });	
			return false;
		}
		
		$.showLoading();
		$.post(
			global.baseUrl+'util/image/upload-image-url',{urls:urls},
			function (result){
				$.hideLoading();
				if (result["code"]==400)  {
					bootbox.alert({title:Translator.t('错误提示') , message:result["message"] });	
				}else{
					bootbox.alert({title:Translator.t('提示'),message:result["message"],
						callback:function(){
							window.location.reload();
							$.showLoading();
						}
					});
				}
			},'json'
		);
	},
	//图片移动到图片类型
	'imageMove':function(obj){
		$c_id = $(obj).attr("data-val");//$(obj).val();
		
		var ids = [];
		$('.image-library input[name="is_check"]').each(function(){
			if($(this).prop('checked') == true){
				ids.push($(this).val());
			}
		});
		
		if(ids.length == 0){
			bootbox.alert({title:Translator.t('错误提示') , message:"请选择图片"});	
			return false;
		}
		
		$.ajax({
			url:"/util/image/images-classifica-move",
			type: 'post',
			dataType:"json",
			data:{c_id:$c_id,ids:ids},
			success:function(result){
				if(result.code=='true'){
					window.location.href=global.baseUrl+'util/image/show-library'; 	
					return true;
				}
				else{
					bootbox.alert(result.message);
					return false;
				}
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},
	//图片空间扩大Btn
	'imageSpaceExpand':function(){
		$.modal({
			  url:'/util/image/image-space-expand-list',
			  method:'get',
			  data:{}
			},'图片空间扩容',{footer:false,inside:false}).done(function($modal){
				$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
			}
		);
	},
	//图片空间计算价值
	'size_mb_change':function(is_pay){
		size_no = $.trim($('#size_mb_no').val());
		days_no = $('#days_no').val();
		imgPackageSurplus = $('#imgPackageSurplus').val();
		
		size_no = parseFloat(size_no);
		days_no = parseFloat(days_no);
		imgPackageSurplus = parseFloat(imgPackageSurplus);
		imgPackageSurplus = Math.round(imgPackageSurplus*100)/100;
		
		if(size_no == ''){
			bootbox.alert('请输入需要购买的容量');
			return false;
		}
		
		if(size_no < 100){
			bootbox.alert('请输入100的倍数');
			return false;
		}
		
		if((size_no % 100) != 0){
			bootbox.alert('请输入100的倍数');
			return false;
		}
		
		if(days_no == ''){
			bootbox.alert('请选择购买天数');
			return false;
		}
		
		//计算需要支付的金额 S
		size_price = (size_no / 100) * 10 * days_no;
		
		if(size_price >= 300){
			size_price = size_price * 0.8;
			size_price = Math.round(size_price*100)/100;
		}else if(size_price >= 200){
			size_price = size_price * 0.85;
			size_price = Math.round(size_price*100)/100;
		}else if(size_price >= 100){
			size_price = size_price * 0.9;
			size_price = Math.round(size_price*100)/100;
		}
		
//		return false;
		
		//计算需要支付的金额 E
		
		$('#image_price_now').text(size_price);
		
		account_balance = $('#account_balance').text();
		
		alipay_price = 0;
		
		if((account_balance - size_price + imgPackageSurplus) >= 0){
			$('#image_expand_save').text('购买');
		}else{
			alipay_price = Math.abs(account_balance - size_price + imgPackageSurplus);
			alipay_price = Math.round(alipay_price*100)/100;
			$('#image_expand_save').text('支付');
		}
		$('#alipay_price').text(alipay_price);
		
		//判断是否支付按钮触发
		if(is_pay == true){
			$.ajax({
				url:"/util/image/image-space-expand-save",
				type: 'post',
				dataType:"json",
				data:{size_no:size_no,days_no:days_no,alipay_price:alipay_price},
				success:function(result){
					if(result.code==1){
						bootbox.alert(result.message);
						return false;
					}else if(result.code==2){
	        			$.showLoading();
	        			var Url=global.baseUrl +'payment/user-account/package-operation';
	        			$.ajax({
	        		        type : 'post',
	        		        cache : 'false',
	        		        data : {
	        		        	package_id:27,
	        		        	operation:'use',
	        		        	package_value:'',
	        		        	package_type:'',
	        		        	oth_params:{size_no:size_no,days_no:days_no,alipay_price:alipay_price}
	        		        },
	        		        dataType : 'json',
	        				url: Url,
	        				async : false,
	        		        success:function(res) {
	        		        	$.hideLoading();
	        		        	if(res.code == 200){
	        		        		$e = $.alert(res.message,'success');
	        		        		$e.then(function(){
	        		        			location.reload();
	        		        		});
	        		        	}else{
	        		        		$e = $.alert(res.message,'danger');
	        		        	}
	        		        }
	        			});
					}else if(result.code==3){
						$('#WIDtotal_fee').val(alipay_price);
						$('#callback_params_base64').val(result.data);
						
						document.forms['image_payment_form'].submit();
					}
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
			});
		}
	}
	
};
