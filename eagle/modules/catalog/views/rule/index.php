<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/rule.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerCssFile($baseUrl."css/catalog/catalog.css");

$this->title = TranslateHelper::t('SKU解析规则');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>
.text-orange{color:orange;}
.text-muted{color:red;}
.text-muted{color:red;}
</style>
<div class="flex-row">
<!-- 左侧列表内容区域 -->
<?= $this->render('/product/_menu', ['class_html' => '']) ?>
<!-- 右侧内容区域 -->
<div class="content-wrapper" >
<form action="" method="post" id="skuruleform">
		<p class="help-block">请设置<strong>捆绑SKU</strong>组成规则及<strong>连接符号</strong>，当同步订单时将自动解析出<strong>SKU</strong>并保存成商品<br></p>
		<div class="form-group skugrouplabel">
		<div class=" moreinfo dis-none" style="display: block;">
		<div class="input-group" style="width:350px;">
			<div class="input-group-btn">
				<div class="btn-group">
					<button data-toggle="dropdown" class="btn btn-default" type="button">
						<span class="text" id="firstKeyTest">SKU</span><span class="caret ml5"></span>
					</button>
					<ul role="menu" class="dropdown-menu dropdown-menu-left copytext">
						<li><a onclick="setKeys2('firstKey','secondKey')" href="javascript:void(0);">SKU</a></li>
						<li><a onclick="setKeys2('secondKey','firstKey')" href="javascript:void(0);">数量</a></li>
					</ul>
					<input type="hidden" name="firstKey" id="firstKey" value="<?php echo $skurule['firstKey'];?>">
				</div>
			</div>
			<input type="text" placeholder="连接符1" id="quantityConnector" name="quantityConnector" class="form-control text-center" value="<?php echo $skurule['quantityConnector'];?>" style="height:33px;">
			<div class="input-group-btn">
				<div class="btn-group">
					<button id="secondKeyBtn" data-toggle="dropdown" class="btn btn-default" type="button" style="border-width:1px 0; border-radius:0;">
						<span class="text" id="secondKeyTest">数量</span><span class="caret ml5"></span>
					</button>
					<ul role="menu" class="dropdown-menu dropdown-menu-left copytext">
						<li><a id="secondKeySku" onclick="setKeys2('secondKey', 'firstKey')" href="javascript:void(0);">SKU</a></li>
						<li><a id="secondKeyQuantity" onclick="setKeys2('firstKey','secondKey')" href="javascript:void(0);">数量</a></li>
					</ul>
					<input type="hidden" name="secondKey" id="secondKey" value="<?php echo $skurule['secondKey'];?>">
				</div>
			</div>
			<input type="text" placeholder="连接符2" name="skuConnector" id="skuConnector" class="form-control text-center" value="<?php echo $skurule['skuConnector'];?>" style="height:33px;">
			<span class="input-group-addon">下一个sku</span>
		</div>
		<p class="help-block">
		例如
		<strong><span class="text-primary pl5 pr5">A00001</span></strong>和<strong>
		<span class="text-primary pl5 pr5">B00002</span></strong>两种
		<strong>SKU</strong>数量分别为 <strong><span class="text-orange pl5 pr5">1</span></strong> 
		个和 <strong><span class="text-orange pl5 pr5">3</span></strong> 个,使用以上规则所生成的<strong>捆绑SKU</strong>为：
			<span class="skugroupexample" id="example">
				<strong><span class="text-primary label-first">A00001</span></strong>
				<strong><span class="text-muted connector-first">*</span></strong>
				<strong><span class="text-orange label-last">1</span></strong>
				<strong><span class="text-success connector-last">+</span></strong>
				<strong><span class="text-primary label-first">B00002</span></strong>
				<strong><span class="text-muted connector-first">*</span></strong>
				<strong><span class="text-orange label-last">3</span></strong>
			</span>
		</p>
	</div>
	</div>
	<hr>
	<p class="help-block">请设置<strong>捆绑SKU</strong>组成的<strong>前后缀关键字</strong>，当同步订单时将自动<strong>替换这些捆绑SKU的前后缀为空</strong></p>
	<div>
		<div class="form-group" style="float:left;width:100px;vertical-align:middle;margin:0px;">
			<label for="Product_tag" class="control-label" style="float:left;width:20%;padding:6px 0px;">
			<a class="cursor_pointer" onclick="productList.list.addTagHtml(this)"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></a>
			</label>
			<input type="text" class="form-control" id="Product_tag" name="keyword[]" value="<?php echo isset($skurule['keyword'][0])?$skurule['keyword'][0]:'';?>" style="float:left;width:80%;"/>
		</div>
		<?php if (isset($skurule['keyword']) && !empty($skurule['keyword'])){
							$i = 0;
							foreach($skurule['keyword'] as $one){
								$i++;
								if ($i == 1) {
									continue;
								}
								echo "<div class=\"form-group\" style=\"float:left;width:100px;vertical-align:middle;margin:0px;\">".
								"<label for=\"Product_tag\" class=\"control-label\" style=\"float:left;width:20%;padding:6px 0px;\">".
								"<a  class=\"cursor_pointer\"  onclick=\"productList.list.delete_form_group(this)\"><span class=\"glyphicon glyphicon-remove-circle\"  class=\"text-danger\" aria-hidden=\"true\"></span></a>".
								"</label>".
								"<input type=\"text\" class=\"form-control\" name=\"keyword[]\" value=\"".$one."\" style=\"float:left;width:80%;\"/>".
								"</div>";	
							}	
						}?>
	</div>
	<div style="clear: both;">
	<p class="help-block">例如捆绑SKU<strong> “A00001*1+B00002*3-ebay”</strong>其中<strong>-ebay</strong>是前后缀关键字，当同步订单时将<strong>自动替换-ebay为空</strong>最终变成<strong>A00001*1+B00002*3</strong>再解析</p>
	</div>
	<div style="clear: both;">
	<input type="button" value=" 保 存 " class="btn btn-success" onclick="productList.list.saveSkuRule()">
	</div>
</form>
</div>
</div>