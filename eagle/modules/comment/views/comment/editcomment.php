<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/31
 * Time: 下午3:30
 */
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);

?>

<div style="width: 550px;">

    <form action="">
        <div class="filter-bar line-indent">
            <label style="margin-right: 20px">评价星级</label>
            <select style="width: 398px;" name="score" id="select-score" class="iv-input"
                    placeholder="五星">
                <option value=5>五星</option>
                <option value=4>四星</option>
                <option value=3>三星</option>
                <option value=2>两星</option>
                <option value=1>一星</option>
            </select>
        </div>
        <div class="line-indent">
            <label>客户留言</label>
            <input class="inline-radio" type="radio" checked="checked" name="content"
                   value="customized"/><label class="inline-lable">自定义留言</label>
            <input class="inline-radio" type="radio" name="content" value="template"/><label
                class="inline-lable">使用评价模板</label>
            <input class="inline-radio" type="radio" name="content" value="uncomment"><label
                class="inline-lable">不留言</label>
        </div>
        <div class="line-indent" style="width: 265px;height:200px;" id="edit-comment-customized-content" >
            <textarea class="iv-input" id="textarea-customized-content" style="margin-left: 71px;margin-top:24px;width:400px;height:168px"
                      ;rows="10"><?=isset($defaultContent['content'])? $defaultContent['content']:""?></textarea>
            <div style="margin-left: 71px;margin-top: 7px;font-size: xx-small;">小提示:不填不会留言</div>
        </div>
        <div style="overflow-y:auto;height: 168px;" id="edit-comment-comment-template" hidden="hidden">
            <table style="width: 400px;margin-left: 121px;margin-top: 21px;">
                <?php
                if (count($commentTemplate)):foreach ($commentTemplate as $k => $t):
                    ?>
                    <tr style="border: solid;border-width:1px;">
                        <td style="padding: 10px"><span><input type="radio" name="template" value="<?= $t['content'] ?>"
                                                               checked></span></td>
                        <td style="padding: 10px"><?= $t['content'] ?></td>
                    </tr>
                    <?php
                endforeach;
                else:echo '请先添加模版!';endif;
                ?>
            </table>
        </div>
        <div style="height: 168px" id="edit-comment-comment-blank" hidden="hidden"></div>
        <input type="hidden" name="orderSourceOrderId" id="order-source-order-id" value="<?=$id?>">
<!--        --><?php //var_dump($defaultContent['_id']);
//        die();
//        ?>
        <input type="hidden" id="default-customized-content-id" value="<?=isset($defaultContent['_id'])?$defaultContent['_id']->{'$id'}:""?>">
        <div style="text-align: center;margin-top: 50px">
            <button type="button" class="btn btn-success" id="commentsubmit">发送好评</button>
            <button type="button" class="btn btn-default" id="close" style="margin-left: 35px;">关闭</button>
        </div>
    </form>
</div>
