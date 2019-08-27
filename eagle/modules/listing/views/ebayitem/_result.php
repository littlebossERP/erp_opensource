<?php 
use common\helpers\Helper_Array;
use yii\helpers\Html;
?>
<br/>
<div class=".container" style="width:98%;margin-left:1%;">
<?php if(isset($result['Errors'])){ 
	if(!isset($result['Errors'][0])){
	    $errors[0]=$result['Errors'];
	}else{
	    $errors=$result['Errors'];
	}}
?>
<?php if ($result['Ack']=='Success'||$result['Ack']=='Warning'):?>
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-1"><label>反馈结果</label></div>
	  	<div class="col-lg-11">
	  	<p class="text-left">
	   	<?php echo $result['Ack'];?>
		</p>
		</div>
	</div>
    <hr/>
   <div class="row">
	  <div class="col-lg-1"><p class="text-right">刊登费用</p></div>
	  <div class="col-lg-11">
	  	<?php if(isset($result['Fees'])):
	    $fee=Helper_Array::toHashmap($result['Fees']['Fee'],'Name','Fee');
		?>
		<table>
		<?php if(isset($result['getItem'])):?>
			<tr><th>Title</th><td><?php echo $result['getItem']['Title']?></td></tr>
			<tr><th>ItemID</th><td><?php echo '['.$result['ItemID'].']'?><a target="_blank" href="<?=$result['getItem']['ListingDetails']['ViewItemURL']?>"><?=$result['getItem']['ListingDetails']['ViewItemURL']?></a></td></tr>
		<?php endif;?>
			<tr>
		            <th>总费用</th>
		            <td><?php echo $result['ebayfee']['fee'].' '.$result['ebayfee']['currency'];?></td>
		    </tr>
		    <?php foreach ($fee as $k=>$f):?>
	        <?php if ($f!='0.0'&&$k!='ListingFee'):?>
	        <tr><th><?php echo $k?></th><td><?php echo $f.' '.$result['ebayfee']['currency']?></td></tr> 
	        <?php endif;?>
	    <?php endforeach;?>
		</table>
		<?php endif;?>
	  </div>
	</div>
	<?php if (isset($result['Errors'])):?>
	<div class="row">
	  <div class="col-lg-1"><p class="text-right">推荐修改</p></div>
	  <div class="col-lg-11">
		<?php  foreach($errors as $k=>$error):?>
		<table>
		    <tr><th width="100">ErrorCode</th><td><?php echo $error['ErrorCode']?></td></tr>
		    <tr><th width="100">SeverityCode</th><td><?php echo $error['SeverityCode']?></td></tr>
		    <tr><th>ShortMessage</th>
		    <td><?php echo $error['ShortMessage']?><br>
		    </td></tr>
		    <tr><th>LongMessage</th>
		    <td><pre><?php echo str_replace('>','&gt;',str_replace('<','&lt;',$error['LongMessage']));?> </pre><br>
		    </td></tr>
		</table><hr/>
		<?php endforeach;?>
	  </div>
	</div>
	<?php endif;?>
  </div>
</div>
<?php elseif($result['Ack']=='Failure'):?>
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-1"><label>反馈结果</label></div>
	  	<div class="col-lg-11">
	  	<p class="text-left">
	   	<?php echo $result['Ack'];?>
		</p>
		</div>
	</div>
    <hr/>
   <div class="row">
	  <div class="col-lg-1"><p class="text-right">错误信息</p></div>
	  <div class="col-lg-11">
	  	
		<?php  foreach($errors as $k=>$error):?>
		<table>
		    <tr><th width="100">ErrorCode</th><td><?php echo $error['ErrorCode']?></td></tr>
		    <tr><th width="100">SeverityCode</th><td><?php echo $error['SeverityCode']?></td></tr>
		    <tr><th>ShortMessage</th>
		    <td><?php echo $error['ShortMessage']?><br>
		    </td></tr>
		    <tr><th>LongMessage</th>
		    <td><?php echo str_replace('>','&gt;',str_replace('<','&lt;',$error['LongMessage']));?>
		    </td></tr>
		</table><hr/>
		<?php endforeach;?>
		
	  </div>
	</div>
  </div>
</div>
	
<?php else:?>

<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-1"><label>数据异常,请联系客服</label></div>
	  	<div class="col-lg-11">
	  	<p class="text-right">
	   	<?php echo $result['Ack'];?>
		</p>
		</div>
	</div>
    <hr/>
   <div class="row">
	  <div class="col-lg-1"><p class="text-right">错误信息</p></div>
	  <div class="col-lg-11">
		<table>
		<tr>
		<th>异常数据</th>
		<td><?php echo print_r($result,1)?></td>
		</tr>
		</table>
	  </div>
	</div>
  </div>
</div>
<?php endif;?>
</div>
<br>
<center>
<?php echo Html::button('关闭窗口',array('class'=>'iv-btn btn-success','onclick'=>"if(confirm('确定关闭这个窗口页面吗?')){window.close();}"))?>
</center>

