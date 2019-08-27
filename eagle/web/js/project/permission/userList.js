/**
  +------------------------------------------------------------------------------
 * 客户列表模块list视图js
 +------------------------------------------------------------------------------
 * @list	js/project
 * @package		permission
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof permission === 'undefined')  permission = new Object();
permission.userList = {
	/**
	  +----------------------------------------------------------
	 * 增加子账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/25				初始化
	 +----------------------------------------------------------
	 **/
	'userAdd': function(){
		$.ajax({
			type:'post',
			url:global.baseUrl+'permission/user/add',
			success: function(data, textStatus){
				bootbox.dialog({
					title : Translator.t("添加子账号"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-user-add-account-return",  
	                    }, 
	                    OK: {  
	                        label: Translator.t("保存"),  
	                        className: "btn-primary btn-user-add-account-save",  
	                        callback: function () {
	                        	return false;
	                        }  
	                    }  
					},
				    message: data,
				});		
			},
			cache:false,
			complete: function(XMLHttpRequest, textStatus){
				//$.parser.parse($('#index-commonwindow').parent());
			}
		});
	},
  
	// 
    'userEdit': function(user_id){
        $.ajax({
            type:'post',
            url:global.baseUrl+'permission/user/edit',
            data:{'user_id':user_id},
            success: function(data, textStatus){
            	bootbox.dialog({
					title : Translator.t("编辑账号"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-user-account-return",  
	                    }, 
	                    OK: {  
	                        label: Translator.t("保存"),  
	                        className: "btn-primary btn-user-account-save",  
	                        callback: function () {
	                        	return false;
	                        }  
	                    }  
					},
				    message: data,
				});		
            },
            cache:false,
            complete: function(XMLHttpRequest, textStatus){
                //$.parser.parse($('#index-commonwindow').parent());
            }
        });
    },
 
	/**
	  +----------------------------------------------------------
	 * 获取列表中checkbox所有选中的列
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @return keys		物流方式id集合数组
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/11				初始化
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
			keys.push($(checkedBoxs[i]).attr('data-uid'));
		}
		return keys;
	},

	getLocalTime: function(nS) {
		return new Date(parseInt(nS) * 1000).toLocaleString().replace(/年|月/g, "-").replace(/日/g, " ");      
	},

	/**
    +----------------------------------------------------------
    * 判断批量删除按钮是否可用
    +----------------------------------------------------------
    * @access	public
    +----------------------------------------------------------
    * @return
       +----------------------------------------------------------
    * log		name	date			note
    * @author	dzt		2015/03/11		初始化
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

    
    init : function(){
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
			permission.userList.checkBtnDisable();
		});
		
		$(document).ajaxStart(function () {
			$.showLoading();
		}).ajaxStop(function () {
			$.hideLoading();
		});	   

    },

    setSync : function(is_active , user_id) {
    	$.ajax({
    		type: "post",
    		url: global.baseUrl+"permission/user/save",
    		data: {'user_id':user_id, 'is_active':is_active},
    		cache: false,
    		dataType:'json',
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
}


