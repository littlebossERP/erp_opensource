<?php
namespace eagle\modules\collect\helpers;
use eagle\modules\tracking\helpers\phpQuery;
use eagle\modules\collect\models\GoodscollectAll;
use eagle\helpers\RobotHelper;

/**
 * Class CollectHelper
 * @package eagle\modules\customer\helpers
 * @author akirametero
 * 采集类方法
 */


class CollectHelper {

	/**
	 * 文件名获取,数据写入
	 * @author akirametero
	 */
	private static function getFileName( $file,$name,$html ){
		$user_data = fopen($file.$name,"w");
		$result= fwrite($user_data,$html);
		return $file.$name;
	}
	//end function

    //获取小图src
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

	/**
	 * 速卖通平台采集内容
	 * @author akirametero
	 * @return array
	 */
	public static function getAlipressInfo( $file,$filename,$html,$urlfrom,$user_id='' ){
		$fileurl= self::getFileName( $file,$filename,$html );
		phpQuery::newDocumentFile( $fileurl );
		//解析html内容
		$link = $urlfrom['host'].$urlfrom['path'];
		$productName = phpQuery::pq('.product-name')->text();
		$description['itemspecifics'] = phpQuery::pq('.property-item')->children()->text();
		$description['description'] = phpQuery::pq('.description-content')->html();
		if(empty(phpQuery::pq("[itemprop='lowPrice']")->text())){
			$price = phpQuery::pq("#j-sku-price")->text();
		}else{
			$price = phpQuery::pq("[itemprop='lowPrice']")->text();
		}
		if(!empty(phpQuery::pq("[data-role='thumbFrame']"))){
			$mainImg =phpQuery::pq("[data-role='thumbFrame']")->children()->attr('src');
		}
		$platform = 'aliexpress';
		if(!empty(phpQuery::pq(".image-thumb-list "))){
			$imglist = phpQuery::pq("#j-image-thumb-list")->children()->html();
			$img = json_encode(self::extract_attrib($imglist,$platform));
		}
		$return= array();
		$return['link']= $link;
		$return['productName']= $productName;
		$return['description']= $description;
		$return['price']= $price;
		$return['mainImg']= $mainImg;
		$return['platform']= $platform;
		$return['img']= $img;
		return $return;
	}
	//end function

	/**
	 * 获取天猫的数据
	 * @author akirametero
	 */
	public static function getTmallInfo( $file,$filename,$html,$urlfrom,$user_id='' ){
		$fileurl= self::getFileName( $file,$filename,$html );
		phpQuery::newDocumentFile( $fileurl );


		$link = $urlfrom['host'].$urlfrom['path'];
		if(phpQuery::pq('#J_AttrUL li')->text() == ''){
			$description['itemspecifics'] = phpQuery::pq('#J_ParamsWrap li')->text();
		}else{
			$description['itemspecifics'] = phpQuery::pq('#J_AttrUL li')->text();
		}
		$description['description'] = phpQuery::pq('#description .content')->html();
		$price_check = phpQuery::pq('#J_StrPriceModBox .tm-price')->text();
		if(strpos($price_check,'-')){
			preg_match('/(.*)\-{1}/',$price_check,$result);
			$price = $result[1];
		}else{
			$price = $price_check;
		}
		$platform = 'tmall';
		$imglist = phpQuery::pq('#J_UlThumb')->children()->html();
		$img = json_encode(self::extract_attrib($imglist,$platform));
		$mainImg = self::extract_attrib($imglist,$platform)[0];
		$result =phpQuery::pq('.tb-detail-hd')->html();
		phpQuery::newDocumentHTML($result);
		$productName = phpQuery::pq('h1')->text();

		$return= array();
		$return['link']= $link;
		$return['productName']= $productName;
		$return['description']= $description;
		$return['price']= $price;
		$return['mainImg']= $mainImg;
		$return['platform']= $platform;
		$return['img']= $img;
		return $return;
	}
	//end function

	/**
	 * 阿里巴巴内容获取
	 * @author akirametero
	 */
	public static function get1688Info( $file,$filename,$html,$urlfrom,$user_id='' ){
		$fileurl= self::getFileName( $file,$filename,$html );
		phpQuery::newDocumentFile($fileurl);
        $link = $urlfrom['host'].$urlfrom['path'];
		$productName = phpQuery::pq('#mod-detail-title .d-title')->text();
		$description['itemspecifics'] = phpQuery::pq('#mod-detail-attributes')->children()->text();
		$description['description'] = phpQuery::pq('#desc-lazyload-container')->html();
		$price = 0;
		$platform = '1688';
		$imglist = phpQuery::pq('.nav-tabs')->children()->html();
		$img = json_encode(self::extract_attrib($imglist,$platform));
		$mainImg = self::extract_attrib($imglist,$platform)[0];

		$return= array();
		$return['link']= $link;
		$return['productName']= $productName;
		$return['description']= $description;
		$return['price']= $price;
		$return['mainImg']= $mainImg;
		$return['platform']= $platform;
		$return['img']= $img;
		return $return;
	}
	//end function

