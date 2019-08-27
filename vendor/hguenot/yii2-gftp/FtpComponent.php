<?php

namespace gftp;

use \gftp\drivers\RemoteDriver;
use \Yii;
use \yii\base\Event;

/**
 * Component used to manage FTP connection
 * 
 * @author Herve Guenot
 * @link http://www.guenot.info
 * @copyright Copyright &copy; 2012 Herve Guenot
 * @license GNU LESSER GPL 3
 * @version 1.0
 */
class FtpComponent extends \yii\base\Component {
	
	/**
	 * @var RemoteDriver FTP handle.
	 */
	private $handle;
	
	/**
	 * @var array Driver options.
	 */
	private $driverOptions = [];

	/**
	 * @var string Connection string
	 */
	private $connectionString = null;

	public function init() {
		parent::init();

		FtpUtils::registerTranslation();
		
		if ($this->connectionString != null) {
			$this->setConnectionString($this->connectionString);
		}
		$this->parseConnectionString();
	}
	
	/**
	 * Destructor. Try to close FTP connection.
	 */
	public function __destruct() {
		try {
			$this->close();
		} catch(\Exception $ex){
			// silently close...
		}
	}
		
	/**
	 * Sets a new connection string. If connection is already openned, try to close it before.
	 *
	 * @param string $connectionString FTP connection string (like ftp://[<user>[:<pass>]@]<host>[:<port>])
	 *
	 * @throws FtpException if <i>connectionString</i> is not valid or if could not close an already openned connection.
	 */
	public function setConnectionString($connectionString) {
		if (!isset($connectionString) || !is_string($connectionString) || trim($connectionString) === "") {
			throw new FtpException(
				Yii::t('gftp', '{connectString} is not a valid connection string', [ 
					'connectString' => $connectionString
				])
			);
		}
		
		$this->close();
		$this->connectionString = $connectionString;
	}
	
	private function parseConnectionString() {
		if (isset($this->connectionString) && is_string($this->connectionString) && 
				trim($this->connectionString) !== "") {
			try {
				$p = new FtpParser();
				$parts = $p->parse($this->connectionString);
			} catch (Exception $e) {
				throw new FtpException(
					Yii::t('gftp', '{connectString} is not a valid connection string: {message}', [
						'connectString' => $this->connectionString, 
						'message' => $e->getMessage()
					])
				);
			}

			$this->close();
			$this->driverOptions = array_merge($this->driverOptions, $parts);
		}
	}

	/**
	 * Returns the connection string with or without password.
	 *
	 * @param bool      $withPassword     if <strong>TRUE</strong>, include password in returned connection string.
	 *
	 * @return string Connection string.
	 */
	public function getConnectionString($withPassword=false) {
		return $this->connectionString;
	}

	/**
	 * Sets the driver options as an array.
	 * It must define a 'class' key representing the driver class name.
	 * 
	 * @param array $driverOptions Driver connection options.
	 */
	public function setDriverOptions(array $driverOptions) {
		$this->driverOptions = $driverOptions;
	}
	
