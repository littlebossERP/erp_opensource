<style type="text/css">
    .iv-progress{
        width:100%;


    }
</style>

<form class="form-horizontal" style="width:550px;height:230px;">
    <div class="form-group">
        <label for="wish_modal_site_id" class="col-xs-2 col-sm-2 col-lg-2" style="text-align:right;line-height:30px;min-width:80px;">选择店铺</label>
        <div class="col-xs-4 col-sm-4 col-lg-4" style="text-align:left;">
            <select class="p0 form-control" id="wish_modal_site_id" style="margin-top:0;">
                <?php if(count($accounts)> 1): ?>
                    <option value="0">请选择速卖通店铺</option>
                <?php endif;?>
                <?php foreach($accounts as $vs):?>
                    <option value="<?=$vs->sellerloginid?>" data-type="site_id" <?php if(count($accounts) == 1): ?> selected="selected" <?php endif;?>><?=$vs->sellerloginid?></option>
                <?php endforeach;?>
            </select>
        </div>

    </div>
    <div class="form-group">
        <label for="ensogo_modal_site_id" class="col-xs-2 col-sm-2 col-lg-2" style="text-align:right;line-height:30px;min-width:80px;">同步数量</label>
        <div class="col-xs-9 col-sm-9 col-lg-9" style="line-height:30px;text-align:left;">
            <div class="iv-progress">
                <progress max="100" value="0" style="width:100%;"></progress>
                <p class="aliexpress_sync_wait" style="display:none;">正在同步中，请稍候...</p>
                <p class="sending aliexpress_sync_sending" style="display:none;">
                    已成功同步<span data-count>0</span>个订单
                </p>
                <p class="text-success" style="display:none;">恭喜已经同步成功，此次完成了<span data-count>0</span>个订单的同步</p>
                <p class="text-danger" style="display:none;">同步失败</p>
            </div>
        </div>

    </div>
    <div class="form-group" style="text-align:center;margin-top:70px;">
        <a class="iv-btn btn-important sync-start">开始</a>
        <a class="iv-btn btn-success modal-close sync-done" style="display:none;">确定</a>
        <a class="iv-btn btn-default modal-close sync-cancel" style="display:none;">转为后台同步</a>
    </div>

</form>
