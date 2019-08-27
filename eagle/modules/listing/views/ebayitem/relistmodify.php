<?php 
use yii\helpers\Url;
use yii\helpers\Html;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/itemedit.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<br/>
<div class=".container" style="width:98%;margin-left:1%;">
<form action="<?=Url::to(['/listing/ebayitem/relistmodify2'])?>" method="post">
<input type="hidden" name="itemid" value="<?php echo $_GET['itemid'];?>">
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-12"><label>选择Itmeid:<?php echo $_GET['itemid'];?>需要修改的内容<br/><input id="checkNegative" type="checkbox" >全选</label></div>
	</div>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">平台与细节<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  <?=Html::checkboxList('setitemvalues', '', array('category'=>'刊登分类','conditionid'=>'物品属性状况','itemspecifics'=>'细节','variation'=>'多属性'));?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">标题与价格<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  	<?php if($item->listingtype == 'Chinese'){?>
		<?=Html::checkBoxList('setitemvalues', '', array('itemtitle'=>'刊登标题','sku'=>'SKU','listingduration'=>'刊登天数','price'=>'价格','privatelisting'=>'私人刊登'));?>
		<?php }else{?>
		<?=Html::checkBoxList('setitemvalues', '', array('itemtitle'=>'刊登标题','sku'=>'SKU','quantity'=>'数量','listingduration'=>'刊登天数','price'=>'价格','bestoffer'=>'议价','privatelisting'=>'私人刊登'));?>
		<?php }?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">图片与描述<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  <?=Html::checkBoxList('setitemvalues', '', array('imgurl'=>'图片','itemdescription'=>'刊登描述'));?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">物流设置<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  <?=Html::checkBoxList('setitemvalues', '', array('shippingdetails'=>'运费设置','dispatchtime'=>'包裹处理时间'));?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">收款与退款<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  <?=Html::checkBoxList('setitemvalues', '', array('paymentmethods'=>'收款方式','return_policy'=>'退货政策','location'=>'商品所在地'));?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">其他<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  <?=Html::checkBoxList('setitemvalues', '', array('gallery'=>'图片展示','listingenhancement'=>'样式','hitcounter'=>'计数器'));?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">账号<input class="part" type="checkbox" ></p></div>
	  <div class="col-lg-10">
	  <?=Html::checkBoxList('setitemvalues', '', array('storecategory'=>'店铺类目','paypal'=>'Paypal账号'));?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"></div>
	  <div class="col-lg-10">
	  <input  type="button" value=" 取 消  " onclick='window.close();'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input  type="submit" value=" 下 一 步  ">
	  </div>
	</div>
  </div>
</div>
</form>
</div>