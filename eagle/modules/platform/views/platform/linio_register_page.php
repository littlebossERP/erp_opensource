<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\models\LinioRegister;

     //货运方式
     $shippingService = [
            'Seller DHL'=>'Seller DHL',
            'Seller DHL Account'=>'Seller DHL Account',
            'Seller FedEx'=>'Seller FedEx',
            'Postal'=>'Postal',
            'Servientrega'=>'Servientrega',
            'DHL E-commerce'=>'DHL E-commerce',
            'Estafeta'=>'Estafeta',
            'N/A'=>'N/A',
            ];

    $check_result = '';
    if($is_exist > 0){
        foreach($is_exist as $pass){
            $check_result = $pass['status'];
        }
    }

?>

<style>
    .left_panel{
        background-image:url(/images/cdiscount/cdiscount_intro_bg.png);background-repeat: no-repeat;
        width: 22%;
        height: 215px;
        padding: 10px 15px;
        background-size: 100% 100%;
        position: relative;
        top: -4px;
        margin-right: 10px;
        float: left;
    }

    .middle_panel{
        width: 50%;
        text-align: center;
        margin-left: auto;
        margin-right: auto;
        float: left;
    }
    .middle_panel p{
        text-align: left;
    }
    .middle_panel_small_title{
        width: 23%;
        font-weight: bold;
        font-size: 18px;
        margin: 15px 0 17px -5%;
    }
    
    .middle_panel_short_input{
        float: left;
        margin-right: 50px;
        height: 57px
    }
    .middle_panel_select{
        width:144px;
        margin:0px;
        height: 30px;
        margin-top: 17px;
    }
    .input_newline{
        clear: both;
    }

    .right_panel{
        float: right;
        margin-top: 109px;
        width: 24%;
    }
    .right_panel table td{
        border: 1px solid #ddd;
        height: 54px;
        text-align: center;
    }
    .right_panel_small_title{
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 15px;
    }


</style>

