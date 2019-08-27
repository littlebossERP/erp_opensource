<?php 

use yii\helpers\Html;
use common\helpers\Helper_Array;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ckeditor/ckeditor.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/templateedit.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<div class=".container" style="width:98%;margin-left:1%;">
<form action="" method="post" id="templateedit_form">
<div class="form-group">
	<?php if (isset($mt)):?>
	<?php echo Html::hiddenInput('tid',$mt->id)?>
	<?php endif;?>
	<div class="panel panel-default">
	  <div class="panel-body">
	  	<div class="row">
		  <div class="col-lg-12"><label>创建商品信息模板</label></div>
		</div>
	    <hr/>
	    <div class="row">
	    	<div class="col-lg-9">
	    		<div class="row">
				  <div class="col-lg-1"><p class="text-right">标题</p></div>
				  <div class="col-lg-11">
				  	<?php echo Html::textInput('title',$mt->title)?><br>
				  </div>
				</div>
				<div class="row">
				  <div class="col-lg-1"><p class="text-right">缩略图</p></div>
				  <div class="col-lg-11">
					<?php echo Html::textInput('pic',$mt->pic,array('size'=>60,'id'=>'pic','onblur'=>"$('#picshow').attr('src',this.value)"))?>
					<img id="picshow" src="<?php if (isset($mt)){echo $mt->pic;}?>" width="80px"><br>
				  </div>
				</div>
				<div class="row">
				  <div class="col-lg-1"><p class="text-right">标准模板</p></div>
				  <div class="col-lg-11">
					<?php echo Html::dropDownList('publictemplate','',Helper_Array::toHashmap($publictemplates,'id','title'),array('prompt'=>'','id'=>'publictemplate','onchange'=>"selecttemplate(this.value,'pic')"))?>
					<img id="ptempshow" src="" width="90px">
					<?php echo Html::button('选择套用',array('onclick'=>"selecttemplate($('#publictemplate').val(),'content')"))?>
				  </div>
				</div>
				<div class="row">
				  <div class="col-lg-1"><p class="text-right">内容</p></div>
				  <div class="col-lg-11">
					<?php echo Html::textarea('content',$mt->content,array('rows'=>80,'cols'=>90))?>
				  </div>
				</div>
				
	    	</div>
			<div class="col-lg-3">
			  	<strong>模板变量</strong>
						<ul>
							<li><a title="变量 [TITLE] 用于替换刊登描述标题"  class="into" onclick="into('[TITLE]');">[TITLE]</a></li>
							<li><a title="描述" class="into" onclick="into('[DESCRIPTION_AND_IMAGES]');">[DESCRIPTION_AND_IMAGES]</a></li>
							<li><a title="SKU" class="into" onclick="into('[SKU]');">[SKU]</a></li>
						</ul>
						<strong>销售信息模板变量</strong>
						<ul>
							<li><a title="运输信息" class="into" onclick="into('[DELIVERY_DETAILS]');">[DELIVERY_DETAILS]</a></li>
							<li><a title="支付信息" class="into" onclick="into('[PAYMENT]');">[PAYMENT]</a></li>
							<li><a title="销售信息" class="into" onclick="into('[TERMS_OF_SALES]');">[TERMS_OF_SALES]</a></li>
							<li><a title="关于我们" class="into" onclick="into('[ABOUT_US]');">[ABOUT_US]</a></li>
							<li><a title="联系我们" class="into" onclick="into('[CONTACT_US]');">[CONTACT_US]</a></li>
						</ul>
						<strong>CrossSelling变量</strong>
						<ul>
							<?php for ($i=1;$i<=20;$i++){?>
							<a class="into" onclick="open_cross(this)">[CROSSSELLING<?php echo $i;?>]</a>
							<ul style="display:none">
								<li><a class="into" onclick="into('[image<?php echo $i;?>]');">[image<?php echo $i;?>]</a></li>
								<li><a class="into" onclick="into('[title<?php echo $i;?>]');">[title<?php echo $i;?>]</a></li>
								<li><a class="into" onclick="into('[price<?php echo $i;?>]');">[price<?php echo $i;?>]</a></li>
							</ul>
							<?php }?>
						</ul>
			</div>
	    </div>
		<div class="row">
		  <div class="col-lg-1"></div>
		  <div class="col-lg-11">
		  	<br>
		  	<?php echo Html::submitButton('保存风格模板',['id'=>'subm'])?>
		  </div>
		</div>
	  </div>
	</div>
</div>
</form>
</div>
<script>
function into(html){
	CKEDITOR.instances.content.insertHtml(html);
}
function selecttemplate(id,type){
	if(!id){
		alert('请选择一个公共风格模板');return false;
	}else{
		$.post("<?=Url::to(['/listing/ebaymuban/selecttemplatedata']) ?>",{tid:id,type:type},
		  function(data){
			if(type=='pic'){
				$('#ptempshow').attr('src',data);
				$('#pic').val(data);
				$('#picshow').attr('src',data);
			}
			if(type=='content'){
				CKEDITOR.instances.content.setData(data);
			}
		  });
	}
}
function open_cross(me){
	is_show = $(me).next('ul').css('display')=='none'?'inline':'none';
	$(me).next('ul').css('display',is_show);
}
</script>