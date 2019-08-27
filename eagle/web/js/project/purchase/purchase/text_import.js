/**
 +------------------------------------------------------------------------------
 *purchase的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		purchase
 * @subpackage  Exception
 * @author		lzhil <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof TextImport === 'undefined')  TextImport = new Object();
TextImport = {
	ConvertedArray:"",
	CurrentRow:'',
	parseTextToTable : function(str) {
		var lines = str.split("\n");
		TextImport.ConvertedArray = new Array();
		lines.forEach(TextImport.processLine);
	},
	processLine : function(element, index, array) {
		if (element == '') return;
		var lineData =  element;
		var cols = lineData.split("\t");
		TextImport.CurrentRow = new Array();
		cols.forEach(TextImport.processLineEachField);
		TextImport.ConvertedArray.push(TextImport.CurrentRow );  
	},
	'processLineEachField' : function(element, index, array) {
		TextImport.CurrentRow.push(element);  
	},

	showImportTextModal:function(title,action_url,callback ){
		html ='<div id = "import_excelFormatText_body" >'+
					'<div class="purchase_import_excel_content" style="width:100%;float:left;">'+
						'<div style="line-height:20px;">'+Translator.t('请录入需要采购的产品sku，数量，单价，备注。')+'<br>'+
								Translator.t('所有列都需要填写。')+
						'</div>'+
						'<textarea id="import_text_data"  class="form-control" style="width:100%;height:400px;margin-top:0px;" data-percent-width="true"></textarea>'+
						'<input type="button" value="'+Translator.t('复制粘贴excel多列指引')+'>>'+'"id="help_toggle" class="btn btn-info" />'+
					'</div>'+
					'<div class="purchase_import_excel_help alert alert-warning" role="alert" show="N" style="margin:10px;display:none;width:0%;text-align:left;">'+
						'<div>'+
							Translator.t('1. 系统支持以下多个列 从excel 批量复制后粘贴。其中"产品SKU"、"采购数量"都是必须的，"采购单价"为空时，自动读取报价信息，"备注"可以为空。')+'<br>'+
							Translator.t('2. 格式示例入下图：')+'<br>'+
							'<div style="clear:both;"><img src="/images/purchase/purchase_import_text_help.png" width="100%"></div>'+'<br>'+
							Translator.t('3. 系统对填入的产品sku进行识别，如果有sku不在阁下的产品列表中，提交时会返回提示信息并中止操作。')+'<br>'+
							Translator.t('4. "采购数量"只能填入数字，否则也会出现提示信息并中止操作。')+'<br>'+
							Translator.t('5. 如果出现重复sku,则相同sku会合并,数量为多个的和，取最后出现的一个采购单价为产品单价。')+'<br>'+
							Translator.t('6. 一张单只能导入一个文件的数据或保存复制粘贴excel格式一次，重复这两种操作都会导致新数据覆盖旧数据，而不会叠加！')+
						'</div>'+
					'</div>'+
				'</div>';

		importFile_bootbox = bootbox.dialog({
			title: title,//
			message: html,
			//show : false,
			className:"import_excelFormatText_dialog",
			buttons:{
				Cancel: {  
					label: Translator.t("返回"),  
					className: "btn-default",  
					callback : function(){
					}
				}, 
				OK: {  
					label: Translator.t("保存"),  
					className: "btn-success",  
					callback : function(){
						//callback();
						TextImport.importExcelText(action_url,callback);
					}
				}, 
			}
		});
		//placeholder 替代方案：
		var watermarkHtml = Translator.t('通过复制excel多个列，然后粘贴，录入sku和采购数量、采购单价');
		watermarkHtml += "<br>";
		watermarkHtml +=Translator.t('如以下例子：');
		watermarkHtml += "<br>";
		watermarkHtml += "<br>";
		watermarkHtml +='SKU001 &nbsp&nbsp&nbsp&nbsp 10 &nbsp&nbsp&nbsp&nbsp 10 &nbsp&nbsp&nbsp&nbsp 备注';
		watermarkHtml += "<br>";
		watermarkHtml +='SKU002 &nbsp&nbsp&nbsp&nbsp 100 &nbsp&nbsp&nbsp&nbsp 10 &nbsp&nbsp&nbsp&nbsp 备注';
		watermarkHtml += "<br>";
		watermarkHtml +='SKU003 &nbsp&nbsp&nbsp&nbsp 10 &nbsp&nbsp&nbsp&nbsp 100 &nbsp&nbsp&nbsp&nbsp 备注';
		watermarkHtml += "<br>";
		watermarkHtml +='SKU005 &nbsp&nbsp&nbsp&nbsp 10 &nbsp&nbsp&nbsp&nbsp 1 &nbsp&nbsp&nbsp&nbsp 备注';
		watermarkHtml += "<br>";
		watermarkHtml +='SKU005 &nbsp&nbsp&nbsp&nbsp 1 &nbsp&nbsp&nbsp&nbsp 0.5 &nbsp&nbsp&nbsp&nbsp 备注';
		watermarkHtml += "<br>";
		$('#import_text_data').watermark(watermarkHtml, {fallback: false});
		
		$("#help_toggle").click(function(){
			var show = $(".purchase_import_excel_help").attr('show');
			if(show=='N'){
				$('.import_excelFormatText_dialog .modal-dialog').css('width','900px');
				$('.purchase_import_excel_content').css({'width':'38%','float':'left'});
				$('.purchase_import_excel_help').css({'width':'58%','float':'right','margin':'10px','display':'block'});
				$('.purchase_import_excel_help').attr('show','Y');
				$("#help_toggle").val(Translator.t('复制粘贴excel多列指引 ')+'<<');
			}
			else{
				$('.import_excelFormatText_dialog .modal-dialog').css('width','450px');
				$('.purchase_import_excel_content').css('width','100%');
				$('.purchase_import_excel_help').css({'width':'0%','display':'none'});
				$('.purchase_import_excel_help').attr('show','N');
				$("#help_toggle").val(Translator.t('复制粘贴excel多列指引')+'>>');
			}
		});
	},
	importExcelText:function(url,callback){
		if ($('#import_text_data').val().length ==0){
			bootbox.alert(Translator.t("无输入文本!"));
			return false;
		}
		
		$('#import_excelFormatText_dialog').css('display','none');
		$('#import_excelFormatText_dialog').parent().addClass('bg_loading');
		if (typeof(importFile_bootbox) != "undefined"){
			importFile_bootbox.find('.btn-primary').attr('data-loading-text',Translator.t('上传中'));
			importFile_bootbox.find('.btn-primary').button('loading');
		}
		
		var data_type = "json";
		TextImport.parseTextToTable($("#import_text_data").val());
	
		if (TextImport.ConvertedArray.length ==0){
			bootbox.alert( $('#data_empty_message').val());
			return;
		}else{
			var prodsList = $.toJSON(TextImport.ConvertedArray);
		}
		
		//供应商名称
		var supplier_name = $("select#supplier_name").val();
		
		$.showLoading();
		$.ajax({  
			url: url,//请求路径
			data:{prodsList : prodsList , data_type : data_type, suppliername : supplier_name},
			type:'post',
			dataType: 'json',//返回数据的类型
			success: function (result){ 
			//debugger;
				$('#import_excelFormatText_dialog').css('display','block');
				$('#import_excelFormatText_dialog').parent().removeClass('bg_loading');
				$('#btn-import-file').button('reset');
				callback(result);
			},  
			error: function (){
				$.hideLoading();
				bootbox.alert( "<b class='red-tips'>"+Translator.t("后台无返回任何数据,请重试或联系客服")+"</b>" );
			}  
		});
	},
};//end of object.TextImport