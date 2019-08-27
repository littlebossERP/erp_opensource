<?php 
use yii\helpers\Html;
?>


<!--------------------
--账号 (1级行 第1行)--
---------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">账号</span>
		</div>
		<div class="action">
		</div>
	</div><!-- end  subbox-title-->

	<div class="subbox-body form">
		<!-- BEGIN FORM -->
		<div class="form-horizontal">
			<h4 class="class-title">分类</h4>
			<div>
				<!-- 1.1.1行 eBay账号-->

				<div class="form-group">
					<label class="control-label col-lg-3" for="selleruserid">eBay账号<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<?php echo Html::dropDownList('selleruserid',@$data['selleruserid'],$ebayselleruserid,['prompt'=>'请选择eBay账号','id'=>'selleruserid','class'=>'form-control'])?>
					</div>
				</div>

				<!-- 1.1.2行 Paypal账号-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="paypal_account" >Paypal账号<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<input class="form-control" id="paypal_account" name="paypal" list="paypallist" value="<?=@$data['paypal']?>">
						<datalist id="paypallist">
							<?php if (count($paypals)):foreach ($paypals as $p):?>
							<option value="<?=$p->paypal?>"><?=$p->paypal.'('.$p->desc.')'?></option>
							<?php endforeach;endif;?>
						</datalist>
					</div>
				</div>
				<!-- 1.1.3行 店铺类目一-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="storecategoryid" >店铺类目一<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::textInput('storecategoryid',@$data['storecategoryid'],array('id'=>"storecategoryid",'class'=>'form-control'))?>
					</div>
					<div class="col-lg-1">
						<?=Html::button('选择',['onclick'=>'doset("storecategoryid")','class'=>'iv-btn btn-search'])?>
					</div>
				</div>
				<!-- 1.1.4行 店铺类目二-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="storecategory2id" >店铺类目二<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::textInput('storecategory2id',@$data['storecategory2id'],array('id'=>"storecategory2id",'class'=>'form-control'))?>
					</div>
					<div class="col-lg-1">
						<?=Html::button('选择',['onclick'=>'doset("storecategory2id")','class'=>'iv-btn btn-search'])?>
					</div>
				</div>

			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END  SUBBOX-BODY-->


