<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<style>
	#html,
	.label-content{
		position: fixed;
		top:0;left:0;right:0;bottom:0;
		margin:auto;
		/*margin: 100px auto;*/
	}
	</style>
	<link rel="stylesheet" href="/css/configuration/carrierconfig/bootstrap.min.css" />
	<link rel="stylesheet" href="/css/configuration/carrierconfig/layout.min.css" />
	<link rel="stylesheet" href="/css/configuration/carrierconfig/uielement.min.css" />
	<link rel="stylesheet" href="/css/configuration/carrierconfig/jquery-ui.min.css" />
	<link rel="stylesheet" href="/css/configuration/carrierconfig/jquery.gritter.min.css" />
	<link rel="stylesheet" href="/css/configuration/carrierconfig/custom.css" />
	<link rel="stylesheet" href="/css/configuration/carrierconfig/print.css" />
</head>
<body>
	<div id="html" class="label-content" style="width:<?= $template->template_width-2 ?>mm; height:<?= $template->template_height-2 ?>mm;">
		<?= $template->template_content ?>
	</div>
</body>
</html>