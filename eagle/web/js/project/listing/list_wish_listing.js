/**
 +------------------------------------------------------------------------------
 * 产品列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		list
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof listWishListing === 'undefined')  listWishListing = new Object();

listWishListing ={
		
	init:function(){
		$('select[name=select_status]').change(function(){
			$('form:first').submit();
		});
		
		$('input[name=chk_wish_fanben_all]').click(function(){
			$('input[name=chk_wish_fanben]').prop("checked", $(this).prop('checked'));
		});
		
		$('#btn_create').click(function(){
			listWishListing.createFanbenCapture();
		});
		
		$('#btn_batch_del').click(function(){
			listWishListing.doListSelectDelete();
		});
		
		$('#btn_batch_sync').click(function(){
			listWishListing.doBatchPostFanBen();
		});
		
		$('#btn_batch_cancel').click(function(){
			listWishListing.doBatchCancelFanBen();
		});
		
		$('#btn_save').click(function(){
			listWishListing.doSyncProdInfo();
		});
		
		$('#btn_binding').click(function(){
			listWishListing.createBindingBox();
		});
		
		
		$('#btn_sync_fanben').click(function(){
			listWishListing.syncFanben();
		});
	},
	
	syncFanben:function(){
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/listing/wish/add-sync-fanben-queue', 
			success: function (data) {
				if (data.message)
					bootbox.alert(data.message);
				return false;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},
	
	createBindingBox:function(){
		var selectFanben = new Array();
		$('input[name=chk_wish_fanben]:checked').each(function(){
			selectFanben.push(this.value);
		});
		
		//$.showLoading();
		$.get('/listing/wish/list-product-binding',{selectedlist : selectFanben},function(data){
			//$.hidLoading();
			listWishListing.fanbenCaptureBox('关联商品' , data , function (){
				//$.showLoading();
				var checkval = listWishListing.bindingSKU();
				
				if (checkval == false) return false;
			})
		});
	},
	
	bindingSKU:function(){
		var rt = true;
		var bindingList = new Array();
		$('input[name=relate_sku]').each(
			function(){
				if (this.value == ""  ){
					$(this).data('id')
					bootbox.alert(Translator.t('关联商品sku不能为空'));
					rt = false;
					return false 
				}else{
					tmprow = new Object();
					tmprow ['productid'] = $(this).data('productid');
					tmprow ['pkid'] =  $(this).data('id');
					tmprow ['syssku'] = this.value;
					bindingList.push(tmprow);
				}
		});
		
		if (rt){
			$.ajax({
				type: 'post',
				url: '/listing/wish/binding-sku',
				data: {bindingList: bindingList},
				cache: false,
				dataType:'json',
				beforeSend: function(XMLHttpRequest){
				},
				success: function(data, textStatus){
					//bootbox.alert(data.message);
						var tmpstr = '--------------------------------------------<br>';
							bootbox.alert('本次提交关联商品共'+data.total+'个<br>'+tmpstr
							+'其中有'+data.delay+'个暂时还没有同步到wish上<br>'+tmpstr
							+'同时创建商品和别名为product_id的商品'+data.sku_alias+'个<br>'+tmpstr
							+'为已存在的商品成功添加product_id为别名'+data.alias+'个<br>'+tmpstr
							+'商品的平台sku与系统sku冲突的商品有'+data.skuexist+'个<br>'+tmpstr
							+'product_id已使用，如必要请到商品管理修改的商品'+data.aliasexist+'个<br>'+tmpstr
							+'关联成功后， 请到商品管理页面补全商品的相关信息后系统才能正常运行!');
				},
				complete: function(XMLHttpRequest, textStatus){
				},
				error: function(XMLResponse){
				}
			});
		}
		
		return rt;
	},
	
	createFanbenCapture:function(){
		var url = '/listing/wish/fan-ben-capture';
		window.location.href = url;
	} , 
	
	editFanbenCapture:function(id){
		var url = '/listing/wish/fan-ben-capture?id='+id;
		window.location.href = url;
	},
	
	
	fanbenCaptureBox:function(title , message , callback){
		bootbox.dialog({
			className : "dialog_box",
			title: title,
			message: message,
			buttons:{
				Cancel: {  
					label: Translator.t("返回"),  
					className: "btn-default",  
					callback: function () {  
					}
				}, 
				OK: {  
					label: Translator.t("保存"),  
					className: "btn-primary",  
					callback: function () {
						isClose = true;
						isClose = callback();
						return isClose;	// callback return false 将不会关闭dialog                         
					}  
				}, 
			},
			
		});
	} , 
	
	/**
	 +----------------------------------------------------------
	 * 批量删除模板
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	 **/
	doListSelectDelete: function() {
		var ids = this.doGetSelect();
		if(ids.length > 0) {
			this.doDelete(ids.join(','),'delete-fan-ben',Translator.t('确定要删除选中的刊登范本?'));
		}else{
			bootbox.alert(Translator.t('请勾选至少一个的刊登范本！') );
		}
	},
	
	/**
	 +----------------------------------------------------------
	 * 删除订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param keys		订单id集合字符串（逗号分隔）
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	 **/
	doDelete: function(keys,action,msg) {
		bootbox.confirm( msg, function(r){
			if (r) {
				$.ajax({
					type: 'post',
					url: '/listing/wish/'+action,
					data: {keys: keys},
					cache: false,
					dataType:'json',
					beforeSend: function(XMLHttpRequest){
					},
					success: function(data, textStatus){
						bootbox.alert(data.message);
						if(data.code == 200) {
							//删除成功后重新读取列表
							window.location.reload();
						}
					},
					complete: function(XMLHttpRequest, textStatus){
					},
					error: function(XMLResponse){
					}
				});
			}
		});
	},
	
	/**
	 +----------------------------------------------------------
	 * 保存并同步商品信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	2014/12/19				初始化
	 +----------------------------------------------------------
	 **/
	doSyncProdInfo:function(){
		pendingPostProdInfo = new Array();
		tmpItem = new Object();
		variancelist = new Array();
		variancelist = new Array();
		$('tr').has('td input').each(function(){
			var isPush = false;
			var tmpRow = new Object;
			tmpItem['fanben_id'] = $(this).attr('data-id');
			tmpItem['parent_sku'] = $(this).attr('data-parent-sku');
			tmpItem['site_id'] = $(this).attr('data-site-id');
			
			$(this).find('input').each(function(){
				if ($(this).attr('type') == "checkbox"){
					if ($(this).prop('name') == 'variance_enable'){
						if ($(this).prop('checked')){
							if ($(this).attr('data-origrin') != 'Y'){
								tmpRow['enable'] = "Y";
								tmpRow['sku'] = $(this).attr('data-sku');
								isPush = true;
							}
						}else{
							if ($(this).attr('data-origrin') == 'Y'){
								tmpRow['enable'] = "N";
								tmpRow['sku'] = $(this).attr('data-sku');
								isPush = true;
							}
						}
					}					
					
				}else if ($(this).attr('type') == "text"){
					
					if ($(this).attr('data-origrin') != $(this).val()){
						tmpRow[$(this).attr('name')] = $(this).val();
						tmpRow['sku'] = $(this).attr('data-sku');
						isPush = true;
					}
				}
				
				
			});
			if (isPush){
				variancelist.push(tmpRow); 
				tmpItem['opt_method'] = 'part';
				tmpItem['variance'] = variancelist;
				pendingPostProdInfo.push(tmpItem);
				variancelist = new Array();
				tmpItem = new Object();
				isPush = false;
			}
		});
		
		
		if (pendingPostProdInfo.length > 0){
			
			$.ajax({
				type: 'post',
				url: '/listing/wish/batch-save-fan-ben',
				data: {itemlist:$.toJSON(pendingPostProdInfo)},
				cache: false,
				dataType:'json',
				beforeSend: function(XMLHttpRequest){
				},
				success: function(data, textStatus){
					if(data.success) {
						window.location.reload();
					}
					
				},
				complete: function(XMLHttpRequest, textStatus){
				},
				error: function(XMLResponse){
				}
			});
		}
		
		return pendingPostProdInfo;
		
	},
	
	/**
	 +----------------------------------------------------------
	 * 批量刊登
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param checkSelf		是否检测当前步骤
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 * @author		million	2014/08/20				修改
	 +----------------------------------------------------------
	 **/
	doBatchPostFanBen: function(checkSelf) {
		var ids = this.doGetSelect();
		if(ids.length > 0) {
			this.doPostFanBen(ids.join(','),'post-fan-ben',Translator.t('确定要立即刊登选中的刊登范本?'));
		}else{
			bootbox.alert(Translator.t('请勾选至少一个的刊登范本！'));
		}
	},
	
	/**
	 +----------------------------------------------------------
	 * 批量刊登选中的范本
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs		2014/08/12				初始化
	 +----------------------------------------------------------
	 **/
	doPostFanBen:function(keys,action,msg) {
		var my = this;
		bootbox.confirm( msg, function(r){
			if (r) {
				$.ajax({
					type: 'post',
					url: '/listing/wish/'+action,
					data: {keys: keys},
					cache: false,
					dataType:'json',
					beforeSend: function(XMLHttpRequest){
					},
					success: function(data, textStatus){
						if(data.code == 200) {
							//删除成功后重新读取列表
							window.location.reload();
						}
						bootbox.alert( data.message);
					},
					complete: function(XMLHttpRequest, textStatus){
					},
					error: function(XMLResponse){
					}
				});
			}
		});
	},
	
	/**
	 +----------------------------------------------------------
	 * 批量取消刊登
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param checkSelf		是否检测当前步骤
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/08/01				初始化
	 * @author		million	2014/08/20				修改
	 +----------------------------------------------------------
	 **/
	doBatchCancelFanBen: function(checkSelf) {
		var ids = this.doGetSelect();
		if(ids.length > 0) {
			this.doCancelFanBen(ids.join(','),'cancel-fan-ben',Translator.t('确定要立取消即刊登选中的刊登范本?'));
		}else{
			bootbox.alert(Translator.t('请勾选至少一个的刊登范本！'));
		}
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
	 * @author		fanjs	2014/08/01				初始化
	 +----------------------------------------------------------
	 **/
	doGetSelect: function() {
		var keys = [];
		//将选中行的irder_id组合成数组
		$('input[name=chk_wish_fanben]:checked').each(function(){
			keys.push(this.value);
		});
		return keys;
	},
	
	/**
	 +----------------------------------------------------------
	 * 批量取消刊登选中的范本
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs		2014/08/12				初始化
	 +----------------------------------------------------------
	 **/
	doCancelFanBen:function(keys,action,msg) {
		var my = this;
		bootbox.confirm(Translator.t(msg),  function(r){
			if (r) {
				$.ajax({
					type: 'post',
					url: '/listing/wish/'+action,
					data: {keys: keys},
					cache: false,
					dataType:'json',
					beforeSend: function(XMLHttpRequest){
					},
					success: function(data, textStatus){
						if(data.code == 200) {
							//删除成功后重新读取列表
							window.location.reload();
						}
						bootbox.alert(Translator.t(data.message));
						
					},
					complete: function(XMLHttpRequest, textStatus){
					},
					error: function(XMLResponse){
					}
				});
			}
		});
	},
	
}

$(function() {
	listWishListing.init();
});