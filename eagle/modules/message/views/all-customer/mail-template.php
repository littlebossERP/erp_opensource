<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\StandardConst;

//$this->title = TranslateHelper::t('自动邮件提醒设置');
$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';

$this->registerJsFile($baseUrl."js/project/message/template/customer_mail_template.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("Customertemplate.init();" , \yii\web\View::POS_READY);

$this->registerCssFile($baseUrl."css/message/customer_message.css");
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
// print_r($data);
// print_r($template_detail);exit();
?>

<style>
.float_left{
	float:left;
}

.content_left{
	width:20%;
	vertical-align: top;
	
}

.content_right{
	width:78%;
	vertical-align: top;
	
}

.td_space_toggle{
	height: auto;
	padding: 0!important;
}

.div_space_toggle{
	display:none;
}

.menu_lev2{
	padding-left: 40px!important;
}

.date_input{
	width: 20px;
}

form ul li{
	margin-bottom: 0px;
}

.table td > .btn-group .dropdown-menu {
  min-width:62px;
  border-radius: 0;
  border-color: #70b1d7;
  left: -51px;
  cursor: pointer;
  font-size:13px;
}



.table td > .btn-group .dropdown-menu li:hover{
  background-color:#70b1d7;
}

</style>
<?php 
// print_r($language_list);exit();
$template_language=!empty($language_list)?$language_list:null;
//     $template_language=['en'=>'英语','cn'=>'中文','fr'=>'法语'];
    $template_type=['C'=>'系统推荐','L'=>'自定义'];
?>
<div class="tracking-index">
<div class="tracking-index col2-layout">
    	<?= 
//     	$this->render('left_menu') 
    	$this->render('new_menu');
    	?>
    	<div class="content-wrapper">
        	<form action="/message/all-customer/mail-template" method="GET">
            	<div class="mail-template-list">
            	<div style="margin: 10px;">
            		<button class="btn btn-success btn-sm" type="button" onclick="Customertemplate.NewCustomerTemplate()"><?= TranslateHelper::t('新增发信模板') ?></button>
            		&nbsp;&nbsp;&nbsp;&nbsp;<?=Html::dropDownList('template_language',@$_REQUEST['template_language'],$template_language,['onchange'=>"template_language_action($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;','prompt'=>'全部语言'])?>
            		&nbsp;&nbsp;&nbsp;&nbsp;<?=Html::dropDownList('template_type',@$_REQUEST['template_type'],$template_type,['onchange'=>"template_type_action($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;','prompt'=>'全部类型'])?>
            	    &nbsp;&nbsp;&nbsp;&nbsp;<input class="eagle-form-control" type="text" id="template_search" name="template_search" placeholder="搜索  主题、内容" value="<?php echo !empty($_REQUEST['template_search'])?$_REQUEST['template_search']:"";?>">&nbsp;<input type="submit" value="搜索" class="btn btn-success btn-sm">
            	</div>
            	<table id=mail-template-list-tb class="table table-hover">
            		<thead>
            	    <tr class="list-firstTr">
            	    	<th style="width:40px;"><?= TranslateHelper::t('编号') ?></th>
            			<th style="width:170px;"><?= TranslateHelper::t('模板名称') ?></th>
            			<th ><?= TranslateHelper::t('模板主题') ?></th>
            			<th ><?= TranslateHelper::t('内容') ?></th>
            			<th style="width:210px;"><?= TranslateHelper::t('操作')?></th>
            		</tr>
            		</thead>
            		<tbody>
            		    <?php $num=1;foreach ($data['data'] as $data_tr):?>
            		    <tr id="tr_template_info_<?php echo $data_tr['id'];?>" data-language="" data-tr-id="<?php echo $data_tr['id']; ?>" style="height:64px;"<?php echo $num%2==0?"class='striped-row'":null;?>>
            		      <?php echo $this->render('mail_Template_tr',['data_tr'=>$data_tr,'template_detail'=>$template_detail,]);?>
            		    </tr>
            		    <?php $num++; endforeach;?>
            		</tbody>
            	</table>
            	<!-- pagination -->
            	<?php if($data['pagination']):?>
            	<div>
            	    <?= \eagle\widgets\SizePager::widget(['pagination'=>$data['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
            	    <div class="btn-group" style="width: 49.6%;text-align: right;">
            	    	<?=\yii\widgets\LinkPager::widget(['pagination' => $data['pagination'],'options'=>['class'=>'pagination']]);?>
            		</div>
            	</div>
            	<?php endif;?>
            	<!-- /.pagination-->
            	
                </div>
            </form>
        </div>
    
</div>
</div>
<script>
function template_language_action(val){
	 $("form").submit();		   
	}
function template_type_action(val){
	 $("form").submit();		   
	}
</script>