// JavaScript Document
// 字符上限截取
function subString(str, len, hasDot){
    var newLength = 0;
    var newStr = "";
    var chineseRegex = /[^\x00-\xff]/g;
    var singleChar = "";
    var strLength = str.replace(chineseRegex,"**").length;
    for(var i = 0;i < strLength;i++){
        singleChar = str.charAt(i).toString();
        if(singleChar.match(chineseRegex) != null){
            newLength += 2;
        }else{
            newLength++;
        }
        if(newLength > len){
            break;
        }
        newStr += singleChar;
    }
    if(hasDot && strLength > len){
        newStr += "...";
    }
    return newStr;
}
//大列表自定义滚动
function autoHeight(){
	var toolsHeight=$(".autoscrolllist .list-body").offset().top+55,
		winHeight=$(window).height()-toolsHeight;
		$(".autoscrolllist .list-body").height(winHeight);
};
//清除空格
function trim(ss){return ss.replace(/(^\s*)|(\s*$)/g, "");}

//图片延迟加载
function loadLazyImage(){
	$("img.lazy").lazyload({effect : "fadeIn"});
};

$(document).ready(function(){
	//左侧tab跟随屏幕滚动
	if($(".fixed-scroll").length > 0){
		var sHegiht= $(".fixed-scroll").offset().top;
		var tHegiht=parseInt(sHegiht)-40;
		$(window).on("scroll",function(){
			var sWidth= $(".fixed-scroll").width();
			if($(window).width()>=767)$(this).scrollTop()>tHegiht? $(".fixed-scroll").css({"position":"fixed","top":"40px","width":sWidth}):$(".fixed-scroll").css({"position":"inherit","top":"auto","width":"auto"})
		});
	};
	
	/*$("#marquee-group").marquee({yScroll: "top",scrollSpeed:10});*/
	
	//限制textarea字符数量
	$("textarea[data-toggle='textcounter']").on("keyup",function(){
		var	maxlimit=parseInt($(this).attr("data-limit")),
			limitNum=$(this).next().find(".limitNum");
		$(this).val().length > maxlimit?$(this).val($(this).val().substring(0, maxlimit)):limitNum.text(maxlimit - $(this).val().length);
	});
	
	//tab跨页面传参
	if($(".tab-pane").length > 0){
		var QueryString,arr =new Array(), paneId;
		var URL = document.location.toString();
		if(URL.indexOf("pane")!=-1){
			QueryString=URL.substring(URL.indexOf("?")+1,URL.length);
			arr=QueryString.split("&");
			for(var i=0; i<arr.length; i++){
				if(arr[i].search("pane")!=-1)paneId="#"+arr[i].substring(5,arr[i].length);
			}
			$(paneId).addClass("active").siblings(".tab-pane").removeClass("active");
			$("a[data-toggle='tab'][href='"+paneId+"']").parent("li").addClass("active").siblings("li").removeClass("active");
			$(".nav li[rel='"+paneId+"']").addClass("active").siblings("li").removeClass("active");
		}else QueryString = "";
	}
	
	//下拉选单点击copy选项文字
	$(".copytext li[class!='edit'][class!='dropdown-submenu']").children("a").on("click",function(){
		$(this).parents(".btn-group, .nav").find("span.text").stop().html($(this).html()).find(".badge").remove();
	});
	
	//新增SKU
	$(".labelkeyword .keyword, .form-control.stockskuname").keypress(function (e) {
		var key = e.which;
		if (key == 13) {
			$(this).next(".input-group-btn").find(".btn-success").click();
		};
	});
	
	//站点国旗注释,操作按钮注释
	$(".img-flag, .icon-btn, .messageico").tooltip({
		container:"body",
		trigger:"hover ",
		placement: "top"
	});
	
	//数字输入框组件
	$("#main").on("click",".customnum .add",function(){
		var n=$(this).parent(".customnum").find(".form-control"),o=$(this).parents("tr, li").find(".total"),s=parseInt(n.val()),t= s*1 + 1;
		if(o.length>0&&s>=parseInt(o.html())){
			alert("超过限定数量");
			n.val("0").prev(".subtract").addClass("text-gray");
		}
		else{
			n.val(t);
			$(this).parent(".customnum").find(".subtract").removeClass("text-gray");
		};
	});
	$("#main").on("click",".customnum .subtract",function(){
		if(!$(this).hasClass("text-gray")){
			var n=$(this).parent(".customnum").find(".form-control"),s=parseInt(n.val()), t=s*1-1;
			if(s>=1){
				n.val(t);
				if(s==1)$(this).addClass("text-gray");
			}
		}
	});
	$("#main").on("change",".customnum .form-control",function(){
		var o=$(this).parents("tr, li").find(".total"),s=parseInt($(this).val());
		if(o.length>0&&s>parseInt(o.html())){
			alert("超过限定数量");
			$(this).val("0").prev(".subtract").addClass("text-gray");
		}else if(s==0)$(this).parent(".customnum").find(".subtract").addClass("text-gray");
		else $(this).parent(".customnum").find(".subtract").removeClass("text-gray");
	});
	
	//显示&隐藏商品详细
	$("#showDetail").on("click",function(){
		if($(this).attr("checked")){
			$(".autoscrolllist .unfoldicon").removeClass("ico-plus-circle").addClass("ico-minus-circle").parents("li").find(".productTr").show();
			$(".panel-group  .panel-collapse, .panel-group .productTr").show();
			$(".panel-group .unfoldicon").removeClass("ico-plus-circle2").addClass("ico-minus-circle2");
		}
		else {
			$(".autoscrolllist .unfoldicon").addClass("ico-plus-circle").removeClass("ico-minus-circle").parents("li").find(".productTr").hide();
			$(".panel-group .panel-collapse, .panel-group .productTr").hide();
			$(".panel-group .unfoldicon").removeClass("ico-minus-circle2").addClass("ico-plus-circle2");
		}
	});
	//显示&隐藏内容
	$(".autoscrolllist .unfoldicon").on("click",function(){
		$(this).toggleClass("ico-plus-circle").toggleClass("ico-minus-circle").parents("li").find(".productTr").toggle();
		var allsub = $(".autoscrolllist .productTr").length;
		var showsub = $(".autoscrolllist .productTr").not(":hidden").length;
		if(showsub==allsub)$("#showDetail").attr("checked",true);
		else $("#showDetail").attr("checked",false);
	});
	
	//窗口内员工全选取消功能
	$(".choicestaff").on("click","input[type='checkbox'][value='all']",function(){
		var objName=$(this).attr("name");
		if ($(this).attr("checked")) {  
			$("input[name='"+objName+"']").attr("checked", true).parent("label").addClass("active");
		} else {  
			$("input[name='"+objName+"']").attr("checked", false).parent("label").removeClass("active");
		}
	});
	//窗口内员工复选框的事件
	$(".choicestaff").on("click","input[type='checkbox'][value!='all']",function(){
		$(this).attr("checked")?$(this).parent("label").addClass("active"):$(this).parent("label").removeClass("active");
		var objName=$(this).attr("name");
		if (!$("input[name='"+objName+"']").checked) {  
			$("input[name='"+objName+"'][value='all']").attr("checked", false);
		}
		var chsub = $("input[name='"+objName+"'][value!='all']").length;
		var checkedsub = $("input[name='"+objName+"'][value!='all']:checked").length;  
		if (checkedsub == chsub) {  
			$("input[name='"+objName+"'][value='all']").attr("checked", true);  
		}  
	});
	
	//全选取消功能
	$(".multichoice").on("click","input[type='checkbox'][value='all']",function(){
		var objName=$(this).attr("name");
		if ($(this).attr("checked")){
			$("input[name='"+objName+"']").not(':disabled').attr("checked", true).parents("tr, li").addClass("active");
		} else {
			$("input[name='"+objName+"']").not(':disabled').attr("checked", false).parents("tr, li").removeClass("active");
		}
	});
	//复选框的事件
	$(".multichoice").on("click","input[type='checkbox'][value!='all']",function(){
		if ($(this).attr("checked"))$(this).parents("tr, li").addClass("active");
		else $(this).parents("tr, li").removeClass("active");
		var objName=$(this).attr("name");
		if (!$("input[name='"+objName+"']").not(':disabled').checked) {
			$("input[name='"+objName+"'][value='all']").attr("checked", false);
		}
		var chsub = $("input[name='"+objName+"'][value!='all']").not(':disabled').length;
		var checkedsub = $("input[name='"+objName+"'][value!='all']:checked").not(':disabled').length;  
		if (checkedsub == chsub) {  
			$("input[name='"+objName+"'][value='all']").attr("checked", true);  
		}  
	});
	
	//复选框文字变化
	$(".multicheck input[type='checkbox']").on("click",function(){
		if($(this).attr("checked")){
			$(this).parent("label").addClass("active");
		}else {
			$(this).parent("label").removeClass("active");
		};
	});
	
	//多选下拉框
	function checkSelected(obj){
		var parent=obj.parents(".multiple-group"),
			chsub=parent.find("input[type='checkbox'][value!='all']").length,
			checked=parent.find("input[type='checkbox'][value!='all']:checked").length;
		parent.find(".choice strong").html(checked);
		checked == chsub?parent.find("input[type='checkbox'][value='all']").attr("checked", true):parent.find("input[type='checkbox'][value='all']").attr("checked", false);
	};
	$(".multiple-group").on("click","input[type='checkbox'][value!='all']",function(){
		$(this).attr("checked")?$(this).parent("label").next("select.form-control").attr("disabled",false):$(this).parent("label").next("select.form-control").attr("disabled",true);
		checkSelected($(this));
	});
	$(".multiple-group").on("click","input[type='checkbox'][value='all']",function(){
		var parent= $(this).parents(".multiple-group");
		$(this).attr("checked")?parent.find("input[type='checkbox'][value!='all']").attr("checked", true):parent.find("input[type='checkbox'][value!='all']").attr("checked", false);
		checkSelected($(this));
	});
	$(".multiple-group").on("click",".checkbox-inline, select.form-control",function(event){
		event.stopPropagation();
	});
	
	//大列表表头排序按钮
	$(".table thead .sort").addClass("sorting");
	$(".table").on("click",".sort",function(){
		if($(this).hasClass("sorting"))$(this).removeClass("sorting").addClass("sorting_asc");
		else if($(this).hasClass("sorting_asc"))$(this).removeClass("sorting_asc").addClass("sorting_desc");
		else if($(this).hasClass("sorting_desc"))$(this).removeClass("sorting_desc").addClass("sorting_asc");
		$(this).parent("th").siblings("th").find(".sort").removeClass("sorting_asc").removeClass("sorting_desc").addClass("sorting");
	});
	$(".table").on("click",".sorting_asc",function(){
		$(this).removeClass("sorting_asc").addClass("sorting_desc");
		$(this).siblings("li.sort").removeClass("sorting_asc, sorting_desc").addClass("sorting");
	});
	$(".table").on("click",".sorting_desc",function(){
		$(this).removeClass("sorting_desc").addClass("sorting_asc");
		$(this).siblings("li.sort").removeClass("sorting_asc, sorting_desc").addClass("sorting");
	});
	
	$(".panelshow").on("click", function() {
		if($(".typepanel").css("top")=="0px"){
			$("html").animate({ marginTop: "0" });
			$(".typepanel").animate({ top: "-41px" });
			$(this).removeClass("opened");
			$(this).find("i").removeClass("ico-minus").addClass("ico-plus");
		}else{
			$("html").animate({ marginTop: "41px" });
			$(".typepanel").animate({ top: "0" });
			$(this).addClass("opened");
			$(this).find("i").removeClass("ico-plus").addClass("ico-minus");
		}
	});
});