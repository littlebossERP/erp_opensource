/**
 +------------------------------------------------------------------------------
 * tracking信息及推荐商品页面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		message/tracking
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof message === 'undefined')  message = new Object();
/*
message.setting = {
		'selectedActiveStatus':0,
		'activeStatus':"",
		'CountriesCnName':""
};
*/
message.tracking = {
	
		
};

message.recommend = {
	'puid':'',
	'recommendProducts':new Array(),
	'productHadShowed':new Array(),
	'productHadClicked':new Array(),
	'recommendPage':1,
	'recommendPageSize':4,
	'init' : function(){
		message.recommend.refreshRecommend();
		
		$(".prev_recommends").click(function(){
			message.recommend.recommendPage --;
			var totalPage = Math.ceil( message.recommend.recommendProducts.length /  message.recommend.recommendPageSize);
			if (message.recommend.recommendPage<1)
				message.recommend.recommendPage = totalPage;
			message.recommend.refreshRecommend();
			
		});
		$(".next_recommends").click(function(){
			message.recommend.recommendPage ++;
			var totalPage = Math.ceil( message.recommend.recommendProducts.length /  message.recommend.recommendPageSize);
			if (message.recommend.recommendPage>totalPage)
				message.recommend.recommendPage = 1;
			message.recommend.refreshRecommend();
		});
	},
	'refreshRecommend':function(){
		var page = message.recommend.recommendPage;
		var prePage = message.recommend.recommendPageSize
		if(message.recommend.recommendProducts.length <= prePage){
			$(".prev_recommends,.next_recommends").remove();
		}
		var start = (page-1) * prePage;
		var end = page*prePage - 1;
		$(".recommend_a").addClass('hidden');
		
		var newShowProdIds = '';
		for(var i=start; i<=end; i++){
			$(".recommend_a[index="+i+"]").removeClass('hidden');
			var prodIndex = $(".recommend_a[index="+i+"]").attr('index');
			var prodId =  $("input#recommend_"+prodIndex).val();
			if($.inArray(prodId,message.recommend.productHadShowed)==-1){
				message.recommend.productHadShowed.push(prodId);
				if(newShowProdIds=='') newShowProdIds=prodId;
				else newShowProdIds+=','+prodId;
			}
		}
		if(newShowProdIds!==''){
			//message.recommend.countProductShowed(newShowProdIds);
		}
	},
	'countProductShowed':function(ids){
		$.ajax({
			type: "get",
			dataType: 'json',
			data:{puid:message.recommend.puid},
			url: '/message/tracking/add-view-count?ids='+ids,
			success:function(data){
			}
		});
	},
	'countProductClicked':function(id){
		if($.inArray(id,message.recommend.productHadClicked)==-1){
			message.recommend.productHadClicked.push(id);
			$.ajax({
				type: "get",
				dataType: 'json',
				data:{puid:message.recommend.puid},
				url: '/message/tracking/add-click-count?id='+id,
				success:function(data){
				}
			});
		}else
			return;
	},
};

message.preload = {
	allReadyGetRecommend : '',
	reloadUrl : window.location.href + "&ischeck=1",
	init : function(){
		var platform = $("#platform").val();
		var seller_id = $("#seller_id").val();
		var site_id = $("#site_id").val();
		var recom_group = $("#recom_prod_group").val();
		var recom_count = $("#recom_prod_count").val();
		// sleep(3000);
		// message.preload.checkIsReady();
		var times = 0;
		while(!message.preload.allReadyGetRecommend){
			if(times>1){
				//check times > 3, Stop waiting
				var ajaxbg = $('#background,#progressBar');
				ajaxbg.hide();
				break;
			}times++;
			var loop = true;
			$.ajax({
				type: "GET",
				dataType: 'json',
				url: '/message/tracking/check-is-ready',
				data: {puid:message.recommend.puid,platform:platform , seller_id:seller_id , site_id:site_id, recom_group:recom_group,recom_count:recom_count},
				async : false,
				success: function (result) {
					if(result.isReady){
						message.preload.allReadyGetRecommend = true;
						loop = false;
						//window.location.reload();
						window.location.href=message.preload.reloadUrl;
					}
					else{
						if(result.message!==''){
							alert(result.message);
							loop = false;
						}else{
							message.preload.allReadyGetRecommend = false;
						}
					}	
				},
				error :function () {
					loop = false;
				}
			});
			if(!loop) {
				var ajaxbg = $('#background,#progressBar');
				ajaxbg.hide();
				break;
			}
			sleep(3000);
		}
		
	},
	
	// checkIsReady : function(){
		// var platform = $("#platform").val();
		// var seller_id = $("#seller_id").val();
		// var site_id = $("#site_id").val();
		// $.ajax({
			// type: "GET",
			// dataType: 'json',
			// url: '/message/tracking/check-is-ready',
			// data: {platform:platform , seller_id:seller_id , site_id:site_id},
			// async : false,
			// success: function (result) {
				// if(result.isReady){
					// message.preload.allReadyGetRecommend = true;
					// window.location.reload();
				// }
				// else{
					// if(result.message!==''){
						// alert(result.message);
						// return false;
					// }else{
						// message.preload.allReadyGetRecommend = false;
					// }
				// }	
			// },
			// error :function () {
				// return false;
			// }
		// });
	// }
};



function sleep(numberMillis) { 
   var now = new Date();
   var exitTime = now.getTime() + numberMillis;  
   while (true) { 
       now = new Date(); 
       if (now.getTime() > exitTime)    return;
    }
}