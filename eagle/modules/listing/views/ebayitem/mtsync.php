<?php
use yii\helpers\Html;
use common\helpers\Helper_Array;
use yii\helpers\Url;
?>
<style>
select.selleruserid{
	margin:0;
}
.process{
	display:none;
}
.processnumber{
	margin-top:4px;
	text-align:center;
	font-weight:bold;
	background-color:#eeefff;
	border-radius:4px;
	width:100%;
}
.processcolor{
	margin-top:-30px;
	background-color:rgb(56,121,217);
	width:10%;
	border-radius:4px;
	height:20px;
}
.processinfo{
	width:100%;
	height:50px;
	overflow:auto;
	font-size:10px;
}
.warning-text{
	font-size:10px;
}
</style>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	同步Item
	</h4>
</div>
<div class="modal-body">
	<!-- 显示需要同步的ebay账号 -->
	<div class="sellerid">
		<form class="form-inline">
		  <div class="form-group">
		    <?=Html::dropDownList('selleruserid',null,Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>'form-control selleruserid','prompt'=>'选择eBay账号'])?>
		    <button type="button" class="btn btn-primary btn-close" onclick="dosync()">同步</button>
		    <span class="warning-text"></span>
		  </div>
		</form>
	</div>
	<!-- 显示具体的同步进度及处理信息 -->
	<div class="process">
		<div class="processpannel">
			<p class="processnumber">0%</p>
			<p class="processcolor"></p>
		</div>
		<div class="processinfo" id="processinfo"></div>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
<script>
var _info = $('.processinfo');
var infodiv = document.getElementById('processinfo');
function dosync(){
	var sellerid = $('.selleruserid').val();
	var allpici = 0;
	var perpiciprocess = 0;
	
	if(sellerid == '' ||sellerid == 'undefined'){
		bootbox.alert('请选择需要同步的账号');return false;
	}
	//开始处理账号的同步
	//1.进度条初始化
	$('.btn-close').attr('disabled',true);
	showprocess('0','',true);
	//2.获取总的需要同步的item的数量
	showprocess('','开始处理,获取账号'+sellerid+'的在线Item总数量<br>');
	$.post('<?=Url::to(['/listing/ebayitem/ajaxgetitemcount'])?>',{sellerid:sellerid},function(r){
		res = eval('('+r+')');
		if(res.ack == 'success'){
			allpici = parseInt(res.data.TotalNumberOfEntries/50+1); //总批次
			showprocess(5,'账号'+sellerid+'的在线Item总数量为'+res.data.TotalNumberOfEntries+'<br>');
			//3.处理获取的itemid列表
			//总数量  ,每页处理50条，总的批次为 （总数量/每页50）+1，当前批次进度为 批次i*每批次进度(95/总批次)
			perpiciprocess = parseInt(95/allpici);//每批次增长进度

			$(document).clearQueue("ajaxRequests");
			for(var i=1;i<=allpici;i++){
				(function(i){
					$(document).queue('ajaxRequests',function(){
						showprocess('','同步第'+i+'批,总计'+allpici+'批'+'<br>');
						$.post("<?=Url::to(['/listing/ebayitem/ajaxgetitem'])?>",{sellerid:sellerid,currentpage:i}, function(r){
							res = eval('('+r+')');
							if(res.ack == 'success'){
								showprocess(5+i*perpiciprocess,'第'+i+'批同步完成'+'<br>');
							}else{
								showprocess('','同步错误:'+res.msg+'<br>');
								$('.btn-close').removeAttr('disabled');
								return false;
							}
							$(document).dequeue("ajaxRequests");
						});
					});
				})(i);
				
			}
			$(document).queue('ajaxRequests',function(){
				//4.恢复按钮的点击操作
				$('.btn-close').removeAttr('disabled');
				showprocess(100,'同步完成<br>');
			});
			$(document).dequeue("ajaxRequests");
		}else{
			$('.btn-close').removeAttr('disabled');
			showprocess('','同步错误:'+res.msg+'<br>');
			return false;
		}
	});
}

function showprocess(processint,processinfo,isinit){
	isinit = isinit?isinit:false;
	if(isinit){
		$('.process').show();
		$('.warning-text').html('<strong>同步中,请勿关闭窗口</strong>');
	}
	if(processint != ''){
		$('.processnumber').text(processint+'%');
		$('.processcolor').width(processint+'%');
		if(processint == 100){
			$('.warning-text').html('');
		}
	}
	if(isinit){
		_info.html('');
	}
	if(processinfo != ''){
		_info.append(processinfo);
		infodiv.scrollTop=infodiv.scrollHeight;
	}
}
</script>