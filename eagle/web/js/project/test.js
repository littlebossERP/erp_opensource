
if (typeof uploadImg === 'undefined')  uploadImg = new Object();
uploadImg.index = {
	'browseNodesJsonStr':false,
	'browseNodes':false,
	'initWidget': function() {	
		uploadImg.index.browseNodes = eval('('+uploadImg.index.browseNodesJsonStr+')');
		var addBaseBNHtml = '<td class="bncol"><div class="bncolContainer"><table></tabel></div></td>';
		
		$('#browse-node-table tr').append(addBaseBNHtml).find('td:last').attr('id','root-browse-node');
		$('#root-browse-node').find('.bncolContainer > table').html('<tr><td data-node-id="'+uploadImg.index.browseNodes.node_id+'"><a class="node" style="cursor: pointer;">'+uploadImg.index.browseNodes.name+'-></a></td></td>');
		$('#root-browse-node').attr('data-pnode-id','root');
		$('[data-node-id='+ uploadImg.index.browseNodes.node_id +']').data('children',uploadImg.index.browseNodes.children);
		$('.bncolContainer:last').addClass('cur-child-node');
		$('.moveCol > button').attr('disabled',true);
		
		$(document).on('click','[data-node-id]',function(){
			var nowParentNodeId = $(this).attr('data-node-id');
			var childNodes = $(this).parents('.bncol').next();
			
			$(this).parents('.bncol').find('td a').removeClass('browse-node-selected');
			$(this).find('a').addClass('browse-node-selected');
			
			if($(childNodes).length != 0){
				var oldParentNodeId = $(childNodes).attr('data-pnode-id');
				if(oldParentNodeId == nowParentNodeId){
					return true;
				}
			}
			
			// 删掉所有child html重新render
			$(this).parents('.bncol').parent().children('td:gt('+$(this).parents('.bncol').index()+')').remove();
			
			$(this).parents('.bncol').after(addBaseBNHtml);
			childNodes = $(this).parents('.bncol').next().attr('data-pnode-id',nowParentNodeId);
			
			var children = $(this).data('children');
			if(!children){// 叶节点 ，选择该browse node
				$(childNodes).find('.bncolContainer > table').append('<tr><td>选择->('+ nowParentNodeId +')</td></td>');
			}else{
				for( var i = 0 ; i < children.length ; i++ ){
					$(childNodes).find('.bncolContainer > table').append('<tr><td data-node-id="'+children[i].node_id+'"><a class="node" style="cursor: pointer;">'+children[i].name+'-></a></td></td>');
					$('[data-node-id="'+children[i].node_id+'"]').data('children',children[i].children);
				}
			}
			
			$('.bncolContainer').removeClass('cur-child-node');
			$('.bncolContainer:last').addClass('cur-child-node');
			
			var showCol = 3;
			var allCol = $(this).parents('#browse-node-table').find('.bncol').length;
			
			if(allCol >= 3){
				var offset = allCol - showCol;
				$('#browseNodeWidgetContainer').data('showColOffset' , offset);
				$('#browseNodeWidgetContainer').data('showCol' , showCol);
				moveColumn()
			}
			
			if(allCol > 3){
				$('.go-left > button').removeAttr('disabled');
			}
		});
		
		$('.moveCol').click(function(){
			if($(this).children('button').attr('disabled')){
				return ;
			}
			var offset = $('#browseNodeWidgetContainer').data('showColOffset');
			var maxOffset = $('#browse-node-table .bncol').length;
			var showCol = $('#browseNodeWidgetContainer').data('showCol');
			if($(this).hasClass('go-left')){
				if(offset > 0){
					$('#browseNodeWidgetContainer').data('showColOffset' , offset-1);
					moveColumn()
				}
				
			}
			
			if($(this).hasClass('go-right')){
				if(offset + showCol < maxOffset){
					$('#browseNodeWidgetContainer').data('showColOffset' , offset+1);
					moveColumn()
				}
			}
		});
		
		function moveColumn(){
			var offset = $('#browseNodeWidgetContainer').data('showColOffset');
			$('#browse-node-table').animate({left: -252 * offset + 'px' });
			
			var allCol = $('#browse-node-table').find('.bncol').length;
			if(offset == 0){
				$('.go-left > button').attr('disabled',true);
			}else{
				$('.go-left > button').removeAttr('disabled');
			}
			
			if(allCol - offset <= 3){
				$('.go-right > button').attr('disabled',true);
			}else{
				$('.go-right > button').removeAttr('disabled');
			}
		}
		
		$('div[role="image-uploader-container"]').batchImagesUploader({
			localImageUploadOn : true,   //支持本地上传。默认开
		    fromOtherImageLibOn: true , // 支持通过url添加图片。默认开
			imagesMaxNum : 5,
			fileMaxSize : 500 , //K为单位。  最大只能设置为500K
			fileFilter : ["jpg","jpeg","gif","pjpeg","png"],
			maxHeight : 100, //图片展示最大宽 ，高度， 最大为100px * 100px。 这里为展示缩略图设置。
			maxWidth : 100,
			initImages : existingImages, //[{thumbnail:"",original:""},{}],  //该组件在展示前本身自带的部分图片 : [],
			fileName: 'product_photo_file',// file控件的name属性值，所有批量上传的file控件name必须一致
		    onUploadFinish : function(imagesData , errorInfo){// 服务器信息返回链接，生成图片元素后触发
//		    	debugger
		    },
		    
		    onDelete : function(data){// 删除图片元素后触发
//				debugger
			}
			
		});
		
		$('#btn-uploadOne').click(function(){
			$.uploadOne({
			     fileElementId:'uploadOne', // input 元素 id
			     
			     // 当获取到服务器数据时，触发success回调函数
			     onUploadSuccess: function (data){
			    	 bootbox.alert({
							title : status,
						    buttons: {
						        ok: {
						            label: Translator.t("确定"),
						        },
						    },
						    message: data.data.thumbnail + '<br>' + data.data.original,
						});		 
			     },
			     
			     // 从服务器获取数据失败时，触发error回调函数。  
			     onError: function(xhr, status, e){
					 bootbox.alert({
							title : status,
						    buttons: {
						        ok: {
						            label: Translator.t("确定"),
					        },
					    },
					    message: e,
					});		
			     }
			});
		});
	},
};






