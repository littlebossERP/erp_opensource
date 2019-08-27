<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>

<?php if ($carrierObj->carrier_type==1){?>
<dl>
 	<dt><?=TranslateHelper::t('仓库') ?></dt>
 	<dd>
 	<?=Html::checkboxList('warehouse',$carrierAccountObj->warehouse,$warehouse)?>
    </dd>
</dl>
<?php }?>
<?php if (count($params)>0){?>

<dl>
<?php
foreach ($params as $param):?>
 				<dt><?=TranslateHelper::t($param['carrier_param_name']) ?></dt>
 				<dd>
                <?php 
                if ($param['display_type'] == 'text'){
                    echo Html::input('text','carrier_params['.$param['carrier_param_key'].']',isset($carrierAccountObj->api_params[$param['carrier_param_key']])?$carrierAccountObj->api_params[$param['carrier_param_key']]:'',['class'=>'eagle-form-control','style'=>"width:450px;",'placeholder'=>TranslateHelper::t($param['carrier_param_name'])]);
                    //start 认证参数解释
                    foreach($qtipKeyArr as $qtipKey) {//判断是否存在相应解释在数据库
                        if($qtipKey['tip_key']==($param['carrier_code'].'-'.$param['carrier_param_key'])){
                            $existData = '<span id="'.$param['carrier_code'].'-'.$param['carrier_param_key'].'" qtipkey="'.$param['carrier_code'].'-'.$param['carrier_param_key'].'"></span>';
                            break;
                        }
                        else{
                            $noneData = '<span id="'.$param['carrier_code'].'-'.$param['carrier_param_key'].'" qtipkey="none_carrierParameter_explain"></span>';
                            continue;
                        }
                    }
                    if(!empty($existData)){
                        echo $existData;
                        $existData = null;
                    }
                    else{
                        echo $noneData;
                    }
                    //end 认证参数解释
                }elseif ($param['display_type'] == 'radio'){
                    echo Html::radioList('carrier_params['.$param['carrier_param_key'].']',isset($carrierAccountObj->api_params[$param['carrier_param_key']])?$carrierAccountObj->api_params[$param['carrier_param_key']]:'',['class'=>'eagle-form-control']);
                }elseif ($param['display_type'] == 'checkbox'){
                    echo Html::checkboxList('carrier_params['.$param['carrier_param_key'].']',isset($carrierAccountObj->api_params[$param['carrier_param_key']])?$carrierAccountObj->api_params[$param['carrier_param_key']]:'',['class'=>'eagle-form-control']);
                }elseif ($param['display_type'] == 'dropdownlist'){
//                     echo Html::dropDownList('carrier_params['.$param['carrier_param_key'].']',isset($carrierAccountObj->api_params[$param['carrier_param_key']])?$carrierAccountObj->api_params[$param['carrier_param_key']]:'',['class'=>'eagle-form-control']);
					$param['carrier_param_value']=unserialize($param['carrier_param_value']);//反序列化
					echo Html::dropDownList('carrier_params['.$param['carrier_param_key'].']',@$carrierAccountObj->api_params[$param['carrier_param_key']],$param['carrier_param_value'],['prompt'=>$param['carrier_param_name'],'style'=>'width:150px;','class'=>'eagle-form-control']);
                }?>
                </dd>
<?php  endforeach;?>
	</dl>
<?php }?>
<?php
$tmpCarrierCode = @$carrierObj->carrier_code;

//对接软通宝所属物流
if(@$carrierObj->api_class == 'LB_RTBCOMPANYCarrierAPI'){
	$tmpCarrierCode = 'lb_rtbcompany';
}

switch ($tmpCarrierCode){
case 'lb_IEUB' :
case 'lb_IEUBNew' :
    echo $this->render('lbieub', [
            'country'=>$country,
            'address_list'=>$address_list,
            'carrierObj'=>$carrierObj,
            'carrierAccountObj'=>$carrierAccountObj
        ]);
    break;
case 'lb_epacket' : 
	echo $this->render('lbepacket',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
	break;

    case 'lb_ebaytnt':
        echo $this->render('lbebaytnt',[
            'country'=>$country,
            'address_list'=>$address_list,
            'carrierObj'=>$carrierObj,
            'carrierAccountObj'=>$carrierAccountObj,
        ]);
        break;
case 'lb_BPOST' :
	echo $this->render('lbbpost',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
	break;
case 'lb_FEDEX' :
	echo $this->render('lbfedex',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
	break;
case 'lb_tnt' :
	echo $this->render('lbtnt',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
break;
case 'lb_TNT' :
	echo $this->render('lbtnt',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
break;
case 'lb_4px' : 
	echo $this->render('lb4px',[
    			'country'=>$country,
    			'address_list'=>$address_list,
    			'carrierObj'=>$carrierObj,
    			'carrierAccountObj'=>$carrierAccountObj,
    	]);
break;
case 'lb_haoyuan' :
	echo $this->render('lbhaoyuan',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
break;
case 'lb_alionlinedelivery' :
	echo $this->render('lbalionlinedelivery',[
	'country'=>$country,
	'address_list'=>$address_list,
	'carrierObj'=>$carrierObj,
	'carrierAccountObj'=>$carrierAccountObj,
	]);
break;
case 'lb_rtbcompany' :
	echo $this->render('lbrtbcompany',[
	'country'=>$country,
	'address_list'=>$address_list,
	'carrierObj'=>$carrierObj,
	'carrierAccountObj'=>$carrierAccountObj,
	]);
	break;
		
default : 
	echo $this->render('default',[
			'country'=>$country,
			'address_list'=>$address_list,
			'carrierObj'=>$carrierObj,
			'carrierAccountObj'=>$carrierAccountObj,
			]);
break;
}
?>

<!--认证参数Qtip解释-->
<script>
    $.initQtip();
</script>