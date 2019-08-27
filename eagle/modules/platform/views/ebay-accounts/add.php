<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

// $this->registerJsFile(\Yii::getAlias('@web').'/js/project/platform/ebayAccountsAdd.js');
?>
<style>
    *{ margin:0; padding:0;}
    .ebay{ width:568px; font-size:14px; color:#666666;}
    .ebay-title{ height:149px; background:#f8f8f8;}
    .ebay-title img{ margin:29px 0 0 106px;}
    .ebay-step{ width:356px; margin:55px auto 0 auto;}
    .ebay-step dl{ height:66px; padding-top:27px; border-bottom:1px dashed #e7e7e7; padding-left:137px;}
    .ebay-step .ebay-step-dl1{ background:url(<?php echo \Yii::getAlias('@web')."/";?>images/paypal/paypal03.jpg) no-repeat 43px 28px;}
    .ebay-step .ebay-step-dl2{ background:url(<?php echo \Yii::getAlias('@web')."/";?>images/paypal/paypal04.jpg) no-repeat 43px 28px;}
    .ebay-step dl dt{ margin-top:12px;line-height: 24px;}
    .ebay-step i,.ebay-step em{ font-style:normal;}
    .ebay-step i{ display:block; float:left;}
    .ebay-step em{ display:block; float:left; margin-left:7px;}
    .ebay-step em img{vertical-align: middle;}
        
</style>


<div class="ebay">
    <div class="ebay-title">
    	<img src="<?php echo \Yii::getAlias('@web')."/";?>images/ebay/ebay01.jpg" />
    	<span style="display: inline-block;width: 200px;margin-top: 54px;vertical-align: top;">
    		<font style="font: bold 30px Arial;"><?= TranslateHelper::t('绑定步骤') ?></font>
    		<br>
    		<font style="font: bold 14px Arial;"><?= TranslateHelper::t('必须完成两个步骤') ?></font>
    	</span>
    </div>
    
    <div class="ebay-step">
        <dl class="ebay-step-dl1">
            <dt><i><?php echo TranslateHelper::t('到');?></i>
                <em>
                    <a style="color: #52a3e4;text-decoration: none;" href="<?php echo Url::to(['/platform/ebay-accounts/bindseller1']);?>" target="_blank_ebay">
                        <img src="<?php echo \Yii::getAlias('@web')."/";?>images/ebay/ebay02.jpg" />
                       	 <font style="border: 1px solid #52a3e4;padding: 2px 10px;border-radius: 3px;"><?= TranslateHelper::t('登录验证')?></font>
                    </a>
                </em>
            </dt>
        </dl>
        <dl class="ebay-step-dl2">
            <dt><i><?php echo TranslateHelper::t('验证成功');?></i><em>
                    <a style="color: #52a3e4;text-decoration: none;border: 1px solid #52a3e4;padding: 2px 10px;border-radius: 3px;" href="#"  onclick="platform.ebayAccountsList.bindseller2();">
						<?php echo TranslateHelper::t('确定绑定');?>
                    </a>
                    <img id='platform-ebayAccounts-add-load'/>
                </em>
            </dt>
        </dl>
        <div id="platform-ebayAccounts-list-message" style="margin-left: 135px;"></div>
    </div>
</div>



