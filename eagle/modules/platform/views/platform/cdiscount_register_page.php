<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/EnsogoAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.EnsogoAccountsNeworedit.initWidget();" , \yii\web\View::POS_READY);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/baseOrder.css?v=20150921');
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/amazonOrder.css');

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);

?>

<style>
    .fRed{
        color:red;
    }
    .new-panel{
        width: 78%;
        text-align: center;
        margin-left: auto;
        margin-right: auto;
    }

    .new-panel .panel-body table{
        margin-left: auto;
        margin-right: auto;
    }
    /*tr td:first-child{*/
        /*width: 220px;*/
        /*text-align: right;*/
        /*font-weight: 600;*/
        /*font-size: larger;*/
    /*}*/
    .new-panel{
        width: 50%;
    }


</style>

<div style="  width: 84%;margin-left: auto;margin-right: auto;">
    <div class="cd_div" style="background-image:url(/images/cdiscount/cdiscount_intro_bg.png);background-repeat: no-repeat;width: 24%;height: 215px;padding: 10px 10px;background-size: 100% 100%;position: relative; top: -4px;">

        <p style="text-align: center; line-height: 37px;">法国Cdiscount简介<br>
        <p style=" line-height: 18px;">Cdiscount是法国最大的电子商务网站，分别在泰国、越南、柬埔寨、厄瓜多尔、比利时等11个国家设有站点，产品涵盖日常生活用品、食品、电子产品、家用电器、婴幼儿用品、箱包、玩具。2014年Cdiscoun营业额达45亿欧元，并为卖家提供专业、高效的物流解决方案。</p>
        <p/>
    </div>

    <div id="store-info" class="panel panel-default new-panel" style="margin-top: -215px;">
        <div class="panel-heading">
            <br>
            <h3 class="panel-title" style="font-weight: bold;"><i class="ico-file4 mr5"></i>Cdiscount开铺申请提交</h3>

        </div>

        <div class="panel-body">
            <tbody>
            <div style="text-align: center;">
                <form method="post" id="form1" name="form1" action="/platform/platform/cdiscount-register" enctype="multipart/form-data" >
                <table style="width: 70%;">
                    <!----------获取当前puid---------->
                    <input type="hidden" id="puid" name="puid" class="puid" value="<?=\Yii::$app->user->identity->getParentUid()?>">
                    <tr><td class=""><span class="" style="font-weight: 100;font-size: small;">公司营业执照（小于1M）</span>&nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="file" id="company_license_picture" name="company_license_picture" class="required_file"></td></tr>
                    <tr><td class="" colspan="2">&nbsp;</td></tr>

                    <tr><td class=""><span class="" style="font-weight: 100;font-size: small;">法人身份证照（小于1M）&nbsp;</span>&nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="file" id="sponsor_id_card_picture" name="sponsor_id_card_picture" value="" class="required_file"></td></tr>
                    <tr><td class="" colspan="2">&nbsp;</td></tr>

                    <tr><td class=""><span class="" style="font-weight: 100;font-size: small;">公司英文名（完整）&nbsp;</span>&nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="company_e_name" name="company_e_name" value="" class="form-control required_input" style="width:100%;display: inline;"></td></tr>

                    <tr><td class=""><span >公司营业执照码</span> &nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="company_license_code" name="company_license_code" value="" class="form-control required_input" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span >公司英文地址</span> &nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="company_e_address" name="company_e_address" value="" class="form-control required_input" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span >公司邮编</span> &nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="company_postcode" name="company_postcode" value="" class="form-control required_input" style="width:100%;display: inline;"></td></tr>

                    <tr><td class=""><span >Cdiscount英文店铺名</span> &nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="shop_e_name" name="shop_e_name" value="" class="form-control required_input" style="width:100%;display: inline;"</td></tr>

                    <tr><td class=""><span >负责人英文名</span>    &nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="director_e_name" name="director_e_name" value="" class="form-control required_input" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span >联系电话</span>&nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="phone" name="phone" value="" class="form-control required_input" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span class="" style="font-weight: 100;font-size: small;">电子邮箱（非QQ邮箱）</span>   &nbsp;<span class="fRed">*</span></td></tr>
                    <tr><td class=""><input required type="text" id="e_mail" name="e_mail" value="" class="form-control required_input" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span class="">销售中的亚马逊/eBay/速卖通 站点链接（全部）</span></td></tr>
                    <tr><td class=""><input type="text" id="all_website_link" name="all_website_link" value="" class="form-control" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span class="">海外仓名称</span></td></tr>
                    <tr><td class=""><input type="text" id="oversea_warehouse_name" name="oversea_warehouse_name" value="" class="form-control" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span class="">第三方收款方式</span></td></tr>
                    <tr><td class=""><input type="text" id="third_party_payment_method" name="third_party_payment_method" value="" class="form-control" style="width: 100%;display: inline;"></td></tr>

                    <tr><td class=""><span class="" style="font-weight: 100;font-size: small;">上传品牌图片（小于1M）&nbsp;</span></td></tr>
                    <tr><td class=""><input type="file" id="brand_picture" name="brand_picture" value="" ></td></tr>

                    <tr><td class="" colspan="2">&nbsp;</td></tr>
                    <tr>
                        <td class="" colspan="2" style="text-align: center;">
                            <input onclick="form_submit()" type="button" id="submit" name="submit" value="提 交" class="btn btn-success" style="width: 90px;">
