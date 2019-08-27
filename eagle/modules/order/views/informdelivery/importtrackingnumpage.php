  <form enctype="multipart/form-data" ?="">
        <h4 class="modal-title" id="myModalLabel">导入物流单号</h4>
  
        <input type="file" name="order_tracknum" id="order_tracknum"><br>
        <a href="/template/ordertracknum_sample.xls">范本下载</a>
		
		<div style="margin-left: 270px;">
			<button type="button" class="iv-btn btn-success" id="save" onclick="importordertracknum()">提交</button>
        </div>
		<div style="margin-left: 200px;margin-top:-27px;"><button class="iv-btn modal-close" onclick="cancel()">关闭</button></div>
        

</form>

<script>
//上传物流单号
function importordertracknum(){
	if($("#order_tracknum").val()){
		$.ajaxFileUpload({  
			 url:'/order/order/importordertracknum',
		     fileElementId:'order_tracknum',
		     type:'post',
		     dataType:'json',
		     success: function (result){
			     if(result.ack=='failure'){
					bootbox.alert(result.message);
				 }else{
					bootbox.alert('操作已成功');
				 }
		     },  
			 error: function ( xhr , status , messages ){
				 bootbox.alert(messages);
		     }  
		});  
	}else{
		bootbox.alert("请添加文件");
	}
}

function cancel(){
	$(".importtrackingnumpage").modal("hide")
}
</script>
<!--
<div>
    <form name="a" id="a" action="/order/informdelivery/alreadyinformdelivery?shipping_status=1" method="post" enctype="multipart/form-data" >
        <input type="hidden" name="leadExcel" value="true">
        <table align="center" width="60%" >
            <tr>        
                <td> 
                    <input type="file" name="inputExcel">
                </td>     
            </tr>
        </table>
        <hr>
        <div style="margin-left: 270px;">
            <input type="submit" value="导入数据" class="iv-btn btn-success" ">
        </div>
    </form>
    <div style="margin-left: 200px;margin-top:-27px;"><button class="iv-btn modal-close" onclick="cancel()">取 消</button></div>
</div>

<script>
    function cancel(){
        $(".importtrackingnumpage").modal("hide")
    }

</script>
-->