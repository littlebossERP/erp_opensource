$(function(){
	showOrHide();
	$('.cmemo').click(function(){
		checked = $(this).prop('checked');
		name = $(this).attr('data');
		vals = (checked)?'1':'0';
		$('input[name="'+name+'"]').val(vals);
		val = $('input[name="'+name+'"]').val();
	})
	$('.sizeList').change(function(){
		$('#sizeDIV').hide();
		str = $(this).val();
		w = '';h='';
		if(str != 'customSize'){
			var size=str.split("x");
			if((typeof size[0] != 'undefined') && (typeof size[1] != 'undefined')){
				w = size[1];
				h = size[0];
			}
		}
		else $('#sizeDIV').show();
		$('input[name="label_paper_size[template_height]"]').val(w);
		$('input[name="label_paper_size[template_width]"]').val(h);
	});
	showMsg();
});
function showOrHide(){
	str = $('.sizeList').val();
	if(str != 'customSize'){
		$('#sizeDIV').hide();
	}
	else{
		$('#sizeDIV').show();
	}
}
function showMsg(){
	msg = $('#msg').val();
	if(typeof msg != 'undefined' && msg != ''){
		if(msg[0] == '0')
			bootbox.alert('保存成功');
		else
			bootbox.alert(msg.substr(1));
	}
}
function checkReq(){
	send = true;
	str = $("#sizeList").val();
	if(str == 'customSize'){
		if($('input[name="label_paper_size[template_height]"]').val().trim() == ''){
			send = false;
			$('input[name="label_paper_size[template_height]"]').focus();
			alert('高不能为空');
		}
		else if($('input[name="label_paper_size[template_width]"]').val().trim() == ''){
			send = false;
			$('input[name="label_paper_size[template_width]"]').focus();
			alert('宽不能为空');
		}
	}
	if(send)
		$('#moduleFORM').submit();
}