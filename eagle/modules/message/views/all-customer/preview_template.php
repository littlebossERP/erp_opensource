<div id="preview_box_container">
    <div id="preview_box_left">
        <div id="div_preview" class="panel panel-info">
            <div class="panel-heading"><h5>1.买家收到的留言或者站内信内容</h5></div>
                <div class="panel-body">
                    <div class="form-group"> 
                        <label for="div_preview_subject" class="col-sm-12">标 题:</label>
                        <div id="div_preview_subject" class="col-sm-12">
                            <h5><?php echo !empty($preview_result['subject'])?$preview_result['subject']:null;?></h5>
                        </div>
                    </div>
                    <div class="form-group"> 
                        <label for="div_preview_content" class="col-sm-12">内 容:</label>
                        <div id="div_preview_content" class="col-sm-12">
                            <?php echo !empty($preview_result['template'])?nl2br($preview_result['template']):null;?>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    <div id="preview_box_right">
        <div class="panel panel-info">
            <div class="panel-heading"><h5>2.买家点击包裹追踪连接，看到包裹进度以及<b>二次营销商品推荐</b><br><span style="color:red">包裹追踪连接依赖物流号，如果没有物流号则连接没有有效内容！</span><br><span style="color:red">如果物流号缺少订单号或者推荐商品，包裹追踪连接则会缺少相应内容。最终以真实效果为准</span></h5></div>
            <div class="panel-body"><img src="/images/tracking/msg_recom_prod_layout_1.png" style="max-width: 550px;"></div>
        </div>
    </div>
</div>
<div class="clearfix" style="margin-bottom: 5px;"></div>