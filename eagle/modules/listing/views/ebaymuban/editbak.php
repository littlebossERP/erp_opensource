<?php 
use yii\helpers\Html;
use eagle\models\EbayCategory;
use yii\helpers\Url;
use common\helpers\Helper_Util;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\TranslateHelper;

//$this->registerJsFile(\Yii::getAlias('@web')."/js/translate.js",[\yii\web\View::POS_HEAD]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/translate.js", ['position' => \yii\web\View::POS_BEGIN]);
$this->registerJs('Translator = new Translate('. json_encode(TranslateHelper::getJsDictionary()).');', \yii\web\View::POS_BEGIN);
$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ckeditor/ckeditor.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
?>
<style>
body{
	font-size:10px;
}
div{
	width:expression(document.body.clientWidth + 'px');
}
.bbar{
	position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    display: block;
    height: 65px;
    background:white;
    line-height: 17px;
    overflow: hidden;
}
.left_pannel{
	height:auto;
	float:left;
	background:url(/images/ebay/listing/profile_menu_bg1.png) -23px 0 repeat-y;
	position:fixed;
}
.left_pannel_first{
	float:left;
	background:url(/images/ebay/listing/profile_menu_bg.png) 0 -6px no-repeat;
	margin-bottom:55px;
	height:12px;
	padding-left:12px;
}
.left_pannel_last{
	float:left;
	background:url(/images/ebay/listing/profile_menu_bg.png) 0 -6px no-repeat;
	margin-top:5px;
	height:12px;
	padding-left:12px;
}
.left_pannel>p{
	margin:50px 0;
	background:url(/images/ebay/listing/profile_menu_bg.png) 0 -41px no-repeat;
	padding-left:16px;
}
.left_pannel>p>a{
	color:#333;
	font-weight:bold;
	cursor:pointer;
}
.left_pannel p:hover a{
	color:blue;
	font-weight:bold;
	cursor:pointer;
}
.left_pannel p a:hover{
	color:rgb(165,202,246);
}
.profile{
	float:right;
}
.profilelist{
	float:left;
	border:4px solid #ddd;
}
.profile span{
	font-weight:bold;
	color:rgb(82,137,228);
	border:1px solid rgb(82,137,228);
	margin-right:4px;
	padding:3px;
	cursor:pointer;
	float:left;
}
.profile .profile_del{
	margin-right:20px;
}
.save_name_div{
	margin:0 0;
	display:none;
	float:left;
}
.save_name_div .save_name{
	border:3px solid #ddd;
}
.save_name_div .profile_save_btn{
	float:right;
}
#changeexclude{
	font-weight:bold;
	color:rgb(82,137,228);
}
</style>
<script>
function isload(){
//	$.showLoading();
//	setTimeout($.hideLoading(),3000);
}
</script>
<br/>
<div class=".container" style="width:98%;margin-left:1%;">
<!-------------------------- 商品信息begin --------------------------------------------->
<!-------------------------- 商品信息 end--------------------------------------------->
<form action="" method="post" id="a" name="a">
<div class="col-lg-11">
<div class="form-group">
<?php if (strlen(@$data['mubanid'])):?>
<?=Html::hiddenInput('mubanid',@$data['mubanid'])?>
<?php endif;?>
<!------------------------------ 平台与细节begin ------------------------------------->
<div class="panel panel-default" id="siteandspe">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>平台与细节</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">eBay站点</p></div>
	  <div class="col-lg-10"><?=Html::dropDownList('siteid',@$data['siteid'],$sitearr,['onchange'=>'isload();','id'=>'siteid'])?></div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登类型</p></div>
	  <div class="col-lg-10"><?=Html::dropDownList('listingtype',@$data['listingtype'],$listingtypearr,['onchange'=>'isload();','id'=>'listingtype'])?></div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">ProductID</p></div>
	  <div class="col-lg-10">
	  	<table>
		<tr><th>EPID</th><td><input name="epid" size="15" value="<?php echo $data['epid']?>"></td></tr>
		<tr><th>ISBN</th><td><input name="isbn" size="15" value="<?php echo $data['isbn']?>"></td></tr>
		<tr><th>UPC</th><td><input name="upc" size="15" value="<?php echo $data['upc']?>"></td></tr>
		<tr><th>EAN</th><td><input name="ean" size="15" value="<?php echo $data['ean']?>"></td></tr>
		</table>
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登分类一</p></div>
	  <div class="col-lg-10">
	  	<label>
		<?php if(strlen($data['primarycategory'])){
			$ec=EbayCategory::findBySql('select * from ebay_category where siteid='.$data['siteid'].' AND categoryid='.$data['primarycategory'].' and leaf=1')->one();
			if (empty($ec)){
				echo "<span style='color:red;font-size:10px;'>无法查找该类目,请重新选择</font>";
			}else{
				echo EbayCategory::getPath($ec,$ec->name,$data['siteid']);
			}
		}
		?>
		</label><br>
		<input class="category" id="primarycategory" name="primarycategory" size="25" value="<?=$data['primarycategory']?>">
		<input type="button" value="选择分类一" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategory','siteid'=>$data['siteid'],'elementid'=>'primarycategory'])?>')">
		<?=Html::button('搜索',['onclick'=>'searchcategory("primary")'])?>
	  </div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登分类二</p></div>
	  <div class="col-lg-10">
	  	<label>
		<?php if(strlen($data['secondarycategory'])){
			$ec=EbayCategory::findBySql('select * from ebay_category where siteid='.$data['siteid'].' AND categoryid='.$data['secondarycategory'].' and leaf=1')->one();
			if (empty($ec)){
				echo "<span style='color:red;font-size:10px;'>无法查找该类目,请重新选择</font>";
			}else{
				echo EbayCategory::getPath($ec,$ec->name,$data['siteid']);
			}
		}
		?>
		</label><br>
		<input class="category" id="secondarycategory" name="secondarycategory" size="25" value="<?php echo $data['secondarycategory']?>">
		<input type="button" value="选择分类二" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategory','siteid'=>$data['siteid'],'elementid'=>'secondarycategory'])?>')">
		<?=Html::button('搜索',['onclick'=>'searchcategory("second")'])?>
	  </div>
	</div>
	<?php echo $this->render('_condition',array('condition'=>$condition,'val'=>$data))?>
	<?php echo $this->render('_specific',array('specifics'=>$specifics,'val'=>$data['specific']))?>
	<hr/>
	<?php echo $this->render('_variation',array('data'=>$data))?>
  </div>
