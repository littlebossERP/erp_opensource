
if (typeof excelFormat === 'undefined')  excelFormat = new Object();

excelFormat = {
		
		options:'',//下拉列表所有option
		nums:0,//列号最后一个字符的数字编号
		lines:0,//总行数
		key:-1,//列号等于两位时，第一个字符的数字编号
		def_selected:'fixed_value',
		def_value:'order_id',
		//生成下拉框html
		getDropdownList:function(lines){
			drop = '<select name="params[excel_format]['+lines+'][data_value]" class="col-xs-5 col-xs-offset-1 req">';
			drop += excelFormat.options;
			drop += "</select>";
			return drop;
		},
		//生成文本框html
		getTextbox:function(lines){
			return '<input type="text" class="col-xs-5 col-xs-offset-1 req" name="params[excel_format]['+lines+'][data_value]" >';
		},
		//生成‘保持为空’的文本框html
		getHiddenEmptyTextbox:function(lines){
			return '<input type="hidden" name="params[excel_format]['+lines+'][data_value]" value="">';
		},
		//根据data_type显示对应的控件
		changeExcelDataType:function(_this_name,value){
			_this = $("[name='"+_this_name+"']");
			val = _this.val();
			lines = _this.parent().parent().attr('data');
			htm = '';
			switch(val){
			case 'sys_data':htm = excelFormat.getDropdownList(lines);if(typeof value == "undefined" || value == '')value=excelFormat.def_value;break;
			case 'fixed_value':htm = excelFormat.getTextbox(lines);break;
			case 'keep_empty':htm = excelFormat.getHiddenEmptyTextbox(lines);break;
			}
			_this.next().html(htm);
			_this.next().children().val(value);
//			_this.next().children().focus();
		},
		getTitle:function(line){
			return $('[name="params[excel_format]['+line+'][title_column]"]').val();
		},
		getType:function(line){
			return $('[name="params[excel_format]['+line+'][data_type]"]').val();
		},
		getValue:function(line){
			return $('[name="params[excel_format]['+line+'][data_value]"]').val();
		},
		setTitle:function(line,value){
			$('[name="params[excel_format]['+line+'][title_column]"]').val(value);
		},
		setTypeAndValue:function(line,Type,Value){
			$('[name="params[excel_format]['+line+'][data_type]"]').val(Type);
			excelFormat.changeExcelDataType("params[excel_format]["+line+"][data_type]",Value);
		},
		setValue:function(line,value){
			$('[name="params[excel_format]['+line+'][data_value]"]').val(value);
		},
		moveLine:function(line,upOrDown){
			title = excelFormat.getTitle(line);
			type = excelFormat.getType(line);
			value = excelFormat.getValue(line);
			line2 = line - upOrDown;
			title2 = excelFormat.getTitle(line2);
			type2 = excelFormat.getType(line2);
			value2 = excelFormat.getValue(line2);
			
			excelFormat.setTitle(line,title2);
			excelFormat.setTypeAndValue(line,type2,value2);
			
			excelFormat.setTitle(line2,title);
			excelFormat.setTypeAndValue(line2,type,value);
			
//			alert(title+','+type+','+value);
//			alert(title2+','+type2+','+value2);
		},
		//隐藏第一行的‘上移’和最后一行的‘下移’,同时取消onclik函数
		hiddenFirstUpLastDown:function(){
			$('.btn-up-0').text('');
			$('.btn-up-0').prop('onclick','');
			Last = excelFormat.lines-1;
			oldLast = Last-1;
			$('.btn-down-'+oldLast).text('下移');
			$('.btn-down-'+oldLast).attr('onclick','excelFormat.moveLine('+oldLast+',-1)');
			$('.btn-down-'+Last).text('');
			$('.btn-down-'+Last).attr('onclick','');
		},
		//删除一行
		delOneLine:function(_this,line){
			if(excelFormat.nums < 0 && excelFormat.lines > 0){
				excelFormat.key--;
				excelFormat.nums = 25;
			}
			last = excelFormat.lines-1;
			if(line != last){
				for(i = line; i < last; i++){
					excelFormat.moveLine(i,-1);
				}
			}
			excelFormat.lines--;
			excelFormat.nums--;
			$('#excelTbody').find('tr[data='+last+']').remove();
			excelFormat.hiddenFirstUpLastDown();
		},
		delAllLine:function(){
			excelFormat.lines = 0;
			excelFormat.nums = 0;
			excelFormat.key = -1;
			$('#excelTbody').html('');
		},
		//初始化dropdownlist的options
		buildOptions:function($array_Data) {
			value = $array_Data;
			if (value instanceof Array || 
					(!(value instanceof Object) && 
					(Object.prototype.toString.call((value)) == '[object Array]') || 
					typeof value.length == 'number' && 
					typeof value.splice != 'undefined' && 
					typeof value.propertyIsEnumerable != 'undefined' && 
					!value.propertyIsEnumerable('splice'))) {
				alert('不为数组');
			}
			else{
			    for(var code in $array_Data){
			    	name = $array_Data[code];
			    	excelFormat.options += "<option value='" + code + "'>" + name + "</option>";
				}
			}
		},
		//根据行数设置val
		setControlValueByLines:function(lines,value){
			$('[name="params[excel_format]['+lines+'][data_value]"]').val(value);
		},
		//获取列号
		getChar:function(){
			if(excelFormat.nums < 0 || excelFormat.nums >= 26){
				excelFormat.nums = 0;
				excelFormat.key++;
			}
	        if(excelFormat.lines >= 0 && excelFormat.lines < 26){
	        	i = excelFormat.nums;
	        	excelFormat.nums++;
	        	excelFormat.lines++;
	            return String.fromCharCode(65 + i);
	        } else {
	            if(excelFormat.key >= 0 && excelFormat.key < 26){
		            char = String.fromCharCode(65 + excelFormat.key) + 
		            		String.fromCharCode(65 + excelFormat.nums);
		            excelFormat.nums++;
		            excelFormat.lines++;
		            return char;
	            }
	            else{
	            	alert('列数超出最大范围！');
	            	return '';
	            }
	        }
	    },
		//插入一行
		insertOneLine:function(title,selected,value){
			if(typeof title == "undefined") title='';
			if(typeof selected == "undefined") selected=excelFormat.def_selected;
			lines = excelFormat.lines;
			char = excelFormat.getChar();
			htm = '<tr data="'+lines+'">';
			htm += '<td>'+char+'</td>';
			htm += '<td><input type="text" class="col-xs-12 req" name="params[excel_format]['+lines+'][title_column]" value="'+title+'"></td>';
			htm += '<td>';
			htm += '<select class="col-xs-6 data_type" name="params[excel_format]['+lines+'][data_type]" onchange="excelFormat.changeExcelDataType(this.name)">';
			htm += '<option value="sys_data">系统数据</option>';
			htm += '<option value="fixed_value">固定值</option>';
			htm += '<option value="keep_empty">保持为空</option>';
			htm += '</select>';
			htm += '<span></span>';
			htm += '</td>';
			htm += '<td>';
			htm += '<a class="col-xs-4 btn btn-xs btn-down-'+lines+'" onclick="excelFormat.moveLine('+lines+',-1)">下移</a>';
			htm += '<a class="col-xs-4 btn btn-xs btn-up-'+lines+'" onclick="excelFormat.moveLine('+lines+',1)">上移</a>';
			htm += '<a class="col-xs-4 btn btn-xs btn-delete" onclick="excelFormat.delOneLine(this,'+lines+')">删除</a>';
			htm += '</td>';
			htm += '</tr>';
			$('#excelTbody').append(htm);
			$('[name="params[excel_format]['+lines+'][data_type]"]').val(selected);
			excelFormat.changeExcelDataType('params[excel_format]['+lines+'][data_type]',value);
			excelFormat.hiddenFirstUpLastDown();
		},
		//
		saveFormat:function(){
			if(excelFormat.lines <= 0){
				alert('请至少添加一列');
			}
			else if(checkREQ()){
				$form = $('#excelformatFORM').serialize();
				var Url=global.baseUrl +'configuration/warehouseconfig/save-excel-format';
				$.ajax({
			      type : 'post',
			      cache : 'false',
			      data : $form,
					url: Url,
			      success:function(response) {
			    	  if(response[0] == 0){
			        		alert(response.substring(2));
//			        		location.reload();
			        		window.location.search='tab_active='+$('#search_tab_active').val();
				        }
			        	else{
			        		alert(response.substring(2)); 
				        }
			      }
				});
			}
		},
		//初始化
		init:function($excel_mode,$excel_format,$array_Data){
			$('[name="params[excel_mode]"][value="'+$excel_mode+'"]').prop("checked",true);
			//初始化数据
			excelFormat.nums = 0;
			excelFormat.lines = 0;
			excelFormat.key = -1;
			excelFormat.buildOptions($array_Data);
			//如果是原来的数据为空，默认显示3行空白
			if(typeof $excel_format != 'object'){
				for(ii = 0; ii < 3; ii++){
					excelFormat.insertOneLine('',excelFormat.def_selected,'');
				}
			}
			else{
				//将原有数据显示出来
				for (var i in $excel_format){
					excelFormat.insertOneLine(
							$excel_format[i]['title_column'],
							$excel_format[i]['data_type'],
							$excel_format[i]['data_value']);
				}
			}
			$('#btn_import_product').click(function(){
				if (typeof(importFile) != 'undefined'){
					form_url = "/configuration/warehouseconfig/excelupload";
					template_url = "/template/商品导入格式-普通商品.xls";
					importFile.showImportModal(
						Translator.t('请选择导入的excel文件') , 
						form_url , 
						template_url , 
						function(result){
//							debugger;
							var ErrorMsg = "";
							if(typeof(result)=='object'){
								if(typeof(result.error)!=='undefined'){
									ErrorMsg += result.error;
								}else{
									//移除所有列
									excelFormat.delAllLine();
									//将导入的数据显示出来
									for (var i in result){
										excelFormat.insertOneLine(result[i],'sys_data','');
									}
								}
							}else{
								ErrorMsg += result;
							}
							if (ErrorMsg != ""){
								ErrorMsg= "<div style='min-height: 100px;overflow: auto;'>"+ErrorMsg+"</div>";
								bootbox.alert(ErrorMsg);
								$('#myModal').modal('show');
							}else{
								$('#myModal').modal('show');
							}
					});
				}else{
					bootbox.alert(Translator.t("没有引入相应的文件!"));
				}
				
			});
		},
}
