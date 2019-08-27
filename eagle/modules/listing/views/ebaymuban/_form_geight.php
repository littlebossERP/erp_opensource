<?php 
use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
	<!--------------------------
	--增值设置 (1级行 第8行)--
	---------------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">增值设置</span>
		</div>
		<div class="col-lg-4">
			<div class="action">
				<div>
				<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['plusmodule'], 'id','savename'),['id'=>'plusmodule_profile','class'=>'profilelist ','prompt'=>''])?>

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
			<div><!-- START -->
				<!-- 8.1.1行  图片显示方式-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="gallery" >图片显示方式<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::radioList('gallery',@$data['gallery'],array('0'=>'不使用','Featured'=>'Featured($)','Gallery'=>'Gallery($)','Plus'=>'Plus($)'))?>
					</div>
					</div>
				</div>

				<!-- 8.1.2行  样式-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="listingenhancement" >样式<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::checkBoxList('listingenhancement',@$data['listingenhancement'],$feature_array)?>
					</div>
					</div>
				</div>

				<!-- 8.1.3行  计数器-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="hitcounter" >计数器<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::radioList('hitcounter',@$data['hitcounter'],array('NoHitCounter'=>'不用计数器','BasicStyle'=>'BasicStyle','RetroStyle'=>'RetroStyle'))?>
					</div>
					</div>
				</div>
				<!-- 8.1.4行  国际站点-->
				<div class="form-group">
					<label class="control-label col-lg-3">国际站点<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo in_array($data['siteid'],array(0,2))?'ebay.co.uk':'ebay.com and ebay.ca'?>
					</div>
					</div>
				</div>
				<!-- 8.1.5行  备注-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="desc" >备注<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::textInput('desc',$data['desc'],['class'=>'iv-input main-input'])?>
					</div>
					</div>
				</div>

			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END  SUBBOX-BODY-->