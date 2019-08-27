<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use yii\helpers\Html;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\carrier\models\SysShippingService;
use common\helpers\Helper_Array;
// $this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/submitOrder.js", [ 
// 		'depends' => [ 
// 				'yii\web\JqueryAsset' 
// 		] 
// ] );
?>
<?php $s = SysShippingService::findOne($orderObj->default_shipping_method_code);?>
<?php $declarationInfo = CarrierApiHelper::getDeclarationInfo($orderObj,$s);?>
<input type="hidden" name="id" value="<?= $orderObj->order_id;?>">

<input type="hidden" name="total" value="<?= $declarationInfo['total'] ?>">
<input type="hidden" name="currency" value="<?= $declarationInfo['currency'] ?>">
<input type="hidden" name="total_price" value="<?= $declarationInfo['total_price'] ?>">
<input type="hidden" name="total_weight" value="<?= $declarationInfo['total_weight'] ?>">

<dl class="getOrderNo_row1">
	<dt class="getOrderNo_ul1">
		<div class="form-group">
		<?php
		//下面如果物流商有特殊的参数需要填写，则可以在下面单独的添加
		//万邑通
		if($orderObj->default_carrier_code == 'lb_winit'){
			$shippingService = SysShippingService::find()->select(['carrier_params','shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
			if(!empty($shippingService)){
				//如果是自发快递，需要选择仓库code
				$winit = new common\api\carrierAPI\LB_WANYITONGCarrierAPI;
				$warehouse = $winit->getWareHouseList($shippingService->shipping_method_code,$shippingService->carrier_account_id);
				if($shippingService['carrier_params']['dispatchType'] === 'S' && count($warehouse)>0){
					//调用接口查询验货仓
					$warehouseCodeList = Helper_Array::toHashMap($warehouse['data'],'warehouseCode','warehouseName');
					echo '<label>验货仓 </label>'.Html::dropDownList('warehouseCode','',$warehouseCodeList,['class'=>'eagle-form-control']);
				}
			}
		}
		if($orderObj->default_carrier_code == 'lb_winitOversea'){
			//根据接口获取保险
			$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
 			$data = [
 				'deliveryWayID'=>$shippingService->shipping_method_code,
 				'accountid'=>$shippingService->carrier_account_id
 			];
 			$winit = new \common\api\overseaWarehouseAPI\LB_WANYITONGOverseaWarehouseAPI;
 			$return = $winit->getInsuranceType($data);
 			$arr = [];
 			if(count($return)>0){
				if(isset($return[0]['insuranceID'])){
					foreach($return as $v){
						$arr[$v['insuranceID']] = $v['insuranceType'];
					}
				}
 			}
 			 if($arr){echo '<label>保险类型 </label>'.Html::dropDownList('insuranceTypeID','',$arr,['prompt'=>'']);}else{echo Html::dropDownList('insuranceTypeID','',['1000000'=>'No Insurance'],['prompt'=>'']);}
		}

		foreach($order_params as $v):
			$field = $v->data_key;
			$data = isset($orderObj->$field)?$orderObj->$field:'';
			
			if(($orderObj->default_carrier_code == 'lb_4px') && ($v->carrier_param_key == 'total_weight')){
				$data = 0;
				foreach($declarationInfo['products'] as $product){
					$data += $product['total_weight'];
				}
			}
			
			if(($orderObj->default_carrier_code == 'lb_winit') && ($v->carrier_param_key == 'weight')){
				$data = 0;
				foreach($declarationInfo['products'] as $product){
					$data += $product['total_weight'];
				}
			}
			
			if(($orderObj->default_carrier_code == 'lb_wishyou') && ($v->carrier_param_key == 'user_desc')){
				$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

				$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();

				if(!empty($accountInfo->api_params['user_desc_mode'])){
					if($accountInfo->api_params['user_desc_mode'] == 'sku'){
							$data = '';
							foreach($declarationInfo['products'] as $product){
								$data .= $product['sku'].'*'.$product['quantity'].';';
							}
							
							$data=substr($data,0,-1);
					}
				}
			}

			if(($orderObj->default_carrier_code == 'lb_anjun') && ($v->carrier_param_key == 'pickingInfo')){
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();

                if(!empty($accountInfo->api_params['pickingInfo_mode'])){
                    if($accountInfo->api_params['pickingInfo_mode'] == 'sku'){
                        $data = '';
                        foreach($declarationInfo['products'] as $product){
                            $data .= $product['sku'].'*'.$product['quantity'].';';
                        }

                        $data=substr($data,0,-1);
                    }
                    if($accountInfo->api_params['pickingInfo_mode'] == 'orderid')
                    {
                        $data = $orderObj->order_id;
                    }

                }
            }


            if(($orderObj->default_carrier_code == 'lb_esutong') && ($v->carrier_param_key == 'InsurValue')){
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id','carrier_params'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                if(!empty($shippingService->carrier_params['InsurType'])){
                    if($shippingService->carrier_params['InsurType'] == 'N'){
                        continue;
                    }
                }else{
                    continue;
                }
            }

		?>
			<label>
				<?= $v->carrier_param_name ?>
				<?= $v->is_required==1?'<span class="star">*</span>':''; ?> 
			</label>
			<?php
				if ($v->display_type == 'text'){
					echo Html::input('text',$v->carrier_param_key,$data,['style'=>$v->input_style,'class'=>'eagle-form-control']);
				}elseif ($v->display_type == 'dropdownlist'){
					echo Html::dropDownList($v->carrier_param_key,$data,$v->carrier_param_value,['style'=>'width:150px;','class'=>'eagle-form-control']);
				}
			 ?>
		</div>
		<?php 
		endforeach;
		/* if($orderObj->carrier_step==\eagle\modules\order\models\OdOrder::CARRIER_CANCELED){
			echo '<label qtipkey="carrier_extra_id">强制发货<span class="star">*</span></label>添加标识码(1-9)<input type="text"  name="extra_id" style="width:50px;">';
		} */
$customerNumber = \eagle\modules\carrier\helpers\CarrierAPIHelper::getCustomerNum2($orderObj);
echo '<label qtipkey="customerNumber">客户参考号<span class="star">*</span></label><input type="text"  name="customer_number" style="width:200px;" value ='.$customerNumber.'>';
if($orderObj->carrier_step==\eagle\modules\order\models\OdOrder::CARRIER_CANCELED){
	echo '<span qtipkey="" style="color:red;">重新上传</span>';
}
		?>
		<div class="cleardiv"></div>
<?php foreach($declarationInfo['products'] as $product){?>
		<dd>
		<h5 class='text-success'>商品名：<?=$product['name'] ?></h5>
		<?php
		foreach($item_params as $v):
			$field = $v->data_key;
			$data = isset($product[$field])?$product[$field]:'';

             if(($orderObj->default_carrier_code == 'lb_hulianyi') && ($v->carrier_param_key == 'productMemo')){
                    $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                    $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();

                    if(!empty($accountInfo->api_params['user_productMemo_mode'])){
                        if($accountInfo->api_params['user_productMemo_mode'] == 'sku'){
                            $data = $product['sku'].'*'.$product['quantity'];
                        }
                        if($accountInfo->api_params['user_productMemo_mode'] == 'orderid'){
                            if(empty($order_id)) {
                                $order_id = $orderObj->order_id;
                                $data = $order_id;
                            }

                        }

                    }
             }

             if(($orderObj->default_carrier_code == 'lb_CNE') && ($v->carrier_param_key == 'EName')){
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();

                if(!empty($accountInfo->api_params['user_productMemo_mode'])){
                    if($accountInfo->api_params['user_productMemo_mode'] == 'sku'){
                        $data = $product['declaration_en'].' '.$product['sku'].'*'.$product['quantity'];
                    }
                    if($accountInfo->api_params['user_productMemo_mode'] == 'orderid'){
                        if(empty($order_id)) {
                            $order_id = $orderObj->order_id;
                            $data = $product['declaration_en'].' '.$order_id;
                        }

                    }

                }
            }

            if(($orderObj->default_carrier_code == 'lb_feite') && ($v->carrier_param_key == 'ItemCode'))
            {
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
                if(!empty($shippingService)){
	                $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
					
	                
	                if(!empty($accountInfo->api_params['ItemCode_mode']))
	                {
	                    if($accountInfo->api_params['ItemCode_mode'] == 'title'){
	                        $data = $product['name'].'*'.$product['quantity'];
	                    }
	                }
                }
            }
            
            if(($orderObj->default_carrier_code == 'lb_yanwen') && ($v->carrier_param_key == 'EName')){
            	$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
            	if(!empty($shippingService)){
	            	$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
	            
	            	if(!empty($accountInfo->api_params['EName_mode'])){
	            		if($accountInfo->api_params['EName_mode'] == 'sku'){
	            			$data = $product['sku'].' '.$data;
	            		}
	            	}
            	}
            }
            
if((($orderObj->default_carrier_code == 'lb_IEUB') || ($orderObj->default_carrier_code == 'lb_IEUBNew')) && ($v->carrier_param_key == 'cnname')){
            	$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
            
            	$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
            
            	if(!empty($accountInfo->api_params['cnname_mode'])){
            		if($accountInfo->api_params['cnname_mode'] == 'sku'){
            			$data = $data.' '.$product['sku'];
            		}
            	}
            }


		?>
			<div class="form-group">
				<label>
					<?= $v->carrier_param_name ?>
					<?= $v->is_required==1?'<span class="star">*</span>':''; ?>
				</label>
				<?php
					$placeholder = $product == null ?'商品库无此SKU,请手动填写':'';
					if ($v->display_type == 'text'){
						echo Html::input('text',$v->carrier_param_key.'[]',$data,[
							'style'=>$v->input_style,
							'class'=>'eagle-form-control',
							'placeholder'=>$placeholder,
							'required'=>$v->is_required==1?'required':null
						]);
					}elseif ($v->display_type == 'dropdownlist'){
						echo Html::dropDownList($v->carrier_param_key.'[]',$data,$v->carrier_param_value,['prompt'=>$v->carrier_param_name,'style'=>'width:150px;','class'=>'eagle-form-control']);
					}
				 ?>
			</div>
			<?php endforeach; ?>
		</dd>
<?php }?>
</dl>