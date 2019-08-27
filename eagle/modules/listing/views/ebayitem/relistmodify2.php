<?php

use yii\helpers\Html;
use eagle\models\EbayCategory;
use common\helpers\Helper_Array;
use common\helpers\Helper_Siteinfo;
use yii\helpers\Url;
use eagle\modules\listing\helpers\EbayListingHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ckeditor/ckeditor.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js?v=".EbayListingHelper::$listingVer, ['depends' => ['yii\web\JqueryAsset']]);

?>
<style>
body{
	font-size:10px;
}
div{
	width:expression(document.body.clientWidth + 'px');
}
.bbar{position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        display: block;
        height: 40px;
        background:white;
        line-height: 17px;
        overflow: hidden;
    }
</style>
<script>
//标题长度
function inputbox_left(inputId,limitLength,text){
    var o=document.getElementById(inputId);
    if(text==undefined){
        left=limitLength-o.value.length;
    }else{
        left=limitLength-text.length;
    }
    $('#length_'+inputId).html(left);
    if(left>=0){
        $('#length_'+inputId).css({'color':'green'});
    }else{
        $('#length_'+inputId).css({'color':'red'});
    }
}
//组织Ebay店铺分类数据 返回json对象
function convertTree(rows){
    nodes = [];  
   // 得到顶层节点
   for(var i = 0; i< rows.length; i++){  
       var row = rows[i];  
       if (row.category_parentid==0){  
           nodes.push({  
               id:row.categoryid,  
               text:row.category_name
           });  
       }  
   }  
     
   var toDo = [];  
   for(var i = 0; i < nodes.length; i++){  
       toDo.push(nodes[i]);  
   }  
   while(toDo.length){  
       var node = toDo.shift();    // 父节点 
       // 得到子节点 
       for(var i=0; i<rows.length; i++){  
           var row = rows[i];  
           if (row.category_parentid == node.id){  
        	   var child = {id:row.categoryid,text:row.category_name};  
               if (node.children){  
                   node.children.push(child);  
               } else {  
                   node.children = [child];  
               }  
               toDo.push(child);  
           }  
       }  
   }
   return nodes;
}
//提交到修改页面
function toEditStep3(){
    document.a.action=global.baseUrl+'listing/ebayitem/relistmodify3';
    document.a.submit();
    document.a.action="";
}
</script>
<br/>
<div class="container" style="width:98%;margin-left:1%;">
<form action="" method="post" id="a" name="a">
<?=Html::hiddenInput('setitemvalues',implode(',', $setitemvalues))?>
<?=Html::hiddenInput('selleruserid',@$data['selleruserid'],['id'=>'selleruserid']);?>
<?php if (strlen(@$data['itemid'])):?>
<?=Html::hiddenInput('itemid',@$data['itemid'])?>
<?php endif;?>

<!-- 平台与细节 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>平台与细节</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php if (in_array('category', $setitemvalues)){?>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登分类一</p></div>
	  <div class="col-lg-10">
	  	<label>
		<?php if(strlen($data['primarycategory'])){
			$ec = EbayCategory::findBySql('select * from ebay_category where siteid='.$data['siteid'].' AND categoryid='.$data['primarycategory'].' and leaf=1')->one();
			echo EbayCategory::getPath($ec,$ec->name,$data['siteid']);
		}
		?>
		</label><br>
		<input class="category" id="primarycategory" name="primarycategory" size="25" value="<?php echo $data['primarycategory']?>">
		<input type="button" value="选择分类一" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategory','siteid'=>$data['siteid'],'elementid'=>'primarycategory'])?>')">
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登分类二</p></div>
	  <div class="col-lg-10">
	  	<label>
		<?php if(strlen($data['secondarycategory'])){
			$ec=EbayCategory::findBySql('select * from ebay_category where siteid='.$data['siteid'].' AND categoryid='.$data['secondarycategory'].' and leaf=1')->one();
			if (isset($ec)){
				echo EbayCategory::getPath($ec,$ec->name,$data['siteid']);
			}
		}
		?>
		</label><br>
		<input class="category" id="secondarycategory" name="secondarycategory" size="25" value="<?php echo $data['secondarycategory']?>">
		<input type="button" value="选择分类二" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategory','siteid'=>$data['siteid'],'elementid'=>'secondarycategory'])?>')">
	  </div>
	</div>
	<?php }?>
	<?php if (in_array('conditionid',$setitemvalues)){?>
	<?=$this->render('_condition',array('condition'=>$condition,'val'=>$data))?>
	<?php }?>
	<?php if (in_array('itemspecifics',$setitemvalues)){?>
		<?php 
		if (isset($data['itemspecifics']['NameValueList']) && is_array($data['itemspecifics']['NameValueList'])){
			if (isset($data['itemspecifics']['NameValueList']['Name'])){
				$tmp = $data['itemspecifics']['NameValueList'];
				unset($data['itemspecifics']['NameValueList']);
				$data['itemspecifics']['NameValueList'][]=$tmp;
			}
			$ItemSpecific = Helper_Array::toHashmap($data['itemspecifics']['NameValueList'],'Name','Value');
		}else{
			$ItemSpecific = array();
			foreach($specifics as $onespecific){
				$ItemSpecific[$onespecific->name]='';
			}
		}
		?>	
		<?=$this->render('_specific',array('specifics'=>$specifics,'val'=>$ItemSpecific))?>
		<hr/>
	<?php }?>
	
	
	<?php if (in_array('variation',$setitemvalues)){?>
	<?=$this->render('_variation',array('data'=>$data))?>
	<?php }?>
  </div>
