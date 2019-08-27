<?php 
use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
	<!--------------------------
	--物流设置 (1级行 第5行)--
	---------------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">物流设置</span>
		</div>
		<div class="col-lg-4">
			<div class="action">
			<!-- 5.1行 选择与保存-->
				<div>
				<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['shippingset'], 'id','savename'),['id'=>'shippingset_profile','class'=>'profilelist','prompt'=>''])?>

				<button class="iv-btn btn-warning profile_load" type="button">读取</button>
				<button class="iv-btn btn-default profile_del" type="button">删除</button>
				<button class="iv-btn btn-search profile_save" type="button">保存</button>
				</div>
				<div class="save_name_div">
					<?=Html::textInput('save_name','',['class'=>'save_name'])?>
					<button class="iv-btn btn-search profile_save_btn" type="button">确定</button>
					<button class="iv-btn btn-default profile_cancle" type="button">取消</button>
				</div>
			</div>
		</div>
	</div><!-- end  subbox-title-->

	<div class="subbox-body form">
		<!-- BEGIN FORM -->
		<div class="form-horizontal">
			<!-- <h4 class="class-title">分类</h4> -->


			<div>

				<!-- 5.1.1行  运输类型-->
				<div class="form-group">
					<label class="control-label col-lg-3">运输类型<span class="requirefix">*</span></label>
					<div class="col-lg-8">
					<div class="whole-onebox">
					<?php echo $this->render('_shipping_calculate',array('data'=>$data))?>
					</div>
					</div>
				</div>
				<!-- 5.1.2行  境内物流-->
				<div class="form-group">
					<label class="control-label col-lg-3">境内物流<span class="requirefix">*</span></label>
					<div class="col-lg-8">
						<?php echo $this->render('_shipping_service',array('data'=>$data,'shippingserviceall'=>$shippingserviceall))?>
					</div>
				</div>
				<!-- 5.1.3行  境外物流-->
				<div class="form-group">
					<label class="control-label col-lg-3">境外物流<span class="requirefix">*</span></label>
					<div class="col-lg-8">
						<?php echo $this->render('_shipping_inservice',array('data'=>$data,'shippingserviceall'=>$shippingserviceall))?>
					</div>
				</div>
				<!-- 5.1.4行  屏蔽目的地-->
				<div class="form-group">
					<label class="control-label col-lg-3">屏蔽目的地<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo $this->render('_shipping_excludeship',array('data'=>$data))?>
					</div>
				</div>

				<!-- 5.1.5行  运费加税-->
				<?php echo $this->render('_shipping',array('data'=>$data,'salestaxstate'=>@$salestaxstate,'shippingserviceall'=>$shippingserviceall))?>

				<!-- 5.1.6行  包裹处理时间-->
				<div class="form-group">
					<label class="control-label col-lg-3">包裹处理时间<span class="requirefix">*</span></label>
					<div class="col-lg-5">
						<?php echo Html::dropDownList('dispatchtime',$data['dispatchtime'], $dispatchtimemax,['class'=>'iv-input'])?>
					</div>
				</div>


			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END  SUBBOX-BODY-->