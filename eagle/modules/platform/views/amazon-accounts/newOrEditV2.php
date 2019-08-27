<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/amazonAccountsNewOrEdit.js?=".time());
$this->registerJs('platform.amazonAccountsNeworedit.setting.mode="'.$mode.'";', \yii\web\View::POS_READY);
//mode--new,view,edit
if ($mode <> "new"){
	$this->registerJs('platform.amazonAccountsNeworedit.setting.amazonUser='.json_encode($amazonUser).';', \yii\web\View::POS_READY);
	$this->registerJs('platform.amazonAccountsNeworedit.setting.chosenCountryList='.json_encode($chosenCountryList).';', \yii\web\View::POS_READY);
}

$mp_arry=[
            "US"=>["US"=>"美国","CA"=>"加拿大","MX"=>"墨西哥"],
            "CA"=>["US"=>"美国","CA"=>"加拿大","MX"=>"墨西哥"],
            "MX"=>["US"=>"美国","CA"=>"加拿大","MX"=>"墨西哥"],
            "UK"=>["UK"=>"英国","DE"=>"德国","FR"=>"法国","IT"=>"意大利","ES"=>"西班牙","IN"=>"印度"],
            "DE"=>["UK"=>"英国","DE"=>"德国","FR"=>"法国","IT"=>"意大利","ES"=>"西班牙","IN"=>"印度"],
            "FR"=>["UK"=>"英国","DE"=>"德国","FR"=>"法国","IT"=>"意大利","ES"=>"西班牙","IN"=>"印度"],
            "IT"=>["UK"=>"英国","DE"=>"德国","FR"=>"法国","IT"=>"意大利","ES"=>"西班牙","IN"=>"印度"],
            "ES"=>["UK"=>"英国","DE"=>"德国","FR"=>"法国","IT"=>"意大利","ES"=>"西班牙","IN"=>"印度"],
            "IN"=>["UK"=>"英国","DE"=>"德国","FR"=>"法国","IT"=>"意大利","ES"=>"西班牙","IN"=>"印度"],
            "JP"=>["JP"=>"日本"],
            "CN"=>["CN"=>"中国"],
            "AU"=>["AU"=>"澳大利亚"],
];
$mp_json=json_encode($mp_arry);
$this->registerJs("platform.amazonAccountsNeworedit.setting.mplist=".$mp_json.";", \yii\web\View::POS_READY);
$this->registerJs('platform.amazonAccountsNeworedit.initWidget();', \yii\web\View::POS_READY);
?>
<form class="form-horizontal" id="platform-amazonAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/amazonAccounts/save" method="post">
  <?php if ($mode<>"new"): ?>
    <input name="amazon_uid" type=hidden value="<?php echo $amazonUser['amazon_uid']; ?>"/>
  <?php endif ?>
  <!-- store name -->
  <div class="form-group">
    <label for="inputStoreName" class="col-sm-4 control-label">
     <span style="color:red;margin-left:3px">*</span>
     <?php echo TranslateHelper::t('店铺名称(自定义)');?>:
    </label>
    <div class="col-sm-8">
      <?php if ($mode<>"new"): ?>
        <input class="form-control" type="text" id="inputStoreName" placeholder="店铺名称(自定义)" name="store_name" value="<?=$amazonUser['store_name'] ?>" style="display:inline;" <?=($mode=="view")?"disabled":"";?>>
      <?php else: ?>
        <input class="form-control" type="text" id="inputStoreName" placeholder="店铺名称(自定义)" name="store_name" value="" style="display:inline;"/>
      <?php endif ?>
    </div>
  </div>
  <!-- merchant id -->
  <div class="form-group">
    <label for="inputSellerid" class="col-sm-4 control-label">
     <span style="color:red;margin-left:3px">*</span>
     <?php echo TranslateHelper::t('MERCHANT ID(Seller Id)');?>:
    </label>
    <div class="col-sm-8">
      <?php if ($mode<>"new"): ?>
        <input class="form-control" type="text" id="inputSellerid" placeholder="卖家编号" name="merchant_id" value="<?=$amazonUser['merchant_id'] ?>" style="display:inline;" readonly/>
      <?php else: ?>
        <input class="form-control" type="text" id="inputSellerid" placeholder="卖家编号" name="merchant_id" value="" style="display:inline;"/>
      <?php endif ?>
    </div>
  </div>

  <!-- access key id -->
  <div class="form-group">
    <label for="inputAccessKeyId" class="col-sm-4 control-label">
      <span style="color:red;margin-left:3px">*</span>
      <?php echo TranslateHelper::t('AWS Access Key ID');?>:
    </label>
    <div class="col-sm-8">
      <?php if ($mode<>"new"): ?>
        <input class="form-control" type="text" id="inputAccessKeyId" placeholder="AWS访问键编号" name="access_key_id" value="<?=!empty($amazonUser['access_key_id'])?$amazonUser['access_key_id']:'' ?>" style="display:inline;" readonly/>
      <?php else: ?>
        <input class="form-control" type="text" id="inputAccessKeyId" placeholder="AWS访问键编号" name="access_key_id" value="" style="display:inline;"/>
      <?php endif ?>
    </div>
  </div>
  <!-- secret id -->
  <?php if ($mode=="new") :?>
  <div class="form-group">

    <label for="inputPassWord" class="col-sm-4 control-label">
      <span style="color:red;margin-left:3px">*</span>
      <?php echo TranslateHelper::t('Secret Key');?>:
    </label>
    <div class="col-sm-8">
        <input class="form-control" type="password" id="inputPassWord" placeholder="密钥" name="secret_access_key" style="display:inline;"/>
    </div>
  </div>
  <?php endif; ?>
  <!-- marketplace id -->
  <?php if ($mode=="new") :?>
  <div class="form-group">
    <label for="inputMarketplaceid" class="col-sm-4 control-label">
     <span style="color:red;margin-left:3px">*</span>
     <?php echo TranslateHelper::t('亚马逊站点');?>:
    </label>
    <div class="col-sm-8">
        <select class="form-control js-sel-mp" id="inputMarketplaceid">
            <option value="US">美国</option>
            <option value="CA">加拿大</option>
            <option value="MX">墨西哥</option>
            <option value="UK">英国</option>
            <option value="DE">德国</option>
            <option value="FR">法国</option>
            <option value="IT">意大利</option>
            <option value="ES">西班牙</option>
            <option value="IN">印度</option>
            <option value="JP">日本</option>
            <option value="CN">中国</option>
            <option value="AU">澳大利亚</option>
        </select>
    </div>
  </div>
  <?php endif; ?>
  <div class="form-group">
    <label class="col-sm-4 control-label">
    </label>
    <div class="col-sm-8 js-checkbox-mp" id="marketplace-options-div">
    <?php if ($mode=="new"): ?>
        <label><input type="checkbox"  name="marketplace_US" checked="" disabled="">美国</label>
        <label><input type="hidden"  name="marketplace_US" value="on" ></label>
        <label><input type="checkbox"  name="marketplace_CA">加拿大</label>
        <label><input type="checkbox"  name="marketplace_MX">墨西哥</label>
    <?php endif ?>
    </div>
  </div>

  <div class="form-group">
    <label for="inputActive" class="col-sm-4 control-label">
      <span style="color:red;margin-left:3px">*</span>
      <?php echo TranslateHelper::t('是否开启系统同步');?>:
    </label>
    <div class="col-sm-8">
        <select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
            <option value="1" <?php if ($mode<>"new" and $amazonUser["is_active"]==1) echo "selected";  ?>><?php echo TranslateHelper::t('是');?></option>
            <option value="0" <?php if ($mode<>"new" and $amazonUser["is_active"]==0) echo "selected";  ?>><?php echo TranslateHelper::t('否');?></option>
        </select>
    </div>
  </div>
  <div class="form-group">
    <label for="helperLink" class="col-sm-4 control-label" style="padding-top: 0px;">
      <?php echo TranslateHelper::t('帮助文档');?>:
    </label>
    <div class="col-sm-8">
        <a target="_blank" style="color:blue;" href="http://help.littleboss.com/word_list_247_516.html">
            <?php echo TranslateHelper::t('如果不知道怎么填写，请点击这里');?>
        </a>
    </div>
  </div>

</form>

