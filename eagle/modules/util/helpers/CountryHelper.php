<?php
namespace eagle\modules\util\helpers;
use yii;
use eagle\models\SysCountry;
use common\helpers\Helper_Array;
use Qiniu\json_decode;
use eagle\modules\util\helpers\RedisHelper;

class CountryHelper {

    static function countryList($keywords){
        $data = [
            'ALB'=> 'AL','DZA'=> 'DZ','ATG'=> 'AG','ABW'=> 'AW','AUS'=> 'AU',
            'AUT'=> 'AT','BHR'=> 'BH','BEL'=> 'BE','BEN'=> 'BJ','BMU'=> 'BM',
            'BIH'=> 'BA','BWA'=> 'BW','BGR'=> 'BG','BFA'=> 'BF','MMR'=> 'MM',
            'BDI'=> 'BI','CMR'=> 'CM','CYM'=> 'KY','CHL'=> 'CL','COM'=> 'KM',
            'COG'=> 'CG','COK'=> 'CK','CIV'=> 'CI','HRV'=> 'HR','CZE'=> 'CZ',
            'DJI'=> 'DJ','DMA'=> 'DM','GNQ'=> 'GQ','EST'=> 'EE','ETH'=> 'ET',
            'FRA'=> 'FR','GUF'=> 'GF','GMB'=> 'GM','GEO'=> 'GE','DEU'=> 'DE',
            'GRC'=> 'GR','GRL'=> 'GL','GIN'=> 'GN','HTI'=> 'HT','HND'=> 'HN',
            'ISL'=> 'IS','IND'=> 'IN','IDN'=> 'ID','ISR'=> 'IL','JOR'=> 'JO',
            'KAZ'=> 'KZ','KIR'=> 'KI','PRK'=> 'KP','KWT'=> 'KW','LSO'=> 'LS',
            'LBY'=> 'LY','LIE'=> 'LI','LTU'=> 'LT','MAC'=> 'MO','MDG'=> 'MG',
            'MLI'=> 'ML','MHL'=> 'MH','MUS'=> 'MU','MYT'=> 'YT','MNG'=> 'MN',
            'MAR'=> 'MA','MOZ'=> 'MZ','NRU'=> 'NR','ANT'=> 'AN','NCL'=> 'NC',
            'NER'=> 'NE','PAK'=> 'PK','PNG'=> 'PG','PRY'=> 'PY','PER'=> 'PE',
            'PRI'=> 'PR','QAT'=> 'QA','SHN'=> 'SH','KNA'=> 'KN','SYC'=> 'SC',
            'SLE'=> 'SL','SLB'=> 'SB','SOM'=> 'SO','ESP'=> 'ES','SDN'=> 'SD',
            'SUR'=> 'SR','SWE'=> 'SE','SYR'=> 'SY','TJK'=> 'TJ','TZA'=> 'TZ',
            'TTO'=> 'TT','UGA'=> 'UG','UKR'=> 'UA','ARE'=> 'AE','GBR'=> 'GB',
            'URY'=> 'UY','VUT'=> 'VU','VAT'=> 'VA','VEN'=> 'VE','VNM'=> 'VN',
            'VIR'=> 'VI','ESH'=> 'EH','WSM'=> 'WS','YEM'=> 'YE','ZWE'=> 'ZW',
            'AGO'=> 'AO','JPN'=> 'JP','REU'=> 'RE','FSM'=> 'FM','AND'=> 'AD',
            'ARM'=> 'AM','BHS'=> 'BS','BLR'=> 'BY','BTN'=> 'BT','VGB'=> 'VG',
            'KHM'=> 'KH','CAF'=> 'CF','CYP'=> 'CY','DOM'=> 'DO','ERI'=> 'ER',
            'FJI'=> 'FJ','GAB'=> 'GA','GIB'=> 'GI','GUM'=> 'GU','GNB'=> 'GW',
            'HUN'=> 'HU','IRN'=> 'IR','KEN'=> 'KE','LAO'=> 'LA','LBR'=> 'LR',
            'LUX'=> 'LU','MYS'=> 'MY','MTQ'=> 'MQ','MEX'=> 'MX','MSR'=> 'MS',
            'NPL'=> 'NP','NZL'=> 'NZ','NOR'=> 'NO','PHL'=> 'PH','ROU'=> 'RO',
            'LCA'=> 'LC','SEN'=> 'SN','SVK'=> 'SK','ZAF'=> 'ZA','SJM'=> 'SJ',
            'TGO'=> 'TG','TUV'=> 'TV','AFG'=> 'AF','AZE'=> 'AZ','BRN'=> 'BN',
            'COL'=> 'CO','DNK'=> 'DK','FIN'=> 'FI','GRD'=> 'GD','HKG'=> 'HK',
            'LVA'=> 'LV','MWI'=> 'MW','MCO'=> 'MC','NIC'=> 'NI','SPM'=> 'PM',
            'SWZ'=> 'SZ','TUR'=> 'TR','AIA'=> 'AI','BOL'=> 'BO','CHN'=> 'CN',
            'EGY'=> 'EG','GHA'=> 'GH','IRQ'=> 'IQ','LBN'=> 'LB','MDA'=> 'MD',
            'SMR'=> 'SM','TWN'=> 'TW','OMN'=> 'OM','RUS'=> 'RU','TON'=> 'TO',
            'SGP'=> 'SG','ARG'=> 'AR','TCD'=> 'TD','PYF'=> 'PF','KGZ'=> 'KG',
            'NIU'=> 'NU','SVN'=> 'SI','BLZ'=> 'BZ','SLV'=> 'SV','GTM'=> 'GT',
            'ITA'=> 'IT','MDV'=> 'MV','NLD'=> 'NL','THA'=> 'TH','ASM'=> 'AS',
            'BGD'=> 'BD','BRA'=> 'BR','CPV'=> 'CV','CRI'=> 'CR','ECU'=> 'EC',
            'FLK'=> 'FK','GUY'=> 'GY','IRL'=> 'IE','KOR'=> 'KR','MKD'=> 'MK',
            'MRT'=> 'MR','NAM'=> 'NA','NGA'=> 'NG','POL'=> 'PL','PRT'=> 'PT',
            'VCT'=> 'VC','SAU'=> 'SA','LKA'=> 'LK','CHE'=> 'CH','TUN'=> 'TN',
            'TCA'=> 'TC','UZB'=> 'UZ','WLF'=> 'WF','ZMB'=> 'ZM','BRB'=> 'BB',
            'CAN'=> 'CA','CUB'=> 'CU','GLP'=> 'GP','JAM'=> 'JM','MLT'=> 'MT',
            'PAN'=> 'PA','RWA'=> 'RW','TKM'=> 'TM','USA'=> 'US','PLW'=> 'PW',
            'SRB'=> 'RS','MNE'=> 'ME','FRO'=> 'FO','PLE'=> 'PS','NFK'=>'NF',
            'GGY'=> 'GG','IMN'=> 'IM','UMI'=> 'UM','ASC'=> 'AC','CUW'=> 'CW',
            'JEY'=> 'JE','BLM'=> 'BL','MAF'=> 'MF',
        ];
        $keywords = strtoupper($keywords);
        return isset($data[$keywords]) ? $data[$keywords] : '';
    }

