/**
 +------------------------------------------------------------------------------
 * tracker tag 专用js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof TrackingTag === 'undefined')  TrackingTag = new Object();
TrackingTag = {
	TagClassList:'',
	TagList:'',
	SelectTagData:'',
	isChange:false,
	init:function(){
		$('.btn_tag_qtip').each(function(){
			TrackingTag.initQtipEntryBtn(this);
		});
		/**/
		$('.btn-qty-memo').each(function(){
			//var track_id = $(this).data('track-id');
			TrackingTag.initMemoQtipEntryBtn(this);
		});
		
		$('.egicon-memo-orange').each(function() {
			TrackingTag.initMemoLeftQtipEntryBtn(this);
		});
		
		$('.btn_qtip_from_nation,.btn_qtip_to_nation').each(function(){
			TrackingTag.initNationQtipEntryBtn(this);
		});
		

		$('table>thead>tr>th>.egicon-question-sign-blue').each(function(){
			TrackingTag.initOperationQtipEntryBtn(this);
		});
		
	},
	
	initOperationQtipEntryBtn:function(obj){
		$(obj).qtip({
			content: {
				text: $('.qtip-operation-msg')
			},
			style: {
				classes: 'basic-qtip z-index-top',
				width: 140,
			},
			position: {
				my: 'top right',
				at: 'bottom right',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},
		});
	},
	
	initNationQtipEntryBtn:function(obj){
		$(obj).qtip({
			content: {
				text: $(obj).next('.div_space_toggle')
			},
			style: {
				classes: 'basic-qtip',
			},
		});
	},
	
	initMemoLeftQtipEntryBtn:function(obj){
		$(obj).qtip({
			content: {
				text: $(obj).next('.div_space_toggle')
			},
			style: {
				classes: 'basic-qtip',
				width:600
			},
		});
	},
	
	initMemoQtipEntryBtn:function(obj){
		var btnObj = $(obj);
		var track_id = $(obj).data('track-id');
		var track_no = $(obj).parents('tr').attr('track_no');
		btnObj.qtip({
			show: {
				event: 'click',
				solo: true,
			},
			hide: 'click',
			content: {
			    button:true,
			    text: function(event, api) {
				    $.ajax({
				 	   //url:'/tracking/tracking/show17-track-tracking-info?num='+track_no,
					   url: "/message/all-customer/detail-location?track_no="+track_no // Use href attribute as URL
				    })
				    .then(function(content) {
				 	   // Set the tooltip content upon successful retrieval
				 	   api.set('content.text', content);
				    }, function(xhr, status, error) {
					   // Upon failure... set the tooltip content to error
				 	   api.set('content.text', status + ': ' + error);
				    });
	   
				    return 'Loading...'; // Set some initial text
			   }
		    },
			/*
			content: {
				button:true,
				text: $('#div_more_info_'+track_id)
			},
			*/
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
			events:{
				show:function(){
					TrackingTag.viewDetailTrackingEvent(track_no);
				}
				
			}
		});
		btnObj.prop('title','详情');
	},
	
	initAllQtipEntryBtn:function(track_id){
		var newBtn = $('.btn_tag_qtip[data-track-id='+track_id+']');
		TrackingTag.initQtipEntryBtn(newBtn);
		
		var newMemoBtn = $('.btn-qty-memo[data-track-id='+track_id+']');
		TrackingTag.initMemoQtipEntryBtn(newMemoBtn);
		
		var newMemoBtnLeft = $('.egicon-memo-orange[data-track-id='+track_id+']');
		TrackingTag.initMemoLeftQtipEntryBtn(newMemoBtnLeft);
		
		$('tr[data-track-id='+track_id+']').find('.btn_qtip_from_nation,.btn_qtip_to_nation').each(function(){
			TrackingTag.initNationQtipEntryBtn(this);
		});
				
	},
	
	initQtipEntryBtn:function(obj){
		var btnObj = $(obj);
		var track_id = $(obj).data('track-id');
		btnObj.qtip({
			show: {
				event: 'click',
				solo: true
			},
			hide: 'click',
			content:{
				button: true,
				text:$('#div_tag_'+track_id) ,
			},
			position: {
				my: 'top left',
				at: 'bottom left',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin 
				},
			},
			
			style: {
				classes :'basic-qtip' , 
				
			},
			events:{
				show:function(){
					$.ajax({
						type: "GET",
						dataType: 'json',
						url: '/tracking/tracking/get-one-tag-info?track_id='+track_id, // Use href attribute as URL
						success:function(content){
							var html = TrackingTag.fillTagContentHtml(track_id,content);
							$('#div_tag_'+track_id).html(html);
							TrackingTag.initQtipBtn(track_id);
							
						}
					});
				},
				hide:function(){
					if (TrackingTag.isChange){
						TrackingTag.updateTrackTrInfo(track_id , function(){
							TrackingTag.initAllQtipEntryBtn(track_id);
							TrObj = $('tr[data-track-id='+track_id+']')
							TrObj.unbind();
							
							TrObj.has('.egicon-flag-gray').mouseover(function(){
								if ($(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
								$(this).find('.btn_tag_qtip.div_space_toggle').removeClass('div_space_toggle');
							});
							
							TrObj.has('.egicon-flag-gray').mouseleave(function(){
								if (! $(this).find('.btn_tag_qtip').hasClass('div_space_toggle'))
									$(this).find('.btn_tag_qtip').addClass('div_space_toggle');
							});
						});
						TrackingTag.isChange = false;
					}
				}
			}
		});
	},
	
	initQtipBtn:function(track_id ){
		$('input[name=tag_track_id]').val(track_id);
						
		$('.span-click-btn').click(function(){
			inputObj = $(this).prev('input[name=select_tag_name]');
			if (inputObj.val().length== 0){
				bootbox.alert(Translator.t('请输入标签'));
				
				return ;
			}
			obj = $(this).children('span');
			isAdd = obj.hasClass('glyphicon-plus');
			if (isAdd){
				obj.removeClass('glyphicon-plus');
				obj.addClass('glyphicon-remove');
				inputObj.prop('readonly','readonly');
				TrackingTag.addTag(obj);
			}else{
				obj.removeClass('glyphicon-remove');
				obj.addClass('glyphicon-plus');
				TrackingTag.delTag(obj);
			}
		});
	},
	
	fillTagContentHtml:function(track_id , data){
		var Html = '';
		
		var select_html = "";
		var rest_html = "";
		var tag_mapping = new Object();
		var existColor = new Object();
		
		$.each(data.all_tag , function(){
			tag_mapping[this.tag_id] = this;
		});
		
		$.each(data.all_select_tag_id, function(i,value){
			
			ColorClassName = tag_mapping[value].classname ; 
			TagName =tag_mapping[value].tag_name;
			BtnClassName="glyphicon-remove"; 
			ReadonlyStr = '  readonly="readonly" ';
			color = tag_mapping[value].color;
			select_html += TrackingTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
			
			existColor[color] = 1;
			
		});
		
		$.each(data.all_tag, function(){
			if (existColor[this.color]!='1' && this.color != 'gray'){
				ColorClassName = this.classname; 
				TagName = this.tag_name;
				BtnClassName="glyphicon-plus"; 
				ReadonlyStr = '  readonly="readonly" ';
				color = this.color;
				rest_html += TrackingTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
				
				existColor[color] = 1;
			} 
		});
		
		for( var color in this.TagClassList){
				if (color == 'gray') continue;
				if (existColor[color]=='1') continue;
				
				ColorClassName = this.TagClassList[color] ; 
				TagName ="";
				BtnClassName="glyphicon-plus"; 
				ReadonlyStr = ' ';
				rest_html += TrackingTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr,color);
							
			}
				
		Html =  '<div name="div_select_tag" class="div_select_tag">'+
				'<input name="tag_track_id" type="hidden" readonly="readonly" value="'+track_id+'"/>'+
				select_html+
				'</div>'+
				'<div name="div_new_tag" class="div_new_tag">'+
				rest_html+
				'</div>';
		return Html;
	},
	
	fillNewTagContentHtml:function(track_id){
		var Html = '';
		
		var select_html = "";
		var rest_html = "";
		var tag_mapping = new Object();
		var existColor = new Object();
				
		$.each(this.TagList , function(){
			ColorClassName = TrackingTag.TagClassList[this.color] ; 
			TagName =this.tag_name;
			BtnClassName="glyphicon-plus"; 
			ReadonlyStr = '  readonly="readonly" ';
			color = this.color;
			rest_html += TrackingTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
			
			existColor[this.color] = 1;
		});
		
		
		for( var color in this.TagClassList){
			if (color == 'gray') continue;
			if (existColor[color]=='1') continue;
			
			ColorClassName = this.TagClassList[color] ; 
			TagName ="";
			BtnClassName="glyphicon-plus"; 
			ReadonlyStr = ' ';
			rest_html += TrackingTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr,color);
			
		}
		
		Html =  '<div name="div_select_tag" class="div_select_tag">'+
				'<input name="tag_track_id" type="hidden" readonly="readonly" value="'+track_id+'"/>'+
				select_html+
				'</div>'+
				'<div name="div_new_tag" class="div_new_tag">'+
				rest_html+
				'</div>';
		return Html;
	},
	
	
	generateHtml:function(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color){
		
		return '<div class="input-group">'+
				'<span class="input-group-addon"><span class="'+ColorClassName+'"></span></span>'+
				'<input name="select_tag_name" type="text" class="form-control" placeholder="" aria-describedby="basic-addon1" value="'+TagName+'" '+ReadonlyStr+' data-color="'+color+'">'+
				'<span class="input-group-addon span-click-btn"><span class="glyphicon '+BtnClassName+'" aria-hidden="true"></span></span>'+
				'</div>';
	},
	
	addTag:function(obj){
		thisobj = $(obj);
		$('div[name=div_select_tag]').append(thisobj.parents('.input-group'));
		
		tracking_id  = $('div[name=div_select_tag] input[name=tag_track_id]').val(); 
		tag_name = thisobj.parent().prev('input[name=select_tag_name]').val(); 
		operation = 'add' ; 
		color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
		this.saveTag(tracking_id , tag_name , operation , color);
	},
	
	delTag:function(obj){
		thisobj = $(obj);
		
		$('div[name=div_new_tag]').append(thisobj.parents('.input-group'));
		tracking_id  = $('div[name=div_select_tag] input[name=tag_track_id]').val(); 
		tag_name = thisobj.parent().prev('input[name=select_tag_name]').val(); 
		operation = 'del' ; 
		color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
		this.saveTag(tracking_id , tag_name , operation , color);
	},
	
	saveTag:function(tracking_id , tag_name , operation , color){
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/save-one-tag', 
			data: {tracking_id : tracking_id  , tag_name :  tag_name , operation :  operation , color : color},
			success: function (result) {
				if (result.success == false){
					bootbox.alert(result.message);
				}
				return true;
			},
			error :function () {
				return false;
			}
		});
		TrackingTag.isChange = true;
	},
	
	updateTrackTrInfo:function(track_id , callback){
		$.ajax({
			type: "GET",
			dataType: 'json',
			url: '/tracking/tracking/update-track-tr-info?track_id='+track_id, // Use href attribute as URL
			success:function(data){
				if (data.TrHtml){
					for(var tmp_track_no in data.TrHtml ){
//						$('#tr_info_'+tmp_track_no).html(data.TrHtml[tmp_track_no]);
						$('tr[data-track-id="'+track_id+'"]').html(data.TrHtml[tmp_track_no]);
					}
				}
				
				callback();
			}
		});
	},
	
	viewDetailTrackingEvent:function(track_no){
		$.ajax({
			type: "GET",
				dataType: 'json',
				url:'/tracking/tracking/view-detail-tracking-event', 
				data: {track_no : track_no },
				success: function (result) {
					return true;
				},
				error: function(){
					return false;
				}
		});
	},
	
}