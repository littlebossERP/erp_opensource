<?php

use \gftp\FtpUtils;

if ($error !== null) {
	echo '<div class="flash-error">Could not display folder content : ' . $error->getMessage() . "</div>\n";
} else {
	$dp = new \yii\data\ArrayDataProvider ([
		'allModels' => $files,
		'sort' => [
			'attributes' => ['filename'],
		],
		'pagination' => [
			'pageSize' => 10,
		]
	]);

	echo 'Current Working dir : ' . htmlspecialchars($baseFolder) . '<br />';
	echo \yii\grid\GridView::widget([
		'id'=>'page-grid',
		'dataProvider'=>$dp,
		'columns'=>array(
			array (
				'header' => 'File name',
				'value' => function ($model, $key, $index, $column) {
					return FtpUtils::displayFilename($model, $column->grid->view->context);
				}, 
				'format' => 'html',
				'filter' => false,
				'attribute' => 'filename',
				'visible' => in_array('filename', $columns, true)
			),
			array (
				'header' => 'Rights',
				'filter' => false,
				'attribute' => 'rights',
				'visible' => in_array('rights', $columns, true)
			),
			array (
				'header' => 'User',
				'filter' => false,
				'attribute' => 'user',
				'visible' => in_array('user', $columns, true)
			),
			array (
				'header' => 'Group',
				'filter' => false,
				'attribute' => 'group',
				'visible' => in_array('group', $columns, true)
			),
			array (
				'header' => 'Modification time',
				'filter' => false,
				'attribute' => 'mdTime',
				'visible' => in_array('mdTime', $columns, true)
			),
			array (
				'header' => 'Size',
				'value' => function ($model, $key, $index, $column) {
					return FtpUtils::isDir($model) ? "" : $model->size;
				},
				'contentOptions' => array('style'=>'text-align: right;'),
				'filter' => false,
				'attribute' => 'size',
				'visible' => in_array('size', $columns, true)
			),
		),
	]); 
}
