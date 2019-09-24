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

if (typeof classificationSetting === 'undefined')  classificationSetting = new Object();

classificationSetting={
	init:function(){
		//分类查询
		$(".chooseTreeName").click(function(){
			$class_id=$(this).attr('class_id');
			window.location.href=global.baseUrl+'catalog/product/index?class_id='+$class_id; 
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
		//缩放
		$(".gly1").click(function(){
			if($(this).attr('data-isleaf')=='open'){
				$(this).removeClass('glyphicon-triangle-bottom');
				$(this).addClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','close');
				
				$(this).parent().find('ul').css('display','none');
			}
			else{
				$(this).addClass('glyphicon-triangle-bottom');
				$(this).removeClass('glyphicon-triangle-right');
				$(this).attr('data-isleaf','open');
				$(this).parent().find('ul').css('display','block');
			}
		});
		
		//增加下级分类
		$('.class_add').click(function(){
			var node = $(this).parents('li').first();
			var node_number = node.attr('node_number');

			$.showLoading();
			$.ajax({
				type : 'post',
			    cache : 'false',
			    dataType: 'json',
			    data: {'node_number': node_number},
				url:'/catalog/product/add-classifica', 
				success: function (result) 
				{
					$.hideLoading();
					if(result.success){
						//判断是否有子节点
						var is_chli = false;
						if(node.find('ul').length > 0){
							is_chli = true;
						}
						var add_html = '';
						if(result.number.length < 6){
							add_html = '<span class="button class_add glyphicon glyphicon-plus" id="addBtn_categoryTreeB_" title="添加分类" ></span>';
						}

						var button_html = 
							'<a id="categoryTreeB__a" class="level" target="_blank" style="">'+
								'<span id="categoryTreeB__span" class="class_name"  style="">新分类</span>'+
								'<span class="button class_remove glyphicon glyphicon-remove" id="removeBtn_categoryTreeB_" title="删除分类" ></span>'+
								'<span class="button class_edit glyphicon glyphicon-edit" id="editBtn_categoryTreeB_" title="更改分类名" ></span>'+
								add_html+
							'</a>';
						
						if(is_chli){
							//已存在下级节点
							var html = '<li node_number="'+ result.number +'" node_id="'+ result.node_id +'" >'+ button_html +'</li>';
							node.find('ul').first().append(html);
						}
						else{
							//不存在下级节点
							node.prepend('<span class="gly1 glyphicon-triangle-bottom class_tree_swith" data-isleaf="open"></span>');
							var html = '<ul id="categoryTreeB_" class="level" style="display:block;margin-left:10px;margin-top:0px;">'+ 
											'<li node_number="'+ result.number +'" node_id="'+ result.node_id +'" >'+ 
											button_html+
											'</li>'+
										'</ul>';
							node.append(html);
							
						}
						
						//节点可修改
						var html_span = node.find('ul li').last().find('.class_name');
						var html = '<input name="edittext" type="text" old_val="新分类" value="新分类">';
						html_span.html(html);
						$('input[name=edittext]').focus();
						$('input[name=edittext]').select();
						
						$('.class_tree_setting span').unbind("click");
						classificationSetting.init();
					}
					else{
						bootbox.alert(result.msg);
						return false;
					}
				},
				error: function()
				{
					bootbox.alert("出现错误，请联系客服求助...");
					$.hideLoading();
					return false;
				}
			});
		});
		
		//修改
		$('.class_edit').click(function(){
			var html_span = $(this).parent().find('.class_name');
			var name = html_span.html();
			
			var html = '<input name="edittext" type="text" old_val="'+ name +'" value="'+ name +'">';
			html_span.html(html);
			$('input[name=edittext]').focus();
			$('input[name=edittext]').select();

			$('.class_tree_setting span').unbind("click");
			classificationSetting.init();
		});
		
		//编辑失去焦点
		$('input[name=edittext]').blur(function(){
			var newname = $(this).val().replace(/[^a-zA-Z0-9-_+xX*#\u4e00-\u9fa5]*/g,'');
			var oldname = $(this).attr('old_val').replace(/[^a-zA-Z0-9-_+xX*#\u4e00-\u9fa5]*/g,'');
			if(newname == oldname){
				$(this).parent().html(newname);
			}
			else{
				var obj = $(this);
				var node = $(this).parents('li').first();
				var node_id = node.attr('node_id');

				$.showLoading();
				$.ajax({
					type : 'post',
				    cache : 'false',
				    dataType: 'json',
				    data: {'node_id': node_id, 'name': newname},
					url:'/catalog/product/edit-classifica', 
					success: function (result) 
					{
						$.hideLoading();
						if(result.success){
							obj.parent().html(newname);
						}
						else{
							obj.parent().html(oldname);
							bootbox.alert(result.msg);
						}
					},
					error: function()
					{
						bootbox.alert("出现错误，请联系客服求助...");
						$.hideLoading();
						return false;
					}
				});
			}
		});
		
		//删除
		$('.class_remove').click(function(){
			var node = $(this).parents('li').first();
			var node_id = node.attr('node_id');
			var name = node.find('.class_name').html().replace("|-", "");

			bootbox.confirm("<b>"+Translator.t('确定删除分类"'+ name +'"？')+"</b>",function(r){
				if (!r) return;
				$.showLoading();
				$.ajax({
					type : 'post',
				    cache : 'false',
				    dataType: 'json',
				    data: {'node_id': node_id},
					url:'/catalog/product/delete-classifica', 
					success: function (result) 
					{
						$.hideLoading();
						if(result.success){
							node.remove();
						}
						else{
							bootbox.alert(result.msg);
						}
					},
					error: function()
					{
						bootbox.alert("出现错误，请联系客服求助...");
						$.hideLoading();
						return false;
					}
				});
			});
		});
	} ,
	
	checkAliaExisting : function (skus) {
		
	},
	
	
}

$(function() {
	classificationSetting.init();
	
});
