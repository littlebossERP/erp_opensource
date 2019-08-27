<?php 

use eagle\models\EbayCategory;
use common\helpers\Helper_Array;
use common\helpers\Helper_Util;
use yii\helpers\Html;
?>
<style>
.iv-table{
	margin:10px 0px;
}
</style>
<p class="title">多属性</p>
<div class="subdiv">
<?php if (strlen($data['primarycategory'])>0):?>
<?php if ($data['listingtype']=='Chinese'):?>
该刊登类型不支持多属性设置
<?php else:?>
<?php 
//属性
if(!empty($data['variation']) && isset($data['variation']['VariationSpecificsSet']['NameValueList'])):
$NameValueList = $data['variation']['VariationSpecificsSet']['NameValueList'];
if (isset($NameValueList['Name'])){
	$NameValueList = array($NameValueList);
}
$specifics = Helper_Array::getCols($NameValueList, 'Name');
//图片
$Pictures = $data['variation']['Pictures'];
//variations
$Variation = $data['variation']['Variation'];
/* echo '<pre>';
print_r($data['variation']);
echo '</pre>'; */
?>
<div>
<table class="iv-table mTop20" id="variation_table">
   	<tr>
        <th><span class="glyphicon glyphicon-plus" id="addrow"></span></th>
        <th>SKU</th>
        <th>StartPrice</th>
        <th>Quantity</th>
        <th>
			<select name="v_productid_name" class="iv-input">
			<?php if (isset($product['upcenabled']) && $product['upcenabled'] == 'Required'){?>
			<option value="UPC" selected='true'>UPC</option>
			<?php }elseif (isset($product['isbnenabled']) && $product['isbnenabled'] == 'Required'){?>
			<option value="EAN" selected='true'>EAN</option>
			<?php }elseif (isset($product['eanenabled']) && $product['eanenabled'] == 'Required'){?>
			<option value="ISBN" selected='true'>ISBN</option>
			<?php }else{?>
				<option value="UPC">UPC</option>
				<option value="EAN">EAN</option>
				<option value="ISBN">ISBN</option>
			<?php }?>				
			</select>
		</th>
        <?php foreach ($specifics as $specific){?>
        <th>
        <?php echo $specific;?>
        <input type="hidden" name="nvl_name[]" value="<?=Helper_Util::inputEncode($specific)?>">
        </th>
        <?php }?>
    </tr>
    <?php foreach ($Variation as $variation_one){?>
    <tr>
    	<th><span class="glyphicon glyphicon-minus"></span></th>
        <?php $sku=isset($variation_one['SKU'])?$variation_one['SKU']:NULL?>
        <td><input size="25" class="iv-input" name="variationsku[]" value="<?php echo $sku;?>"></td>


        <td><input class="iv-input" name="startprice[]" size="6" value="<?php echo isset($variation_one['StartPrice']['Value'])?$variation_one['StartPrice']['Value']:$variation_one['StartPrice'];?>"></td>
        <td><input class="iv-input" name="quantity[]" size="2" value="<?php echo isset($variation_one['SellingStatus']['QuantitySold'])?$variation_one['Quantity']-$variation_one['SellingStatus']['QuantitySold']:$variation_one['Quantity'];?>"></td>
		<td>
		<?php 
			if (isset($variation_one['VariationProductListingDetails'])){
				if (isset($variation_one['VariationProductListingDetails']['EAN'])){
					$p_v = $variation_one['VariationProductListingDetails']['EAN'];
				}elseif (isset($variation_one['VariationProductListingDetails']['ISBN'])){
					$p_v = $variation_one['VariationProductListingDetails']['ISBN'];
				}elseif (isset($variation_one['VariationProductListingDetails']['UPC'])){
					$p_v = $variation_one['VariationProductListingDetails']['UPC'];
				}
                                        $p_v = 'Does not apply';
			}else{
				$p_v = 'Does not apply';
			}
		?>
		<input required="required" class="iv-input" name="v_productid_val[]" size="18" value="<?=$p_v?>"></td>
        <?php 
        if(isset($variation_one['VariationSpecifics']['NameValueList']['Name'])){
            $variation_one['VariationSpecifics']['NameValueList']=array($variation_one['VariationSpecifics']['NameValueList']);
        }
        foreach ($variation_one['VariationSpecifics']['NameValueList'] as $one){?>
        <td><input  class="iv-input" name="<?php echo base64_encode($one['Name'])?>[]" size="18" value="<?php echo is_array($one['Value'])?Helper_Util::inputEncode($one['Value'][0]):Helper_Util::inputEncode($one['Value']);?>"></td>
        <?php }?>
    </tr>
    <?php }?>
