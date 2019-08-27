if (typeof downloadexcel === 'undefined')  downloadexcel = new Object();

downloadexcel = 
{	
		init:function()
		{
		},
	getExcelUrl:function(){
		//查询是否又返回Excel路劲
		var $pending_id = $('#pending_id').val();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/purchase/purchase/get-excel-url', 
			data:{pending_id:$pending_id},
			async : false,
			success: function (result) {
				if(result.success == 1){
					clearInterval(id_Interval);
					$('.div_wait').css('display','none');
					$('.div_download').css('display','block');
					$('#down_load_excel_url').attr('href',result.url);
					$('#export_count').html(result.export_count);
				}
				else if(result.success == 0){
					clearInterval(id_Interval);
					$('.div_wait').css('display','none');
					$('.div_error').css('display','block');
					$('#export_error').html(result.message);
				}
			},
			error :function () {
				clearInterval(id_Interval);
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
}
