<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\models\carrier\CrTemplate;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->title = TranslateHelper::t('运输服务列表');
//$this->params['breadcrumbs'][] = $this->title;
 ?>
<style>
	.hidetr td {
		overflow:hidden;
	}
</style>
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
<?php if ($is_custom==1){?>
<div class="order-operate" style="margin-top:10px;margin-bottom:10px;">
   <a class="btn btn-success" href='<?=Url::to(['/carrier/shippingservice/createcustom','return_url'=>$return_url])?>'><?=TranslateHelper::t('新增自定义运输服务') ?></a>
</div>
<?php }?>
	<div style="height:10px">
		<form action="" method="post" name='form1' id='form1'>
<?=Html::hiddenInput('carrier_code',isset($search_data['carrier_code'])?$search_data['carrier_code']:'',['id'=>'carrier_code'])?>
<?=Html::hiddenInput('is_used',isset($search_data['is_used'])?$search_data['is_used']:'',['id'=>'is_used'])?>
<?=Html::hiddenInput('carrier_account_id',isset($search_data['carrier_account_id'])?$search_data['carrier_account_id']:'',['id'=>'carrier_account_id'])?>
<?=Html::hiddenInput('is_custom',isset($search_data['is_custom'])?$search_data['is_custom']:'')?>
</form>
		</div>
	
	<?php 
	$sort = new Sort(['attributes' => ['carrier_code','shipping_method_name','service_name','is_used','create_time','carrier_account_id']]);
	?>
	<!-- table -->
	    <table class="table table-condensed" style="table-layout:fixed;font-size: 12px;">
	     <thead>
	        <tr class="list-firstTr">
	        	<th class="text-nowrap" style="width:60px;"></th>
	        	<th><?=$sort->link('shipping_method_name',['label'=>TranslateHelper::t('运输服务')]) ?></th>
	        	<th><?=Html::dropDownList('carrier_code',isset($search_data['carrier_code'])?$search_data['carrier_code']:'',$carriers,['prompt'=>TranslateHelper::t('物流商'),'style'=>'width:65px;','class'=>'search']);?><?=$sort->link('carrier_code',['label'=>TranslateHelper::t('')]) ?></th>
	        	<?php if ($is_custom==0){?>
	        	<th><?=Html::dropDownList('carrier_account_id',isset($search_data['carrier_account_id'])?$search_data['carrier_account_id']:'',$accounts,['prompt'=>TranslateHelper::t('物流商账号'),'style'=>'width:100px;','class'=>'search']);?><?=$sort->link('carrier_account_id',['label'=>TranslateHelper::t('')]) ?></th>
		        <?php }?>
		        <th><?=Html::dropDownList('is_used',isset($search_data['is_used'])?$search_data['is_used']:'',['0'=>'关闭','1'=>'开启'],['prompt'=>TranslateHelper::t('是否开启'),'style'=>'width:80px;','class'=>'search']);?><?=$sort->link('is_used',['label'=>TranslateHelper::t('')]) ?></th>
		        <th><?=$sort->link('service_name',['label'=>TranslateHelper::t('运输服务别名')]) ?></th>
		        <th><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建日期')]) ?></th>
		        <?php if ($is_custom==1){?>
	        	<th><?php echo TranslateHelper::t('自定义打印模板')?></th>
		        <?php }?>
		        <th><?= TranslateHelper::t('操作')?></th>
		      </tr>
		      </thead>
		  <tbody>
	        <?php if(count($list)>0){ ?>
         <?php $rowIndex = 1; foreach($list['data'] as $row){?>
	            <tr class="hidetr <?php echo $row['is_used']==0?'danger':''?>" >
	            	<td class="text-nowrap"><?php echo $rowIndex;?></td>
	            	<td><?=$row['shipping_method_name'] ?></td>
	            	<td><?=isset($carriers[$row['carrier_code']])?$carriers[$row['carrier_code']]:$row['carrier_code'] ?><br>
	            	<?php echo $row['warehouse_name']?>
	            	</td>
	            	<?php if ($is_custom==0){?>
	            	<td><?=isset($accounts[$row['carrier_account_id']])?$accounts[$row['carrier_account_id']]:''; ?></td>
		            <?php }?>
		            <td>
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
		            <td><?=$row['service_name'] ?></td>
		            <td><?=date('Y-m-d',$row['create_time']) ?></td>
		            <?php if ($is_custom==1){?>
		        	<td style="text-align: left;">
		        	<?php 
		        	if (count($row->custom_template_print)>0){
						foreach ($row->custom_template_print as $template_type=>$template_id){
							if (strlen($template_id)==0){
							$template_name ='<span style="color:red;">未设置</span>';
							}else {
							$template_obj = CrTemplate::findOne(['template_id'=>$template_id]);
							$template_name =  $template_obj->template_name;
							}
							echo $template_type.':'.$template_name.'<br/>';
						}
					}else{
						echo '未设置自定义打印模板';
					}
					?>
		        	</td>
		        	<?php }?>
		            <td>
		            <?php if ($row['is_used'] == 0){?>
			            <?php if ($row['is_custom']==1){?>
			            	<a style="text-decoration: none;" href="<?=Url::to(['/carrier/shippingservice/createcustom','id'=>$row['id'],'is_used'=>1,'return_url'=>$return_url])?>" class="onoff"><?php echo TranslateHelper::t('开启并编辑');?></a>
			            <?php }else {?>
			            	<a style="text-decoration: none;" href="<?=Url::to(['/carrier/shippingservice/create','id'=>$row['id'],'is_used'=>1,'return_url'=>$return_url])?>" class="onoff"><?php echo TranslateHelper::t('开启并编辑');?></a>
						<?php }?>
					<?php }elseif ($row['is_used'] == 1){?>
					<a style="text-decoration: none;" href="javascript:void(0)" url="<?=Url::to(['/carrier/shippingservice/onoff','id'=>$row['id'],'is_used'=>0])?>" class="onoff"><?php echo TranslateHelper::t('关闭');?></a> |
					<?php if ($row['is_custom']==1){?>
					<a style="text-decoration: none;" href="<?=Url::to(['/carrier/shippingservice/createcustom','id'=>$row['id'],'return_url'=>$return_url])?>"><?php echo TranslateHelper::t('编辑');?></a>
					<?php }else {?>
					<a style="text-decoration: none;" href="<?=Url::to(['/carrier/shippingservice/create','id'=>$row['id'],'return_url'=>$return_url])?>"><?php echo TranslateHelper::t('编辑');?></a>
					<?php }?>
					<?php }?>
		            </td>
		        </tr>
	        <?php $rowIndex++;}}else{echo '<tr><td colspan="6">没有运输服务,请添加</td></tr>';}?>
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