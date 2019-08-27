/**
 * 前台自定义操作行为记录js
 */

if (typeof littlebossAppTracker === 'undefined')  littlebossAppTracker = new Object();

/**
+----------------------------------------------------------
* app相关行为记录
+----------------------------------------------------------
* @access public
+----------------------------------------------------------
* @param appKey			每个app对应的全局唯一的key值。
* @param urlPath		是指页面url（网站的域名就不需要提供）或自定义的前台用户操作行为（如的一些不与后台交互的button click事件）。
* @param isLandpage		Y或者N，默认是N。  是指是否把该自定义的访问看做是1个着落页的访问，是的话，后面在app的着落页访问统计中会出现。
+----------------------------------------------------------
* log			name	date					note
* @author		dzt		2015/03/17				初始化
+----------------------------------------------------------
**/

littlebossAppTracker.log = function( appKey , urlPath , isLandpage ){
	if(typeof isLandpage == "undefined")
		isLandpage = 'N';
	
	$.ajax({
        type:'post',
        cache:false,
        url: '/app/app/tracker',
        data:{appKey:appKey,urlPath:urlPath,isLandpage:isLandpage},
      
    });
}