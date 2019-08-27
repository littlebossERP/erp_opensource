function checkall(){
	if($("#ck_0").is(':checked')==true){
		$(".ck").prop("checked","checked");
	}else{
		$(".ck").prop("checked",false);
	}
}

//删除1个草稿箱数据
function delone(id){
	$.showLoading();
	$.post(global.baseUrl+"collect/collect/delebay",{ids:id},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败:'+r);
		}
	});
}

//批量删除草稿箱数据
function delall(){
	ids = getallid();
	if(ids == false){
		return false;
	}
	delone(ids);
}

//移动一个草稿到待发布
function moveone(id){
	$.showLoading();
	$.post(global.baseUrl+"collect/collect/movetofanben",{ids:id},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败:'+r);
		}
	});
}

//立即发布一个草稿
function additemone(id){
	$.showLoading();
	$.post(global.baseUrl+"collect/collect/additemone",{ids:id},function(r){
		$.hideLoading();
		bootbox.alert(r);
	});
}

//下拉操作草稿箱
function doaction(obj){
	var ids = arguments[1]?arguments[1]:getallid();
	switch(obj){
		case 'delete':
			delone(ids);
			break;
		case 'mutiedit':
			window.open(global.baseUrl+"collect/collect/ebaymutiedit?mubanid="+ids);
			break;
		case 'edit':
			window.open(global.baseUrl+"collect/collect/ebayedit?mubanid="+ids);
			break;
		case 'movetowait':
			moveone(ids);
			break;
		case 'additem':
			additemone(ids);
			break;
		case 'dingshi':
			break;
		default:
			break;
	}
}

//获取选中的数据ID，如没有数据，则返回false
function getallid(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的采集数据");exit();
    }
	idstr='';
	$('input[name="collect[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	return idstr;
}