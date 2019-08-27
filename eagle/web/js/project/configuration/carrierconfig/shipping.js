if (typeof ShippingJS === 'undefined')  ShippingJS = new Object();

ShippingJS={
		init:function(){
			$("select[name^='shipMethodList']").combobox({removeIfInvalid:false});
			$(".ui-combobox-input").prop('placeholder','输入运输服务名称快速找到要用的运输服务，按回车进行搜索');
			$(".ui-combobox-input").val('');
			$('.ui-menu').click(function(){
				type = $('input[name=ThisType]').val();
				if($(".ui-combobox-input").val() == '输入运输服务名称快速找到要用的运输服务'){
					$(".ui-combobox-input").val('');
				}
				$carrier_code = $('input[name=carrier_code]').val();
				var Url=global.baseUrl +'configuration/carrierconfig/findshipping';
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : {
			        	service_name:$(".ui-combobox-input").val(),
			        	carrier_code:$carrier_code,
			        	type:type,
			        },
					url: Url,
			        success:function(response) {
			        	$('#all_shipping_shows_DIV').html(response);
			        	ChangeTableWidth();
			        }
			    });
			});
			$(".ui-combobox-input").keypress(function(){
//				_this = $(this);
//				obj = _this.parent().prev();
//				if(obj.prop('id') == 'shipMethodList'){
//					if(event.keyCode == "13"){
//						$carrier_code=$(this).parents().parents().next().val();
//						$('#table_'+$carrier_code).find ('tr').hide ();
//						$('#table_'+$carrier_code).find ('tr:contains("' + $(this).val() + '")').show ();
//					}
//				}
				_this = $(this);
				obj = _this.parent().prev();
				if(obj.prop('id') == 'shipMethodList')
					if(event.keyCode == "13"){
						$carrier_code = $('input[name=carrier_code]').val();
						type = $('input[name=ThisType]').val();
						var Url=global.baseUrl +'configuration/carrierconfig/findshipping';
						$.ajax({
					        type : 'post',
					        cache : 'false',
					        data : {
					        	service_name:_this.val(),
					        	carrier_code:$carrier_code,
					        	type:type,
					        },
							url: Url,
					        success:function(response) {
					        	$('#all_shipping_shows_DIV').html(response);
					        	ChangeTableWidth();
					        }
					    });
					}
			});
			//批量修改
			$('#batchChange').change(function(){
				val = $(this).val();
				if(val > 0){
					$sform = $('#batchship').serialize();
					reUrl = '';
					resMsg = '请选择需要批量修改的运输服务';
					switch(val){
						case '1':
							reUrl = '_batchchangeaddress';
							break;
						case '2':
							reUrl = '_batchcloseship';
							break;
						case '3':
							reUrl = '_batchchangewarehouse';
							break;
					}
					if(reUrl != ""){
						var Url=global.baseUrl +'configuration/carrierconfig/batchchangeshipping?reurl='+reUrl;
						$.ajax({
					        type : 'post',
					        cache : 'false',
					        data : $sform,
							url: Url,
					        success:function(response) {
					        	if(response[0] == 1){
					        		bootbox.alert(resMsg);
					        	}else if(response[0] == 0){
					        		$('#myModal').html(response.substring(1));
						        	$('#myModal').modal('show');
					        	}
					        	else{
					        		bootbox.alert(response);
					        	}
					        }
					    });
					}
					$(this).val(0);
				}
			});
			//运输服务全选/取消
			$('input[name=check_all]').click(function(){
				is_check = $(this).prop('checked');
				$('.selectShip').each(function(){
					$(this).prop('checked',is_check);
				});
			});
			
			ChangeTableWidth();
			$(window).resize(function(){
				ChangeTableWidth();
			});
			
		},
		init2:function(){
			$("select[name^='params[service_code][amazon]']").combobox({removeIfInvalid:false});
			$('input[name^="proprietary_warehouses"]').click(function(){
				if($(this).is(':checked')){
					$('.houses'+$(this).val()).val(1);
				}
				else{
					$('.houses'+$(this).val()).val(0);
				}
			});
			$('input[name="print[selected]"]').change(function(){
				var val = $(this).val();
				for(var i = 0; i < 3; i++){
					var vals = 0;
					if(val == i){
						vals = 1;
					}
					$('.print_selected_'+i).val(vals);
				}
			});
			$('#label_custom').change(function(){
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArr"]').hide();
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArrNew"]').hide();
				$('div[class="drop_list"]').find('select[data="label_custom"]').show();
				$('#labelcustom').show();
				$('div[class="drop_list"]').find('select[data="label_custom_new"]').hide();
			});
			$('#label_custom_new').change(function(){
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArr"]').hide();
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArrNew"]').hide();
				$('div[class="drop_list"]').find('select[data="label_custom"]').hide();
				$('#labelcustom').hide();
				$('div[class="drop_list"]').find('select[data="label_custom_new"]').show();
			});
			$('#label_littlebossOptionsArr').change(function(){
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArr"]').show();
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArrNew"]').hide();
				$('div[class="drop_list"]').find('select[data="label_custom"]').hide();
				$('#labelcustom').hide();
				$('div[class="drop_list"]').find('select[data="label_custom_new"]').hide();
			});
			$('#label_littlebossOptionsArrNew').change(function(){
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArrNew"]').show();
				$('div[class="drop_list"]').find('select[data="label_custom"]').hide();
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArr"]').hide();
				$('#labelcustom').hide();
				$('div[class="drop_list"]').find('select[data="label_custom_new"]').hide();
			});
			$('#label_api').change(function(){
				$('div[class="drop_list"]').find('select[data="label_custom"]').hide();
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArr"]').hide();
				$('div[class="drop_list"]').find('div[data="label_littlebossOptionsArrNew"]').hide();
				$('#labelcustom').hide();
				$('div[class="drop_list"]').find('select[data="label_custom_new"]').hide();
			});
			$(".gaoji").click(function(){
				if($(".gaojiitem").is(":hidden")){
					$(".gaojiitem").show();
					$('#caretDown').html("- 收起");
				}
				else{
					$(".gaojiitem").hide();
					$('#caretDown').html("+ 展开");
				}
			});
			
			
		}
}

