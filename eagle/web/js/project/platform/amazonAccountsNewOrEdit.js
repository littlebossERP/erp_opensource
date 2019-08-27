/**
 +------------------------------------------------------------------------------
 * 查看/修改指定的采购单的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */


if (typeof platform === 'undefined')   platform= new Object();

platform.amazonAccountsNeworedit={
    //mode can be new,view,edit
   'setting':{'mode':"","amazonUser":"","chosenCountryList":"","currentDialog":"","loadingBtn":"","mplist":''},	    
   'initWidget': function() {
   	    var thisObject = platform.amazonAccountsNeworedit;
   	    
		$("#marketplaceid-country-tip-a").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/amazon-accounts/m-placeid-countrymap',
			   function (data){
					bootbox.dialog({
						title : Translator.t("MarketplaceId跟国家名称的对应关系"),
					    buttons: {
					        ok: {
					            label: Translator.t("关闭"),
					            className: "btn-amazon-m-placeid-countrymap-close",  
					            callback: function () {  
					            	setTimeout(
				            			function(){
				            				if($('.bootbox-body').length > 0){// 防止二层模态框关闭时影响一层模态框的展示 
								         		$('body').addClass('modal-open');
								         	} 
										}, 
							         500);
		                        }  
					        },
					    },
					    message: data,
					});		
			});
		});
		
	    // var chosenList=thisObject.setting.chosenCountryList;
	    // //显示已经选择好的国家
	    // for(i=0;i<chosenList.length;i++){
	    // 	var countryLabel=chosenList[i];
	    // 	$("input[name='"+countryLabel+"']").attr("checked",true);
	    // }

        //显示已经选择好的国家
        var chosenList=thisObject.setting.chosenCountryList;
        var mpList = thisObject.setting.mplist;
        thisObject.showChonsedMP(mpList,chosenList);
	    
	    thisObject.initBtn();		        	
	    	
	},
	
	'initBtn':function(){
		
        $(".btn-amazon-account-create").click(function(e,arg){//新增amazon账号
        	platform.amazonAccountsNeworedit.setting.loadingBtn = $(this).attr('disabled',true);
        	if(!platform.amazonAccountsNeworedit.setting.currentDialog)
        		platform.amazonAccountsNeworedit.setting.currentDialog = $(this).parent().parent().parent().parent();
        	// 由于没有在bootbox 定义callback的dialog里面的 .modal-footer button 点击之后 bootbox都会自动关闭dialog 
        	// 所以想不通过callback 触发button click事件 就需要下面语句阻止 该button的事件传递
        	e.stopPropagation(); // 防止bootbox 捉到hidden.bs.modal后先把 modal 的dom删掉，导致post找不到form内容 , 
        	
        	if ($("#marketplace-options-div input:checked").length==0){
        		bootbox.alert({title:Translator.t('错误提示'),message:Translator.t('至少需要选择1个国家')});
        		$(this).removeAttr('disabled');
        		return;
        	}
        	
			$.post(					   
				   global.baseUrl+'platform/amazon-accounts/create',$("#platform-amazonAccounts-form").serialize(),
				   function (data){
					   
					   var retinfo = eval('(' + data + ')');
					   if (retinfo["code"]=="fail")  {
						   bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
						   $(platform.amazonAccountsNeworedit.setting.loadingBtn).removeAttr('disabled');
							return false;
					   }else{
						   bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功创建'),callback:function(){
							   window.location.reload();
							   $.showLoading();
					   		}
						   });		
					   }
			    });
	    });	
        
        $(".btn-amazon-account-save").click(function(e,arg){ // 编辑指定amazon的账号信息
        	if(!platform.amazonAccountsNeworedit.setting.currentDialog)	{
        		var bootboxDialogs = $(this).parents('.bootbox.modal.fade.in');
        		platform.amazonAccountsNeworedit.setting.currentDialog = bootboxDialogs[0];
        	}
        	e.stopPropagation(); // 防止bootbox 捉到hidden.bs.modal后先把 modal 的dom删掉，导致post找不到form内容
        	
        	if ($("#marketplace-options-div input:checked").length==0){
        		bootbox.alert({title:Translator.t('错误提示'),message:Translator.t('至少需要选择1个国家')});
        		return;
        	}
        	
        	//提示取消的国家选项
        	var uncheckList="";
        	var chosenCountryList=platform.amazonAccountsNeworedit.setting.chosenCountryList;
        	for(i=0;i<chosenCountryList.length;i++){
        		// if (document.getElementById(chosenCountryList[i]).checked == false){
                if ($("[name = '"+chosenCountryList[i]+"']").prop("checked") == false){
        			countryName=$("[name = '"+chosenCountryList[i]+"']").parent().text();
        			uncheckList=uncheckList+countryName+" ";
        		}
        	}
        	
        	if (uncheckList.length>0){
        		 bootbox.confirm({  
    		        title : Translator.t('店铺关闭确认'),
        			message : '该amazon账号下---"'+uncheckList+'"店铺将会被关闭，相应的订单也不再同步。是否确定?',  
    		        callback : function(r) {  
						if (r) {
							platform.amazonAccountsNeworedit.sumbitUpdateAmazonInfo();
						}
    		        },  
		        });
        	}else   
        		platform.amazonAccountsNeworedit.sumbitUpdateAmazonInfo();
        });
        
        $(".btn-amazon-account-add-marketplace").click(function(e,arg){//新增 marketplace
        	platform.amazonAccountsNeworedit.setting.loadingBtn = $(this).attr('disabled',true);
        	if(!platform.amazonAccountsNeworedit.setting.currentDialog)
        		platform.amazonAccountsNeworedit.setting.currentDialog = $(this).parent().parent().parent().parent();
    		e.stopPropagation(); // 防止bootbox 捉到hidden.bs.modal后先把 modal 的dom删掉，导致post找不到form内容
        	
        	if ($("#marketplace-options-div input:checked").length==0){
        		bootbox.alert({title:Translator.t('错误提示'),message:Translator.t('请选择一个Marketplace')});
        		$(this).removeAttr('disabled');
        		return;
        	}
        	
        	$.showLoading();
			$.post(					   
				   global.baseUrl+'platform/amazon-accounts/update',$("#platform-amazonAccounts-form").serialize(),
				   function (data){
					   $.hideLoading();
					   var retinfo = eval('(' + data + ')');
					   if (retinfo["code"]=="fail")  {
						   bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
						   $(platform.amazonAccountsNeworedit.setting.loadingBtn).removeAttr('disabled');
							return false;
					   }else{
						   bootbox.alert({title:Translator.t('提示'),message:Translator.t('添加成功'),callback:function(){
							   window.location.reload();
							   $.showLoading();
					   		}
						   });		
					   }
			    });
	    });	
        $(".js-sel-mp").change(function(e,arg){
            var objthis = platform.amazonAccountsNeworedit;
            // onchange="platform.amazonAccountsNeworedit.showMPCheckbox(this,<?=$mk_json?>)"
            objthis.showMPCheckbox(objthis.setting.mplist);
        });
	},
	
	
	'sumbitUpdateAmazonInfo':function(){
		$.showLoading();
		$.post(
			global.baseUrl+'platform/amazon-accounts/update',$("#platform-amazonAccounts-form").serialize(),
			function (data){
				$.hideLoading();
				var retinfo = eval('(' + data + ')');
				if (retinfo["code"]=="fail")  {
					bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
					return false;
				}else{
					// 重新触发hide关闭窗口
		        	$(platform.amazonAccountsNeworedit.setting.currentDialog).modal('hide');
					bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功更新'),callback:function(){
							window.location.reload();
							$.showLoading();
						}
					});
				}
			}
		);
	},
    /**
     * [showMPCheckbox：动态显示分区站点]
     * @author willage 2017-08-24T16:25:23+0800
     * @update willage  2017-08-24T16:25:23+0800
     */
    'showMPCheckbox':function(data){
        var type = $(".js-sel-mp").val();
        console.log(type);
        console.log(data[type]);
        var str="";
        for(var k in data[type]) {
            //遍历对象，k即为key，obj[k]为当前k对应的值
            console.log(data[type][k]);
            if (k===type) {
                str+='<label><input type="checkbox" '+'name="marketplace_'+k+'"'+' checked disabled>'+data[type][k]+'</label>'
                str+='<label><input type="hidden" '+'name="marketplace_'+k+'"'+' value="on" ></label>'
            }else{
                str+='<label><input type="checkbox" '+'name="marketplace_'+k+'"'+'>'+data[type][k]+'</label>'
            }


        }
        $(".js-checkbox-mp").html(str);
    },
    /**
     * [showChonsedMP:显示已经选择分区站点]
     * @author willage 2017-08-24T16:26:14+0800
     * @update willage  2017-08-24T16:26:14+0800
     */
    'showChonsedMP':function(data,chonsed){
        if (!chonsed.length) {return;}
        var type = chonsed[0].slice(12);
        console.log(data);
        var tmp=false;
        var str="";
        for(var k in data[type]) {
            //遍历对象，k即为key，obj[k]为当前k对应的值
            tmp=false;
            console.log(data[type][k]);
            for (var i = 0; i < chonsed.length; i++) {
                if (k===chonsed[i].slice(12)) {
                    tmp=true;
                    break;
                }
            }
            if (tmp===true) {
                str+='<label><input type="checkbox" '+'name="marketplace_'+k+'"'+' checked>'+data[type][k]+'</label>'
            }else{
                str+='<label><input type="checkbox" '+'name="marketplace_'+k+'"'+'>'+data[type][k]+'</label>'
            }
        }
        $(".js-checkbox-mp").html(str);
    },

}




