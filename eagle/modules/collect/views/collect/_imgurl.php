<?php 
use yii\helpers\Html;
?>
<style>
.mutiuploader{
	display:none;
}
</style>
<p class="title">图片</p>
<div class="subdiv" style="display:none;">
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
<div class="subdiv">
<?php $images = [];?>
<?php if (is_array($data['imgurl'])):?>
<?php $i=0;?>
<?php foreach ($data['imgurl'] as $img):?>
<?php if ($i==0){
	array_push($images, ['primary'=>true,'src'=>$img]);
}else{
	array_push($images, ['src'=>$img]);
}$i++;
?>
<?php endforeach;?>
<?php endif;?>
<?php 
if((is_null($data['imgurl'])||(!is_array($data['imgurl'])&&strlen($data['imgurl'])==0))&& strlen($data['mainimg'])){
	array_push($images, ['primary'=>true,'src'=>$data['mainimg']]);
}
?>
<?= $this->renderFile(\Yii::getAlias('@modules').'/util/views/ui/img-list.php',[
	'name'=>'extra_images',
	'max'=>12,
	'primaryKey'=>'main_image',
	'images'=>$images
]) ?>
</div>