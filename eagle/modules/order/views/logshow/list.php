<?php
use yii\widgets\LinkPager;
use yii\helpers\Html;
use eagle\modules\util\models\OperationLog;
use eagle\modules\util\helpers\OperationLogHelper;

$this->title='订单操作日志';
$this->params['breadcrumbs'][] = $this->title;
?>	
<!-- 搜索 -->
<form class="form-inline" action="" method="post">
  <div class="form-group">
    <label for="exampleInputName2">订单号</label>
    <?=Html::textInput('orderid',@$_REQUEST['orderid'],['class'=>'form-control'])?>
  </div>
  <div class="form-group">
    <label for="exampleInputEmail2">操作类型</label>
    <?=Html::dropDownList('type',@$_REQUEST['type'],OperationLog::$ordertype,['class'=>'form-control','prompt'=>''])?>
  </div>
  <?=Html::submitButton('搜索',['class'=>'btn btn-success'])?>
</form>
<br>
<!-- 展示 -->
<table class="table table-condensed" style="font-size:12px">
<tr>
	<th>模块</th>
	<th>订单号</th>
	<th>操作人</th>
	<th>操作类型</th>
	<th>操作详情</th>
	<th>操作时间</th>
</tr>
<?php if (count($logs)):foreach ($logs as $log):?>
<tr>
	<td><?=OperationLogHelper::getChineseLogType($log->log_type);?></td>
	<td><?=$log->log_key?></td>
	<td><?=$log->capture_user_name?></td>
	<td><?=$log->log_operation?></td>
	<td><?=$log->comment?></td>
	<td><?=$log->update_time?></td>
</tr>
<?php endforeach;endif;?>
</table>
<?php
echo LinkPager::widget(['pagination' => $pages]);
			?>