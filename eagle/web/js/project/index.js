$("#sidebar .toggleMenuL").click(function(){
	/*
	if ($(this).parent().children('ul').is(":hidden")==false){
		$(this).parent().children('ul').hide();
		
		if($(this).hasClass('glyphicon-menu-down'))
			$(this).removeClass('glyphicon-menu-down');
		$(this).addClass('glyphicon-menu-up');
	}else{
		$(this).parent().children('ul').show();
		
		if($(this).hasClass('glyphicon-menu-up'))
			$(this).removeClass('glyphicon-menu-up');
		$(this).addClass('glyphicon-menu-down');
	}
	*/
	
	if ($(this).parent().next('.sidebar-shrink-li').children('ul').is(":hidden")==false){
		$(this).parent().next('.sidebar-shrink-li').children('ul').hide();
		
		if($(this).hasClass('glyphicon-menu-down'))
			$(this).removeClass('glyphicon-menu-down');
		$(this).addClass('glyphicon-menu-up');
		
	}else{
		$(this).parent().next('.sidebar-shrink-li').children('ul').show();
		
		if($(this).hasClass('glyphicon-menu-up'))
			$(this).removeClass('glyphicon-menu-up');
		$(this).addClass('glyphicon-menu-down');
	}
	
});

$("#sidebar .toggleMenuOutR").click(function(){
	if ($(this).parent().parent().next('ul').is(":hidden")==false){
		$(this).parent().parent().next('ul').hide();
		
		if($(this).hasClass('glyphicon-menu-down'))
			$(this).removeClass('glyphicon-menu-down');
		$(this).addClass('glyphicon-menu-up');
	}else{
		$(this).parent().parent().next('ul').show();
		
		if($(this).hasClass('glyphicon-menu-up'))
			$(this).removeClass('glyphicon-menu-up');
		$(this).addClass('glyphicon-menu-down');
	}
});

$(document).ready(function(){
	$('.guide-bar-shadow-2').click(function(){
		window.location.href = "/platform/platform/all-platform-account-binding";
	});
	
});
