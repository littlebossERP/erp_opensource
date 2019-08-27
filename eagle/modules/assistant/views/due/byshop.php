<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;

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
                    'url' => '/assistant/due/byshop',
                ],           
                '规则统计' => [
                    'url' => '/assistant/due/dueinfo',
                ]
            ],
        ],
    ];

    echo $this->render('//layouts/new/left_menu',[
            'menu' => $menu,
            'active' => '店铺统计',
        ]);
?>
<div class="content-wrapper">
    <table class="table-striped table-nobordered">
    	<thead>
	        <tr class="list-firstTr">
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('店铺') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('平台') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('催款单数') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('催款成功单数') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('催款成功率') ?>
	            </th>
				<th class="text-nowrap col-lg-2">
					<?= TranslateHelper::t('成功催款金额') ?>
				</th>
		    </tr>
	    </thead>
	    <tbody>
	        <?php foreach($info1 as $shop_id=>$info):?>
	        <tr>
	            <td class="text-nowrap"><?= $shop_id ?></td>
	            <td class="text-nowrap"><?= $info['platform'] ?></td>
	            <td class="text-nowrap"><?= $info['count'] ?></td>
	            <td class="text-nowrap"><?= $info['successCount'] ?></td>
	            <td class="text-nowrap"><?= sprintf('%u',$info['successCount']/$info['count']*100) ?>%</td>
				<td class="text-nowrap"><?= $info['total'] ?></td>
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




