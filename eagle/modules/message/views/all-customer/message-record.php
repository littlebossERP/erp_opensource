<?php 
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("initial()", \yii\web\View::POS_READY);
?>
<table class="table people_table" style="margin-bottom: 0px">
    <thead>
        <tr>
            <th style="vertical-align: middle;">会话编号</th>
            <th style="vertical-align: middle;">最后消息</th>
            <th style="vertical-align: middle;">相关内容</th>
            <th style="vertical-align: middle;">是否回复</th>
        </tr>
    </thead>
    <tbody>
    <?php $num=0;foreach ($message_list as $msg):?>
        <tr <?php echo $num%2==0?"class='striped-row'":null;$num++;?>>
        <td style="vertical-align: middle;"><?php echo $msg['ticket_id']?></td>
        <td style="vertical-align: middle;"><p><?php echo $msg['last_omit_msg']?></p></td>
        <td style="vertical-align: middle;">
        <?php 
        if($msg['message_type']==1){
            echo "单号：<a onclick=\"GetOrderid('{$msg['related_id']}')\">{$msg['related_id']}</a>";
            if(!empty($msg['track_no'])){
                echo "<br />物流：<a class='detail-location' data-id='{$msg['track_no']}'>{$msg['track_no']}</a>";
            }else{
                echo "<br />物流信息暂时没有";
            }
            
        }
        if($msg['message_type']==2){
            $type=$msg['related_type'];
            switch ($type){
                case "O":
                    echo "订单：<a onclick=\"GetOrderid('{$msg['related_id']}')\">{$msg['related_id']}</a>";
                    if(!empty($msg['track_no'])){
                        echo "<br />物流：<a class='detail-location' data-id='{$msg['track_no']}'>{$msg['track_no']}</a>";
                    }else{
                        echo "<br />物流信息暂时没有";
                    }                      
                        
                    break;
                case "P":
                    echo "<p>产品:{$msg['related_id']}</p>";
                    break;
                default:
                    break;            
            }

        }
        ?>
        </td>
        <td style="vertical-align: middle;"><?php echo $msg['has_replied']==1?"<span style='color:#02ce59;'>已回复</span>":"<span style='color:#ff9900;'>未回复</span>"?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
</table>