
<div>
    <form name="a" id="a" action="/order/informdelivery/noinformdelivery?shipping_status=0&is_exsit_tracking_number_order=0" method="post" enctype="multipart/form-data" >
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
        $(".importtrackingnopage").modal("hide")
    }

</script>