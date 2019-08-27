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

// var_dump($carriers['pagination']);

// print_r($carriers['pagination']->getPageCount());
// print_r($carriers['pagination']->getPage());

$this->registerJs("$(\"select[name='carrier_code_sel']\").combobox({removeIfInvalid:true});" , \yii\web\View::POS_READY);
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

.ui-autocomplete {
z-index: 2000 !important;
overflow-y: scroll;
max-height: 400px;
}

</style>
<div class="tracking-index col2-layout">
	<?= $this->render('//layouts/menu_left_carrier') ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
		<form action="" method="post" name='form1' id='form1'>
		<div style='margin-bottom:5px'>
			<div style="width:200px;float:left;">
				<div class="div-input-group" style="width:100%">
					<div style="">
						<select name='carrier_code_sel' class="eagle-form-control" >
							<option value="all" <?=(!isset($service['service_code']['amazon']) or !is_numeric($service['service_code']['amazon']) )?" selected ":'' ?>><?= TranslateHelper::t("物流商")?></option>
							<?php foreach($carrierList as $carrierKey => $carrierVal):
								if (isset($_POST['carrier_code_sel'])) $isSelect = ($_POST['carrier_code_sel'] == $carrierKey)?"selected":"";
								else $isSelect = ""; ?>
							<option value="<?= $carrierKey?>" <?= $isSelect ?>><?=$carrierVal?></option>
							<?php endforeach;?>
						</select>
					</div>
				</div>
			</div>
			
			<?=Html::submitButton('搜索',['class'=>"btn btn-primary btn-sm",'id'=>'search'])?>
		</div>	
		</form>
			
	    <table class="table table-condensed table-striped" style="table-layout:fixed;line-height:50px;">
	    <thead>
	    <tr>
	    	<th class="text-nowrap" width="60px;"></th>
        	<th class="text-nowrap"><?=TranslateHelper::t('物流商')?></th>
	        <th class="text-nowrap"><?=TranslateHelper::t('发货类型') ?></th>
	        <th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
	     </tr>
	     </thead>
	     <tbody>
	     <?php if(count($carriers)>0){ ?>
         <?php $rowIndex = 1; 
         foreach($carriers['data'] as $carrier){?>
	     <tr>
	     	<td class="text-nowrap"><?=$rowIndex;?></td>
        	<td class="text-nowrap"><?=$carrier['carrier_name']?></td>
	        <td class="text-nowrap"><?php echo SysCarrier::$carrier_type[$carrier['carrier_type']]?></td>
	        <td class="text-nowrap">
	        <a style="text-decoration: none;" href="<?=Url::to(['/carrier/carrieraccount/create','carrier_code'=>$carrier['carrier_code'],'return_url'=>$return_url])?>"><?php echo TranslateHelper::t('添加账号');?></a> |
	        <a style="text-decoration: none;" href="<?=Url::to(['/carrier/carrieraccount/index','carrier_code'=>$carrier['carrier_code']])?>"><?php echo TranslateHelper::t('账号管理');?></a>
	        </td>
	     </tr>
	     <?php $rowIndex++;}?>
	      <?php }?>
	      
	      
	      <?php
	      	if (empty($_POST['carrier_code_sel']) || ($_POST['carrier_code_sel']=='all'))
			if($carriers['pagination']->getPageCount() == $carriers['pagination']->getPage()+1){
				$rtbArr=SysCarrier::find()->where(' api_class=:api_class ',[':api_class'=>'LB_RTBCOMPANYCarrierAPI'])->asArray()->all();

				if(count($rtbArr)>0){
					echo "<tr><td class='text-nowrap'><span><span class='glyphicon glyphicon-plus' onclick='spreadorder(this);'></span></span></td><td class='text-nowrap'>软通宝(物流商集合)</td><td class='text-nowrap'>货代</td><td class='text-nowrap'></td></tr>";
		?>
         	<?php foreach ($rtbArr as $rtbone){ ?>
			<tr class='rtb_list' style='display:none;'>
				<td class="text-nowrap"></td><td class="text-nowrap"><?=$rtbone['carrier_name'] ?></td>
				<td class="text-nowrap"><?=SysCarrier::$carrier_type[$rtbone['carrier_type']] ?></td>
				<td class="text-nowrap">
					<a style="text-decoration: none;" href="<?=Url::to(['/carrier/carrieraccount/create','carrier_code'=>$rtbone['carrier_code'],'return_url'=>$return_url])?>"><?php echo TranslateHelper::t('添加账号');?></a> |
	        		<a style="text-decoration: none;" href="<?=Url::to(['/carrier/carrieraccount/index','carrier_code'=>$rtbone['carrier_code']])?>"><?php echo TranslateHelper::t('账号管理');?></a>
				</td>
			</tr>         	
         	<?php } ?>
         	<?php
         }



			}
	      ?>
	      
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

<script>
//展开，收缩订单商品
function spreadorder(obj){
	var html = $(obj).parent().html();

	if(html.indexOf('minus')!=-1){
		//当前应该为处理收缩,'-'号存在
		$('.rtb_list').hide();
		$(obj).parent().html('<span class="glyphicon glyphicon-plus" onclick="spreadorder(this);">');
		return false;
	}else{
		//当前应该为处理收缩,'+'号存在
		$('.rtb_list').show();
		$(obj).parent().html('<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);">');
		return false;
	}
}
</script>
