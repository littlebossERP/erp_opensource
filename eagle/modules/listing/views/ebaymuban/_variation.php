<?php 
use eagle\models\EbayCategory;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\listing\helpers\EbayListingHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/variation.js?v=".EbayListingHelper::$listingVer, ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/variation.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("varRestore.init();", \yii\web\View::POS_READY);
?>
<style>
.form-group{
	width:100%;
	margin:5px 5%;
}
.ddSelect,.deSelect{
	text-align: left;
    position: absolute;
    border: 1px solid #CCC;
 	display:none; 
    margin: 0;
    width: 170px !important;
    background-color: #ffffff;
    overflow: auto;
    padding: 1px;
    padding-top: 0px;
	z-index: 20;
	max-height:200px;
	overflow-y: scroll;
}
.ddSelect a,.deSelect a{
	display: block !important;
    text-decoration: none;
    overflow: hidden;
    cursor: pointer;
    font-size: 12px;
    padding: 0 4px !important;
    margin: 0px !important;
    border-top: 1px solid #F8F8F8;
    height: 20px;
    line-height: 21px;
	color:black !important;
}
.ddSelect a:hover,.deSelect a:hover{
	background: #66b3ff;
}
.variation_mutiuploader{
	display:none;
}
.trKey span{
	font-weight: bold;
}
.trKey {
	font-size:15px;
}
.trKey>:first-child{
	background-color: #F5F5F5;
    border-bottom: white 8px solid;
}
input[name="assoc_pic_key"]{
	margin-left: 15px;
}
</style>
    <input id="variation" name="variation" value='<?php echo empty($data['variation'])?null:json_encode($data['variation'])?>' type="hidden">
	<label class="control-label col-lg-3" for="pos_variation">多属性</label>

	  	<?php if (strlen($data['primarycategory'])>0):?>
		<?php if ($data['listingtype']=='Chinese'):?>
			<span>该刊登类型不支持多属性设置</span>
		<?php else:?>
		<?php $category=EbayCategory::find()->where('categoryid = :c and siteid = :s and leaf=1',[':c'=>$data['primarycategory'],':s'=>$data['siteid']])->one();?>
		<?php if (empty($category)||$category->variationenabled!=1):?>
			<span>该刊登类目不支持多属性设置</span>
		<?php else:?>
		<div class="col-lg-8">
		<div class="whole-onebox">
		<table  id="pos_variation">
		   <tr>
    		   <td>
    		       <span>是否开启多属性：</span><input type="radio" name="isMuti" value="1" <?php echo empty($data['variation'])?'':'checked="checked"'?>><span>开启</span>
    		       <input type="radio" name="isMuti" value="0" <?php echo empty($data['variation'])?'checked="checked"':''?>><span>关闭</span>
		       </td>
		   </tr>
		   <tr>
		        <td style="padding-top: 10px;">
		        <!--  
				<input type="button" class="iv-btn btn-search" id="create"value="设置" onclick="window.open('<?=Url::to(['/listing/ebaymuban/variation','primarycategory'=>$data['primarycategory'],'siteid'=>$data['siteid']])?>','Variation');">
				-->
				<input <?php echo empty($data['variation'])?'style="display:none;"':''?> type="button" class="iv-btn btn-search" id="create" value="设置" onclick="mutiattribute(<?php echo $data['primarycategory']?>,<?php echo $data['siteid'];?>)">
				<!--  <input type="button"  class="iv-btn btn-default"id="clean" value="清除" onclick="javascript:deleteva()"> -->
		        </td>
		        <td><span id="variationDisplayCell" style="color: red"></span></td>
		    </tr>

		</table>

		</div>
        <div class="form-group" style="display: none;" id="mutiattribute">
        <!-- 父窗口传递平台的值，如果没有，则显示没有选择平台，无法显示参考底价 -->
        <input type="hidden" name="siteidbyp" id="siteidbyp"/>
        <!-- 中间参数接受值，value和id -->
        <input type="hidden" name="convertidv" id="convertidv"/>
        	<input name="nametmp" id="nametmp" type="hidden">
        	<table class="iv-table mTop20" id="variation_table">
        	<thead>
        	<tr>
        		<th style="width:150px;">SKU</th>
        		<th style="width:85px;">数量</th>
        		<th style="width:85px;">价格</th>
        		<th style="width:115px;" id="th3"></th>
        		<th>
        			<span style="cursor: pointer;" onclick="addNew(this,<?php echo $data['primarycategory']?>,<?php echo $data['siteid'];?>)" class="glyphicon glyphicon-plus" title="添加属性"></span>
        		</th>
        	</tr>
        	</thead>
        	<tbody>
        	<tr>
        		<td>
        			<input name="v_sku[]" class="iv-input"  size="20">
                    <label id="displaybp1" style="display:none;"></label>
        		</td>
        		<td><input required="required" class="iv-input" name="v_quantity[]" size="10" onkeyup="replaceFloat(this)"></td>
        		<td><input required="required" class="iv-input" name="price[]" size="10" onkeyup="replacePrice(this)"></td>
        		<td><input required="required" class="iv-input" name="v_productid_val[]" size="15" value="Does not apply"></td>
        		<td style="text-align:center;">
        			<span style="cursor: pointer;" onclick="deleteRow(this)" class="glyphicon glyphicon-minus" title="删除该sku组"></span>
        		</td>
        	</tr>
        	</tbody>
        	</table>
        	<hr>
        	<table id="varTable">
            	<tr>
            		<td>
            			<input type="button" value="增加属性项" onclick="addItem()" class="iv-btn btn-search">
            		</td>
            	</tr>
        	   <tr style="font-size: 15px;">
        	       <td>
        			 <b style="font-weight: bold;">图片关联：</b><span id="assoc_pic_key"></span><input type="button" value="设置多属性图片" class="btn btn-primary btn-xs" id="setPic" style="display: none;margin-left:20px;margin-bottom:3px;" onclick="setPics()">
        		   </td>
        	   </tr>
        	</table>
        	<hr>
        <hr>
        	<table>
        	<tr>
        	<td>
        		
        	</td>
        	<td>
        	 	数量批量
        	 	<select id="mulset_quantity_type" class="iv-input">
        	 		<option value="=">=</option>
        	 		<option value="+">+</option>
        	 		<option value="-">-</option>
        	 		<option value="*">*</option>
        	 		<option value="/">/</option>
        	 	</select>
        	 	<input name="mulset_quantity" size="10" class="iv-input" onkeyup="replaceFloat(this)"><input type="button" value="批量设置数量" onclick="mulsetquantity()" class="iv-btn btn-search">&nbsp;
        	 	价格批量
        	 	<select id="mulset_price_type" class="iv-input">
        	 		<option value="=">=</option>
        	 		<option value="+">+</option>
        	 		<option value="-">-</option>
        	 		<option value="*">*</option>
        	 		<option value="/">/</option>
        	 	</select>
        		<input name="mulset_price" size="10" class="iv-input" onkeyup="replacePrice(this)"><input type="button" value="批量设置价格" onclick="mulsetprice()" class="iv-btn btn-search">&nbsp;
        	</td>
        	</table>
        </div>
		</div>
		<!-- <div class="col-lg-2"> -->

		<!-- </div> -->

	<script type="text/javascript">
