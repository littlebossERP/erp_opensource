<?php
namespace eagle\modules\util\helpers;

use yii;
use eagle\modules\order\models\OdOrder;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use common\helpers\Helper_Array;
use eagle\modules\order\models\Usertab;
use yii\db\Query;

/**
+------------------------------------------------------------------------------
 * 首页控制类
+------------------------------------------------------------------------------
 * @category	application
 * @package		Helper/excel
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
+------------------------------------------------------------------------------
 */


class ExcelHelper
{
	// \PHPExcel_Cell::columnIndexFromString()
	// \PHPExcel_Cell::stringFromColumnIndex()
	private static $num_char = array(
		'a' => 0,
		'b' => 1,
		'c' => 2,
		'd' => 3,
		'e' => 4,
		'f' => 5,
		'g' => 6,
		'h' => 7,
		'i' => 8,
		'j' => 9,
		'k' => 10,
		'l' => 11,
		'm' => 12,
		'n' => 13,
		'o' => 14,
		'p' => 15,
		'q' => 16,
		'r' => 17,
		's' => 18,
		't' => 19,
		'u' => 20,
		'v' => 21,
		'w' => 22,
		'x' => 23,
		'y' => 24,
		'z' => 25,
		'aa' => 26,
		'ab' => 27,
		'ac' => 28,
		'ad' => 29,
		'ae' => 30,
		'af' => 31,
		'ag' => 32,
		'ah' => 33,
		'ai' => 34
	);
	
	public  static $content =array(
			'order_id'=>'小老板订单号',
			'order_source_order_id'=>'平台订单号',
			'order_status'=>'小老板订单状态',
			'logistic_status'=>'物流状态',
			'order_source_status'=>'平台状态',
			'user_message'=>"付款备注",
			'user_content'=>"买家留言",
			'order_manual_id'=>'自定义标签 ',
			'desc'=>'订单备注',
			'attributes'=>'订单商品属性',
// 			'pay_status'=>'平台付款状态',
// 			'shipping_status'=>'平台发货状态',
			'order_source'=>'平台',
			'order_source_site_id'=>'站点',
			'plat_form_account'=>'卖家自定义名称',
			'selleruserid'=>'卖家账号',
			'source_buyer_user_id'=>'买家账号',
			'order_source_transactionid'=>'ebay交易号',
// 			'saas_platform_user_id'=>'卖家平台账号',
// 			'is_manual_order'=>'是否挂起状态',
// 			'order_source_srn'=>'SRN号(ebay)',
//			'order_type'=>'订单类型',
			'currency'=>'货币',
			'price'=>'单价',
			'grand_total'=>'订单金额',
			'subtotal'=>'产品总价格',
			'shipping_cost'=>'运费',
			'discount_amount'=>'折扣',
// 			'returned_total'=>'退款总金额',
			'order_item_cost'=>'订单商品成本',
			'commission_total'=>'佣金',
			'paypal_fee'=>'paypal手续费(ebay)',
			'order_source_create_time'=>'下单日期',
			'printtime'=>'打单时间',
			'paid_time'=>'订单付款时间',
			'complete_ship_time'=>'订单发货时间',
// 			'price_adjustment'=>'价格手动调整（下单后人工调整）',
			'consignee'=>'收货人姓名',
			'consignee_country'=>'收件人国家',
			'consignee_postal_code'=>'收货人邮编',
			'consignee_phone'=>'收货人电话',
			'consignee_mobile'=>'收货人手机',
			'consignee_email'=>'收货人Email',
			'consignee_company'=>'收货人公司',
			'consignee_country_code'=>'收货人国家代码',
			'consignee_country_label_cn'=>'收货人国家中文',
			'consignee_city'=>'收货人城市',
			'consignee_province'=>'收货人省',
			'consignee_district'=>'收货人区',
			'consignee_county'=>'收货人镇',
			'consignee_address_line1'=>'收货人地址1',
			'consignee_address_line2'=>'收货人地址2',
			'consignee_address_line3'=>'收货人地址3',
			'address1_2'=>'地址1+地址2',
			'addressdetail'=>'详细地址',
			'default_warehouse_id'=>'仓库',
			'order_source_shipping_method'=>'客选物流',
// 			'default_carrier_code'=>'默认物流商代码',
			'default_shipping_method_code'=>'运输服务',
// 			'create_time'=>'创建时间',
// 			'update_time'=>'更新时间 ',
			'tracknum'=>'物流跟踪号',
			'sku'=>'店铺SKU',
			'root_sku'=>'本地商品sku',
			'quantity'=>'数量',
			'name_cn'=>'中文配货名称',
			'name_en'=>'英文配货名称',
			'name'=>'商品名称',
			'product_name'=>'商品标题',
	        'seller_weight'=>'称重重量',
	        'prod_weight' => '商品重量',
			'declaration_ch'=>'商品报关中文名',
			'declaration_en'=>'商品报关英文名',
			'declaration_value'=>'商品报关价值',
			'declaration_value_currency'=>'商品报关货币',
			'photo_primary'=>'商品主图',
			'photo_url'=>'商品主图URL',
	        'product_comment'=>'商品备注',
	        'alias_comment'=>'商品别名备注',
			'order_source_itemid'=>'平台商品itemId',
// 			'order_source_order_item_id'=>'订单商品编号 /交易编号',
			'sku_quantity'=>'多品名',
			'custom'=>'自定义',
	);
	
	public  static $item_content =array(
			'order_source_srn'=>'下单日期',
			'order_source_order_item_id'=>'订单来源ID',
			'sku'=>'订单状态',
			'product_name'=>'付款状态',
			'photo_primary'=>'商品主图冗余',
			'photo_url'=>'商品主图URL',
			'shipping_price'=>'运费',
			'shipping_discount'=>'运费折扣',
			'price'=>'下单时价格',
			'promotion_discount'=>'促销折扣',
			'ordered_quantity'=>'下单时候的数量',
			'quantity'=>'需发货的商品数量',
			'sent_quantity'=>'已发货数量',
			'packed_quantity'=>'已打包数量',
			'returned_quantity'=>'退货数量',
			'invoice_requirement'=>"发票要求",
			'buyer_selected_invoice_category'=>'发票种类 ',
			'invoice_title'=>'发票抬头',
			'invoice_information'=>'发票内容',
			'create_time'=>'创建时间',
			'update_time'=>'更新时间',
	);
	
	public  static $order_content =array(
			'A'=>'order_source_create_time',
			'B'=>'order_source_order_id',
			'C'=>'order_status',
			'D'=>'pay_status',
			'E'=>'shipping_status',
			'F'=>'selleruserid',
			'G'=>'order_source',
			'H'=>'order_source_site_id',
			'I'=>'source_buyer_user_id',
			'J'=>'consignee_country',
			'K'=>'grand_total',
			'L'=>'currency',
			'M'=>'is_manual_order',
			'N'=>'order_source_srn',
			'O'=>"user_message",
			'P'=>'order_manual_id',
			'Q'=>'order_type',
			'R'=>'saas_platform_user_id',
			'S'=>'order_source_shipping_method',
			'T'=>'subtotal',
			'U'=>'shipping_cost',
			'V'=>'discount_amount',
			'W'=>'returned_total',
			'X'=>'price_adjustment',
			'Y'=>'consignee',
			'Z'=>'consignee_postal_code',
			'AA'=>'consignee_phone',
			'AB'=>'consignee_mobile',
			'AC'=>'consignee_email',
			'AD'=>'consignee_company',
			'AE'=>'consignee_country_code',
			'AF'=>'consignee_city',
			'AG'=>'consignee_province',
			'AH'=>'consignee_district',
			'AI'=>'consignee_county',
			'AJ'=>'consignee_address_line1',
			'AK'=>'consignee_address_line2',
			'AL'=>'consignee_address_line3',
			'AM'=>'default_warehouse_id',
			'AN'=>'default_carrier_code',
			'AO'=>'default_shipping_method_code',
			'AP'=>'paid_time',
			'AQ'=>'delivery_time',
			'AR'=>'create_time',
			'AS'=>'update_time',
			'AT'=>'printtime',
		 
	);

	private static $excel_resize_img_file_mapping_md5 = [];
	private static $RemoteFilehHostCanNotConnect = [];
	
