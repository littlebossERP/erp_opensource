<?php 
$this->title='TestPage';

function getVal($name){
	return isset($_GET[$name])? $_GET[$name]:'';
}
// var_dump($_GET);die;
?>
<div class="panel panel-success">
	<form action="/ajax/test-api-run" method="post" target="#test_result" ajax-form class="form-horizontal pull-left" style="width:45%;padding:10px;">
		<label for="">类及方法名</label>
		<input type="text" class="form-control" name="className" placeholder="类名" required value="<?= getVal('className')?>" />
		<input type="text" class="form-control" name="methodName" placeholder="方法名" required onblur="window.document.title = this.previousElementSibling.value.split('\\').pop() + '.' + this.value;" value="<?= getVal('methodName')?>" />

		<div class="form-group">
			<label class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
				uid设置
			</label>
			<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
				<input type="text" class="form-control" name="uid" value="<?= getVal('uid')?>" />
			</div>
		</div>

		<label for="">构造函数参数</label>
		<?php if(isset($_GET['construct'])):
		for($i=0;$i<count($_GET['construct']); $i++):
		if(!$_GET['construct'][$i]) continue; ?>
		<div class="form-group" iv-template-added="addConstructParam">
			<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
				<input type="text" class="form-control" name="construct[]" value="<?=$_GET['construct'][$i]?>" />
			</div>
			<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
				<input type="text" class="form-control" name="c_value[]" value="<?=$_GET['c_value'][$i]?>" />
			</div>
		</div>
		<?php endfor;endif; ?>
		<div class="form-group" iv-template="addConstructParam">
			<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
				<input type="text" class="form-control" name="construct[]" />
			</div>
			<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
				<input type="text" class="form-control" name="c_value[]" />
			</div>
		</div>
		<a onclick="$.addFormElement('addConstructParam')" class="btn btn-success">添加一行</a>
		<hr />

		<label for="">参数</label>
		<?php if(isset($_GET['param'])):
		for($i=0;$i<count($_GET['param']); $i++):
		if(!$_GET['param'][$i]) continue; ?>
		<div class="form-group" iv-template-added="addTestCol">
			<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
				<input type="text" class="form-control" name="param[]" value="<?=$_GET['param'][$i]?>" />
			</div>
			<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
				<input type="text" class="form-control" name="value[]" value="<?=$_GET['value'][$i]?>" />
			</div>
		</div>
		<?php endfor;endif; ?>
		<div class="form-group" iv-template="addTestCol">
			<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
				<input type="text" class="form-control" name="param[]" />
			</div>
			<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
				<input type="text" class="form-control" name="value[]" />
			</div>
		</div>
		<a onclick="$.addFormElement('addTestCol')" class="btn btn-success">添加一行</a>
		<button onclick="$('#test_result').html('')" class="btn btn-primary">运行</button>
		<a onclick="$.location.href('?'+$(this).closest('form').serialize())" class="btn btn-danger">生成链接</a>
	</form>
	<pre class="pull-right" id="test_result" style="width:55%;height:500px;overflow-y:auto;border:2px solid #000;font-size:14px;padding:5px;"></pre>
</div>
