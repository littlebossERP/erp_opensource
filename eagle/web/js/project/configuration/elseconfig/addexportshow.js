if (typeof ExportJS === 'undefined')  ExportJS = new Object();
ExportJS={
		init:function(){	
			/*$(document).ready(function(){
				tempId=$('#number').val();
				$.ajax({
			        type: "POST",
			        url: global.baseUrl+'order/excel/get-order-excel-id',
			        data: {"id":tempId},
			        dataType: "json",
			        success: function(data){
			        	if(data.code == 0){
			        		var field = data.orderField;
			        		if (field != ''){
			        				$.ajax({
			        			        type: "POST",
			        			        url: global.baseUrl+'configuration/elseconfig/export-html-table-view',
			        			        data: {"arr":field,
			        				        "templateType":'elseconfig'},
			        			        success: function(data){
			        			        	$('#exportTable').html('');
			        						$('#exportTable').append(data);
			        			        }
			        			    });
			        		}
			        	}
			        }
			    });
			});*/
			
			//移除
			$(document).on('click','.tdRemove',function(){
				$(this).parent('div').remove();
				$val=$(this).parent().find('.spanObj').data('field');
				$('#exportForm').find('span[data-field='+$val+']').parent().removeClass("orderExportActive");
			});
			
			//编辑
			$(document).on('click','.tdPencil',function(){
				$name=$(this).prev('span').html();
				$customname=$(this).prev('span').attr('data-customname');
				$ordername=$(this).prev('span').attr('ordername');
				$val=$(this).prev('span').data('field');
				$cvalue=$(this).prev('span').attr('data-value');
				$title='编辑-'+$name;
				$.modal({
					  url:'/configuration/elseconfig/editcontent',
					  method:'post',
					  data:{name:$name,val:$val,cusval:$cvalue,customname:$customname,ordername:$ordername}
					},$title,{footer:false,inside:false}).done(function($modal){
						//不清楚为什么上一级窗口多次关闭后，再打开这个窗口就会运行多次这个事件，所以作判断只生成一个窗口
						if($(".two").length>0){
							$modal.close();
							return false;
						}
						$height=parseInt($('.modal-box').eq(0).height())+parseInt($('.modal-box').eq(0).css("marginTop"))+parseInt($('.modal-box').eq(0).css("marginBottom"));
						$('.modal-box').eq(0).after('<div class="modal-backdrop  in" style="height:'+$height+'px;z-index:0;">');//插入遮盖
						$modal.addClass('two');
						
						$('.alertsave').click(function(){
							$newname=$('.newName').val();
							$customname=$('.orderTitEditName').data('customname');
							$val=$('.orderTitEditName').data('field');
							$value='';
							if($val=='custom')
								$value=$('.newValue').val();
							if($.trim($newname)=='')
								$newname=$name;
							$('.myj-table').find('span[data-customname='+$customname+']').html($newname);
							if($val=='custom')
								$('.myj-table').find('span[data-customname='+$customname+']').attr('data-value',$value);
							
							$('.modal-backdrop').remove();
							$modal.close();
						});
						
						$('.modal-close').click(function(){
							$('.modal-backdrop').remove();
							$modal.close();
						});
					}
					);
			});
			
			//添加或移除
			$('.addTab').click(function(){
				if(!$(this).hasClass("orderExportActive")){
					$name=$(this).find('.addTabContent').html();
					$val=$(this).find('.addTabContent').data('field');
					if($val!='custom'){
						$(this).addClass('orderExportActive');
					}
					$custom_newname=1;
					$(".myj-table").find("[data-field=custom]").each(function(){
						if(parseInt($(this).attr('data-customname'))>$custom_newname)
							$custom_newname=$(this).attr('data-customname');
					});
					$custom_newname=parseInt($custom_newname)+1;
					var t01 = $(".myj-table tr").length;
					$out=0;
					$i_new=1;
					for($i=0;$i<6;$i++){
						for($j=0;$j<t01;$j++){
							$td=$('.myj-table').find('td[data-names='+$i_new+']').html();
							if($.trim($td)==''){
								if($val!='custom')
									$('.myj-table').find('td[data-names='+$i_new+']').html('<div class="tdDivCont"><span class="glyphicon glyphicon-remove tdRemove pull-left mTop5"></span><span class="spanObj" data-field="'+$val+'" data-customname="'+$val+'" data-value="" ordername=" '+$name+'"> '+$name+'</span><span class="glyphicon glyphicon-pencil tdPencil pull-right mTop5"></span></div>');
								else
									$('.myj-table').find('td[data-names='+$i_new+']').html('<div class="tdDivCont"><span class="glyphicon glyphicon-remove tdRemove pull-left mTop5"></span><span class="spanObj" data-field="'+$val+'" data-customname="'+$custom_newname+'" data-value="" ordername=" '+$name+'"> '+$name+'</span><span class="glyphicon glyphicon-pencil tdPencil pull-right mTop5"></span></div>');
								$out=1;
								return false;
							}
							$i_new++;
						}
//						$i_new=$i+1+t01;
						if($out==1)
							return false;
					}
				}
				else{
					$(this).removeClass('orderExportActive');
					$val=$(this).find('.addTabContent').data('field');
					$('.myj-table').find('span[data-field='+$val+']').parent().remove();
				}
			});
			
			//重置
			$('.orderExportReset').click(function(){
				$('.myj-table').find('td').html('');
				$('.myj-table2').find('div').removeClass('orderExportActive');
			});
		}
}

//保存
function exportshowsave(val){
	$content='';	
	var t01 = $(".myj-table tr").length;
	$i_new=1;
	for($i=0;$i<6;$i++){
		for($j=0;$j<t01;$j++){
			$re_obj=$('.myj-table').find('td[data-names='+$i_new+']');
			var $result = $re_obj.html();
			if ($.trim($result)!='') {
				if($re_obj.find('span[class=spanObj]').data('field')=='custom'){
					$field='-'+$.trim($re_obj.find('span[class=spanObj]').data('field'))+'-'+$.trim($re_obj.find('span[class=spanObj]').data('customname'));
					$content=$content+$field+':'+$.trim($re_obj.find('span[class=spanObj]').html())+':'+$re_obj.find('span[class=spanObj]').data('value')+',';
				}
				else
					$content=$content+$.trim($re_obj.find('span[class=spanObj]').data('field'))+':'+$.trim($re_obj.find('span[class=spanObj]').html())+':'+$re_obj.find('span[class=spanObj]').data('value')+',';
			}
			$i_new++;
		}
//		$i_new=($i+1)*t01+1;
	}

	$name=$('#templateName').val();
	if($name==''){
		bootbox.alert({
			title:'无效操作',
			message:Translator.t('自定义范本名不能为空'),
		});
		return false;
	}
	
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	content:$content,
        	name:$name,
        	val:val,
        },
        dataType: 'json',
		url: '/configuration/elseconfig/savecontent',
        success:function(response) {
        	bootbox.alert(response.msg, function (res) {
                	location.reload();
        	});
        }
    });
}
