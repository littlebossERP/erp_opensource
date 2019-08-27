/**
  +------------------------------------------------------------------------------
 * 客户列表模块list视图js
 +------------------------------------------------------------------------------
 * @list	js/project
 * @package		permission
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof permission === 'undefined')  permission = new Object();
permission.operationloglist = {
	init : function(){
		//选择用户
		$(".select_user_log").change(function(){
			var select_str = '';
			
			if($(this).attr('select_type') == 'all'){
				$(".select_user_log").prop('checked',this.checked);
				if(this.checked){
					select_str = 'all';
				}
			}
			else{
				if(!this.checked){
					$('.select_user_log[select_type="all"]').prop('checked', this.checked);
				}
				
				//设置已选择信息
				var select_str = '';
				$('.select_user_log:checked').each(function(){
					select_str += $(this).val() +',';
				});
			}
			
			$('input[name="select_user_strs"]').val(select_str);
		});
		
		//选择模块
		$(".select_module_log").change(function(){
			var select_str = '';
			
			if($(this).attr('select_type') == 'all'){
				$(".select_module_log").prop('checked',this.checked);
				if(this.checked){
					select_str = 'all';
				}
			}
			else{
				if(!this.checked){
					$('.select_module_log[select_type="all"]').prop('checked', this.checked);
				}
				
				//设置已选择信息
				var select_str = '';
				$('.select_module_log:checked').each(function(){
					select_str += $(this).val() +',';
				});
			}
			
			$('input[name="select_module_strs"]').val(select_str);
		});
		
		$('input[name="startdate"]').change(function(){
			
		});
	}

}


