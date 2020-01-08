<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'hostInfo'=>'',
    
	'currentEnv'=>'production',  //当前环境--production或者test
	'manualSync'=>[
		'test'=>[
			'function'=>['\eagle\modules\manual_sync\helpers\SyncHelper','testCallback'],
			'getAccounts'=>['\eagle\modules\manual_sync\helpers\SyncHelper','testGetAccounts'],
			'overtime'=>300,
			'access'=>'private' 	// 私有进程 需要运行增加参数 type:test  eg: ./yii manual-sync/run test
		],
		'wish:product'=>[ 		// wish商品同步
			'function'=>['\eagle\modules\listing\helpers\WishHelper','manualSyncCallback'],   // 队列回调操作函数
			// 'getAccounts'=>['\eagle\modules\listing\helpers\WishHelper','manualSyncGetAccountsByInterval'],   // 获取店铺列表函数
			'overtime'=>1800,  // 30分钟
			'retry'=>60, 		// 1分钟
		],
		'wish:push'=>[ 		// wish发布
			'function'=>['\eagle\modules\listing\helpers\WishHelper','wishPushSyncCallback'],   // 队列回调操作函数
			'overtime'=>1800,  // 30分钟
		],
		'smt:push'=>[ 		// 没有自动操作
		    // dzt20191018 修改为AliexpressOrderV2Helper
			'function'=>['\eagle\modules\order\helpers\AliexpressOrderV2Helper','getOrderListManual'],   // 队列回调操作函数
			'overtime'=>1800,  // 30分钟
		],
		'smt:product'=>[ 	// 速卖通手工/自动同步
			'function'=>['\eagle\modules\listing\models\AliexpressListing','syncAll'],
			'overtime'=>1800,  // 30分钟
			'access'=>'private'
		],
		'smt:productpush'=>[ 	// 速卖通商品刊登
			'function'=>['\eagle\modules\listing\models\AliexpressListing','pushAll'],
			'overtime'=>1800,  // 30分钟
			'access'=>'private'
		],
		'smt:enable'=>[ 	// 速卖通上下架
			'function'=>['\eagle\modules\listing\models\AliexpressListing','enableAll'],
			'overtime'=>1800,  // 30分钟
			'access'=>'private'
		],
	]  
];
