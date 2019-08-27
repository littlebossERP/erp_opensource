<?php 
use eagle\models\EbayCategory;
use yii\helpers\Url;
?>
	<p class="title">多属性</p>
  	<?php if (strlen($data['primarycategory'])>0):?>
	<?php if ($data['listingtype']=='Chinese'):?>
	该刊登类型不支持多属性设置
	<?php else:?>
	<?php $category=EbayCategory::find()->where('categoryid = :c and siteid = :s and leaf=1',[':c'=>$data['primarycategory'],':s'=>$data['siteid']])->one();?>
	<?php if ($category->variationenabled!=1):?>
	该刊登类目不支持多属性设置
	<?php else:?>
	<table id="pos_variation">
	   <tr>
	        <td>
			<input type="button" class="iv-btn btn-search" id="create"value="设置" onclick="window.open('<?=Url::to(['/listing/ebaymuban/variation','primarycategory'=>$data['primarycategory'],'siteid'=>$data['siteid']])?>','Variation');">
			<input type="button"  class="iv-btn btn-default"id="clean" value="清除" onclick="javascript:deleteva()">
	        </td>
	    </tr>
	    <tr>
	        <td id="variationDisplayCell"></td>
	    </tr>
	</table>
	<script type="text/javascript">
	function renderVariation(json){
		if(json == ''){
			$('#variationDisplayCell').html('未设置多属性');
			return false;
		}
	 	var variationObj=jQuery.parseJSON(json);
	 	if (!(variationObj && typeof(variationObj.Variation) != 'undefined')){
	  		$('#variationDisplayCell').html('未设置多属性');
	  		return false;
	 	}
	 
		var retString='';
		for (var i=0; i<variationObj.Variation.length;i++){
			var row= variationObj.Variation[i].SKU+' ┃ ';
			if(typeof(variationObj.Variation[i].VariationProductListingDetails)!='undefined'){
				for(k in variationObj.Variation[i].VariationProductListingDetails){
					row = row+k+':'+variationObj.Variation[i].VariationProductListingDetails[k]+' ┃ ';
				}
			}
		  	for (var j=0;j<variationObj.Variation[i].VariationSpecifics.NameValueList.length;j++){
		   		tmp=variationObj.Variation[i].VariationSpecifics.NameValueList[j];
		   		row=row + tmp.Name +' ： ' +tmp.Value +' ┃ ';
			}
			retString=retString +row 
			   
			+ variationObj.Variation[i].Quantity
			+ ' ┃ '
			+ variationObj.Variation[i].StartPrice
			+' ┃<br>';
		}
		$('#variationDisplayCell').html(retString);
	}
	window.onload=function(){renderVariation($('#variation').val())};
	function deleteva(){
	 	$('#variation').val('');
	 	renderVariation('');
	}
	</script>
	<textarea rows="" cols="" style="display: none;" id="variation" name="variation" >
	<?php echo json_encode($data['variation'])?>
	</textarea>
	<?php endif;?>
	<?php endif;?>
	<?php endif;?>