</div>


<!-- 标题与价格 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>标题与价格</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
<?php if (in_array('itemtitle',$setitemvalues)){?>
<div class="row">
    <div class="col-lg-2"><p class="text-right">刊登主标题</p></div>
    <div class="col-lg-10">
	<input name="itemtitle" size="80" value="<?php echo $data['itemtitle']?>" id="itemtitle" onkeydown="inputbox_left('itemtitle',80)" onkeypress="inputbox_left('itemtitle',80)" onkeyup="inputbox_left('itemtitle',80)">
	<span id='length_itemtitle' style="font-weight:bold">80  </span>
	</div>
</div>
<div class="row">
    <div class="col-lg-2"><p class="text-right">刊登副标题</p></div>
    <div class="col-lg-10">
	<input name="subtitle" size="80" value="<?php echo $data['subtitle']?>">
	</div>
</div>
<?php }?>
<?php if (in_array('sku',$setitemvalues)){?>
<div class="row">
  <div class="col-lg-2"><p class="text-right">Customer Label</p></div>
  <div class="col-lg-10"><?=Html::textInput('sku',$data['sku'],array('size'=>30))?>使用多属性时,该值将无效</div>
</div>
<?php }?>
<?php if (in_array('quantity',$setitemvalues)){?>
<div class="row">
  <div class="col-lg-2"><p class="text-right">数量</p></div>
  <div class="col-lg-10"><?=Html::textInput('quantity',$data['quantity'],array('size'=>10))?>使用多属性时,该值将无效</div>
</div>
<div class="row">
  <div class="col-lg-2"><p class="text-right">LotSize</p></div>
  <div class="col-lg-10"><?=Html::textInput('lotsize',$data['lotsize'],array('size'=>3))?></div>
</div>
<?php }?>

<?php if (in_array('listingduration',$setitemvalues)){?>
<div class="row">
  <div class="col-lg-2"><p class="text-right">刊登天数</p></div>
  <div class="col-lg-10"><?=Html::dropDownList('listingduration',$data['listingduration'],Helper_Siteinfo::getListingDuration($data['listingtype']))?></div>
</div>
<?php }?>

<?php if (in_array('price',$setitemvalues)){?>
<?=$this->render('_price',array('data'=>$data))?>
<?php }?>

<?php if (in_array('bestoffer',$setitemvalues)){?>
<?=$this->render('_bestoffer',array('data'=>$data))?>
<?php }?>

<?php if (in_array('privatelisting',$setitemvalues)){?>
<div class="row">
  <div class="col-lg-2"><p class="text-right">私人刊登</p></div>
  <div class="col-lg-10">
<?=Html::checkbox('privatelisting',$data['privatelisting'])?>
是否设置为私人刊登(privateListing)
</div>
</div>
<?php }?>
  </div>
</div>

<!-- 图片与描述 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>图片与描述</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php if (in_array('imgurl',$setitemvalues)){?>
	<?=$this->render('_imgurl',array('data'=>$data));?>
	<?php }?>
	<?php if (in_array('itemdescription',$setitemvalues)){?>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">描述</p></div>
	  	<div class="col-lg-10">
	  	<?=Html::textarea('itemdescription',$data['itemdescription'],array('rows'=>20,'cols'=>100))?>
		</div>
	</div>
	<?php }?>
  </div>
</div>

