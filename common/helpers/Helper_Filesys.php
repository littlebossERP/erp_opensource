<?php
namespace common\helpers;

use Exception;
// $Id: filesys.php 2212 2009-02-06 00:51:32Z dualface $

/**
 * 定义 Helper_Filesys 类
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @version $Id: filesys.php 2212 2009-02-06 00:51:32Z dualface $
 * @package helper
 */

/**
 * Helper_Filesys 类提供了一组简化文件系统操作的方法
 *
 * 部分方法来自 Yii Framework 框架的 CFileHelper 类，并作了修改。
 *
 * @author YuLei Liao <liaoyulei@qeeyuan.com>
 * @version $Id: filesys.php 2212 2009-02-06 00:51:32Z dualface $
 * @package helper
 */
abstract class Helper_Filesys
{
    /**
     * 遍历指定目录及子目录下的文件，返回所有与匹配模式符合的文件名
     *
     * @param string $dir
     * @param string $pattern
     *
     * @return array
     */
    static function recursionGlob($dir, $pattern)
    {
        $dir = rtrim($dir, '/\\') . DS;
        $files = array();

        // 遍历目录，删除所有文件和子目录
        $dh = opendir($dir);
        if (!$dh) return $files;

        $items = (array)glob($dir . $pattern);
        foreach ($items as $item)
        {
            if (is_file($item)) $files[] = $item;
        }

        while (($file = readdir($dh)))
        {
            if ($file == '.' || $file == '..') continue;

            $path = $dir . $file;
            if (is_dir($path))
            {
                $files = array_merge($files, self::recursionGlob($path, $pattern));
            }
        }
        closedir($dh);

        return $files;
    }

    /**
     * 创建一个目录树，失败抛出异常
     *
     * 用法：
     * @code php
     * Helper_Filesys::mkdirs('/top/second/3rd');
     * @endcode
     *
     * @param string $dir 要创建的目录
     * @param int $mode 新建目录的权限
     *
     * @throw Exception
     */
    static function mkdirs($dir, $mode = 0777)
    {
        if (!is_dir($dir))
        {
            $ret = @mkdir($dir, $mode, true);
            if (!$ret)
            {
                throw new Exception($dir);
            }
        }
        return true;
    }

