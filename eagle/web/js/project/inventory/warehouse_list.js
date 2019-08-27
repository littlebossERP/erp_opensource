/**
 +------------------------------------------------------------------------------
 * 仓库列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		inventory/warehouse
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof Warehouse === 'undefined')  Warehouse = new Object();
/*
Warehouse.setting = {
		'selectedActiveStatus':0,
		'activeStatus':"",
		'CountriesCnName':""
};
*/
Warehouse.list = {
	'init': function() {
		$('#btn_clear').click(function() {
			url = global.baseUrl + "inventory/warehouse/warehouse_list";
			window.location.href = url;
		});
		$("#create_new_locally_warehouse").click(function () {
			$.showLoading();
			$.get(global.baseUrl + 'inventory/warehouse/get-warehouse-detail?tt=create', function(data) {
				$.hideLoading();
				bootbox.dialog({
					className : "view_wh_detail_win",
					//title: Translator.t("仓库详情"),
					//closeButton : false,
					buttons:{
						Cancel: {
							label: Translator.t("取消"),
							className: "btn-default",
							callback : function() {
								$('.view_wh_detail_win').modal('hide');
								$('.view_wh_detail_win').on('hidden.bs.modal', '.modal', function(event) {
									$(this).removeData('bs.modal');
								});
							}
						},
						Save: {
							label: Translator.t("保存"),
							className: "btn-primary",
							callback : function() {
								if (! $('#warehouse_model_form').formValidation('form_validate')){
									bootbox.alert(Translator.t('有必填项未填或格式不正确!'));
									return false;
								}
								$.showLoading();
								$.ajax({
									type:'post',
									data:$("#warehouse_model_form").serialize(),
									url:global.baseUrl + 'inventory/warehouse/create-warehouse', 
									dataType:'json',
									success: function(data) {
										$.hideLoading();
										bootbox.alert({
											title:Translator.t('操作结果'),
											message:(data.message=='')?Translator.t("保存成功！"):data.message,
											callback: function() {
												if(data.success)
													window.location.reload();
											}
										});
									},
									error:function(){
										$.hideLoading();
										bootbox.alert({
											message : '<b style="color:red;">'+Translator.t("后台获取数据异常，请重试")+"</b>",
										});
									},
								});
							}
						}
					},
					message: data,
				});
			});
		});
	},
	'initFormValidateInput' : function(){
			$('#warehouse_model_form #name').formValidation({validType:['trim','length[2,50]','safeForHtml'],tipPosition:'left',required:true});
			$('#warehouse_model_form #comment').formValidation({validType:['trim','length[0,255]','safeForHtml'],tipPosition:'left'});
		
			$('#warehouse_model_form select#address_nation').combobox({removeIfInvalid:true});
		},
	
	'activeStatusFliter' : function(status){
		switch (status)
		{
		case 'Y':
		$("#warehouse_list_params_form input[name='is_active']").val('Y');
		break;
		case 'N':
		$("#warehouse_list_params_form input[name='is_active']").val('N');
		break;
		case 'All':
		$("#warehouse_list_params_form input[name='is_active']").val('');
		}
		
		$("#warehouse_list_params_form").submit();
	},
	
	'viewWarehouseDetail' : function (id){
		$.showLoading();
		$.get(global.baseUrl + 'inventory/warehouse/get-warehouse-detail?tt=view&warehouse_id='+id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "view_wh_detail_win",
				//title: Translator.t("仓库详情"),
				//closeButton : false,
				buttons:{
					Cancel: {
						label: Translator.t("关闭"),
						className: "btn-default",
						callback : function() {
							$('.view_wh_detail_win').modal('hide');
							$('.view_wh_detail_win').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					}, 
				},
				message: data,
			});
		});
	},
	'editWarehouse' : function(warehouse_id){
		$.showLoading();
		$.get(global.baseUrl + 'inventory/warehouse/get-warehouse-detail?tt=edit&warehouse_id='+warehouse_id, function(data) {
			$.hideLoading();
			bootbox.dialog({
				className : "view_wh_detail_win",
				//title: Translator.t("仓库详情"),
				//closeButton : false,
				buttons:{
					Cancel: {
						label: Translator.t("取消"),
						className: "btn-default",
						callback : function() {
							$('.view_wh_detail_win').modal('hide');
							$('.view_wh_detail_win').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
						}
					},
					Save: {
						label: Translator.t("保存"),
						className: "btn-primary",
						callback : function() {
							if (!$('#warehouse_model_form').formValidation('form_validate')){
								bootbox.alert(Translator.t('有必填项未填或格式不正确!'));
								return false;
							}

							var save_val = $("#warehouse_model_form").serialize();
							//当关闭仓库时，判断是否存在库存，存在则弹出提示
							var is_stock = false;
							var is_active = $('#is_active').val();
							if(is_active != null && is_active == 'N'){
								$.ajax({
									type:'GET',
									data:{'warehouse_id' : warehouse_id},
									url:global.baseUrl + 'inventory/warehouse/check-stock', 
									dataType:'json',
									async : false,
									success: function(result) {
										is_stock = result.success;
									},
									error:function(){
										return false;
									},
								});
							}
							
							if(is_stock){
								bootbox.confirm(
								Translator.t("<span style='color:red'>此仓库存在库存或者在途数量，是否继续关闭？</span>"),
									function(r){
										if(r){
											Warehouse.list.saveWarehouse(save_val);
											return true;
										}
										else{
										}
									}
								);
							}
							else{
								Warehouse.list.saveWarehouse(save_val);
							}
						}
					}
				},
				message: data,
			});
		});
	},
	
	'saveWarehouse' : function(val){

		$.showLoading();
		$.ajax({
			type:'post',
			data:val,
			url:global.baseUrl + 'inventory/warehouse/create-warehouse', 
			dataType:'json',
			success: function(data) {
				$.hideLoading();
				bootbox.alert({
					title:Translator.t('操作结果'),
					message:(data.message=='')?Translator.t("保存成功！"):data.message,
				});
			},
			error:function(){
				$.hideLoading();
				bootbox.alert({
					message : '<b style="color:red;">'+Translator.t("后台获取数据异常，请重试")+"</b>",
				});
			},
		});
	},
	
	'changeActiveStatus' : function(warehouse_id,status){
		
		
	},
		
}; //end of object Warehouse

