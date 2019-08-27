<div class="unit-test" style="margin-top:20px;">
	<?php
	foreach(debug_backtrace()[1]['args'][1] as $name=>$unit):
	?>
	<form class="panel panel-info form-horizontal" action="<?=strtolower($unit['method'])?>">
		<div class="panel-heading">
			<?= $name.' - '.$unit['class'].'::'.$unit['method'] ?>
		</div>
		<div class="panel-body row">
			<div class="col-xs-12 col-sm-12 col-md-5 col-lg-5">
				<div class="panel panel-success">
					<div class="panel-heading">
						输入参数
					</div>
					<div class="penel-body form-group">
						<?php foreach($unit['args'] as $key=>$val): ?>
							<label class="control-label col-xs-3 col-sm-3 col-md-3 col-lg-3" for=""><?=$val?></label>
							<div class="col-xs-8 col-sm-8 col-md-8 col-lg-8">
								<input type="text" class="form-control" name="<?=$val?>" />
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div class="col-xs-12 col-sm-12 col-md-7 col-lg-7">
				<div class="panel panel-success">
					<div class="panel-heading">
						返回
					</div>
					<div class="penel-body responseText" style="padding:5px;max-height:300px;overflow-y:auto;"></div>
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<input type="button" class="btn btn-primary" value="Submit" />
		</div>
	</form>
	<?php endforeach; ?>
</div>

<script src="//cdn.bootcss.com/jquery/2.1.1/jquery.js"></script>
<script>
$(function(){
	"use strict";

	$("input:button").on('click',function(){
		var 
			$unit = $(this).closest('form'),
			data = $unit.serialize(),
			url = $unit.attr('action');
		$unit.find(".responseText").html('');
		$.ajax({
			url:url,
			method:'post',
			data:data,
			success:function(data){
				console.log(data);
				if(typeof data=='object' && 'code' in data){
					if(data.code === true){
						alert('true');
					}
					if(data.code === false){
						alert('false');
					}
					var result = JSON.stringify(data.code);
				}else{
					var result = data;
				}
				$unit.find(".responseText").html(result);
			},
			error:function(err){
				$unit.find(".responseText").html(err.responseText);
				console.error(err)
			}
		});
	});

});
</script>