    static function countryData($keywords){
        $data = [
            'AL' => '{"country_code":"AL","country_en":"Albania","country_zh":"\u963f\u5c14\u5df4\u5c3c\u4e9a","region":"Europe"}',
            'DZ' => '{"country_code":"DZ","country_en":"Algeria","country_zh":"\u963f\u5c14\u53ca\u5229\u4e9a","region":"Africa"}',
            'AG' => '{"country_code":"AG","country_en":"Antigua and Barbuda","country_zh":"\u5b89\u63d0\u74dc\u548c\u5df4\u5e03\u8fbe","region":"Central America and Caribbean"}',
            'AW' => '{"country_code":"AW","country_en":"Aruba","country_zh":"\u963f\u9c81\u5df4","region":"Central America and Caribbean"}',
            'AU' => '{"country_code":"AU","country_en":"Australia","country_zh":"\u6fb3\u5927\u5229\u4e9a","region":"Oceania"}',
            'AT' => '{"country_code":"AT","country_en":"Austria","country_zh":"\u5965\u5730\u5229","region":"Europe"}',
            'BH' => '{"country_code":"BH","country_en":"Bahrain","country_zh":"\u5df4\u6797","region":"Middle East"}',
            'BE' => '{"country_code":"BE","country_en":"Belgium","country_zh":"\u6bd4\u5229\u65f6","region":"Europe"}',
            'BJ' => '{"country_code":"BJ","country_en":"Benin","country_zh":"\u8d1d\u5b81","region":"Africa"}',
            'BM' => '{"country_code":"BM","country_en":"Bermuda","country_zh":"\u767e\u6155\u5927\u7fa4\u5c9b","region":"North America"}',
            'BA' => '{"country_code":"BA","country_en":"Bosnia and Herzegovina","country_zh":"\u6ce2\u65af\u5c3c\u4e9a\u548c\u9ed1\u585e\u54e5\u7ef4\u90a3","region":"Europe"}',
            'BW' => '{"country_code":"BW","country_en":"Botswana","country_zh":"\u535a\u8328\u74e6\u7eb3","region":"Africa"}',
            'BG' => '{"country_code":"BG","country_en":"Bulgaria","country_zh":"\u4fdd\u52a0\u5229\u4e9a","region":"Europe"}',
            'BF' => '{"country_code":"BF","country_en":"Burkina Faso","country_zh":"\u5e03\u57fa\u7eb3\u6cd5\u7d22","region":"Africa"}',
            'MM' => '{"country_code":"MM","country_en":"Burma","country_zh":"\u7f05\u7538","region":"Southeast Asia"}',
            'BI' => '{"country_code":"BI","country_en":"Burundi","country_zh":"\u5e03\u9686\u8fea","region":"Africa"}',
            'CM' => '{"country_code":"CM","country_en":"Cameroon","country_zh":"\u5580\u9ea6\u9686","region":"Africa"}',
            'KY' => '{"country_code":"KY","country_en":"Cayman Islands","country_zh":"\u5f00\u66fc\u7fa4\u5c9b","region":"Central America and Caribbean"}',
            'CL' => '{"country_code":"CL","country_en":"Chile","country_zh":"\u667a\u5229","region":"South America"}',
            'KM' => '{"country_code":"KM","country_en":"Comoros","country_zh":"\u79d1\u6469\u7f57","region":"Africa"}',
            'CG' => '{"country_code":"CG","country_en":"Congo, Republic of the","country_zh":"\u521a\u679c","region":"Africa"}',
            'CK' => '{"country_code":"CK","country_en":"Cook Islands","country_zh":"\u5e93\u514b\u7fa4\u5c9b","region":"Oceania"}',
            'CI' => '{"country_code":"CI","country_en":"Cote d Ivoire (Ivory Coast)","country_zh":"\u8c61\u7259\u6d77\u5cb8","region":"Africa"}',
            'HR' => '{"country_code":"HR","country_en":"Croatia, Democratic Republic of the","country_zh":"\u514b\u7f57\u5730\u4e9a","region":"Europe"}',
            'CZ' => '{"country_code":"CZ","country_en":"Czech Republic","country_zh":"\u6377\u514b","region":"Europe"}',
            'DJ' => '{"country_code":"DJ","country_en":"Djibouti","country_zh":"\u5409\u5e03\u63d0","region":"Africa"}',
            'DM' => '{"country_code":"DM","country_en":"Dominica","country_zh":"\u591a\u7c73\u5c3c\u514b","region":"Central America and Caribbean"}',
            'GQ' => '{"country_code":"GQ","country_en":"Equatorial Guinea","country_zh":"\u8d64\u9053\u51e0\u5185\u4e9a","region":"Africa"}',
            'EE' => '{"country_code":"EE","country_en":"Estonia","country_zh":"\u7231\u6c99\u5c3c\u4e9a","region":"Europe"}',
            'ET' => '{"country_code":"ET","country_en":"Ethiopia","country_zh":"\u57c3\u585e\u4fc4\u6bd4\u4e9a","region":"Africa"}',
            'FR' => '{"country_code":"FR","country_en":"France","country_zh":"\u6cd5\u56fd","region":"Europe"}',
            'GF' => '{"country_code":"GF","country_en":"French Guiana","country_zh":"\u6cd5\u5c5e\u572d\u4e9a\u90a3","region":"South America"}',
            'GM' => '{"country_code":"GM","country_en":"Gambia","country_zh":"\u5188\u6bd4\u4e9a","region":"Africa"}',
            'GE' => '{"country_code":"GE","country_en":"Georgia","country_zh":"\u683c\u9c81\u5409\u4e9a","region":"Asia"}',
            'DE' => '{"country_code":"DE","country_en":"Germany","country_zh":"\u5fb7\u56fd","region":"Europe"}',
            'GR' => '{"country_code":"GR","country_en":"Greece","country_zh":"\u5e0c\u814a","region":"Europe"}',
            'GL' => '{"country_code":"GL","country_en":"Greenland","country_zh":"\u683c\u9675\u5170","region":"North America"}',
            'GN' => '{"country_code":"GN","country_en":"Guinea","country_zh":"\u51e0\u5185\u4e9a","region":"Africa"}',
            'HT' => '{"country_code":"HT","country_en":"Haiti","country_zh":"\u6d77\u5730","region":"Central America and Caribbean"}',
            'HN' => '{"country_code":"HN","country_en":"Honduras","country_zh":"\u6d2a\u90fd\u62c9\u65af","region":"Central America and Caribbean"}',
            'IS' => '{"country_code":"IS","country_en":"Iceland","country_zh":"\u51b0\u5c9b","region":"Europe"}',
            'IN' => '{"country_code":"IN","country_en":"India","country_zh":"\u5370\u5ea6","region":"Asia"}',
            'ID' => '{"country_code":"ID","country_en":"Indonesia","country_zh":"\u5370\u5ea6\u5c3c\u897f\u4e9a","region":"Southeast Asia"}',
            'IL' => '{"country_code":"IL","country_en":"Israel","country_zh":"\u4ee5\u8272\u5217","region":"Middle East"}',
            'JO' => '{"country_code":"JO","country_en":"Jordan","country_zh":"\u7ea6\u65e6","region":"Middle East"}',
            'KZ' => '{"country_code":"KZ","country_en":"Kazakhstan","country_zh":"\u54c8\u8428\u514b\u65af\u5766","region":"Asia"}',
            'KI' => '{"country_code":"KI","country_en":"Kiribati","country_zh":"\u57fa\u91cc\u5df4\u65af","region":"Oceania"}',
            'KP' => '{"country_code":"KP","country_en":"Korea, North","country_zh":"\u671d\u9c9c","region":"Asia"}',
            'KW' => '{"country_code":"KW","country_en":"Kuwait","country_zh":"\u79d1\u5a01\u7279","region":"Middle East"}',
            'LS' => '{"country_code":"LS","country_en":"Lesotho","country_zh":"\u83b1\u7d22\u6258","region":"Africa"}',
            'LY' => '{"country_code":"LY","country_en":"Libya","country_zh":"\u5229\u6bd4\u4e9a","region":"Africa"}',
            'LI' => '{"country_code":"LI","country_en":"Liechtenstein","country_zh":"\u5217\u652f\u6566\u58eb\u767b","region":"Europe"}',
            'LT' => '{"country_code":"LT","country_en":"Lithuania","country_zh":"\u7acb\u9676\u5b9b","region":"Europe"}',
            'MO' => '{"country_code":"MO","country_en":"Macau","country_zh":"\u6fb3\u95e8","region":"Southeast Asia"}',
            'MG' => '{"country_code":"MG","country_en":"Madagascar","country_zh":"\u9a6c\u8fbe\u52a0\u65af\u52a0","region":"Africa"}',
            'ML' => '{"country_code":"ML","country_en":"Mali","country_zh":"\u9a6c\u91cc","region":"Africa"}',
            'MH' => '{"country_code":"MH","country_en":"Marshall Islands","country_zh":"\u9a6c\u7ecd\u5c14\u7fa4\u5c9b","region":"Oceania"}',
            'MU' => '{"country_code":"MU","country_en":"Mauritius","country_zh":"\u6bdb\u91cc\u6c42\u65af","region":"Africa"}',
            'YT' => '{"country_code":"YT","country_en":"Mayotte","country_zh":"\u9a6c\u7ea6\u7279\u5c9b","region":"Africa"}',
            'MN' => '{"country_code":"MN","country_en":"Mongolia","country_zh":"\u8499\u53e4","region":"Asia"}',
            'MA' => '{"country_code":"MA","country_en":"Morocco","country_zh":"\u6469\u6d1b\u54e5","region":"Africa"}',
            'MZ' => '{"country_code":"MZ","country_en":"Mozambique","country_zh":"\u83ab\u6851\u6bd4\u514b","region":"Africa"}',
            'NR' => '{"country_code":"NR","country_en":"Nauru","country_zh":"\u7459\u9c81","region":"Oceania"}',
            'AN' => '{"country_code":"AN","country_en":"Netherlands Antilles","country_zh":"\u8377\u5c5e\u5b89\u7684\u5217\u65af","region":"Central America and Caribbean"}',
            'NC' => '{"country_code":"NC","country_en":"New Caledonia","country_zh":"\u65b0\u5580\u91cc\u591a\u5c3c\u4e9a","region":"Oceania"}',
            'NE' => '{"country_code":"NE","country_en":"Niger","country_zh":"\u5c3c\u65e5\u5c14","region":"Africa"}',
            'PK' => '{"country_code":"PK","country_en":"Pakistan","country_zh":"\u5df4\u57fa\u65af\u5766","region":"Asia"}',
            'PG' => '{"country_code":"PG","country_en":"Papua New Guinea","country_zh":"\u5df4\u5e03\u4e9a\u65b0\u51e0\u5185\u4e9a","region":"Oceania"}',
            'PY' => '{"country_code":"PY","country_en":"Paraguay","country_zh":"\u5df4\u62c9\u572d","region":"South America"}',
            'PE' => '{"country_code":"PE","country_en":"Peru","country_zh":"\u79d8\u9c81","region":"South America"}',
            'PR' => '{"country_code":"PR","country_en":"Puerto Rico","country_zh":"\u6ce2\u591a\u9ece\u5404","region":"Central America and Caribbean"}',
            'QA' => '{"country_code":"QA","country_en":"Qatar","country_zh":"\u5361\u5854\u5c14","region":"Middle East"}',
            'SH' => '{"country_code":"SH","country_en":"Saint Helena","country_zh":"\u5723\u8d6b\u52d2\u62ff","region":"Africa"}',
            'KN' => '{"country_code":"KN","country_en":"Saint Kitts-Nevis","country_zh":"\u5723\u57fa\u8328\u548c\u5c3c\u7ef4\u65af","region":"Central America and Caribbean"}',
            'SC' => '{"country_code":"SC","country_en":"Seychelles","country_zh":"\u585e\u820c\u5c14","region":"Africa"}',
            'SL' => '{"country_code":"SL","country_en":"Sierra Leone","country_zh":"\u585e\u62c9\u5229\u6602","region":"Africa"}',
            'SB' => '{"country_code":"SB","country_en":"Solomon Islands","country_zh":"\u6240\u7f57\u95e8\u7fa4\u5c9b","region":"Oceania"}',
            'SO' => '{"country_code":"SO","country_en":"Somalia","country_zh":"\u7d22\u9a6c\u91cc","region":"Africa"}',
            'ES' => '{"country_code":"ES","country_en":"Spain","country_zh":"\u897f\u73ed\u7259","region":"Europe"}',
            'SD' => '{"country_code":"SD","country_en":"Sudan","country_zh":"\u82cf\u4e39","region":"Africa"}',
            'SR' => '{"country_code":"SR","country_en":"Suriname","country_zh":"\u82cf\u91cc\u5357","region":"South America"}',
            'SE' => '{"country_code":"SE","country_en":"Sweden","country_zh":"\u745e\u5178","region":"Europe"}',
            'SY' => '{"country_code":"SY","country_en":"Syria","country_zh":"\u53d9\u5229\u4e9a","region":"Middle East"}',
            'TJ' => '{"country_code":"TJ","country_en":"Tajikistan","country_zh":"\u5854\u5409\u514b\u65af\u5766","region":"Asia"}',
            'TZ' => '{"country_code":"TZ","country_en":"Tanzania","country_zh":"\u5766\u6851\u5c3c\u4e9a","region":"Africa"}',
            'TT' => '{"country_code":"TT","country_en":"Trinidad and Tobago","country_zh":"\u7279\u7acb\u5c3c\u8fbe\u548c\u591a\u5df4\u54e5","region":"Central America and Caribbean"}',
            'UG' => '{"country_code":"UG","country_en":"Uganda","country_zh":"\u4e4c\u5e72\u8fbe","region":"Africa"}',
            'UA' => '{"country_code":"UA","country_en":"Ukraine","country_zh":"\u4e4c\u514b\u5170","region":"Europe"}',
            'AE' => '{"country_code":"AE","country_en":"United Arab Emirates","country_zh":"\u963f\u62c9\u4f2f\u8054\u5408\u914b\u957f\u56fd","region":"Middle East"}',
            'GB' => '{"country_code":"GB","country_en":"Great Britain","country_zh":"\u82f1\u56fd","region":"Europe"}',
            'UY' => '{"country_code":"UY","country_en":"Uruguay","country_zh":"\u4e4c\u62c9\u572d","region":"South America"}',
            'VU' => '{"country_code":"VU","country_en":"Vanuatu","country_zh":"\u74e6\u52aa\u963f\u56fe","region":"Oceania"}',
            'VA' => '{"country_code":"VA","country_en":"Vatican City State","country_zh":"\u68b5\u8482\u5188\u57ce\u56fd","region":"Europe"}',
            'VE' => '{"country_code":"VE","country_en":"Venezuela","country_zh":"\u59d4\u5185\u745e\u62c9","region":"South America"}',
            'VN' => '{"country_code":"VN","country_en":"Vietnam","country_zh":"\u8d8a\u5357","region":"Southeast Asia"}',
            'VI' => '{"country_code":"VI","country_en":"Virgin Islands (U.S.)","country_zh":"\u7ef4\u5c14\u4eac\u7fa4\u5c9b\uff08\u7f8e\u56fd\uff09","region":"Central America and Caribbean"}',
            'EH' => '{"country_code":"EH","country_en":"Western Sahara","country_zh":"\u897f\u6492\u54c8\u62c9","region":"Africa"}',
            'WS' => '{"country_code":"WS","country_en":"Western Samoa","country_zh":"\u897f\u8428\u6469\u4e9a","region":"Oceania"}',
            'YE' => '{"country_code":"YE","country_en":"Yemen","country_zh":"\u4e5f\u95e8","region":"Middle East"}',
            'ZW' => '{"country_code":"ZW","country_en":"Zimbabwe","country_zh":"\u6d25\u5df4\u5e03\u97e6","region":"Africa"}',
            'AO' => '{"country_code":"AO","country_en":"Angola","country_zh":"\u5b89\u54e5\u62c9","region":"Africa"}',
            'AA' => '{"country_code":"AA","country_en":"APO\/FPO","country_zh":"APO \/ FPO","region":"North America"}',
            'JP' => '{"country_code":"JP","country_en":"Japan","country_zh":"\u65e5\u672c","region":"Asia"}',
            'RE' => '{"country_code":"RE","country_en":"R\u00e9union","country_zh":"\u7559\u5c3c\u6c6a","region":"Africa"}',
            'FM' => '{"country_code":"FM","country_en":"Micronesia","country_zh":"\u5bc6\u514b\u7f57\u5c3c\u897f\u4e9a","region":"Oceania"}',
            'AD' => '{"country_code":"AD","country_en":"Andorra","country_zh":"\u5b89\u9053\u5c14\u5171\u548c\u56fd","region":"Europe"}',
            'AM' => '{"country_code":"AM","country_en":"Armenia","country_zh":"\u4e9a\u7f8e\u5c3c\u4e9a","region":"Asia"}',
            'BS' => '{"country_code":"BS","country_en":"Bahamas","country_zh":"\u5df4\u54c8\u9a6c","region":"Central America and Caribbean"}',
            'BY' => '{"country_code":"BY","country_en":"Belarus","country_zh":"\u767d\u4fc4\u7f57\u65af","region":"Europe"}',
            'BT' => '{"country_code":"BT","country_en":"Bhutan","country_zh":"\u4e0d\u4e39","region":"Asia"}',
            'VG' => '{"country_code":"VG","country_en":"British Virgin Islands","country_zh":"\u82f1\u5c5e\u7ef4\u5c14\u4eac\u7fa4\u5c9b","region":"Central America and Caribbean"}',
            'KH' => '{"country_code":"KH","country_en":"Cambodia","country_zh":"\u67ec\u57d4\u5be8","region":"Southeast Asia"}',
            'CF' => '{"country_code":"CF","country_en":"Central African Republic","country_zh":"\u4e2d\u975e\u5171\u548c\u56fd","region":"Africa"}',
            'CD' => '{"country_code":"CD","country_en":"Congo, Democratic Republic of the","country_zh":"\u521a\u679c\u6c11\u4e3b\u5171\u548c\u56fd","region":"Africa"}',
            'CY' => '{"country_code":"CY","country_en":"Cyprus","country_zh":"\u585e\u6d66\u8def\u65af","region":"Europe"}',
            'DO' => '{"country_code":"DO","country_en":"Dominican Republic","country_zh":"\u591a\u7c73\u5c3c\u52a0\u5171\u548c\u56fd","region":"Central America and Caribbean"}',
            'ER' => '{"country_code":"ER","country_en":"Eritrea","country_zh":"\u5384\u7acb\u7279\u91cc\u4e9a","region":"Africa"}',
            'FJ' => '{"country_code":"FJ","country_en":"Fiji","country_zh":"\u6590\u6d4e","region":"Oceania"}',
            'GA' => '{"country_code":"GA","country_en":"Gabon Republic","country_zh":"\u52a0\u84ec","region":"Africa"}',
            'GI' => '{"country_code":"GI","country_en":"Gibraltar","country_zh":"\u76f4\u5e03\u7f57\u9640","region":"Europe"}',
            'GU' => '{"country_code":"GU","country_en":"Guam","country_zh":"\u5173\u5c9b","region":"Oceania"}',
            'GW' => '{"country_code":"GW","country_en":"Guinea-Bissau","country_zh":"\u51e0\u5185\u4e9a\u6bd4\u7ecd","region":"Africa"}',
            'HU' => '{"country_code":"HU","country_en":"Hungary","country_zh":"\u5308\u7259\u5229","region":"Europe"}',
            'IR' => '{"country_code":"IR","country_en":"Iran","country_zh":"\u4f0a\u6717","region":"Asia"}',
            'KE' => '{"country_code":"KE","country_en":"Kenya Coast Republic","country_zh":"\u80af\u5c3c\u4e9a","region":"Africa"}',
            'LA' => '{"country_code":"LA","country_en":"Laos","country_zh":"\u8001\u631d","region":"Southeast Asia"}',
            'LR' => '{"country_code":"LR","country_en":"Liberia","country_zh":"\u5229\u6bd4\u91cc\u4e9a","region":"Africa"}',
            'LU' => '{"country_code":"LU","country_en":"Luxembourg","country_zh":"\u5362\u68ee\u5821","region":"Europe"}',
            'MY' => '{"country_code":"MY","country_en":"Malaysia","country_zh":"\u9a6c\u6765\u897f\u4e9a","region":"Southeast Asia"}',
            'MQ' => '{"country_code":"MQ","country_en":"Martinique","country_zh":"\u9a6c\u63d0\u5c3c\u514b","region":"Central America and Caribbean"}',
            'MX' => '{"country_code":"MX","country_en":"Mexico","country_zh":"\u58a8\u897f\u54e5","region":"North America"}',
            'MS' => '{"country_code":"MS","country_en":"Montserrat","country_zh":"\u8499\u7279\u585e\u62c9\u7279\u5c9b","region":"Central America and Caribbean"}',
            'NP' => '{"country_code":"NP","country_en":"Nepal","country_zh":"\u5c3c\u6cca\u5c14","region":"Asia"}',
            'NZ' => '{"country_code":"NZ","country_en":"New Zealand","country_zh":"\u65b0\u897f\u5170","region":"Oceania"}',
            'NO' => '{"country_code":"NO","country_en":"Norway","country_zh":"\u632a\u5a01","region":"Europe"}',
            'PH' => '{"country_code":"PH","country_en":"Philippines","country_zh":"\u83f2\u5f8b\u5bbe","region":"Southeast Asia"}',
            'RO' => '{"country_code":"RO","country_en":"Romania","country_zh":"\u7f57\u9a6c\u5c3c\u4e9a","region":"Europe"}',
            'LC' => '{"country_code":"LC","country_en":"Saint Lucia","country_zh":"\u5723\u5362\u897f\u4e9a","region":"Central America and Caribbean"}',
            'SN' => '{"country_code":"SN","country_en":"Senegal","country_zh":"\u585e\u5185\u52a0\u5c14","region":"Africa"}',
            'SK' => '{"country_code":"SK","country_en":"Slovakia","country_zh":"\u65af\u6d1b\u4f10\u514b","region":"Europe"}',
            'ZA' => '{"country_code":"ZA","country_en":"South Africa","country_zh":"\u5357\u975e","region":"Africa"}',
            'SJ' => '{"country_code":"SJ","country_en":"Svalbard","country_zh":"\u65af\u74e6\u5c14\u5df4\u5fb7\u7fa4\u5c9b","region":"Europe"}',
            'TG' => '{"country_code":"TG","country_en":"Togo","country_zh":"\u591a\u54e5","region":"Africa"}',
            'TV' => '{"country_code":"TV","country_en":"Tuvalu","country_zh":"\u56fe\u74e6\u5362","region":"Oceania"}',
            'AF' => '{"country_code":"AF","country_en":"Afghanistan","country_zh":"\u963f\u5bcc\u6c57","region":"Asia"}',
            'AZ' => '{"country_code":"AZ","country_en":"Azerbaijan Republic","country_zh":"\u963f\u585e\u62dc\u7586","region":"Asia"}',
            'BN' => '{"country_code":"BN","country_en":"Brunei Darussalam","country_zh":"\u6587\u83b1","region":"Southeast Asia"}',
            'CO' => '{"country_code":"CO","country_en":"Colombia","country_zh":"\u54e5\u4f26\u6bd4\u4e9a","region":"South America"}',
            'DK' => '{"country_code":"DK","country_en":"Denmark","country_zh":"\u4e39\u9ea6","region":"Europe"}',
            'FI' => '{"country_code":"FI","country_en":"Finland","country_zh":"\u82ac\u5170","region":"Europe"}',
            'GD' => '{"country_code":"GD","country_en":"Grenada","country_zh":"\u683c\u6797\u7eb3\u8fbe","region":"Central America and Caribbean"}',
            'HK' => '{"country_code":"HK","country_en":"Hong Kong","country_zh":"\u9999\u6e2f","region":"Southeast Asia"}',
            'LV' => '{"country_code":"LV","country_en":"Latvia","country_zh":"\u62c9\u8131\u7ef4\u4e9a","region":"Europe"}',
            'MW' => '{"country_code":"MW","country_en":"Malawi","country_zh":"\u9a6c\u62c9\u7ef4","region":"Africa"}',
            'MC' => '{"country_code":"MC","country_en":"Monaco","country_zh":"\u6469\u7eb3\u54e5","region":"Europe"}',
            'NI' => '{"country_code":"NI","country_en":"Nicaragua","country_zh":"\u5c3c\u52a0\u62c9\u74dc","region":"Central America and Caribbean"}',
            'PM' => '{"country_code":"PM","country_en":"Saint Pierre and Miquelon","country_zh":"\u5723\u76ae\u57c3\u5c14\u548c\u5bc6\u514b\u9686","region":"North America"}',
            'SZ' => '{"country_code":"SZ","country_en":"Swaziland","country_zh":"\u65af\u5a01\u58eb\u5170","region":"Africa"}',
            'TR' => '{"country_code":"TR","country_en":"Turkey","country_zh":"\u571f\u8033\u5176","region":"Middle East"}',
            'AI' => '{"country_code":"AI","country_en":"Anguilla","country_zh":"\u5b89\u572d\u62c9\u5c9b","region":"Central America and Caribbean"}',
            'BO' => '{"country_code":"BO","country_en":"Bolivia","country_zh":"\u73bb\u5229\u7ef4\u4e9a","region":"South America"}',
            'CN' => '{"country_code":"CN","country_en":"China","country_zh":"\u4e2d\u56fd","region":"Asia"}',
            'EG' => '{"country_code":"EG","country_en":"Egypt","country_zh":"\u57c3\u53ca","region":"Africa"}',
            'GH' => '{"country_code":"GH","country_en":"Ghana","country_zh":"\u52a0\u7eb3","region":"Africa"}',
            'IQ' => '{"country_code":"IQ","country_en":"Iraq","country_zh":"\u4f0a\u62c9\u514b","region":"Middle East"}',
            'LB' => '{"country_code":"LB","country_en":"Lebanon, South","country_zh":"\u9ece\u5df4\u5ae9","region":"Middle East"}',
            'MD' => '{"country_code":"MD","country_en":"Moldova","country_zh":"\u6469\u5c14\u591a\u74e6","region":"Europe"}',
            'SM' => '{"country_code":"SM","country_en":"San Marino","country_zh":"\u5723\u9a6c\u529b\u8bfa","region":"Europe"}',
            'TW' => '{"country_code":"TW","country_en":"Taiwan","country_zh":"\u53f0\u6e7e\u7701","region":"Southeast Asia"}',
            'OM' => '{"country_code":"OM","country_en":"Oman","country_zh":"\u963f\u66fc","region":"Middle East"}',
            'RU' => '{"country_code":"RU","country_en":"Russian Federation","country_zh":"\u4fc4\u7f57\u65af","region":"Asia"}',
            'TO' => '{"country_code":"TO","country_en":"Tonga","country_zh":"\u6c64\u52a0","region":"Oceania"}',
            'SG' => '{"country_code":"SG","country_en":"Singapore","country_zh":"\u65b0\u52a0\u5761","region":"Southeast Asia"}',
            'AR' => '{"country_code":"AR","country_en":"Argentina","country_zh":"\u963f\u6839\u5ef7","region":"South America"}',
            'TD' => '{"country_code":"TD","country_en":"Chad","country_zh":"\u4e4d\u5f97","region":"Africa"}',
            'PF' => '{"country_code":"PF","country_en":"French Polynesia","country_zh":"\u6cd5\u5c5e\u73bb\u5229\u5c3c\u897f\u4e9a","region":"Oceania"}',
            'KG' => '{"country_code":"KG","country_en":"Kyrgyzstan","country_zh":"\u5409\u5c14\u5409\u65af\u5766","region":"Asia"}',
            'NU' => '{"country_code":"NU","country_en":"Niue","country_zh":"\u7ebd\u57c3","region":"Oceania"}',
            'SI' => '{"country_code":"SI","country_en":"Slovenia","country_zh":"\u65af\u6d1b\u6587\u5c3c\u4e9a","region":"Europe"}',
            'BZ' => '{"country_code":"BZ","country_en":"Belize","country_zh":"\u4f2f\u5229\u5179","region":"Central America and Caribbean"}',
            'SV' => '{"country_code":"SV","country_en":"El Salvador","country_zh":"\u8428\u5c14\u74e6\u591a","region":"Central America and Caribbean"}',
            'GT' => '{"country_code":"GT","country_en":"Guatemala","country_zh":"\u5371\u5730\u9a6c\u62c9","region":"Central America and Caribbean"}',
            'IT' => '{"country_code":"IT","country_en":"Italy","country_zh":"\u610f\u5927\u5229","region":"Europe"}',
            'MV' => '{"country_code":"MV","country_en":"Maldives","country_zh":"\u9a6c\u5c14\u4ee3\u592b","region":"Asia"}',
            'NL' => '{"country_code":"NL","country_en":"Netherlands","country_zh":"\u8377\u5170","region":"Europe"}',
            'TH' => '{"country_code":"TH","country_en":"Thailand","country_zh":"\u6cf0\u56fd","region":"Southeast Asia"}',
            'AS' => '{"country_code":"AS","country_en":"American Samoa","country_zh":"\u7f8e\u5c5e\u8428\u6469\u4e9a","region":"Oceania"}',
            'BD' => '{"country_code":"BD","country_en":"Bangladesh","country_zh":"\u5b5f\u52a0\u62c9\u56fd","region":"Asia"}',
            'BR' => '{"country_code":"BR","country_en":"Brazil","country_zh":"\u5df4\u897f","region":"South America"}',
            'CV' => '{"country_code":"CV","country_en":"Cape Verde Islands","country_zh":"\u4f5b\u5f97\u89d2\u7fa4\u5c9b","region":"Africa"}',
            'CR' => '{"country_code":"CR","country_en":"Costa Rica","country_zh":"\u54e5\u65af\u8fbe\u9ece\u52a0","region":"Central America and Caribbean"}',
            'EC' => '{"country_code":"EC","country_en":"Ecuador","country_zh":"\u5384\u74dc\u591a\u5c14","region":"South America"}',
            'FK' => '{"country_code":"FK","country_en":"Falkland Islands (Islas Makvinas)","country_zh":"\u798f\u514b\u5170\u7fa4\u5c9b\uff08\u9a6c\u5c14Makvinas\uff09","region":"South America"}',
            'GY' => '{"country_code":"GY","country_en":"Guyana","country_zh":"\u572d\u4e9a\u90a3","region":"South America"}',
            'IE' => '{"country_code":"IE","country_en":"Ireland","country_zh":"\u7231\u5c14\u5170","region":"Europe"}',
            'KR' => '{"country_code":"KR","country_en":"Korea, South","country_zh":"\u97e9\u56fd","region":"Asia"}',
            'MK' => '{"country_code":"MK","country_en":"Macedonia","country_zh":"\u9a6c\u5176\u987f","region":"Europe"}',
            'MR' => '{"country_code":"MR","country_en":"Mauritania","country_zh":"\u6bdb\u91cc\u5854\u5c3c\u4e9a","region":"Africa"}',
            'NA' => '{"country_code":"NA","country_en":"Namibia","country_zh":"\u7eb3\u7c73\u6bd4\u4e9a","region":"Africa"}',
            'NG' => '{"country_code":"NG","country_en":"Nigeria","country_zh":"\u5c3c\u65e5\u5229\u4e9a","region":"Africa"}',
            'PL' => '{"country_code":"PL","country_en":"Poland","country_zh":"\u6ce2\u5170","region":"Europe"}',
            'PT' => '{"country_code":"PT","country_en":"Portugal","country_zh":"\u8461\u8404\u7259","region":"Europe"}',
            'VC' => '{"country_code":"VC","country_en":"Saint Vincent and the Grenadines","country_zh":"\u5723\u6587\u68ee\u7279","region":"Central America and Caribbean"}',
            'SA' => '{"country_code":"SA","country_en":"Saudi Arabia","country_zh":"\u6c99\u7279\u963f\u62c9\u4f2f","region":"Middle East"}',
            'LK' => '{"country_code":"LK","country_en":"Sri Lanka","country_zh":"\u65af\u91cc\u5170\u5361","region":"Asia"}',
            'CH' => '{"country_code":"CH","country_en":"Switzerland","country_zh":"\u745e\u58eb","region":"Europe"}',
            'TN' => '{"country_code":"TN","country_en":"Tunisia","country_zh":"\u7a81\u5c3c\u65af","region":"Africa"}',
            'TC' => '{"country_code":"TC","country_en":"Turks and Caicos Islands","country_zh":"\u7279\u514b\u65af\u548c\u51ef\u79d1\u65af\u7fa4\u5c9b","region":"Central America and Caribbean"}',
            'UZ' => '{"country_code":"UZ","country_en":"Uzbekistan","country_zh":"\u4e4c\u5179\u522b\u514b\u65af\u5766","region":"Asia"}',
            'WF' => '{"country_code":"WF","country_en":"Wallis and Futuna","country_zh":"\u74e6\u5229\u65af\u7fa4\u5c9b\u548c\u5bcc\u56fe\u7eb3\u7fa4\u5c9b","region":"Oceania"}',
            'ZM' => '{"country_code":"ZM","country_en":"Zambia","country_zh":"\u8d5e\u6bd4\u4e9a","region":"Africa"}',
            'BB' => '{"country_code":"BB","country_en":"Barbados","country_zh":"\u5df4\u5df4\u591a\u65af","region":"Central America and Caribbean"}',
            'CA' => '{"country_code":"CA","country_en":"Canada","country_zh":"\u52a0\u62ff\u5927","region":"North America"}',
            'CU' => '{"country_code":"CU","country_en":"Cuba","country_zh":"\u53e4\u5df4","region":"South America"}',
            'GP' => '{"country_code":"GP","country_en":"Guadeloupe","country_zh":"\u74dc\u5fb7\u7f57\u666e\u5c9b","region":"Central America and Caribbean"}',
            'JM' => '{"country_code":"JM","country_en":"Jamaica","country_zh":"\u7259\u4e70\u52a0","region":"Central America and Caribbean"}',
            'MT' => '{"country_code":"MT","country_en":"Malta","country_zh":"\u9a6c\u8033\u4ed6","region":"Europe"}',
            'PA' => '{"country_code":"PA","country_en":"Panama","country_zh":"\u5df4\u62ff\u9a6c","region":"Central America and Caribbean"}',
            'RW' => '{"country_code":"RW","country_en":"Rwanda","country_zh":"\u5362\u65fa\u8fbe","region":"Africa"}',
            'TM' => '{"country_code":"TM","country_en":"Turkmenistan","country_zh":"\u571f\u5e93\u66fc\u65af\u5766","region":"Asia"}',
            'US' => '{"country_code":"US","country_en":"United States","country_zh":"\u7f8e\u56fd","region":"North America"}',
            'PW' => '{"country_code":"PW","country_en":"Palau","country_zh":"\u5e15\u52b3","region":"Oceania"}',
            'ME' => '{"country_code":"ME","country_en":"Montenegro","country_zh":"\u9ed1\u5c71","region":"Europe"}',
            'RS' => '{"country_code":"RS","country_en":"Serbia","country_zh":"\u585e\u5c14\u7ef4\u4e9a","region":"Europe"}',
            'UK' => '{"country_code":"UK","country_en":"United Kingdom","country_zh":"\u82f1\u56fd","region":"Europe"}',
            'FO' => '{"country_code":"FO","country_en":"Faroe Islands","country_zh":"\u6cd5\u7f57\u7fa4\u5c9b","region":"Europe"}',
            'ZR' => '{"country_code":"ZR","country_en":"ZAIRE","country_zh":"\u624e\u4f0a\u5c14","region":"Africa"}',
            'PS' => '{"country_code":"PS","country_en":"Palestine","country_zh":"\u5df4\u52d2\u65af\u5766","region":"Asia"}',
            'KS' => '{"country_code":"KS","country_en":"Kosovo","country_zh":"\u79d1\u7d22\u6c83","region":"Europe"}',
            'YK' => '{"country_code":"YK","country_en":"Kosovo","country_zh":"\u79d1\u7d22\u6c83","region":"Europe"}',
            'NF' => '{"country_code":"NF","country_en":"Norfolk Island","country_zh":"\u8bfa\u798f\u514b\u5c9b","region":"Oceania"}',
            'MP' => '{"country_code":"MP","country_en":"Saipan Island","country_zh":"\u585e\u73ed\u5c9b","region":"Oceania"}',
            'GG' => '{"country_code":"GG","country_en":"Guernsey","country_zh":"\u683c\u6069\u897f\u5c9b","region":"Europe"}',
            'IM' => '{"country_code":"IM","country_en":"Isle Of Man","country_zh":"\u9a6c\u6069\u5c9b","region":"Europe"}',
			'UM' => '{"country_code":"UM","country_en":"United States Minor Outlying Islands","country_zh":"\u7f8e\u56fd\u672c\u571f\u5916\u5c0f\u5c9b\u5c7f","region":"America"}',
			'AC' => '{"country_code":"AC","country_en":"Ascension Island","country_zh":"\u963f\u68ee\u677e\u5c9b","region":"other"}',
			'CW' => '{"country_code":"CW","country_en":"Curaçao","country_zh":"\u5e93\u62c9\u7d22","region":"South America"}',
			'JE' => '{"country_code":"JE","country_en":"Jersey","country_zh":"\u6cfd\u897f\u5c9b","region":"Europe"}',
			'BL' => '{"country_code":"BL","country_en":"Saint Barthélemy","country_zh":"\u5723\u5df4\u6258\u6d1b\u7f2a\u5c9b","region":"other"}',
            'MF' => '{"country_code":"MF","country_en":"Saint Martin","country_zh":"\u5723\u9a6c\u4e01\u5c9b","region":"North America"}',
        ];
        $keywords = strtoupper($keywords);
        return isset($data[$keywords]) ? $data[$keywords] : '';
    }

