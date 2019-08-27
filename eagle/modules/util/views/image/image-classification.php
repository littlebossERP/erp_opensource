<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderFrontHelper; 
use Qiniu\json_decode;
?>

<style>
.modal-box{
	width:600px;
}
.ztree {
    margin: 0;
    padding: 5px;
    color: #333;
	font-family: "Arial","Microsoft YaHei","黑体","宋体",sans-serif !important;
}
.ztree li {
    padding: 0;
    margin: 0;
    list-style: none;
    line-height: 14px;
    text-align: left;
    white-space: nowrap;
    outline: 0;
	margin-top: 3px;
}
.ztree li a {
    padding: 1px 3px 0 0;
    margin: 0;
    cursor: pointer;
    height: 22px;
    color: #333;
    background-color: transparent;
    text-decoration: none;
    vertical-align: top;
    display: inline-block;
	line-height: 19px;
}
.ztree li ul {
    margin: 0;
    padding: 0 0 0 18px;
}
.ztree * {
    font-size: 16px;
}
.ztree li span.button.noline_open {
    background-position: -92px -72px;
}
.ztree li span.button.switch {
    width: 18px;
    height: 18px;
}
.ztree li span.button {
    /*line-height: 0;*/
    margin: 0;
    width: 16px;
    height: 16px;
    display: inline-block;
   /*vertical-align: middle;*/
    border: 0 none;
    cursor: pointer;
    outline: none;
    background-color: transparent;
    background-repeat: no-repeat;
    background-attachment: scroll;
    background-image: url(./img/zTreeStandard.png);
}
ul.ztree span.glyphicon-triangle-right, ul.ztree span.glyphicon-triangle-bottom, ul.ztree span.glyphicon-edit, ul.ztree span.glyphicon-remove{
    cursor: pointer;
}
.bgeee{
	background-color:#76b6ec!important;
}
.ztree li span.button.add {
    margin-left: 5px;
    margin-right: -1px;
    background-position: -144px 0;
    vertical-align: top;
	line-height: 19px;
}
.ztree li span.button.edit {
    margin-right: 2px;
    background-position: -110px -48px;
    vertical-align: top;
	margin-left: 6px;
	line-height: 19px;
}
.ztree li span.button.remove {
    margin-right: 2px;
    background-position: -110px -64px;
    vertical-align: top;
	margin-left: 2px;
	line-height: 19px;
}
.displays{
	display:none!important;
}
.new{
	margin-left: 4px!important;
}
.modal-footer {
	border-top: 1px solid #e5e5e5!important;
	margin-bottom: -44px!important;
}
</style>

<div>
	<ul id="categoryTreeB_0_ul_0" class="ztree">
		<li id="categoryTreeB_0" class="level0" tabindex="0">
			<span id="categoryTreeB_0_switch" title="categoryTreeB_1_ul_0" class="gly1 glyphicon glyphicon-triangle-bottom pull-left" data-isleaf="open"></span>
			<a id="categoryTreeB_0_a" class="level0 curSelectedNode" target="_blank" style="">
				<span id="categoryTreeB_0_ico" title=""  class="button ico_open" style="width:0px;height:0px;"></span>
				<span id="categoryTreeB_0_span">所有分类</span>
				<span class="button add glyphicon glyphicon-plus" id="addBtn_categoryTreeB_0" title="添加分类"></span>
			</a>
			
			<?php echo $html; ?>		
		</li>
	</ul>
</div>

<div><input id="removeli" type="hidden" value=""><input id="addli" type="hidden" value="0"></div>

<div class="modal-footer col-xs-12 w1009">
	<button type="button" class="btn btn-primary queding">确定</button>
	<button class="btn-default btn modal-close">取消</button>
</div>

<script>
$(document).on('mouseover','a',function(){
	if($(this).attr('id')!=null){
		$(this).addClass('bgeee');
		$index=$(this).attr('id').replace('categoryTreeB_', '').replace('_a', '');;
// 		$('#categoryTreeB_'+$index+'_span').css('text-decoration','underline');
		$(this).find('span').eq(0).css('text-decoration','underline');
		$(this).find('.add').removeClass('displays');
		$(this).find('.edit').removeClass('displays');
		$(this).find('.remove').removeClass('displays');
	}
}); 