//调整运输服务table，使对齐
function ChangeTableWidth(){
	div = $('.like_table_div2').width();
	table = $('.like_table').width();
	paddingR = div-table;
	$('.like_table_div').css('padding-right',(paddingR+4)+".1px");
}

//开启或关闭指定运输服务
function openOrCloseShipping(_this,type,key,servercode,shipcode,account){
	$id = $(_this).parent().parent().attr('data');
	$is_used = 0;
	
	if(type == 'open'){
		if($('input[name=address_not_null_'+servercode+']').val() == '1'){
//			$.alert('请先添加地址信息再开启运输服务');
			$('input[name=address_not_null_'+servercode+']').parent().find('.iv-btn.btn-search.title-button').click();
			return false;
		}
		
		$is_used = 1;
		var id = $(_this).parent().parent().attr('data');
//		var code = $('input[name=carrier_code]').val();
		var code = servercode;
		
		$.modal({
			  url:'/configuration/carrierconfig/shippingservice',
			  method:'get',
			  data:{id:id, code:code, type:type, key:key,shipcode:shipcode,account:account}
			},'编辑',{footer:false,inside:false}).done(
				function($modal){
				$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
				}
			);
	}else{
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {
	        	id:$id,
	        	is_used:$is_used,
	        },
			url: '/configuration/carrierconfig/openorcloseshipping',
	        success:function(response) {
	        	if(response[0] == 0){
	        		bootbox.alert(response.substring(2));
	        		if(undefined == $('#search_carrier_code').val()){
	        			
	        		}else if(($('#search_tab_active').val() == 'customexcel') || ($('#search_tab_active').val() == 'customtracking') || ($('#search_tab_active').val() == 'apicarrier')){
        				window.location= global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
        			}
		        }
	        	else{
	        		bootbox.alert(response.substring(2));
		        }
	        }
	    });
	}
}

