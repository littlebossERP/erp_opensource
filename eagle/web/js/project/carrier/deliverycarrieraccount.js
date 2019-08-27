/**
 +------------------------------------------------------------------------------
 *物流商帐号
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		carrier
 * @subpackage  Exception
 * @author		qfl <fulin.qu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
$(function(){
	var code = $('#select_carrier').val();
	var id = $('#carrier_id').val();
    var address_library = jQuery.parseJSON($('#account_address_library').val());
                
    if(code=='rtbcompany'){
    	code = $('#select_rtbcarrier').val();
    }else{
    	$('#select_rtbcarrier').hide();
    }
    
    if(id != ''){
    	$("#select_carrier").attr("disabled","disabled");
    	$("#select_rtbcarrier").attr("disabled","disabled");
    }
        
	loadParams(code,id);
	$('#select_carrier').change(function(){
		var id = $('#carrier_id').val();
		
		$('#select_rtbcarrier').hide();
		
		if($(this).val() == 'rtbcompany'){
			$('#select_rtbcarrier').show();
			
			loadParams($('#select_rtbcarrier').val(),id);
			//根据已选择的物流商 给出默认的帐号别名
			$('#account-alias').val($('#select_rtbcarrier').find('option[value='+$('#select_rtbcarrier').val()+']').html());
			
			return false;
		}else{
			loadParams($(this).val(),id);
			//根据已选择的物流商 给出默认的帐号别名
			$('#account-alias').val($(this).find('option[value='+$(this).val()+']').html());
		}
	});
	
	$('#select_rtbcarrier').change(function(){
		var id = $('#carrier_id').val();
		
		loadParams($(this).val(),id);
		//根据已选择的物流商 给出默认的帐号别名
		$('#account-alias').val($(this).find('option[value='+$(this).val()+']').html());
	});
	
	$('#btn-save-carrieraccount').click(function(){
		$("#select_carrier").removeAttr("disabled");
		$("#select_rtbcarrier").removeAttr("disabled");
	});
	

    /*
        地址库预载入
     */
	$('#account_list').change(function() {
        if($('#inputParams').html() == '' || ($(this).val() == '0')) {
            //未选择物流商，无需预载入
            return false;
        }else {
            //预载入开始
            var address_info = address_library[$(this).val()];

            if('shippingfrom' in address_info) {
                var shipping_form_area = $('.shippingfrom-area');
                if(shipping_form_area.length > 0) {
                var country = shipping_form_area.find('.shippingfrom-country');
                var province = shipping_form_area.find('.shippingfrom-province');
                var city = shipping_form_area.find('.shippingfrom-city');
                var district = shipping_form_area.find('.shippingfrom-district');
                var street = shipping_form_area.find('.shippingfrom-street');
                var postcode = shipping_form_area.find('.shippingfrom-postcode');
                var company = shipping_form_area.find('.shippingfrom-company');
                var contact = shipping_form_area.find('.shippingfrom-contact');
                var phone = shipping_form_area.find('.shippingfrom-phone');
                var fax = shipping_form_area.find('.shippingfrom-fax');
                var mobile = shipping_form_area.find('.shippingfrom-mobile');
                var email = shipping_form_area.find('.shippingfrom-email');
		    if(country.length > 0 && ('country' in address_info.shippingfrom)) {
			//国家
			country.val(address_info.shippingfrom.country);
		    }
		    if(province.length > 0 && ('province' in address_info.shippingfrom)) {
		    //省份
		    province.val(address_info.shippingfrom.province);
		    }
		    if(city.length > 0 && ('city' in address_info.shippingfrom)) {
		    //城市
			city.val(address_info.shippingfrom.city);
		    }
		    if(district.length > 0 && ('district' in address_info.shippingfrom)) {
		    //区域
		    district.val(address_info.shippingfrom.district);
		    }
		    if(street.length > 0 && ('street' in address_info.shippingfrom)) {
		    //地址
		    street.val(address_info.shippingfrom.street);
		    }
		    if(postcode.length > 0 && ('postcode' in address_info.shippingfrom)) {
		    //邮编
		    postcode.val(address_info.shippingfrom.postcode);
		    }
		    if(company.length > 0 && ('company' in address_info.shippingfrom)) {
		    //公司
		    company.val(address_info.shippingfrom.company);
		    }
		    if(contact.length > 0 && ('contact' in address_info.shippingfrom)) {
		    //发件人
		    contact.val(address_info.shippingfrom.contact);
		    }
		    if(phone.length > 0 && ('phone' in address_info.shippingfrom)) {
		    //电话
		    phone.val(address_info.shippingfrom.phone);
		    }
		    if(fax.length > 0 && ('fax' in address_info.shippingfrom)) {
		    //传真
		    fax.val(address_info.shippingfrom.fax);
		    }
		    if(mobile.length > 0 && ('mobile' in address_info.shippingfrom)) {
		    //手机
		    mobile.val(address_info.shippingfrom.mobile);
		    }
		    if(email.length > 0 && ('email' in address_info.shippingfrom)) {
		    //邮箱
		    email.val(address_info.shippingfrom.email);
		    }
                }
            }
            
            if('pickupaddress' in address_info) {
                var pickup_address_area = $('.pickupaddress-area');
                if(pickup_address_area.length > 0) {
                var country = pickup_address_area.find('.pickupaddress-country');
                var province = pickup_address_area.find('.pickupaddress-province');
                var city = pickup_address_area.find('.pickupaddress-city');
                var district = pickup_address_area.find('.pickupaddress-district');
                var street = pickup_address_area.find('.pickupaddress-street');
                var postcode = pickup_address_area.find('.pickupaddress-postcode');
                var company = pickup_address_area.find('.pickupaddress-company');
                var contact = pickup_address_area.find('.pickupaddress-contact');
                var phone = pickup_address_area.find('.pickupaddress-phone');
                var fax = pickup_address_area.find('.pickupaddress-fax');
                var mobile = pickup_address_area.find('.pickupaddress-mobile');
                var email = pickup_address_area.find('.pickupaddress-email');
		    if(country.length > 0 && ('country' in address_info.pickupaddress)) {
			//国家
			country.val(address_info.pickupaddress.country);
		    }
                    /*
		    if(province.length > 0 && ('province' in address_info.pickupaddress)) {
		    //省份
		    province.val(address_info.pickupaddress.province);
		    }
		    if(city.length > 0 && ('city' in address_info.pickupaddress)) {
		    //城市
			city.val(address_info.pickupaddress.city);
		    }
		    if(district.length > 0 && ('district' in address_info.pickupaddress)) {
		    //区域
		    district.val(address_info.pickupaddress.district);
		    }
                    */
                    Init('pickupaddress',address_info.pickupaddress.province,address_info.pickupaddress.city,address_info.pickupaddress.district);
		    if(street.length > 0 && ('street' in address_info.pickupaddress)) {
		    //地址
		    street.val(address_info.pickupaddress.street);
		    }
		    if(postcode.length > 0 && ('postcode' in address_info.pickupaddress)) {
		    //邮编
		    postcode.val(address_info.pickupaddress.postcode);
		    }
		    if(company.length > 0 && ('company' in address_info.pickupaddress)) {
		    //公司
		    company.val(address_info.pickupaddress.company);
		    }
		    if(contact.length > 0 && ('contact' in address_info.pickupaddress)) {
		    //发件人
		    contact.val(address_info.pickupaddress.contact);
		    }
		    if(phone.length > 0 && ('phone' in address_info.pickupaddress)) {
		    //电话
		    phone.val(address_info.pickupaddress.phone);
		    }
		    if(fax.length > 0 && ('fax' in address_info.pickupaddress)) {
		    //传真
		    fax.val(address_info.pickupaddress.fax);
		    }
		    if(mobile.length > 0 && ('mobile' in address_info.pickupaddress)) {
		    //手机
		    mobile.val(address_info.pickupaddress.mobile);
		    }
		    if(email.length > 0 && ('email' in address_info.pickupaddress)) {
		    //邮箱
		    email.val(address_info.pickupaddress.email);
		    }
                }
            }



            if('returnaddress' in address_info) {
                var return_address_area = $('.returnaddress-area');
                if(return_address_area.length > 0) {
                var country = return_address_area.find('.returnaddress-country');
                var province = return_address_area.find('.returnaddress-province');
                var city = return_address_area.find('.returnaddress-city');
                var district = return_address_area.find('.returnaddress-district');
                var street = return_address_area.find('.returnaddress-street');
                var postcode = return_address_area.find('.returnaddress-postcode');
                var company = return_address_area.find('.returnaddress-company');
                var contact = return_address_area.find('.returnaddress-contact');
                var phone = return_address_area.find('.returnaddress-phone');
                var fax = return_address_area.find('.returnaddress-fax');
                var mobile = return_address_area.find('.returnaddress-mobile');
                var email = return_address_area.find('.returnaddress-email');
		    if(country.length > 0 && ('country' in address_info.returnaddress)) {
			//国家
			country.val(address_info.returnaddress.country);
		    }
		    if(province.length > 0 && ('province' in address_info.returnaddress)) {
		    //省份
		    province.val(address_info.returnaddress.province);
		    }
		    if(city.length > 0 && ('city' in address_info.returnaddress)) {
		    //城市
			city.val(address_info.returnaddress.city);
		    }
		    if(district.length > 0 && ('district' in address_info.returnaddress)) {
		    //区域
		    district.val(address_info.returnaddress.district);
		    }
		    if(street.length > 0 && ('street' in address_info.returnaddress)) {
		    //地址
		    street.val(address_info.returnaddress.street);
		    }
		    if(postcode.length > 0 && ('postcode' in address_info.returnaddress)) {
		    //邮编
		    postcode.val(address_info.returnaddress.postcode);
		    }
		    if(company.length > 0 && ('company' in address_info.returnaddress)) {
		    //公司
		    company.val(address_info.returnaddress.company);
		    }
		    if(contact.length > 0 && ('contact' in address_info.returnaddress)) {
		    //发件人
		    contact.val(address_info.returnaddress.contact);
		    }
		    if(phone.length > 0 && ('phone' in address_info.returnaddress)) {
		    //电话
		    phone.val(address_info.returnaddress.phone);
		    }
		    if(fax.length > 0 && ('fax' in address_info.returnaddress)) {
		    //传真
		    fax.val(address_info.returnaddress.fax);
		    }
		    if(mobile.length > 0 && ('mobile' in address_info.returnaddress)) {
		    //手机
		    mobile.val(address_info.returnaddress.mobile);
		    }
		    if(email.length > 0 && ('email' in address_info.returnaddress)) {
		    //邮箱
		    email.val(address_info.returnaddress.email);
		    }
                }
            }
        }
    })
})
function loadParams(code,id){
	$.ajax({
		url:loadParamsUrl,
		data:{code:code,id:id},
		type:'post',
		success:function(response){
			$('#inputParams').html(response);
		}
	})
}

$('#savebutton').click(function(){
	var formdata = $('#create_form').serialize();
	$.ajax({
        type : 'post',
        data:formdata,
		url: saveUrl,
        success:function(result) {
        	if(result){
        		alert('保存成功');
        		window.close();
        	}else{
        		alert('数据有误,请检查');
        	}
        	// $('#putre').html(result);
        }
    });
})


