function checkall(){
	if($("#ck_0").is(':checked')==true){
		$(".ck").prop("checked","checked");
	}else{
		$(".ck").prop("checked",false);
	}
}

//删除1个采集数据
function delone(id){
	$.showLoading();
	$.post(global.baseUrl+"collect/collect/del",{ids:id},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败:'+r);
		}
	});
}

//批量删除采集数据
function delall(){
	ids = getallid();
	if(ids == false){
		return false;
	}
	delone(ids);
}

//单个认领操作
//obj：用户选择的平台.id操作的id
function renlingone(obj,id){
	$.showLoading();
	switch(obj){
		case 'ebay':
			$.post(global.baseUrl+"collect/collect/renlingebay",{ids:id},function(r){
				$.hideLoading();
				if(r=='success'){
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert('操作失败:'+r);
				}
			});
			break;
		default:
			$.hideLoading();
			break;
	}
}

//批量认领
function renling(obj){
	ids = getallid();
	if(ids == false){
		return false;
	}
	renlingone(obj,ids);
}

//获取选中的数据ID，如没有数据，则返回false
function getallid(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的采集数据");return false;
    }
	idstr='';
	$('input[name="collect[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	return idstr;
}