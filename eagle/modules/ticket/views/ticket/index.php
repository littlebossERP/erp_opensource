<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\ticket\helpers\TicketHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/ticket/ticket.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerCssFile($baseUrl."css/catalog/catalog.css");

$closed_state_id = TicketHelper::get_Closed_State_Id();
$open_state_id = TicketHelper::get_Open_State_Id();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>
.bg_loading {
	background-image: url(/../images/loading.gif);
	background-repeat: no-repeat;
	background-position: center;
}
.view_ticket_win .modal-body{
	overflow: auto;
	max-height: 600px;
}

/*
table > tbody > tr > th{
	height: 20px;
  	padding: 3px;
  	vertical-align: middle;
	text-align: center !important;
	background-color: #d9effc;
	font: bold 12px SimSun,Arial;
	color: #374655;
}
table > tbody > tr > td{
  	vertical-align: middle;
	text-align: center;
	word-break:break-word;
}
*/
.cursor_pointer{
	cursor: pointer;
}
ul li a{
	cursor:pointer;
}
.ui-autocomplete {
z-index: 2000;
}
.popover-content{
	background-color: rgb(255, 168, 168);
}
</style>
<div class="catalog-index col2-layout">
	<div id="sidebar">
		<a id="sidebar-controller" onclick="toggleSidebar();" title="展开收起左侧菜单">&lsaquo;</a>
	  	<div class="sidebarLv1Title">
			<div>
				<span class=""></span>
				<?= TranslateHelper::t('工单管理')?>
			</div>
		</div>
		<ul class="ul-sidebar-one">
			<li class="ul-sidebar-li<?=(yii::$app->controller->id=='ticket' and ((yii::$app->controller->action->id == 'index') or (yii::$app->controller->action->id == 'list')))?' active':''?>">
				<a class="" href="<?= Url::to(['/ticket/ticket/index'])?>">
					<span class="glyphicon glyphicon-list"></span>
					<span><?= TranslateHelper::t('我的工单')?></span>
				</a>
			</li>
		</ul>
	</div>
	<div class="content-wrapper" >
	<?php if(!empty($connect_err)){?>
		<div class="width:100%" >
			<?=$connect_err ?>
		</div>
	<?php }?>
		<form  class="form-horizontal"
			action="<?= Url::to(['/'.yii::$app->controller->module->id.'/'.yii::$app->controller->id.'/'.yii::$app->controller->action->id])?>"
			method="get" style="float:left;width:100%;">
				
			<div style="float:left;width:100%;">
				<div style="margin-right:10px;float:left;">
					<div class="div-input-group" style="width:100%">
						<div style="float:left;">
							<select name='status' class="eagle-form-control"  style="float:left;margin:0px;">
								<option value="all"><?= TranslateHelper::t("处理状态")?></option>
								<option value="6" <?= (isset($_GET['status']) && (int)$_GET['status']== 6)?"selected":"" ?> ><?=TranslateHelper::t("已回复") ?></option>
								<?php foreach($status as $k=>$v):
									if (!empty($_GET['status'])) $isSelect = ($_GET['status'] == $k)?"selected":"";
									else $isSelect = "";?>
								<option value="<?= $k ?>" <?= $isSelect ?> >
									<?php 
										if((int)$k==$closed_state_id) echo TranslateHelper::t("已解决/已撤销");
										elseif ((int)$k==$open_state_id) echo TranslateHelper::t("待回复");
										else echo $v;
									?>
								</option>
								<?php endforeach;?>
								
							</select> 
						</div>
					</div>
				</div>
				<div style="width:250px;float:left;">
					<div class="div-input-group" style="width:100%">
						<div style="" class="input-group" style="float:left;">
							<input name='keyword' type="text" class="form-control" style="height:28px;float:left;width:100%;" 
								placeholder="<?= TranslateHelper::t('输入搜索字段(工单号或标题)')?>"
								value="<?= (empty($_GET['keyword'])?'':$_GET['keyword'])?>"/>
							<span class="input-group-btn" style="">
								<button type="submit" class="btn btn-default" style="padding:3px 6px;">
									<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
							    </button>
						    </span>
						</div>
					</div>
				</div>
			</div>
			<div style="float:left;width:100%;">
				<div style="float:left;">
					<button type="button" class="btn-xs btn-transparent font-color-1" onclick="ticket.list.addTicket()" style="margin: 5px 0px;font-size: 12px;">
						<span class="glyphicon glyphicon-plus"></span>
						<?= TranslateHelper::t('添加工单')?>
					</button>
				</div>
				<div style="float:left;">
					<button type="button" class="btn-xs btn-transparent font-color-1" onclick="ticket.list.batchCancelTicket()" style="margin: 5px 0px;font-size: 12px;">
						<span class="glyphicon glyphicon-off" aria-hidden="true" style="height:16px;"></span>
						<?= TranslateHelper::t('批量关闭')?>
					</button>
				</div>
				<div style="float:left;">
					<button type="button" class="btn-xs btn-transparent font-color-1" onclick="ticket.list.batchReopenTicket()" style="margin: 5px 0px;font-size: 12px;">
						<span class="glyphicon glyphicon-play-circle" aria-hidden="true" style="height:16px;"></span>
						<?= TranslateHelper::t('批量开启')?>
					</button>
				</div>
				<div style="float:left;">
					<button type="button" class="btn-xs btn-transparent font-color-1" onclick="ticket.list.batchDeleteTicket()" style="margin: 5px 0px;font-size: 12px;">
						<span class="glyphicon glyphicon-trash" aria-hidden="true" style="height:16px;"></span>
						<?= TranslateHelper::t('批量删除')?>
					</button>
				</div>
			</div>
		</form>
		<div style="float:left;width:100%;">
			<table>
				<tr>
					<th style="width: 30px;text-align: center;">
						<input type="checkbox" name="chk_ticket_all">
					</th>
					<th width="100px">处理编号</th>
					<th width="300px">标题</th>
					<th width="200px">处理情况</th>
					<th width="100px">所属模块</th>
					<th width="170px">创建时间</th>
					<th width="170px">开始处理时间</th>
					<th width="170px">处理/关闭时间</th>
					<th width="200px">操作</th>
				</tr>
				<?php 
				if(!empty($tickets)){
					foreach ($tickets as $index=>$row){?>
				<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
					<td style="text-align: center;">
						<input type="checkbox" name="chk_ticket_one" value="<?=$row['ticket_id']?>">
					</td>
					<td><?= $row['number'] ?></td>
					<td><?= isset($row['title'])?$row['title']:'' ?></td>
					<td>
						<?php $status_cn = '--';
							  if(isset($status[$row['status_id']])){
							  		$status_cn=$status[$row['status_id']];
							  		if((int)$row['status_id']==$open_state_id){
							  			if((int)$row['isanswered']==1)
							  				$status_cn='已回复';
							  			else 
							  				$status_cn='待回复';
							  		}elseif((int)$row['status_id']==$closed_state_id){
							  			$status_cn='已解决/已撤销';
							  		}
							  }
							  echo $status_cn;
						?>
					</td>
					<td><?= isset($topic[$row['topic_id']])?$topic[$row['topic_id']]['topic']:"<span style='display:none'>topic_id=".$row['topic_id']."</span>--" ?></td>
					<td><?= $row['created'] ?></td>
					<?php 
					$start_duedate = TicketHelper::get_Ticket_Start_Duedate($row['ticket_id']);
					if(empty($start_duedate)) $start_duedate='--';
					?>
					<td><?=$start_duedate ?></td>
					<td><?= empty($row['closed'])?'--':$row['closed'] ?></td>
					<td>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('详情')?>" onclick="ticket.list.viewTicket(<?=$row['ticket_id'] ?>)" style="vertical-align:middle;">
							<span class="glyphicon glyphicon-list-alt" aria-hidden="true" style="top:2px;"></span>
						</button>
						<?php if((int)$row['status_id']==$open_state_id){?>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('回复')?>" onclick="ticket.list.replyTicket(<?=$row['ticket_id'] ?>)" style="vertical-align:middle;">
							<span class="egicon-envelope" aria-hidden="true" style="top:2px;"></span>
						</button>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('关闭')?>" onclick="ticket.list.cancelTicket(<?=$row['ticket_id'] ?>,'<?=$row['number'] ?>')" style="vertical-align:middle;">
							<span class="glyphicon glyphicon-off" aria-hidden="true" style="height:16px;"></span>
						</button>
						<?php }elseif ((int)$row['status_id']==$closed_state_id){?>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('重开')?>" onclick="ticket.list.reopenTicket(<?=$row['ticket_id'] ?>,'<?=$row['number'] ?>')" style="vertical-align:middle;">
							<span class="glyphicon glyphicon-play-circle" aria-hidden="true" style="height:16px;"></span>
						</button>
						<?php }?>
						
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('删除')?>" onclick="ticket.list.deleteTicket(<?=$row['ticket_id'] ?>,'<?=$row['number'] ?>')" style="vertical-align:middle;">
							<span class="glyphicon glyphicon-trash" aria-hidden="true" style="height:16px;"></span>
						</button>
					</td>
				</tr>
			 <?php 
					}
				}
				?>
			</table>
		</div>
		<!-- pagination -->
		<?php if($pagination):?>
		<div style="margin-top: 20px;float: left;">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="margin-left:20px;text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
		<?php endif;?>
		<!-- /.pagination-->
		
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"></div>
	
	

</div>
<div class="creat_ticket_win"></div>
<div class="view_ticket_win"></div>
<div class="reply_ticket_win"></div>