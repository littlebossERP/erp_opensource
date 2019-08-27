<?php
namespace eagle\modules\listing\helpers;

use yii;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\html_catcher\helpers\HtmlCatcherHelper;
use eagle\modules\catalog\helpers\PhotoHelper;
use eagle\modules\catalog\models\Photo;
use eagle\models\catalog\Product;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\listing\models\PriceministerProductList;

class PriceministerOfferSyncHelper{


	static private function changeDBPuid($puid){
		if ( empty($puid))
			return false;
	
		return true;
	}//end of changeDBPuid
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取PM offers
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$SellerProductIdList
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/07/20			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GetOfferList($priceministerAccount, $ProductEanList=[]){
		$timeout=600; //s
		$retInfo=[];
		echo "\n enter function : GetOfferList";
	
		$priceminister_token = $priceministerAccount['token'];
		//@todo
		
		return true;
	}
	
	/**

	 */
	public static function webSiteInfoToDb($uid, $seller, $prodcutInfo, $is_base64=true){
		echo "\n <br> start to webSiteInfoToDb, uid=$uid,seller=$seller ... ";
		$rtn['success']=true;
		$rtn['message']='';
		try{
			//异常情况
			if (empty($uid)){
				$message = "uid is empty!";
				echo "\n $message";
				//\Yii::error(['Priceminister',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
				return false;
			}
			
			
			//check  $prodcutInfo should decode or not 
			if (is_string($prodcutInfo) && $is_base64){
				//if string then base64 decode 
				$prodcutInfo = base64_decode($prodcutInfo);
				//if string then json decode
				if (is_string($prodcutInfo)){
					$prodcutInfo = json_decode($prodcutInfo,true);
				}
			}
			if(!empty($prodcutInfo['product_url']) && stripos($prodcutInfo['product_url'], 'http://www.priceminister.com')===false)
				$prodcutInfo['product_url'] = 'http://www.priceminister.com'.$prodcutInfo['product_url'];
			/*
			//var_dump($prodcutInfo);
			if(empty($prodcutInfo['barcode'])){
				$rtn['success']=false;
				$rtn['message']='ean lost';
				echo "\n ean lost;";
				return $rtn;
			}
			if(empty($prodcutInfo['productid'])){
				$rtn['success']=false;
				$rtn['message']='productid lost';
				echo "\n productid lost;";
				return $rtn;
			}
			*/
			//不能匹配任何商品
			if(empty($prodcutInfo['sku']) && empty($prodcutInfo['itemid']))
				return false;
			//商品无sku的情况:
			if(empty($prodcutInfo['sku']) && !empty($prodcutInfo['itemid'])){
				$item = OdOrderItem::find()->where(['order_source_order_item_id'=>$prodcutInfo['itemid']])->one();
				if(empty($item))
					return true;
				$item->photo_primary = empty($prodcutInfo['photo_primary'])?'':$prodcutInfo['photo_primary'];
				$item->product_name = empty($prodcutInfo['headline'])?'':$prodcutInfo['headline'];
				$item->product_url = empty($prodcutInfo['product_url'])?'':$prodcutInfo['product_url'];
				$item->save(false);
				return true;
				
			}
			
			//商品有SKU的情况:
			$listing = PriceministerProductList::find()->where(['partnumber'=>$prodcutInfo['sku'],'seller_id'=>$seller ])->orderBy('id asc')->one();
			if(!empty($listing->id)){
				//删除重复的listing
				$command = Yii::$app->subdb->createCommand ( "DELETE FROM `priceminister_product_list` WHERE `partnumber`=:partnumber AND `seller_id`=:seller_id AND `id`<>:id" );
				$command->bindValue ( ':partnumber', $prodcutInfo['sku'], \PDO::PARAM_STR );
				$command->bindValue ( ':seller_id', $seller, \PDO::PARAM_STR );
				$command->bindValue ( ':id', $listing->id, \PDO::PARAM_STR );
				$affectRows = $command->execute ();
			}
			
			if(empty($listing))
				$listings = new PriceministerProductList();
			
			if(!empty($prodcutInfo['sku']) && empty($listing->partnumber))
				$listing->partnumber = $prodcutInfo['sku'];
			$sku = $listing->partnumber;
				
			$photo_primary=empty($prodcutInfo['photo_primary'])?'':$prodcutInfo['photo_primary'];
			$photo_others=empty($prodcutInfo['photo_other'])?[]:$prodcutInfo['photo_other'];
			$all_imgs = [];
			if(!empty($photo_primary))
				$all_imgs[] = $photo_primary;
			$all_imgs += $all_imgs;
			//update photo info when product photo is null
			if(!empty($sku) && !empty($photo_primary)){
				$rootSku = ProductHelper::getRootSkuByAlias($sku);
				if(!empty($rootSku))
					$sku = $rootSku;
				//echo "\n sku=".$sku."\n";//liang test
				$pd_product = Product::find()->where(['sku'=>$sku])->one();
				//echo "\n this sku=$sku has ".count($pd_product)." products \n";//liang test
				if($pd_product<>null){
					if(empty($pd_product->photo_primary) || stripos($pd_product->photo_primary,'batchImagesUploader/no-img.png')!==false){
						$pd_product->photo_primary =$photo_primary;
					}
					if($pd_product->name=='' or $pd_product->name==$pd_product->sku){
						if(!empty($prodcutInfo['headline']))
							$pd_product->name = $prodcutInfo['headline'];
					}
					$pd_product->save(false);
					$pd_photos = Photo::find()->where(['sku'=>$sku])->all();
					//echo "\n this sku=$sku has ".count($pd_photos)." photos \n";//liang test
					if($pd_photos==null){
						PhotoHelper::savePhotoByUrl($sku, $photo_primary, $photo_others);
					}
				}
			}
			//update order details product url
			if(!empty($prodcutInfo['product_url']) || !empty($photo_primary)){
				$url = empty($prodcutInfo['product_url'])?'':$prodcutInfo['product_url'];
				$photo =  $photo_primary;
				//echo "<br>update eagle2 order detail<br>";
				OdOrderItem::updateAll(['product_url'=>$url,'photo_primary'=>$photo],'sku=:sku',[':sku'=>$prodcutInfo['sku']]);
			}
			
		
			$listing->seller_id = $seller;
			if(empty($listing->headline))
				$listing->headline = !empty($prodcutInfo['headline'])?$prodcutInfo['headline']:null;
			$listing->caption =  !empty($prodcutInfo['caption'])?$prodcutInfo['caption']:null;
			$listing->topic =  !empty($prodcutInfo['topic'])?$prodcutInfo['topic']:null;
			if(empty($listing->url))
				$listing->url = !empty($prodcutInfo['product_url'])?$prodcutInfo['product_url']:null;
			if(!empty($all_imgs))
				$listing->image = json_encode($all_imgs);
			
			if(!$listing->save()){
				$rtn['success'] = false;
				$rtn['message'] = "\n prod ean:".$prodcutInfo['sku']." failure to save to db :".print_r($listing->getErrors());
				echo "\n prod ean:".$prodcutInfo['sku']." failure to save to db :".print_r($listing->getErrors());
			}
				
			
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = is_array($e->getMessage())?json_encode($e->getMessage()):$e->getMessage();
			echo $rtn['message'];
		}
		
		return $rtn;
	}
	
