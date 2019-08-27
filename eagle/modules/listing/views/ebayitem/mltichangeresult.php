<?php


?>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>
<div class="content-wrapper" >
<?php if (count($result)):?>
<?php foreach ($result as $k=>$v):?>
<div 
<?php if ($v['ack'] == 'success'):?>
class='alert alert-success'
<?php else:?>
class='alert alert-danger'
<?php endif;?>
>
<strong><?=$k?>:</strong>
<?php if ($v['ack'] == 'success'):?>
<?=$v['ack']?>
<?php else:?>
<?=$v['msg']?>
<?php endif;?>
</div>
<?php endforeach;?>
<?php endif;?>
</div>
</div>