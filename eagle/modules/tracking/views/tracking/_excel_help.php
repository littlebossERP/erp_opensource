<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
?>

<ul class="list-unstyled">
	<li><?php 
	echo TranslateHelper::t('1. 系统支持上传excel 文件进行 批量(不限制物流号数量)查询。其中 快递单号 这个列是必须的，其他列是可选的。');
	?></li>
	<li>
<?php 
	echo TranslateHelper::t('2. 每次录入的查询物流号，系统将会记录这个查询，并进行每隔6小时持续跟踪，阁下可以在查询记录里面查询其最新
递送情况，也可以通过设置，让系统每天通过电邮自动报告有问题的递送包裹。');
	?>
	</li>
	<li>
<?php 
	echo TranslateHelper::t('3. 如果多次录入是同一个物流号，并且携带的物流费用，订单号等内容不一样，系统会以后来的物流费用，订单号 覆盖之前的。进行更新维护物流号的详细信息。');
	?>
	
	</li>
	<li>
		<a href="<?= $baseUrl."template/Tracking Template.xls"?>"><?php 
	echo TranslateHelper::t('下载 Excel批量上传模板');
		?></a>
	</li>
</ul>