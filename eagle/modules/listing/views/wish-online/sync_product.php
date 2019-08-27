<div class="modal fade bs-modal-sm"  id="sync_modal" tabindex="2">
    <div class="modal-dialog modal-sm"  style="position: fixed; top:0;left: 0;right: 0;bottom: 0; margin: auto; height: 300px">
        <div class="modal-content" style="border:0;width:600px;">
            <div class="modal-head">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" style="text-align:center;background-color:#364655;color:white;border-color:#364655;line-height:30px;font-size:16px;">同步商品</h3>
            </div>
            <div class="modal-body">
                <div class="cite_goods_box">
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label for="wish_modal_site_id" class="col-xs-1 col-sm-1 col-lg-1" style="text-align:right;line-height:30px;min-width:80px;">选择店铺</label>
                            <div class="col-xs-11 col-sm-11 col-lg-11" style="width:300px;text-align:left;">
                                <select class="p0 form-control" id="wish_modal_site_id" style="margin-top:0;">
                                    <?php if(count($wish_account)> 1): ?>
                                    <option value="0">请选择Wish店铺</option>
                                    <?php endif;?>
                                    <?php foreach($wish_account as $id => $account):?>
                                        <option value="<?=$id?>" data-type="site_id" <?php if(count($wish_account) == 1): ?> selected="selected" <?php endif;?>><?=$account['store_name']?></option>
                                    <?php endforeach;?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-11 col-sm-11 col-lg-11 " style="width:400px;line-height:30px;text-align:left;">
                                <span class="col-xs-5">已同步商品数：<i class="sync_finished_num">0</i></span>
                                <div class="col-xs-7 sync_tips"></div>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div id="wish_log"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="wish_modal_footer">
                <button type="button" class="btn btn-default" id="wish_flash" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" id="wish_commit" style="display: none;">确定</button>
            </div>
        </div>
    </div>
</div>

