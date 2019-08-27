<?php
use yii\helpers\Html;
use yii\helpers\Url;

?>

<?php 

?>
<style>
#setting-form{
	float:left;
}
#setting-form .table th,#setting-form .table td{
	text-align: center;
	border: 1px solid #ddd;
}
p .alert b{
	font-weight:bold;
}
.red{
	color:red
}
</style>
<div class="tracking-index col2-layout">
	<?= $this->render('_menu') ?>
	<div class="content-wrapper" >
		<p style="width:100%;float:left;">
			<span class="alert alert-info" style="float:left;text-align:left;line-height:2;">
			物流跟踪助手可以自动同步下列平台订单的物流号。<br>
			当用户新绑定这些平台的账号时，系统会自动拉取部分旧单，同时物流跟踪助手也会同步物流号跟踪信息，此时<b class="red">会消耗物流查询配额</b>。<br>
			用户可以自行设置新绑定的时候立即同步多少天前的订单物流号(不设置的话默认为7天,数值不能小于0,小于0的情况下会强制设置为0)，来调整配额的消耗程度(或完全不同步旧单)。
			本设置不会影响订单管理系统的同步。
			
			</span>
		</p>
		<form id="setting-form" style="" action="/tracking/tracking/get-od-trackno-days-set" method="post">
			
			
			<table class="table" style="float:left;width:100%;">
				<tr><th width="50%">新绑定账号的平台</th><th width="50%">同步天数</th></tr>
				<?php if(!empty($platforms)){?>
				<?php foreach ($platforms as $platform=>$cn){?>
				<tr>
					<td><?=$cn?></td>
					<td><input type="number" name="setting[<?=$platform?>]" value="<?=@$setting[$platform]?>" style="width:50px;"></td>
				</tr>
				<?php } ?>
				<?php } ?>
			</table>
			<p style="width:100%;display:inline-block;text-align:center;"><button type="submit" class="btn btn-success">保存</button></p>
		</form>
	</div>
</div>