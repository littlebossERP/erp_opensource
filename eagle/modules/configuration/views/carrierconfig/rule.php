<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;
use yii\data\Sort;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$tmp_js_version = '2.22';

$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/shippingRules.js?v=".$tmp_js_version);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/rule/rule.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."js/project/util/select_country.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
.main_top{
	font-family: 'Applied Font Regular', 'Applied Font';
	font-size:13px;
	height:48px;
	line-height:22px;
	margin:5px 0;
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

.table td, .table th {
    text-align: center;
    word-break: break-all;
}

.rule_table > tbody > tr{
	border-top: 1px solid #b9d6e8;
	border-bottom: 1px solid #b9d6e8;
}

</style>
<?php echo $this->render('../leftmenu/_leftmenu');?>
<?php 
//判断子账号是否有权限查看
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>
	<?= empty($codes) ? "" : Html::hiddenInput('carrier_code',$codes)?>
	<div>
		<div style='margin-bottom: 10px;'>
			<?php if(!empty($open_carriers)){?>
				<a class="iv-btn btn-search title-button" onclick="$.modal({url:'/configuration/carrierconfig/shippingrules',method:'get',data:{id:'0',sid:'0'}},'添加运输服务匹配规则',{footer:false,inside:false}).done(function($modal){$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});});">添加运输服务匹配规则</a>
			<?php }else{?>
				<a target="_blank" href="/configuration/carrierconfig/index" style="font-size: 15px; margin-right:30px; color: red; ">未曾开启运输服务，请点击这里跳转！</a>
			<?php }?>
			<!-- <a class="iv-btn btn-search title-button" onclick="<?= "$.openModal('/configuration/carrierconfig/shippingrules',{id:'0',sid:'0'},'添加运输服务匹配规则','get')";?>">添加运输服务匹配规则</a>-->
			<a target="_blank" href="http://www.littleboss.com/word_list_188_134.html" style="font-size: 15px;">查看匹配运输服务帮助文档</a>
			
			<span style='float: right;color: red;'>提示：按住鼠标拖动到想要的顺序位置后释放鼠标，即可完成对优先级的设置</span>
		</div>
		
		<?php if(!empty($open_carriers)){?>
		<div>
			<table class='table table-condensed table-bordered rule_table'>
				<thead>
					<tr>
						<th><?= TranslateHelper::t('顺序')?></th>
						<th><?= TranslateHelper::t('规则名称')?></th>
						<th><?= TranslateHelper::t('物流商')?></th>
						<th><?= TranslateHelper::t('运输服务别名')?></th>
						<th><?= TranslateHelper::t('仓库')?></th>
						<th><?= TranslateHelper::t('分配规则')?></th>
					</tr>
				</thead>
				<tbody class="sortable_r" style='cursor: move;'>
					<?php
					if(count($rules['data']) > 0){
						$tmp_i1 = 1;
						foreach ($rules['data'] as $rule){
					?>
					<tr data='<?=$rule['id']?>' class='item <?=(($rule['is_active'] == 0) ? 'warning' : '') ?>' id='<?=$rule['id']?>' sort='<?=$tmp_i1 ?>'>
						<td><?=$tmp_i1 ?></td>
						<td><?= TranslateHelper::t($rule['rule_name']);?></td>
						<td><?= TranslateHelper::t($rule['carrier_name']);?></td>
						<td><?= TranslateHelper::t($rule['service_name']);?></td>
						<td><?= @$warehousesAll[$rule['proprietary_warehouse_id']]; ?></td>
						<td>
							<?php
							if($rule['is_active'] == 0){
								echo '<span style="color: red;" class="btn btn-xs">停用中</span>';
							}
							?>
							
							<a class='btn btn-xs' onclick="ruleJS.openRule(this)">修改</a>
							<a class='btn btn-xs' onclick="ruleJS.delRule(this)">删除</a>
						</td>
					</tr>
					<?php
							$tmp_i1++;
						}
					}				
					?>
				</tbody>
			</table>
		</div>
		<?php }?>
	</div>

