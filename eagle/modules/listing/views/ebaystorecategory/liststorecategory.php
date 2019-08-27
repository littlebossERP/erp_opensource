<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>
<style type="text/css"> 
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
}
.search>*{
	margin:5px;
}
.main-input{
	width:300px;
}
#TreeList { 
    background-color: #FFFFFF; 
    margin-top: 6px; 
    margin-right: 9px; 
    margin-bottom: 6px; 
    margin-left: 9px; 
    border: 1px solid #5d7b96; 
    padding-bottom: 6px; 
    padding-left: 6px; 
} 
#TreeList .mouseOver { 
    background-color: #FAF3E2; 
} 

#TreeList .ParentNode { 
    line-height: 21px; 
    height: 21px; 
    margin-top: 2px; 
/*    clear: both; */
} 

#TreeList .ChildNode { 
/*    background-image: url(../demoImgs/Sys_ModuleIcos.png); */
    background-position: 15px -58px; 
    padding-left: 39px; 
    line-height: 21px; 
    background-repeat: no-repeat; 
    border-top-width: 0px; 
    border-right-width: 0px; 
    border-bottom-width: 1px; 
    border-left-width: 0px; 
    border-top-style: dashed; 
    border-right-style: dashed; 
    border-bottom-style: dashed; 
    border-left-style: dashed; 
    border-top-color: #aabdce; 
    border-right-color: #aabdce; 
    border-bottom-color: #aabdce; 
    border-left-color: #aabdce; 
    cursor: default; 
/*    clear: both; */
    height: 21px; 
    color: #314f6a; 
} 

#TreeList .title { 
    float: left; 
} 
#TreeList .input { 
    font-size: 12px; 
    line-height: 16px; 
    color: #FFF; 
    height: 16px; 
    background-color: #3F6B8F; 
    width: 120px; 
    text-align: center; 
    margin-top: 1px; 
    border-top-width: 1px; 
    border-right-width: 1px; 
    border-bottom-width: 1px; 
    border-left-width: 1px; 
    border-top-style: solid; 
    border-right-style: solid; 
    border-bottom-style: solid; 
    border-left-style: solid; 
    border-top-color: #1F3547; 
    border-right-color: #FFF; 
    border-bottom-color: #FFF; 
    border-left-color: #1F3547; 
    float: left; 
} 
#TreeList .editBT { 
    float: left; 
    overflow: visible; 
} 
#TreeList .editBT .ok { 
/*    background-image: url(../demoImgs/Sys_ModuleIcos.png); */
    background-repeat: no-repeat; 
    background-position: 0px -89px; 
    height: 13px; 
    width: 12px; 
    float: left; 
    margin-left: 3px; 
    padding: 0px; 
    margin-top: 3px; 
    cursor: pointer; 
} 
#TreeList .editBT .cannel { 
/*    background-image: url(../demoImgs/Sys_ModuleIcos.png); */
    background-repeat: no-repeat; 
    background-position: 0px -120px; 
    float: left; 
    height: 13px; 
    width: 12px; 
    margin-left: 3px; 
    padding: 0px; 
    margin-top: 3px; 
    cursor: pointer; 
} 

#TreeList .editArea { 
    float: right; 
    color: #C3C3C3; 
    cursor: pointer; 
    margin-right: 6px; 
} 

#TreeList .editArea span { 
    margin: 2px; 
} 

#TreeList .editArea .mouseOver { 
    color: #BD4B00; 
    border-top-width: 1px; 
    border-right-width: 1px; 
    border-bottom-width: 1px; 
    border-left-width: 1px; 
    border-top-style: solid; 
    border-right-style: solid; 
    border-bottom-style: solid; 
    border-left-style: solid; 
    border-top-color: #C9925A; 
    border-right-color: #E6CFBB; 
    border-bottom-color: #E6CFBB; 
    border-left-color: #C9925A; 
    background-color: #FFFFFF; 
    margin: 0px; 
    padding: 1px; 
} 

#TreeList .ParentNode .title { 
    color: #314f6a; 
    cursor: pointer; 
/*    background-image: url(../demoImgs/Sys_ModuleIcos.png); */
    background-repeat: no-repeat; 
    padding-left: 39px; 
} 

#TreeList .ParentNode.show .title { 
    font-weight: bold; 
    background-position: 3px -27px; 
} 

#TreeList .ParentNode.hidden .title { 
    background-position: 3px 4px; 
} 

#TreeList .ParentNode .editArea { 
    color: #999;     
} 
#TreeList .ParentNode.show { 
    background-color: #d1dfeb; 
    border-top-width: 0px; 
    border-right-width: 0px; 
    border-bottom-width: 1px; 
    border-left-width: 1px; 
    border-top-style: solid; 
    border-right-style: solid; 
    border-bottom-style: solid; 
    border-left-style: solid; 
    border-top-color: #5d7b96; 
    border-right-color: #5d7b96; 
    border-bottom-color: #5d7b96; 
    border-left-color: #5d7b96; 
} 

