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
	.clear {
	    clear: both;
	    height: 0px;
	}
	.myBatchHouseTitle{
		height:44px;
		line-height:44px;
		font-size:18px;
		font-weight:bold;
	}
</style>

<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
		    	&times;
		    </button>
		    <h4 class="modal-title" id="myModalLabel">
				自营仓库设置
			</h4>
		</div>
		<div class="modal-body">
			<div class="myBatchHouseTitle">自营仓库设置</div>
			<div>
				<form id="myBatchHouse">
					<?= Html::hiddenInput('carrier_code',$carrier_code)?>
					<div class="sr-only">
						<?php 
							foreach ($selectShip as $k=>$s){
								echo Html::checkbox('selectShip[]',true,['value'=>$s]);
							}
						?>
					</div>
					<label><input type="checkbox" id="mycheckAll"> 全选/取消</label>
					<?php foreach ($warehouses as $k=>$w){?>
						<?= Html::hiddenInput('warehouse['.$k.']',$w['is_selected'])?>
						<label><input type="checkbox" name="mywarehouse[<?= $k?>]" <?= ($w['is_selected'])?'checked':''?>> <?= $w['name']?></label>
					<?php }?>
				</form>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary" onclick="batchChangewarehouseNow()">提交</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal -->

<script>
	$(function(){
		$('input[name^="mywarehouse"]').click(function(){
			name = $(this).prop('name').substr(2);
			is_checked = ($(this).prop('checked'))?1:0;
			$('input[name="'+name+'"]').val(is_checked);
		});
		$('#mycheckAll').click(function(){
			is_checked = $(this).prop('checked');
			is_checked_val = ($(this).prop('checked'))?1:0;
			$('input[name^="mywarehouse"]').each(function(){
				$(this).prop('checked',is_checked);
				name = $(this).prop('name').substr(2);
				$('input[name="'+name+'"]').val(is_checked_val);
			});
		});
	});
	function batchChangewarehouseNow(){
		$form = $('#myBatchHouse').serialize();
		var Url=global.baseUrl +'configuration/carrierconfig/batchchangewarehousenow';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $form,
			url: Url,
	        success:function(response) {
	        	if(response[0] == 1){
	        		alert('请选择仓库');
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