// 	function renderVariation(json){
// 		if(json == ''){
// 			$('#variationDisplayCell').html('未设置多属性');
// 			return false;
// 		}
// 	 	var variationObj=jQuery.parseJSON(json);
// 	 	if (!(variationObj && typeof(variationObj.Variation) != 'undefined')){
// 	  		$('#variationDisplayCell').html('未设置多属性');
// 	  		return false;
// 	 	}
	 
// 		var retString='';
// 		for (var i=0; i<variationObj.Variation.length;i++){
// 			var row= variationObj.Variation[i].SKU+' ┃ ';
// 			if(typeof(variationObj.Variation[i].VariationProductListingDetails)!='undefined'){
// 				for(k in variationObj.Variation[i].VariationProductListingDetails){
// 					row = row+k+':'+variationObj.Variation[i].VariationProductListingDetails[k]+' ┃ ';
// 				}
// 			}
// 		  	for (var j=0;j<variationObj.Variation[i].VariationSpecifics.NameValueList.length;j++){
// 		   		tmp=variationObj.Variation[i].VariationSpecifics.NameValueList[j];
// 		   		row=row + tmp.Name +' ： ' +tmp.Value +' ┃ ';
// 			}
// 			retString=retString +row 
			   
// 			+ variationObj.Variation[i].Quantity
// 			+ ' ┃ '
// 			+ variationObj.Variation[i].StartPrice
// 			+' ┃<br>';
// 		}
// 		$('#variationDisplayCell').html(retString);
// 	}
// 	window.onload=function(){renderVariation($('#variation').val())};
// 	function deleteva(){
// 	 	$('#variation').val('');
// 	 	renderVariation('');
// 	}
	</script>






<!-- 
	<textarea rows="" cols="" style="display: none;" id="variation" name="variation" >-->
	<!--<?php echo json_encode($data['variation'])?>-->
	<!-- //<?php //echo $data['variation']?> -->
	<!--  </textarea> -->
	<?php endif;?>
	<?php endif;?>
	<?php endif;?>
