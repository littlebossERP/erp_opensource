<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\message\models\Message;

$this->title=$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
<!--
.jumbotron {
padding-top: 48px;
padding-bottom: 48px;
}
.jumbotron {
padding-top: 30px;
padding-bottom: 30px;
margin-bottom: 30px;
color: inherit;
background-color: #eee;
}
-->
</style>
<div class="jumbotron">
      <div class="container">
        <h1 class="text-left">操作失败!</h1>
        <p class="text-left"><?php echo isset($error)?$error:'';?></p>
      </div>
</div>
<script type="text/javascript">
</script>