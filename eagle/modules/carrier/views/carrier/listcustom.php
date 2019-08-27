<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\jui\Dialog;
use eagle\modules\carrier\models\SysCarrier;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->title = TranslateHelper::t('物流商列表');
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
	<?php $is_custom = isset($_GET['is_custom'])?$_GET['is_custom']:0;?>
	<div class="order-operate" style="margin-top:10px;margin-bottom:10px;">
	   <button data-toggle="modal" data-target="#checkOrder" onclick="editCarrier('','carrier')" class="btn btn-success"><?=TranslateHelper::t('新增自定义物流商') ?></button>
	</div>
	    <table class="table table-condensed table-striped" style="table-layout:fixed;line-height:50px;">
	    <thead>
	    <tr>
	    	<th class="text-nowrap" width="60px;"></th>
        	<th class="text-nowrap"><?=TranslateHelper::t('自定义物流商')?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
	     </tr>
	     </thead>
	     <tbody>
	     <?php if(count($carriers)>0){ ?>
         <?php $rowIndex = 1; foreach($carriers['data'] as $carrier){?>
	     <tr>
	     	<td class="text-nowrap"><?=$rowIndex;?></td>
        	<td class="text-nowrap"><?=$carrier['carrier_name']?></td>
	        <td class="text-nowrap">
	        <a style="text-decoration: none;" onclick="editCarrier('<?=$carrier['carrier_code'] ?>','carrier')" data-toggle="modal" data-target="#checkOrder"><?php echo TranslateHelper::t('编辑');?></a> |
	        <a style="text-decoration: none;" href="<?=Url::to(['/carrier/shippingservice/index','carrier_code'=>$carrier['carrier_code'],'is_custom'=>$is_custom])?>"><?php echo TranslateHelper::t('运输服务管理');?></a>
	        </td>
	     </tr>
	     <?php $rowIndex++;}?>
	      <?php }?>
	      </tbody>
	    </table>
	    <?php if($carriers['pagination']):?>
		<div id="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$carriers['pagination'] , 'pageSizeOptions'=>array( 15 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%; text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $carriers['pagination'],'options'=>['class'=>'pagination']]);?>
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
	function editCarrier(carrierCode,op) {//添加订单标签
		var reUrl = '';
		if(op=='carrier')reUrl='<?= \Yii::$app->urlManager->createUrl("carrier/carrier/createcustom") ?>';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {code : carrierCode},
			url: reUrl,
	        success:function(response) {
	        	$('#checkOrder .modal-content').html(response);
	        }
	    });
	}
</script>