#TreeList .ParentNode.hidden { 
    border-top-width: 0px; 
    border-right-width: 0px; 
    border-bottom-width: 1px; 
    border-left-width: 0px; 
    border-top-style: dashed; 
    border-right-style: dashed; 
    border-bottom-style: dashed; 
    border-left-style: dashed; 
    border-top-color: #aabdce; 
    border-right-color: #aabdce; 
    border-bottom-color: #aabdce; 
    border-left-color: #aabdce; 
} 

#TreeList .Row { 
/*    clear: both; */
    margin-left: 24px; 
/*    background-image: url(../demoImgs/Sys_ModuleIcos2.png); */
    background-repeat: repeat-y; 
    background-position: 7px 0px; 
} 
</style> 
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'账户信息']);?>
<div class="content-wrapper" >
	<div class="mianbaoxie">
		<span></span>账号信息列表
	</div>
<form action="" method="post" name="a" id="a">
<div class="search">
<p class="title">eBay账号</p>
<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$ebayselleruserid,['prompt'=>'请选择账号','id'=>'selleruserid','class'=>'iv-input main-input'])?><br>
<?=Html::submitButton('展示eBay店铺信息',['class'=>'iv-btn btn-search'])?>
<?=Html::button('读取eBay店铺信息',['class'=>'iv-btn','onclick'=>'updateStoreCategory();'])?>
</div>
</form>
<?=Html::hiddenInput('selleruserid_tmp',@$_REQUEST['selleruserid'],['id'=>'selleruserid_tmp'])?>
<!-- 显示店铺类目的主信息 -->
<?php if (count($ct)):?>
<div id="TreeList">
	<?php foreach ($ct as $c):?>
	  <?php if ($c['2']==''):?>
	  <div class="ParentNode show"> 
	      <div class="title"><?=$c['1']['category_name']?></div> 
	      <div class="editBT"></div> 
	      <div class="editArea"><span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'edit')">编辑</span>|<span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'addlevel')">添加同级目录</span>|<span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'addsub')">添加下级目录</span>|<span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'del')">删除</span></div> 
      </div>
      <?php else:?>
      <div class="Row">
	      <div class="ChildNode"> 
	        <div class="title"><?=$c['2'].$c['1']['category_name']?></div> 
	        <div class="editBT"></div> 
	        <div class="editArea"><span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'edit')">编辑</span>|<span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'addlevel')">添加同级目录</span>|<span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'addsub')">添加下级目录</span>|<span cid="<?=$c['1']['categoryid']?>" onclick="mod(this,'del')">删除</span></div> 
	      </div>
      </div>
      <?php endif;?>
    <?php endforeach;?>
</div> 
<?php endif;?>
</div>
</div>

<!-- 设置类目名称的modal -->
<!-- 模态框（Modal） -->
<div class="modal fade" id="categorysetModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<script>
function updateStoreCategory(){
	if($('#selleruserid').val()==''){
		bootbox.alert('请选择账号');
		return false;
	}
	$.showLoading();
	$.post("<?=Url::to(['/listing/ebaystorecategory/updatecategory']) ?>",{selleruserid:$('#selleruserid').val()},
	  function(dataobj){
		var data;
		data=eval('('+dataobj+')');
		$.hideLoading();
		if(data.Ack == 'Success'||data.Ack == 'Warning'){
			bootbox.alert('同步成功');
		}else{
			bootbox.alert('操作失败:'+data.Errors.LongMessage);
		}
	  });
}

//操作修改类目名
function mod(obj,type){
	if(type == 'del'){
		$.showLoading();
		$.post("<?=Url::to(['/listing/ebaystorecategory/dodel']) ?>",{selleruserid:$('#selleruserid').val(),cid:$(obj).attr('cid')},
		function(result){
		$.hideLoading();
		$('#categorysetModal').modal('hide');
		if(result == 'Success'||result == 'Warning'){
			bootbox.alert('同步成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败:'+result);
		}
		});
	}else{
		var Url='<?=Url::to(['/listing/ebaystorecategory/mod'])?>';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : {selleruserid : $('#selleruserid_tmp').val(),categoryid:$(obj).attr('cid'),type:type},
			url: Url,
	        success:function(response) {
	        	$('#categorysetModal .modal-content').html(response);
	        	$('#categorysetModal').modal('show');
	        }
	    });
	}
}

//接口操作的调用
function doaction(type){
	$.showLoading();
	$.post("<?=Url::to(['/listing/ebaystorecategory/domod']) ?>",{selleruserid:$('#selleruserid').val(),type:type,name:$('#category_tmp').val(),cid:$('#categoryid_tmp').val()},
	function(result){
	$.hideLoading();
	$('#categorysetModal').modal('hide');
	if(result == 'Success'||result == 'Warning'){
		bootbox.alert('同步成功');
		window.location.reload();
	}else{
		bootbox.alert('操作失败:'+result);
	}
	});
}
</script>