	//$data_arr数据二维数组
	//$column_name_arr列索引数组(每一列的第一行名字)
	//$save_file_name保存文件名
	public static function array2csv($data_arr=array(), $column_name_arr=array(), $filename=NULL)
	{
		header("Content-type:text/csv"); 
		header("Content-Disposition:attachment;filename=".$filename); 
		header('Cache-Control:must-revalidate,post-check=0,pre-check=0'); 
		header('Expires:0'); 
		header('Pragma:public'); 
		//如果定义导出的列名
		if(!empty($column_name_arr)){
			echo iconv("UTF-8","gbk", implode(',', $column_name_arr))."\r\n";
		}
		foreach($data_arr as $value)
		{
			$tmpstr = str_replace(',', '，', implode('asld4kf6a2s&d7lsd', $value));
			$tmpstr = str_replace('asld4kf6a2s&d7lsd', ',', $tmpstr);
			$tmpstr = str_replace("\n", ' ', $tmpstr);
			$tmpstr = str_replace("\r", ' ', $tmpstr);
			echo iconv('UTF-8', 'gbk', $tmpstr)."\r\n";
		}
		//$this->PHPExcel_Writer_CSV->save('php://output');
	}


	//导入csv文件，并转换为数组
	//$fp文件指针
	//$keyarr列索引数组
	public function csv2array($file, $keyarr=array())
	{
		$returnArr = array();//最终返回数组
		fgetcsv($file,100000, ",");//跳过第一行
		while($data = fgetcsv($file,100000, ","))
		{
			foreach($data as $k=>$v)
				$tmpArr[$keyarr[$k]] = iconv('GBK', 'UTF-8', $v);
			//$tmpArr[$keyarr[$k]] = mb_convert_encoding('GBK', 'UTF-8', $v);
			$returnArr[] = $tmpArr;
		}
		return $returnArr;
	}
	