$(document).on('mouseout','a',function(){
	if($(this).attr('id')!=null){
		$(this).removeClass('bgeee');
		$index=$(this).attr('id').replace('categoryTreeB_', '').replace('_a', '');
// 		$('#categoryTreeB_'+$index+'_span').css('text-decoration','none');
		$(this).find('span').eq(0).css('text-decoration','none');
		if($index!='0'){
			$(this).find('.add').addClass('displays');
			$(this).find('.edit').addClass('displays');
			$(this).find('.remove').addClass('displays');
		}
	}
}); 
//增加
$(document).off('click','.add').on('click','.add',function(){
	var delid=$(this).attr('id').replace('addBtn_', '');
	//增加标识
	$index=$('#addli').val();
	$index=parseInt($index)+1;
	$('#addli').val($index);

	if(delid=="categoryTreeB_-"){//增加后增加
		title=$(this).parent().parent().find('span[data-isleaf="open"]').attr('title');
	}
	else{
		title=$('#'+delid+'_switch').attr('title');
	}
	$temp=$(this).parent().parent();
	$par=''; //父节点标识

	if(title=='' || title==null){
		//本身没有子目录
		title="categoryTreeB_-_ul_-";
		$tabindex=0;
		$level=$temp.attr("class").replace("level","");
		$level=parseInt($level)+1;
		$level='level'+$level;
		
		$par=delid.replace("categoryTreeB_","");
		if($par=='-')
			$par=$temp.attr("name");
	}
	else if(title=='categoryTreeB_-_ul_-'){
		//增加子目录后再增加
		$tabindex=1;
		$level=$temp.attr("class").replace("level","");
		$level=parseInt($level)+1;
		$level='level'+$level;
		
		$par=$temp.attr("name");
		if($par=='' || $par==null)
			$par=$temp.attr("id").replace("categoryTreeB_","");
	}
	else{
		$tabindex=$('#'+title).find('li').length;
		$level=$('#'+title).attr('class');
		
		$par=delid.replace("categoryTreeB_","");
	}

	$html='';
	if($tabindex==0){
		$html+='<ul id="'+title+'" class="'+$level+'" name="ul_'+$index+'" style="display:block;margin-left:10px;">';
	}
	$html+='<li id="categoryTreeB_-" name="li_'+$index+'" class="'+$level+'" tabindex="'+$tabindex+'" >';

	$html+='<a id="categoryTreeB_-_a" name="a_'+$index+'" class="'+$level+'"  onclick="" target="_blank" style="">'+
			'<span id="categoryTreeB_-_span" name="span_'+$index+'" data-par="'+$par+'" style="">新分类</span>';
	
	if($level!='level3')
		$html+='<span class="button add glyphicon glyphicon-plus displays" id="addBtn_categoryTreeB_-" name="addBtn_'+$index+'" title="添加分类" ></span>';
	
	$html+='<span class="button edit glyphicon glyphicon-edit displays new" id="editBtn_categoryTreeB_-" name="editBtn_'+$index+'" title="更改分类名" ></span>'+
			'<span class="button remove glyphicon glyphicon-remove displays new" id="removeBtn_categoryTreeB_-" name="removeBtn_'+$index+'" title="删除分类" ></span>'+
			'</a></li>';

	if($tabindex==0){
		$html+='</ul>';
	}

	if($tabindex==0){
		//添加三角形
		$temp.find('a').eq(0).before('<span id="categoryTreeB_'+delid.replace("categoryTreeB_","")+'_switch" title="'+title+'" class="gly1 glyphicon glyphicon-triangle-bottom pull-left" data-isleaf="open"></span>');
		$temp.find('a').eq(0).after($html);
	}
	else if($tabindex==1){
		$temp.find('ul[class='+$level+']').append($html);
	}
	else
		$('#'+title).append($html);
	
});
//修改
$(document).off('click','.edit').on('click','.edit',function(){
	var delid=$(this).attr('id').replace('editBtn_', '');
	$oldid=delid.replace('categoryTreeB_', '');

	if($oldid=="-"){
		$add_index=$(this).attr('name').replace('editBtn_', '');
		$oldname=$('span[name=span_'+$add_index+']').html();
		$('span[name=span_'+$add_index+']').html('<input name="edittext" id="edittext-'+$oldid+'" type="text" value="'+$oldname+'">');
		$('span[name=span_'+$add_index+']').addClass('editspan');
		$('span[name=span_'+$add_index+']').find('input[name=edittext]').focus();
	}
	else{
		$oldname=$('#'+delid+'_span').html();
		$('#'+delid+'_span').html('<input name="edittext" id="edittext-'+$oldid+'" type="text" value="'+$oldname+'">');
		$('#'+delid+'_span').addClass('editspan');
		$('input[name=edittext]').focus();
	}

});
//编辑失去焦点
$(document).on('blur','input[name=edittext]',function(){
	$newname=$(this).val();
	$divid=$(this).attr('id').replace('edittext-', '');

	if($divid=="-"){
		$add_index=$(this).parent().attr('name').replace('span_', '');;
		$('span[name=span_'+$add_index+']').html($newname);
	}
	else
		$('#categoryTreeB_'+$divid+'_span').html($newname);
});
//删除
$(document).off('click','remove').on('click','.remove',function(){
	var delid=$(this).attr('id').replace('removeBtn_', '');
	
	//记录哪些删除了
	var arr_del=$('#removeli').val();
	if($('#'+delid).attr('id')!="categoryTreeB_-"){
		if($('#'+delid).attr('id')!=null)
			arr_del+=$('#'+delid).attr('id').replace('categoryTreeB_', '')+';';
	}
	$('#removeli').val(arr_del);

	//没有子节点时删三角形
	if($('#'+delid).attr('id')!="categoryTreeB_-"){
		$num=$('#'+delid).parent().find('li[class='+$('#'+delid).parent().attr('class')+']').length;
		if($num<2){
			$('#'+delid).parent().prev().prev().remove();
			$('#'+delid).parent().remove();
		}
		else
			$('#'+delid).remove();
	}
	else{
		$add_index=$(this).attr('name').replace('removeBtn_', '');
		$num=$('li[name=li_'+$add_index+']').parent().find('li[class='+$('li[name=li_'+$add_index+']').parent().attr('class')+']').length;
		if($num<2){
			$('li[name=li_'+$add_index+']').parent().prev().prev().remove();
			$('li[name=li_'+$add_index+']').parent().remove();
		}
		$('li[name=li_'+$add_index+']').remove();
	}
});
//展开隐藏
$(".gly1").click(function(){
	if($(this).attr('data-isleaf')=='open'){
		$(this).removeClass('glyphicon-triangle-bottom');
		$(this).addClass('glyphicon-triangle-right');
		$(this).attr('data-isleaf','close');
		$('#'+$(this).attr('title')).css('display','none');
	}
	else{
		$(this).addClass('glyphicon-triangle-bottom');
		$(this).removeClass('glyphicon-triangle-right');
		$(this).attr('data-isleaf','open');
		$('#'+$(this).attr('title')).css('display','block');
	}
});

</script>