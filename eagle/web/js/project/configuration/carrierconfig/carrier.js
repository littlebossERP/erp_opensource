//加载对应物流商的账户信息
function loadNewAccount(){
	var val = $('#codes').val();
	var Url=global.baseUrl +'configuration/carrierconfig/newaccount'
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {code:val},
		url: Url,
        success:function(response) {
	        $('#newAccountMsg').html(response);
        }
    });
}

//保存账号修改
function saveAccount($code,$id){
	$.maskLayer(true);
	$name = '';
	$params = new Array();
	$i = 0;
	$req = false;
//	判断必填字段是否为空
	$('.required').each(function(){
		if($(this).val().trim() == ""){//为空则提示并返回
			var txt = $(this).parent().prev().find('label').html();
			$.alert(txt+'不能为空','danger');
			$(this).focus();
			$req = true;
			$.maskLayer(false);
			return false;
		}
	});
	if($req == false){
		$form = $('#accountEditFORM').serialize();
		$is_default = $('#isDefault').is(':checked');
		var Url=global.baseUrl +'configuration/carrierconfig/saveaccount';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $form,
	        dataType: 'json',
			url: Url,
	        success:function(response) {
	        	if(response.success){
	        		//新增时，当是wish邮，打开wish邮授权地址
        			if($id == 0 && $code == "lb_wishyou")
        			{
        				$id = response.message;
						//进入v2环境模拟登陆
						var Url = global.baseUrl +'platform/wish-postal-v2/get-url';
        				$.ajax({
        					type : 'post',
        					dataType: 'json',
        					data : { wish_account_id:$id },
        					url: Url,
        					success:function(response_w) 
        					{
        						if(response_w['status'] == 0)
        						{
        							$.ajax({
        								type:'get',
        								url:response_w['url'],
        					          	dataType: 'json',
        					           	xhrFields: {
        					           		withCredentials: true
        					           	},
        					           	success: function(result) 
        					           	{
        					           		//alert(result);
        					           		if(result == 1)
        					           		{
        					           			window.open (global.baseUrl+"\platform/wish-postal-v2/auth?wish_account_id="+$id);
        				        				
        				        				window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
        					           		}
        					           		else
        					           			alert('登陆失败！');
        					            }
        					        });
        						}
        						else
        						{
        							window.open (global.baseUrl+"\platform/wish-postal-v2/auth?wish_account_id="+$id);
        	        				
        	        				window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
        						}
        					}
        				});
        			}
        			else if($id == 0 && $code == "lb_chukouyi"){
        				$id = response.message;
        				//进入v2环境模拟登陆
        				var Url = global.baseUrl +'platform/wish-postal-v2/get-url';
        				$.ajax({
        					type : 'post',
        					dataType: 'json',
        					data : { wish_account_id:$id },
        					url: Url,
        					success:function(response) 
        					{
        						if(response['status'] == 0)
        						{
        							$.ajax({
        								type:'get',
        								url:response['url'],
        					          	dataType: 'json',
        					           	xhrFields: {
        					           		withCredentials: true
        					           	},
        					           	success: function(result) 
        					           	{
        					           		//alert(result);
        					           		if(result == 1){
        					           			window.open (global.baseUrl+"\carrier/carrier/chukouyi-auth?account_id="+$id);
        				        				window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
        					           		}
        					           		else
        					           			alert('登陆失败！');
        					            }
        					        });
        						}
        						else
        						{
				           			window.open (global.baseUrl+"\carrier/carrier/chukouyi-auth?account_id="+$id);
			        				window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
        						}
        					}
        				});
        			}else if( $id == 0 && $code == "lb_newwinit" ){
						$id = response.message;
						//进入v2环境模拟登陆
						var Url = global.baseUrl +'platform/wyt-postal-v2/get-url';
						$.ajax({
							type : 'post',
							dataType: 'json',
							data : { wyt_account_id:$id },
							url: Url,
							success:function(response)
							{
								if(response['status'] == 0)
								{
									$.ajax({
										type:'get',
										url:response['url'],
										dataType: 'json',
										xhrFields: {
											withCredentials: true
										},
										success: function(result)
										{
											//alert(result);
											if(result == 1){
												window.open (global.baseUrl+"\platform/wyt-postal-v2/auth?wyt_account_id="+$id);

												window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
											}
											else
												alert('登陆失败！');
										}
									});
								}
								else
								{
									window.open (global.baseUrl+"\platform/wyt-postal-v2/auth?wyt_account_id="+$id);

									window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
								}
							}
						});
						
					}
        			else
        			{
		        		var event = $.alert(response.message,'success');
		        		event.then(function(){
	//	        			location.reload();
		        			window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$code;
		        		});
        			}
		        }
	        	else{
	        		$.maskLayer(false);
	        		var event = $.alert(response.message,'error');
		        }
	        }
	    });
	}
};
//开启物流商中的创建新账号，成功则调用开启物流商的function
function createAccount($obj){
	$.maskLayer(true);
	if($('#newaccount-body').attr('data') == '1'){
		openCarrier($obj);
	}
	else{
		$code = $('#codes').val();
		$name = '';
		$params = new Array();
		$i = 0;
		$req = false;
//		判断必填字段是否为空
		$('.required').each(function(){
			if($(this).val().trim() == ""){//为空则提示并返回
				var txt = $(this).parent().prev().find('label').html();
				$.alert(txt+'不能为空!','danger');
				$(this).focus();
				$req = true;
				$.maskLayer(false);
				return false;
			}
		});

		if($req == false){
			$form = $('#newAccountMsg').serialize();
			var Url=global.baseUrl +'configuration/carrierconfig/saveaccount';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : $form,
		        dataType: 'json',
				url: Url,
		        success:function(response) {
		        	if(response.success){
		        		openCarrier($obj);
			        }
		        	else{
		        		$.maskLayer(false);
		        		$.alert(response.message,'danger');
			        }
		        }
		    });
		}
	}
};

//关闭物流商
function closeCarrier(code){
	$event = $.confirmBox('您确认关闭此物流商吗？');
	$event.then(function(){
		var Url=global.baseUrl +'configuration/carrierconfig/openorclosecarriernow';
		$.maskLayer(true);
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code:code,status:0},
			url: Url,
	        success:function(response) {
		        var event = $.alert(response.substring(2),'success');
				 event.then(function(){
				  // 确定,刷新页面
				  window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+code;
				 
				},function(){
				  // 取消，关闭遮罩
				  $.maskLayer(false);
				});
	        }
	    });
	});
}

//开启物流商
function openCarrier(code){
	$event = $.confirmBox('您确认开启此物流商吗？');
	$event.then(function(){
		var Url=global.baseUrl +'configuration/carrierconfig/openorclosecarriernow';
		$.maskLayer(true);
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code:code,status:1},
			url: Url,
	        success:function(response) {
		        var event = $.alert(response.substring(2),'success');
				 event.then(function(){
					 location.href = global.baseUrl + 'configuration/carrierconfig/index?tcarrier_code=' + code;
				},function(){
				  $.maskLayer(false);
				});
	        }
	    });
	});
}
