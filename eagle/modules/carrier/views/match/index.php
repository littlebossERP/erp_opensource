<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\carrier\apihelpers\ApiHelper;
use yii\widgets\LinkPager;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->title=TranslateHelper::t('运输服务匹配规则列表');
//$this->params['breadcrumbs'][] = $this->title;
?>
<style>
.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}

.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
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
<div class="tracking-index col2-layout">
<?= $this->render('//layouts/menu_left_carrier') ?>
<!-- 右侧table内容区域 -->
<div class="content-wrapper" >
<a class="btn btn-success" href="<?= Url::to(['/carrier/match/edit','is_custom'=>$_GET['is_custom'],'return_url'=>$return_url])?>"><?= TranslateHelper::t('添加运输服务匹配规则')?></a>

<div style="height:10px">
		<form action="" method="post" name='form1' id='form1'>
<?=Html::hiddenInput('carrier_code',isset($search_data['carrier_code'])?$search_data['carrier_code']:'',['id'=>'carrier_code'])?>
<?=Html::hiddenInput('carrier_type',isset($search_data['carrier_type'])?$search_data['carrier_type']:'',['id'=>'carrier_type'])?>
<?=Html::hiddenInput('is_used',isset($search_data['is_used'])?$search_data['is_used']:'',['id'=>'is_used'])?>
</form>
		</div>
<table class="table table-condensed" style="table-layout:fixed;font-size: 12px;">
<thead>
	<tr>
		<th class="text-nowrap" style="width:60px;"></th>
		<th><?php echo TranslateHelper::t('规则名');?></th>
		<th><?php echo TranslateHelper::t('运输服务');?></th>
		<th><?php echo TranslateHelper::t('优先级');?></th>
		<th><?php echo TranslateHelper::t('是否开启');?></th>
		<th><?php echo TranslateHelper::t('操作');?></th>
	</tr>
	</thead>
	<tbody>
<?php if(count($list)>0){ ?>
         <?php $rowIndex = 1; foreach($list['data'] as $row){?>
	<tr class="<?php echo $row['is_active']==0?'danger':''?>">
		<td class="text-nowrap"><?php echo $rowIndex;?></td>
		<td><?php echo $row->rule_name;?></td>
		<td><?php $services = ApiHelper::getShippingServices(true);echo $services[$row->transportation_service_id];?></td>
		<td><?php echo $row->priority;?></td>
		<td>
		 <?php if ($row['is_active']==1){?>
		            <p class="text-success">
		            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
		            <?= TranslateHelper::t('已开启') ?>
		            </p>
		            <?php }else{?>
		            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
		            <?php }?>
		</td>
		<td>
		 <?php if ($row['is_active'] == 0){?>
		            <a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/match/onoff','id'=>$row['id'],'is_active'=>1])?>" class="onoff"><?php echo TranslateHelper::t('开启');?></a>
					<?php }elseif ($row['is_active'] == 1){?>
					<a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/match/onoff','id'=>$row['id'],'is_active'=>0])?>" class="onoff"><?php echo TranslateHelper::t('关闭');?></a> |
					<a style="text-decoration: none;" href="<?=Url::to(['/carrier/match/edit','is_custom'=>$_GET['is_custom'],'id'=>$row->id,'return_url'=>$return_url])?>"><?php echo TranslateHelper::t('编辑');?></a> 
					<?php }?>
					| <a style="text-decoration: none;" href="javascript:void(0)" onclick="delcarriermatch('<?=$row->id ?>')" class=""><?php echo TranslateHelper::t('删除');?></a> 
		</td>
	</tr>
<?php $rowIndex++;}}else{echo '<tr><td colspan="6">还没有添加运输服务匹配规则,请添加</td></tr>';}?>
</tbody>
</table>
<?php if($list['pagination']):?>
		<div id="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$list['pagination'] , 'pageSizeOptions'=>array( 15 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%; text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $list['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
		<?php endif;?>
</div>
</div>

<script type="text/javascript">

function delcarriermatch(val){
		bootbox.confirm("是否确定删除物流匹配规则？", function (res) {
            if (res == true) {
            	$.showLoading();
        		$.post('<?=Url::to(['/carrier/match/del-match'])?>',{id:val},function(response){
        			$.hideLoading();
        			var result = JSON.parse(response);
        			if(result.Ack ==1){
        				bootbox.alert(result.msg);
        			}
        			window.location.reload();
        			$.showLoading();
        		});
            }
        });
}

</script>