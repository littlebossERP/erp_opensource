<?php 
use yii\helpers\Html;
use eagle\models\EbayCategory;
use yii\helpers\Url;
use common\helpers\Helper_Util;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\TranslateHelper;
use common\helpers\Helper_Siteinfo;
use eagle\modules\listing\helpers\EbayListingHelper;
//$this->registerJsFile(\Yii::getAlias('@web')."/js/translate.js",[\yii\web\View::POS_HEAD]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/translate.js", ['position' => \yii\web\View::POS_BEGIN]);
$this->registerJs('Translator = new Translate('. json_encode(TranslateHelper::getJsDictionary()).');', \yii\web\View::POS_BEGIN);
//$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ckeditor/ckeditor.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js?v=".EbayListingHelper::$listingVer, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubaneditload.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerCssFile(\Yii::getAlias('@web')."/css/batchImagesUploader.css");
// $this->registerCssFile(\Yii::getAlias('@web')."/css/listing/ebay/ebaymuban/_main_box.css",['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
/*===========================================================================*/
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
.right-menu{
	width: 12%;
	float: left;
    background: #fff;
    height: 12px;
    margin-left: 1%;
    position: fixed;
    right: 0;
    /*top: 20px;*/
}
/*=========================================================*/
/*.btndo{
	margin-top:20px;
	padding-bottom:40px;
}
.btndo button{
	margin-left:40px;
	padding-left:30px;
	padding-right:30px;
}*/
/*========================================================*/
.save_name_div{
	margin:0 0;
	display:none;
	float:left;
}
/*========================================================*/
.whole-onebox{
	background-color:rgb(249,249,249);
	padding:12px;
	margin:5px 0px;
}
strong{
	color:rgb(46,204,113);
	font-weight:bold;
	font-size:15px;
}
.closeshipping{
	color:#ddd;
	float:right;
	margin-top:-10px;
	cursor:pointer;
}
td{
	padding:2px 20px;
}
.wuliudeal{
	color:rgb(46,204,113);
	margin:0px 20px;
	font-size:13px;
	cursor:pointer;
}
.fixed-boxsize{
	width:470px;
}
/*=========================================================*/
    .bbar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        display: block;
        height: 40px;
        /*background: #374655;*/
        background: white;
        line-height: 17px;
        overflow: hidden;
    }

        .donext {
        width: 115px;
        height: 32px;
    }
/*=========================================================*/
.profilelist{
	float:left;
	border:1px solid #ddd;
	border-radius:3px;
	width:100px;
	margin-right:5px;
	margin-top:1px;
}
/*=========================================================*/
.requirefix{
	color:red;
}
.main-box{
	border: 1px solid #7ca7cc;
    border-top: 0;
      /*border-style:solid;*/
    border-color:#578ebe;
    width: 87%;
    float: left;
}
.foot-label-format{
	position: fixed;
	float: left;
	width: 100%;
	background-color: #578ebe;
	/*bottom: -10px;*/
	/*_position:absolute;*/
	z-index:14;
	right:15px;
	display:block;
	font-size:15px;
	font-weight:800;
	text-align:center;
	height: 30px;
    bottom: 0;
    width: 100%;
    z-index: 10;
    line-height: 30px;
    color: #FFFFFF;

}
.subbox-title{
	/*background-color: #999999;*/
	background-color: #578ebe;
    padding: 11px 0 9px 9px;
    margin: 0px;
}

.caption{
	padding: 6px 6px;
}
.caption-subject{
	color: #FFF;
	font-size: 14px;
}
.action{
	float: right;
	display:inline;
}

.class-title{
    border-bottom: 1px solid #efefef;
    width: 100%;
    padding-left: 10px;
    padding: 10px;
    background: #fefefe;
    font-size: 12px;
    font-weight: bold;
    color: #444;
}
.class-new{
    border-bottom: 1px solid #efefef;
    width: 100%;
    /*padding-left: 10px;*/
    /*padding: 10px;*/
    background: #fefefe;
    font-size: 12px;
    font-weight: bold;
    color: #444;
}
.subbox .form .form-horizontal .form-group{
	margin: 0px;
}

.subbox .form .form-horizontal .form-group >label{
	padding: 12px;
	vertical-align: middle;
	margin-top: 1px;
	font-weight: 400;
}

.subbox .form .form-horizontal .form-group >div{
	padding: 8px;
	vertical-align: middle;
	border-left: 1px solid #efefef;
}


.subbox .form .form-horizontal .form-group .form-control{
	padding: 0px 6px;
	margin: 0px;
}

</style>
<br/>
<!-- 左侧菜单 -->
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'范本列表']);?>


