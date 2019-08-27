
<table id="wish_list" class="table table-bordered table-hover mTop20">
    <thead>
        <tr>
            <input type="hidden" name="type" value="<?=$type?>"/>
            <th class="wish_fanben_id">
                <span class="glyphicon glyphicon-plus product_all_show" style="cursor: pointer"></span>
                <input type="checkbox" name="check_product_online" class="check_product_online">
            </th>
            <th>缩略图</th>
            <th class="wish_product_id">Product Id</th>
            <th class="wish_title">标题</th>
            <th class="wish_sku">Sku</th>
            <th id="saves" data-sort="<?=$saves;?>">被收藏<?php if(!empty($saves)):?><span class="glyphicon <?php if($saves =='saves-up'){echo 'glyphicon-sort-by-attributes';}else{echo 'glyphicon-sort-by-attributes-alt';}?>"></span><?php endif;?></th>
            <th id="sold" data-sort="<?=$sold;?>">已售出<?php if(!empty($sold)):?><span class="glyphicon <?php if($sold =='sold-up'){echo 'glyphicon-sort-by-attributes';}else{echo 'glyphicon-sort-by-attributes-alt';}?>"></span><?php endif;?></th>
            <th>库存</th>
            <th>Wish店铺</th>
            <th>
                <select id="product_status" name="product_status">
                    <option value="0">所有状态</option>
                    <?php foreach($product_status as $id => $val):?>
                        <option value="<?=$id;?>" <?php if(trim($pstatus) == $id){echo 'selected="selected"';}?>><?=$val;?></option>
                    <?php endforeach;?>
                </select>

            </th>
            <th class="wish_error_message">修改状态</th>
            <th class="wish_operation">操作</th>
        </tr>
    </thead>
    <tbody id="wish-online-list">
        <?php if(!empty($list)):?>
            <?php foreach($list as $key => $fanben):?>
                <tr>
                    <td class="wish_fanben_id_list">
                        <?php if(!empty($fanben['variation'])):?>
                            <span class="glyphicon glyphicon-plus product_show"  data-id="<?=$fanben['id'];?>"></span>
                        <?php endif;?>
                        <input type="checkbox" class="product check_product" id="product_<?=$fanben['id'];?>" name="product[]" value="<?=$fanben['id'];?>" data-id="<?=$fanben['id'];?>">
                    </td>
                    <td><img src="<?=$fanben['main_image'];?>" width="50"></td>
                    <td><?=$fanben['wish_product_id'];?></td>
                    <td><?=$fanben['name'];?></td>
                    <td><?=$fanben['parent_sku'];?></td>
                    <td><?=$fanben['number_saves'];?></td>
                    <td><?=$fanben['number_sold'];?></td>
                    <td><?=$fanben['total_inventory'];?></td>
                    <td><?=$fanben['store_name'];?></td>
                    <td><?=$fanben['status_name'];?></td>
                    <td><?=$fanben['error_message'];?></td>
                    <td style="width:100px;">
                        <span class="product_edit iconfont icon-xiugai" data-id="<?=$fanben['id']?>" title="编辑商品" style="margin-left:10px;"></span>
                        <?php if($type == 1):?>
                        <span class="product_change iconfont icon-xiajia" data-id="<?=$fanben['id']?>" data-status="1" title="商品下架" style="margin-left:10px;"></span>
                            <?php endif;?>
                        <?php if($type == 2):?>
                        <span class="product_change iconfont icon-shangjia" data-id="<?=$fanben['id']?>" data-status="2" title="商品上架" style="margin-left:10px;"></span>
                        <?php endif;?>
                    </td>
                </tr>
                <?php if(!empty($fanben['variation'])):?>
                    <?php foreach($fanben['variation'] as $k => $variation):?>
                        <tr class="variation_<?=$fanben['id']?> variation_tr" style="display: none;">
                            <td >
                                <span class="glyphicon" style="width:10px"></span>
                                <input type="checkbox" class="variation check_product product_<?=$fanben['id']?>" name="variation[]" value="<?=$variation['id'];?>" data-pid="<?=$fanben['id'];?>">
                            </td>
                            <td colspan="10">
                                <span style="margin:0 20px;">颜色：<?=$variation['color'];?></span>
                                <span style="margin-left:20px;">尺码：<?=$variation['size'];?></span>
                                <span style="margin-left:20px;">SKU：<?=$variation['sku'];?></span>
                                <span style="margin-left:20px;">价格：$<?=$variation['price'];?></span>
                                <span style="margin-left:20px;">库存：<?=$variation['inventory'];?></span>
                                <span style="margin-left:20px;">运费：$<?=$variation['shipping'];?></span>
                                <span style="margin-left:20px;">运输时间：<?=$fanben['shipping_time'];?></span>
                            </td>
                            <td>
                                <span class="product_edit iconfont icon-xiugai" data-id="<?=$fanben['id']?>" title="编辑商品"></span>
                                <?php if($type == 1):?>
                                    <span class="product_variaition_change iconfont icon-xiajia"  data-id="<?=$variation['id'];?>" data-status="1" title="商品下架"></span>
                                <?php endif;?>
                                <?php if($type == 2):?>
                                    <span class="product_variaition_change iconfont icon-shangjia" data-id="<?=$variation['id'];?>" data-status="2" title="商品上架"></span>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php endif;?>
            <?php endforeach;?>
        <?php endif;?>
    </tbody>
</table>