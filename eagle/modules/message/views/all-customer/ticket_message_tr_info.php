<?php 
//use eagle\modules\util\helpers\StandardConst;
?>
	<td style="text-align: left;"><input type="checkbox" class="chk_one"><?php echo $letter['ticket_id']?></td>
	<td style="text-align: left;">
		<a onclick="ShowDetailMessage('<?php echo $letter['seller_id']?>','<?php echo $letter['buyer_id']?>','<?php echo $letter['platform_source']?>','<?php echo $letter['ticket_id']?>','<?php echo $letter['related_type']?>','0','','')" class="no-qtip-icon" qtipkey="cs_row_last_message">
			<?php echo $letter['last_omit_msg'];?>
		</a><br />
		<a class="no-qtip-icon" onclick="ShowDetailMessage('<?php echo $letter['seller_id']?>','<?php echo $letter['buyer_id']?>','<?php echo $letter['platform_source']?>','<?php echo $letter['ticket_id']?>','<?php echo $letter['related_type']?>','0','','')" <?php echo $letter['msg_sent_error']=="Y"?"qtipkey='cs_customer_show_session_detail_red'":''?>">
			<?php echo $letter['msg_sent_error']=="Y"?"<span class='egicon-envelope-remove'></span>":null;?>
		</a>
		<span class="btn_tag_qtip <?php echo !empty($selected_flag)?null:"div_space_toggle"?>" data-customer-id="<?=$letter['ticket_id']; ?>" data-session-id="<?=$letter['session_id']?>" data-message-type="ticket" >
			<?php ?>
		    <?php if(empty($selected_flag)):?>
		        <a title="添加标签" style="cursor: pointer;">
		            <span class="egicon-flag-gray" data-customer-id="<?=$letter['ticket_id'];?>" ></span>
		        </a>
		    <?php endif;?>
		    <?php if(!empty($selected_flag))://根据判断来显示对应的所有标签?>
		        <?php 
		        $sum=count($selected_flag); //计算已选择的标签数量
		        for($tag_no=0;$tag_no<$sum;$tag_no++): //筛选以选择的标签?>
		            <?php foreach ($all_flag as $flags):?>
		                <?php if($selected_flag[$tag_no]==$flags['tag_id']):?>
		                    <a title="<?php echo $flags['tag_name'];?>" style="cursor: pointer;">
		                        <span class="<?php echo $flags['classname'];?>" data-customer-id="<?=$letter['ticket_id'];?>" ></span>
		                    </a>
		                <?php endif;?>
		            <?php endforeach;?>
		        <?php endfor;?>
		    <?php endif;?>
		</span>
	</td>
	<td style="text-align: left;">
         <?php $type=$letter['related_type'];
			if(!empty($letter['tracking_info_type']) && $letter['tracking_info_type']=='17track')
				$f = 'onclick="iframe_17Track(\''.(empty($letter['track_no'])?'':$letter['track_no']).'\')"';
			else
				$f = '';
			switch ($type){
				case "O":
					echo "订单:<a onclick=\"GetOrderid('{$letter['related_id']}')\">{$letter['related_id']}</a>";
					if(!empty($letter['item_id'])){
					    echo '<br />Item Id：'.$letter['item_id'];
					}
					if(!empty($letter['track_no'])){
					echo "<br />物流:<a class='lastest-detail-location' ".$f." data-id='{$letter['track_no']}' data-info-type='{$letter['tracking_info_type']}'>{$letter['track_no']}</a>";
					}else{
						echo "<br />物流信息暂时没有";
					}
					break;
				case "Q"://暂时只有cdiscount用到，order_question
				    echo "订单:<a onclick=\"GetOrderid('{$letter['related_id']}')\">{$letter['related_id']}</a><br />";
				    if(!empty($letter['track_no'])){
				        echo "物流:<a class='lastest-detail-location' ".$f." data-id='{$letter['track_no']}' data-info-type='{$letter['tracking_info_type']}'>{$letter['track_no']}</a>";
				    }else{
				        echo "物流信息暂时没有";
				    }
				    break;
				case "P":
					echo "<a>商品:{$letter['related_id']}</a>";
					break;
				case "S":
					echo "系统平台";
					break;
				default:
					break;
                                
			} 
		?>
	</td>
	<td style="text-align: left;">
		<?php 
		if(isset($selleruserids[$letter['platform_source']][$letter['seller_id']])){
			echo $selleruserids[$letter['platform_source']][$letter['seller_id']];
		}else{
			if(isset($accounts)){
				if(isset($accounts[$letter['seller_id']])){
					echo $accounts[$letter['seller_id']];
				}else{
					echo $letter['seller_id'];
				}
			}else{
				echo $letter['seller_id'];
			}
		}
// 		echo $letter['seller_id'];
		?>
	</td>
	<td style="text-align: left;">
		<a class="people_message" data-id="[{source:'<?php echo $letter['platform_source'];?>',buyid:'<?php echo $letter['buyer_id'];?>',sellid:'<?php echo $letter['seller_id']?>'}]">
			<?php echo $letter['buyer_nickname']?>
		</a>
	</td>
	<td style="text-align: left;">
		<?php 
		if($letter['has_replied']==0){
		    echo "<span style='color:#ff9900'>未回复</span>";
		}else if($letter['has_replied'] == 1&&$letter['original_msg_type'] == 'error_states'){
		    echo "<span style='color:#ff9900'>未回复(异常状态)</span>";
		}else{
		    echo "<span style='color:#00c453'>已回复</span>";
		}
// 		echo $letter['has_replied']==0?"<span style='color:#ff9900'>未回复</span>":"<span style='color:#00c453'>已回复</span>";
		?>
	</td>
	<td style="text-align: left;"><?php echo $letter['lastmessage']?></td>
	<?php if($letter['platform_source'] != ''&&$letter['platform_source'] == 'cdiscount'):?>
	<td style="text-align: left;"><?php echo $letter['session_type']?></td>
	<td style="text-align: left;"><?php echo $letter['session_status']?></td>
	<?php endif;?>
	<td style="text-align: left;">
		<a onclick="ShowDetailMessage('<?php echo $letter['seller_id']?>','<?php echo $letter['buyer_id']?>','<?php echo $letter['platform_source']?>','<?php echo $letter['ticket_id']?>','<?php echo $letter['related_type']?>','0','','')"  class="no-qtip-icon" qtipkey="cs_row_last_message">
			<span class="btn_customer_reply_msg egicon-reply-msg"></span>
		</a>
	</td>
