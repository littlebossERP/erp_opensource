<?php
namespace eagle\modules\util\helpers;
use Yii;
/**
 +------------------------------------------------------------------------------
 * pdf merge 的helper 
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		lkh
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class PDFMergeHelper{
	static $shareUrl = "/var/www/14/"; // 共享目录
	
	//
	/**
	 +---------------------------------------------------------------------------------------------
	 * pdf 合并
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param    $targetPath 			string  合并的pdf路径 
	 * @param	 $pdfPathList			array 	pdf路径  e.g. ['/var/www/1.pdf','/var/www/2.pdf',]
	 +---------------------------------------------------------------------------------------------
	 * @return						array	 ['success'=>true , 'filePath'=>'' , 'message'=>'参数不正确']
	 *
	 * @invoking					PDFMergeHelper::PDFMerge('123.pdf', ['/var/www/1.pdf','/var/www/2.pdf',]);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function PDFMerge($targetPath  , $pdfPathList ){
		try{
			if (empty($targetPath)){
				return $result = ['success'=>false , 'filePath'=>'' , 'message'=>'请输入目标文件名！'];
			}
			
			$webUrl = '';
			if (!empty($pdfPathList)){
				//1 个时候 直接返回 当前的路径
				if (count($pdfPathList) == 1){
					$fileName = self::_getFileName($pdfPathList[0]);
					$result = ['success'=>true ,  'filePath'=>$pdfPathList[0]];
				}else{
					// 多个 pdf 时需要合并成一个
					$shell = 'pdfunite';
					foreach ($pdfPathList as $path){
						if (!file_exists($path)) return $result = ['success'=>false ,  'filePath'=>$path , 'message'=>'E1物理路径无效'];
						
						$shell .= ' '.$path;
					}
					$shell .= ' '.$targetPath;
					/*
					echo "<br>*************************************<br>";
					echo $shell;
					echo "<br>*************************************<br>";
					*/
					system($shell,$result);
					
					if ($result == 0){
						$mergePDFFileName = self::_getFileName($targetPath);
						$result = ['success'=>true ,  'filePath'=>$targetPath];
					}else{
						return $result = ['success'=>false ,  'filePath'=>$targetPath , 'message'=>'E2代码执行失败' ,'shell'=>$shell , 'code'=>$result];
					}
				}
			}else{
				//
				$result = ['success'=>false ,  'filePath'=>'' , 'message'=>'参数不正确'];
			}
		}catch(\Exception $e){
			$result = ['success'=>false ,  'filePath'=>'' , 'message'=>$e->getMessage()];
		}
		
		
		return $result;
		
	}//end of PDFMerge
	
	static private function _getFileName($url){
		$list = explode('/', $url);
		if (!empty($list))
			return end($list);
		else
			return '';
	}//end of _getFileName
}