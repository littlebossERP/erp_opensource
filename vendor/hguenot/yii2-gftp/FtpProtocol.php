<?php

namespace gftp;

/**
 * Description of FtpProtocol
 *
 * @author herve
 */
class FtpProtocol {
	
	private static $drivers = [];
	
	public static function registerDriver($protocol, $driver, $port) {
		$key = strtolower($protocol);
		self::$drivers[$key] = new FtpProtocol($protocol, $driver, $port);
	}
	
	public static function values() {
		return self::$drivers;
	}
	
	public static function valueOf($protocol) {
		$key = strtolower($protocol);
		return isset(self::$drivers[$key]) ? self::$drivers[$key] : null;
	}
	
	private function __construct($protocol, $driver, $port) {
		$this->protocol = $protocol;
		$this->driver = $driver;
		$this->port = $port;
	}
	
	private $protocol;
	private $driver;
	private $port;
	
	public function getProtocol(){
		return $this->protocol;
	}
	
	public function getDriver(){
		return $this->driver;
	}
	
	public function getPort(){
		return $this->port;
	}
	
	public function __get($name) {
		if ($name == 'protocol')
			return $this->getProtocol();
		if ($name == 'driver')
			return $this->getDriver();
		if ($name == 'port')
			return $this->getPort();
	}
}
