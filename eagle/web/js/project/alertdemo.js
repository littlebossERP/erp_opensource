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
			$('#small-show-btn').unbind('click').click(function(){
//				$('#my-small-modal').modal('show');
				
				 //bootbox.alert("This alert has custom button text");
				bootbox.confirm("dfffff",function(){});
			/*	bootbox.confirm({
				    buttons: {
				        confirm: {
				            label: 'Localized confirm text',
				            className: 'confirm-button-class'
				        },
				        cancel: {
				            label: 'Localized cancel text',
				            className: 'cancel-button-class'
				        }
				    },
				    message: 'Your message',
				    callback: function(result) {
				        console.log(result);
				    },
				    title: "You can also add a title",
				});	*/
				
			/*	bootbox.alert({
				    buttons: {
				        ok: {
				            label: 'Localized confirm text',
				        },
				    },
				    message: "You can also ade",
				});					
				*/
				
				
				
			});

			$('#change1-btn').unbind('click').click(function(){
				$('#mySmallModalLabel').text("aaaaa dddddddd fffffffff ddddddddddd");
				$('#my-small-modal').modal('show');
			});
			
			
		}
		
}

$(function() {
	purchaseOrder.list.init();
	
});