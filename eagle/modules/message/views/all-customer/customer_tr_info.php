<?php 
use eagle\modules\util\helpers\StandardConst;
?>
    <td><input type="checkbox" id="chk_all"></td>
    <td style="text-align: left;">
    <a class="no-qtip-icon" onclick="ShowDetailMessage('<?php echo $customer['seller_id'];?>','<?php echo $customer['customer_id'];?>','<?php echo $customer['platform_source'];?>','','','<?php echo $customer['os_flag']?>','<?php echo $customer['msg_sent_error']?>','<?php echo $customer['os_flag']==1?"remind":"ok";?>')" <?php echo $customer['os_flag']==1?"qtipkey='cs_customer_show_session_detail_amber'":($customer['msg_sent_error']=="C"?"qtipkey='cs_customer_show_session_detail_green'":'');?>"><?php echo $customer['customer_nickname'];?></a><br />
    <a onclick="ShowDetailMessage('<?php echo $customer['seller_id'];?>','<?php echo $customer['customer_id'];?>','<?php echo $customer['platform_source'];?>','','','<?php echo $customer['os_flag']?>','<?php echo $customer['msg_sent_error']?>','<?php echo $customer['os_flag']==1?"remind":"ok";?>')" class="no-qtip-icon" <?php echo $customer['os_flag']==1?"qtipkey='cs_customer_show_session_detail_amber'":($customer['msg_sent_error']=="C"?"qtipkey='cs_customer_show_session_detail_green'":'');?>"><?php echo $customer['os_flag']==1?"<span class='egicon-envelope-remind'></span>":($customer['msg_sent_error']=="C"?"<span class='egicon-envelope-ok'></span>":null);?></a>
    <a class="no-qtip-icon" onclick="ShowDetailMessage('<?php echo $customer['seller_id'];?>','<?php echo $customer['customer_id'];?>','<?php echo $customer['platform_source'];?>','','','<?php echo $customer['os_flag']?>','<?php echo $customer['msg_sent_error']?>','remove')" qtipkey="<?php echo $customer['msg_sent_error']=="Y"?"cs_customer_show_session_detail_red":null?>"><?php echo $customer['msg_sent_error']=="Y"?"<span class='egicon-envelope-remove'></span>":null?></a>
    <span class="btn_tag_qtip <?php echo !empty($selected_flag)?null:"div_space_toggle"?>" data-customer-id="<?php echo $customer['id']; ?>" data-order-num="<?php echo $customer['order_num']?>" >
    <?php if(empty($selected_flag)):?>    
        <a title="添加标签" style="cursor: pointer;">
            <span class="egicon-flag-gray" data-customer-id="<?php echo $customer['id'];?>" data-order-num="<?php echo $customer['order_num']?>"></span>
        </a>
    <?php endif;?>
    <?php if(!empty($selected_flag))://根据判断来显示对应的所有标签?>
        <?php 
        $sum=count($selected_flag); //计算已选择的标签数量
        for($tag_no=0;$tag_no<$sum;$tag_no++): //筛选以选择的标签?>
            <?php foreach ($all_flag as $flags):?>
                <?php if($selected_flag[$tag_no]==$flags['tag_id']):?>
                    <a title="<?php echo $flags['tag_name'];?>" style="cursor: pointer;">
                        <span class="<?php echo $flags['classname'];?>" data-customer-id="<?php echo $customer['id'];?>" data-order-num="<?php echo $customer['order_num']?>"></span>
                    </a>
                <?php endif;?>
            <?php endforeach;?>
        <?php endfor;?>
    <?php endif;?>
    </span>
    <?php if($customer['os_flag']==0&&$customer['msg_sent_error']!="C"):?>
    <span class="customer_btn_tag_bootbox div_space_toggle">
        <a onclick="ShowDetailMessage('<?php echo $customer['seller_id'];?>','<?php echo $customer['customer_id'];?>','<?php echo $customer['platform_source'];?>','','','<?php echo $customer['os_flag']?>','<?php echo $customer['msg_sent_error']?>','<?php echo $customer['os_flag']==1?"remind":"ok";?>')" class="no-qtip-icon" qtipkey="cs_customer_show_session_detail_grey"><span class="egicon-envelope-hover"></span></a>
    </span>
    <?php endif;?>
    </td>
    <td style="text-align: left; ">
    <?php 
    if(isset($accounts)){
		if(isset($accounts[$customer['seller_id']])){
			echo $accounts[$customer['seller_id']];
		}else{
			echo $customer['seller_id'];
		}
	}else{
		echo $customer['seller_id'];
	}
    ?>
    </td>
    <td style="text-align: left; color:#00c453;"><?php echo !empty($customer['nation_code'])?StandardConst::$COUNTRIES_CODE_NAME_CN[$customer['nation_code']]:null;?></td>
    <td style="text-align: left; "><?php echo $customer['order_num'] == 0?$customer['order_num'].'<span qtipkey="order_count_zero"></span>':$customer['order_num'];?></td>
    <td style="text-align: left; "><?php echo $customer['currency']?>&nbsp;<?php echo $customer['life_order_amount'];?></td>
    <td style="text-align: left; "><a onclick="GetOrderid('<?php echo $customer['last_order_id'];?>')"><?php echo !empty($customer['last_order_id'])?"<span style='color:#808080'>订单:</span>{$customer['last_order_id']}":null;?><?php echo ($customer['order_num'] == 0 && !empty($customer['last_order_id']))?'<span qtipkey="order_detail_open"></span>':null?></a><br /><a class="lastest-detail-location" data-id="<?php echo !empty($customer['track_no'])?$customer['track_no']:null?>"><?php echo !empty($customer['track_no'])?"<span style='color:#808080'>物流:</span>{$customer['track_no']}":null?></a></td>
    <td style="text-align: left; "><?php echo $customer['last_order_time'];?></td>
    <td style="text-align: left; "><?php echo $customer['last_message_time'];?></td>
    <td style="text-align: left; " class="button_align">
        
        <?php if($customer['os_flag']==1||($customer['os_flag']==0&&$customer['msg_sent_error']=="C")) { ?>
        <a onclick="ShowDetailMessage('<?php echo $customer['seller_id'];?>','<?php echo $customer['customer_id'];?>','<?php echo $customer['platform_source'];?>','','','<?php echo $customer['os_flag']?>','<?php echo $customer['msg_sent_error']?>','<?php echo $customer['os_flag']==1?"remind":"ok";?>')" class="no-qtip-icon" qtipkey="cs_customer_send_message_icon"><span class="btn_customer_reply_msg egicon-reply-msg"></span></a>
        <a class="order_list no-qtip-icon" qtipkey="cs_customer_order_history" data-customer-id=<?php echo $customer['id'];?> data-id="[{source:'<?php echo $customer['platform_source'];?>',id:'<?php echo $customer['customer_id'];?>',list_style:'detail-location'}]" <?php echo $customer['order_num']==0?"style='display:none;'":null?>><?php echo $customer['order_num']==0?null:"<span class='btn_customer_history_order egicon-history-order'></span>";?></a>
        <a class="message_list no-qtip-icon" qtipkey="cs_customer_message_history" data-customer-id=<?php echo $customer['id'];?> data-id="[{source:'<?php echo $customer['platform_source'];?>',buyid:'<?php echo $customer['customer_id'];?>',sellid:'<?php echo $customer['seller_id']?>'}]"><span class="btn_customer_history_msg egicon-history-msg"></span></a>
        <?php }else{ ?>

            <a class="order_list no-qtip-icon" qtipkey="cs_customer_order_history" data-id="[{source:'<?php echo $customer['platform_source'];?>',id:'<?php echo $customer['customer_id'];?>',list_style:'detail-location'}]" <?php echo $customer['order_num']==0?"style='display:none;'":null?>><?php echo $customer['order_num']==0?null:"<span class='btn_customer_history_order egicon-history-order'></span>";?></a>
            
        <?php }?>
        
    </td>
