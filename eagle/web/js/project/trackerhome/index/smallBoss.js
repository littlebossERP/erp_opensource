var guanwang = {};
guanwang.index = {};
guanwang.index.smallboss = {
    changeLabel: function(){
        $(".aleTab>li:eq(1)").addClass("on");
        $(".aleTab>li:eq(0)").removeClass("on");
        $(".aleCon>.tab").hide();
        $(".aleCon>.tab").eq($(".aleTab>li:eq(1)").index()).show();
    }
}


// JavaScript Document

$(function(){
    $('.guanwang_index_smallboss_free').click(function(){
    	$(".aleTab>li").removeClass("on");
        $(".aleTab>li:eq(1)").addClass("on");
        $(".aleCon>.tab").hide();
        $(".aleCon>.tab").eq($(".aleTab>li:eq(1)").index()).show();
    });

    $('#guanwang-index-smallboss-login').click(function(){
    	$(".aleTab>li").removeClass("on");
        $(".aleTab>li:eq(0)").addClass("on");
        $(".aleCon>.tab").hide();
        $(".aleCon>.tab").eq($(".aleTab>li:eq(0)").index()).show();
    });

    $('.guanwang_index_forgetpass').click(function(){
    	$(".aleTab>li").removeClass("on");
    	if($(this).hasClass('clickAlert') || $(".aleTab>li:eq(2)").css('display') != 'none'){
    		$(".aleTab>li:eq(2)").show();
    		$(".aleTab>li:eq(2)").addClass("on");
    	}
    	
        $(".aleCon>.tab").hide();
        $(".aleCon>.tab").eq($(".aleTab>li:eq(2)").index()).show();
    });
    
    
	/*CSS*/
	$(".nav a:last").css("padding","0").css("marginLeft","15px").css("background","none").css("border","none");
	$(".inforTab li a:last").css("marginRight","0");
	$(".infor dl:eq(2)").css("background","none");
	$(".infor dl:eq(5)").css("background","none");
	$(".infor dl:eq(8)").css("background","none");
	
	$(".nav a:last,.submit,.reg-submit").css("opacity","0.9");
	$(".nav a:last,.submit,.reg-submit").hover(function(){
		$(this).css("opacity","1");	
	},function(){
		$(this).css("opacity","0.9");
	})
	
	/*Tab*/
	tab(".inforTab>li",".inforCon>.tab");
	tab(".aleTab>li",".aleCon>.tab");
	tab(".helpTab>li",".helpCon>.tab");
	
	tab(".helpTT1-tab>li",".helpTT1-con>.tab");
	tab(".helpTT2-tab>li",".helpTT2-con>.tab");
	tab(".helpTT3-tab>li",".helpTT3-con>.tab");
	tab(".helpTT4-tab>li",".helpTT4-con>.tab");
	tab(".helpTT5-tab>li",".helpTT5-con>.tab");
	tab(".helpTT6-tab>li",".helpTT6-con>.tab");
	tab(".helpTT7-tab>li",".helpTT7-con>.tab");
	tab(".helpTT8-tab>li",".helpTT8-con>.tab");
	tab(".helpTT9-tab>li",".helpTT9-con>.tab");
	tab(".helpTT10-tab>li",".helpTT10-con>.tab");
	
	function tab(oTab,oNeirong){
		$(oTab).click(function(){
			$(this).addClass("on");
			$(this).siblings().removeClass("on");
			$(oNeirong).hide();
			$(oNeirong).eq($(this).index()).show();
		})
	}
	
	/*产品与服务点击切换*/
	$(".proSerTab>li").click(function(){
		$(this).addClass("on");
		$(this).siblings().removeClass("on");
		$(".proSerCon>.tab").hide();
		$(".proSerCon>.tab").eq($(this).index()).show();
	})

    /*合作切换*/
    var area = $("ul.scrolline");
    var timespan = 3000;
    var timeID;

    area.hover(function(){
        clearInterval(timeID);
    },function(){
        timeID = setInterval(function(){
            var moveline = area.find("li:first");
            var linewidth = moveline.width();
            moveline.animate({marginLeft:-linewidth+"px"},500,function(){
                moveline.css("marginLeft",0).appendTo(area);
            });
        },timespan)
    }).trigger("mouseleave");

    /*alert*/
    alertContent(".clickAlert",".alertDiv",".alertClose");
    alertContent(".clickAlert1",".alertDiv1",".alertClose1");

    function alertContent(LeanA,LeanB,LeanC){
        $(LeanB).hide();
        $(LeanA).click(function(){
            $(LeanB).fadeIn(200);
        })
        $(LeanC).click(function(){
            $(LeanB).fadeOut(200);
            $(".xy").hide();
        })
    }

    $(".alert_hideDiv").click(function(){
        $(".xy").show();
		$(".reg-submit").hide();
		$(".reg_b").show();
    })
	
	$(".reg_b").click(function(){
		$(this).hide();
		$(".xy").hide();	
		$(".reg-submit").show();
	})
	
	/*input 失去获取焦点*/
	inputFB(".ueername");
	inputFB(".password");
	inputFB(".ueername");
	inputFB(".ueername");
	function inputFB(inputClass){
		$(inputClass).focus(function(){
			if($(this).val() == this.defaultValue){
				$(this).val("");	
			}
		})
		$(inputClass).blur(function(){
			if($(this).val() == ""){
				$(this).val(this.defaultValue);	
			}
			
		})	
	}
	
	/*hideMenu*/
	$(".nav").hover(function(){
		$(".hideDiv").show();	
	});
	$(".hideDiv").mouseleave(function(){
		$(".hideDiv").hide();	
	});
})