    /**
     * 淘宝内容获取
     * @author akirametero
     */
    public static function getTaoBaoInfo( $file,$filename,$html,$urlfrom,$user_id='' ){
        $fileurl= self::getFileName( $file,$filename,$html );
        phpQuery::newDocumentFile($fileurl);
        $link = $urlfrom['host'].$urlfrom['path'];
        $productName = phpQuery::pq('#J_Title .tb-main-title')->text();
        //判断是否为旺铺店铺
        if(empty(phpQuery::pq('.tb-attributes-list')->children()->text())){
            $description['itemspecifics'] = phpQuery::pq('.attributes-list')->children()->text();
        }else{
            $description['itemspecifics'] = phpQuery::pq('.tb-attributes-list')->children()->text();
        }
        $description['description'] = phpQuery::pq('#J_DivItemDesc')->html();
        $price =phpQuery::pq('#J_StrPrice .tb-rmb-num')->text();
        $mainImg = phpQuery::pq('#J_ImgBooth')->attr('src');
        $platform = 'taobao';
        $imglist = phpQuery::pq('#J_UlThumb')->children()->html();
        $img = json_encode(self::extract_attrib($imglist,$platform));

        $return= array();
        $return['link']= $link;
        $return['productName']= $productName;
        $return['description']= $description;
        $return['price']= $price;
        $return['mainImg']= $mainImg;
        $return['platform']= $platform;
        $return['img']= $img;
        return $return;

    }
    //end function

    /**
     * 获取亚马逊内容
     * @author akirametero
     */
    public static function getAmazonInfo( $file,$filename,$html,$urlfrom,$user_id='' ){
        $fileurl= self::getFileName( $file,$filename,$html );
        phpQuery::newDocumentFile($fileurl);
        $link = $urlfrom['host'].$urlfrom['path'];
        $productName= phpQuery::pq("#productTitle")->text();
        $description= array();
        $description['itemspecifics']= phpQuery::pq('#feature-bullets')->text();

        $description['description']= phpQuery::pq('#detail-bullets')->html();
        $price= phpQuery::pq('#priceblock_ourprice')->text();
        //带范围的价格
        if( strpos( $price,'-' )!==false ){
            $price_arr= explode('-',$price);
            $price= $price_arr[1];
        }
        //可能存在几个框框选择的价格,比如book类型的商品
        if( $price=='' ){
            $price= phpQuery::pq('#tmmSwatches .a-color-price')->text();
        }
        //又一种可能
        if( $price=='' ){
            $price= phpQuery::pq('#priceblock_dealprice')->text();
        }
        //又又一种可能
        if( $price=='' ){
            $price= phpQuery::pq("#priceblock_saleprice")->text();
        }
        if( $price=='' ){
            $price= 0;
        }
        $mainImg = phpQuery::pq('#landingImage')->attr('data-old-hires');
        $imglist= phpQuery::pq('#altImages .a-nostyle')->children()->html();
        $img_arr = self::extract_attrib($imglist,'amazon');
        $img= json_encode($img_arr);
        if( $mainImg=='' ){
            $imgsrc= phpQuery::pq('#landingImage')->attr('src');
            if( substr( $imgsrc,0,4 )=='http' || substr( $imgsrc,0,5 )=='https' ){
                //是否是http或者https开头
                $mainImg= $imgsrc;
            }else{
                $mainImg= '';
                if( !empty($img_arr) ){
                    $mainImg= $img_arr[0];
                }
            }
        }
        //还是空,那就可能是book类型的商品
        if( $mainImg=='' ){
            $mainImg= phpQuery::pq('#imgBlkFront')->attr('src');
        }

        $platform= 'amazon';

        //评论总数
        $decoration= 0;//默认为0
        $decoration_html= phpQuery::pq('#averageCustomerReviewCount')->children()->text();
        //筛选出数字
        preg_match('/\d+/',$decoration_html,$decoration_arr);
        $decoration= $decoration_arr[0];

        $return= array();
        $return['link']= $link;
        $return['productName']= $productName;
        $return['description']= $description;
        $return['price']= str_replace(array('$','￥','£','EUR'),'',$price);
        $return['mainImg']= $mainImg;
        $return['platform']= $platform;
        $return['img']= $img;
        return $return;
    }
    //end function

