<?php
use yii\helpers\Html;
?>
<style>
.margin-h2 {
    margin-left: 10px;
    margin-right: 10px;
}
select {
    padding: 3px 4px;
    height: 30px;
}
.table {
    width: 100%;
    max-width: 100%;
    margin-bottom: 20px;
	margin-top:5px;
	word-break:break-all;
	word-wrap:break-all;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}
.modal-box{
	width:800px;
}
</style>
<div>
    <div class="margin-b2">
    <?php $memo = $config['skubarcode'];?>
        条码规格: 
        <select class="margin-h2" name="width">
            <option value="40" <?php echo $memo['width']==40?'selected':''; ?>>宽度 40mm</option> 
            <option value="50" <?php echo $memo['width']==50?'selected':''; ?>>宽度 50mm</option> 
            <option value="60" <?php echo $memo['width']==60?'selected':''; ?>>宽度 60mm</option> 
            <option value="80" <?php echo $memo['width']==80?'selected':''; ?>>宽度 80mm</option> 
            <option value="100" <?php echo $memo['width']==100?'selected':''; ?>>宽度 100mm</option> 
        </select>
        x
        <select class="margin-h2" name="height">
            <option value="20" <?php echo $memo['height']==20?'selected':''; ?>>高度 20mm</option> 
            <option value="25" <?php echo $memo['height']==25?'selected':''; ?>>高度 25mm</option> 
            <option value="30" <?php echo $memo['height']==30?'selected':''; ?>>高度 30mm</option> 
        </select>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th width="290">商品名称</th>
                <th width="290">sku</th>
                <th width="140">数量</th>
                <th width="80" class="align-center">操作</th>
            </tr>
        </thead>
        <tbody>
        	<?php 
        	foreach ($skulist as $skulistone){
				$sku = $skulistone[0];
				$pd = $skulistone[1];
				?>
				<tr name="listone">
					<td name="skuname"><?php echo $pd;?></td>
					<td name="sku"><?php echo $sku;?></td>
					<td><input name="number" type="number" value="1">
					<a name="ApplyToAll">应用到所有</a>
					</td>
					<td class="align-center"><button name="skuprintdelete" class="btn btn-sm btn-default">删除</button></td>
				</tr>
			<?php 
			}        	
        	?>
        </tbody>
    </table>
</div>
<div class="modal-footer">
					<button type="button" class="btn btn-primary">打印</button>
					<button type="button" class="iv-btn btn-default btn-sm modal-close" data-dismiss="modal" >取消</button>
</div>
</div>