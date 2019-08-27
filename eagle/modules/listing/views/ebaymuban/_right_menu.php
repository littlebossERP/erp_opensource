<!-- 快捷导航 -->
	<div class="left_pannel" id="floatnav">
		<div class="left_pannel_first"></div>
		<p onclick="goto('account')"><a>账号</a></p>
		<p onclick="goto('siteandspe')"><a>平台与细节</a></p>
		<p onclick="goto('titleandprice')"><a>标题与价格</a></p>
		<p onclick="goto('picanddesc')"><a>图片与描述</a></p>
		<p onclick="goto('shippingset')"><a>物流设置</a></p>
		<p onclick="goto('returnpolicy')"><a>收货与退款</a></p>
		<p onclick="goto('buyerrequire')"><a>买家要求</a></p>
		<p onclick="goto('plusmodule')"><a>增值设置</a></p>
		<div class="left_pannel_last"></div>
	</div>


	<!-- 操作按钮区域  START-->
<div class="btndo">
<?php echo Html::hiddenInput('act','',['id'=>'act'])?>
<?php echo Html::button('检测',array('onclick'=>'doaction("verify")','class'=>'donext btn btn-warning'))?>
<?php echo Html::button('保存',array('onclick'=>'doaction("save")','class'=>'donext btn btn-success'))?>
<?php echo Html::button('预览',array('onclick'=>'preview()','class'=>'donext btn'))?>
<?php if (strlen(@$data['mubanid'])):?>
<?php echo Html::button('立即刊登',array('onclick'=>'doaction("additem")','class'=>'donext btn btn-default'))?>
<?php echo Html::button('另存为新刊登范本',array('onclick'=>'doaction("savenew")','class'=>'donext btn btn-default'))?>
<?php endif;?>
<?php echo Html::button('重复刊登检测',array('onclick'=>'checkitem()','class'=>'donext btn btn-default'))?>
<?=Html::submitButton('',['style'=>'display:none;'])?>
<?php echo Html::hiddenInput('uuid',Helper_Util::getLongUuid())?>
</div>
<!-- 操作按钮区域 end -->



		<!-- 设置店铺类目的modal -->
		<!-- 模态框（Modal） -->
		<div class="modal fade" id="categorysetModal" tabindex="-1" role="dialog" 
		   aria-labelledby="myModalLabel" aria-hidden="true">
		   <div class="modal-dialog">
		      <div class="modal-content">
		         
		      </div><!-- /.modal-content -->
			</div><!-- /.modal -->
		</div>

		<!-- 搜索刊登类目的modal -->
		<div class="modal fade" id="searchcategoryModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		   <div class="modal-dialog" style="width: 800px;">
		      <div class="modal-content">

		      </div><!-- /.modal-content -->
			</div><!-- /.modal -->
		</div>


			    <div class="row">
			<!-- <div class="col-md-12"> -->
				<div class="foot-label-format">
					<span>footaaaaaaaaaaaaaaaaaaaaaaaaaaaa</span>
				</div>
			<!-- </div> -->
		</div>