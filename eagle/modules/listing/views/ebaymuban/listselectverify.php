<?php 
?>
<script type="text/javascript">
function verify(){
	　　$(".result").each( function(){
		if($(this).text()=='Success' || $(this).text()=='Warning'){
			return;
		}
		$(this).html("");
		$(this).next().html(" 正在检测，请不要关闭页面...");
		var mubanid=$(this).attr('mubanid');
		var act = 'verify';
		var obj=this;
		$(document).queue('ajaxRequests',function(){
			$.ajax({
				type:'post',
				url:global.baseUrl+"listing/ebaymuban/ajaxverify",
				data:{mubanid:mubanid,act:act},
				timeout: 300000,
				dataType:'json',
				error:function(r){
					$(obj).html('超时');
					$(obj).next().html('');
					$(document).dequeue("ajaxRequests");
				},
				success:function(r){
				 $(obj).html(r.Ack);
				 $(obj).next().html(r.show);
				 $(document).dequeue("ajaxRequests");
			}});
		});
	 });
	 $(document).dequeue("ajaxRequests");
}; 
</script>
<body>
<br/>
<div class=".container" style="width:98%;margin-left:1%;">
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-12">
	  	<input type="button" value=" 提 交 检 测 " onclick="verify()">
		</div>
	</div><br/>
<table class="table table-bordered table-hover">
<tr class="active">
<th width='100px'>刊登范本编号</th><th width='80px'>缩略图</th><th width='400px'>刊登范本标题</th><th width='80px'>刊登结果</th><th>详细信息</th>
</tr>
<?php 
foreach ($list as $one){
?>
<tr>
<td><?php echo $one->mubanid;?></td>
<td><img src='<?php echo $one->mainimg;?>' width=60px height=60px></td>
<td><?php echo $one->itemtitle;?></td>
<td class="result" mubanid="<?php  echo $one->mubanid;?>"></td>
<td style="text-align:left;"></td>
</tr>
<?php 
}
?>
</table>
</div>
</div>
</div>
</body>
</html>

