<?php 
namespace common\api\carrierAPI;

use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use Qiniu\json_decode;

class LB_139EXPRESSCarrierAPI extends BaseCarrierAPI
{
	static private $url = '';
	
	private $submitGate = null;
	public $token = null;       
	
	public function __construct(){
 		self::$url = 'http://openapi.139express.com/DocumentsTracking';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/08/28  			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data)
	{
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持申请订单号');	
	}
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/08/28                                           初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data)
	{		
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单');
	}
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/08/28                                           初始化  
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data)
	{
	    return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运');
	}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/08/28                                           初始化  
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data)
	{
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持获取跟踪号');
	}

	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/08/28                                         初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data)
	{
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打印面单');
	}

	/**
	 +----------------------------------------------------------
	 * 查询物流轨迹
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/08/28                                        初始化
	 +----------------------------------------------------------
	 **/
	public function SyncStatus($data)
	{
		$res = array();
	    
	    //设置key&token
		// TODO carrier user account @XXX@
	    $this->token = '@XXX@';	    
	    $urls=self::$url."/index?accessTokenKey=".$this->token."&Waybillnumber=".$data[0]."&lang=cn";

	    //发送
	    $response = $this->submitGate->mainGate($urls, null, 'curl', 'get');
	    if($response['error'] != 0){
	    	$res['error'] = "1";
	    	$res['trackContent'] = $response['msg'];
	    	$res['referenceNumber']=$data;
	    	return $res;
	    }

	    $response=json_decode($response['data'],true);
	    if(empty($response)){
	    	$res['error'] = "1";
	    	$res['trackContent'] = "没有查询到相关数据";
	    	$res['referenceNumber'] = $data;
	    	return $res;
	    }

	    if ($response['Code'] != 0) {
	        $res['error'] = "1";
	        $res['trackContent'] = $response['Message'];
	        $res['referenceNumber']=$data;
	    }else{
	        if($response['Code'] == 0&&!empty($response['Data'])){
	            $response_array = $response['Data'];
	            $res['error']="0";
	            $res['referenceNumber'] = $response_array['Number'];
	            $res['destinationCountryCode'] = $response_array['accept_Country'];
	            $res['trackingNumber'] = $response_array['Number'];
	            $res['trackContent']=[];
	            if(!empty($response_array['Details'])){//判断是否有轨迹数据
	                foreach($response_array['Details'] as $i=>$t){
	                    $res['trackContent'][$i]['createDate'] = '';
	                    $res['trackContent'][$i]['createPerson'] = '';
	                    $res['trackContent'][$i]['occurAddress'] = empty($t['Loaction'])?'':$t['Loaction'];
	                    $res['trackContent'][$i]['occurDate'] = empty($t['DateTime'])?'':$t['DateTime'];
	                    $res['trackContent'][$i]['trackCode'] = '';
	                    $res['trackContent'][$i]['trackContent'] = empty($t['Description'])?'':$t['Description'];
	                }
	                $res['trackContent'] = array_reverse($res['trackContent']);//时间倒序
	            }
	
	        }else{
	            $res['error'] = "1";
	            $res['trackContent'] = "没有查询到相关数据";
	            $res['referenceNumber'] = $data;
	        }
	
	
	    }
	    return $res;
	}
	
	
	/**
	 * 用于验证物流账号信息是否真实
	 * $data 用于记录所需要的认证信息
	 *
	 * return array(is_support,error,msg)
	 * 			is_support:表示该货代是否支持账号验证  1表示支持验证，0表示不支持验证
	 * 			error:表示验证是否成功	1表示失败，0表示验证成功
	 * 			msg:成功或错误详细信息
	 */
	public function getVerifyCarrierAccountInformation($data)
	{
		$result = array('is_support'=>0,'error'=>1);
	
// 		try
// 		{
// 		    //获取token
// 		    $url = self::$url.'/rest/v1/OAuth/AccessToken?';
// 		    $url_params = 'clientId='.$data['clientId'].'&password='.$data['password'].'&returnFormat=json ';
// 		    $response = $this->submitGate->mainGate($url, $url_params, 'curl', 'GET');
		    
// 		    if($response['error']){
// 		        return $result;
// 		    }
// 		    else {
//     		    $token_dhl = json_decode($response['data'],true);
//     		    if(!empty($token_dhl['accessTokenResponse']['token'])){
//     		    	$result['error'] = 0;
//     		    }
// 		    }
// 		}
// 		catch(CarrierException $e){}
	
		return $result;
	}
}

 ?>