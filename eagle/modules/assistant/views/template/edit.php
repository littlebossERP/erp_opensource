<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\assistant\helpers\HtmlHelper;
// $this->registerCssFile('/css/select2.min.css');
// $this->registerJsFile('/js/lib/select2.min.js');
?>
<style>
    .dp-template p{
        width:calc(100% - 20px);
    }
    .dp-template li{
        list-style: none;
    }
</style>
<form action="save" method="post" ajax-form="normal" ajax-reload="true" class="form-horizontal container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3>编辑模板</h3>
            <input type="hidden" name="tpl_id" value="<?= $omtpl['id'] ?>" />
        </div>
        <div class="panel-body">
            <div class="form-group row dp-template">
                <label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
                    推荐模板
                </label>
                <ul class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
                    <?php
                    foreach($tpls as $tpl):
                        $checked = isset($checked)?false:true;
                        ?>
                        <li class="clearfix">
                            <div class="pull-left">
                                <input type="radio" id="tplModel" name="tplModel" value="<?= $tpl->id ?>" />
                            </div>
                            <p class="pull-right"><?= $tpl->content_zh ?></p>
                        </li>
                        <?php
                    endforeach;
                    ?>
                </ul>
            </div>
            <div class="form-group row dp-template">
                <label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
                    模板名称
                </label>
                <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
                    <input type="text" name="template_name" value="<?= $omtpl['template_name'] ?>" required="required">
                </div>
            </div>

            <div class="form-group row">
                <div class="col-xs-4 col-sm-4 col-md-4 col-lg-4 col-xs-offset-2 col-sm-offset-2 col-md-offset-2 col-lg-offset-2">
                    <select id="addTags" class="form-control">
                        <option value="">标签</option>
                        <option value="[买家姓名]">买家姓名</option>
                        <option value="[原始金额]">原始金额</option>
                        <option value="[订单号]">订单号</option>
                    </select> <?= HtmlHelper::tips('在对买家进行催款时，系统会自动将内容中的标签替换为对应的订单信息') ?>
                </div>
            </div>
            <div class="form-group row">
                <label for="" class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">留言内容</label>
                <div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
                    <textarea name="message_content" class="form-control" rows="10" required="required"><?= $omtpl['content'] ?></textarea>
                </div>
            </div>
        </div>
        <div class="panel-footer text-right">
            <!-- <input type="button" class="btn btn-success" value="预览" /> -->
            <input type="submit" value="保存" class="btn btn-primary" />
            <button class="btn btn-default" data-dismiss="modal">取消</button>
        </div>
    </div>
</form>

