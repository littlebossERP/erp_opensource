<?php 

use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
	.modal-content{
		border-color:#797979;
	}
	.modal-header{
		background:#364655;
		height:44px;
		border-bottom:2px solid #797979;
	}
	.modal-header .modal-title {
	    font: bold 18px;
	    color: white;
		height:44px;
		line-height:44px;
	}
	.modal-header .close {
	    color: white;
		height:44px;
		line-height:44px;
	}
	.modal-body{
		min-height:220px;
		font-size: 13px;
    	color: rgb(51, 51, 51);
	}
	.modal-dialog{
		width:530px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.p_lg{
		font-size: 30px;
		margin-top:60px;
	}
</style>
<div class="modal-dialog">
      <div class="modal-content">
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
		删除物流账号
	</h4>
</div>
<div class="modal-body">
	<p class="p_lg">您确定要删除该条揽件/发货地址吗？</p>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-primary" onclick="delAddressNow('<?= $id?>')" >确定</button>
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
</div><!-- /.modal-content -->
	</div><!-- /.modal -->
<script>
	function delAddressNow($id){
		var Url=global.baseUrl +'configuration/carrierconfig/deladdressnow';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {id:$id},
			url: Url,
	        success:function(response) {
	        	if(response[0] == 0){
	        		alert(response.substring(2));
// 			        location.reload();
					if(window.location.href.indexOf('?')>-1){
						$nowurl=window.location.href.substring(0,window.location.href.indexOf('?'));
						window.location.href=$nowurl+'?tcarrier_code='+$('#search_carrier_code').val();
					}
					else
						window.location.search='tcarrier_code='+$('#search_carrier_code').val();
		        }
	        	else{
	        		alert(response.substring(2));
		        }
	        }
	    });
	}
</script>