    /**
     * 删除指定目录及其下的所有文件和子目录，失败抛出异常
     *
     * 用法：
     * @code php
     * // 删除 my_dir 目录及其下的所有文件和子目录
     * Helper_Filesys::rmdirs('/path/to/my_dir');
     * @endcode
     *
     * 注意：使用该函数要非常非常小心，避免意外删除重要文件。
     *
     * @param string $dir 要删除的目录
     *
     * @throw Exception
     */
    static function rmdirs($dir)
    {
        $dir = realpath($dir);
        if ($dir == '' || $dir == '/' || (strlen($dir) == 3 && substr($dir, 1) == ':\\'))
        {
            // 禁止删除根目录
            throw new Exception($dir);
        }

        // 遍历目录，删除所有文件和子目录
        if(false !== ($dh = opendir($dir)))
        {
            while(false !== ($file = readdir($dh)))
            {
                if($file == '.' || $file == '..')
                {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path))
                {
                    self::rmdirs($path);
                }
                else
                {
                    unlink($path);
                }
            }
            closedir($dh);
            if (@rmdir($dir) == false)
            {
                throw new Exception($dir);
            }
        }
        else
        {
            throw new Exception($dir);
        }
    }

    /**
     * 复制一个目录及其子目录的文件到目的地
     *
     * 用法：
     * @code
     * Helper_Filesys::copyDir($src, $dst);
     * @endcode
     *
     * 如果目的地目录不存在，则会创建需要的目录。
     *
     * 可以通过 $options 参数控制要复制的文件，以及复制的深度。
     *
     * $options 参数可用的选项有：
     *
     * -  extnames: 只有指定扩展名的文件被复制
     *    如果不指定该参数，则查找所有扩展名的文件。
     *
     * -  excludes: 排除指定的目录或文件
     *    "upload/avatars" 表示排除 "$dir/upload/avatars" 目录。
     *
     * -  levels: 整数，指定查找的目录深度，默认为 -1。
     *    如果为 0 表示不查找子目录。
     *
     * 注意：copyDirs() 总是会排除所有以“.”开头的目录和文件。
	 */
	static function copyDir($src, $dst, $options=array())
    {
        $extnames = !empty($options['extnames'])
                    ? Q::normalize($options['extnames'])
                    : array();
        foreach ($extnames as $offset => $extname)
        {
            if ($extname[0] == '.')
            {
                $extnames[$offset] = substr($extname, 1);
            }
        }
        $excludes = !empty($options['excludes'])
                    ? Q::normalize($options['excludes'])
                    : array();
        $level    = isset($options['level'])
                    ? intval($options['level'])
                    : -1;
		self::_copyDirectoryRecursive($src, $dst, '', $extnames, $excludes, $level);
	}

    /**
     * 在指定目录及其子目录中查找文件
     *
     * 用法：
     * @code php
     * $files = Helper_FileSys::findFiles($dir, array(
     *     // 只查找扩展名为 .jpg, .jpeg, .png 和 .gif 的文件
     *     'extnames' => 'jpg, jpeg, png, gif',
     *     // 排除 .svn 目录和 upload/avatars 目录
     *     'excludes' => '.svn, upload/avatars',
     *     // 只查找 2 层子目录
     *     'level'    => 2,
     * ));
     * @endcode
     *
     * findFiles() 的 $options 参数支持下列选项：
     *
     * -  extnames: 字符串或数组，指定查找文件时有效的文件扩展名。
     *    如果不指定该参数，则查找所有扩展名的文件。
     *
     * -  excludes: 字符串或数组，指定查找文件时要排除的目录或文件。
     *    "upload/avatars" 表示排除 "$dir/upload/avatars" 目录。
     *
     * -  levels: 整数，指定查找的目录深度，默认为 -1。
     *    如果为 0 表示不查找子目录。
     *
     * findFiles() 返回一个数组，包含了排序后的文件完整路径。
     *
     * @param string|array $dir 要查找文件的目录
     * @param array $options 查找选项
     *
     * @return array 包含有效文件名的数组
	 */
	static function findFiles($dir, $options=array())
    {
        $extnames = !empty($options['extnames'])
                    ? Q::normalize($options['extnames'])
                    : array();
        foreach ($extnames as $offset => $extname)
        {
            if ($extname[0] == '.')
            {
                $extnames[$offset] = substr($extname, 1);
            }
        }
        $excludes = !empty($options['excludes'])
                    ? Q::normalize($options['excludes'])
                    : array();
        $level    = isset($options['level'])
                    ? intval($options['level'])
                    : -1;

        $list = self::_findFilesRecursive($dir, '', $extnames, $excludes, $level);
		sort($list);
		return $list;
    }

    /**
     * 内部使用
	 */
	private static function _copyDirectoryRecursive($src, $dst, $base, $extnames, $excludes, $level)
	{
		@mkdir($dst);
		@chmod($dst,0777);
		$folder = opendir($src);
		while (($file = readdir($folder)))
        {
            if ($file{0} == '.') continue;
			$path = $src . DIRECTORY_SEPARATOR . $file;
			$is_file = is_file($path);
			if(self::_validatePath($base, $file, $is_file, $extnames, $excludes))
			{
                if($is_file)
                {
                    copy($path, $dst . DIRECTORY_SEPARATOR . $file);
                }
                elseif($level)
                {
                    self::_copyDirectoryRecursive($path, $dst . DIRECTORY_SEPARATOR . $file,
                        $base . '/' . $file, $extnames, $excludes, $level - 1);
                }
			}
		}
		closedir($folder);
	}


    /**
     * 递归查找文件，用于 {@link Helper_FileSys::findFiles()}
     *
     * @param string $dir 要查找的源目录
     * @param string $base 与源目录的相对路径
     * @param array $extnames 有效的扩展名
     * @param array $excludes 要排除的文件或目录
     * @param integer $level 要查找的目录深度
     *
     * @return 包含有效文件名的数组
	 */
    private static function _findFilesRecursive($dir, $base, $extnames,
                                                $excludes, $level)
	{
		$list = array();
		$handle = opendir($dir);
		while(($file = readdir($handle)))
		{
			if($file == '.' || $file == '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            $is_file = is_file($path);
            if (self::_validatePath($base, $file, $is_file, $extnames, $excludes))
            {
                if ($is_file)
                {
                    $list[] = $path;
                }
                elseif ($level)
                {
                    $list = array_merge($list, self::_findFilesRecursive($path,
                            $base . '/' . $file, $extnames, $excludes, $level - 1));
                }
            }
		}
		closedir($handle);
		return $list;
	}

	/**
     * 验证文件或目录，返回验证结果
     *
     * @param string $base 与源目录相对路径
     * @param string $file 文件名或目录名
     * @param boolean $is_file 是否是文件
     * @param array $extnames 有效的文件扩展名
     * @param array $excludes 要排除的文件名或目录
     *
	 * @return boolean 该文件是否通过验证
	 */
    private static function _validatePath($base, $file, $is_file,
                                           $extnames,  $excludes)
    {
        $test = ltrim(str_replace('\\', '/', "/{$base}/{$file}"), '/');
		foreach($excludes as $e)
        {
            if ($file == $e || $test == $e) return false;
		}
        if(!$is_file || empty($extnames)) return true;

		if(($pos = strrpos($file, '.')) !==false)
		{
			$type = substr($file, $pos + 1);
			return in_array($type, $extnames);
		}
        else
        {
            return false;
        }
	}
}

