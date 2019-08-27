/**
 +------------------------------------------------------------------------------
 *统计 通用js
 +------------------------------------------------------------------------------
 * @category	js/project/statistics
 * @package		profit
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof statistics === 'undefined')  statistics = new Object();
statistics.list=
{
	'init': function() 
	{
		$.initQtip();
		//默认日期
		var date = new Date();
		$('#statistics_enddate').val(date.getFullYear() +'-'+ (date.getMonth() + 1) +'-'+ date.getDate());
		date.setMonth(date.getMonth() - 1);  
		$('#statistics_startdate').val(date.getFullYear() +'-'+ (date.getMonth() + 1) +'-'+ date.getDate());
		
		$('#statistics_startdate').datepicker({dateFormat:"yy-mm-dd"});
		$('#statistics_enddate').datepicker({dateFormat:"yy-mm-dd"});
		
		//全选、取消平台
		$("input[name='select_platform_all']").change(function(){
			statistics.list.selectplatformAll(this);
		});
		
		//单个勾选、取消平台
		$("input[name='select_platform']").change(function(){
			statistics.list.selectplatform(this);
		});
		
		//全选、取消店铺
		$("input[name='select_store_all']").change(function(){
			$("input[name='select_store']:visible").prop('checked',this.checked);
		});
		
		//全选、取消订单类型
		$("input[name='select_order_type_all']").change(function(){
			$("input[name='select_order_type']:visible").prop('checked',this.checked);
		});
	},
	
	//全选、取消平台
	'selectplatformAll' : function (check) 
	{
		if(check.checked){
			//勾选所有的平台
			$("input[name='select_platform']").prop('checked',true);
			//勾选并显示所有的店铺
			$("input[name='select_store']").prop('checked',true);
			$("input[name='select_store']").parent().css('display',"");
			
			$("input[name='select_store_all']").prop('checked',true);
		}
		else{
			//取消勾选所有的平台
			$("input[name='select_platform']").prop('checked',false);
			//取消勾选并屏蔽所有的店铺
			$("input[name='select_store']").prop('checked',false);
			$("input[name='select_store']").parent().css('display',"none");
			
			$("input[name='select_store_all']").prop('checked',false);
		}
		
		//当存在订单类型时
		if($("#div_order_type")){
			statistics.list.CheckShowOrderType();
		}
	},
	
	//单个勾选、取消平台
	'selectplatform' : function (platform) 
	{
		var platformname = '( '+ $(platform).val() +' )';
		$("input[name='select_store'][info$='"+ platformname +"']").prop('checked',platform.checked);
		
		if(platform.checked){
			//显示对应店铺
			$("input[name='select_store'][info$='"+ platformname +"']").parent().css('display',"");
			
			$("input[name='select_store_all']").prop('checked',true);
		}
		else{
			//屏蔽对应店铺
			$("input[name='select_store'][info$='"+ platformname +"']").parent().css('display',"none");
		}
		
		//当存在订单类型时
		if($("#div_order_type")){
			if(platform.checked){
				//当是Amazon，则显示FBA订单勾选项
				if(platformname == '( amazon )')
					$("#order_type_fba").prop('checked',true);
				else if(platformname == '( cdiscount )')
					$("#order_type_fbc").prop('checked',true);
			}
			else{
				//当是Amazon，则屏蔽FBA订单勾选项
				if(platformname == '( amazon )')
					$("#order_type_fba").prop('checked',false);
				else if(platformname == '( cdiscount )')
					$("#order_type_fbc").prop('checked',false);
			}
			
			statistics.list.CheckShowOrderType();
		}
	},
	
	//判断是否显示订单类型框
	'CheckShowOrderType' : function () 
	{
		$("#order_type_fba").css('display',"none");
		$("#order_type_fbc").css('display',"none");
		
		//当已勾选平台存在amazon、cdiscount，则显示
		var status = 0;
		$("input[name='select_platform']:checked").each(function(){
			var val = $(this).val();
			if(val == 'amazon'){
				$("#order_type_fba").css('display',"");
				status = 1;
			}
			else if(val == 'cdiscount'){
				$("#order_type_fbc").css('display',"");
				status = 1;
			}
		});
		
		if(status == 1){
			$("#div_order_type").css('display',"");
		}
		else
			$("#div_order_type").css('display',"none");
	},
	
}
