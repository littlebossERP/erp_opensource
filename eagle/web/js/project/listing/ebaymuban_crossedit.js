function crossItem_Add(){
	html=$('#crossItemSample').clone().attr('id','').show();
	$('#items').append(html);
}
function crossItem_remove(e){
	if(confirm('确定删除吗?')==false) return false;
	$(e).parent().parent().parent().remove();
}
$(document).ready(function() {
	$('#crossselling_form').bootstrapValidator({
		feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
		fields: {
			title: {
                validators: {
                    notEmpty: {
                        message: 'The title is required and cannot be empty'
                    },
					stringLength: {
				        min: 1,
				        max: 30,
				        message: 'The title must be more than 1 and less than 30 characters long'
				    }
                }
            }
		}
	});
//	$('#subm').click(function() {
//        $('#crossselling_form').bootstrapValidator('validate');
//    });
});