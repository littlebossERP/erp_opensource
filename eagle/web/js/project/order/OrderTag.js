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
if (typeof OrderTag === 'undefined')  OrderTag = new Object();
OrderTag = {
	TagClassList:'',
	TagList:'',
	SelectTagData:'',
	isChange:false,
	init:function(){
		$('[id*=div_tag]').parents('.basic-qtip').remove();
		
		$('.btn_tag_qtip').each(function(){
			OrderTag.initQtipEntryBtn(this);
		});
		/**/
		$('.btn-qty-memo').each(function(){
			//var order_id = $(this).data('order-id');
			OrderTag.initMemoQtipEntryBtn(this);
		});
		
		$('.egicon-memo-orange').each(function() {
			OrderTag.initMemoLeftQtipEntryBtn(this);
		});
		
		$('.btn_qtip_from_nation,.btn_qtip_to_nation').each(function(){
			OrderTag.initNationQtipEntryBtn(this);
		});
		
//
//		$('table>thead>tr>th>.egicon-question-sign-blue').each(function(){
//			OrderTag.initOperationQtipEntryBtn(this);
//		});
		
	},
	
	initOperationQtipEntryBtn:function(obj){
		$(obj).qtip({
			content: {
				text: $('.qtip-operation-msg')
			},
			style: {
				classes: 'basic-qtip',
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
		var order_id = $(obj).data('order-id');
		btnObj.qtip({
			show: {
				event: 'click',
				solo: true,
			},
			hide: 'click',
			content: {
				button:true,
				text: $('#div_more_info_'+order_id)
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
	},
	
	initAllQtipEntryBtn:function(order_id){
		var newBtn = $('.btn_tag_qtip[data-order-id='+order_id+']');
		OrderTag.initQtipEntryBtn(newBtn);
		
		var newMemoBtn = $('.btn-qty-memo[data-order-id='+order_id+']');
		OrderTag.initMemoQtipEntryBtn(newMemoBtn);
		
		var newMemoBtnLeft = $('.egicon-memo-orange[data-order-id='+order_id+']');
		OrderTag.initMemoLeftQtipEntryBtn(newMemoBtnLeft);
		
		$('tr[data-order-id='+order_id+']').find('.btn_qtip_from_nation,.btn_qtip_to_nation').each(function(){
			OrderTag.initNationQtipEntryBtn(this);
		});
				
	},
	
	initQtipEntryBtn:function(obj){
		var btnObj = $(obj);
		var order_id = $(obj).data('order-id');
		btnObj.qtip({
			show: {
				event: 'click',
				solo: true
			},
			hide: 'click',
			content:{
				button: true,
				text:$('#div_tag_'+order_id) ,
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
						url: '/order/order/get-one-tag-info?order_id='+order_id, // Use href attribute as URL
						success:function(content){
							var html = OrderTag.fillTagContentHtml(order_id,content);
							$('#div_tag_'+order_id).html(html);
							OrderTag.initQtipBtn(order_id);
							
						}
					});
				},
				hide:function(){
					if (OrderTag.isChange){
						OrderTag.updateTrackTrInfo(order_id , function(){
							OrderTag.initAllQtipEntryBtn(order_id);
							TrObj = $('tr[data-order-id='+order_id+']')
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
						OrderTag.isChange = false;
					}
				}
			}
		});
	},
	
	initQtipBtn:function(order_id ){
		$('input[name=tag_order_id]').val(order_id);
						
		$('.span-click-btn').click(function(){
			inputObj = $(this).parent().find('input[name=select_tag_name]');
			
			obj = $(this).children('span');
			if(obj.hasClass('glyphicon-edit')){
				inputObj.prop('readonly','');
				obj.removeClass('glyphicon-edit');
				obj.addClass('glyphicon-saved');
				
				$(this).prev().css("display", 'none');
			}else if (obj.hasClass('glyphicon-saved')){
				OrderTag.editTag(obj);
			}else if (obj.hasClass('glyphicon-plus')){
				if (inputObj.val().length== 0){
					bootbox.alert(Translator.t('请输入标签'));
					return ;
				}
				
				obj.removeClass('glyphicon-plus');
				obj.addClass('glyphicon-remove');
				inputObj.prop('readonly','readonly');
				OrderTag.addTag(obj);
			}else{
				obj.removeClass('glyphicon-remove');
				obj.addClass('glyphicon-plus');
				OrderTag.delTag(obj);
			}
		});
	},
	
	fillTagContentHtml:function(order_id , data){
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
			select_html += OrderTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
			
			existColor[color] = 1;
			
		});
		
		$.each(data.all_tag, function(){
			if (existColor[this.color]!='1' && this.color != 'gray'){
				ColorClassName = this.classname; 
				TagName = this.tag_name;
				BtnClassName="glyphicon-plus"; 
				ReadonlyStr = '  readonly="readonly" ';
				color = this.color;
				rest_html += OrderTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
				
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
				rest_html += OrderTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr,color);
							
			}
				
		Html =  '<div name="div_select_tag" class="div_select_tag">'+
				'<input name="tag_order_id" type="hidden" readonly="readonly" value="'+order_id+'"/>'+
				select_html+
				'</div>'+
				'<div name="div_new_tag" class="div_new_tag">'+
				rest_html+
				'</div>';
		return Html;
	},
	
	fillNewTagContentHtml:function(order_id){
		var Html = '';
		
		var select_html = "";
		var rest_html = "";
		var tag_mapping = new Object();
		var existColor = new Object();
				
		$.each(this.TagList , function(){
			ColorClassName = OrderTag.TagClassList[this.color] ; 
			TagName =this.tag_name;
			BtnClassName="glyphicon-plus"; 
			ReadonlyStr = '  readonly="readonly" ';
			color = this.color;
			rest_html += OrderTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color);
			
			existColor[this.color] = 1;
		});
		
		
		for( var color in this.TagClassList){
			if (color == 'gray') continue;
			if (existColor[color]=='1') continue;
			
			ColorClassName = this.TagClassList[color] ; 
			TagName ="";
			BtnClassName="glyphicon-plus"; 
			ReadonlyStr = ' ';
			rest_html += OrderTag.generateHtml(ColorClassName , TagName ,BtnClassName, ReadonlyStr,color);
			
		}
		
		Html =  '<div name="div_select_tag" class="div_select_tag">'+
				'<input name="tag_order_id" type="hidden" readonly="readonly" value="'+order_id+'"/>'+
				select_html+
				'</div>'+
				'<div name="div_new_tag" class="div_new_tag">'+
				rest_html+
				'</div>';
		return Html;
	},
	
	
	generateHtml:function(ColorClassName , TagName ,BtnClassName, ReadonlyStr , color){
		var str = '';
		if(TagName == '' || TagName == undefined){
			str = 'display: none';
		}
		return '<div class="input-group">'+
				'<span class="input-group-addon"><span class="'+ColorClassName+'"></span></span>'+
				'<input name="select_tag_name" type="text" class="form-control" placeholder="" aria-describedby="basic-addon1" value="'+TagName+'" '+ReadonlyStr+' data-color="'+color+'">'+
				'<span class="input-group-addon span-click-btn"><span class="glyphicon '+BtnClassName+'" aria-hidden="true"></span></span>'+
				'<span class="input-group-addon span-click-btn" style="'+ str +'"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></span>'+
			'</div>';
	},
	
	addTag:function(obj){
		thisobj = $(obj);
		$('div[name=div_select_tag]').append(thisobj.parents('.input-group'));
		
		tracking_id  = $('div[name=div_select_tag] input[name=tag_order_id]').val(); 
		tag_name = thisobj.parent().prev('input[name=select_tag_name]').val(); 
		operation = 'add' ; 
		color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
		this.saveTag(tracking_id , tag_name , operation , color);
		
		thisobj.parent().next().css("display", '');
	},
	
	delTag:function(obj){
		thisobj = $(obj);
		
		$('div[name=div_new_tag]').append(thisobj.parents('.input-group'));
		tracking_id  = $('div[name=div_select_tag] input[name=tag_order_id]').val(); 
		tag_name = thisobj.parent().prev('input[name=select_tag_name]').val(); 
		operation = 'del' ; 
		color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
		this.saveTag(tracking_id , tag_name , operation , color);
	},
	
	editTag:function(obj){
		thisobj = $(obj);
		
		tracking_id  = $('div[name=div_select_tag] input[name=tag_order_id]').val(); 
		tag_name = thisobj.parent().parent().find('input[name=select_tag_name]').val().trim(); 
		operation = 'edit' ; 
		color = thisobj.parent().parent().find('input[name=select_tag_name]').data('color');
		
		if(tag_name == '' ){
			bootbox.confirm(
				Translator.t("<span style='color:red'>标签内容为空时，会删除此标签对应的所有订单关系，是否继续删除？</span>"),
				function(r){
					if(r){
						OrderTag.saveTag(tracking_id , tag_name , operation , color);

						$('div[name=div_new_tag]').append(thisobj.parents('.input-group'));
						
						thisobj.parent().css("display", 'none');
						
						thisobj.parent().prev().css("display", '');
						thisobj.parent().prev().children('span').removeClass('glyphicon-remove');
						thisobj.parent().prev().children('span').addClass('glyphicon-plus');
					}
				}
			);
		}
		else{
			OrderTag.saveTag(tracking_id , tag_name , operation , color);

			thisobj.parent().parent().find('input[name=select_tag_name]').prop('readonly','readonly');
			thisobj.removeClass('glyphicon-saved');
			thisobj.addClass('glyphicon-edit');
			
			thisobj.parent().prev().css("display", '');
		}
	},
	
	saveTag:function(order_id , tag_name , operation , color){
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/order/order/save-one-tag', 
			data: {order_id : order_id  , tag_name :  tag_name , operation :  operation , color : color},
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
		OrderTag.isChange = true;
	},

	updateTrackTrInfo:function(order_id , callback){
		$.ajax({
			type: "GET",
			dataType: 'json',
			url: '/order/order/update-order-tr-info?order_id='+order_id, // Use href attribute as URL
			success:function(data){
				if (data.sphtml){
					$('span[data-order-id='+order_id+']').html(data.sphtml);
				}
				callback();
			}
		});
	},
}