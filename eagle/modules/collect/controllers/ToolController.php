<?php namespace eagle\modules\collect\controllers;

use yii;
use yii\data\Pagination;
//use eagle\modules\tracking\helpers\phpQuery;

use eagle\modules\collect\helpers\CollectHelper;
use eagle\helpers\RobotHelper;

class ToolController extends \yii\web\Controller
{
	public $enableCsrfValidation = false;
	public static $URL ='';

	/**
    private static function extract_attrib($tag,$platfrom) {
        preg_match_all('/(src)=("[^"]*")/i', $tag, $matches);
        $ret = array();
        if($platfrom != '1688'){
            foreach($matches[1] as $key => $i) {
                preg_match_all('/\"(.*?)\"/',$matches[2][$key],$imgs);
                $ret[$key] = str_replace(strstr($imgs[1][0],'.jpg'),'.jpg',$imgs[1][0]);
            }
        }else{
            foreach($matches[1] as $key => $i) {
                preg_match_all('/\"(.*?)\"/',$matches[2][$key],$imgs);
                $ret[$key] = str_replace(strstr($imgs[1][0],'.60x60'),'.jpg',$imgs[1][0]);
            }
        }
        return $ret;
    }

	public function actionTest(){
        $user_id= 1;
		$url ='/Library/WebServer/xiaolaoban/trunk2/eagle/web/attachment/crawl_html/';
		//$filename = "amazon.html";
        $filename = "ebey.html";
		phpQuery::newDocumentFile($url.$filename);
        $detail_src= phpQuery::pq('#desc_ifr')->attr('src');
        //print_r ($detail_src);exit;
        //存在介绍
        if( $detail_src!='' ){
            //获取src的内容,写入html
            $detail_content= file_get_contents($detail_src);
            $fn= 'ebey_content_'.$user_id.'.html';
            $user_data = fopen($url.$fn,"w");
            fwrite($user_data,$detail_content);
            phpQuery::newDocumentFile($url.$fn);
            $description['description']= phpQuery::pq('#ds_div')->html();
            print_r ($description);exit;
        }else{
            $description['description']= '';
        }

	}
	**/

	/*
	 *  数据采集
	 * @author yht
	 */
	public function actionRun(){

		header("Access-Control-Allow-Origin: *");
		if(YII::$app->params['currentEnv'] == 'production' || !isset(YII::$app->params['currentEnv'])){

			self::$URL='/var/www/eagle2/eagle/web/attachment/crawl_html/';
		} else {

			self::$URL='/eagle/web/attachment/crawl_html/';
		}
        //self::$URL='/Library/WebServer/xiaolaoban/trunk2/eagle/web/attachment/crawl_html/';

		//获取用户ID
		$uid = \Yii::$app->user->id;
        //$uid= 1;
		//判断用户是否登录
		if($uid == ""){
			$res['status'] = 1;
			$res['msg'] = '请登录小老板账号';
			return json_encode($res);
		}
		$request = \Yii::$app->request;
		$data = $request->post('commodity');
		$url = $request->post('url');
		$urlfrom = parse_url($url);
		//判断数据内容是否存在
		if($data != ""){
			//取出body内容
			preg_match("/<body.*?>(.*?)<\/body>/is", $data, $body);
			$html  = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
							</head>'.$body[0].'</html>';
		}else{
			$res['status'] = 1;
			$res['msg'] = '数据未能准确获取';
			return  json_encode($res);
		}
		//临时文件名称
		$filename = "user_".$uid.".html";

		if($urlfrom['host']=="www.aliexpress.com" && $urlfrom['path'] !== "/"){
            //速卖通平台
			$info= CollectHelper::getAlipressInfo( self::$URL,$filename,$html,$urlfrom );
		}else if($urlfrom['host']=="detail.tmall.com"){
            //天猫商城
            $info= CollectHelper::getTmallInfo( self::$URL,$filename,$html,$urlfrom );
		}else if($urlfrom['host']=="detail.1688.com"){
            //1688商城采集
            $info= CollectHelper::get1688Info( self::$URL,$filename,$html,$urlfrom );
		}else if($urlfrom['host']=="item.taobao.com"){
            //淘宝商城采集
            $info= CollectHelper::getTaoBaoInfo( self::$URL,$filename,$html,$urlfrom );
		}elseif( $urlfrom['host']=='www.amazon.com' ){
            //亚马孙平台
            $info= CollectHelper::getAmazonInfo( self::$URL,$filename,$html,$urlfrom );
        }else if( $urlfrom['host']=='www.ebay.com' ){
            //ebey平台
            $info= CollectHelper::getEbeyInfo( self::$URL,$filename,$html,$urlfrom,$uid );
        }
		else{
			$res['status'] = 1;
			$res['msg'] = '相关平台开发中';
			return  json_encode($res);
		}
		//数据存储
        //print_r ($info);exit;
        $result= CollectHelper::saveInfo( $info );
        return $result;
	}

	/**
	 * 这里用来爬去邮箱的,amzhelper
	 *
	 * akirametero
	 */
	public function actionEmail(){
		CollectHelper::get_email_info( 1,30 );
	}
	//end function

	/**
	 *
	 * akirametero
	 */
	public function actionAmazon(  ){
		self::$URL='/var/www/eagle2/eagle/web/attachment/crawl_html/';

		self::$URL='/Library/WebServer/xiaolaoban/trunk2/eagle/web/attachment/crawl_html/';
		//需要抓取的url
		$url= 'https://www.amazon.cn/gp/product/B010S9M3L6';
		$html= RobotHelper::getHtml($url);//print_r ($html);exit;
		//print_r ($html);exit;
		//爬虫开始
		if( empty($html) ){
			echo 'html空,没有爬取到';exit;
		}
		//临时文件名称
		$uid= 1;
		$filename = "user_".$uid.".html";
		$urlfrom = parse_url($url);
		$result= CollectHelper::getAmazonInfo( self::$URL,$filename,$html,$urlfrom );
		print_r($result);exit;
	}
	//end function

}