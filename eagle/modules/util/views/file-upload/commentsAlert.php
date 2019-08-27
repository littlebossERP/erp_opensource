<?php

$this->registerCssFile(\Yii::getAlias('@web')."/css/prestashop/base.css?v=".eagle\modules\util\helpers\VersionHelper::$prestashop_listing_version);

$this->title = "访问受限:没有权限";
?>
<style>
.commentAlert{
font-size: 36px;
    width: 100%;
    text-align: center;
    padding-top: 10%;
}
</style>

<div class="commentAlert">
    <?php if(isset($alertContent)){?>
       <div><?php echo $alertContent;?></div>
       <div>请访问 <a target="_blank" href="/payment/user-account/package-list">套餐以及价格</a> 页面，了解更多详情，升级套餐获得更多功能！</div>
   <?php }else{?>
       <div style="padding-bottom: 25px;">很抱歉，您未能使用此功能</div>
       <div>请访问 <a target="_blank" href="/payment/user-account/package-list">套餐以及价格</a> 页面，了解更多详情，开启更加炫酷的模板，强大的功能！</div>
   <?php }?>
</div>