<div style="width: 92%;margin-left: auto;margin-right: auto;">
    <!---------------------------------------------start 左边模块------------------------------------------------>
    <div class="left_panel">
        <p style="text-align: center; line-height: 37px;">拉美最大电商平台Linio<br>
        <p style=" line-height: 18px;margin-bottom: 14px;">Linio是目前拉美地区最大的电商平台，隶属于德国电子商务集团Rocket Internet SE旗下，已开通墨西哥，哥伦比亚，秘鲁，智利，阿根廷、厄瓜多尔、委内瑞拉、巴拿马独立站点。Linio在拉美有3000多人的本地团队，专注解决用户物流、支付、市场，客诉等问题。<p/>
        <div style="width: 107px;margin: 0 auto;"><a href="http://www.littleboss.com/linio.html">点击这里了解更多</a></div>
    </div>
    <!---------------------------------------------end 左边模块------------------------------------------------>

    <!---------------------------------------------start 中间模块------------------------------------------------>
    <div id="store-info" class="panel panel-default middle_panel">
        <div class="panel-heading">
            <br>
            <h3 class="panel-title" style="font-weight: bold;"><i class="ico-file4 mr5"></i>Linio开铺申请提交</h3>
        </div>

        <div class="panel-body">
            <tbody>
            <div>
            <form method="post" id="form1" name="form1" action="" enctype="multipart/form-data" >
                <div style="width: 70%;margin: 0 auto;">
                    <!-------获取当前puid-------->
                    <input type="hidden" id="puid" name="puid" class="puid" value="<?=$puid?>">
                    <!-------获取审核结果-------->
                    <input type="hidden" id="check_result" name="check_result" class="check_result" value="<?=$check_result?>">

                    <!----------------基础信息------------------>
                    <div>
                        <p class="middle_panel_small_title">| 基础信息</p>
                            <div>
                                <p>店铺名称（首字母需大写，名称尽量简洁）</p>
                                <input type="text" id="shop_name" name="shop_name" value="" class="form-control">
                            </div>
                            <div>
                                <p>公司名（须与P卡账户一致）</p>
                                <input type="text" id="company_name" name="company_name" value="" class="form-control">
                            </div>
                            <div style="height: 57px;">
                                <div class="middle_panel_short_input">
                                    <p>法人名称</p>
                                    <input type="text" id="director_name" name="director_name" value="" class="form-control">
                                </div>
                                <div class="middle_panel_short_input">
                                    <p>税务登记证号</p>
                                    <input type="text" id="tax_registration_certificate" name="tax_registration_certificate" value="" class="form-control">
                                </div>
                            </div>
                    </div>

                    <!----------------联系方式------------------>
                    <div>
                        <p class="middle_panel_small_title">| 联系方式</p>
                            <div>
                                <p>Linio注册邮箱（不可出现Linio字符）</p>
                                <input  type="text" id="e_mail" name="e_mail" value="" class="form-control">
                            </div>
                            <div>
                                <p>其他平台店铺网址</p>
                                <input  type="text" id="other_website_link" name="other_website_link" value="" class="form-control">
                            </div>
                            <div style="height: 115px;">
                                <div class="middle_panel_short_input">
                                    <p>联系人姓名</p>
                                    <input  type="text" id="contact" name="contact" value="" class="form-control">
                                </div>
                                <div class="middle_panel_short_input">
                                    <p>电话</p>
                                    <input  type="text" id="phone" name="phone" value="" class="form-control">
                                </div>
                                <div class="middle_panel_short_input">
                                    <p>手机</p>
                                    <input  type="text" id="mobile" name="mobile" value="" class="form-control">
                                </div>
                                <div class="middle_panel_short_input">
                                    <p>Skype</p>
                                    <input  type="text" id="skype" name="skype" value="" class="form-control">
                                </div>
                            </div>
                    </div>

                    <!----------------账单地址------------------>
                    <div>
                        <p class="middle_panel_small_title">| 账单地址</p>
                        <div style="height: 118px;">
                            <div class="middle_panel_short_input">
                                <p>国家</p>
                                <input  type="text" id="bill_country" name="bill_country" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>省份</p>
                                <input  type="text" id="bill_province" name="bill_province" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>城市</p>
                                <input  type="text" id="bill_city" name="bill_city" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>邮政编码</p>
                                <input  type="text" id="bill_postal_code" name="bill_postal_code" value="" class="form-control">
                            </div>
                        </div>
                        <div style="margin-bottom: 23px;">
                            <p style="clear: both;">街道、门牌号码</p>
                            <input  type="text" id="bill_street" name="bill_street" value="" class="form-control">
                        </div>
                    </div>

                    <!----------------仓库地址------------------>
                    <div>
                        <p class="middle_panel_small_title">| 仓库地址</p>
                        <div style="height: 175px;">
                            <div class="middle_panel_short_input" style="margin-right: 200px;">
                                <p>仓库联系人</p>
                                <input  type="text" id="warehouse_contacts" name="warehouse_contacts" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>国家</p>
                                <input  type="text" id="warehouse_country" name="warehouse_country" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>省份</p>
                                <input  type="text" id="warehouse_province" name="warehouse_province" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>城市</p>
                                <input  type="text" id="warehouse_city" name="warehouse_city" value="" class="form-control">
                            </div>
                            <div class="middle_panel_short_input">
                                <p>邮政编码</p>
                                <input  type="text" id="warehouse_postal_code" name="warehouse_postal_code" value="" class="form-control">
                            </div>
                        </div>
                        <div style="margin-bottom: 23px;">
                            <p style="clear: both;">街道、门牌号码</p>
                            <input  type="text" id="warehouse_street" name="warehouse_street" value="" class="form-control">
                        </div>
                    </div>

                    <!----------------货运信息------------------>
                    <div>
                        <p class="middle_panel_small_title">| 货运信息</p>
                        <div style="height: 289px;">
                            <div class="middle_panel_short_input">
                                <p>Chile</p>
                                <input  type="text" id="chile_arr_day" name="chile_arr_day" value="" class="form-control" placeholder="到货时长（天）">
                            </div>
                            <div class="middle_panel_short_input">
                                <?=Html::dropDownList('chile_ship_service','',$shippingService,['prompt'=>'--选择货运方式--','class'=>'iv-input do middle_panel_select']);?>
                            </div>

                            <div class="middle_panel_short_input input_newline">
                                <p>Colombia</p>
                                <input  type="text" id="colombia_arr_day" name="colombia_arr_day" value="" class="form-control" placeholder="到货时长（天）">
                            </div>
                            <div class="middle_panel_short_input">
                                <?=Html::dropDownList('colombia_ship_service','',$shippingService,['prompt'=>'--选择货运方式--','class'=>'iv-input do middle_panel_select']);?>
                            </div>

                            <div class="middle_panel_short_input input_newline">
                                <p>Mexico</p>
                                <input  type="text" id="mexico_arr_day" name="mexico_arr_day" value="" class="form-control" placeholder="到货时长（天）">
                            </div>
                            <div class="middle_panel_short_input">
                                <?=Html::dropDownList('mexico_ship_service','',$shippingService,['prompt'=>'--选择货运方式--','class'=>'iv-input do middle_panel_select']);?>
                            </div>

                            <div class="middle_panel_short_input input_newline">
                                <p>Panama</p>
                                <input  type="text" id="panama_arr_day" name="panama_arr_day" value="" class="form-control" placeholder="到货时长（天）">
                            </div>
                            <div class="middle_panel_short_input">
                                <?=Html::dropDownList('panama_ship_service','',$shippingService,['prompt'=>'--选择货运方式--','class'=>'iv-input do middle_panel_select']);?>
                            </div>

                            <div class="middle_panel_short_input input_newline">
                                <p>Peru</p>
                                <input  type="text" id="peru_arr_day" name="peru_arr_day" value="" class="form-control" placeholder="到货时长（天）">
                            </div>
                            <div class="middle_panel_short_input">
                                <?=Html::dropDownList('peru_ship_service','',$shippingService,['prompt'=>'--选择货运方式--','class'=>'iv-input do middle_panel_select']);?>
                            </div>
                        </div>
                    </div>

                    <!----------------账户信息------------------>
                    <div>
                        <p class="middle_panel_small_title">| 账户信息</p>
                        <div>
                            <p>Payoneer卡名称</p>
                            <input  type="text" id="payoneer_name" name="payoneer_name" value="" class="form-control">
                        </div>
                        <div>
                            <p>Payoneer ID</p>
                            <input  type="text" id="payoneer_id" name="payoneer_id" value="" class="form-control">
                        </div>
                    </div>

                    <!------------------确认提交------------------>
                    <div style="margin-top: 30px;">
                        <input onclick="form_submit(<?=$check_result?>)" type="button" id="submit" name="submit" value="确认提交" class="btn btn-success" style="width: 106px;">
                    </div>
                </div>
            </form>
            </div>
            </tbody>
        </div>
    </div>
    <!---------------------------------------------end 中间模块------------------------------------------------>

    <!---------------------------------------------start 左边模块------------------------------------------------>
    <div class="right_panel">
        <div>
            <p class="right_panel_small_title">平台佣金</p>
            <table width="100%">
                <tr><td>类目</td><td>佣金比例</td></tr>
                <tr><td>Appliances</td><td>10%</td></tr>
                <tr><td>Books</td><td>3%</td></tr>
                <tr><td>Cellphones</td><td>6%</td></tr>
                <tr><td>Computing</td><td>6%</td></tr>
                <tr><td>Fashion</td><td>10%</td></tr>
                <tr><td>Health & Beauty</td><td>10%</td></tr>
                <tr><td>Home</td><td>10%</td></tr>
                <tr><td>Kids & Babies</td><td>10%</td></tr>
                <tr><td>Photography</td><td>6%</td></tr>
                <tr><td>Sports</td><td>10%</td></tr>
                <tr><td>TV/Audio/Video</td><td>6%</td></tr>
                <tr><td>Videogames</td><td>6%</td></tr>
                <tr><td>Other</td><td>10%</td></tr>
            </table>
        </div>

        <div style="width: 107%;margin-left: -10px;margin-top: 8px;">
            <p style="color: red;">*For accessories/peripherals, commission rate will be 10%</p>
            <p style="color: red;">**For Chile, commission rate for books will be 15%</p>
        </div>

        <div style="margin-top: 74px;">
            <p class="right_panel_small_title">平台惩罚</p>
            <table width="100%">
                <tr><td>事件</td><td>惩罚</td></tr>
                <tr><td>卖家取消订单>2%</td><td>账户冻结24小时</td</tr>
                <tr><td>延迟发货>10%</td><td>账户冻结24小时</td></tr>
            </table>
        </div>
    </div>
    <!---------------------------------------------end 左边模块------------------------------------------------>



