CKEDITOR.replace('content',{ height: '400px'});
$(document).ready(function() {
	$('#templateedit_form').bootstrapValidator({
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
    $('#templateedit_form').bootstrapValidator('validate');
});