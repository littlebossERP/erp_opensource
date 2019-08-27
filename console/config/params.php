<?php
return [
    'adminEmail' => 'admin@example.com',
    'subdb'=>[
         "host"=>"localhost",
         "dbPrefix"=>"user_",
         "username"=>"root",
         "password"=>"",
         
      ],
    'currentEnv'=>'production',  //当前环境--production或者test
    'mysql_bak_path'=>'@console/mysql_bak', //备份数据库sql 存放目录
    
    // 备份数据库 的账号
    'backupdb'=>[
    	'db' => [
	        'dsn' => 'mysql:host=localhost;dbname=managedb',
	        'username' => 'root',
	        'password' => '',
        ],
	    'subdb'=>[
	    	"host"=>"localhost",
    		"dbPrefix"=>"user_",
			"username"=>"root",
			"password"=>"",
  		],
	],


	'fetch_order_need_autocheck'=>0 //订单拉取导入到eagle系统的时候，是否进行自动检测。 0---表示不检测; 1---表示检测




];