/**
+------------------------------------------------------------------------------
*采购单列表的界面js
+------------------------------------------------------------------------------
* @category	js/project
* @package		purchase
* @subpackage  Exception
* @author		lolo <jiongqiang.xiao@witsion.com>
* @version		1.0
+------------------------------------------------------------------------------
*/

if (typeof purchaseOrder === 'undefined')  purchaseOrder = new Object();
purchaseOrder.list={
		'init': function() {
			$('#btn-create').unbind('click').click(function(){
//				$('#myModal').modal({remote: global.baseUrl+'purchase/purchase/create'});
				
				// bootbox.dialog.buttons.callback return false;例子
				$.showLoading();
				$.get( global.baseUrl+'purchase/purchase/create',
				   function (data){
						$.hideLoading();
						bootbox.dialog({
							className: "myClass", 
							title : Translator.t("新增采购单"),
							buttons: {  
								Cancel: {  
			                        label: Translator.t("返回"),  
			                        className: "btn-default",  
			                        callback: function () {  
//			                        	debugger
			                        }
			                    }, 
			                    OK: {  
			                        label: Translator.t("保存"),  
			                        className: "btn-primary",  
			                        callback: function () {
			                        	return false;// callback return false 将不会关闭dialog 
			                        }  
			                    }, 
							},
						    message: data,
						});	
				});
			});
			
			$('#btn-list2').unbind('click').click(function(){
				$.showLoading();
				$.get( global.baseUrl+'test/demo/ajax-refresh-demo',
				   function (data){
						$.hideLoading();
						bootbox.dialog({
							className: "myClass", 
							title : Translator.t("新增采购单"),
							buttons: {  
								Cancel: {  
			                        label: Translator.t("返回"),  
			                        className: "btn-default",  
			                        callback: function () {  
			                        }
			                    }, 
			                   
							},
						    message: data,
						});	
				});
			});
			

			$.initQtip();
		},
		
		'editPurchaseOrder' : function (id) {
			// bootbox.dialog 不定义buttons ;例子
			$.showLoading();
			$.get( global.baseUrl+'test/demo/edit-view?id='+id,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						closeButton: false,
						className: "myClass", 
					    message: data,
					});	
			});
		},
		
		'viewPurchaseOrder' : function (id) {
			
			// bootbox.dialog 不定义buttons ;例子
			$.showLoading();
			$.get( global.baseUrl+'test/demo/view?id='+id,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						closeButton: false,
						className: "myClass", 
					    message: data,
					});	
			});
//			$('#checkOrder').modal({remote: global.baseUrl+'purchase/purchase/view?id='+id});
		}
		
};




/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lolo <jiongqiang.xiao@witsion.com>
+----------------------------------------------------------------------
| Create Date: 2014-02-27
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *查看/修改指定的采购单的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		lolo <jiongqiang.xiao@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */


