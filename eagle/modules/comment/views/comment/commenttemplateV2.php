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
	'订单评价' => [
		'icon' => 'iconfont icon-stroe',
		'items' => [
			'自动好评' => [
				'url' => '/comment/comment/rule-v2',
			],
			'等待您留评' => [
				'url' => '/comment/comment/index-v2',
			],
			'评价模板' => [
				'url' => '/comment/comment/template-v2',
			],
		],
	],
	'好评记录' => [
		'icon' => 'iconfont icon-stroe',
		'items' => [
			'好评记录' => [
				'url' => '/comment/comment/log-v2',
			],
		]
	],
];
	echo $this->render('//layouts/new/left_menu',[
			'menu'=>$menu,
			'active'=>'评价模板',
		]);
?>
<div class="table-action clearfix">
	<div class="pull-left">
		<button  onclick="$.modal({url:'addtemplate-v2'},'添加好评模板',{footer:false,inside:false});" class="iv-btn btn-warn" title="添加好评模板" style="background-color:#2DCC70;color: white;">添加好评模板</button>
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
    	
    	<td><?= $template['content'] ?></td>
    	<td>
			<a onclick="$.modal({url:'addtemplate-v2?template_id=<?= $template['_id']->{'$id'} ?>'},'编辑好评模板',{footer:false,inside:false});"  class=" glyphicon glyphicon-edit"  title="编辑好评模板" style="cursor:pointer;margin-right:20px;color:#666"></a>

			<span class="glyphicon glyphicon-trash icon-button" title="删除" onclick="comment_template_deleteV2('<?= $template['_id']->{'$id'} ?>')"></span>
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
