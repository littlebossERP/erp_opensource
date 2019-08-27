/**
 +------------------------------------------------------------------------------
 *全部查询界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof StationLetter === 'undefined')  StationLetter = new Object();
StationLetter = {
	templateList :[],
	templateAddiInfoList:'',
	previewDatalist : '',
	defaultTemplate : '' ,
	originTemplate:'',
	platformAccount:'',
	rolePlatformList:'',
	roleAccoutList:'',
	roleNationList:'',
	currentPlatformAccount :{},
	currentPlatformAccountMapping :{},
	currentNation:{},
	currentStatus:{},
	currentLayoutId:'1',
	currentRecommendProductCount:'8',
	currentRecommendProductGroup:'0',
	StatusList:{},
	NationList:{},
	NationMapping:{},
	roleTableList:{},
	PlatformLabel:{},
	MatchResult:{},
	isRefreshMatching:false,
	recomGroups:{},
	init:function(){
		$.initQtip();
		$('select[name=letter_template_used]').change(function(){
			StationLetter.fillTemplateData();
			StationLetter.setNewTemplateName();
		});
		
		$('#letter_template').select(function(){
			StationLetter.markPos(this);
		});
		
		$('#letter_template').click(function(){
			StationLetter.markPos(this);
		});
		
		$('#letter_template').keyup(function(){
			StationLetter.markPos(this);
		});
		
		$('#send-station-letter').on('click' , '#btn_set_roles' , function(){
			//StationLetter.fillTemplateData();
			StationLetter.showRoleSettingBox();
		});
		
		$('#path').val(this.defaultTemplate.path);
		$('select[name=letter_template_used]').val(this.defaultTemplate.template_id);
		
		$('div[name=div_auto_template] table').on('change' , 'select[name=unmatch_template]' , function(){
			//StationLetter.fillTemplateData();
			if (this.value == -1){
				$('select[name=unmatch_template]').parents('tr').find('td[data-recom-prod]').text(Translator.t('否'));
				return;
			}
			if (StationLetter.templateAddiInfoList[this.value]['recom_prod']!= undefined &&  StationLetter.templateAddiInfoList[this.value]['recom_prod']=='Y')
				$(this).parents('tr').find('td[data-recom-prod]').text(Translator.t('是'));
			else
				$(this).parents('tr').find('td[data-recom-prod]').text(Translator.t('否'));
		});
		this.setNewTemplateName();
		this.fillTemplateData();
		
		$('#select_recom_group select[name=recom_prod_group]').change(function(){
			if (StationLetter.recomGroups[this.value] ==undefined ){
				$("#select_recom_group input#template_platform").val('');
				$("#select_recom_group input#template_sellerid").val('');
			}else{
				if (StationLetter.recomGroups[this.value]['platform']!= undefined )
					$("#select_recom_group input#template_platform").val(StationLetter.recomGroups[this.value]['platform']);
				
				if (StationLetter.recomGroups[this.value]['seller_id']!= undefined )
					$("#select_recom_group input#template_sellerid").val(StationLetter.recomGroups[this.value]['seller_id']);
			}
		});
		
	},
	
	
	
	/**********   status start   ********/
	setCurrentStatus:function(obj){
		var code = $(obj).data('code');
		StationLetter.currentStatus[code]= $(obj).prop('checked');
	},
	setStatusHtml:function(id){
		var html = "";
		
		$.each(StationLetter.StatusList,function(code,label){
			
			if (code == StationLetter.StatusList[id] )
				var ischecked = 'checked="checked"';
			else
				var ischecked = '';
			html += '<div  class="col-sm-4"><input name="chk-status" class="role-checkbox" type="checkbox" '+ischecked+' data-code="'+
			code+'"  onclick="StationLetter.setCurrentStatus(this)"/><span  class="qtip-checkbox-label">'+label+'</span></div>'
		});
		return '<div>'+html+'</div>';
	},
	initStatusBox:function(obj){
		var thisObj = $(obj);
		var roleId  = thisObj.data('role-id');
		var contentHtml = StationLetter.setStatusHtml(roleId);
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
				classes: 'basic-qtip z-index-top',
				width: 450,
			},
			position: {
				my: 'top left',
				at: 'bottom left',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},
			events:{
				show:function(){
					//StationLetter.currentPlatformAccountMapping = {};
					//StationLetter.currentPlatformAccount = {};
				},
				hide:function(){
					if (roleId != "" && roleId != undefined && roleId != null){
						var roleselect = '[data-role-id='+roleId+']';
					}else{
						roleselect = '[data-role-id="0"]';
					}
					var thisText = "";
					$.each(StationLetter.currentStatus,function(i,v){
						if (v==true){
							if (thisText != "") thisText += ",";
							thisText += StationLetter.StatusList[i]; 
						}
						
					});
					if (thisText == '') thisText = Translator.t('请选择物流状态');
					
					$('div[name=div-ship-status]'+roleselect).text(thisText);
				}
			}
		});
	},
	/**********   status end   ********/
	/**********   nation start   ********/
	selectAllNation:function(isSelect){
		$('input[name^=chk-nation]:visible').prop('checked',isSelect);
		$('input[name^=chk-nation]').each(function(){
			StationLetter.setCurrentNation(this);
		});
		
	},
	selectGroupNation:function(obj,isSelect){
		$(obj).parent().find('input[type=checkbox]:visible').prop('checked',isSelect);
		$('input[name^=chk-nation]').each(function(){
			StationLetter.setCurrentNation(this);
		});
		
	},
	setCurrentNation:function(obj){
		var code = $(obj).data('code');
		StationLetter.currentNation[code]= $(obj).prop('checked');
	},
	
	setNationHtml:function(id){
		var html = '<input class="btn btn-success btn-xs" type="button" value="全部全选" onclick="StationLetter.selectAllNation(true);">'
			+'<input class="btn btn-danger btn-xs" type="button" value="全部取消" onclick="StationLetter.selectAllNation(false)">'
			+'<button type="button" class="btn btn-warning btn-xs display_all_country_toggle">全部展开/折叠</button>';
		var lastPlatform = "";
		
		$.each(StationLetter.NationList,function(index,values){
			html += '<div><hr>';
			html += '<input class="btn btn-success btn-xs" type="button" value="'+values.name+Translator.t('全选')+'" onclick="StationLetter.selectGroupNation(this,true);">';
			html += '<input class="btn btn-danger btn-xs" type="button" value="'+Translator.t('取消')+'" onclick="StationLetter.selectGroupNation(this,false)">';
			html += '<button type="button" class="btn btn-warning btn-xs display_toggle">'+Translator.t('展开/折叠')+'</button>';
			html +='<div  class="region_country">'
			for(var code in values.value){
				if (code == StationLetter.roleNationList[id] )
					var ischecked = 'checked="checked"';
				else
					var ischecked = '';
				
				html += '<label ><input name="chk-nation" class="role-checkbox" type="checkbox" '+ischecked+' data-code="'+
				code+'"  onclick="StationLetter.setCurrentNation(this)"/><span class="qtip-checkbox-label">'+values.value[code]+'</span></label>';
			}
			html += '</div><div class="clearfix"></div></div>';
			
		});
		return '<div id="div_nation_list_background">'+html+'</div>';
		/*
		var html = "";
		var lastPlatform = "";
		$.each(StationLetter.NationList,function(code,label){
			
			if (code == StationLetter.roleNationList[id] )
				var ischecked = 'checked="checked"';
			else
				var ischecked = '';
			html += '<div  class="col-sm-4"><input name="chk-nation" class="role-checkbox" type="checkbox" '+ischecked+' data-code="'+
			code+'"  onclick="StationLetter.setCurrentNation(this)"/><span class="qtip-checkbox-label">'+label+'</span></div>'
		});
		return '<div>'+html+'</div>';
		*/
		
	},
	
	initNationBox:function(obj){
		var thisObj = $(obj);
		var roleId  = thisObj.data('role-id');
		var contentHtml = StationLetter.setNationHtml(roleId);
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
				my: 'bottom left',
				at: 'bottom left',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},
			events:{
				show:function(){
					//StationLetter.currentPlatformAccountMapping = {};
					//StationLetter.currentPlatformAccount = {};
					
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
					$.each(StationLetter.currentNation,function(i,v){
						if (v==true){
							
							
							if (thisText != "") thisText += ",";
							thisText += StationLetter.NationMapping[i]; 
						}
						
					});
					if (thisText == '') thisText = Translator.t('请选择国家');
					
					$('div[name=div-select-nation]'+roleselect).text(thisText);
				}
			}
		});
	},
	/**********   nation end   ********/
	
	/**********   platform & account  start    ********/
	setCurrentPlatformAccount:function(obj){
		var platform = $(obj).data('platform');
		var accountid = $(obj).data('account-id');
		StationLetter.currentPlatformAccount[platform+':'+accountid]= $(obj).prop('checked');
		StationLetter.currentPlatformAccountMapping[platform+':'+accountid] = $(obj).next('span').text()
	},
	
	setPlatformHtml:function(id){
		var html = "";
		var lastPlatform = "";
		$.each(StationLetter.platformAccount,function(){
			if (lastPlatform != this.platform){
				if (html != "") html +='<div class="clearfix"></div>';
				if (StationLetter.PlatformLabel[this.platform] != undefined)
					html += '<h5>'+StationLetter.PlatformLabel[this.platform]+'</h5>';
				else
					html += '<h5>'+this.platform+'</h5>';
				lastPlatform = this.platform;
				
			}
			if (this.platform == StationLetter.rolePlatformList[id] 
			&& this.name == StationLetter.roleAccoutList )
				var ischecked = 'checked="checked"';
			else
				var ischecked = '';
			html += '<div class="col-sm-12"><input name="chk-platform" class="role-checkbox" type="checkbox" '+ischecked+' data-platform="'+
			this.platform+'" data-account-id="'+this.id+'" onclick="StationLetter.setCurrentPlatformAccount(this)"/><span class="qtip-checkbox-label">'+this.name+'</span></div>'
			
				
		});
		return '<div>'+html+'</div>';
	},
	
	initAccountBox:function(obj){
		var thisObj = $(obj);
		var roleId  = thisObj.data('role-id');
		var contentHtml = StationLetter.setPlatformHtml(roleId);
		
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
				classes: 'basic-qtip z-index-top',
				width: 450,
			},
			position: {
				my: 'top left',
				at: 'bottom left',
				viewport: $("#page-content"),
				adjust: {
					method: 'shift flip' // Requires Viewport plugin
				},
			},
			events:{
				show:function(){
					//StationLetter.currentPlatformAccountMapping = {};
					//StationLetter.currentPlatformAccount = {};
				},
				hide:function(){
					if (roleId != "" && roleId != undefined && roleId != null){
						var roleselect = '[data-role-id='+roleId+']';
					}else{
						roleselect = '[data-role-id="0"]';
					}
					var thisText = "";
					$.each(StationLetter.currentPlatformAccount,function(i,v){
						if (v==true){
							endindex = i.indexOf(':');
							var thisPlatform = i.substring(0,endindex);
							if (thisText != "") thisText += ",";
							if (StationLetter.PlatformLabel[thisPlatform] != undefined)
								thisPlatform = StationLetter.PlatformLabel[thisPlatform];
								
							thisText += thisPlatform+':'+StationLetter.currentPlatformAccountMapping[i]; 
						}
						
					});
					if (thisText == '') thisText = Translator.t('请选择平台账号');
					$('div[name=div-platform-account]'+roleselect).text(thisText);
				}
			}
		});
	},
	/**********   platform & account  end    ********/
	
	/**********   role  start    ********/
	initRoleTable:function(){
		$('#role_table>tbody').html('');
		$.each(this.roleTableList,function(){
			StationLetter.addNewRole(this);
		});
	},
	addNewRole:function(roleRow){
		var OperationHtml = "";
		var roleNameHtml = '';
		var isAdd = true;
		if (roleRow==undefined){
			roleRow = {id:0 , priority :$('#div_role_pannel table tr').length  , 
			name:'',
			platform_account : Translator.t('请选择平台账号') , 
			nations_label : Translator.t('请选择国家'),
			status_label:Translator.t('请选择物流状态') , template_id:1,
			layout_id:1} 
			
			OperationHtml = '<button class="btn-transparent" type="button" autocomplete="off" data-loading-text="'+Translator.t('保存中')+'" onclick="StationLetter.saveRole(this)">'+Translator.t('保存')+'</button>'+
			'<button class="btn-transparent" type="button" autocomplete="off" data-loading-text="'+Translator.t('删除中')+'" onclick="StationLetter.deleteRole(this)">'+Translator.t('删除')+'</a>';
		}else{
			isAdd = false;
			if (roleRow.priority > 1){
				OperationHtml = '<button class="btn-transparent" type="button" autocomplete="off" data-loading-text="'+Translator.t('处理中')+'" onclick="StationLetter.setRoleUp(this)">'+Translator.t('优先')+'</button>';
			}
			OperationHtml += '<button class="btn-transparent" type="button" autocomplete="off" data-loading-text="'+Translator.t('删除中')+'" onclick="StationLetter.deleteRole(this)">'+Translator.t('删除')+'</button>';
		}
		var templateHtml = "";
		if (isAdd){
			$.each(this.templateList,function(){
				if (roleRow.template_id == this.id){
					var selectHtml = "selected";
				}else{
					var selectHtml = "";
				}
				templateHtml += '<option value="'+this.id+'" '+selectHtml+'>'+Translator.t(this.name)+'</option>';
			});
			
			templateHtml = '<select name="template">'+templateHtml+'</select>';
			roleNameHtml = '<input type="text" name="role_name" value="'+roleRow.name+'"/>';
		}else{
			$.each(this.templateList,function(){
				if (roleRow.template_id == this.id){
					templateHtml = Translator.t(this.name);
					return;
				}
				
			});
			
			roleNameHtml = roleRow.name;
		}
		
		var addHtml = '<tr data-role-id="'+roleRow.id+'">'+
		'<td>'+roleRow.priority+'<input type="hidden" name="priority" value="'+roleRow.priority+'"/></td>'+
		'<td>'+roleNameHtml+'</td>'+
		'<td><div name="div-platform-account" data-role-id="'+roleRow.id+'">'+roleRow.platform_account+'</div></td>'+
		'<td><div name="div-select-nation" data-role-id="'+roleRow.id+'">'+roleRow.nations_label+'</div></td>'+
		'<td><div name="div-ship-status" data-role-id="'+roleRow.id+'">'+roleRow.status_label+'</div></td>'+
		'<td>'+templateHtml+'</td>'+
		'<td>'+OperationHtml+'</td>'+
		'</tr>';
		$('#div_role_pannel table').append(addHtml);
		
		$('div[name=div-platform-account][data-role-id=0]').each(function(){
			StationLetter.initAccountBox(this);
		});
		$('div[name=div-select-nation][data-role-id=0]').each(function(){
			StationLetter.initNationBox(this);
		});
		$('div[name=div-ship-status][data-role-id=0]').each(function(){
			StationLetter.initStatusBox(this);
		});
		
	},
	setRoleUp:function(obj){
		$(obj).button('loading');
		var id = $(obj).parents("tr").data('role-id');
		if (id != "" && id != undefined && id != null && id != "0"){
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/message/csmessage/set-role-up?role_id='+id, 
				success: function (result) {
					$(obj).button('reset');
					var message = result.message;
					if (result.message){
						bootbox.alert(message);  
					}
					
					if(result.success){
						$(obj).parents("tr").remove();
					}
					
					if (result.data){
						StationLetter.roleTableList = result.data.roles;
						StationLetter.initRoleTable();
					}
					
					return result;
				},
				error: function(){
					bootbox.alert("Internal Error");
					$(obj).button('reset');
					return false;
				}
			});
		}else{
			bootbox.alert(Translator.t('没有选中有效的规则!'));
		}
	},
	
	saveRole:function(obj){
		
		var roleId = $(obj).parents("tr").data('role-id');
		var roleName = $(obj).parents("tr").find('input[name=role_name]').val();
		var templateId = $(obj).parents("tr").find('select[name=template]').val();
		var layoutId = $(obj).parents("tr").find('select[name=layout]').val();
		var priority = $(obj).parents("tr").find('input[name=priority]').val();
		if (roleName == ""){
			bootbox.alert(Translator.t('填入规则名称'));
			return;
		}
		
		
		
		if ($('input[name=chk-platform]:checked').length ==0){
			bootbox.alert(Translator.t('请选择平台账号'));
			return;
		}
		
		if ($('input[name=chk-nation]:checked').length ==0){
			bootbox.alert(Translator.t('请选择国家'));
			return;
		}
		
		if ($('input[name=chk-status]:checked').length ==0){
			bootbox.alert(Translator.t('请选择物流状态'));
			return;
		}
		$(obj).button('loading');
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/message/csmessage/save-role?role_id='+roleId, 
			data:{ role_name : roleName , 
			platform_account :StationLetter.currentPlatformAccount , 
			nation:StationLetter.currentNation ,
			ship_status : StationLetter.currentStatus , 
			template_id :templateId  , 
			layout_id :layoutId,
			priority: priority},
			success: function (result) {
				$(obj).button('reset');
				//StationLetter.currentRoleData = '';
				StationLetter.isRefreshMatching = true;
				StationLetter.currentPlatformAccount = {};
				StationLetter.currentPlatformAccountMapping = {};
				StationLetter.currentNation = {};
				StationLetter.currentStatus = {};
				
				if (result.data){
					StationLetter.roleTableList = result.data.roles;
					StationLetter.initRoleTable();
				}
			},
			error:function(){
				bootbox.alert("Internal Error");
				$(obj).button('reset');
				return false;
			}
		});
	},
	
	deleteRole:function(obj){
		$(obj).button('loading');
		var id = $(obj).parents("tr").data('role-id');
		if (id != "" && id != undefined && id != null && id != "0"){
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/message/csmessage/delete-role?role_id='+id, 
				
				success: function (result) {
					$(obj).button('reset');
					var message = result.message;
					if (result.message){
						bootbox.alert(message);  
					}
					
					if(result.success){
						$(obj).parents("tr").remove();
						StationLetter.isRefreshMatching = true;
					}
					
					if (result.data){
						StationLetter.roleTableList = result.data.roles;
						StationLetter.initRoleTable();
					}
					
					return result;
				},
				error: function(){
					bootbox.alert("Internal Error");
					$(obj).button('reset');
					return false;
				}
			});
		}else{
			$(obj).parents("tr").remove();
			
		}
	},
	/**********   role  end    ********/
	fillTemplateData:function(){
		//debugger;
		$.each(StationLetter.templateList , function(i){
			if (this.id == $('select[name=letter_template_used]').val() ){
				$('#letter_template').val(this.body);//StationLetter.templateList[i]
				$('#template_id').val(this.id);
				$('#subject').val(this.subject);
				$('select[name=template_layout]').val(StationLetter.templateAddiInfoList[this.id].layout);
				$('select[name=recom_prod_count]').val(StationLetter.templateAddiInfoList[this.id].recom_prod_count);
				if(StationLetter.templateAddiInfoList[this.id].recom_prod_group!==undefined){
					var recom_prod_group = StationLetter.templateAddiInfoList[this.id].recom_prod_group;
					if(recom_prod_group!==0){
						$('input#template_platform').val(StationLetter.recomGroups[recom_prod_group].platform);
						$('input#template_sellerid').val(StationLetter.recomGroups[recom_prod_group].seller_id);
						$('select[name=recom_prod_group]').val(StationLetter.recomGroups[recom_prod_group].id);
					}
				}
			}
		});
		this.originTemplate = $('#letter_template').val();
		if(StationLetter.defaultTemplate.template_id!==-1 && StationLetter.defaultTemplate.template_id!==-2 && StationLetter.defaultTemplate.template_id!==0){
			$("#select_recom_group").show();
		}
		var letter_template_used = $('select[name=letter_template_used]').val() +'';
		if(letter_template_used!=='-1' && letter_template_used!=='-2' && letter_template_used!=='0' ){
			$("#select_recom_group").show();
		}else{
			$("#select_recom_group").hide();
		}
	},
	
	setNewTemplateName:function(){
		if ($('select[name=letter_template_used]').val() =='-1'){
			var newTemplateHtml = '<label for="letter_template_name" class="col-sm-1 control-label">'+Translator.t('新模板名称')+'</label>'+
					'<div class="col-sm-5">'+
					'<input type="text" class="form-control" id="letter_template_name" name="letter_template_name" value="" />'+
					'</div>';
			$('#div_new_template').html(newTemplateHtml);
			$('#letter_template').val('');
			$('#template_id').val('-1');
			$('div[name=div_manual_template]').css('display','block');
			$('div[name=div_auto_template]').css('display','none');
			StationLetter.showMessageBoxByType('message');
		}else if ($('select[name=letter_template_used]').val() =='-2'){
			var newTemplateHtml = '<div class="col-sm-5">'+
					'<input type="button" class="btn btn-success btn-sm" id="btn_set_roles" name="btn_set_roles" style="margin: 5px 0 10px 0;" value="'+Translator.t('设置匹配规则')+'" />'+
					'</div>';
			$('#div_new_template').html(newTemplateHtml);
			$('#letter_template').val('');
			$('#template_id').val('-2');
			$('div[name=div_manual_template]').css('display','none');
			$('div[name=div_auto_template]').css('display','block');
			StationLetter.showMessageBoxByType('role');
		}else{
			$('#div_new_template').html('');
			$('div[name=div_manual_template]').css('display','block');
			$('div[name=div_auto_template]').css('display','none');
			StationLetter.showMessageBoxByType('message');
		}
	},
	
	markPos:function(textObj){
		if (textObj.createTextRange) {
			textObj.caretPos = document.selection.createRange().duplicate();
		}
	},
	
	addLetterVariance:function(){
		textObj = $('#letter_template');
		textFeildValue = $('select[name=letter_template_variance]').val();
		 if (document.all) {
			if (textObj.createTextRange && textObj.caretPos) {
				var caretPos = textObj.caretPos;
				caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? textFeildValue + ' ' : textFeildValue;
			} else {
				textObj.value = textFeildValue;
			}
		} else {
			if (textObj.setSelectionRange == undefined)
				textObj = textObj[0];
			if (textObj.setSelectionRange) {
				var rangeStart = textObj.selectionStart;
				var rangeEnd = textObj.selectionEnd;
				var tempStr1 = textObj.value.substring(0, rangeStart);
				var tempStr2 = textObj.value.substring(rangeEnd);
				textObj.value = tempStr1 + textFeildValue + tempStr2;
			} else {
				//alert("This version of Mozilla based browser does not support setSelectionRange");
			}
		}

	},
	
	sendMessageByRole:function(callback){
		var track_no_mapping_role = {};
		var isBreak = false;
		var track_no = '';
		var no_send_html = '';
		
		if ($('#track_no_list').val() == ""){
			bootbox.alert('没有有效的订单号!');
			return false;
		}
		
		$('select[name=unmatch_template]').each(function(){
			track_no = $(this).data('track-no');
			if (this.value==-1){
				if (no_send_html != '') no_send_html += ','
				no_send_html += track_no+''
				//isBreak = true;
				//return isBreak;
			}else{
				if ( track_no_mapping_role[this.value] == undefined){
					track_no_mapping_role[this.value] = [];
				}
				track_no_mapping_role[this.value].push(track_no);
				//track_no_mapping_role[track_no] = this.value;
			}
		});
		
		/*
		if (isBreak){
			bootbox.alert('请指定'+track_no+'使用的模版!');
			return false;
		}
		*/
		if (no_send_html != ''){
			bootbox.confirm(no_send_html+'\n'+Translator.t("以上物流号将不发送!是否继续?"),function(r){
				if ( r){
					StationLetter.requestSendMessageByRole(track_no_mapping_role, callback);
				}
			});
		}else{
			StationLetter.requestSendMessageByRole(track_no_mapping_role, callback);
		}
	},
	
	requestSendMessageByRole:function(track_no_mapping_role, callback){
		$('input[name=match_role_tracker]').each(function(){
				track_no = $(this).data('track-no');
				if ( track_no_mapping_role[this.value] == undefined){
					track_no_mapping_role[this.value] = [];
				}
				track_no_mapping_role[this.value].push(track_no);
				//track_no_mapping_role[track_no] = this.value;
			});
			$.showLoading();
			$.ajax({
				type: "POST",
					dataType: 'json',
					url:'/tracking/tracking/send-message-by-role', 
					data: {track_no_mapping_role : track_no_mapping_role , track_no_list :$('#track_no_list').val(),pos : $("input[name='pos']").val()},
					success: function (result) {
						$.hideLoading();
						message = result.message;
						if (result.validation){
							for( var track_no in result.validation){
								if (result.validation[track_no]['subject']){
									message += '<br><br>'+ track_no + ' (标题) 超出100长度:'+result.validation[track_no]['subject'];
								}
								
								else if (result.validation[track_no]['content']){
									message += '<br><br>'+ track_no + ' (内容) 超出2000长度!';
								}
								
								else if (result.validation[track_no]['from_email']){
									message += '<br><br>'+ track_no + ' 卖家店铺邮箱未设置!';
								}
								
								else if (result.validation[track_no]['to_email']){
									message += '<br><br>'+ track_no + ' 买家邮箱地址格式错误!';
								}
								else
									message += '<br><br>'+ track_no + result.validation[track_no];
							}
						}
						bootbox.alert({
							message:message,
							callback:function(){
								callback();
							}
						});
					},
					error:function(){}
			});
	},
	
	/* 
		isUpdate :
		B short for both , send message and save template
		M short for message , send message
		T short for template , save template
	
	*/
	sendMessage:function(isUpdate , callback){
		if ($('#subject').val() ==''){
			bootbox.alert(Translator.t('请输入'+$('label[for=letter_theme]').text()+'!'));
			return;
		}
		if ($('select[name=letter_template_used]').val() =='-1'){
			if ($('#letter_template_name').val() == "") {
				bootbox.alert(Translator.t('请输入新模板名称!'));
				return;
			}
		}
		
		if ($.trim($('#letter_template').val()).length == 0 ) {
			bootbox.alert(Translator.t('请输入有效的内容!'));
			return;
		}
		$.showLoading();
		var pos = $("input[name='pos']").val();
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/send-message?isUpdate='+isUpdate+'&pos='+pos, 
				data: $('#send-station-letter').serialize(),
				success: function (result) {
					
					var message = result.message;
					if (result.validation){
						for( var track_no in result.validation){
							if (result.validation[track_no]['subject']){
								message += '<br><br>'+ track_no + ' (标题) 超出100长度:'+result.validation[track_no]['subject'];
							}
							
							else if (result.validation[track_no]['content']){
								message += '<br><br>'+ track_no + ' (内容) 超出2000长度!';
							}
							
							else if (result.validation[track_no]['from_email']){
								message += '<br><br>'+ track_no + ' 卖家店铺邮箱未设置!';
							}
							
							else if (result.validation[track_no]['to_email']){
								message += '<br><br>'+ track_no + ' 买家邮箱地址格式错误!';
							}
							else
								message += '<br><br>'+ track_no + result.validation[track_no];
						}
						
						bootbox.alert({
							className : 'order_info',
							buttons: {  
							   ok: {  
									label: '确定', 
																	
								}  
							},  
							message: message, 
							
						});  
					}else{
						bootbox.alert({
							message:message,
							callback:function(){
								if (result.success)
									callback();
							}
						});
						
					}
					$.hideLoading();
					return result;
				},
				error: function(){
					$.hideLoading();
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	setDisabled:function(IsDisabled){
		if (IsDisabled){
			$('#send-station-letter input').prop('readonly','readonly');
			$('#send-station-letter textarea').prop('readonly','readonly');
		}else{
			$('#send-station-letter input').removeAttr('readonly');
			$('#send-station-letter textarea').removeAttr('readonly');
		}
		
	} , 
	
	showPreviewBoxByObj:function(obj){
		var myObj = $(obj);
		
		if (myObj.parents('tr').find('input[name=match_role_tracker]').length == 1){
			template_id = myObj.parents('tr').find('input[name=match_role_tracker]').val();
			track_no = myObj.parents('tr').find('input[name=match_role_tracker]').data('track-no');
		}else{
			template_id = myObj.parents('tr').find('select[name=unmatch_template]').val();
			track_no = myObj.parents('tr').find('select[name=unmatch_template]').data('track-no');
		}
		if (template_id<=0){
			bootbox.alert(Translator.t('请选择模板!'));
			return;
		}
		
		if (StationLetter.templateAddiInfoList[template_id]['layout'] != undefined)
			StationLetter.currentLayoutId = StationLetter.templateAddiInfoList[template_id]['layout'];
		else
			StationLetter.currentLayoutId = 1;
		
		if (StationLetter.templateAddiInfoList[template_id]['recom_prod_count'] != undefined){
			StationLetter.currentRecommendProductCount = StationLetter.templateAddiInfoList[template_id]['recom_prod_count'];
			StationLetter.currentRecommendProductGroup = StationLetter.templateAddiInfoList[template_id].recom_prod_group;
		}else{
			StationLetter.currentRecommendProductCount = 8;
			StationLetter.currentRecommendProductGroup = '0';
		}
		var content = "";
		var subject = "";
		$.each(StationLetter.templateList,function(){
			if (this.id == template_id){
				subject = this.subject;
				content = this.body;
				
				return;
			}
		});
		
		StationLetter.showPreviewBox(track_no , content , subject);
		
		StationLetter.setProductRecommendSetting(track_no,StationLetter.currentLayoutId ,StationLetter.currentRecommendProductCount,StationLetter.currentRecommendProductGroup);
	},
	
	showPreviewTemplateBox:function(track_no , template_id){
		var content = '';
		var subject = '';
		$.each(StationLetter.templateList,function(){
			if (this.id == template_id){
				subject = this.subject;
				content = this.body;
				if (StationLetter.templateAddiInfoList[template_id].layout != undefined)
					StationLetter.currentLayoutId = StationLetter.templateAddiInfoList[template_id].layout; 
				else
					StationLetter.currentLayoutId = '1'; 
				
				StationLetter.currentRecommendProductCount = '8';
				
				if (StationLetter.templateAddiInfoList[template_id].recom_prod_count != undefined){
					StationLetter.currentRecommendProductCount = StationLetter.templateAddiInfoList[template_id].recom_prod_count;
					StationLetter.currentRecommendProductGroup = StationLetter.templateAddiInfoList[template_id].recom_prod_group;
				}else{
					StationLetter.currentRecommendProductCount = '8';
					StationLetter.currentRecommendProductGroup = '0';
				}
				return;
			}
		});
		StationLetter.showPreviewBox(track_no , content , subject);
	},
	
	showPreviewBox:function(track_no_list , content , subject){
		$.showLoading();
		$.ajax({
			type: "GET",
			dataType: 'json',
			url:'/tracking/tracking/preview-message', 
			data: {track_no_list : track_no_list , template: content  , subject : subject  },
			success: function (result) {
				$.hideLoading();
				StationLetter.previewDatalist = result;
				for(var track_no in result){
					var thisHtml =  StationLetter.setPreviewHtml(track_no);
					/**/
					var tracknoHtml ='';
					var selectHtml = ''
					var linkHtml = "";
					if (StationLetter.previewDatalist[track_no].recom_prod_preview_link != undefined)
						linkHtml = StationLetter.previewDatalist[track_no].recom_prod_preview_link;

					
					for(var i in StationLetter.previewDatalist ){
						/*
						if (i == track_no){
							selectHtml = " selected ";
						}else{
							selectHtml = "";
						}
						*/
						tracknoHtml += '<option value="'+i+'" >'+i+'</option>';
						
					}
					if (tracknoHtml != '')
						tracknoHtml = '<select name="preview_track_no">'+tracknoHtml+'</select>';
					
					break;
				}
				
				thisHtml='<div id="preview_box_container">'+
					'<div id="preview_box_left">'+thisHtml+'</div>'+
					'<div id="preview_box_right">'+
					'<div class="panel panel-info">'+
					'<div class="panel-heading">'+
					'<h5>2.'+Translator.t('买家点击包裹追踪连接，看到包裹进度以及')+'<b>'+Translator.t('二次营销商品推荐')+'</b>';
				if (linkHtml != ''){
					thisHtml += '<small class="pull-right"><a id="link_recom_prod" href="'+linkHtml+'" target="_blank" style="color: red;font-weight: bold;">'+Translator.t('查看真实效果')+'</a></small>';
				}
					
				thisHtml +='</h5><br><span style="color:red">包裹追踪连接依赖物流号，如果没有物流号则连接没有有效内容！</span><br><span style="color:red">如果物流号缺少订单号或者推荐商品，包裹追踪连接则会缺少相应内容。最终以真实效果为准</span></div>'+
					'<div class="panel-body">';
				if (linkHtml != ''){
					thisHtml += '<a href="'+linkHtml+'" target="_blank">';
				}
				
				//thisHtml += '<img src="/images/tracking/msg_recom_prod_layout_'+StationLetter.currentLayoutId+'.png" style="max-width: 550px;">';
				thisHtml += '<img src="/images/tracking/msg_recom_prod_layout_1.png" style="max-width: 550px;">';
				
				if (linkHtml != ''){
					thisHtml += '</a>';
				}
					
				thisHtml +='</div></div></div>'+'</div>'+
					'<div class="clearfix" style="margin-bottom: 5px;"></div>'+
					'<div class="modal-footer">'+tracknoHtml+'<button data-bb-handler="Ok" type="button" class="btn btn-transparent">关闭</button></div>'
					
				var thisbox = bootbox.dialog({
				title: Translator.t("预览"),
				className: "preview-box", 
				message: thisHtml,
				
				});
				$('.modal-footer').on('change' , 'select[name=preview_track_no]' , function(){
					//StationLetter.fillTemplateData();
					StationLetter.fillPreviewData();
					if (StationLetter.previewDatalist[this.value].recom_prod_preview_link !=undefined)
						$('#link_recom_prod').prop('href',''+StationLetter.previewDatalist[this.value].recom_prod_preview_link);
				});
				
				var trackno = $('select[name=preview_track_no]').val();
				StationLetter.setProductRecommendSetting(trackno,StationLetter.currentLayoutId ,StationLetter.currentRecommendProductCount,StationLetter.currentRecommendProductGroup);
				
			},
			error: function(){
				$.hideLoading();
				bootbox.alert("Internal Error");
				return false;
			}
		});
	},
	
	PreviewMessage:function(){
		
		$.showLoading();
		this.setDisabled(true);
		$("#div_preview").show();
		$(".div_template").hide();
		
		$.ajax({
			type: "GET",
				dataType: 'json',
				url:'/tracking/tracking/preview-message', 
				data: {track_no_list : $('#track_no_list').val() , template: $('#letter_template').val()  , subject : $('#subject').val() },
				success: function (result) {
					StationLetter.previewDatalist = result;
					StationLetter.fillPreviewData();
					$.hideLoading();
					return result;
				},
				error: function(){
					bootbox.alert("Internal Error");
					return false;
				}
		});
	},
	
	fillEditData:function(obj,msg_id){
		var myObj = $(obj);
		$('div[name=div_manual_template]').css('display','block');
		$('div[name=div_letter_template_used]').css('display','none');
		$('input[name=subject]').val(myObj.parents('.letter-history-list-row').find('[name=subject]').text());
		$('textarea[name=letter_template]').val(myObj.parents('.letter-history-list-row').find('[name=content]').text())
		$('#op_method').val('resend');
		$('#msg_id').val(msg_id);
		
		if ($('div[name=station_letter]').css('display')=='none'){
			StationLetter.showMessageBoxByType('message');
		}
		
		
			$('button[data-bb-handler=Ok]').css('display','none');
			$('button[data-bb-handler=Btn4]').css('display','none');
			
		
	},
	
	setPreviewHtml:function(trackno){
		var subject = StationLetter.previewDatalist[trackno].subject;
		var template = StationLetter.previewDatalist[trackno].template;
		var tail = StationLetter.previewDatalist[trackno].tail;
		
		var thisHtml = 
		'<div id="div_preview"  class="panel panel-info">'+
		'<div class="panel-heading"><h5>1.'+Translator.t('买家收到的留言或者站内信内容')+'</h5></div>'+
				'<div class="panel-body">'+
				'<div  class="form-group"  > '+
					'<label for="div_preview_subject" class="col-sm-12" style="color:#31708f;background-color:#d9edf7;margin-top:3px;">'+Translator.t('标 题')+':</label>'+
					'<div id="div_preview_subject" class="col-sm-12">'+
					
					'<h5>'+subject+'</h5>'+
					'</div>'+
				'</div>'+
				'<div  class="form-group"  > '+
					'<label for="div_preview_content" class="col-sm-12" style="color:#31708f;background-color:#d9edf7;margin-top:3px;">'+Translator.t('内 容')+':</label>'+
					'<div id="div_preview_content" class="col-sm-12">'+template+'<br><br>'+tail+'</div>'+
				'</div>'+
				'</div>'+
			'</div>';
		
		return thisHtml;
	},
	fillPreviewData:function(){
		var trackno = $('select[name=preview_track_no]').val();
		var thisHtml = this.setPreviewHtml(trackno);
		StationLetter.setProductRecommendSetting(trackno,StationLetter.currentLayoutId ,StationLetter.currentRecommendProductCount,StationLetter.currentRecommendProductGroup);
		$('#preview_box_left').html(thisHtml);
	},
	
	ClosePreview:function(){
		this.setDisabled(false);
		$("#div_preview").hide();
		$(".div_template").show();
	},
	
	setProductRecommendSetting:function(track_no, layout_id,product_count,recom_group){
		$.get('/tracking/tracking/set-product-recommend-setting?track_no='+track_no+'&layout_id='+layout_id+'&product_count='+product_count+'&recom_prod_group='+recom_group);
	},
	
	batchShowMessageBox:function(){
		var selected_tracking_no_list = new Array();
		var unmatch_platform_track_no = '';
		var edm_platform_count = 0;
		$("[name='chk_tracking_record']:checkbox").each(function(){
			if (this.checked){
				selected_tracking_no_list.push(this.value);
				if($(this).data('order-platform')!=='ebay' && $(this).data('order-platform')!=='amazon' && $(this).data('order-platform')!=='cdiscount' && $(this).data('order-platform')!=='aliexpress'){
					var thisTrackNo = $(this).parents('tr').eq(0).attr('track_no');
					if(thisTrackNo!==undefined){
						if(unmatch_platform_track_no=='')
							unmatch_platform_track_no = thisTrackNo;
						else
							unmatch_platform_track_no +=','+thisTrackNo;
					}
				}else{
					if($(this).data('order-platform')=='cdiscount' || $(this).data('order-platform')=='amazon'){
						edm_platform_count++;
					}
				}
			}
		});
		if(unmatch_platform_track_no){
			bootbox.alert(Translator.t("选择的物流号中有不支持发信的销售平台，物流号如下<br>")+unmatch_platform_track_no+"<br>"+Translator.t("操作中止！"));
			return;
		}
		if (selected_tracking_no_list.length ==0){
			bootbox.alert(Translator.t("请选择需要发信的物流号?"));
			return;
		}
		$.showLoading();
		$.get('/tracking/tracking/station-letter-detail?is_decode=true&track_no='+selected_tracking_no_list,
		   function (data){
				$.hideLoading();
				if (data == 'empty'){
					bootbox.alert('选中的物流号不是可发信的平台，或没有对应有效的订单信息， 不能发信!');
					return false;
				}
				var title = "<p>"+Translator.t("发送站内信")+'</p><small style="color:red">目前只支持速卖通、eBay、Amazon、Cdiscount的发信。Amazon、Cdiscount发信会消耗</small><a href="/payment/user-account/package-list" target="_blank"><small style="color:blue">小老板邮件发送额度</small></a>';
				if(edm_platform_count > 0)
					title+= '<br><small>本次操作至少消耗</small><b style="color:red"> '+edm_platform_count+' </b><small>个小老板邮件发送额度!</small><a href="/order/od-lt-message/view-quota-history" target="_blank"><small style="color:blue">查看剩余额度</small></a>';
				var thisbox = bootbox.dialog({
					title: title,
					className: "xlbox", 
					message: data,
					buttons:{
						Ok: {  
							label: Translator.t("发送并保存"),  
							className: "btn-success",  
							callback: function () { 
							
								StationLetter.sendMessage('B', function(){
									$(thisbox).modal('hide');
								});
								return false;
							}
						}, 
						Btn5:{
							label: Translator.t("仅发送"),  
							className: "btn-success",  
							callback: function () { 
								StationLetter.sendMessage('M', function(){
									$(thisbox).modal('hide');
								});
								$('button[data-bb-handler=Btn3]').css('display','none');
								$('button[data-bb-handler=Btn1]').css('display','inline-block');
								$('#preview_track_no').css('display','none');
								return false;
							}
						},
						Btn4:{
							label: Translator.t("保存模版"),  
							className: "btn-success",  
							callback: function () { 
								StationLetter.sendMessage('T', function(){
									$(thisbox).modal('hide');
								});
								$('button[data-bb-handler=Btn3]').css('display','none');
								$('button[data-bb-handler=Btn1]').css('display','inline-block');
								$('#preview_track_no').css('display','none');
								return false;
							}
						},
						Btn6:{
							label: Translator.t("发送"),  
							className: "btn-success",  
							callback: function () { 
							
							StationLetter.sendMessageByRole(function(){
									$(thisbox).modal('hide');
							});
								return false;
							}
						},
						Btn1:{
							label: Translator.t("预览"),  
							className: "btn-info",  
							callback: function () { 
								if ($('#track_no_list').val() == ""){
									bootbox.alert('选中的物流号没有对应有效的订单号, 不能预览!');
									return false;
								}
								StationLetter.currentLayoutId = $('select[name=template_layout]').val();
								StationLetter.currentRecommendProductCount = $('select[name=recom_prod_count]').val();
								StationLetter.currentRecommendProductGroup = $('select[name=recom_prod_group]').val();
								StationLetter.showPreviewBox($('#track_no_list').val() , $('#letter_template').val() , $('#subject').val());
								return false;
							}
						},
						
						Btn3:{
							label: Translator.t("关闭预览"),  
							className: "btn-transparent",  
							callback: function () { 
								StationLetter.ClosePreview();
								$('button[data-bb-handler=Btn3]').css('display','none');
								$('button[data-bb-handler=Btn1]').css('display','inline-block');
								$('#preview_track_no').css('display','none');
								return false;
							}
						},
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-transparent",  
							callback: function () {  
							}
						}, 
					}
				});	
				
				StationLetter.showMessageBoxByType('role');
				
				StationLetter.init();
				StationLetter.setMatchRoleHtml()
		});
	},
	
	
	showTemplateBox:function(template_id){
		if (template_id > 0 ){
			var title = Translator.t("修改模版");
			var url = '/tracking/tracking/manage-template?template_id='+template_id ;
		}else{
			var title = Translator.t("新增模版");
			var url = '/tracking/tracking/manage-template?template_id=-1' ;
		}
			
		$.get(url, 
		function(data){
			var thisbox = bootbox.dialog({
					title: title,
					className: "xlbox", 
					message: data,
					buttons:{
						Ok: {  
							label: Translator.t("保存"),  
							className: "btn-success",  
							callback: function () { 
								StationLetter.sendMessage('T', function(){
									$(thisbox).modal('hide');
								});
								return false;
							}
						}, 
						
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-transparent",  
							callback: function () {  
							}
						}, 
					}
				});

			StationLetter.showMessageBoxByType('template');
			
			StationLetter.init();
		});
	},
	
	deleteTemplate:function(template_id,obj){
		if (template_id > 0 ){
			var name = $(obj).parents("tr").find("td:eq(1)")[0].innerHTML;
			bootbox.confirm(
				Translator.t("确定删除模板")+"<b style='color:red;'>"+name+"</b>?",
				function(r){
					if (! r) return;
					$.showLoading();
					$.ajax({
						type: "POST",
						dataType: 'json',
						url: '/tracking/tracking/delete-template',
						data: {template_id:template_id} , 
						success: function (result) {
							//$.hideLoading();
							if (result.success){
								$.hideLoading();
								bootbox.alert({
									buttons: {
										ok: {
											label: 'OK',
											className: 'btn-primary'
										}
									},
									message: Translator.t("成功删除模板!"),
									callback: function() {
										window.location.reload();
									}, 
								});
							}
							else{
								$.hideLoading();
								bootbox.alert(Translator.t("删除模板失败:")+result.message);
								return false;
							}
						},
						error :function () {
							$.hideLoading();
							bootbox.alert(result.message);
							return false;
						}
					});
				}
			);
		}else{
			bootbox.alert(Translator.t("没有选定模板，或模板id丢失！"));
			return false;
		}
	},
	
	
	
	showMessageBox:function(order_id,track_no,show_method,count){
		$.showLoading();
		
		$.get('/tracking/tracking/station-letter-detail?track_no='+track_no+'&order_id='+order_id+'&show_method='+show_method,
		   function (data){
				$.hideLoading();
				if (data == 'empty'){
					bootbox.alert('选中的物流号不是可发信的平台，或没有对应有效的订单信息， 不能发信!');
					return false;
				}
				var thisbox = bootbox.dialog({
					title: Translator.t("物流号:")+track_no+'<br><small>Cdiscount和Amazon平台订单发信会消耗</small><a href="/payment/user-account/package-list" target="_blank"><small style="color:blue">小老板邮件发送额度! </small></a><a href="/order/od-lt-message/view-quota-history" target="_blank"><small style="color:blue"> 查看剩余额度</small></a>',
					className: "xlbox", 
					message: data,
					buttons:{
						Btn2:{
							label: Translator.t("重发所有失败消息")+'('+count+')',  
							className: "btn-info",  
							callback: function () { 
								StationLetter.resendAllFailureMessage(count);
								return false;
							}
						},
						Ok: {  
							label: Translator.t("发送并保存"),  
							className: "btn-success",  
							callback: function () { 
							
								StationLetter.sendMessage('B', function(){
									$(thisbox).modal('hide');
								});
								return false;
							}
						}, 
						Btn5:{
							label: Translator.t("仅发送"),  
							className: "btn-success",  
							callback: function () { 
								StationLetter.sendMessage('M', function(){
									$(thisbox).modal('hide');
								});
								$('button[data-bb-handler=Btn3]').css('display','none');
								$('button[data-bb-handler=Btn1]').css('display','inline-block');
								$('#preview_track_no').css('display','none');
								return false;
							}
						},
						Btn4:{
							label: Translator.t("保存模版"),  
							className: "btn-success",  
							callback: function () { 
								StationLetter.sendMessage('T', function(){
									$(thisbox).modal('hide');
								});
								$('button[data-bb-handler=Btn3]').css('display','none');
								$('button[data-bb-handler=Btn1]').css('display','inline-block');
								$('#preview_track_no').css('display','none');
								return false;
							}
						},
						Btn6:{
							label: Translator.t("发送"),  
							className: "btn-success",  
							callback: function () { 
							
							StationLetter.sendMessageByRole(function(){
									$(thisbox).modal('hide');
							});
								return false;
							}
						},
						Btn1:{
							label: Translator.t("预览"),  
							className: "btn-info",  
							callback: function () { 
								if ($('#track_no_list').val() == ""){
									bootbox.alert('选中的物流号没有对应有效的订单号, 不能预览!');
									return false;
								}
								StationLetter.currentLayoutId = $('select[name=template_layout]').val();
								StationLetter.currentRecommendProductCount = $('select[name=recom_prod_count]').val();
								StationLetter.currentRecommendProductGroup = $('select[name=recom_prod_group]').val();
								StationLetter.showPreviewBox($('#track_no_list').val() , $('#letter_template').val() , $('#subject').val());
								return false;
							}
						},
						
						Btn3:{
							label: Translator.t("关闭预览"),  
							className: "btn-transparent",  
							callback: function () { 
								StationLetter.ClosePreview();
								$('button[data-bb-handler=Btn3]').css('display','none');
								$('button[data-bb-handler=Btn1]').css('display','inline-block');
								$('#preview_track_no').css('display','none');
								return false;
							}
						},
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-transparent",  
							callback: function () {  
							}
						}, 
					}
				});	
				
				
				if (count==0){
					$('button[data-bb-handler=Btn2]').css('display','none');
				}
				StationLetter.init();
				if (show_method=='role'){
					StationLetter.setMatchRoleHtml();
				}
				StationLetter.showMessageBoxByType(show_method);
					
				
		});
	},
	
	showMessageBoxByType:function(show_method){
		if (show_method== 'history'){
			$('div[name=station_letter]').css('display','none');
			$('div[name=message_history]').css('width','100%');
			$('div[name=message_history]').css('display','block');
			$('button[data-bb-handler=Btn1]').css('display','none');
			$('button[data-bb-handler=Ok]').css('display','none');
			$('button[data-bb-handler=Btn3]').css('display','none');
			$('button[data-bb-handler=Btn4]').css('display','none');
			$('button[data-bb-handler=Btn5]').css('display','none');
			$('button[data-bb-handler=Btn6]').css('display','none');
		}else if (show_method== 'role'){
			$('div[name=message_history]').css('display','none');
			$('button[data-bb-handler=Btn2]').css('display','none');
			$('button[data-bb-handler=Btn3]').css('display','none');
			$('div[name=station_letter]').css('display','block');
			$('div[name=message_history]').css('width','100%');
			$('button[data-bb-handler=Btn1]').css('display','none');
			$('button[data-bb-handler=Ok]').css('display','none');
			$('button[data-bb-handler=Btn4]').css('display','none');
			$('button[data-bb-handler=Btn5]').css('display','none');
			$('button[data-bb-handler=Btn6]').css('display','inline-block');
		}else if (show_method== 'template'){
			$('#track_no_list').parents('.form-group').css('display','none');
			//$('select[name=letter_template_used]').parents('.form-group').css('display','none');
			$('select[name=letter_template_used]').attr('disabled','disabled');
			$('button[data-bb-handler=Btn2]').css('display','none');
			$('button[data-bb-handler=Btn3]').css('display','none');
			$('div[name=station_letter]').css('display','block');
			$('div[name=message_history]').css('width','100%');
			$('button[data-bb-handler=Btn1]').css('display','none');
			$('button[data-bb-handler=Ok]').css('display','none');
			$('button[data-bb-handler=Btn4]').css('display','inline-block');
			$('button[data-bb-handler=Btn5]').css('display','none');
			$('button[data-bb-handler=Btn6]').css('display','none');
		}else{
			$('div[name=message_history]').css('display','none');
			$('button[data-bb-handler=Btn2]').css('display','none');
			$('button[data-bb-handler=Btn3]').css('display','none');
			$('div[name=station_letter]').css('display','block');
			$('div[name=message_history]').css('width','100%');
			$('button[data-bb-handler=Btn1]').css('display','inline-block');
			$('button[data-bb-handler=Ok]').css('display','inline-block');
			$('button[data-bb-handler=Btn4]').css('display','inline-block');
			$('button[data-bb-handler=Btn5]').css('display','inline-block');
			$('button[data-bb-handler=Btn6]').css('display','none');
		}
	},
	
	resendAllFailureMessage:function(count){
		if (count==0) {
			bootbox.alert(Translator.t('没有需要重发的消息!'));
			return;
		}
		
		bootbox.confirm(Translator.t("是否重发所有失败的邮件?"),function(r){
		if (! r) return;
			$.showLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/resend-all-failure-message', 
				success: function (result) {
					if (result.success){
						window.location.reload();
					}else{
						bootbox.alert(result.message);
						$.hideLoading();
					}
					return true;
				},
				error :function () {
					
					bootbox.alert("Internal Error");
					$.hideLoading();
					return false;
				}
			});
		
		});
	},
	
	showRoleSettingBox:function(){
		$.showLoading();
		StationLetter.isRefreshMatching = false;
		$.get('/message/csmessage/role-setting',
		   function (data){
				$.hideLoading();
				if (data == 'empty'){
					bootbox.alert('选中的物流号没有对应有效的订单信息, 不能发信!');
					return false;
				}
				
				StationLetter.clearAddRoleSetingCache();
				
				var thisbox = bootbox.dialog({
					title: Translator.t("发送站内信"),
					className: "xlbox", 
					message: data,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-transparent",  
							callback: function () {
								
							}
						}, 
					}
				});
				
				thisbox.on('hidden.bs.modal', function (e) {
					if (StationLetter.isRefreshMatching){
						bootbox.confirm(Translator.t("规则已经更改!是否刷新匹配结果?"),function(r){
							if ( r){
								StationLetter.refreshRoleMatching();
							}
						});
					}
				});
				
				StationLetter.initRoleTable();
				$('#div_role_pannel').on('click' , '#btn_add_role' , function(){
					if ($('#role_table tr[data-role-id=0]').length == 0 ){
						StationLetter.addNewRole();
					}else{
						bootbox.alert(Translator.t('请先保存规则再增加'));
					}
					
				});
			}
		);
	},
	
	clearAddRoleSetingCache:function(){
		StationLetter.currentPlatformAccount = {};
		StationLetter.currentPlatformAccountMapping = {};
		StationLetter.currentNation = {};
		StationLetter.currentStatus = {};
	},
	
	setMatchRoleHtml:function(){
		var thisHtml = "";
		thisHtml += '<label class="col-sm-1"></label>'+
				'<div class="col-sm-11">';
				
		if (StationLetter.MatchResult['match_data'] != undefined && StationLetter.MatchResult['match_data'].length >0){
			thisHtml +=  '<table class="table">'+
						'<thead>'+
							'<tr>'+
								'<th>'+Translator.t('物流号')+'</th>'+
								'<th>'+Translator.t('匹配内容模板')+'</th>'+
								'<th>'+Translator.t('订单号')+'</th>'+
								'<th>'+Translator.t('递送国家')+'</th>'+
								'<th>'+Translator.t('跟踪连接')+'</th>'+
								'<th>'+Translator.t('匹配状态')+'</th>'+
								'<th>'+Translator.t('操作')+'</th>'+
							'</tr>'+'</thead>';
			
			$.each(StationLetter.MatchResult['match_data'], function(){
				if (StationLetter.templateAddiInfoList[this.template_id]['name'] != undefined)
					var my_role_name = ":"+StationLetter.templateAddiInfoList[this.template_id]['name'];
				else
					var my_role_name = '';
				
				if (StationLetter.templateAddiInfoList[this.template_id]['recom_prod'] != undefined && StationLetter.templateAddiInfoList[this.template_id]['recom_prod']=='Y'){
					var my_recom_prod = Translator.t('是');
				}else{
					var my_recom_prod = Translator.t('否');
				}
					
				thisHtml += '<tr>'+
								'<td>'+this.track_no+'</td>'+
								'<td>'+this.role_name+my_role_name+'<input type="hidden" name="match_role_tracker" value="'+this.template_id+'" data-track-no="'+this.track_no+'"></td>'+
								'<td>'+this.platform+':'+this.order_id+'</td>'+
								'<td>'+this.nation+'</td>'+
								'<td>'+my_recom_prod+'</td>'+
								'<td>'+Translator.t('匹配成功')+'</td>'+
								'<td><a onclick="StationLetter.showPreviewBoxByObj(this)">'+Translator.t('预览')+'</a></td>'+
							'</tr>';
			});
			thisHtml += '</table>';
		}

		if (StationLetter.MatchResult['unmatch_data'] != undefined && StationLetter.MatchResult['unmatch_data'].length >0){
			thisHtml += '<p>'+Translator.t('匹配模板失败，请手动指定使用模板')+'</p>';
			thisHtml += '<table class="table">'+
						'<thead>'+
							'<tr>'+
								'<th>'+Translator.t('物流号')+'</th>'+
								'<th>'+Translator.t('匹配内容模板')+'</th>'+
								'<th>'+Translator.t('订单号')+'</th>'+
								'<th>'+Translator.t('递送国家')+'</th>'+
								'<th>'+Translator.t('跟踪连接')+'</th>'+
								'<th>'+Translator.t('匹配状态')+'</th>'+
								'<th>'+Translator.t('操作')+'</th>'+
							'</tr>'+'</thead>';
			$.each(StationLetter.MatchResult['unmatch_data'],function(){
					thisHtml += '<tr>'+
								'<td>'+this.track_no+'</td>'+
								'<td>'+
									'<select name="unmatch_template" data-track-no="'+this.track_no+'">'+
										'<option value="-1">'+Translator.t("未指定")+'</option>';
										
										$.each(StationLetter.templateList,function(){
											thisHtml += '<option value="'+this.id+'">'+Translator.t(this.name)+'</option>';
										});
										
										
										
					thisHtml += '</select>'+
								'</td>'+
								'<td>'+this.platform+':'+this.order_id+'</td>'+
								'<td>'+this.nation+'</td>'+
								'<td data-recom-prod="">'+Translator.t('否')+'</td>'+
								'<td>'+Translator.t('匹配失败')+'</td>'+
								'<td><a onclick="StationLetter.showPreviewBoxByObj(this)">'+Translator.t('预览')+'</a></td>'+
							'</tr>'
			});
			thisHtml += '</table>';
		}
					
		thisHtml +='</div>';
		
		$('div[name=div_auto_template]').html(thisHtml);
	},
	
	refreshRoleMatching:function(){
		$.ajax({
			type: "POST",
				dataType: 'json',
				url:'/tracking/tracking/refresh-match-role', 
				data: {track_no :$('#track_no_list').val() ,is_decode : true},
				success: function (result) {
					StationLetter.MatchResult = result;
					StationLetter.setMatchRoleHtml();
				},
				error:function(){}
		});
	},
	
	cancelFailureMessage:function(obj,message_id,order_id,track_no){
		bootbox.confirm(
				Translator.t("确定撤销发送？"),
				function(r){
					if (! r) return;
					$.showLoading();
					$.ajax({
						type: "POST",
						dataType: 'json',
						url: '/tracking/tracking/cancel-failure-message',
						data: {message_id:message_id,order_id:order_id,track_no:track_no} , 
						success: function (result) {
							//$.hideLoading();
							if (result.success){
								$.hideLoading();
								$(obj).parents('div[class="letter-history-list-row"]').remove();
							}else{
								$.hideLoading();
								bootbox.alert(result.error);
							}
						},
						error :function () {
							$.hideLoading();
							bootbox.alert("网络错误");
						}
					});
				}
			);
	},
	
	
}