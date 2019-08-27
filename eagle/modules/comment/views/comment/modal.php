<?php
use yii\helpers\Html;
use eagle\helpers\HtmlHelper;
$this->registerCssFile(\Yii::getAlias('@web') . "/css/comment-v2.css");
?>

<form action="">
<div class="content">
	<div class="box">
			<div class="comment-star">
				<span>评价星级</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<select id='sel'>
					<option value="5">五星</option>
					<option value="4">四星</option>
					<option value="3">三星</option>
					<option value="2">二星</option>
					<option value="1">一星</option>
				</select>
			</div>

			<div class="comment-content">
				<span>评价内容</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="content" value="one" checked onclick="onee()">自定义留言</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="content" value="two" onclick="two()">使用评价模板</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="content" value="three" onclick="three()">不留言</label>
			</div>

			<div class="kuang1" id='kuang1'>
				<textarea name="" id="one" cols="57" rows="12" style="resize: none;font-size:14px;" readonly>Thanks for your visit,we sincerely hope acquire your lasting support and will provide better commodities for you.
				</textarea>
				<p style='line-height:20px;'>
					小提示：不填不会留言
				</p>
			</div>

			<div class="kuang2" id='kuang2' style="overflow-y:auto;">
					<table cellpadding="5">
						<tr style='width:415px;height:60px;border:1px solid;font-size:14px'>
							<td><span style="margin-left:10px;padding-right:10px;text-align:center;"><input type="radio" name="word" value="1" checked></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Thanks for your visit, we sincerely hope acquire your lasting support and will provide better commodities for you.</td>
						</tr>
						<tr style='width:415px;height:60px;border:1px solid;font-size:14px'>
							<td><span style="margin-left:10px;padding-right:10px;text-align:center;"><input type="radio" name="word" value="2"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>We are pleased to know that you have received the goods smoothly and looking forward your lasting supports.</td>
						</tr>
						


					</table>

			</div>

			<div class="kuang3" id='kuang3'>

			</div>
	

			<div class="sure-button">
				<center>
				<input type="submit" value="确定" style="width:102px;height:36px;background:#04CE59;border:none;border-radius:5px;font-weight:400;color:#FFFAFA;">&nbsp;&nbsp;&nbsp;
				<button style="width:102px;height:36px;background:#EFEFEF;border:none;border-radius:5px;font-weight:400;color:#6B6B6B;" class="modal-close">取消</button>
				</center>
			</div>
			
	</div>		
</div>
</form>

<script>	
	var kuangone=document.getElementById('kuang1');
	var kuangtwo=document.getElementById('kuang2');
	var kuangthree=document.getElementById('kuang3');
	function onee(){
		if(kuangone.style.display=='none'){
			kuangone.style.display='block';
		}
		kuangtwo.style.display='none';
		kuangthree.style.display='none';
	}

	function two(){
		kuangone.style.display='none';
		kuangtwo.style.display='block';
		kuangthree.style.display='none';
	}

	function three(){
		kuangone.style.display='none';
		kuangtwo.style.display='none';
		kuangthree.style.display='block';
	}
</script>
		




