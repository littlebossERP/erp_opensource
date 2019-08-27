function packageOperation(operation, check, id, package_value, group_id){
	if(package_value == undefined){
		package_value = 0;
	}
	
	if(group_id == undefined){
		group_id = 0;
	}
	
	$.showLoading();
	var Url=global.baseUrl +'payment/user-account/package-operation-check';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	package_id:id,
        	operation:operation,
        	package_value:package_value,
        	package_type:check,
        },
        dataType : 'json',
		url: Url,
		async : false,
        success:function(res) {
        	$.hideLoading();
        	if(res.code == 200){
        		$e = $.confirmBox(res.data);
        		$e.then(function(){
        			$.showLoading();
        			var Url=global.baseUrl +'payment/user-account/package-operation';
        			$.ajax({
        		        type : 'post',
        		        cache : 'false',
        		        data : {
        		        	package_id:id,
        		        	operation:operation,
        		        	package_value:package_value,
        		        	package_type:check,
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
        		});
        	}else{
        		if((group_id == 3) || (group_id == 4)){
        			$e = $.alert(res.message);
        		}else{
        			$e = $.alert(res.message,'danger');
        		}
        	}
        }
	});
	
}

function zeroSo1packageOperation(vip_type){
	id = '';
	tmp_check = '';
	package_status = '';
	
	if(vip_type == 'vip2'){
		id = $("input[name='vip2_radio']:checked").val();
		tmp_check = $("input[name='vip2_radio']:checked").attr('id');
		package_status = $("input[name='vip2_radio']:checked").attr('package_status');
	}else if(vip_type == 'vip2_5'){
		id = $("input[name='vip2_5_radio']:checked").val();
		tmp_check = $("input[name='vip2_5_radio']:checked").attr('id');
		package_status = $("input[name='vip2_5_radio']:checked").attr('package_status');
	}else if(vip_type == 'vip3'){
		id = $("input[name='vip3_radio']:checked").val();
		tmp_check = $("input[name='vip3_radio']:checked").attr('id');
		package_status = $("input[name='vip3_radio']:checked").attr('package_status');
	}
	
	if(id == undefined){
		$e = $.alert('请选择相关套餐', 'danger');
		return false;
	}
	
	if(tmp_check == 'vip2_5_radio_954'){
		packageOperation('use', 0, id, 954, 4);
		return false;
	}else if(tmp_check == 'vip3_radio_897'){
		packageOperation('use', 0, id, 897, 4);
		return false;
	}
	
	if(package_status == 0){
		packageOperation('use', tmp_check, id, 0, 4);
	}else if(package_status == 2){
		packageOperation('use', tmp_check, id);
	}else{
		packageOperation('cancel', tmp_check, id)
	}
}

function zeroSo1packageRadioChange(vip_type, package_status){
	btn_id = '';
	
	if(vip_type == 'vip2'){
		btn_id = 'package_btn_vip2';
	}else if(vip_type == 'vip2_5'){
		btn_id = 'package_btn_vip2_5';
	}else if(vip_type == 'vip3'){
		btn_id = 'package_btn_vip3';
	}
	
	if(package_status == 0){
		$('#'+btn_id).text('购买套餐');
	}else if(package_status == 2){
		$('#'+btn_id).text('重新开通');
	}else{
		$('#'+btn_id).text('取消套餐');
	}
	
}