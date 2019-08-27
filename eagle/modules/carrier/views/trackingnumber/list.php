<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\jui\Dialog;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysTrackingNumber;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->title = TranslateHelper::t('物流号管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 

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
	<div class="order-operate" style="margin-top:10px;margin-bottom:10px;">
	   <button data-toggle="modal" data-target="#checkOrder" onclick="addTrackingNumber()" class="btn btn-success"><?=TranslateHelper::t('添加可用物流号') ?></button>
	</div>
	    <table class="table table-condensed table-striped" style="table-layout:fixed;line-height:50px;">
	    <thead>
	    <tr>
	    	<th class="text-nowrap" width="60px;"></th>
        	<th class="text-nowrap"><?=TranslateHelper::t('物流号')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('运输服务')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('分配状态')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('创建人')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('创建日期')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('分配日期')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
	     </tr>
	     </thead>
	     <tbody>
	     <?php if(count($list)>0){ ?>
         <?php $rowIndex = 1; foreach($list['data'] as $row){?>
	     <tr>
	     	<td class="text-nowrap"><?=$rowIndex;?></td>
        	<td class="text-nowrap"><?=$row['tracking_number']?></td>
        	<td class="text-nowrap"><?=$row['service_name']?></td>
        	<td class="text-nowrap"><?=SysTrackingNumber::$is_used[$row['is_used']]?></td>
        	<td class="text-nowrap"><?=$row['user_name']?></td>
        	<td class="text-nowrap"><?php if ($row['create_time']>0){echo date('Y-m-d',$row['create_time']);}else{echo '';}?></td>
        	<td class="text-nowrap"><?php if ($row['use_time']>0){echo date('Y-m-d',$row['use_time']);}else{echo '';}?></td>
	        <td class="text-nowrap">
	        <a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/trackingnumber/del','id'=>$row['id']])?>" class="del"><?php echo TranslateHelper::t('删除');?></a>
	        </td>
	     </tr>
	     <?php $rowIndex++;}?>
	      <?php }?>
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
<!-- Modal -->
	<div id="checkOrder" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
        </div><!-- /.modal-content -->
    </div>
    </div>
    <!-- /.modal-dialog -->
<script>
	function addTrackingNumber() {//添加订单标签
		var reUrl='<?= \Yii::$app->urlManager->createUrl("carrier/trackingnumber/savetrackingnumber") ?>';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code : ''},
			url: reUrl,
	        success:function(response) {
	        	$('#checkOrder .modal-content').html(response);
	        }
	    });
	}
</script>