//打开规则
function selectRuless(obj,sid){
	warehouse_id = arguments[2] ? arguments[2] : 0;//设置第二个参数的默认值为2 
	
	var val = $(obj).val();
	if(val >= 0){
		$.modal({
			  url:'/configuration/carrierconfig/shippingrules',
			  method:'get',
			  data:{id:val,sid:sid,warehouse_id:warehouse_id}
			},'编辑',{footer:false,inside:false}).done(
				function($modal){
				$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
				}
			);
		$(obj).val(-1);
	}
}

//更新运输服务
function UpdateShipping(key){
	$.maskLayer(true);
	$carrier_code = key;
//	$carrier_code = $('input[name=carrier_code]').val();
	var Url=global.baseUrl +'configuration/carrierconfig/updateshippingservice';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	carrier_code:$carrier_code,
        },
		url: Url,
        success:function(response) {
        	$.maskLayer(false);
        	if(response[0] == 0){
        		$e = $.alert(response.substring(2),'success');
        		$e.then(function(){
        			window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$carrier_code;
        		});
	        }
        	else{
        		$.alert(response.substring(2),'danger');
	        }
        },
        error:function(response){
        	$.maskLayer(false);
        	$e = $.alert('网络异常','danger');
    		$e.then(function(){
    			location.reload();
    		});
        }
    });
}

//删除指定运输服务
function delServiceNow(obj){
	$sname = $(obj).parent().parent().children('td:first').text();
	bootbox.confirm("确定删除运输服务 :"+$sname+" ？", function (res) {
        if (res == true) {
			$id = $(obj).parent().parent().attr('data');
			var Url=global.baseUrl +'configuration/carrierconfig/delshippingservice';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
					id:$id,
		            },
				url: Url,
		        success:function(response) {
		        	if(response[0] == 0){
		        		$(obj).parent().parent().remove();
		        		bootbox.alert(response.substring(2));
			        }
		        	else{
		        		bootbox.alert(response.substring(2));
			        }
		        }
		    });
        }
	});
}

//打开新建运输服务modal
function newShipping(){
	var code = $('input[name=carrier_code]').val();
	var Url=global.baseUrl +'configuration/carrierconfig/shippingservice';
	$.ajax({
        type : 'get',
        cache : 'false',
        data : {
			id:0,
			code:code,
			type:'add',
			key:'custom',
            },
		url: Url,
        success:function(response) {
        	$('#myModal').html(response);
        	$('#myModal').modal('show');
        }
    });
}

//打开编辑自定义运输服务modal
function editCustomShipping(obj,type){
	var id = $(obj).parent().parent().attr('data');
	var code = $('input[name=carrier_code]').val();
	var Url=global.baseUrl +'configuration/carrierconfig/shippingservice';
	$.ajax({
        type : 'get',
        cache : 'false',
        data : {
			id:id,
			code:code,
			type:type,
			key:'custom',
            },
		url: Url,
        success:function(response) {
        	$('#myModal').html(response);
        	$('#myModal').modal('show');
        }
    });
}

//打开-《运输服务编辑》modal
function openEditServiceModel(obj,type){
	var id = $(obj).parent().parent().attr('data');
	var code = $('input[name=carrier_code]').val();

	$.openModal('/configuration/carrierconfig/shippingservice',{id:id, code:code, type:type},'编辑','get');
}

function UpdatePlatform($platform, obj){
	$.showLoading();
	$select = $(obj).prev();
	$val = $select.val();
	var Url=global.baseUrl +'configuration/carrierconfig/updateplatform';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {
        	platform:$platform,
        },
		url: Url,
        success:function(response) {
        	$.hideLoading();
        	if(response[0] == 0){
        		$select.html(response.substr(1));
        		$select.val($val);
        		bootbox.alert('更新成功');
        	}
        	else{
        		bootbox.alert(response.substr(2));
        	}
        }
    });
}

