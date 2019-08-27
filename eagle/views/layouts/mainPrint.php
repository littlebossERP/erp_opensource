<?php 
use yii\helpers\Html;
//use frontend\assets\AppAsset;
//AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <SCRIPT type="text/javascript">
    function changeLanguage(key){
   	 	$.get('?r=site/change-language',{'lan':key}, function(result){
			window.location.reload();
		});
    }
    </SCRIPT>
</head>
<body>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>