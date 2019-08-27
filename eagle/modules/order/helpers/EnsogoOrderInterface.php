<?php
namespace eagle\modules\order\helpers;
use yii;
use yii\data\Pagination;

use eagle\models\SaasEnsogoUser;
use eagle\modules\listing\models\EnsogoApiQueue;
use eagle\modules\listing\models\EnsogoFanben;
use eagle\modules\listing\models\EnsogoFanbenVariance;
use eagle\modules\order\models\EnsogoOrder;
use eagle\modules\order\models\EnsogoOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper ;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use Qiniu\json_decode;

/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: fanjs
+----------------------------------------------------------------------
| Create Date: 2014-08-01
+----------------------------------------------------------------------
 */
/**
 +------------------------------------------------------------------------------
 * ensogo api 接口模块 
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lkh
 +------------------------------------------------------------------------------
 */
class EnsogoOrderInterface{
	
	static private $EnsogoCouriersListStr = '[{"slug":"17postservice","name":"17 Post Service","phone":"+852 2620 0289","other_name":"17PostService"},{"slug":"2go","name":"2GO","phone":"+63 2 77-99-222","other_name":"Negros Navigation"},{"slug":"360lion","name":"360 Lion Express","phone":"+86 0755-22927311","other_name":""},{"slug":"4-72","name":"4-72 Entregando","phone":"+57 1 4722000","other_name":"Colombia Postal Service"},{"slug":"4px","name":"4PX","phone":"+86 755-33936349","other_name":"递四方"},{"slug":"4squaregroup","name":"4Square Group","phone":"+44 845 519 6854","other_name":"4 Square"},{"slug":"800bestex","name":"Best Express","phone":"+86 4009565656","other_name":"百世汇通"},{"slug":"abf","name":"ABF Freight","phone":"+1 (800) 610-5544","other_name":"Arkansas Best Corporation"},{"slug":"abxexpress-my","name":"ABX Express","phone":"+60 03-7711 6633","other_name":"ABX Express (M) Sdn Bhd"},{"slug":"acscourier","name":"ACS Courier","phone":"+30 210 81 90 000","other_name":"Αναζήτηση Καταστημάτων"},{"slug":"adicional","name":"Adicional Logistics","phone":"+351 707 300 444","other_name":""},{"slug":"adsone","name":"ADSOne","phone":"+61(3) 8379 8201","other_name":"ADSOne Group"},{"slug":"aeroflash","name":"Mexico AeroFlash","phone":"+52 55 5445 2100","other_name":"AeroFlash"},{"slug":"air21","name":"AIR21","phone":"+63 (02) 854-2100","other_name":"AIR 21 PH"},{"slug":"airpak-express","name":"Airpak Express","phone":"+60 03-7875 7768","other_name":""},{"slug":"airspeed","name":"Airspeed International Corporation","phone":"+632 852 - 7338","other_name":"Airspeed Philippines"},{"slug":"an-post","name":"An Post","phone":"+353 1850 57 58 59","other_name":"Ireland Post"},{"slug":"apc","name":"APC Postal Logistics","phone":"+1 (888) 413-7300","other_name":"APC-PLI"},{"slug":"apc-overnight","name":"APC Overnight","phone":"+44 800 37 37 37","other_name":"The Alternative Parcels Company Limited"},{"slug":"aramex","name":"Aramex","phone":"+971 (600) 544000","other_name":"ارامكس"},{"slug":"arrowxl","name":"Arrow XL","phone":"+44 800 015 1509","other_name":"Yodel XL"},{"slug":"asendia-de","name":"Asendia Germany","phone":"+49 0800 18 17 000","other_name":"Asendia De"},{"slug":"asendia-uk","name":"Asendia UK","phone":"+44 845 8738155","other_name":"Asendia United Kingdom"},{"slug":"asendia-usa","name":"Asendia USA","phone":"+1 610 461 3661","other_name":"Brokers Worldwide"},{"slug":"asm","name":"ASM","phone":"+34 902 11 33 00","other_name":"Asm-Red"},{"slug":"aupost-china","name":"AuPost China","phone":"+86 4007005618","other_name":"澳邮宝"},{"slug":"australia-post","name":"Australia Post","phone":"+61 13 13 18","other_name":"AusPost"},{"slug":"austrian-post","name":"Austrian Post (Express)","phone":"+43 810 010 100","other_name":"Österreichische Post AG"},{"slug":"austrian-post-registered","name":"Austrian Post (Registered)","phone":"+43 810 010 100","other_name":"Österreichische Post AG"},{"slug":"belpost","name":"Belpost","phone":"+375 17 293 59 10","other_name":"Belposhta, Белпочта"},{"slug":"bert-fr","name":"Bert Transport","phone":"+33 4 75 31 01 15","other_name":""},{"slug":"bgpost","name":"Bulgarian Posts","phone":"+3592/949 3280","other_name":"Български пощи"},{"slug":"bh-posta","name":"JP BH Pošta","phone":"+387 033 723 440","other_name":"Bosnia and Herzegovina Post"},{"slug":"bluedart","name":"Bluedart","phone":"+91 18602331234","other_name":"Blue Dart Express"},{"slug":"bondscouriers","name":"Bonds Couriers","phone":"+61 1300-369-300","other_name":""},{"slug":"boxc","name":"BOXC","phone":"+1 424 278-4286","other_name":"BOXC快遞"},{"slug":"bpost","name":"Belgium Post","phone":"+32 2 276 22 74","other_name":"bpost, Belgian Post"},{"slug":"bpost-international","name":"bpost international","phone":"+32 2 201 23 45","other_name":"Landmark Global"},{"slug":"brazil-correios","name":"Brazil Correios","phone":"+55 3003-0100","other_name":"Brazilian Post"},{"slug":"brt-it","name":"BRT Bartolini","phone":"+39 011 397 411 1","other_name":"BRT Corriere Espresso, DPD Italy"},{"slug":"cambodia-post","name":"Cambodia Post","phone":"+855 23 723 51","other_name":"Cambodia Post"},{"slug":"canada-post","name":"Canada Post","phone":"+1 866 607 6301","other_name":"Postes Canada"},{"slug":"canpar","name":"Canpar Courier","phone":"+1 800-387-9335","other_name":"TransForce"},{"slug":"cbl-logistica","name":"CBL Logistics","phone":"+34902887887","other_name":""},{"slug":"ceska-posta","name":"Česká Pošta","phone":"+420 840 111 244","other_name":"Czech Post"},{"slug":"china-ems","name":"China EMS","phone":"+86 20 11183","other_name":"中国邮政速递物流"},{"slug":"china-post","name":"China Post","phone":"+86 20 11185","other_name":"中国邮政, ePacket, e-Packet"},{"slug":"chronopost-france","name":"Chronopost France","phone":"+33 (0) 969 391 391","other_name":"La Poste EMS"},{"slug":"chronopost-portugal","name":"Chronopost Portugal","phone":"+351 707 20 28 28","other_name":"Chronopost pt"},{"slug":"citylinkexpress","name":"City-Link Express","phone":"+60 603-5565 8399","other_name":"Citylink Malaysia"},{"slug":"cj-gls","name":"CJ GLS","phone":"+63-567-1320","other_name":"CJ Korea Express, 씨제이지엘에스주식회사"},{"slug":"cnexps","name":"CNE Express","phone":"+86 400 021 5600","other_name":"国际快递"},{"slug":"colis-prive","name":"Colis Privé","phone":"+33 0826 82 83 84","other_name":"ColisPrivé"},{"slug":"colissimo","name":"Colissimo","phone":"+33 3631","other_name":"Colissimo fr"},{"slug":"collectplus","name":"Collect+","phone":"+44 1923 601616","other_name":"Collect Plus UK"},{"slug":"con-way","name":"Con-way Freight","phone":"+1 800 426-6929","other_name":"Conway"},{"slug":"correo-argentino","name":"Correo Argentino","phone":"+54 11 4891-9191","other_name":"Argentina Post"},{"slug":"correos-chile","name":"Correos Chile","phone":"+562 600 950 2020","other_name":"Chile Post"},{"slug":"correos-de-mexico","name":"Correos de Mexico","phone":"+52 01 800 701 7000","other_name":"Mexico Post"},{"slug":"correosexpress","name":"Correos Express","phone":"+34 902 1 22 333","other_name":""},{"slug":"costmeticsnow","name":"Cosmetics Now","phone":"+1-415-230-2376","other_name":"CosmeticsNow"},{"slug":"courier-plus","name":"Courier Plus","phone":"+234-18102031","other_name":"Courier Plus"},{"slug":"courierit","name":"Courier IT","phone":"+27 21 555 6777","other_name":"Courierit"},{"slug":"courierpost","name":"CourierPost","phone":"+64 0800 268 743","other_name":"Express Couriers"},{"slug":"couriers-please","name":"Couriers Please","phone":"+61 1300 361 000","other_name":"CouriersPlease"},{"slug":"cpacket","name":"cPacket","phone":"+86 400-999-6128","other_name":"u52a0u90aeu5b9d"},{"slug":"cuckooexpress","name":"Cuckoo Express","phone":"+86 400-100-0533","other_name":"布谷鸟"},{"slug":"cyprus-post","name":"Cyprus Post","phone":"+357 22805802","other_name":"ΚΥΠΡΙΑΚΑ ΤΑΧΥΔΡΟΜΕΙΑ"},{"slug":"dachser","name":"DACHSER","phone":"+34 96316 5700","other_name":"Azkar"},{"slug":"danmark-post","name":"Post Danmark","phone":"+45 80 20 70 30","other_name":"Danske Post"},{"slug":"dawnwing","name":"Dawn Wing","phone":"+27 0861 223 224","other_name":"DPD Laser Express Logistics"},{"slug":"dbschenker-se","name":"DB Schenker Sweden","phone":"+46 31 337 04 00","other_name":"Deutsche Bahn"},{"slug":"delcart-in","name":"Delcart","phone":"+91 080 41311515","other_name":""},{"slug":"delhivery","name":"Delhivery","phone":"+91 (124) 6719500","other_name":"Gharpay"},{"slug":"delivreeking","name":"Delivree King","phone":"+91 1800-3000-2620","other_name":"delivery king"},{"slug":"deltec-courier","name":"Deltec Courier","phone":"+44 20 8569 6767","other_name":"Deltec Interntional Courier"},{"slug":"detrack","name":"Detrack","phone":"+65 6844 0509","other_name":"Detrack Singapore"},{"slug":"deutsch-post","name":"Deutsche Post Mail","phone":"+49 (0) 180 2 000221","other_name":"dpdhl"},{"slug":"dhl","name":"DHL Express","phone":"+1 800 225 5345","other_name":"DHL International"},{"slug":"dhl-benelux","name":"DHL Benelux","phone":"+31 26-324 6700","other_name":"DHL TrackNet Benelux"},{"slug":"dhl-deliverit","name":"DHL 2-Mann-Handling","phone":"+49 228 28609898","other_name":"DHL Deliver IT"},{"slug":"dhl-es","name":"DHL Spain Domestic","phone":"+34 902 09 05 41","other_name":"DHL España"},{"slug":"dhl-germany","name":"Deutsche Post DHL","phone":"+49 228 28609898","other_name":"DHL Germany"},{"slug":"dhl-global-mail","name":"DHL eCommerce","phone":"+1 317 554 5191","other_name":"DHL Global Mail"},{"slug":"dhl-global-mail-asia","name":"DHL Global Mail Asia","phone":"+65 6883 0771","other_name":"DGM Asia"},{"slug":"dhl-hk","name":"DHL Hong Kong","phone":"+852 2710-8111","other_name":"DHL HK Domestic"},{"slug":"dhl-nl","name":"DHL Netherlands","phone":"+31 26-324 6700","other_name":"DHL Nederlands"},{"slug":"dhl-pieceid","name":"DHL Express (Piece ID)","phone":null,"other_name":"DHL International"},{"slug":"dhl-poland","name":"DHL Poland Domestic","phone":"+48 42 6 345 345","other_name":"DHL Polska"},{"slug":"dhlparcel-nl","name":"DHL Parcel NL","phone":"+31 0900 - 222 21 20","other_name":"Selektvracht, dhlparcel.nl"},{"slug":"directfreight-au","name":"Direct Freight Express","phone":"+1300347397","other_name":""},{"slug":"directlink","name":"Direct Link","phone":"+852 28504183","other_name":"Direct Link"},{"slug":"dmm-network","name":"DMM Network","phone":"+39 02-55396","other_name":"dmmnetwork.it"},{"slug":"dotzot","name":"Dotzot","phone":"+91 33004444","other_name":"Dotzot"},{"slug":"dpd","name":"DPD","phone":"+49 01806 373 200","other_name":"Dynamic Parcel Distribution"},{"slug":"dpd-de","name":"DPD Germany","phone":"+49 01806 373 200","other_name":"DPD Germany"},{"slug":"dpd-ireland","name":"DPD Ireland","phone":"+353 (0)90 64 20500","other_name":"DPD ie"},{"slug":"dpd-poland","name":"DPD Poland","phone":"+48 801 400 373","other_name":"Dynamic Parcel Distribution Poland"},{"slug":"dpd-uk","name":"DPD UK","phone":"+44 845 9 300 350","other_name":"Dynamic Parcel Distribution UK"},{"slug":"dpe-za","name":"DPE South Africa","phone":"+27 (011) 573 3000","other_name":"DPE Worldwide Express"},{"slug":"dpex","name":"DPEX","phone":"+65 6781 8888","other_name":"TGX, Toll Global Express Asia"},{"slug":"dsv","name":"DSV","phone":"+1 (732) 850-8000","other_name":""},{"slug":"dtdc","name":"DTDC India","phone":"+91 33004444","other_name":"DTDC Courier & Cargo"},{"slug":"dynamic-logistics","name":"Dynamic Logistics","phone":"+66 02-688-6688","other_name":"Dynamic Logistics Thailand"},{"slug":"easy-mail","name":"Easy Mail","phone":"+30 210 48 35 000","other_name":""},{"slug":"ec-firstclass","name":"EC-Firstclass","phone":"+86 4006 988 223","other_name":"ChuKou1, CK1"},{"slug":"ecargo-asia","name":"Ecargo","phone":"+82 (0) 70-4940-0025","other_name":"Ecargo Pte. Ltd"},{"slug":"ecexpress-cn","name":"EC Express","phone":"+86 021-31190878","other_name":"CIS Post, u4e0au6d77u4e1cu64ce"},{"slug":"echo","name":"Echo","phone":"+1 (800) 354-7993","other_name":"Echo Global Logistics"},{"slug":"ecom-express","name":"Ecom Express","phone":"+91 011-30212000","other_name":"EcomExpress"},{"slug":"elta-courier","name":"ELTA Hellenic Post","phone":"+30 801 11 83000","other_name":"Greece Post, Ελληνικά Ταχυδρομεία, ELTA Courier, Ταχυμεταφορές ΕΛΤΑ"},{"slug":"emirates-post","name":"Emirates Post","phone":"+971 600 599999","other_name":"مجموعة بريد الإمارات, UAE Post"},{"slug":"empsexpress","name":"EMPS Express","phone":"+86 (755) 36620359","other_name":"快信快包"},{"slug":"ensenda","name":"Ensenda","phone":"+1-888-407-4640","other_name":""},{"slug":"envialia","name":"Envialia","phone":"+34 902400909","other_name":"Envialia Spain"},{"slug":"equick-cn","name":"Equick China","phone":"+1 400 706 6078","other_name":"北京网易速达"},{"slug":"estafeta","name":"Estafeta","phone":"+52 1-800-378-2338","other_name":"Estafeta Mexicana"},{"slug":"estes","name":"Estes","phone":"+1-886-378-3748","other_name":"Estes Express Lines"},{"slug":"exapaq","name":"Exapaq","phone":"+33 (0)1 55 35 02 80 ","other_name":"DPD France"},{"slug":"fastrak-th","name":"Fastrak Services","phone":"+66 (2) 710-2900","other_name":"Fastrak Advanced Delivery Systems"},{"slug":"fastway-au","name":"Fastway Australia","phone":"+61 (0) 2 9737 8288","other_name":"Fastway Couriers"},{"slug":"fastway-ireland","name":"Fastway Ireland","phone":"+353 1 4242 900","other_name":"Fastway Couriers"},{"slug":"fastway-nz","name":"Fastway New Zealand","phone":"+64 (09) 634 3704","other_name":""},{"slug":"fastway-za","name":"Fastway South Africa","phone":"+27 0861 222 882","other_name":"Fastway Couriers"},{"slug":"fedex","name":"FedEx","phone":"+1 800 247 4747","other_name":"Federal Express"},{"slug":"fedex-freight","name":"FedEx Freight","phone":"+1 800.463.3339","other_name":"FedEx LTL"},{"slug":"fedex-uk","name":"FedEx UK","phone":"+ 44 2476 706 660","other_name":"FedEx United Kingdom"},{"slug":"fercam","name":"FERCAM Logistics & Transport","phone":"+39 0471 530 000","other_name":"FERCAM SpA"},{"slug":"first-flight","name":"First Flight Couriers","phone":"+91 022-39576666","other_name":"FirstFlight India"},{"slug":"first-logistics","name":"First Logistics","phone":"+62 021 - 73880707","other_name":"PT Synergy First Logistics"},{"slug":"flytexpress","name":"Flyt Express","phone":"+1 400-888-4003","other_name":"飞特物流"},{"slug":"gati-kwe","name":"Gati-KWE","phone":"+91 1800-180-4284","other_name":"Gati-Kintetsu Express"},{"slug":"gdex","name":"GDEX","phone":"+60 03-77872222","other_name":"GD Express"},{"slug":"geodis-calberson-fr","name":"Geodis Calberson France","phone":"+33 (0)1 70 15 16 17","other_name":"Geodiscalberson"},{"slug":"ghn","name":"Giao hàng nhanh","phone":"+84 19006491","other_name":"Giaohangnhanh.vn, GHN"},{"slug":"globegistics","name":"Globegistics Inc.","phone":"+1 516-479-6671","other_name":""},{"slug":"gls","name":"GLS","phone":"+44 247 621 3455","other_name":"General Logistics Systems"},{"slug":"gls-italy","name":"GLS Italy","phone":"+39 199 151188","other_name":"GLS Corriere Espresso"},{"slug":"gls-netherlands","name":"GLS Netherlands","phone":"+31 0900-1116660","other_name":"GLS NL"},{"slug":"gofly","name":"GoFly","phone":"+86 13143830725","other_name":"GoFlyi"},{"slug":"gojavas","name":"GoJavas","phone":"+91 124-4405730","other_name":"Javas"},{"slug":"greyhound","name":"Greyhound","phone":"-","other_name":"Greyhound Package Express"},{"slug":"hermes","name":"Hermesworld","phone":"+44 844 543 7000","other_name":"Hermes-europe UK"},{"slug":"hermes-de","name":"Hermes Germany","phone":"+49 1806-311211","other_name":"myhermes.de, Hermes Logistik Gruppe Deutschland"},{"slug":"hh-exp","name":"Hua Han Logistics","phone":"+86-0755-82518733","other_name":"u534eu7ff0u56fdu9645u7269u6d41"},{"slug":"homedirect-logistics","name":"Homedirect Logistics","phone":"+44 1908 410560","other_name":""},{"slug":"hong-kong-post","name":"Hong Kong Post","phone":"+852 2921 2222","other_name":"香港郵政"},{"slug":"hrvatska-posta","name":"Hrvatska Pošta","phone":"+385 0800 303 304","other_name":"Croatia Post"},{"slug":"i-parcel","name":"i-parcel","phone":"+44 1342 315 455","other_name":"iparcel"},{"slug":"imxmail","name":"IMX Mail","phone":"+44 (0) 20 84 39 11 77","other_name":"IMX International Mail Consolidator"},{"slug":"india-post","name":"India Post Domestic","phone":"+91 1800 11 2011","other_name":"भारतीय डाक"},{"slug":"india-post-int","name":"India Post International","phone":"+91 1800 11 2011","other_name":"भारतीय डाक, Speed Post & eMO, EMS, IPS Web"},{"slug":"inpost-paczkomaty","name":"InPost Paczkomaty","phone":"+48 801 400 100","other_name":""},{"slug":"interlink-express","name":"Interlink Express","phone":"+44 8702 200 300","other_name":"Interlink UK"},{"slug":"international-seur","name":"International Seur","phone":"+34 902101010","other_name":"SEUR Internacional"},{"slug":"israel-post","name":"Israel Post","phone":"+972 2 629 0691","other_name":"חברת דואר ישראל"},{"slug":"israel-post-domestic","name":"Israel Post Domestic","phone":"+972 2 629 0691","other_name":"חברת דואר ישראל מקומית"},{"slug":"italy-sda","name":"Italy SDA","phone":"+39 199 113366","other_name":"SDA Express Courier"},{"slug":"jam-express","name":"Jam Express","phone":"+63 239 7502","other_name":"JAM Global Express"},{"slug":"japan-post","name":"Japan Post","phone":"+81 0570-046111","other_name":"日本郵便"},{"slug":"jayonexpress","name":"Jayon Express (JEX)","phone":"","other_name":""},{"slug":"jcex","name":"JCEX","phone":"+86 571-86436777","other_name":"JiaCheng, 杭州佳成"},{"slug":"jet-ship","name":"Jet-Ship Worldwide","phone":"+0","other_name":""},{"slug":"jne","name":"JNE","phone":"+62 021-29278888","other_name":"Express Across Nation, Tiki Jalur Nugraha Ekakurir"},{"slug":"kangaroo-my","name":"Kangaroo Worldwide Express","phone":"+ 60 603-5518 8228","other_name":""},{"slug":"kerry-logistics","name":"Kerry Express Thailand","phone":"+66 02 3384777","other_name":"嘉里物流, Kerry Logistics"},{"slug":"kerryttc-vn","name":"Kerry TTC Express","phone":"+84 08 38112112","other_name":"KTTC"},{"slug":"kgmhub","name":"KGM Hub","phone":"+65 6532 7172","other_name":"KGM"},{"slug":"kn","name":"Kuehne + Nagel","phone":"+41-44-7869511","other_name":"KN"},{"slug":"korea-post","name":"Korea Post","phone":"+82 2 2195 1114","other_name":"우정사업본부"},{"slug":"la-poste-colissimo","name":"La Poste","phone":"+33 3631","other_name":"Coliposte"},{"slug":"lao-post","name":"Lao Post","phone":"+","other_name":"Laos Postal Service"},{"slug":"lasership","name":"LaserShip","phone":"+1 (800) 527-3764","other_name":"LaserShip"},{"slug":"lbcexpress","name":"LBC Express","phone":"+63 800-10-8585999","other_name":"LBC Express"},{"slug":"lietuvos-pastas","name":"Lietuvos Paštas","phone":"+370 8 700 55 400","other_name":"Lithuania Post, LP Express"},{"slug":"lion-parcel","name":"Lion Parcel","phone":"+62 21 63798000","other_name":""},{"slug":"lwe-hk","name":"Logistic Worldwide Express","phone":"+852 3421 1122","other_name":"LWE"},{"slug":"magyar-posta","name":"Magyar Posta","phone":"+36 6 4046 4646","other_name":"Hungarian Post"},{"slug":"malaysia-post","name":"Malaysia Post EMS / Poslaju","phone":"+603 27279100","other_name":"Pos Ekspres, Pos Malaysia Express"},{"slug":"malaysia-post-posdaftar","name":"Malaysia Post - Registered","phone":"+603 27279100","other_name":"PosDaftar"},{"slug":"matdespatch","name":"Matdespatch","phone":"+6011 1080 4414","other_name":""},{"slug":"matkahuolto","name":"Matkahuolto","phone":"+358 0800 132 582","other_name":"Oy Matkahuolto Ab"},{"slug":"maxcellents","name":"Maxcellents Pte Ltd","phone":"+65 81115705","other_name":"Maxcellent"},{"slug":"mexico-redpack","name":"Mexico Redpack","phone":"+52 1800-013-3333","other_name":"TNT Mexico"},{"slug":"mexico-senda-express","name":"Mexico Senda Express","phone":"+52 1800 833 93 00","other_name":"Mexico Senda Express"},{"slug":"mondialrelay","name":"Mondial Relay","phone":"+33 09 69 32 23 32","other_name":"Mondial Relay France"},{"slug":"mrw-spain","name":"MRW","phone":"+34 902 300 403","other_name":"MRW Spain"},{"slug":"myhermes-uk","name":"myHermes UK","phone":"+44 844 543 7411","other_name":""},{"slug":"mypostonline","name":"Mypostonline","phone":"+60 07-6625692","other_name":"MYBOXPOST"},{"slug":"nacex-spain","name":"NACEX Spain","phone":"+34 900 100 000","other_name":"NACEX Logista"},{"slug":"nanjingwoyuan","name":"Nanjing Woyuan","phone":"+86 18168003600","other_name":"u6c83u6e90"},{"slug":"nationwide-my","name":"Nationwide Express","phone":"+60 603-5163 3333","other_name":"nationwide2u"},{"slug":"new-zealand-post","name":"New Zealand Post","phone":"+64 9 367 9710","other_name":"NZ Post"},{"slug":"newgistics","name":"Newgistics","phone":"+1 877-860-5997","other_name":""},{"slug":"nhans-solutions","name":"Nhans Solutions","phone":"+65 66590749","other_name":"Nhans Courier"},{"slug":"nightline","name":"Nightline","phone":"+353 (0)1 883 5400","other_name":""},{"slug":"ninjavan","name":"Ninja Van","phone":"+65 6602 8271","other_name":""},{"slug":"ninjavan-my","name":"Ninja Van Malaysia","phone":"+65 6602 8271","other_name":"NinjaVan MY"},{"slug":"nipost","name":"NiPost","phone":"+234 09-3149531","other_name":"Nigerian Postal Service"},{"slug":"norsk-global","name":"Norsk Global","phone":"+44 1753 800800","other_name":"Norsk European Wholesale"},{"slug":"nova-poshta","name":"Nova Poshta","phone":"+380 50-4-500-609","other_name":"Новая Почта"},{"slug":"nuvoex","name":"NuvoEx","phone":"+91 8882140288","other_name":"Nuvo Ex"},{"slug":"oca-ar","name":"OCA Argentina","phone":"+54 800-999-7700","other_name":"OCA e-Pak"},{"slug":"old-dominion","name":"Old Dominion Freight Line","phone":"+1-800-432-6335","other_name":"ODFL"},{"slug":"omniparcel","name":"Omni Parcel","phone":"+852 3195-3195","other_name":"Omni-Channel Logistics (Seko)"},{"slug":"oneworldexpress","name":"One World Express","phone":"+86 0755-86536663","other_name":"u6613u65f6u9039u7269u6d41"},{"slug":"ontrac","name":"OnTrac","phone":"+1 800 334 5000","other_name":"OnTrac Shipping"},{"slug":"opek","name":"FedEx Poland Domestic","phone":"+48 22 732 79 99","other_name":"OPEK"},{"slug":"packlink","name":"Packlink","phone":"","other_name":"Packlink Spain"},{"slug":"pandulogistics","name":"Pandu Logistics","phone":"+62 (021) 461 6007","other_name":""},{"slug":"panther","name":"Panther","phone":"+60 07-6625692","other_name":"Panther Group UK"},{"slug":"parcel-express","name":"Parcel Express","phone":"+65 6513 7762","other_name":"Parcel Express Pte Ltd"},{"slug":"parcel-force","name":"Parcel Force","phone":"+44 844 800 44 66","other_name":"Parcelforce UK"},{"slug":"parcelled-in","name":"Parcelled.in","phone":"+91 1800 419 0285","other_name":"Parcelled"},{"slug":"parcelpost-sg","name":"Parcel Post Singapore","phone":"+65","other_name":"ParcelPost"},{"slug":"pfcexpress","name":"PFC Express","phone":"+86 0755-83727415","other_name":"PFCu7687u5bb6u7269u6d41"},{"slug":"poczta-polska","name":"Poczta Polska","phone":"+48 43-842-06-00","other_name":"Poland Post"},{"slug":"portugal-ctt","name":"Portugal CTT","phone":"+351 707 26 26 26","other_name":"Correios de Portugal"},{"slug":"portugal-seur","name":"Portugal Seur","phone":"+351 707 50 10 10","other_name":"SEUR"},{"slug":"pos-indonesia","name":"Pos Indonesia Domestic","phone":"+62 21 161","other_name":"Indonesian Post Domestic"},{"slug":"pos-indonesia-int","name":"Pos Indonesia Int\'l","phone":"+62 21 161","other_name":"Indonesian Post International EMS"},{"slug":"post-serbia","name":"Post Serbia","phone":"+381 700 100 300","other_name":"Pou0161ta Srbije"},{"slug":"post56","name":"Post56","phone":"+86 400-9966-156","other_name":"捷邮快递"},{"slug":"posta-romana","name":"Poșta Română","phone":"+40 021 9393 111","other_name":"Romania Post"},{"slug":"poste-italiane","name":"Poste Italiane","phone":"+39 803 160","other_name":"Italian Post"},{"slug":"poste-italiane-paccocelere","name":"Poste Italiane Paccocelere","phone":"+39 803 160","other_name":"Italian Post EMS / Express"},{"slug":"posten-norge","name":"Posten Norge / Bring","phone":"+47 21316260","other_name":"Norway Post, Norska Posten"},{"slug":"posti","name":"Itella Posti","phone":"+358 200 71000","other_name":"Finland Post"},{"slug":"postnl","name":"PostNL Domestic","phone":"+31 (0)900 0990","other_name":"PostNL Pakketten, TNT Post Netherlands"},{"slug":"postnl-3s","name":"PostNL International 3S","phone":"+31 (0)900 0990","other_name":"TNT Post parcel service United Kingdom"},{"slug":"postnl-international","name":"PostNL International","phone":"+31 (0)900 0990","other_name":"Netherlands Post, Spring Global Mail"},{"slug":"postnord","name":"PostNord Logistics","phone":"+46 771 33 33 10","other_name":"Posten Norden"},{"slug":"postur-is","name":"Iceland Post","phone":"+354 580 1200","other_name":"Postur.is, Íslandspóstur"},{"slug":"ppbyb","name":"PayPal Package","phone":"+86 20 11185","other_name":"贝邮宝"},{"slug":"professional-couriers","name":"Professional Couriers","phone":"+91 080 22110641","other_name":"TPC India"},{"slug":"ptt-posta","name":"PTT Posta","phone":"+90 444 1 788","other_name":"Turkish Post"},{"slug":"purolator","name":"Purolator","phone":"+1-888-744-7123","other_name":"Purolator Freight"},{"slug":"quantium","name":"Quantium","phone":"+852 2318 1213","other_name":"u51a0u5eadu7269u6d41"},{"slug":"qxpress","name":"Qxpress","phone":"+86 755-8829 7707","other_name":"Qxpress Qoo10"},{"slug":"raben-group","name":"Raben Group","phone":"+49 2166 520 0","other_name":"myRaben"},{"slug":"raf","name":"RAF Philippines","phone":"+632 820-2920 to 25","other_name":"RAF Int\'l. Forwarding"},{"slug":"raiderex","name":"RaidereX","phone":"+65 8666-5481","other_name":"Detrack"},{"slug":"ramgroup-za","name":"RAM","phone":"+27 0861 726 726","other_name":"RAM Group"},{"slug":"red-express","name":"Red Express","phone":"+91 1800-123-2400","other_name":"Red Express"},{"slug":"red-express-wb","name":"Red Express Waybill","phone":"+91 1800-123-2400","other_name":"Red Express WayBill"},{"slug":"redur-es","name":"Redur Spain","phone":"+34 93 263 16 16","other_name":"Eurodis"},{"slug":"rl-carriers","name":"RL Carriers","phone":"+1 800-543-5589","other_name":"R+L Carriers"},{"slug":"rocketparcel","name":"Rocket Parcel International","phone":"+82-2-6237-1418","other_name":""},{"slug":"royal-mail","name":"Royal Mail","phone":"+44 1752387112","other_name":"Royal Mail United Kingdom"},{"slug":"rpx","name":"RPX Indonesia","phone":"+62 0-800-1-888-900","other_name":"Repex Perdana International"},{"slug":"rpxonline","name":"RPX Online","phone":"+852 2620 0289","other_name":"Cathay Pacific"},{"slug":"rrdonnelley","name":"RR Donnelley","phone":"+18007424455","other_name":"RRD"},{"slug":"russian-post","name":"Russian Post","phone":"+7 (495) 956-20-67","other_name":"Почта России, EMS Post RU"},{"slug":"rzyexpress","name":"RZY Express","phone":"+65 96982006","other_name":"RZYExpress"},{"slug":"safexpress","name":"Safexpress","phone":"+91 1800 113 113","other_name":"Safexpress"},{"slug":"sagawa","name":"Sagawa","phone":"+81 0120-18-9595","other_name":"佐川急便"},{"slug":"sapo","name":"South African Post Office","phone":"+27 0860 111 502","other_name":"South African Post Office"},{"slug":"saudi-post","name":"Saudi Post","phone":"+966 9200 05700","other_name":"البريد السعودي"},{"slug":"sekologistics","name":"SEKO Logistics","phone":"+852 3195 3195","other_name":"SEKO"},{"slug":"sf-express","name":"S.F. Express","phone":"+852 273 00 273 / +86 4008-111-111 / +886 800 088 830","other_name":"順豊快遞"},{"slug":"sfb2c","name":"S.F International","phone":"+86 21 6976 3579 ","other_name":"順豐國際"},{"slug":"sfcservice","name":"SFC Service","phone":"+86 400-881-8106","other_name":"u6df1u5733u4e09u6001u56fdu9645u901fu9012"},{"slug":"sgt-it","name":"SGT Corriere Espresso","phone":"+39 02 580751","other_name":"SoGeTras Corriere Espresso"},{"slug":"sic-teliway","name":"Teliway SIC Express","phone":"+33 (0) 1 39 37 40 08","other_name":"Prevote"},{"slug":"singapore-post","name":"Singapore Post","phone":"+65 6841 2000","other_name":"SingPost"},{"slug":"singapore-speedpost","name":"Singapore Speedpost","phone":"+65 6222 5777","other_name":"Singapore EMS"},{"slug":"singparcel","name":"SingParcel Service","phone":"+65 9807 0835","other_name":"SPS"},{"slug":"siodemka","name":"Siodemka","phone":"+48 22 777 77 77 ext. 3","other_name":"Siodemka Kurier"},{"slug":"skynet","name":"SkyNet Malaysia","phone":"+60 3- 56239090","other_name":"SkyNet MY"},{"slug":"skynetworldwide","name":"SkyNet Worldwide Express","phone":"+44 20 8538 1988","other_name":"Skynetwwe"},{"slug":"skynetworldwide-uk","name":"Skynet Worldwide Express UK","phone":"+44 20 8538 1988","other_name":"Skynet UK"},{"slug":"skypostal","name":"SkyPostal","phone":"+1 (305) 599-1812","other_name":"Postrac"},{"slug":"smsa-express","name":"SMSA Express","phone":"+966 92000 9999","other_name":""},{"slug":"spain-correos-es","name":"Correos de España","phone":"+34 900400004","other_name":"Spain Post, ChronoExpress"},{"slug":"spanish-seur","name":"Spanish Seur","phone":"+34 902101010","other_name":"SEUR"},{"slug":"specialisedfreight-za","name":"Specialised Freight","phone":"+27 21 528 1000","other_name":"SFS"},{"slug":"speedcouriers-gr","name":"Speed Couriers","phone":"+30 210 9762007","other_name":"Speed Couriers"},{"slug":"speedexcourier","name":"Speedex Courier","phone":"+965 1881881","other_name":"Speedex Courier"},{"slug":"spreadel","name":"Spreadel","phone":"+911246128000","other_name":"Jabong delivery"},{"slug":"srekorea","name":"SRE Korea","phone":"+82 02 2661 0055","other_name":"SRE 배송서비스"},{"slug":"star-track","name":"StarTrack","phone":"+61 13 2345","other_name":"Star Track"},{"slug":"star-track-express","name":"Star Track Express","phone":"+61 13 2345","other_name":"AaE Australian air Express"},{"slug":"sto","name":"STO Express","phone":"+86 95543","other_name":"申通快递, Shentong Express"},{"slug":"sweden-posten","name":"Sweden Posten","phone":"+46 8 23 22 20","other_name":"Sweden Post"},{"slug":"swiss-post","name":"Swiss Post","phone":"+41 848 888 888","other_name":"La Poste Suisse, Die Schweizerische Post, Die Post"},{"slug":"szdpex","name":"DPEX China","phone":"+86 755-8829 7707","other_name":"DPEX（深圳）国际物流, Toll China"},{"slug":"taiwan-post","name":"Taiwan Post","phone":"+886 (02)2703-7527","other_name":"Chunghwa Post, 台灣中華郵政"},{"slug":"taqbin-hk","name":"TAQBIN Hong Kong","phone":"+852 2829 2222","other_name":"Yamat, 雅瑪多運輸- 宅急便"},{"slug":"taqbin-jp","name":"Yamato Japan","phone":"+81 0120-17-9625","other_name":"ヤマト運輸, TAQBIN"},{"slug":"taqbin-my","name":"TAQBIN Malaysia","phone":"+60 1800-8-827246","other_name":"TAQBIN Malaysia"},{"slug":"taqbin-sg","name":"TAQBIN Singapore","phone":"+65 1800 225 5888","other_name":"Yamato Singapore"},{"slug":"taxydromiki","name":"Geniki Taxydromiki","phone":"+30 210 4851100","other_name":"ΓΕΝΙΚΗ ΤΑΧΥΔΡΟΜΙΚΗ"},{"slug":"tgx","name":"TGX","phone":"+852 3513 0888","other_name":"Top Gun Express, 精英速運"},{"slug":"thailand-post","name":"Thailand Thai Post","phone":"+66 (0) 2831 3131","other_name":"ไปรษณีย์ไทย"},{"slug":"tiki","name":"Tiki","phone":"+62 500 125","other_name":"Citra Van Titipan Kilat"},{"slug":"tnt","name":"TNT","phone":"+1 800 558 5555","other_name":"TNT Express"},{"slug":"tnt-au","name":"TNT Australia","phone":"+61 13 11 50","other_name":"TNT AU"},{"slug":"tnt-click","name":"TNT-Click Italy","phone":"+39 199 803 868","other_name":"TNT Italy"},{"slug":"tnt-fr","name":"TNT France","phone":"+33 4 72 80 77 77","other_name":"TNT Express FR"},{"slug":"tnt-it","name":"TNT Italy","phone":"+39 199 803 868","other_name":"TNT Express IT"},{"slug":"tnt-reference","name":"TNT Reference","phone":"+1 800 558 5555","other_name":"TNT consignment reference"},{"slug":"tnt-uk","name":"TNT UK","phone":"+44 800 100 600","other_name":"TNT United Kingdom"},{"slug":"tnt-uk-reference","name":"TNT UK Reference","phone":"+44 800 100 600","other_name":"TNT UK consignment reference"},{"slug":"tntpost-it","name":"Nexive (TNT Post Italy)","phone":"+39 02 50720011","other_name":"Postnl TNT"},{"slug":"toll-ipec","name":"Toll IPEC","phone":"+61 1300 865 547","other_name":"Toll Express"},{"slug":"toll-priority","name":"Toll Priority","phone":"+61 13 15 31","other_name":"Toll Group, Toll Priority"},{"slug":"trakpak","name":"TrakPak","phone":"+27 21 528 1000","other_name":"bpost international P2P Mailing Trak Pak"},{"slug":"transmission-nl","name":"TransMission","phone":"+31 (0)79 3438250","other_name":"mijnzending"},{"slug":"tuffnells","name":"Tuffnells Parcels Express","phone":"+44 114 256 1111","other_name":""},{"slug":"ubi-logistics","name":"UBI Logistics Australia","phone":"+61-2-9355 3888","other_name":""},{"slug":"uk-mail","name":"UK Mail","phone":"+44 8451 554 455","other_name":"Business Post Group"},{"slug":"ukrposhta","name":"UkrPoshta","phone":"+380 (0) 800-500-440","other_name":"Укрпошта"},{"slug":"ups","name":"UPS","phone":"+1 800 742 5877","other_name":"United Parcel Service"},{"slug":"ups-freight","name":"UPS Freight","phone":"+1 800-333-7400","other_name":"UPS LTL and Truckload"},{"slug":"ups-mi","name":"UPS Mail Innovations","phone":"+1 800-500-2224","other_name":"UPS MI"},{"slug":"usps","name":"USPS","phone":"+1 800-275-8777","other_name":"United States Postal Service"},{"slug":"viettelpost","name":"ViettelPost","phone":"+84 1900 8095","other_name":"Bưu chính Viettel"},{"slug":"vnpost","name":"Vietnam Post","phone":"+84 1900 54 54 81","other_name":"VNPost"},{"slug":"vnpost-ems","name":"Vietnam Post EMS","phone":"+84 1900 54 54 81","other_name":"VNPost EMS"},{"slug":"wahana","name":"Wahana","phone":"+62 2171355152","other_name":"Wahana Indonesia"},{"slug":"wedo","name":"WeDo Logistics","phone":"+86 (0779)2050300","other_name":"運德物流"},{"slug":"wishpost","name":"WishPost","phone":"","other_name":"Wish"},{"slug":"xdp-uk","name":"XDP Express","phone":"+44 843 1782222","other_name":"XDP UK"},{"slug":"xdp-uk-reference","name":"XDP Express Reference","phone":"+44 843 1782222","other_name":"XDP UK"},{"slug":"xend","name":"Xend Express","phone":"+63 2 806 9363","other_name":"Xend Business Solutions"},{"slug":"xpressbees","name":"XpressBees","phone":"+91 020 46608 105","other_name":"XpressBees logistics"},{"slug":"yanwen","name":"Yanwen","phone":"+86 010-64656790/91/92/93 -0","other_name":"燕文物流"},{"slug":"yodel","name":"Yodel Domestic","phone":"+44 844 453 7443","other_name":"Home Delivery Network Limited (HDNL)"},{"slug":"yodel-international","name":"Yodel International","phone":"+91 011-30212000","other_name":"Home Delivery Network, HDNL"},{"slug":"yrc","name":"YRC","phone":"+1 800-468-5739","other_name":"YRC Freight"},{"slug":"yto","name":"YTO Express","phone":"+86 95554","other_name":"u5706u901au901fu9012"},{"slug":"yundaex","name":"Yunda Express","phone":"+86 400-821-6789","other_name":"韵达快递"},{"slug":"yunexpress","name":"Yun Express","phone":"+86 400-0262-126","other_name":"云途物流"},{"slug":"zalora-7-eleven","name":"Zalora 7-Eleven","phone":"+65 3157 5555","other_name":"7-11"},{"slug":"zjs-express","name":"ZJS International","phone":"+86 4006789000","other_name":"宅急送快運"},{"slug":"zto","name":"ZTO Express","phone":"+86 95311","other_name":"中通快递"}]';
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ensogo订单发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id					eagle订单号 （必需）
	 * @param $tracking_provider 		发货方式 （必需）
	 * @param $tracking_number 			快递号
	 * @param $ship_note 				发货备注
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function shipEnsogoOrder($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
		//check required param
		if (empty($orderid)){
			return array(
				'success' => false,
				'message' => "order id 不能为空"
			);
		}
		
		if (empty($tracking_provider)){
			return array(
				'success' => false,
				'message' => "发货方式 不能为空"
			);
		}
		
		try {
			//make invoking journal
			//$journal_id = SysLogHelper::InvokeJrn_Create("Ensogo",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid],"edb\global");
				return array(
					'success' => false,
					'message' => 'ENSOGOOD010: Not found such internal order id: '.$orderid
				);
			}
			
			//step 2: insert the data into request as parameters
			$action_type = 'order_ship';
			$site_id = $order_model->saas_platform_user_id;
			$params = array(
				'order_id'=>$order_model->order_source_order_id , 
				'tracking_provider'=> $tracking_provider, 
				'tracking_number'=> $tracking_number , 
				'ship_note'=> $ship_note,
			);
			
			$rtn = EnsogoOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'ENSOGOOD500: : '.$e->getMessage(),
			);
		}
	}//end of shipEnsogoOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ensogo订单退货/退款/取消
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id					eagle订单号 （必需）
	 * @param $reason_code 				退货/退款/取消原因代号 （必需）
	 * @param $reason_note 				原因备注
	 +---------------------------------------------------------------------------------------------
	 * @description			reason_code 列表
	 * 					  code     meaning
	 * 						0	No More Inventory
	 * 						1	Unable to Ship
	 *						2	Customer Requested Refund
	 * 						3	Item Damaged
	 * 						7	Received Wrong Item
	 * 						8	Item does not Fit
	 * 						9	Arrived Late or Missing
	 * 						-1	Other, if none of the reasons above apply. reason_note is required if this is used as reason_code
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function cancelEnsogoOrder($orderid , $reason_code , $reason_note=''){
		//check required param
		if (empty($orderid)){
			return array(
				'success' => false,
				'message' => "order id 不能为空"
			);
		}
		
		$reason_code_active = array(0,1,2,3,7,8,9,-1);
		
		if (! in_array($reason_code, $reason_code_active)){
			return array(
				'success' => false,
				'message' => "退货/取消原因代号无效"
			);
		}
		
		if ($reason_code == -1){
			if (empty($reason_note)){
				return array(
					'success' => false,
					'message' => "当退货/取消原因代号等于-1时必须说明原因"
				);
			}
		}
		
		try {
			//make invoking journal
			//$journal_id = SysLogHelper::InvokeJrn_Create("Ensogo",__CLASS__, __FUNCTION__ , array($orderid , $reason_code , $reason_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background', "can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'ENSOGOOD010: Not found such internal order id: '.$orderid
				);
			}
			
			//step 2: insert the data into request as parameters
			$action_type = 'order_cancel';
			$site_id = $order_model->saas_platform_user_id;
			$params = array(
				'order_id'=>$order_model->order_source_order_id , 
				'reason_code'=> $reason_code, 
				'reason_note'=> $reason_note , 
			);
			
			$rtn = EnsogoOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage() ],"edb\global");
			return array(
				'success' => false,
				'message' => 'ENSOGOOD500: : '.$e->getMessage(),
			);
		}
	}//end of cancelEnsogoOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改订单ensogo订单发货信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $order_id					eagle订单号 （必需）
	 * @param $tracking_provider 		发货方式 （必需）
	 * @param $tracking_number 			快递号
	 * @param $ship_note 				发货备注
	 +--------------------------------------------------------------------------------------------- 
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function modifyEnsogoOrderShippedInfo($orderid , $tracking_provider , $tracking_number='' , $ship_note=''){
	//check required param
		if (empty($orderid)){
			return array(
				'success' => false,
				'message' => "order id 不能为空"
			);
		}
		
		if (empty($tracking_provider)){
			return array(
				'success' => false,
				'message' => "发货方式 不能为空"
			);
		}
		
		try {
			//make invoking journal
			//$journal_id = SysLogHelper::InvokeJrn_Create("Ensogo",__CLASS__, __FUNCTION__ , array($orderid , $tracking_provider , $tracking_number , $ship_note));
				
			//step 1: if not found such order, return  error
			$order_model = OdOrder::findOne($orderid);	
			if ($order_model == null) {
				Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background',"can not find the orderid:".$orderid ],"edb\global");
				return array(
					'success' => false,
					'message' => 'ENSOGOOD010: Not found such internal order id: '.$orderid
				);
			}
			
			//step 2: insert the data into request as parameters
			$action_type = 'order_modify';
			$site_id = $order_model->saas_platform_user_id;
			$params = array(
				'order_id'=>$order_model->order_source_order_id , 
				'tracking_provider'=> $tracking_provider, 
				'tracking_number'=> $tracking_number , 
				'ship_note'=> $ship_note,
			);
			
			$rtn = EnsogoOrderHelper::appendAddOrderOpToQueue($orderid, $action_type, $site_id, $params);
			
			//write the invoking result into journal before return
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, "Submitted Queue");	
			
			return $rtn;
		} catch (Exception $e) {
			Yii::error(['ensogo',__CLASS__,__FUNCTION__,'Background', "orderid:".$orderid." ".$e->getMessage()  ],"edb\global");
			return array(
				'success' => false,
				'message' => 'ENSOGOOD500: : '.$e->getMessage(),
			);
		}
		
	}//end of modifyEnsogoOrderShippedInfo
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取本地 ensogo 的courier 信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $key					获取courier 指定的字段 默认显示为 other_name（必需）
	 +---------------------------------------------------------------------------------------------
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getShippingCodeNameMap($key='both_name'){
		$CouriersList = json_decode(self::$EnsogoCouriersListStr,true);
		$result = [];
		foreach($CouriersList as $courier){
			if (array_key_exists($key , $courier)){
				$result[$courier['slug']] = $courier[$key];
			}else if ($key =="both_name"){
				$result[$courier['slug']] = $courier['name'] .(empty($courier['other_name'])?"":"(".$courier['other_name'].")");
			}
			
		}
		
		return $result;
	}
	
	
	/* 返回该 platform 允许的 默认 carrier name
	 * yzq @ 2015-7-9
	 * */
	public static function getDefaultShippingCode(){
		return 'OTHER';
	}
}
?>