function saveShipingService(key){
	$formdata = $('#shippingserviceForm').serialize();
	var Url=global.baseUrl +'configuration/carrierconfig/saveshippingservice?key='+key;

	$.ajax({
        type : 'post',
        cache : 'false',
        data : $formdata,
        dataType: 'json',
		url: Url,
        success:function(response) {
        	if(response.code == 0){
//        		alert(response.msg);
        		bootbox.alert(response.msg);
        		
        		if((undefined == $('#search_tab_active').val()) && (undefined == $('#search_carrier_code').val())){
        			location.reload();
        		}else if(($('#search_tab_active').val() == 'customexcel') || ($('#search_tab_active').val() == 'customtracking')){
        			window.location.href=global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
        		}else{
        			window.location.href = global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+response.data;
        		}
	        }
        	else{
        		bootbox.alert(response.msg);
//        		alert(response.msg);
	        }
        }
    });
}

//eDis同步地址和交运偏好信息
function updatesEdisAddressInof(serviceID){
	$.showLoading();
	var Url=global.baseUrl +'configuration/carrierconfig/update-edis-address-inof';
	$.ajax({
        type : 'post',
        cache : 'false',
        dataType: 'html',
        data : {
        	serviceID:serviceID,
        },
		url: Url,
        success:function(response) {
        	$.hideLoading();
        	$(".edis").html(response);
        }
    });
}

function labelcustomClick(){
	window.location.href = 'http://carriersetting.com/configuration/carrierconfig/carrier-custom-label-list';
}

//打开-《运输服务编辑》modal
function openEditServiceModelNew(obj,type,servercode,account){
	var id = $(obj).parent().parent().attr('data');
	var code = servercode;

	$.modal({
		  url:'/configuration/carrierconfig/shippingservice',
		  method:'get',
		  data:{id:id, code:code, type:type,account:account}
		},'编辑',{footer:false,inside:false}).done(function($modal){
			$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
		}
		);
}

function carrierActiveFalse(type){
	if(type=="1"){
        bootbox.alert("请添加授权");return false;
    }
}

