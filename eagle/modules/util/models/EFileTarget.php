<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace eagle\modules\util\models;

use Yii;
use yii\db\Connection;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\VarDumper;
use yii\log\FileTarget;
use eagle\modules\util\helpers\DataStaticHelper;

/**
 * DbTarget stores log messages in a database table.
 *
 * The database connection is specified by [[db]]. Database schema could be initialized by applying migration:
 *
 * ```
 * yii migrate --migrationPath=@yii/log/migrations/
 * ```
 *
 * If you don't want to use migration and need SQL instead, files for all databases are in migrations directory.
 *
 * You may change the name of the table used to store the data by setting [[logTable]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class EFileTarget extends FileTarget
{
	/**
	 * Returns a string to be prefixed to the given message.
	 * If [[prefix]] is configured it will return the result of the callback.
	 * The default implementation will return user IP, user ID and session ID as a prefix.
	 * @param array $message the message being exported.
	 * The message structure follows that in [[Logger::messages]].
	 * @return string the prefix string
	 */
	public function getMessagePrefix($message)
	{
		if ($this->prefix !== null) {
			return call_user_func($this->prefix, $message);
		}
	
		if (Yii::$app === null) {
			return '';
		}
	
		$request = Yii::$app->getRequest();
		$ip = $request instanceof Request ? $request->getUserIP() : '-';
	
		/* @var $user \yii\web\User */
		$user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
		if ($user && ($identity = $user->getIdentity(false))) {
			$userID = $identity->getId();
		} else {
			//lolo20150309 change 
			//$userID = '-';
			$userID ="".DataStaticHelper::getCurrentBGJobId();
		}
	
		/* @var $session \yii\web\Session */
		$session = Yii::$app->has('session', true) ? Yii::$app->get('session') : null;
		$sessionID = $session && $session->getIsActive() ? $session->getId() : '-';
	
		return "[$ip][$userID][$sessionID]";
	}	
	
}
