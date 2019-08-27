<?php

namespace gftp\converter;

/**
 * Description of SftpFileListConverter
 */
class SimpleFileListConverter extends \yii\base\Component implements FtpFileListConverter {
	
	public function parse($files) {
		
		$ftpFiles = [];
		
		foreach ($files as $file) {
			$ftpFiles[] = new \gftp\FtpFile([
				'filename' => $file
			]);
		}
		
		return $ftpFiles;
	}
	
}
