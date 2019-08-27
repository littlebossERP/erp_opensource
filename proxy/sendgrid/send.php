<?php
// using SendGrid's PHP Library
// https://github.com/sendgrid/sendgrid-php

// If you are using Composer (recommended)
//require 'vendor/autoload.php';

// If you are not using Composer
require "sendgrid/sendgrid-php.php";
$time1 = time();

// TODO add send mail info @XXX@ sendgrid apikey
$apiKey = '@XXX@';

$rtn = array("code"=>200,"response"=>"");
$errors = array();

$isBasc64 = false;
if(!empty($_REQUEST['isBasc64']))
	$isBasc64 = true;
// #test data
// $fromEmail = "service@littleboss.com";
// $fromName = "小老板 ERP";

$fromEmail = @$_REQUEST['fromEmail'];	//"service@littleboss.com"
$fromName = @$_REQUEST['fromName'];	//"小老板 ERP"

if(empty($fromEmail) || empty($fromName)){
	$errors[] = "sender email or name is empty!";
	exit(json_encode(array("code"=>200,"response"=>json_encode($errors))));
}

// #test data
// $toEmail = "102875908@qq.com";
// $toName = "Mr.Lu";

$toEmail = @$_REQUEST['toEmail'];
$toName = @$_REQUEST['toName'];
if(empty($toEmail)){
	$errors[] = "recipient email is empty!";
	exit(json_encode(array("code"=>200,"response"=>json_encode($errors))));
}

// #test data
// $subject = "Sending with SendGrid is Fun";
// $content = "and easy to do anywhere, even with PHP";

$subject = @$_REQUEST['subject'];
$content = @$_REQUEST['content'];

if($isBasc64){
	$subject = base64_decode($subject);
	$content = base64_decode($content);
}
$time2 = time();
// echo "\n step 1 used time:".($time2-$time1);
$from = new SendGrid\Email($fromName,$fromEmail);
$to = new SendGrid\Email($toName,$toEmail);
$content = new SendGrid\Content("text/html", $content);
$time3 = time();
// echo "\n step 2 used time:".($time3-$time2);
$mail = new SendGrid\Mail($from, $subject, $to, $content);
// $apiKey = getenv('SENDGRID_API_KEY');
$sg = new \SendGrid($apiKey);
$time4 = time();
// echo "\n step 3 used time:".($time4-$time3);
$response = $sg->client->mail()->send()->post($mail);
$time5 = time();
// echo "\n step 4 used time:".($time5-$time4);
$rtn['code'] = $response->statusCode();
$rtn['response'] = $response->body();
exit(json_encode($rtn));
?>