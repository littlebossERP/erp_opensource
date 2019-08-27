<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>

<style>

.ltemNameImg {
    padding: 6px 12px;
    margin-top: 10px;
}

.ltemContentImg {
    padding-left: 80px;
}

.ltemContentImg .input-group {
    width: 180px;
    float: left;
}

.ltemContentImg select {
    width: 140px;
    float: left;
}

select.form-control, input.form-control {
    border: 1px solid #b9d6e8;
    color: #555;
    font-size: 12px;
    height: 30px;
    border-radius: 0px;
    padding: 5px;
    margin: 0 0 0 10px;
}

.mLeft20 {
    margin-left: 20px;
}

.form-control {
    font-size: 13px;
}

.ltemContentImg .tipLabel {
    background-color: rgb(255,153,0);
    color: #fff;
    padding: 3px 6px 3px 12px;
    display: inline-block;
    margin-top: 10px;
    margin-bottom: 10px;
}

.ltemContentImg .price {
    color: rgb(255,153,0);
}

.ltemContentImg p {
    margin-bottom: 5px;
}

</style>

<form style="margin: 10px;" id="image_payment_form" action="/payment/ali-pay/build-alipay-form" method="post" target="_blank">
<input id="WIDtotal_fee" type="text" name="WIDtotal_fee" class="iv-input hidden" value="0">
<input id="callback_params_base64" type="text" name="callback_params_base64" class="iv-input hidden" value="">

<input id="imgPackageSurplus" type="text" name="imgPackageSurplus" class="iv-input hidden" value="<?=$imgPackageSurplus ?>">
<?php
if(count($imgPackageRecord) > 0){
?>
<div class="ltemNameImg">
	<b>已经购买容量：</b><span ><span ><?=$imgPackageRecord['other_params']['size_no'] ?> MB</span></span>
</div>

<div class="ltemNameImg">
	<b>套餐天数：</b><span ><span ><?=($imgPackageRecord['other_params']['days_no'] * 30) ?> 天</span></span>
</div>

<div class="ltemNameImg">
	<b>下次续费日期：</b><span ><span ><?=date("Y-m-d H:i:s", $imgPackageRecord['endtime']); ?></span></span>
</div>
<?php
}
?>


<div class="ltemNameImg">
	<b>购买容量：</b>
</div>

<div class="ltemContentImg">
	<div class="input-group">
		<input id="size_mb_no" onchange='util.imageLibrary.size_mb_change(false)' type="text" class="form-control" placeholder="输入100的整倍数">
		<span class="input-group-addon">MB</span>
	</div>
	<select id="days_no" class="form-control mLeft20" onchange='util.imageLibrary.size_mb_change(false)' >
		<option value="1" selected="">30天</option>
		<option value="2">60天</option>
		<option value="3">90天</option>
		<option value="6">180天</option>
		<option value="12">360天</option>
	</select>
	<div class="clear"></div>
</div>

<div class="ltemNameImg" style='clear:both;margin-top:50px;margin-bottom: 10px;'>
	<b>需要支付：</b><span class="price">￥<span id="image_price_now">0</span></span>
</div>

<div class="ltemContentImg">
	<label style='margin-bottom: 10px;<?=($balance > 0 ? '' : 'display:none;') ?>'>
		账户余额：<span class="price">￥<span id="account_balance"><?=$balance ?></span></span>
	</label>
	<br>
	<label>
		支付宝：<span class="price">￥<span id="alipay_price">0</span></span>
	</label>
	<br>
</div>

<div class="ltemContentImg" style='margin-top:25px;'>
	<span class="tipLabel">促销活动！</span>
	<p class="fColor2">单笔满 100 元，可享&nbsp;<span class="price">9.0</span>折优惠</p>
	<p class="fColor2">单笔满 200 元，可享&nbsp;<span class="price">8.5</span>折优惠</p>
	<p class="fColor2">单笔满 300 元，可享&nbsp;<span class="price">8.0</span>折优惠</p>
</div>

            
<div class="modal-footer" style='clear:both;'>
	<button type="button" id="image_expand_save" class="iv-btn btn-primary btn-sm" onclick="util.imageLibrary.size_mb_change(true)">支付</button>
	<button class="iv-btn btn-default btn-sm modal-close">关闭</button>
</div>

</form>