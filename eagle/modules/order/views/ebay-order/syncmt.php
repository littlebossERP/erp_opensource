<?php 

use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
.table td,.table th{
	text-align: left;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
</style>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	手动同步订单
	</h4>
</div>
<div class="modal-body">
	<table class="table table-condensed table-bordered  table-striped"  style="font-size:12px;width:400px">
	<tr>
		<th>账号</th><th>最后次同步完成时间</th><th></th>
	</tr>
	<?php if (count($sync)):foreach ($sync as $s):?>
	<tr>
		<td><?=$s->selleruserid?></td>
		<td><?=date('Y-m-d H:i:s',$s->lastprocessedtime)?></td>
		<td>
			<?=Html::button('优先同步',['onclick'=>'dosyncmt(this)'])?>
		</td>
	</tr>
	<?php endforeach;endif;?>
	</table>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
<script>
	function dosyncmt(obj){
		$.showLoading();
		var selleruserid = $(obj).parent().prev().prev().text();
		var Url='<?=Url::to(['/order/order/ajaxsyncmt'])?>';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {selleruserid : selleruserid},
			url: Url,
	        success:function(response) {
	        	var result = eval('(' + response + ')');
	        	$.hideLoading();
	        	if(result.ack == 'success'){
	        		bootbox.alert('请求成功,请稍后刷新订单列表');
	        		$(obj).parent().html("<font color='red'>等待同步中...</font>");
		        }else if(result.ack == 'failure'){
					bootbox.alert('请求失败：'+result.msg);
			    }
	        }
	    });
	}
</script>