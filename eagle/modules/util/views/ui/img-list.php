<?php 
$_data = array_merge([
	'name'=>'extra_images',
	'max'=>8,
	'checkbox'=>false,
	'primaryKey'=>'main_image',
	'btn'=>[
		'kebianji','shanchu','link'
	],
	'images'=>[]
],compact('name','max','checkbox','primaryKey','btn','images'));

extract($_data);



?>

<div class="iv-image-upload">
	<?php $id = rand(0,9999); ?>
	<div id="select-url-<?=$id?>" role="modal">
		<div role="form" class="iv-form">
			<div style="margin-bottom:10px;" iv-template-added="iv-add-url-<?=$id?>" >
				URL 地址：<input type="text" name="url[]" value="" class="iv-input" style="width:300px;" />
			</div>
			<div style="margin-bottom:10px;" iv-template="iv-add-url-<?=$id?>" >
				URL 地址：<input type="text" name="url[]" value="" class="iv-input" style="width:300px;" />
			</div>
			<a class="add-column text-info input-area" data-id="iv-add-url-<?=$id?>" style="display:block;margin:10px 0px;width:100%;">
				<i class="iconfont icon-jiahao2"></i>
				添加一个新URL地址
			</a>
		</div>
	</div>
	<div class="iv-image-header clearfix">
		<?php if($checkbox): ?>
		<a class="pull-left"><span class="iconfont icon-shanchu"></span> 批量删除</a>
		<?php endif; ?>
		<div class="pull-right">
			<input type="file" data-url="<?= @$postLocalFileUrl?>" class="iv-btn btn-success iv-image-upload-upload btn-upload" multiple="multiple" value="从本地选择图片" />
			<a class="iv-btn btn-success select-url" target="#select-url-<?=$id ?>" title="从网络地址（URL）选择图片" btn-resolve >从网络地址（URL）选择图片</a>
			<a href="/util/image/select-image-lib" target="_modal" class="iv-btn btn-success select-lib" title="图片库" btn-resolve btn-reject>从图片库选择图片</a>
		</div>
	</div>
	<div class="iv-image-body">
		<ul class="sortable clearfix" id="<?= $id ?>" data-max="<?=$max?>">
			<?php foreach($images as $img): ?>
			<li class="iv-image-box" iv-template-added="iv-image-upload-<?= $id ?>" >
				<div class="iv-image-box-header">
					<input type="checkbox" name="<?= $name ?>[]" value="<?= $img['src'] ?>" checked="checked" <?= $checkbox?'':'style="display:none"' ?> />

					<label class="primary">
						<input type="radio" name="<?= $primaryKey ?>" value="<?= $img['src'] ?>" <?= isset($img['primary']) && $img['primary']==true?'checked="checked"':'' ?> />
						<span class='text-danger'>主图</span>
						<a>设为主图</a>
					</label>
				</div>
				<div class="iv-image-box-body">
					<img width="100%" height="100%" src="<?= $img['src'] ?>" title="<?= isset($img['title'])? $img['title']:'' ?>" />
					<div class="iv-image-box-cover"></div>
					<div class="iv-image-box-cover2">
						<a class="iv-btn btn-none copy-url" style="padding:2px 5px;" data-clipboard-text="<?= $img['src'] ?>">复制地址</a>
						<a class="cover-close iconfont icon-guanbi"></a>
						<p><?= $img['src'] ?></p>
					</div>
				</div>
				<div class="iv-image-box-footer">
					<?php 
					if(in_array('kebianji',$btn)):
					?>
					<a title="在线美图" class="iv-image-box-footer-icon iconfont icon-kebianji"></a>
					<?php endif; ?>
					<a title="删除" class="iv-image-box-footer-icon iconfont icon-shanchu"></a>
					<a title="复制链接" class="iv-image-box-footer-icon iconfont icon-link"></a>
				</div>
			</li>
			<?php endforeach; ?>
			<li class="iv-image-box" iv-template="iv-image-upload-<?= $id ?>">
				<div class="iv-image-box-header">
					<input type="checkbox" name="<?= $name ?>[]" <?= $checkbox?'':'style="display:none"' ?> />

					<label class="primary">
						<input type="radio" name="<?= $primaryKey ?>" value="" />
						<span class='text-danger'>主图</span>
						<a>设为主图</a>
					</label>
				</div>
				<div class="iv-image-box-body">
					<img width="100%" height="100%" src="" title="" />
					<div class="iv-image-box-cover"></div>
					<div class="iv-image-box-cover2">
						<a class="iv-btn btn-none copy-url" style="padding:2px 5px;" data-clipboard-text>复制地址</a>
						<a class="cover-close iconfont icon-guanbi"></a>
						<p class="image-src"></p>
					</div>
				</div>
				<div class="iv-image-box-footer">
					<?php 
					if(in_array('kebianji',$btn)):
					?>
					<a title="在线美图" class="iv-image-box-footer-icon iconfont icon-kebianji"></a>
					<?php endif; ?>
					<a title="删除" class="iv-image-box-footer-icon iconfont icon-shanchu"></a>
					<a title="复制链接" class="iv-image-box-footer-icon iconfont icon-link"></a>
				</div>
			</li>
		</ul>
	</div>
	<div class="iv-image-footer">
	</div>

</div>

