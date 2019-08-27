<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<form enctype="multipart/form-data" method="POST">
<div style="margin:0 0 10px 0">
	<?php
		echo Html::hiddenInput('platform',$_REQUEST['platform']);
	
		$tmpColLabelName= $_REQUEST['platform']."店铺";
		$tmpColName="selleruserid";
		$tmpRequire= "必填";
		$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
		echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['style'=>'margin:0 5px 0 5px']);
		echo Html::dropDownList($tmpColName ,@$defaultRT[$tmpColName],$seller_array,['class'=>'iv-input','style'=>'width:200px;']); //($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
		
		if (!empty($sites)):
			$tmpColLabelName= "站点";
			$tmpColName="order_source_site_id";
			$tmpRequire= "必填";
			$tail = ($tmpRequire == "必填")?'<span style="color:red">*</span>':'';
			echo Html::label(TranslateHelper::t($tmpColLabelName).$tail,null,['style'=>'margin:0 5px 0 5px;']);
			echo Html::dropDownList($tmpColName ,@$defaultRT[$tmpColName],$sites,['class'=>'iv-input','style'=>'width:120px;']); //($tmpColName,null,['class'=>'form-control','placeholder'=>TranslateHelper::t($tmpRequire.'，'.$tmpColLabelName)]);
		endif;?>
</div>
	<input type="file" name="import_orders" id="import_orders"><br>
	<a href="/template/手工订单 导入 模板.xls" style="margin-right: 20px;">模板下载</a>
 </form>
 <br>
		<span class="label label-warning">功能说明！</span>
		<br>
		<br>
		<ol style="list-style: decimal;margin-left: 15px;line-height: 20px;">
<li>模版中带*号的红色字体，为必填项；</li>
<li>订单号仅支持数字、字母和横杠；</li>
<li>订单金额、单价和数量必须大于0；</li>
<li>国家请填写国家二字码，如：RU、CA、US等，请参考模版的第三个excel sheet</li>
<li>一订单多SKU的请分多行填写，除SKU、数量和单价以外，其它信息与第一个SKU保持一致；</li>
<li>SKU如果使用小老板商品管理的SKU，请按照商品管理里的SKU填写，未使用小老板库存功能的，可以填写任意值；</li>
<li>运输至没有“省/州”的地区，“省/州”字段请用“城市”字段代替</li>
<li>单次导入不能超过500行数据 </li>
<li>没有填写币种时，默认为USD </li>
		</ol>

 