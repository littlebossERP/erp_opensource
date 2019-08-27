<table class="table people_table">
    <thead>
        <tr>
            <th>客户名称</th>
            <th>买家帐号</th>
            <th>平台来源</th>
            <th>历史购买次数</th>
            <th>历史购买总金额</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($message)):?>
        <tr>
            <td><?php echo $message['customer_nickname']?></td>
            <td><?php echo $message['customer_id']?></td>
            <td><?php echo $message['platform_source']?></td>
            <td><?php echo $message['life_order_count']?></td>
            <td><?php echo $message['currency']?>&nbsp;<?php echo $message['life_order_amount']?></td>
        </tr>
        <?php endif;?>
        <?php if(!empty($people_flags)):?>
        <tr>
            <td>
            <?php foreach ($people_flags as $flags):?>
            <span title="<?php echo $flags['tag_name']?>" class="egicon-flag-<?php echo $flags['color'];?>"></span>
            <?php endforeach;?>
            </td>       
        </tr>
        <?php endif;?>
    </tbody>
</table>