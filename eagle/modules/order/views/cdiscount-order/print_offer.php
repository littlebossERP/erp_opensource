
<?php
use eagle\modules\util\helpers\TranslateHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("cdOffer.printPage.init()", \yii\web\View::POS_READY);
?>
<!DOCTYPE head PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<TITLE>打印cdiscount在线商品建议</TITLE>
</head>
<body>
</body>
<script type="text/javascript">
</script>

</html>