<?php 
use yii\helpers\Html;
use eagle\models\EbayCategory;
?>
	<!--------------------------
	--图片与描述 (1级行 第4行)--
	---------------------------->
	<!-- BEGIN subbox-title -->
	<div class="subbox-title row">
		<div class="caption col-lg-8">
			<span class="caption-subject">图片与描述</span>
		</div>
		<div class="action">
		</div>
	</div><!-- end  subbox-title-->

	<div class="subbox-body form">
		<!-- BEGIN FORM -->
		<div class="form-horizontal">
			<h4 class="class-title">分类</h4>
			<div>
				<!-- 4.1.1行  图片-->
				<div class="form-group">
					<?php echo $this->render('_imgurl',array('data'=>$data));?>
				</div>

				<!-- 4.1.2行  描述-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="itemdescription">描述<span class="requirefix">*</span></label>
   					<div class="col-lg-8">
					<?php echo Html::textArea('itemdescription',$data['itemdescription'],array('rows'=>40,'cols'=>100,'class'=>'iv-editor','filter-mode'=>"false",'items'=>"['bold','italic', 'underline','strikethrough','|', 'forecolor', 'hilitecolor', '|', 'justifyleft', 'justifycenter','justifyright','justifyfull','|', 'insertunorderedlist', 'insertorderedlist', '|', 'outdent', 'indent', '|', 'subscript', 'superscript', '|','selectall', 'removeformat', '|','undo', 'redo','/',
					'fontname','fontsize', 'formatblock','|','cut','copy', 'paste','plainpaste','wordpaste','|','link','unlink','|','image','|'/*,'lazadaImgSpace','|'*/,'fullscreen','source']"))?>
					</div>
				</div>


				<!-- 4.1.3行  风格模板-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="template" >商品信息模板<span class="requirefix">*</span></label>
					<div class="col-lg-8">
					<div class="whole-onebox">
						<?php echo Html::dropDownList('template',$data['template'],$mytemplates,array('prompt'=>'','class'=>'iv-input fixed-boxsize'))?>
					</div>
					</div>
				</div>
				<!-- 4.1.4行  可视化模板细节-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="itemdescription_listing" >可视化模板细节<span class="requirefix">*</span></label>
					<div class="col-lg-8">
						<?php echo Html::textArea('itemdescription_listing',$data['itemdescription_listing'],array('rows'=>40,'cols'=>100,'class'=>'iv-editor','filter-mode'=>"false",'items'=>"['bold','italic', 'underline','strikethrough','|', 'forecolor', 'hilitecolor', '|', 'justifyleft', 'justifycenter','justifyright','justifyfull','|', 'insertunorderedlist', 'insertorderedlist', '|', 'outdent', 'indent', '|', 'subscript', 'superscript', '|','selectall', 'removeformat', '|','undo', 'redo','/',
							'fontname','fontsize', 'formatblock','|','cut','copy', 'paste','plainpaste','wordpaste','|','link','unlink','|','image','|'/*,'lazadaImgSpace','|'*/,'fullscreen','source']"))?>
					</div>
				</div>
				<!-- 4.1.5行  销售信息范本-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="basicinfo" >销售信息<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::dropDownList('basicinfo',$data['basicinfo'],$basicinfos,array('prompt'=>'','class'=>'iv-input'))?>
					</div>
					</div>
				</div>

				<!-- 4.1.6行  交叉销售-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="crossselling" >产品推荐<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::dropDownList('crossselling',$data['crossselling'],$crosssellings,array('prompt'=>'','class'=>'iv-input'))?>
					</div>
					</div>
				</div>
				<!-- 4.1.7行  交叉销售(二)-->
				<div class="form-group">
					<label class="control-label col-lg-3" for="crossselling_two" >产品推荐(二)<span class="requirefix">*</span></label>
					<div class="col-lg-5">
					<div class="whole-onebox">
						<?php echo Html::dropDownList('crossselling_two',$data['crossselling_two'],$crosssellings,array('prompt'=>'','class'=>'iv-input'))?>
					</div>
					</div>
				</div>


			</div><!-- END  -->
		</div><!-- END FORM -->
	</div><!-- END SUBBOX-BODY -->