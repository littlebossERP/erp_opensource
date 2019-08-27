<?php
	$warehouse = [
		"USEA"=>"美东仓库",
		"USWE"=>"美西仓库",
		"AU"=>"澳洲仓库",
		"DE"=>"德国仓库",
// 		"CNTC"=>"深圳头程仓",
		"UK"=>"英国仓库",
// 		"HK"=>"香港仓库",
		"CZ"=>"捷克仓库",
	];
	
	$warehouseService = [
		'USEA'=>[
// 			"USPS-LWPARCEL"=>"USEA-USPS-LWParcel(美东-USPS-小包)",
// 			"USPS-BPARCEL"=>"USEA-USPS-BParcel(美东-USPS-大包)",
// 			"FEDEX-SMALLPARCEL"=>"USEA-FEDEX-SMALLPARCEL(美东-FEDEX-小包)",
// 			"FEDEX-LARGEPARCEL"=>"USEA-FEDEX-LARGEPARCEL(美东-FEDEX-大包)",
// 			"OCEAN"=>"Ocean Shipping(海运-美东)",
			"FEDEX-GROUND"=>"FEDEX-GROUND(FEDEX-大货渠道-商业地址)",
			"FEDEX-HOMEDELIVERY"=>"FEDEX-HOMEDELIVERY(FEDEX-大货渠道-住宅地址)",
// 			"TUIJIAN"=>"Return_OR(海外退件_原件)",
// 			"VR"=>"VR(虚拟渠道)",
			"IPA-INT-ECONOMIC"=>"IPA-INT-ECONOMIC(国际经济小包)",   //2018-01-25
			"FEDEX-SM"=>"FEDEX-SM(美国FEDEX小包)",   
			"FEDEX-LP"=>"FEDEX-LP(美国FEDEX大包)",
			"USPS-FIRST-CLASS"=>"USPS-First-Class(美国USPS小包)",
			"USPS-PRIORITY"=>"USPS-PRIORITY(美国USPS大包)",
			"FEDEX_GROU"=>"fedex_GROU(联邦一票多箱)",
			"FEDEX-3-DAY"=>"FedEx-3-Day(联邦3日达)",
			"FEDEX-IE-EXPORT"=>"FEDEX-IE-EXPORT(联邦经济国际快递)",
		],
		
		'USWE'=>[
// 			"USWE-FEDEX-LPARCEL"=>"USWE-FEDEX-LPARCEL(美西-FEDEX-大包)",
// 			"USWE-FEDEX-SPARCEL"=>"USWE-FEDEX-SPARCEL(美西-FEDEX-小包)",
// 			"USWE-USPS-LWPARCEL"=>"USWE-USPS-LWParcel(美西-USPS-小包)",
// 			"USWE-USPS-BPARCEL"=>"USWE-USPS-BParcel(美西-USPS-大包)",
			"FEDEX-GROUND"=>"FEDEX-GROUND(FEDEX-大货渠道-商业地址)",
			"FEDEX-HOMEDELIVERY"=>"FEDEX-HOMEDELIVERY(FEDEX-大货渠道-住宅地址)",			
// 			"TUIJIAN"=>"Return_OR(海外退件_原件)",
// 			"VR"=>"VR(虚拟渠道)",
			"UPS-GROUND-USWE"=>"UPS-GROUND-USWE(美西UPS重货渠道)", //2018-01-25
			"FEDEX-SM"=>"FEDEX-SM(美国FEDEX小包)",
			"FEDEX-LP"=>"FEDEX-LP(美国FEDEX大包)",
			"USPS-FIRST-CLASS"=>"USPS-First-Class(美国USPS小包)",
			"USPS-PRIORITY"=>"USPS-PRIORITY(美国USPS大包)",
			"FEDEX_GROU"=>"fedex_GROU(联邦一票多箱)",
			"FEDEX-3-DAY"=>"FedEx-3-Day(联邦3日达)",
			"FEDEX-IE-EXPORT"=>"FEDEX-IE-EXPORT(联邦经济国际快递)",			
		],
		
		'AU'=>[
			"AUSPOST-LL"=>"AUSPOST LargeLetter(澳洲邮政大信封)",
// 			"AUPOST-P-S-E"=>"AuPost-eParcel-SmallParcel-E(澳邮包裹-小包)",
// 			"AUPOST-P-L-E"=>"AuPost-eParcel-LargeParcel-E(澳邮包裹-大包)",
// 			"AUPOST-PE-S"=>"AUPOST-PE-S(邮政包裹-小包(快件))",
// 			"AUPOST-PE-L"=>"AUPOST-PE-L(邮政包裹-大包(快件))",
			"AUSPPST-P-S-E"=>"AUSPPST-P-S-E(澳洲邮政小包)",    //2018-01-25
			"AUSPPST-P-L-E"=>"AUSPPST-P-L-E(澳洲邮政大包)",
		],
		
		'UK'=>[
// 			"ROY-PARCEL-48H"=>"ROY-Parcel-48H(ROY-48H包裹)",
// // 			"ROY-PARCEL-INT"=>"ROY-Parcel-Int(ROY-国际包裹)",
// 			"V-UK-YODEL"=>"V-UK-YODEL",
// 			"UK-HERMES"=>"UK-HERMES(英国hermes)",
// 			"UK-HERMES-L"=>"UK-HERMES-L(英国hermes超大渠道)",
// 			"XDP-GB"=>"XDP-GB(529专属渠道）",
// 			"HERMES-EU"=>"HERMES-EU(HERMES-欧洲件)",		
			"ROY-PARCEL-48H"=>"ROY-PARCEL-48H(皇家邮政48小时包裹)",   //2018-01-25
			"V-UK-YODEL"=>"V-UK-YODEL(YODEL签名)",
			"HERMES-EU"=>"HERMES-EU(HERMES欧洲包裹)",
			"UK-HERMES"=>"UK-HERMES(英国hermes(0-15kg))",
			"UK-HERMES-L"=>"UK-HERMES-L(英国hermes(15-30kg))",
			"XDP-EP"=>"XDP-EP(XDP-EP(20-50kg))",
			"XDP-GROUPS"=>"XDP-GROUPS(XDP一票多箱(50-350kg))",
			"XDP-ECONOMY-GROUPS"=>"XDP-ECONOMY-GROUPS(XDP一票多箱经济(20-250kg))",
			"ROY-LLETTER-UNTR"=>"ROY-LLETTER-UNTR(皇家邮政平邮信封)",
			"UPS-STANDARD"=>"UPS-STANDARD(UPS-standard欧洲)",
			"UPS-STANDARD-UK"=>"UPS-STANDARD-UK(UPS-standard-uk英国本土)",
			"YODEL-PACKET"=>"YODEL-PACKET(YODEL小包)",
		],
		
		'DE'=>[
// 			"DEPOST-SLETTER"=>"DEPOST-SLETTER(德国邮政小信封)",
// 			"DEPOST-LLETTER"=>"DEPOST-LLETTER(德国邮政大信封)",
			"DHL-DE-PACKET"=>"DHL-DE-Packet(DHL德国标准包裹)",
// 			"DHL-DE-ECOPACKET"=>"DHL-DE-EcoPacket(DHL德国经济包裹)",
			"DHL-DE-EUPACKET"=>"DHL-DE-EuPacket(DHL德国欧洲包裹)",
// 			"HERMES-DE-PACKET"=>"HERMES-DE-PACKET(HERMES德国包裹)",
		],
		
		'CZ'=>[
			"DHL-PACKET-CZ"=>"DHL-PACKET-CZ(捷克DHL标准包裹)",
			"DHL-EUPACKET-CZ"=>"DHL-EUPACKET-CZ(捷克DHL欧洲包裹)",
			"DHL-SMALL_PACKET"=>"DHL-SMALL_PACKET(DHL德国小包)",
		],
	];

?>