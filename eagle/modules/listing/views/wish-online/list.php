<?php

$this->registerJsFile( \Yii::getAlias('@web') . '/js/project/listing/wish_online.js',[
    'depends' => [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
        'eagle\assets\PublicAsset'
    ]
]);
$this->registerCSSFile(\Yii::getAlias('@web').'/css/listing/wish_list.css');
?>

<style type="text/css">
    .tab-xs li{
        margin: auto 10px;
        text-align:center;
    }
    .tab-xs li a{
        width:65px;
    }
    .wish_fanben_id_list{
        border-right: none;
    }
</style>
<?php   
echo $this->render('//layouts/new/left_menu_2',[
    'menu'=> \eagle\modules\listing\helpers\WishHelper::getMenu(),
    'active'=>'Wish平台商品'
]);
?>
<div class="form-group">
    <form id="wish_form" method="get" action="/listing/wish-online/wish-product-list" class="block">
        <div class="filter-bar" style="margin-top: -10px;">
            <span class="iconfont icon-stroe"></span>
            <select class="iv-input wish_site_id" name="site_id" id="wish_site_id" style="width:90px;">
                <?php if(empty($site_id)): ?>
                    <option value="0">请选择Wish店铺</option>
                <?php endif;?>
                <?php if(isset($wish_account)): ?>
                    <?php foreach($wish_account as $id => $account):?>
                        <option value="<?=$id?>" <?php if(!empty($site_id)){ ?><?php if($site_id == $id){ echo 'selected="selected"';}?><?php }?>><?=$account['store_name']?></option>
                    <?php endforeach;?>
                <?php endif; ?>
            </select>
        </div>
        <div class="main-tab">
            <?php foreach($product_status as $id => $val): ?>
               <label>
                    <input type="radio" name="pstatus" value="<?=$id?>"  <?php if(trim($pstatus) == $id){echo 'checked';} ?>>
                    <span><?=$val?></span>
               </label> 
            <?php endforeach;?>
            <label class="mLeft40">
                <input type="radio" name="enable"  value="Y" <?php if($enable == 'Y') echo 'checked';?>>               
                <span>已启用</span>
            </label>
            <label>
                <input type="radio" name="enable" value="N" <?php if($enable == 'N') echo 'checked';?>>
                <span>已禁用</span>
            </label>
        </div>
        <?= $this->render("_wish_search",[
            'wish_account' => $wish_account,
            'site_id' => $site_id,
            'search_condition_name' => $search_condition_name,
            'default_search_condition' => $default_search_condition,
            'search_condition' => $search_condition,
            'search_value' => $search_value,
            'enable' => $enable
        ]);?>
    </form>
    <form id="wish_batch_modify" method="post" action="/listing/wish-online/wish-batch-modify">
        <input type="hidden" name="site_id" value=""/>
    </form>
</div>
<div class="form-group">
    <?=$this->render('_main_table',[
        'list' => $list,
        'product_status' => $product_status,
        'pstatus' => $pstatus,
        'sort' => $sort,
        'sold' => $sold,
        'saves' => $saves,
        'enable' => $enable,
        'page'  => $page
    ]);?>
</div>

<?= $this->render("sync_product",['wish_account'=>$wish_account]);?>  