</div>
<script>
    function form_submit(check_result){

        if(check_result == 0){
            bootbox.alert('贵户已经申请过注册，请耐心等候审核！<br>我们会尽快将审核结果发送到贵户邮箱中！');
            return false;
        }
        if(check_result == 1){
            bootbox.alert('<font color="red">贵户已经成功通过linio注册审核，请勿重复申请！</font>');
            return false;
        }

        //检查所有text框必填
        var input_obj=$('input');
        for(var i=0;i<input_obj.length;i++){
            if(input_obj[i].type=='text'){
                if(input_obj[i].value=='') {
                    $.alert('存在未填写项，请检查！','success');
                    return false;
                }
            }
        }

        //检查所有select框必填
        var select_obj=$('select');
        for(var i=0;i<select_obj.length;i++){
            if(select_obj[i].value=='') {
                $.alert('存在货运方式未选择，请检查！','success');
                return false;
            }
        }

        var reg =/^.*[lL]inio.*$/;
        var v = $("#e_mail").val();
        if(reg.test(v)){
            $.alert('注册邮箱不允许出现linio字符','success');
            return false;
        }

        var reg = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i;
        var v = $("#e_mail").val();
        if(!reg.test(v)){
            $.alert('注册邮箱格式错误，请正确填写！','success');
            return false;
        }

        var reg =/^[A-Z].*$/;
        var v = $("#shop_name").val();
        if(!reg.test(v)){
            $.alert('店铺名称（首字母需大写）','success');
            return false;
        }

        var values = $('#form1').serialize();
        $.ajax({
            type: "post",
            dataType: 'json',
            url: '/platform/platform/linio-register',
            data: values ,
            success: function (result) {
                if(result.success){
                    var event = $.confirmBox('<font class="text-success">'+result.message+'</font>');
                    event.then(function(){
                        // 确定
                        window.location.reload();//刷新当前页面.
                    },function(){
                        // 取消
                        window.location.reload();//刷新当前页面.
                    });
                }else{
                    $.alert('<font color="red">'+result.message+'</font>');
                }
            },
            error :function () {
                $.alert('<font color="red">上传数据失败！</font>');
            }

        });




    }

</script>
