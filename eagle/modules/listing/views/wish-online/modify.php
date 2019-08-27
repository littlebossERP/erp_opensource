<?php

$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/wish_online.js',['depends' => ['yii\web\JqueryAsset','yii\bootstrap\BootstrapAsset','yii\bootstrap\BootstrapPluginAsset','eagle\assets\PublicAsset']]);
$this->registerCSSFile(\Yii::getAlias('@web').'/css/listing/wish_list.css');
?>
<style>
    input[type=radio]{
        width:15px;
        height:15px;
    }
</style>

<?php
    $active ='Wish平台商品';
    $menu = [
        '刊登管理'=>[
            'icon'=>'icon-shezhi',
            'items'=>[
                '待发布'=>[
                    'url'=>'/listing/wish/wish-list',
                    // 'tabbar'=>81,
                    // 'qtipkey'=>'',
                ],
                '刊登失败'=>[
                    'url'=>'/listing/wish/wish-list?type=2&lb_status=4',
                    'tabbar' => $WishListCount,
                ],
            ]
        ],
        '商品列表'=>[
            'icon'=>'icon-pingtairizhi',
            'items'=>[
                'Wish平台商品'=>[
                    'url'=>'/listing/wish-online/wish-product-list',
                ],
            ]
        ],
    ];
    echo $this->render('//layouts/new/left_menu',[
        'menu'=>$menu,
        'active'=>$active
    ]);
?>
<div class="table-action clearfix">
    <div class="pull-left">
        <a href="#" class="title_modify" target="#title-modal" title="批量标题修改" onclick="$.overLay(1)">
            <span class="iconfont icon-xiugai" ></span>标题修改
        </a>
        <a href="#" class="price_modify" target="#price-modal" title="批量价格修改" onclick="$.overLay(1)">
            <span class="iconfont icon-xiugai"></span>价格修改
        </a>
        <a href="#" class="inventory_modify" target="#inventory-modal" title="批量库存修改" onclick="$.overLay(1)">
            <span class="iconfont icon-xiugai"></span>库存修改
        </a>
    </div>
</div>
<table id="wish_list" class="iv-table table-default mTop20">
    <thead>
        <tr>
            <th style="width:10%;">
                图片
            </th>
            <th class="wish_title" style="width:70%;">标题</th>
            <th style="width:10%;">价格</th>
            <th style="width:10%;">库存</th>
        </tr>
    </thead>
    <tbody id="wish-online-list">
        <?php foreach($list as $product): ?>
            <?php if(isset($product['variance'])): ?>
                <?php foreach($product['variance'] as $key => $variance): ?>
                    <tr>
                        <td>
                            <img src="<?=$product['main_image']?>"/>
                            <input type="hidden" name="product_id" value="<?=$product['id']?>"> 
                            <input type="hidden" name="variance_id" value="<?=$variance['id']?>">
                        </td>
                        <td>
                            <span class="pull-left modify_title" style="margin:0 10px;width:70%;" data-id="<?=$variance['id']?>" data-pid="<?=$product['id']?>"><?=$product['name']?></span>
                            <span class="modify_error_tips pull-right " style="color:#ff0033;margin:0 10px;text-align: left;width:200px;">
                            </span>
                        </td>
                        <td class="modify_price" data-id="<?=$variance['id']?>" data-pid="<?=$product['id']?>"><?=$variance['price']?></td>
                        <td class="modify_inventory" data-id="<?=$variance['id']?>" data-pid="<?=$product['id']?>"><?=$variance['inventory']?></td>
                    </tr>
                <?php endforeach;?>
            <?php endif;?>
        <?php endforeach;?>
    </tbody>
</table>
<div style="text-align: center;margin-top:30px;">
    <button class="iv-btn btn-success" id="modify_ensure">提&nbsp;&nbsp;交</button>
</div>
<form action="deal-batch-modify" method="post" id="modify-post">

