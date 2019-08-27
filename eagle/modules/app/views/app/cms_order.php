<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/app/appView.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs('appManager.view.init();', \yii\web\View::POS_READY);

$imageBasePath=Yii::getAlias('@web')."/images";

echo $this->render("cms_common_header", ['appInfo' => $appInfo,'showOperation'=>$showOperation]);

?>


<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="basicinfo">
      <div class="tabpanel-container" style="padding:15px 15px 10px 15px">
            <p> ebay刊登助手，支持定时刊登。</p>
            <p> 已经为众多卖家提供了超过3年的服务。</p>
            <p>免费版功能包括：商品管理、eBay刊登、eBay订单管理、客服管理……</p>
     </div>
  </div>
  <div role="tabpanel" class="tab-pane" id="usage">...</div>
  <div role="tabpanel" class="tab-pane" id="example">
      <div class="tabpanel-container" style="padding:15px 15px 10px 15px">  
        <img src="/images/app/ebay_listing_example1.jpg"/>
      </div>
  </div>  
  
</div>


