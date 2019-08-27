<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\models\QueueDhgateGetorder;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\order\helpers\OrderTrackerApiHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("history_order()", \yii\web\View::POS_READY);
// print_r($connect);

?>
<style>
.z-index{z-index: 1041 !important;}
.z-index-detail{z-index: 1042 !important;}

</style>
<div class="letter-message" id="letter-message">
    
    <div>
    <?php if(($headDatas['message_type']==1&&!empty($product_list))||($headDatas['message_type']==2)&&!empty($product_list)):?>
       <table class="letter-header">
            <tbody>
                <tr>
                    <td style="width: 650px">
					   <img class="pull-left" style="width: 160px;" src="<?= (!empty($product_list['photo_primary'])?$product_list['photo_primary']:"")?>" />
					   <p>商品sku:<?= (!empty($product_list['sku'])?$product_list['sku']:"")?></p>
					   <p style="max-width: 800px;"><?= (!empty($product_list['name'])?$product_list['name']:"")?></p>
					</td>
                    <td style="width:120px; vertical-align: top;"><a class="message_order_list" data-id="[{source:'<?php echo empty($order_history['order_source'])?null:$order_history['order_source'];?>',id:'<?php echo !empty($order_history['buyer_id'])?$order_history['buyer_id']:null;?>',list_style:'history-detail-location'}]" >订单历史(<?php echo !empty($product_list['list_num'])?$product_list['list_num']:"0"; ?>)</a></td>
                </tr>
            </tbody>
       </table>
    <?php endif;?>
    </div>
    <div class="product-list">
    	<?php if(!empty($product_list['items'])):?>
		  <?php 
		  if (is_string($product_list['items'])){
			$items = json_decode($product_list['items'],true);
		  }
		  else{
			$items = $product_list['items'];
		  }
		  foreach ($items as $anItem):?>
        <div class="product">
           <img src="<?= (!empty($anItem['photo_primary'])?$anItem['photo_primary']:"");?>" />
           <p class="product_title"><?= (!empty($anItem['product_name'])?$anItem['product_name']:"")?></p>
           <span>*<?= (!empty($anItem['ordered_quantity'])?$anItem['ordered_quantity']:0)?></span> 
        </div>
        <?php endforeach;?>
        <?php endif;?>
    </div>
    <div class="chat-message">
    <?php if($headDatas['message_type']==1)://订单留言?> 
        <?php foreach ($connect as $contents):
        $track_no=!empty($contents['track_no'])?"({$contents['track_no']})":null;
        ?>
            <div <?php echo $contents['send_or_receiv']==1?"class='right-message'":"class='left-message'";?>>
                <div class="message-header"><?php echo $contents['send_or_receiv']==1?"回复":"订单留言";?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $contents['send_or_receiv']==1?null:"<a onclick=\"GetOrderid('{$contents['related_id']}')\">关于订单：{$contents['related_id']}</a><a class='message-detail-location' id='location' data-id='{$contents['track_no']}'>{$track_no}</a>"?><span><?php echo $contents['send_or_receiv']==1?$headDatas['seller_nickname']:$headDatas['buyer_nickname'];?>&nbsp;&nbsp;<?php echo $contents['platform_time'];?></span></div>
                <div class="message-content"><p><?php echo $contents['content'];?></p><?php echo $contents['haveFile']==1?"<img src='{$contents['fileUrl']}'>":null;?></div>
            </div>
        <?php endforeach;?>
    <?php endif;?>
    <?php if($headDatas['message_type']==3)://系统消息?> 
        <?php foreach ($connect as $contents):
        $track_no=!empty($contents['track_no'])?"({$contents['track_no']})":null;
        ?>
            <div <?php echo $contents['send_or_receiv']==1?"class='right-message'":"class='left-message'";?>>
                <div class="message-header"><?php echo $contents['send_or_receiv']==1?"回复":"系统消息";?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo ($contents['send_or_receiv']==0&&!empty($contents['related_id']))?"<a onclick=\"GetOrderid('{$contents['related_id']}')\">关于订单：{$contents['related_id']}</a><a class='message-detail-location' id='location' data-id='{$contents['track_no']}'>{$track_no}</a>":null;?><span><?php echo $contents['send_or_receiv']==1?$headDatas['seller_nickname']:$headDatas['buyer_nickname'];?>&nbsp;&nbsp;<?php echo $contents['platform_time'];?></span></div>
                <div class="message-content"><p><?php echo $contents['content'];?></p><?php echo $contents['haveFile']==1?"<img src='{$contents['fileUrl']}'>":null;?></div>
            </div>
        <?php endforeach;?>
    <?php endif;?>
    <?php if($headDatas['message_type']==2)://站内信?>
            <?php foreach ($connect as $contents):
                if($contents['related_type']=='O'){  //判断类型
                    $track_no=!empty($contents['track_no'])?"({$contents['track_no']})":null;
                    $class="<a onclick=\"GetOrderid('{$contents['related_id']}')\">关于订单：{$contents['related_id']}</a><a class='message-detail-location' id='location' data-id='{$contents['track_no']}'>{$track_no}</a>";
                }else if($contents['related_type']=='P'){ //商品
                    if(!empty($headDatas['platform_source'])&&!empty($headDatas['buyer_id'])&&!empty($contents['related_id']))
                    {
                        $customerArr=['source_buyer_user_id' => $headDatas['buyer_id']];
                        $skulist=['sku'=>$contents['related_id']];
                        $result=OrderTrackerApiHelper::getOrderList($headDatas['platform_source'],$customerArr,$skulist);
                    }else {
                        $result=array();
                        $result['success']=false;
                    }
                    $all_list=array();
                    if($result['success']==true){
                        $all_list=$result['orderArr']['data'];
                    }else{
                        $all_list=null;
                    }
                    if(!empty($all_list)){//为只有商品信息的提供平台连接
                        $num=count($all_list);
                        $class="<a class='sku_order_list' data-id=\"[{source:'{$headDatas['platform_source']}',id:'{$headDatas['buyer_id']}',list_style:'history-detail-location',sku:'{$contents['related_id']}'}]\">相关商品:{$contents['related_id']}({$num}条相关订单)</a>";
                    }else{
                        if(!empty($contents['productUrl'])){
                            $class="相关商品:{$contents['related_id']}&nbsp;<a href='{$contents['productUrl']}' target='_blank'>(平台链接)</a>";
                        }else{
                            $class="相关商品:{$contents['related_id']}";
                        }
                    }
                }else{
                    $track_no=!empty($contents['track_no'])?"({$contents['track_no']})":null;
                    if(!empty($contents['related_id'])){
                        $class="<a onclick=\"GetOrderid('{$contents['related_id']}')\">关于订单：{$contents['related_id']}</a><a class='message-detail-location' id='location' data-id='{$contents['track_no']}'>{$track_no}</a>";
                    }else{
                        $class="";
                    }    
                }
            ?>
            <div <?php echo $contents['send_or_receiv']==1?"class='right-message'":"class='left-message'";?>>
                <div class="message-header"><?php echo $contents['send_or_receiv']==1?"回复":($contents['related_type']=='O'?"订单留言":($contents['related_type']=="P"?"站内信":"系统消息"));?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo ($contents['send_or_receiv']==0&&!empty($contents['related_id']))?$class:null?><span><?php echo $contents['send_or_receiv']==1?$headDatas['seller_nickname']:$headDatas['buyer_nickname'];?>&nbsp;&nbsp;<?php echo $contents['platform_time'];?></span></div>
                <div class="message-content"><p><?php echo $contents['content'];?></p><?php echo $contents['haveFile']==1?"<img src='{$contents['fileUrl']}'>":null;?></div>
            </div>
        <?php endforeach;?>
    <?php endif;?>
    </div>
    <?php if($headDatas['message_type']==1||$headDatas['message_type']==2):?>      
    <div class="sent-message">
        <textarea class="message-area"></textarea>
        <input type="button" onclick="SentMessge('<?php echo $headDatas['session_id']?>')" value="发送消息" class="btn btn-info btn-lg" style="float: right; margin-top:5px;">
        <input type="button" value="标记已处理" onclick="TabMessage('<?php echo $headDatas['platform_source']?>','<?php echo $headDatas['ticket_id']?>')" class="btn btn-primary btn-lg" style="float:right; margin-top:5px; margin-right:10px;">
    </div>
    <?php endif;?>
<div>
<script>


//回复消息
function SentMessge(session_id){
//		location.href='/inside-letter/sent-message?session_id='+session_id+'&message='+$(".message-area").val();
	
	$.ajax({
		type:"GET",
// 		dataType:"json",
		url:'/message/all-customer/sent-message?session_id='+session_id+'&message='+$(".message-area").val(),
		success:function(data){
			var content=$(".chat-message");
// 			content.prepend(data);
			content.append(data);
			$(".message-area").val("");
			$(".detail_letter .modal-body").scrollTop($(".detail_letter .modal-body").height());
		}
	});
}	
</script>
