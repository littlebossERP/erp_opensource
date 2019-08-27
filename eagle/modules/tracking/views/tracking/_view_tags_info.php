
<form id="frm_tag" name="frm_tag" method="Get">

<div class="form-group">
<label class="control-label col-sm-11">请从已存在的标签中 勾选该物流号要使用的标签，也可以取消勾选来去掉标签。<br>
也可以新建标签，使用自定义的 标签样式 以及 文字。

</label>
<div class="col-sm-1">
<a class="btn btn-primary" onclick="ListTracking.addTagHtml()">
添加
</a>
</div>
</div>
<br style="clear: both">
<div  id="div_tag_list">




</form>
</div>

<script type="text/javascript">

ListTracking.TagList=<?= json_encode($TagList['all_tag'])?>;
ListTracking.TrackingTagList=<?= json_encode($TagList['all_select_tag_id'])?>;
ListTracking.TagClassList=<?= json_encode($classList)?>;

ListTracking.fillTagData();
ListTracking.setSelectTag();
</script>
<style>
.form-group.input-group{
  float: left;
  width: 25%;
  vertical-align: middle;
  padding-right: 10px;
  padding-left: 10px;
	
}


div#div_tag_list{
	padding-top: 5px;
}

.checkboxbtn{
  display: inline-block;
  height: 30px;
  text-align: center;
  vertical-align: middle;
  -ms-touch-action: manipulation;
  cursor: pointer;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  background-image: none;
  border: 1px solid transparent;
  color: #333;
  background-color: #fff;
  border-color: #ccc;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}

.input-group-addon > label{
	margin-bottom:0px;
}

.input-group-btn > button{
	padding: 0px;
	height: 30px;
}

</style>