</div>
<!-------------------------------平台与细节end ------------------------------------->

<!-------------------------------标题与价格begin ------------------------------------->
<div class="panel panel-default" id="titleandprice">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>标题与价格</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登主标题</p></div>
	  <div class="col-lg-10">
	  <input name="itemtitle" size="80" value="<?php echo $data['itemtitle']?>" id="itemtitle" onkeydown="inputbox_left('itemtitle',80)" onkeypress="inputbox_left('itemtitle',80)" onkeyup="inputbox_left('itemtitle',80)">
		<span id='length_itemtitle' style="font-weight:bold">80  </span>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">刊登副标题</p></div>
	  <div class="col-lg-10"><input name="itemtitle2" size="80" value="<?php echo $data['itemtitle2']?>"></div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">Customer Label</p></div>
	  <div class="col-lg-10"><?php echo Html::textInput('sku',$data['sku'],array('size'=>30))?>使用多属性时,该值将无效</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">数量</p></div>
	  <div class="col-lg-10"><?php echo Html::textInput('quantity',$data['quantity'],array('size'=>10))?>使用多属性时,该值将无效</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">LotSize</p></div>
	  <div class="col-lg-10"><?php echo Html::textInput('lotsize',$data['lotsize'],array('size'=>3))?></div>
	</div>
	<?php echo $this->render('_price',array('data'=>$data))?>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">税率</p></div>
	  <div class="col-lg-10"><?php echo Html::textInput('vatpercent',$data['vatpercent'],array('size'=>8))?>%</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">私人刊登</p></div>
	  <div class="col-lg-10"><?php echo Html::checkBox('privatelisting',$data['privatelisting'])?>是否设置为私人刊登(privateListing)</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">永久在线</p></div>
	  <div class="col-lg-10"><?php echo Html::checkBox('outofstockcontrol',$data['outofstockcontrol'],array('uncheckValue'=>0))?>卖光库存减为0</div>
	</div>
  </div>
</div>

<!-------------------------------标题与价格end ------------------------------------->
<!-------------------------------图片与描述begin ------------------------------------->
<div class="panel panel-default" id="picanddesc">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>图片与描述</label></div>
	  	<div class="col-lg-10">
		</div>
	</div>
    <hr/>
    <?php echo $this->render('_imgurl',array('data'=>$data));?>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">描述</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::textArea('itemdescription',$data['itemdescription'],array('rows'=>20,'cols'=>100))?>
		</div>
	</div>
	<hr>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">风格模板</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::dropDownList('template',$data['template'],$mytemplates,array('prompt'=>''))?>
	  	</div>
	</div>
	<hr>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">销售信息范本</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::dropDownList('basicinfo',$data['basicinfo'],$basicinfos,array('prompt'=>''))?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">交叉销售</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::dropDownList('crossselling',$data['crossselling'],$crosssellings,array('prompt'=>''))?>
		</div>
	</div>
  </div>
</div>
<!-------------------------------图片与描述end ------------------------------------->

