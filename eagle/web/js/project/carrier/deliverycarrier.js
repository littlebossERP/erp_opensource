/**
 +------------------------------------------------------------------------------
 *物流商参数
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		carrier
 * @subpackage  Exception
 * @author		qfl <fulin.qu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
$('#addnewbutton').click(function(){
	$('#create_form').append($('#backupdiv').html());
})
$('#savebutton').click(function(){
	$.ajax({
		url:paramsUrl,
		data:$('#create_form').serialize(),
		type:'post',
		success:function(response){
			if(response=='paramserror')alert('参数值格式有误,请检查');
			else if(response=='error')alert('保存失败,请检查后重试');
			else if(response=='success'){
				alert('保存成功');
				location.href = indexUrl;
			}
		}
	})

})
var delparam = function(me){
	$(me).parents('.table-striped').remove();
}