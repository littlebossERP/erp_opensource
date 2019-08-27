var itemscache=new Array();
showSC();

function showSC(){
	if($('#bar').children('select').length<=0){
		buildItemCatSelect('1','0');
	}
	//$('#bar').show();	
}
function buildItemCatSelect_end(items,pid,level){
	//未存分类 存入缓存
	if(!itemscache[pid+'_'+level]){
		itemscache[pid+'_'+level]=items;
	}
	//return 0;	
	if(!items.length>0){
		return 0;
	};
	var opstrs='';
	$.each(items,function(i,n){
		if(n.leaf==1){
			ta='';
			va='';
		}else{
			ta='is_parent=1';
			va=' > ';
		}
		opstrs+='<option value='+n.categoryid+' '+ ta+'>'+n.name+va+' </option>';
	});
	opstrs='<select size=10 class=selectcat level='+level+' '+'pid='+pid+' onchange=getcat(this)>'+opstrs+'</select>';
	$('#bar').append(opstrs);
	$('select[disabled]').removeAttr('disabled');
	return 0;
}

function buildItemCatSelect(pid,level){
	if(!level) level=1;
	if($('#bar').children('select[level='+level+']').is('[pid='+pid+']')){
		//alert('已经存在'); 
		return 1;
	}else{ //删除 后段
		var maxlevel=$('#bar').children('select[level]').length;
		for(i=level;i<maxlevel;i++){
			$('#bar').children('select[level='+i+']').remove();
		}
	}
	//取得 items
	if(itemscache[pid+'_'+level]){
		buildItemCatSelect_end(itemscache[pid+'_'+level],pid,level);
	}else{
$.getJSON(global.baseUrl+"listing/ebaymuban/get-ebay-cats",{'siteid':$('#siteid').val(),'pid':pid,'level':parseInt(level)+1},
		function(data){
			buildItemCatSelect_end(data,pid,level);
		}
	);
	}
}

function getcat(obj){
	var o=$(obj).children('option[value='+$(obj).val()+']');
	// 未定义 数字变量 时 ,传值会出错.
	var level=(parseInt($(obj).attr('level'))+1);
	var pid=(parseInt($(obj).attr('pid')));
	if(o.is('[is_parent]')){
		obj.disabled =true;

		buildItemCatSelect(o.val(),level);
		if($('#bar').children('.divsubmit').html()!=null){
			$('#bar').children('.divsubmit').remove();
		}
	}else{
		$('input[name=primaryCategory]').val(o.val());
		$('input[name=primaryCategoryName]').val(o.html());
		
		if($('#bar').children('.divsubmit').html()==null){
			$('#bar').append('<div class=\'divsubmit\'><input type=\'button\' value=\'使用该分类\' onclick="saveclose()"></div>');
		}
		buildItemCatSelect('',level);
	}
}
// 操作上一个页面中的 select 项目 
function saveclose(){
	$('#f').submit();
	window.opener.isload();
}