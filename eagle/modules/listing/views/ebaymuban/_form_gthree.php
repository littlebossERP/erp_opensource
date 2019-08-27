<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\Helper_Siteinfo;
?>
	<!--------------------------
	--标题与价格 (1级行 第3行)--
	---------------------------->

	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">标题与价格</span>
		</div>
		<div class="action">
		</div>
	</div><!-- end  subbox-title-->

	<div class="subbox-body form">
		<!-- BEGIN FORM -->
		<div class="form-horizontal">
			<h4 class="class-title">分类</h4>
			<div>
				<!-- 3.1.1行  刊登主标题-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="itemtitle">刊登主标题<span class="requirefix">*</span></label>
					<div class="col-lg-8">
						<input name="itemtitle" class="iv-input" size="80" value='<?php echo $data['itemtitle']?>' id="itemtitle" onkeydown="inputbox_left('itemtitle',80)" onkeypress="inputbox_left('itemtitle',80)" onkeyup="inputbox_left('itemtitle',80)">
					<!-- <div class="col-lg-1"> -->
						<span id='length_itemtitle' style="font-weight:bold">80  </span>
					<!-- </div> -->
					</div>
				</div>

				<!-- 3.1.2行  刊登副标题-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="itemsubtitle" >刊登副标题<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<input name="itemtitle2" class="iv-input main-input" size="80" value="<?php echo $data['itemtitle2']?>" id="itemsubtitle">
					</div>
				</div>
				<!-- 3.1.3行  Customer Label-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="customer_label_id" >Customer Label<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::textInput('sku',$data['sku'],array('class'=>'iv-input main-input','id'=>'customer_label_id'))?>
						<span class="text-format-D">使用多属性时,该值将无效</span>
					</div>
				</div>
				<!-- 3.1.4行  数量-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="amount" >数量<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::textInput('quantity',$data['quantity'],array('class'=>'iv-input main-input','id'=>'amount'))?>
						<span class="text-format-D">使用多属性时,该值将无效</span>
					</div>
				</div>
				<!-- 3.1.5行  LotSize-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="lotsize_id" >LotSize<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::textInput('lotsize',$data['lotsize'],array('class'=>'iv-input main-input','id'=>'lotsize_id'))?>
					</div>
				</div>
				<!-- 3.1.6行  刊登天数-->
				<div class="form-group">
					<label class="control-label col-lg-3">刊登天数</label>
					<div class="col-lg-5">
						<?php echo Html::dropDownList('listingduration',$data['listingduration'],Helper_Siteinfo::getListingDuration($data['listingtype']),['class'=>'iv-input'])?>
					</div>
				</div>
				<!-- 3.1.7-3.1.8行  -->
					<?php echo $this->render('_price',array('data'=>$data))?>
				<!-- 3.1.8行  税率-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="tax_rate" >税率<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::textInput('vatpercent',$data['vatpercent'],array('size'=>8,'class'=>'iv-input main-input','id'=>'tax_rate'))?>%
					</div>
				</div>
				<!-- 3.1.9行  私人刊登-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="private_listing" >私人刊登<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::checkBox('privatelisting',$data['privatelisting'],array('id'=>'private_listing'))?>是否设置为私人刊登(privateListing)
					</div>
				</div>
				<!-- 3.1.10行  永久在线-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="forever_online" >永久在线<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::checkBox('outofstockcontrol',$data['outofstockcontrol'],array('uncheckValue'=>0,'id'=>'forever_online'))?>卖光库存减为0
					</div>
				</div>

			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END  SUBBOX-BODY-->