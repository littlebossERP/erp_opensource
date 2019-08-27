
//倒计时  （使用方法：addTimer("timer1", 604800); ）
var addTimer = function () {     
        var list = [],     
            interval;     
    
        return function (id, time) {     
            if (!interval)     
                interval = setInterval(go, 1000);     
            list.push({ 'id':id, 'time': time });     
        };     
    
        function go() {   
        	if(list.length==0){
        		clearTimeout(interval);
        		interval = "";
        	}
        	
            for (var i = 0; i < list.length; i++) {
            	var obj = document.getElementById( list[i].id );
            	if(obj==undefined){
            		 list.splice(i--, 1); 
            		 continue;
            	}
            	
               obj.innerHTML = getTimerString(list[i].time ? list[i].time -= 1 : 0);   
               
               if (list[i].time<0){     
                   list.splice(i--, 1);  
                   continue;
               }
            }     
        }     
    
        function getTimerString(time) {     
                d = Math.floor(time / 86400),     
                h = Math.floor((time % 86400) / 3600),     
                m = Math.floor(((time % 86400) % 3600) / 60),     
                s = Math.floor(((time % 86400) % 3600) % 60);   
         /*       
                if(h<10)
                	h = "0" + h;
                if(m<10)
                	m= "0" + h;
                if(s<10)
                	s = "0" + s;
           */
            if (time>0)     
                return "剩余发货：<span style=\"color:red;\">" + d + "</span>" + "天" + "<span style=\"color:red;\">" + h + "</span>" + "小时" + "<span style=\"color:red;\">" + m + "</span>" + "分";
            else return "<span style=\"color:red;\">剩余发货：已到期</span>";
        }     
} (); 

//物流操作
$('.do-carrier').change(function(){
	var action = $(this).val();
	if(action==''){
		return false;
	}
	var count = $('.ck:checked').length;
	if(count == 0){
		alert('请选择订单!');
		return false;
	}
	switch(action){
		case 'getorderno':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			OrderCommon.setShipmentMethod(idstr);
			/*
			document.a.target="_blank";
			document.a.action=global.baseUrl+"carrier/carrierprocess/waitingpost";
			document.a.submit();
			document.a.action="";
			*/
			break;
		case 'dodispatch':
			document.a.target="_blank";
			document.a.action=global.baseUrl+"carrier/carrieroperate/dodispatch";
			document.a.submit();
			document.a.action="";
			break;
		case 'gettrackingno':
			document.a.target="_blank";
			document.a.action=global.baseUrl+"carrier/carrieroperate/gettrackingno";
			document.a.submit();
			document.a.action="";
			break;
		case 'doprint':
			document.a.target="_blank";
			document.a.action=global.baseUrl+"carrier/carrieroperate/doprint";
			document.a.submit();
			document.a.action="";
			break;
		case 'cancelorderno':
			document.a.target="_blank";
			document.a.action=global.baseUrl+"carrier/carrieroperate/cancelorderno";
			document.a.submit();
			document.a.action="";
			break;
		case 'recreate':
			document.a.target="_blank";
			document.a.action=global.baseUrl+"carrier/carrieroperate/recreate";
			document.a.submit();
			document.a.action="";
			break;
		case 'signwaitsend':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			OrderCommon.shipOrder(idstr);
			
			break;
		default:
			return false;
			break;
	}
}).mousedown(function(){$(this).val('');});
$('.do').mousedown(function(){$(this).val('');});

$("#ck_all").click(function(){
	if($(this).prop("checked")==true){
		$(".ck").prop("checked",true);
	}else{
		$(".ck").prop("checked",false);
	}

});

$('.showAdvSearch').click(function(){
		$('.adv-search-opt').removeClass('off');
		$('.search-area-bg').css("border","1px solid #f4f9fc");
		$('select.adv-search-opt[disabled],input.adv-search-opt[disabled],button.adv-search-opt[disabled]').removeAttr('disabled');
		$('.showAdvSearch').addClass('off');
		$('.hideAdvSearch').removeClass('off');
});
$('.hideAdvSearch').click(function(){
	$('.adv-search-opt').addClass('off');
	$('.search-area-bg').css("border-width","0px");
	$('select.adv-search-opt[disabled],input.adv-search-opt[disabled],button.adv-search-opt[disabled]').attr('disabled',true);
	$('.hideAdvSearch').addClass('off')
	$('.showAdvSearch').removeClass('off');
});
$('tr').has('.egicon-flag-gray').on('mouseover',function(){
	if ($(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
	$(this).find('.btn_tag_qtip').removeClass('div_space_toggle');
});

$('tr').has('.egicon-flag-gray').on('mouseleave',function(){
	if (! $(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
		$(this).find('.btn_tag_qtip').addClass('div_space_toggle');
});
$(function(){
	OrderTag.init();
	//加载订单信息
	$(".order-info").each(function(){
		var id = $(this).text();
		var p = $(this).parent("a");
		var info_type = $(p).data('info-type');
		var info_type = $(p).data('info-type');
		if(info_type=='17track')
			return true;

		$(this).qtip({ 
			show: {
				event: 'click',
				solo: true,
			},
			hide: 'click',
			content: {
				text: $("#div_more_info_"+id),
				
			},
			style: {
	            classes: 'qtip qtip-default basic-qtip nopadding',
	            width:'600px'
	        },
	        position:{
	        	my:'top right',
	        },
		});  
	});

	$(".fulfill_timeleft").each(function(){
		addTimer($(this).prop('id'),$(this).data('time'));
	});
	
});

if (typeof OrderList === 'undefined')  OrderList = new Object();
OrderList = {
	initClickTip : function(){
		$('.click-to-tip').each(function(){
			OrderList.initMemoQtipEntryBtn(this);
		});
	},
	
	initMemoQtipEntryBtn:function(obj){
		var btnObj = $(obj);
		var tipkey = $(obj).data('qtipkey');
		btnObj.qtip({
			show: {
				event: 'mouseover',
				solo: true,
			},
			hide: 'click',
			content: {
			    button:true,
				text: $('#'+tipkey).html(),
		    },
			position: {
				my: 'top center',
				at: 'bottom center',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},

			style: {
				classes: 'basic-qtip nopadding',
				width:600
			},
		});
		btnObj.prop('title','点击查看相关说明');
	},
	
	//高级搜索
	mutisearch:function(){
		var status = $('.mutisearch').is(':hidden');
		if(status == true){
			//未展开
			$('.mutisearch').show(500);
			$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
			return false;
		}else{
			$('.mutisearch').hide(500);
			$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
			return false;
		}
		
	}
}
