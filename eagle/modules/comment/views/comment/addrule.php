<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['yii\web\JqueryAsset']]);

?>
<style>
.left_menu a{
	color: #62778B;
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
			'active' => '自动好评',
		]);
?>
<h4 style="margin-left:100px;">自动好评规则</h4>
<form action="" method="post" class="form-horizontal form-standard" id="addrule-form">
	<div class="form-group">
		<label class="col-sm-2 col-lg-2 col-xs-2 col-md-2 control-label">对纠纷订单好评</label>

		<div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
			<div class="checkbox">
				<label>
					<input type="radio" name="is_dispute" value="1" <?= $rule->is_dispute===1?'checked':''?>  />是
				</label>
				<label>
					<input type="radio" name="is_dispute" value="0" <?= $rule->is_dispute!==1?'checked':''?>  />否
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-2 col-lg-2 col-xs-2 col-md-2 control-label">关联店铺
		<span qtipkey="comment_relevant_site_tip"></span>
		</label>

		<div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
			<div class="checkbox ">
				<?= HtmlHelper::checkboxGroup('shop_id',$aliexpressuser,$rule->selleruseridlist,['className'=>'col-lg-2']) ?>
			</div>
		</div>
	</div>
	<!-- <div class="form-group">
		<label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
			语言版本
		</label>
		<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2">
			<select id="comment_language" class="form-control" style="width:100px;">
				<option value="en">英语</option>
				<option value="fr">法语</option>
				<option value="de">德语</option>
			</select>
		</div>
	</div> -->
	<div class="form-group">
		<label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
			推荐模板
		</label>
		<div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
			<?php 
			echo HtmlHelper::select('content',[]);
			 ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
			好评内容
		</label>
		<div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
			<!-- <textarea name="content" id="addrule-content" class="form-control" rows="8" required <?= $rule->content?'':'readonly'?>><?= $rule->content?></textarea> -->
			<textarea name="content" id="addrule-content" class="form-control" rows="8" required <?= $rule->content?>><?= $rule->content?></textarea>
			<div class="alert alert-danger" role="alert" id="add-rule-put-result" style="display:none"></div>
		</div>
	</div>
	<div class="form-group">
		<label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
			匹配国家
			<span qtipkey="comment_sel_countries_tip"></span>
		</label>
		<div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
		         
			<?= HtmlHelper::selCountries('countries',$rule->countrylist);?>
		</div>
	</div>
	<div class="form-group">
		<div class="col-xs-offset-3 col-sm-offset-3 col-md-offset-3 col-lg-offset-3">
			<input class="btn btn-success " type="submit" value="<?= $rule->id?'保存规则':'新增规则'?>" />
			<a href="./rule" class="btn btn-default">取消</a>
		</div>
	</div>
	<input type="hidden" name="ruleid" value="<?= $rule->id ?>">
</form>

