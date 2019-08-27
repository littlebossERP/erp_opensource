<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\assistant\helpers\HtmlHelper;
// $this->registerCssFile('/css/select2.min.css');
// $this->registerJsFile('/js/lib/select2.min.js');
?>
<style>
.dp-template p{
	width:calc(100% - 20px);
}
.dp-template li{
	list-style: none;
}
</style>
<form action="create" method="post" ajax-form="normal" ajax-reload="true" class="form-horizontal container-fluid">
	<div  class="panel panel-default">
		<div class="panel-heading">
			<h3><?= $rule->rule_id?'编辑':'新增' ?>规则
			<?php if(empty($rule->rule_id)){
				echo '<span class="text-danger" style="color:red;margin:10px;">（由于巴西Boleto付款匹配不到，建议手动催付，系统默认不勾选巴西，如需请手动勾选。）</span>';
			}?>
			</h3>
			<input type="hidden" name="rule_id" value="<?= $rule->rule_id ?>" />
		</div>
		<div class="panel-body">
			<div id="due_setting" class="form-group row dp-template">
				<label class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
					催款设置
				</label>
				<div>
					<table>
						<tr>
							<td colspan="2">
								<input name="onecheck" type="checkbox" value="1" id="reminder1" checked data-disabled="#reminder2"  lb-show="#coll_1" time-style="#oneTime" ><label>第一次催款</label>
							</td>
						</tr>
						<tbody id="coll_1">
							<tr>
								<td  style="width:60px;">催款时间</td>
								<td class="input-group">
									<input id="oneTime" type="number" name="timeout1"  class="form-control" value="<?= $rule->timeout/60 ?>" min="30" />
									<span class="input-group-addon">分钟</span>
								</td>
								<td>
									<span style="color: #ff0000">下单后n分钟没付款后催款</span>
								</td>
							</tr>
							<tr>
								<td  style="width:60px;">催款模板</td>
								<td><?= HtmlHelper::SelectTemplate(['class'=>'form-control','name'=>'selModel_1','tp-show'=>'p[name=Model]'],$rule->message_content)?></td>
								<td>
									<label id="showTpl"  class="btn btn-default">预览</label>
									<a class="btn btn-default" href="/assistant/template/list">添加自定义模板</a>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border: 1px solid"><?= HtmlHelper::showTemplate()?></td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
				</div>
				<?php /**?>
				<div>
					<table>
						<tr>
							<td colspan="2">
								<input name="twocheck"  type="checkbox" value="2" id="reminder2" data-disabled="#reminder3"  lb-show="#coll_2"  time-style="#twoTime" <?php if($rule->message_content2>0){
									echo 'checked="checked;"';
								} ?>><label>第二次催款</label>
							</td>
						</tr>
						<tbody id="coll_2"  style="<?php if($rule->message_content2>0){
							echo 'display:block;';
						}else{
							echo 'display:none';
						}?>">
						<tr>
							<td  style="width:60px;">催款时间</td>
							<td class="input-group">
								<input id="twoTime" type="number" name="timeout2"  class="form-control" value="<?= $rule->timeout2/3600 ?>"  />
								<span class="input-group-addon">小时</span>
							</td>
							<td>
								<span style="color: #ff0000">下单后n小时没付款后催款</span>
							</td>
						</tr>
						<tr>
							<td  style="width:60px;">催款模板</td>
							<td><?= HtmlHelper::SelectTemplate(['class'=>'form-control','name'=>'selModel_2','tp-show'=>'p[name=Model]'],$rule->message_content2)?></td>
							<td>
								<label id="showTpl" class="btn btn-default" >预览</label>
								<a class="btn btn-default" href="/assistant/template/list">添加自定义模板</a>
							</td>
						</tr>
						<tr>
							<td colspan="3" style="border: 1px solid"><?= HtmlHelper::showTemplate()?></td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php **/?>
				<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
				</div>
				<?php /**?>
				<div>
					<table>
						<tr>
							<td colspan="2">
								<input name="threecheck"  type="checkbox" value="3" id="reminder3"     lb-show="#coll_3" time-style="#threeTime"<?php if($rule->message_content3>0){
									echo 'checked="checked;"';
								}
								if($rule->message_content2<=0){
									echo 'disabled';
								} ?>><label>第三次催款</label>
							</td>
						</tr>
						<tbody id="coll_3" style="<?php if($rule->message_content3>0){
							echo 'display:block;';
						}else{
							echo 'display:none';
						}?>">
						<tr>
							<td  style="width:60px;">催款时间</td>
							<td class="input-group">
								<input id="threeTime" type="number" name="timeout3"  class="form-control" value="<?= $rule->timeout3/3600 ?>"  />
								<span class="input-group-addon">小时</span>
							</td>
							<td>
								<span style="color: #ff0000">下单后n小时没付款后催款</span>
							</td>
						</tr>
						<tr>
							<td  style="width:60px;">催款模板</td>
							<td><?= HtmlHelper::SelectTemplate(['class'=>'form-control','name'=>'selModel_3','tp-show'=>'p[name=Model]'],$rule->message_content3)?></td>
							<td>
								<label id="showTpl" class="btn btn-default">预览</label>
								<a class="btn btn-default" href="/assistant/template/list">添加自定义模板</a>
							</td>
						</tr>
						<tr>
							<td colspan="3" style="border: 1px solid"><?= HtmlHelper::showTemplate()?></td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php **/?>
				<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">
				</div>
			</div>

			<?php /**?>
			<div class="form-group row">
				<label for="" class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">催款范围<?= HtmlHelper::tips('多个未付款订单只催款一单，如果有已付款订单则不催款') ?></label>
				<div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
					<div class="input-group">
						<input type="number" name="expiretime"  class="form-control" value="<?= $rule->expire_time/3600 ?>" autofocus min="2" />
						<span class="input-group-addon">小时</span>
					</div>
				</div>
				<div>
					<span style="color: #ff0000">内同买家不重复催付（无成功付款订单只催一单）</span>
				</div>
			</div>
			<?php **/?>
			<input type="hidden" name="expiretime"  class="form-control" value="2" />
			<div class="form-group row">
				<label for="" class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">订单金额<?= HtmlHelper::tips('订单金额默认留空，表示订单金额不限。例如设置A-B，则订单范围为 >= A, < B') ?></label>
				<div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 form-inline">
					<div class="input-group" style="width:45%;">
						<span class="input-group-addon">$</span>
						<input type="number" name="min_money" class="form-control" value="<?= $rule->min_money ?>" min="0" dynamic-max="max_money" />
					</div> - 
					<div class="input-group" style="width:45%;">
						<span class="input-group-addon">$</span>
						<input type="number" name="max_money" class="form-control" value="<?= $rule->max_money ?>" dynamic-min="min_money" />
					</div>
				</div>
			</div>
			<div class="form-group row">
				<label for="" class="col-xs-2 col-sm-2 col-md-2 col-lg-2 control-label">匹配国家<?= HtmlHelper::tips('选择匹配国家，如果订单来源于规则中所列出的国家，则此规则自动匹配此订单') ?></label>
				<div class="col-xs-10 col-sm-10 col-md-10 col-lg-10">
					<?= HtmlHelper::selCountries('countries',explode(',',$rule->country));?>
				</div>
			</div>
		</div>
		<div class="panel-footer text-right">
			<!-- <input type="button" class="btn btn-success" value="预览" /> -->
			<input type="submit" value="保存" class="btn btn-primary" />
			<button class="btn btn-default" data-dismiss="modal">取消</button>
		</div>
	</div>
</form>