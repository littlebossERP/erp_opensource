<li class="iv-select-img-lib-item">

    <div class="iv-select-item-header">
        <p><?= $name ?></p>
    </div>
    <div class="iv-select-item-body">
		<label>
			<input type="checkbox" class='check' role="ajax-page-key" name="id" value="<?= $id ?>" />
			<input type="checkbox" name="img[]" value="<?= $origin_url ?>" style="display:none;" />
			<img src="<?= $thumbnail_url ?>" alt="" />
		</label>
        <div class="iv-select-item-cover">
            <a class="iv-btn btn-none copy-url" style="padding:2px 5px;" data-clipboard-text="<?= $origin_url ?>">复制地址</a>
            <a class="cover-close iconfont icon-guanbi"></a>
            <p><?= $origin_url ?></p>
        </div>
    </div>
    <div class="iv-select-item-footer">
        <a title="在线美图" class="iv-image-box-footer-icon iconfont icon-kebianji"></a>
        <a title="删除" class="iv-image-box-footer-icon iconfont icon-shanchu"></a>
        <a title="复制链接" class="iv-image-box-footer-icon iconfont icon-link"></a>
    </div>
</li>