<?php 
use yii\helpers\Html;
?>
<style>
.mutiuploader{
	display:none;
}
</style>
<p class="title">图片</p>
<div class="subdiv">
第一张图片默认为主图,同时使用多张图片会产生额外费用,最多使用12张.多图时会自动上传到ebay服务器,请上传JPEG、BMP、TIF、或者GIF格式的图片
  	<div id="divimgurl">
	<?php if (is_array($data['imgurl'])):?>
	<?php foreach ($data['imgurl'] as $img):?>
		<div>
		<img src="<?php echo $img?>" width="50px" height="50px" > <?php echo Html::textInput('imgurl[]',$img,array('size'=>60,'class'=>'iv-input','onblur'=>'javascript:imgurl_input_blur(this)','status'=>1))?>
		<?php echo Html::button('删除',array('onclick'=>'javascript:delImgUrl_input(this)','class'=>'iv-btn btn-search'))?>
		</div>
	<?php endforeach;?>
	<?php else:?>
		<div>
		<img src="" width="50px" height="50px"> <?php echo Html::textInput('imgurl[]','',array('size'=>60,'class'=>'iv-input','onblur'=>'javascript:imgurl_input_blur(this)'));?>
		<?php echo Html::button('删除',array('onclick'=>'javascript:delImgUrl_input(this)','class'=>'iv-btn btn-search'))?>
		
		<!-- 	添加本地上传按钮 -->
		<?php echo Html::button('本地上传',['onclick'=>'javascript:localimgup(this)','class'=>'iv-btn btn-search'])?>
		<?php echo Html::button('上传到ebay',array('onclick'=>'javascript:UploadSiteHostedPictures(this)','class'=>'iv-btn btn-search'))?>
		</div>
	<?php endif;?>
	</div><br>
	<?php echo Html::button('添加一张图片',array('onclick'=>'javascript:Addimgurl_input();return false;','class'=>'iv-btn btn-search'))?>
	<?php echo Html::button('批量上传图片',array('onclick'=>'mutiupload();','class'=>'iv-btn btn-search'))?>
		<div class="mutiuploader" role="image-uploader-container">
			<div class="btn-group" role="group">
				<button type="button" class="btn btn-info" id="btn-uploader" >Upload Images</button>
			</div>
		</div>
	<?=Html::input('file','product_photo_file','',['id'=>'img_tmp','class'=>'hidden'])?>
</div>
