$(function(){
	$('.setused').click(function(){
		tr = $(this).parent().parent();
		id = tr.attr('data');
		no = tr.find('td[data=no]').text();
		bootbox.confirm("确定标记跟踪号 :"+no+" 为已分配？", function (res) {
            if (res == true) {
            	$.showLoading();
            	var Url=global.baseUrl +'configuration/carrierconfig/mark-track-number';
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : {
			        	id:id,
			        },
					url: Url,
			        success:function(response) {
			        	$.hideLoading();
			        	if(response[0] != 0){
			        		bootbox.alert(response.substr(2), function() {});
			        	}else {
			        		bootbox.alert(response.substr(2), function() {});
			        		location.reload();
			        	}
			        }
			    });
            }
        });
	});
	$('.del').click(function(){
		tr = $(this).parent().parent();
		id = tr.attr('data');
		no = tr.find('td[data=no]').text();
		bootbox.confirm("确定删除跟踪号 :"+no+" ？", function (res) {
            if (res == true) {
            	$.showLoading();
            	var Url=global.baseUrl +'configuration/carrierconfig/del-track';
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : {
			        	id:id,
			        },
					url: Url,
			        success:function(response) {
			        	$.hideLoading();
			        	if(response[0] != 0){
			        		bootbox.alert(response.substr(2), function() {});
			        	}else {
			        		bootbox.alert(response.substr(2), function() {});
			        		tr.remove();
			        	}
			        }
			    });
            }
        });

	});
});