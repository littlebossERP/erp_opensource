$(".ystep1").loadStep({
    //ystep的外观大小
    //可选值：small,large
    size: "large",
    //ystep配色方案
    //可选值：green,blue
    color: "green",
    //ystep中包含的步骤
    steps: [{
      //步骤名称
      title: "未付款",
      //步骤内容(鼠标移动到本步骤节点时，会提示该内容)
      content: "小老板系统内是未付款状态"
    },{
      title: "已付款",
      content: "小老板系统内是已付款状态"
    },{
      title: "发货中",
      content: "小老板系统内是发货中状态"
    },{
      title: "已发货",
      content: "小老板系统内是已发货状态"
    },{
      title: "已完成",
      content: "小老板系统内是已完成状态"
    }]
  });

var sta;
if($('#statushide').val()=='100'){
	sta=1;
}else if($('#statushide').val()=='200'){
	sta=2;
}else if($('#statushide').val()=='300'){
	sta=3;
}else if($('#statushide').val()=='400'){
	sta=4;
}else if($('#statushide').val()=='500'){
	sta=5;
}else{
	sta=1;
}
$(".ystep1").setStep(sta);

$("#myTab a:first").tab('show');

function TableTransactionModifyAdd(){
	$('#TableTransactionModify').append($('#new').clone().removeAttr('style'));
	initTableInputValidation();
}
function  removeTransaction(obj){
	$(obj).parent().parent().remove();
}

function saveConsigneeInfo(order_id){
	$.ajax({
		type: "POST",
		dataType: 'json',
		url:'/order/aliexpressorder/save-consignee-info?order_id='+order_id, 
		data: $('#frm_order_edit #div_consignee_info input').serialize(),
		success: function (result) {
			var  tmpMsg ;
			if (result.message){
				tmpMsg = result.message;
			}else{
				
				tmpMsg = '保存成功！';
			}
			
			OrderCommon.SuccessBox(tmpMsg,'order_info');
					
			return true;
		},
		error: function(){
			bootbox.alert("Internal Error");
			return false;
		}
	});
}

function initSelectConsigneeCountryCode(){
	$('#frm_order_edit #div_consignee_info [name=consignee_country_code]').unbind().on('change',function(){
		$('input[name=consignee_country]').val($('#frm_order_edit #div_consignee_info [name=consignee_country_code]').find('option:selected').text());
		
	});
}

function initTableInputValidation(){
	$("#TableTransactionModify [name='item[sku][]']").formValidation({validType:['trim','length[2,30]'],tipPosition:'right',required:true});
	$("#TableTransactionModify [name='item[product_name][]']").formValidation({validType:['trim','length[2,255]'],tipPosition:'right',required:true});
	$("#TableTransactionModify [name='item[ordered_quantity][]']").formValidation({validType:['trim','amount'],tipPosition:'right',required:true});
}

function saveOrderInfo(){
	$.showLoading();
	if (! $('#frm_order_edit').formValidation('form_validate')){
		$.hideLoading();
		//bootbox.alert(Translator.t('录入格式不正确!'));
		return false;
	}
	$('#frm_order_edit').submit();
	
}