<?php
use eagle\modules\util\helpers\ImageHelper;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/util/imageLibraryList.js?v=1.1", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("util.imageLibrary.listInit()", \yii\web\View::POS_READY);
 
 
$this->title = "文件库";
?>
<style>
.img-info>div{margin: 5px 0;}
input[name='add_image_url']{width: 80%;}
.image-name{
	text-overflow: ellipsis;
	overflow: hidden;
	text-align: center;
	white-space: nowrap;
	line-height: 20px;
}
.dropdown-menuTree {
    min-width: 160px;
    padding: 0;
    margin: 2px 0 0;
    font-size: 13px;
    text-align: left;
    background-color: #fff;
    /*border: 1px solid #ccc;*/
}
.dropdown-menuTree li.tit1 {
    height: 32px;
    line-height: 32px;
    /*color: #000;*/
    list-style: none;
    vertical-align: top;
    /*padding-left: 5px;*/
	font-size: 12px;
}
.pRight10 {
    padding-right: 10px;
}
.mRight10 {
    margin-right: 10px;
}
ul.chooseTree {
    list-style: none;
    margin: 0;
    padding-left: 10px;
    width: 100%;
    margin-bottom: 5px;
    /*color: #000;*/
	font-size: 12px;
}
ul.chooseTree ul {
    list-style: none;
    margin: 0;
    padding-left: 17px;
}
ul.chooseTree li div.outDiv {
    height: 22px;
    padding-top: 3px;
}
ul.chooseTree li span.chooseTreeName {
    cursor: pointer;
    padding-left: 3px;
    padding-right: 3px;
}
ul.chooseTree span.glyphicon-triangle-right, ul.chooseTree span.glyphicon-triangle-bottom {
    cursor: pointer;
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
.margin-left-43{
	margin-left: 43px;
}
</style>
<ul class="nav nav-tabs" role="tablist" style="padding-top: 10px">
    <li role="presentation" class="active"><a href="#image-library" aria-controls="image-library" role="tab" data-toggle="tab" id="photo-list">图片库</a></li>
    <?php if(isset($permissions['data']['level_version'])&&($permissions['data']['level_version'] == 'v2.5'||$permissions['data']['level_version'] == 'v3')):?>
    <li role="presentation"><a href="/util/file-upload/show-file" aria-controls="pdf-library" role="tab" data-toggle="tab" id="file-list">文件库</a></li>
    <?php endif;?>
</ul>
<div class="tab-content" style="padding-top: 15px;">
<div class="tab-pane active" id="image-library" role="tabpanel">
<div class="image-library flex-row" >
	<DIV class="image-usage" style="flex: 0 1 200px;">
		<table class="table table-bordered">
			<tbody>
				<tr>
					<td>
						<div style="position: relative;">
							<b>图片库使用情况</b>
						</div>
					</td>
					<td>
					</td>
				</tr>
				<tr class="hidden">
					<td>总容量</td>
					<td id="lib-size"><?= round($usage['count_library_size']/1024/1024,2) ?>M</td>
				</tr>
				<tr>
					<td>图片已使用</td>
					<td id="lib-usage"><?= round($usage['imageInfo']['total_size']/1024/1024,2) ?>M</td>
				</tr>
				<tr>
					<td>图片数</td>
					<td id="img-num"><?= $usage['imageInfo']['image_number'] ?></td>
				</tr>
				<!--  <tr>
					<td>文件库已使用</td>
					<td id="lib-usage"><?= round($usage['fileInfo']['total_size']/1024/1024,2) ?>M</td>
				</tr>
				<tr>
					<td>文件数</td>
					<td id="img-num"><?= $usage['fileInfo']['pdf_number'] ?></td>
				</tr>-->
				<tr class="hidden">
					<td>剩余空间</td>
					<td id="lib-available" class="fRed"><?= round(($usage['residual_size'])/1024/1024,2) ?>M</td>
				</tr>
			</tbody>
		</table>

		<table class="table table-bordered">
			<tbody>
				<tr>
					<td style="padding: 0px;">
						<ul class="dropdown-menuTree col-xs-12" id="treeTab">
								<li class="liBorder bgColor5 tit1" name="liOne">
									<span>&nbsp;&nbsp;<b>图片分类</b></span>
									<a href="javascript:void(0);" class="pull-right mRight10 aClick" data-toggle="modal" data-target="#editCategory">
										<span class="txt">设置</span>
									</a>
								</li>
								<div id="liOne">
									<li>
										<div class="pRight10" id="categoryTreeA">
											<ul class="chooseTree">
												<li groupid="0" groupname="所有分类">
													<div class="outDiv"><span class="gly glyphicon glyphicon-triangle-bottom pull-left" data-isleaf="open"></span><div class="pull-left"><label><span class="chooseTreeName" onclick="null" data-groupid="0">所有分类<span class="num"></span></span><span class=""></span></label></div></div>
													<?php echo $imagesClassifica_data['imagesClassifica']['html']; ?>
												</li>
											</ul>
										</div>
									</li>
								</div>
						</ul>
					</td>
				</tr>
			</tbody>
		</table>		
		
		
	</DIV>
	
	<DIV class="image-list" style="flex: 1; margin-left: 10px;">
		<div class="iv-alert alert-remind" style="margin:10px;">
			温馨提示：如果图片库空间不够，可以删除最早刊登成功商品的图片（非描述部分图片）。<br />
		</div>
		<div class="search">			
			<?php 
			$batchDo = array();
			$batchDo['check-all'] = "全选";
			$batchDo['uncheck'] = "全不选";
			$batchDo['batch-delete'] = "批量删除";
			?>
			<!--<?=Html::dropDownList('batch-do','',$batchDo,['prompt'=>'批量操作','class'=>'eagle-form-control batch-do','onchange'=>'util.imageLibrary.batchDo(this);'])?>-->
			
			<div class="btn-group">
				<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				移动到<span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<?php
						echo $imagesClassifica_data['imagesClassificaDropDownList'];
					?>
				</ul>
			</div>	
			
			<div class="btn-group margin-left-4">
				<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				批量操作<span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a data-val="check-all" href="#" onclick="util.imageLibrary.batchDo(this)">全选</a></li>
					<li><a data-val="uncheck" href="#" onclick="util.imageLibrary.batchDo(this)">全不选</a></li>
					<li><a data-val="batch-delete" href="#" onclick="util.imageLibrary.batchDo(this)">批量删除</a></li>
				</ul>
			</div>		
						
			<button type="button" class="btn btn-info btn-sm margin-left-4" onclick="util.imageLibrary.showUpload();" >上传图片</button>
			
			<div class="btn-group margin-left-43">
			<form action="/util/image/show-library" method="get" id="imageSearch" name="image_search" style="margin-top: 3px;">
				图片名称：<?=Html::textInput('name',@$_REQUEST['name'],['id'=>'img-name','class'=>'eagle-form-control'])?>
				<input type="submit" value="搜索"  id="search" class="btn btn-success btn-sm">
			</form>
			</div>
		</div>
		
		<div role="image-uploader-container" style="display: none;">
			<div class="btn-group" role="group">
				<button type="button" class="btn btn-info" id="btn-uploader" >本地上传图片</button>
				<button type="button" class="btn btn-info btn-group" id="btn-upload-from-lib" data-toggle="modal" data-target="#addImagesBox" ><?= TranslateHelper::t('添加 URL 图片'); ?></button>
			</div>
			<button type="button" class="btn btn-info" id="re-add-url" style="display: none;" onclick="util.imageLibrary.upUrlImg();">上传url图片</button>
		</div>
		
		<?php if(!empty($images)):?>
		<div style="text-align: left;">
			<div class="btn-group" >
				<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
			</div>
			<?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 30 , 60 , 90 , 120 ) , 'class'=>'btn-group dropdown']);?>
		</div>
		<?php endif;?>
		
		<div class="imgBox-container flex-row" style="flex-wrap: wrap;">
			<?php if(!empty($images)):?>
				<?php foreach ($images as $oneImage):?>
				<DIV class="imgBox flex-column" style="flex: 0 0 230px; padding: 10px; margin: 10px 10px 0 0;border: 1px solid #ccc;">
					<DIV class="">
						<LABEL style="cursor:default;">
							<?=Html::checkbox('is_check',false,['value'=>$oneImage['id'],'class'=>'eagle-form-control','style'=>'margin: 0;'])?>
							<button type="button" class="close" aria-label="Close" onclick="util.imageLibrary.deleteImg(<?= $oneImage['id'] ?>)" ><span aria-hidden="true">×</span></button>
							<br />
							<img src="<?= $oneImage['thumbnail_url'] ?>" style="width: 210px;height: 210px;"/>
						</LABEL>
					</DIV>
					<DIV class="img-info" style="max-width: 210px;">
						<DIV class="image-name"><?= $oneImage['original_name'] ?></DIV>
						<div style="float: left;"><?= $oneImage['original_width'] ?>px X <?= $oneImage['original_height'] ?>px</div>
						<div style="float: right;"><?= round($oneImage['origin_size'] /1024) ?>K</div>
						<div style="clear: both;"></div>
						<!-- <div style="float: right;cursor: pointer;line-height: 28px;" ondblclick="util.imageLibrary.showUrl(this);"><font>双击获取图片链接</font>  <?= html::textInput('origin_url',$oneImage['origin_url'],['class'=>'eagle-form-control','style'=>'display: none;'])?></div> -->
						<div>图片链接 <?= html::textInput('origin_url',$oneImage['origin_url'],['class'=>'eagle-form-control'])?></div>
						<div style="clear: both;"></div>
					</DIV>
				</DIV>
				<?php endforeach;?>
			<?php endif;?>
		</div>
		<?php if(!empty($pages)):?>
		<div style="text-align: left;">
			<div class="btn-group" >
				<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
			</div>
			<?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 30 , 60 , 90 , 120 ) , 'class'=>'btn-group dropup']);?>
		</div>
		<?php endif;?>
	</DIV>

</div>
</div>
</div>

<div class="modal imgLibExpand" id="imgLibExpand" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content bs-example bs-example-tabs">
			<!--modalHead-->
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
				<h4 class="modal-title" id="myModalLabel">图片库扩容</h4>
			 </div>
			<div class="modal-body tab-content p10">
			<P>温馨提示：如果图片库空间不够，可以删除刊登成功商品的图片（非描述部分图片）。</P>
			<br />
			<P style="color: red;">提别提示：eBay平台商品如果有多张商品图片（不包含描述部分图片）则可以删除，如果只有一张商品图片（不包含描述部分图片）则不可删除。</P>
			<p style="text-align: center;margin: 30px 0;font: bold 20px Arial;">图片扩容敬请期待</p>
			</div>
			<!--modalFoot-->
			<div class="modal-footer" style="clear:both;">
				<button type="button" class="btn btn-default" data-names="" data-dismiss="modal">确定</button>
			</div>
		</div>
	</div>
</div>