    /**
     * 通过国家缩写 获取国家名称
     * @param $code
     * @return array|mixed
     */
    static function getCountryName($code){
        $code_2 = self::countryList($code);
       // \Yii::info("aliexpressOrder  Error country GET country:".$code_2,"file");
        if(!empty($code_2)){
            $data = self::countryData($code_2);
        } else {
            $data = self::countryData($code);
        }
       // \Yii::info("aliexpressOrder  Error country GET country DATA:".$data,"file");
        return empty($data) ? [] :json_decode($data,true);
    }

    /**
     * 三位国家缩写 转换成 二位国家缩写
     * @param $code
     * @return array
     */
    static function changeCountryCode($code){
        $code_2 = self::countryList($code);
        return empty($code_2) ? $code : $code_2;
    }
    
    /**
     * 获取对应地区的国家列表信息
     * @author hqw
     * @return array()
     */
    static function getRegionCountry(){
    	
    	global $hitCache;
    	$hitCache = "NoHit";
    	$cachedArrAll = array();
    	$countrys = array();
    	$gotCache = RedisHelper::getOrderCache(0,'system',"RegionCountry") ;
    	if (!empty($gotCache)){
    		$cachedArrAll = json_decode($gotCache,true);
    		$countrys = $cachedArrAll;
    		$hitCache= "Hit";
    	}
    		
    	if ($hitCache <>"Hit"){
    		$query = SysCountry::find();
	    	$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
	    	$countrys =[];
	    	foreach ($regions as $region){
	    		$arr['name']= $region['region'];
	    		$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
	    		$countrys[]= $arr;
	    	}
    		//save the redis cache for next time use
    		RedisHelper::setOrderCache(0,'system',"RegionCountry",json_encode($countrys)) ;
    	}
    	
    	return $countrys;
    }
    
