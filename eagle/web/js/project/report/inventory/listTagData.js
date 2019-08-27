/**
 +------------------------------------------------------------------------------
 * 库存模块 多标签统计 添加统计对象
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof listTagData === 'undefined')  listTagData = new Object();
listTagData={
		init: function() {
			
			$("#cbx-listTag-all").click(function(){
				if ($(this).is(':checked')){
					$(".class-tagsList").prop("checked", true);
				}else{
					$(".class-tagsList").prop("checked", false);
				}
			});
			
			$(".class-tagsList").click(function(){
				if ($("#cbx-listTag-all").is(':checked')){
					$("#cbx-listTag-all").prop("checked", false);
				}
			});
		},
}