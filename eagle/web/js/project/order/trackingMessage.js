/**
 +------------------------------------------------------------------------------
 * OMS 通知及营销页面 专用js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof OrderTrackingMessage === 'undefined')  OrderTrackingMessage = new Object();

OrderTrackingMessage = {
	init : function(){
		$("#ck_all").click(function(){
			if($(this).prop("checked")==true){
				$(".ck").prop("checked",true);
			}else{
				$(".ck").prop("checked",false);
			}

		});
	},
	
	ignoreMsgSend : function (order_id){
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}  
			}, 
			message: Translator.t("确认忽略？忽略后选中订单将不会再于通知及营销页面显示！"),
			callback: function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type: "GET",
						url:'/order/od-lt-message/ignore-msg-send?order_id='+order_id,
						dataType:'json',
						success: function (result) {
							$.hideLoading();
							if(result.success===true){
								bootbox.alert({
									message: '操作成功',  
									callback: function() {  
										window.location.reload();
									},  
								});
							}else{
								bootbox.alert(result.message);
								return false;
							}
						},
						error :function () {
							$.hideLoading();
							bootbox.alert(Translator.t('操作失败,后台返回异常'));
							return false;
						}
					});
				}
			}
		});	
	},
	
	batchIgnoreMsgSend : function (){
		var orderidList = [];
		$('input[name="order_id[]"]:checked').each(function(){
			orderidList.push($(this).val());
		});
		if(orderidList.length==0&&type!=''){
			bootbox.alert("请选择要操作的订单");
			return false;
		}
		idstr='';
		$.each(orderidList,function(index, value){
			idstr+=','+value;
		});
		
		OrderTrackingMessage.ignoreMsgSend(idstr);
	},
	
	ignoreTrackingNo:function (order_id,track_no){
		bootbox.confirm({
			buttons: {  
				confirm: {  
					label: Translator.t("确认"),  
					className: 'btn-danger'  
				},  
				cancel: {  
					label: Translator.t("取消"),
					className: 'btn-primary'  
				}  
			}, 
			message: Translator.t("确认忽略物流号？<br><br>一般对平邮或者已经确认投递失败的包裹，进行此操作。如果确认忽略该物流号物流助手将不会再查询该物流号信息。您确定要忽略当前物流号吗？"),
			callback: function(r){
				if(r){
					$.showLoading();
					$.ajax({
						type: "GET",
						url:'/order/od-lt-message/ignore-tracking-no?order_id='+order_id+'&track_no='+track_no,
						dataType:'json',
						success: function (result) {
							$.hideLoading();
							if(result.success===true){
								bootbox.alert({
									message: '操作成功',  
									callback: function() {
										searchButtonClick();
									},  
								});
							}else{
								bootbox.alert(result.message);
								return false;
							}
						},
						error :function () {
							$.hideLoading();
							bootbox.alert(Translator.t('操作失败,后台返回异常'));
							return false;
						}
					});
				}
			}
		});
	},
}

function searchButtonClick(){
	var Url=global.baseUrl +'order/od-lt-message/list-tracking-message';
	
	$.location.state(Url,'小老板',$("#searchForm").serialize(),0,'post',false);
}

function customProductSearch(){
	var Url=global.baseUrl +'order/od-lt-message/custom_product_list';
	
	$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
}

function groupSearch(){
	var Url=global.baseUrl +'order/od-lt-message/group_list';
	
	$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
}


if (typeof customProduct === 'undefined')  customProduct = new Object();

customProduct = {
	dataSet:{
		isMustAttrArr: [],
		mustAttr:["product_name","title","photo_url","product_url","price","currency","platform","seller_id"],
		mustSelectAttr:["currency","platform","seller_id"],
		mustGroupSelectAttr:["platform","seller_id"],
	},
	
	init:function(){
//		$(document).on('click', 'input[name="group_select"]', function () {
//            $(this).closest('div[class="row"]').find('input[type="text"],select').attr('disabled', true);
//            if (this.checked) {
//                $(this).closest('div').find('input[type="text"],select').attr('disabled', false);
//            };
//        });
		$(document).on('click', '#chk_all', function () {
			customProduct.checkAll(this);
        });
		
	},
	
	newProduct:function(){
		var handle= $.openModal("/order/od-lt-message/customized-recommended-product",{},'自定义商品','post');  // 打开窗口命令
		handle.done(function($window){
			
			customProduct.customPlatformChange('select[name="platform"]','new');//当做select的onchange事件
			
			$window.find('#saveNewProduct').on('click',function(){
				customProduct.saveProdcut($window);
			});
			$window.find('#windowDisplay').on('click',function(){
				$window.close();
			});
			
		});
	},
	
	saveProdcut:function(winobj){
		customProduct.dataSet.isMustAttrArr = [];
		$(customProduct.dataSet.mustAttr).each(function(i){
			 
			if($.inArray(customProduct.dataSet.mustAttr[i], customProduct.dataSet.mustSelectAttr) != -1){
				if($('select[name="'+customProduct.dataSet.mustAttr[i]+'"]').val() == ''){
					customProduct.dataSet.isMustAttrArr.push($('select[name="'+customProduct.dataSet.mustAttr[i]+'"]').data("name"));
				}
			}else{
				if($('input[name="'+ customProduct.dataSet.mustAttr[i] +'"]').val() == ''){
					customProduct.dataSet.isMustAttrArr.push($('input[name="'+ customProduct.dataSet.mustAttr[i] +'"]').data("name"));
				}
			}
			
		});
		
		if(customProduct.dataSet.isMustAttrArr.length > 0){
			var alert_message = '';
			$(customProduct.dataSet.isMustAttrArr).each(function(i){
				alert_message += customProduct.dataSet.isMustAttrArr[i] + '，';
			});
			$.alertBox(alert_message.substring(0,alert_message.length-1)+"的值不能为空！");
		}else if($('#group_name').val() != ''){
			if($('#belong_platform').data('name') == undefined){
				$.alertBox('组别属性获取失败，保存失败！');
			}else if($('#belong_platform').data('name') == $('select[name="platform"]').val() && $('#belong_seller').data('name') == $('select[name="seller_id"]').val()){
				$.ajax({//与下面的保存一样
		            type: 'POST',
		            url: '/tracking/tracking-recommend-product/save-product',
		            data:$('#custom_product').serialize(),
		            dataType: 'json',
		            success: function (data) {
		                if (data.code == 200) {
		                	$.alertBox(data.message);
		                	winobj.close();
		                	//location.reload();
							var Url=global.baseUrl +'order/od-lt-message/custom_product_list';
							$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
		                } else {
		                	$.alertBox(data.message);
		                }

		            },
		            error: function () {
		            	$.alertBox("网络错误！");
		            }
		        });
			}else{
				$.alertBox('保存失败，商品的平台和店铺与商品组的平台和店铺一致！');
			}
		}else{
			$.ajax({
	            type: 'POST',
	            url: '/tracking/tracking-recommend-product/save-product',
	            data:$('#custom_product').serialize(),
	            dataType: 'json',
	            success: function (data) {
	                if (data.code == 200) {
	                	$.alertBox(data.message);
	                	winobj.close();
	                	//location.reload();
						var Url=global.baseUrl +'order/od-lt-message/custom_product_list';
						$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
	                } else {
	                	$.alertBox(data.message);
	                }

	            },
	            error: function () {
	            	$.alertBox("网络错误！");
	            }
	        });
		}
		
	},
	
	editProduct:function(product_id){
		if(product_id == ''||product_id == undefined){
			$.alertBox("编辑失败，数据有误！");
		}else{
			var handle= $.openModal("/order/od-lt-message/edit-product",{product_id:product_id},'编辑商品','post');  // 打开窗口命令
			handle.done(function($window){
				
				customProduct.customPlatformChange('select[name="platform"]','edit');//当做select的onchange事件
				
				$window.find('#saveNewProduct').on('click',function(){
					customProduct.saveProdcut($window);
				});
				$window.find('#windowDisplay').on('click',function(){
					$window.close();
				});
			});
		}
	},
	
	newGroup:function(){
		var handle= $.openModal("/order/od-lt-message/new-group",{},'新建商品组','post');  // 打开窗口命令
		handle.done(function($window){
			$window.find('#saveNewGroup').on('click',function(){
				customProduct.dataSet.isMustAttrArr = [];
				$(customProduct.dataSet.mustGroupSelectAttr).each(function(i){
					if($('select[name="'+customProduct.dataSet.mustGroupSelectAttr[i]+'"]').val() == ''){
						customProduct.dataSet.isMustAttrArr.push($('select[name="'+customProduct.dataSet.mustGroupSelectAttr[i]+'"]').data("name"));
					}
				});
				
				if($('input[name="group_name"]').val() == ''){
					customProduct.dataSet.isMustAttrArr.push($('input[name="group_name"]').data("name"));
				}
					
				
				if(customProduct.dataSet.isMustAttrArr.length > 0){
					var alert_message = '';
					$(customProduct.dataSet.isMustAttrArr).each(function(i){
						alert_message += customProduct.dataSet.isMustAttrArr[i] + '，';
					});
					$.alertBox(alert_message.substring(0,alert_message.length-1)+"的值不能为空！");
				}else{
					$.ajax({
			            type: 'POST',
			            url: '/tracking/tracking-recommend-product/save-group',
			            data:$('#custom_group').serialize(),
			            dataType: 'json',
			            success: function (data) {
			                if (data.code == 200) {
			                	$.alertBox(data.message);
			                	$window.close();
			                } else {
			                	$.alertBox(data.message);
			                }

			            },
			            error: function () {
			            	$.alertBox("网络错误！");
			            }
			        });
				}
			});
			
			$window.find('#windowDisplay').on('click',function(){
				$window.close();
			});
			
		});
	},
	
	addToGroup:function(){
        var ids = [];
        $(".lzd_body>tr").each(function (i) {
            var id = '';
            if ($(this).find('input[id="chk_one"]').prop('checked')) {
                id = $(this).data("id");
                ids.push(id);
            }
        });
        if (ids.length > 0) {
        	var handle= $.openModal("/order/od-lt-message/add-products-to-group",{ids:ids},'新建商品组','post');  // 打开窗口命令
    		handle.done(function($window){
    			$window.find('#saveToGroup').on('click',function(){
    				if($('#group_name').val() == ''){
    					$.alertBox("请选择要加入的商品组！");
    				}else{
    					$.ajax({
    			            type: 'POST',
    			            url: '/tracking/tracking-recommend-product/add-products-to-group-save',
    			            data:$('#addToGroup').serialize(),
    			            dataType: 'json',
    			            success: function (data) {
    			                if (data.code == 200) {
    			                	$.alertBox(data.message);
    			                	$window.close();
    			                } else {
    			                	$.alertBox(data.message);
    			                }

    			            },
    			            error: function () {
    			            	$.alertBox("网络错误！");
    			            }
    			        });
    				}
    				
    			});
    			
    			$window.find('#windowDisplay').on('click',function(){
					$window.close();
				});
    		});
        } else {
            $('select[name="batch"] option').eq(0).prop('selected', true);
            $.alertBox("没有勾选相关产品！");
            return null;
        };
	},
	
	deleteProduct:function(id){
		$event = $.confirmBox('您确定删除该商品吗？');
		$event.then(function(){
			$.showLoading();
			$.ajax({
		        type : 'POST',
		        data : {id:id},
				url: '/tracking/tracking-recommend-product/delete-product',
				dataType: 'json',
		        success:function(data) {
		        	$.hideLoading();
		        	if (data.code == 200) {
	                	$.alertBox(data.message);
	                	//location.reload();
						var Url=global.baseUrl +'order/od-lt-message/custom_product_list';
						$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
	                } else {
	                	$.alertBox(data.message);
	                }
		        },
		        error: function () {
		        	$.hideLoading();
	            	$.alertBox("网络错误！");
	            }
		    });
		});
	},
    
	checkAll: function (obj) {
        if ($(obj).prop('checked')) {
            $('input[type="checkbox"]').each(function (i) {
                $(this).prop("checked", true);
            });
        } else {
            $('input[type="checkbox"]').each(function (i) {
                $(this).prop("checked", false);
            });
        }
    },
    
    saveGroup:function(){
    	customProduct.dataSet.isMustAttrArr = [];
		$(customProduct.dataSet.mustGroupSelectAttr).each(function(i){
			if($('select[name="'+customProduct.dataSet.mustGroupSelectAttr[i]+'"]').val() == ''){
				customProduct.dataSet.isMustAttrArr.push($('select[name="'+customProduct.dataSet.mustGroupSelectAttr[i]+'"]').data("name"));
			}
		});
		
		if($('input[name="group_name"]').val() == ''){
			customProduct.dataSet.isMustAttrArr.push($('input[name="group_name"]').data("name"));
		}
			
		
    	if(customProduct.dataSet.isMustAttrArr.length > 0){
    		var alert_message = '';
			$(customProduct.dataSet.isMustAttrArr).each(function(i){
				alert_message += customProduct.dataSet.isMustAttrArr[i] + '，';
			});
			$.alertBox(alert_message.substring(0,alert_message.length-1)+"的值不能为空！");
    	}else{
    		$.showLoading();
    		$.ajax({
	            type: 'POST',
	            url: '/tracking/tracking-recommend-product/edit-group-list-save',
	            data:$('#custom_group').serialize(),
	            dataType: 'json',
	            success: function (data) {
	            	$.hideLoading();
	                if (data.code == 200) {
	                	$.alertBox(data.message);
	                	//window.location.href = global.baseUrl+'tracking/tracking-recommend-product/group-list';
						var Url=global.baseUrl +'order/od-lt-message/group_list';
						$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
	                } else {
	                	$.alertBox(data.message);
	                }

	            },
	            error: function () {
	            	$.hideLoading();
	            	$.alertBox("网络错误！");
	            }
	        });
    	}
    },
    
    deleteGroup:function(id){
		$event = $.confirmBox('您确定删除该商品组吗？');
		$event.then(function(){
			$.showLoading();
			$.ajax({
		        type : 'POST',
		        data : {id:id},
				url: '/tracking/tracking-recommend-product/delete-group',
				dataType: 'json',
		        success:function(data) {
		        	$.hideLoading();
		        	if (data.code == 200) {
	                	$.alertBox(data.message);
	                	//location.reload();
						var Url=global.baseUrl +'order/od-lt-message/group_list';
						$.location.state(Url,'小老板',$("#customListSearch").serialize(),0,'post',false);
	                } else {
	                	$.alertBox(data.message);
	                }
		        },
		        error: function () {
		        	$.hideLoading();
	            	$.alertBox("网络错误！");
	            }
		    });
		});
    },
	
	editGroupProds:function(id){
		//debugger;
		$.openModal("/order/od-lt-message/edit-group-list",{id:id},'编辑推荐商品分组','post');  // 打开窗口命令
	},
    
    platformChange:function(obj){
    	$('#group_attr').html('');
    	if($(obj).val() != ''){
    		var str = '';
    		$.ajax({
		        type : 'POST',
		        data : {group_id:$(obj).val()},
				url: '/tracking/tracking-recommend-product/get-group-info',
				dataType: 'json',
		        success:function(data) {
		        	if (data.code == 200) {
		        		str = '<span id="belong_platform" data-name="'+ data.data.platform +'">所属平台：'+ data.data.platform +'</span>&nbsp;&nbsp;<span id="belong_seller" data-name="'+ data.data.seller +'">所属店铺：'+ data.data.seller +'</span>';
		        		$('#group_attr').html(str);
		        	} else {
		        		str = '<span>获取组别属性失败</span>';
		        		$('#group_attr').html(str);
	                }
		        },
		        error: function () {
	            	$.alertBox("网络错误！");
	            }
		    });
    	}
    },
    
    //多个点店铺换时返回对应的所有帐号信息
	customProductListOnChange:function(obj){
		if($(obj).val() != ''){
			$.ajax({
		        type : 'POST',
		        data : {platform:$(obj).val()},
				url: '/tracking/tracking-recommend-product/get-platform-accounts',
				dataType: 'json',
		        success:function(data) {
		        	if (data.code == 200) {
		        		var str = '<option value="">展示店铺</option>';
		        		for(var name in data.data){
		        			str += '<option value="'+ name +'">'+ name +'</option>';
		        		}
		        		$('select[name="seller_search"]').html(str);
		        	} else {
		        		var str = '<option value="">展示店铺</option>';
		    			$('select[name="seller_search"]').html(str);
		    			$.alertBox(data.message);
	                }
		        },
		        error: function () {
	            	$.alertBox("网络错误！");
	            }
		    });
		}else{
			var str = '<option value="">展示店铺</option>';
			$('select[name="seller_search"]').html(str);
		}
	},
	
	customPlatformChange:function(obj,type){
		if($(obj).val() != ''){
			$.ajax({
		        type : 'POST',
		        data : {platform:$(obj).val()},
				url: '/tracking/tracking-recommend-product/get-platform-accounts',
				dataType: 'json',
		        success:function(data) {
		        	if (data.code == 200) {
		        		var str = '<option value="">选择店铺</option>';
		        		for(var name in data.data){
		        			str += '<option value="'+ name +'">'+ name +'</option>';
		        		}
		        		$('select[name="seller_id"]').html(str);
		        		if(type != undefined && type == 'edit'){
		        			$('select[name="seller_id"] option[value="'+ $('#accountValue').val() +'"]').prop('selected',true);
		        		}else if(type != undefined && type == 'new'){
		        			$('select[name="seller_id"] option[value="'+ $('#userHabit').val() +'"]').prop('selected',true);
		        		}
		        	} else {
		        		var str = '<option value="">选择店铺</option>';
		    			$('select[name="seller_id"]').html(str);
		    			$.alertBox(data.message);
	                }
		        },
		        error: function () {
	            	$.alertBox("网络错误！");
	            }
		    });
		}else{
			var str = '<option value="">选择店铺</option>';
			$('select[name="seller_id"]').html(str);
		}
	},
}
