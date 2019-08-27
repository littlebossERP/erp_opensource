/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author:  dzt <zhitian.deng@witsion.com> eagle 1.0
+----------------------------------------------------------------------
| Copy by: lzhl <zhiliang.lu@witsion.com> 2015-04-16 eagle 2.0
+----------------------------------------------------------------------
 */
 
/**
 +------------------------------------------------------------------------------
 *修改采购备货策略的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		suggestion
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof purchaseSug === 'undefined')  purchaseSug = new Object();

purchaseSug.modifyPage = {
	strategiesInfo : false,
	submitUrl : global.baseUrl+'purchase/purchasesug/set-stock-strategies',
	'init' : function (){	
    	$('input[type="text"]').formValidation({validType:['trim','integer'],tipPosition:'left'});

    	$('#save-modified-strategies').click(function(){
    		var validation = purchaseSug.modifyPage.validateRules();
    		if(validation){
				$.showLoading();
	    		$.post(purchaseSug.modifyPage.submitUrl,
					$('#stock-strategy-setup-form').serialize(),
					function(data){
						$.hideLoading();
						bootbox.dialog({
							className : "modified-strategies-result",
							title : Translator.t("操作结果"),
							buttons : {
								Cancel : {
									label : Translator.t("关闭"),
									className : "btn-default",
									callback : function() {
										if(data.success==true){
											window.location.href = global.baseUrl+'purchase/purchasesug/sug_strategies';
										}else{
											$('.modified-strategies-result').modal('hide');
											$('.modified-strategies-result').on('hidden.bs.modal', '.modal', function(event) {
												$(this).removeData('bs.modal');
											});
										}
									}
								}
							},
							message : (data['message']=='')?Translator.t('保存成功！'):data['message'],
						});
					},
					'json'
				);
	    		
    		}
    	});
    
    	$('#refresh-modified-strategies').click(function(){
    		window.location.href = global.baseUrl+'purchase/purchasesug/sug_strategies';
    	});
	},
	'validateRules':function(){
		var item = $(":radio:checked");
		var len=item.length;
		var checkedVal = '';
		if(len>0){
			checkedVal=$(":radio:checked").val();
		}
		if(checkedVal==''){
			bootbox.alert(Translator.t('无选中任何方案'));
			return false;
		}
		if(checkedVal==1){
			if (! $('#stock-strategy-setup-form').formValidation('form_validate')){
				bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
				return;
			}
			var normal_stock=$('input[name="normal_stock"]').val();
			var min_stock=$('input[name="min_stock"]').val();
			if (normal_stock==='' ||min_stock===''){
				bootbox.alert(Translator.t('常备库存数 和 最低盈余库存数 必须填写'));
				return;
			}
			normal_stock=parseInt(normal_stock);
			min_stock=parseInt(min_stock);
			if(normal_stock<=min_stock){
				bootbox.alert(Translator.t('常备库存数 必须大于 触发备货的 最低盈余库存数'));
				return false;
			}
		}
		if(checkedVal==2){
			if (! $('#stock-strategy-setup-form').formValidation('form_validate')){
				bootbox.alert('<b style="color:red;">'+Translator.t('有必填信息未填写，或信息填写有误。')+'</b>');
				return;
			}
			var count_sales_period=parseInt($('input[name="count_sales_period"]').val());
			var min_total_sales_percentage=parseInt($('input[name="min_total_sales_percentage"]').val());
			var stock_total_sales_percentage=($('input[name="stock_total_sales_percentage"]').val());
			if (count_sales_period=='' ||min_total_sales_percentage=='' ||stock_total_sales_percentage==''){
				bootbox.alert(Translator.t('统计周期 和 销量百分比 均必须填写'));
				return false;
			}else{
				if(min_total_sales_percentage>=stock_total_sales_percentage){
					bootbox.alert(Translator.t('建议备库存数量百分比必须大于触发备份库存的百分比'));
					return false;
				}
			}
		}
		return true;
	}
};