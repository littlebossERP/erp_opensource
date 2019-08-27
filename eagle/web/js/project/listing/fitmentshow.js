//类目变更时自动对类目id的汽配可使用性做验证
$('#primarycategory').change(function(){
	vaildfitment();
});
//选择新平台时，清空类目值
$('select[name=site]').change(function(){
	$('#primarycategory').val('');
});
//如果是编辑的页面，进行页面的重置
$(document).ready(
	function(){
		var fid = $('input[name=fid]').val();
		if(fid != undefined && fid.length>0){
			vaildfitment();
		}
	}
);
//处理页面fitment数据的展示
function vaildfitment(){
	var category = $('#primarycategory').val();
	if(category == undefined || category.length == 0){
		return false;
	}
	$.post(global.baseUrl+"listing/ebaycompatibility/checkcategory",{category:category,siteid:$('select[name=site]').val()},function(r){
		if(r == 'failure'){
			$str = '<span class="failure">该类目不支持汽配兼容</span>';
			$('.choosefitment').html('');
		}else{
			$str = '<span class="success">该类目支持汽配兼容</span>';
		}
		$('.checkcategoryresult').html($str);
		if(r != 'failure'){
			var head = r.split(",");
			var head_str = '<th>操作</th>';
			for(i=0;i<head.length;i++){
				head_str += '<th>'+head[i]+'</th>';
			}
			$('.table_head').html(head_str);
			$('.muti_do').show();
			
			//ajax获取fitment的操作级联狂
			$.get(global.baseUrl+"listing/ebaycompatibility/ajaxgetcompatibilitysearchnames",{category:category,siteid:$('select[name=site]').val(),name:r},function(res){
				$('.choosefitment').html(res);
			});
		}
	});
}

//提交表单时对表单数据填充的验证
function checkdata(){
	var mubanname = $('input[name=mubanname]').val();
	if(mubanname==""){
		bootbox.alert('请填写模板名!');
		return false;
	};
}

//删除特定的fitment范本
function dodel(mubanid){
	$.post(global.baseUrl+'listing/ebaycompatibility/delete',{mubanid:mubanid},function(r){
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败'+r);
		}
	});
}

//编辑或新建汽配范本
function doedit(){
	window.open(global.baseUrl+'listing/ebaycompatibility/edit');
}

//编辑时触发选择刊登类目
function choosecategory(){
	window.open(global.baseUrl+"listing/ebaycompatibility/selectcategroy?siteid="+$('select[name=site]').val()+'&elementid=primarycategory');
}

//验证模板名是否可用
function checkname(name){
	if(name == undefined || name.length == 0){
		bootbox.alert('请输入模板名');return false;
	}
	$.post(global.baseUrl+"listing/ebaycompatibility/checkname",{value:name},function(r){
		if(r == 'success'){
			$str = '<span class="success">该模板名可用</span>';
		}else{
			$str = '<span class="failure">该模板名已存在,建议更换</span>';
		}
		$('.checknameresult').html($str);
	});
}

//删除具体值
function deloccurrence(obj,onlyone)
{
	//点击删除图片删除一条数据
	if(onlyone == "one")
	{
		$(obj).parent().parent().remove();
		return false;
	}
	//删除按钮, 判断是否选中至少一条数据
	if($("input[name='occurrence']:checked").length == 0)
	{
		alert("请勾选您要删除的数据");return false;
	}
	
	$("input[name='occurrence']").each(function(){
		if($(this).prop("checked"))
		{
			$(this).parent().parent().remove();
		}
	});
}

//反选
function inverseall()
{
	$("input[name='occurrence']").each(function(){
		if($(this).prop("checked"))
		{
			$(this).prop("checked",false);
		}
		else
		{
			$(this).prop("checked", "checked");
		}
	});
}

//全不选
function cancel()
{
	$("input[name='occurrence']").each(function(){
		$(this).prop("checked",false);
	});
}

//全选
function checkalloccurrence()
{
	$("input[name='occurrence']").each(function(){
		$(this).prop("checked",true);
	});
}

//从再现Item抓取fitment数据
function getfromitem(){
	//$('#getitemfitmentModal .modal-content').html();
   	$('#getitemfitmentModal').modal('show');
}

//从在线Item抓取fitment数据的后台逻辑处理
function ajaxgetfromitem(){
   	//数据验证
	var itemid = $('input[name=itemid]').val();
	if(itemid == undefined || itemid ==""){
		bootbox.alert("请输入ItemID");return false;
	}
	if(isNaN(itemid)){
		bootbox.alert("请确认您输入的是ItemID");return false;
	}
	var mubanname = $('input[name=mubanname]').val();
	if(mubanname == undefined || mubanname ==""){
		bootbox.alert("请输入模板名");return false;
	}
	$.showLoading();
	$.post(global.baseUrl+'listing/ebaycompatibility/ajaxgetfromitem',{itemid:itemid,mubanname:mubanname},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败:'+r);
		}
	});
}