	/**
	 * 从excel文件获取数据到一个二维数组中，支持xls,xlsx格式的文件的导入。
	 *
	 * @param array $file 上传文件句柄
	 * @param array $column_field  excel列字符索引对应表中字段名
	 * @param boolean $ignore_first_row 是否忽略第一行(true 忽略第一行)
	 * @param boolean $is_automatic_matching 是否自动根据Excel列名匹配对应字段名
	 * 
	 * @return array $mapData. Array containing row number and row data.
	 * e.g.
	 * 		1.如果定义了$column_field :array( "A" => "sku", "B" => "name", "C" => "category_name") 即用户自定义 excel里面A列数据为sku ， B列为name，C列为category_name
	 * 		array( 
 	 * 		  2 => // row number：2.
	 * 			array(
	 * 		      'sku' => 'BK00090' 
	 * 		      'name' => '淡水贝壳圆珠/DIY半成品散珠子串珠材料批发 水晶饰品手工配件' 
	 *		      'category_name' => '手饰' 
	 *			)
	 *		  32 => // row number：32.
	 *		    array(
	 *		      'sku' => '' 
	 *		      'name' => ''
	 *		      'category_name' => '衣服'
	 *			)
	 * 		)
	 * 
	 * 		2.如果没有定义$column_field ， 则先找到最大column 下标 ，然后 row data 返回的最大column 下标个数的值，对应column没有定义的，返回 "".
	 * 	 	array (
 	 * 		  2 => // row number：2.
	 * 			array (size=3
	 * 		      'A' => 'BK00090' 
	 * 		      'B' => '淡水贝壳圆珠/DIY半成品散珠子串珠材料批发 水晶饰品手工配件' 
	 *		      'C' => '手饰'
	 *			  'D' => ''
 	 *   		),
 	 *   
	 *		  32 => // row number：32.
	 *		    array (
	 *		      'A' => '' 
	 *		      'B' => ''
	 *		      'C' => '衣服'
	 *			  'D' => '阿玛尼'
	 * 			),
	 * 		)
	 */
	public static function excelToArray( $file, $column_field = array(), $ignore_first_row = true, $is_automatic_matching = false ){
// 		$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;// 节省读取文件空间，使用更少内存
// 		$cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;// 节省读取文件时间，使用更多内存
// 		$cacheSettings = array();
// 		\PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
		
		//取得扩展名
		$extension = strtolower(substr($file['name'] , strripos($file['name'],'.') + 1 )) ;
		if(empty($file['name'])){
			return TranslateHelper::t("文件上传失败,请按'F5'刷新页面再操作导入产品。");
		}
		try {
			//根据不同扩展名创建阅读器
			switch (trim($extension)){
				case 'xlsx':$reader = \PHPExcel_IOFactory::createReader('Excel2007');break;
				case 'xls':$reader = \PHPExcel_IOFactory::createReader('Excel5');break;
				//case 'csv':
				//	$reader = \PHPExcel_IOFactory::createReader('CSV');
				// 	$reader->setInputEncoding('GBK');
				//	break;
				default: return TranslateHelper::t('上传文件为不支持文件格式。请将文件另存为XLS格式或下载示例文件在上面修改。');
			}
				
			//设为只读
			$reader->setReadDataOnly(true);
			
// 			echo "memory usage2:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
			
			//加载文件
			$objPHPExcel = $reader->load($file['tmp_name']);
			
// 			echo "memory usage3:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
			
			//如果没有起始行, 默认为第一行
			$first_row = $ignore_first_row ? 2 : 1;
			//检查sheet数量
			$sheet_counts = $objPHPExcel->getSheetCount();
				
			//得到所有表格对象, 取第一个活动表格
			for($i=0; $i<$sheet_counts; $i++){
				$objWorksheet = $objPHPExcel->getSheet($i);
				//取所有行
// 				$last_row = $objWorksheet->getHighestRow();
				$last_row = $objWorksheet->getHighestDataRow();
// 				$allColumn = $objWorksheet->getHighestColumn(); //取得总列数 包括formular单元格
				$allColumn = $objWorksheet->getHighestDataColumn(); //取得总列数
// 				$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($allColumn) - 1;
				if($last_row > 1) break;
			}
			
// 			$sheetData = $objWorksheet->toArray(null,true,true,true);
			
			$maxCell = $objWorksheet->getHighestRowAndColumn();
			$originSheetData = $objWorksheet->rangeToArray('A'.$first_row.':' . $maxCell['column'] . $maxCell['row']);// get all sheet data
			
// 			echo "memory usage4:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
			
// 			$sheetData = array_map('array_filter', $sheetData);// filter empty column
			$sheetData = array();
			foreach ($originSheetData as $index=>$tempRow){// filter empty column 
				$tempRow = array_filter($tempRow,'self::array_filter_callback');
				$sheetData[$index] = $tempRow;
			}
			$sheetData = array_filter($sheetData);// filter empty row
// 			var_dump($sheetData);

// 			echo "memory usage5:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
			//如果所有表格为空
			if(empty($sheetData)) return TranslateHelper::t('全部表格为空');
			$mapData = array();
			//循环取得excel值
			if(!empty($column_field)){
			    if($is_automatic_matching){
			        $row_mapping = array();
			        //根据列名自动匹配
			        foreach ( $sheetData as $rowIndex => $rowData ){
			            $rowNumber = $rowIndex + $first_row;
			            if($rowIndex == 0){
			                foreach($rowData as $col_num => $col_name){
			                    foreach($column_field as $k => $v){
			                        foreach($v as $mat_name => $mat_type){
			                            if($mat_type == 'equal' && strtoupper($col_name) == strtoupper($mat_name)){
			                                $row_mapping[$col_num] = $k;
			                                unset($v);
			                                break 2;
			                            }
			                            else if($mat_type == 'like' && strpos(strtoupper($col_name),strtoupper($mat_name)) !== false){
			                                $row_mapping[$col_num] = $k;
			                                $mat_status = 1;
			                                unset($v);
			                                break 2;
			                            }
			                        }
			                    }
			                }
			            }
			            else if(!empty($row_mapping)){
    			            foreach($row_mapping as $k => $v){
        						$wordstring = isset($rowData[$k]) ? $rowData[$k] : '';
        						$mapData[$rowNumber][$v] = self::characet($wordstring);
        					}
			            }
			        }
			    }
			    else{
    				foreach ( $sheetData as $rowIndex => $rowData ){
    					$rowNumber = $rowIndex + $first_row;
    					foreach($column_field as $k=>$v){
    						$columnIndex = \PHPExcel_Cell::columnIndexFromString(strtoupper($k)) - 1 ;
    						$wordstring = isset($rowData[$columnIndex])?$rowData[$columnIndex]:'';
    						$mapData[$rowNumber][$v] = self::characet($wordstring);
    					}
    				}
			    }
			}else{
				// 当$column_field为空，获取二维数组最大的下标为$highestColumnIndex
				$highestColumnIndex = 0;
				foreach ( $sheetData as $temRow ){
					$columns = array_keys($temRow);
					if(max($columns) > $highestColumnIndex){
						$highestColumnIndex = max($columns);
					}
				}

				foreach ( $sheetData as $rowIndex => $rowData ){
					$rowNumber = $rowIndex + $first_row;
					for( $i = 0 ; $i <= $highestColumnIndex ; $i++ ){
						$wordstring = isset($rowData[$i]) ? $rowData[$i] : '';
						$mapData[$rowNumber][\PHPExcel_Cell::stringFromColumnIndex($i)] = self::characet($wordstring);
					}
				}
			}
			
// 			echo "memory usage6:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
			unset($reader);
			unset($objPHPExcel);
			unset($objWorksheet);
			unset($originSheetData);
			unset($sheetData);
// 			echo "memory usage7:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;

// 		}catch (\PHPExcel_Reader_Exception $e){
// 			\Yii::error("excelToArray: File:".$e->getFile().",Message:".$e->getMessage(),"file");
// 			return "File:".$e->getFile().",Message:".$e->getMessage();
		}catch (\Exception $e) {
			// 			SysLogHelper::SysLog_Create("Catalog",__CLASS__, __FUNCTION__,"","get import file info Exception $e ", "trace");
			\Yii::error("excelToArray: File:".$e->getFile().",Lile:".$e->getLine().",Message:".$e->getMessage(),"file");
			return "File:".$e->getFile().",Lile:".$e->getLine().",Message:".$e->getMessage();
		}
		return $mapData;
		
	}
	
	
	/**
	 * 从一个二维数组中将数据导出到Excel中，Excel文件通过header方式发送到用户浏览器下载。支持导出xls,xlsx格式的文件.
	 * $data_array 数据为空或 $excel_file_name 文件命名有问题的，中止导出文件，并返回一个数组：Array( ”success”=>0 , ”message”=>”XXX” )。
	 * 
	 * @param array $data_array 		要导出的二维数组。 
	 * @param array $filed_array		 数据字段翻译，值为列的解释名称。
	 * @param string $excel_file_name 	导出的excel文件的名称。如果参数为空或者文件扩展名不是xls或xlsx时，系统默认生成文件名为'littleboss'.time().'.xlsx'。
	 * @param array $img_filed_arr		like : [filed_name1=>[maxWidth=>int,maxHeight=>int],filed_name2=>[],...]带图片导出时，需要指定图片列的field名，如果带图片，图片的来源需要为网址绝对路径，或指定文件夹下的本地文件，jpg或者png
	 * @param bool $isDownload		           是否浏览器导出，默认是
	 * @param array $excel_col_stype    导出excel的格式设置组
	 * @param int   $img_max_count      导出最大图片数量
	 */
	public static function exportToExcel($data_array, $filed_array=array(), $excel_file_name=null,$img_filed_arr=array(),$isDownload=true,$excel_col_stype=array(),$img_max_count = 0){
		/*\Yii::info(['Excel',__CLASS__,__FUNCTION__,'Online',"try to fuck $excel_file_name with ".
				print_r($data_array,true) . " and fild array  ".print_r($filed_array,true)
				],"edb\global");
		*/
		
		$uid = \Yii::$app->subdb->getCurrentPuid();
		$result['success'] = 0;
		$result['message'] = '';
		//数据为空或者文件命名有问题的，跳出。
		if(empty($data_array)){$result['message'] = '导出数据不得为空'; return $result;}
		if($excel_file_name){
			//$excel_file_name = preg_replace('/(\\\)+(\\/)+/', '', $excel_file_name);
			$excel_file_name = str_replace('*', '-', $excel_file_name);
			$excel_file_name = str_replace(':', '-', $excel_file_name);
			$excel_file_name = str_replace('/', '-', $excel_file_name);
			$excel_file_name = str_replace('\\', '-', $excel_file_name);
			$excel_file_name = str_replace('?', '-', $excel_file_name);
			$excel_file_name = str_replace('[', '-', $excel_file_name);
			$excel_file_name = str_replace(']', '-', $excel_file_name);
			if(strlen($excel_file_name)>100){
				$result['message'] = '文件名不得长于100个字符';
				return $result;
			}
		}
		$tmp_array = array();
		$orderSource = odorder::$orderSource;
		foreach($data_array as $k=>$v){
			$tmp = array();
			foreach($v as $k2=>$v2){
				if ($v2==''){
					$tmp[$k2]= strval($v2);
					continue;
				}
				$tmp[$k2] = strval($v2);
				
				//特殊字符开头处理
				if(!empty($v2) && substr($v2, 0, 1) == '='){
				    $v2=' '.$v2;
				}
				    
				switch ($k2){
					case 'order_source':
						$tmp[$k2]=isset($orderSource[$v2])?$orderSource[$v2]:$v2;
						break;
					case 'order_source_create_time':
					case 'paid_time':
					case 'delivery_time':
					case 'create_time':
					case 'update_time':
					case 'printtime':
					case 'complete_ship_time':
						if (($v2)>0){
							$tmp[$k2]=date('Y-m-d H:i:s',$v2);
						}else{
							$tmp[$k2]='';
						}
						break;
					case 'order_status':
						$tmp[$k2]=OdOrder::$status[$v2];
						break;
					case 'pay_status':
						$tmp[$k2]=OdOrder::$paystatus[$v2];
						break;
					case 'shipping_status':
						$tmp[$k2]=($v2=='0'?'未发货':'已发货');
						break;
					case 'default_warehouse_id':
						if (!is_null((int)$v2)){
							$warehouse = InventoryApiHelper::getWarehouseIdNameMap();
							$tmp[$k2]=@$warehouse[(int)$v2];
						}else{
							$tmp[$k2]='';
						}
						break;
					case 'default_shipping_method_code':
						if (!is_null($v2)){
							$ships = CarrierApiHelper::getShippingServices();
							$tmp[$k2]=@$ships[$v2];
						}else{
							$tmp[$k2]='';
						}
						break;
					case 'consignee_postal_code':
						$tmp[$k2]=' '.$v2;
						break;
					//liang 2015-11-17 start
					case 'consignee_phone':
						if(is_numeric($v2))
							$tmp[$k2]=' '.$v2;
						break;
					case 'consignee_mobile':
						if(is_numeric($v2))
							$tmp[$k2]=' '.$v2;
						break;
					case 'order_source_order_id':
						if(is_numeric($v2))
							$tmp[$k2]=' '.$v2;
						break;
					//liang 2015-11-17 end
					default:
						$tmp[$k2] = strval($v2);
						break;
				}
//				$tmp[$k2] = strval($v2);
			}
			$tmp_array[$k] = $tmp;
		}
		$data_array = $tmp_array;
		//将表格列标题加入数组
		//var_dump($data_array);die;
	/*	\Yii::info(['Excel',__CLASS__,__FUNCTION__,'Online',"try to 1 $excel_file_name with ".
				print_r($data_array,true) . " and fild array  ".print_r($filed_array,true)
				],"edb\global");
		*/
		$excel_array = array();
		if(!empty($filed_array)){
			$excel_array[] = $filed_array;//excel第一行，对应每列数据对应的内容
			foreach($data_array as $v)
				$excel_array[] = $v;
		}else{
			$excel_array = $data_array;
		}
		$phpexcel = new \PHPExcel();
		unset($data_array);//data array 已使用完毕, 释放内存
		
		//得到扩展名
		if(!$excel_file_name) $excel_file_name = 'littleboss'.time().'.xlsx';
		$extention = substr($excel_file_name, strrpos($excel_file_name, '.')+1);
		
		// xls sheet name 测出最多是31个字符数，但是不知道能不能修改
		if('xls' == $extention && iconv_strlen($excel_file_name , 'utf-8') > 31){
			$result['message'] = 'xls sheet name不得长于31个字符';
			return $result;
		}
		
		//如果不是这三种格式则用默认值
		if(!in_array(trim($extention), array( 'xls', 'xlsx' )))$excel_file_name = 'littleboss'.time().'.xlsx';
		
		switch(trim($extention)){
// 			case 'csv': return self::array2csv($data_array, $filed_cn_array, $excel_file_name);break;
			case 'xls': $writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');break;
			case 'xlsx': $writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');break;
			default: $writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
		}
	
		$phpexcel->setActiveSheetIndex(0);
		$sheet = $phpexcel->getActiveSheet();
		$sheet->setTitle($excel_file_name);
		$sheet->fromArray($excel_array);
		
		//设置excel单元格格式
		if(!empty($excel_col_stype)){
		    $is_setWrapText = false;   //自动换行
		    $is_setWidth = 0;          //列宽
		    $is_setHorizontal = false;    //垂直居中
		    foreach ($excel_col_stype as $k => $v){
		    	if($k == 'setWrapText' && $v === true){
		    	    $is_setWrapText = true;
		    	}
		    	else if($k == 'setWidth' && $v > 0){
		    	    $is_setWidth = $v;
		    	}
		    	else if($k == 'setHorizontal' && $v === true){
		    	    $is_setHorizontal = true;
		    	}
		    }
		    if($is_setWrapText || $is_setWidth > 0){
		        $data_count = count($excel_array);
        		$count = count($filed_array);
        		$startN = (int)($count / 26);
        		$endN = $count % 26;
        		$startStr = chr($startN + ord('A') - 1);
        		$endStr = chr($endN + ord('A') - 1);
        		
        		$startStr = '';
        		$endStr = '';
        		for($n = 1; $n <= $count; $n++){
        		    if($n > 26){
        		        if($n % 26 == 0){
        		            $startN = (int)($n / 26) - 1;
        		            $endN = 26;
        		        }
        		        else{
        		            $startN = (int)($n / 26);
        		            $endN = $n % 26;
        		        }
        		        
                		$startStr = chr($startN + ord('A') - 1);
                		$endStr = chr($endN + ord('A') - 1);
        		    }
        		    else{
        		        $endN = $n % 27;
        		        $endStr = chr($endN + ord('A') - 1);
        		    }
        		    $colStr = $startStr.$endStr;
        		    
        		    if($is_setWrapText){
        		        $sheet->getStyle($colStr.'2:'.$colStr.$data_count)->getAlignment()->setWrapText(TRUE);
        		    }
        		    if($is_setWidth > 0){
        		        $sheet->getColumnDimension($colStr)->setWidth($is_setWidth);
        		    }
        		    if($is_setWrapText){
        		    	$sheet->getStyle($colStr.'1:'.$colStr.$data_count)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        		    }
        		}
    		}
		}
		
		//++++++++++++++++++++++++++++++++++导出带图片的excel处理	lzhl 2016-11-05+++++++++++++++++++++++
		//设置了输出图片filed，则进入图片写入流程
		
		
		if(!empty($img_filed_arr)){
			$excel_data_key_array = array_keys($excel_array[1]);
			$filed_intersect = array_intersect(array_keys($img_filed_arr),$excel_data_key_array);
			
			$tmp_img_filed_arr = [];
			if(!empty($filed_intersect)){
				foreach ($img_filed_arr as $img_filed=>$width_height_info){
					if(in_array($img_filed, $filed_intersect))
						$tmp_img_filed_arr[$img_filed] = $width_height_info;
				}
			}
			$img_filed_arr = $tmp_img_filed_arr;
			
			try{
				$img_count = 0;   //用于判断最大输出图片数量
				self::$RemoteFilehHostCanNotConnect = [];//链接超时或有问题打不开的url，域名集合
				foreach ($img_filed_arr as $img_filed=>$width_height_info){
					$ColumnDimension = '';
					$DimensionMaxWidth = '';
					$img_max_width = empty($width_height_info['width'])?150:$width_height_info['width'];//图片最大宽度
					$img_max_height = empty($width_height_info['height'])?150:$width_height_info['height'];//图片最大高度
					//获得列标
					$excel_data_key_array = array_keys($excel_array[1]);
					foreach ($excel_data_key_array as $index=>$filed_key){
						if($img_filed==$filed_key)
							$ColumnDimension = self::ColumnIndexToChr($index);
					}
					
					if(!empty($ColumnDimension)){
						//对每行数据进行轮询
						foreach ($excel_array as $rowIndex=>$rowData){
							//var_dump($rowData);
							$imgsrc = '';//图片来源,网址绝对路径或本地文件
							$tmp_flie_name = '';//临时压缩图片文件名
							
							if($rowIndex==0)
								continue;//表头跳过
							
							//有需要转成输出图片的就处理
							if(!empty($rowData[$img_filed])){
								$imgsrc = $rowData[$img_filed];
								if(empty($imgsrc))
									continue;
								if(!empty($img_max_count) && $img_count > $img_max_count){
									continue;
								}
								//var_dump($imgsrc);
								$exportImage = self::getExportImage($imgsrc, $uid, $img_max_width, $img_max_height);
								//var_dump($exportImage);
								
								if(!empty($exportImage['resource']) && !empty($exportImage['file_name']) && !empty($exportImage['type'])){
									if($exportImage['type']=='jpeg' || $exportImage['type']=='jpg' || $exportImage['type']==2)
										$img = imagecreatefromjpeg($exportImage['file_name']);
									elseif($exportImage['type']=='png' || $exportImage['type']==3)
										$img = imagecreatefrompng($exportImage['file_name']);
									else{
										$img='';//图片格式不支持jpeg和png以外的,跳出
										continue;
									}
									$sheet->setCellValue($ColumnDimension.($rowIndex+1),'');//清空单元格
									$width = imagesx($img);
									$height = imagesy($img);
									$objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
									//$objDrawing->setName( $img_filed.($rowIndex+1) );
									//$objDrawing->setDescription( $img_filed.($rowIndex+1) );
									$objDrawing->setCoordinates($ColumnDimension.($rowIndex+1));
									$objDrawing->setImageResource($img);
									$objDrawing->setOffsetX(1);
									$objDrawing->setOffsetY(2);
									$objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_DEFAULT);//渲染方法
									$objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
									$objDrawing->setWidth($width);
									$objDrawing->setHeight($height);
									
									if(empty($DimensionMaxWidth) || $width/6.5 > $DimensionMaxWidth )
										$DimensionMaxWidth = $width/6.5;//像素与行宽转换
									
									$sheet->getColumnDimension($ColumnDimension)->setWidth( $DimensionMaxWidth );
									$sheet->getRowDimension( ($rowIndex+1).'' )->setRowHeight( $height/1.3 );//像素与行高转换
									$objDrawing->setWorksheet($sheet);
									$img_count++;
									
									unset($exportImage);
									unset($objDrawing);
								}else{
									unset($exportImage);
									continue;
								}
							}
						}//end of each row data
					}
				}//end of each img filed
			}catch (\Exception $e) {
				$journal_id = SysLogHelper::InvokeJrn_Create("Excel",__CLASS__, __FUNCTION__ , array(
						$uid,$excel_array, $filed_array, $excel_file_name,$img_filed_arr));
				SysLogHelper::InvokeJrn_UpdateResult($journal_id, $e->getMessage());
				unset($excel_array);
				unset($phpexcel);
				
				\Yii::info('export_excel_img uid:'.$uid.'，  '.$e->getMessage()."\r\n".$e->getTraceAsString(), "file");
				
				if($isDownload){
				    exit("导出遇到问题，请联系客服！");
				}
				else{
				    $result['message'] = '导出图片中失败，请联系客服！';
				    return $result;
				}
			}
			
			//轮询设置垂直居中 start ######################
			$highestRow = $sheet->getHighestRow(); // e.g. 10
			$highestColumn = $sheet->getHighestColumn(); // e.g 'F'
			$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn); // 5
			
