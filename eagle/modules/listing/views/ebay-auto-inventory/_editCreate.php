<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model eagle\modules\listing\models\EbayAutoInventory */
/* @var $form yii\widgets\ActiveForm */
$this->registerCssFile(\Yii::getAlias('@web').'/css/listing/ebay/ebayautoinventory.css');
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ebaylistingv2/inventoryIndex.js", ['depends' => ['yii\web\JqueryAsset']]);
?>

<div class="ebay-auto-inventory-table">
    <table id="cSave" class="table_listing">
        <caption>
            <p name='seller_id' style="display:none;"><?=$model['selleruserid']?></p>
            <a href="<?=$model['viewitemurl']?>" name='item_id'><?=$model['itemid']; ?></a>
        </caption>
        <thead>
            <tr>
                <th style="min-width:24px;max-width:24px;width:24px;box-sizing: content-box;">
                    <div id="cSave_all" ck="2" >
                        <input id="cSave_ck0" type="checkbox" name="id[]" value="0" onclick="autoInventory.selectAll(this)">
                    </div>
                </th>
                <th style="width:100px;min-width:100px;">
                    img
                </th>
                <th>sku</th>
                <th>多属性</th>
                <th>库存</th>
                <th>售出</th>
                <th>补库数量</th>
            </tr>
        </thead>
        <tbody id="cSave_body">
            <?php if ($model['isvariation']==1): ?>
            <?php $isvar=true; ?>
            <?php else: ?>
            <?php $isvar=false; ?>
            <?php endif ?>
            <?php if (!isset($variation['Variation'][0])): ?>
            <?php $variation['Variation']=array($variation['Variation']) ?>
            <?php endif ?>
            <?php foreach ($variation['Variation'] as $vkey => $val): ?>
            <tr name="<?=$vkey?>">
                <!--col1-checkbox  -->
                <td style="min-width:24px;max-width:24px;width:24px;box-sizing: content-box;">
                    <input id="lv1_cSave<?=$vkey?>" type="checkbox" class="cSave_ck" name="id<?=$vkey?>" value="<?=$vkey?>" onclick='autoInventory.selectlvOne(this)' >
                </td>
                <!--col2-img  -->
                <td style="width:100px;min-width:100px;">
                    <img alt="" src=<?=$model['mainimg']?> style='width:60px;height: 60px;'>
                    <p class="m0_ebay"><!-- itemid -->
                        <a href=<?=$model['viewitemurl']?>><?=$model['itemid']; ?></a>
                    </p>
                </td>
                <td>
                    <span class="e_sku" style="display:inline-block;">
                        <?php echo $isvar?(isset($val['SKU'])?$val['SKU']:NULL):(isset($model['sku'])?$model['sku']:NULL) ?>
                    </span>
                </td>
                <!--col3-name-value  -->
                <td style="width:100px;min-width:100px;">
                    <p name='vari-spec-<?=$vkey ?>' style="display: none;"><?=json_encode($val['VariationSpecifics']['NameValueList']) ?></p>
                    <span class="e_sku" style="display:inline-block;">
                        <?php if (!isset($val['VariationSpecifics']['NameValueList'][0])): ?>
                        <?php $val['VariationSpecifics']['NameValueList']=array($val['VariationSpecifics']['NameValueList']) ?>
                        <?php endif ?>

                        <?php foreach ($val['VariationSpecifics']['NameValueList'] as $key => $value): ?>
                            <p>
                            <?php if (is_array($value['Value'])): ?>
                            <?php echo '['.$value['Name'].':'.$value['Value'][0].']'  ?>
                            <?php else: ?>
                            <?php echo '['.$value['Name'].':'.$value['Value'].']' ?>
                            <?php endif ?>
                            </p>
                        <?php endforeach ?>
                    </span>
                </td>

                <td><?php  echo $isvar?$val['Quantity']:$model['quantity'];  ?></td>
                <td>
                    <?php if ($isvar): ?>
                        <?php if (isset($val['SellingStatus'])): ?>
                        <span><?php echo $val['SellingStatus']['QuantitySold'] ?></span>
                        <?php else: ?>
                        <span><?php echo '0' ?></span>
                        <?php endif ?>
                    <?php else: ?>
                        <span><?php echo $model['quantitysold'] ?></span>
                    <?php endif ?>

                </td>
                <td>
                    <input type="text" class="form-control" name="<?=$vkey ?>" value="<?=$isvar?$val['Quantity']:$model['quantity'];?>" size="4">
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    
    <div style="text-align: center;margin: 10px;">
        <button type="button" class="iv-btn btn-success" onclick="autoInventory.created_save()">新增</button>
        <!-- <button type="button" class="iv-btn btn-default">取消</button> -->
    </div>
    
</div>