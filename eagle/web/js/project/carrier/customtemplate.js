$(function() {
	'use strict';


	// 选择规格
	$("#sizeSel button").on('click', function() {
		var $this = $(this),
			idx = $this.index();
		$this.addClass('active').siblings().removeClass('active');
		$this.parent().find(".jsDo").children().eq(idx).show().siblings().hide();
		$this.parent().find(".jsDo select").trigger('change');
		return false;
	});

	$("#sizeSel")
		.find(".jsDo").find('select').on('change',function(){
			var $size = $(this).next().find('input'),
			size = $(this).val().split('x');
			$size.eq(0).val(size[0]);
			$size.eq(1).val(size[1]);
		});

	// 前往编辑器
	$("#createNewTemplate form").on('submit',function(){
		$('#createNewTemplate').hide();
	});

	// 删除模版
	$("[data-ajax-request]").on('click',function(){
		var $this=$(this);
		bootbox.confirm('确定要删除?',function(rs){
			if(rs){
				$.post($this.data('ajax-request'),{},function(rs){
					bootbox.alert(rs.message,function(){
						if(!rs.error){
							$this.closest('tr').remove();
                                                        //同步自定义模板数量标签值
                                                        var total_size = $('.nav-tabs>.active .total_size');
                                                        total_size.text((total_size.text()-1));
						}
					});
				});
			}
		});
	});

	$('#createNewTemplate input[name="template_name"]').blur(function(){
		var template_name = $(this);
		$.post('checktemplatename',{templatename:template_name.val()},function(result){
			if(result){
				$('#template_name_notice').text(' 该模版名已存在').show();
				$('#template_save_button').attr('disabled',true);
			}else{
				$('#template_name_notice').text('').hide();
				$('#template_save_button').attr('disabled',false);
			}
		})
	})

	$('.selftab').each(function(index,ele){
		var a = $(ele);
		//如果获取到的元素不是a元素 则继续想下找到a元素
		if(!a.is('a')){
			a.find('a').each(function(){
				var a2 = $(this);
				if(a2.attr('href') != undefined && a2.attr('href').indexOf('selftemplate') == -1){
					a2.attr('href',a2.attr('href')+'&selftemplate=1');
				}
			})
		}
		if(a.attr('href') != undefined && a.attr('href').indexOf('selftemplate') == -1)
			a.attr('href',a.attr('href')+'&selftemplate=1');
	});

	//搜索按钮
	$('#search_button').click(function(){
		//判断当前在哪个选项卡
		if($('.nav-tabs li:last').hasClass('active'))
			$('#selftemplatetag').val(1);
		else
			$('#selftemplatetag').val('');

                if($('.nav-tabs>li.sys').hasClass('active')) {
                    $('#search_tab_active').val('sys');
                }else {
                    $('#search_tab_active').val('self');
                }

		//提交当前表单
		this.closest('form').submit();

	})
});

function copytemplateToself(id,name){
	if(id == '' || name == '' || id == undefined || name == undefined)return false;
	$('#template_id').val(id);
	$('#copyToNewTemplate input[name="template_name"]').val('自定义:' + name);
}
function docopy(){
	var id = $('#template_id').val(),
		name = $('#copyToNewTemplate input[name="template_name"]').val();
	if(id == '' || name == ''){
		bootbox.alert('复制失败');
	}
	var data = $('#copyToNewTemplate form').serialize();

	$.ajax({
		url:'copytemplate',
		type:'post',
		data:data,
		success:function(response){
			if(response){
				bootbox.alert({
					message:'复制成功,模版名：' + name,
					callback:function(){
						location.href = '/carrier/carriercustomtemplate/?tab_active=sys';
					}
				});
				$('#copyToNewTemplate').modal('hide');
			}
			else bootbox.alert('复制失败,请检查模版名是否重复');
		}
	})
}
