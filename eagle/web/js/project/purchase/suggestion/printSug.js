/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: dzt <zhitian.deng@witsion.com> 2014-08-12 eagle 1.0
+----------------------------------------------------------------------
| Copy by: lzhl <zhiliang.lu@witsion.com> 2015-04-20 eagle 2.0
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *打印所选采购建议的界面js
 +------------------------------------------------------------------------------
 * @category	js/project/purchase
 * @package		suggestion
 * @subpackage  Exception
 +------------------------------------------------------------------------------
 */

if (typeof printSug === 'undefined')  printSug = new Object();
printSug.page = {
	init : function(){
		printInfo = opener.purchaseSug.list.printInfo;
		//console.log(typeof( opener.purchaseSug.list));
		if(typeof(printInfo) != 'undefined' && printInfo ){
			var columns = printInfo.columns;
			var dataRows = printInfo.dataRows;
			
			var totalWidth = 0;
			for(var i in columns){
				if(i=='remove')
					continue;
				totalWidth += columns[i].width;
			}
			printHtml = '<table cellspacing="0" border="1" width="'+totalWidth+'px">';

			// 载入title	
			for(var i in columns){
				if(i=='remove')
					continue;
				printHtml += '<th style="border:1px solid;font-size:16px;width:' + columns[i].width/totalWidth*100 + '%;';
				if (typeof columns[i].align == 'undefined' || columns[i].align == '') {
		            	columns[i].align = 'center';
		            }
		        printHtml += 'text-align:' + columns[i].align + ';"';
				if (typeof columns[i].rowspan != 'undefined' && columns[i].rowspan > 1) {
					printHtml += ' rowspan="' + columns[i].rowspan + '"';
				}
				if (typeof columns[i].colspan != 'undefined' && columns[i].colspan > 1) {
					printHtml += ' colspan="' + columns[i].colspan + '"';
				}
				
				printHtml += '>' + columns[i].title + '</th>';
			}
			 // 载入内容
		    for (var i = 0; i < dataRows.length; i++) {
		    	printHtml += '<tr>';
		    	for(var j in columns){
					if(j=='remove')
					continue;
		            printHtml += '<td';
		            if (typeof columns[j].align == 'undefined' || columns[j].align == '') {
		            	columns[j].align = 'center';
		            }
		            printHtml += ' style="border:1px solid;text-align:' + columns[j].align + ';"';
		            printHtml += '>';
		            if(columns[j].field == "img"){
		            	printHtml += "<img src='"+dataRows[i].img+"' style='width:"+columns[j].width+"px ! important;height:"+columns[j].width+"px ! important;padding:5px;'>";
		            }else{
		            	if(dataRows[i][columns[j].field] != null)
		            		printHtml += dataRows[i][columns[j].field];
		            }
		            printHtml += '</td>';
		        }
		        printHtml += '</tr>';
		    }
		    printHtml += '</table>';
			
		    $('body').html(printHtml);
		}else{
			bootbox.alert({
				title:'Warning',
				message:Translator.t('没有被打印的行！'),
			});
		}	
	},
};