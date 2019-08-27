<?php 
use eagle\helpers\HtmlHelper;
use eagle\modules\util\helpers\VersionHelper;
use yii\helpers\Html;
?>
<div>
   <table id="select-table" class="table table-striped table-bordered table-hover">
        <thead>
            <th style="width:20px;"></th>
			<th style="width: 40%;">文件名称</th>
			<th>文件大小</th>
			<th>创建时间</th>
        </thead>
        <?php if(!empty($pdfs)):?>
        <tbody>
            <?php foreach ($pdfs as $key => $pdf):?>
            <tr>
                <td><input type="radio" name="pdf_select" pdfid="<?php echo $pdf['id'];?>" pdfkey="<?php echo $pdf['origin_url']?>" <?php echo $key==0?'checked':''?>></td>
                <td><?php echo $pdf['original_name']?></td>
                <td><?php echo round($pdf['origin_size']/1024/1024,2);?> M</td>
                <td><?php echo $pdf['create_time']?></td>
            </tr>
            <?php endforeach;?>
        </tbody>
        <?php endif;?>
   </table>
   <div style="text-align: center;"><input type="button" class="btn btn-success" value="确认" id="selectpdf"></div>
</div>