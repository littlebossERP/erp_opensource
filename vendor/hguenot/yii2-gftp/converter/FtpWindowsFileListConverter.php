<?php

namespace gftp\converter;

/**
 * Converts FTP file list results to FtpFile.
 * 
 * @author Herve Guenot
 * @link http://www.guenot.info
 * @copyright Copyright &copy; 2015 Herve Guenot
 * @license GNU LESSER GPL 3
 * @version 1.0
 */
class FtpWindowsFileListConverter extends \yii\base\Component implements FtpFileListConverter {
	
	public function parse($fullList) {
		
		$ftpFiles = [];
		
		foreach ($fullList as $line) {
			if (($split = $this->parse_ftp_rawlist($line)) !== false) {
				$ftpFiles[] = new \gftp\FtpFile([
					'isDir' => $split['isdir'],
					'rights' => '',
					'user' => '',
					'group' => '',
					'size' => $split['size'],
					'mdTime' => $split['day'] . ' ' . $split['month'] . ' ' . $split['time/year'],
					'filename' => $split['name']
				]);
			}
		}
		
		return $ftpFiles;
	}
	
	// function inspired by http://andreas.glaser.me/2009/03/12/php-ftp_rawlist-parser-windows-unixlinux/
	private function parse_ftp_rawlist($line) {
		$output = array();

		ereg('([0-9]{2})-([0-9]{2})-([0-9]{2}) +([0-9]{2}):([0-9]{2})(AM|PM) +([0-9]+|) +(.+)', $Current, $split);
		if (is_array($split)) {
			if ($split[3] < 70) {
				$split[3] += 2000;
			} else {
				$split[3] += 1900;
			}
			$output['isdir'] = ($split[7] == '');
			$output['size'] = $split[7];
			$output['month'] = $split[1];
			$output['day'] = $split[2];
			$output['time/year'] = $split[3];
			$output['name'] = $split[8];
		}
		return !empty($output) ? $output : false;
	}

}
