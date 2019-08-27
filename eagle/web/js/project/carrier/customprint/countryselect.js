/**
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		hqw
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof countryselect === 'undefined')  countryselect = new Object();
countryselect = {
		
	/**********   nation start   ********/
	NationList:{},
	NationMapping:{},
	roleNationList:'',
	currentNation:{},
	selectAllNation:function(isSelect){
		$('input[name^=chk-nation]:visible').prop('checked',isSelect);
		$('input[name^=chk-nation]').each(function(){
			countryselect.setCurrentNation(this);
		});
		
	},
	selectGroupNation:function(obj,isSelect){
		$(obj).parent().find('input[type=checkbox]:visible').prop('checked',isSelect);
		$('input[name^=chk-nation]').each(function(){
			countryselect.setCurrentNation(this);
		});
		
	},
	setCurrentNation:function(obj){
		var code = $(obj).data('code');
		countryselect.currentNation[code]= $(obj).prop('checked');
	},
	
	setNationHtml:function(id){
		var html = '<input class="btn btn-success btn-xs" type="button" value="全部全选" onclick="countryselect.selectAllNation(true);">'
			+'<input class="btn btn-danger btn-xs" type="button" value="全部取消" onclick="countryselect.selectAllNation(false)">'
			+'<button type="button" class="btn btn-warning btn-xs display_all_country_toggle">全部展开/折叠</button>';
		
		$.each(countryselect.NationList,function(index,values){
			html += '<div><hr>';
			html += '<input class="btn btn-success btn-xs" type="button" value="'+values.name+Translator.t('全选')+'" onclick="countryselect.selectGroupNation(this,true);">';
			html += '<input class="btn btn-danger btn-xs" type="button" value="'+Translator.t('取消')+'" onclick="countryselect.selectGroupNation(this,false)">';
			html += '<button type="button" class="btn btn-warning btn-xs display_toggle">'+Translator.t('展开/折叠')+'</button>';
			html +='<div  class="region_country">'
			for(var code in values.value){
				if ( countryselect.currentNation[code] )
					var ischecked = 'checked="checked"';
				else
					var ischecked = '';
				
				html += '<label ><input name="chk-nation" class="role-checkbox" type="checkbox" '+ischecked+' data-code="'+
				code+'"  onclick="countryselect.setCurrentNation(this)"/><span class="qtip-checkbox-label">'+values.value[code]+'</span></label>';
			}
			html += '</div><div class="clearfix"></div></div>';
			
		});
		return '<div id="div_nation_list_background">'+html+'</div>';
	},
	
	initNationBox:function(obj){
		var thisObj = $(obj);
		var roleId  = thisObj.data('role-id');
		var contentHtml = countryselect.setNationHtml(roleId);
		thisObj.qtip({
			show: {
				event: 'click',
				solo: true,
			},
			hide: 'click',
			content: {
				button:true,
				text: contentHtml,
			},
			style: {
				classes: 'basic-qtip z-index-top nopadding',
				width: 750,
			},
			position: {
				my: 'center left',
				at: 'center right',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},
			events:{
				show:function(){
					
					$(".display_toggle").unbind().click(function(){
						obj=$(this).next();
						var hidden = obj.css('display');
						if(typeof(hidden)=='undefined' || hidden=='none'){
							obj.css('display','block');
						}else if( hidden=='block'){
							obj.css('display','none');
						}
						
					});
					
					$(".display_all_country_toggle").unbind().click(function(){
						$('.region_country').each(function(){
							obj=$(this);
							var hidden = obj.css('display');
							if(typeof(hidden)=='undefined' || hidden=='none'){
								obj.css('display','block');
							}else if( hidden=='block'){
								obj.css('display','none');
							}
							
						})
					});
				},
				hide:function(){
					if (roleId != "" && roleId != undefined && roleId != null){
						var roleselect = '[data-role-id='+roleId+']';
					}else{
						roleselect = '[data-role-id="0"]';
					}
					var thisText = "";
					$.each(countryselect.currentNation,function(i,v){
						if (v==true){
							
							
							if (thisText != "") thisText += ",";
							
							if (countryselect.NationMapping[i] != undefined)
								thisText += countryselect.NationMapping[i]; 
							else
								thisText += i;
						}
						
					});
					//$('div[name=div-select-nation]'+roleselect).text(thisText);
					$('[name=div-select-nation]'+roleselect+'>input').val(thisText);
				}
			}
		});
	},
	/**********   nation end   ********/
	
	
}
