<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use eagle\modules\assistant\config\Cfg;
use eagle\models\sys\SysCountry;
use eagle\models\assistant\OmOrderMessageTemplate;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/lib/require.js");


$this->registerJsFile(\Yii::getAlias('@web')."/js/project/assistant.js", ['depends' => ['yii\web\JqueryAsset']]);


// echo '<pre>';var_dump($country);die;

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
            'active' => '规则统计',
        ]);
?>
<div class="content-wrapper">

    <table class="table-striped table-nobordered">
	    <thead>
	        <tr class="list-firstTr">
	            <th class="text-nowrap col-lg-3">
	            	<?= TranslateHelper::t('第一次催款') ?>
	            </th>
				<?php /**?>
				<th class="text-nowrap col-lg-2">
					<?= TranslateHelper::t('第二次催款') ?>
				</th>
				<th class="text-nowrap col-lg-2">
					<?= TranslateHelper::t('第三次催款') ?>
				</th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('催款范围') ?>
	            </th>
				<?php **/?>
				<th class="text-nowrap col-lg-1">
					<?= TranslateHelper::t('订单金额') ?>
				</th>
	            <th class="text-nowrap col-lg-3">
	            	<?= TranslateHelper::t('适用国家') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('催款单数') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('催款成功单数') ?>
	            </th>
				<th class="text-nowrap col-lg-1">
					<?= TranslateHelper::t('催款成功金额') ?>
				</th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('催款成功率') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('状态') ?>
	            </th>
		    </tr>
	    </thead>
	    <tbody>
	        <?php 
	        $bgColor = [
	        	'启用'=>'transparent',
	        	'停用'=>'#ddd',
	        	'历史'=>'#ccc'
	        ];
	        // var_dump(\Yii::$app->user->identity->getParentUid());die;
			$maxCountries =Cfg::VIEW_RULE_COUNTRY_MAX_LENGTH;
	        	// echo "<pre>";print_r($info2);die;
	        foreach($info2 as $info):

				$c = SysCountry::getCountriesName(explode(',',$info['country']),'zh');
				if($info['country'] == '*'){
					$c[]  = '全部国家';
				}
				$viewCountry = implode('、',array_slice($c,0,$maxCountries)).(count($c)>$maxCountries?'...':'');
	        ?>
	        <tr style="background-color:<?= $bgColor[$info['status']] ?>">
	            <td>催款时间：<?= $info['timeout']/60 ?> 分钟<br>
					催款留言：<?php $template = OmOrderMessageTemplate::findOne($info['message_content']);
					if(isset($template)){
						print_r($template->template_name);
					}else{
						echo '未设置';
					}?><br>
					<a show-model="show" <?= HtmlHelper::showtpl(['m_id'=>$info['message_content']]) ?>>查看留言</a>
				</td>
				<?php /**?>
				<td>催款时间：<?= $info['timeout2']/3600 ?> 小时<br>
					催款留言：<?php $template = OmOrderMessageTemplate::findOne($info['message_content2']);
					if(isset($template)){
						print_r($template->template_name);
					}else{
						echo '未设置';
					}?><br>
					<a show-model="show" <?= HtmlHelper::showtpl(['m_id'=>$info['message_content2']]) ?>>查看留言</a>
				</td>
				<td>催款时间：<?= $info['timeout3']/3600 ?>小时<br>
					催款留言：<?php $template = OmOrderMessageTemplate::findOne($info['message_content3']);
					if(isset($template)){
						print_r($template->template_name);
					}else{
						echo '未设置';
					}?><br>
					<a show-model="show" <?= HtmlHelper::showtpl(['m_id'=>$info['message_content3']]) ?>>查看留言</a>
				</td>
	            <td class="text-nowrap"><?= $info['expire_time']/3600 ?> h</td>
				<?php **/?>
	            <td class="text-nowrap"><?= $info['money'] ?></td>
	            <td text-overflow="3"><?= $viewCountry ?></td>
	            <td class="text-nowrap"><?= $info['count'] ?></td>
	            <td class="text-nowrap"><?= $info['successCount'] ?></td>
				<td class="text-nowrap"><?= $info['total'] ?></td>
	            <td class="text-nowrap text-center"><?= $info['count']?(sprintf('%u %%',$info['successCount']/$info['count']*100)):'-' ?></td>
	            <td><?= $info['status'] ?></td>
	        </tr>
	        <?php endforeach;?>
	    </tbody>
    </table>
    <div id="pager-group" >
	        <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages, 'pageSizeOptions'=>$pages->pageSizeLimit, 'class'=>'btn-group dropup']);?>
        <div class="btn-group" style="width: 49.6%;text-align: right;">
        	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
    	</div>
    </div>
</div>

	<!-- <div class="panel panel-primary">
		<div class="panel-heading">图表分析</div>
		<div class="panel-body">
			<div id="charts" style="width:100%;height:400px;">
				

			</div>

		</div>

	</div> -->