//打开标签模板选择列
function selectMianBan(type){
	$tmp=$('#over-lay').children().eq(1).hide();   //隐藏前弹窗，只针对这页面	
	if(type=='md'){
		var id=$('#printTemplateId').val();
		$.modal({
			  url:'/configuration/carrierconfig/customlabellist',
			  method:'get',
			  data:{type:type,id:id}
			},'选择面单',{footer:false,inside:false}).done(function($modal){
				//确定
				$('.btn.btn-primary').click(function(){
					//将图片，标题，ID放到运输服务页面
					$('#model_md').children().children().attr('src',$modal.find('#BigImg').children('img').attr('src'));
					$('#model_md').children().children().attr('data',$modal.find('#BigImg').children('img').attr('data'));
					$('#model_md').children('input').attr('value',$modal.find('#BigImg').children().attr('data'));
					$('#model_md').children('div').html($modal.find('#BigImgSpan').children().html());
					
					//判断加打内容分别显示或隐藏
					$addshow=$modal.find('#BigImg').children('input').attr('value'); //新的加打内容
					$oldaddshow=$('#oldAddShow').attr('value');     //旧的加打内容
					$oldarr=$oldaddshow.split('|');  //分割数组
					$newstr='';

					if($addshow.indexOf("Order_show")>=0){
						$('#isAddOrder').attr('style','display:inline-block;');
						$newstr=$newstr+'1:Order_show,';
					}
					else if($addshow.indexOf("Order_show")<0 && $oldaddshow.indexOf("2:Order_show,")<0 && $oldaddshow.indexOf("3:Order_show,")<0)
						$('#isAddOrder').attr('style','display:none;');
					
					if($addshow.indexOf("Sku_show")>=0){
						$('#isAddSku').attr('style','display:inline-block;');
						$newstr=$newstr+'1:Sku_show,';
					}
					else if($addshow.indexOf("Sku_show")<0 && $oldaddshow.indexOf("2:Sku_show,")<0 && $oldaddshow.indexOf("3:Sku_show,")<0)
						$('#isAddSku').attr('style','display:none;');
					
					if($addshow.indexOf("CustomsCn_show")>=0){
						$('#isCustomsCn').attr('style','display:inline-block;');
						$newstr=$newstr+'1:CustomsCn_show,';
					}
					else if($addshow.indexOf("CustomsCn_show")<0 && $oldaddshow.indexOf("2:CustomsCn_show,")<0 && $oldaddshow.indexOf("3:CustomsCn_show,")<0)
						$('#isCustomsCn').attr('style','display:none;');
					
					$newstr=$newstr+"|";
			        for (key in $oldarr){
			        	if(key!=0 && key<$oldarr.length-1)
			        		$newstr=$newstr+$oldarr[key]+'|';
			        }
					$('#oldAddShow').attr('value',$newstr);
					
					$('#over-lay').children().eq(1).show();
					$modal.close();
					});
				//关闭页面
				$('.modal-close').click(function(){
					$('#over-lay').children().eq(1).show();
				});
			});
	}
	else if(type=='bg'){
		var id=$('#customsFormTemplateId').val();
		$.modal({
			  url:'/configuration/carrierconfig/customlabellist',
			  method:'get',
			  data:{type:type,id:id}
			},'选择报关单',{footer:false,inside:false}).done(function($modal){
				//关闭页面
				$('.modal-close').click(function(){
					$('#over-lay').children().eq(1).show();
				});
				//确定
				$('.btn.btn-primary').click(function(){
					$('#model_bg').children('img').attr('src',$modal.find('#BigImg').children().attr('src'));
					$('#model_bg').children('img').attr('data',$modal.find('#BigImg').children().attr('data'));
					$('#model_bg').children('input').attr('value',$modal.find('#BigImg').children().attr('data'));
					$('#model_bg').children('div').html($modal.find('#BigImgSpan').children().html());
					
					//判断加打内容分别显示或隐藏
					$addshow=$modal.find('#BigImg').children('input').attr('value'); //新的加打内容
					$oldaddshow=$('#oldAddShow').attr('value');     //旧的加打内容
					$oldarr=$oldaddshow.split('|');  //分割数组
					$newstr='';

					if($addshow.indexOf("Order_show")>=0){
						$('#isAddOrder').attr('style','display:inline-block;');
						$newstr=$newstr+'2:Order_show,';
					}
					else if($addshow.indexOf("Order_show")<0 && $oldaddshow.indexOf("1:Order_show,")<0 && $oldaddshow.indexOf("3:Order_show,")<0)
						$('#isAddOrder').attr('style','display:none;');
					
					if($addshow.indexOf("Sku_show")>=0){
						$('#isAddSku').attr('style','display:inline-block;');
						$newstr=$newstr+'2:Sku_show,';
					}
					else if($addshow.indexOf("Sku_show")<0 && $oldaddshow.indexOf("1:Sku_show,")<0 && $oldaddshow.indexOf("3:Sku_show,")<0)
						$('#isAddSku').attr('style','display:none;');
					
					if($addshow.indexOf("CustomsCn_show")>=0){
						$('#isCustomsCn').attr('style','display:inline-block;');
						$newstr=$newstr+'2:CustomsCn_show,';
					}
					else if($addshow.indexOf("CustomsCn_show")<0 && $oldaddshow.indexOf("1:CustomsCn_show,")<0 && $oldaddshow.indexOf("3:CustomsCn_show,")<0)
						$('#isCustomsCn').attr('style','display:none;');
					
					$newstr=$newstr+"|";
			        for (key in $oldarr){
			        	if(key==0 && key<$oldarr.length-1)
			        		$newstr=$oldarr[key]+'|'+$newstr;
			        	if(key==2 && key<$oldarr.length-1)
			        		$newstr=$newstr+$oldarr[key]+'|';
			        }
					$('#oldAddShow').attr('value',$newstr);
										
					$('#over-lay').children().eq(1).show();
					$modal.close();
					});
			}
			);
	}
	else if(type=='jh'){
		var id=$('#jhTemplateId').val();
		$.modal({
			  url:'/configuration/carrierconfig/customlabellist',
			  method:'get',
			  data:{type:type,id:id}
			},'选择配货单',{footer:false,inside:false}).done(function($modal){
				//关闭页面
				$('.modal-close').click(function(){
					$('#over-lay').children().eq(1).show();
				});
				//确定
				$('.btn.btn-primary').click(function(){
					$('#model_jh').children('img').attr('src',$modal.find('#BigImg').children().attr('src'));
					$('#model_jh').children('img').attr('data',$modal.find('#BigImg').children().attr('data'));
					$('#model_jh').children('input').attr('value',$modal.find('#BigImg').children().attr('data'));
					$('#model_jh').children('div').html($modal.find('#BigImgSpan').children().html());
					
					//判断加打内容分别显示或隐藏
					$addshow=$modal.find('#BigImg').children('input').attr('value'); //新的加打内容
					$oldaddshow=$('#oldAddShow').attr('value');     //旧的加打内容
					$oldarr=$oldaddshow.split('|');  //分割数组
					$newstr='';

					if($addshow.indexOf("Order_show")>=0){
						$('#isAddOrder').attr('style','display:inline-block;');
						$newstr=$newstr+'3:Order_show,';
					}
					else if($addshow.indexOf("Order_show")<0 && $oldaddshow.indexOf("1:Order_show,")<0 && $oldaddshow.indexOf("2:Order_show,")<0)
						$('#isAddOrder').attr('style','display:none;');
					
					if($addshow.indexOf("Sku_show")>=0){
						$('#isAddSku').attr('style','display:inline-block;');
						$newstr=$newstr+'3:Sku_show,';
					}
					else if($addshow.indexOf("Sku_show")<0 && $oldaddshow.indexOf("1:Sku_show,")<0 && $oldaddshow.indexOf("2:Sku_show,")<0)
						$('#isAddSku').attr('style','display:none;');
					
					if($addshow.indexOf("CustomsCn_show")>=0){
						$('#isCustomsCn').attr('style','display:inline-block;');
						$newstr=$newstr+'3:CustomsCn_show,';
					}
					else if($addshow.indexOf("CustomsCn_show")<0 && $oldaddshow.indexOf("1:CustomsCn_show,")<0 && $oldaddshow.indexOf("2:CustomsCn_show,")<0)
						$('#isCustomsCn').attr('style','display:none;');
					
					$newstr=$newstr+"|";
					for(var i=$oldarr.length-2;i>=0;i--){
						$newstr=$oldarr[i]+'|'+$newstr;
					}
					$('#oldAddShow').attr('value',$newstr);
					
					$('#over-lay').children().eq(1).show();
					$modal.close();
					});
			}
			);
	}
}
//报关标签勾选
function enableBg(obj){
	if($('#openBg').attr("checked")){
		$('#selectBaoguan').hide();
		$('#openBg').attr("checked", false);
		$('#customsFormTemplateId').attr('value','');
	}
	else{
		$('#selectBaoguan').show();
		$('#openBg').attr("checked", true);
	}
}
//配货标签勾选
function enableJh(obj){
	if($('#openJh').attr("checked")){
		$('#selectJianhuo').hide();
		$('#openJh').attr("checked", false);
		$('#jhTemplateId').attr('value','');
	}
	else{
		$('#selectJianhuo').show();
		$('#openJh').attr("checked", true);
	}
}







