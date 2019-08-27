<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\widgets\LinkPager;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->registerJsFile(\Yii::$app->urlManager->baseUrl . "/js/project/carrier/customtemplate.js",['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);


// $this->registerCssFile($baseUrl."css/carrier/customtemplate.css");
 ?>
<style>
	#template_name_notice {
		font-size:12px;
		color:#ce4844;
		line-height: 40px;
		display: none;
	}
	.total_size {
		display:inline-block;
		height:18px;
		text-align: center;
		background:#d9effc;
		border:1px solid #d9effc;
		border-radius: 8px 8px 8px 8px;
		padding: 0px 8px;
		line-height: 18px;
		font-weight: bold;
		color:#3a87ad;
	}
</style>
<div class="tracking-index col2-layout">
	<?= $this->render('//layouts/menu_left_carrier') ?>
	<div class="content-wrapper">
	<form action="">
	<input type="hidden" name="selftemplate" id="selftemplatetag">
	<input type="hidden" name="tab_active" id="search_tab_active">
	<div class="btn-group" style="width:220px;height:40px;">
		<div class="input-group">
			<input type="text" class="form-control" placeholder="请输入模版名" name="template_name" style="width:166px;height:34px;" value="<?= isset($_GET['template_name'])?$_GET['template_name']:'' ?>">
			<div class="input-group-btn">
				<button class="btn btn-default" id="search_button"><i class="glyphicon glyphicon-search"></i>搜索</button>
			</div>
		</div>
	</div>
	</form>
	<!-- tab panes start -->
	<div>
	  <!-- Nav tabs -->
	  <ul class="nav nav-tabs" role="tablist" style="height:42px;">
	      
              <li role="presentation" class="<?php echo $tab_active==='self' ? '' : 'active';?> sys">
	          <a href="#systemplate" aria-controls="systemplate" role="tab" data-toggle="tab">系统模版 <span class="total_size"><?= $sys_templates_total ?></span></a>
	      </li>
              <li role="presentation" class="<?php echo $tab_active==='self' ? 'active' : '';?> self">
	          <a href="#selftemplate" aria-controls="selftemplate" role="tab" data-toggle="tab">自定义模版 <span class="total_size"><?= $templates_total ?></span></a>
	      </li>
		  <div class="panel-heading" style="position: absolute;right:5px;top:43px;">
			  <button data-toggle="modal" data-target="#createNewTemplate" class="btn btn-success"><?=TranslateHelper::t('新增自定义模版') ?></button>
		  </div>
	  </ul>


	  <!-- Tab panes -->
	<div class="tab-content">
	  <!-- 系统模版 -->
	  <div role="tabpanel" class="tab-pane <?php echo $tab_active==='self' ? '' : 'active';?>" id="systemplate">
	  	<form action="" name="search">
		  <table class="table table-hover table-striped table-bordered" style="border-top:none">
			  <tr class="list-sys-firstTr">
				  <th class="text-nowrap col-lg-3">
					  <?=$syssort->link('template_name',['label'=>TranslateHelper::t('模版名称')]) ?>
				  </th>
				  <th class="text-nowrap col-lg-3">
					  <?=$syssort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?>
				  </th>
				  <th class="text-nowrap col-lg-2">
					  <?= HTML::dropDownList('size',empty($size)?'':$size,['100mmX100mm','100mmX50mm','A4 (210mm x 297mm)','80mm x 30mm'],['prompt'=>'规格','onchange'=>'document.search.submit()']) ?>
				  </th>
				  <th class="text-nowrap col-lg-1">
					  <?=$syssort->link('template_type',['label'=>TranslateHelper::t('单据类别')]) ?>
				  </th>
				  <th class="text-nowrap col-lg-3">
					  <?= TranslateHelper::t('操作') ?>
				  </th>
			  </tr>
			  <?php foreach($systemplates as $systemplate):?>
				  <tr>
					  <td class="text-nowrap"><?=$systemplate->template_name ?></td>
					  <td class="text-nowrap"><?=date('Y-m-d H:i:s',$systemplate->create_time) ?></td>
					  <td class="text-nowrap"><?=$systemplate->template_width ?>mmX<?=$systemplate->template_height?>mm</td>
					  <td class="text-nowrap"><?=$systemplate->template_type ?></td>
					  <td class="text-nowrap">
						  <div class="btn-group">
							  <a class="btn btn-default btn-sm" href="<?=Url::to(['/carrier/carriercustomtemplate/preview','template_id'=>$systemplate->id,'is_sys'=>1])?>" target="_blank"><span class="glyphicon glyphicon-eye-open"></span><?=TranslateHelper::t('预览') ?></a>
							  <a href="#" data-toggle="modal" data-target="#copyToNewTemplate" class="btn btn-success btn-sm" onclick="copytemplateToself(<?= $systemplate->id?>,'<?= $systemplate->template_name?>')">
								  <span class="glyphicon glyphicon-copy"></span> <?=TranslateHelper::t('复制为自定义模版') ?>
							  </a>
						  </div>
					  </td>
				  </tr>
			  <?php endforeach;?>
		  </table>
		  </form>
		  <?php if($syspages):?>
			  <div id="pager-group">
				  <?= \eagle\widgets\SizePager::widget(['pagination'=>$syspages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
				  <div class="btn-group" style="width: 49.6%;text-align: right;">
					  <?=\yii\widgets\LinkPager::widget(['pagination' => $syspages,'options'=>['class'=>'pagination']]);?>
				  </div>
			  </div>
		  <?php endif;?>
	  	
	  </div>
	  <!-- 用户自定义模板页 -->
	  <div role="tabpanel" class="tab-pane <?php echo $tab_active==='self' ? 'active' : '';?>" id="selftemplate">
	  	<!-- selftemplate start -->
<!--	  	<div class="panel panel-default">-->
			
		    <table class="table table-hover table-striped table-bordered" style="border-top:none">
		        <tr class="list-firstTr">
		            <th class="text-nowrap col-lg-3">
		            	<?=$sort->link('template_name',['label'=>TranslateHelper::t('模版名称'),'class'=>'selftab']) ?>
		            </th>
		            <th class="text-nowrap col-lg-3">
		            	<?=$sort->link('update_time',['label'=>TranslateHelper::t('修改时间'),'class'=>'selftab']) ?>
		            </th>
		            <th class="text-nowrap col-lg-3">
		            	<?=$sort->link('template_type',['label'=>TranslateHelper::t('单据类别'),'class'=>'selftab']) ?>
		            </th>
		            <th class="text-nowrap col-lg-3">
		            	<?= TranslateHelper::t('操作') ?>
		            </th>
			    </tr>
		        <?php foreach($templates as $template):?>
		        <tr>
		            <td class="text-nowrap"><?=$template->template_name ?></td>
		            <td class="text-nowrap"><?=date('Y-m-d H:i:s',$template->update_time) ?></td>
		            <td class="text-nowrap"><?=$template->template_type ?></td>
		            <td class="text-nowrap">
						<div class="btn-group">
							<a class="btn btn-default btn-sm" href="/carrier/carriercustomtemplate/edit?id=<?=$template->template_id ?>" target="_blank"><span class="glyphicon glyphicon-edit"></span><?=TranslateHelper::t('编辑') ?></a>	 
							<a class="btn btn-default btn-sm" href="/carrier/carriercustomtemplate/preview?template_id=<?=$template->template_id ?>" target="_blank"><span class="glyphicon glyphicon-edit"></span><?=TranslateHelper::t('预览') ?></a>
							<button data-ajax-request="/carrier/carriercustomtemplate/delete?template_id=<?=$template->template_id ?>" class="btn btn-danger btn-sm">
								<span class="glyphicon glyphicon-remove"></span> <?=TranslateHelper::t('删除') ?>
							</button>
						</div>
		            </td>
		        </tr>
		        <?php endforeach;?>
		    </table>
		    <?php if($pages):?>
		    <div id="pager-group">
		        <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup selftab']);?>
		        <div class="btn-group" style="width: 49.6%;text-align: right;">
		        	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination selftab']]);?>
		    	</div>
		    </div>
		    <?php endif;?>
		   
<!--		</div>-->
		<!-- self template end -->
	  	
	  </div>
	  </div>
	</div>
	 <!-- table panes end -->

	<!-- Modal -->
		<div class="modal fade" id="createNewTemplate">
			<form action="edit" method="get" target="_blank" class="form-horizontal">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title">新增自定义模版</h4>
						</div>
						<div class="modal-body">
							<div class="form-group">
								<label for="" class="col-lg-4 control-label">模版名称</label>
								<div class="col-lg-5">
									<input type="text" name="template_name" class="form-control" required="required" />
								</div>
								<span id="template_name_notice" class="glyphicon glyphicon-warning-sign"></span>
							</div>
							<div class="form-group">
								<label for="" class="col-lg-4 control-label">单据类别</label>
								<div class="col-lg-5">
									<select name="template_type" class="form-control">
										<option value="地址单">地址单</option>
										<option value="报关单">报关单</option>
										<option value="配货单">配货单</option>
										<option value="发票">发票</option>
										<option value="商品标签">商品标签</option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label for="" class="col-lg-4 control-label">规格</label>
								<div class="col-lg-5" id="sizeSel">
									<button class="btn btn-default active">标准规格</button>
									<button class="btn btn-default">自定义规格</button>
									<div class="jsDo">
										<select name="template_size" class="form-control">
											<option value="x">-- 请选择单据规格 --</option>
											<option value="100x100">100mm x 100mm</option>
											<option value="100x50">100mm x 50mm</option>
											<option value="210x297">A4 (210mm x 297mm)</option>
											<option value="80x30">80mm x 30mm</option>
										</select>

										<div class="input-group" style="display:none;margin-top:5px;">
											<input type="text" class="form-control" name="width" placeholder="宽度" />
											<span class="input-group-addon" >mm</span>
											<input type="text" class="form-control" name="height" placeholder="高度" />
											<span class="input-group-addon" >mm</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button class="btn btn-primary" type="submit" id="template_save_button">确定</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->

			</form>
		</div><!-- /.modal -->

		<div class="modal fade" id="copyToNewTemplate">
			<form class="form-horizontal">
			<input type="hidden" name="template_id" id="template_id">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title">复制系统模版</h4>
						</div>
						<div class="modal-body">
							<div class="form-group">
								<label for="" class="col-lg-4 control-label">模版名称</label>
								<div class="col-lg-5">
									<input type="text" name="template_name" class="form-control" required="required" />
								</div>
								<span id="template_name_notice" class="glyphicon glyphicon-warning-sign"></span>
							</div>
						</div>
						<div class="modal-footer">
							<button class="btn btn-primary" type="button" onclick="docopy()" id="template_copy_button">确定</button>
							<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->

			</form>
		</div><!-- /.modal -->
	</div>
</div>
<script>
	var needKeepTab = '<?= $selftemplate ?>';
</script>
