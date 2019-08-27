<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ConfigHelper;
// print_r($template_detail);exit();
$lang = json_decode($data_tr['addi_info'],true);
$languages=!empty($lang['lang'])?$lang['lang']:null;
$language_type = ConfigHelper::getConfig("Message/template_language_statistics",'NO_CACHE');
$language_num = json_decode($language_type,true);
if(!empty($data_tr)&&!empty($template_detail)):?>
		<td><?= $data_tr['id'];?></td>
		<td class="language_dropdown">
		    <?php 
		    if(!empty($data_tr['type'])){
		        echo $data_tr['type']=='L'?"<span style='padding:1px 6px 1px 6px;' class='label label-success'>自定义</span><br />":($data_tr['type']=='C'?"<span style='padding:1px 6px 1px 6px;' class='label label-primary'>系统推荐</span><br />":null);
		    }
		    ?>
    		<span><?=$data_tr['template_name']?></span><br />
    		<span>[<?php echo !empty($_GET['language'])?$_GET['language']:(!empty($template_detail[$data_tr['id']])?$languages[$template_detail[$data_tr['id']][0]['lang']]:null);?>]</span>
    		<span><?= (!empty($_GET['language'])) ? $languages[$_GET['language']]:TranslateHelper::t('切换语言')?></span>
			<div class="btn-group">
				<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
					<span class="glyphicon glyphicon-menu-down"></span>
				</a>
				<ul class="dropdown-menu" data-selname="language" role="menu">
					<li><?= TranslateHelper::t('切换语言')?></li>
					<?php 
					if (!empty($languages)){
					foreach($languages as $code=>$label):?>
						<li<?php if (! empty($_GET['language'])) if ($_GET['language']==$code) echo ' class="active" '?>><?= $label?></li>
					<?php endforeach;
					}?>
				</ul>
				
			</div>
		
			<select name="language"  class="table_head_select"  style="display: none;">
				<option value=""><?= TranslateHelper::t('切换语言 ')?></option>
				<?php 
				if(!empty($languages)){
				foreach($languages as $code=>$label):?>
				<option value="<?= $code?>" <?php if (! empty($_GET['language'])) if ($_GET['language']==$code) echo " selected " ?>><?= $label?></option>
				<?php endforeach;
				}?>
			</select>
		</td>
		<td><?= !empty($template_detail[$data_tr['id']])?$template_detail[$data_tr['id']][0]['subject']:null; ?></td>
		<td><?php
		if(!empty($template_detail[$data_tr['id']])){
		    $template_body = nl2br($template_detail[$data_tr['id']][0]['content']);
    		$template_body =str_replace('[', '<b style="color:#337ab7">&#91;', $template_body);
    		$template_body =str_replace(']', '&#93;</b>', $template_body);
    		echo $template_body;
		}
		?></td>
		<td>
		<?php if($data_tr['type']=="L"):?>
		    <input type="hidden" id="language_array" name="language_array" value="<?php echo !empty($data_tr['addi_info'])?str_replace('"',"'",$data_tr['addi_info']):null;?>">
		    <?php if(count($language_num)!=count($languages)):?>
		    <a class="btn btn-success btn-sm" style="text-decoration: none;"  onclick="Customertemplate.OtherLanguageTemplate('<?php echo $data_tr['template_name'];?>','<?php echo $data_tr['id'];?>','update',this)"><?= TranslateHelper::t('新增') ?></a>
			<?php endif;?>
			<a class="btn btn-success btn-sm" style="text-decoration: none;"  onclick="Customertemplate.EditCustomerTemplate('<?php echo $data_tr['template_name'];?>','<?php echo $data_tr['id'];?>','<?php echo !empty($_GET['language'])?$_GET['language']:(!empty($template_detail[$data_tr['id']])?$template_detail[$data_tr['id']][0]['lang']:null);?>')"><?= TranslateHelper::t('修改') ?></a>
			<a class="btn btn-success btn-sm" style="text-decoration: none;"  onclick="Customertemplate.DeleteTemplate(<?=$data_tr['id'] ?>,this)"><?= TranslateHelper::t('删除') ?></a>
			<a class="btn btn-success btn-sm" style="text-decoration: none;"  onclick="Customertemplate.PreviewCustomerTemplate('<?php echo $data_tr['id'];?>','<?php echo !empty($_GET['language'])?$_GET['language']:(!empty($template_detail[$data_tr['id']])?$template_detail[$data_tr['id']][0]['lang']:null);?>')"><?= TranslateHelper::t('预览') ?></a>
		<?php endif;?>
		<?php if($data_tr['type']=="C"):?>
			<a class="btn btn-success btn-sm" style="text-decoration: none;" onclick="Customertemplate.PreviewCustomerTemplate('<?php echo $data_tr['id'];?>','<?php echo !empty($_GET['language'])?$_GET['language']:(!empty($template_detail[$data_tr['id']])?$template_detail[$data_tr['id']][0]['lang']:null);?>')"><?= TranslateHelper::t('预览') ?></a>
		<?php endif;?>
		</td>
<?php endif;?>