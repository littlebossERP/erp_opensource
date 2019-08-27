<?php
use eagle\modules\util\helpers\ImageHelper;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/util/pdfLibraryList.js?v=1.0", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("pdfLibrary.init()", \yii\web\View::POS_READY);

$this->title = "文件库";
?>
<style>
.img-info>div{margin: 5px 0;}


.pRight10 {
    padding-right: 10px;
}
.mRight10 {
    margin-right: 10px;
}

.pull-left {
    float: left!important;
}
.fColor1 {
    color: #428bca;
}
.bgColor {
    background: rgb(1,189,240);
	color: rgb(255,255,255);
}
.margin-left-4{
	margin-left: 4px;
}
#pdf_table td{
	text-align: center;
}
.pdf-usage table,.pdf-usage table td{
	border: 0px !important;
}
</style>
<ul class="nav nav-tabs" role="tablist" style="padding-top: 10px">
    <li role="presentation"><a href="/util/image/show-library" aria-controls="image-library" role="tab" data-toggle="tab" id="photo-list">图片库</a></li>
    <li role="presentation" class="active"><a href="#pdf-library" aria-controls="pdf-library" role="tab" data-toggle="tab" id="file-list">文件库</a></li>
</ul>
<div class="tab-content" style="padding-top: 15px;">
<div class="tab-pane active" id="image-library" role="tabpanel">
<div class="pdf-library flex-row" >
	<DIV class="pdf-usage" style="flex: 0 1 200px;">
		<table class="table table-bordered">
			<tbody>
				<tr>
					<td>
						<div style="position: relative;">
							<b>上传空间使用情况</b>
						</div>
					</td>
					<td></td>
				</tr>
				<tr>
					<td>总容量</td>
					<td id="lib-size"><?= round($pdf_usage['count_library_size']/1024/1024,2) ?>M</td>
				</tr>
				<tr>
					<td>图片已使用</td>
					<td id="lib-usage"><?= round($pdf_usage['imageInfo']['total_size']/1024/1024,2) ?>M</td>
				</tr>
				<tr>
					<td>图片数</td>
					<td id="img-num"><?= $pdf_usage['imageInfo']['image_number'] ?></td>
				</tr>
				<tr>
					<td>文件库已使用</td>
					<td id="lib-usage"><?= round($pdf_usage['fileInfo']['total_size']/1024/1024,2) ?>M</td>
				</tr>
				<tr>
					<td>文件数</td>
					<td id="img-num"><?= $pdf_usage['fileInfo']['pdf_number'] ?></td>
				</tr>
				<tr>
					<td>剩余空间</td>
					<td id="lib-available" class="fRed"><?= round($pdf_usage['residual_size']/1024/1024,2) ?>M</td>
				</tr>
			</tbody>
		</table>

		
	</DIV>
	
	<DIV class="pdf-list" style="flex: 1; margin-left: 10px;">
		<div class="iv-alert alert-remind" style="margin:10px;">
			温馨提示：如果文件库空间不够，可以删除弃用的文件，删除文件时，请确保文件当前没有被产品详细页面的提供“附件下载”使用中，如果有产品使用该文件的，请从产品编辑页面删除这个附件链接，或者删除该产品。<br />
		</div>
		<div class="search">
			<form action="/util/file-upload/show-file" method="get" id="pdfSearch" name="pdf_search">
				文件名称：<?=Html::textInput('search_file_name',@$_REQUEST['search_file_name'],['id'=>'img-name','class'=>'eagle-form-control'])?>
				<input type="submit" value="搜索"  id="search" class="btn btn-success btn-sm">
			</form>
			
			<input type="button" class="btn btn-success" value="上传新文件" id="file-select">
			<button type="button" class="btn btn-primary margin-left-4" onclick="pdfLibrary.deleteBatchPdf();" >批量删除</button>
			<input type="file" class="btn-pdf-upload hidden" multiple="multiple" value="从本地选择图片" iv-uploaded="iv-uploaded" id="file-upload">
		</div>
		
		
		<?php if(!empty($pdfs)):?>
		<div style="text-align: left;">
			<div class="btn-group" >
				<?php echo LinkPager::widget(['pagination'=>$pdf_pages,'options'=>['class'=>'pagination']]);?>
			</div>
			<?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pdf_pages , 'pageSizeOptions'=>array( 30 , 60 , 90 , 120 ) , 'class'=>'btn-group dropdown']);?>
		</div>
		<?php endif;?>
		
		<table id="pdf_table" class="table table-striped table-bordered table-hover">
    		<thead>
    			<tr>
    				<th style="width:20px;"><input type="checkbox" id="chk_all"></th>
    				<th style="width: 20%;">文件名称</th>
    				<th style="width: 40%;">文件链接</th>
    				<th>文件大小</th>
    				<th>创建时间</th>
    				<th>操作</th>
    			</tr>
    		</thead> 
			<?php if(!empty($pdfs)):?>
			<tbody>
				<?php foreach ($pdfs as $onePdf):?>
				<tr data-id="<?= $onePdf['id']?>" class="striped-row">
				    <td>
				        <input type="checkbox" id="chk_one_<?=$onePdf['id'];?>" name="parent_chk[]" value="<?=$onePdf['id']?>">
				    </td>
				    <td><a href="<?php echo $onePdf['origin_url']?>" target="_blank"><?php echo $onePdf['original_name']?></a></td>
				    <td><?php echo $onePdf['origin_url'];?><input class="hidden hidden_url" type="text" value="<?php echo $onePdf['origin_url'];?>"></td>
				    <td><?php echo round($onePdf['origin_size']/1024/1024,2);?> M</td>
				    <td><?php echo $onePdf['create_time'];?></td>
				    <?php 
				    //获取查找key值
				    $key = '';
				    if(!empty($onePdf['file_key'])){
				        $key_array = explode('/', $onePdf['file_key']);
				        if(isset($key_array[2])){
				            $key = $key_array[2];
				        }
				    }
				    ?>
				    <td>
				        <span><input value="复制链接"  type="button" onclick="pdfLibrary.copyUrl(this);" class="btn btn-warning btn-xs hidden"></span>
				        <span><input value="删除"  type="button" class="btn btn-danger btn-xs" onclick="pdfLibrary.deleteOneProduct(<?php echo $onePdf['id']?>,'<?php echo $key?>')"></span>
				    </td>
				</tr>
				<?php endforeach;?>
			</tbody>
			<?php endif;?>
		</table>
		<?php if(!empty($pdf_pages)):?>
		<div style="text-align: left;">
			<div class="btn-group" >
				<?php echo LinkPager::widget(['pagination'=>$pdf_pages,'options'=>['class'=>'pagination']]);?>
			</div>
			<?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pdf_pages , 'pageSizeOptions'=>array( 30 , 60 , 90 , 120 ) , 'class'=>'btn-group dropup']);?>
		</div>
		<?php endif;?>
	</DIV>

</div>
</div>
</div>
