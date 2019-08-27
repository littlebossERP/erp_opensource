//加载对应的modal
function openCarrierModel($page){
	var Url=global.baseUrl +'configuration/carrierconfig/'+$page;
	$.ajax({
        type : 'get',
        cache : 'false',
        data : {},
		url: Url,
        success:function(response) {
        	$('#myModal').html(response);
        	$('#myModal').modal('show');
        }
    });
};