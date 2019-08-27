<?php 
use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
	<!--------------------------
	--买家要求 (1级行 第7行)--
	---------------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">买家要求</span>
		</div>
		<div class="col-lg-4">
			<div class="action">
				<!-- 7.1行 选择与保存-->
				<div>
				<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['buyerrequire'], 'id','savename'),['id'=>'buyerrequire_profile','class'=>'profilelist','prompt'=>''])?>

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
				<!-- 7.1.1行  买家必需用paypal-->
				<div class="form-group">
					<label class="control-label col-lg-3">买家必需用paypal<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
					<?php echo Html::radioList('buyerrequirementdetails[LinkedPayPalAccount]',
							@$data['buyerrequirementdetails']['LinkedPayPalAccount'], array('true'=>'是','false'=>'否'))?>
					</div>
					</div>
				</div>


				<!-- 7.1.2行  政策违反-->
				<div class="form-group">
					<label class="control-label col-lg-3">政策违反<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<table>
					  	<tr><td>违 反 次 数</td><td> <?php echo Html::dropDownList('buyerrequirementdetails[MaximumBuyerPolicyViolations][Count]',
							@$data['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Count'],
							array('','4'=>4,'5'=>5,'6'=>6,'7'=>7))?></td></tr>
						<tr><td>评估时段</td><td><?php echo Html::dropDownList('buyerrequirementdetails[MaximumBuyerPolicyViolations][Period]',
							@$data['buyerrequirementdetails']['MaximumBuyerPolicyViolations']['Period'],
							array('','Days_30'=>'30天内','Days_180'=>'180天内'))?></td></tr></table>
					</div>
					</div>
				</div>

				<!-- 7.1.3行  不付款订单-->
				<div class="form-group">
					<label class="control-label col-lg-3">不付款订单<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<table>
					  	<tr><td>不付款次数</td><td><?php echo Html::dropDownList('buyerrequirementdetails[MaximumUnpaidItemStrikesInfo][Count]',
							@$data['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Count'],
							array('','2'=>2,'3'=>3,'4'=>4,'5'=>5))?></td></tr>
						<tr><td>评估时段</td><td><?php echo Html::dropDownList('buyerrequirementdetails[MaximumUnpaidItemStrikesInfo][Period]',
							@$data['buyerrequirementdetails']['MaximumUnpaidItemStrikesInfo']['Period'],
							array('','Days_30'=>'30天内','Days_180'=>'180天内','Days_360'=>'360天内'))?></td></tr></table>
					</div>
					</div>
				</div>

				<!-- 7.1.4行  10天内限制拍卖-->
				<div class="form-group">
					<label class="control-label col-lg-3">10天内限制拍卖<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
			  			<?php echo Html::dropDownList('buyerrequirementdetails[MaximumItemRequirements][MaximumItemCount]',
					@$data['buyerrequirementdetails']['MaximumItemRequirements']['MaximumItemCount'],array('','2'=>2,'3'=>3,'4'=>4,'5'=>5),['class'=>'iv-input main-input'])?>
						<span class="text-format-D">次</span>
					</div>
					</div>
				</div>

			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END  SUBBOX-BODY-->