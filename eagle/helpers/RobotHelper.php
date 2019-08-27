<?php
namespace eagle\helpers;

use \Yii;
use eagle\models\QueueIpList;

class RobotHelper{

    private static $_get_html_num= 0;//防止一直死循环
    private static $_get_ip_row= 0;//防止一直死循环
    private static $_can_get_num= 5;//如果失败,默认内置循环次数,防止死循环

    /**
     * 抓取内容,返回html
     * $url string
     * @todo 抓取过程,如果代理IP一直失败,会循环取几次,通过上面设定控制循环获取几次
     * akirametero
     */
    public static function getHtml( $url,$proxy=false ){
        //获取url的host
        $url_info= parse_url( $url );
        if( !isset( $url_info['host'] ) ){
            return false;
        }
        $scheme= $url_info['scheme'];
        $host= $url_info['host'];
        $referer=
        //爬虫curl
        $header = array (
            "Host:{$host}",
            "Referer:{$scheme}://{$host}",
            'User-Agent: Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; BIDUBrowser 2.6)'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //代理模式
        if( $proxy===true  ){
            //获取一个可用的随机IP信息
            //url是https还是http,截取开头的5个匹配
            $str= mb_substr( $url,0,5 );
            if( $str=='https' ){
                $type= 'https';
            }elseif( $str=='http' ){
                $type= 'http';
            }else{
                return false;
            }
            $rs_ip= self::getIpRow( $type );//这个方法里面,可能循环几次
            if( $rs_ip===false ){//这里挂了就是表示IP库一直刷不到可用IP
                return false;
            }
            $proxyip= $rs_ip['ip'].":".$rs_ip['port'];
            curl_setopt($ch, CURLOPT_PROXY, $proxyip);//IP:port
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 检查证书中是否设置域名
            //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式
            //curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);//代理类型
        }
        //超时秒数设定,最好设置下,否则卡那里就不好玩了
        curl_setopt($ch,CURLOPT_TIMEOUT,60);
        $result= curl_exec($ch);
        curl_close ($ch);
        unset($ch);
        //如果没有获取到数据,那这个IP是不能用了嘛,删除掉就行了
        if( $proxy===true && empty( $result ) ){

            QueueIpList::deleteAll(['ip'=>$rs_ip['ip']]);
            //再爬取一次html
            if( self::$_get_html_num>=self::$_can_get_num ){
                return false;
            }
            self::getHtml( $url,true );
            self::$_get_html_num++;
        }
        return $result;
    }
    //end function



    /**
     * 获取新的IP资源,写入IP库,入库前需要知道是否可用,不可用就不用写了
     * $api_url api地址,生成的
     * $type http/https
     * @todo  需要区分IP适用在http还是https,获取IP的API地址:http://www.kuaidaili.com/genapiurl
     * @todo http和http的api接口数据分开获取
     *akirametero
     */
    public static function setIpList( $type='http' ){
        if( $type=='http' ){
            $http_url= 'http://www.amazon.com/gp/pdp/profile/ALYZJ7W14YS26';
            $api_url= '';
        }else{
            $http_url= 'https://www.amazon.com/gp/pdp/profile/A46C9XPKN7ZBM';
            $api_url= '';
        }

        $result= self::getHtml( $api_url );
        $arr= json_decode( $result,true );
        if( isset( $arr ) ){
            $data= self::getArr( $arr,'data' );
            if( empty( $data ) ){
                return false;
            }
            $proxy_list= self::getArr( $data,'proxy_list' );
            if( !empty( $proxy_list ) ){
                //循环写入IP库
                foreach( $proxy_list as $proxy_vss ){
                    if( $proxy_vss!=''  ){
                        $iparr= explode(':',$proxy_vss);
                        //需要合格的IP,带端口号
                        if( count($iparr)==2 ){
                            //是否可用,分别测试https和http,根据type
                            $reshtml= self::getHtml( $http_url,true,$proxy_vss );
                            //如果返回是空,那就是这个IP不能用,那就不用写进去了
                            if( !empty( $reshtml ) ){
                                //如果IP存在了,也不用写了
                                $rs= QueueIpList::findOne(['ip'=>$iparr[0]]);
                                if( empty( $rs ) ){
                                    $obj= new QueueIpList();
                                    $obj->ip= $iparr[0];
                                    $obj->port= $iparr[1];
                                    $obj->protocol= $type;
                                    $obj->is_can= 1;
                                    $obj->createtime= time();
                                    $obj->save(false);
                                    return true;
                                }
                            }
                        }
                    }
                }
            }else{
                return false;
            }
        }
    }
    //end function



    /**
     * 获取可用IP:port,随机用其中一个
     * akirametero
     */
    private static function getIpRow( $type='http' ){

        $connection= Yii::$app->db_queue;
        $sql= "SELECT * FROM `queue_ip_list` AS t1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `queue_ip_list`)-(SELECT MIN(id) FROM `queue_ip_list`))+(SELECT MIN(id) FROM `queue_ip_list`)) AS id) AS t2 WHERE t1.id >= t2.id AND protocol='{$type}' AND is_can=1  ORDER BY t1.id LIMIT 1";
        $res= $connection->createCommand( $sql )->query()->read();
        //如果没有一个可用的IP了,加载一次IPlist
        if( empty( $res ) ){
            //再爬取一次html
            if( self::$_get_ip_row>=self::$_can_get_num ){
                return false;
            }
            //更新IP库
            self::setIpList($type);
            //更新完毕了,再获取一次可用IP..
            self::getIpRow($type);
            self::$_get_ip_row++;

        }
        return $res;

    }
    //end function


    /**
     * 爬取邮件用的,amzhelper
     * akirametero
     */
    public static function getAmzhelperEmailHtmlInfo( $page ){
        $cookie_dir= '/Library/WebServer/xiaolaoban/trunk2/eagle/runtime/logs/cookie.txt';

        $url= "http://www.amzhelper.com/usermanage/product_top_reviewers.php?goodsId=9861&country=&currentPage=".$page;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_dir); //读取cookie
        $html = curl_exec($ch); //执行cURL抓取页面内容
        curl_close($ch);
        return $html;
    }
    //end function


    /**
     * amzhelper登录用curl
     * akirametero
     */
    public static function setAmzhelperLogin(){
//这个是登录用的ajax地址
        $url= 'http://www.amzhelper.com/AjaxRequest/checkLogin.php?userEmail=shazishazi168@126.com&userPass=sss12369875&Remember=false&t0.842723085738222';
        $cookie_dir= '/Library/WebServer/xiaolaoban/trunk2/eagle/runtime/logs/cookie.txt';

        $curl = curl_init();//初始化curl模块
        curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);//是否自动显示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_dir); //设置Cookie信息保存在指定的文件中
        curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源
    }



    /**
     * 返回数组中对应的key的value
     * akirametero
     * @param $arr
     * @param $field
     * @return array
     *
     */
    private static function getArr( $arr,$key ){
        $arr= array();
        if( empty( $arr ) ){
            return $arr;
        }else{
            if( isset( $arr[$key] ) ){
                return $arr[$key];
            }else{
                return $arr;
            }
        }
    }
    //end function



}