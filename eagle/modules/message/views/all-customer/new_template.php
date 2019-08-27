<?php 
$this->registerJs("$.initQtip()", \yii\web\View::POS_READY);
?>
<div class="row">
	
		<div class="col-sm-12" name="station_letter" style="display: block;"><style>
<!--

-->
#send-station-letter label{
font-family: SimSun;
	font-size: 12px;
	  white-space: nowrap;
	  margin-top: 5px;
}

.letter_tittle{
  color: #f0ad4e;
  font-size: 16px;
  line-height: 29px;
	font-family: SimSun;
}

.form-group>button{
	margin: 5px 0 10px 0;
}

.panel-body {
  padding: 0px 0px 0px 0px;
}

.form-group{
margin-bottom: 0px;

}

.form-horizontal .form-group {
  margin-right: 0px;
  margin-left: 0px;
}

.order_info .modal-body , .xlbox .modal-body{
	max-height: 600px;
}

#preview_track_no{
	margin-right: 15px;
	float:right;
	display:none;
}
</style>
<div class="panel">
		<!-- Default panel contents -->
	<div class="panel-body">
		<form class="form-horizontal" role="form" id="send-station-letter">
		    <input type="text" style="display: none" id="template_id" name="template_id" value="<?php echo !empty($template_id)?$template_id:"0";?>">
		    <input type="text" style="display: none" id="template_detail_id" name="template_detail_id" value="<?php echo !empty($one_record)?$one_record['id']:"0";?>">
		    <input type="text" style="display: none" id="isupdate" name="isupdate" value="<?php echo !empty($isupdate)?$isupdate:null;?>">
		    <?php if(!empty($template_id)&&empty($isupdate)):?>
		    <input type="text" style="display: none" id="letter_template_language" name="letter_template_language" value="<?php echo !empty($select_language)?$select_language:null;?>">
		    <?php endif;?>
			<div name="div_letter_template_used" class="form-group" style="display: block;" >
				<label for="letter_template_used" class="col-sm-1 control-label" qtipkey="message_template">使用模板</label>
				<div class="col-sm-5">
				<?php if(empty($one_record)&&empty($isupdate)){//isupdate区分是否新增其他语言?>
					<select name="letter_template_used" class="form-control" disabled="disabled">
						<option value="-1">新建模版</option>
					</select>
				<?php }else{?>
				    <input <?php echo !empty($isupdate)?"disabled='disabled'":null?>type="text" class="form-control" id="letter_template_used" name="letter_template_used" value="<?php echo $title;?>">
				<?php }?>
				</div>
				<div id="div_new_template" <?php echo !empty($one_record)?"style='display:none'":(!empty($isupdate)?"style='display:none'":null);?>><label for="letter_template_name" class="col-sm-1 control-label">新模板名称</label><div class="col-sm-5"><input type="text" class="form-control" id="letter_template_name" name="letter_template_name" value=""></div></div>
			</div>
			<div name="div_manual_template" class="form-group" style="display: block;">
				<label for="letter_theme" class="col-sm-1 control-label" qtipkey="message_title">标 题</label>
				<div class="col-sm-11">
					<input type="text" class="form-control" id="subject" name="subject" value="<?php echo !empty($one_record)?$one_record['subject']:null;?>">
				</div>
			</div>

			<div name="div_manual_template" class="form-group" style="display: block;">
				<label for="letter_template_variance" class="col-sm-1 control-label " qtipkey="message_variables">可用变量</label>
				<div class="col-sm-5">
					<select name="letter_template_variance" class="form-control">
						<option value="[收件人名称]">收件人名称</option>
						<option value="[收件人国家]">收件人国家</option>
						<option value="[收件人地址，包含城市]">收件人地址，包含城市</option>
						<option value="[收件人邮编]">收件人邮编</option>
						<option value="[收件人电话]">收件人电话</option>
						<option value="[平台订单号]">平台订单号</option>
						<option value="[订单金额]">订单金额</option>
						<option value="[订单物品列表(商品sku，名称，数量，单价)]">订单物品列表(商品sku，名称，数量，单价)</option>
						<option value="[包裹物流号]">包裹物流号</option>
						<option value="[包裹递送物流商]">包裹递送物流商</option>
						<option value="[买家查看包裹追踪及商品推荐链接]">买家查看包裹追踪及商品推荐链接</option>
					</select>
				</div>
				<button type="button" class="btn btn-default btn-sm " style="float:left;" onclick="Customertemplate.addLetterVariance()" qtipkey="msg_insert_var">
				<span class="glyphicon glyphicon-plus" aria-hidden="true" ></span>插入变量</button>
				<div class="col-sm-2">
				<?php if(empty($isupdate)){//新增语言模版的时候过滤?>
				    <select name="letter_template_language" class="form-control" <?php echo (!empty($template_id)&&empty($isupdate))?"disabled='disabled'":null?>>
				        <?php
				        if(!empty($language)){ 
				            foreach ($language as $key => $val):?>
				            <option value="<?php echo $key?>" 
				            <?php if(!empty($select_language)){
				                echo $key==$select_language?"selected='selected'":null;}?>><?php echo $val;?>
				            </option>
				        <?php endforeach;}?>
				    </select>
				<?php }else{?>
				    <select name="letter_template_language" class="form-control" <?php echo (!empty($template_id)&&empty($isupdate))?"disabled='disabled'":null?>>
				        <?php 
				        if(!empty($language)){  
				            foreach ($language as $key => $val):?>
				            <option value="<?php echo $key?>"><?php echo $val;?></option>
				        <?php endforeach;}?>
				    </select>
				<?php }?>
				</div>
		    </div>

			<div name="div_manual_template" class="form-group div_template" style="display: block;">
				<div class="col-sm-1"></div>
				<div class="col-sm-11">
					<textarea class="form-control" name="letter_template" id="letter_template"><?php echo !empty($one_record)?$one_record['content']:null;?></textarea>
				</div>
			</div>
		</form>
	</div>
</div>
</div>
	</div>