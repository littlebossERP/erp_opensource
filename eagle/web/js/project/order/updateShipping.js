function updateLazadaShipping(source,order_id){
	$.showLoading();
	
	if('lazada' == source){
		var url = '/order/lazada-order/update-lazada-shipping';
	}else if('linio' == source){
		var url = '/order/linio-order/update-linio-shipping';
	}
	
	$.ajax({
		url:url,
		type:'post',
		data:{order_id:order_id},
		dataType:'json',
		success:function(res){
			$.hideLoading();
			if(res.code == 200){
				$e = $.alert(res.message,'success');
			}
			else if(res.code == 400){
				if(res.data.length>0){
					$e = $.alert(res.data.join(','),'danger');
				}else{
					$e = $.alert(res.message,'danger');
				}
			}else{
				$e = $.alert('失败','danger');
			}
			$e.then(function(){
				if(res.code == 200){
					location.reload();
				}
			});
		},
		 error: function () {
			$.hideLoading();
			$e = $.alert('网络错误！','danger');
			$e.then(function(){
				location.reload();
 			});
		 }
		
	});
}