<?php

namespace gftp;

/* Jison generated parser */

class FtpParser
{
	public function parse($str) {
		
		FtpUtils::registerTranslation();
		$protocol = null;
		
		foreach(FtpProtocol::values() as $current){
			$regex = '/^' . $current->getProtocol() . ':\/\/[^\/]+$/';
			
			if (preg_match($regex, strtolower($str))){
				$str = substr($str, strlen($current->getProtocol())+3);
				$protocol = $current;
				break;
			}
		}
		
		if ($protocol === null){
			throw new FtpException("Could not find a valid protocol for " . $str);
		}

		// Split connect string using reverse string
		$parts = explode('@', strrev($str), 2);
		// array("<port>:<url>", "<pass>:<user>") or array("<port>:<url>") 
		
		$res = [
			'class' => $protocol->driver,
			'host' => 'localhost',
			'port' => $protocol->port,
			'user' => 'anonymous',
			'pass' => ''
		];
		
		if (count($parts) >= 1) {
			$hosts = explode(":", $parts[0], 2);
			// array("<port>", "<url>") or array("<url>") 
			if (count($hosts) == 1) {
				$res['host'] = strrev($hosts[0]);
			} else if(count($hosts) == 2) {
				$res['port'] = strrev($hosts[0]);
				$res['host'] = strrev($hosts[1]);
			} else {
				throw new FtpException("Invalid URL / port");
			}
		}
		if (count($parts) == 2) {
			$hosts = explode(":", strrev($parts[1]), 2);
			// array("<user>", "<pass>") or array("<user>") 
			if (count($hosts) == 1) {
				$res['user'] = $hosts[0];
			} else if(count($hosts) == 2) {
				$res['user'] = $hosts[0];
				$res['pass'] = $hosts[1];
			}
		}

		if (!isset($res['host']))
			throw new FtpException("No host found");
		
		if (isset($res['port']) && !preg_match('/^[0-9]+/', $res['port']))
			throw new FtpException("Port is not a number");
		
		\Yii::trace(\yii\helpers\VarDumper::dumpAsString($res), 'gftp');
		return $res;
	}
}
