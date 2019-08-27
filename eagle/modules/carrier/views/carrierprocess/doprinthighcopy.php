<?php 
use eagle\models\carrier\CrTemplate;
use eagle\modules\carrier\apihelpers\PrintApiHelper;
use yii\helpers\Url;
use eagle\models\CrCarrierTemplate;

?>
<link rel="stylesheet" href="/css/carrier/uielement.min.css" />
<link rel="stylesheet" href="/css/carrier/custom.css" />
<link rel="stylesheet" href="/css/carrier/print.css" />
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jquery.min.js"></script>
<style type="text/css">
<!--
body { margin: 0px; padding:0;}
.noprint { display: none}
ul{padding:0px;	 margin:0px; }
li{ list-style-type:none;}
body .label-content {
	background-color: transparent;
	border-radius: 0;
	-webkit-box-shadow: none;
	-moz-box-shadow: none;
	box-shadow: none;
}
body .one-label {
 	page-break-after: always;  
 	page-break-inside: avoid;  
}
.label-content .view-mask, .label-content .custom-area, .label-content .custom-drop .dropitem .line-handle, .label-content .custom-drop .dropitem .ui-resizable-handle{ display:none;}
.label-content .custom-drop{ border:1px solid #fff;}
.label-content .custom-drop .dropitem{ cursor:default;}
.label-content .custom-drop .dropitem:hover{ color:inherit; background-color:transparent;}
-->
</style>


<?php
        if($carrierConfig['label_paper_size']['val'] == '210x297'){//A4
            $printData_str = '<div style="width:200mm">';
        }else if($carrierConfig['label_paper_size']['val'] == '100x100'){//100x100
            $printData_str = '';
        }else if($carrierConfig['label_paper_size']['val'] == 'customSize'){//自定大小标签相当于不限定标签大小用不像A4纸那样拼接,情况就像10*10一样
        	$printData_str = '';
        	$carrierConfig['label_paper_size']['val'] = '100x100';
        }
        foreach ($data as $order){
            if($carrierConfig['label_paper_size']['val'] == '210x297'){//A4
                $count = 0;
            }
        	foreach ($print_params['lable_type'] as $print_paramkey => $print_param){
				$template = $templateArr[$print_paramkey];
        		
        		$printData = PrintApiHelper::getHighCopyPrintData($template, $shippingService, $order, $carrierConfig);
        		
        		if(isset($printData['error'])){
        			echo $printData['msg'];
        			return false;
        		}
        		
        		if($carrierConfig['label_paper_size']['val'] == '210x297'){
        		    $count = $count + 1;
        		    $printData_str .= '<div style="float:left;">'.$printData.'</div>';
        		}else if($carrierConfig['label_paper_size']['val'] == '100x100'){
        		    $printData_str .= '<div class="one-label">'.$printData.'</div>';
        		}
        // 		echo $printData;
        	}
        	if($carrierConfig['label_paper_size']['val'] == '210x297'){
        	    if($count == 3){ 
        	        $printData_str .= '<div style="width:100mm;height:100mm;float:left;"></div>';
        	    }
        	}
        }
        if($carrierConfig['label_paper_size']['val'] == '210x297'){
            $printData_str .= '</div>';
        }
        echo $printData_str;
?>
<!-- 
<script type="text/javascript">
function trim(ss)
{
	return ss.replace(/(^\s*)|(\s*$)/g, "");
}
$(function(){
	//清除无用类名
	$('.active').removeClass('active');
	$(".custom-drop").removeClass("ui-droppable").find(".dropitem").removeClass("ui-draggable ui-resizable").find(".ui-resizable-handle").remove();

	$(".label-content").each(function() {
	
		//燕文国家分区
		if($(this).find(".dropitem[data-title='燕文国家分区(平)']").length>0 || $(this).find(".dropitem[data-title='燕文国家分区(挂)']").length>0){
			var	area_surface=$(this).find(".dropitem[data-title='燕文国家分区(平)']").find(".detail"),
				area_register=$(this).find(".dropitem[data-title='燕文国家分区(挂)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1),
				space="&nbsp;&nbsp;&nbsp;";
				
			switch(country_cn){
				case "俄罗斯":
					area_surface.html("平9"+space+"A"+space+"序1");
					area_register.html("挂13"+space+"A"+space+"序1");
				break;
				case "美国":
					area_surface.html("平7"+space+"A"+space+"序2");
					area_register.html("挂11"+space+"A"+space+"序2");
				break;
				case "巴西":
					area_surface.html("平10"+space+"A"+space+"序3");
					area_register.html("挂14"+space+"A"+space+"序3");
				break;
				case "英国":
					area_surface.html("平8"+space+"A"+space+"序4");
					area_register.html("挂12"+space+"A"+space+"序4");
				break;
				case "瑞典":
					area_surface.html("平17"+space+"A"+space+"序5");
					area_register.html("挂21"+space+"A"+space+"序5");
				break;
				case "澳大利亚":
					area_surface.html("平11"+space+"A"+space+"序6");
					area_register.html("挂15"+space+"A"+space+"序6");
				break;
				case "阿根廷":
					area_surface.html("平19"+space+"A"+space+"序7");
					area_register.html("挂23"+space+"A"+space+"序7");
				break;
				case "乌克兰":
					area_surface.html("平20"+space+"A"+space+"序8");
					area_register.html("挂24"+space+"A"+space+"序8");
				break;
				case "日本":
					area_surface.html("平1"+space+"A"+space+"序9");
					area_register.html("挂1"+space+"B"+space+"序9");
				break;
				case "以色列":
					area_surface.html("平16"+space+"A"+space+"序10");
					area_register.html("挂20"+space+"A"+space+"序10");
				break;
				case "加拿大":
					area_surface.html("平13"+space+"A"+space+"序11");
					area_register.html("挂17"+space+"A"+space+"序11");
				break;
				case "挪威":
					area_surface.html("平18"+space+"A"+space+"序12");
					area_register.html("挂22"+space+"A"+space+"序12");
				break;
				case "西班牙":
					area_surface.html("平14"+space+"A"+space+"序13");
					area_register.html("挂18"+space+"A"+space+"序13");
				break;
				case "法国":
					area_surface.html("平12"+space+"A"+space+"序14");
					area_register.html("挂16"+space+"A"+space+"序14");
				break;
				case "德国":
					area_surface.html("平15"+space+"A"+space+"序15");
					area_register.html("挂19"+space+"A"+space+"序15");
				break;
				case "土耳其":
					area_surface.html("平1"+space+"A"+space+"序16");
					area_register.html("挂1"+space+"B"+space+"序16");
				break;
				case "意大利":
					area_surface.html("平1"+space+"A"+space+"序17");
					area_register.html("挂1"+space+"B"+space+"序17");
				break;
				case "芬兰":
					area_surface.html("平1"+space+"A"+space+"序18");
					area_register.html("挂1"+space+"B"+space+"序18");
				break;
				case "比利时":
					area_surface.html("平1"+space+"A"+space+"序19");
					area_register.html("挂1"+space+"B"+space+"序19");
				break;
				case "克罗地亚":
					area_surface.html("平21"+space+"A"+space+"序21");
					area_register.html("挂1"+space+"B"+space+"序22");
				break;
				case "智利":
					area_surface.html("平1"+space+"A"+space+"序20");
					area_register.html("挂1"+space+"B"+space+"序20");
				break;
				case "捷克":
					area_surface.html("平1"+space+"A"+space+"序22");
					area_register.html("挂1"+space+"B"+space+"序22");
				break;
				case "希腊":
					area_surface.html("平1"+space+"A"+space+"序23");
					area_register.html("挂1"+space+"B"+space+"序23");
				break;
				case "台湾":
					area_surface.html("平1"+space+"A"+space+"序24");
					area_register.html("挂1"+space+"B"+space+"序24");
				break;
				case "匈牙利":
					area_surface.html("平21"+space+"A"+space+"序25");
					area_register.html("挂1"+space+"B"+space+"序25");
				break;
				case "葡萄牙":
					area_surface.html("平1"+space+"A"+space+"序26");
					area_register.html("挂1"+space+"B"+space+"序26");
				break;
				case "爱尔兰":
					area_surface.html("平1"+space+"A"+space+"序27");
					area_register.html("挂1"+space+"B"+space+"序27");
				break;
				case "丹麦":
					area_surface.html("平1"+space+"A"+space+"序28");
					area_register.html("挂1"+space+"B"+space+"序28");
				break;
				case "荷兰":
					area_surface.html("平21"+space+"A"+space+"序29");
					area_register.html("挂1"+space+"B"+space+"序29");
				break;
				case "白俄罗斯":
					area_surface.html("平1"+space+"A"+space+"序30");
					area_register.html("挂1"+space+"B"+space+"序30");
				break;
				case "墨西哥":
					area_surface.html("平1"+space+"A"+space+"序31");
					area_register.html("挂1"+space+"B"+space+"序31");
				break;
				case "拉脱维亚":
					area_surface.html("平1"+space+"A"+space+"序32");
					area_register.html("挂1"+space+"B"+space+"序32");
				break;
				case "波兰":
					area_surface.html("平1"+space+"A"+space+"序33");
					area_register.html("挂1"+space+"B"+space+"序33");
				break;
				case "斯洛伐克":
					area_surface.html("平1"+space+"A"+space+"序34");
					area_register.html("挂1"+space+"B"+space+"序34");
				break;
				case "立陶宛":
					area_surface.html("平1"+space+"A"+space+"序35");
					area_register.html("挂1"+space+"B"+space+"序35");
				break;
				case "新加坡":
					area_surface.html("平1"+space+"A"+space+"序36");
					area_register.html("挂1"+space+"B"+space+"序36");
				break;
				case "奥地利":
					area_surface.html("平1"+space+"A"+space+"序37");
					area_register.html("挂1"+space+"B"+space+"序37");
				break;
				case "马耳他":
					area_surface.html("平1"+space+"A"+space+"序38");
					area_register.html("挂1"+space+"B"+space+"序38");
				break;
				case "爱沙尼亚":
					area_surface.html("平1"+space+"A"+space+"序39");
					area_register.html("挂1"+space+"B"+space+"序39");
				break;
				case "保加利亚":
					area_surface.html("平1"+space+"A"+space+"序40");
					area_register.html("挂1"+space+"B"+space+"序40");
				break;
				default:
					area_surface.html("平1"+space+"A"+space+"序41");
					area_register.html("挂1"+space+"B"+space+"序41");
				break;
			};
		};
		//燕文香港平邮国家分区
		if($(this).find(".dropitem[data-title='燕文香港国家分区(平)']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='燕文香港国家分区(平)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "阿富汗":
				case "孟加拉":
				case "不丹":
				case "文莱达鲁萨兰国":
				case "柬埔寨":
				case "东帝汶":
				case "印度":
				case "印度尼西亚":
				case "朝鲜":
				case "韩国":
				case "老挝":
				case "马来西亚":
				case "马尔代夫":
				case "马里亚纳群岛":
				case "缅甸":
				case "尼泊尔":
				case "巴基斯坦":
				case "菲律宾":
				case "新加坡":
				case "斯里兰卡":
				case "泰国":
				case "越南":
				areaLabel.html("1");
				break;
					
				case "安圭拉岛":
				case "安提瓜及巴布达":
				case "亚美尼亚":
				case "阿森松":
				case "澳大利亚":
				case "奥地利":
				case "阿塞拜疆":
				case "亚速尔":
				case "巴哈马":
				case "巴林":
				case "巴利阿里群岛":
				case "巴巴多斯":
				case "比利时":
				case "伯利兹":
				case "百慕达":
				case "英属印度洋地区":
				case "加拿大":
				case "加那利群岛":
				case "加罗林群岛":
				case "开曼群岛":
				case "加沙及汗尤尼斯":
				case "圣诞岛":
				case "科科斯群岛":
				case "科西嘉岛":
				case "哥斯达黎加":
				case "古巴":
				case "塞浦路斯":
				case "多米尼加岛":
				case "多米尼加共和国":
				case "萨尔瓦多":
				case "赤道几内亚":
				case "法罗群岛":
				case "斐济":
				case "法国":
				case "法属圭亚那":
				case "法属波利尼西亚":
				case "法属西印度群岛":
				case "德国":
				case "直布罗陀":
				case "希腊":
				case "格陵兰":
				case "格林纳达":
				case "危地马拉":
				case "海地":
				case "洪都拉斯":
				case "伊朗":
				case "伊拉克":
				case "爱尔兰":
				case "以色列":
				case "意大利":
				case "牙买加":
				case "日本":
				case "约旦":
				case "哈萨克":
				case "基里巴斯":
				case "科威特":
				case "吉尔吉斯":
				case "黎巴嫩":
				case "列支敦士登":
				case "卢森堡":
				case "马德拉":
				case "马尔他":
				case "马绍尔群岛":
				case "墨西哥":
				case "摩纳哥":
				case "蒙古":
				case "蒙特塞拉特":
				case "瑙鲁":
				case "荷兰":
				case "新喀里多尼亚":
				case "新西兰":
				case "新西兰属土岛屿":
				case "尼加拉瓜":
				case "诺褔克岛":
				case "阿曼":
				case "巴拿马":
				case "巴布亚新几内亚":
				case "皮特凯恩岛":
				case "葡萄牙":
				case "波多黎各":
				case "卡塔尔":
				case "萨摩亚":
				case "西萨摩亚":
				case "沙地阿拉伯":
				case "所罗门群岛":
				case "西班牙":
				case "北非西班牙属土":
				case "斯匹次卑尔根群岛":
				case "圣基茨和尼维斯":
				case "圣赫勒拿岛":
				case "圣卢西亚":
				case "圣皮埃尔和密克隆群岛":
				case "圣文森特和格林纳丁斯":
				case "瑞士":
				case "叙利亚":
				case "塔吉克":
				case "汤加":
				case "托尔托拉岛":
				case "千里达和多巴哥":
				case "特里斯坦-达库尼亚岛":
				case "土耳其":
				case "土库曼":
				case "特克斯和凯科斯群岛":
				case "图瓦卢":
				case "阿拉伯联合酋长国":
				case "英国":
				case "美国":
				case "乌兹别克":
				case "瓦努阿图":
				case "梵蒂冈":
				case "美属处女群岛":
				case "威克岛":
				case "瓦利斯群岛和富图纳群岛":
				case "也门":
				areaLabel.html("2");
				break;
					
				case "阿尔巴尼亚":
				case "白俄罗斯":
				case "波黑":
				case "保加利亚":
				case "克罗地亚":
				case "捷克":
				case "丹麦":
				case "爱沙尼亚":
				case "芬兰":
				case "格鲁吉亚":
				case "匈牙利":
				case "冰岛":
				case "拉脱维亚":
				case "立陶宛":
				case "马其顿":
				case "摩尔多瓦":
				case "黑山共和国":
				case "挪威":
				case "波兰":
				case "罗马尼亚":
				case "俄罗斯":
				case "塞尔维亚":
				case "斯洛伐克":
				case "斯洛文尼亚":
				case "瑞典":
				case "乌克兰":
				areaLabel.html("3");
				break;
					
				case "阿尔及利亚":
				case "安哥拉":
				case "阿根廷":
				case "贝寧":
				case "玻利维亚":
				case "博茨瓦纳":
				case "巴西":
				case "布基纳法索":
				case "布隆迪":
				case "喀麦隆":
				case "佛得角群岛":
				case "中非共和国":
				case "乍得":
				case "智利":
				case "哥伦比亚":
				case "科摩罗":
				case "刚果":
				case "刚果":
				case "科特迪瓦":
				case "吉布提":
				case "厄瓜多尔":
				case "埃及":
				case "厄立特里亚":
				case "埃塞俄比亚":
				case "福克兰群岛":
				case "加薘":
				case "冈比亚":
				case "加纳":
				case "新几内亚":
				case "几内亚比绍":
				case "圭亚那":
				case "肯尼亚":
				case "莱索托":
				case "利比里亚":
				case "利比亚":
				case "马达加斯加":
				case "马拉维":
				case "马里":
				case "毛里塔尼亚":
				case "毛里求斯":
				case "摩洛哥":
				case "莫桑比克":
				case "纳米比亚":
				case "荷属安的列斯群岛":
				case "尼日尔":
				case "尼日利亚":
				case "巴拉圭":
				case "秘鲁":
				case "留尼旺岛":
				case "卢旺达":
				case "圣多美和普林西比":
				case "塞内加尔":
				case "塞舌尔":
				case "塞拉里昂":
				case "索马里":
				case "南非":
				case "苏丹":
				case "苏里南":
				case "斯威士兰":
				case "坦桑尼亚":
				case "多哥":
				case "突尼斯":
				case "乌干达":
				case "乌拉圭":
				case "委内瑞拉":
				case "赞比亚":
				case "津巴布韦":
				areaLabel.html("4");
				break;
			};
		}
		//燕文香港挂号国家分区
		if($(this).find(".dropitem[data-title='燕文香港国家分区(挂)']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='燕文香港国家分区(挂)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
			case "越南":
			case "不丹":
			case "阿富汗":
			case "中国":
			case "印度尼西亚":
			case "台湾":
			case "马来西亚":
			case "东帝汶":
			case "泰国":
			case "新加坡":
			case "印度":
			case "巴基斯坦":
			case "柬埔寨":
			case "北韩":
			case "南韩":
			case "文莱达鲁萨兰国":
			case "马尔代夫":
			case "孟加拉":
			case "老挝":
			case "菲律宾":
			case "尼泊尔":
			case "斯里兰卡":
			case "缅甸":
			case "马里亚纳群岛 ":
			areaLabel.html("1");
			break;
				
			case "比利时":
			case "百慕达":
			case "巴巴多斯":
			case "奥地利":
			case "巴哈马":
			case "阿拉伯联合酋长国":
			case "亚美尼亚":
			case "巴林":
			case "伯利兹":
			case "加拿大":
			case "科科斯群岛":
			case "阿塞拜疆":
			case "亚鲁巴":
			case "澳大利亚":
			case "瑞士":
			case "萨摩亚":
			case "库克群岛":
			case "安圭拉岛":
			case "安提瓜及巴布达":
			case "安道尔":
			case "斯匹次卑尔根群岛":
			case "哥斯达黎加":
			case "古巴":
			case "萨尔瓦多":
			case "圣诞岛":
			case "塞浦路斯":
			case "葡萄牙":
			case "德国":
			case "也门":
			case "丹麦":
			case "多米尼加岛":
			case "多米尼加共和国":
			case "科索夫":
			case "威克岛":
			case "新西兰属土岛屿":
			case "加罗林群岛":
			case "巴利阿里群岛":
			case "西班牙":
			case "马德拉":
			case "芬兰":
			case "斐济":
			case "亚速尔":
			case "密克罗尼西亚":
			case "法罗群岛":
			case "法国":
			case "北非西班牙属土":
			case "英国":
			case "格林纳达":
			case "科西嘉岛":
			case "法属圭亚那":
			case "加沙及汗尤尼斯":
			case "直布罗陀":
			case "格陵兰":
			case "阿森松":
			case "特里斯坦 - 达库尼亚岛":
			case "瓜德罗普岛":
			case "赤道几内亚":
			case "希腊":
			case "南乔治亚岛和南桑德韦奇岛":
			case "危地马拉":
			case "关岛":
			case "加那利群岛":
			case "西萨摩亚":
			case "洪都拉斯":
			case "瓦利斯群岛和富图纳群岛":
			case "海地":
			case "瓦努阿图":
			case "圣卢西亚":
			case "爱尔兰":
			case "以色列":
			case "美属处女群岛":
			case "伊拉克":
			case "伊朗":
			case "冰岛":
			case "意大利":
			case "牙买加":
			case "约旦":
			case "日本":
			case "托尔托拉岛":
			case "吉尔吉斯":
			case "新西兰":
			case "基里巴斯":
			case "圣文森特和格林纳丁斯":
			case "圣基茨和尼维斯":
			case "梵蒂冈":
			case "乌兹别克":
			case "科威特":
			case "开曼群岛":
			case "哈萨克":
			case "特克斯和凯科斯群岛":
			case "黎巴嫩":
			case "瑞典":
			case "列支敦士登":
			case "美国":
			case "圣马力诺":
			case "波多黎各":
			case "皮特凯恩岛":
			case "卢森堡":
			case "巴布亚新几内亚":
			case "图瓦卢":
			case "千里达和多巴哥":
			case "土耳其":
			case "瑙鲁":
			case "汤加":
			case "荷兰":
			case "马绍尔群岛":
			case "土库曼":
			case "塔吉克":
			case "尼加拉瓜":
			case "蒙古":
			case "所罗门群岛":
			case "马提尼克岛":
			case "卡塔尔":
			case "蒙特塞拉特":
			case "马尔他":
			case "法属波利尼西亚":
			case "圣皮埃尔和密克隆群岛":
			case "叙利亚":
			case "墨西哥":
			case "阿曼":
			case "挪威":
			case "沙地阿拉伯":
			case "新喀里多尼亚":
			case "巴拿马":
			case "诺褔克岛":
			case "圣赫勒拿岛":
			areaLabel.html("2");
			break;
				
			case "博茨瓦纳":
			case "纳米比亚":
			case "莫桑比克":
			case "布隆迪":
			case "马达加斯加":
			case "科特迪瓦":
			case "贝宁":
			case "尼日尔":
			case "摩尔多瓦":
			case "刚果":
			case "拉脱维亚":
			case "保加利亚":
			case "布基纳法索":
			case "玻利维亚":
			case "波兰":
			case "立陶宛":
			case "莱索托":
			case "捷克":
			case "巴拉圭":
			case "毛里塔尼亚":
			case "留尼旺岛":
			case "罗马尼亚":
			case "塞尔维亚":
			case "俄罗斯":
			case "卢旺达":
			case "秘鲁":
			case "巴西":
			case "塞舌尔":
			case "苏丹":
			case "喀麦隆":
			case "波黑":
			case "尼日利亚":
			case "斯洛文尼亚":
			case "哥伦比亚":
			case "斯洛伐克":
			case "塞拉里昂":
			case "利比里亚":
			case "塞内加尔":
			case "索马里":
			case "苏里南":
			case "圣多美和普林西比":
			case "佛得角群岛":
			case "马拉维":
			case "斯威士兰":
			case "毛里求斯":
			case "乍得":
			case "多哥":
			case "阿根廷":
			case "马里":
			case "马其顿":
			case "突尼斯":
			case "黑山共和国":
			case "安哥拉":
			case "摩纳哥":
			case "摩洛哥":
			case "利比亚":
			case "荷属安的列斯群岛":
			case "坦桑尼亚":
			case "乌克兰":
			case "乌干达":
			case "白俄罗斯":
			case "乌拉圭":
			case "中非共和国":
			case "刚果":
			case "科摩罗":
			case "委内瑞拉":
			case "肯尼亚":
			case "智利":
			case "阿尔巴尼亚":
			case "匈牙利":
			case "克罗地亚":
			case "圭亚那":
			case "几内亚比绍":
			case "新几内亚":
			case "冈比亚":
			case "加纳":
			case "格鲁吉亚":
			case "加薘":
			case "福克兰群岛":
			case "埃塞俄比亚":
			case "厄立特里亚":
			case "埃及":
			case "爱沙尼亚":
			case "厄瓜多尔":
			case "阿尔及利亚":
			case "吉布提":
			case "南非":
			case "赞比亚":
			case "津巴布韦":
			areaLabel.html("3");
			break;
				
			};
		}
		//美国EUB省州分区
		if($(this).find(".dropitem[data-title='美国EUB省州分区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	area_usstate=$(this).find(".dropitem[data-title='美国EUB省州分区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".postcode").text()),
				postcode=address.substring(0,2);
			switch(postcode){
				case "94":
				case "95":
				case "96":
				case "97":
				case "98":
				case "99":
				area_usstate.html("2");
				break;
				
				case "30":
				case "31":
				case "32":
				case "33":
				case "34":
				case "35":
				case "36":
				case "37":
				case "38":
				case "39":
				case "40":
				case "41":
				case "42":
				case "43":
				case "44":
				case "45":
				case "46":
				case "47":
				case "48":
				case "49":
				case "50":
				case "51":
				case "52":
				case "53":
				case "54":
				case "55":
				case "56":
				case "57":
				case "58":
				case "59":
				case "60":
				case "61":
				case "62":
				case "63":
				case "64":
				case "65":
				case "66":
				case "67":
				case "68":
				case "69":
				case "70":
				case "71":
				case "72":
				case "73":
				case "74":
				case "75":
				case "76":
				case "77":
				case "78":
				case "79":
				area_usstate.html("3");
				break;
				
				case "80":
				case "81":
				case "82":
				case "83":
				case "84":
				case "85":
				case "86":
				case "87":
				case "88":
				case "89":
				case "90":
				case "91":
				case "92":
				case "93":
				area_usstate.html("4");
				break;
				
				default:area_usstate.html("1");break;
			};
		};
		//俄速通分拣分区
		if($(this).find(".dropitem[data-title='俄速通分拣分区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	area_usstate=$(this).find(".dropitem[data-title='俄速通分拣分区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".postcode").text()),
 				postcode=address.substring(0,1);
				postcode1=address.substring(0,2);
				postcode2=address.substring(0,3);
	 		if(postcode == '1'){
	 			area_usstate.html("1");
 	 		}else if(postcode == '2'){
 	 			area_usstate.html("2");
 	 	 	}else if(postcode == '3'){
 	 	 		area_usstate.html("3");
 	 	 	}else if(postcode == '4' || postcode1 == '60' || postcode1 == '61' || postcode1 == '62'){
 	 	 		area_usstate.html("4");
 	 	 	}else if(postcode1 == '68' || postcode1 == '69'){
 	 	 		area_usstate.html("5");
 	 	 	}else if(postcode == '6' && postcode1 != '60' && postcode1 != '61' && postcode1 != '62' && postcode1 != '68' && postcode1 != '69' && postcode2 != '640' && postcode2 != '641'){
 	 	 		area_usstate.html("6");
 	 	 	}else{
 	 	 		area_usstate.html("none");
 	 	 	};
		};
		//收件人国家分区
		if($(this).find(".dropitem[data-title='收件人国家分区2']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='收件人国家分区2']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				//address=$(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text(),
				country_cn=address.substring(1,address.length-1);
 			//alert(areaLabel+"&&"+address+"&&"+country_cn)
			switch(country_cn){
				case "阿尔巴尼亚":
				case "阿尔及利亚":
				case "阿富汗":
				areaLabel.html("0");
				break;
				
				case "阿根廷":
				areaLabel.html("1");
				break;
				
				case "阿联酋":
				case "阿鲁巴":
				case "阿曼":
				case "阿塞拜疆":
				case "阿松森岛":
				case "埃及":
				case "埃塞俄比亚":
				case "爱尔兰":
				case "爱沙尼亚":
				areaLabel.html("2");
				break;
				
				case "安道尔":
				case "安哥拉":
				case "安圭拉":
				case "安提瓜和巴布达":
				case "奥地利":
				case "澳大利亚":
				case "巴巴多斯":
				case "巴布亚新几内亚":
				case "巴哈马":
				case "巴基斯坦":
				areaLabel.html("3");
				break;
				
				case "巴拉圭":
				case "巴勒斯坦":
				case "巴林":
				case "巴拿马":
				case "巴西":
				case "白俄罗斯":
				case "百慕大":
				case "保加利亚":
				case "北马里亚纳群岛":
				case "贝宁":
				case "比利时":
				case "冰岛":
				case "波多黎各":
				case "波黑":
				case "波兰":
				case "玻利维亚":
				case "伯利兹":
				case "博茨瓦纳":
				case "不丹":
				case "布基纳法索":
				case "布隆迪":
				case "朝鲜":
				case "赤道几内亚":
				case "丹麦":
				case "德国":
				case "东帝汶":
				case "多哥":
				case "多米尼加":
				case "俄罗斯":
				case "厄瓜多尔":
				case "厄立特里亚":
				case "法国":
				case "法罗群岛":
				case "法属波利尼西亚":
				case "法属圭亚那":
				areaLabel.html("4");
				break;
				
				case "法属南部领土":
				case "梵蒂冈":
				case "菲律宾":
				case "斐济":
				case "芬兰":
				case "佛得角":
				case "福克兰群岛（马尔维纳斯）":
				case "复活岛":
				case "冈比亚":
				case "刚果（布）":
				case "刚果（金）":
				case "哥伦比亚":
				case "哥斯达黎加":
				case "格林纳达":
				case "格陵兰":
				case "格鲁吉亚":
				case "古巴":
				case "瓜德罗普":
				case "关岛":
				case "圭亚那":
				case "哈萨克斯坦":
				case "海地":
				case "韩国":
				case "荷兰":
				case "荷属安的列斯":
				case "赫德岛和麦克唐那岛":
				case "黑山":
				case "洪都拉斯":
				case "基里巴斯":
				case "吉布提":
				case "吉尔吉斯斯坦":
				case "几内亚":
				case "几内亚比绍":
				case "加拿大":
				case "加纳":
				case "加纳利群岛":
				case "加蓬":
				case "柬埔寨":
				case "捷克":
				case "津巴布韦":
				case "喀麦隆":
				case "卡奔达":
				case "卡塔尔":
				case "开曼群岛":
				case "科科斯（基林）群岛":
				case "科摩罗":
				case "科特迪瓦":
				case "科威特":
				case "克罗地亚":
				case "肯尼亚":
				case "库克群岛":
				case "拉脱维亚":
				case "莱索托":
				case "老挝":
				case "黎巴嫩":
				case "立陶宛":
				case "利比里亚":
				case "利比亚":
				case "列支敦士登":
				case "留尼汪":
				case "卢森堡":
				case "卢旺达":
				case "罗马尼亚":
				case "马达加斯加":
				case "马尔代夫":
				case "马耳他":
				case "马拉维":
				case "马来西亚":
				case "马里":
				case "马其顿":
				case "马绍尔群岛":
				case "马提尼克":
				case "马约特":
				case "毛里求斯":
				case "毛里塔尼亚":
				case "美国":
				case "美国本土外小岛屿":
				case "美属萨摩亚":
				case "美属维尔京群岛":
				case "蒙古":
				case "蒙特塞拉特":
				case "孟加拉国":
				case "秘鲁":
				case "密克罗尼西亚":
				case "缅甸":
				case "摩尔多瓦":
				case "摩洛哥":
				case "摩纳哥":
				case "莫桑比克":
				case "墨西哥":
				case "纳米比亚":
				case "南非":
				case "南乔治亚岛和南桑德韦奇岛":
				case "瑙鲁":
				case "尼泊尔":
				case "尼加拉瓜":
				case "尼日尔":
				case "尼日利亚":
				case "纽埃":
				case "挪威":
				case "诺福克岛":
				case "帕劳":
				case "皮特凯恩":
				case "葡萄牙":
				case "日本":
				case "瑞典":
				case "瑞士":
				case "萨尔瓦多":
				case "萨摩亚":
				case "塞尔维亚":
				case "塞拉利昂":
				case "塞内加尔":
				case "塞浦路斯":
				case "塞舌尔":
				case "沙特阿拉伯":
				case "圣诞岛":
				case "圣多美和普林西比":
				case "圣赫勒拿":
				case "圣基茨和尼维斯":
				case "圣卢西亚":
				case "圣马力诺":
				case "圣皮埃尔和密克隆":
				case "圣文森特和格林纳丁斯":
				case "斯里兰卡":
				case "斯洛伐克":
				case "斯洛文尼亚":
				case "斯瓦尔巴岛和扬马延岛":
				case "斯威士兰":
				case "苏丹":
				case "苏里南":
				case "所罗门群岛":
				case "索马里":
				case "塔吉克斯坦":
				case "泰国":
				case "坦桑尼亚":
				case "汤加":
				case "特克斯和凯科斯群岛":
				case "特里斯达库尼亚":
				areaLabel.html("5");
				break;
				
				case "特立尼达和多巴哥":
				case "突尼斯":
				case "图瓦卢":
				case "土耳其":
				case "土库曼斯坦":
				case "托克劳":
				case "瓦利斯和富图纳":
				case "瓦努阿图":
				case "危地马拉":
				case "委内瑞拉":
				case "文莱":
				case "乌干达":
				case "乌克兰":
				case "乌拉圭":
				case "乌兹别克斯坦":
				case "西班牙":
				case "西撒哈拉":
				case "希腊":
				case "新加坡":
				case "新喀里多尼亚":
				case "新西兰":
				case "匈牙利":
				case "叙利亚":
				case "牙买加":
				case "亚美尼亚":
				case "亚速尔群岛和马德拉群岛":
				case "也门":
				case "伊夫尼":
				case "伊拉克":
				case "伊朗":
				case "以色列":
				case "意大利":
				case "印度":
				case "印度尼西亚":
				case "英国":
				case "英属维尔京群岛":
				case "英属印度洋领地":
				case "约旦":
				case "约翰斯敦岛":
				areaLabel.html("6");
				break;
				
				case "越南":
				areaLabel.html("7");
				break;
				
				case "赞比亚":
				case "扎伊尔":
				case "乍得":
				case "直布罗陀":
				case "智利":
				case "中非":
				case "中国澳门":
				case "中国台湾":
				case "中国香港":
				areaLabel.html("8");
				break;
				
			};
		}
		//燕文香港平邮国家分区
		if($(this).find(".dropitem[data-title='燕文香港国家分区(平)']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='燕文香港国家分区(平)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "阿富汗":
				case "孟加拉":
				case "不丹":
				case "文莱达鲁萨兰国":
				case "柬埔寨":
				case "东帝汶":
				case "印度":
				case "印度尼西亚":
				case "朝鲜":
				case "韩国":
				case "老挝":
				case "马来西亚":
				case "马尔代夫":
				case "马里亚纳群岛":
				case "缅甸":
				case "尼泊尔":
				case "巴基斯坦":
				case "菲律宾":
				case "新加坡":
				case "斯里兰卡":
				case "泰国":
				case "越南":
				areaLabel.html("1");
				break;
					
				case "安圭拉岛":
				case "安提瓜及巴布达":
				case "亚美尼亚":
				case "阿森松":
				case "澳大利亚":
				case "奥地利":
				case "阿塞拜疆":
				case "亚速尔":
				case "巴哈马":
				case "巴林":
				case "巴利阿里群岛":
				case "巴巴多斯":
				case "比利时":
				case "伯利兹":
				case "百慕达":
				case "英属印度洋地区":
				case "加拿大":
				case "加那利群岛":
				case "加罗林群岛":
				case "开曼群岛":
				case "加沙及汗尤尼斯":
				case "圣诞岛":
				case "科科斯群岛":
				case "科西嘉岛":
				case "哥斯达黎加":
				case "古巴":
				case "塞浦路斯":
				case "多米尼加岛":
				case "多米尼加共和国":
				case "萨尔瓦多":
				case "赤道几内亚":
				case "法罗群岛":
				case "斐济":
				case "法国":
				case "法属圭亚那":
				case "法属波利尼西亚":
				case "法属西印度群岛":
				case "德国":
				case "直布罗陀":
				case "希腊":
				case "格陵兰":
				case "格林纳达":
				case "危地马拉":
				case "海地":
				case "洪都拉斯":
				case "伊朗":
				case "伊拉克":
				case "爱尔兰":
				case "以色列":
				case "意大利":
				case "牙买加":
				case "日本":
				case "约旦":
				case "哈萨克":
				case "基里巴斯":
				case "科威特":
				case "吉尔吉斯":
				case "黎巴嫩":
				case "列支敦士登":
				case "卢森堡":
				case "马德拉":
				case "马尔他":
				case "马绍尔群岛":
				case "墨西哥":
				case "摩纳哥":
				case "蒙古":
				case "蒙特塞拉特":
				case "瑙鲁":
				case "荷兰":
				case "新喀里多尼亚":
				case "新西兰":
				case "新西兰属土岛屿":
				case "尼加拉瓜":
				case "诺褔克岛":
				case "阿曼":
				case "巴拿马":
				case "巴布亚新几内亚":
				case "皮特凯恩岛":
				case "葡萄牙":
				case "波多黎各":
				case "卡塔尔":
				case "萨摩亚":
				case "西萨摩亚":
				case "沙地阿拉伯":
				case "所罗门群岛":
				case "西班牙":
				case "北非西班牙属土":
				case "斯匹次卑尔根群岛":
				case "圣基茨和尼维斯":
				case "圣赫勒拿岛":
				case "圣卢西亚":
				case "圣皮埃尔和密克隆群岛":
				case "圣文森特和格林纳丁斯":
				case "瑞士":
				case "叙利亚":
				case "塔吉克":
				case "汤加":
				case "托尔托拉岛":
				case "千里达和多巴哥":
				case "特里斯坦-达库尼亚岛":
				case "土耳其":
				case "土库曼":
				case "特克斯和凯科斯群岛":
				case "图瓦卢":
				case "阿拉伯联合酋长国":
				case "英国":
				case "美国":
				case "乌兹别克":
				case "瓦努阿图":
				case "梵蒂冈":
				case "美属处女群岛":
				case "威克岛":
				case "瓦利斯群岛和富图纳群岛":
				case "也门":
				areaLabel.html("2");
				break;
					
				case "阿尔巴尼亚":
				case "白俄罗斯":
				case "波黑":
				case "保加利亚":
				case "克罗地亚":
				case "捷克":
				case "丹麦":
				case "爱沙尼亚":
				case "芬兰":
				case "格鲁吉亚":
				case "匈牙利":
				case "冰岛":
				case "拉脱维亚":
				case "立陶宛":
				case "马其顿":
				case "摩尔多瓦":
				case "黑山共和国":
				case "挪威":
				case "波兰":
				case "罗马尼亚":
				case "俄罗斯":
				case "塞尔维亚":
				case "斯洛伐克":
				case "斯洛文尼亚":
				case "瑞典":
				case "乌克兰":
				areaLabel.html("3");
				break;
					
				case "阿尔及利亚":
				case "安哥拉":
				case "阿根廷":
				case "贝寧":
				case "玻利维亚":
				case "博茨瓦纳":
				case "巴西":
				case "布基纳法索":
				case "布隆迪":
				case "喀麦隆":
				case "佛得角群岛":
				case "中非共和国":
				case "乍得":
				case "智利":
				case "哥伦比亚":
				case "科摩罗":
				case "刚果":
				case "刚果":
				case "科特迪瓦":
				case "吉布提":
				case "厄瓜多尔":
				case "埃及":
				case "厄立特里亚":
				case "埃塞俄比亚":
				case "福克兰群岛":
				case "加薘":
				case "冈比亚":
				case "加纳":
				case "新几内亚":
				case "几内亚比绍":
				case "圭亚那":
				case "肯尼亚":
				case "莱索托":
				case "利比里亚":
				case "利比亚":
				case "马达加斯加":
				case "马拉维":
				case "马里":
				case "毛里塔尼亚":
				case "毛里求斯":
				case "摩洛哥":
				case "莫桑比克":
				case "纳米比亚":
				case "荷属安的列斯群岛":
				case "尼日尔":
				case "尼日利亚":
				case "巴拉圭":
				case "秘鲁":
				case "留尼旺岛":
				case "卢旺达":
				case "圣多美和普林西比":
				case "塞内加尔":
				case "塞舌尔":
				case "塞拉里昂":
				case "索马里":
				case "南非":
				case "苏丹":
				case "苏里南":
				case "斯威士兰":
				case "坦桑尼亚":
				case "多哥":
				case "突尼斯":
				case "乌干达":
				case "乌拉圭":
				case "委内瑞拉":
				case "赞比亚":
				case "津巴布韦":
				areaLabel.html("4");
				break;
			};
		}
		//燕文香港挂号国家分区
		if($(this).find(".dropitem[data-title='燕文香港国家分区(挂)']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='燕文香港国家分区(挂)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
			case "越南":
			case "不丹":
			case "阿富汗":
			case "中国":
			case "印度尼西亚":
			case "台湾":
			case "马来西亚":
			case "东帝汶":
			case "泰国":
			case "新加坡":
			case "印度":
			case "巴基斯坦":
			case "柬埔寨":
			case "北韩":
			case "南韩":
			case "文莱达鲁萨兰国":
			case "马尔代夫":
			case "孟加拉":
			case "老挝":
			case "菲律宾":
			case "尼泊尔":
			case "斯里兰卡":
			case "缅甸":
			case "马里亚纳群岛 ":
			areaLabel.html("1");
			break;
				
			case "比利时":
			case "百慕达":
			case "巴巴多斯":
			case "奥地利":
			case "巴哈马":
			case "阿拉伯联合酋长国":
			case "亚美尼亚":
			case "巴林":
			case "伯利兹":
			case "加拿大":
			case "科科斯群岛":
			case "阿塞拜疆":
			case "亚鲁巴":
			case "澳大利亚":
			case "瑞士":
			case "萨摩亚":
			case "库克群岛":
			case "安圭拉岛":
			case "安提瓜及巴布达":
			case "安道尔":
			case "斯匹次卑尔根群岛":
			case "哥斯达黎加":
			case "古巴":
			case "萨尔瓦多":
			case "圣诞岛":
			case "塞浦路斯":
			case "葡萄牙":
			case "德国":
			case "也门":
			case "丹麦":
			case "多米尼加岛":
			case "多米尼加共和国":
			case "科索夫":
			case "威克岛":
			case "新西兰属土岛屿":
			case "加罗林群岛":
			case "巴利阿里群岛":
			case "西班牙":
			case "马德拉":
			case "芬兰":
			case "斐济":
			case "亚速尔":
			case "密克罗尼西亚":
			case "法罗群岛":
			case "法国":
			case "北非西班牙属土":
			case "英国":
			case "格林纳达":
			case "科西嘉岛":
			case "法属圭亚那":
			case "加沙及汗尤尼斯":
			case "直布罗陀":
			case "格陵兰":
			case "阿森松":
			case "特里斯坦 - 达库尼亚岛":
			case "瓜德罗普岛":
			case "赤道几内亚":
			case "希腊":
			case "南乔治亚岛和南桑德韦奇岛":
			case "危地马拉":
			case "关岛":
			case "加那利群岛":
			case "西萨摩亚":
			case "洪都拉斯":
			case "瓦利斯群岛和富图纳群岛":
			case "海地":
			case "瓦努阿图":
			case "圣卢西亚":
			case "爱尔兰":
			case "以色列":
			case "美属处女群岛":
			case "伊拉克":
			case "伊朗":
			case "冰岛":
			case "意大利":
			case "牙买加":
			case "约旦":
			case "日本":
			case "托尔托拉岛":
			case "吉尔吉斯":
			case "新西兰":
			case "基里巴斯":
			case "圣文森特和格林纳丁斯":
			case "圣基茨和尼维斯":
			case "梵蒂冈":
			case "乌兹别克":
			case "科威特":
			case "开曼群岛":
			case "哈萨克":
			case "特克斯和凯科斯群岛":
			case "黎巴嫩":
			case "瑞典":
			case "列支敦士登":
			case "美国":
			case "圣马力诺":
			case "波多黎各":
			case "皮特凯恩岛":
			case "卢森堡":
			case "巴布亚新几内亚":
			case "图瓦卢":
			case "千里达和多巴哥":
			case "土耳其":
			case "瑙鲁":
			case "汤加":
			case "荷兰":
			case "马绍尔群岛":
			case "土库曼":
			case "塔吉克":
			case "尼加拉瓜":
			case "蒙古":
			case "所罗门群岛":
			case "马提尼克岛":
			case "卡塔尔":
			case "蒙特塞拉特":
			case "马尔他":
			case "法属波利尼西亚":
			case "圣皮埃尔和密克隆群岛":
			case "叙利亚":
			case "墨西哥":
			case "阿曼":
			case "挪威":
			case "沙地阿拉伯":
			case "新喀里多尼亚":
			case "巴拿马":
			case "诺褔克岛":
			case "圣赫勒拿岛":
			areaLabel.html("2");
			break;
				
			case "博茨瓦纳":
			case "纳米比亚":
			case "莫桑比克":
			case "布隆迪":
			case "马达加斯加":
			case "科特迪瓦":
			case "贝宁":
			case "尼日尔":
			case "摩尔多瓦":
			case "刚果":
			case "拉脱维亚":
			case "保加利亚":
			case "布基纳法索":
			case "玻利维亚":
			case "波兰":
			case "立陶宛":
			case "莱索托":
			case "捷克":
			case "巴拉圭":
			case "毛里塔尼亚":
			case "留尼旺岛":
			case "罗马尼亚":
			case "塞尔维亚":
			case "俄罗斯":
			case "卢旺达":
			case "秘鲁":
			case "巴西":
			case "塞舌尔":
			case "苏丹":
			case "喀麦隆":
			case "波黑":
			case "尼日利亚":
			case "斯洛文尼亚":
			case "哥伦比亚":
			case "斯洛伐克":
			case "塞拉里昂":
			case "利比里亚":
			case "塞内加尔":
			case "索马里":
			case "苏里南":
			case "圣多美和普林西比":
			case "佛得角群岛":
			case "马拉维":
			case "斯威士兰":
			case "毛里求斯":
			case "乍得":
			case "多哥":
			case "阿根廷":
			case "马里":
			case "马其顿":
			case "突尼斯":
			case "黑山共和国":
			case "安哥拉":
			case "摩纳哥":
			case "摩洛哥":
			case "利比亚":
			case "荷属安的列斯群岛":
			case "坦桑尼亚":
			case "乌克兰":
			case "乌干达":
			case "白俄罗斯":
			case "乌拉圭":
			case "中非共和国":
			case "刚果":
			case "科摩罗":
			case "委内瑞拉":
			case "肯尼亚":
			case "智利":
			case "阿尔巴尼亚":
			case "匈牙利":
			case "克罗地亚":
			case "圭亚那":
			case "几内亚比绍":
			case "新几内亚":
			case "冈比亚":
			case "加纳":
			case "格鲁吉亚":
			case "加薘":
			case "福克兰群岛":
			case "埃塞俄比亚":
			case "厄立特里亚":
			case "埃及":
			case "爱沙尼亚":
			case "厄瓜多尔":
			case "阿尔及利亚":
			case "吉布提":
			case "南非":
			case "赞比亚":
			case "津巴布韦":
			areaLabel.html("3");
			break;
				
			};
		}
		//美国EUB省州分区
		if($(this).find(".dropitem[data-title='美国EUB省州分区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	area_usstate=$(this).find(".dropitem[data-title='美国EUB省州分区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".postcode").text()),
				postcode=address.substring(0,2);
			switch(postcode){
				case "94":
				case "95":
				case "96":
				case "97":
				case "98":
				case "99":
				area_usstate.html("2");
				break;
				
				case "30":
				case "31":
				case "32":
				case "33":
				case "34":
				case "35":
				case "36":
				case "37":
				case "38":
				case "39":
				case "40":
				case "41":
				case "42":
				case "43":
				case "44":
				case "45":
				case "46":
				case "47":
				case "48":
				case "49":
				case "50":
				case "51":
				case "52":
				case "53":
				case "54":
				case "55":
				case "56":
				case "57":
				case "58":
				case "59":
				case "60":
				case "61":
				case "62":
				case "63":
				case "64":
				case "65":
				case "66":
				case "67":
				case "68":
				case "69":
				case "60":
				case "61":
				case "62":
				case "63":
				case "64":
				case "65":
				case "66":
				case "67":
				case "68":
				case "69":
				case "70":
				case "71":
				case "72":
				case "73":
				case "74":
				case "75":
				case "76":
				case "77":
				case "78":
				case "79":
				area_usstate.html("3");
				break;
				
				case "80":
				case "81":
				case "82":
				case "83":
				case "84":
				case "85":
				case "86":
				case "87":
				case "88":
				case "89":
				case "90":
				case "91":
				case "92":
				case "93":
				area_usstate.html("4");
				break;
				
				default:area_usstate.html("1");break;
			};
		};
		//国际挂号小包分拣区
		if($(this).find(".dropitem[data-title='国际挂号小包分拣区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='国际挂号小包分拣区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "俄罗斯":
				areaLabel.html("RU21");
				break;
				
				case "美国":
				areaLabel.html("US22");
				break;

				case "英国":
				areaLabel.html("GB23");
				break;

				case "巴西":
				areaLabel.html("BR24");
				break;
				
				case "澳大利亚":
				areaLabel.html("AU25");
				break;

				case "法国":
				areaLabel.html("FR26");
				break;
				
				case "西班牙":
				areaLabel.html("ES27");
				break;

				case "加拿大":
				areaLabel.html("CA28");
				break;

				case "以色列":
				areaLabel.html("IL29");
				break;

				case "意大利":
				areaLabel.html("IT30");
				break;

				case "德国":
				areaLabel.html("DE31");
				break;
				
				case "智利":
				areaLabel.html("CL32");
				break;

				case "瑞典":
				areaLabel.html("SE33");
				break;

				case "白俄罗斯":
				areaLabel.html("BY34");
				break;

				case "挪威":
				areaLabel.html("NO35");
				break;

				case "荷兰":
				areaLabel.html("NL36");
				break;

				case "乌克兰":
				areaLabel.html("UA37");
				break;

				case "瑞士":
				areaLabel.html("CH38");
				break;

				case "墨西哥":
				areaLabel.html("MX39");
				break;

				case "波兰":
				areaLabel.html("PL40");
				break;
				
			};
		}
		//国际挂号小包分区
		if($(this).find(".dropitem[data-title='国际挂号小包分区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='国际挂号小包分区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "澳大利亚":
				case "以色列":
				case "意大利":
				case "德国":
				case "瑞典":
				case "挪威":
				case "荷兰":
				case "瑞士":
				case "波兰":
				areaLabel.html("3");
				break;

				case "美国":
				case "英国":
				case "法国":
				case "西班牙":
				case "加拿大":
				case "白俄罗斯":
				case "乌克兰":
				areaLabel.html("5");
				break;
				
				case "巴西":
				case "墨西哥":
				areaLabel.html("7");
				break;

				case "智利":
				areaLabel.html("8");
				break;

				case "俄罗斯":
				areaLabel.html("11");
				break;				
			};
		}
		//国际平常小包分区
		if($(this).find(".dropitem[data-title='国际平常小包分区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel=$(this).find(".dropitem[data-title='国际平常小包分区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "意大利":
				case "瑞士":
				case "波兰":
				areaLabel.html("3");
				break;

				case "美国":
				case "法国":
				case "西班牙":
				case "加拿大":
				case "白俄罗斯":
				case "乌克兰":
				areaLabel.html("4");
				break;
				
				case "巴西":
				case "墨西哥":
				areaLabel.html("5");
				break;

				case "智利":
				areaLabel.html("6");
				break;

				case "俄罗斯":
				areaLabel.html("7");
				break;

				case "英国":
				case "澳大利亚":
				case "以色列":
				case "德国":
				case "瑞典":
				case "挪威":
				case "荷兰":
				areaLabel.html("8");
				break;				
			};
		}
		//线上发货国家分区
		if($(this).find(".dropitem[data-title='线上发货国家分区']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel2=$(this).find(".dropitem[data-title='线上发货国家分区']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "日本":
				areaLabel2.html("1");
				break;
				
				case "韩国":
				case "马来西亚":
				case "泰国":
				case "新加坡":
				case "印度":
				case "印度尼西亚":
				areaLabel2.html("2");
				break;
				
				case "爱尔兰":
				case "奥地利":
				case "澳大利亚":
				case "保加利亚":
				case "比利时":
				case "波兰":
				case "丹麦":
				case "德国":
				case "芬兰":
				case "荷兰":
				case "捷克":
				case "克罗地亚":
				case "挪威":
				case "葡萄牙":
				case "瑞典":
				case "瑞士":
				case "斯洛伐克":
				case "希腊":
				case "匈牙利":
				case "以色列":
				case "意大利":
				areaLabel2.html("3");
				break;
				
				case "土耳其":
				case "新西兰":
				areaLabel2.html("4");
				break;
				
				case "阿曼":
				case "阿塞拜疆":
				case "爱沙尼亚":
				case "巴基斯坦":
				case "白俄罗斯":
				case "波黑":
				case "朝鲜":
				case "法国":
				case "菲律宾":
				case "哈萨克斯坦":
				case "吉尔吉斯斯坦":
				case "加拿大":
				case "卡塔尔":
				case "拉脱维亚":
				case "立陶宛":
				case "卢森堡":
				case "罗马尼亚":
				case "马耳他":
				case "美国":
				case "蒙古":
				case "塞浦路斯":
				case "沙特阿拉伯":
				case "斯里兰卡":
				case "斯洛文尼亚":
				case "乌克兰":
				case "乌兹别克斯坦":
				case "西班牙":
				case "叙利亚":
				case "亚美尼亚":
				case "英国":
				case "越南":
				areaLabel2.html("5");
				break;
				
				case "南非":
				areaLabel2.html("6");
				break;
				
				case "阿根廷":
				case "巴西":
				case "墨西哥":
				areaLabel2.html("7");
				break;
				
				case "阿富汗":
				case "阿拉伯联合酋长国":
				case "阿联酋":
				case "巴林":
				case "不丹":
				case "柬埔寨":
				case "科威特":
				case "老挝":
				case "黎巴嫩":
				case "马尔代夫":
				case "孟加拉":
				case "秘鲁":
				case "缅甸":
				case "尼泊尔":
				case "文莱":
				case "也门":
				case "伊拉克":
				case "伊朗":
				case "约旦":
				case "智利":
				areaLabel2.html("8");
				break;
				
				case "阿尔巴尼亚":
				case "安道尔":
				case "冰岛":
				case "法罗群岛":
				case "梵蒂冈":
				case "格鲁吉亚":
				case "列支敦士登":
				case "马其顿":
				case "摩尔多瓦":
				case "摩纳哥":
				case "塞尔维亚":
				case "圣马力诺":
				case "直布罗陀":
				areaLabel2.html("9");
				break;
				
				case "阿尔及利亚":
				case "阿鲁巴":
				case "埃及":
				case "埃塞俄比亚":
				case "安哥拉":
				case "安圭拉":
				case "安提瓜":
				case "巴巴多斯":
				case "巴布亚新几内亚":
				case "巴哈马":
				case "巴拉圭":
				case "巴拿马":
				case "百慕大":
				case "贝宁":
				case "波多黎各":
				case "玻利维亚":
				case "伯利兹":
				case "博茨瓦纳":
				case "布隆迪":
				case "赤道几内亚":
				case "多哥":
				case "多米尼加":
				case "厄瓜多尔":
				case "法属波利尼西亚":
				case "法属圭亚那":
				case "斐济":
				case "冈比亚":
				case "哥伦比亚":
				case "哥斯达黎加":
				case "格林纳达":
				case "格陵兰岛":
				case "古巴":
				case "瓜德罗普":
				case "关岛":
				case "圭亚那":
				case "海地":
				case "荷属安的列斯群岛":
				case "洪都拉斯":
				case "基里巴斯":
				case "吉布提":
				case "几内亚":
				case "几内亚比绍":
				case "加纳":
				case "加蓬":
				case "津巴布韦":
				case "喀麦隆":
				case "开曼群岛":
				case "科特迪瓦":
				case "肯尼亚":
				case "库克群岛":
				case "利比里亚":
				case "利比亚":
				case "留尼旺岛":
				case "卢旺达":
				case "马达加斯加":
				case "马德拉群岛":
				case "马拉维":
				case "马里":
				case "马绍尔群岛":
				case "马提尼克":
				case "毛里求斯":
				case "毛里塔尼亚":
				case "美属萨摩亚":
				case "密克罗尼西亚":
				case "摩洛哥":
				case "莫桑比克":
				case "纳米比亚":
				case "瑙鲁共和国":
				case "尼加拉瓜":
				case "尼日尔":
				case "尼日利亚":
				case "萨尔瓦多":
				case "塞拉利昂":
				case "塞内加尔":
				case "塞舌尔":
				case "圣皮埃尔和密克隆":
				case "斯威士兰":
				case "苏丹":
				case "苏里南":
				case "索马里":
				case "坦桑尼亚":
				case "汤加":
				case "特立尼达和多巴哥":
				case "突尼斯":
				case "瓦努阿图":
				case "危地马拉":
				case "委内瑞拉":
				case "乌干达":
				case "乌拉圭":
				case "新喀里多尼亚":
				case "牙买加":
				case "亚速尔群岛":
				case "赞比亚":
				areaLabel2.html("10");
				break;
				
				case "俄罗斯":
				areaLabel2.html("11");
				break;
				
				case "香港":
				case "澳门":
				case "台湾":
				areaLabel2.html("12");
				break;
			};
		};
		//wish邮分区1
		if($(this).find(".dropitem[data-title='wish邮分区1']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel2=$(this).find(".dropitem[data-title='wish邮分区1']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "俄罗斯":
				areaLabel2.html("21");
				break;

				case "美国":
				areaLabel2.html("22");
				break;
				
				case "英国":
				areaLabel2.html("23");
				break;
				
				case "巴西":
				areaLabel2.html("24");
				break;

				case "澳大利亚":
				areaLabel2.html("25");
				break;
				
				case "法国":
				areaLabel2.html("26");
				break;

				case "西班牙":
				areaLabel2.html("27");
				break;

				case "加拿大":
				areaLabel2.html("28");
				break;
				
				case "以色列":
				areaLabel2.html("29");
				break;

				case "意大利":
				areaLabel2.html("30");
				break;
				
				case "德国":
				areaLabel2.html("31");
				break;
				
				case "智利":
				areaLabel2.html("32");
				break;

				case "瑞典":
				areaLabel2.html("33");
				break;
				
				case "白俄罗斯":
				areaLabel2.html("34");
				break;

				case "挪威":
				areaLabel2.html("35");
				break;

				case "荷兰":
				areaLabel2.html("36");
				break;
				
				case "乌克兰":
				areaLabel2.html("37");
				break;
				
				case "瑞士":
				areaLabel2.html("38");
				break;

				case "墨西哥":
				areaLabel2.html("39");
				break;

				case "波兰":
				areaLabel2.html("40");
				break;
			};
		};
		//wish邮分区2(挂号)
		if($(this).find(".dropitem[data-title='wish邮分区2(挂号)']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel2=$(this).find(".dropitem[data-title='wish邮分区2(挂号)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "澳大利亚":
				case "以色列":
				case "意大利":
				case "德国":
				case "瑞典":
				case "挪威":
				case "荷兰":
				case "瑞士":
				case "波兰":
				areaLabel2.html("3");
				break;

				case "美国":
				case "英国":
				case "法国":
				case "西班牙":
				case "加拿大":
				case "白俄罗斯":
				case "乌克兰":
				areaLabel2.html("5");
				break;

				case "巴西":
				case "墨西哥":
				areaLabel2.html("7");
				break;

				case "智利":
				areaLabel2.html("8");
				break;

				case "俄罗斯":
				areaLabel2.html("11");
				break;
			};
		};
		//wish邮分区3
		if($(this).find(".dropitem[data-title='wish邮分区3']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	area_usstate=$(this).find(".dropitem[data-title='wish邮分区3']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".postcode").text()),
				countrycn=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				postcode=address.substring(0,1);
			if(countrycn == '(美国)'){
				if(postcode == 0 || postcode == 1 || postcode == 2 || postcode == 3 || postcode == 4 || postcode == 5 || postcode == 6){
					area_usstate.html("美国1(USJFKA)");
				}else if(postcode == 7 || postcode == 8){
					area_usstate.html("美国2(USSFOA)");
				}else{
					area_usstate.html("美国3(USLAXA)");
				}
			}else{
				area_usstate.html(countrycn);
			};
		};
		//wish邮分区4(平邮)
		if($(this).find(".dropitem[data-title='wish邮分区4(平邮)']").length>0 && $(this).find(".dropitem[data-title='收件人地址']").length>0){
			var	areaLabel2=$(this).find(".dropitem[data-title='wish邮分区4(平邮)']").find(".detail"),
				address=trim($(this).find(".dropitem[data-title='收件人地址']").find(".country_cn").text()),
				country_cn=address.substring(1,address.length-1);
			switch(country_cn){
				case "意大利":
				case "瑞士":
				case "波兰":
				areaLabel2.html("3");
				break;

				case "美国":
				case "法国":
				case "西班牙":
				case "加拿大":
				case "白俄罗斯":
				case "乌克兰":
				areaLabel2.html("4");
				break;
				
				case "巴西":
				case "墨西哥":
				areaLabel2.html("5");
				break;

				case "智利":
				areaLabel2.html("6");
				break;

				case "俄罗斯":
				areaLabel2.html("7");
				break;

				case "英国":
				case "澳大利亚":
				case "以色列":
				case "德国":
				case "瑞典":
				case "挪威":
				case "荷兰":
				areaLabel2.html("8");
				break;
			};
		};
		//4px分拣分区代码
		if($(this).find(".dropitem[data-title='4px分拣分区']").length>0 && $(this).find(".dropitem[data-title='4px分拣分区代码']").length>0){
			var	area_code=$(this).find(".dropitem[data-title='4px分拣分区代码']").find(".detail"),
				area=trim($(this).find(".dropitem[data-title='4px分拣分区']").find(".detail").text());
			switch(area){
				case "SYD":area_code.html("3A");break;
				case "MEL":area_code.html("3B");break;
				case "BNE":area_code.html("3D");break;
				case "PER":area_code.html("3C");break;
				case "DE":area_code.html("39");break;
				case "BR":area_code.html("2");break;
				case "IL":area_code.html("8");break;
				case "IT":area_code.html("15");break;
				case "NL":area_code.html("16");break;
				case "SE":area_code.html("22");break;
				case "DK":area_code.html("27");break;
				case "NO":area_code.html("21");break;
				case "ES":area_code.html("5");break;
				case "RU":area_code.html("1");break;
				case "FR":area_code.html("9");break;
				case "GB":area_code.html("10");break;
				case "GR":area_code.html("26");break;
				case "BE":area_code.html("25");break;
				case "IE":area_code.html("30");break;
				case "JFK":area_code.html("4A");break;
				case "ORD":area_code.html("4B");break;
				case "SFO":area_code.html("4C");break;
				case "LAX":area_code.html("4D");break;
				case "HNL":area_code.html("4E");break;
				case "KIX":area_code.html("14A");break;
				case "NRT":area_code.html("14B");break;
				case "HR":area_code.html("29");break;
				case "BG":area_code.html("33");break;
				case "SK":area_code.html("34");break;
				case "HU":area_code.html("31");break;
				case "CZ":area_code.html("35");break;
				case "FI":area_code.html("24");break;
				case "CH":area_code.html("19");break;
				case "PL":area_code.html("17");break;
				case "PT":area_code.html("28");break;
				case "NZ":area_code.html("18");break;
				case "TR":area_code.html("23");break;
				case "UA":area_code.html("11");break;
				case "EE":area_code.html("20");break;
				case "BY":area_code.html("12");break;
				case "LV":area_code.html("37");break;
				case "AR":area_code.html("13");break;
				case "CL":area_code.html("36");break;
				default:area_code.html("");break;
			};
		};
	});
});
</script>
 -->