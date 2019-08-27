/**
 * 
 */
if (typeof pdfLibrary === 'undefined')  pdfLibrary = new Object();

pdfLibrary = {
		returnData:[],
		dataCount:'',
		init:function(){
			$(document).on('click','#chk_all',function(){
				pdfLibrary.checkAll(this);
			});
			
			$('.btn-pdf-upload').on('change',function(e){
				$.showLoading();
				var uploadQueue = [],
					files = this.files;
				pdfLibrary.dataCount = this.files.length;
				Array.prototype.forEach.call(files,function(file){ 			// 把选择的图片都加入上传队列中
					pdfLibrary.BatchUploadPdf(file)
				});
			});
			$(document).on('show.bs.tab', '#photo-list', function (e) {
				var url = $(this).attr('href');
				window.location.href = url;
			});
			
			$(document).on('click', '#file-select', function (e) {
				$('#file-upload').click();
			});
			
		},
		
		checkAll:function(obj){
			if($(obj).prop('checked')){
				$('input[type="checkbox"]').each(function(i){
					$(this).prop("checked",true);
				});
			}else{
				$('input[type="checkbox"]').each(function(i){
					$(this).prop("checked",false);
				});
			}
		},
		
		BatchUploadPdf:function(file){
			var name = 'pdf_file',
				formData = new FormData();
			formData.append(name,file);
			$.ajax({
				url:'/util/file-upload/upload-pdf-to-ali-oss',
				type:'POST',
				cache:false,
				data:formData,
				processData:false,
				contentType:false,
				dataType:'json',
				success:function(data){
					pdfLibrary.returnData.push(data);//存下return结果
					pdfLibrary.dataCheck();
                },
                error:function(){
                	var erro = {};
                	erro.status = false;
                	erro.rtnMsg = '网络错误, 请稍后再试！';
                	pdfLibrary.returnData.push(erro);
                	pdfLibrary.dataCheck();
                }
			});
		},
		
		dataCheck:function(){
			if(pdfLibrary.dataCount == pdfLibrary.returnData.length){
				var str = '<div style="font-size:15px">';
				for(var i = 0;i<pdfLibrary.returnData.length;i++){
					if(pdfLibrary.returnData[i].status){
						str += '<div style="margin-bottom: 8px;">' + pdfLibrary.returnData[i].name + ' <span style="color:#2ecc71">上传成功</span>。</div>';
					}else{
						str += '<div style="margin-bottom: 8px;">' + pdfLibrary.returnData[i].name + ' <span style="color:#d9534f">上传失败，失败原因：' + pdfLibrary.returnData[i].rtnMsg + '</span></div>';
					}
				}
				str += '</div>';
//				$.hideLoading();
				bootbox.alert({title:Translator.t('提示'),message:str,callback:function(){
					   window.location.reload();
			   		}
				   });
			}
		},
		
		deleteBatchPdf:function(){
            if($("input:checked").not("#chk_all").length==0){
                bootbox.alert('您还未选择文件');
                return false;
            }else{
                var pdfItems = $('#pdf_table').serializeObject()['parent_chk'];
                bootbox.confirm({
                    title : "文件批量删除",
                    message : "确定要将这"+pdfItems.length+"项删除吗?",
                    buttons : {
                        confirm : {
                            label : "确定",
                            className: "btn-primary"
                        },
                        cancel : {
                            label : "取消",
                            className: "btn-default"
                        }
                    },
                    callback: function(result) {
                        if (result) {
                            pdfLibrary.deleteProduct(pdfItems,'');
                        }
                    }
                });
            }

        },
        
        deleteProduct:function(ids,key){
			$.showLoading();
            $.ajax({
                type:'POST',
                url:'/util/file-upload/delete',
                data:{
                    ids:ids,
                    key:key,
                },
                dataType:'json',
                success:function(data){
                    $.hideLoading();
                    if(data.code == 200){
//                    	bootbox.alert(data.message);
                    	bootbox.alert({title:Translator.t('提示'),message:Translator.t(data.message),callback:function(){
							   window.location.reload();
					   		}
						   });
                    }else if(data.code == 300){//删除失败
                    	bootbox.alert(data.data);
                    }else{
                    	bootbox.alert(data.message);
                    }
                },
                error:function(){
                    $.hideLoading();
                    $.alertMsg({message:'网络错误, 请稍后再试！',type:'message',success:0,timeout:3});
                }
            });
        },
        deleteOneProduct:function(id,key){
			bootbox.confirm({
                title : "PDF文件删除",
                message : "确定要将这项删除吗?",
                buttons : {
                    confirm : {
                        label : "确定",
                        className: "btn-primary"
                    },
                    cancel : {
                        label : "取消",
                        className: "btn-default"
                    }
                },
                callback: function(result) {
                    if (result) {
                        pdfLibrary.deleteProduct(id,key);
                    }
                }
            });
        },
        
        copyUrl:function(obj){
        	var content = $(obj).parents('tr').find('.hidden_url');
            content.select(); //选择对象 
            document.execCommand("Copy"); //执行浏览器复制命令 
        },
}