    //根据JS获取的国家列表
    public static function getScopeCountry(){
    	
    	global $hitCache;
    	$hitCache = "NoHit";
    	$cachedArrAll = array();
    	$countryListArr = array();
    	$gotCache = RedisHelper::getOrderCache(0,'system',"ScopeCountry") ;
    	if (!empty($gotCache)){
    		$cachedArrAll = json_decode($gotCache,true);
    		$countryListArr = $cachedArrAll;
    		$hitCache= "Hit";
    	}
    	
    if ($hitCache <>"Hit"){
    	$country_json = <<<EOF
[{"alpha2":"AC","alpha3":"","countryCallingCodes":["+247"],"name":"Ascension Island","cn":"阿森松岛","continent":"other"},{"alpha2":"AD","alpha3":"AND","countryCallingCodes":["+376"],"name":"Andorra","cn":"安道尔","continent":"Europe"},{"alpha2":"AE","alpha3":"ARE","countryCallingCodes":["+971"],"name":"United Arab Emirates","cn":"阿拉伯联合酋长国","continent":"Asia"},{"alpha2":"AF","alpha3":"AFG","countryCallingCodes":["+93"],"name":"Afghanistan","cn":"阿富汗","continent":"Asia"},{"alpha2":"AG","alpha3":"ATG","countryCallingCodes":["+1 268"],"name":"Antigua And Barbuda","cn":"安提瓜和巴布达","continent":"North America"},{"alpha2":"AI","alpha3":"AIA","countryCallingCodes":["+1 264"],"name":"Anguilla","cn":"安圭拉岛","continent":"North America"},{"alpha2":"AL","alpha3":"ALB","countryCallingCodes":["+355"],"name":"Albania","cn":"阿尔巴尼亚","continent":"Europe"},{"alpha2":"AM","alpha3":"ARM","countryCallingCodes":["+374"],"name":"Armenia","cn":"亚美尼亚","continent":"Asia"},{"alpha2":"AO","alpha3":"AGO","countryCallingCodes":["+244"],"name":"Angola","cn":"安哥拉","continent":"Africa"},{"alpha2":"AQ","alpha3":"ATA","countryCallingCodes":["+672"],"name":"Antarctica","cn":"南极洲","continent":"Antarctica"},{"alpha2":"AR","alpha3":"ARG","countryCallingCodes":["+54"],"name":"Argentina","cn":"阿根廷","continent":"South America"},{"alpha2":"AS","alpha3":"ASM","countryCallingCodes":["+1 684"],"name":"American Samoa","cn":"美属萨摩亚","continent":"Oceania"},{"alpha2":"AT","alpha3":"AUT","countryCallingCodes":["+43"],"name":"Austria","cn":"奥地利","continent":"Europe"},{"alpha2":"AU","alpha3":"AUS","countryCallingCodes":["+61"],"name":"Australia","cn":"澳大利亚","continent":"Oceania"},{"alpha2":"AW","alpha3":"ABW","countryCallingCodes":["+297"],"name":"Aruba","cn":"阿鲁巴岛","continent":"North America"},{"alpha2":"AZ","alpha3":"AZE","countryCallingCodes":["+994"],"name":"Azerbaijan","cn":"阿塞拜疆","continent":"Asia"},{"alpha2":"BA","alpha3":"BIH","countryCallingCodes":["+387"],"name":"Bosnia & Herzegovina","cn":"波斯尼亚和黑塞哥维那","continent":"Europe"},{"alpha2":"BB","alpha3":"BRB","countryCallingCodes":["+1 246"],"name":"Barbados","cn":"巴巴多斯岛","continent":"North America"},{"alpha2":"BD","alpha3":"BGD","countryCallingCodes":["+880"],"name":"Bangladesh","cn":"孟加拉共和国","continent":"Asia"},{"alpha2":"BE","alpha3":"BEL","countryCallingCodes":["+32"],"name":"Belgium","cn":"比利时","continent":"Europe"},{"alpha2":"BF","alpha3":"BFA","countryCallingCodes":["+226"],"name":"Burkina Faso","cn":"布基纳法索","continent":"Africa"},{"alpha2":"BG","alpha3":"BGR","countryCallingCodes":["+359"],"name":"Bulgaria","cn":"保加利亚","continent":"Europe"},{"alpha2":"BH","alpha3":"BHR","countryCallingCodes":["+973"],"name":"Bahrain","cn":"巴林","continent":"Asia"},{"alpha2":"BI","alpha3":"BDI","countryCallingCodes":["+257"],"name":"Burundi","cn":"布隆迪","continent":"Africa"},{"alpha2":"BJ","alpha3":"BEN","countryCallingCodes":["+229"],"name":"Benin","cn":"贝宁湾","continent":"Africa"},{"alpha2":"BL","alpha3":"BLM","countryCallingCodes":["+590"],"name":"Saint Barthélemy","cn":"圣巴托洛缪岛","continent":"other"},{"alpha2":"BM","alpha3":"BMU","countryCallingCodes":["+1 441"],"name":"Bermuda","cn":"百慕大群岛","continent":"North America"},{"alpha2":"BN","alpha3":"BRN","countryCallingCodes":["+673"],"name":"Brunei Darussalam","cn":"文莱达鲁萨兰国","continent":"Asia"},{"alpha2":"BO","alpha3":"BOL","countryCallingCodes":["+591"],"name":"Bolivia, Plurinational State Of","cn":"玻利维亚","continent":"South America"},{"alpha2":"BQ","alpha3":"BES","countryCallingCodes":["+599"],"name":"Bonaire, Saint Eustatius And Saba","cn":"博内尔岛，圣圣尤斯特歇斯和萨巴","continent":"South America"},{"alpha2":"BR","alpha3":"BRA","countryCallingCodes":["+55"],"name":"Brazil","cn":"巴西","continent":"South America"},{"alpha2":"BS","alpha3":"BHS","countryCallingCodes":["+1 242"],"name":"Bahamas","cn":"巴哈马群岛","continent":"North America"},{"alpha2":"BT","alpha3":"BTN","countryCallingCodes":["+975"],"name":"Bhutan","cn":"不丹","continent":"Asia"},{"alpha2":"BV","alpha3":"BVT","countryCallingCodes":[],"name":"Bouvet Island","cn":"布韦岛","continent":"Antarctica"},{"alpha2":"BW","alpha3":"BWA","countryCallingCodes":["+267"],"name":"Botswana","cn":"博茨瓦纳","continent":"Africa"},{"alpha2":"BY","alpha3":"BLR","countryCallingCodes":["+375"],"name":"Belarus","cn":"白俄罗斯","continent":"Europe"},{"alpha2":"BZ","alpha3":"BLZ","countryCallingCodes":["+501"],"name":"Belize","cn":"伯利兹城","continent":"North America"},{"alpha2":"CA","alpha3":"CAN","countryCallingCodes":["+1"],"name":"Canada","cn":"加拿大","continent":"North America"},{"alpha2":"CC","alpha3":"CCK","countryCallingCodes":["+61"],"name":"Cocos (Keeling) Islands","cn":"科科斯群岛（基林）","continent":"Oceania"},{"alpha2":"CD","alpha3":"COD","countryCallingCodes":["+243"],"name":"Democratic Republic Of the Congo","cn":"刚果民主共和国","continent":"Africa"},{"alpha2":"CF","alpha3":"CAF","countryCallingCodes":["+236"],"name":"Central African Republic","cn":"中非共和国","continent":"Africa"},{"alpha2":"CG","alpha3":"COG","countryCallingCodes":["+242"],"name":"Republic Of the Congo","cn":"刚果","continent":"Africa"},{"alpha2":"CH","alpha3":"CHE","countryCallingCodes":["+41"],"name":"Switzerland","cn":"瑞士","continent":"Europe"},{"alpha2":"CI","alpha3":"CIV","countryCallingCodes":["+225"],"name":"Cote d'Ivoire","cn":"科特迪瓦","continent":"Africa"},{"alpha2":"CK","alpha3":"COK","countryCallingCodes":["+682"],"name":"Cook Islands","cn":"库克群岛","continent":"Oceania"},{"alpha2":"CL","alpha3":"CHL","countryCallingCodes":["+56"],"name":"Chile","cn":"智利","continent":"South America"},{"alpha2":"CM","alpha3":"CMR","countryCallingCodes":["+237"],"name":"Cameroon","cn":"喀麦隆","continent":"Africa"},{"alpha2":"CN","alpha3":"CHN","countryCallingCodes":["+86"],"name":"China","cn":"中国（大陆）","continent":"Asia"},{"alpha2":"CO","alpha3":"COL","countryCallingCodes":["+57"],"name":"Colombia","cn":"哥伦比亚","continent":"South America"},{"alpha2":"CP","alpha3":"","countryCallingCodes":[],"name":"Clipperton Island","cn":"克利珀顿岛","continent":"other"},{"alpha2":"CR","alpha3":"CRI","countryCallingCodes":["+506"],"name":"Costa Rica","cn":"哥斯达黎加","continent":"North America"},{"alpha2":"CU","alpha3":"CUB","countryCallingCodes":["+53"],"name":"Cuba","cn":"古巴","continent":"North America"},{"alpha2":"CV","alpha3":"CPV","countryCallingCodes":["+238"],"name":"Cape Verde","cn":"佛得角","continent":"Africa"},{"alpha2":"CW","alpha3":"CUW","countryCallingCodes":["+599"],"name":"Curacao","cn":"科腊索岛","continent":"South America"},{"alpha2":"CX","alpha3":"CXR","countryCallingCodes":["+61"],"name":"Christmas Island","cn":"圣诞岛","continent":"Oceania"},{"alpha2":"CY","alpha3":"CYP","countryCallingCodes":["+357"],"name":"Cyprus","cn":"塞浦路斯","continent":"Asia"},{"alpha2":"CZ","alpha3":"CZE","countryCallingCodes":["+420"],"name":"Czech Republic","cn":"捷克共和国","continent":"Europe"},{"alpha2":"DE","alpha3":"DEU","countryCallingCodes":["+49"],"name":"Germany","cn":"德国","continent":"Europe"},{"alpha2":"DG","alpha3":"","countryCallingCodes":[],"name":"Diego Garcia","cn":"迭戈加西亚","continent":"other"},{"alpha2":"DJ","alpha3":"DJI","countryCallingCodes":["+253"],"name":"Djibouti","cn":"吉布提","continent":"Africa"},{"alpha2":"DK","alpha3":"DNK","countryCallingCodes":["+45"],"name":"Denmark","cn":"丹麦","continent":"Europe"},{"alpha2":"DM","alpha3":"DMA","countryCallingCodes":["+1 767"],"name":"Dominica","cn":"多米尼克","continent":"North America"},{"alpha2":"DO","alpha3":"DOM","countryCallingCodes":["+1 809","+1 829","+1 849"],"name":"Dominican Republic","cn":"多米尼加共和国","continent":"North America"},{"alpha2":"DZ","alpha3":"DZA","countryCallingCodes":["+213"],"name":"Algeria","cn":"阿尔及利亚","continent":"Africa"},{"alpha2":"EA","alpha3":"","countryCallingCodes":[],"name":"Ceuta, Mulilla","cn":"休达和梅利利亚","continent":"Africa"},{"alpha2":"EC","alpha3":"ECU","countryCallingCodes":["+593"],"name":"Ecuador","cn":"赤道","continent":"South America"},{"alpha2":"EE","alpha3":"EST","countryCallingCodes":["+372"],"name":"Estonia","cn":"爱沙尼亚","continent":"Europe"},{"alpha2":"EG","alpha3":"EGY","countryCallingCodes":["+20"],"name":"Egypt","cn":"埃及","continent":"Africa"},{"alpha2":"EH","alpha3":"ESH","countryCallingCodes":["+212"],"name":"Western Sahara","cn":"西撒哈拉","continent":"Africa"},{"alpha2":"ER","alpha3":"ERI","countryCallingCodes":["+291"],"name":"Eritrea","cn":"厄立特里亚","continent":"Africa"},{"alpha2":"ES","alpha3":"ESP","countryCallingCodes":["+34"],"name":"Spain","cn":"西班牙","continent":"Europe"},{"alpha2":"ET","alpha3":"ETH","countryCallingCodes":["+251"],"name":"Ethiopia","cn":"埃塞俄比亚","continent":"Africa"},{"alpha2":"FI","alpha3":"FIN","countryCallingCodes":["+358"],"name":"Finland","cn":"芬兰","continent":"Europe"},{"alpha2":"FJ","alpha3":"FJI","countryCallingCodes":["+679"],"name":"Fiji","cn":"斐济","continent":"Oceania"},{"alpha2":"FK","alpha3":"FLK","countryCallingCodes":["+500"],"name":"Falkland Islands","cn":"福克兰群岛","continent":"South America"},{"alpha2":"FM","alpha3":"FSM","countryCallingCodes":["+691"],"name":"Micronesia, Federated States Of","cn":"密克罗尼西亚","continent":"Oceania"},{"alpha2":"FO","alpha3":"FRO","countryCallingCodes":["+298"],"name":"Faroe Islands","cn":"法罗群岛","continent":"Europe"},{"alpha2":"FR","alpha3":"FRA","countryCallingCodes":["+33"],"name":"France","cn":"法国","continent":"Europe"},{"alpha2":"FX","alpha3":"","countryCallingCodes":["+241"],"name":"France, Metropolitan","cn":"法属美特罗伯利坦","continent":"other"},{"alpha2":"GA","alpha3":"GAB","countryCallingCodes":["+241"],"name":"Gabon","cn":"加蓬","continent":"Africa"},{"alpha2":"UK","alpha3":"","countryCallingCodes":[],"name":"United Kingdom","cn":"英国(UK)","continent":"Europe"},{"alpha2":"GB","alpha3":"GBR","countryCallingCodes":["+44"],"name":"United Kingdom","cn":"英国(GB)","continent":"Europe"},{"alpha2":"GD","alpha3":"GRD","countryCallingCodes":["+473"],"name":"Grenada","cn":"格林纳达","continent":"North America"},{"alpha2":"GE","alpha3":"GEO","countryCallingCodes":["+995"],"name":"Georgia","cn":"格鲁吉亚","continent":"Asia"},{"alpha2":"GF","alpha3":"GUF","countryCallingCodes":["+594"],"name":"French Guiana","cn":"法属圭亚那","continent":"South America"},{"alpha2":"GG","alpha3":"GGY","countryCallingCodes":["+44"],"name":"Guernsey","cn":"格恩西岛","continent":"Europe"},{"alpha2":"GH","alpha3":"GHA","countryCallingCodes":["+233"],"name":"Ghana","cn":"加纳","continent":"Africa"},{"alpha2":"GI","alpha3":"GIB","countryCallingCodes":["+350"],"name":"Gibraltar","cn":"直布罗陀","continent":"Europe"},{"alpha2":"GL","alpha3":"GRL","countryCallingCodes":["+299"],"name":"Greenland","cn":"格陵兰","continent":"North America"},{"alpha2":"GM","alpha3":"GMB","countryCallingCodes":["+220"],"name":"Gambia","cn":"冈比亚","continent":"Africa"},{"alpha2":"GN","alpha3":"GIN","countryCallingCodes":["+224"],"name":"Guinea","cn":"几内亚","continent":"Africa"},{"alpha2":"GP","alpha3":"GLP","countryCallingCodes":["+590"],"name":"Guadeloupe","cn":"瓜德罗普岛","continent":"North America"},{"alpha2":"GQ","alpha3":"GNQ","countryCallingCodes":["+240"],"name":"Equatorial Guinea","cn":"赤道几内亚","continent":"Africa"},{"alpha2":"GR","alpha3":"GRC","countryCallingCodes":["+30"],"name":"Greece","cn":"希腊","continent":"Europe"},{"alpha2":"GS","alpha3":"SGS","countryCallingCodes":[],"name":"South Georgia And The South Sandwich Islands","cn":"南乔治亚和南三明治群岛","continent":"other"},{"alpha2":"GT","alpha3":"GTM","countryCallingCodes":["+502"],"name":"Guatemala","cn":"危地马拉","continent":"North America"},{"alpha2":"GU","alpha3":"GUM","countryCallingCodes":["+1 671"],"name":"Guam","cn":"关岛","continent":"Oceania"},{"alpha2":"GW","alpha3":"GNB","countryCallingCodes":["+245"],"name":"Guinea-bissau","cn":"几内亚比绍共和国","continent":"Africa"},{"alpha2":"GY","alpha3":"GUY","countryCallingCodes":["+592"],"name":"Guyana","cn":"圭亚那","continent":"South America"},{"alpha2":"HK","alpha3":"HKG","countryCallingCodes":["+852"],"name":"Hong Kong","cn":"中国香港","continent":"Asia"},{"alpha2":"HM","alpha3":"HMD","countryCallingCodes":[],"name":"Heard Island And McDonald Islands","cn":"赫德岛和麦克唐纳岛","continent":"other"},{"alpha2":"HN","alpha3":"HND","countryCallingCodes":["+504"],"name":"Honduras","cn":"洪都拉斯","continent":"North America"},{"alpha2":"HR","alpha3":"HRV","countryCallingCodes":["+385"],"name":"Croatia","cn":"克罗地亚","continent":"Europe"},{"alpha2":"HT","alpha3":"HTI","countryCallingCodes":["+509"],"name":"Haiti","cn":"海地","continent":"North America"},{"alpha2":"HU","alpha3":"HUN","countryCallingCodes":["+36"],"name":"Hungary","cn":"匈牙利","continent":"Europe"},{"alpha2":"IC","alpha3":"","countryCallingCodes":[],"name":"Canary Islands","cn":"加那利群岛","continent":"Africa"},{"alpha2":"ID","alpha3":"IDN","countryCallingCodes":["+62"],"name":"Indonesia","cn":"印度尼西亚","continent":"Asia"},{"alpha2":"IE","alpha3":"IRL","countryCallingCodes":["+353"],"name":"Ireland","cn":"爱尔兰","continent":"Europe"},{"alpha2":"IL","alpha3":"ISR","countryCallingCodes":["+972"],"name":"Israel","cn":"以色列","continent":"Asia"},{"alpha2":"IM","alpha3":"IMN","countryCallingCodes":["+44"],"name":"Isle Of Man","cn":"马恩岛","continent":"Europe"},{"alpha2":"IN","alpha3":"IND","countryCallingCodes":["+91"],"name":"India","cn":"印度","continent":"Asia"},{"alpha2":"IO","alpha3":"IOT","countryCallingCodes":["+246"],"name":"British Indian Ocean Territory","cn":"英属印度洋领地","continent":"Africa"},{"alpha2":"IQ","alpha3":"IRQ","countryCallingCodes":["+964"],"name":"Iraq","cn":"伊拉克共和国","continent":"Asia"},{"alpha2":"IR","alpha3":"IRN","countryCallingCodes":["+98"],"name":"Iran, Islamic Republic Of","cn":"伊朗","continent":"Asia"},{"alpha2":"IS","alpha3":"ISL","countryCallingCodes":["+354"],"name":"Iceland","cn":"冰岛","continent":"Europe"},{"alpha2":"IT","alpha3":"ITA","countryCallingCodes":["+39"],"name":"Italy","cn":"意大利","continent":"Europe"},{"alpha2":"JE","alpha3":"JEY","countryCallingCodes":["+44"],"name":"Jersey","cn":"泽西岛","continent":"Europe"},{"alpha2":"JM","alpha3":"JAM","countryCallingCodes":["+1 876"],"name":"Jamaica","cn":"牙买加","continent":"North America"},{"alpha2":"JO","alpha3":"JOR","countryCallingCodes":["+962"],"name":"Jordan","cn":"约旦","continent":"Asia"},{"alpha2":"JP","alpha3":"JPN","countryCallingCodes":["+81"],"name":"Japan","cn":"日本","continent":"Asia"},{"alpha2":"KE","alpha3":"KEN","countryCallingCodes":["+254"],"name":"Kenya","cn":"肯尼亚","continent":"Africa"},{"alpha2":"KG","alpha3":"KGZ","countryCallingCodes":["+996"],"name":"Kyrgyzstan","cn":"吉尔吉斯斯坦","continent":"Asia"},{"alpha2":"KH","alpha3":"KHM","countryCallingCodes":["+855"],"name":"Cambodia","cn":"柬埔寨","continent":"Asia"},{"alpha2":"KI","alpha3":"KIR","countryCallingCodes":["+686"],"name":"Kiribati","cn":"基里巴斯","continent":"Oceania"},{"alpha2":"KM","alpha3":"COM","countryCallingCodes":["+269"],"name":"Comoros","cn":"科摩罗","continent":"Africa"},{"alpha2":"KN","alpha3":"KNA","countryCallingCodes":["+1 869"],"name":"Saint Kitts And Nevis","cn":"圣克里斯托弗和尼维斯岛","continent":"North America"},{"alpha2":"KP","alpha3":"PRK","countryCallingCodes":["+850"],"name":"Korea, Democratic People's Republic Of","cn":"北韩","continent":"Asia"},{"alpha2":"KR","alpha3":"KOR","countryCallingCodes":["+82"],"name":"Korea, Republic Of","cn":"韩国","continent":"Asia"},{"alpha2":"KW","alpha3":"KWT","countryCallingCodes":["+965"],"name":"Kuwait","cn":"科威特","continent":"Asia"},{"alpha2":"KY","alpha3":"CYM","countryCallingCodes":["+1 345"],"name":"Cayman Islands","cn":"开曼群岛","continent":"North America"},{"alpha2":"KZ","alpha3":"KAZ","countryCallingCodes":["+7","+7 6","+7 7"],"name":"Kazakhstan","cn":"哈萨克斯坦","continent":"Asia"},{"alpha2":"LA","alpha3":"LAO","countryCallingCodes":["+856"],"name":"Lao People's Democratic Republic","cn":"老挝人民民主共和国","continent":"Asia"},{"alpha2":"LB","alpha3":"LBN","countryCallingCodes":["+961"],"name":"Lebanon","cn":"黎巴嫩","continent":"Asia"},{"alpha2":"LC","alpha3":"LCA","countryCallingCodes":["+1 758"],"name":"Saint Lucia","cn":"圣卢西亚岛","continent":"North America"},{"alpha2":"LI","alpha3":"LIE","countryCallingCodes":["+423"],"name":"Liechtenstein","cn":"列支敦士登","continent":"Europe"},{"alpha2":"LK","alpha3":"LKA","countryCallingCodes":["+94"],"name":"Sri Lanka","cn":"斯里兰卡","continent":"Asia"},{"alpha2":"LR","alpha3":"LBR","countryCallingCodes":["+231"],"name":"Liberia","cn":"利比里亚","continent":"Africa"},{"alpha2":"LS","alpha3":"LSO","countryCallingCodes":["+266"],"name":"Lesotho","cn":"莱索托","continent":"Africa"},{"alpha2":"LT","alpha3":"LTU","countryCallingCodes":["+370"],"name":"Lithuania","cn":"立陶宛","continent":"Europe"},{"alpha2":"LU","alpha3":"LUX","countryCallingCodes":["+352"],"name":"Luxembourg","cn":"卢森堡","continent":"Europe"},{"alpha2":"LV","alpha3":"LVA","countryCallingCodes":["+371"],"name":"Latvia","cn":"拉脱维亚","continent":"Europe"},{"alpha2":"LY","alpha3":"LBY","countryCallingCodes":["+218"],"name":"Libya","cn":"利比亚","continent":"Africa"},{"alpha2":"MA","alpha3":"MAR","countryCallingCodes":["+212"],"name":"Morocco","cn":"摩洛哥","continent":"Africa"},{"alpha2":"MC","alpha3":"MCO","countryCallingCodes":["+377"],"name":"Monaco","cn":"摩纳哥","continent":"Europe"},{"alpha2":"MD","alpha3":"MDA","countryCallingCodes":["+373"],"name":"Moldova","cn":"摩尔多瓦","continent":"Europe"},{"alpha2":"ME","alpha3":"MNE","countryCallingCodes":["+382"],"name":"Montenegro","cn":"黑山共和国","continent":"Europe"},{"alpha2":"MF","alpha3":"MAF","countryCallingCodes":["+590"],"name":"Saint Martin","cn":" 圣马丁岛","continent":"North America"},{"alpha2":"MG","alpha3":"MDG","countryCallingCodes":["+261"],"name":"Madagascar","cn":"马达加斯加","continent":"Africa"},{"alpha2":"MH","alpha3":"MHL","countryCallingCodes":["+692"],"name":"Marshall Islands","cn":"马绍尔群岛","continent":"Oceania"},{"alpha2":"MK","alpha3":"MKD","countryCallingCodes":["+389"],"name":"Macedonia, The Former Yugoslav Republic Of","cn":"马其顿王国","continent":"Europe"},{"alpha2":"ML","alpha3":"MLI","countryCallingCodes":["+223"],"name":"Mali","cn":"马里","continent":"Africa"},{"alpha2":"MM","alpha3":"MMR","countryCallingCodes":["+95"],"name":"Myanmar","cn":"缅甸","continent":"Asia"},{"alpha2":"MN","alpha3":"MNG","countryCallingCodes":["+976"],"name":"Mongolia","cn":"蒙古","continent":"Asia"},{"alpha2":"MO","alpha3":"MAC","countryCallingCodes":["+853"],"name":"Macao","cn":"中国澳门","continent":"Asia"},{"alpha2":"MP","alpha3":"MNP","countryCallingCodes":["+1 670"],"name":"Northern Mariana Islands","cn":"北马里亚纳群岛","continent":"Oceania"},{"alpha2":"MQ","alpha3":"MTQ","countryCallingCodes":["+596"],"name":"Martinique","cn":"马提尼克岛","continent":"North America"},{"alpha2":"MR","alpha3":"MRT","countryCallingCodes":["+222"],"name":"Mauritania","cn":"毛利塔尼亚","continent":"Africa"},{"alpha2":"MS","alpha3":"MSR","countryCallingCodes":["+1 664"],"name":"Montserrat","cn":"蒙特塞拉特岛","continent":"North America"},{"alpha2":"MT","alpha3":"MLT","countryCallingCodes":["+356"],"name":"Malta","cn":"马耳他","continent":"Europe"},{"alpha2":"MU","alpha3":"MUS","countryCallingCodes":["+230"],"name":"Mauritius","cn":"毛里求斯","continent":"Africa"},{"alpha2":"MV","alpha3":"MDV","countryCallingCodes":["+960"],"name":"Maldives","cn":"马尔代夫","continent":"Asia"},{"alpha2":"MW","alpha3":"MWI","countryCallingCodes":["+265"],"name":"Malawi","cn":"马拉维","continent":"Africa"},{"alpha2":"MX","alpha3":"MEX","countryCallingCodes":["+52","+521"],"name":"Mexico","cn":"墨西哥","continent":"North America"},{"alpha2":"MY","alpha3":"MYS","countryCallingCodes":["+60"],"name":"Malaysia","cn":"马来西亚","continent":"Asia"},{"alpha2":"MZ","alpha3":"MOZ","countryCallingCodes":["+258"],"name":"Mozambique","cn":"莫桑比克","continent":"Africa"},{"alpha2":"NA","alpha3":"NAM","countryCallingCodes":["+264"],"name":"Namibia","cn":"纳米比亚","continent":"Africa"},{"alpha2":"NC","alpha3":"NCL","countryCallingCodes":["+687"],"name":"New Caledonia","cn":"新喀里多尼亚","continent":"Oceania"},{"alpha2":"NE","alpha3":"NER","countryCallingCodes":["+227"],"name":"Niger","cn":"尼日尔","continent":"Africa"},{"alpha2":"NF","alpha3":"NFK","countryCallingCodes":["+672"],"name":"Norfolk Island","cn":"诺福克岛","continent":"Oceania"},{"alpha2":"NG","alpha3":"NGA","countryCallingCodes":["+234"],"name":"Nigeria","cn":"尼日利亚","continent":"Africa"},{"alpha2":"NI","alpha3":"NIC","countryCallingCodes":["+505"],"name":"Nicaragua","cn":"尼加拉瓜","continent":"North America"},{"alpha2":"NL","alpha3":"NLD","countryCallingCodes":["+31"],"name":"Netherlands","cn":"荷兰","continent":"Europe"},{"alpha2":"NO","alpha3":"NOR","countryCallingCodes":["+47"],"name":"Norway","cn":"挪威","continent":"Europe"},{"alpha2":"NP","alpha3":"NPL","countryCallingCodes":["+977"],"name":"Nepal","cn":"尼泊尔","continent":"Asia"},{"alpha2":"NR","alpha3":"NRU","countryCallingCodes":["+674"],"name":"Nauru","cn":"瑙鲁","continent":"Oceania"},{"alpha2":"NU","alpha3":"NIU","countryCallingCodes":["+683"],"name":"Niue","cn":"纽埃岛","continent":"Oceania"},{"alpha2":"NZ","alpha3":"NZL","countryCallingCodes":["+64"],"name":"New Zealand","cn":"新西兰","continent":"Oceania"},{"alpha2":"OM","alpha3":"OMN","countryCallingCodes":["+968"],"name":"Oman","cn":"阿曼","continent":"Asia"},{"alpha2":"PA","alpha3":"PAN","countryCallingCodes":["+507"],"name":"Panama","cn":"巴拿马","continent":"North America"},{"alpha2":"PE","alpha3":"PER","countryCallingCodes":["+51"],"name":"Peru","cn":"秘鲁","continent":"South America"},{"alpha2":"PF","alpha3":"PYF","countryCallingCodes":["+689"],"name":"French Polynesia","cn":"法属玻利尼西亚","continent":"Oceania"},{"alpha2":"PG","alpha3":"PNG","countryCallingCodes":["+675"],"name":"Papua New Guinea","cn":"巴布亚新几内亚","continent":"Oceania"},{"alpha2":"PH","alpha3":"PHL","countryCallingCodes":["+63"],"name":"Philippines","cn":"菲律宾","continent":"Asia"},{"alpha2":"PK","alpha3":"PAK","countryCallingCodes":["+92"],"name":"Pakistan","cn":"巴基斯坦","continent":"Asia"},{"alpha2":"PL","alpha3":"POL","countryCallingCodes":["+48"],"name":"Poland","cn":"波兰","continent":"Europe"},{"alpha2":"PM","alpha3":"SPM","countryCallingCodes":["+508"],"name":"Saint Pierre And Miquelon","cn":"圣彼埃尔和密克隆岛","continent":"other"},{"alpha2":"PN","alpha3":"PCN","countryCallingCodes":[],"name":"Pitcairn","cn":"皮特克恩","continent":"Oceania"},{"alpha2":"PR","alpha3":"PRI","countryCallingCodes":["+1 787","+1 939"],"name":"Puerto Rico","cn":"波多黎各","continent":"North America"},{"alpha2":"PS","alpha3":"PSE","countryCallingCodes":["+970"],"name":"Palestinian Territory, Occupied","cn":"巴勒斯坦","continent":"Asia"},{"alpha2":"PT","alpha3":"PRT","countryCallingCodes":["+351"],"name":"Portugal","cn":"葡萄牙","continent":"Europe"},{"alpha2":"PW","alpha3":"PLW","countryCallingCodes":["+680"],"name":"Palau","cn":"帕劳群岛","continent":"Oceania"},{"alpha2":"PY","alpha3":"PRY","countryCallingCodes":["+595"],"name":"Paraguay","cn":"巴拉圭","continent":"South America"},{"alpha2":"QA","alpha3":"QAT","countryCallingCodes":["+974"],"name":"Qatar","cn":"卡塔尔","continent":"Asia"},{"alpha2":"RE","alpha3":"REU","countryCallingCodes":["+262"],"name":"Reunion","cn":"留尼旺岛","continent":"Africa"},{"alpha2":"RO","alpha3":"ROU","countryCallingCodes":["+40"],"name":"Romania","cn":"罗马尼亚","continent":"Europe"},{"alpha2":"RS","alpha3":"SRB","countryCallingCodes":["+381"],"name":"Serbia","cn":"塞尔维亚","continent":"Europe"},{"alpha2":"RU","alpha3":"RUS","countryCallingCodes":["+7","+7 3","+7 4","+7 8"],"name":"Russian Federation","cn":"俄罗斯","continent":"Europe"},{"alpha2":"RW","alpha3":"RWA","countryCallingCodes":["+250"],"name":"Rwanda","cn":"卢旺达","continent":"Africa"},{"alpha2":"SA","alpha3":"SAU","countryCallingCodes":["+966"],"name":"Saudi Arabia","cn":"沙特阿拉伯","continent":"Asia"},{"alpha2":"SB","alpha3":"SLB","countryCallingCodes":["+677"],"name":"Solomon Islands","cn":"所罗门群岛","continent":"Oceania"},{"alpha2":"SC","alpha3":"SYC","countryCallingCodes":["+248"],"name":"Seychelles","cn":"塞舌尔","continent":"Africa"},{"alpha2":"SD","alpha3":"SDN","countryCallingCodes":["+249"],"name":"Sudan","cn":"苏丹","continent":"Africa"},{"alpha2":"SE","alpha3":"SWE","countryCallingCodes":["+46"],"name":"Sweden","cn":"瑞典","continent":"Europe"},{"alpha2":"SG","alpha3":"SGP","countryCallingCodes":["+65"],"name":"Singapore","cn":"新加坡","continent":"Asia"},{"alpha2":"SH","alpha3":"SHN","countryCallingCodes":["+290"],"name":"Saint Helena, Ascension And Tristan Da Cunha","cn":"圣赫勒拿岛","continent":"other"},{"alpha2":"SI","alpha3":"SVN","countryCallingCodes":["+386"],"name":"Slovenia","cn":"斯洛文尼亚","continent":"Europe"},{"alpha2":"SJ","alpha3":"SJM","countryCallingCodes":["+47"],"name":"Svalbard And Jan Mayen","cn":"斯瓦尔巴特群岛","continent":"Europe"},{"alpha2":"SK","alpha3":"SVK","countryCallingCodes":["+421"],"name":"Slovakia","cn":"斯洛伐克","continent":"Europe"},{"alpha2":"SL","alpha3":"SLE","countryCallingCodes":["+232"],"name":"Sierra Leone","cn":"塞拉利昂","continent":"Africa"},{"alpha2":"SM","alpha3":"SMR","countryCallingCodes":["+378"],"name":"San Marino","cn":"圣马力诺","continent":"Europe"},{"alpha2":"SN","alpha3":"SEN","countryCallingCodes":["+221"],"name":"Senegal","cn":"塞内加尔","continent":"Africa"},{"alpha2":"SO","alpha3":"SOM","countryCallingCodes":["+252"],"name":"Somalia","cn":"索马里","continent":"Africa"},{"alpha2":"SR","alpha3":"SUR","countryCallingCodes":["+597"],"name":"Suriname","cn":"苏里南","continent":"South America"},{"alpha2":"SS","alpha3":"SSD","countryCallingCodes":["+211"],"name":"South Sudan","cn":"南苏丹","continent":"Africa"},{"alpha2":"ST","alpha3":"STP","countryCallingCodes":["+239"],"name":"São Tomé and Príncipe","cn":"圣多美和普林西比","continent":"Africa"},{"alpha2":"SV","alpha3":"SLV","countryCallingCodes":["+503"],"name":"El Salvador","cn":"萨尔瓦多","continent":"North America"},{"alpha2":"SX","alpha3":"SXM","countryCallingCodes":["+1 721"],"name":"Sint Maarten","cn":"荷属圣马丁","continent":"North America"},{"alpha2":"SY","alpha3":"SYR","countryCallingCodes":["+963"],"name":"Syrian Arab Republic","cn":"叙利亚","continent":"Asia"},{"alpha2":"SZ","alpha3":"SWZ","countryCallingCodes":["+268"],"name":"Swaziland","cn":"斯威士兰","continent":"Africa"},{"alpha2":"TA","alpha3":"","countryCallingCodes":["+290"],"name":"Tristan de Cunha","cn":"特里斯坦-达库尼亚群岛","continent":"other"},{"alpha2":"TC","alpha3":"TCA","countryCallingCodes":["+1 649"],"name":"Turks And Caicos Islands","cn":"特克斯和凯科斯群岛","continent":"other"},{"alpha2":"TD","alpha3":"TCD","countryCallingCodes":["+235"],"name":"Chad","cn":"乍得湖","continent":"Africa"},{"alpha2":"TF","alpha3":"ATF","countryCallingCodes":[],"name":"French Southern Territories","cn":"法属南部领地","continent":"other"},{"alpha2":"TG","alpha3":"TGO","countryCallingCodes":["+228"],"name":"Togo","cn":"多哥","continent":"Africa"},{"alpha2":"TH","alpha3":"THA","countryCallingCodes":["+66"],"name":"Thailand","cn":"泰国","continent":"Asia"},{"alpha2":"TJ","alpha3":"TJK","countryCallingCodes":["+992"],"name":"Tajikistan","cn":"塔吉克斯坦","continent":"Asia"},{"alpha2":"TK","alpha3":"TKL","countryCallingCodes":["+690"],"name":"Tokelau","cn":"托克劳群岛","continent":"Oceania"},{"alpha2":"TL","alpha3":"TLS","countryCallingCodes":["+670"],"name":"East Timor","cn":"东帝汶","continent":"Asia"},{"alpha2":"TM","alpha3":"TKM","countryCallingCodes":["+993"],"name":"Turkmenistan","cn":"土库曼斯坦","continent":"Asia"},{"alpha2":"TN","alpha3":"TUN","countryCallingCodes":["+216"],"name":"Tunisia","cn":"突尼斯","continent":"Africa"},{"alpha2":"TO","alpha3":"TON","countryCallingCodes":["+676"],"name":"Tonga","cn":"汤加","continent":"Oceania"},{"alpha2":"TR","alpha3":"TUR","countryCallingCodes":["+90"],"name":"Turkey","cn":"土耳其","continent":"Asia"},{"alpha2":"TT","alpha3":"TTO","countryCallingCodes":["+1 868"],"name":"Trinidad And Tobago","cn":"特立尼达和多巴哥","continent":"South America"},{"alpha2":"TV","alpha3":"TUV","countryCallingCodes":["+688"],"name":"Tuvalu","cn":"图瓦卢","continent":"Oceania"},{"alpha2":"TW","alpha3":"TWN","countryCallingCodes":["+886"],"name":"Taiwan, Province Of China","cn":"中国台湾","continent":"Asia"},{"alpha2":"TZ","alpha3":"TZA","countryCallingCodes":["+255"],"name":"Tanzania, United Republic Of","cn":"坦桑尼亚","continent":"Africa"},{"alpha2":"UA","alpha3":"UKR","countryCallingCodes":["+380"],"name":"Ukraine","cn":"乌克兰","continent":"Europe"},{"alpha2":"UG","alpha3":"UGA","countryCallingCodes":["+256"],"name":"Uganda","cn":"乌干达","continent":"Africa"},{"alpha2":"UM","alpha3":"UMI","countryCallingCodes":[],"name":"United States Minor Outlying Islands","cn":"美国本土外小岛屿","continent":"other"},{"alpha2":"US","alpha3":"USA","countryCallingCodes":["+1"],"name":"United States","cn":"美国","continent":"North America"},{"alpha2":"UY","alpha3":"URY","countryCallingCodes":["+598"],"name":"Uruguay","cn":"乌拉圭","continent":"South America"},{"alpha2":"UZ","alpha3":"UZB","countryCallingCodes":["+998"],"name":"Uzbekistan","cn":"乌兹别克斯坦","continent":"Asia"},{"alpha2":"VA","alpha3":"VAT","countryCallingCodes":["+379","+39"],"name":"Vatican City State","cn":"梵蒂冈城国","continent":"Europe"},{"alpha2":"VC","alpha3":"VCT","countryCallingCodes":["+1 784"],"name":"Saint Vincent And The Grenadines","cn":"圣文森特和格林纳丁斯","continent":"South America"},{"alpha2":"VE","alpha3":"VEN","countryCallingCodes":["+58"],"name":"Venezuela, Bolivarian Republic Of","cn":"委内瑞拉","continent":"South America"},{"alpha2":"VG","alpha3":"VGB","countryCallingCodes":["+1 284"],"name":"Virgin Islands (British)","cn":"维尔京群岛（英国）","continent":"North America"},{"alpha2":"VI","alpha3":"VIR","countryCallingCodes":["+1 340"],"name":"Virgin Islands (US)","cn":"维尔京群岛（美国）","continent":"North America"},{"alpha2":"VN","alpha3":"VNM","countryCallingCodes":["+84"],"name":"Viet Nam","cn":"越南","continent":"Asia"},{"alpha2":"VU","alpha3":"VUT","countryCallingCodes":["+678"],"name":"Vanuatu","cn":"瓦努阿图","continent":"Oceania"},{"alpha2":"WF","alpha3":"WLF","countryCallingCodes":["+681"],"name":"Wallis And Futuna","cn":"瓦利斯和富图纳群岛","continent":"Oceania"},{"alpha2":"WS","alpha3":"WSM","countryCallingCodes":["+685"],"name":"Samoa","cn":"萨摩亚群岛","continent":"Oceania"},{"alpha2":"YE","alpha3":"YEM","countryCallingCodes":["+967"],"name":"Yemen","cn":"也门","continent":"Asia"},{"alpha2":"YT","alpha3":"MYT","countryCallingCodes":["+262"],"name":"Mayotte","cn":"马约特岛","continent":"Africa"},{"alpha2":"ZA","alpha3":"ZAF","countryCallingCodes":["+27"],"name":"South Africa","cn":"南非","continent":"Africa"},{"alpha2":"ZM","alpha3":"ZMB","countryCallingCodes":["+260"],"name":"Zambia","cn":"赞比亚","continent":"Africa"},{"alpha2":"ZW","alpha3":"ZWE","countryCallingCodes":["+263"],"name":"Zimbabwe","cn":"津巴布韦","continent":"Africa"},{"alpha2":"KS","alpha3":"ZWE","name":"Kosovo","cn":"科索沃","continent":"Europe"},{"alpha2":"EC","alpha3":"ECU","name":"Republic of Ecuado","cn":"厄瓜多尔","continent":"South America"},{"alpha2":"EU","alpha3":"","countryCallingCodes":["+388"],"name":"European Union","cn":"欧洲联盟","continent":"Europe"},{"alpha2":"AN","alpha3":"","name":"Netherlands Antilles","cn":"荷属安的列斯群岛","continent":"South America"}]
EOF;
    	
    	$countryList = json_decode($country_json, true);
    	
    	$countryListArr = array();
    	
    	foreach ($countryList as $countryListVal){
    		$countryListArr[$countryListVal['alpha2']] = array('continent'=>$countryListVal['continent'], 'cn'=>$countryListVal['cn'], 'en_name'=>$countryListVal['name']);
    	}
    		//save the redis cache for next time use
    		RedisHelper::setOrderCache(0,'system',"ScopeCountry",json_encode($countryListArr)) ;
    }
    	
    	
    	return $countryListArr;
    }
}