	/** 
	 * @return array The driver options.
	 */
	public function getDriverOptions() {
		return $this->driverOptions;
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
			if ($login)
				$this->login();
		}
	}

	// *************************************************************************
	// REMOTE WRAPPER METHODS
	// *************************************************************************
	/**
	 * Connect to FTP server.
	 *
	 * throws FtpException If connection failed.
	 */
	public function connect() {
		if (isset($this->handle) && $this->handle != null) {
			$this->close();
		}
		
		$this->parseConnectionString();
		$this->handle = \Yii::createObject($this->driverOptions);
		$this->handle->connect();
		$this->onConnectionOpen(new Event(['sender' => $this]));
	}


	/**
	 * Log into the FTP server. If connection is not openned, it will be openned before login.
	 *
	 * @param string    $user          Username used for log on FTP server.
	 * @param string    $password      Password used for log on FTP server.
	 *
	 * @throws FtpException if connection failed.
	 */
	public function login () {
		$this->connectIfNeeded(false);
		$this->handle->login();
		$this->onLogin(new Event(['sender' => $this, 'data' => $this->handle->user]));
	}

	/**
	 * Returns list of files in the given directory.
	 *
	 * @param string    $dir           The directory to be listed.
	 *                                 This parameter can also include arguments, eg. $ftp->ls("-la /your/dir");
	 *                                 Note that this parameter isn't escaped so there may be some issues with filenames containing spaces and other characters.
	 * @param string    $full          List full dir description.
	 * @param string    $recursive     Recursively list folder content
	 *
	 * @return FtpFile[] Array containing list of files.
	 */
	public function ls($dir = ".", $full = false, $recursive = false) {
		$this->connectIfNeeded();
		return $this->handle->ls($dir, $full, $recursive);
	}

	/**
	 * Close FTP connection.
	 *
	 * @throws FtpException Raised when error occured when closing FTP connection.
	 */
	public function close() {
		if (isset($this->handle) && $this->handle != null) {
			$this->handle->close();
			$this->handle = false;
			$this->onConnectionClose(new Event(['sender' => $this]));
		}
	}

	/**
	 * Create a new folder on FTP server.
	 *
	 * @param string    $dir           Folder to create on server (relative or absolute path).
	 *
	 * @throws FtpException If folder creation failed.
	 */
	public function mkdir($dir) {
		$this->connectIfNeeded();
		$this->handle->mkdir($dir);
		$this->onFolderCreated(new Event(['sender' => $this, 'data' => $dir]));
	}

	/**
	 * Removes a folder on FTP server.
	 *
	 * @param string    $dir           Folder to delete from server (relative or absolute path).
	 *
	 * @throws FtpException If folder deletion failed.
	 */
	public function rmdir($dir) {
		$this->connectIfNeeded();
		$this->handle->rmdir($dir);
		$this->onFolderDeleted(new Event(['sender' => $this, 'data' => $dir]));
	}

	/**
	 * Changes current folder.
	 *
	 * @param string    $dir           Folder to move on (relative or absolute path).
	 *
	 * @return string Current folder on FTP server.
	 *
	 * @throws FtpException If folder deletion failed.
	 */
	public function chdir($dir) {
		$this->connectIfNeeded();
		$this->handle->chdir($dir);
		$this->onFolderChanged(new Event(['sender' => $this, 'data' => $dir]));
		try {
			$cwd = $this->pwd();
		} catch (FtpException $ex) {
			$cwd = $dir;	
		}
		return $cwd;
	}

	/**
	 * Download a file from FTP server.
	 *
	 * @param string    $remote_file   The remote file path.
	 * @param string    $local_file    The local file path. If set to <strong>null</strong>, file will be downloaded inside current folder using remote file base name).
	 * @param int       $mode          The transfer mode. Must be either <strong>FTP_ASCII</strong> or <strong>FTP_BINARY</strong>.
	 * @param bool      $asynchronous  Flag indicating if file transfert should block php application or not.
	 *
	 * @return string The full local path (absolute).
	 *
	 * @throws FtpException If an error occcured during file transfert.
	 */
	public function get($remote_file, $local_file = null, $mode = FTP_ASCII, $asynchronous = false) {
		$this->connectIfNeeded();		
		$local_file = $this->handle->get($remote_file, $local_file,$mode, $asynchronous);
		$this->onFileDownloaded(new Event(['sender' => $this, 'data' => $local_file]));
		return $local_file;
	}

	/**
	 * Upload a file to the FTP server.
	 *
	 * @param string    $local_file    The local file path.
	 * @param string    $remote_file   The remote file path. If set to <strong>null</strong>, file will be downloaded inside current folder using local file base name).
	 * @param int       $mode          The transfer mode. Must be either <strong>FTP_ASCII</strong> or <strong>FTP_BINARY</strong>.
	 * @param bool      $asynchronous  Flag indicating if file transfert should block php application or not.
	 *
	 * @return string The full local path (absolute).
	 *
	 * @throws FtpException If an error occcured during file transfert.
	 */
	public function put($local_file, $remote_file = null, $mode = FTP_ASCII, $asynchronous = false) {
		$this->connectIfNeeded();
		$full_remote_file = $this->handle->put($local_file, $remote_file, $mode, $asynchronous);
		$this->onFileUploaded(new Event(['sender' => $this, 'data' => $remote_file]));
		return $full_remote_file;
	}

	/**
	 * Test existence of file/folder on remote server.
	 * 
	 * @param string $filename File or folder path to test existence.
	 * 
	 * @return boolean `true` if file exists, `false` otherwise.
	 */
	public function fileExists($filename) {
		$this->connectIfNeeded();
		return $this->handle->fileExists($filename);
	}

	/**
	 * Deletes specified files from FTP server.
	 *
	 * @param string    $path          The file to delete.
	 *
	 * @throws FtpException If file could not be deleted.
	 */
	public function delete($path) {
		$this->connectIfNeeded();
		$this->handle->delete($path);
		$this->onFileDeleted(new Event(['sender' => $this, 'data' => $path]));
	}

	/**
	 * Retrieves the file size in bytes.
	 *
	 * @param string    $path          The file to delete.
	 *
	 * @return int File size.
	 *
	 * @throws FtpException If an error occured while retrieving file size.
	 */
	public function size($path) {
		$this->connectIfNeeded();
		return $this->handle->size($path);
	}

	/**
	 * Renames a file or a directory on the FTP server.
	 *
	 * @param string    $oldname       The old file/directory name.
	 * @param string    $newname       The new name.
	 *
	 * @throws FtpException If an error occured while renaming file or folder.
	 */
	public function rename($oldname, $newname) {
		$this->connectIfNeeded();
		$this->handle->rename($oldname, $newname);
		$this->onFileRenamed(
			new Event(['sender' => $this, 'data' => [
				'oldname' => $oldname, 
				'newname' => $newname
			]])
		);
	}

	/**
	 * Returns the current directory name.
	 *
	 * @return The current directory name.
	 *
	 * @throws FtpException If an error occured while getting current folder name.
	 */
	public function pwd() {
		$this->connectIfNeeded();
		return $this->handle->pwd();
	}

	/**
	 * Set permissions on a file via FTP.
	 *
	 * @param string    $mode          The new permissions, given as an <strong>octal</strong> value.
	 * @param string    $file          The remote file.
	 *
	 * @throws FtpException If couldn't set file permission.
	 */
	public function chmod($mode, $file) {
		$this->connectIfNeeded();
		$this->handle->chmod($mode, $file);
		$this->onFileModeChanged(
			new Event(['sender' => $this, 'data' => [
				'mode' => $mode, 'file' => $file
			]])
		);
	}

	/**
	 * Gets the last modified time for a remote file.
	 *
	 * @param string    $path          The file from which to extract the last modification time.
	 *
	 * @return string The last modified time as a Unix timestamp on success.
	 *
	 * @throws FtpException If could not retrieve the last modification time of a file.
	 */
	public function mdtm($path) {
		$this->connectIfNeeded();
		return $this->handle->mdtm($path);
	}

	/* *********************************
	 * EVENTS SECTION
	 */
	/**
	 * Raised when connection to FTP server was openned.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onConnectionOpen($event) {
		$this->trigger('onConnectionOpen', $event);
	}

	/**
	 * Raised when connection to FTP server was closed.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onConnectionClose($event) {
		$this->trigger('onConnectionClose', $event);
	}

	/**
	 * Raised when users has logged in on the FTP server.
	 * Username is stored in : <code>$event->params</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onLogin($event) {
		$this->trigger('onLogin', $event);
	}

	/**
	 * Raised when a folder was created on FTP server.
	 * Folder name is stored in : <code>$event->params</code>.
	 *
	 * @param $event CEvent Event parameter.
	 */
	public function onFolderCreated($event) {
		$this->trigger('onFolderCreated', $event);
	}

	/**
	 * Raised when a folder was deleted on FTP server.
	 * Folder name is stored in : <code>$event->params</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFolderDeleted($event) {
		$this->trigger('onFolderDeleted', $event);
	}

	/**
	 * Raised when current FTP server directory has changed.
	 * New current folder is stored in : <code>$event->params</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFolderChanged($event) {
		$this->trigger('onFolderChanged', $event);
	}

	/**
	 * Raised when a file was downloaded from FTP server.
	 *
	 * Local filename is stored in : <code>$event->params['local_file']</code>.
	 * Remote filename is stored in : <code>$event->params['remote_file']</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFileDownloaded($event) {
		$this->trigger('onFileDownloaded', $event);
	}

	/**
	 * Raised when a file was uploaded to FTP server.
	 *
	 * Local filename is stored in : <code>$event->params['local_file']</code>.
	 * Remote filename is stored in : <code>$event->params['remote_file']</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFileUploaded($event) {
		$this->trigger('onFileUploaded', $event);
	}

	/**
	 * Raised when file's permissions was changed on FTP server.
	 *
	 * Remote filename is stored in : <code>$event->params['file']</code>.
	 * New permisseion are stored in octal value in : <code>$event->params['mode']</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFileModeChanged($event) {
		$this->trigger('onFileModeChanged', $event);
	}

	/**
	 * Raised when a file was deleted on FTP server.
	 * Remote filename is stored in : <code>$event->params</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFileDeleted($event) {
		$this->trigger('onFileDeleted', $event);
	}

	/**
	 * Raised when a file or folder was renamed on FTP server.
	 * Old filename is stored in : <code>$event->params['oldname']</code>.
	 * New filename is stored in : <code>$event->params['newname']</code>.
	 *
	 * @param $event \yii\base\Event Event parameter.
	 */
	public function onFileRenamed($event) {
		$this->trigger('onFileRenamed', $event);
	}
	
}
