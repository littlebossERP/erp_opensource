<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
?>
<style>
	#comment-template-add-form tr td {
		border:none;
	}
</style>
<form id="comment-template-add-form" action="">
	<table class="template-add-table">
		<?php if(empty($template)):?>
		<tr>
			<td class="col-lg-2"><?= TranslateHelper::t('语言版本')?></td>
			<td>
				<select id="comment_language" class="form-control" style="width:100px;">
					<option value="en">英语</option>
					<option value="fr">法语</option>
					<option value="de">德语</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="col-lg-2"><?= TranslateHelper::t('推荐模版')?></td>
			<td>
				<?php 
				$option = [];
				echo HtmlHelper::select('content',$option);
				 ?>
			</td>
		</tr>
		<?php endif;?>
		
		<tr>
			<td class="col-lg-2"><?= TranslateHelper::t('好评内容')?></td>
			<td>
				<div>
					<textarea name="content" style="margin:10px 0px;" rows="3" cols="70" id="comment_add_template_content"><?=isset($template->content)?$template->content:''?></textarea>
				</div>
			</td>
		</tr>
	</table>
	<input type="hidden" name="id" value="<?= isset($template->id)?$template->id:''?>">
	<div class="modal-footer">
		<input type="submit" class="btn btn-primary" id="template_submit_ok" value="确认" />
		<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
	</div>

</form>
<script>
var xlb_template_cn = 
	['请选择', '感谢您的光临，希望以后继续支持我们，我们会继续为您提供更好的商品.','很高兴您已经顺利收到货物，期待您以后多多支持哦~', '自定义..']
;
var xlb_template = {
	'en': ['请选择', 'Thanks for your visit, we sincerely hope acquire your lasting support and will provide better commodities for you.','We are pleased to know that you have received the goods smoothly and looking forward your lasting supports.', '自定义..'],
	'de': ['请选择', 'Danke für Ihre Bestellung, Auch freuen wir uns auf Ihre nächte Bestellungen. Wir werden Ihnen geradeaus qualitätive Angebote und besten Service anbieten.','Es freut uns, dass Sie schon Ihre Produkte erhalten haben, Auch freuen wir uns auf Ihre nächte Bestellungen.', '自定义..'],
	'fr': ['请选择', 'Merci de votre venu. Espérons que vous pourriez nous donner votre soutien à l\'avenir. Nous allons vous fournir des produit de bonne qualité et d\'un prix raisonnable.','Nous sommes heureux d\'entendre que vous avez bien reçu le colis.  Nous tenons à vous remercier pour votre soutien.', '自定义..'],
};
//初始化
$(".lb-select").lbSelectRender(xlb_template_cn);
$('.select-viewer').html(xlb_template_cn[0]);

$(".lb-select,#comment_language").on('change',function(){
	var $lan = $('#comment_language'),
		selectValue = $lan.val(),
		$this = $('.lb-select'),
		$textarea = $('#comment_add_template_content');
	if($this.val() == 0)$textarea.val('');
	else if(xlb_template[selectValue][$this.val()] == '自定义..'){
		$textarea.val('').attr({placeholder:'请输入好评内容'});
	}
	else $textarea.val(xlb_template[selectValue][$this.val()]);
}).change();

//保存模版内容
// $('#template_submit_ok').click(function(){
// 	//获得到数据
// 	var $form = $('#comment-template-add-form'),
// 		data = $form.serialize();
// 	//保存
// 	$form._selfajax('/comment/comment/doaddtemplate',data);
// })

$("#comment-template-add-form")._on('submit',function(e){
	e.preventDefault();
	var $form = $(this);
	$form._selfajax('/comment/comment/doaddtemplate',$form.serialize());
})


</script>