			for ($row = 1; $row <= $highestRow; ++$row) {
				for ($col = 0; $col <= $highestColumnIndex; ++$col) {
					$sheet->getCellByColumnAndRow($col, $row)->getStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
				}
			}//轮询设置垂直居中 end
		}//图片写入end
		//++++++++++++++++++++++++++++++++++导出带图片的excel处理	 end+++++++++++++++++++++++
		
		unset($excel_array);//excel array 已使用完毕, 释放内存
		/*
		$aa = get_class_methods($sheet);
		var_dump($aa);exit;
		*/
		//导出操作
		if($isDownload){
    		ob_end_clean();//清除缓冲区,避免乱码
    		header('Content-Type:application/csv;charset=UTF-8');
    		//header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
    		header('Content-Disposition: attachment;filename="'.$excel_file_name.'"');
    		header('Cache-Control: max-age=0');
    		header("Content-type: text/xlsx");
    		//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8');
    		//$filename = '包裹统计信息.xls';
    		$writer->save('php://output');
    		exit;
		}else{
			try {
			    //创建导出路径
			    $pathArr = self::createExcelDir(true);
			    $path = $pathArr['path'];
			    $urlpath = $pathArr['urlpath'];
				//echo "memory usage:".round(memory_get_usage()/1024/1024 , 2)."M.".PHP_EOL;
				// 保存到eagle/web/attachment/tmp_export_file目录下
				$writer->save($path.DIRECTORY_SEPARATOR.$excel_file_name);
				$result['success'] = 1;
				$result['src_file_url'] = $urlpath.DIRECTORY_SEPARATOR.$excel_file_name;
				return $result;
			} catch (\Exception $e) {
				$errorMessage = "file:".$e->getFile()." line:".$e->getLine()." message:".$e->getMessage();
				\Yii::error("justExportToExcel $errorMessage","file");
				$result['message'] = $errorMessage;
				return $result;
			}
		}
	}
	
	
	/**
	 * 从一个二维数组中将数据导出到csv文件中，csv文件通过header方式发送到用户浏览器下载。支持导出csv格式的文件.
	 * $data_array 数据为空或 $excel_file_name 文件命名有问题的，中止导出文件，并返回一个数组：Array( ”success”=>0 , ”message”=>”XXX” )。
	 *
	 * @param array $data_array 要导出的二维数组。
	 * @param array $filed_array 数据字段翻译，值为列的解释名称。
	 * @param string $excel_file_name 导出的excel文件的名称。如果参数为空或者文件扩展名不是xls或xlsx时，系统默认生成文件名为'littleboss'.time().'.xlsx'。
	 * @param array $castStrArr 需要将特定字段强制为字符串，因为csv存在科学计数法。暂时支持转换为字符串类型，以后可以扩展
	 * 		    如果想某列不用科学计数法要定义	$castStrArr = array();  //强制转换类型数组
     										$castStrArr['order_id'] = 'str';  //order_id为需要转换的键值，str 为转换为字符串类型
     *									
	 */
	public static function exportToCsv($data_array, $filed_array=array(), $excel_file_name=null, $castStrArr=array()){
		$result['success'] = 0;
		$result['message'] = '';
		//数据为空或者文件命名有问题的，跳出。
		if(empty($data_array)){$result['message'] = '导出数据不得为空'; return $result;}
		if($excel_file_name){
			//$excel_file_name = preg_replace('/(\\\)+(\\/)+/', '', $excel_file_name);
			$excel_file_name = str_replace('*', '-', $excel_file_name);
			$excel_file_name = str_replace(':', '-', $excel_file_name);
			$excel_file_name = str_replace('/', '-', $excel_file_name);
			$excel_file_name = str_replace('\\', '-', $excel_file_name);
			$excel_file_name = str_replace('?', '-', $excel_file_name);
			$excel_file_name = str_replace('[', '-', $excel_file_name);
			$excel_file_name = str_replace(']', '-', $excel_file_name);
			if(strlen($excel_file_name)>100){
				$result['message'] = '文件名不得长于100个字符';
				return $result;
			}
		}
		$tmp_array = array();
		foreach($data_array as $k=>$v){
			$tmp = array();
			foreach($v as $k2=>$v2){
				switch ($k2){
					case 'order_source_create_time':
					case 'paid_time':
					case 'delivery_time':
					case 'create_time':
					case 'update_time':
					case 'printtime':
						if (($v2)>0){
							$tmp[$k2]=date('Y-m-d H:i:s',$v2);
						}else{
							$tmp[$k2]='';
						}
						break;
					case 'order_status':
						$tmp[$k2]=OdOrder::$status[$v2];
						break;
					case 'pay_status':
						$tmp[$k2]=OdOrder::$paystatus[$v2];
						break;
					case 'shipping_status':
						$tmp[$k2]=($v2=='0'?'未发货':'已发货');
						break;
					case 'default_warehouse_id':
						if (!is_null($v2)){
							$warehouse = InventoryApiHelper::getWarehouseIdNameMap();
							$tmp[$k2]=@$warehouse[$v2];
						}else{
							$tmp[$k2]='';
						}
						break;
					case 'default_shipping_method_code':
						if (!is_null($v2)){
							$ships = CarrierApiHelper::getShippingServices();
							$tmp[$k2]=@$ships[$v2];
						}else{
							$tmp[$k2]='';
						}
						break;
					case 'order_manual_id':
						$usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
						$tmp[$k2]=@$usertabs[$v2];
						break;
					default:
						if (count($castStrArr) == 0){
							$tmp[$k2] = strval($v2);
						}else {
							if (array_key_exists($k2, $castStrArr)){
								if ($castStrArr[$k2] == 'str')
									$tmp[$k2]=strval($v2)."\t";
							}else{
								$tmp[$k2]=strval($v2);
							}
						}
						break;
				}
			}
			$tmp_array[$k] = $tmp;
		}
		$data_array = $tmp_array;
	
		unset($tmp_array);  //数组$tmp_array 释放
	
		//将表格列标题加入数组
		$excel_array = array();
		if(!empty($filed_array)){
			$excel_array[] = $filed_array;//excel第一行，对应每列数据对应的内容
			foreach($data_array as $v)
				$excel_array[] = $v;
		}else{
			$excel_array = $data_array;
		}
	
		unset($data_array);  //数组$data_array 释放
	
		//得到扩展名
		if(!$excel_file_name) $excel_file_name = 'littleboss'.time().'.csv';
		$extention = substr($excel_file_name, strrpos($excel_file_name, '.')+1);
	
		// xls sheet name 测出最多是31个字符数，但是不知道能不能修改
		if('csv' == $extention && iconv_strlen($excel_file_name , 'utf-8') > 31){
			$result['message'] = 'csv sheet name不得长于31个字符';
			return $result;
		}
	
		//如果不是这三种格式则用默认值
		if(!in_array(trim($extention), array( 'csv' )))$excel_file_name = 'littleboss'.time().'.csv';
	
		$fh = fopen('php://output', 'w') or die("can't open php://output");
		
		//Windows下使用BOM来标记文本文件的编码方式
		fwrite($fh,chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 告诉浏览器发送的是一个csv文件
		//ob_end_clean();
		/*
		header('Content-Type:application/csv;charset=GBK');//UTF-8 original
		header("Content-Disposition: attachment; filename=".$excel_file_name);
		header('Expires:0');
		header('Cache-Control: max-age=0');
		header('Pragma:public');
	*/
		// force download
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		
		// disposition / encoding on response body
		header("Content-Disposition: attachment;filename={$excel_file_name}");
		header("Content-Transfer-Encoding: binary");
		
		// 输出每一行数据
		foreach($excel_array as $excel_line){
			//if(fputcsv($fh, $excel_line) === false){
			if(fputcsv($fh,  $excel_line) === false){
				die("Can't write CSV line");
			}
		}
	
		unset($excel_array);  //数组$excel_array 释放
	
		fclose($fh) or die("Can't close php://output");
	
		exit;
	}
	
	/**
	 * @param array $sheetInfo: array(
	 *  'data_array'=>$data_array, //要导出的二维数组。 
	 *  'filed_array'=>$filed_array, //数据字段翻译，值为列的解释名称。
	 *  'title'=>"xxx", 
	 *  ) 
	 * @param $excel_file_name  以上三个参数与ExportToExcel 一致，此处不再描述。
	 * @param boolean $isDownload 是否输出到浏览器下载 还是 保持到本地
	 * 
	 * 但请注意！！！
	 * =========================> 此方法只作纯粹导出之用 <=========================
	 * =========================> 这方法可以是共用的 ，请不要在这里加入特定字段转换什么的处理 <=========================
	 * =========================> 这方法可以是共用的 ，请不要在这里加入特定字段转换什么的处理 <=========================
	 * =========================> 这方法可以是共用的 ，请不要在这里加入特定字段转换什么的处理 <=========================
	 * 重要的事情说三遍！！！
	 * 以上。
	 * 
	 */
	public static function justExportToExcel($sheetInfo,$excel_file_name=null,$isDownload=true){
		$result['success'] = 0;
		$result['message'] = '';
		//数据为空或者文件命名有问题的，跳出。
		if(empty($sheetInfo)){$result['message'] = '导出数据不得为空'; return $result;}
		if($excel_file_name){
			//$excel_file_name = preg_replace('/(\\\)+(\\/)+/', '', $excel_file_name);
			$excel_file_name = str_replace('*', '-', $excel_file_name);
			$excel_file_name = str_replace(':', '-', $excel_file_name);
			$excel_file_name = str_replace('/', '-', $excel_file_name);
			$excel_file_name = str_replace('\\', '-', $excel_file_name);
			$excel_file_name = str_replace('?', '-', $excel_file_name);
			$excel_file_name = str_replace('[', '-', $excel_file_name);
			$excel_file_name = str_replace(']', '-', $excel_file_name);
			if(strlen($excel_file_name)>100){
				$result['message'] = '文件名不得长于100个字符';
				return $result;
			}
		}
		
		$phpexcel = new \PHPExcel();
		
		//得到扩展名
		if(!$excel_file_name) $excel_file_name = 'littleboss'.time().'.xlsx';
		$extention = substr($excel_file_name, strrpos($excel_file_name, '.')+1);
		
		//如果不是这三种格式则用默认值
		if(!in_array(trim($extention), array( 'xls', 'xlsx' , 'csv' )))$excel_file_name = 'littleboss'.time().'.xlsx';
		
		switch(trim($extention)){
			case 'csv': $writer = \PHPExcel_IOFactory::createWriter($phpexcel,"CSV");break;
			case 'xls': $writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');break;
			case 'xlsx': $writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');break;
			default: $writer = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
		}
		
		$sheetIndex = 0;
		foreach ($sheetInfo as $oneSheetInfo){
			$data_array = empty($oneSheetInfo['data_array'])?array():$oneSheetInfo['data_array'];
			$filed_array = empty($oneSheetInfo['filed_array'])?array():$oneSheetInfo['filed_array'];
			$title = empty($oneSheetInfo['title'])?"":$oneSheetInfo['title'];
			
			foreach($data_array as $k=>&$v){
				$tmp = array();
				foreach($v as $k2=>&$v2){
					$v2 = strval($v2);// 全部转为字符串处理
				}
			}
			
			//将表格列标题加入数组
			if(!empty($filed_array)){
				array_unshift($data_array,$filed_array);
			}
			
			// xls sheet name 测出最多是31个字符数，但是不知道能不能修改
			if('xlsx' != $extention && iconv_strlen($title , 'utf-8') > 31){
				$title = substr($title, 0 , 28)."...";
			}
			
			if($sheetIndex != 0){
				$phpexcel->createSheet();
			}
			$phpexcel->setActiveSheetIndex($sheetIndex);
			$sheet = $phpexcel->getActiveSheet();
			$sheetIndex++;
// 			$sheet->fromArray($excel_array);
			$sheet->setTitle($title);
			
			list ($startColumn, $startRow) = \PHPExcel_Cell::coordinateFromString("A1");
			
			// 下面方法可以让 xls 和xlsx 的长数字变成字符串， csv长数字不能解决
			// 可以通过导入数据， 然后对列数据类型定义为文本即可解决 csv长数字问题
			foreach ($data_array as $row =>$rowData){
				$currentColumn = $startColumn;
				foreach ($rowData as $col =>$val){
					if(is_string($val)){
						$sheet->setCellValueExplicit($currentColumn.strval($startRow),$val,\PHPExcel_Cell_DataType::TYPE_STRING);
					} else  if ($val != null) {
						// Set cell value
						$sheet->getCell(\PHPExcel_Cell::stringFromColumnIndex($currentColumn) . $startRow)->setValue($val);
					}
			
					++$currentColumn;
				}
				++$startRow;
			}
// 			var_dump($currentColumn);
// 			var_dump($startRow);
// 			exit();
			
			unset($data_array);//excel array 已使用完毕, 释放内存
		}
		
		unset($sheetInfo);//excel array 已使用完毕, 释放内存
		if($isDownload){
			//导出操作
			ob_end_clean();//清除缓冲区,避免乱码
			header('Content-Type:application/csv;charset=UTF-8');
			header('Content-Disposition: attachment;filename="'.$excel_file_name.'"');
			header('Cache-Control: max-age=0');
			header("Content-type: text/xlsx");
			$writer->save('php://output');
			exit;
		}else{
			try {
// 				echo "memory usage:".round(memory_get_usage()/1024/1024 , 2)."M.".PHP_EOL;
				// 保存到eagle/web/attachment/tmp_export_file目录下
				$writer->save(\Yii::getAlias("@eagle/web/attachment/tmp_export_file").DIRECTORY_SEPARATOR.$excel_file_name);
				$result['success'] = 1;
				return $result;
			} catch (\Exception $e) {
				$errorMessage = "file:".$e->getFile()." line:".$e->getLine()." message:".$e->getMessage();
				\Yii::error("justExportToExcel $errorMessage","file");
				$result['message'] = $errorMessage;
				return $result;
			}
		}
	}
	
	//数组转码公用函数
	public function array_iconv($in_charset,$out_charset,$arr) {
		return eval('return '.iconv($in_charset,$out_charset,var_export($arr,true).';'));
	}
	
	public static function characet($data){
		if( !empty($data) ){
			$fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
			if( $fileType != 'UTF-8'){
				$data = mb_convert_encoding($data ,'utf-8' , $fileType);
			}
		}
		return $data;
	}
	
	public static function array_filter_callback($val){//避免array_filter默认把0过滤掉
		return isset($val);
	}
	
	
	private static function getExportImage($imgsrc,$uid,$width=150,$height=150){
		if(preg_match("/^(http|https|ftp):\/\/(\w|\W|\s|\S)+$/",$imgsrc)){
			//网络来源时,先看看链接是不是小老板的url,不是的话看图片缓存服务器有无
			if(!preg_match("/^(http|https|ftp):\/\/image\-cache\.littleboss\.com(\w|\W|\s|\S)+$/",$imgsrc))
				$imgsrc = ImageCacherHelper::getImageCacheUrl($imgsrc,$uid,1);
		}else{
			if(preg_match("/^\/images\/(\w|\W|\s|\S)+$/",$imgsrc)){
				//小老板相对路径图片的话，先补全路径
				$imgsrc = substr($imgsrc,1);
			}
			//var_dump($imgsrc);
			//本地图片
			try{
				$img_infos = getimagesize($imgsrc);
				list($width,$height,$type) = $img_infos;
				return ['file_name'=>$imgsrc,'resource'=>true,'type'=>$type];
			}catch (\Exception $e){
				return ['file_name'=>'','resource'=>false,'type'=>''];
			}
			
		}
		//var_dump($imgsrc);
		$path = self::createExcelImgDir(true);
		
		return self::compressed_image($imgsrc, $path, $width, $height);
	}
	
	/**
	 * desription 压缩图片
	 * @param sting $imgsrc 图片路径
	 * @param string $imgdst 压缩后保存路径,空时直接输出，不另存文件
	 */
	public static function compressed_image($imgsrc,$path,$maxWidth=150,$maxHeight=150){
		
		$media_type = '';
		$md5FileName = md5($imgsrc).'_'.$maxWidth.'x'.$maxHeight;
		
		//判断db里面有无resize记录
		$db_recorded = self::getExportResizeImgPathFromDb($imgsrc,$maxWidth.'x'.$maxHeight);
		if(!empty($db_recorded['file_path']) && !empty($db_recorded['type'])){
			//db有记录，就判断resize过的图片是否存在于文件夹
			$this_file = self::getImgPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'excel_img'.DIRECTORY_SEPARATOR.$db_recorded['file_path'];
			
			if(file_exists($this_file)){
				return ['file_name'=>$this_file,'resource'=>true,'type'=>$db_recorded['type']];
			}
			
		}
		
		$resize_file_name = '';
		//db无记录，或文件不存在，则立即创建resize图片
		//读取图片宽 高 类型信息	//这里加入一个超时判断机制
		if( self::checkRemoteFile($imgsrc) ){
			try{
				list($width,$height,$type) = getimagesize($imgsrc);
			}catch (\Exception $e){
				return ['file_name'=>'','resource'=>false,'type'=>''];
			}
		}else{
			return ['file_name'=>'','resource'=>false,'type'=>''];
		}
		if($width>$height){//宽大于高，按宽度比例压缩
			$new_width = ($width>$maxWidth?$maxWidth:$width);
			$new_height =($width>$maxWidth? $height*($maxWidth/$width) :$height );
		}else{//宽小于高，按高度比例压缩
			$new_height =($height>$maxHeight?$maxHeight:$height);
			$new_width = ($height>$maxHeight? $width*($maxHeight/$height) :$width);
		}
		
		switch($type){
			case 1:
				$giftype=self::check_gifcartoon($imgsrc);
				if($giftype){
					$imgdst= $path.DIRECTORY_SEPARATOR.$md5FileName.'.gif';
					$resize_file_name = $md5FileName.'.gif';
					$media_type = 'jpeg';
					//header('Content-Type:image/gif');
					$image_wp=imagecreatetruecolor($new_width, $new_height);
					$image = imagecreatefromgif($imgsrc);
					imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
					
					//判断文件是否存在
					if(file_exists($imgdst)){
					    $resource = true;
						break;
					}
					
					//100代表的是质量、压缩图片容量大小
					$resource = imagejpeg($image_wp, $imgdst,100);
					imagedestroy($image_wp);
				}else{
					//类型错误
					$resource = '';
					$imgdst = '';
				}
				break;
			case 2:
				$imgdst= $path.DIRECTORY_SEPARATOR.$md5FileName.'.jpg';
				$media_type = 'jpeg';
				$resize_file_name = $md5FileName.'.jpg';
				//header('Content-Type:image/jpeg');
				$image_wp=imagecreatetruecolor($new_width, $new_height);
				$image = imagecreatefromjpeg($imgsrc);
				imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				
				//判断文件是否存在
				if(file_exists($imgdst)){
				    $resource = true;
					break;
				}
				
				//100代表的是质量、压缩图片容量大小
				$resource = imagejpeg($image_wp, $imgdst,100);
				imagedestroy($image_wp);
				break;
			case 3:
				$imgdst= $path.DIRECTORY_SEPARATOR.$md5FileName.'.png';
				$resize_file_name = $md5FileName.'.png';
				$media_type = 'png';
				//header('Content-Type:image/png');
				$image_wp=imagecreatetruecolor($new_width, $new_height);
				$image = imagecreatefrompng($imgsrc);
				imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				
				//判断文件是否存在
				if(file_exists($imgdst)){
				    $resource = true;
					break;
				}
				
				//75代表的是质量、压缩图片容量大小
				$resource = imagepng($image_wp, $imgdst);
				imagedestroy($image_wp);
				break;
			default:
				$imgdst = '';
				$resource = '';
				break;
		}
		
		if(!empty($imgdst) && !empty($resize_file_name) && !empty($resource)){
			self::setExportResizeImgPathToDb($imgsrc, $resize_file_name, $media_type, $maxWidth.'x'.$maxHeight);
		}
		
		return ['file_name'=>$imgdst,'resource'=>$resource,'type'=>$media_type];
	}
	
	/**
	* desription 判断是否gif动画
	* @param sting $image_file图片路径
	* @return boolean t 是 f 否
	*/
	private static function check_gifcartoon($image_file){
	    $fp = fopen($image_file,'rb');
	    $image_head = fread($fp,1024);
	    fclose($fp);
	    return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;
	}
  
	/**
	 * 将指定数值转换成Excel列名 ( 数字转字母 ,类似于Excel列标)
     * @param 	Int 	$index 索引值
     * @param 	Int 	$start 字母起始值(默认值65，表示chr从‘A’开始)
     * @return 	String 	返回字母
     * @author 	lzhl 	2016-12-05
     */
    private static function ColumnIndexToChr($index, $start = 65) {
        $str = '';
        if (floor($index / 26) > 0) {
            $str .= self::ColumnIndexToChr(floor($index / 26)-1);
        }
        return $str . chr($index % 26 + $start);
    }
    
    /**
     * 获取 img 保存路径
     * @param	$is_create_dir 是否创建日期目录
     * @return string
     */
    public static function createExcelImgDir($is_create_dir = true){
    	if($is_create_dir){
    		$basepath = self::getImgPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'excel_img';
    		//根据年月生成目录，用于以后方便管理删除文件
    		$dataDir = date("Ym");
    
    		if(!file_exists($basepath.DIRECTORY_SEPARATOR.$dataDir)){
    			mkdir($basepath.DIRECTORY_SEPARATOR.$dataDir);
    			chmod($basepath.DIRECTORY_SEPARATOR.$dataDir,0777);
    		}
    		return $basepath.DIRECTORY_SEPARATOR.$dataDir;
    	}else{
    		$basepath = self::getImgPathString();
    		return $basepath;
    	}
    }
    
    /**
     * 获取TcPdf保存的路径
     * @return string
     */
    public static function getImgPathString(){
    	return \Yii::getAlias('@eagle/web');
    }
    
    /**
     * 获取web保存的路径
     * @return string
     */
    public static function getWebPathString(){
    	return \Yii::getAlias('@eagle/web');
    }
    
    /**
     * 读表获得最近一次resize的文件记录
     * @param 	string	$imgsrc	原始图片url
     * @param 	string	$size	resize尺寸，like '100x100'
     * @return 	string	'日期文件夹/文件名' or ''
     */
    public static function getExportResizeImgPathFromDb($imgsrc,$size){
    	if(!empty(self::$excel_resize_img_file_mapping_md5[md5($imgsrc)]))
    		return self::$excel_resize_img_file_mapping_md5[md5($imgsrc)];
    	
    	$command = \Yii::$app->db_queue2->createCommand("select * from export_excel_resize_img where src_img_md5='".md5($imgsrc)."' and resize='".$size."' order by id desc" );
    	$record = $command->queryOne();
    	if(!empty($record['resize_file_name']) && !empty($record['date_ym'])){
    		$file_path = $record['date_ym'].DIRECTORY_SEPARATOR.$record['resize_file_name'];
    		self::$excel_resize_img_file_mapping_md5[md5($imgsrc)] = $file_path;
    		return ['file_path'=>$file_path,'type'=>$record['type']];
    	}else
    		return '';
    }
    
    /**
     * 设置最近一次resize的文件路径记录，如果当月已经有记录，先删除在新建(可能由于文件被删除)
     * @param string	$imgsrc				原始图片url
     * @param string	$resize_file_name	resize之后的文件名
     * @param string	$type				图片类型
     * @param string	$size				resize尺寸，like '100x100'
     */
    public static function  setExportResizeImgPathToDb($imgsrc,$resize_file_name, $type, $size){
    	$date_ym = date("Ym");
    	//删除旧记录
    	$command = \Yii::$app->db_queue2->createCommand("
    			DELETE FROM `export_excel_resize_img` WHERE
    			`src_img_md5`=:md5 and `src_img_url`=:imgsrc and `resize`=:size and `type`=:type and `date_ym`=$date_ym
    			" );
    	$command->bindValue(":md5",md5($imgsrc),\PDO::PARAM_STR);
    	$command->bindValue(":imgsrc",$imgsrc,\PDO::PARAM_STR);
    	$command->bindValue(":size",$size,\PDO::PARAM_STR);
    	$command->bindValue(":type",$type,\PDO::PARAM_STR);
    	$record = $command->execute();
    	
    	//写入新记录
    	$command = \Yii::$app->db_queue2->createCommand("
    			INSERT INTO `export_excel_resize_img` 
    			(`src_img_md5`, `src_img_url`, `resize`, `resize_file_name`, `type`, `date_ym`) 
    			VALUES
    			(:md5,:imgsrc,:size,:resize_file_name,:type,$date_ym)
    			" );
    	$command->bindValue(":md5",md5($imgsrc),\PDO::PARAM_STR);
    	$command->bindValue(":imgsrc",$imgsrc,\PDO::PARAM_STR);
    	$command->bindValue(":size",$size,\PDO::PARAM_STR);
    	$command->bindValue(":resize_file_name",$resize_file_name,\PDO::PARAM_STR);
    	$command->bindValue(":type",$type,\PDO::PARAM_STR);
    	$record = $command->execute();
    	
    	self::$excel_resize_img_file_mapping_md5[md5($imgsrc)] = $date_ym.DIRECTORY_SEPARATOR.$resize_file_name;
    }
    
    /**
     * 检查url是否能够快速响应
     * @param 	string	$url				原始图片url
     * @return 	boolean
     */
	public static function checkRemoteFile($url){
		$hostCanNotConnect = self::$RemoteFilehHostCanNotConnect;
		//var_dump($hostCanNotConnect);
		$tempUrl=parse_url($url);
		//var_dump($tempUrl);
		
		$urlHost=$tempUrl['host'];
		//var_dump($urlHost);
		if(in_array($urlHost,$hostCanNotConnect)){
			//echo "<br>hostCanNotConnect<br>";
			return false;
		}
		$timeout = 3; //timeout seconds

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// don't download content
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
		
		$canConnect = ((curl_exec($ch)!==FALSE))?true:false;
		if($canConnect==false){
			self::$RemoteFilehHostCanNotConnect[] = $urlHost;
			//var_dump(self::$RemoteFilehHostCanNotConnect);
		}
		//return (curl_exec($ch)!==FALSE);
		return $canConnect;
	}
	
	/**
	 * 获取 Excel 保存路径
	 * @param	$is_create_dir 是否创建日期目录
	 * @return string
	 */
	public static function createExcelDir($is_create_dir = true){
		if($is_create_dir){
			$basepath = self::getWebPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'tmp_export_file';
			//根据年月生成目录，用于以后方便管理删除文件
			$dataDir = date("Ym");
	
			if(!file_exists($basepath.DIRECTORY_SEPARATOR.$dataDir)){
				mkdir($basepath.DIRECTORY_SEPARATOR.$dataDir);
				chmod($basepath.DIRECTORY_SEPARATOR.$dataDir,0777);
			}
			
			return ['path' => $basepath.DIRECTORY_SEPARATOR.$dataDir, 
			       'urlpath' => DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'tmp_export_file'.DIRECTORY_SEPARATOR.$dataDir];
		}else{
			$basepath = self::getWebPathString();
			return ['path' => $basepath, 'urlpath' => ''];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 执行导出队列，导出Excel
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public static function queueExportExcel($use_module){
	    $rtn['message'] = "";
	    $rtn['success'] = true;
	    
        $stime = date('Y-m-d H:i:s');
		
		$condition = '';
		if($use_module != '')
		    $condition = " and use_module='$use_module'";
		$sql="SELECT * FROM `export_excel_queue` WHERE `status` = 'S' ".$condition." and next_time<".time()." ORDER BY uid,`create_time` ASC LIMIT 0 , 5";//一次计算最多5个用户，避免卡太久
		$command = \Yii::$app->get('db_queue')->createCommand($sql);
		$pendingExportQueue = $command->queryAll();
		
		if(!empty($pendingExportQueue)){
    		foreach ($pendingExportQueue as $pendingExport){
    		    $stime = date('Y-m-d H:i:s');
    		    $pending_id = $pendingExport['id'];
    		    $uid = $pendingExport['uid'];
    		    $name = $pendingExport['name'];
    		    $className = $pendingExport['export_class'];
    		    $functionName = $pendingExport['export_function'];
    		    $data = $pendingExport['param_value'];
    		    $data = $data == base64_encode(base64_decode($data)) ? base64_decode($data) : $data;
    		    $data = json_decode($data, true);

    		    $r_sql = "UPDATE `export_excel_queue` SET `status`='E',`update_time`=NULL,`message`='',`src_file_url`=''  WHERE `id` = $pending_id ";
    		    $command = \Yii::$app->get('db_queue')->createCommand($r_sql);
    		    $command->execute();
    		    
    		    echo "\n start uid :".$uid." $name...";
    		    
    		    try{
    		    	 
    		    	$renpost = $className::$functionName($data,false,$uid);
    		    	
    		    } catch (\Exception $e) {
    		    	$rtn['success'] = false;
    		    	$rtn['message'] = $e->getMessage();
    		    }
    		    
    		    if($renpost['success'] == 0){
    		    	echo "\n cronCronExportExcel Exception:".$renpost['message'];
    		    	$renpost['src_file_url'] = '';
    		    	$rtn['message'] = $renpost['message'];
    		    	$rtn['success'] = false;
    		    }
    		    else{
    		        echo "\n start uid :".$uid." $name success";
    		        $renpost['message'] = '';
    		        
    		        if(empty($renpost['src_file_url'])){
    		            $renpost['src_file_url'] = '';
    		            $renpost['message'] = '导出失败：路劲丢失！';
    		        }
    		    }
    		    
    		    $count = empty($renpost['count']) ? 0 : $renpost['count'];
    		    	
    		    $update_time = date('Y-m-d H:i:s', time());
    		    $dis = strtotime($update_time) - strtotime($stime);
    		    $r_sql = "UPDATE `export_excel_queue` SET `status`='E',`update_time`='$update_time',`message`=:errMsg,`src_file_url`=:src_file_url,`export_count`=:export_count,`taking_time`=:taking_time  WHERE `id` = $pending_id ";
    		    $command = \Yii::$app->get('db_queue')->createCommand($r_sql);
    		    $command->bindValue(":errMsg",$renpost['message'],\PDO::PARAM_STR);
    		    $command->bindValue(":src_file_url",$renpost['src_file_url'],\PDO::PARAM_STR);
    		    $command->bindValue(":export_count",$count,\PDO::PARAM_STR);
    		    $command->bindValue(":taking_time",$dis,\PDO::PARAM_STR);
    		    $command->execute();
    		}
		}
		else{
		    $rtn['message'] = "n/a";
		    $rtn['success'] = true;
		}
		
		return $rtn;
	}
	
	/**
	 +----------------------------------------------------------
	 * 插入导出Excel队列
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public static function insertExportCrol($className, $functionName, $data, $exportName){
		$rtn['success'] = 1;
		$rtn['pending_id'] = 0;
	
		$uid=\Yii::$app->user->id;
		if ($uid==""){
		    $rtn['success'] = 0;
		    $rtn['message'] = "登录信息丢失";
		}
	    else{
	        //print_r($uid);die;
	        try{
        		//插入导出队列
        		$create_time = date('Y-m-d H:i:s', time());
        		$queue_sql = "select * from `export_excel_queue` where `uid` = $uid and name='$exportName'";
        		$queue = Yii::$app->get('db_queue')->createCommand($queue_sql)->queryOne();
        		if(empty($queue)){
        			$sql = "INSERT INTO `export_excel_queue`
        			( `uid`, `name`,`status`, `create_time`, `update_time`, `export_class`, `export_function`, `param_value`, `use_module`) VALUES
        			( $uid,'$exportName','S','$create_time',NULL, '$className', '$functionName', '$data', 'I')";
        			 
        			Yii::$app->get('db_queue')->createCommand($sql)->execute();
        			 
        			//取回id
        			$queue_sql = "select id from `export_excel_queue` where `uid` = $uid and name='$exportName'";
        			$queue = Yii::$app->get('db_queue')->createCommand($queue_sql)->queryOne();
        			$rtn['pending_id'] = $queue['id'];
        		}
        		else{
        			$rtn['pending_id'] = $queue['id'];
        	
        			if($queue['status'] != 'S'){
        				$sql = "UPDATE `export_excel_queue` SET
        				`status`='S', `create_time`='$create_time', `update_time`=NULL, `param_value`='$data', src_file_url=''
        				where `uid` = $uid and name='$exportName'";
        	
        				Yii::$app->get('db_queue')->createCommand($sql)->execute();
        			}
        		}
	        }
	        catch(\Exception $ex){
	            $rtn['success'] = 0;
	            $rtn['message'] = $ex->getMessage();
	        }
	    }
	
		return $rtn;
	}
	
	/**
	 +----------------------------------------------------------
	 * 查询已导出Excel的路劲
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public static function getExcelUrl($pending_id){
		$rtn['success'] = 1;
		$rtn['message'] = '';
		 
		$queue_sql = "select * from `export_excel_queue` where id=$pending_id";
		$queue = Yii::$app->get('db_queue')->createCommand($queue_sql)->queryOne();
		if(empty($queue)){
			$rtn['success'] = 0;
			$rtn['message'] = '导出信息已失效，请重新再导！';
		}
		else{
			if($queue['status'] == 'E'){
				if(empty($queue['message']) && $queue['src_file_url'] != ''){
				    //导出成功，返回地址
					$rtn['url'] = $queue['src_file_url'];
					$rtn['export_count'] = $queue['export_count'];
				}
				else if(empty($queue['message'])){
				    //导出还未完成
					$rtn['success'] = 2;
				}
				else{
				    //导出失败
					$rtn['success'] = 0;
					$rtn['message'] = $queue['message'];
				}
			}
			else{
			    //导出还未完成
				$rtn['success'] = 2;
			}
		}
		 
		return json_encode($rtn);
	}
	    
}