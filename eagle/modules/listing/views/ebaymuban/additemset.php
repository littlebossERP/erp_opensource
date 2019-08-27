<?php
use yii\helpers\Html;
?>
<style>
.row>*{
	margin:5px 0px;
}
p{
	margin:10px 0px;
}
button{
	width:80px;
	margin:5px 40px;
}
tr{
	background-color:rgb(249,249,249);
	padding:5px;
	border:2px #fff solid;
}
</style>
<script>
function check(){
	if(parseInt($('#loop_perday').val()) > parseInt($('#loop').val())){
		bootbox.alert('每天上架次数不能大于总次数');
		return false;
	}
	if($('#day_start_date').val().length==0){
		bootbox.alert('请填写刊登时间');
		return false;
	}
	document.a.submit();
	return true;
}
</script>
<?php if(@$_REQUEST['error']=='timeout'){echo '<script>alert("开始时间小于当前时间");</script>';}?>
<br/>
<div class=".container" style="width:98%;margin-left:1%;">
<form action="" method="post" name="a" id="a">
<?=Html::hiddenInput('mids',@$_REQUEST['mubanid'])?>
<div class="panel panel-default">
  <div class="panel-body">
  	<div class="row">
	  <div class="col-lg-2"><label>刊登定时设置</label></div>
	  	<div class="col-lg-10">
	  	<p class="text-right">
		</p>
		</div>
	</div>
    <hr/>
   <div class="row">
   		<div class="col-lg-2"></div>
   		<div class="col-lg-8">
	  	<p>刊登时区</p>
		<?php if ($eas->isNewRecord){$eas->gmt=8;}?>
		<?php $gmtarr=array(
		        	'-12'=>'(GMT-12:00)Eniwetok, Kwajalein',
					'-11'=>'(GMT-11:00)Midway Island, Samoa',
					'-10'=>'(GMT-10:00)Hawaii',
					'-9'=>'(GMT-09:00)Alaska',
					'-8'=>'(GMT-08:00)Pacific Time (US & Canada), Tijuana',
					'-7'=>'(GMT-07:00)Mountain Time (US & Canada), Arizona',
					'-6'=>'(GMT-06:00)Central Time (US & Canada), Mexico City',
					'-5'=>'(GMT-05:00)Eastern Time (US & Canada), Bogota, Lima, Quito',
					'-4'=>'(GMT-04:00)Atlantic Time (Canada), Caracas, La Paz',
					'-3'=>'(GMT-03:00)Brassila, Buenos Aires, Georgetown, Falkland Is',
					'-2'=>'(GMT-02:00)Mid-Atlantic, Ascension Is., St. Helena',
					'-1'=>'(GMT-01:00)Azores, Cape Verde Islands',
					'0'=>'(GMT+00:00)Casablanca,Dublin, Edinburgh, London, Lisbon, Monrovia',
					'1'=>'(GMT+01:00)Amsterdam, Berlin, Brussels, Madrid, Paris, Rome',
					'2'=>'(GMT+02:00)Cairo, Helsinki, Kaliningrad, South Africa',
					'3'=>'(GMT+03:00)Baghdad, Riyadh, Moscow, Nairobi',
					'4'=>'(GMT+04:00)Abu Dhabi, Baku, Muscat, Tbilisi',
					'5'=>'(GMT+05:00)Ekaterinburg, Islamabad, Karachi, Tashkent',
					'6'=>'(GMT+06:00)Almaty, Colombo, Dhaka, Novosibirsk',
					'7'=>'(GMT+07:00)Bangkok, Hanoi, Jakarta',
					'8'=>'(GMT+08:00)Beijing, Hong Kong, Perth, Singapore, Taipei',
					'9'=>'(GMT+09:00)Osaka, Sapporo, Seoul, Tokyo, Yakutsk',
					'10'=>'(GMT+10:00)Canberra, Guam, Melbourne, Sydney, Vladivostok',
					'11'=>'(GMT+11:00)Magadan, New Caledonia, Solomon Islands',
					'12'=>'(GMT+12:00)Auckland, Wellington, Fiji, Marshall Island',
		        	);?>
		<?=Html::dropDownList('gmt',$eas->gmt,$gmtarr,['class'=>'iv-input'])?>
	  </div>
	  <div class="col-lg-2"></div>
	</div>
	<div class="row">
	  <div class="col-lg-2"></div>
	  <div class="col-lg-8">
	  <p>刊登时间</p>
		<?=Html::input('date','day_start_date',$eas->day_start_date2,['id'=>'day_start_date','class'=>'iv-input'])?>
		&nbsp;&nbsp;&nbsp;
		<?php 
		    $hours=array_merge(array('00','01','02','03','04','05','06','07','08','09'),range(10,23));
		    echo Html::dropDownList('day_start_time_hour',substr($eas->day_start_time,0,2),array_combine($hours,$hours),['class'=>'iv-input'])?>时
		&nbsp;&nbsp;
		<?php 
		    $minutes=array_merge(array('00','01','02','03','04','05','06','07','08','09'),range(10,59));
		    echo Html::dropDownList('day_start_time_minute',substr($eas->day_start_time,2,2),array_combine($minutes,$minutes),['class'=>'iv-input'])?>分
		<?php if (!isset($_REQUEST['timerid'])):?>  
		<?Html::hiddenInput('timerid',@$_REQUEST['timerid'])?>  
		    <br>
		<?php $day=array('7'=>'周日','1'=>'周一','2'=>'周二','3'=>'周三','4'=>'周四','5'=>'周五','6'=>'周六')?>
		<?=Html::checkboxList('whatday',array(7,1,2,3,4,5,6),$day)?><br>
		<?php endif;?>
	  </div>
	  <div class="col-lg-2"></div>
	</div>
	<?php if (!isset($_REQUEST['timerid'])){?>
	<div class="row">
	  <div class="col-lg-2"></div>
	  <div class="col-lg-8">
	  <p>刊登范本限制</p>
	  范本刊登总次数为<?=Html::textInput('loop','1',array('size'=>3,'id'=>'loop','class'=>'iv-input'))?>次,每<?=Html::textInput('loop_per','1',array('size'=>3,'id'=>'loop_per','class'=>'iv-input'))?>天上<?=Html::textInput('loop_perday','1',array('size'=>3,'id'=>'loop_perday','class'=>'iv-input'))?>次
	  </div>
	  <div class="col-lg-2"></div>
	</div>
	<div class="row">
	  <div class="col-lg-2"></div>
	  <div class="col-lg-8">
	  <p>刊登范本频率</p>
	  	相同刊登范本间隔<?=Html::textInput('time_split_samesku','30',array('size'=>8,'class'=>'iv-input'))?>
		<select name="time_split_samesku_unit" class="iv-input">
			<option value="1">分钟</option>
			<option value="60">小时</option>
		</select>
		<br>
		不同刊登范本间隔<?=Html::textInput('time_split','30',array('size'=>8,'class'=>'iv-input'))?>
		<select name="time_split_unit" class="iv-input">
			<option value="1">分钟</option>
			<option value="60">小时</option>
		</select>
	  </div>
	  <div class="col-lg-2"></div>
	</div>
	<?php }?>
	<div class="row">
	  <div class="col-lg-2"></div>
	  <div class="col-lg-8">
	  	<p>刊登范本</p>
	  	<table>
		<?php foreach ($mubans as $d):?>
		<tr><td><img src="<?php echo $d->mainimg?>" width="60px" height="60px"></td>
		<td><b><?php echo $d->itemtitle?></b>
		<?=Html::hiddenInput("itemtitle[$d->mubanid]",$d->itemtitle)?>
		</td></tr>
		<?php endforeach;?>
		</table>
	  </div>
	  <div class="col-lg-2"></div>
	</div>
	<div class="row">
	  <div class="col-lg-2"></div>
	  <div class="col-lg-8">
	 <?php echo Html::Button('确认',['onclick'=>'javascript:check();','class'=>'iv-btn btn-search'])?>
	 <?php echo Html::Button('取消',['onclick'=>'javascript:window.close();','class'=>'iv-btn btn-default'])?>
	  </div>
	  <div class="col-lg-2"></div>
	</div>
  </div>
</div>
</form>
</div>