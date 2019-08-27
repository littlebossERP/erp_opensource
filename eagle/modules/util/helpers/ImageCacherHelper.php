<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\models\PhotoCacheQueue;
use eagle\modules\util\models\PhotoCache;
use eagle\modules\util\models\GlobalLog;

use eagle\modules\util\helpers\ImageHelper;
use eagle\modules\util\models\S3;
use eagle\modules\util\models\UserImage;
use eagle\modules\util\models\GlobalImageInfo;
use eagle\modules\util\helpers\TranslateHelper;
use Qiniu;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\ResultHelper;
use Qiniu\Storage\BucketManager;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use Qiniu\json_decode;

/**
 +------------------------------------------------------------------------------
 * log模块
 +------------------------------------------------------------------------------
 * @category	Image
 * @package		Helper
 * @subpackage  Exception
 * @author		YZQ
 * @version		1.0 2016-2-17
 +------------------------------------------------------------------------------
 */
class ImageCacherHelper {
	/**
	 +---------------------------------------------------------------------------------------------
	 * 插入一条请求，希望ImageCacher帮忙缓存这个国外的图片
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param puid
	 * @param orig_url
	 * @param priority    1最高，10最低，默认5
	 +---------------------------------------------------------------------------------------------
	 * @return		$rtn['message'] = "";
					$rtn['success'] =true/false				
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016-2-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	
 
	public static function insertOneRequest($puid,$orig_url,$priority=5){
    	$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$classification = "PhotoCache";
		
		//看看redis有没有这个key，如果有并且值是不为空，就是已经有请求在做或者已经有值了，不需要insert这个request
		$exist_val = \Yii::$app->redis->hget($classification,$orig_url);
		if (!empty($exist_val)){
			if($exist_val=='pending' && $priority<3){
				//如果priority<3,检查一下是否有pending且priority>3的，有就update它们的priority
				PhotoCacheQueue::updateAll(['priority'=>$priority],"puid=$puid and status='P' and priority<$priority and orig_url=:orig_url ",[':orig_url'=>$orig_url]);
				$rtn['message'] = "已经有在redis且priority<3, update priority to Queue";
			}else{
				$rtn['message'] = "已经有在redis了，不需要insert Request to Queue";
			}
			$rtn['success'] = false;
			return $rtn;
		}
		
        try{
        	$aReq = new PhotoCacheQueue();
        	$aReq->puid = $puid;
        	$aReq->orig_url = $orig_url;
        	$aReq->priority = $priority;
        	$aReq->status='P';
        	$aReq->create_time = $now_str;
        	$rtn['success'] = $aReq->save(false);
        }catch(\Exception $e){
        	$rtn['message'] = $e->getMessage();
        	$rtn['success'] = false;
        }
        
        //set redis这个key='pending'，这样就不会重复不停insert to queue for 同一个url了
        if ($rtn['success'])
        \Yii::$app->redis->hset($classification,$orig_url,'pending');
        
        return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 队列处理器执行队列请求
	 * 每次获取一批进内存，按照puid来执行(因为要把使用掉的量提交到该用户图片库里面)，然后批量update队列里面的请求为完成
	 * 该job目前不支持多进程并行
	 +---------------------------------------------------------------------------------------------
	 * @param $specified_orig_url 执行执行的原图片url，如果不指定，就执行50个pending的 一次
	 +---------------------------------------------------------------------------------------------
	 * @return 		$rtn['message'] = "";
					$rtn['success'] =true/false	
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016-2-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function processCacheReq($specified_orig_url='',$totalJobs=0,$thisJobId=0){
		global $failed_prefix_count,$NeedXlbPhotoCacher;		
		$rtn['message'] = "";
		$rtn['count'] = 0;
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$classification = "PhotoCache";
		//先把已经有的photo cache 放到redis去
		self::initCacheToRedis();
		
		//上次可能有一些是失败的，把它们改为pending，如果已经间隔了一个小时
		if($totalJobs>0)
			$command = \Yii::$app->db_queue->createCommand("update ut_photo_cache_queue set status='P' where
				status='F' and update_time<:update_time and id % $totalJobs = $thisJobId ");
		else 
			$command = \Yii::$app->db_queue->createCommand("update ut_photo_cache_queue set status='P' where
					status='F' and update_time<:update_time ");
		$command->bindValue(':update_time', date('Y-m-d H:i:s',strtotime('-60 minutes')), \PDO::PARAM_STR);
		$insert = $command->execute();
		
		$pendingReq_mod = PhotoCacheQueue::find();
		
		if (!empty($specified_orig_url))
			$pendingReq_mod->andWhere(['orig_url'=>$specified_orig_url]);
		if($totalJobs>0)
			$pendingReq_mod->andWhere(" id % $totalJobs = $thisJobId ");
		
		$pendingReq_array = $pendingReq_mod->andWhere(['status'=>'P'])
		->limit(50)->asArray()->orderBy('priority')
		->all();
	 
		$rtn['count'] = count($pendingReq_array);
		
		 
		$doneIds = [];
		$failedIds = [];
		foreach ($pendingReq_array as $aReq){
			$prefix_url = str_replace("http://","",$aReq['orig_url']);
			$prefix_url = str_replace("https://","",$prefix_url);
			$postionOfSlash = strpos( $prefix_url ,'/');
			if ($postionOfSlash>1)
				$prefix_url = substr($prefix_url,0,$postionOfSlash-1);
			
			//如果已经连续4次这个link 死掉了，就不要再尝试这个,
			//例如 http://pmcdn.priceminister.com/photo/1065800093_ML.jpg 
			//就是 pmcdn.priceminister.com
			if (!empty($failed_prefix_count[$prefix_url]) and $failed_prefix_count[$prefix_url]>4){
				$addi_info['errorMessage'] = "这个前缀 $prefix_url 错误次数很多了".$failed_prefix_count[$prefix_url]."，不要处理";
				$query = "update `ut_photo_cache_queue` set update_time=:update_time,
						local_path=:local_path,status='I',addi_info=:addi_info ,
						try_count = try_count + 1 where id=  ".$aReq['id'];
				$command = \Yii::$app->db_queue->createCommand($query);
				$command->bindValue(':update_time', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
				$command->bindValue(':local_path', '', \PDO::PARAM_STR);
				$command->bindValue(':addi_info', json_encode($addi_info) , \PDO::PARAM_STR);
				$insert = $command->execute();
				continue;
			}
				
			echo "\nTry to get photo for puid ".$aReq['puid']." ".$aReq['orig_url']." \n";
			//首先识别 priceminister的需要用xlb photo cacher
			if (strpos($prefix_url, "priceminister",0)!== false)
				$NeedXlbPhotoCacher[$prefix_url] = true;	
			
			//do the fetch,using QiNiu
			if (empty($NeedXlbPhotoCacher[$prefix_url]))
				$ret = self::askQiNiuToDoCache($aReq['orig_url'],$aReq['puid']);
			
			//如果第一次七牛不行，并且这个 domain name 还不是识别为使用 小老板 photo cacher的，试试用cacher看看
			if (isset($NeedXlbPhotoCacher[$prefix_url]) or !$ret['success'] and empty($NeedXlbPhotoCacher[$prefix_url])){
				$cacherRet = self::getImageByXlbCacher($aReq['orig_url']);
				if ($cacherRet['success'])
					$ret = self::askQiNiuToDoCache($cacherRet['cachedUrl'],$aReq['puid']);
				else{
					echo "\n askQiNiuToDoCache and getImageByXlbCacher all failed!";
					echo "\n askQiNiuToDoCache result:".print_r(@$ret,true);
					echo "\n getImageByXlbCacher result:".print_r($cacherRet,true);
					continue;
				}
				if (!empty($ret['success']) and $ret['success'])
					$NeedXlbPhotoCacher[$prefix_url] = true;
			}
			
			//如果 original path 和 local path 尾部不同，就当成是失败的，不要弄进去啊
			$aReq['local_path'] = $ret['local_path'];
			$pos1 = strripos($aReq['orig_url'],"/");
			$pos1a = strripos($aReq['orig_url'],"_");
		 
			if ($pos1a !== false and $pos1 < $pos1a){
				$pos1 = $pos1a;
			}
			
			$pos2 = strripos($aReq['local_path'],"/");
			$pos2a = strripos($aReq['local_path'],"_");
			if ($pos2a !== false and $pos2 < $pos2a){
				$pos2 = $pos2a;
			}
			
			if ( !empty($pos1) and !empty($pos2) ){
				
				$a = substr($aReq['orig_url'],$pos1);
				$b = substr($aReq['local_path'],$pos2);
				
				if (strlen($a) < strlen($b)){
					$len1 = strlen($a)  - 1;
				}else
					$len1 = strlen($b)  - 1;
				
				$a = substr($aReq['orig_url'],strlen($aReq['orig_url']) - $len1);
				$b = substr($aReq['local_path'],strlen($aReq['local_path']) - $len1);
				if ($a <> $b){
					$ret['success'] = false;
					$ret['message'] = "original and local file name not match ".$aReq['orig_url']." vs ".$aReq['local_path'].
									" so substr pos1 = ".$a. " but substr pos2 = ".$b ;
				}
			}
			
			if (!$ret['success']){
				$addi_info = array('errorMessage'=>$ret['message']);
				$query = "update `ut_photo_cache_queue` set update_time=:update_time,
						local_path=:local_path,status='".($aReq['try_count']>4 ? 'I' :'F')."',addi_info=:addi_info ,
						try_count = try_count + 1 where id=  ".$aReq['id'];
				$command = \Yii::$app->db_queue->createCommand($query);
				$command->bindValue(':update_time', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
				$command->bindValue(':local_path', '', \PDO::PARAM_STR);
				$command->bindValue(':addi_info', json_encode($addi_info) , \PDO::PARAM_STR);
				$insert = $command->execute();

				if (empty($failed_prefix_count[$prefix_url]))
					$failed_prefix_count[$prefix_url] = 0;
				
				$failed_prefix_count[$prefix_url] ++;
				
				continue;
			}
			
			$aReq['local_path'] = $ret['local_path'];
			$doneIds[] = $aReq['id'];
			
			//insert the record to Photo Cache table, if existing orig url, replace it
			//到底是insert还是update，还要看看redis有没有现成的
			$exist_val = \Yii::$app->redis->hget($classification,$aReq['orig_url']);
			if (empty($exist_val) or $exist_val=='pending'){//do insert
				$query = "replace INTO `ut_photo_cache`
				(`puid`, `status`, `orig_url`, `local_path`, `create_time` ) VALUES
				(".$aReq['puid'].",'C',:orig_url,:local_path, '".date("Y-m-d H:i:s")."' )";
				$command = \Yii::$app->db_queue->createCommand($query);
				$command->bindValue(':orig_url', $aReq['orig_url'], \PDO::PARAM_STR);
				$command->bindValue(':local_path', $aReq['local_path'], \PDO::PARAM_STR);
				$insert = $command->execute();
			}else{//do update
				if ($exist_val <> $aReq['local_path'] ){
					PhotoCache::updateAll(['update_time'=> date('Y-m-d H:i:s'),
										'local_path'=>$aReq['local_path'] ] ,
										['orig_url'=>$aReq['orig_url'] ] );
					
				}
			}
			
			\Yii::$app->redis->hset($classification,$aReq['orig_url'],$aReq['local_path']);
			
		}
		
		PhotoCacheQueue::updateAll(['update_time'=> date('Y-m-d H:i:s'),
						'status'=>'C'] ,[ 'id'=>$doneIds] );
		
		return $rtn;	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 初始化Cache结果到Redis，并记录当时的cache 快照时间
	 * 如果已经初始化了，就不需要重新初始化，理论上这个会自动迭代跟踪补全的
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return 		$rtn['message'] = "";
	                $rtn['success'] =true/false
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016-2-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function initCacheToRedis(){
		global $INIT_TIME;
		$rtn['message'] = "";
		$rtn['success'] = true;
		$rtn['count']=0;
		$now_str = date('Y-m-d H:i:s');
		
		$classification = "PhotoCache";
		//redis也需要节省开销，同一个进程如果看到已经有init time 了，不要再次问redis了
		if (empty($INIT_TIME))
			$INIT_TIME = \Yii::$app->redis->hget($classification,"init_time");
		//如果有值，就不需要重新put to Redis了，如果是空白或者不存在，才做
		if (!empty($INIT_TIME))
			return $rtn;
		
		\Yii::$app->redis->del($classification );		
		\Yii::$app->redis->hset($classification,"init_time",$now_str);
		$INIT_TIME = $now_str;
		//Load 所有现在的值出来，放到redis去
		$photoCache_array = PhotoCache::find()->asArray()->all();
		foreach ($photoCache_array as $aCache){
			\Yii::$app->redis->hset($classification,$aCache['orig_url'],$aCache['local_path']);
			$rtn['count']++;
		}
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 叫七牛去获取这个远程的url，然后返回七牛的缓存url
	 +---------------------------------------------------------------------------------------------
	 * @param  $orig_url
	 * @param  $puid
	 +---------------------------------------------------------------------------------------------
	 * @return 		$rtn['message'] = "";
	 				$rtn['success'] =true/false
	 				$rtn['local_path'] = ''
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016-2-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function askQiNiuToDoCache($orig_url,$puid){
		$toFetchUrls = array();
		$rtn['message'] = "";
		$rtn['success'] =true;
		$rtn['local_path'] = '';
		
		$qiniuPath = $puid."/". date('Ymd')."/";
		$totalAddSize = 0;
		$qiNiuFullPath =  $qiniuPath.str_replace("/","_",str_replace("http://","",$orig_url));
		// 1.检查url
		 
		// 获取图片key 上传到七牛的key
		$accessKey = ImageHelper::$qiniuAccessKey;
		$secretKey = ImageHelper::$qiniuSecretKey;
		// TODO imglib qiniu domain
		$qiniuDomain = '/';
		$auth = new Auth($accessKey, $secretKey);
		$bucket = 'omsphotos';
		$bucketMgr = new BucketManager($auth);
 		//echo "start to ask QiNiu for retrieving $orig_url to $qiNiuFullPath \n";
		// 3.上传url
		$timeMS1 = TimeUtil::getCurrentTimestampMS();
		$err = null;
		 
				$tryCount = 3;
				$oCounter = 0;
				$doRetry = false;
				do{
					$timeMS11 = TimeUtil::getCurrentTimestampMS();
					try{					
						$journal_id = SysLogHelper::InvokeJrn_Create("ImageCacher",__CLASS__, __FUNCTION__ , array($orig_url, $bucket , $qiNiuFullPath));
						list($ret, $err) = $bucketMgr->fetch($orig_url, $bucket , $qiNiuFullPath );
						$rtn['success'] =true;
						SysLogHelper::InvokeJrn_UpdateResult($journal_id, $ret);
						
					}catch(\Exception $e){
						$rtn['success'] =false;
						$rtn['message'] = "actionUploadImageUrl $qiNiuFullPath upload fails. Error:".$e->getMessage();
						echo $rtn['message'];
						$doRetry = true;
					}
					if ($err !== null) {
						$doRetry = true;
						$rtn['success'] =false;
						$rtn['message'] = "actionUploadImageUrl $orig_url upload fails. Error:".print_r($err,true);
						\Yii::error("actionUploadImageUrl $orig_url upload fails. Error:".print_r($err,true),"file");
					}
		
					if($doRetry === false){
						$timeMS12 = TimeUtil::getCurrentTimestampMS();
						\Yii::info("actionUploadImageUrl 上传url $orig_url size= K 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS12-$timeMS11)."（毫秒）" , "file"); 
					}
				}while ($oCounter++ < $tryCount && $doRetry);
		 	//echo "qi Niu done:".print_r($ret,true)." got error:".print_r($err,true)."\n";
		 
		$timeMS2 = TimeUtil::getCurrentTimestampMS();
		\Yii::info("actionUploadImageUrl 上传1个url 到七牛服务器  butcketName:$bucket 。 耗时".($timeMS2-$timeMS1)."（毫秒）" , "file");
		//4. 获取最终原图和缩略图的url
		$imageUrl = "http://".$qiniuDomain.$qiNiuFullPath;
		//  缩略图用  160
		$thumbnailUrl = $imageUrl."?imageView2/1/w/160/h/160";// 长宽为160  等比缩放，居中裁剪
		$rtn['local_path'] = $imageUrl;
		
		return $rtn;
	}
	 
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取系统里面的图片cache url，如果没有，则返回original url
	 +---------------------------------------------------------------------------------------------
	 * @param  $orig_url
	 * @param  $puid ,default = 0  
	 * ·param  $priority, 1 high, 10 low
	 +---------------------------------------------------------------------------------------------
	 * @return 		$rtn['message'] = "";
	 $rtn['success'] =true/false
	 $rtn['local_path'] = ''
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016-2-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getImageCacheUrl($orig_url,$puid=0,$priority=5){
		global $CACHE; //due to order page may have many records,this function may be invoked for many times, cache the result,save redis IO

		$orig_url = trim($orig_url);
		
		if (isset($CACHE['getImageCacheUrl'][$orig_url]))
			return $CACHE['getImageCacheUrl'][$orig_url]; 

		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		//TODO 如果为服务器的图片，则不需要缓存
		 
		//先把数据库的图片mapping 同步到redis，如果redis告知已经同步过了，就不需要额外重复干活了
		self::initCacheToRedis();
		
		$classification = "PhotoCache";
		//如果没有获取到或者是正在等待缓存，那还是返回orig url
		$local_path = \Yii::$app->redis->hget($classification,$orig_url);
		if (empty($local_path) or $local_path=='pending')
			$local_path = $orig_url;
		else{//key中含有特殊字符％，这个特殊符号会转译成％25的，所以您直接以自己的进行访问浏览器不能解析导致文件不存在的。
			//below logic added by yzq 20170210
			$ar1 = explode('/',$local_path);
			$count = count($ar1);
			if ($count > 0){
				$fileName = $ar1[$count - 1];
				$fileName = urlencode($fileName);
				$ar1[$count - 1] = $fileName;
				$local_path = implode('/',$ar1);
			}
			//$local_path = str_replace("%","%25",$local_path);
		}
			
		//if 没有缓存图片，并且有puid《》0的，就要求立刻做一个吧
		if ($local_path == $orig_url and $puid > 0)
			self::insertOneRequest( $puid ,$orig_url,$priority );
		
		$CACHE['getImageCacheUrl'][$orig_url] = $local_path;
		return $local_path;
	}
	
	static public function delImageRedisCacheUrl($orig_url){
		$classification = "PhotoCache";
		\Yii::$app->redis->hdel($classification,$orig_url);
	}
	
	static public function getImageByXlbCacher($photo_url){
		 
		$rtn['message']='net work error';
		$rtn['error_code']=400;
		$rtn['success']=false;
 
		return $rtn;
	}
}