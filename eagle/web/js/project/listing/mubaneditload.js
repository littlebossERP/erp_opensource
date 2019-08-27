//滚动监听快捷
$(window).scroll(function(event){
	//获取每个监听节点的高度
	var winPos = $(window).scrollTop();
	var siteandspe = $('#siteandspe').offset().top-20;
	var titleandprice = $('#titleandprice').offset().top-20;
	var picanddesc = $('#picanddesc').offset().top-20;
	var shippingset = $('#shippingset').offset().top-20;
	var returnpolicy = $('#returnpolicy').offset().top-20;
	var buyerrequire = $('#buyerrequire').offset().top-20;
	var plusmodule = $('#plusmodule').offset().top-20;
	var account = $('#account').offset().top-20;
	
	if(winPos < siteandspe){
		showscrollcss('account');
	}else if(winPos > siteandspe && winPos < titleandprice){
		showscrollcss('siteandspe');
	}else if(winPos > titleandprice && winPos < picanddesc){
		showscrollcss('titleandprice');
	}else if(winPos > picanddesc && winPos < shippingset){
		showscrollcss('picanddesc');
	}else if(winPos > shippingset && winPos < returnpolicy){
		showscrollcss('shippingset');
	}else if(winPos > returnpolicy && winPos < buyerrequire){
		showscrollcss('returnpolicy');
	}else if(winPos > buyerrequire && winPos < plusmodule){
		showscrollcss('buyerrequire');
	}else if(winPos > plusmodule){
		showscrollcss('plusmodule');
	}
	
});