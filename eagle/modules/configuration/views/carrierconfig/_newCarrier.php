<?php 
use yii\helpers\Html;
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
		width:500px;
		min-height:300px;
		line-height:44px;
		font-size: 13px;
    	color: rgb(51, 51, 51);
		padding:15px 15px;
	}
	.modal-dialog{
		width:500px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
</style>

<div class="modal-dialog">
      <div class="modal-content">
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
		新建物流商
	</h4>
</div>
<div class="modal-body">
	<form id="newcarrierFORM">
		<div><b class="col-sm-4 text-right">物流商名：</b>
		<?= Html::textinput('carrier_name','',['style'=>'width:200px;height:28px;line-height:28px;','class'=>'req iv-input'])?>
		</div>
		<div>
			<b class="col-sm-4 text-right">自定义物流商类型：</b>
			<label><input type="radio" name="carrier_type" value="1" class="req"> Excel导出数据</label>
			<label><input type="radio" name="carrier_type" value="0"> 分配跟踪号</label>
		</div>
	</form>
</div>
<div class="modal-footer">
	<button type="button" onclick="newCarrierAjax()" class="btn btn-success btn-lg" >新建</button>
	<button type="button" class="btn btn-default btn-lg" data-dismiss="modal">关闭</button>
</div>
</div><!-- /.modal-content -->
	</div><!-- /.modal -->