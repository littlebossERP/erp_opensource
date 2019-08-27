<?php namespace eagle\modules\comment\config;


class CommentConfig
{

	const APP_TRACKER_KEY 					= "AliHaoPing"; 	// 用户行为记录的KEY
	const DEFAULT_RANK 						= 5; 				// 默认评分
	const VIEW_RULE_COUNTRY_MAX_LENGTH 		= 10; 				// 自动好评列表页国家显示最大数量
	const LOG_IN_CONSOLE 					= true; 			// 是否在控制台输出后台脚本日志

	// const API_OPEN_STATUS 					= 'test'; 			// production
	const API_OPEN_STATUS 					= 'production'; 		


}