<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['yii\web\JqueryAsset']]);

?>
<style>
	.comment-top-ul {
		list-style-type: none;
		height:50px;
		margin-bottom:0px;
		padding-left:15px;
	}
	.comment-top-ul li {
		float:left;
		font-size: 12px;
		height:40px;
		line-height: 40px;
		margin-right:10px;
		color:#666;
	}
	.icon-button {
		display:inline-block;
		height:20px;
		width:25px;
		cursor: pointer;
		font-size: 13px;
		font-weight:bold;
	}
	.comment-table-content-tr {
		font-size:12px;
		color:#666;
		height:50px;
		border-bottom:1px solid #ccc;
	}
	.comment-table-content-tr:hover .icon-button {
		color:#02BDF0;
	}
	.send_error {color:#FF9801;font-weight:bold;}
	.send_success {color:#2DCC6F;font-weight:bold;}
	.left_menu a{
		color: #62778B;
	}
	.left_menu .down{
	cursor: pointer;
	}
	.btn-fun{
		background-color: #01BDF0 !important;
		color: white !important;
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
			'active'=> '好评记录',
		]);

?>
<form action="" name="search_form1" class="block">
	<div class="filter-bar">
		<label><?= TranslateHelper::t('好评日期')?></label>
		<input class="iv-input" type="date" name="stime" placeholder="开始时间" max="etime"/> -
		<input class="iv-input" type="date" name="etime" placeholder="结束时间" min="stime"/>
		<button type="submit" href="#" onclick="document.search_form1.submit()" class="iv-btn btn-search" style="padding: 5px 15px;">
			<span class="iconfont icon-sousuo"></span>
		</button>
	</div>
	<div class="table-action clearfix">
		<div class="pull-left">
			<a href="#" id="comment-log-recomment" class="iv-btn btn-fun"><span class="glyphicon glyphicon-heart-empty"></span> 批量重新评价</a>
		</div>
	</div>	
</form>
<table class=" table-striped table-nobordered" >
	<thead>
    <tr class="list-firstTr">
    	<th style="width:20px">
        	<input type="checkbox" id="comment-checkall" checked>
    	</th>
        <th class="text-nowrap col-lg-1">
        	<?= TranslateHelper::t('平台订单号') ?>
        </th>
        <th class="text-nowrap col-lg-2">
        	<?= TranslateHelper::t('速卖通订单号') ?>
        </th>
        <th class="text-nowrap col-lg-1">
         	<?php $aliexpressusers_arr = array_map(function($user){
        	    	return $user->sellerloginid;
        		},$aliexpressusers);
        	?>
        	<?= HtmlHelper::dropdownlistSearchButton('selleruser',$aliexpressusers_arr,TranslateHelper::t('店铺名称'))?>
        </th>
        <th class="text-nowrap col-lg-1">
        	<?=TranslateHelper::t('买家姓名') ?>
        </th>
        <th class="text-nowrap col-lg-1">
        	<?=TranslateHelper::t('订单金额') ?>
        </th>
        <th class="text-nowrap col-lg-2">
        	<?=TranslateHelper::t('下单时间') ?>
        </th>
        <th class="text-nowrap col-lg-2">
        	<?=TranslateHelper::t('好评留言') ?>
        </th>
        <th class="text-nowrap col-lg-1">
        	<?=$sort->link('is_success',['label'=>TranslateHelper::t('发送状态')]) ?>
        </th>
        <th class="text-nowrap col-lg-1">
        	<?= TranslateHelper::t('操作') ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php 
    $statusStr = [
    	['发送失败','send_error'],
    	['发送成功','send_success'],
    	['等待结果','text-primary']
	];
    if(count($logs)):
    foreach($logs as $log):
    	// var_dump($log->attributes);
    	if(!$log->is_success && in_array(trim($log->error_msg),['发送失败：It is already leave feedback.', '发送失败：The order can not be null.']) ){
    		continue;
    	}
    ?>
    <tr class="comment-table-content-tr">
    	<td>
    		<?php if($log->is_success==0):?>
    		<input type="checkbox" class="order_checkbox" value="<?=$log->id?>" checked>
    		<?php endif;?>
    	</td>
    	<td style="font-weight:bold"><?= $log->order_id ?></td>
    	<td>
    		<a target="littleboss_to_aliexpress" href="http://trade.aliexpress.com/order_detail.htm?orderId=<?= $log->order_source_order_id ?>"><?= $log->order_source_order_id ?></a>
    	</td>
    	<td style="font-weight:bold"><?= $log->selleruserid ?></td>
    	<td><?= $log->source_buyer_user_id ?></td>
    	<td><?= $log->subtotal.'&nbsp;'.$log->currency ?></td>
		<td><?= date('Y-m-d H:i:s',$log->order_source_create_time) ?></td>
		<td><?= $log->content ?></td>
		<td>
			<span class="<?= $statusStr[$log->is_success][1]?>">
				<?= $statusStr[$log->is_success][0].($log->is_success?'':'<br/>原因：'.$log->error_msg) ?>
			</span>
		</td>
		<td>
			<?php if($log->is_success == 0):?>
			<span class="glyphicon glyphicon-heart-empty icon-button" title="重新好评" onclick="comment_log_recomment(<?=$log->id?>)"></span>
			<?php endif;?>
		</td>
    </tr>
    <?php endforeach;else:?>
    <tr>
    	<td colspan="11">还没有好评记录哦.</td>
    </tr>
    <?php endif;?>
    </tbody>
</table>
<?= HtmlHelper::Pagination($pages) ?>
