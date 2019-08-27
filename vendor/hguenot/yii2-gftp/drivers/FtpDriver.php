<?php

namespace gftp\drivers;

use gftp\FtpException;
use gftp\converter\FtpFileListConverter;
use gftp\converter\FtpUnixFileListConverter;
use gftp\converter\FtpWindowsFileListConverter;
use gftp\converter\SimpleFileListConverter;
use \Yii;

/**
 * Basic FTP connection driver.
 */
class FtpDriver extends \yii\base\Object implements RemoteDriver {
	
	
	/**
	 * @var   mixed     FTP handle.
	 */
	protected $handle;
	
	/**
	 * @var   string    FTP hostname.
	 */
	private $host = 'localhost';

	/**
	 * @var   string    FTP port.
	 */
	private $port = 21;

	/**
	 * @var   string    FTP username.
	 */
	private $user = 'anonymous';

	/**
	 * @var   string    FTP password.
	 */
	private $pass = '';

	/**
	 * @var   FtpFileListConverter Converts string array in FtpFile array.
	 */
	private $fileListConverter;

	/**
	 * @var   integer      Connection timeout in seconds.
	 */
	private $timeout = 30;

	/**
	 * @var   bool      Connect in passive mode
	 */
	private $passive = true;

	/**
	 * @var   mixed     Used for passing data to error hanling function.
	 */
	private $param = '';

	// *************************************************************************
	// DRIVER INITIALIZATION
	// *************************************************************************
	public function init() {
		parent::init();
		
		self::registerErrorHandler();
		\gftp\FtpUtils::registerTranslation();
	}
	
	// *************************************************************************
	// ACCESSORS
	// *************************************************************************
	/**
	 * Changing FTP host name or IP
	 * 
	 * @param string $host New hostname
	 */
	public function setHost(/* string */ $host) {
		// Close connection before changing host.
		if ($this->host !== $host) {
			$this->close();
			$this->host = $host;
		}
	}
	
	/**
	 * @return string The current FTP host.
	 */
	public function getHost() {
		return $this->host;
	}
	
	/**
	 * Changing FTP port.
	 * 
	 * @param integer $port New hostname
	 */
	public function setPort(/* integer */ $port) {
		// Close connection before changing port.
		if ($this->port !== $port) {
			$this->close();
			$this->port = $port;
		}
	}
	
	/**
	 * @return integer The current FTP port.
	 */
	public function getPort() {
		return $this->port;
	}
	
	/**
	 * Changing FTP connecting username.
	 * 
	 * @param string $user New username
	 */
	public function setUser(/* string */ $user) {
		// Close connection before changing username.
		if ($this->user !== $user) {
			$this->close();
			$this->user = $user;
		}
	}
	
	/**
	 * @return string The FTP connecting username.
	 */
	public function getUser() {
		return $this->user;
	}
	
	/**
	 * Changing FTP password.
	 * 
	 * @param string $pass New password
	 */
	public function setPass(/* string */ $pass) {
		// Close connection before changing password.
		if ($this->pass !== $pass) {
			$this->close();
			$this->pass = $pass;
		}
	}
	
	/**
	 * @return string The FTP password.
	 */
	public function getPass() {
		return $this->pass;
	}
	
	/**
	 * Changing FTP passive mode.
	 * 
	 * @param boolean $passive Set passive mode
	 * 
	 * @throws FtpException if passive mode could not be set.
	 */
	public function setPassive(/* boolean */ $passive) {
		// Close connection before changing password.
		if ($this->passive !== $passive) {
			$this->passive = $passive;
			if (isset($this->handle) && $this->handle != null) {
				$this->pasv($this->passive);
			}
		}
	}
	
	/**
	 * @return boolean FTP passive mode.
	 */
	public function getPassive() {
		return $this->passive;
	}
	
	/**
	 * Changing connection timeout in seconds.
	 * 
	 * @param integer $timeout Set passive mode
	 */
	public function setTimeout(/* integer */ $timeout) {
		$this->timeout = $timeout;
	}
	
	/**
	 * @return integer FTP connection timeout.
	 */
	public function getTimeout() {
		return $this->timeout;
	}
	
