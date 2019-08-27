<?php

use yii\helpers\Html;
use eagle\modules\listing\models\EbayCrosssellingItem;
use common\helpers\Helper_Array;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ebaymuban_crossedit.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<style>
.row{
	font-size:12px;
}
</style>
<?php $money_list = array('美元'=>'$','欧元'=>'EUR','英镑'=>'£','加拿大'=>'C $','瑞士'=>'CHF','香港'=>'HK$','印度'=>'RS.','马来西亚'=>'RM','菲律宾'=>'PHP');?>
<div class=".container" style="width:98%;margin-left:1%;">
<form name="a" id="crossselling_form" action="" method="post">
	<?php if(!$ec->isNewRecord):?>
	<?php echo Html::hiddenInput('crosssellingid',$ec->crosssellingid)?>
	<?php $items=EbayCrosssellingItem::find()->where(['crosssellingid' =>$ec->crosssellingid])->orderBy('sort asc')->all();
	?>
	<?php endif;?> 
	<div class="panel panel-default form-group">
	  <div class="panel-body">
	  	<div class="row">
		  <div class="col-lg-12"><label>创建产品推荐范本(CrossSelling)</label></div>
		</div>
	    <hr/>
	    <div class="row">
	    	<div class="col-lg-10">
	    		<div class="row">
				  <div class="col-lg-3"><p class="text-right">范本名称</p></div>
				  <div class="col-lg-9">
				  <?php echo Html::textInput('title',$ec->title,array('size'=>40))?>
				  	<br>
				  </div>
				</div>
				<div class="row">
				  <div class="col-lg-3"><p class="text-right">范本对应eBay账号</p></div>
				  <div class="col-lg-9">
				  <?php echo Html::dropDownList('selleruserid',$ec->selleruserid,Helper_Array::toHashmap($selleruserids,'selleruserid','selleruserid'))?>	
					<br>
				  </div>
				</div>
				<div class="row">
				  <div class="col-lg-3"><p class="text-right">交叉销售内容</p></div>
				  <div class="col-lg-9">
					
					<div id="items">
					<?php if (isset($items)&&count($items)>0):foreach ($items as $item):?>
					<div class="row cross_info" >
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">图片地址</p></div>
						  <div class="col-lg-7">
						  	<?php echo Html::textInput('crossItem[picture][]',$item->data['picture'],array('onchange'=>"$(this.parentNode).next().find('img').attr('src',this.value).attr('width','60px')",'size'=>50))?>
						  </div>
						  <div class="col-lg-2">
						  	<img alt="" src="<?php echo @$item->data['picture'];?>" width="50px">
						  </div>
						  <div class="col-lg-1">
						  	<?php echo Html::button('删除',array('onclick'=>'crossItem_remove(this)'))?>
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">链接地址</p></div>
						  <div class="col-lg-10">
						  	<?php echo Html::textInput('crossItem[url][]',@$item->data['url'],array('size'=>50))?>
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">商品标题</p></div>
						  <div class="col-lg-10">
						  	<?php echo Html::textInput('crossItem[title][]',@$item->data['title'],array('size'=>50))?>
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">商品价格</p></div>
						  <div class="col-lg-10">
						  <select name="crossItem[icon][]">
						  	<?php foreach($money_list as $k => $v): ?>
						  	<option value="<?php echo $v ?>" <?php if(@$item->data['icon']==$v)echo 'selected' ?>><?php echo $k.' '.$v ?></option>
						  <?php endforeach; ?>
						  </select>
						  	<input type="text" name="crossItem[price][]" value="<?php echo @$item->data['price'] ?>" style="width:80px" >
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">排序</p></div>
						  <div class="col-lg-10">
						  	<input type="number" name="crossItem[sort][]" value="<?php echo @$item->sort ?>" style="width:50px">
						  </div>
						</div>
						<br><hr>
					</div>
					<?php endforeach;endif;?>
					<?php if (!isset($items)||count($items)==0):?>
					<div class="row cross_info" >
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">图片地址</p></div>
						  <div class="col-lg-7">
						  	<?php echo Html::textInput('crossItem[picture][]','',array('onchange'=>"$(this.parentNode).next().find('img').attr('src',this.value).attr('width','60px')",'size'=>50))?>
						  </div>
						  <div class="col-lg-2">
						  	<img alt="" src="" width="50px">
						  </div>
						  <div class="col-lg-1">
						  	<?php echo Html::button('删除',array('onclick'=>'crossItem_remove(this)'))?>
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">链接地址</p></div>
						  <div class="col-lg-10">
						  	<?php echo Html::textInput('crossItem[url][]','',array('size'=>50))?>
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">商品标题</p></div>
						  <div class="col-lg-10">
						  	<?php echo Html::textInput('crossItem[title][]','',array('size'=>50))?>
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">商品价格</p></div>
						  <div class="col-lg-10">
						  <select name="crossItem[icon][]">
						  	<?php foreach($money_list as $k => $v): ?>
						  	<option value="<?php echo $v ?>" ><?php echo $k.' '.$v ?></option>
						  	<?php endforeach; ?>
						  </select>
						  <input type="text" name="crossItem[price][]" style="width:80px" >
						  </div>
						</div>
						<div class="row">
						  <div class="col-lg-2"><p class="text-right">排序</p></div>
						  <div class="col-lg-10">
						  	<input type="number" name="crossItem[sort][]" style="width:50px">
						  </div>
						</div>
						<br><hr>
					</div>
					<?php endif;?>
					</div>	
					<?php echo Html::button('添加交叉销售信息',array('onclick'=>'crossItem_Add()'))?><br><br>
				  </div>
				</div>
	    	</div>
	    </div>
		<div class="row">
		  <div class="col-lg-1"></div>
		  <div class="col-lg-11">
		  <?php echo Html::button('取消',array('onclick'=>"window.close()"))?>
		  <?php echo Html::button('确定',array('type'=>'submit','id'=>'subm'))?>
		
		  	<br>
		  </div>
		</div>
	  </div>
	</div>
</form>
</div>
<!-- 用来生成添加交叉销售信息组的模型 -->
<div id="crossItemSample" class="row cross_info"  style="display:none;">
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">图片地址</p></div>
	  <div class="col-lg-7">
	  	<?php echo Html::textInput('crossItem[picture][]','',array('onchange'=>"$(this.parentNode).next().find('img').attr('src',this.value).attr('width','60px')",'size'=>50))?>
	  </div>
	  <div class="col-lg-2">
	  	<img alt="" src="" width="50px">
	  </div>
	  <div class="col-lg-1">
	  	<?php echo Html::button('删除',array('onclick'=>'crossItem_remove(this)'))?>
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">链接地址</p></div>
	  <div class="col-lg-10">
	  	<?php echo Html::textInput('crossItem[url][]','',array('size'=>50))?>
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">商品标题</p></div>
	  <div class="col-lg-10">
	  	<?php echo Html::textInput('crossItem[title][]','',array('size'=>50))?>
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">商品价格</p></div>
	  <div class="col-lg-10">
	  <select name="crossItem[icon][]">
	  	<?php foreach($money_list as $k => $v): ?>
	  	<option value="<?php echo $v ?>" ><?php echo $k.' '.$v ?></option>
	  	<?php endforeach; ?>
	  </select>
	  <input type="text" name="crossItem[price][]" style="width:80px" >
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">排序</p></div>
	  <div class="col-lg-10">
	  	<input type="number" name="crossItem[sort][]" style="width:50px">
	  </div>
	</div>
	<br><hr>
</div>
<script>
</script>