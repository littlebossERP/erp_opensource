/**
 +------------------------------------------------------------------------------
 *利润 计算的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/statistics
 * @package		profit
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof setorderscost === 'undefined')  setorderscost = new Object();
setorderscost =
{
	'init': function() 
	{
		setorderscost.initFormValidateInput();
	},
	
	'changeType' : function (type,obj){
		$.showLoading();
		$(".type-btn").removeClass("btn-success");
		$(obj).addClass("btn-success");
		if(type==0){
			//$(".price_based_on_order input").val("");
			$(".price_based_on_order").hide();
			$(".additional_cost").show();
			$(".purchase_price").show();
			$("input[name='price_type']").val(0);
			$('.purchase_price input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
			$('.additional_cost input').formValidation({validType:['trim','amount'],tipPosition:'left'});
			$('.price_based_on_order input').formValidation({validType:['trim','amount'],tipPosition:'left',required:false});
		}
		if(type==1){
			//$(".purchase_price input").val("");
			$(".purchase_price").hide();
			$(".price_based_on_order").show();
			$(".additional_cost").hide();
			$("input[name='price_type']").val(1);
			$('.purchase_price input').formValidation({validType:['trim','amount'],tipPosition:'left',required:false});
			$('.additional_cost input').formValidation({validType:['trim','amount'],tipPosition:'left'});
			$('.price_based_on_order input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
		}
		$.hideLoading();
	},

	'initFormValidateInput' : function (){
		$('.purchase_price input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
		$('.additional_cost input').formValidation({validType:['trim','amount'],tipPosition:'left'});
		$('.logistics_cost input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
		$('.logistics_weight input').formValidation({validType:['trim','amount'],tipPosition:'left'});
		
		$('.logistics_cost input').each(function(){
			if(this.value == '')
				$(this).nextAll("td[id^='cancel_order_'] button").show();
			else
				$(this).nextAll("td[id^='cancel_order_'] button").hide();
		});
	},

	'initCancelBtn' : function (obj,id){
		var value = $(obj).val();
		if(value=='' || typeof(value)=='undefined'){
			$("#cancel_order_"+id+" button").show();
		}else{
			$("#cancel_order_"+id+" button").hide();
		}
	},

	'setCostAndProfitOrder' : function (){
		$.showLoading();
		if (!$('#order_cost_form').formValidation('form_validate')){
			$.hideLoading();
			bootbox.alert(Translator.t('录入格式不正确或不完整!'));
			return false;
		}
		$.ajax({
			type: "POST",
			dataType: 'json',
			url : '/statistics/profit/set-cost-and-profit-order',
			data:$("#order_cost_form").serialize(),
			success: function (result) {
				$.hideLoading();
				if(result.success && result.calculated_profit){
					if(result.message=='')
						var msg='操作成功！';
					else
						var msg=result.message;
					bootbox.alert({
						buttons: {
							ok: {
								label: 'OK',
								className: 'btn-primary'
							}
						},
						message: msg,
						callback: function() {
							$('.profit-order').modal('hide');
							profit.list.profit_search(0);
							//window.location.reload();
						}
					});
				}
				else{	
					bootbox.alert({
						buttons: {
							ok: {
								label: 'OK',
								className: 'btn-primary'
							}
						},
						message: '有部分设置保存失败,请再次设置失败部分：'+result.message,
						callback: function() {
							$('.profit-order').modal('hide');
							$('.profit-order').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					});
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert("出现错误，请联系客服求助...");
				return false;
			}
		
		});
	},

	'cancelProfit' : function (){
		$('.profit-order').modal('hide');
		$('.profit-order').on('hidden.bs.modal', '.modal', function(event) {
			$(this).removeData('bs.modal');
		});
	},

	'cancelProfitOrder' : function (id){
		$("#cancel_order_"+id).parent().remove();
		$("input[name='order_id[]'][value='"+id+"']").prop('checked',false);
	},
}
