/**
 * 运输服务匹配规则
 * @author million 2015-03-05
 */
$(function(){
	//点击checkbox批量选择
	$('.all-select').click(function(){
		if($(this).prop('checked')){
			$(this).parent().parent().find('input[type=checkbox]:visible').prop('checked','checked');
		}else{
			$(this).parent().parent().find('input[type=checkbox]:visible').removeAttr('checked');
		}
	})
	
	//点击匹配项，隐藏和展示匹配条件
	$('input[name^=rules]').click(function(){
		var n = $(this).val();
		if($(this).prop('checked')){
			$("."+n).removeClass('sr-only');
			$('#myTab a[href="#'+n+'"]').tab('show');
		}else{
			$("."+n).addClass('sr-only');
			$('#myTab a:first').tab('show') 
		}
	})
	
	
	//订单来源
	$('input[name^=source]').click(function(){
		$('input[name^=source]').each(function(){
			var n = $(this).val();
			if($(this).prop('checked')){
				$("."+n).removeClass('sr-only');
			}else{
				$("."+n).addClass('sr-only');
			}
		})
	})
	//点击站点
	$('input[name^=site]').click(function(){
		$('input[name^=site]').each(function(){
			var n = $(this).val();
			var platform = $(this).parent().parent().attr('platform');
			if($(this).prop('checked')){
				$("."+platform+n).removeClass('sr-only');
			}else{
				$("."+platform+n).addClass('sr-only');
			}
		})
	})
	$('.site').click(function(){
		$('input[name^=site]').each(function(){
			var n = $(this).val();
			var platform = $(this).parent().parent().attr('platform');
			if($(this).prop('checked')){
				$("."+platform+n).removeClass('sr-only');
			}else{
				$("."+platform+n).addClass('sr-only');
			}
		})
	})
	$('#myTab a:first').tab('show');
	
	$(".display_toggle").click(function(){
		obj=$(this).next();
		var hidden = obj.css('display');
		if(typeof(hidden)=='undefined' || hidden=='none'){
			obj.css('display','block');
		}else if( hidden=='block'){
			obj.css('display','none');
		}
	});
	$(".display_all_transportation_toggle").click(function(){
		$('.transportation').each(function(){
			obj=$(this);
			var hidden = obj.css('display');
			if(typeof(hidden)=='undefined' || hidden=='none'){
				obj.css('display','block');
			}else if( hidden=='block'){
				obj.css('display','none');
			}
		})
	});
	
	
	$(".display_all_country_toggle").click(function(){
		$('.region_country').each(function(){
			obj=$(this);
			var hidden = obj.css('display');
			if(typeof(hidden)=='undefined' || hidden=='none'){
				obj.css('display','block');
			}else if( hidden=='block'){
				obj.css('display','none');
			}
		})
	});
	
});
//检查提交数据
function checkform(){
	$(".sr-only").remove();
	return true;
}
