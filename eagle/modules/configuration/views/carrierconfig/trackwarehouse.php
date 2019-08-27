<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/trackwarehouse.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/insertTrack.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

?>
<style>
.partDIV{
	font-family: 'Applied Font Regular', 'Applied Font';
	font-size:13px;
	height:25px;
	line-height:1.5;
	padding:2px 10px;
	color:black;
}
.partDIV>div{
	float:left;
	margin:0 12px 5px 0;
	height:25px;
}
.partDIV>div input{
	height:22px;
 	line-height:1.5;
 	width:128px;
}
.partDIV select{
	height:22px;
	line-height:22px;
	width:128px;
}
#searchBtn{
 	width:100px;
	padding-top:2px;
}
.showtable{
	border-collapse: collapse;
	border: 1px solid #797979;
	font-size: 13px;
    color: rgb(51, 51, 51);
}
.showtable>thead>tr>th{
	text-align:center;
	background:white;
	border: 1px solid #797979;
}
.showtable>tbody>tr>td{
	text-align:center;
	border: 1px solid #797979;
}
.btn-up{
	width:30%;
}
.btn-down{
	width:30%;
}
.btn-null{
	width:30%;
}
</style>

<?php echo $this->render('../leftmenu/_leftmenu');?>

<div>
	<form id='searchFORM' action='' method='post'>
		<div class="partDIV">
			<div><b>跟踪号：</b><?= Html::textinput('tracking_number',@$data['tracking_number'],['class'=>'iv-input','id'=>'carrier_name'])?></div>
			<div><b>自定义物流商：</b><?= Html::dropdownlist('carrier_name',@$data['carrier_name'],@$carriers,['class'=>'iv-input','id'=>'carrier_name'])?></div>
			<div><b>自定义运输服务：</b><?= Html::dropdownlist('shipping_method_name',@$data['shipping_method_name'],@$methods,['class'=>'iv-input','id'=>'carrier_name'])?></div>
			<div><b>分配状态：</b><?= Html::dropdownlist('is_used',@$data['is_used'],$status,['class'=>'iv-input','id'=>'carrier_name'])?></div>
		</div>
		<div class="partDIV">
			<div><b>创建日期：</b>
				<input class="iv-input" type="date" name="create_timeStart" placeholder="开始时间" max="create_timeEnd" value="<?= @$data['create_timeStart']?>" /> ~ 
				<input class="iv-input" type="date" name="create_timeEnd" placeholder="结束时间" min="create_timeStart" value="<?= @$data['create_timeEnd']?>" />
			</div>
			<div><b>分配日期：</b>
				<input class="iv-input" type="date" name="use_timeStart" placeholder="开始时间" max="use_timeEnd" value="<?= @$data['use_timeStart']?>" /> ~ 
				<input class="iv-input" type="date" name="use_timeEnd" placeholder="结束时间" min="use_timeStart" value="<?= @$data['use_timeEnd']?>" />
			</div>
			<div><b>订单号：</b><?= Html::textinput('order_id',@$data['order_id'],['class'=>'iv-input','id'=>'carrier_name'])?></div>
			<div><?= Html::input('submit','','筛选',['class'=>'iv-btn btn-search btn-spacing-middle','id'=>'searchBtn'])?></div>
		</div>
	</form>
	<div class="col-xs-12" style="margin:7px 0">
		<a class="iv-btn btn-search" href="/configuration/carrierconfig/insert-track" target="_modal" title="添加跟踪号">添加跟踪号</a>
	</div>
	<div>
		<table class="table text-center showtable" style="table-layout:fixed;line-height:50px; margin:0;">
			<thead>
				<tr>
					<th><?= TranslateHelper::t('跟踪号')?></th>
					<th><?= TranslateHelper::t('自定义物流商')?></th>
					<th><?= TranslateHelper::t('自定义运输服务')?></th>
					<th><?= TranslateHelper::t('分配状态')?></th>
					<th><?= TranslateHelper::t('创建人')?></th>
					<th><?= TranslateHelper::t('创建日期')?></th>
					<th><?= TranslateHelper::t('分配日期')?></th>
					<th><?= TranslateHelper::t('订单号')?></th>
					<th><?= TranslateHelper::t('操作')?></th>
				</tr>
			</thead>
			<tbody>
				<?php 
					if(!empty($table['data']))
					foreach ($table['data'] as $row){
						$statu = ($row['is_used'])?'已分配':'未分配';
						$creT = empty($row['create_time'])?'':date ('Y-m-d H:i:s',@$row['create_time']);
						$useT = empty($row['use_time'])?'':date ('Y-m-d H:i:s',@$row['use_time']);
				?>
				<tr data="<?= $row['id']?>">
					<td data='no'><?= TranslateHelper::t(@$row['tracking_number'])?></td>
					<td><?= TranslateHelper::t(@$row['carrier_name'])?></td>
					<td><?= TranslateHelper::t(@$row['shipping_method_name'])?></td>
					<td><?= TranslateHelper::t($statu)?></td>
					<td><?= TranslateHelper::t(@$row['user_name'])?></td>
					<td><?= TranslateHelper::t($creT)?></td>
					<td><?= TranslateHelper::t($useT)?></td>
					<td><?= TranslateHelper::t(@$row['order_id'])?></td>
					<td>
						<?php if(!$row['is_used']){?>
						<a class="btn btn-xs setused">标记已分配</a>
						<?php }?>
						<a class="btn btn-xs del">删除</a>
					</td>
				</tr>
				<?php }?>
			</tbody>
		</table>
		
		<?php if($table['pagination']):?>
		<div id="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$table['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 , 500 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%; text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $table['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
			</div>
		<?php endif;?>
	</div>
</div>