<!--                        <input type="submit" id="submit" name="submit" value="提 交" class="btn btn-success" style="width: 90px;">-->
                            &nbsp;&nbsp;&nbsp;
                            <input type="button" id="reload" name="reload" value="重 置" class="btn btn-important" onclick="reload_page()" style="width: 90px;">
                            <!--   <input type="button" id="submit" name="submit" value="提 交"  onclick="submit_data()" class="btn btn-success">-->
                        </td>
                    </tr>
                </table>
            </form>
            </div>
            </tbody>
        </div>
    </div>
</div>
<script>
    //重置，刷新页面
    function reload_page(){
        window.location.reload();
    }

    function form_submit(){

        //前端检验文件是否符合上传类型，大小，必填
        if($("#company_license_picture").val())
        {
            var file = document.getElementById("company_license_picture").files[0];
            console.log(file);
            var _TheArray = new Array("image/jpeg","image/jpg","image/pjpeg","image/gif","image/png")
            var rs = $.inArray(file.type, _TheArray);
            if(rs==-1) {$.alert(file.name+' 文件类型不符合，只支持jpg,gif,png格式图片','info');return false;}
            if(file.size>'1048576'){$.alert(file.name+' 文件大小不能超过1MB','info');return false;}
        }
        else{$.alert('公司营业执照必填','info');return false;}

        if($("#sponsor_id_card_picture").val())
        {
            var file = document.getElementById("sponsor_id_card_picture").files[0];
            console.log(file);
            var _TheArray = new Array("image/jpeg","image/jpg","image/pjpeg","image/gif","image/png")
            var rs = $.inArray(file.type, _TheArray);
            if(rs==-1){$.alert(file.name+' 文件类型不符合，只支持jpg,gif,png格式图片','info');return false;}
            if(file.size>'1048576'){$.alert(file.name+' 文件大小不能超过1MB','info');return false;}
        }
        else{$.alert('法人身份证照必填','info');return false;}

        if($("#brand_picture").val())
        {
            var file = document.getElementById("brand_picture").files[0];
            console.log(file);
            var _TheArray = new Array("image/jpeg","image/jpg","image/pjpeg","image/gif","image/png")
            var rs = $.inArray(file.type, _TheArray);
            if(rs==-1){$.alert(file.name+' 文件类型不符合，只支持jpg,gif,png格式图片','info');return false;};
            if(file.size>'1048576'){$.alert(file.name+' 文件大小不能超过1MB','info');return false;};
        }

        var puid = $('#puid').val();
        var company_e_name = $("#company_e_name").val();if(company_e_name.length==0){$.alert('公司英文名必填','info');return false;}
        var company_license_code = $("#company_license_code").val();if(company_license_code.length==0){$.alert('公司营业执照码必填','info');return false;}
        var company_e_address = $("#company_e_address").val();if(company_e_address.length==0){$.alert('公司英文地址必填','info');return false;}
        var company_postcode = $("#company_postcode").val();if(company_postcode.length==0){$.alert('公司邮编必填','info');return false;}
        var shop_e_name = $("#shop_e_name").val();if(shop_e_name.length==0){$.alert('英文店铺名必填','info');return false;}
        var director_e_name = $("#director_e_name").val();if(director_e_name.length==0){$.alert('负责人英文名必填','info');return false;}
        var phone = $("#phone").val();if(phone.length==0){$.alert('联系电话必填','info');return false;}
        var e_mail = $("#e_mail").val();if(e_mail.length==0){$.alert('电子邮箱必填','info');return false;}
        var all_website_link = $("#all_website_link").val();
        var oversea_warehouse_name = $("#oversea_warehouse_name").val();
        var third_party_payment_method = $("#third_party_payment_method").val();

        $.ajaxFileUpload({
            url:'/platform/platform/cdiscount-register',
            fileElementId:['company_license_picture','sponsor_id_card_picture','brand_picture'],
            data:{puid:puid,company_e_name:company_e_name,company_license_code:company_license_code,company_e_address:company_e_address,company_postcode:company_postcode,shop_e_name:shop_e_name,
                  director_e_name:director_e_name,phone:phone,e_mail:e_mail,all_website_link:all_website_link,oversea_warehouse_name:oversea_warehouse_name,third_party_payment_method:third_party_payment_method},
            type:'post',
            dataType:'json',
            success: function (result){
                    if(result.success){
//                        $.alert('<font class="text-success">上传数据成功，请等候客服进行审核！</font>');
                        bootbox.alert('<font class="text-success">上传数据成功，请等候客服进行审核！</font>');
                    }else{
                        $.alert('<font color="red">'+result.message+'</font>');
                    }
            },
            error: function ( xhr , status , messages ){
                $.alert('<font color="red">'+messages+'</font>');
            }
        });

    }

</script>
