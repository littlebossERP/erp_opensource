
/**
 +------------------------------------------------------------------------------
 * priceminister OMS界面js
 +------------------------------------------------------------------------------
 * @category	js/project/order
 * @package		order
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */
 
if (typeof pmOrder === 'undefined')  pmOrder = new Object();

pmOrder.list = {
	operateNewSaleItem : function (operate, itemid, sellerid){
		if(operate=='refuse'){
			bootbox.confirm(Translator.t("确定拒绝接受订单商品？"),function(r){
				if (! r) return;
				$.showLoading();
				$.ajax({
					type: "POST",
					dataType: 'json',
					url: '/order/priceminister-order/operate-new-sale',
					data: {operate:operate,itemid:itemid,sellerid:sellerid},
					success: function(result){
						$.hideLoading();
						if(result.success){
							bootbox.alert('操作成功，稍后将会同步最新状态。');
							return true;
						}else{
							bootbox.alert('操作失败：'+result.message);
							return false;
						}
					},
					error :function(){
						$.hideLoading();
						bootbox.alert('操作失败：后台传输异常！');
						return false;
					}	
				});
				
			});	
		}else{
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: '/order/priceminister-order/operate-new-sale',
				data: {operate:operate,itemid:itemid,sellerid:sellerid},
				success: function(result){
					$.hideLoading();
					if(result.success){
						bootbox.alert('操作成功，稍后将会同步最新状态。');
						return true;
					}else{
						bootbox.alert('操作失败：'+result.message);
						return false;
					}
				},
				error :function(){
					$.hideLoading();
					bootbox.alert('操作失败：后台传输异常！');
					return false;
				}	
			});
		}
	}
};