</form>
<div id="title-modal" style="display:none">
    <div style="width:500px;height:300px;">
        <div style="margin-left:50px;" class="title-modal-content">
            <div class="filter-bar">
                <input type="radio" name="title_modify_type" value="fadd" checked="checked"/>
                <label style="width:100px;text-align:left;">标题开头添加</label>
                <input type="text" class="" name="fadd" style="margin-left:30px;width: 237px" />
            </div>
            <div class="filter-bar">
                <input type="radio" name="title_modify_type" value="badd"/>
                <label style="width:100px;text-align:left;">标题结尾添加</label>
                <input type="text" class="" name="badd" style="margin-left:30px;width: 237px" disabled="disabled" />
            </div>
            <div class="filter-bar">
                <input type="radio" name="title_modify_type" value="fdel"/>
                <label style="width:100px;text-align:left;">标题开头删除</label>
                <input type="text" class="" name="fdel" style="margin-left:30px;width: 237px" disabled="disabled" />
            </div>
            <div class="filter-bar">
                <input type="radio" name="title_modify_type" value="bdel"/>
                <label style="width:100px;text-align:left;">标题结尾删除</label>
                <input type="text" class="" name="bdel" style="margin-left:30px;width: 237px" disabled="disabled" />
            </div>
            <div class="filter-bar">
                <input type="radio" name="title_modify_type" value="rp"/>
                <label style="width:100px;text-align:left;letter-spacing: 9px;">标题中的</label>
                <input type="text" class="" name="content" style="margin-left:30px;width:80px;" disabled="disabled" />
                <span style="margin:0 10px;">替换为</span>
                <input type="text" class="" name="content_replace" class="iv-input" style="width:80px;" disabled="disabled" />
            </div>
        </div>
        <div class="filter-bar" style="text-align:center;margin-top:30px;">
            <button class="iv-btn btn-success" id="modify_title_ensure" style="margin-right:50px;">确定</button>
            <button class="iv-btn btn-background modal-close">取消</button>
        </div>
    </div>
</div>
<div id="price-modal" style="display:none">
    <div style="width:400px;height:200px;margin-top:10px;">
        <div style="margin-left:35px;">
            <div class="filter-bar">
                <input type="radio" name="price_modify_type" checked="checked" value="batch"/>
                <span>批量修改为</span>
                <input type="text" name="price_modify1" value=""  style="width:220px;"/>
            </div>
            <div class="filter-bar">
                <input type="radio" name="price_modify_type" value="other"/>
                <span>按</span>
                <select name="modify_price_select" class="" placeholder="价格">
                    <option value="price">价格</option>
                    <option value="percent">百分比</option>
                </select>
                <span style="margin:0 5px">增加</span>
                <input type="text" name="price_modify2" value="" style="width:158px;" disabled="disabled" />
            </div>
            <p>小提示: 增加后面可填负数</p>
        </div>
        <div class="filter-bar" style="text-align:center;margin-top:30px;">
            <button class="iv-btn btn-success" id="modify_price_ensure" style="margin-right:50px;">确定</button>
            <button class="iv-btn btn-background modal-close">取消</button>
        </div>
    </div>
</div>
<div id="inventory-modal" style="display:none">
    <div style="width:400px;height:200px;margin-top:10px;">
        <div style="margin-left:35px;">
            <div class="filter-bar">
                <input type="radio" name="inventory_modify_type" value="batch" checked="checked" />
                <span>批量修改为</span>
                <input type="text" name="inventory_modify1" value=""  style="width:220px;"/>
            </div>
            <div class="filter-bar">
                <input type="radio" name="inventory_modify_type" value="other"/>
                <span>按</span>
                <select name="modify_inventory_select" class="" placeholder="库存">
                    <option value="inventory">库存</option>
                    <option value="percent">百分比</option>
                </select>
                <span style="margin:0 5px">增加</span>
                <input type="text" name="inventory_modify2" value="" style="width:158px;" disabled="disabled"/>
            </div>
            <p>小提示: 增加后面可填负数</p>
        </div>
        <div class="filter-bar" style="text-align:center;margin-top:30px;">
            <button class="iv-btn btn-success" id="modify_inventory_ensure" style="margin-right:50px;">确定</button>
            <button class="iv-btn btn-background modal-close">取消</button>
        </div>
    </div>
</div>