$(function(){
	$("#myTab a:first").tab('show');
	var carrierCode = $('#carrier_code').val();
	$('#carrier_code').change(function(){
        var carrierCode = $(this).val();
        loadAddress(carrierCode);
	});
	loadAddress(carrierCode);
})

function loadAddress(carrierCode){
	var id = $('input[name=id]').val();
	$.ajax({
        type : 'post',
        data:{carrier_code:carrierCode,id:id},
        url: loadAddressUrl,
        success:function(data) {	
            $('#address').html(data);
        }
    });
}