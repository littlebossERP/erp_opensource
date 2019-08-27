if (typeof insertTrackJS === 'undefined')  insertTrackJS = new Object();

insertTrackJS = {
		init:function(){
			$('#saveInsert').click(function(){
				$sform = $('#insertTrackFORM').serialize();
				var Url=global.baseUrl +'configuration/carrierconfig/save-track';
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : $sform,
					url: Url,
			        success:function(response) {
			        	if(response != '0'){
			        		if(response[0] == '*'){
			        			bootbox.alert(response.substr(1));
			        		}
			        		else{
			        			bootbox.alert('<p>系统内已存在的跟踪号：</p><p>'+response+'</p><p>其他跟踪号已成功添加</p>',function() {location.reload();});
			        		}
			        	}else {
			        		//location.reload();
			        		if($('#style').val()=="1")
			        			window.location=global.baseUrl+"configuration/carrierconfig/index?tab_active=customtracking&tcarrier_code="+$('#search_carrier_code').val();
			        		else
			        			window.location=global.baseUrl+"configuration/carrierconfig/index?tab_active=trackwarehouse";
			        	}
			        }
			    });
			});
			$('input[name=insertType]').change(function(){
				$type = $(this).val();
				for($i = 0; $i < 3; $i++){
					$('#insertType'+$i).hide();
				}
				$('#insertType'+$type).show();
			});
			$('#scanIn').keypress(function(){
				if(event.keyCode == "13"){
					val = $(this).val();
					if(val == ""){
						alert('扫描的跟踪号为空！');
					}
					else{
						val += '\n';
						val += $('#scanText').val();
						$('#scanIn').val('');
						$('#scanText').val(val);
					}
				}
			});
			$('#btn_import_product').click(function(){
				if (typeof(importFile) != 'undefined'){
					form_url = "/configuration/carrierconfig/exceluploadall";
					template_url = "/template/跟踪号导入模版.xls";
					importFile.showImportModal(
						Translator.t('请选择导入的excel文件') , 
						form_url , 
						template_url , 
						function(result){
							debugger;
							var ErrorMsg = "";
							if(typeof(result)=='object'){
								if(typeof(result.error)!=='undefined'){
									ErrorMsg += result.error;
								}else{
									val = '';
									//将导入的数据显示出来
									for (var r in result){
										if(r != '1')
										for(var v in result[r]){
											if(v != 'A') break;
//											alert(v+','+result[r][v]);
											val += result[r][v]+'\n';
										}
									}
									if(val != '')
										$('#exceltext').val(val);
								}
							}else{
								ErrorMsg += result;
							}
							if (ErrorMsg != ""){
								ErrorMsg= "<div style='min-height: 100px;overflow: auto;'>"+ErrorMsg+"</div>";
								bootbox.alert(ErrorMsg);
							}else{
//								bootbox.alert(Translator.t('导入成功'));
							}
							
					});
				}else{
					bootbox.alert(Translator.t("没有引入相应的文件!"));
				}
				
			});
		},
};