<?php
// @codingStandardsIgnoreFile
require_once __DIR__.'/../vendor/autoload.php';

use Qiniu\Auth;

// TODO qiniu dev account @XXX@ // 未知作用
$accessKey = '@XXX@';
$secretKey = '@XXX@';
$testAuth = new Auth($accessKey, $secretKey);
$bucketName = 'phpsdk';
$key = 'php-logo.png';

$dummyAccessKey = 'abcdefghklmnopq';
$dummySecretKey = '1234567890';
$dummyAuth = new Auth($dummyAccessKey, $dummySecretKey);

$tid = getenv('TRAVIS_JOB_NUMBER');

$testEnv = getenv('QINIU_TEST_ENV');

if (!empty($tid)) {
    $pid = getmypid();
    $tid = strstr($tid, '.');
    $tid .= '.' . $pid;
}

function qiniuTempFile($size)
{
    $fileName = tempnam(sys_get_temp_dir(), 'qiniu_');
    $file = fopen($fileName, 'wb');
    if ($size > 0) {
        fseek($file, $size-1);
        fwrite($file, ' ');
    }
    fclose($file);
    return $fileName;
}
