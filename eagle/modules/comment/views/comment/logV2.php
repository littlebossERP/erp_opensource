<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
//$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);

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

    .send_error {
        color: #FF9801;
        font-weight: bold;
    }

    .send_success {
        color: #2DCC6F;
        font-weight: bold;
    }

    .left_menu a {
        color: #62778B;
    }

    .left_menu .down {
        cursor: pointer;
    }

    .commenting {
        color: yellowgreen;
    }

    .not_retry_failed {
        color: red;
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
    'active' => '好评记录',
]);
$commentStatusMap = [
    [-1, '全部状态'],
    [1, '评价成功'],
    [2, '好评中'],
    [3, '评价失败']
];

?>
<form action="" name="search_form1" class="block">
    <div class="filter-bar">
        <select style="width: 130px" name="selleruser" class="iv-input" placeholder="全部店铺"
                onchange="document.search_form1.submit()">
            <option value="0">全部店铺</option>

            <?php foreach ($aliexpressusers as $val): ?>
                <option value="<?= $val['sellerloginid'] ?>"
                        <?php if ($val['sellerloginid'] == $selleruserid): ?>selected="selected" <?php endif; ?>><?= $val['sellerloginid'] ?></option>
            <?php endforeach; ?>
        </select>

        <select name="commentStatus" class="iv-input" placeholder="评价状态"
                onchange="document.search_form1.submit()">
            <?php foreach ($commentStatusMap as $map): ?>
                <option
                    value="<?= $map[0] ?>"
                    <?php if (isset($commentStatus) && $map[0] == $commentStatus): ?>selected="selected"<?php endif; ?>><?= $map[1] ?></option>
            <?php endforeach; ?>
        </select>

        <div class="input-group iv-input">
            <label style="margin-left:5px;top: 2px;position: relative; ">速卖通订单号:</label>
            <input type="text" id="num" class="iv-input" name="searchval" placeholder="请填写订单号" value="<?=isset($searchval)?$searchval:""?>">
            <button type="submit" href="#" onclick="document.search_form1.submit()" class="iv-btn btn-search">
                <span class="iconfont icon-sousuo"> </span>
            </button>
        </div>
    </div>
</form>
<table class=" table-striped table-nobordered">
    <thead>
    <tr class="list-firstTr">

        <th class="text-nowrap col-lg-2">
            <?= TranslateHelper::t('速卖通订单号') ?>
        </th>

        <th class="text-nowrap col-lg-1">
            <?= TranslateHelper::t('买家姓名') ?>
        </th>
        <th class="text-nowrap col-lg-1">
            <?= TranslateHelper::t('订单金额($)') ?>
        </th>
        <th class="text-nowrap col-lg-1">
            <?= TranslateHelper::t('店铺名称') ?>
        </th>
        <th class="text-nowrap col-lg-1">
            <?= TranslateHelper::t('评价状态') ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php
    $statusStr = [
        ['发送失败', 'send_error'],
        ['发送成功', 'send_success'],
        ['评价中', 'commenting'],
        ['评价失败', 'not_retry_failed']
    ];
    if (count($logs)):
        foreach ($logs as $log):
            // var_dump($log->attributes);
//            if ($log['isSuccess']!=1 && in_array(trim($log['errorMsg']), ['It is already leave feedback.', 'The order can not be null.'])) {
//                continue;
//            }
            ?>
            <tr class="comment-table-content-tr">

                <td><?= $log['orderSourceOrderId'] ?></td>
                <td><?= $log['sourceBuyerUserId'] ?></td>
                <td><?= $log['subTotal'] ?></td>
                <td style="font-weight:bold"><?= $log['sellerUserId'] ?></td>
                <td>
			<span class="<?= $statusStr[$log['isSuccess']][1] ?>">
				<?= $statusStr[$log['isSuccess']][0] . (($log['isSuccess'] == 1 || $log['isSuccess'] == 2) ? '' : '<br/>原因：' . $log['errorMsg']) ?>
			</span>
                </td>
            </tr>
        <?php endforeach;
    else:?>
        <tr>
            <td colspan="11">还没有好评记录哦.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
<?= HtmlHelper::Pagination($pages) ?>
