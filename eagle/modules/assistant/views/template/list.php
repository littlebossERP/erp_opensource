<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;

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
            'active' => '催款模板设置'
        ]);

?>
<div class="content-wrapper">
    <div class="table-action clearfix">
        <div class="pull-left">
            <a href="#" data-modal="add" className="largeModal" class="iv-btn btn-important btn-spacing-middle" style="color:white;"><?= TranslateHelper::t('添加催款模板') ?></a>
        </div>
    </div>
    <table class="table-striped table-nobordered">
        <tr class="list-firstTr">
            <th>
                <?= TranslateHelper::t('模板名称') ?>
            </th>
            <th>
                <?= TranslateHelper::t('催款内容') ?>
            </th>
            <th>
                <?= TranslateHelper::t('操作') ?>
            </th>
        </tr>
        <?php foreach($omtpl as $key=>$tpl):?>
        <tr>
            <td style="width:20%;"><?= $tpl->template_name ?></td>
            <td text-overflow="2" style="width:50%;"><?= $tpl->content ?></td>
            <td style="width:30%">
                <a class="btn btn-default btn-sm" data-modal="edit" className="largeModal" <?= HtmlHelper::params(['id'=>$tpl->id]) ?>>
                    <span class="glyphicon glyphicon-edit icon-button"></span>
                </a>
                <button click-confirm="此操作不可逆" ajax-request="deletetpl" ajax-method="post" ajax-data="t_id=<?=$tpl->id ?>" class="btn btn-danger btn-sm">
                    <span class="glyphicon glyphicon-remove icon-button"></span>
                </button>
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
