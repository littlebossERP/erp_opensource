<?php 

function write_log($str) {
    global $fdErr,$fd;
    date_default_timezone_set("Asia/Hong_Kong");
    try{
        if (!isset($fd)){
            $fd = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."log".DIRECTORY_SEPARATOR."info".date('Ymd').".log", "a");
            // echo "string";
        }
        fputs($fd, date("Y-m-d H:i:s")." ".$str." \n");
        // echo "1";
    }catch (Exception $e) {
            // echo $e->getMessage()."\n";
    }


}


?>