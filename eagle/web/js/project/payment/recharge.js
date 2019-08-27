$(function(){
	$('#WIDtotal_fee').bind('input propertychange', function() {
		var re = /^[0-9]*[1-9][0-9]*$/;
		var re = /^[0-9]*$/;
		$val = $(this).val();
		if($val != '' && !re.test($val)){
			$(this).val($val.substring(0,$val.length-1));
			$.alertBox('请输入正整数!','danger');
			$(this).focus();
		}
	});
});

function useCoupon(){
	$code = $('input[name="coupon"]').val();
	if($code.trim() == ''){
		$.alert('请输入现金券编码!','danger');
		return false;
	}
	$.showLoading();
	var Url=global.baseUrl +'payment/user-account/use-coupon';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	code:$code,
        },
        dataType : 'json',
		url: Url,
		async : false,
        success:function(res) {
        	$.hideLoading();
        	if(res.code == 200){
        		$e = $.alert(res.message,'success');
        		$e.then(function(){
        			location.reload();
        		});
        	}else{
        		$e = $.alert(res.message,'danger');
        	}
        }
	});
}

function checkFee(){
	var re = /^[0-9]*[1-9][0-9]*$/;
	$val = $('#WIDtotal_fee').val();
	if(!re.test($val)){
		$('#WIDtotal_fee').val($val.substring(0,$val.length-1));
		$.alert('通过支付宝方式充值：充值金额必须为正整数!','danger');
		$('#WIDtotal_fee').focus();
		return false;
	}
	return true;
}