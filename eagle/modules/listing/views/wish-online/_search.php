<div class="filter-bar">
    <span class="iconfont icon-stroe"></span>
    <select class="iv-input wish_site_id" name="site_id" id="wish_site_id">
        <?php if(empty($site_id)): ?>
            <option value="0">请选择Wish店铺</option>
        <?php endif;?>
        <?php if(isset($wish_account)): ?>
            <?php foreach($wish_account as $id => $account):?>
                <option value="<?=$id?>" <?php if(!empty($site_id)){ ?><?php if($site_id == $id){ echo 'selected="selected"';}?><?php }?>><?=$account['store_name']?></option>
            <?php endforeach;?>
        <?php endif; ?>
    </select>
    <div class="input-group iv-input">
        <select name="select_condition" id="search_condition" class="iv-input" placeholder="标题">
            <?php foreach($default_search_condition as $id => $name):?>
                <option value="<?=$id?>"><?=$name;?></option>
            <?php endforeach;?>
        </select>
        <input name="search_value" class="iv-input" value="<?=$search_value;?>"/>
        <button type="submit" id="wish_search_btn" class="iv-btn btn-search">
            <span class="iconfont icon-sousuo"></span>
        </button>
    </div>
</div>
