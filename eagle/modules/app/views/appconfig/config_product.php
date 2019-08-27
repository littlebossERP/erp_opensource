<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ConfigHelper;
//$this->registerJsFile(\Yii::getAlias('@web')."/js/project/app/appView.js", ['depends' => ['yii\web\JqueryAsset']]);
//$imageBasePath=Yii::getAlias('@web')."/images";




$nameMaxLen=ConfigHelper::getConfig("product/name_maxlen");
if ($nameMaxLen===null) $nameMaxLen="";

$nameMaxNum=ConfigHelper::getConfig("product/name_maxnum");
if ($nameMaxNum===null) $nameMaxNum="";

?>
<style>
.app-configset-modal .modal-dialog{
	width: 600px;
}
/* modal限高设置 */
.app-configset-modal .modal-body {
max-height: 490px;
min-height: 330px;
overflow-y: auto;
}
</style>


<form id="app-configset-form" class="form-horizontal">
  <div class="form-group">
    <label for="inputEmail3" class="col-sm-3 control-label"><?=TranslateHelper::t('商品名称长度') ?></label>
    <div class="col-sm-6">
      <input name="product/name_maxlen" type="email" class="form-control" id="inputEmail3"  value="<?=$nameMaxLen ?>">
    </div>
  </div>
  <div class="form-group">
    <label for="inputEmail3" class="col-sm-3 control-label"><?=TranslateHelper::t('最多商品数') ?></label>
    <div class="col-sm-6">
      <input name="product/name_maxnum" type="email" class="form-control" id="inputEmail3" value="<?=$nameMaxNum ?>">
    </div>
  </div>
</form>



<script>
$(function() {
	$(".appconfig-save-btn").click(function(){
		   $.showLoading();
		   $.post(global.baseUrl+'app/appconfig/save',$("#app-configset-form").serialize(),
				   	 function (data){			
			             $.hideLoading();		   		             
					   	 var retinfo = eval('(' + data + ')');
						 if (retinfo["code"]=="fail")  {
							  bootbox.alert(retinfo["message"], function() {});
							  return false;
						 }								 
						 
						 bootbox.alert(Translator.t("app参数设置成功"), function() {
							//window.location.reload();
						 });
				      }
			    );			
	});
	
});
</script>

