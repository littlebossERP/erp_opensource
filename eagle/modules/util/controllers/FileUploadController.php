<?php
namespace eagle\modules\util\controllers;

use eagle\modules\util\helpers\UploadFileHelper;
use OSS\OssClient;
use OSS\Core\OssException;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\models\UserPdf;
use eagle\modules\util\models\GlobalPdfInfo;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;
use yii\data\Pagination;
 
class FileUploadController extends \eagle\components\Controller {
    public $enableCsrfValidation = false;

    public function actionIndex() {
         
        return $this->render('index');
    }
    
    // util/file-upload/upload-pdf-to-ali-oss
    public function actionUploadPdfToAliOss() {
    
        AppTrackerApiHelper::actionLog("pdf_lib", "/util/pdf/upload");
        set_time_limit(0);
        $pdfTmpPath=\Yii::getAlias('@app') . '/media/temp/';
        \Yii::info("上传pdf文件:".json_encode($_FILES,true),"info");
        //1.检查上传的图片信息
        if (!isset($_FILES["pdf_file"]) || !is_uploaded_file($_FILES["pdf_file"]["tmp_name"]))  {	//是否存在文件
            exit(json_encode(array('name' => '文件' , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("文件不存在!"))));
        }
         
        $file = $_FILES["pdf_file"];
        $puid=\Yii::$app->user->identity->getParentUid();
         
        if($file["error"] > 0 || $file["size"] <= 0) {
            exit(json_encode(array('name' => '文件' , 'status' => false, 'size' => null , 'rtnMsg' => TranslateHelper::t("文件上传出错，请稍候再试..."))));
        }
    
        // 上传文件最大size
        $customMaxSize = UploadFileHelper::$PdfMaxSize;
         
        $rtnMsg = "";
        if(!in_array(strtolower(substr($file["name"],strrpos($file["name"],".")+1)), UploadFileHelper::$fileMime) && !array_key_exists($file["type"], UploadFileHelper::$fileMime)){
            $rtnMsg .= TranslateHelper::t("%s :对不起，我们只支持上传 %s 格式的文件！" , $file["name"] , implode(",", array_keys(UploadFileHelper::$fileMime)));
        }
         
        if( $file["size"] > $customMaxSize ) {
            $rtnMsg .= TranslateHelper::t("%s :文件 %s K , 超出规定大小  %s K ， 请重新上传文件!" , $file["name"] , round($file["size"] / 1024) , ($customMaxSize / 1024 ));
        }
    
//         // 检查文件库空间
//         $usage = GlobalPdfInfo::find()->where(['puid'=>$puid])->asArray()->one();
//         if(empty($usage)){
//             $usage['total_size'] = 0;
//             $usage['library_size'] = UploadFileHelper::$baseLibrarySize;
//         }
//         $newUsage = $usage['total_size'] + $file["size"];
//         if( $newUsage > $usage['library_size']){
//             $rtnMsg .= TranslateHelper::t("上传文件大小%sM ,上传文件后文件库使用 %sM , 超出规定大小  %sM ，请重新上传文件!" , round($file["size"] / 1024 / 1024 , 2) , round($newUsage / 1024 / 1024 , 2) , round($usage['library_size'] / 1024 / 1024 , 2));
//         }
        
        // 检查文件库空间
        $usage = UploadFileHelper::GlobalInfo();//图片以及文件库一齐检查
        $newUsage = $usage['count_size'] + $file["size"];
        if( $newUsage > $usage['count_library_size']){
            $rtnMsg .= TranslateHelper::t("上传文件大小%sM ,上传文件后文件库使用 %sM , 超出规定大小  %sM ，请重新上传文件!" , round($file["size"] / 1024 / 1024 , 2) , round($newUsage / 1024 / 1024 , 2) , round($usage['count_library_size'] / 1024 / 1024 , 2));
        }
         
        if($rtnMsg <> ''){
            exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => $rtnMsg)));
        }
         
        $name = $file["name"];
        $originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );
         
        if(move_uploaded_file ( $file["tmp_name"] , $pdfTmpPath . $originName ) === false) {// 重命名上传图片
            exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统获取文件失败！'))));
        }
    
        $ossAccessId = UploadFileHelper::$ALI_OSS_ACCESS_ID;
        $ossAccessKey = UploadFileHelper::$ALI_OSS_ACCESS_KEY;
        $ossEndpoint = UploadFileHelper::$ALI_OSS_ENDPOINT;
        $bucket = UploadFileHelper::$ALI_OSS_PDF_BUCKET;
        $ossDomain = $bucket.'.'.$ossEndpoint."/";
        $ossPath = \Yii::$app->user->id."/". date('Ymd');// 阿里云oss保存路径
        $ossFileName = $ossPath."/".$originName;// 上传到阿里云oss 这个值是唯一的，oss通过此值管理图片
    
        $ossClient = new OssClient($ossAccessId, $ossAccessKey, $ossEndpoint);
    
        $doRetry = false;
        $oCounter = 0;
        $tryCount = 3;// 上传失败重试次数
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        $originSize = abs(filesize($pdfTmpPath . $originName));
        $errMsg = '';
        do{
            try{
                $ossClient->uploadFile($bucket, $ossFileName, $pdfTmpPath . $originName);// 上传本地图片文件
                $doRetry = false;
                unlink ($pdfTmpPath . $originName );// 删除保存到本地的上传图片
            }catch(OssException $e){
                \Yii::error("$ossFileName upload fails. Error:".$e->getMessage(),"file");
                $doRetry = true;
                $errMsg .= $e->getMessage();
            }catch(\Exception $e){
                \Yii::error("$ossFileName upload fails. Error:file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage(),"file");
                $doRetry = true;
                $errMsg .= $e->getMessage();
            }
        } while ($oCounter++ < $tryCount && $doRetry);
         
        if($doRetry){
            exit(json_encode(array('name' =>$file["name"], 'status' => false, 'size' => $file["size"] , 'rtnMsg' => TranslateHelper::t('系统上传文件到阿里云服务器失败:').$errMsg)));
        }
         
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        \Yii::info("上传文件文件 $ossFileName size=".round($originSize/1024)."K 到阿里云服务器  butcketName:$bucket 。 耗时".($timeMS2-$timeMS1)."（毫秒）","info");
         
        //4.pdf url
        $pdfUrl = "http://".$ossDomain.$ossFileName;
    
//         $im_size = getpdfsize($pdfUrl);
//         $pdfW = $im_size[0];
//         $pdfH = $im_size[1];
         
        //5. 记录相关信息到global和user的图片表
        $userPdf = new UserPdf();
        $userPdf->origin_url = $pdfUrl;
//         $userPdf->thumbnail_url = $thumbnailUrl;
        $userPdf->origin_size = $originSize;
        $userPdf->original_name = $name;
//         $userPdf->thumbnail_size = 0;
        $userPdf->create_time = date("Y-m-d H:i:s");
        $userPdf->service = 2;// service:0 amazon s3图片上传服务， 1 ： 七牛，2 阿里云oss
        $userPdf->file_key = $ossFileName;
        $userPdf->language = !empty($_POST['language'])?$_POST['language']:'';
        $userPdf->add_info = '';
//         $userPdf->amazon_key = $ossFileName;
//         $userPdf->original_name = $name;
//         $userPdf->original_width = $pdfW;
//         $userPdf->original_height = $pdfH;
        if(!$userPdf->save(false)){
            \Yii::error("$ossFileName userPdf save fail","file");
        }
         
        $globalPdfInfo = GlobalPdfInfo::find()->where(["puid"=>$puid])->one();
        if ($globalPdfInfo === null){
            $globalPdfInfo = new GlobalPdfInfo;
            $globalPdfInfo->puid = $puid;
            $globalPdfInfo->pdf_number = 1;
            $globalPdfInfo->total_size = $originSize;
            if(!$globalPdfInfo->save(false)){
                \Yii::error("$ossFileName : $puid create global pdf info record failed","file");
            }
             
        }else{
            $globalPdfInfo->pdf_number = $globalPdfInfo->pdf_number + 1;
            $globalPdfInfo->total_size = $globalPdfInfo->total_size + $originSize;
            if(!$globalPdfInfo->save(false)){
                \Yii::error("$ossFileName : $puid update global pdf info record failed","file");
            }
        }
        exit(json_encode( array('name' => $file["name"], 'status' => true, 'pdf_id'=>$userPdf->id,'data' => array('original' => $pdfUrl) , 'rtnMsg' => $rtnMsg)));
//         if(!empty($_GET['dir']) && $_GET['dir']==='pdf'){        // kindeditor专用
//             exit(json_encode([
//                 'error'=>0,
//                 'url'=>$pdfUrl
//             ]));
//         }else{
//             exit(json_encode( array('name' => $file["name"], 'status' => true, 'data' => array('original' => $pdfUrl) , 'rtnMsg' => $rtnMsg)));
//         }
    
    }
    
    // /util/file-upload/delete
    public function actionDelete() {
        AppTrackerApiHelper::actionLog("pdf_lib", "/util/pdf/delete");
        if(!empty($_POST['key'])){
            $find_result = UploadFileHelper::FindFile($_POST['key']);
        }
        
        //检查有没有在线商品有用该文件，300为有产品
        if(!empty($find_result)){
            return ResultHelper::getResult(300, $find_result, "");
        }
        
        if(empty($_POST['ids'])){
            return ResultHelper::getResult(400, '', "请选择图片");
        }
         
//         $ids = explode(',', $_POST['ids']);
        $ids = $_POST['ids'];
        $alipdfs = UserPdf::find()->where(['service'=>2,'id'=>$ids])->asArray()->all();
        //批量删除检查是否有文件存在
        if(empty($_POST['key'])&&!empty($alipdfs)){
            $pdf_results = UploadFileHelper::BatchFindKey($alipdfs);
        }
        
        //批量检查有没有在线商品有用该文件，，有则全部删除失败，300为有产品
        if(!empty($pdf_results)){
            return ResultHelper::getResult(300, $pdf_results, "");
        }
        
        if(!empty($alipdfs)){
            list($ret, $msg) = UploadFileHelper::deleteAlipdfs($alipdfs);
            if(!$ret)
                return ResultHelper::getResult(400, '', "部分图片删除失败：".$msg);
        }
         
         
        return ResultHelper::getResult(200, "", "删除成功");
    }
    
    //文件库list
    public function actionShowFile() {
        AppTrackerApiHelper::actionLog("pdf_lib", "/util/pdf/show-library");
    	// 'service'=>1指 7牛图片  ， 0是使用amazon s3上传的图片，2是阿里云oss
        $puid = \Yii::$app->subdb->getCurrentPuid();
         
    	$pdf_query = UserPdf::find()->where(['service'=>2]);
    	if(!empty($_REQUEST['search_file_name'])){
    		$pdf_query->andWhere(['like','original_name',$_REQUEST['search_file_name']]);
    	}

    	$pdf_pages = new Pagination(['totalCount' =>$pdf_query->count(), 'defaultPageSize'=>30 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
    	$pdfs = $pdf_query->offset($pdf_pages->offset)->limit($pdf_pages->limit)->orderBy('create_time desc')->asArray()->all();
    	
    	// 使用global 获取使用情况，图片库上线前，清空已有global 信息。后面可能要再将amazon图片转移到七牛
//     	$puid=\Yii::$app->user->identity->getParentUid();
//     	$pdf_usage = GlobalPdfInfo::find()->where(['puid'=>$puid])->asArray()->one();
//     	if(empty($pdf_usage)){
//     		$pdf_usage = array();
//     		$pdf_usage['total_size'] = 0;
//     		$pdf_usage['pdf_number'] = 0;
//     		$pdf_usage['library_size'] = UploadFileHelper::$baseLibrarySize;
//     	}
    	$pdf_usage = UploadFileHelper::GlobalInfo();
    	
    	return $this->render('list',['pdfs'=>$pdfs,'pdf_usage'=>$pdf_usage,'pdf_pages'=>$pdf_pages]);
    }
    //刊登界面选择list
    public function actionSelectPdf() {
        AppTrackerApiHelper::actionLog("pdf_select", "/util/pdf/select-pdf");
        // 'service'=>1指 7牛图片  ， 0是使用amazon s3上传的图片，2是阿里云oss
        $pdfs = UserPdf::find()->where(['service'=>2])->orderBy('create_time desc')->asArray()->all();
    
        return $this->renderAjax('select-list',['pdfs'=>$pdfs]);
    }
    
}