<div class="row">
		<div class="main-box">
			<!-- <div class="row"> -->
		    <!-- <dir class="col-lg-11 main-box"><!-- 主题内容部分 -->
		    <form action="" method="post" id="a" name="a">
				<?php if (strlen(@$data['mubanid'])):?>
				<?=Html::hiddenInput('mubanid',@$data['mubanid'])?>
				<?php endif;?>
			<!--------------------
			--账号 (1级行 第1行)--
			---------------------->
			<div class="row">
				<div class="col-lg-12" >
					<div class="subbox" id="account">
						<?php echo $this->render('_form_gone',
							array(
							'data'=>$data,
							'ebayselleruserid'=>$ebayselleruserid,
							'paypals'=>$paypals
							))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->

			<!--------------------------
			--平台与细节 (1级行 第2行)--
			---------------------------->
			<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="siteandspe">
					<?php echo $this->render('_form_gtwo',
						array(
						'data'=>$data,
						'sitearr'=>$sitearr,
						'listingtypearr'=>$listingtypearr,
						'condition'=>$condition,
						'specifics'=>$specifics
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->
			<!--------------------------
			--标题与价格 (1级行 第3行)--
			---------------------------->
			<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="titleandprice">
					<?php echo $this->render('_form_gthree',
						array(
						'data'=>$data,
						// 'sitearr'=>$sitearr,
						// 'listingtypearr'=>$listingtypearr,
						// 'condition'=>$condition,
						// 'specifics'=>$specifics
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->
			<!--------------------------
			--图片与描述 (1级行 第4行)--
			---------------------------->
			<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="picanddesc">
					<?php echo $this->render('_form_gfour',
						array(
						'data'=>$data,
						'mytemplates'=>$mytemplates,
						'basicinfos'=>$basicinfos,
						'crosssellings'=>$crosssellings,
						// 'specifics'=>$specifics
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->
			<!--------------------------
			--物流设置 (1级行 第5行)--
			---------------------------->
			<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="shippingset">
					<?php echo $this->render('_form_gfive',
						array(
						'data'=>$data,
						'profile'=>$profile,
						'shippingserviceall'=>$shippingserviceall,
						'dispatchtimemax'=>$dispatchtimemax,
						'salestaxstate'=>@$salestaxstate,
						// 'specifics'=>$specifics
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->
			<!--------------------------
			--收货与退货 (1级行 第6行)--
			---------------------------->
			<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="returnpolicy">
					<?php echo $this->render('_form_gsix',
						array(
						'data'=>$data,
						'profile'=>$profile,
						'paymentoption'=>$paymentoption,
						'returnpolicy'=>$returnpolicy,
						'locationarr'=>$locationarr
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->
			<!--------------------------
			--买家要求 (1级行 第7行)--
			---------------------------->
			<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="buyerrequire">
					<?php echo $this->render('_form_gseven',
						array(
						// 'data'=>$data,
						'profile'=>$profile,
						// 'shippingserviceall'=>$shippingserviceall,
						// 'dispatchtimemax'=>$dispatchtimemax,
						// 'specifics'=>$specifics
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->
			<!--------------------------
			--增值设置 (1级行 第8行)--
			---------------------------->
				<div class="row" >
				<div class="col-lg-12" >
					<div class="subbox" id="plusmodule">
					<?php echo $this->render('_form_geight',
						array(
						'data'=>$data,
						'profile'=>$profile,
						'feature_array'=>$feature_array,
						// 'dispatchtimemax'=>$dispatchtimemax,
						// 'specifics'=>$specifics
						))?>
					</div><!-- END SUBBOX -->
				</div><!-- END col12 -->
			</div><!-- END ROW -->



		</div><!-- end main-box -->


		<div class="right-menu">
			<div class="left_pannel" id="floatnav">
				<div class="left_pannel_first"></div>
				<p onclick="goto('account')"><a>账号</a></p>
				<p onclick="goto('siteandspe')"><a>平台与细节</a></p>
				<p onclick="goto('titleandprice')"><a>标题与价格</a></p>
				<p onclick="goto('picanddesc')"><a>图片与描述</a></p>
				<p onclick="goto('shippingset')"><a>物流设置</a></p>
				<p onclick="goto('returnpolicy')"><a>收货与退款</a></p>
				<p onclick="goto('buyerrequire')"><a>买家要求</a></p>
				<p onclick="goto('plusmodule')"><a>增值设置</a></p>
				<div class="left_pannel_last"></div>
			</div>
		</div>
</div><!-- end row -->

<div class="row">

       <!--底部btn组-->
        <div class="bbar" style="border-top:3px solid #ddd;text-align:center;padding-top:5px;z-index: 11;">

        
        <!--底部btn组end-->
	<!-- 操作按钮区域  START-->
	<div class="btndo">
	<?php echo Html::hiddenInput('act','',['id'=>'act'])?>
	<?php echo Html::button('检测',array('onclick'=>'doaction("verify")','class'=>'btn btn-warning btn-sm donext'))?>
	<?php echo Html::button('保存',array('onclick'=>'doaction("save")','class'=>'btn btn-success btn-sm donext'))?>
	<?php echo Html::button('预览',array('onclick'=>'preview()','class'=>' btn btn-sm donext '))?>
	<?php if (strlen(@$data['mubanid'])):?>
	<?php echo Html::button('立即刊登',array('onclick'=>'doaction("additem")','class'=>' btn btn-default btn-sm donext '))?>
	<?php echo Html::button('另存为新刊登范本',array('onclick'=>'doaction("savenew")','class'=>'btn btn-default btn-sm donext '))?>
	<?php endif;?>
	<?php echo Html::button('重复刊登检测',array('onclick'=>'checkitem()','class'=>'btn btn-default btn-sm donext '))?>
	<?=Html::submitButton('',['style'=>'display:none;'])?>
	<?php echo Html::hiddenInput('uuid',Helper_Util::getLongUuid())?>
	</div>
	<!-- 操作按钮区域 end -->
	</div>
</div>
		</form>



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

</main><!-- end main-view -->
</dir><!-- end right_content-->
