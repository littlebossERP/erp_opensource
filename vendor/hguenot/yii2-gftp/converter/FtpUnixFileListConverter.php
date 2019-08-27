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
class FtpUnixFileListConverter extends \yii\base\Component implements FtpFileListConverter {
	
	public function parse($fullList) {
		
		$ftpFiles = [];
		
		foreach ($fullList as $line) {
			if (($split = $this->parse_ftp_rawlist($line)) !== false) {
				$ftpFiles[] = new \gftp\FtpFile([
					'isDir' => $split['isdir'],
					'rights' => $split['perms'],
					'user' => $split['owner'],
					'group' => $split['group'],
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

		$split = preg_split('[ ]', $line, 9, PREG_SPLIT_NO_EMPTY);
		if ($split[0] != 'total') {
			$output['isdir'] = ($split[0] {0} === 'd');
			$output['perms'] = $split[0];
			$output['number'] = $split[1];
			$output['owner'] = $split[2];
			$output['group'] = $split[3];
			$output['size'] = $split[4];
			$output['month'] = $split[5];
			$output['day'] = $split[6];
			$output['time/year'] = $split[7];
			$output['name'] = $split[8];
		}
		return !empty($output) ? $output : false;
	}

}
