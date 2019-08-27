/**
 * 现金抵用券js
 * @author dzt20161206
 */

if (typeof payment === 'undefined')  payment = new Object();

payment.userVoucherList ={
	init:function(){
		if($('input[name="disable_type"]:checked').length == 0){
			$('input[name="disable_type"][value="0"]').parent().addClass('btn-important');
		}else
			$('input[name="disable_type"]:checked').parent().addClass('btn-important');
	},
	
	useVoucher:function (){
		$code = $('input[name="voucher"]').val();
		if($code.trim() == ''){
			$.alert('请输入发放的现金抵用券编码!','danger');
			return false;
		}
		$.showLoading();
		var Url=global.baseUrl +'payment/user-account/use-voucher';
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
	        },
            error: function () {
                $.hideLoading();
                bootbox.alert("内部错误！请联系客服");
            }
	        
		});
	}
}