<!-------------------------------物流设置begin ------------------------------------->
<div class="panel panel-default" id="shippingset">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>物流设置</label></div>
	  	<div class="col-lg-10">
		  	<div class="profile">
		  		<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['shippingset'], 'id','savename'),['id'=>'shippingset_profile','class'=>'profilelist','prompt'=>''])?>
		  		<span class="profile_load">读取</span>
		  		<span class="profile_del">删除</span>
		  		<div class="save_name_div">
		  			<?=Html::textInput('save_name','',['class'=>'save_name'])?>
		  			<span class="profile_save_btn">确定</span>
		  		</div>
		  		<span class="profile_save">保存</span>
		  	</div>
		</div>
	</div>
    <hr/>
    <?php echo $this->render('_shipping',array('data'=>$data,'salestaxstate'=>@$salestaxstate,'shippingserviceall'=>$shippingserviceall))?>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">包裹处理时间</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::dropDownList('dispatchtime',$data['dispatchtime'], $dispatchtimemax)?>
		</div>
	</div>
  </div>
</div>
<!-------------------------------物流设置end ------------------------------------->
<!-------------------------------收款与退货begin ------------------------------------->
<div class="panel panel-default" id="returnpolicy">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>收款与退货</label></div>
	  	<div class="col-lg-10">
	  		<div class="profile">
		  		<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['returnpolicy'], 'id','savename'),['id'=>'returnpolicy_profile','class'=>'profilelist','prompt'=>''])?>
		  		<span class="profile_load">读取</span>
		  		<span class="profile_del">删除</span>
		  		<div class="save_name_div">
		  			<?=Html::textInput('save_name','',['class'=>'save_name'])?>
		  			<span class="profile_save_btn">确定</span>
		  		</div>
		  		<span class="profile_save">保存</span>
		  	</div>
		</div>
	</div>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">收款方式</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::checkBoxList('paymentmethods',@$data['paymentmethods'], $paymentoption)?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">立即付款</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::checkBox('autopay',@$data['autopay'],array('uncheckValue'=>0))?>是否要求买家立即付款
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">付款说明</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::textArea('shippingdetails[PaymentInstructions]',@$data['shippingdetails']['PaymentInstructions'],array('rows'=>5,'cols'=>60))?>
		</div>
	</div>
	<hr/>
	<?php echo $this->render('_returnpolicy',array('data'=>$data,'return_policy'=>$returnpolicy))?>
	<hr/>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">商品所在地</p></div>
	  	<div class="col-lg-10">
	  	<table>
		<tr>
		<th>国家</th>
		<td>
		<?php echo Html::dropDownList('country',@$data['country'],$locationarr)?>
		</td>
		</tr>
		<tr>
		<th>地区</th>
		<td>
		<?php echo Html::textInput('location',@$data['location'])?>
		</td>
		</tr>
		<tr>
		<th>邮编</th>
		<td>
		<?php echo Html::textInput('postalcode',@$data['postalcode'])?>
		</td>
		</tr>
		</table>
		</div>
	</div>
  </div>
</div>
<!-------------------------------收款与退货end ------------------------------------->

<!-------------------------------买家要求begin ------------------------------------->
<div class="panel panel-default" id="buyerrequire">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>买家要求</label></div>
	  	<div class="col-lg-10">
	  		<div class="profile">
		  		<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['buyerrequire'], 'id','savename'),['id'=>'buyerrequire_profile','class'=>'profilelist','prompt'=>''])?>
		  		<span class="profile_load">读取</span>
		  		<span class="profile_del">删除</span>
		  		<div class="save_name_div">
		  			<?=Html::textInput('save_name','',['class'=>'save_name'])?>
		  			<span class="profile_save_btn">确定</span>
		  		</div>
		  		<span class="profile_save">保存</span>
		  	</div>
		</div>
	</div>
    <hr/>
    <?php echo $this->render('_buyerrequirement',array('data'=>$data,'buyerrequirementenable'=>$buyerrequirementenable))?>
  </div>
</div>

<!-------------------------------买家要求end ------------------------------------->
<!-------------------------------增值设置begin ------------------------------------->
<div class="panel panel-default" id="plusmodule">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>增值设置</label></div>
	  	<div class="col-lg-10">
	  		<div class="profile">
		  		<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['plusmodule'], 'id','savename'),['id'=>'plusmodule_profile','class'=>'profilelist','prompt'=>''])?>
		  		<span class="profile_load">读取</span>
		  		<span class="profile_del">删除</span>
		  		<div class="save_name_div">
		  			<?=Html::textInput('save_name','',['class'=>'save_name'])?>
		  			<span class="profile_save_btn">确定</span>
		  		</div>
		  		<span class="profile_save">保存</span>
		  	</div>
		</div>
	</div>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">图片显示方式</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::radioList('gallery',@$data['gallery'],array('0'=>'不使用','Featured'=>'Featured($)','Gallery'=>'Gallery($)','Plus'=>'Plus($)'))?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">样式</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::checkBoxList('listingenhancement',@$data['listingenhancement'],$feature_array)?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">计数器</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::radioList('hitcounter',@$data['hitcounter'],array('NoHitCounter'=>'不用计数器','BasicStyle'=>'BasicStyle','RetroStyle'=>'RetroStyle'))?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">国际站点</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::checkBox('crossbordertrade',@$data['crossbordertrade'],array('uncheckValue'=>0));?>
		<label for="crossbordertrade">
		<?php echo in_array($data['siteid'],array(0,2))?'ebay.co.uk':'ebay.com and ebay.ca'?>
		</label>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">备注</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::textInput('desc',$data['desc'])?>
		</div>
	</div>
  </div>
