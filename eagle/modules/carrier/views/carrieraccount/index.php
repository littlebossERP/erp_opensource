<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\jui\Dialog;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->title = TranslateHelper::t('物流账号列表');
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
		<a class="btn btn-success" href="<?= Url::to(['/carrier/carrieraccount/create','return_url'=>$return_url])?>"><?= TranslateHelper::t('添加物流账号')?></a>
		<div style="height:10px">
		<form action="" method="post" name='form1' id='form1'>
<?=Html::hiddenInput('carrier_code',isset($search_data['carrier_code'])?$search_data['carrier_code']:'',['id'=>'carrier_code'])?>
<?=Html::hiddenInput('carrier_type',isset($search_data['carrier_type'])?$search_data['carrier_type']:'',['id'=>'carrier_type'])?>
<?=Html::hiddenInput('is_used',isset($search_data['is_used'])?$search_data['is_used']:'',['id'=>'is_used'])?>
</form>
		</div>
		<?php 
		$sort = new Sort(['attributes' => ['carrier_code','carrier_name','carrier_type','is_used','create_time']]);
		?>
	    <table class="table table-condensed" style="table-layout:fixed;font-size: 12px;">
	    <thead>
	        <tr>
	        	<th class="text-nowrap" style="width:60px;"></th>
	        	<th class="text-nowrap"><?=$sort->link('carrier_name',['label'=>TranslateHelper::t('账号别名')]) ?></th>
	        	<th class="text-nowrap"><?=Html::dropDownList('carrier_code',isset($search_data['carrier_code'])?$search_data['carrier_code']:'',$carrier,['prompt'=>TranslateHelper::t('物流商'),'style'=>'width:65px;','class'=>'search']);?><?=$sort->link('carrier_code',['label'=>TranslateHelper::t('')]) ?></th>
		        <th class="text-nowrap"><?=Html::dropDownList('carrier_type',isset($search_data['carrier_type'])?$search_data['carrier_type']:'',['0'=>'货代','1'=>'海外仓'],['prompt'=>TranslateHelper::t('发货类型'),'style'=>'width:80px;','class'=>'search']);?><?=$sort->link('carrier_type',['label'=>TranslateHelper::t('')]) ?></th>
		        <th class="text-nowrap"><?=Html::dropDownList('is_used',isset($search_data['is_used'])?$search_data['is_used']:'',['0'=>'关闭','1'=>'开启'],['prompt'=>TranslateHelper::t('是否开启'),'style'=>'width:80px;','class'=>'search']);?><?=$sort->link('is_used',['label'=>TranslateHelper::t('')]) ?></th>
		        <th class="text-nowrap"><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建日期')]) ?></th>
		        <th class="text-nowrap" style="width:300px;"><?= TranslateHelper::t('操作')?></th>
		      </tr>
		  </thead>
		  <tbody>
		 <?php if(count($list)>0){ ?>
         <?php $rowIndex = 1; foreach($list['data'] as $row){?>
	            <tr class="hidetr <?php echo $row['is_used']==0?'danger':''?>">
	            	<td class="text-nowrap"><?php echo $rowIndex;?></td>
	            	<td class="text-nowrap"><?=$row['carrier_name'] ?></td>
	            	<td class="text-nowrap"><?=$carrier[$row['carrier_code']] ?></td>
		            <td class="text-nowrap"><?=$row['carrier_type']==0?'货代':'海外仓' ?></td>
		            <td class="text-nowrap">
		            <?php if ($row['is_used']==1){?>
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
		            <td class="text-nowrap"><?=date('Y-m-d',$row['create_time']) ?></td>
		            <td class="text-nowrap">
		            <?php if ($row['is_used'] == 0){?>
		            <a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/carrieraccount/onoff','id'=>$row['id'],'is_used'=>1])?>" class="onoff"><?php echo TranslateHelper::t('开启');?></a> |
					<?php }elseif ($row['is_used'] == 1){?>
					<a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/carrieraccount/onoff','id'=>$row['id'],'is_used'=>0])?>" class="onoff"><?php echo TranslateHelper::t('关闭');?></a> |
					<a style="text-decoration: none;" href="<?=Url::to(['/carrier/carrieraccount/create','id'=>$row['id'],'return_url'=>$return_url])?>"><?php echo TranslateHelper::t('编辑');?></a> |
					<?php }?>
					<a style="text-decoration: none;" href="<?=Url::to(['/carrier/shippingservice/index','carrier_account_id'=>$row['id']])?>"><?php echo TranslateHelper::t('运输服务管理');?></a>
					<a style="text-decoration: none;" class="refreshButton" carrierid="<?=$row['id'] ?>" title="获取物流商最新的运输方式,新的运输服务将会添加到系统中"><?php echo TranslateHelper::t('更新运输服务');?></a>
		            </td>
		        </tr>
	         
	        <?php $rowIndex++;}}else{echo '<tr><td colspan="6">还没有添加物流商账号,请添加</td></tr>';}?>
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
<script>
	var freshUrl = "<?=Url::to(['/carrier/carrieraccount/refreshshippingservice'])?>";

</script>
