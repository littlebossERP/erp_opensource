<?php 
	$warehouse = [
		'1000001'=>'澳洲仓',
		'1000089'=>'德国仓',
		'1000008'=>'美国仓(USWC)',
		'1000069'=>'英国仓(UK)',
//		'1000129'=>'美国仓(USKY)', //delete 2016.5.16
		'1005189'=>'英国仓(UKMA)',
        '1022191'=>'英国GF仓(UKGF)',
        '1008190'=>'美国东岸新仓(USKYN)',
        '1018192'=>'美国南岸仓',
		'1051191'=>'USKY2 Warehouse',
		'1054191'=>'美国仓(USWC2)',
	];

	$warehouseService = [
		'1000001'=>[
			'1000021'=>'AU Post Parcel Post(With Registration)',
			'1000023'=>'AU Post Large Letter (No Registration)',
			'1000024'=>'AU Post Parcel Post (No Registration)',
			'1000019'=>'AU Post Large Letter(With Registration)',
			'1000020'=>'AU Post eParcel',
			'1000188'=>'AU Post Small Letter(With Registration)',
			'1000189'=>'AU Post Small Letter (No Registration)',
			'1010379'=>'Toll IPEC',
			'1000190'=>'AU Post Express',
			'1010399'=>'Toll Priority',
            '1034663'=>'客户自提服务-AU',//add 2016.8.4
		],
		'1000089'=>[
			'1000228'=>'DE Post Non-tracked Large Letter Service (2-4 business days)',
			'1000229'=>'DE Post Non-tracked International (Europe) Service (2-8 business days)',
			'1000308'=>'DE Post Non-tracked Small Letter (1-2 business days)',
			'1010309'=>'DHL Paket International Service (2-8 business days)',
			'1000211'=>'DPD Domestic Normal Parcels (1-2 business days)',
			'1000213'=>'DPD International Parcels (2-8 business days)',
			'1000212'=>'DPD Domestic Small Parcels (1-2 business days)',
			'1010314'=>'DE Post International (Europe) Service (2-8 business days)',
			'1010308'=>'DHL Paket Service (1-2 business days)',
            '1016664'=>'DHL Freight (Europe) Service (2-10 business days)', //add 2016.8.4
            '1016665'=>'DHL Freight Domestic Service (1-2 business days)',  //add 2016.8.4
            '1010359'=>'客户自提服务DE',  //add 2016.8.4
		],

		'1000008'=>[
			'1000090'=>'UPS SUREPOST SERVICE',
			'1000111'=>'UPS GROUND SERVICE',
			'1000112'=>'UPS 3RD DAY SELECT-RESIDENTIAL SERVICE',
			'1000113'=>'UPS NEXT DAY AIR SAVER SERVICE',
			'1000252'=>'USPS First Class Mail Tracked Service',
			'1000254'=>'USPS Priority Mail Parcels Tracked Service',
			'1000251'=>'USPS First Class Mail Tracked International Service',
			'1000253'=>'USPS Priority Mail Parcels Tracked International Service',
			'1010521'=>'UPS 3rd Day Select-Residential Sign Service',
			'1010519'=>'UPS Ground Sign Service',
			'1010520'=>'UPS Next Day Air Saver Sign Service',
            '1000089'=>'DGM EXPEDITED SERVICE', //add 2016.8.4
            '1000088'=>'DGM GROUND SERVICE',    //add 2016.8.4
            '1025659'=>'DHL Express International Service', //add 2016.8.4
            '1017659'=>'DHL Global Forwarding Service', //add 2016.8.4
            '1011659'=>'UPS Mail Innovation Service',   //add 2016.8.4
            '1026660'=>'USPS Priority Mail Parcels Tracked Service-for ebay',   //add 2016.8.4
            '1034668'=>'客户自提服务-USWC',   //add 2016.8.4
		],

		'1000069'=>[
			'1000175'=>'Royal Mail 1st class tracked',
			'1000177'=>'Royal Mail 2nd class tracked',
			'1000176'=>'Royal Mail 1st class tracked & signed',
			'1000178'=>'Royal Mail 2nd class tracked & signed',
//			'1000170'=>'bPost DSA (Large letter) Untracked Service',    //delete 2016.8.4
			'1000171'=>'bPost International Parcels Tracked',
			'1000173'=>'bPost International Parcels Untracked（Letters）',
			'1000172'=>'bPost International Parcels Untracked（Flats）',
			'1000174'=>'bPost International Parcels Untracked（Packets）',
			'1000268'=>'bPost DSA (Small letter) Untracked Service',
			'1000328'=>'Royal Mail 48(Untracked)',
			'1000388'=>'UPS Freight Service',
			'1010319'=>'Royal Mail Large Letter 24 Untracked Service',
			'1000149'=>'Yodel Home 72 Service',
			'1000148'=>'Yodel Home 24 Service ',
			'1010419'=>'DPD International Air Express Service',
			'1010439'=>'DPD International Normal Parcels Service',
			'1010459'=>'DPD International Small Parcels Service',
//			'1010479'=>'DPD Normal Parcels Service',    //delete 2016.8.4
			'1010499'=>'Royal Mail Parcels 24 Untracked Service',
			'1010501'=>'Royal Mail Parcels 48 Untracked Service',
			'1010502'=>'Royal Mail Large Letter 24 Untracked & Signed Service',
			'1010503'=>'Royal Mail Large Letter 48 Untracked & Signed Service',
			'1010619'=>'XDP 2 Man Delivery Service',
			'1010620'=>'XDP 1 Man Parcel Service',
            '1010639'=>'Yodel Home Mini',
            '1010694'=>'P2P International Parcels Tracked Service',
            '1010695'=>'P2P International Parcels Untracked(Flats)',
            '1010696'=>'P2P International Parcels Untracked(Packets)',
            '1010697'=>'Whistl Large Letter Untracked Service',
            '1014662'=>'DPD Normal Parcels Service (Length<=1m)',
            '1014668'=>'DPD Normal Parcels Service (1m<Length<=1.5m)',
            '1014660'=>'DPD Large Parcels Service (1.5m<Length<=2m)',   //add 2016.8.4
            '1034669'=>'Parcel force 24 tracked & Signed',  //add 2016.8.4
            '1034670'=>'Parcel force 48 tracked & Signed',  //add 2016.8.4
            '1034671'=>'Parcel force international tracked & Signed',   //add 2016.8.4

		],

//        //delete 2016.8.4
//		'1000129'=>[
//			'1010543'=>'USPS First Class Mail Tracked Service',
//			'1010559'=>'USPS Priority Mail Parcels Tracked Service',
//			'1010540'=>'UPS GROUND SERVICE',
//			'1010542'=>'UPS 3RD DAY SELECT-RESIDENTIAL SERVICE',
//			'1010541'=>'UPS Next Day Air Saver Service',
//			'1010544'=>'UPS SUREPOST SERVICE',
//		],
		
		'1005189'=>[
			'1018660'=>'Royal Mail 1st class tracked & signed--UKMA',
			'1018659'=>'Royal Mail 1st class tracked-UKMA',
			'1018661'=>'Royal Mail 2nd class tracked--UKMA',
			'1018662'=>'Royal Mail 2nd class tracked & signed--UKMA',
			'1018663'=>'Royal Mail 48(Untracked)--UKMA',
			'1018664'=>'Royal Mail Large Letter 24 Untracked & Signed Service--UKMA',
			'1018665'=>'Royal Mail Large Letter 24 Untracked Service--UKMA',
			'1018666'=>'Royal Mail Large Letter 48 Untracked & Signed Service--UKMA',
			'1018667'=>'Royal Mail Parcels 24 Untracked Service--UKMA',
			'1018668'=>'Royal Mail Parcels 48 Untracked Service--UKMA',
			'1018669'=>'DPD International Air Express Service--UKMA',
			'1018670'=>'DPD International Normal Parcels Service--UKMA',
			'1018671'=>'DPD International Small Parcels Service--UKMA',
			'1018672'=>'DPD Large Parcels Service (1.5m<Length<=2m)--UKMA',
			'1018673'=>'DPD Normal Parcels Service (1m<Length<=1.5m)--UKMA',
			'1018674'=>'DPD Normal Parcels Service (Length<=1m)--UKMA',
			'1018675'=>'P2P International Parcels Tracked Service--UKMA',
			'1018676'=>'P2P International Parcels Untracked(Flats)--UKMA',
			'1018682'=>'Yodel Home 24 Service--UKMA',
			'1018677'=>'P2P International Parcels Untracked(Packets)--UKMA',
			'1018678'=>'UPS Freight Service--UKMA',
			'1018679'=>'Whistl Large Letter Untracked Service--UKMA',
			'1018680'=>'XDP 1 Man Parcel Service--UKMA',
			'1018681'=>'XDP 2 Man Delivery Service--UKMA',
			'1018683'=>'Yodel Home Mini--UKMA',
			'1018684'=>'Yodel Home 72 Service--UKMA',
            '1038663'=>'Panther 2 Man Standard Service',   //add 2016.8.4
            '1034672'=>'Parcel force 24 tracked  & Signed-UKMA',   //add 2016.8.4
            '1034674'=>'Parcel force 48 tracked  & Signed-UKMA',   //add 2016.8.4
            '1034673'=>'Parcel force international tracked & Signed-UKMA',   //add 2016.8.4
            '1034666'=>'客户自提服务-UKMA',   //add 2016.8.4
		],

        '1022191'=>[
            '1056661'=>'DPD International Normal Parcels Service-UKGF',
            '1056662'=>'DPD International Small Parcels Service-UKGF',
            '1056664'=>'DPD Normal Parcels Service (Length<=1m)-UKGF',
            '1056665'=>'P2P International Parcels Tracked Service-UKGF',
            '1056666'=>'P2P International Parcels Untracked(Flats)-UKGF',
            '1056667'=>'P2P International Parcels Untracked(Packets)-UKGF',
            '1056668'=>'Parcel force 24 tracked & Signed-UKGF',
            '1056671'=>'Parcel force 48L tracked & Signed-UKGF',
            '1056669'=>'Parcel force 48 tracked & Signed-UKGF',
            '1056670'=>'Parcel force international tracked & Signed-UKGF',
            '1054662'=>'Royal Mail 1st class tracked & signed-UKGF',
            '1054661'=>'Royal Mail 1st class tracked-UKGF',
            '1055662'=>'Royal Mail 2nd class tracked & signed-UKGF',
            '1055661'=>'Royal Mail 2nd class tracked-UKGF',
            '1055663'=>'Royal Mail 48(Untracked)-UKGF',
            '1055665'=>'Royal Mail Large Letter 24 Untracked & Signed Service-UKGF',
            '1055664'=>'Royal Mail Large Letter 24 Untracked Service-UKGF',
            '1055668'=>'Royal Mail Large Letter 48 Untracked & Signed Service-UKGF',
            '1055666'=>'Royal Mail Parcels 24 Untracked Service-UKGF',
            '1055667'=>'Royal Mail Parcels 48 Untracked Service-UKGF',
            '1055669'=>'Royal Mail Small Letter 48 Untracked Service-UKGF',
            '1057670'=>'Scrap Service-UKGF',
            '1057672'=>'Self Pick Service-UKGF',
            '1056672'=>'UPS Freight Service-UKGF',
            '1057664'=>'XDP 1 Man Parcel Service-UKGF',
            '1057665'=>'XDP 2 Man Delivery Service-UKGF',
            '1057661'=>'Yodel Home 24 Service-UKGF'	,
            '1057662'=>'Yodel Home 72 Service-UKGF',
            '1057663'=>'Yodel Home Mini-UKGF'
        ],

        '1008190'=>[
            '1024660'=>'UPS - 3 Day Select (Express 3 Business Days)-Residential Sign-USKYN',
			'1020659'=>'UPS - 3 Day Select (Express 3 Business Days)-Residential-USKYN',
			'1020660'=>'UPS - Ground (Standard 1-5 Business Days)-USKYN',
			'1023659'=>'UPS - Ground (Standard 1-5 Business Days)-Sign-USKYN',
			'1020661'=>'UPS - Next Day Air Saver (One-Day Service)-USKYN',
			'1024659'=>'UPS - Next Day Air Saver (One-Day Service)-Sign-USKYN',
			'1020662'=>'UPS - Surepost (Economy 1-6 Business Days)-USKYN',
			'1020663'=>'USPS - First Class Package (Standard 3-5 Business Days)-USKYN',
			'1047661'=>'USPS - First Class Package International (Standard 6-11 Business Days)-USKYN',
			'1020664'=>'USPS - Priority Mail (Express 2-3 Business Days)-USKYN',
			'1047662'=>'USPS - Priority Mail International (Standard 6-11 Business Days)-USKYN',
			'1036660'=>'USPS - Priority Mail (Express 2-3 Business Days)-eBay-USKYN',
			'2000022'=>'Self Pick Service',
			'1026659'=>'Test Last-mile Service-USKYN',
			'1050661'=>'DHL - Global Forwarding (Standard 5-7 Business Days)-USKYN',
        ],

        '1018192'=>[
            '2000004'=>'UPS Ground Service-USTX',
            '2000005'=>'UPS Ground Service Signed-USTX',
            '2000006'=>'UPS 3rd Day Select-Residential Service-USTX',
            '2000007'=>'UPS 3rd Day Select-Residential Service Signed-USTX',
            '2000008'=>'UPS Next Day Air Saver Service-USTX',
            '2000009'=>'UPS Next Day Air Saver Service Signed-USTX',
            '2000010'=>'UPS SUREPOST SERVICE-USTX',
			'2000011'=>'USPS - First Class Package (Standard 3-5 Business Days)-USTX',
			'2000012'=>'USPS - Priority Mail (Express 2-3 Business Days)-USTX',
			'2000013'=>'USPS - First Class Package International (Standard 6-11 Business Days)-USTX',
			'2000014'=>'USPS - Priority Mail International (Standard 6-11 Business Days)-USTX',
			
        ],
		
		'1051191'=>[
            '1000368'=>'Test Last-mile Service',
			'1024660'=>'UPS - 3 Day Select (Express 3 Business Days)-Residential Sign-USKYN',
			'1020659'=>'UPS - 3 Day Select (Express 3 Business Days)-Residential-USKYN',
			'1020660'=>'UPS - Ground (Standard 1-5 Business Days)-USKYN',
			'1023659'=>'UPS - Ground (Standard 1-5 Business Days)-Sign-USKYN',
			'1020661'=>'UPS - Next Day Air Saver (One-Day Service)-USKYN',
			'1024659'=>'UPS - Next Day Air Saver (One-Day Service)-Sign-USKYN',
			'1020662'=>'UPS - Surepost (Economy 1-6 Business Days)-USKYN',
			'1020663'=>'USPS - First Class Package (Standard 3-5 Business Days)-USKYN',
			'1047661'=>'USPS - First Class Package International (Standard 6-11 Business Days)-USKYN',
			'1020664'=>'USPS - Priority Mail (Express 2-3 Business Days)-USKYN',
			'1047662'=>'USPS - Priority Mail International (Standard 6-11 Business Days)-USKYN',
			'1036660'=>'USPS - Priority Mail (Express 2-3 Business Days)-eBay-USKYN',
			'2000022'=>'Self Pick Service',
			'1026659'=>'Test Last-mile Service-USKYN',
			
        ],
		
		'1054191'=>[
            '1000368'=>'Test Last-mile Service',
			'1000111'=>'UPS - Ground (Standard 1-5 Business Days)-USWC',
			'1010519'=>'UPS - Ground (Standard 1-5 Business Days)-Sign-USWC',
			'1000113'=>'UPS - Next Day Air Saver (One-Day Service)-USWC',
			'1010520'=>'UPS - Next Day Air Saver (One-Day Service)-Sign-USWC',
			'1000112'=>'UPS - 3 Day Select (Express 3 Business Days)-Residential-USWC',
			'1010521'=>'UPS - 3 Day Select (Express 3 Business Days)-Residential Sign-USWC',
			'1000090'=>'UPS - Surepost (Economy 1-6 Business Days)-USWC',
			'1000254'=>'USPS - Priority Mail (Express 2-3 Business Days)-USWC',
			'1000252'=>'USPS - First Class Package (Standard 3-5 Business Days)-USWC',
			'1000251'=>'USPS - First Class Package International (Standard 6-11 Business Days)-USWC',
			'1000253'=>'USPS - Priority Mail International (Standard 6-11 Business Days)-USWC',
			'1026660'=>'USPS - Priority Mail (Express 2-3 Business Days)-eBay-USWC',
			'1025659'=>'DHL - Express Worldwide (Express 3-5 Business Days)-USWC',
			'1017659'=>'DHL - Global Forwarding (Standard 5-7 Business Days)-USWC',
			
        ],
		
	];

 ?>

