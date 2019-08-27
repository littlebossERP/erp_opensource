0
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
		min-height:50px;
		font-size: 13px;
    	color: rgb(51, 51, 51);
	}
	.modal-dialog{
		width:500px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.clear {
	    clear: both;
	    height: 0px;
	}	
</style>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		    	&times;
		    </button>
		    <h4 class="modal-title" id="myModalLabel">
				批量关闭运输服务
			</h4>
		</div>
		<div class="modal-body">
			<div class="">
				<form id="myBatchShipping">
					<div class="sr-only">
					<?php 
						foreach ($selectShip as $k=>$s){
							echo Html::checkbox('selectShip['.$k.']',true,['value'=>$s]);
						}
					?>
					</div>
					<?= Html::checkboxList('selectShip',$selectShip,$selectShip,['style'=>'display:none'])?>
					<h2>是否继续批量将所选的运输方式关闭 ?</h2>
				</form>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" onclick="batchCloseShippingNow()">确定</button>
			<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal -->

<script>

	function batchCloseShippingNow(){
		$form = $('#myBatchShipping').serialize();
		var Url=global.baseUrl +'configuration/carrierconfig/batchcloseshippingnow';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $form,
			url: Url,
	        success:function(response) {
	        	alert(response);
	        }
	    });
	}
</script>