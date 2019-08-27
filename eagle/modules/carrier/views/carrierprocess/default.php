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

<style>
    .order-param-group , .prod-param-group{
        width: 280px;
        float: left;
        text-align: right;
        display: block;
        margin-right: 10px;
    }
</style>

<input type="hidden" name="id" value="<?= $orderObj->order_id;?>">

<input type="hidden" name="total" value="<?= $declarationInfo['total'] ?>">
<input type="hidden" name="currency" value="<?= $declarationInfo['currency'] ?>">
<input type="hidden" name="total_price" value="<?= $declarationInfo['total_price'] ?>">
<input type="hidden" name="total_weight" value="<?= $declarationInfo['total_weight'] ?>">



<div  style="width: 100%;">
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
// 			 if($arr){echo '<label>保险类型 </label>'.Html::dropDownList('insuranceTypeID','',$arr,['prompt'=>'']);}else{echo Html::dropDownList('insuranceTypeID','',['1000000'=>'No Insurance'],['prompt'=>'']);}


            ?>

                <div class=" order-param-group">
                    <div style="float: right">
        			    <?php echo Html::dropDownList('insuranceTypeID','',$arr?$arr:array('1000000'=>'No Insurance'),['prompt'=>'','style'=>'width:150px;','class'=>'eagle-form-control']);?>
                    </div>
                    <div style="width:120px; float: right;margin-top:9px; margin-right: 4px;">
                        <label>保险类型 </label>
                    </div>
                </div>





        <?php

        }
        if($orderObj->default_carrier_code == 'lb_santaic'){
        	$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id','carrier_params'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
        	$shipping_method_code=$shippingService->carrier_params;
			$isFba=$shipping_method_code['isFba'];
        	$arr = ['FBA'=>'FBA','other'=>'其它海外仓储'];
        	if($isFba==1){
        			?>
        			<div class="form-group order-param-group">
	                    <div style="float: right">
	        			    <?php echo Html::dropDownList('warehouseName','FBA',$arr?$arr:array(''=>'No Insurance'),['prompt'=>'','style'=>'width:150px;','class'=>'eagle-form-control']);?>
	                    </div>
	                    <div style="width:120px; float: right;margin-top:9px; margin-right: 4px;">
	                        <label>FBA订单 </label>
	                    </div>
                	</div>
        			<?php
        	}
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
			
			if(($orderObj->default_carrier_code == 'lb_aipaqi') && ($v->carrier_param_key == 'forecastWeight')){
				$data = 0;
				foreach($declarationInfo['products'] as $product){
					$data += $product['total_weight'];
				}
			}
			if(($orderObj->default_carrier_code == 'lb_badatong') && ($v->carrier_param_key == 'actualWeight')){
				$data = 0;
				foreach($declarationInfo['products'] as $product){
					$data += $product['total_weight'];//
				}
			}
			if(($orderObj->default_carrier_code == 'lb_badatong') && ($v->carrier_param_key == 'apItemTitle')){
				$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
				$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
				if(!empty($accountInfo->api_params['apItemTitle_server'])){
					if($accountInfo->api_params['apItemTitle_server'] == 'Y'){
						$data = '';
						foreach($declarationInfo['products'] as $product){
							if(!empty($product['prod_name_ch']))
								$data .= $product['prod_name_ch'].'*'.$product['quantity'].',';
						}
						$data=substr($data,0,-1);
					}
				}
			}
			if(($orderObj->default_carrier_code == 'lb_shenzhenyouzheng') && ($v->carrier_param_key == 'fWeight')){
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
			
			if(($orderObj->default_carrier_code == 'lb_wishyou') && ($v->carrier_param_key == 'from_country')){
				$data = 'china';
			}
				
			if(($orderObj->default_carrier_code == 'lb_wishyou') && ($v->carrier_param_key == 'trade_amount')){
				foreach($declarationInfo['products'] as $product){
					$data += $product['total_price'];
				}
			}

			if(($orderObj->default_carrier_code == 'lb_anjun') && ($v->carrier_param_key == 'pickingInfo')){
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id','carrier_params'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();

                $tmpAnjunpickingInfo_mode = '';
                if(!empty($shippingService->carrier_params['pickingInfo_mode_service'])){
					if($shippingService->carrier_params['pickingInfo_mode_service'] != 'ALL')
						$tmpAnjunpickingInfo_mode = $shippingService->carrier_params['pickingInfo_mode_service'];
				}
				
				if(empty($tmpAnjunpickingInfo_mode)){
					if(!empty($accountInfo->api_params['pickingInfo_mode'])){
						$tmpAnjunpickingInfo_mode = $accountInfo->api_params['pickingInfo_mode'];
					}
				}
                
                if(!empty($tmpAnjunpickingInfo_mode)){
                    if($tmpAnjunpickingInfo_mode == 'sku'){
                        $data = '';
                        foreach($declarationInfo['products'] as $product){
                            $data .= $product['sku'].'*'.$product['quantity'].';';
                        }

                        $data=substr($data,0,-1);
                    }else
                    if($tmpAnjunpickingInfo_mode == 'orderid')
                    {
                        $data = $orderObj->order_id;
                    }else
                    if($tmpAnjunpickingInfo_mode == 'sku_prod_name'){
                    	$data = '';
                    	foreach($declarationInfo['products'] as $product){
                    		$data .= $product['sku'].' '.$product['prod_name_ch'].'*'.$product['quantity'].';';
                    	}
                    
                    	$data=substr($data,0,-1);
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
            

            if(($orderObj->default_carrier_code == 'lb_winit') && ($v->carrier_param_key == 'width'||$v->carrier_param_key == 'length'||$v->carrier_param_key == 'height')){
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
            
                $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
            
                if(!empty($accountInfo->api_params['defaultSize'])){
                    if($accountInfo->api_params['defaultSize'] == 'Y'){
                        if($v->carrier_param_key == 'width'){
                            $data = $product['pro_width'];
                        }else if($v->carrier_param_key == 'length'){
                            $data = $product['pro_length'];
                        }else if($v->carrier_param_key == 'height'){
                            $data = $product['pro_height'];
                        }
                    }
                }else{//默认填值
                    if($v->carrier_param_key == 'width'){
                        $data = $product['pro_width'];
                    }else if($v->carrier_param_key == 'length'){
                        $data = $product['pro_length'];
                    }else if($v->carrier_param_key == 'height'){
                        $data = $product['pro_height'];
                    }
                }
            }
            
            if(($orderObj->default_carrier_code == 'lb_yiyunquanqiu')){
            	if($v->carrier_param_key == 'goods_description'){
            		$data = '';
            		foreach($declarationInfo['products'] as $product){
            			$data .= $product['sku'].'*'.$product['quantity'].';';
            		}
            		$data=substr($data,0,-1);
            	}else if($v->carrier_param_key == 'length'){
            		$data = '1';
            	}else if($v->carrier_param_key == 'width'){
            		$data = '1';
            	}else if($v->carrier_param_key == 'height'){
            		$data = '1';
            	}
            }

		?>
        <div class=" order-param-group">

            <div style="float: right">
			<?php
				if ($v->display_type == 'text'){
//					echo Html::input('text',$v->carrier_param_key,$data,['style'=>$v->input_style,'class'=>'eagle-form-control']);
                    echo Html::input('text',$v->carrier_param_key,$data,['style'=>'width:150px;','class'=>'eagle-form-control']);

                }elseif ($v->display_type == 'dropdownlist'){
					echo Html::dropDownList($v->carrier_param_key,$data,$v->carrier_param_value,['style'=>'width:150px;','class'=>'eagle-form-control']);
				}
			 ?>
            </div>
            <div style="width:120px; float: right;margin-top:9px; margin-right: 4px;">
                <label>
                    <?= $v->carrier_param_name ?>
                    <?= $v->is_required==1?'<span class="star" style="color: red;">*</span>':''; ?>
                </label>
                <?php
//                if(!empty($v->param_describe)) echo '<img style="cursor: pointer;" width="16" src="/images/questionMark.png" title="'.$v->param_describe.'">'
                ?>
                <?= empty($v->param_describe) ? '' : "<span class='carrier_qtip_".$v->id."'></span>"; ?>
            </div>
		</div>
		<?php 
		endforeach;
		//此处改为直接显示客户参考号
// 		if($orderObj->carrier_step==\eagle\modules\order\models\OdOrder::CARRIER_CANCELED){
// 			echo '<label qtipkey="carrier_extra_id">强制发货<span class="star">*</span></label>添加标识码(1-9)<input type="text"  name="extra_id" style="width:50px;">';
// 		}

$customerNumber = \eagle\modules\carrier\helpers\CarrierAPIHelper::getCustomerNum2($orderObj);
echo '<div class=" order-param-group" style="width: 350px;" >
        <div style="float: left;width: 120px;margin-top: 9px;margin-right: 10px;"><label qtipkey="carrier_customer_number">客户参考号<span class="star" style="color: red;">*</span></label></div>
        <div style="float: left;"><input type="text"  class="eagle-form-control" name="customer_number" style="width:150px;" value ='.$customerNumber.'>';
if(($orderObj->carrier_step==\eagle\modules\order\models\OdOrder::CARRIER_CANCELED) && (count($orderObj->trackinfos) > 0)){
	echo '<span qtipkey="carrier_order_upload_again" style="color:red; margin-left: 4px;">重新上传</span>';
}
echo '</div></div>';
?>
</div>
<hr style="margin-top:1px;margin-bottom:2px;clear: both;"/>
<?php foreach($declarationInfo['products'] as $product){?>
<h5 class='text-success' style="text-align:left;">商品名：<?=$product['name'] ?></h5>
<div  style="width: 100%;">
		<?php
		foreach($item_params as $v):
			$field = $v->data_key;
			$data = isset($product[$field])?$product[$field]:'';

             if(($orderObj->default_carrier_code == 'lb_hulianyi') && ($v->carrier_param_key == 'productMemo')){
                    $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                    if(!empty($shippingService)){
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
             }


            if(($orderObj->default_carrier_code == 'lb_SF') && ($v->carrier_param_key == 'diPickName')){
                $shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();

                if(!empty($shippingService)){
                    $accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();

                    if(!empty($accountInfo->api_params['user_diPickName_mode'])){
                        if($accountInfo->api_params['user_diPickName_mode'] == 'sku'){
                            $data = $product['sku'];
                        }
                        if($accountInfo->api_params['user_diPickName_mode'] == 'N'){
                            $data = '';
                        }
                        if($accountInfo->api_params['user_diPickName_mode'] == 'Name'){
                            $data = $product['prod_name_ch'];
                        }
                        if($accountInfo->api_params['user_diPickName_mode'] == 'skuNullName'){
                            $data = $product['sku'].' '.$product['prod_name_ch'];
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
	            	else if($accountInfo->api_params['cnname_mode'] == 'skuOrderid'){
						$data = $data.' '.$product['sku'].' '.$orderObj->order_id;
	            	}
            	}
            }
            
            if(($orderObj->default_carrier_code == 'lb_4px') && ($v->carrier_param_key == 'EName')){
            	$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
            
            	$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
            
            	if(!empty($accountInfo->api_params['cnname_mode'])){
            		if($accountInfo->api_params['cnname_mode'] == 'sku'){
            			$data = $data.' '.$product['sku'];
            		}else if($accountInfo->api_params['cnname_mode'] == 'order'){
            			$data = $data.' '.$orderObj->order_id;
            		}
            	}
            }
            
            if(($orderObj->default_carrier_code == 'lb_4px') && ($v->carrier_param_key == 'Name')){
            	$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
            
            	$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
            
            	if(!empty($accountInfo->api_params['zcnname_mode'])){
            		if($accountInfo->api_params['zcnname_mode'] == 'sku'){
            			$data = $data.' '.$product['sku'];
            		}
            	}
            }
            
            if(($orderObj->default_carrier_code == 'lb_epacket') && ($v->carrier_param_key == 'Name')){
            	$shippingService = SysShippingService::find()->select(['shipping_method_code','carrier_account_id'])->where(['id'=>$orderObj->default_shipping_method_code])->one();
            
            	$accountInfo = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$shippingService->carrier_account_id])->one();
            
            	if(!empty($accountInfo->api_params['cnname_mode'])){
            		if($accountInfo->api_params['cnname_mode'] == 'sku'){
            			$data = $data.' '.$product['sku'];
            		}
            	}
            }
            


		?>

			<div class=" prod-param-group">
                <div style="float: right" >
				<?php
					$placeholder = $product == null ?'商品库无此SKU,请手动填写':'';
					if ($v->display_type == 'text'){
						echo Html::input('text',$v->carrier_param_key.'[]',$data,[
//							'style'=>$v->input_style,
                            'style'=>'width:150px;',
							'class'=>'eagle-form-control',
							'placeholder'=>$placeholder,
							'required'=>$v->is_required==1?'required':null
						]);
					}elseif ($v->display_type == 'dropdownlist'){
						echo Html::dropDownList($v->carrier_param_key.'[]',$data,$v->carrier_param_value,['prompt'=>$v->carrier_param_name,'style'=>'width:150px;','class'=>'eagle-form-control']);
					}
				 ?>
                </div>
                <div  style="width:120px; float: right;margin-top:9px;margin-right:4px;">
                    <label>
                        <?= $v->carrier_param_name ?>
                        <?= $v->is_required==1?'<span class="star" style="color: red;">*</span>':''; ?>
                        <?php if(!empty($v->param_describe)) echo '<img style="cursor: pointer;" width="16" src="/images/questionMark.png" title="'.$v->param_describe.'">'?>
                    </label>
                </div>
			</div>
			<?php endforeach; ?>
</div>
<?php }?>