	/**
	 * Returns the file list converter used to convert full file list (string array) in FtpFile array.
	 *
	 * @return FtpFileListConverter The current file list converter
	 *
	 * @see Ftp::ls
	 */
	public function getFileListConverter() {
		return $this->fileListConverter;
	}

	/**
	 * Change the current file list converter.
	 *
	 * @param FtpFileListConverter $fileListConverter the new file list converter.
	 *
	 * @throws \yii\base\Exception If type of $fileListConverter is not valid.
	 */
	public function setFileListConverter(FtpFileListConverter $fileListConverter) {
		$this->fileListConverter = $fileListConverter;
	}

	// *************************************************************************
	// FTP DRIVER METHODS
	// *************************************************************************
	/**
	 * @see RemoteDriver::connect()
	 */
	public function connect() {
		if (isset($this->handle) && $this->handle != null) {
			$this->close();
		}
		$this->handle = ftp_connect($this->host, $this->port, $this->timeout);
		if ($this->handle === false) {
			throw new FtpException(
				Yii::t('gftp', 'Could not connect to FTP server "{host}" on port "{port}"', [
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

	/**
	 * @see RemoteDriver::login()
	 */
	public function login() {
		$this->connectIfNeeded(false);
		if (ftp_login($this->handle, $this->user, $this->pass) === false) {
			throw new FtpException(
				Yii::t('gftp', 'Could not login to FTP server "{host}" on port "{port}" with user "{user}"', [
					'host' => $this->host, 'port' => $this->port, 'user' => $this->user
				])
			);
		} else if ($this->passive) {
			try {
				$this->pasv($this->passive);
			}catch(FtpException $e){

			}
		}
	}

	/**
	 * @see RemoteDriver::close()
	 */
	public function close() {
		if (isset($this->handle) && $this->handle != null) {
			if (!ftp_close($this->handle)) {
				throw new FtpException(
					Yii::t('gftp', 'Could not close connection to FTP server "{host}" on port "{port}"', [
						'host' => $this->host, 'port' => $this->port
					])
				);
			} else {
				$this->handle = false;
			}
		}
	}

	/**
	 * @see RemoteDriver::ls($dir, $full, $recursive)
	 */
	public function ls($dir = ".", $full = false, $recursive = false) {
		$this->connectIfNeeded();
		$this->param = $dir;
		$fileListConverter = $this->fileListConverter;

		$res = array();
		if (!$full) {
			$opts = $recursive ? "-R " : "";
			$res = ftp_nlist($this->handle, $opts.$dir);
			$fileListConverter = new SimpleFileListConverter();
		} else {
			$res = ftp_rawlist($this->handle, $dir, $recursive);
		}

		if ($res === false) {
			throw new FtpException(
				Yii::t('gftp', 'Could not read folder "{folder}" on server "{host}"', [
					'host' => $this->host, 'folder' => $dir
				])
			);
		}

		return $fileListConverter->parse($res);
	}

	/**
	 * @see RemoteDriver::pwd()
	 */
	public function pwd() {
		$this->connectIfNeeded();

		$dir = ftp_pwd($this->handle);
		if ($dir === false) {
			throw new FtpException(
				Yii::t('gftp', 'Could not get current folder on server "{host}"', [
					'host' => $this->host
				])
			);
		}

		return $dir;
	}

	/**
	 * @see RemoteDriver::chdir($dir)
	 */
	public function chdir($dir) {
		$this->connectIfNeeded();
		$this->param = $dir;

		if (!ftp_chdir($this->handle, $dir)) {
			throw new FtpException(
				Yii::t('gftp', 'Could not go to "{folder}" on server "{host}"', [
					'host' => $this->host, 'folder' => $dir
				])
			);
		}

		try {
			$path = $this->pwd();
		} catch (FtpException $ex) {
			$path = $dir;	
		}
		return $path;
	}

	/**
	 * @see RemoteDriver::mkdir($dir)
	 */
	public function mkdir($dir) {
		$this->connectIfNeeded();
		$this->param = $dir;

		if (!ftp_mkdir($this->handle, $dir)) {
			throw new FtpException(
				Yii::t('gftp', 'An error occured while creating folder "{folder}" on server "{host}"', [
					'host' => $this->host, 'folder' => $dir
				])
			);
		}
	}

	/**
	 * @see RemoteDriver::rmdir($dir)
	 */
	public function rmdir($dir) {
		$this->connectIfNeeded();
		$this->param = $dir;

		if (!ftp_rmdir($this->handle, $dir)) {
			throw new FtpException(
				Yii::t('gftp', 'An error occured while removing folder "{folder}" on server "{host}"', [
					'host' => $this->host, 'folder' => $dir
				])
			);
		}
	}

	/**
	 * @see RemoteDriver::chmod($mode, $file)
	 */
	public function chmod($mode, $file) {
		$this->connectIfNeeded();
		if (substr($mode, 0, 1) != '0') {
			$mode = (int) (octdec ( str_pad ( $mode, 4, '0', STR_PAD_LEFT ) ));
		}

		$this->param = array('mode' => $mode, 'file' => $file);

		if (!ftp_chmod($this->handle, $mode, $file)) {
			throw new FtpException(
				Yii::t('gftp', 'Could change mode (to "{mode}") of file "{file}" on server "{host}"', [
					'host' => $this->host, 'file' => $file, '{mode}' => $mode
				])
			);
		}
	}

	/**
	 * @see RemoteDriver::fileExists($filename)
	 */
	public function fileExists($filename) {
		$this->connectIfNeeded();

		$res = ftp_nlist($this->handle, $filename);
		return $res !== false;
	}

	/**
	 * @see RemoteDriver::delete($path)
	 */
	public function delete($path) {
		$this->connectIfNeeded();
		$this->param = $path;

		if (!ftp_delete($this->handle, $path)) {
			throw new FtpException(
				Yii::t('gftp', 'Could not delete file "{file}" on server "{host}"', [
					'host' => $this->host, 'file' => $path
				])
			);
		}
	}

	/**
	 * @see RemoteDriver::get($mode, $remote_file, $local_file, $asynchronous)
	 */
	public function get($remote_file, $local_file = null, $mode = FTP_ASCII, $asynchronous = false) {
		$this->connectIfNeeded();

		if (!isset($local_file) || $local_file == null || !is_string($local_file) || trim($local_file) == "") {
			$local_file = getcwd() . DIRECTORY_SEPARATOR . basename($remote_file);
		}
		$this->param = array('remote_file' => $remote_file, 'local_file' => $local_file, 'asynchronous' => $asynchronous);

		if ($asynchronous !== true) {
			if (!ftp_get($this->handle, $local_file, $remote_file, $mode)){
				throw new FtpException(
					Yii::t('gftp', 'Could not synchronously get file "{remote_file}" from server "{host}"', [
						'host' => $this->host, 'remote_file' => $remote_file
					])
				);
			}
		} else {
			$ret = ftp_nb_get($this->handle, $local_file, $remote_file, $mode);
				
			while ($ret == FTP_MOREDATA) {
				// continue downloading
				$ret = ftp_nb_continue($this->handle);
			}
			if ($ret == FTP_FAILED){
				throw new FtpException(
					Yii::t('gftp', 'Could not asynchronously get file "{remote_file}" from server "{host}"', [
						'host' => $this->host, 'remote_file' => $remote_file
					])
				);
			}
		}
		
		return realpath($local_file);
	}

	/**
	 * @see RemoteDriver::put($mode, $local_file, $remote_file, $asynchronous)
	 */
	public function put($local_file, $remote_file = null, $mode = FTP_ASCII, $asynchronous = false) {
		$this->connectIfNeeded();
		if (!isset($remote_file) || $remote_file == null || !is_string($remote_file) || trim($remote_file) == "") {
			$remote_file = basename($local_file);
		}
		$this->param = array('remote_file' => $remote_file, 'local_file' => $local_file, 'asynchronous' => $asynchronous);

		if ($asynchronous !== true) {
			if (!ftp_put($this->handle, $remote_file, $local_file, $mode)) {
				throw new FtpException(
					Yii::t('gftp', 'Could not put file "{local_file}" on "{remote_file}" on server "{host}"', [
						'host' => $this->host, 'remote_file' => $remote_file, 'local_file' => $local_file
					])
				);
			}
		} else {
			$ret = ftp_nb_put($this->handle, $remote_file, $local_file, $mode);
				
			while ($ret == FTP_MOREDATA) {
				$ret = ftp_nb_continue($this->handle);
			}
				
			if ($ret !== FTP_FINISHED) {
				throw new FtpException(
					Yii::t('gftp', 'Could not put file "{local_file}" on "{remote_file}" on server "{host}"', [
						'host' => $this->host, 'remote_file' => $remote_file, 'local_file' => $local_file
					])
				);
			}
		}

		return $remote_file;
	}

	/**
	 * @see RemoteDriver::rename($oldname, $newname)
	 */
	public function rename($oldname, $newname) {
		$this->connectIfNeeded();
		$this->param = array('oldname' => $oldname, 'newname' => $newname);

		if (!ftp_rename($this->handle, $oldname, $newname)) {
			throw new FtpException(
				Yii::t('gftp', 'Could not rename file "{oldname}" to "{newname}" on server "{host}"',[
					'host' => $this->host, 'oldname' => $oldname, 'newname' => $newname
				])
			);
		}
	}

	/**
	 * @see RemoteDriver::mdtm($path)
	 */
	public function mdtm($path) {
		$this->connectIfNeeded();
		$this->param = $path;

		$res = ftp_mdtm($this->handle, $path);
		if ($res < 0) {
			throw new FtpException(
				Yii::t('gftp', 'Could not get modification time of file "{file}" on server "{host}"', [
					'host' => $this->host, 'file' => $path
				])
			);
		}

		return $res;
	}

	/**
	 * @see RemoteDriver::size($path)
	 */
	public function size($path) {
		$this->connectIfNeeded();
		$this->param = $path;

		$res = ftp_size($this->handle, $path);
		if ($res < 0) {
			throw new FtpException(
				Yii::t('gftp', 'Could not get size of file "{file}" on server "{host}"', [
					'host' => $this->host, 'file' => $path
				])
			);
		}
		
		return $res;
	}

	// *************************************************************************
	// SPECIFIC FTP METHODS
	// *************************************************************************
	/**
	 * Returns the remote system type.
	 * 
	 * @return string The remote system type
	 */
	public function systype() {
		$this->connectIfNeeded();
		$res = @ftp_systype($this->handle);
		return $res == null || $res == false ? 'UNIX' : $res;
	}

	/**
	 * Turns on or off passive mode.
	 *
	 * @param bool      $pasv          If <strong>TRUE</strong>, the passive mode is turned on, else it's turned off.
	 */
	public function pasv($pasv) {
		$this->connectIfNeeded();
		$this->param = $pasv;

		if (!ftp_pasv($this->handle, $pasv === true)) {
			throw new FtpException(
					Yii::t('gftp', 'Could not {set} passive mode on server "{host}": {message}', [
						'host' => $this->host, 'set' => $pasv ? "set" : "unset"
					])
			);
		}
	}

	/**
	 * Execute any command on FTP server.
	 *
	 * @param string    $command       FTP command.
	 * @param bool      $raw           Do not parse command to determine if it is a <i>SITE</i> or <i>SITE EXEC</i> command.
	 *
	 * @returns bool|string[] Depending on command : SITE and SITE EXEC command will returns <strong>TRUE</strong>; other command will returns an array. If <strong>$raw</strong> is set to <strong>TRUE</strong>, it always return an array.
	 *
	 * @throws FtpException If command execution fails.
	 *
	 * @see Ftp::exec Used to execute a <i>SITE EXEC</i> command
	 * @see Ftp::site Used to execute a <i>SITE</i> command
	 * @see Ftp::raw  Used to execute any other command (or if $raw is set to <strong>TRUE</strong>)
	 */
	public function execute($command, $raw = false) {
		$this->connectIfNeeded();
		$this->param = $command;

		if (!$raw && $this->stringStarts($command, "SITE EXEC")) {
			$this->exec(substr($command, strlen("SITE EXEC")));
			return true;
		} else if (!$raw && $this->stringStarts($command, "SITE")) {
			$this->site(substr($command, strlen("SITE")));
			return true;
		} else {
			return $this->raw($command);
		}
	}

	/**
	 * Sends a SITE EXEC command request to the FTP server.
	 *
	 * @param string    $command       FTP command (does not include <i>SITE EXEC</i> words).
	 *
	 * @throws FtpException If command execution fails.
	 */
	public function exec($command) {
		$this->connectIfNeeded();
		$this->param = "SITE EXEC " . $command;
		$exec = true;

		if (!ftp_exec($this->handle, substr($command, strlen("SITE EXEC")))) {
			throw new FtpException(
				Yii::t('gftp', 'Could not execute command "{command}" on "{host}"', [
					'host' => $this->host, '{command}' => $this->param
				])
			);
		}
	}

	/**
	 * Sends a SITE command request to the FTP server.
	 *
	 * @param string    $command       FTP command (does not include <strong>SITE</strong> word).
	 *
	 * @throws FtpException If command execution fails.
	 */
	public function site($command) {
		$this->connectIfNeeded();
		$this->param = "SITE " . $command;

		if (!ftp_site($this->handle, $command)) {
			throw new FtpException(
				Yii::t('gftp', 'Could not execute command "{command}" on "{host}"', [
					'host' => $this->host, '{command}' => $this->param
				])
			);
		}
	}

	/**
	 * Sends an arbitrary command to the FTP server.
	 *
	 * @param string    $command       FTP command to execute.
	 *
	 * @return string[] The server's response as an array of strings. No parsing is performed on the response string and not determine if the command succeeded.
	 *
	 * @throws FtpException If command execution fails.
	 */
	public function raw($command) {
		$this->connectIfNeeded();
		$this->param = $command;

		$res = ftp_raw($this->handle, $command);
		return $res;
	}
	
	// *************************************************************************
	// UTILITY METHODS
	// *************************************************************************
	/**
	 * Connects and log in to FTP server if not already login.
	 * Call to {link GFTp::connect} and {@link GTP::login} is not mandatory.
	 * Must be called in each method, before executing FTP command.
	 *
	 * @param bool      $login         Flag indicating if login will be done.
	 *
	 * @see GFTp::connect
	 * @see GFTp::login
	 *
	 * @throws FtpException if connection of login onto FTP server failed.
	 */
	protected function connectIfNeeded($login = true) {
		if (!isset($this->handle) || $this->handle == null) {
			$this->connect();
				
			if ($login && $this->user != null && $this->user != "") {
				$this->login($this->user, $this->pass);
			}
		}
	}

	// *************************************************************************
	// ERROR HANDLING
	// *************************************************************************
	/**
	 * Handles FTP error (ftp_** functions sometimes use PHP error instead of methofr return).
	 * It throws FtpException when ftp_** error is found.
	 *
	 * @param string    $function         FTP function name
	 * @param string    $message          Error message
	 *
	 * @return FtpException if PHP error on ftp_*** method is found, null otherwise.
	 */
	private function createException($function, $message) {
		if ($function == 'ftp_connect()' || $function == 'ftp_ssl_connect()') {
			$this->handle = false;
			return new FtpException(
				Yii::t('gftp', 'Could not connect to FTP server "{host}" on port "{port}": {message}', [
					'host' => $this->host, 'port' => $this->port, 'message' => $message
				])
			);
		} else if ($function == 'ftp_close()') {
			return new FtpException(
				Yii::t('gftp', 'Could not close connection to FTP server "{host}" on port "{port}": {message}', [
					'host' => $this->host, 'port' => $this->port, 'message' => $message
				])
			);
		} else if ($function == 'ftp_nlist()' || $function == 'ftp_rawlist()') {
			return new FtpException(
				Yii::t('gftp', 'Could not read folder "{folder}" on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'folder' => $this->param
				])
			);
		} else if ($function == 'ftp_mkdir()') {
			return new FtpException(
				Yii::t('gftp', 'Could not create folder "{folder}" on "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'folder' => $this->param
				])
			);
		} else if ($function == 'ftp_rmdir()') {
			return new FtpException(
				Yii::t('gftp', 'Could not remove folder "{folder}" on "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'folder' => $this->param
				])
			);
		} else if ($function == 'ftp_cdup()') {
			return new FtpException(
				Yii::t('gftp', 'Could not move to parent directory on "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'folder' => $this->param
				])
			);
		} else if ($function == 'ftp_chdir()') {
			return new FtpException(
				Yii::t('gftp', 'Could not move to folder "{folder}" on "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'folder' => $this->param
				])
			);
		} else if ($function == 'ftp_pwd()') {
			return new FtpException(
				Yii::t('gftp', 'Could not get current folder on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message
				])
			);
		} else if ($function == 'ftp_chmod()') {
			return new FtpException(
				Yii::t('gftp', 'Could change mode (to "{mode}") of file "{file}" on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 
					'file' => $this->param['file'], 'mode' => $this->param['mode']
				])
			);
		} else if ($function == 'ftp_put()') {
			return new FtpException(
				Yii::t('gftp', 'Could not put file "{local_file}" on "{remote_file}" on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 
					'remote_file' => $this->param['remote_file'], 'local_file' => $this->param['local_file']
				])
			);
		} else if ($function == 'ftp_get()') {
			return new FtpException(
				Yii::t('gftp', 'Could not synchronously get file "{remote_file}" from server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 
					'remote_file' => $this->param['remote_file']
				])
			);
		} else if ($function == 'ftp_size()') {
			return new FtpException(
				Yii::t('gftp', 'Could not get size of file "{file}" on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'file' => $this->param
				])
			);
		} else if ($function == 'ftp_nb_get()' || $function == 'ftp_nb_continue()') {
			return new FtpException(
				Yii::t('gftp', 'Could not asynchronously get file "{remote_file}" from server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 
					'remote_file' => $this->param['remote_file']
				])
			);
		} else if ($function == 'ftp_rename()') {
			return new FtpException(
				Yii::t('gftp', 'Could not rename file "{oldname}" to "{newname}" on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 
					'oldname' => $this->param['oldname'], 'newname' => $this->param['newname']
				])
			);
		} else if ($function == 'ftp_delete()') {
			return new FtpException(
				Yii::t('gftp', 'Could not delete file "{file}" on server "{host}" : {message}', [
					'host' => $this->host, 'message' => $message, 'file' => $this->param
				])
			);
		} else if ($function == 'ftp_pasv()') {
			return new FtpException(
				Yii::t('gftp', 'Could not {set} passive mode on server "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'set' => $this->param ? "set" : "unset"
				])
			);
		} else if ($function == 'ftp_mdtm()') {
			return new FtpException(
				Yii::t('gftp', 'Could not get modification time of file "{file}" on server "{host}"', [
					'host' => $this->host, 'message' => $message, 'file' => $this->param
				])
			);
		} else if ($function == 'ftp_exec()' || $function == 'ftp_raw()' || $function == 'ftp_site()') {
			return new FtpException(
				Yii::t('gftp', 'Could not execute command "{command}" on "{host}": {message}', [
					'host' => $this->host, 'message' => $message, 'command' => $this->param
				])
			);
		}

		return null;
	}

	private static $errorHandlerRegistered = false;
	
	private static function registerErrorHandler() {
		if (!self::$errorHandlerRegistered && YII_ENABLE_ERROR_HANDLER) {
			\set_error_handler(function ($code,$message,$file,$line,$context) {
				if (isset($context['this']) && $context['this'] instanceof FtpDriver) {
					// disable error capturing to avoid recursive errors
					restore_error_handler();
					restore_exception_handler();
					if (isset($message)) {
						// FTP error message are formed : ftp_***(): <message>
						$messages = explode(':', $message, 2);
						$func = explode(' ', $messages[0], 2);
						$ex = $context['this']->createException($func[0], $messages[1]);
						if ($ex != null) {
							throw $ex;
						}
					}
				}

				if (isset (\Yii::$app) && isset(\Yii::$app->errorHandler))
				\Yii::$app->errorHandler->handleError($code,$message,$file,$line);
			}, error_reporting());
			self::$errorHandlerRegistered = true;
		}
	}
}