	/**
	 获取订pm单时更新相关产品的信息
	
	 */
	public static function syncProdInfoWhenGetOrderDetail($uid,$seller,$product_sku=[],$itemid=[], $priority=2,$type='sku'){
		$product_key=[];
		if($type=='sku'){
			foreach ($product_sku as $i=>$sku){
				$product_key[$sku] = empty($itemid[$i])?'':$itemid[$i];
			}
		}elseif($type=='itemid'){
			$product_key=$itemid;
		}else 
			echo "\n syncProdInfoWhenGetOrderDetail---error:unvalidation key type!";//test
		
		try{
			$field_list=array('product_id','img','url','bestprices','brand');
			$site = '';
			$callback = 'eagle\modules\listing\helpers\PriceministerOfferSyncHelper::webSiteInfoToDb($puid,$seller,$prodcutInfo);';
			$rtn = HtmlCatcherHelper::requestCatchHtml($uid,$product_key,'priceminister',$field_list,$site,$callback,$falg=true,$priority,['seller_id'=>$seller,'key_type'=>$type]);
			echo "\n syncProdInfoWhenGetOrderDetail---uid=$uid,product_key=".json_encode($product_key);//test
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
		}
		return $rtn;
	}
	
	
	/*
	 * abandoned 废弃function
	 */
	public static function getSellerInfo($token){
		return true;
	}
}
?>