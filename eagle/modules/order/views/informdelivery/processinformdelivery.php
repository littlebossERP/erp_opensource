<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\tracking\models\Tracking;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/message/customer_message.css");

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
//$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['country'])){
    $this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['country']),true)).";" , \yii\web\View::POS_READY);
}

$this->registerJs("OrderCommon.NationList=".json_encode(@$countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode(@$country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.customCondition=".json_encode(@$counter['custom_condition']).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initCustomCondtionSelect();" , \yii\web\View::POS_READY);

?>


    <!------------ 左侧菜单 start  ------------------->
    <?=$this->render('_leftmenu',['counter'=>$counter]);?>
    <!-------------  左侧菜单 end  ------------------->
<div class="tracking-index col2-layout">
    <!-- --------------------------------------------搜索 bigin--------------------------------------------------------------- -->
    <div>
        <!-- 搜索区域 -->
        <form class="form-inline" id="form1" name="form1" action="" method="post">
            <div style="margin:30px 0px 0px 0px">
                <!----------------------------------------------------------- 卖家账号 ----------------------------------------------------------->
                <?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'卖家账号'])?>
                <!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
                <div class="input-group iv-input">
                    <?php $sel = [
                        'order_id'=>'小老板订单号',
                        'order_source_order_id'=>'平台订单号',
                        'tracknum'=>'物流号',
                    ]?>
                    <?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input'])?>
                    <?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num'])?>

                </div>

                <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
                <?=Html::submitButton('筛选',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
                <?=Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>

                <!----------------------------------------------------------- 模糊搜索 ----------------------------------------------------------->
                <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
            </div>
        </form>
    </div>
    <!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->
    <br>
    <!-- --------------------------------------------列表  begin--------------------------------------------------------------- -->
    <div>
        <form name="a" id="a" action="" method="post">
            <div class="nav nav-pills">
                <?php echo Html::button(TranslateHelper::t('停止发货通知'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:batchstopdelivery();"]);echo "&nbsp;";?>
            </div>
            <br>
            <table class="table table-condensed table-bordered" style="font-size:12px;">
                <tr>
                    <th width="1%">
                        <span class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span><input type="checkbox" check-all="e1" />
                    </th>
                    <th width="6%"><b>小老板单号</b></th>
                    <th width="10%"><b>速卖通订单号</b></th>
                    <th width="13%"><b>物流跟踪号</b></th>
                    <th width="8%"><b>标记类型</b></th>
                    <th width="17%"><b>物流服务</b></th>
                    <th width="15%"><b>DESCRIPTION</b></th>
                    <th width="8%"><b>状态</b></th>
                    <th width="10%"><b>声明类型</b></th>
                    <th width="12%"><b>操作</b></th>
                </tr>
                <?php if (count($models)):foreach ($models as $delivery_order):?>
                    <tr style="background-color: #f4f9fc">
                        <td><span class="orderspread glyphicon glyphicon-minus" onclick="spreadorder(this,'<?=$delivery_order['order_id']?>');"></span><input type="checkbox" name="order_id[]" class="order-id"  value="<?=$delivery_order['order_id']?>" data-check="e1"/>
                        </td>
                        <td><?=$delivery_order['order_id']?></td>
                        <td><?=$delivery_order['order_source_order_id']?></td>
                        <td><?=$delivery_order['tracking_number']?></td>
                        <td><?=(in_array($delivery_order['signtype'], ['all','part'])?(($delivery_order['signtype'] == 'all' )?TranslateHelper::t('全部发货'):TranslateHelper::t('部分发货')):TranslateHelper::t('未知'))?></td>
                        <td><?=$delivery_order['shipping_method_name']?></td>
                        <td><?=$delivery_order['description']?></td>
                        <td><?=$delivery_order['status']==0?'未处理':($delivery_order['status']==1?'标记成功':'标记失败')?></td>
                        <td><??></td>
                        <td>
                            <select id="operateType-<?=$delivery_order['order_id']?>" name="operateType[]" class="iv-input sendType" onchange="doaction(this.value , '<?=$delivery_order['order_id']?>','<?=str_pad($delivery_order['order_id'], 11, "0", STR_PAD_LEFT);?>')">
                                <option value="" id="operate">-操作类型-</option>
                                <option value="stop_delivery" id="stop_delivery">停止发货通知</option>
                                <option value="order_detail" id="order_detail">订单详情</option>
                            </select>
                        </td>

                    </tr>
                <?php endforeach;endif;?>
            </table>
        </form>
        <?php if($pages):?>
            <div id="pager-group">
                <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 , 500 ) , 'class'=>'btn-group dropup']);?>
                <div class="btn-group" style="width: 49.6%; text-align: right;">
                    <?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
                </div>
            </div>
        <?php endif;?>
    </div>
    <div style="clear: both;"></div>
    <!-- --------------------------------------------列表  begin--------------------------------------------------------------- -->

</div>

<script>


    //重置
    function cleform() {
        $(':input', '#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
    }

    //单个停止发货操作
    function doaction(val, orderid,longorderid) {
        switch (val) {
            case 'stop_delivery': //停止发货通知
                $(".operateType option[value='operate']").attr("selected", "selected");
                $.ajax({
                    type: "POST",
                    dataType: 'json',
                    url: '/order/informdelivery/stopdelivery',
                    data: {order_id: orderid},
                    success: function (result) {
                        var event1 = $.confirmBox(result.message);
                        $.maskLayer(true);//遮罩
                        event1.then(function () {
                            //確定
                            window.location.reload();//刷新当前页面.
                        }, function () {
                            // 取消，关闭遮罩
                            $.maskLayer(false);
                        });

                    }
                });
                break;

            case 'order_detail': //订单详情
                $(".operateType option[value='operate']").attr("selected", "selected");
                window.open(global.baseUrl + "order/aliexpressorder/edit?orderid="+longorderid);
                break;

            default:
                return false;
                break;
        }
    }

    //批量停止操作
    function batchstopdelivery() {

        var count = $('.order-id:checked').length;
        if (count == 0) {
            $.alert('请选择订单!', 'success');
            return false;
        }

        var params = $("form").serialize(); //序列化

        $.ajax({
            type: "POST",
            dataType: 'json',
            url: '/order/informdelivery/stopdelivery',
            data: params,
            success: function (result) {
                var event1 = $.confirmBox(result.message);
                $.maskLayer(true);//遮罩
                event1.then(function () {
                    //確定
                    window.location.reload();//刷新当前页面.
                }, function () {
                    // 取消，关闭遮罩
                    $.maskLayer(false);
                });

            }
        });
    }


</script>
