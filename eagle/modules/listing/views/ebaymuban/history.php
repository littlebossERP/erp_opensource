<div class="tracking-index col2-layout">
<?=$this->render('../_leftmenu');?>
<div class="content-wrapper" >
<table class="table table-bordered ">
<tr>
<th width="200px">缩略图</th>
<th width="300px">ItemID</th>
<th width="400px">Title</th>
</tr>
<?php if (count($items)):foreach ($items as $item):?>
<tr>
<td><img src="<?php echo $item->mainimg?>" width="80px" height="80px"></td>
<td><?php echo $item->itemid?></td>
<td><a href="<?php echo $item->viewitemurl?>" target="_blank"><?php echo $item->itemtitle?></a></td>
</tr>
<?php endforeach;endif;?>
</table>
</div>
</div>