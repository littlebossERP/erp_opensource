/**
 +------------------------------------------------------------------------------
 *导入 excel js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof import_file === 'undefined')  import_file = new Object();
import_file = {
	init:function(){
		$('#btn-import-file').bind('click',function(){
			import_file.importExcelFile(function(result){
				if (result.success){
					$('#import_excel_file').modal('hide');
					bootbox.alert(result.message);
					
				}else{
					bootbox.alert(result.message);
				}
			});
		});
	},
	ShowExcelImport:function(){
		$('#import_excel_file').modal();
	},
	
	importExcelFile:function(callback){
		//$("#form_import_excel_file").submit();
		if ($('#input_import_excel_file').val().length ==0){
			bootbox.alert($('#tip_file_empty').val());
			return false;
		}
		
		$('#import_excel_body').css('display','none');
		$('#import_excel_body').parent().addClass('bg_loading');
		$('#btn-import-file').button('loading');
		$.ajaxFileUpload({  
			url: '/tracking/tracking/import-tracking-by-excel',//请求路径
			secureuri:false,
			fileElementId:'input_import_excel_file',//file控件的name属性值，所有批量上传的file控件name必须一致
			dataType: 'json',//返回数据的类型
			//data:{"desc":desc},//上传文件时可同时附带其他数据
			success: function (result){ 
				$('#import_excel_body').css('display','block');
				$('#import_excel_body').parent().removeClass('bg_loading');
				$('#btn-import-file').button('reset');
				callback(result);
				
			},  
			error: function (result){
				bootbox.alert(result.message);
				$('#import_excel_body').css('display','block');
				$('#import_excel_body').parent().removeClass('bg_loading');
				$('#btn-import-file').button('reset');
			}  
		});
	},
}

$(function(){
	import_file.init();
})