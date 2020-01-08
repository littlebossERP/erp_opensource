<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-eagle',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'eagle\controllers',
    'timeZone' => 'PRC',
	
    'components' => [
	    'urlManager' => [
		    'enablePrettyUrl' => true,
		    'showScriptName' => false,
		    'rules' => [
			    '<controller:\w+>/<id:\d+>' => '<controller>/view',
			    '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
			    '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
		    ],
	    ],
        'user' => [
            'identityClass' => 'eagle\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,  //traceLevel大于0，则将debug_backtrace信息也记录到日志中
            'targets' => [
			    [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
					'except'=>['file','edb\*','carrier_api']
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['warning','info'],
                    'categories'=>['file'],
                    'logVars'=>[],
                    'logFile'=>'@runtime/logs/eagle.log.'.date('Ymd'),
                    'maxFileSize'=>5000,  //Maximum log file size, in kilo-bytes.
                    'maxLogFiles'=>10  //Number of log files used for rotation.
                ],
                [
	                'class' => 'yii\log\FileTarget',
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
                    'categories'=>['sync_order_ship'],
                    'logVars'=>[],
                    'logFile'=>'@runtime/logs/sync_order_ship.log.'.date('Ymd'),
                    'maxFileSize'=>29000,  //Maximum log file size, in kilo-bytes.
                    'maxLogFiles'=>2  //Number of log files used for rotation.
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
                    'class' => 'eagle\modules\util\models\EFileTarget',
                    'levels' => ['info'],
                    'categories'=>['ebayapi'],
                    'logVars'=>[],
                    'logFile'=>'@runtime/logs/ebayapi.log.'.date('Ymd'),
                    'maxFileSize'=>900000,  //Maximum log file size, in kilo-bytes.
                    'maxLogFiles'=>5  //Number of log files used for rotation.
                ],
                [
	                'class' => 'eagle\modules\util\models\EDbTarget',
	                'logVars'=>[],
	                'levels' => ['warning','info','error'],	               
                      'categories'=>['edb\user','edb\global'],
	            ],                
                
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
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
	'as ApplicationBehavior'=>['class' => 'eagle\components\ApplicationBehavior'],
    'params' => $params,
    
    'modules' => [
        'purchase' => [
            'class' => 'eagle\modules\purchase\Module',
        ],
        'catalog' => [
            'class' => 'eagle\modules\catalog\Module',
        ],		
        'util' => [
             'class' => 'eagle\modules\util\Module',
        ],        
        'listing' => [
             'class' => 'eagle\modules\listing\Module',
        ],   
	    'app' => [
            'class' => 'eagle\modules\app\Module',
        ],
    
        'inventory' => [
        'class' => 'eagle\modules\inventory\Module',
        ],
        'auction' => [
        'class' => 'eagle\modules\auction\Module',
        ],
        'platform' => [
        'class' => 'eagle\modules\platform\Module',
        ],
        'report' => [
        'class' => 'eagle\modules\report\Module',
        ],
        'ticket' => [
        'class' => 'eagle\modules\ticket\Module',
        ],
        'customer' => [
        'class' => 'eagle\modules\customer\Module',
        ],
        'delivery' => [
        'class' => 'eagle\modules\delivery\Module',
        ],
		'order' => [
        'class' => 'eagle\modules\order\Module',
        ],
		'amazon' => [
        'class' => 'eagle\modules\amazon\Module',
        ],
        'permission' => [
        'class' => 'eagle\modules\permission\Module',
        ],
    	'carrier' => [
    	'class' => 'eagle\modules\carrier\Module',
    	],
    	'message' => [
        'class' => 'eagle\modules\message\Module',
        ],
		'comment' => [
        'class' => 'eagle\modules\comment\Module',
        ],
        'statistics' => [
                'class' => 'eagle\modules\statistics\Module',
        ],
        'manual_sync'=>[
                'class' => 'eagle\modules\manual_sync\Module',
        ],
        
        'configuration' => [
                'class' => 'eagle\modules\configuration\Module',
        ],
        'collect' => [
                'class' => 'eagle\modules\collect\Module',
        ],
        'imageeditor' => [
                'class' => 'eagle\modules\imageeditor\Module',
        ],
        
        'dash_board' => [
                'class' => 'eagle\modules\dash_board\Module',
        ],
        'amazoncs' => [
                'class' => 'eagle\modules\amazoncs\Module',
        ],
        'move' => [
                'class' => 'eagle\modules\move\Module',
        ],
        'tracking' => [
                'class' => 'eagle\modules\tracking\Module',
        ],
	],
];
