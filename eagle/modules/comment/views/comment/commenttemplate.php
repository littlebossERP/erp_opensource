<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use yii\helpers\Url;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['eagle\assets\PublicAsset']]);

?>
<style>
	.icon-button {
		display:inline-block;
		height:20px;
		width:25px;
		cursor: pointer;
		font-size: 13px;
		font-weight:bold;
	}
	.template-add-table {
		font-size:12px;
		color:#666;
	}
	.left_menu a{
		color: #62778B;
	}
	.right_content{
		padding-top: 10px;
	}
	.left_menu .down{
		cursor: pointer;
	}
</style>
<?php 
	$menu = [
		'自动好评' =>[
			'icon' => 'iconfont icon-stroe', 
			'items'=>[
				'自动好评' => [
					'url' => '/comment/comment/rule',
				],
				'好评模板' =>[
					'url' => '/comment/comment/template',
				],
			],
		],
		'手动批量好评' =>[
			'icon' => 'iconfont icon-shezhi',
			'items'=> [
				'手动批量好评'=>[
					'url' => '/comment/comment/index',
				],
			],
		],
		'好评记录' =>[
			'icon' => 'iconfont icon-stroe',
			'items'=>[
				'好评记录'=>[
					'url' => '/comment/comment/log',
				],
			]
		],
	];
	echo $this->render('//layouts/new/left_menu',[
			'menu'=>$menu,
			'active'=>'好评模板',
		]);
?>
<div class="table-action clearfix">
	<div class="pull-left">
		<?= HtmlHelper::modalButton(TranslateHelper::t('添加好评模板'),'addtemplate',null,['class'=>'iv-btn btn-important btn-spacing-middle' ,'style'=>'color:white;']);?>
	</div>
</div>
<table class=" table-striped table-nobordered" >
		<thead>
    <tr class="list-firstTr">
        <th class="text-nowrap col-lg-10">
        	<?= TranslateHelper::t('好评内容') ?>
        </th>
        <th class="text-nowrap col-lg-1">
        	<?= TranslateHelper::t('操作') ?>
        </th>
    </tr>
    </thead>
    <tbody>
    
    <?php if(count($templates)):foreach($templates as $template):?>
    <tr class="comment-table-content-tr">
    	
    	<td><?= $template->content ?></td>
    	<td>
        	<?= HtmlHelper::modalButton('<span class="glyphicon glyphicon-edit"></span>',Url::to(['/comment/comment/addtemplate','template_id'=>$template->id]),[],['style'=>'cursor:pointer;margin-right:20px;color:#666']) ?>
			<span class="glyphicon glyphicon-trash icon-button" title="删除" onclick="comment_template_delete('<?= $template->id ?>')"></span>
    	</td>
    	
    </tr>
    <?php endforeach;else:?>
    <tr>
    	<td colspan="2">没有评论模版哦. 快添加吧</td>
    </tr>
    <?php endif;?>
    </tbody>
</table>
<?= HtmlHelper::Pagination($pages) ?>