</table>
<input class="iv-input" name="mulset_price" size="10"><input type="button" class="iv-btn btn-success" value="批量设置价格" onclick="$('input[name^=startprice]').val($(this).prev().val())">&nbsp;
<input class="iv-input" name="mulset_quantity" size="10"><input type="button" class="iv-btn btn-success" value="批量设置数量" onclick="$('input[name^=quantity]').val($(this).prev().val())">&nbsp;
<br/><br/>
<table class="iv-table mTop20">
   	<tr>
        <th>
        	<span style="font-size: 12pt;">图片关联</span>
        <?php 
        $specifics_arr = array();
        foreach ($specifics as $specific){
        	$specifics_arr[$specific]= $specific;
        }
        if(isset($Pictures['VariationSpecificName'])){
            echo Html::radioList('VariationSpecificName',@$Pictures['VariationSpecificName'],$specifics_arr,array('separator'=>'&nbsp;&nbsp;'));
        }else{
            echo Html::radioList('VariationSpecificName',@$Pictures[0]['VariationSpecificName'],$specifics_arr,array('separator'=>'&nbsp;&nbsp;'));
        }
        ?>
        </th>
    </tr>
    <tr>
    <td>
    <table class="iv-table mTop20" id="picture-table">
    <tr>
    <th><span class="glyphicon glyphicon-plus" id="addpic"></span></th>
    <th width="100px;">属性值</th><th>图片</th></tr>
    <?php if(isset($Pictures['VariationSpecificPictureSet']['VariationSpecificValue'])){
        $Pictures['VariationSpecificPictureSet'] = array($Pictures['VariationSpecificPictureSet']);
        };
    ?>
    <?php if(isset($Pictures['VariationSpecificPictureSet'])){//修改格式
        $PicturesArray = $Pictures['VariationSpecificPictureSet'];
    }else if(isset($Pictures[0]['VariationSpecificPictureSet'])){
        $PicturesArray = $Pictures[0]['VariationSpecificPictureSet'];
    }else{
        $PicturesArray = [];
    }
    if(!empty($PicturesArray)){
        foreach ($PicturesArray as $picture){?>
    <tr>
    	<th><span class="glyphicon glyphicon-minus"></span></th>
	    <th><?php echo $picture['VariationSpecificValue']?></th>
	    <td>
	    <?php if (isset($picture['PictureURL'])){?>
		    <?php if (is_array($picture['PictureURL'])){?>
		    	<?php foreach ($picture['PictureURL'] as $PictureURL_one){?>
		    	<input class="iv-input" name="picture[<?php echo Helper_Util::inputEncode($picture['VariationSpecificValue']);?>][]" size="80" value="<?php echo $PictureURL_one;?>">
		    	<?php }?>
		    <?php }else{?>
		    	<input class="iv-input" name="picture[<?php echo Helper_Util::inputEncode($picture['VariationSpecificValue']);?>][]" size="80" value="<?php echo $picture['PictureURL'];?>">
		    <?php }?>
	    <?php }else{?>
	    	<input class="iv-input" name="picture[<?php echo Helper_Util::inputEncode($picture['VariationSpecificValue']);?>][]" size="80" value="">
	    <?php }?>
	    </td>
    </tr>
    <?php }}?>
    </table>
    </td>
    </tr>
</table>
</div>
<script type="text/javascript">

</script>
<?php else: //支持多属性 但是没有设置?>
刊登时未设置多属性,无法更改
<?php endif;?>
<?php endif;?>
<?php endif;?>
	</div>
</div>