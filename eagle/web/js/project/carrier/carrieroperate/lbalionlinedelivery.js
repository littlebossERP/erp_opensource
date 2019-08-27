function aliChangeCompany(obj, tmp_isHomeLanshou){
	val = $(obj).val();
	
	if(val == -1){
//		$('#domesticLogisticsCompany').show();
//		$("#lab_ali_company").show();
//		if(tmp_isHomeLanshou == 'Y'){
//			$('#domesticLogisticsCompany').val('上门揽收');
//		}
		
		$(obj).next().next().show();
		$(obj).next().show();
		
		if(tmp_isHomeLanshou == 'Y'){
			$(obj).next().next().val('上门揽收');
		}
	}else{
//		$('#domesticLogisticsCompany').hide();
//		$("#lab_ali_company").hide();
		
		$(obj).next().next().hide();
		$(obj).next().hide();
	}
}