</div>
<!-------------------------------增值设置end ------------------------------------->
<!-------------------------------账号begin ------------------------------------->
<div class="panel panel-default" id="account">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>账号</label></div>
	  	<div class="col-lg-10">
	  		<div class="profile">
		  		<?=Html::dropDownList('profile','',Helper_Array::toHashmap($profile['account'], 'id','savename'),['id'=>'account_profile','class'=>'profilelist','prompt'=>''])?>
		  		<span class="profile_load">读取</span>
		  		<span class="profile_del">删除</span>
		  		<div class="save_name_div">
		  			<?=Html::textInput('save_name','',['class'=>'save_name'])?>
		  			<span class="profile_save_btn">确定</span>
		  		</div>
		  		<span class="profile_save">保存</span>
		  	</div>
		</div>
	</div>
    <hr/>
    <div class="row">
	  <div class="col-lg-2"><p class="text-right">eBay账号</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::dropDownList('selleruserid',@$data['selleruserid'],$ebayselleruserid,['id'=>'selleruserid'])?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">Paypal账号</p></div>
	  	<div class="col-lg-10">
	  	<?php echo Html::textInput('paypal',@$data['paypal'])?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">店铺类目一</p></div>
	  	<div class="col-lg-10">
		<?php echo Html::textInput('storecategoryid',@$data['storecategoryid'],array('id'=>"storecategoryid"))?>
		<?=Html::button('选择',['onclick'=>'doset("storecategoryid")'])?>
		</div>
	</div>
	<div class="row">
	  <div class="col-lg-2"><p class="text-right">店铺类目二</p></div>
	  	<div class="col-lg-10">
		<?php echo Html::textInput('storecategory2id',@$data['storecategory2id'],array('id'=>"storecategory2id"))?>
		<?=Html::button('选择',['onclick'=>'doset("storecategory2id")'])?>
		</div>
	</div>
  </div>
</div>
<!-------------------------------账号设置end ------------------------------------->
<div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;">
<?php echo Html::hiddenInput('act','',['id'=>'act'])?>
<?php echo Html::button('预览效果',array('onclick'=>'preview()','class'=>'donext'))?>&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo Html::button('保存刊登范本',array('onclick'=>'doaction("save")','class'=>'donext'))?>&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo Html::button('检测刊登范本/刊登费',array('onclick'=>'doaction("verify")','class'=>'donext'))?>&nbsp;&nbsp;&nbsp;&nbsp;
<?php if (strlen(@$data['mubanid'])):?>
<?php echo Html::button('立即刊登',array('onclick'=>'doaction("additem")','class'=>'donext'))?>&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo Html::button('另存为新刊登范本',array('onclick'=>'doaction("savenew")','class'=>'donext'))?>&nbsp;&nbsp;&nbsp;&nbsp;
<?php endif;?>
<?php echo Html::button('重复刊登检测',array('onclick'=>'checkitem()','class'=>'donext'))?>
<?=Html::submitButton('',['style'=>'display:none;'])?>
<?php echo Html::hiddenInput('uuid',Helper_Util::getLongUuid())?>
</div>
<br><br><br>
</div>
</div>
</form>
<div class="col-lg-1">
<!-- 快捷导航 -->
	<div class="left_pannel" id="floatnav">
		<div class="left_pannel_first"></div>
		<p onclick="goto('siteandspe')"><a>平台与细节</a></p>
		<p onclick="goto('titleandprice')"><a>标题与价格</a></p>
		<p onclick="goto('picanddesc')"><a>图片与描述</a></p>
		<p onclick="goto('shippingset')"><a>物流设置</a></p>
		<p onclick="goto('returnpolicy')"><a>收货与退款</a></p>
		<p onclick="goto('buyerrequire')"><a>买家要求</a></p>
		<p onclick="goto('plusmodule')"><a>增值设置</a></p>
		<p onclick="goto('account')"><a>账号</a></p>
		<div class="left_pannel_last"></div>
	</div>
</div>
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

<!-- 搜索刊登类目的modal -->
<div class="modal fade" id="searchcategoryModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog" style="width: 800px;">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>