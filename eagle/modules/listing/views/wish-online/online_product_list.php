<?php
$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/wish_online.js',['depends' => [
    'yii\web\JqueryAsset',
    'yii\bootstrap\BootstrapAsset',
    'eagle\assets\PublicAsset'
]]);
$this->registerCSSFile(\Yii::getAlias('@web').'/css/listing/wish_list.css');
?>
<script type="text/javascript">
    var global = (function() { return this || (1,eval)("(this)"); }());
    global.wish_type= '<?=$wish_type;?>';
</script>
<style type="text/css">
    table#wish_list tr td{
        text-align: center;
    }
</style>
<?php
    $active ='在线商品';
    $menu = [
        '刊登管理'=>[
            'icon'=>'icon-shezhi',
            'items'=>[
                '待发布'=>[
                    'url'=>'/listing/wish/wish-list',
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
<form id="wish_form"  method="get" action="/listing/wish-online/online-product-list" class="block">
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
<div class="table-action clearfix">
    <div class="pull-left">
        <!-- <a href="#" id="batch_edit"><span class="iconfont icon-xiugai"></span> 批量修改</a>
        <a href="#" id="batch_disabled"><span class="iconfont icon-xiajia"></span> 批量下架</a>  -->
        <button class="btn btn-info wish-list-btn" id="batch_edit" data-status = '2'><span class="iconfont icon-xiugai"></span> 批量修改</button>
        <button class="btn btn-info wish-list-btn" id="batch_disabled" data-status = '2'><span class="iconfont icon-xiajia"></span> 批量下架</button>
    </div>
    <div class="pull-right">
        <!-- <a href="#" id="batch_sync" class="iv-btn btn-important btn-spacing-middle">同步商品</a> -->
        <a title="同步商品" class="iv-btn btn-important" style="color:white;" href="start-sync" target="_modal" >同步商品</a>
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
        'type' => 1
    ]);?>
</div>
<?= $this->render('_product_edit',['wish_model'=>$wish_model]);?>
<?= $this->render("sync_product",['wish_account'=>$wish_account,'page'=>$page]);?>



