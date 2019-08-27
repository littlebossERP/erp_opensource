<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysCarrierCustom;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use yii\helpers\Url;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/carriercustom.js", ['depends' => ['yii\web\JqueryAsset']]);
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->title = TranslateHelper::t('新增自定义运输服务');
//$this->params['breadcrumbs'][] = $this->title;
?>
<style>

table{
	font-size:14px;
	
}
.text-right{
	font-weight:bold;
}
.text-left{
	font-weight:bold;
}
.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
</style>
 <script>
    var loadAddressUrl = '<?= \Yii::$app->urlManager->createUrl("carrier/shippingservice/loadaddress") ?>';
 </script>
<div class="tracking-index col2-layout">
<?= $this->render('//layouts/menu_left_carrier') ?>
<div class="content-wrapper" >
<?php if (!empty($errors)){?>
<div class="alert alert-danger" role="alert">
<?php foreach ($errors as $error){?>
<?=$error?><br>
<?php }?>
</div>
<?php }?>
<form action="" method="post" id="create_form">
<?=Html::hiddenInput('return_url',$return_url);?>
<input type="hidden" name="id" value="<?= $id ?>">
<table class="table table-condensed">
	    <tr>
            <td width='120px;' style="vertical-align:middle;"><b><?=TranslateHelper::t('物流商') ?></b>
                <?=Html::dropDownList('carrier_code',$service->carrier_code,$carrier,['prompt'=>'请选择物流商','id'=>'carrier_code','class'=>'eagle-form-control'])?>
            <input type="submit" id="savebutton" class="btn btn-primary btn-md" value="保存"/></td>
        </tr>
</table>
<ul id="myTab" class="nav nav-tabs" style="height: 41px;">
<?php $arr = array('info'=>'基本信息','address'=>'地址信息','print'=>'自定义打印模板设置')?>
<?php foreach ($arr as $key=>$value){?>
   <li class="<?php echo $key;?>"><a href="<?php echo '#'.$key;?>" data-toggle="tab"><?php echo $value?></a></li>
<?php }?>
</ul>
<div id="myTabContent" class="tab-content">
<div class='info tab-pane fade' id="info"  style="padding-top:8px;">
<!-- table -->
    <table class="table table-condensed">
	    <tr>
            <td width='120px;' class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('运输服务名') ?></td>
            <td>
                <input type="text" size=80 class='eagle-form-control' name="service_name" value="<?=@$service['service_name'] ?>">
            </td>
        </tr>
        <tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('查询网址') ?></td>
            <td>
                <input type="url" size=80 class='eagle-form-control' name="web" value="<?= isset($service['web'])?$service['web']:'http://www.17track.net'; ?>">
            </td>
        </tr>
        <tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('标记发货') ?></td>
            <td style="vertical-align:middle;">
	            <table>
		            <tr>
		            <td>eBay</td><td><?=Html::input('text','service_code[ebay]',@$service['service_code']['ebay'],['placeholder'=>'请填写标记发货物流服务名','style'=>'width:300px;','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Amazon</td><td><?=Html::dropDownList('service_code[amazon]',@$service['service_code']['amazon'],$amazon,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Aliexpress</td><td><?=Html::dropDownList('service_code[aliexpress]',@$service['service_code']['aliexpress'],$ali,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Wish</td><td><?=Html::dropDownList('service_code[wish]',@$service['service_code']['wish'],$wish,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>DHGate</td><td><?=Html::dropDownList('service_code[dhgate]',@$service['service_code']['dhgate'],$dhgate,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Cdiscount</td><td><?=Html::input('text','service_code[cdiscount]',@$service['service_code']['cdiscount'],['placeholder'=>'请填写标记发货物流服务名','style'=>'width:300px;','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Lazada</td><td><?=Html::dropDownList('service_code[lazada]',@$service['service_code']['lazada'],$lazada,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Linio</td><td><?=Html::dropDownList('service_code[linio]',@$service['service_code']['linio'],$linio,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Ensogo</td><td><?=Html::dropDownList('service_code[ensogo]',@$service['service_code']['ensogo'],$ensogo,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
	            </table>
            </td>
        </tr>
        <tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('状态') ?></td>
            <td>
                <input type="radio" value="1" id="used2" name="is_used" checked <?= @$service['is_used']==1?'checked':'' ?> autocomplete="off"><label for="used2"><?=TranslateHelper::t('启用') ?></label>
 		        <input type="radio" value="0" id="used1" name="is_used" <?= isset($service['is_used'])&&$service['is_used']==0?'checked':'' ?> autocomplete="off"><label for="used1"><?=TranslateHelper::t('停用') ?></label>
            </td>
	    </tr>
    </table>
</div>	
<div class='address tab-pane fade' id="address"  style="padding-top:8px;">

</div>
<div class='print tab-pane fade' id="print"  style="padding-top:8px;">
<a href="<?= Url::to(['/carrier/carriercustomtemplate/index'])?>"  class="btn btn-default" target="_blank">
	<font><?= TranslateHelper::t('自定义打印模版')?></font>
</a><br>
<?php $templates =  CarrierApiHelper::getCustomTemplate();?>
<?php 
foreach ($templates as $type=>$template){
echo $type.Html::dropDownList('custom_template_print['.$type.']',isset($service['custom_template_print'][$type])?$service['custom_template_print'][$type]:'',$template,['prompt'=>'请选择'.$type,'class'=>'eagle-form-control','style'=>'width:150px;']);
}
?>
</div>
</div>
</form>
</div>
</div>