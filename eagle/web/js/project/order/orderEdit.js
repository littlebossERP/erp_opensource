	//改变仓库时，拉取对应的运输服务
	function load_shipping_method_for_control(obj, control){
		$warehouse_id = $(obj).selectVal();
		$change_shipping_method_code = $(control);
		$.ajax({
			url: global.baseUrl + "order/order/get-shipping-method-by-warehouseid",
			data: {
				warehouse_id:$warehouse_id,
			},
			type: 'post',
			dataType: 'json',
			success: function(response) {
				$change_shipping_method_code.html('');
				var options = '';
				$.each(response , function(index , value){
					options += '<option value="'+index+'">'+value+'</option>';
				});
				$change_shipping_method_code.html(options);
				
			}
		});
	}
