<?php

namespace gftp;

use \Yii;
use gftp\drivers\RemoteDriver;

/**
 * Utility class for FTP component.
 * 
 * @author Herve Guenot
 * @link http://www.guenot.info
 * @copyright Copyright &copy; 2015 Herve Guenot
 * @license GNU LESSER GPL 3
 * @version 1.0
 */
class FtpUtils {

	/** @var FtpUtils Global instance of FtpUtils. */
	private static $_instance = null;
	
	/**
	 * Returns unique instance of FtpUtils.
	 *
	 * @return FtpUtils Unique instance of FtpUtils
	 */
	public static function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new FtpUtils();
		}

		return self::$_instance;
	}

	/**
	 * Return if a {@link FtpFile} is a directory (based on user rights) or not.
	 *
	 * @param FtpFile $data File to test
	 *
	 * @return boolean <strong>TRUE</strong> if file is a directory, <strong>FALSE</strong> otherwise.
	 */
	public static function isDir($data) {
		return substr($data->rights, 0, 1) == "d" || $data->isDir;
	}

	/**
	 * Build filename for FtpWidget.
	 *
	 * @param FtpFile $file Current {@link FtpFile}
	 * @param FtpWidget $context Current displayable widget
	 *
	 * @return string Displayed filename
	 */
	public static function displayFilename (FtpFile $file, FtpWidget $context) {
		if ($context->allowNavigation && self::isDir($file)) {
			$dir = $context->baseFolder."/".$file->filename;
			if ($context->baseFolder == "/") {
				$dir = "/".$file->filename;
			}
			if ($file->filename == '..') {
				$dir = str_replace('\\', '/', dirname($context->baseFolder));
			}
			$arr = array_merge([""], $_GET, [$context->navKey => $dir]);
			return \yii\helpers\Html::a($file->filename, $arr);
		} else {
			return $file->filename;
		}
	}

	public static function registerTranslation(){
		self::registerTranslationFolder('gftp', __DIR__ . '/messages');
	}
	
	public static function registerTranslationFolder($group, $folder){
		if (isset(Yii::$app) && null !== Yii::$app->getI18n()) {
			$translations = isset(Yii::$app->getI18n()->translations) ? Yii::$app->getI18n()->translations : [];
			if (!isset($translations[$group]) && !isset($translations[$group.'*'])) {
				$translations[$group] = [
					'class' => 'yii\i18n\PhpMessageSource',
					'sourceLanguage' => 'en',
					'basePath' => $folder
				];
			}
			Yii::$app->getI18n()->translations = $translations;
		}
	}
}

