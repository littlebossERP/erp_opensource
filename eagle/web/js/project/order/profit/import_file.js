/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com> 2016-03-15 eagle 2.0
 * @version		1.0
 +------------------------------------------------------------------------------
 */
 
if (typeof importFile === 'undefined')  importFile = new Object();

importFile={
	showImportModal:function(title,form_action_url ,template_path , type, callback ){
		
		html = '<div id = "import_excel_body">'+
		'<form id="form_import_file"; action="'+form_action_url+'" method="post" enctype="multipart/form-data">'+
		'<input type="file" id="input_import_file" name="input_import_file">'+
		'</form>'+
		'<p>'+Translator.t('XLS示例文件下载')+' <a href="'+template_path+'">'+Translator.t('例子下载')+'</a></p><br>'+
		'<p style="color:red">'+Translator.t('注意：')+"</p>"+
		'<span style="color:blue">&nbsp;&nbsp;&nbsp;&nbsp;'+Translator.t('只允许上传excel格式文件(.xls或.xlsx)；')+"</span><br>"+
		'<span style="color:blue">&nbsp;&nbsp;&nbsp;&nbsp;'+Translator.t('为保证上传质量，最好一次上传不超过100个订单数据。')+"</span><br>"+
		'</div>';
		
		importFile_bootbox = bootbox.dialog({
			
			title: title,//
			message: html,
			//show : false,
			buttons:{
				Cancel: {  
					label: Translator.t("返回"),  
					className: "btn-default",  
					callback : function(){
						
					}
				}, 
				OK: {  
					label: Translator.t("保存"),  
					className: "btn-primary",  
					callback : function(){
						//callback();
						
						importFile.importExcelFile(type , callback);
					}
				}, 
			}
		});
	},
	
	importExcelFile:function(type, callback){
		if ($('#input_import_file').val().length ==0){
			bootbox.alert(Translator.t("请选择需要的上传文件!"));
			return false;
		}
		
		$('#import_excel_body').css('display','none');
		$('#import_excel_body').parent().addClass('bg_loading');
		if (typeof(importFile_bootbox) != "undefined"){
			importFile_bootbox.find('.btn-primary').attr('data-loading-text',Translator.t('上传中'));
			// importFile_bootbox.find('.btn-primary').button('loading');
		}
		$.showLoading();
		$.ajaxFileUpload({  
			url: $('#form_import_file').attr('action'),//请求路径
			secureuri:false,
			isUploadFile:true,
			isNotCheckFile:true,
			fileElementId:'input_import_file',//file控件的name属性值，所有批量上传的file控件name必须一致
			dataType: 'json',//返回数据的类型
			data:{"type":type},//上传文件时可同时附带其他数据
			success: function (result){ 
			debugger;
				$('#import_excel_body').css('display','block');
				$('#import_excel_body').parent().removeClass('bg_loading');
				$('#btn-import-file').button('reset');
				callback(result);
				$.hideLoading();
			},  
			error: function (result){
				$.hideLoading();
				bootbox.alert(Translator.t('数据传输错误！'));
				/*
				bootbox.alert(result.message);
				$('#import_excel_body').css('display','block');
				$('#import_excel_body').parent().removeClass('bg_loading');
				$('#btn-import-file').button('reset');
				*/
				return false;
			}  
		});
	},
}