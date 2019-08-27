<?php
use yii\helpers\Html;
use yii\helpers\Url;


$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<?php 

?>
<style>
.cs_contacts ul li{
	list-style: none;
    line-height: 28px;
	width:100%;
	padding:0px 5px;
}
.quick_faq ul li{
	font-size: 14px;
	list-style: none;
    border-bottom: 1px solid #ccc;
    line-height: 28px;
	width:100%;
	padding:0px 5px;
}

.quick_faq ul li a{
	width:100%;
	display: inline-block;
}
</style>
<div class="cs_contacts" style="border:1px solid;margin:5px 0px;">
	<h5 class="preview">客户服务</h5>
	<ul style="font-size: 14px;">
		<li><span style="font-weight:600;">ERP讨论群 ：</span><span>317561579(满员)</span></li>
		<li><span style="font-weight:600;">ERP讨论②群 ：</span><span>376681462(满员)</span></li>
		<li><span style="font-weight:600;">ERP讨论③群 ：</span><span>866409466</span></li>
		<li><span style="font-weight:600;">PriceMinister群： </span><span>228590063</span></li>
		<li><span style="font-weight:600;">Cdiscount群 ：</span><span>481382349</span></li>
		<li><span style="font-weight:600;">Linio群 ：</span><span>516810644</span></li>
		<li><span style="font-weight:600;">云建站群 ：</span><span>418630743</span></li>
	
	</ul>
</div>
<div class="quick_faq" style="border:1px solid;margin:5px 0px;">
	<h5 class="preview">图文教程</h5>
	<ul style="font-size: 14px;">
		<li><a href="http://help.littleboss.com/word_list_188.html" target="_blank" >ERP功能</a></li>
		<li><a href="http://help.littleboss.com/word_list_118.html" target="_blank" >刊登管理</a></li>
		<li><a href="http://help.littleboss.com/word_list_127.html" target="_blank" >云站（自建站）</a></li>
		<li><a href="http://help.littleboss.com/word_list_82.html" target="_blank" >客服管理</a></li>
		<li><a href="http://help.littleboss.com/word_list_15.html" target="_blank" >物流跟踪助手</a></li>
		<li><a href="http://help.littleboss.com/word_list_106.html" target="_blank" >速卖通催款助手</a></li>
		<?php  if(0==1) echo '<li><a href="http://help.littleboss.com/word_list_112.html" target="_blank" >速卖通好评助手</a></li>'; ?>
		<li><a href="http://help.littleboss.com/word_list_229.html" target="_blank" >Cdiscount跟卖终结者</a></li>
	</ul>
</div>
<div class="eagle_ad" style="margin:0px">
</div>
<script>

</script>