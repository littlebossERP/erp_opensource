<?php 
use eagle\modules\comment\config\CommentConfig;

use eagle\models\sys\SysCountry;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\comment\helpers\CommentHelper;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/commment/comment/jquery.pagination.js",['depends'=>['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."css/pagination.css");
?>
<style>
	.icon-button {
		display:inline-block;
		height:20px;
		width:25px;
		cursor: pointer;
		font-size: 13px;
		font-weight:bold;
	}
	.left_menu a{
		color: #62778B;
	}
	.right_content{
		padding-top: 10px;
	}
	.left_menu .down{
		cursor: pointer;
	}
</style>
<?php 
	$menu = [
		'自动好评' =>[
			'icon' => 'iconfont icon-stroe', 
			'items'=>[
				'自动好评' => [
					'url' => '/comment/comment/rule',
				],
				'好评模板' =>[
					'url' => '/comment/comment/template',
				],
			],
		],
		'手动批量好评' =>[
			'icon' => 'iconfont icon-shezhi',
			'items'=> [
				'手动批量好评'=>[
					'url' => '/comment/comment/index',
				],
			],
		],
		'好评记录' =>[
			'icon' => 'iconfont icon-stroe',
			'items'=>[
				'好评记录'=>[
					'url' => '/comment/comment/log',
				],
			]
		],
	];
	echo $this->render('//layouts/new/left_menu',[
			'menu'=>$menu,
			'active'=>'自动好评',
		]);
?>
<div class="iv-alert alert-remind">
	速卖通接口变更导致评价出现问题，我们正在加紧修复
</div>
<table class="table-striped table-nobordered" style="margin-bottom:40px;">
	<thead>
		<tr>
			<th class="col-lg-3">速卖通店铺名称</th>
			<th class="col-lg-9">启用状态</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($enables_obj as $enable_obj):?>
		<tr>
			<td><?= $enable_obj->selleruserid?></td>
			<td><?= HtmlHelper::SwitchButton('enable_status',$enable_obj,[1,0],['tracker-key'=>'AliHaoPing','tracker-remark'=>'shop']);?></td>
		</tr>
		<?php endforeach;?>
	</tbody>
</table>
<?= HtmlHelper::Pagination($page) ?>


<div class="table-action clearfix">
	<div class="pull-right">
		<a href="addrule" class="iv-btn btn-important btn-spacing-middle" style="color:white;">添加好评规则</a>
	</div>
</div>
<table class="table-striped table-nobordered">
	<thead>
		<tr>
			<th class="col-lg-3">好评内容</th>
			<th class="col-lg-3">适用国家</th>
			<th class="col-lg-2">关联店铺</th>
			<th class="col-lg-2">对纠纷订单好评</th>
			<th class="col-lg-1">启用状态</th>
			<th class="col-lg-1">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php
		
		$maxCountries = CommentConfig::VIEW_RULE_COUNTRY_MAX_LENGTH;
		foreach($rules as $rule):
			// 只显示10个国家
			$c = SysCountry::getCountriesName($rule->countrylist,'zh');
			$viewCountry = implode('、',array_slice($c,0,$maxCountries)).(count($c)>$maxCountries?'...':'');
		?>
		<tr>
			<td><div text-overflow="2"><?=$rule->content ?></div></td>
			<td><?= $viewCountry ?></td>
			<td><?= implode('、',$rule->selleruseridlist)?></td>
			<td class="text-success"><?= $rule->is_dispute?'是':'否'?></td>
			<td><?= HtmlHelper::SwitchButton('is_use',$rule,[1,0],['tracker-key'=>'AliHaoPing']) ?></td>
			<td>
				<span class="glyphicon glyphicon-edit icon-button" title="编辑" onclick='location.href="<?= Url::to(["/comment/comment/addrule","id"=>$rule->id])?>"'></span>
				<span class="glyphicon glyphicon-trash icon-button" title="删除" onclick="deleteRule('<?= $rule->id?>')"></span>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?= HtmlHelper::Pagination($showpage) ?>

