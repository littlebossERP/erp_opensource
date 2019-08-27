<?php
namespace eagle\components;


class OpenApi {
    protected static $api = [];

    protected $log_path = '/tmp/';

    /*
     * 不对子类公开的方法 开始====================
     */
    protected function __construct() {
        //$this->log('new class '.get_class($this), '/var/log/soso.log');
    }

    protected function write_log($msg, $file) {
        $time = date('Y-m-d H:i:s ');
        $msg = $time.$msg.PHP_EOL;
        error_log($msg, 3, $file);
    }
    /*
     * 不对子类公开的方法 结束======================
     */


    /*
     * 对子类公开的方法 开始======================
     */
    protected function output($data, $code = 0, $msg = '') {
        $output = ['response'=>['code'=>$code, 'msg'=>$msg, 'data'=>$data]];
        return json_encode($output);
    }


    protected function debug($msg) {
        $file = $this->log_path.get_class($this).'.debug';
        $this->write_log($msg, $file);
    }


    protected function log($msg, $file_path = '') {
        if(!empty($file_path) && @is_writable($file_path)) {
            $file = $file_path;
        }else {
            $file = $this->log_path.get_class($this).'.log';
        }
        $this->write_log($msg, $file);

    }
    /*
     * 对子类公开的方法 结束======================
     */

    protected static function explodePath($path, $need_instance = FALSE) {
        $ary_element = explode('.', $path);
        $last_element = array_pop($ary_element);
        if(TRUE === $need_instance) {
            $class_name = ucfirst(array_pop($ary_element));
            $ary_element[] = $class_name;
            $real_path =  implode('\\', $ary_element);
            return array('path'=>$real_path, 'function'=>$last_element);
        }
        return array('function'=>$last_element);
    }


    public static function run($name, $params) {
        if(isset(self::$api[$name])) {
            $path_info = self::explodePath($name);
            $ins = self::$api[$name];
        }else {
            $path_info = self::explodePath($name, true);
            if(class_exists($path_info['path'])) {
                $ins = new $path_info['path']();
                self::$api[$name] = $ins;
            }else {
                return false;
            }
        }

        if(method_exists($ins, $path_info['function'])) {
            return $ins->$path_info['function']($params);
        }else {
            return false;
        }
    }
}