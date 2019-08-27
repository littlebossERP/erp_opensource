<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);

?>
<style>
    .left_menu a {
        color: #62778B;
    }

    .left_menu .down {
        cursor: pointer;
    }
</style>
<?php
$menu = [
    '订单评价' => [
        'icon' => 'iconfont icon-stroe',
        'items' => [
            '自动好评' => [
                'url' => '/comment/comment/rule-v2',
            ],
            '等待您留评' => [
                'url' => '/comment/comment/index-v2',
            ],
            '评价模板' => [
                'url' => '/comment/comment/template-v2',
            ],
        ],
    ],
    '好评记录' => [
        'icon' => 'iconfont icon-stroe',
        'items' => [
            '好评记录' => [
                'url' => '/comment/comment/log-v2',
            ],
        ]
    ],
];
echo $this->render('//layouts/new/left_menu', [
    'menu' => $menu,
    'active' => '订单好评',
]);
?>

    <br><br>
    <h4 style="margin-left:80px;">自动好评规则</h4><br><br>


    <form action="" method="post" class="form-horizontal form-standard" id="addrule-form" version="V2">
        <div class="form-group">
            <label class="col-sm-2 control-label">对纠纷订单好评</label>
                <div class="col-sm-10">
                    <div class="radio-inline">
                        <label>
                            <input type="radio" name="is_dispute"
                                   value="1" <?= $rule['isCommentIssue'] === 1 ? 'checked' : '' ?> /> 是
                        </label>
                    </div>
                        
                    <div class="radio-inline">
                        <label>
                            <input type="radio" name="is_dispute"
                                   value="0" <?= $rule['isCommentIssue'] !== 1 ? 'checked' : '' ?> /> 否
                        </label>
                    </div>
                </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 col-lg-2 col-xs-2 col-md-2 control-label">评价店铺
                
            </label>

            <div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
                <div class="checkbox">
                    <?= HtmlHelper::checkboxGroup('shop_id', $aliexpressuser, $rule['sellerIdList'], ['className' => 'col-lg-2']) ?>
                </div>
            </div>
        </div>
        <div class="form-group filter-bar" style="margin-left: 129px">
            <label style="margin-right: 26px;color: #aaaaaa;">评价星级</label>
            <select style="width: 264px;" name="score" id="" class="iv-input"
                    >
                <option value=5 selected="selected">五星</option>
                <option value=4>四星</option>
                <option value=3>三星</option>
                <option value=2>两星</option>
                <option value=1>一星</option>
            </select>
        </div>
        <div class="form-group">
            <label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
                好评内容
            </label>
            <div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
                <textarea name="content" id="addrule-content" class="form-control" rows="8"
                          required <?= $rule['content'] ?>><?= $rule['content'] ?></textarea>
            </div>
        </div>
        <div class="form-group">    
            <label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
                评价订单国家
                
            </label>
            <div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">

                <?= HtmlHelper::selCountriesV2('countries', $rule['countryList']); ?>
            </div>
        </div>
   
        <div class="form-group">
        <div class="col-xs-offset-3 col-sm-offset-3 col-md-offset-3 col-lg-offset-3">
            <input class="btn btn-success " type="submit" value="<?= $rule['_id'] ? '保存规则' : '新增规则' ?>"/>
            <a href="./rule-v2" class="btn btn-default">取消</a>
        </div>
        </div>
        <input type="hidden" name="ruleid" value="<?= $rule['_id'] ?>">
    </div>
    </form>

</div>


