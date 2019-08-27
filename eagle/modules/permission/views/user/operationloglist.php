<?php
use yii\helpers\Html;
use yii\helpers\Url;

$tmp_js_version = '1.01';
$baseUrl = \yii::$app->urlManager->baseUrl;

$this->registerJsFile($baseUrl."/js/project/permission/operationloglist.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("permission.operationloglist.init();" , \yii\web\View::POS_READY);

$this->title = "操作日志";
?>

<style>
#div_feedback_tab{
	font-size: 13px;
}
#div_feedback_tab .table th, #div_feedback_tab .table th > a{
    background-color: #eee;
}
#div_feedback_tab .table > tbody > tr:hover {
    background-color: #f5f5f5;
}
#div_feedback_tab .table tr.striped-row {
    background-color: #fff;
}
#div_feedback_tab .table td{
    border: 1px solid #ddd !important;
}
#div_feedback_tab th, #div_feedback_tab th{
	text-align: center !important;
}
.div_choose{
	border: 1px solid #ccc;
	width:90%;
	padding:5px 0 5px 20px;
	float:left;
}
.div_choose > span{
	font-size: 15px; 
	line-height: 24px;
	float:left;
}
.lb_check_node{
	margin-left: 0;
	margin-right: 20px; 
	line-height: 25px;
	font-size: 14px;
}
.lb_choose_title{
	float: left;
	line-height: 25px;
	font-size: 14px;
	margin: 0px 5px;
}
</style>

<!-- 左侧标签快捷区域 -->
<?php 
    echo $this->render('//layouts/new/left_menu_2',[
    'menu'=>@$menu,
    'active'=>@$active
]);
?>

<form  class="form-horizontal" action="/permission/user/operation-log" method=get style="float:left;width:100%;">
	<div style="width: 100%; float:left;">
	    <div class="div-input-group" style="float: left;margin-left:20px; margin-bottom:10px;">
	    	<LABEL class="lb_choose_title">日期：</LABEL>
			<input type="datetime-local" class="eagle-form-control" name="startdate" style="margin:0px;height:28px;float:left; margin-right:0px;"
				value="<?= empty($param['startdate']) ? date("Y-m-d", time() - 3600 * 24).'T00:00' : $param['startdate'] ?>" min="2017-01-01T00:00"/>
			<LABEL class="lb_choose_title"> ~ </LABEL>
			<input type="datetime-local" class="eagle-form-control" name="enddate" style="margin:0px;height:28px;float:left; margin-right:20px;"
				value="<?= empty($param['enddate']) ? date("Y-m-d",  time() + 3600 * 24).'T00:00' : $param['enddate'] ?>" min="2017-01-01T00:00" />
			
			<LABEL class="lb_choose_title">模糊搜索：</LABEL>
	        <SELECT name="search_type" value="" class="eagle-form-control" style="float:left; margin:0px; ">
	  			<OPTION value="operator_content" <?=(!empty($param['search_type']) && $param['search_type']=="operator_content")?"selected":"" ?>>操作内容</OPTION>
	  			<OPTION value="login_ip" <?=(!empty($param['search_type']) && $param['search_type']=="login_ip")?"selected":"" ?>>登陆IP</OPTION>
	  		</SELECT>
			<input name='search_txt' type="text" class="eagle-form-control" style="width:200px;margin:0px 20px 0px 0px; height:28px;float:left;"
				placeholder="搜索内容"
				value="<?= (empty($param['search_txt'])?'':$param['search_txt'])?>"/>
	
			<button type="submit" class="iv-btn btn-search btn-spacing-middle">搜索</button>
		</div>
		<div class="div_choose">
		    <span>用户名：</span>
		    <div style="width: 90%; float:left;">
			    	<label class="lb_check_node"><input type="checkbox" class="select_user_log" select_type="all" <?= !empty($param['select_user_arr']) && in_array('all', $param['select_user_arr']) ? 'checked' : ''  ?>>全部</label>
		  		    <?php if(!empty($user_list)){
		  		    foreach($user_list as $val){
						if(!empty($uid) && $uid != $val['uid']){
							continue;
						}
					?>	
		      		    <label style="margin-left:0;margin-right:20px; line-height: 25px; ">
		    				<input type="checkbox" class="select_user_log" <?= !empty($param['select_user_arr']) && (in_array('all', $param['select_user_arr']) || in_array($val['uid'], $param['select_user_arr'])) ? 'checked' : ''  ?> value="<?= $val['uid'] ?>">
		    				<?= $val['username'] ?>
		    			</label>
					<?php }} ?>
			</div>
		</div>
		<div class="div_choose" style="border-top:none;">
		    <span>操作模块：</span>
		    <div style="width: 90%; float:left;">
		    	<label class="lb_check_node" ><input type="checkbox" class="select_module_log" select_type="all" <?= !empty($param['select_module_arr']) && in_array('all', $param['select_module_arr']) ? 'checked' : ''  ?>>全部</label>
		  		    <?php if(!empty($module_list)){
		  		    	foreach($module_list as $key => $val){?>	
		      		    <label class="lb_check_node" >
		    				<input type="checkbox" class="select_module_log" <?= !empty($param['select_module_arr']) && (in_array('all', $param['select_module_arr']) || in_array($key, $param['select_module_arr'])) ? 'checked' : ''  ?> value="<?= $key ?>">
		    				<?= $val ?>
		    			</label>
					<?php }} ?>
			</div>
		</div>
		<input type="hidden" name="select_user_strs" value="<?= isset($param['select_user_strs']) ? $param['select_user_strs'] : 'all' ?>" />
		<input type="hidden" name="select_module_strs" value="<?= isset($param['select_module_strs']) ? $param['select_module_strs'] : 'all' ?>" />
	</div>
</form>

<!-- 功能按钮  -->

<div id="div_feedback_tab">
	<TABLE class="table table-striped table-bordered table-hover">
		<TR>
			<TH style="width: 100px;">用户名</TH>
			<TH style="width: 80px">操作模块</TH>
			<TH style="width: 100px;">操作时间</TH>
			<TH style="width: 60px;">IP</TH>
			<TH style="width: 300px">操作内容</TH>
		</TR>
		<?php foreach($list as $key => $item){?>
		<TR>
			<TD style="text-align: center;"><?= $item['operator_name'] ?></TD>
			<TD style="text-align: center;"><?= $item['log_module'] ?></TD>
			<TD style="text-align: center;"><?= $item['create_time'] ?></TD>
			<TD style="text-align: center;"><?= $item['login_ip'] ?></TD>
			<TD ><?= $item['operator_content'] ?></TD>
		</TR>
		<?php }?>
	</TABLE>

	<div id="pager-group" >
		<div class="btn-group" style="width: 49.6%;text-align: right;">
			<?=\yii\widgets\LinkPager::widget(['pagination' => $page,'options'=>['class'=>'pagination']]);?>
		</div>
		<?= \eagle\widgets\SizePager::widget(['pagination'=>$page , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	</div>

</div>

