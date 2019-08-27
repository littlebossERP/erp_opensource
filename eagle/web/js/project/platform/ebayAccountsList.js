
/**
 +------------------------------------------------------------------------------
 * 物流商模块list视图js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined')  platform = new Object();
platform.ebayAccountsList = {
	listSyncSet:function(val,usr,sel){
		$.ajax({
		type: "post",
		url: global.baseUrl+"platform/ebay-accounts/listing-set-sync",
		data: {setval:val,setusr:usr,setitem:sel},
		cache: false,
		dataType:'json',
		beforeSend: function(XMLHttpRequest){
			//ShowLoading();
		},
		success: function(data, textStatus){
			bootbox.alert({ title:Translator.t('提示') , message:data.message , callback:function(){
				window.location.reload();
				$.showLoading();
			}});
		},
		complete: function(XMLHttpRequest, textStatus){
			//HideLoading();
		},
		error: function(XMLResponse){
			//请求出错处理
		}
	});
	},
    /**
	 +----------------------------------------------------------
	 * 删除一个或多个Ebay帐号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param code		Ebay帐号ID
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/02				初始化
	 +----------------------------------------------------------
	 **/
    'menuDelete': function(code) {
        keys = this.doGetSelect();
        if(typeof code === 'number'){
            keys = new Object;
            keys[0] = code;
        }
        
        bootbox.confirm({  
	        title : 'Confirm',
			message : '您确定要删除Ebay帐号?',  
	        callback : function(r) {  
	        	if (r) {
					$.ajax({
						type: "post",
						url: global.baseUrl+"platform/ebay-accounts/delete",
						data: {keys: keys},
						cache: false,
						dataType:'json',
						beforeSend: function(XMLHttpRequest){
							//ShowLoading();
						},
						success: function(data, textStatus){
							$.showLoading();
							window.location.reload();
						},
						complete: function(XMLHttpRequest, textStatus){
							//HideLoading();
						},
						error: function(XMLResponse){
							//请求出错处理
						}
					});
				}
	        },  
        });
	},
    /**
	 +----------------------------------------------------------
	 * 绑定EBAY帐号视图数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/02				初始化
	 +----------------------------------------------------------
	 **/
    'menuAdd': function(storeName){
        $.ajax({
			type: "post",
			url: global.baseUrl+"platform/ebay-accounts/add?storename="+storeName,
			cache:false,
			async:false,//为了让后面的物流跟踪助手设置弹窗浮在最上面，这里设置为同步
			success: function(data, textStatus){
				bootbox.dialog({
					title : Translator.t("绑定ebay帐号"),
				    message: data,
				});		
				
			}
		});
		//物流跟踪助手设置弹窗
		$.ajax({
			type: "post",
			dataType:"json",
			url: global.baseUrl+"tracking/tracking/check-platform-is-set-get-days-ago-order-track-no?platform=ebay",
			cache:false,
			success: function(data){
				if(!data.show || true)
					return false;
				bootbox.dialog({
					title : Translator.t("设置物流跟踪助手新绑定账号同步天数"),
				    message: data.html,
					buttons:{
						Ok: {
							label: Translator.t("设置"),
							className: "btn-success btn",
							callback: function () {
								$.showLoading();
								$.ajax({
									type: "POST",
									url:'/tracking/tracking/set-get-days-ago-order-track-no',
									data: {days:$("#getHowManyDaysAgo").val(),platform:'ebay'},
									success: function (result) {
										$.hideLoading();
										return true;
									},
									error : function(){
										$.hideLoading();
										return false;
									}
								});
							}
						}
					}
				});
			}
		});
    },
	
	/**
	 +----------------------------------------------------------
	 * ebay 刊登专用 绑定EBAY帐号视图数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/02				初始化
	 +----------------------------------------------------------
	 **/
    menuListingAdd: function(accountname){
        $.ajax({
			type: "post",
			url: global.baseUrl+"platform/ebay-accounts/listing-add?accountname="+accountname,
			cache:false,
			success: function(data, textStatus){
				bootbox.dialog({
					title : Translator.t("刊登绑定ebay帐号"),
				    message: data,
				});		
				
			}
		});
    },
    /**
     +----------------------------------------------------------
     * 绑定EBAY帐号视图数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param
        +----------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2015/03/02				初始化
     +----------------------------------------------------------
     **/
    'bindseller2': function(){
        $('#platform-ebayAccounts-add-load').attr('src', global.baseUrl+'images/ebay/loading.gif');
        $.ajax({
            type: "post",
            url: global.baseUrl+"platform/ebay-accounts/bindseller2",
            cache:false,
            dataType: "json",
            success: function(data){
            	$('#platform-ebayAccounts-add-load').removeAttr('src');
            	if(data.status == true){
            		bootbox.alert({ title:Translator.t('提示') , message:data.msg , callback:function(){
						window.location.reload();
						$.showLoading();
					}});
            	}else{
            		$('#platform-ebayAccounts-list-message').html(data.msg );
            	}
            }
        });
    },
	
	/**
     +----------------------------------------------------------
     * 绑定EBAY帐号视图数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param
        +----------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2015/03/02				初始化
     +----------------------------------------------------------
     **/
    listingbindseller2: function(accountname){
        $('#platform-ebayAccounts-add-load').attr('src', global.baseUrl+'images/ebay/loading.gif');
        $.ajax({
            type: "post",
            url: global.baseUrl+"platform/ebay-accounts/listingbindseller2?accountname="+accountname,
            cache:false,
            dataType: "json",
            success: function(data){
            	$('#platform-ebayAccounts-add-load').removeAttr('src');
            	if(data.status == true){
            		bootbox.alert({ title:Translator.t('提示') , message:data.msg , callback:function(){
						window.location.reload();
						$.showLoading();
					}});
            	}else{
            		$('#platform-ebayAccounts-list-message').html(data.msg );
            	}
            }
        });
    },

    /**
	 +----------------------------------------------------------
	 * 获取列表中checkbox所有选中的列
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @return keys		订单id集合数组
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/02				初始化
	 +----------------------------------------------------------
	 **/
	doGetSelect: function() {
		var keys = [];
		//获取选中行的数据
		var checkboxs = $('input[type="checkbox"]');
        var checkedBoxs = [];
        for( var i = 0 ; i < checkboxs.length ; i++ ){
        	if( checkboxs[i].id != 'check-all-record' && checkboxs[i].checked != false){
        		checkedBoxs.push(checkboxs[i]);
        	}
        }
		//将选中行的ebay_uid组合成数组
		for(var i = 0; i < checkedBoxs.length; i++){
			keys.push($(checkedBoxs[i]).attr('data-euid'));
		}
		return keys;
	},
	
    /**
     +----------------------------------------------------------
     * 判断批量删除按钮是否可用
     +----------------------------------------------------------
     * @access	public
     +----------------------------------------------------------
     * @return
        +----------------------------------------------------------
     * log		name	date					note
     * @author	dzt		2015/03/02				初始化
     +----------------------------------------------------------
     **/
    checkBtnDisable : function () {
        var checkboxs = $('input[type="checkbox"]');
        var checkedBoxs = [];
        for( var i = 0 ; i < checkboxs.length ; i++ ){
        	if( checkboxs[i].id != 'check-all-record' && checkboxs[i].checked != false){
        		checkedBoxs.push(checkboxs[i]);
        	}
        }
        
        if (checkedBoxs.length > 0) {
            $('#platform-ebayAccounts-delete').removeAttr('disabled');
        } else {
            $('#platform-ebayAccounts-delete').attr('disabled','disabled');
        }
    },
	/**
	  +----------------------------------------------------------
	 * 是否启用功能按钮
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param value     该字段返回值
	 * @param code      物流商代码
	 * @param not_in_user    是否系统表中数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/25				初始化
	 +----------------------------------------------------------
	 **/
	checkable: function(value, code, select){
		var str = '<input type="hidden" name="ebay_uid" value="'+code+'"/>';
		str += '<input type="hidden" name="is_active" value="'+value+'"/>';
		//str += '<input type="hidden" name="ebay_select" value="'+select+'"/>';
		return value == '1' ? str+'<center class="platform-ebayAccounts-list-isactive"><a href="#"><img src="'+global.baseUrl+'js/lib/easyui/themes/icons/ok.png" /></a></center>' : str+'<center class="platform-ebayAccounts-list-isactive"><a href="#"><img src="'+global.baseUrl+'js/lib/easyui/themes/icons/cancel.png" /></a></center>';
	},
	/**
	 * 显示同步item设置的状态
	 * @author fanjs
	 */
	showset:function(value,code,select){
		return value == '1'?'<a href="#" onclick="set(0,'+code+',\''+select+'\');" title="点击关闭">开启</a>':'<a href="#"  onclick="set(1,'+code+',\''+select+'\');" title="点击开启">关闭</a>';
	},
	
	
	initWidget : function(){
		
		//按钮状态切换函数
//		$('.platform-ebayAccounts-list-isactive').click(function(){
//			var ebay_uid = $(this).siblings('input[name=ebay_uid]').val();
//			var is_active = $(this).siblings('input[name=is_active]').val();
//			//var ebay_select = $(this).siblings('input[name=ebay_select]').val();
//			is_active = is_active == 1 ? 0 : 1;
//			//生成上传数据
//			var uploadData = new Object();
//			uploadData['ebay_uid'] = ebay_uid;
//			uploadData['is_active'] = is_active;
//			//uploadData['ebay_select'] = ebay_select;
//			$.ajax({
//				type: "post",
//				url: global.baseUrl+"platform/ebay-accounts/save",
//				data: uploadData,
//				cache: false,
//				success: function(data, textStatus){
//					$('#platform-ebayAccounts-list-datagrid').datagrid('reload');
//				}
//			});
//		});
//		
		// 
		$(document).on('click','input[type="checkbox"]',function(){
			// check or uncheck all checkboxs
			if(this.id == 'check-all-record'){
				if( this.checked == true ){
					var checkboxs = $('input[type="checkbox"]');
			        for( var i = 0 ; i < checkboxs.length ; i++ ){
		        		checkboxs[i].checked = true;
			        }
				}else{
					var checkboxs = $('input[type="checkbox"]');
			        for( var i = 0 ; i < checkboxs.length ; i++ ){
			        	checkboxs[i].checked = false;
			        }
				}
			}
			
			// 
			platform.ebayAccountsList.checkBtnDisable();
		});
		
		$(document).ajaxStart(function () {
			$.showLoading();
		}).ajaxStop(function () {
			$.hideLoading();
		});
	},
	
	accountAlias : function(ebay_uid){
		var handle= $.openModal(global.baseUrl+"platform/ebay-accounts/setaliasbox",{ebay_uid:ebay_uid},'设置别名','post');  // 打开窗口命令
		handle.done(function($window){
	     // 窗口载入完毕事件
		 
		 $window.find("#btn_ok").on('click',function(){
			 btnObj = $(this);
			 btnObj.prop('disabled','disabled');
			  $.ajax({
					type: "POST",
					dataType: 'json',
					url:'/platform/ebay-accounts/save-alias', 
					data: $('#platform-ebay-setalias-form').serialize(),
					success: function (result) {
						if (result.success ){
							$.alert(Translator.t('操作成功'));
							$window.close(); 
							window.location.reload();
						}else{
							$.alert(result.message);
							btnObj.prop('disabled','');
						}
					}
					 
				 });
			 
	     })
	     $window.find("#btn_cancel").on('click',function(){
	            $window.close();       // 关闭当前模态框
	     })
		});
		
		
	},
	
}


function set(val,usr,sel){
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/ebay-accounts/set-sync",
		data: {setval:val,setusr:usr,setitem:sel},
		cache: false,
		dataType:'json',
		beforeSend: function(XMLHttpRequest){
			//ShowLoading();
		},
		success: function(data, textStatus){
			bootbox.alert({ title:Translator.t('提示') , message:data.message , callback:function(){
				window.location.reload();
				$.showLoading();
			}});
		},
		complete: function(XMLHttpRequest, textStatus){
			//HideLoading();
		},
		error: function(XMLResponse){
			//请求出错处理
		}
	});
}

