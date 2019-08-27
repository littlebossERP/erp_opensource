<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
.div_down_load{
	font-size:18px;
	line-height:30px;
}
</style>

<div class="div_wait div_down_load">
    <p>正在导出，请不要关闭，稍候......</p>
</div>
<div class="div_download div_down_load" style="display: none;">
    <p>已导出：<span id="export_count" style="color: red;"></span> 行数据</p>
    <p>导出文件已生成，请<a id="down_load_excel_url" target="_blank">立即下载</a></p>
</div>
<div class="div_error div_down_load" style="display: none;">
    <p>导出失败：<span id="export_error" style="color: red;"></span></p>
</div>
<p id="count"></p>
<input type="button" id="btn_cancel" class="iv-btn btn-primary modal-close" style="float: right;" data-dismiss="modal" value="<?=TranslateHelper::t('关闭')?>" />
<input id="pending_id" type="hidden" value="<?=$pending_id ?>"></input>

<script>
    //每隔3秒执行一次GetExcelUrl方法
    var id_Interval = window.setInterval("getExcelUrl()",3000);

	$('.modal-close').click(function(){
		$('.modal-backdrop').remove();
	});

    function getExcelUrl(){
		//查询是否又返回Excel路劲
		var $pending_id = $('#pending_id').val();
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/excel/get-excel-url', 
			data:{pending_id:$pending_id},
			async : false,
			success: function (result) {
				if(result.success == 1){
					clearInterval(id_Interval);
					$('.div_wait').css('display','none');
					$('.div_download').css('display','block');
					$('#down_load_excel_url').attr('href',result.url);
					$('#export_count').html(result.export_count);
				}
				else if(result.success == 0){
					clearInterval(id_Interval);
					$('.div_wait').css('display','none');
					$('.div_error').css('display','block');
					$('#export_error').html(result.message);
				}
			},
			error :function () {
				clearInterval(id_Interval);
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	}
</script>



