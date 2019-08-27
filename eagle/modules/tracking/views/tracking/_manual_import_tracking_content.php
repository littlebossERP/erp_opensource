<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
?>

<div class="panel" style="background-color: #f4f9fc;">
	<!-- Default panel contents -->
	<div class="panel-body" style="height: 380px;">

		<ul class="list-unstyled">
			<li>
				<div>
					<?php
					echo "<span style='color:red;font-size: 16px;' >自2018年9月30日起物流跟踪助手功能将直接由17track提供，届时小老板ERP将停止提供该功能服务。欢迎各位卖家继续在17track平台使用物流追踪服务。为您带来不便，敬请谅解。<a href='https://user.17track.net/zh-cn/register?gb=%23role%3D2' target='_blank'>点击注册17TRACK</a></span>";
					?>
					<br>
					<button type="button" class='btn-xs btn-transparent float_right' onClick="manual_import.list.manual_help()">
					<?= TranslateHelper::t('多列复制粘贴excel指引')?>
					<span class="egicon-question-sign-blue" style="vertical-align: middle;"></span>
					</button>
					<button type="button" class='btn-xs btn-transparent float_right' onClick="manual_import.list.excel_help()">
					<?= TranslateHelper::t('Excel格式上传指引')?>
					<span class="egicon-question-sign-blue" style="vertical-align: middle;"></span>
					</button>

				</div>
			</li>
			<li>
			<textarea id="txt_search_data"  class="form-control" data-percent-width="true"></textarea>
			</li>
			<li><div class="row">
					<div class="col-md-6">
					<button id="btn_query" qtipkey="tracker_track_button" class="btn btn-success btn-lg"
							data-loading-text="<?= TranslateHelper::t('查询中...')?>" >
							
							<span class="glyphicon glyphicon-search"  aria-hidden="true"></span>
							<?= TranslateHelper::t(' 查  询 ')?>
						</button>
							
					<input
							id="btn_clear" class="btn-transparent" type="button"
							value="<?= TranslateHelper::t('清空')?>" />
							
							
					<input id="btn_upload"
							class="btn btn-info" type="button"
							value="<?= TranslateHelper::t('Excel上传')?>" /> 
							
				
					<input
							id="btn_export" class="btn btn-info" type="button"
							value="<?= TranslateHelper::t('查询结果导出Excel')?>" />
							
							
					</div>
					
					
					<div id="div_progress" class="col-md-6 div_space_toggle">
						
						<div class="progress noBottom">
							<div
								class="progress-bar progress-bar-success progress-bar-striped active"
								style="width: 0%">
								<span></span>
							</div>
							<div
								class="progress-bar progress-bar-danger progress-bar-striped active"
								style="width: 0%">
								<span></span>
							</div>
							<div
								class="progress-bar progress-bar-primary progress-bar-striped active"
								style="width: 0%">
								<span></span>
							</div>
						</div>
						<p class="text-center progress_p">0/0</p>
					</div>
				</div>
				</li>
			
		</ul>


		
	</div>

	<!-- Table -->
	<div id="div_list_tracking_result"></div>
</div>









