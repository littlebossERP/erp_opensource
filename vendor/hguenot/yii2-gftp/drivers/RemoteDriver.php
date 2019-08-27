<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace gftp\drivers;

/**
 *
 * @author herve
 */
interface RemoteDriver {
	
	const ASCII = FTP_ASCII;
	const BINARY = FTP_BINARY;
	
	/**
	 * Connect to FTP server.
	 *
	 * throws FtpException If connection failed.
	 */
	public function connect();
	
	/**
	 * Close FTP connection.
	 *
	 * @throws FtpException Raised when error occured when closing FTP connection.
	 */
	public function close();
	
	/**
	 * Log into the FTP server. If connection is not openned, it will be openned before login.
	 *
	 * @throws FtpException if connection failed.
	 */
	public function login ();
	
	/**
	 * Returns list of files in the given directory.
	 *
	 * @param string    $dir           The directory to be listed.
	 *                                 This parameter can also include arguments, eg. $ftp->ls("-la /your/dir");
	 *                                 Note that this parameter isn't escaped so there may be some issues with filenames containing spaces and other characters.
	 * @param string    $full          List full dir description.
	 *
	 * @return FtpFile[] Array containing list of files.
	 */
	public function ls($dir = ".", $full = false);
	
	/**
	 * Create a new folder on FTP server.
	 *
	 * @param string    $dir           Folder to create on server (relative or absolute path).
	 *
	 * @throws FtpException If folder creation failed.
	 */
	public function mkdir($dir);
	
	/**
	 * Removes a folder on FTP server.
	 *
	 * @param string    $dir           Folder to delete from server (relative or absolute path).
	 *
	 * @throws FtpException If folder deletion failed.
	 */
	public function rmdir($dir);
	
	/**
	 * Changes current folder.
	 *
	 * @param string    $dir           Folder to move on (relative or absolute path).
	 *
	 * @return string Current folder on FTP server.
	 *
	 * @throws FtpException If folder deletion failed.
	 */
	public function chdir($dir);
	
	/**
	 * Download a file from FTP server.
	 *
	 * @param string    $remote_file   The remote file path.
	 * @param string    $local_file    The local file path. If set to <strong>null</strong>, file will be downloaded inside current folder using remote file base name).
	 * @param int       $mode          The transfer mode. Must be either <strong>ASCII</strong> or <strong>BINARY</strong>.
	 * @param bool      $asynchronous  Flag indicating if file transfert should block php application or not.
	 *
	 * @return string The full local path (absolute).
	 *
	 * @throws FtpException If an error occcured during file transfert.
	 */
	public function get($remote_file, $local_file = null, $mode = FTP_ASCII, $asynchronous = false);
	
	/**
	 * Upload a file to the FTP server.
	 *
	 * @param string    $local_file    The local file path.
	 * @param string    $remote_file   The remote file path. If set to <strong>null</strong>, file will be downloaded inside current folder using local file base name).
	 * @param int       $mode          The transfer mode. Must be either <strong>ASCII</strong> or <strong>BINARY</strong>.
	 * @param bool      $asynchronous  Flag indicating if file transfert should block php application or not.
	 *
	 * @return string The full local path (absolute).
	 *
	 * @throws FtpException If an error occcured during file transfert.
	 */
	public function put($local_file, $remote_file = null, $mode = FTP_ASCII, $asynchronous = false);
	
	/**
	 * Test existence of file/folder on remote server.
	 * 
	 * @param string $filename File or folder path to test existence.
	 * 
	 * @return boolean `true` if file exists, `false` otherwise.
	 */
	public function fileExists($filename);
	
	/**
	 * Deletes specified files from FTP server.
	 *
	 * @param string    $path          The file to delete.
	 *
	 * @throws FtpException If file could not be deleted.
	 */
	public function delete($path);
	
	/**
	 * Retrieves the file size in bytes.
	 *
	 * @param string    $path          The file to delete.
	 *
	 * @return int File size.
	 *
	 * @throws FtpException If an error occured while retrieving file size.
	 */
	public function size($path);
	
	/**
	 * Renames a file or a directory on the FTP server.
	 *
	 * @param string    $oldname       The old file/directory name.
	 * @param string    $newname       The new name.
	 *
	 * @throws FtpException If an error occured while renaming file or folder.
	 */
	public function rename($oldname, $newname);
	
	/**
	 * Returns the current directory name.
	 *
	 * @return The current directory name.
	 *
	 * @throws FtpException If an error occured while getting current folder name.
	 */
	public function pwd();
	
	/**
	 * Set permissions on a file via FTP.
	 *
	 * @param string    $mode          The new permissions, given as an <strong>octal</strong> value.
	 * @param string    $file          The remote file.
	 *
	 * @throws FtpException If couldn't set file permission.
	 */
	public function chmod($mode, $file);
	
	/**
	 * Gets the last modified time for a remote file.
	 *
	 * @param string    $path          The file from which to extract the last modification time.
	 *
	 * @return string The last modified time as a Unix timestamp on success.
	 *
	 * @throws FtpException If could not retrieve the last modification time of a file.
	 */
	public function mdtm($path);
	
}