<!-- 物流设置 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>物流设置</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php if (in_array('shippingdetails',$setitemvalues)){?>
	<?=$this->render('_shipping',array('data'=>$data,'salestaxstate'=>@$salestaxstate,'shippingserviceall'=>$shippingserviceall))?>
	<?php }?>
    <?php if (in_array('dispatchtime',$setitemvalues)){?>
    <hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">包裹处理时间</p></div>
	  	<div class="col-lg-10">
		<?=Html::dropDownList('dispatchtime',$data['dispatchtime'], $dispatchtimemax)?>
		</div>
	</div>
	<?php }?>
  </div>
</div>

<!-- 收款与退货 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>收款与退货</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php if (in_array('paymentmethods',$setitemvalues)){?>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">收款方式</p></div>
	  	<div class="col-lg-10">
		<?=Html::checkBoxList('paymentmethods',@$data['paymentmethods'], $paymentoption)?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">立即付款</p></div>
	  	<div class="col-lg-10">
		<?=Html::checkBox('autopay',@$data['autopay'],array('uncheckValue'=>0))?>是否要求买家立即付款
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">付款说明</p></div>
	  	<div class="col-lg-10">
	  	<?=Html::textArea('shippingdetails[PaymentInstructions]',@$data['shippingdetails']['PaymentInstructions'],array('rows'=>5,'cols'=>60))?>
		</div>
	</div>
	<hr/>
	<?php }?>
	<?php if (in_array('return_policy',$setitemvalues)){
		?>
	<?=$this->render('_returnpolicy',array('data'=>$data,'return_policy'=>$returnpolicy))?>
	<?php }?>
	<?php if (in_array('location',$setitemvalues)){?>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">商品所在地</p></div>
	  	<div class="col-lg-10">
	  	<table>
		<tr>
		<th>国家</th>
		<td>
		<?=Html::dropDownList('country',@$data['country'],$locationarr)?>
		</td>
		</tr>
		<tr>
		<th>地区</th>
		<td>
		<?=Html::textInput('location',@$data['location'])?>
		</td>
		</tr>
		<tr>
		<th>邮编</th>
		<td>
		<?=Html::textInput('postalcode',@$data['postalcode'])?>
		</td>
		</tr>
		</table>
		</div>
	</div>
	<?php }?>
  </div>
</div>

<!-- 增强设置 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>增值设置</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php if (in_array('gallery',$setitemvalues)){?>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">图片显示方式</p></div>
	  	<div class="col-lg-10">
	  	<?=Html::radioList('gallery',@$data['gallery'],array('0'=>'不使用','Featured'=>'Featured($)','Gallery'=>'Gallery($)','Plus'=>'Plus($)'))?>
		</div>
	</div>
	<?php }?>
	<?php if (in_array('listingenhancement',$setitemvalues)){?>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">样式</p></div>
	  	<div class="col-lg-10">
	  	<?=Html::checkBoxList('listingenhancement',@$data['listingenhancement'],$feature_array)?>
		</div>
	</div>
	<?php }?>
	<?php if (in_array('hitcounter',$setitemvalues)){?>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">计数器</p></div>
	  	<div class="col-lg-10">
	  	<?=Html::radioList('hitcounter',@$data['hitcounter'],array('NoHitCounter'=>'不用计数器','BasicStyle'=>'BasicStyle','RetroStyle'=>'RetroStyle'))?>
		</div>
	</div>
	<?php }?>
  </div>
</div>

<!-- 账号 -->
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>账号</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php if (in_array('paypal',$setitemvalues)){?>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">Paypal账号</p></div>
	  	<div class="col-lg-10">
	  	<?=Html::textInput('paypal',@$data['paypal'])?>
		</div>
	</div>
	<?php }?>
	<?php if (in_array('storecategory',$setitemvalues)){?>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">店铺类目一</p></div>
	  	<div class="col-lg-10">
		<?=Html::textInput('storecategoryid',@$data['storecategoryid'],array('id'=>"storecategoryid"))?>
		<?=Html::button('选择',['onclick'=>'doset("storecategoryid")'])?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">店铺类目二</p></div>
	  	<div class="col-lg-10">
		<?=Html::textInput('storecategory2id',@$data['storecategory2id'],array('id'=>"storecategory2id"))?>
		<?=Html::button('选择',['onclick'=>'doset("storecategory2id")'])?>
		</div>
	</div>
	<?php }?>
  </div>
</div>
<div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;">
<input  type="button" value=" 取 消  " onclick='window.close();'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input  type="button" onclick='toEditStep3()' value=" 修 改  ">
</div>
<br><br><br>
</form>
</div>
<!-- 设置店铺类目的modal -->
<!-- 模态框（Modal） -->
<div class="modal fade" id="categorysetModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>