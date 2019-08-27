<?php
use eagle\modules\comment\helpers\IssueStatus;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use common\helpers\Helper_Emoji;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerCssFile(\Yii::getAlias('@web') . "/js/lib/intro/introjs.min.css");
$this->registerCssFile(\Yii::getAlias('@web') . "/css/emoji.css");
//$this->registerCssFile(\Yii::getAlias('@web') . "/css/comment-v2.css");
$this->registerJsFile(\Yii::getAlias('@web') . "/js/lib/intro/intro.min.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);

?>
<style>
    .comment-top-ul {
        list-style-type: none;
        height: 50px;
        margin-bottom: 0px;
        padding-left: 15px;
    }

    .comment-top-ul li {
        float: left;
        font-size: 12px;
        height: 40px;
        line-height: 40px;
        margin-right: 10px;
        color: #666;
    }

    .icon-button {
        display: inline-block;
        height: 20px;
        width: 25px;
        cursor: pointer;
        font-size: 13px;
        font-weight: bold;
    }

    .comment-table-content-tr {
        font-size: 12px;
        color: #666;
        height: 50px;
        border-bottom: 1px solid #ccc;
    }

    .comment-table-content-tr:hover .icon-button {
        color: #02BDF0;
    }

    .has-dispute {
        color: #FF9801;
        font-weight: bold;
    }

    .has-no-dispute {
        color: #2DCC6F;
        font-weight: bold;
    }

    .comment-add-title {
        height: 30px;
        font: bold 15px/30px 微软雅黑
    }

    .comment-add-content-div {
        clear: both;
        height: 50px;
        border-bottom: 1px solid #ccc;
        margin: 10px 0px;
    }

    .comment-add-content-div label {
        font-weight: normal
    }

    .left_menu a {
        color: #62778B;
    }

    .left_menu .down {
        cursor: pointer;
    }

    .inline-radio {
        margin-left: 20px !important;
        margin-bottom: 1px !important;
    }

    .inline-lable {
        margin-left: 5px !important;
        margin-top: 8px !important;
    }

    .line-indent {
        margin-left: 50px;
    }

    .btn-fun {
        background-color: #01BDF0 !important;
        color: white !important;
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
    'active' => '等待您留评',
]);
?>
<div>
    <div class="panel">
        <form action="" name="search_form1" class="block">
            <div class="filter-bar">
                <?php
                $aliexpressusers_arr = array_map(function ($user) {
                    return $user->sellerloginid;
                }, $aliexpressusers);
                ?>
                <select name="selleruser" class="iv-input" placeholder="全部店铺"
                        onchange="document.search_form1.submit()">
                    <option value="0">全部店铺</option>
                    <?php foreach ($aliexpressusers_arr as $val): ?>
                        <option value="<?= $val ?>"
                                <?php if ($val == $selleruser): ?>selected="selected" <?php endif; ?>><?= $val ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="input-group iv-input">
                    <label style="margin-left:5px;top: 2px;position: relative; ">速卖通订单号:</label>
                    <input type="text" id="num" class="iv-input" name="searchval" placeholder="请填写订单号">
                    <button type="submit" href="#" onclick="document.search_form1.submit()" class="iv-btn btn-search">
                        <span class="iconfont icon-sousuo"></span>
                    </button>
                </div>
            </div>
        </form>
        <?php if(!isset($_GET['operate_type']) || $_GET['operate_type'] === '0'): ?>
            <div class="table-action clearfix">
                <div class="pull-left" >
                    <a  id="listcommentsubmitV2" class="iv-btn btn-fun"><span class="iconfont icon-pingjia" style="font-size:12px;"></span> 批量评价</a>
                </div>
            </div>
        <?php endif ?>

<!--        <div class="iv-alert alert-remind">-->
<!--            只显示100条未评价订单，评价完后重新拉取100条-->
<!--        </div>-->
        
        <table class=" table-striped table-nobordered">
            <thead>
            <tr class="list-firstTr">
                <th class="col-lg-1">
                    <input type="checkbox" data-check-all="order_info"/>
                </th>
                <th class="text-nowrap col-lg-3">
                    <?= TranslateHelper::t('速卖通订单号') ?>
                </th>

                <th class="text-nowrap col-lg-2">
                    <?= TranslateHelper::t('买家姓名') ?>
                </th>
                <th class="text-nowrap col-lg-3">
                    <?= TranslateHelper::t('订单金额($)') ?>
                </th>
                <th class="text-nowrap col-lg-2">

                    <!--     	<?php $aliexpressusers_arr = array_map(function ($user) {
                        return $user->sellerloginid;
                    }, $aliexpressusers);
                    ?>
	            	<?= HtmlHelper::dropdownlistSearchButton('selleruser', $aliexpressusers_arr, TranslateHelper::t('店铺名称')) ?>-->
                    <?= TranslateHelper::t('店铺名称'); ?>
                </th>
                <th class="text-nowrap col-lg-1" style="text-align: center">
                    <?= TranslateHelper::t('操作') ?>
                    <!-- <span qtipkey="comment_operation_tip"></span> -->
                </th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($orders)):foreach ($orders as $order): ?>
                <tr class="comment-table-content-tr" id="tr-comment-<?=$order->order_source_order_id?>">
                    <td><input type="checkbox" class="order_checkbox" value="<?= $order->order_source_order_id ?>"
                               littlebossid="<?= intval($order->order_id) ?>" data-check="order_info"/></td>
                    <td>
                        <div><?= !empty($order->issuestatus) && ($order->issuestatus == IssueStatus::IN_ISSUE ||$order->issuestatus == IssueStatus::END_ISSUE) ? '<a style="margin-left:-35px;background-color:red;border:0;padding:2px 3px;color:white;">纠纷</a>' : '' ?>
                            <?= $order->order_source_order_id ?></div>
                    </td>
                    <td><?= $order->source_buyer_user_id ?></td>
                    <td><?= $order->subtotal ?></td>
                    <td><?= $order->selleruserid ?></td>
                    <td style="text-align: center">
                        <?php if (!isset($_GET['operate_type']) || $_GET['operate_type'] == 0): ?>
                            <!-- <span class="glyphicon glyphicon-heart-empty icon-button" title="好评" data-toggle="modal"
                                  data-target="#comment_list"
                                  onclick="commentsubmit(<?= $order->order_source_order_id ?>)"></span> -->
                            <!-- <span class="glyphicon glyphicon-hourglass icon-button" title="不予处理" onclick="commentignore(<?= intval($order->order_id) ?>)"></span> -->
                        <?php endif; ?>
                        <a  onclick="editcomment(<?=$order->order_source_order_id?>,'评价')" class=" iv-btn btn-important"  title="评价" style="cursor:pointer;color:#0967FD;background-color: transparent">评价</a>
                    </td>
                </tr>
            <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="11">该条件下无订单.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?= HtmlHelper::Pagination($pages) ?>
    </div>
</div>
