<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use eagle\modules\app\apihelpers\AppApiHelper;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\widgets\SizePager;
// $puid = \Yii::$app->user->identity->getParentUid();
?>


<div class="authorization" >
    <div class="alert alert-danger col-md-4" role="alert" style="width:100%;">
        <?php foreach ($ebaydisableuserid as $detailAccounts):?>
            <?php if($detailAccounts['listing_status']==='0'):?>
                <span>您绑定的eBay账号：<?=$detailAccounts["selleruserid"]?> 的ebay刊登授权未开启！</span><br />
            <?php elseif(($detailAccounts['listing_status']==='1') && ($detailAccounts['listing_expiration_time'] < time())):?>
                <span>您绑定的eBay账号：<?=$detailAccounts["selleruserid"]?> 的ebay刊登授权已过期！</span><br />
            <?php endif;?>
       <?php endforeach;?>
    </div>
<div style="text-align: center;">
    <?php 
    // 绑定平台
    list($url,$label) = AppApiHelper::getPlatformMenuData();
    ?>
    <a target="_blank" href="<?=$url ?>">
    <?=Html::Button('开通授权',['class'=>'iv-btn btn-search'])?>
    </a>

</div>


</div>
</div>
