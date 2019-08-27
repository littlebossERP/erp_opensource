function bind_all(){
		//省份
		if(!isNull($province_all['0'])) bindArrayByID("shippingfrom_province",$province_all['0'],$p0);
		if(!isNull($province_all['1'])) bindArrayByID("pickupaddress_province",$province_all['1'],$p1);
		if(!isNull($province_all['2'])) bindArrayByID("returnaddress_province",$province_all['2'],$p2);
		if(!isNull($province_all['3'])) bindArrayByID("shippingfrom_province_en",$province_all['3'],$p3);
		//市
		if(!isNull($city_all['0'])) bindArrayByID("shippingfrom_city",$city_all['0'][$('#shippingfrom_province').val()],$c0);
		if(!isNull($city_all['1'])) bindArrayByID("pickupaddress_city",$city_all['1'][$('#pickupaddress_province').val()],$c1);
		if(!isNull($city_all['2'])) bindArrayByID("returnaddress_city",$city_all['2'][$('#returnaddress_province').val()],$c2);
		if(!isNull($city_all['3'])) bindArrayByID("shippingfrom_city_en",$city_all['3'][$('#shippingfrom_province_en').val()],$c3);
		//区
		if(!isNull($dis_all['0'])) bindArrayByID("shippingfrom_district",$dis_all['0'][$('#shippingfrom_city').val()],$d0);
		if(!isNull($dis_all['1'])) bindArrayByID("pickupaddress_district",$dis_all['1'][$('#pickupaddress_city').val()],$d1);
		if(!isNull($dis_all['2'])) bindArrayByID("returnaddress_district",$dis_all['2'][$('#returnaddress_city').val()],$d2);
		if(!isNull($dis_all['3'])) bindArrayByID("shippingfrom_district_en",$dis_all['3'][$('#shippingfrom_city_en').val()],$d3);
	}
	function procinceChange(num){
		switch(num){
			case 0:bindArrayByID("shippingfrom_city",$city_all['0'][$('#shippingfrom_province').val()]);
					cityChange(0);
					break;
			case 1:bindArrayByID("pickupaddress_city",$city_all['1'][$('#pickupaddress_province').val()]);
					cityChange(1);
					break;
			case 2:bindArrayByID("returnaddress_city",$city_all['2'][$('#returnaddress_province').val()]);
					cityChange(2);
					break;
			case 3:bindArrayByID("shippingfrom_city_en",$city_all['3'][$('#shippingfrom_province_en').val()]);
					cityChange(3);
					break;
		}
	}
	function cityChange(num){
		switch(num){
			case 0:bindArrayByID("shippingfrom_district",$dis_all['0'][$('#shippingfrom_city').val()]);break;
			case 1:bindArrayByID("pickupaddress_district",$dis_all['1'][$('#pickupaddress_city').val()]);break;
			case 2:bindArrayByID("returnaddress_district",$dis_all['2'][$('#returnaddress_city').val()]);break;
			case 3:bindArrayByID("shippingfrom_district_en",$dis_all['0'][$('#shippingfrom_city_en').val()]);break;
		}
	}
	function isNull(data){ 
		return (data == "" || data == undefined || data == null) ? true : false; 
	}
	function bindArrayByID(ID,$array_Data,selectValue) {
	    //清空区域
	    $("#"+ID).find('option').remove();

	    $arr = $array_Data;
	    for(var key in $arr){ 
	    	bindSelect(ID, key, $arr[key],selectValue);
		}
	}
	function bindSelect(ID, code, name,selectValue) {
	    var selected = '';
	    if(selectValue==code)selected = ' selected';
	    $("#" + ID).append("<option value='" + code + "'"+selected+">" + name + "</option>");
	}
	//保存地址
	function SaveAddress($id,$carrier_code,$type){
		tip = true;
		//CNE不要控制寄件人地址信息必填
		if($carrier_code != 'lb_CNE'){
			//验证是否为空
			$('.addreq').each(function(){
				if(tip)
				if($(this).val().trim() == ""){
					tip = false;
					name = $(this).prev().text();
					_this = this;
					$event = $.alert(name+"为必填项",'danger');
					$event.then(function(){
						$(_this).focus();
					});
				}
			});
		}
		
		if($('#isSaveCommonAddress').prop('checked')){
			if($('#address_name').val().trim() == ""){
				tip = false;
				$event = $.alert('保存为《常用地址》的地址名不能为空！','danger');
				$event.then(function(){
					$('#address_name').focus();
				});
			}
		}
		if(tip){
			$formdata = $('#address_form').serialize();

			var Url=global.baseUrl +'configuration/carrierconfig/saveaddress';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : $formdata,
				url: Url,
		        success:function(response) {
		        	$event = $.alert(response,'danger');
					$event.then(function(){
						if(undefined == $('#search_carrier_code').val()){
							location.reload();
						}else{
							if(window.location.href.indexOf('?')>-1){
								$nowurl=window.location.href.substring(0,window.location.href.indexOf('?'));
								window.location.href=$nowurl+'?tcarrier_code='+$('#search_carrier_code').val();
							}
							else
								window.location.search='tcarrier_code='+$('#search_carrier_code').val();
						}
					});
		        }
		    });
		}
	};
	//设置默认地址
	function setDefaultAddress($id,$obj){
		var Url=global.baseUrl +'configuration/carrierconfig/setdefaultaddress';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {id:$id},
			url: Url,
	        success:function(response) {
	        	$('.def_address').html('设为默认');
	        	$('.def_address').attr('style','');
	        	$('.def_address').attr('onclick','setDefaultAddress('+$('.def_address').attr('data')+',this)');
	        	$('.def_address').attr('class','btn btn-xs');
	        	$($obj).html('默认地址');
	        	$($obj).attr('style','color:#FF9900');
	        	$($obj).attr('class','btn btn-xs def_address');
	        	$($obj).attr('onclick','');
	        }
	    });
	}
	//打开添加/编辑地址modal
	function openAddressModal(id){
		$codes = $('input[name=carrier_code]').val();
		var Url=global.baseUrl +'configuration/carrierconfig/address';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {
	        	id:id,
	        	codes:$codes,
	        },
			url: Url,
	        success:function(response) {
	        	$('#myModal').html(response);
	          	$('#myModal').modal('show');
	        }
	    });
	}
	//打开删除地址modal
	function openDelAddressModal(id){
		var Url=global.baseUrl +'configuration/carrierconfig/deladdress';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {
	        	id:id,
	        },
			url: Url,
	        success:function(response) {
	        	$('#myModal').html(response);
	          	$('#myModal').modal('show');
	        }
	    });
	}

	//显示隐藏常用地址名文本框
	function isSaveCommonAddressChange(obj){
		var check = $(obj).prop('checked');
		if(check){
			$('#set_default_address').attr('class','');
		}
		else{
			$('#set_default_address').attr('class','hidden');
		}
	};