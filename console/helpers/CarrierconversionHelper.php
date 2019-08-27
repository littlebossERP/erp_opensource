<?php
namespace console\helpers;

use \Exception;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysCarrier;
use common\helpers\Helper_Array;
use eagle\modules\carrier\models\CarrierUserAddress;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\inventory\models\WarehouseCoverNation;
use eagle\modules\inventory\models\WarehouseMatchingRule;

class CarrierconversionHelper{
	
	public static function carrierAddressConversion(){
		
		//原本的自定义物流是没有启用或关闭功能的,这里做数据转换时,直接开启了.
		$exesysCustomSql2 = 'update `sys_carrier_custom` set `is_used`=1 ';
		\yii::$app->subdb->createCommand($exesysCustomSql2)->execute();
		
		//物流匹配规则优先级
		$exeruleSql2 = 'update matching_rule a
						left join (
						select a.id,(@rowNum:=@rowNum+1) as rowNo
						from matching_rule a,(Select (@rowNum :=0)) b
						order by a.is_active desc,a.priority,a.transportation_service_id,a.rule_name
						) b on b.id=a.id
						set priority = b.rowNo';
		
		\yii::$app->subdb->createCommand($exeruleSql2)->execute();
		
		
		$exerecordSql2 = 'insert `carrier_use_record`(`carrier_code`,`is_active`,`create_time`,`update_time`,`is_del`,`carrier_type`)
						select `a`.`carrier_code`,1,min(`a`.`create_time`),min(`a`.`update_time`),0,max(`a`.`carrier_type`)
						from `sys_carrier_account` a
						left join `carrier_use_record` b on b.`carrier_code`=a.`carrier_code`
						where b.`carrier_code` is null
						group by `a`.`carrier_code`';
		
		\yii::$app->subdb->createCommand($exerecordSql2)->execute();
		
		
		$exeorderSql1 = 'UPDATE `od_order_v2` SET `delivery_status` = 6,`distribution_inventory_status` = 4  where `order_status` = 300 and `delivery_status` =3';
		\yii::$app->subdb->createCommand($exeorderSql1)->execute();
		
		$exeorderSql2 = 'UPDATE `od_order_v2` SET `delivery_status` = 3,`distribution_inventory_status` = 4  where `order_status` = 300 and `delivery_status` =2';
		\yii::$app->subdb->createCommand($exeorderSql2)->execute();
		
		$exeorderSql3 = 'UPDATE `od_order_v2` SET `delivery_status` = 3,`distribution_inventory_status` = 4  where `order_status` = 300 and `delivery_status` =1';
		\yii::$app->subdb->createCommand($exeorderSql3)->execute();
		
		$exeorderSql4 = 'UPDATE `od_order_v2` SET `distribution_inventory_status` = 2  where `order_status` = 300 and `delivery_status` =0';
		\yii::$app->subdb->createCommand($exeorderSql4)->execute();
		
		$exeorderSql5 = "update `od_order_v2` set `pay_order_type` = 'pending' where `order_source` = 'aliexpress' and `order_status` = 200";
		\yii::$app->subdb->createCommand($exeorderSql5)->execute();
		
		$exeorderSql6 = 'UPDATE `od_delivery` SET `picking_status`=2';
		\yii::$app->subdb->createCommand($exeorderSql6)->execute();
		
		$exeorderSql7 = "INSERT INTO `ut_config_data` (`path`, `value`) VALUES
			('print_picking_type', 'order_id'),
			('skurule', '{\"keyword_rule\":\"open\",\"keyword\":[],\"substring_rule\":\"open\",\"firstChar\":\"\",\"secondChar\":\"\",\"split_rule\":\"open\",\"firstKey\":\"sku\",\"quantityConnector\":\"*\",\"secondKey\":\"quantity\",\"skuConnector\":\"+\",\"sku_ago\":\"\",\"sku_later\":\"\"}'),
			('no_show_product_image', 'N'),
			('no_show_platform_order_id', 'Y')";
		\yii::$app->subdb->createCommand($exeorderSql7)->execute();
		
		
		//货代的Conversion start
		$sysCarrierArr = SysCarrier::find()->select(['carrier_code'])->where(" address_list <> '' ")->asArray()->all();
		$sysCarrierArr = Helper_Array::toHashmap($sysCarrierArr, 'carrier_code', 'carrier_code');
		$carrierAccountAll = SysCarrierAccount::find()->where(['in','carrier_code',$sysCarrierArr])->asArray()->all();
		
		foreach ($carrierAccountAll as &$carrierAccountOne){
			$carrierAccountOne['address'] = unserialize($carrierAccountOne['address']);
			
			$carrierUserAddress = new CarrierUserAddress();
			
			$carrierUserAddress->carrier_code = $carrierAccountOne['carrier_code'];
			$carrierUserAddress->type = 0;
			$carrierUserAddress->is_default = 0;
			$tmpAddress = array();
			
			if(in_array($carrierAccountOne['carrier_code'], array('lb_IEUB','lb_IEUBNew','lb_4px'))){
				if(isset($carrierAccountOne['address']['shippingfrom'])){
					$tmpAddress['shippingfrom'] = array(
							'contact' => empty($carrierAccountOne['address']['shippingfrom']['contact']) ? '' : $carrierAccountOne['address']['shippingfrom']['contact'],
							'contact_en' => '',
							'company' => empty($carrierAccountOne['address']['shippingfrom']['company']) ? '' : $carrierAccountOne['address']['shippingfrom']['company'],
							'company_en' => '',
							'phone' => empty($carrierAccountOne['address']['shippingfrom']['phone']) ? '' : $carrierAccountOne['address']['shippingfrom']['phone'],
							'mobile' => empty($carrierAccountOne['address']['shippingfrom']['mobile']) ? '' : $carrierAccountOne['address']['shippingfrom']['mobile'],
							'fax' => empty($carrierAccountOne['address']['shippingfrom']['fax']) ? '' : $carrierAccountOne['address']['shippingfrom']['fax'],
							'email' => empty($carrierAccountOne['address']['shippingfrom']['email']) ? '' : $carrierAccountOne['address']['shippingfrom']['email'],
							'country' => empty($carrierAccountOne['address']['shippingfrom']['country']) ? '' : $carrierAccountOne['address']['shippingfrom']['country'],
							'province' => empty($carrierAccountOne['address']['shippingfrom']['province']) ? '' : $carrierAccountOne['address']['shippingfrom']['province'],
							'province_en' => '',
							'city' => empty($carrierAccountOne['address']['shippingfrom']['city']) ? '' : $carrierAccountOne['address']['shippingfrom']['city'],
							'city_en' => '',
							'district' => empty($carrierAccountOne['address']['shippingfrom']['district']) ? '' : $carrierAccountOne['address']['shippingfrom']['district'],
							'district_en' => '',
							'postcode' => empty($carrierAccountOne['address']['shippingfrom']['postcode']) ? '' : $carrierAccountOne['address']['shippingfrom']['postcode'],
							'street' => empty($carrierAccountOne['address']['shippingfrom']['street']) ? '' : $carrierAccountOne['address']['shippingfrom']['street'],
							'street_en' => '',
					);
				}
			}else if(in_array($carrierAccountOne['carrier_code'], array('lb_epacket'))){
				if(isset($carrierAccountOne['address']['shippingfrom'])){
					$tmpAddress['shippingfrom'] = array(
							'contact' => '',
							'contact_en' => empty($carrierAccountOne['address']['shippingfrom']['contact']) ? '' : $carrierAccountOne['address']['shippingfrom']['contact'],
							'company' => '',
							'company_en' => empty($carrierAccountOne['address']['shippingfrom']['company']) ? '' : $carrierAccountOne['address']['shippingfrom']['company'],
							'phone' => empty($carrierAccountOne['address']['shippingfrom']['phone']) ? '' : $carrierAccountOne['address']['shippingfrom']['phone'],
							'mobile' => empty($carrierAccountOne['address']['shippingfrom']['mobile']) ? '' : $carrierAccountOne['address']['shippingfrom']['mobile'],
							'fax' => empty($carrierAccountOne['address']['shippingfrom']['fax']) ? '' : $carrierAccountOne['address']['shippingfrom']['fax'],
							'email' => empty($carrierAccountOne['address']['shippingfrom']['email']) ? '' : $carrierAccountOne['address']['shippingfrom']['email'],
							'country' => empty($carrierAccountOne['address']['shippingfrom']['country']) ? '' : $carrierAccountOne['address']['shippingfrom']['country'],
							'province' => '',
							'province_en' => empty($carrierAccountOne['address']['shippingfrom']['province']) ? '' : $carrierAccountOne['address']['shippingfrom']['province'],
							'city' => '',
							'city_en' => empty($carrierAccountOne['address']['shippingfrom']['city']) ? '' : $carrierAccountOne['address']['shippingfrom']['city'],
							'district' => '',
							'district_en' => empty($carrierAccountOne['address']['shippingfrom']['district']) ? '' : $carrierAccountOne['address']['shippingfrom']['district'],
							'postcode' => empty($carrierAccountOne['address']['shippingfrom']['postcode']) ? '' : $carrierAccountOne['address']['shippingfrom']['postcode'],
							'street' => '',
							'street_en' => empty($carrierAccountOne['address']['shippingfrom']['street']) ? '' : $carrierAccountOne['address']['shippingfrom']['street'],
					);
				}
			}else if(in_array($carrierAccountOne['carrier_code'], array('lb_ebaytnt','lb_alionlinedelivery'))){
				if(isset($carrierAccountOne['address']['shippingfrom_en'])){
					$tmpAddress['shippingfrom'] = array(
							'contact' => '',
							'contact_en' => empty($carrierAccountOne['address']['shippingfrom_en']['contact']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['contact'],
							'company' => '',
							'company_en' => empty($carrierAccountOne['address']['shippingfrom_en']['company']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['company'],
							'phone' => empty($carrierAccountOne['address']['shippingfrom_en']['phone']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['phone'],
							'mobile' => empty($carrierAccountOne['address']['shippingfrom_en']['mobile']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['mobile'],
							'fax' => empty($carrierAccountOne['address']['shippingfrom_en']['fax']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['fax'],
							'email' => empty($carrierAccountOne['address']['shippingfrom_en']['email']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['email'],
							'country' => empty($carrierAccountOne['address']['shippingfrom_en']['country']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['country'],
							'province' => '',
							'province_en' => empty($carrierAccountOne['address']['shippingfrom_en']['province']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['province'],
							'city' => '',
							'city_en' => empty($carrierAccountOne['address']['shippingfrom_en']['city']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['city'],
							'district' => '',
							'district_en' => empty($carrierAccountOne['address']['shippingfrom_en']['district']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['district'],
							'postcode' => empty($carrierAccountOne['address']['shippingfrom_en']['postcode']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['postcode'],
							'street' => '',
							'street_en' => empty($carrierAccountOne['address']['shippingfrom_en']['street']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['street'],
					);
				}
			}else if((in_array($carrierAccountOne['carrier_code'], array('lb_haoyuan'))) || (substr($carrierAccountOne['carrier_code'],-10) == 'rtbcompany')){
				if(isset($carrierAccountOne['address']['shippingfrom'])){
					$tmpAddress['shippingfrom'] = array(
							'contact' => empty($carrierAccountOne['address']['shippingfrom']['contact']) ? '' : $carrierAccountOne['address']['shippingfrom']['contact'],
							'contact_en' => '',
							'company' => empty($carrierAccountOne['address']['shippingfrom']['company']) ? '' : $carrierAccountOne['address']['shippingfrom']['company'],
							'company_en' => '',
							'phone' => empty($carrierAccountOne['address']['shippingfrom']['phone']) ? '' : $carrierAccountOne['address']['shippingfrom']['phone'],
							'mobile' => empty($carrierAccountOne['address']['shippingfrom']['mobile']) ? '' : $carrierAccountOne['address']['shippingfrom']['mobile'],
							'fax' => empty($carrierAccountOne['address']['shippingfrom']['fax']) ? '' : $carrierAccountOne['address']['shippingfrom']['fax'],
							'email' => empty($carrierAccountOne['address']['shippingfrom']['email']) ? '' : $carrierAccountOne['address']['shippingfrom']['email'],
							'country' => empty($carrierAccountOne['address']['shippingfrom']['country']) ? '' : $carrierAccountOne['address']['shippingfrom']['country'],
							'province' => empty($carrierAccountOne['address']['shippingfrom']['province']) ? '' : $carrierAccountOne['address']['shippingfrom']['province'],
							'province_en' => '',
							'city' => empty($carrierAccountOne['address']['shippingfrom']['city']) ? '' : $carrierAccountOne['address']['shippingfrom']['city'],
							'city_en' => '',
							'district' => empty($carrierAccountOne['address']['shippingfrom']['district']) ? '' : $carrierAccountOne['address']['shippingfrom']['district'],
							'district_en' => '',
							'postcode' => empty($carrierAccountOne['address']['shippingfrom']['postcode']) ? '' : $carrierAccountOne['address']['shippingfrom']['postcode'],
							'street' => empty($carrierAccountOne['address']['shippingfrom']['street']) ? '' : $carrierAccountOne['address']['shippingfrom']['street'],
							'street_en' => '',
							'areacode' => empty($carrierAccountOne['address']['shippingfrom']['areacode']) ? '' : $carrierAccountOne['address']['shippingfrom']['areacode'],
					);
				}
			}else{
				if(isset($carrierAccountOne['address']['shippingfrom']) || isset($carrierAccountOne['address']['shippingfrom_en'])){
					$tmpphone_en = empty($carrierAccountOne['address']['shippingfrom_en']['phone']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['phone'];
					$tmpphone = empty($carrierAccountOne['address']['shippingfrom']['phone']) ? '' : $carrierAccountOne['address']['shippingfrom']['phone'];
					
					$tmpmobile_en = empty($carrierAccountOne['address']['shippingfrom_en']['mobile']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['mobile'];
					$tmpmobile = empty($carrierAccountOne['address']['shippingfrom']['mobile']) ? '' : $carrierAccountOne['address']['shippingfrom']['mobile'];
					
					$tmpfax_en = empty($carrierAccountOne['address']['shippingfrom_en']['fax']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['fax'];
					$tmpfax = empty($carrierAccountOne['address']['shippingfrom']['fax']) ? '' : $carrierAccountOne['address']['shippingfrom']['fax'];
					
					$tmppostcode_en = empty($carrierAccountOne['address']['shippingfrom_en']['postcode']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['postcode'];
					$tmppostcode = empty($carrierAccountOne['address']['shippingfrom']['postcode']) ? '' : $carrierAccountOne['address']['shippingfrom']['postcode'];
					
					$tmpemail_en = empty($carrierAccountOne['address']['shippingfrom_en']['email']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['email'];
					$tmpemail = empty($carrierAccountOne['address']['shippingfrom']['email']) ? '' : $carrierAccountOne['address']['shippingfrom']['email'];
					
					$tmpcountry_en = empty($carrierAccountOne['address']['shippingfrom_en']['country']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['country'];
					$tmpcountry = empty($carrierAccountOne['address']['shippingfrom']['country']) ? '' : $carrierAccountOne['address']['shippingfrom']['country'];
					
					
					$tmpAddress['shippingfrom'] = array(
						'contact' => empty($carrierAccountOne['address']['shippingfrom']['contact']) ? '' : $carrierAccountOne['address']['shippingfrom']['contact'],
						'contact_en' => empty($carrierAccountOne['address']['shippingfrom_en']['contact']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['contact'],
						'company' => empty($carrierAccountOne['address']['shippingfrom']['company']) ? '' : $carrierAccountOne['address']['shippingfrom']['company'],
						'company_en' => empty($carrierAccountOne['address']['shippingfrom_en']['company']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['company'],
						'phone' => empty($tmpphone_en) ? $tmpphone : $tmpphone_en,
						'mobile' => empty($tmpmobile_en) ? $tmpmobile : $tmpmobile_en,
						'fax' => empty($tmpfax_en) ? $tmpfax : $tmpfax_en,
						'email' => empty($tmpemail_en) ? $tmpemail : $tmpemail_en,
						'country' => empty($tmpcountry_en) ? $tmpcountry : $tmpcountry_en,
						'province' => empty($carrierAccountOne['address']['shippingfrom']['province']) ? '' : $carrierAccountOne['address']['shippingfrom']['province'],
						'province_en' => empty($carrierAccountOne['address']['shippingfrom_en']['province']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['province'],
						'city' => empty($carrierAccountOne['address']['shippingfrom']['city']) ? '' : $carrierAccountOne['address']['shippingfrom']['city'],
						'city_en' => empty($carrierAccountOne['address']['shippingfrom_en']['city']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['city'],
						'district' => empty($carrierAccountOne['address']['shippingfrom']['district']) ? '' : $carrierAccountOne['address']['shippingfrom']['district'],
						'district_en' => empty($carrierAccountOne['address']['shippingfrom_en']['district']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['district'],
						'postcode' => empty($tmppostcode_en) ? $tmppostcode : $tmppostcode_en,
						'street' => empty($carrierAccountOne['address']['shippingfrom']['street']) ? '' : $carrierAccountOne['address']['shippingfrom']['street'],
						'street_en' => empty($carrierAccountOne['address']['shippingfrom_en']['street']) ? '' : $carrierAccountOne['address']['shippingfrom_en']['street'],
					);
				}
			}
			
			if(isset($carrierAccountOne['address']['pickupaddress'])){
				$tmpAddress['pickupaddress'] = array(
						'contact' => empty($carrierAccountOne['address']['pickupaddress']['contact']) ? '' : $carrierAccountOne['address']['pickupaddress']['contact'],
						'company' => empty($carrierAccountOne['address']['pickupaddress']['company']) ? '' : $carrierAccountOne['address']['pickupaddress']['company'],
						'phone' => empty($carrierAccountOne['address']['pickupaddress']['phone']) ? '' : $carrierAccountOne['address']['pickupaddress']['phone'],
						'fax' => empty($carrierAccountOne['address']['pickupaddress']['fax']) ? '' : $carrierAccountOne['address']['pickupaddress']['fax'],
						'country' => empty($carrierAccountOne['address']['pickupaddress']['country']) ? '' : $carrierAccountOne['address']['pickupaddress']['country'],
						'province' => empty($carrierAccountOne['address']['pickupaddress']['province']) ? '' : $carrierAccountOne['address']['pickupaddress']['province'],
						'city' => empty($carrierAccountOne['address']['pickupaddress']['city']) ? '' : $carrierAccountOne['address']['pickupaddress']['city'],
						'district' => empty($carrierAccountOne['address']['pickupaddress']['district']) ? '' : $carrierAccountOne['address']['pickupaddress']['district'],
						'postcode' => empty($carrierAccountOne['address']['pickupaddress']['postcode']) ? '' : $carrierAccountOne['address']['pickupaddress']['postcode'],
						'street' => empty($carrierAccountOne['address']['pickupaddress']['street']) ? '' : $carrierAccountOne['address']['pickupaddress']['street'],
						'mobile' => empty($carrierAccountOne['address']['pickupaddress']['mobile']) ? '' : $carrierAccountOne['address']['pickupaddress']['mobile'],
						'email' => empty($carrierAccountOne['address']['pickupaddress']['email']) ? '' : $carrierAccountOne['address']['pickupaddress']['email'],
				);
			}
			
			if(isset($carrierAccountOne['address']['returnaddress'])){
				$tmpAddress['returnaddress'] = array(
						'contact' => empty($carrierAccountOne['address']['returnaddress']['contact']) ? '' : $carrierAccountOne['address']['returnaddress']['contact'],
						'company' => empty($carrierAccountOne['address']['returnaddress']['company']) ? '' : $carrierAccountOne['address']['returnaddress']['company'],
						'phone' => empty($carrierAccountOne['address']['returnaddress']['phone']) ? '' : $carrierAccountOne['address']['returnaddress']['phone'],
						'fax' => empty($carrierAccountOne['address']['returnaddress']['fax']) ? '' : $carrierAccountOne['address']['returnaddress']['fax'],
						'country' => empty($carrierAccountOne['address']['returnaddress']['country']) ? '' : $carrierAccountOne['address']['returnaddress']['country'],
						'province' => empty($carrierAccountOne['address']['returnaddress']['province']) ? '' : $carrierAccountOne['address']['returnaddress']['province'],
						'city' => empty($carrierAccountOne['address']['returnaddress']['city']) ? '' : $carrierAccountOne['address']['returnaddress']['city'],
						'district' => empty($carrierAccountOne['address']['returnaddress']['district']) ? '' : $carrierAccountOne['address']['returnaddress']['district'],
						'postcode' => empty($carrierAccountOne['address']['returnaddress']['postcode']) ? '' : $carrierAccountOne['address']['returnaddress']['postcode'],
						'street' => empty($carrierAccountOne['address']['returnaddress']['street']) ? '' : $carrierAccountOne['address']['returnaddress']['street'],
						'mobile' => empty($carrierAccountOne['address']['returnaddress']['mobile']) ? '' : $carrierAccountOne['address']['returnaddress']['mobile'],
						'email' => empty($carrierAccountOne['address']['returnaddress']['email']) ? '' : $carrierAccountOne['address']['returnaddress']['email'],
				);
			}
			
			$carrierUserAddress->address_params = $tmpAddress;
			
			if($carrierUserAddress->save(false)){
				$exe_shipping_service_Sql = 'update `sys_shipping_service` set `common_address_id`='.$carrierUserAddress->id.
					" where `carrier_account_id`=".$carrierAccountOne['id']." and `is_used`=1 ";
				\yii::$app->subdb->createCommand($exe_shipping_service_Sql)->execute();
			}
		}
		//货代的Conversion end
		
		//自定义物流的地址Conversion Start
		$shippingService = SysShippingService::find()->where(['is_custom'=>1])->all();
		
		foreach ($shippingService as $shippingServiceOne){
			$carrierUserAddress = new CarrierUserAddress();
			
			$carrierUserAddress->carrier_code = $shippingServiceOne['carrier_code'];
			$carrierUserAddress->type = 0;
			$carrierUserAddress->is_default = 0;
			$tmpAddress = array();
			
			$tmpAddress['shippingfrom'] = array();
			$tmpAddress['pickupaddress'] = array();
			$tmpAddress['returnaddress'] = array();
			
			if(isset($shippingServiceOne['address']['shippingfrom']) || isset($shippingServiceOne['address']['shippingfrom_en'])){
				$tmpphone_en = empty($shippingServiceOne['address']['shippingfrom_en']['phone']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['phone'];
				$tmpphone = empty($shippingServiceOne['address']['shippingfrom']['phone']) ? '' : $shippingServiceOne['address']['shippingfrom']['phone'];
					
				$tmpmobile_en = empty($shippingServiceOne['address']['shippingfrom_en']['mobile']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['mobile'];
				$tmpmobile = empty($shippingServiceOne['address']['shippingfrom']['mobile']) ? '' : $shippingServiceOne['address']['shippingfrom']['mobile'];
					
				$tmpfax_en = empty($shippingServiceOne['address']['shippingfrom_en']['fax']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['fax'];
				$tmpfax = empty($shippingServiceOne['address']['shippingfrom']['fax']) ? '' : $shippingServiceOne['address']['shippingfrom']['fax'];
					
				$tmppostcode_en = empty($shippingServiceOne['address']['shippingfrom_en']['postcode']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['postcode'];
				$tmppostcode = empty($shippingServiceOne['address']['shippingfrom']['postcode']) ? '' : $shippingServiceOne['address']['shippingfrom']['postcode'];
					
				$tmpemail_en = empty($shippingServiceOne['address']['shippingfrom_en']['email']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['email'];
				$tmpemail = empty($shippingServiceOne['address']['shippingfrom']['email']) ? '' : $shippingServiceOne['address']['shippingfrom']['email'];
					
				$tmpcountry_en = empty($shippingServiceOne['address']['shippingfrom_en']['country']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['country'];
				$tmpcountry = empty($shippingServiceOne['address']['shippingfrom']['country']) ? '' : $shippingServiceOne['address']['shippingfrom']['country'];
				
				$tmpAddress['shippingfrom'] = array(
					'contact' => empty($shippingServiceOne['address']['shippingfrom']['contact']) ? '' : $shippingServiceOne['address']['shippingfrom']['contact'],
					'contact_en' => empty($shippingServiceOne['address']['shippingfrom_en']['contact']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['contact'],
					'company' => empty($shippingServiceOne['address']['shippingfrom']['company']) ? '' : $shippingServiceOne['address']['shippingfrom']['company'],
					'company_en' => empty($shippingServiceOne['address']['shippingfrom_en']['company']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['company'],
					'phone' => empty($tmpphone_en) ? $tmpphone : $tmpphone_en,
					'mobile' => empty($tmpmobile_en) ? $tmpmobile : $tmpmobile_en,
					'fax' => empty($tmpfax_en) ? $tmpfax : $tmpfax_en,
					'email' => empty($tmpemail_en) ? $tmpemail : $tmpemail_en,
					'country' => empty($tmpcountry_en) ? $tmpcountry : $tmpcountry_en,
					'province' => empty($shippingServiceOne['address']['shippingfrom']['province']) ? '' : $shippingServiceOne['address']['shippingfrom']['province'],
					'province_en' => empty($shippingServiceOne['address']['shippingfrom_en']['province']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['province'],
					'city' => empty($shippingServiceOne['address']['shippingfrom']['city']) ? '' : $shippingServiceOne['address']['shippingfrom']['city'],
					'city_en' => empty($shippingServiceOne['address']['shippingfrom_en']['city']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['city'],
					'district' => empty($shippingServiceOne['address']['shippingfrom']['district']) ? '' : $shippingServiceOne['address']['shippingfrom']['district'],
					'district_en' => empty($shippingServiceOne['address']['shippingfrom_en']['district']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['district'],
					'postcode' => empty($tmppostcode_en) ? $tmppostcode : $tmppostcode_en,
					'street' => empty($shippingServiceOne['address']['shippingfrom']['street']) ? '' : $shippingServiceOne['address']['shippingfrom']['street'],
					'street_en' => empty($shippingServiceOne['address']['shippingfrom_en']['street']) ? '' : $shippingServiceOne['address']['shippingfrom_en']['street'],
				);
			}
			
			if(isset($shippingServiceOne['address']['pickupaddress'])){
				$tmpAddress['pickupaddress'] = array(
						'contact' => empty($shippingServiceOne['address']['pickupaddress']['contact']) ? '' : $shippingServiceOne['address']['pickupaddress']['contact'],
						'company' => empty($shippingServiceOne['address']['pickupaddress']['company']) ? '' : $shippingServiceOne['address']['pickupaddress']['company'],
						'phone' => empty($shippingServiceOne['address']['pickupaddress']['phone']) ? '' : $shippingServiceOne['address']['pickupaddress']['phone'],
						'fax' => empty($shippingServiceOne['address']['pickupaddress']['fax']) ? '' : $shippingServiceOne['address']['pickupaddress']['fax'],
						'country' => empty($shippingServiceOne['address']['pickupaddress']['country']) ? '' : $shippingServiceOne['address']['pickupaddress']['country'],
						'province' => empty($shippingServiceOne['address']['pickupaddress']['province']) ? '' : $shippingServiceOne['address']['pickupaddress']['province'],
						'city' => empty($shippingServiceOne['address']['pickupaddress']['city']) ? '' : $shippingServiceOne['address']['pickupaddress']['city'],
						'district' => empty($shippingServiceOne['address']['pickupaddress']['district']) ? '' : $shippingServiceOne['address']['pickupaddress']['district'],
						'postcode' => empty($shippingServiceOne['address']['pickupaddress']['postcode']) ? '' : $shippingServiceOne['address']['pickupaddress']['postcode'],
						'street' => empty($shippingServiceOne['address']['pickupaddress']['street']) ? '' : $shippingServiceOne['address']['pickupaddress']['street'],
						'mobile' => empty($shippingServiceOne['address']['pickupaddress']['mobile']) ? '' : $shippingServiceOne['address']['pickupaddress']['mobile'],
						'email' => empty($shippingServiceOne['address']['pickupaddress']['email']) ? '' : $shippingServiceOne['address']['pickupaddress']['email'],
				);
			}
				
			if(isset($shippingServiceOne['address']['returnaddress'])){
				$tmpAddress['returnaddress'] = array(
						'contact' => empty($shippingServiceOne['address']['returnaddress']['contact']) ? '' : $shippingServiceOne['address']['returnaddress']['contact'],
						'company' => empty($shippingServiceOne['address']['returnaddress']['company']) ? '' : $shippingServiceOne['address']['returnaddress']['company'],
						'phone' => empty($shippingServiceOne['address']['returnaddress']['phone']) ? '' : $shippingServiceOne['address']['returnaddress']['phone'],
						'fax' => empty($shippingServiceOne['address']['returnaddress']['fax']) ? '' : $shippingServiceOne['address']['returnaddress']['fax'],
						'country' => empty($shippingServiceOne['address']['returnaddress']['country']) ? '' : $shippingServiceOne['address']['returnaddress']['country'],
						'province' => empty($shippingServiceOne['address']['returnaddress']['province']) ? '' : $shippingServiceOne['address']['returnaddress']['province'],
						'city' => empty($shippingServiceOne['address']['returnaddress']['city']) ? '' : $shippingServiceOne['address']['returnaddress']['city'],
						'district' => empty($shippingServiceOne['address']['returnaddress']['district']) ? '' : $shippingServiceOne['address']['returnaddress']['district'],
						'postcode' => empty($shippingServiceOne['address']['returnaddress']['postcode']) ? '' : $shippingServiceOne['address']['returnaddress']['postcode'],
						'street' => empty($shippingServiceOne['address']['returnaddress']['street']) ? '' : $shippingServiceOne['address']['returnaddress']['street'],
						'mobile' => empty($shippingServiceOne['address']['returnaddress']['mobile']) ? '' : $shippingServiceOne['address']['returnaddress']['mobile'],
						'email' => empty($shippingServiceOne['address']['returnaddress']['email']) ? '' : $shippingServiceOne['address']['returnaddress']['email'],
				);
			}
			
			$carrierUserAddress->address_params = $tmpAddress;
// 			print_r($tmpAddress);
			$carrierUserAddress->save(false);
		}
		//自定义物流的地址Conversion end
		
	}
	
	/**
	 * 物流海外仓转第三方仓库 DataConversion
	 */
	public static function carrierToWarehouseConversion(){
		$carrierAccountArr = SysCarrierAccount::find()->where(['carrier_type'=>1,'warehouse_id'=>-1])->all();
		
		foreach ($carrierAccountArr as &$carrierAccount){
			$tmpWarehouseCarrieraccount = array();
			
			if((is_array($carrierAccount['warehouse'])) && (!empty($carrierAccount['warehouse']))){
				foreach ($carrierAccount['warehouse'] as $warehouseVal){
					$tmpWarehouse = Warehouse::find()->where(['carrier_code'=>$carrierAccount['carrier_code'],'third_party_code'=>$warehouseVal])->one();
					
					$params = array();
					$params['puid'] = $carrierAccount->user_id;
					
					if($tmpWarehouse == null){
						$params['warehouse_id'] = -1;
						$params['is_oversea'] = 1;
						$params['carrier_code'] = $carrierAccount['carrier_code'];
						$params['third_party_code'] = $warehouseVal;
						$params['warehouse_name'] = $carrierAccount['carrier_name'].'-'.$warehouseVal;
						$params['is_active'] = 'Y';
						$params['address_nation'] = '中国';
						
						$saveResult = InventoryHelper::saveWarehouseInfoById($params);
						
						if($saveResult['response']['code'] == 0){
							$tmpWarehouseCarrieraccount[$warehouseVal] = $saveResult['response']['data'];
						}
					}else{
						$tmpWarehouseCarrieraccount[$warehouseVal] = $tmpWarehouse->warehouse_id;
					}
				}
				
				if(count($carrierAccount['warehouse']) == 0){
					$carrierAccount->warehouse_id = current($tmpWarehouseCarrieraccount);
					reset($tmpWarehouseCarrieraccount);
					
					$carrierAccount->save(false);
				}else{
					$tmpI = 1;
					foreach ($tmpWarehouseCarrieraccount as $tmpforKey => $tmpforVal){
						if($tmpI == 1){
							$carrierAccount->warehouse_id = $tmpforVal;
							$carrierAccount->warehouse = array($tmpforKey);
							
							$carrierAccount->save(false);
						}else{
							$tmpCarrierAccount = new SysCarrierAccount();
							
							$tmpCarrierAccount->carrier_code = $carrierAccount->carrier_code;
							$tmpCarrierAccount->carrier_name = $carrierAccount->carrier_name.$tmpI;
							$tmpCarrierAccount->carrier_type = $carrierAccount->carrier_type;
							$tmpCarrierAccount->api_params = $carrierAccount->api_params;
							$tmpCarrierAccount->create_time = time();
							$tmpCarrierAccount->update_time = time();
							$tmpCarrierAccount->user_id = $carrierAccount->user_id;
							$tmpCarrierAccount->is_used = $carrierAccount->is_used;
							$tmpCarrierAccount->address = $carrierAccount->address;
							$tmpCarrierAccount->warehouse = array($tmpforKey);
							$tmpCarrierAccount->is_default = 0;
							$tmpCarrierAccount->warehouse_id = $tmpforVal;
							
							if($tmpCarrierAccount->save(false)){
								$shippingExecuteResult = \Yii::$app->get('subdb')->createCommand()->update('sys_shipping_service',
										['carrier_account_id' => $tmpCarrierAccount->id], ['and', ['carrier_account_id' => $carrierAccount->id], ['third_party_code'=>$tmpforKey]])->execute();
								
								CarrierOpenHelper::saveAddOrEditCarrierToManagedbRecord($tmpCarrierAccount);
							}
						}
						
						$tmpI++;
					}
				}
			}
		}
	}
	
	/**
	 * 将物流匹配规则转为新的格式
	 */
	public static function carrierMatchingRuleConversion(){
		$matchingRuleArr = MatchingRule::find()->where('created > 0')->all();
		
		foreach ($matchingRuleArr as $matchingRule){
			
			if(!empty($matchingRule->rules)){
				$tmpSources = false;
				
				$tmpMatchingRule = $matchingRule->rules;
				
				foreach ($tmpMatchingRule as $tmpRulesKey => $tmpRulesVal){
					if(in_array($tmpRulesVal, array('source','site','selleruserid'))){
						$tmpSources = true;
						unset($tmpMatchingRule[$tmpRulesKey]);
					}
					
					if($tmpRulesVal == 'warehouse'){
						unset($tmpMatchingRule[$tmpRulesKey]);
						
						if(!empty($matchingRule->warehouse)){
							$tmpShippingObj = SysShippingService::find()->where(['id'=>$matchingRule->transportation_service_id])->one();
							
							if($tmpShippingObj != null){
								$tmp_proprietary_warehouse = empty($tmpShippingObj->proprietary_warehouse) ? array() : $tmpShippingObj->proprietary_warehouse;
								
								foreach ($matchingRule->warehouse as $tmpWarehouseVal){
									if(!in_array($tmpWarehouseVal, $tmp_proprietary_warehouse)){
										$tmp_proprietary_warehouse[] = $tmpWarehouseVal;
									}
								}
								
								$tmpShippingObj->proprietary_warehouse = $tmp_proprietary_warehouse;
								$tmpShippingObj->save(false);
							}
						}
					}
				}
				
				if($tmpSources){
					$tmpMatchingRule[] = 'sources';
				}
				
				$matchingRule->rules = $tmpMatchingRule;
				$matchingRule->save(false);
			}
		}
	}
	
	/**
	 * 将仓库的可递送国家匹配规则转为新的仓库匹配规则表
	 */
	public static function warehouseMatchingRuleConversion(){
		$tmpWarehouseArr = Warehouse::find()->select(['warehouse_id','name'])->where('warehouse_id != 0')->asArray()->all();
		
		if(count($tmpWarehouseArr) > 0){
			$tmpI = 1;
			foreach ($tmpWarehouseArr as $tmpWarehouse){
				$warehouseCoverNationArr = WarehouseCoverNation::find()->select(['nation','warehouse_id'])->where(['warehouse_id'=>$tmpWarehouse['warehouse_id']])->asArray()->all();
				
				$tmpReceivingCountry = array();
				foreach ($warehouseCoverNationArr as $warehouseCoverNation){
					$tmpReceivingCountry[] = $warehouseCoverNation['nation'];
				}
				
				if(!empty($tmpReceivingCountry)){
					$tmpRules = array();
					$tmpRules['rules'] = array('receiving_country');
					
					$tmpRules['items_location_country'] = '';
					$tmpRules['items_location_provinces'] = '';
					$tmpRules['items_location_city'] = '';
					$tmpRules['receiving_provinces'] = '';
					
					$tmpRules['receiving_city'] = '';
					$tmpRules['skus'] = '';
					$tmpRules['freight_amount'] = array('min'=>'','max'=>'');
					$tmpRules['receiving_country'] = $tmpReceivingCountry;
					
					$tmpWarehouseMatchingRule = new WarehouseMatchingRule();
					$tmpWarehouseMatchingRule->rule_name = $tmpWarehouse['name'];
					$tmpWarehouseMatchingRule->warehouse_id = $tmpWarehouse['warehouse_id'];
					$tmpWarehouseMatchingRule->priority = $tmpI;
					$tmpWarehouseMatchingRule->is_active = 1;
					$tmpWarehouseMatchingRule->created = time();
					$tmpWarehouseMatchingRule->updated = time();
					$tmpWarehouseMatchingRule->rules = json_encode($tmpRules);
					
					$tmpWarehouseMatchingRule->save(false);
					
					$tmpI++;
				}
			}
		}
	}
	
}