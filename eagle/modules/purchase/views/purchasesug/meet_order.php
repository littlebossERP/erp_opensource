<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerCssFile ( $baseUrl . "/css/purchase/purchase.css" );
$this->registerJsFile($baseUrl."/js/project/purchase/suggestion/list.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/suggestion/printSug.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/purchase_link_list.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("purchaseSug.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->title = TranslateHelper::t ( '采购管理' );
$this->params ['breadcrumbs'] [] = $this->title;
?>

<style>
.create_or_edit_purchase_win .modal-dialog{
	width: 900px;
	max-height: 650px;
	overflow-y: auto;	
}
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
/*
th{
	height: 20px;
  	padding: 3px;
  	vertical-align: middle;
	background-color: #d9effc;
	font: bold 12px SimSun,Arial;
	color: #374655;
}
td{
  	vertical-align: middle;
	word-break:break-word;
}
*/
</style>

<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/purchase/_menu') ?>
	<!-- 右侧内容区域 -->
	<div class="content-wrapper" >
		<FORM action="<?= Url::to(['/purchase/purchasesug/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left;">
			<div style="width: 100%;float: left;margin-bottom: 10px;">
				<div class="div-input-group" title="<?= TranslateHelper::t('按销售平台过滤结果') ?>">
					<div style="float:left;" class="input-group">
						<SELECT name="order_source" value="" class="eagle-form-control" style="width:150px;margin:0px">
							<OPTION value="" <?=(empty($_GET['order_source']))?" selected ":'' ?>><?= TranslateHelper::t('销售平台') ?></OPTION>
								<?php foreach($order_source as $key=>$value){
									echo "<option value='".$key."'";
									if(isset($_GET['order_source']) && $_GET['order_source']==$key) echo " selected ";
									echo ">".$value."</option>";						
								} ?>
						</SELECT>
					</div>
				</div>

				<div class="div-input-group" style="float: left;margin-left:5px;">
					<div style="float:left;" class="">
						<input name="start_date" id="purchaselist_startdate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('平台下单日期从 此日期后')?>" 
							value="<?= (empty($_GET['start_date'])?"":$_GET['start_date']);?>" style="width:150px;margin:0px;height:28px;float:left;"/>
						<input name="end_date" id="purchaselist_enddate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('至 此日期前')?>" 
							value="<?= (empty($_GET['end_date'])?"":$_GET['end_date']);?>" style="width:150px;margin:0px;height:28px;float:left;"/>
					</div>
				</div>
				
				<div class="div-input-group" title="<?= TranslateHelper::t('订单状态') ?>">
					<div style="float:left;" class="input-group">
						<SELECT name="order_status" class="eagle-form-control" style="width:150px;margin:0px">
							<OPTION value="0" <?=(empty($_GET['order_status']) || $_GET['order_status'] == 0)?" selected ":'' ?>><?= TranslateHelper::t('订单状态') ?></OPTION>
							<OPTION value="1" <?=(!empty($_GET['order_status']) && $_GET['order_status'] == 1)?" selected ":'' ?>><?= TranslateHelper::t('已付款后但未发货') ?></OPTION>
							<OPTION value="2" <?=(!empty($_GET['order_status']) && $_GET['order_status'] == 2)?" selected ":'' ?>><?= TranslateHelper::t('已付款或已虚拟发货') ?></OPTION>
						</SELECT>
					</div>
				</div>
				
				<div class="div-input-group" style="float: left;margin-left:5px;">
					<div style="float:left;">
						<input name="search_sku" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('输入需要查询的SKU，多个之间用;隔开')?>" title="<?= TranslateHelper::t('根据输入的SKU查询') ?>"  
							value="<?php if(!empty($_GET['search_sku'])) echo $_GET['search_sku'] ?>" style="width:300px;margin:0px;height:28px;float:left;"/>

						<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
							<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
						</button>
						<button type="button" id="btn_clear" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
							<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
						</button>
					</div>
				</div>
			</div>
			<div style="float:left;width:100%">
				<div class="div-input-group alert alert-warning" style="float: left;margin-left:5px;" >
					<div style="float:left;">
						<label for="group_by_sku"><?= TranslateHelper::t('以SKU为商品标识') ?></label>
						<input name="group_by" id="group_by_sku" type="radio" value="sku" <?=(empty($_REQUEST['group_by']) || $_REQUEST['group_by']=='sku')?'checked':'' ?>/>
						<label for="group_by_name"><?= TranslateHelper::t('以商品名为商品标识') ?></label>
						<input name="group_by" id="group_by_name" type="radio" value="product_name" <?=(!empty($_REQUEST['group_by']) && $_REQUEST['group_by']=='product_name')?'checked':'' ?> />

						
					</div>
				</div>
				
				<!-- 功能按钮  -->
				<div style="">
					<div style="float:left;margin-left:100px;margin-top:10px;">
						<button type="button" class="btn btn-primary" id="print_meet_order" disabled="disabled" style="border-style: none;"><?= TranslateHelper::t('打印见单采购商品表') ?></button>
					</div>
				</div>
			</div>
		</FORM>
		
				<!-- table -->
		<div class="meet_order_list" style="width:100%;float:left;">
			<table cellspacing="0" cellpadding="0" class="table table-hover" style="width:100%;float:left;font-size: 12px;">
				<tr class="list-firstTr">
					<th width="20px" tag="ck" title="<?=TranslateHelper::t('全选') ?>"><input type="checkbox" id="select_all" ></th>
					<th width="150px" tag="sku" style="text-align:center!important;">SKU</th>
					<th width="100px" tag="img" style="text-align:center!important;"><?=TranslateHelper::t('图片') ?></th>
					<th width="250px" tag="name" style="text-align:center!important;"><?=TranslateHelper::t('商品名称') ?></th>
					<th width="100px" tag="product_attributes" style="text-align:center!important;"><?=TranslateHelper::t('商品属性') ?></th>
					<th width="200px" tag="prod_name_ch" style="text-align:center!important;"><?=TranslateHelper::t('中文配货名') ?></th>
					<th width="70px" tag="qty" style="text-align:center!important;"><?=TranslateHelper::t('需求数量') ?></th>
					<th width="200px" tag="supplier" style="text-align:center!important;"><?=TranslateHelper::t('供应商') ?></th>
				</tr>
				<?php foreach($data['items'] as $index=>$purchase):?>
					<?php 
						$product_info=[];
						if(isset($data['prod_info'][$purchase['sku']])){
							$product_info = $data['prod_info'][$purchase['sku']];
						}
					?>
					<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
						<td style="text-align:left;vertical-align: middle;"><input type="checkbox" class="select_one" name="row_id" value="<?=$index?>" ></td>
						<td style="vertical-align: middle;" value="<?=$purchase['sku'] ?>"><?=empty($product_info['purchase_link']) ? $purchase['sku'] : '<a href="'.$product_info['purchase_link'].'" target="_blank" class="purchase_link_list_show" purchase_link_json=\''.$product_info['purchase_link_list'].'\'>'.$purchase['sku'].'</a>' ?></td>
						<td style="text-align:center!important;vertical-align: middle;" value="<?=empty($purchase['photo_primary'])?'/images/batchImagesUploader/no-img.png':$purchase['photo_primary'] ?>">
							<img src="<?=empty($purchase['photo_primary'])?'/images/batchImagesUploader/no-img.png':$purchase['photo_primary'] ?>" style="width:100px;height:100px"/>
						</td>
						<td style="text-align:center!important;vertical-align: middle;"><?=$purchase['product_name'] ?></td>
						<td style="text-align:center!important;vertical-align: middle;"><?=$purchase['product_attributes'] ?></td>
						<td style="text-align:center!important;vertical-align: middle;"><?=empty($product_info['prod_name_ch'])?'<span style="color:#D1D2D4">(SKU还没于商品模块创建信息)</span>':$product_info['prod_name_ch'] ?></td>
						<td style="text-align:center!important;vertical-align: middle;"><?=$purchase['total_quantity'] ?></td>
						<td style="text-align:center!important;vertical-align: middle;">
							<?php 
								$supplier_str='';
								if(!empty($product_info)){
									if(isset($suppliers[$product_info['supplier_id']]))
										$supplier_str.=$suppliers[(string)$product_info['supplier_id']].'<br>';
									else 
										$supplier_str.="(供应商未设置)<br>";
									if(isset($product_info['purchase_price']))
										$supplier_str.=$product_info['purchase_price'].'¥<br>';
									else 
										$supplier_str.="(采购价未设置)<br>";
									if(!empty($product_info['purchase_link']))
										$supplier_str.='<a href="'.$product_info['purchase_link'].'" target="_blank">'.$product_info['purchase_link'].'</a>';
									else 
										$supplier_str.="(采购链接未设置)";
								}else{
									$supplier_str.="(供应商未设置)<br>";
									$supplier_str.="(采购价未设置)<br>";
									$supplier_str.="(采购链接未设置)";
								}
								echo $supplier_str;
							?>
						</td>
					</tr>
				<?php endforeach;?>
			</table>
		</div>

		<?php if($data['pagination']):?>
		<div id="pager-group">
			<?= \eagle\widgets\SizePager::widget(['pagination'=>$data['pagination'] , 'pageSizeOptions'=>array( 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
			<div class="btn-group" style="width: 49.6%;text-align: right;">
				<?=\yii\widgets\LinkPager::widget(['pagination' => $data['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
		<?php endif;?>

		<!-- Modal -->
		<div class="operation_result"></div>
		<!-- /.modal-dialog -->

	</div> 
	
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>