	/**
	 *ebey内容获取
	 * @author akirametero
	 */
	public static function getEbeyInfo( $file,$filename,$html,$urlfrom,$user_id='' ){
        $fileurl= self::getFileName( $file,$filename,$html );
        phpQuery::newDocumentFile($fileurl);
        $link = $urlfrom['host'].$urlfrom['path'];
		$platform= 'ebey';
		$productName= str_replace('Details about','',phpQuery::pq('#itemTitle')->text());
		$pricetxt= phpQuery::pq('#prcIsum')->text();
        if( $pricetxt=='' ){
            $pricetxt= phpQuery::pq('#mm-saleDscPrc')->text();
        }
		$price= preg_replace('/[^\.0123456789]/s', '', $pricetxt);
		$mainImg= phpQuery::pq('#icImg')->attr('src');
		$img= phpQuery::pq('#vi_main_img_fs')->children()->html();
		$imglist= json_encode(self::extract_attrib($img,$platform));
        $description= array();
        $description['itemspecifics'] = str_replace('Item specifics','',phpQuery::pq('.section')->text());
		$detail_src= phpQuery::pq('#desc_ifr')->attr('src');
        //存在介绍
        if( $detail_src!='' ){
            //获取src的内容,写入html
            $detail_content= @file_get_contents($detail_src);
            $fn= 'ebey_content_'.$user_id.'.html';
            $user_data = fopen($file.$fn,"w");
            fwrite($user_data,$detail_content);
            phpQuery::newDocumentFile($file.$fn);
            $description['description']= phpQuery::pq('#ds_div')->html();

        }else{
            $description['description']= '';
        }

        $return= array();
        $return['link']= $link;
        $return['productName']= $productName;
        $return['description']= $description;
        $return['price']= str_replace('$','',$price);
        $return['mainImg']= $mainImg;
        $return['platform']= $platform;
        $return['img']= $imglist;
        return $return;

	}

	//end function

    /**
     * 保存数据
     * @author akirametero
     */
    public static function saveInfo( $info=array() ){
        if( empty( $info ) ){
            return false;
        }

        $goodscollect = new GoodscollectAll();
        $goodscollect->title= trim($info['productName']);
        $goodscollect->description= json_encode($info['description']);
        $goodscollect->createtime= time();
        $goodscollect->price= (float)$info['price'];
        $goodscollect->link= $info['link'];
        $goodscollect->mainimg= $info['mainImg'];
        $goodscollect->platform= $info['platform'];
        $goodscollect->img= $info['img'];
        //判断数据是否正确入库
        if($goodscollect->save(false)){
            //success
            $res['status'] = 0;
            return  json_encode($res);
        }else{
            //error
            $res['status'] = 1;
            $res['msg'] = "保存失败！";
            return json_encode($res);
        }
    }
    //end function


    /**
     * amzhelper邮箱数据爬取
     * $allpage,总页数
     * $page,开始页数
     * akirametero
     */
    public function get_email_info( $page,$allpage ){


        $file= '/Library/WebServer/xiaolaoban/trunk2/eagle/web/attachment/crawl_html/';
        $filename= 'email.html';

        //首先登录
        RobotHelper::setAmzhelperLogin();
        for( $i=$page;$page<=$allpage;$i++ ){
            $html= RobotHelper::getAmzhelperEmailHtmlInfo( $i );
            $fileurl= self::getFileName( $file,$filename,$html );
            phpQuery::newDocumentFile($fileurl);
            phpQuery::pq('.text-c')->text();
            $txt= phpQuery::pq('.text-c')->children()->text();
            //筛选邮箱啦
            preg_match_all('/[\w.%-]+@[\w.-]+\.[a-z]{2,4}/i', $txt, $matches);
            $email_arr= $matches[0];
            foreach( $email_arr as $email_vss ){
                //写入txt
                $data = fopen($file.'email.txt',"a");
                fwrite($data,$email_vss."\r\n");
                echo $email_vss,PHP_EOL;
            }
        }
        echo 'ok';exit;

    }
    //end function


    /**
     * 亚马逊评论数据处理
     * amazon参考地址:https://www.amazon.com/gp/pdp/profile/A46C9XPKN7ZBM
     * akirametero
     */
    public function get_amazon_profile_html(){

    }
    //end function


}