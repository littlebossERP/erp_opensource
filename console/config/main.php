<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'gii'],
    'controllerNamespace' => 'console\controllers',
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'timeZone' => 'PRC',
     'components' => [
        'log' => [
		    'traceLevel' => 0,
			'flushInterval' => 1,
            'targets' => [
			    [
				    'exportInterval' => 1,
                    'class' => 'eagle\modules\util\models\EFileTarget',
                    'levels' => ['error', 'warning','info','trace'],
					'except'=>['file','edb\*']
                ],			
                [
				    'exportInterval' => 1,
                    'class' => 'eagle\modules\util\models\EFileTarget',
                    'levels' => ['warning','info'],
                    'categories'=>['file'],
					'logVars'=>[],
                    'logFile'=>'@runtime/logs/eagle.log.'.date('Ymd'),
                    'maxFileSize'=>5000,  //Maximum log file size, in kilo-bytes.
                    'maxLogFiles'=>10  //Number of log files used for rotation.
                ],
                [
				    'exportInterval' => 1,
	                'class' => 'eagle\modules\util\models\EFileTarget',
	                'levels' => ['error'],
	                'categories'=>['file'],
	                'logFile'=>'@runtime/logs/eagle_error.log.'.date('Ymd'),
	                'maxFileSize'=>5000,  //Maximum log file size, in kilo-bytes.
	                'maxLogFiles'=>3  //Number of log files used for rotation.
                ],
                [
                    'exportInterval' => 1,
                    'class' => 'eagle\modules\util\models\EFileTarget',
                    'levels' => ['info'],
                    'categories'=>['carrier_api'],
                    'logVars'=>[],
                    'logFile'=>'@runtime/logs/carrier_api.log.'.date('Ymd'),
                    'maxFileSize'=>900000,  //Maximum log file size, in kilo-bytes.
                    'maxLogFiles'=>2  //Number of log files used for rotation.
                ],
                [
				    'exportInterval' => 1,
	                'class' => 'eagle\modules\util\models\EDbTarget',
	                'logVars'=>[],
	                'levels' => ['warning','info','error'],
	                'categories'=>['edb\user','edb\global'],
	            ],                
                
            ],
        ],
      
        'db' => [
	        'class' => 'yii\db\Connection',
	        'dsn' => 'mysql:host=localhost;dbname=managedb',
	        'username' => 'root',
	        'password' => '',
	        'charset' => 'utf8',
        ],
        'db_queue' => [
	        'class' => 'yii\db\Connection',
	        'dsn' => 'mysql:host=localhost;dbname=managedb_queue',
	        'username' => 'root',
	        'password' => '',
	        'charset' => 'utf8',
        ],  
         'db_queue2' => [
                 'class' => 'yii\db\Connection',
                 'dsn' => 'mysql:host=localhost;dbname=managedb_queue',
                 'username' => 'root',
                 'password' => '',
                 'charset' => 'utf8',
         ],
        'subdb' => [
	        'class' =>  'eagle\models\DBManager',
	        'charset' => 'utf8',
        ],
        'redis' => [
                'class' => 'yii\redis\Connection',
                'hostname' => '127.0.0.1',
                'port' => 6379,
                'database' => 0
        ],
        
    ],
    'params' => $params,
];
