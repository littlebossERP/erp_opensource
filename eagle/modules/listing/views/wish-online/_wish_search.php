<div class="filter-bar">
    <div class="input-group iv-input">
        <select name="search_condition" id="search_condition" class="iv-input" placeholder="标题">
            <?php foreach($default_search_condition as $id => $name):?>
                <option value="<?=$id?>" <?php if($id == $search_condition): echo 'selected'?><?php endif;?>><?=$name;?></option>
            <?php endforeach;?>
        </select>
        <input name="search_value" class="iv-input" value="<?=$search_value;?>"/>
        <button type="submit" id="wish_search_btn" class="iv-btn btn-search">
            <span class="iconfont icon-sousuo"></span>
        </button>
    </div>
    <a class="iv-btn btn-info wish-list-btn" id="batch_modify" data-status = '2' href="#">
        <span class="iconfont icon-xiugai"></span> 批量修改
    </a>
    <a class="iv-btn btn-info wish-list-btn" id="batch_disabled" data-status = '<?= $enable== 'Y' ? 1:2;?>'>
        <?php if($enable == 'N'): ?>
            <span class="iconfont icon-shangjia"></span> 批量上架
        <?php else: ?>
            <span class="iconfont icon-xiajia"></span> 批量下架
        <?php endif;?>
    </a>
    <a title="同步商品" class="iv-btn btn-important pull-right" style="color:white;" href="start-sync" target="_modal">同步商品</a>
</div>
