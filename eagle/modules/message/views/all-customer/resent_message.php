<div>
<textarea class="edit-message-area"><?php echo $message['msg']?></textarea>
<input type="button" onclick="HandleEditReSent('<?php echo $message['ticket_id'];?>','<?php echo $message['msg_id'];?>','<?php echo $message['platform_source']?>','<?php echo $message['seller_id']?>','<?php echo $message['ticket_id']?>','<?php echo $message['buyer_id']?>','<?php echo $message['nick_name']?>')" value="发送消息" class="btn btn-success" style="float: right; margin-top:5px;">
<input type="button" value="返回" data-dismiss="modal" class="btn btn-success" style="float: right; margin-top:5px; margin-right:10px;">
</div>