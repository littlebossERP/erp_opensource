<div id="topwarehouse">
	<ul class="nav nav-pills" role="tablist">
	  <?php foreach ($warehouses as $warehouseId=>$warehouse){?>
	    <li role="presentation" <?php if ($warehouseId==$warehouse_id){?>class="active btn btn-xs"<?php }?>><a href="#home" aria-controls="home" role="tab" data-toggle="tab" class="warehouse" warehouse-id ="<?php echo $warehouseId?>"><?php echo $warehouse?></a></li>
	  <?php }?>
 	</ul>
</div>