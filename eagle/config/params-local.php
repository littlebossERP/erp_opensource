<?php
return [
   "menuFoldThreshold"=>3,// 一级菜单显示最多项。当一级菜单数量（算上home）大于这个数值n，就需要把超出的菜单和第n个菜单，折叠到1个菜单项（如：others）中
   "menu"=>[       
			"ebay刊登助手"=>[  //一级菜单
			        "matchUrl"=>["/ebay/listing"],  //如果当前url匹配上matchUrl,会标示成active。			        
			        "app"=>"ebay_listing",//说明这个app的一级菜单，并且只有该app开启才显示
			        "priority"=>"3",//priority优先级，越大优先级越高，位置越靠前，默认为0
			        "url"=>"#", // 默认#，表示不可点击的菜单			        
					"subMenu"=>[				
						"刊登范本"=>[ 	//二级菜单。属性跟一级菜单一样
						     "url"=>"#",
							"subMenu"=>[
							    "风格模板"=>[],  	//三级菜单。属性跟一级菜单一样
							     "范本列表"=>[]	]
					     ]						
					]
			],
			"添加app"=>["priority"=>"100","url"=>"/app/app/list","matchUrl"=>["/app/app/list"]],			
			"仓库"=>["matchUrl"=>["/inventory"],"url"=>"/inventory/inventory/"],
			"商品"=>["priority"=>"1","matchUrl"=>["/catalog"],"url"=>"/catalog/product/list"],
			"采购"=>["priority"=>"1","url"=>"/purchase/purchase/","matchUrl"=>["/purchase"]],
			"物流查询助手"=>["priority"=>"10","url"=>"/tracking/tracking/index","app"=>"tracking","matchUrl"=>["/tracking"]],
		],  //end for menu
		
       "topmenu"=>[
           "订单管理"=>["url"=>"#","permission"=>"order","priority"=>"1","menuKey"=>"order","subMenu"=>
               		[
               		"eBay订单"=>["priority"=>"11","platform"=>"ebay","url"=>"/order/ebay-order/list?menu_select=all","matchUrl"=>[["order","ebay-order"]]],
                 	"AliExpress订单"=>["priority"=>"11","platform"=>"aliexpress","url"=>"/order/aliexpressorder/aliexpresslist?menu_select=all","matchUrl"=>[["order","aliexpressorder"]]],
            		"Amazon订单"=>["priority"=>"11","platform"=>"amazon","url"=>"/order/amazon-order/list?menu_select=all","matchUrl"=>[["order","amazon-order"]]],
            		"Wish订单"=>["priority"=>"11","platform"=>"wish","url"=>"/order/wish-order/list?menu_select=all","matchUrl"=>[["order","wish-order"]]],
            		"DHgate订单"=>["priority"=>"11","platform"=>"dhgate","url"=>"/order/dhgate-order/list?menu_select=all","matchUrl"=>[["order","dhgate-order"]]],
            		"Lazada订单"=>["priority"=>"11","platform"=>"lazada","url"=>"/order/lazada-order/list?menu_select=all","matchUrl"=>[["order","lazada-order"]]],
            		"Linio订单"=>["priority"=>"11","platform"=>"linio","url"=>"/order/linio-order/list?menu_select=all","matchUrl"=>[["order","linio-order"]]],
            		"Jumia订单"=>["priority"=>"11","platform"=>"jumia","url"=>"/order/jumia-order/list?menu_select=all","matchUrl"=>[["order","jumia-order"]]],
            		"Cdiscount订单"=>["priority"=>"11","platform"=>"cdiscount","url"=>"/order/cdiscount-order/list?menu_select=all","matchUrl"=>[["order","cdiscount-order"]]],
					"PM订单"=>["priority"=>"11","platform"=>"priceminister","url"=>"/order/priceminister-order/list?menu_select=all","matchUrl"=>[["order","priceminister-order"]]],
					"Newegg订单"=>["priority"=>"11","platform"=>"newegg","url"=>"/order/newegg-order/list?menu_select=all","matchUrl"=>[["order","newegg-order"]]],
					"Shopee订单"=>["priority"=>"11","platform"=>"shopee","url"=>"/order/shopee-order/list?menu_select=all","matchUrl"=>[["order","shopee-order"]]],	
					
					"bonanza订单"=>["priority"=>"11","platform"=>"bonanza","url"=>"/order/bonanza-order/list?menu_select=all","matchUrl"=>[["order","bonanza-order"]]],
					"自定义店铺订单"=>["priority"=>"11","platform"=>"customized","url"=>"/order/customized-order/list","matchUrl"=>[["order","customized-order"]]],
					
            		]
                						],
										
		    						
				
				"物流发货"=>["priority"=>"10","permission"=>"delivery","url"=>"/delivery/order/listnodistributionwarehouse","matchUrl"=>[["delivery"]]],
				"仓库"=>[ "url"=>"#","menuKey"=>"inventory","priority"=>"1","subMenu"=>
				      [
				            "仓库"=>["priority"=>"1","permission"=>"inventory","url"=>"/inventory/inventory/","matchUrl"=>[["inventory","inventory"]]],
							"海外仓"=>["priority"=>"1","permission"=>"inventory","url"=>"/inventory/oversea-warehouse/","matchUrl"=>[["inventory","oversea-warehouse"]]],
							"FBA库存"=>["priority"=>"1","permission"=>"inventory","url"=>"/inventory/fba-warehouse/","matchUrl"=>[["inventory","fba-warehouse"]]],
					
					   ]
					],
				
				"商品"=>["url"=>"#","priority"=>"1","subMenu"=>
	                                [
	                                	"商品管理"=>["priority"=>"1","permission"=>"catalog","url"=>"/catalog/product/list","matchUrl"=>[["catalog","product"]]],
		                                "商品配对"=>["priority"=>"2","permission"=>"catalog","url"=>"/catalog/matching/index","matchUrl"=>[["catalog","matching"]]],
	                                ]
                 ],
				
				"采购"=>["priority"=>"1","permission"=>"purchase","url"=>"/purchase/purchase/","matchUrl"=>[["purchase"]]],
			
//                "刊登管理"=>["url"=>"#","menuKey"=>"listing","priority"=>"1","subMenu"=>
//                    [
// //                         "店铺搬家"=>["url"=>"/listing/listing-draft/wish-lists","matchUrl"=>[["listing","listing-draft"]]],
// //                         "ebay刊登"=>["platform"=>"ebay","url"=>"/listing/ebayitem/list","matchUrl"=>[["listing","ebayitem"],["listing","ebaymuban"],["listing","additemset"]]],
//                         "lazada刊登"=>["platform"=>"lazada","url"=>"/listing/lazada-listing/publish","matchUrl"=>[["listing","lazada-listing"]]],
//                         "linio刊登"=>["platform"=>"linio","url"=>"/listing/linio-listing/publish","matchUrl"=>[["listing","linio-listing"]]],
// //                         "wish刊登"=>["platform"=>"wish","url"=>"/listing/wish/wish-list","matchUrl"=>[["listing","wish"]]],
//                         "jumia刊登"=>["platform"=>"jumia","url"=>"/listing/jumia-listing/publish","matchUrl"=>[["listing","jumia-listing"]]],
// //                         "AliExpress刊登"=>["platform"=>"aliexpress","url"=>"/listing/aliexpress/pending","matchUrl"=>[["listing","aliexpress"]]],
//                    ]
//                ],
	     
	         
	        "独立应用"=>["url"=>"#","priority"=>"1","subMenu"=>
	              [ 
//               		"客服管理"=>["url"=>"/message/all-customer/customer-list","permission"=>"message","matchUrl"=>[["message"]]],
//                		"AliExpress催款助手"=>["priority"=>"9","url"=>"/assistant/rule/list","matchUrl"=>[["assistant"]]],
       			//	"AliExpress好评助手"=>["priority"=>"9","url"=>"/comment/comment/rule-v2","matchUrl"=>[["comment"]]],
// 	                      "物流跟踪助手"=>["priority"=>"10","permission"=>"tracking","url"=>"/tracking/tracking/index","matchUrl"=>[["tracking"]]],
// 	                      "Cdiscount跟卖终结者"=>["priority"=>"10","platform"=>"cdiscount","url"=>"/listing/cdiscount/index","matchUrl"=>[["listing","cdiscount"]]],
					  "图片库"=>["priority"=>"10","url"=>"/util/image/show-library","matchUrl"=>[["util","image"]]]	
                    ]
             	],
			
		    "设置"=>["url"=>"#","priority"=>"1","subMenu"=>
	              [ 
				    "选择运输服务"=>["url"=>"/configuration/carrierconfig/index","matchUrl"=>[["configuration","carrierconfig"]]],
					"常用报关信息"=>["url"=>"/configuration/carrierconfig/common-declared-info#show_div_undefined","matchUrl"=>[["configuration","carrierconfig","common-declared-info"]]],			
					"仓库设置"=>["url"=>"/configuration/warehouseconfig/self-warehouse-list","matchUrl"=>[["configuration","warehouseconfig"]]],					
              		"订单设置"=>["url"=>"/configuration/elseconfig/excel-model-list","matchUrl"=>[["configuration","elseconfig"]]],
					"发货设置"=>["url"=>"/configuration/deliveryconfig/customsettings","matchUrl"=>[["configuration","deliveryconfig"]]],
					"商品设置"=>["url"=>"/configuration/productconfig/index","matchUrl"=>[["configuration","productconfig"]]],
					"全局设置"=>["url"=>"/configuration/carrierconfig/searchlist","matchUrl"=>[["configuration","carrierconfig"]]],
				   ]
			 	],
				
			"统计"=>["url"=>"/statistics/profit/index","priority"=>"1","matchUrl"=>[["statistics","profit"]]],	
			
             	
             ]  //end for menu
                
];
