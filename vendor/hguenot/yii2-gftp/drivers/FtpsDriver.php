<?php

namespace gftp\drivers;

use gftp\FtpException;
use gftp\converter\FtpUnixFileListConverter;
use gftp\converter\FtpWindowsFileListConverter;
use \Yii;

/**
 * FTP over SSL connection driver.
 */
class FtpsDriver extends FtpDriver {

	public function connect() {
		if (isset($this->handle) && $this->handle != null) {
			$this->close();
		}
		$this->handle = ftp_ssl_connect($this->host, $this->port, $this->timeout);
		if ($this->handle === false) {
			$this->handle = false;
			throw new FtpException(
				Yii::t('gftp', 'Could not connect to FTP server "{host}" on port "{port}" using SSL', [
					'host' => $this->host, 'port' => $this->port
				])
			);
		} else {
			if (strtolower($this->systype()) == 'unix') {
				$this->setFileListConverter(new FtpUnixFileListConverter());
			} else {
				$this->setFileListConverter(new FtpWindowsFileListConverter());
			}
		}
	}
	
}
