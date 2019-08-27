<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use eagle\modules\assistant\config\Cfg;
use eagle\models\sys\SysCountry;
use eagle\models\assistant\OmOrderMessageTemplate;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/assistant.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/totop.js", ['depends' => ['yii\web\JqueryAsset']]);
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
        '催款设置' => [
            'icon' => 'iconfont icon-shezhi',
            'items'=> [
                '催款规则列表'=>[
                    'url'=> '/assistant/rule/list',
                ],
                '催款模板设置'=>[
                    'url'=>'/assistant/template/list',
                ]
            ],
        ],
        '催款记录' => [
            'icon' => 'iconfont icon-liebiao',
            'items'=> [
                '催款记录' =>[
                    'url'=> '/assistant/due/list',
                ],
            ],
        ],
        '统计信息' => [
            'icon' => 'iconfont icon-liebiao',
            'items' => [
                '店铺统计' => [
                    'url' => '/assistant/due/list',
                ],           
                '规则统计' => [
                    'url' => '/assistant/due/dueinfo',
                ]
            ],
        ],
    ];

    echo $this->render('//layouts/new/left_menu',[
            'menu' => $menu,
            'active' => '催款规则列表',
        ]);
?>
<div class="content-wrapper">
	<div class="panel panel-default">
		<div class="panel-heading">
			<p class="text-danger" style="color:red;margin:10px;">温馨提示：对于已经与买家留言沟通过的订单，系统不会对其催款。由于巴西Boleto付款匹配不到，建议手动催付，系统默认不勾选巴西，如需请手动勾选。</p>
		</div>
		<table class="table-striped table-nobordered">
				<tr>
					<th><?= TranslateHelper::t('店铺名称') ?></th>
					<th><?= TranslateHelper::t('平台') ?></th>
					<th><?= TranslateHelper::t('状态') ?></th>
					<th><?= TranslateHelper::t('操作') ?></th>
				</tr>
				<?php foreach($shops as $shop):?>
				<tr>
					<td class="text-nowrap"><?= $shop->dp_shop_id ?></td>
					<td class="text-nowrap"><?= $shop->platform ?></td>
					<td><?= $shop->enable_status==2?'启用':'停用' ?></td>
					<td><?= HtmlHelper::SwitchButton('enable_status',$shop,[2,1],['click-confirm'=>'您确定要启用/停用该店铺的自动催款功能吗？','tracker-key'=>'AliCuikuan']) ?></td>
				</tr>
				<?php endforeach;?>
		</table>
		<div id="pager-group">
			<?= \eagle\widgets\SizePager::widget(['pagination'=>$showpages, 'pageSizeOptions'=>$showpages->pageSizeLimit, 'class'=>'btn-group dropup']);?>
			<div class="btn-group" style="width: 49.6%;text-align: right;">
				<?=\yii\widgets\LinkPager::widget(['pagination' => $showpages,'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
		<!-- <button class="btn btn-success" data-modal="add" className="largeModal"><?=TranslateHelper::t('添加催款规则') ?></button> -->
		<div class="table-action clearfix">
			<div class="pull-right">
				<a href="#" data-modal="add" className="largeModal" class="iv-btn btn-important btn-spacing-middle" style="color:white;"><?= TranslateHelper::t('添加催款规则') ?></a>
			</div>
		</div>
	    <table class="table-striped table-nobordered">
	        <tr class="list-firstTr">
				<?php /**?>
				<th class="text-nowrap col-lg-1">
					<?  TranslateHelper::t('催款范围') ?>
				</th>
				<?php **/?>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('订单金额') ?>
	            </th>
				<th class="text-nowrap col-lg-2">
					<?= TranslateHelper::t('适用国家') ?>
				</th>
				<th class="text-nowrap col-lg-2">
					<?= TranslateHelper::t('第一次催款') ?>
				</th>
				<?php /**?>
				<th class="text-nowrap col-lg-2">
					<?  TranslateHelper::t('第二次催款') ?>
				</th>
				<th class="text-nowrap col-lg-2">
					<?  TranslateHelper::t('第三次催款') ?>
				</th>
				<?php **/?>
	            <th class="text-nowrap col-lg-1">
	            	<?=$sort->link('is_active',['label'=>TranslateHelper::t('启用状态')]) ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('操作') ?>
	            </th>
		    </tr>
			<?php

			$maxCountries =Cfg::VIEW_RULE_COUNTRY_MAX_LENGTH;
			foreach($rules as $rule):
			// 只显示5个国家
			$c = SysCountry::getCountriesName(explode(',',$rule->country),'zh');
				if($rule->country == '*'){
					$c[]  = '全部国家';
				}
			$viewCountry = implode('、',array_slice($c,0,$maxCountries)).(count($c)>$maxCountries?'...':'');
			?>
	        <tr>
				<?php /**?>
				<td class="text-nowrap"><?= $rule->expire_time/3600 ?> 小时</td>
				<?php **/?>
				<td><?= ($rule->min_money?'$'.$rule->min_money:'$0').' - '.($rule->max_money?'$'.$rule->max_money:'不限') ?></td>
				<td class="" text-overflow="3">
					<?= $viewCountry ?>
				</td>
	            <td class=""  >
					催款时间：<?= $rule->timeout/60 ?> 分钟<br>
					催款留言：<?php $template = OmOrderMessageTemplate::findOne($rule->message_content);
					if(isset($template)){
						print_r($template->template_name);
					}else{
						echo '未设置';
					}
					?><br>
					<a class="btn btn-link" show-model="show" <?= HtmlHelper::showtpl(['m_id'=>$rule->message_content]) ?>>查看留言</a>
				</td>
				<?php /**?>
				<td class="" >
					催款时间：<?= $rule->timeout2/3600 ?> 小时<br>
					催款留言：<?php $template = OmOrderMessageTemplate::findOne($rule->message_content2);
					if(isset($template)){
						print_r($template->template_name);
					}else{
						echo '未设置';
					}
					?><br>
					<a class="btn btn-link" show-model="show" <?= HtmlHelper::showtpl(['m_id'=>$rule->message_content2]) ?>>查看留言</a>
				</td>
				<td class="" >
					催款时间：<?= $rule->timeout3/3600 ?> 小时<br>
					催款留言：<?php $template = OmOrderMessageTemplate::findOne($rule->message_content3);
					if(isset($template)){
						print_r($template->template_name);
					}else{
						echo '未设置';
					}
					?><br>
					<a class="btn btn-link" show-model="show" <?= HtmlHelper::showtpl(['m_id'=>$rule->message_content3]) ?>>查看留言</a>
				</td>
				<?php **/?>
	            <td class="text-nowrap"><?= $rule->is_active==2?'启用':'停用'?></td>
	            <td class="text-nowrap">
					<div class="btn-group">
						<?= HtmlHelper::SwitchButton('is_active',$rule,[2,1],['click-confirm'=>TranslateHelper::t('您确定要启用/停用此催款规则吗？')]) ?>
						<a class="btn btn-default btn-sm" data-modal="add" className="largeModal" <?= HtmlHelper::params(['id'=>$rule->rule_id]) ?>>
							<span class="glyphicon glyphicon-edit icon-button"></span>
						</a>
						<button click-confirm="此操作不可逆" ajax-request="delete" ajax-method="post" ajax-data="rule_id=<?=$rule->rule_id ?>" class="btn btn-danger btn-sm">
							<span class="glyphicon glyphicon-remove icon-button"></span>
						</button>
					</div>
	            </td>
	        </tr>
	        <?php endforeach;?>
	    </table>
	    <div id="pager-group">
		        <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages, 'pageSizeOptions'=>$pages->pageSizeLimit, 'class'=>'btn-group dropup']);?>
	        <div class="btn-group" style="width: 49.6%;text-align: right;">
	        	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
	    	</div>
	    </div>

	</div>
</div>
