<?php 
use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
	<!--------------------------
	--收货与退货 (1级行 第6行)--
	---------------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">收货与退货</span>
		</div>
		<div class="col-lg-4">
			<div class="action">
			<!-- 6.1行 选择与保存-->
				<div>
				<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['returnpolicy'], 'id','savename'),['id'=>'returnpolicy_profile','class'=>'profilelist','prompt'=>''])?>

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

			<!-- <h4 class="class-title"></h4> -->
			<div>
					<!-- 6.1.1行  收款方式-->
					<div class="form-group">
						<label class="control-label col-lg-3" for="paymentmethods" >收款方式<span class="requirefix">*</span></label>
						<div class="col-lg-5">
							<div class="whole-onebox">
							<?php echo Html::checkBoxList('paymentmethods',@$data['paymentmethods'], $paymentoption)?>
							</div>
						</div>
					</div>

					<!-- 6.1.2行  立即付款-->
					<div class="form-group">
						<label class="control-label col-lg-3" for="autopay" >立即付款<span class="requirefix">*</span></label>
						<div class="col-lg-5">
						<div class="whole-onebox">
							<?php echo Html::checkBox('autopay',@$data['autopay'],array('uncheckValue'=>0))?>是否要求买家立即付款
						</div>
						</div>
					</div>

					<!-- 6.1.3行  付款说明-->
					<div class="form-group">
						<label class="control-label col-lg-3">付款说明<span class="requirefix">*</span></label>
						<div class="col-lg-5">
						<div class="whole-onebox">
							<?php echo Html::textArea('shippingdetails[PaymentInstructions]',@$data['shippingdetails']['PaymentInstructions'],array('rows'=>5,'cols'=>60,'class'=>'iv-input'))?>
						</div>
						</div>
					</div>
					<!-- 6.1.4行  -->
					<div class="form-group">
						<?php echo $this->render('_returnpolicy',array('data'=>$data,'return_policy'=>$returnpolicy))?>
					</div>

					<!-- 6.1.4行  商品所在地-->
					<div class="form-group">
						<label class="control-label col-lg-3">商品所在地<span class="requirefix">*</span></label>
						<div class="col-lg-8">
							<div class="whole-onebox">
							<table>
								<tr>
								<th>国家</th>
								<td>
								<?php echo Html::dropDownList('country',@$data['country'],$locationarr)?>
								</td>
								</tr>
								<tr>
								<th>地区</th>
								<td>
								<?php echo Html::textInput('location',@$data['location'])?>
								</td>
								</tr>
								<tr>
								<th>邮编</th>
								<td>
								<?php echo Html::textInput('postalcode',@$data['postalcode'])?>
								</td>
								</tr>
							</table>
							</div>
						</div>
					</div>



			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END  SUBBOX-BODY-->