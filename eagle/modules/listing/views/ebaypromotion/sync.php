<?php

use yii\helpers\Url;
?>
<style>
.dis{
	display:none;
	text-align:center;
}
</style>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	同步在线促销规则
	</h4>
</div>
<div class="modal-body">
	<!-- 显示需要同步的ebay账号 -->
	<div class="sellerid">
		<strong>同步时请勿关闭窗口</strong>
		<button type="button" class="iv-btn btn-search" onclick="dosync()">开始同步</button>
	</div>
	<!-- 显示具体的同步进度及处理信息 -->
	<div class="process">
		<div class="processinfo" id="processinfo"></div>
		<div class="dis"><button type="button" class="iv-btn btn-default" onclick="window.location.reload();">刷新页面</button></div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="iv-btn btn-default" data-dismiss="modal">关闭</button>
</div>
<script>
function dosync(){
	$('strong').text('同步中...');
	$.showLoading();
	$.post('<?=Url::to(['/listing/ebaypromotion/ajaxsync'])?>',{},function(r){
		$.hideLoading();
		$('strong').text('同步完成');
		$('.dis').show();
		$('#processinfo').html(r);
	});
}
</script>