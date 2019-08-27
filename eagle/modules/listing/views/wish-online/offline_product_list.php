<?php

$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/wish_online.js',['depends' => ['yii\web\JqueryAsset','yii\bootstrap\BootstrapAsset']]);
$this->registerCSSFile(\Yii::getAlias('@web').'/css/listing/wish_list.css');
?>

<style type="text/css">
    table#wish_list tr td{
        text-align: center;
    }
</style>
<?php
    $active ='下架商品';
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
                '在线商品'=>[
                    'url'=>'/listing/wish-online/online-product-list',
                ],
                '下架商品'=>[
                'url'=>'/listing/wish-online/offline-product-list',
                ],
            ]
        ],
    ];
    echo $this->render('//layouts/new/left_menu',[
        'menu'=>$menu,
        'active'=>$active
    ]);
?>  
<div class="form-group">
    <form id="wish_form" method="get" action="/listing/wish-online/offline-product-list" class="block">
        <?= $this->render("_search",[
            'wish_account' => $wish_account,
            'site_id' => $site_id,
            'search_condition_name' => $search_condition_name,
            'default_search_condition' => $default_search_condition,
            'search_condition' => $search_condition,
            'search_value' => $search_value
        ]);?>
        <input type="hidden" id="pstatus" name="pstatus" value="<?=$pstatus;?>"/>
        <input type="hidden" id="sort" name="sort" value="<?=$sort;?>"/>
    </form>
    <!-- <div class="row">
        <div class="col-xs-4">
            <button class="btn btn-info wish-list-btn" id="batch_sync"><span class="glyphicon glyphicon-cloud-download"></span> 同步商品</button>
            <button class="btn btn-info wish-list-btn" id="batch_disabled" data-status = '2'><span class="glyphicon glyphicon-collapse-down"></span> 批量上架</button>
        </div>
    </div> -->
    <div class="table-action clearfix">
    <div class="pull-left">
        <!-- <a href="#" id="batch_disabled"><span class="iconfont icon-xiajia"></span> 批量下架</a>  -->
        <button class="btn btn-info wish-list-btn" id="batch_disabled" data-status = '2'><span class="iconfont icon-xiajia"></span> 批量上架</button>
    </div>
    <div class="pull-right">
        <!-- <a href="#" id="batch_sync" class="iv-btn btn-important btn-spacing-middle">同步商品</a> -->
        <button class="btn btn-warning" id="batch_sync">同步商品</button>
    </div>
</div>
</div>
<div class="form-group">
    <?=$this->render('_product_list',[
        'list' => $list,
        'product_status' => $product_status,
        'pstatus' => $pstatus,
        'sort' => $sort,
        'sold' => $sold,
        'saves' => $saves,
        'type' => 2
    ]);?>
</div>

<?= $this->render("sync_product",['wish_account'=>$wish_account,'page'=>$page]);?>
