<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\Helper_Siteinfo;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoTimerListing */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="ebay-auto-timer-listing-table">
    <table id="create" class="table_listing">
        <!-- <caption>table title and/or explanatory text</caption> -->
        <thead>
            <tr>
                <th style="min-width:24px;max-width:24px;width:24px;box-sizing: content-box;">
                <div id="ck_all" ck="2" >
                    <input id="create_ck0" type="checkbox" name="id[]" value="0" onclick="autoInventory.checkAll(this)">
                </div>
                </th>
                <th style="width:100px;min-width:100px;">
                    img
                </th>
                <th>item信息</th>
                <th>sku</th>
                <th style="width:0.5%"></th>
                <th style="width:2.5%">subsku</th>
                <th style="width:2.5%">属性值</th>
                <th style="width:1.5%">价格</th>
                <th style="width:1%">库存</th>
                <th>时间</th>
                <!-- <th>补库数量</th> -->
                <th>操作</th>
            </tr>

        </thead>
        <tbody>
        <!-- 遍历输出在线item -->
        <?php foreach ($models as $mkey => $mval): ?>
            <?php \yii::info($mval->detail->mubanid,"file") ?>
            <tr name="<?=$mkey?>">
                <!--col1-checkbox  -->
                <td style="min-width:24px;max-width:24px;width:24px;box-sizing: content-box;">
                    <input id="lv1_create<?=$mkey?>" type="checkbox" class="create_ck" name="itemid<?=$mkey?>" value="<?=$mkey?>" onclick='autoInventory.selectlvOne(this)' >
                </td>
                <!--col2-img  -->
                <td style="width:100px;min-width:100px;">
                    <img alt="" src="<?=$mval->mainimg?>" style='width:60px;height: 60px;'>
                </td>
                <!--col3-item info  -->
                <td style="min-width:90px;max-width:250px;text-align:left;vertical-align:top;">
                    <span style="color:black"><!-- 标题 -->
                        <?php echo $mval->itemtitle ?>
                    </span>
                    <span class="pull-right"><!-- 类型 -->
                        <?php if ($mval->isvariation): ?>
                            <span class="squareSpan" data-container="body" data-toggle="popover" data-placement="right" data-html="true" style="background-color:#0087e0;">
                            多
                            </span>
                        <?php elseif($mval->listingtype=='FixedPriceItem'): ?>
                            <span class="squareSpan" data-container="body" data-toggle="popover" data-placement="right" data-html="true" style="background-color:#096;">
                            固
                            </span>
                        <?php elseif(($mval->listingtype=='Chinese')&&($mval->isvariation==0)): ?>
                            <span class="squareSpan" data-container="body" data-toggle="popover" data-placement="right" data-html="true" style="background-color:#C9D12D;">
                            拍
                            </span>
                        <?php endif ?>

                    </span>
                    <!-- itemid -->
<!--                     <p class="m0_ebay">
                        <a href=<?php //echo $mval->viewitemurl?>><?php //echo $mval->itemid; ?></a>
                    </p> -->

                    <p class="e_color e_m0"><!-- 卖家平台 -->
                        <?php $site=Helper_Siteinfo::getEbaySiteIdList('no','code')?>
                        <?php echo '「'.$mval->selleruserid.'」「'.$site[$mval->siteid].'」「'.$mval->paypal.'」'?>
                    </p>

                </td>
                <!--col4-sku  -->
                <td style="width:120px;min-width:120px;">
                    <span class="e_sku" style="display:inline-block;">
                        <?php echo isset($mval->sku)?$mval->sku:NULL?>
                    </span>
                </td>

                <!--col5-variation info  -->
                <td colspan="5" style="vertical-align:top;padding:0;min-width:400px;">
                    <table class="subtable_var" style="border:0px">
                        <tbody>
                        <?php if ($mval->isvariation): ?>
                        <?php $variation = $mval->detail->variation['Variation']?>
                        <!-- 遍历输出多属性 -->
                        <?php foreach ($variation as $key => $val): ?>

                            <?php \yii::info($val,"file") ?>
                            <tr style="vertical-align: top;">
                                <td style="width:5%">
                                </td>
                                <td style="width:25%"><!-- 输出subsku -->
                                    <span class="e_sku" style="display:inline-block;">
                                        <?php echo isset($val['SKU'])?$val['SKU']:NULL ?>
                                    </span>
                                </td>
                                <td style="width:25%"><!-- 输出name value -->
                                    <span class="e_sku" style="display:inline-block;">
                                        <?php if (!isset($val['VariationSpecifics']['NameValueList'][0])): ?>
                                        <?php $val['VariationSpecifics']['NameValueList']=array($val['VariationSpecifics']['NameValueList']) ?>
                                        <?php endif ?>
                                        <?php //print_r($val['VariationSpecifics']['NameValueList'],0) ?>
                                        <?php foreach ($val['VariationSpecifics']['NameValueList'] as $key => $value): ?>
                                            <?php if (is_array($value['Value'])): ?>
                                            <?php echo $value['Value'][0]  ?>
                                            <?php else: ?>
                                            <?php echo $value['Value'] ?>
                                            <?php endif ?>
                                        <?php endforeach ?>
                                    </span>
                                </td>
                                <td style="width:15%"><!-- 输出价格 -->
                                    <?php if (is_array($val['StartPrice'])): ?>
                                        <span><?php echo $val['StartPrice']['Value'] ?></span>
                                        <span><?php echo $val['StartPrice']['CurrencyID'] ?></span>
                                    <?php else: ?>
                                        <?php  echo $val['StartPrice'];  ?>
                                    <?php endif ?>
                                </td>
                                <td style="width:10%"><!-- 输出库存 -->
                                    <?php  echo $val['Quantity'];  ?>
                                </td>
                            </tr>
                        <?php endforeach ?>


                        <?php else: ?>
                            <td style="width:5%">
                            </td>
                            <td style="width:25%"><!-- 输出subsku -->
                            </td>
                            <td style="width:25%"><!-- 输出name value -->
                            </td>
                            <td style="width:15%"><!-- 输出价格 -->
                                <?php  echo $mval->startprice;  ?>
                            </td>
                            <td style="width:10%"><!-- 输出库存 -->
                                <?php  echo $mval->quantity;  ?>
                            </td>

                        <?php endif ?>
                        </tbody>
                    </table>
                </td>
                <!--col6-time-->
                <td style="width:110px;min-width:110px;box-sizing:content-box;">
                    <p class="e_m0"><?php echo $mval->listingduration; ?></p>
                    <?php if ($mval->outofstockcontrol): ?>
                        <p class="e_m0 e_color">永久在线</p>
                    <?php endif ?>

                </td>
                <!--col7-action-->
                <td>
                    <a onclick="autoTimerListing.create_setting(<?=$mval->mubanid?>)">
                        <span class="egicon-edit" title="设置定时刊登">
                        </span>
                    </a>
                </td>

            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>