//var purchase = new Object();
if (typeof purchaseOrder === 'undefined')  purchaseOrder = new Object();
purchaseOrder.updateorview={
		'setting':{	'purchaseDetail':"",'comments':"",'purchaseItems' : "",'shippingModes':"",'mode':"",'statusMap':"",'paymentStatusMap':"",'warehouseIdNameMap':"",'purchaseId':"",'purchaseOrderId':""},
		'initWidget': function() {
			// 动态添加 formValidation 验证规则例子
			$.extend($.fn.formValidation.defaults.rules, {
				compareNum : {
					validator : function( value  ) {
						return false;
					},
					message : '常备库存数必须大于触发备份库存的界线'
				},
				comparePercentage : {
					validator : function( value ) {
						return false;
					},
					message : '建议备库存数量百分比必须大于触发备份库存的百分比'
				}
			});
			
			$('input[type="text"]:eq(1),input[type="text"]:eq(11)').formValidation({validType:['trim','length[2,250]'],tipPosition:'left'});
			$('#capture_user_name').formValidation({validType:['compareNum'],tipPosition:'left'});
			$('input[type="text"]:eq(5),input[type="text"]:eq(12)').formValidation({validType:['comparePercentage'],tipPosition:'right'});
			
			$('input[required="required"]').formValidation({validType:['trim','length[1,250]'],tipPosition:'left',required:true});
		},
		
		'initSubmitBtn':function() {	//编辑采购单，点击 保存
			if($('#update-purchase-form').formValidation('form_validate')){
				$.showLoading();
				$.get( global.baseUrl+'test/demo/save', $('#update-purchase-form').serialize(),
				   function (data){
						$.hideLoading();
						bootbox.alert({
						    message: "ok!", callback: function () {
						    	$('#update-purchase-form').parents('.modal').modal('hide');
						    	$("#purchase-list-table").queryAjaxPage();
	                        },
						});	
				});
			}
		},
		
	
		'popOperationLogView':function() {			

		},
		// 查看或新增备注
		'memoViewOrSave':function() { 
		
			
		},
		
		'statusFormatter':function(value) {
			
			return purchaseOrder.updateorview.setting.statusMap[value]["label"];
		},
		'paymentStatusFormatter':function(value) {
			//alert("statusFormatter");
			return purchaseOrder.updateorview.setting.paymentStatusMap[value];
		},
		'warehouseFormatter':function(value) {
			//alert("statusFormatter");
			return purchaseOrder.updateorview.setting.warehouseIdNameMap[value];
		},
		
		
};


if (typeof purchaseOrder === 'undefined')  purchaseOrder = new Object();
purchaseOrder.list2={
		'init': function() {
			$('#btn-create').unbind('click').click(function(){
//				$('#myModal').modal({remote: global.baseUrl+'purchase/purchase/create'});
				
				// bootbox.dialog.buttons.callback return false;例子
				$.showLoading();
				$.get( global.baseUrl+'test/demo/create',
				   function (data){
						$.hideLoading();
						bootbox.dialog({
							className: "myClass", 
							title : Translator.t("新增采购单"),
							buttons: {  
								Cancel: {  
			                        label: Translator.t("返回"),  
			                        className: "btn-default",  
			                        callback: function () {  
//			                        	debugger
			                        }
			                    }, 
			                    OK: {  
			                        label: Translator.t("保存"),  
			                        className: "btn-primary",  
			                        callback: function () {
			                        	return false;// callback return false 将不会关闭dialog 
			                        }  
			                    }, 
							},
						    message: data,
						});	
				});
			});
			
			$('#btn-purchase-search').unbind('click').click(function(){
				purchaseOrder.list2.searchPurchaseOrder();
			});
			
			$.initQtip();
			
		},
		
		'editPurchaseOrder' : function (id) {
			// bootbox.dialog 不定义buttons ;例子
			$.showLoading();
			$.get( global.baseUrl+'test/demo/edit-view?id='+id,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						closeButton: false,
						className: "myClass", 
					    message: data,
					});	
			});
		},
		
		'viewPurchaseOrder' : function (id) {
			
			// bootbox.dialog 不定义buttons ;例子
			$.showLoading();
			$.get( global.baseUrl+'test/demo/view?id='+id,
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						closeButton: false,
						className: "myClass", 
					    message: data,
					});	
			});
//			$('#checkOrder').modal({remote: global.baseUrl+'purchase/purchase/view?id='+id});
		},
		
		'searchPurchaseOrder' : function(){
			var search_purchase_order_id = $('input[name=search_purchase_order_id]').val();
			$("#purchase-list-table").queryAjaxPage({
				'purchase_order_id':search_purchase_order_id,
			});
		},
		
};



