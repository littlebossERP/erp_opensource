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
		width:400px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	
	.impor_red{
		color:#ED5466 ;
		background:#F5F7F7;
		padding:0 3px;
		margin-right:3px;
	}
	.d_width_m{
		width:150px;
		height:23px;
		color:black;
	}
	.clear {
	    clear: both;
	    height: 0px;
	}
	.myl{
		float:left;
		text-align:right;
		width:160px;
		padding-right:5px;
		min-height:1px;
		min-height:25px;
		line-height:25px;
	}
	.myr{
		margin-left:170px;
		padding-left:5px;
		min-height:25px;
		line-height:25px;
	}
	
	
</style>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		    	&times;
		    </button>
		    <h4 class="modal-title" id="myModalLabel">
				批量修改地址
			</h4>
		</div>
		<div class="modal-body">
			<div class="myl"><span class="impor_red">*</span>揽收/发货地址</div>
				<div class="myr">
					<form id="myBatchAddress">
						<?= Html::hiddenInput('carrier_code',$carrier_code)?>
						<div class="sr-only">
						<?php 
							foreach ($selectShip as $k=>$s){
								echo Html::checkbox('selectShip[]',true,['value'=>$s]);
							}
						?>
						</div>
						<?= Html::dropDownList('addressNames','',['0'=>'默认揽收地址']+@$addressNames['list'],['class'=>'d_width_m']);?>
					</form>
				</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" onclick="batchChangeaddressNow()">提交</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal -->

<script>

	function batchChangeaddressNow(){
		$form = $('#myBatchAddress').serialize();
		var Url=global.baseUrl +'configuration/carrierconfig/batchchangeaddressnow';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $form,
			url: Url,
	        success:function(response) {
	        	if(response[0] == 1){
	        		alert('请选择地址');
	        	}else if(response[0] == 0){
	        		if(response[1] == 0){
		        		alert(response.substring(3));
			        }
		        	else{
		        		alert(response.substring(3));
			        }
	        	}
	        	else{
	        		alert(response);
	        	}
	        }
	    });
	}
</script>