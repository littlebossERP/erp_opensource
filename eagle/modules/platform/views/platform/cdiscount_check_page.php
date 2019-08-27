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

?>



<div class="tracking-index col2-layout" style="width: 95%; margin-left: auto;margin-right: auto;">
    <!-- --------------------------------------------搜索 begin--------------------------------------------------------------- -->
    <div>
        <!-- 搜索区域 -->
        <form class="form-inline" id="form1" name="form1" action="" method="post">
            <div style="margin:30px 0px 0px 0px">
                <!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
                &nbsp;&nbsp;&nbsp;
                注册邮箱：<?=Html::textInput('search_email',@$_REQUEST['search_email'],['class'=>'iv-input','id'=>'num'])?>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                注册日期：
                <input type="text" class="iv-input" id="Startdate" name="Startdate" value="<?= empty($startdate)?"":$startdate?>">--
                <input type="text" class="iv-input" id="Enddate" name="Enddate" value="<?= empty($enddate)?"":$enddate?>">

                <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
                <?=Html::submitButton('筛选',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
                <?=Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>

            </div>
        </form>
    </div>
    <!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->
    <br>
    <!-- --------------------------------------------列表  begin--------------------------------------------------------------- -->
    <div>
        <form name="form1" id="form1" action="" method="post">
            <br>
            <table class="table table-condensed table-bordered" style="font-size:12px; width: 100%;table-layout:fixed;">
                <tr>
                    <th width="4%">puid</th>
                    <th width="6%">店铺英文名</th>
                    <th width="6%"><b>注册日期</b></th>
                    <th width="6%"><b>公司营业执照</b></th>
                    <th width="6%"><b>法人身份证</b></th>
                    <th width="6%"><b>公司英文名</b></th>
                    <th width="6%"><b>公司营业执照码</b></th>
                    <th width="6%"><b>公司英文地址</b></th>
                    <th width="6%"><b>公司邮编</b></th>
                    <th width="6%"><b>站点链接</b></th>
                    <th width="6%"><b>负责人</b></th>
                    <th width="6%"><b>电话号码</b></th>
                    <th width="6%"><b>电子邮箱</b></th>
                    <th width="6%"><b>海外仓</b></th>
                    <th width="6%"><b>第三方收款方</b></th>
                    <th width="6%"><b>品牌</b></th>
                    <th width="6%"><b>备注</b></th>
                </tr>
                <?php if (count($cd_register_models)):foreach ($cd_register_models as $info):?>
                    <tr style="background-color: #f4f9fc">
                        <td style="display: none;"><?=$info['id']?></td>
                        <td><?=$info['puid']?></td>
                        <td><?=$info['shop_e_name']?></td>
                        <td><?=date('Y-m-d H:i:s',$info['create_time'])?></td>
                        <td><a href="<?=$info['company_license_picture']?>" target="_blank"><img src="<?=$info['company_license_picture']?>" width="60px" height="50px"/></a></td>
                        <td><a href="<?=$info['sponsor_id_card_picture']?>" target="_blank"><img src="<?=$info['sponsor_id_card_picture']?>" width="60px" height="50px"/></a></td>
                        <td><?=$info['company_e_name']?></td>
                        <td><?=$info['company_license_code']?></td>
                        <td><?=$info['company_e_address']?></td>
                        <td><?=$info['company_postcode']?></td>
                        <td style="word-wrap:break-word;"><?=$info['all_website_link']?></td>
                        <td><?=$info['director_e_name']?></td>
                        <td><?=$info['phone']?></td>
                        <td><?=$info['e_mail']?></td>
                        <td><?=$info['oversea_warehouse_name']?></td>
                        <td><?=$info['third_party_payment_method']?></td>
                        <td>
                            <?php
                            if(!empty($info['brand_picture'])){?>
                                <a href="<?=$info['brand_picture']?>" target="_blank"><img src="<?=$info['brand_picture']?>" width="60px" height="50px"/></a>
                            <?php } else echo '未上传图片'?>
                        </td>
                        <td>
                            <input type="button" id="desc" name="desc" value="编辑备注" class="iv-btn btn-important" onclick="update_desc(this,'<?=$info['id']?>')">
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
    window.onload =function() {
        $("#Startdate").datepicker({dateFormat: "yy-mm-dd"});
        $("#Enddate").datepicker({dateFormat: "yy-mm-dd"});
    }
</script>

<script>

    //重置
    function cleform(){
        $(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
    }

    //更新备注
    function update_desc(obj,id){
        $.ajax({
            type: "GET",
            dataType: 'html',
            url:'/platform/platform/update-desc-page',
            data: {id:id},
            success: function (result) {
//                    bootbox.alert(result.message);

                bootbox.dialog({
                    title: Translator.t("确认拣货完成"),
                    className: "update-desc-page",
                    message: result
                });
            }
        });

    }



</script>
