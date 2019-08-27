<?php 
use eagle\modules\comment\config\CommentConfig;

use eagle\models\sys\SysCountry;
use eagle\modules\comment\dal_mongo\CommentRule;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\comment\helpers\CommentHelper;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
//$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);
//$this->registerJsFile(\Yii::getAlias('@web')."/js/project/commment/comment/jquery.pagination.js",['depends'=>['yii\web\JqueryAsset']]);
//$this->registerCssFile(\Yii::getAlias('@web')."css/pagination.css");
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
		'订单评价' =>[
			'icon' => 'iconfont icon-stroe', 
			'items'=>[
				'自动好评' => [
					'url' => '/comment/comment/rule-v2',
				],
				'等待您留评'=>[
					'url' => '/comment/comment/index-v2',
				],
				'评价模板' =>[
					'url' => '/comment/comment/template-v2',
				],
			],
		],
		'好评记录' =>[
			'icon' => 'iconfont icon-stroe',
			'items'=>[
				'好评记录'=>[
					'url' => '/comment/comment/log-v2',
				],
			]
		],
	];
	echo $this->render('//layouts/new/left_menu',[
			'menu'=>$menu,
			'active'=>'自动好评',
		]);
?>


<div class="table-action clearfix">
	<div class="pull-left">
		<a href="addrule-v2?add=1" class="iv-btn btn-important " style="color:white;background-color:#2DCC70">添加好评规则</a>
	</div>
</div>
<table class="table-striped table-nobordered">
	<thead>
		<tr>
			<th class="col-lg-1">评价星级</th>
			<th class="col-lg-3">评价订单国家</th>
			<th class="col-lg-3">评价店铺</th>
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
			$c = SysCountry::getCountriesName($rule['countryList'],'zh');
			$viewCountry = implode('、',array_slice($c,0,$maxCountries)).(count($c)>$maxCountries?'...':'');
			if(!isset($rule['score'])){
				$rule['score']=-1;
			}
			switch ($rule['score']){
				case 5:
					$score="五星";
					break;
				case 4:
					$score="四星";
					break;
				case 3:
					$score="三星";
					break;
				case 2:
					$score="两星";
					break;
				case 1:
					$score="一星";
					break;
				default:
					$score="";
			}
				
		?>
		<tr>
			<td><?=$score ?></td>
			<td style="padding-right: 10px"><?= $viewCountry ?></td>
			<td style="padding-right: 10px"><?= implode('、',$rule['sellerIdList'])?></td>
			<td class="text-success"><?= $rule['isCommentIssue']?'是':'否'?></td>
			<td><?= HtmlHelper::SwitchButtonMG('isUse',new CommentRule(),$rule,[1,0],['tracker-key'=>'AliHaoPing','actionKey'=>"iosswitchmg"]) ?></td>
			<td>
				<span class="glyphicon glyphicon-edit icon-button" title="编辑" onclick='location.href="<?= Url::to(["/comment/comment/addrule-v2","id"=>$rule['_id']])?>"'></span>
				<span class="glyphicon glyphicon-trash icon-button" title="删除" onclick="deleteRuleV2('<?= $rule['_id']?>')"></span>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?= HtmlHelper::Pagination($showpage) ?>

