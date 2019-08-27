<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;
use yii\data\Sort;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';

// $this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/shippingRules.js");
// $this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/rule/rule.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/warehouseconfig/overseawarehouselist.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("overseaWarehouseList.init();" , \yii\web\View::POS_READY);

?>
<style>
.main_top{
	font-family: 'Applied Font Regular', 'Applied Font';
	font-size:13px;
	height:48px;
	line-height:22px;
	margin:10px 0;
	padding:2px 10px;
	color:black;
}
.main_top>div{
	float:left;
	margin-right:12px;
}
.main_top select{
	height:22px;
	line-height:22px;
}
.title-btn{
	width:100px;
	height:26px;
	line-height:26px;
	padding:0;
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
		<div class="main_top">
			<div><b>仓库名 : </b><?= Html::dropDownList('warehouse_name_rulelist',@$_GET['warehouse_id'],['-1'=>'']+$warehouseIdNameMap,['id'=>'carrier_name','class'=>'iv-input'])?></div>
			<div><b>仓库类型 : </b><?= Html::dropDownList('warehouse_type_rulelist',@$_GET['warehouse_type'],['-1'=>'']+array(0=>'自营仓库',1=>'第三方仓库'),['class'=>'iv-input'])?></div>
			<div><b>状态 : </b><?= Html::dropDownList('warehouse_state_rulelist',@$_GET['warehouse_state'],['-1'=>'']+array(1=>'启用',0=>'关闭'),['class'=>'iv-input'])?></div>
			<div><?= Html::input('button','','筛选',['class'=>'iv-btn btn-search btn-xs title-btn','onclick'=>'searchWarehouseRuleListBtn()'])?></div>
		</div>
		<div>
			<table class="table text-center showtable" style="table-layout:fixed;line-height:50px; margin:0;">
				<thead>
					<tr>
						<th><?= TranslateHelper::t('规则名')?></th>
						<th><?= TranslateHelper::t('仓库名')?></th>
						<th><?= TranslateHelper::t('仓库类型')?></th>
						<th><?= TranslateHelper::t('仓库地址')?></th>
						<th><?= TranslateHelper::t('状态')?></th>
						<th><?= TranslateHelper::t('分配规则设置')?></th>
					</tr>
				</thead>
				<tbody>
					<?php 
						foreach ($warehouseMatchRuleInfo['data'] as $rule){
					?>
					<tr data='<?= $rule['id']?>'>
						<td><?= TranslateHelper::t($rule['rule_name']);?></td>
						<td><?= TranslateHelper::t($rule['name']);?></td>
						<td><?= TranslateHelper::t($rule['is_oversea'] == 0 ? '自营仓库' : '第三方仓库');?></td>
						<td><?= TranslateHelper::t($rule['address_street']);?></td>
						<td><?= TranslateHelper::t($rule['is_active'] == 0 ? '关闭': '启用') ?></td>
						<td>
							<a class='btn btn-xs' onclick="editWarehouseRule(<?=$rule['id'] ?>,'<?=$rule['warehouse_id'] ?>')">修改分配规则</a>
						</td>
					</tr>
					<?php 
					}?>
				</tbody>
			</table>
			<?php if($warehouseMatchRuleInfo['pagination']):?>
			<div id="pager-group">
			    <?= \eagle\widgets\SizePager::widget(['pagination'=>$warehouseMatchRuleInfo['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
			    <div class="btn-group" style="width: 49.6%;text-align: right;">
			    	<?=\yii\widgets\LinkPager::widget(['pagination' => $warehouseMatchRuleInfo['pagination'],'options'=>['class'=>'pagination']]);?>
				</div>
			</div>
			<?php endif;?>
		</div>
	</div>

<!-- modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
</div>