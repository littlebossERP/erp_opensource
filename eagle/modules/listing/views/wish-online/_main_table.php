
<table id="wish_list" class="iv-table table-default mTop20">
    <input type="hidden" name="enable" value="<?=$enable?>"/>
    <thead>
        <tr>
            <th class="wish_fanben_id">
                <span class="glyphicon glyphicon-plus wish_product_all_show" style="cursor: pointer"></span>
            </th>
            <th style="width:20px;">
                <input type="checkbox" name="check_product_online" class="check_product_online">
            </th>
            <th style="min-width:100px;">缩略图</th>
            <th class="wish_title" style="width:40%;text-align: left;">标题</th>
            <th class="wish_sku" style="width:15%;">Sku</th>
            <th style="min-width:100px;" id="saves" data-sort="<?=$saves;?>">被收藏<?php if(!empty($saves)):?><span class="glyphicon <?php if($saves =='saves-up'){echo 'glyphicon-sort-by-attributes';}else{echo 'glyphicon-sort-by-attributes-alt';}?>"></span><?php endif;?></th>
            <th style="min-width:100px;" id="sold" data-sort="<?=$sold;?>">已售出<?php if(!empty($sold)):?><span class="glyphicon <?php if($sold =='sold-up'){echo 'glyphicon-sort-by-attributes';}else{echo 'glyphicon-sort-by-attributes-alt';}?>"></span><?php endif;?></th>
            <th style="min-width:80px;">库存</th>
            <th style="width:10%;">店铺</th>
            <th class="wish_operation" style="min-width:100px;">操作</th>
        </tr>
    </thead>
    <tbody id="wish-online-list">
        <?php if(!empty($list)):?>
            <?php foreach($list as $key => $fanben):?>
                <tr>
                    <td class="wish_fanben_id_list" data-rowspan="<?= count($fanben['variation'])+1;?>">
                        <?php if(!empty($fanben['variation'])):?>
                            <span class="glyphicon glyphicon-plus wish_product_show"  data-id="<?=$fanben['id'];?>"></span>
                        <?php endif;?>
                    </td>
                    <td>
                        <input type="checkbox" class="product check_product" id="product_<?=$fanben['id'];?>" name="product[]" value="<?=$fanben['id'];?>" data-id="<?=$fanben['id'];?>">
                    </td>
                    <td><img src="<?=$fanben['main_image'];?>" width="50"></td>
                    <td>
                        <?=$fanben['name'];?>
                    </td>
                    <td><?=$fanben['parent_sku'];?></td>
                    <td><?=$fanben['number_saves'];?></td>
                    <td><?=$fanben['number_sold'];?></td>
                    <td><?=$fanben['total_inventory'];?></td>
                    <td><?=$fanben['store_name'];?></td>
                    <td style="width:100px;">
                        <span class="product_edit iconfont icon-xiugai" data-id="<?=$fanben['id']?>" title="编辑商品"></span>
                        <?php if($enable == 'Y'):?>
                        <span class="product_change iconfont icon-xiajia" data-id="<?=$fanben['id']?>" data-status="1" title="商品下架"></span>
                            <?php endif;?>
                        <?php if($enable == 'N'):?>
                        <span class="product_change iconfont icon-shangjia" data-id="<?=$fanben['id']?>" data-status="2" title="商品上架"></span>
                        <?php endif;?>
                    </td>
                </tr>
                <?php if(!empty($fanben['variation'])):?>
                    <?php foreach($fanben['variation'] as $k => $variation):?>
                        <tr class="variation_<?=$fanben['id']?> variation_tr" style="display: none;">
                            <td>
                                <input type="checkbox" class="variation check_product product_<?=$fanben['id']?>" name="variation[]" value="<?=$variation['id'];?>" data-pid="<?=$fanben['id'];?>">
                            </td>
                            <td colspan="7" style="text-align:left;">
                                <strong style="width:10px;">
                                <?php if(!empty($fanben['error_message']) && $k == 0): ?>
                                    <span class="iconfont icon-cuowu error_tips" style="color:#ff0033;" data-toggle="popover" data-content="<?=$fanben['error_message']?>"></span>
                                <?php endif;?>
                                </strong>
                                <span style="padding-left:100px;">
                                    <span style="margin:0 20px;">颜色：<?=$variation['color'];?></span>
                                    <span style="margin-left:20px;">尺码：<?=$variation['size'];?></span>
                                    <span style="margin-left:20px;">SKU：<?=$variation['sku'];?></span>
                                    <span style="margin-left:20px;">价格：$<?=$variation['price'];?></span>
                                    <span style="margin-left:20px;">库存：<?=$variation['inventory'];?></span>
                                    <span style="margin-left:20px;">运费：$<?=$variation['shipping'];?></span>
                                    <span style="margin-left:20px;">运输时间：<?=$fanben['shipping_time'];?></span>
                                </span>
                            </td>
                            <td>
                                <span class="product_edit iconfont icon-xiugai" data-id="<?=$fanben['id']?>" title="编辑商品"></span>
                                <?php if($enable == 'Y'):?>
                                    <span class="product_variaition_change iconfont icon-xiajia"  data-id="<?=$variation['id'];?>" data-status="1" title="商品下架"></span>
                                <?php endif;?>
                                <?php if($enable == 'N'):?>
                                    <span class="product_variaition_change iconfont icon-shangjia" data-id="<?=$variation['id'];?>" data-status="2" title="商品上架"></span>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <?php endif;?>
            <?php endforeach;?>
        <?php endif;?>
    </tbody>
    <!--分页---->
    <?php if(! empty($page)):?>
        <tfoot>
            <tr>
                <td colspan = "10">
                    <?php 
                        $pageBar = new \render\layout\Pagebar();
                        $pageBar->page = $page;
                        echo $pageBar;
                    ?>
                </td>
            </tr>
        </tfoot>
    <?php endif;?>
<!--分页---->
</table>