<?php

use yii\helpers\Url;
use yii\helpers\Html;
$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ckeditor/ckeditor.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/salesinfoedit.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<div class=".container" style="width:98%;margin-left:1%;">
<form action="<?=Url::to(['/listing/ebaymuban/salesinfoedit'])?>" method="post">
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><p class="text-right"><label>销售信息范本名</label></p></div>
	  <div class="col-lg-10">
	  <?php echo Html::hiddenInput('sid',$si->id);?>
	  <?php echo Html::textInput('name',$si->name,array('size'=>100));?>
	  </div>
	</div><hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right"><label>Payment</label></p></div>
	  <div class="col-lg-10">
	  <?php echo Html::textarea('payment',$si->payment,array('id'=>'payment','rows'=>20,'cols'=>100))?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	 <div class="col-lg-2"><p class="text-right"><label>Delivery details</label></p></div>
	  <div class="col-lg-10">
	  <?php echo Html::textarea('deliverydetails',$si->delivery_details,array('id'=>'deliverydetails','rows'=>20,'cols'=>100))?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right"><label>Terms of sales</label></p></div>
	  <div class="col-lg-10">
	  <?php echo Html::textarea('termsofsales',$si->terms_of_sales,array('id'=>'termsofsales','rows'=>20,'cols'=>100))?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right"><label>About us</label></p></div>
	  <div class="col-lg-10">
	  <?php echo Html::textarea('aboutus',$si->about_us,array('id'=>'aboutus','rows'=>20,'cols'=>100))?>
	  </div>
	</div>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right"><label>Contact us</label></p></div>
	  <div class="col-lg-10">
	  <?php echo Html::textarea('contactus',$si->contact_us,array('id'=>'contactus','rows'=>20,'cols'=>100))?>
	  </div>
	</div>
</div>
</div>
<div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;">
<?php echo Html::submitButton('保存销售信息范本')?>
</div>
</form>
<br><br><br>
</div>