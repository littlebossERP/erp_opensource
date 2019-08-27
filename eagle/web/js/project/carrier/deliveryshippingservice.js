/**
 +------------------------------------------------------------------------------
 *物流商参数
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		delivery
 * @subpackage  Exception
 * @author		qfl <fulin.qu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
//ajax获取物流商参数和帐号
$(function(){
	var currentCode = $('#carrier_code').val();
	$('#carrier_code').change(function(){
        var carrierCode = $(this).val();
		loadParams(carrierCode);
		loadAccount(carrierCode);
        //var selectName = $(this).find('option[value='+carrierCode+']').html();
        //$('input[name=service_name]').val(selectName);
	});
	loadParams(currentCode);
	//loadAccount(currentCode);
	
	$("select[name='service_code[amazon]']").combobox({removeIfInvalid:false});
})
//加载参数
function loadParams(currentCode){
	var id = $('input[name=id]').val();
	$.ajax({
        type : 'post',
        data:{code:currentCode,id:id},
        url: loadParamsUrl,
        success:function(data) {	
            $('#params').html(data);
        }
    });

}
//加载物流账号
function loadAccount(currentCode){
	var id = $('input[name=id]').val();
	$.ajax({
        type : 'post',
        data:{code:currentCode,id:id},
        url: loadAccountUrl,
        success:function(data) {	
            $('#account').html(data);
        }
    });
}

