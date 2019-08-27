<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.com All rights reserved.
+----------------------------------------------------------------------
| Author: 韩兴鲁
+----------------------------------------------------------------------
| Create Date: 2014-06-13
+----------------------------------------------------------------------
 * @version		1.0
 +------------------------------------------------------------------------------
 */

/**
+------------------------------------------------------------------------------
 * 删除站内信EBAY接口类
+------------------------------------------------------------------------------
 * @category	ebayinterface
 * @package		vendors/ebayinterface
 */
class EbayInterfaceReviseMyMessages
{
    /**
    +----------------------------------------------------------
     * 调用EBAY  DeleteMyMessages接口
    +----------------------------------------------------------
     * @access static
    +----------------------------------------------------------
     * @param tokenMessage	        以EBAY TOKEN为索引的数组
    +----------------------------------------------------------
     * @return	数组			    删除站内信EBAY反馈
    +----------------------------------------------------------
     **/
    public static function ReviseMyMessages($tokenMessage, $read=null, $flag=null)
    {
        if(EbayInterface_config::$production){
            $url = 'https://api.ebay.com/ws/api.dll';
            $header = array(
                'X-EBAY-API-COMPATIBILITY-LEVEL: 767',
                'X-EBAY-API-DEV-NAME: ae6a5aaf-a223-4030-a01e-7efacc164628',
                'X-EBAY-API-APP-NAME: witsion8f-f31c-4795-9061-643836f62f5',
                'X-EBAY-API-CERT-NAME: 4f003b00-994a-4431-962b-553945994363',
                'X-EBAY-API-CALL-NAME: ReviseMyMessages',
                'X-EBAY-API-SITEID: 0',
                'Content-type: text/xml'
            );
        }else{
            $url = 'https://api.sandbox.ebay.com/ws/api.dll';
            $header = array(
                'X-EBAY-API-COMPATIBILITY-LEVEL: 767',
                'X-EBAY-API-DEV-NAME: ae6a5aaf-a223-4030-a01e-7efacc164628',
                'X-EBAY-API-APP-NAME: witsion0f-99db-47c8-b875-6f1c9e6d7d2',
                'X-EBAY-API-CERT-NAME: 49c68ed3-4b9f-41af-b966-e6a06695a552',
                'X-EBAY-API-CALL-NAME: ReviseMyMessages',
                'X-EBAY-API-SITEID: 0',
                'Content-type: text/xml'
            );
        }


        $xmlhead = '<?xml version="1.0" encoding="utf-8"?><ReviseMyMessagesRequest xmlns="urn:ebay:apis:eBLBaseComponents">';

        $result = array();
        $errorToken = array();
        foreach($tokenMessage as $k=>$v){
            if(empty($v)) continue; //如果该TOKEN下没有站内信就继续循环
            $xml = $xmlhead;
            $xml .= "<RequesterCredentials><eBayAuthToken>$k</eBayAuthToken></RequesterCredentials><WarningLevel>High</WarningLevel>";
            if(!is_null($read)) $xml .= "<Read>".$read."</Read>";
            if(!is_null($flag)) $xml .= "<Flagged>".$flag."</Flagged>";
            $xml .= "<MessageIDs>";
            $n = 1;
            foreach($v as $id){
                $n++;
                $xml .= "<MessageID>$id</MessageID>";
                //一次最多删除10个
                if($n > 9){
                    $xml .= '</MessageIDs></ReviseMyMessagesRequest>';
                    $response = self::mypost($url, $xml, $header);
                    //如果TOKEN错误就跳过
                    if($response->Errors->ErrorCode == 931){
                        $errorToken[] = $k;
                        continue 2;
                    }
                    $result[] = $response;
                    $n = 1;
                    $xml = $xmlhead;
                    $xml .= "<RequesterCredentials><eBayAuthToken>$k</eBayAuthToken></RequesterCredentials><MessageIDs>";
                }
            }
            $xml .= '</MessageIDs></ReviseMyMessagesRequest>';
            $response = self::mypost($url, $xml, $header);
            //如果TOKEN错误就跳过
            if($response->Errors->ErrorCode == 931){
                $errorToken[] = $k;
                continue;
            }
            $result[] = $response;
        }

        //生成未成功数组
        $result = self::errorOperate($result);
        //如果存在token无效
        if(!empty($errorToken)){
            foreach($errorToken as $key){
                foreach($tokenMessage[$key] as $messageid){
                    $result[$messageid] = 'Token error! 请重新绑定Ebay帐号';
                }
            }
        }
        return $result;
    }
    /**
    +----------------------------------------------------------
     * curl 反馈xml信息转换为对象
    +----------------------------------------------------------
     * @access static
    +----------------------------------------------------------
     * @param tokenMessage	        以EBAY TOKEN为索引的数组
    +----------------------------------------------------------
     * @return	数组			    删除站内信EBAY反馈
    +----------------------------------------------------------
     **/
    static public function mypost($url, $xml, $header)
    {
        $response = Helper_Curl::post($url, $xml, $header);
        return simplexml_load_string($response);
    }
    /**
    +----------------------------------------------------------
     * 反回数组错误处理函数
    +----------------------------------------------------------
     * @access static
    +----------------------------------------------------------
     * @param tokenMessage	        以EBAY TOKEN为索引的数组
    +----------------------------------------------------------
     * @return	数组			    删除站内信EBAY反馈
    +----------------------------------------------------------
     **/
    static public function errorOperate($data)
    {
        $result = array();
        foreach($data as $d){
            //只保存不能同步的站内信ID
            if($d->Ack == 'Success') continue;
            foreach($d->Errors as $error){
                $key = $error->ErrorParameters->Value;
                $result["$key"] = strval($error->LongMessage);
            }
        }
        return $result;
    }
}


