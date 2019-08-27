<?php
namespace eagle\modules\util\helpers;

use OSS\OssClient;
use OSS\Core\OssException;
use eagle\modules\util\models\UserPdf;
use eagle\modules\util\models\GlobalPdfInfo;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\models\GlobalImageInfo;
use eagle\modules\util\helpers\ImageHelper;

class UploadFileHelper {
    
    public static $PdfMaxSize = 20971520 ; // 20M
    public static $baseLibrarySize = 524288000 ; //
    
    public static $fileMime = array ( 'application/pdf' => 'pdf','application/octet-stream'=>'rar','application/zip'=>'zip');
    
	// TODO imglib alioss account
    public static $ALI_OSS_ACCESS_ID = '';
    public static $ALI_OSS_ACCESS_KEY = '';
    public static $ALI_OSS_ENDPOINT = '';
    public static $ALI_OSS_PDF_BUCKET = '';
    
    
    /**
     * 删除阿里云oss 文件
     */
    public static function deleteAlipdfs($pdfs){
        $deleteKeys = array();
        $deleteNum = 0;
        $deleteSize= 0;
        $errMsg = '';
        $ids = array();
        foreach ($pdfs as $onePdf){
            $deleteNum ++;
            $deleteSize += $onePdf['origin_size'];
            $deleteKeys[] = $onePdf["file_key"];
            $ids[] = $onePdf["id"];
        }
    
        if(!empty($deleteKeys)){
             
            $timeMS1=TimeUtil::getCurrentTimestampMS();
            try{
                $ossClient = new OssClient(self::$ALI_OSS_ACCESS_ID, self::$ALI_OSS_ACCESS_KEY, self::$ALI_OSS_ENDPOINT);
                $bucket = self::$ALI_OSS_PDF_BUCKET;
                $ossClient->deleteObjects($bucket, $deleteKeys);
                 
            } catch(OssException $e) {
                \Yii::error("deleteAliPdfs fails. Error:".$e->getMessage().",pdfs:".json_encode($pdfs),"file");
                $errMsg .= $e->getMessage();
            }catch(\Exception $e){
                \Yii::error("deleteAliPdfs fails. Error:file:".
                    $e->getFile().",line:".$e->getLine().",message:".$e->getMessage().",pdfs:".json_encode($pdfs),"file");
                $errMsg .= $e->getMessage();
            }
    
            $timeMS2=TimeUtil::getCurrentTimestampMS();
            if (!empty($errMsg)) {
                return array(false,$errMsg);
            }else{
                self::_resetGlobalPdfInfo($deleteNum,$deleteSize);
                UserPdf::deleteAll(['service'=>2,'id'=>$ids]);
            }
        }
         
        return array(true,'');
    }
    //批量查找删除文件检查，假如有，全部删除失败
    public static function BatchFindKey($alipdfs){
        $html = '';
        foreach ($alipdfs as $pdf){
            if(!empty($pdf['file_key'])){
                $key_array = explode('/', $pdf['file_key']);
                if(isset($key_array[2])){
                    $file = self::FindFile($key_array[2],$pdf['original_name']);
                    if(!empty($file)){
                        $html .= $file;
                    }
                }
            }
        }
        
        return $html;
    }
    
     public static function FindFile($key,$name=''){
         $html = '';
          
         return $html;
     }
    /**
     * 删除图片后 修改图片库GlobalPdfInfo表数据
     * dzt 2017-03-24
     */
    private static function _resetGlobalPdfInfo($deleteNum,$deleteSize){
        $puid=\Yii::$app->user->identity->getParentUid();
        $globalPdfInfo=GlobalPdfInfo::find()->where(["puid"=>$puid])->one();
        if ($globalPdfInfo===null){
            $globalPdfInfo=new GlobalPdfInfo;
            $globalPdfInfo->puid=$puid;
            $globalPdfInfo->pdf_number=0;
            $globalPdfInfo->total_size=0;
            if(!$globalPdfInfo->save(false)){
                \Yii::error("actionDelete : $puid create global pdf info record failed","file");
            }
        }else{
            if($globalPdfInfo->pdf_number - $deleteNum < 0){
                $globalPdfInfo->pdf_number = 0;
            }else{
                $globalPdfInfo->pdf_number = $globalPdfInfo->pdf_number - $deleteNum;
            }
             
            if($globalPdfInfo->total_size - $deleteSize < 0){
                $globalPdfInfo->total_size = 0;
            }else{
                $globalPdfInfo->total_size = $globalPdfInfo->total_size - $deleteSize;
            }
             
            if(!$globalPdfInfo->save(false)){
                \Yii::error("actionDelete : $puid update global pdf info record failed","file");
            }
        }
    }
    //获取文件库 图片库的使用情况
    public static function GlobalInfo($puid=false){
        if(empty($puid))
            $puid=\Yii::$app->user->identity->getParentUid();
        
        $all_info = [];
        $fileInfo = GlobalPdfInfo::find()->where(['puid'=>$puid])->asArray()->one();
        
        $imageInfo = GlobalImageInfo::find()->where(['puid'=>$puid])->asArray()->one();
        if(empty($fileInfo)){
            $fileInfo = array();
            $fileInfo['total_size'] = 0;
            $fileInfo['pdf_number'] = 0;
            $fileInfo['library_size'] = ImageHelper::$baseLibrarySize;//以图片库为标准
        }
        
        if(empty($imageInfo)){
            $imageInfo = array();
            $imageInfo['total_size'] = 0;
            $imageInfo['image_number'] = 0;
            $imageInfo['library_size'] = ImageHelper::$baseLibrarySize;
        }
        
        $all_info['fileInfo'] = $fileInfo;
        $all_info['imageInfo'] = $imageInfo;
        
        $all_info['count_library_size'] = $imageInfo['library_size'];//总的文件空间,以图片库为标准
        $all_info['count_number'] = $fileInfo['pdf_number'] + $imageInfo['image_number'];//总文件数
        $all_info['count_size'] = $fileInfo['total_size'] + $imageInfo['total_size'];//总文件大小
        $all_info['residual_size'] = $imageInfo['library_size'] - $fileInfo['total_size'] - $imageInfo['total_size'];//剩余大小
        
        return $all_info;
    }
    
}