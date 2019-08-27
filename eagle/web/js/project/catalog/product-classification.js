/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		product
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof classificationList === 'undefined')  classificationList = new Object();

classificationList={
	init:function(){
		//分类查询
		$(".chooseTreeName").click(function(){
			var class_id = $(this).attr('class_id');
			
			var obj = $(this).parents('.classification').first();
			if(obj != undefined && obj.attr('class_key') == 'inventory'){
				window.location.href=global.baseUrl+'inventory/inventory/index?class_id='+class_id;
			}
			else{
				window.location.href=global.baseUrl+'catalog/product/index?class_id='+class_id;
			}
		});
		//分类缩放
		$(".gly").click(function(){
			if($(this).attr('data-isleaf')=='open'){
				$(this).removeClass('glyphicon-triangle-bottom');
				$(this).addClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','close');
				
				$(this).parent().next().css('display','none');
			}
			else{
				$(this).addClass('glyphicon-triangle-bottom');
				$(this).removeClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','open');
				$(this).parent().next().css('display','block');
			}
		});
		//分类设置
		$(".class_setting").click(function(){
			$.modal({
				url:'/catalog/product/product-classifica',
				method:'POST',
				data:{},
			},
			'商品分类',{footer:false,inside:false}).done(function($modal){
				$('.modal-box').find('nav').remove();
				$('.modal-box').find('footer').remove();
				
				$(".btn-primary").click(function(){
					$modal.close();
					window.location.reload();
				});
			});
		});
		
	},
}

$(function() {
	classificationList.init();
	
});
