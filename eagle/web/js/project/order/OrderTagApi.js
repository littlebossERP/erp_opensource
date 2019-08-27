/**
+------------------------------------------------------------------------------
 * order外的模块操作订单tag的专用js
+------------------------------------------------------------------------------
 * @category	js/project
 * @package		order
 * @subpackage  Exception
 * @author		lzhl <zhiliang.lu@witsion.com> 2016-01-11 eagle 2.0
 * @version		1.0
+------------------------------------------------------------------------------
 */
if (typeof OrderTagApi === 'undefined')
	OrderTagApi = new Object();
OrderTagApi = {
	TagClassList : '',
	TagList : '',
	SelectTagData : '',
	isChange : false,
	init : function () {
		$('.btn_order_tag_qtip').each(function () {
			$(this).click(function(){
				OrderTagApi.initQtipEntryBtn(this);
			});
		});
	},

	initAllQtipEntryBtn : function (order_id) {
		var newBtn = $('.btn_order_tag_qtip[data-order-id=' + order_id + ']');
		OrderTagApi.initQtipEntryBtn(newBtn);
	},

	initQtipEntryBtn : function (obj) {
		var btnObj = $(obj);
		var order_id = $(obj).data('order-id');
		$.ajax({
			type : "GET",
			dataType : 'json',
			url : '/order/order/get-one-tag-info?order_id=' + order_id, 
			success : function (content) {
				var html = OrderTagApi.fillTagContentHtml(order_id, content);
				bootbox.dialog({
					title:"订单标签编辑",
					closeButton: false,
					className: "order_tag_dialog_"+order_id, 
					message:html,
					buttons : {
						Ok : {
							label : Translator.t("OK"),
							className : "btn-success",
							callback : function() {
								OrderTagApi.updateOrderTagInfo(order_id);
							}
						}
					},
				});	
				OrderTagApi.initQtipBtn(order_id);
			}
		});
		
	},

	initQtipBtn : function (order_id) {
		$('input[name=tag_order_id]').val(order_id);

		$('.span-click-btn-orderTag').click(function () {
			inputObj = $(this).prev('input[name=select_tag_name]');
			if (inputObj.val().length == 0) {
				bootbox.alert(Translator.t('请输入标签'));

				return;
			}
			obj = $(this).children('span');
			isAdd = obj.hasClass('glyphicon-plus');
			if (isAdd) {
				obj.removeClass('glyphicon-plus');
				obj.addClass('glyphicon-remove');
				inputObj.prop('readonly', 'readonly');
				OrderTagApi.addTag(obj);
			} else {
				obj.removeClass('glyphicon-remove');
				obj.addClass('glyphicon-plus');
				OrderTagApi.delTag(obj);
			}
		});
	},

	fillTagContentHtml : function (order_id, data) {
		var Html = '';

		var select_html = "";
		var rest_html = "";
		var tag_mapping = new Object();
		var existColor = new Object();

		$.each(data.all_tag, function () {
			tag_mapping[this.tag_id] = this;
		});

		$.each(data.all_select_tag_id, function (i, value) {

			ColorClassName = tag_mapping[value].classname;
			TagName = tag_mapping[value].tag_name;
			BtnClassName = "glyphicon-remove";
			ReadonlyStr = '  readonly="readonly" ';
			color = tag_mapping[value].color;
			select_html += OrderTagApi.generateHtml(ColorClassName, TagName, BtnClassName, ReadonlyStr, color);

			existColor[color] = 1;

		});

		$.each(data.all_tag, function () {
			if (existColor[this.color] != '1' && this.color != 'gray') {
				ColorClassName = this.classname;
				TagName = this.tag_name;
				BtnClassName = "glyphicon-plus";
				ReadonlyStr = '  readonly="readonly" ';
				color = this.color;
				rest_html += OrderTagApi.generateHtml(ColorClassName, TagName, BtnClassName, ReadonlyStr, color);

				existColor[color] = 1;
			}
		});

		for (var color in this.TagClassList) {
			if (color == 'gray')
				continue;
			if (existColor[color] == '1')
				continue;

			ColorClassName = this.TagClassList[color];
			TagName = "";
			BtnClassName = "glyphicon-plus";
			ReadonlyStr = ' ';
			rest_html += OrderTagApi.generateHtml(ColorClassName, TagName, BtnClassName, ReadonlyStr, color);

		}

		Html = '<div name="div_select_tag" class="div_select_tag">' +
			'<input name="tag_order_id" type="hidden" readonly="readonly" value="' + order_id + '"/>' +
			select_html +
			'</div>' +
			'<div name="div_new_tag" class="div_new_tag">' +
			rest_html +
			'</div>';
		return Html;
	},
	
	generateHtml : function (ColorClassName, TagName, BtnClassName, ReadonlyStr, color) {

		return '<div class="input-group">' +
		'<span class="input-group-addon"><span class="' + ColorClassName + '"></span></span>' +
		'<input name="select_tag_name" type="text" class="form-control" placeholder="" aria-describedby="basic-addon1" value="' + TagName + '" ' + ReadonlyStr + ' data-color="' + color + '">' +
		'<span class="input-group-addon span-click-btn-orderTag"><span class="glyphicon ' + BtnClassName + '" aria-hidden="true"></span></span>' +
		'</div>';
	},

	addTag : function (obj) {
		thisobj = $(obj);
		$('div[name=div_select_tag]').append(thisobj.parents('.input-group'));

		tracking_id = $('div[name=div_select_tag] input[name=tag_order_id]').val();
		tag_name = thisobj.parent().prev('input[name=select_tag_name]').val();
		operation = 'add';
		color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
		this.saveTag(tracking_id, tag_name, operation, color);
	},

	delTag : function (obj) {
		thisobj = $(obj);

		$('div[name=div_new_tag]').append(thisobj.parents('.input-group'));
		tracking_id = $('div[name=div_select_tag] input[name=tag_order_id]').val();
		tag_name = thisobj.parent().prev('input[name=select_tag_name]').val();
		operation = 'del';
		color = thisobj.parent().prev('input[name=select_tag_name]').data('color');
		this.saveTag(tracking_id, tag_name, operation, color);
	},

	saveTag : function (order_id, tag_name, operation, color) {
		$.ajax({
			type : "POST",
			dataType : 'json',
			url : '/order/order/save-one-tag',
			data : {
				order_id : order_id,
				tag_name : tag_name,
				operation : operation,
				color : color
			},
			success : function (result) {
				if (result.success == false) {
					bootbox.alert(result.message);
				}
				return true;
			},
			error : function () {
				return false;
			}
		});
		OrderTagApi.isChange = true;
	},

	updateOrderTagInfo : function (order_id) {
		$.ajax({
			type : "GET",
			// dataType : 'json',
			url : '/order/order/update-order-tag-html?order_id=' + order_id, // Use href attribute as URL
			success : function (content) {
				$("#order_tag_list_"+order_id).html(content);
				OrderTagApi.init();
			}
		});
	},
}


//回复消息
function SentMessge(ticket_id){
//		location.href='/inside-letter/sent-message?session_id='+session_id+'&message='+$(".message-area").val();
	if($(".message-area").val() == ''){
		   bootbox.alert('发送消息不能为空！');
            return;
	}
	var jude = 1;//检查是否存在变量没有替换
	var check_array = ["收件人名称","收件人国家","收件人地址，包含城市","收件人邮编","收件人电话","平台订单号","订单金额","订单物品列表(商品sku，名称，数量，单价)","包裹物流号","包裹递送物流商","买家查看包裹追踪及商品推荐链接"];
	var checked = [];
	var template_message = $('.message-area').val(); 
	for(var i in check_array){
		var re = new RegExp(check_array[i],"g");
		var arr = template_message.match(re);
		if(arr != null){
			checked.push(check_array[i]);
		}
	}
	if(checked.length != 0){
		if(confirm("存在没替换的变量，是否继续发送？")){
			jude = 1;
		}else{
			jude = 0;
		}
	}
	if(jude){
		$.ajax({
			type:"GET",
//	 		dataType:"json",
			url:'/message/all-customer/sent-message?ticket_id='+ticket_id+'&message='+$(".message-area").val(),
			success:function(data){
				var content=$(".all-chat");
	 			content.prepend(data);
//				content.append(data);
				$(".message-area").val("");
				$(".detail_letter .modal-body").scrollTop($(".detail_letter .modal-body").height());
			}
		});
	}
	
}


function addDesc(order_id){
	var dom_id="#add_"+order_id+"_desc";
	$(dom_id).css("display","block");
	var btn_add = "#addDesc_"+order_id+"_btn";
	var btn_save = "#saveDesc_"+order_id+"_btn";
	var btn_cancle = "#cancleAddDesc_"+order_id+"_btn";
	$(btn_add).css("display","none");
	$(btn_save).css({"display":"block","float":"left"});
	$(btn_cancle).css({"display":"block","float":"left"});
}
function cancleAddDesc(order_id){
	var dom_id="#add_"+order_id+"_desc";
	$(dom_id).css("display","none");
	var btn_add = "#addDesc_"+order_id+"_btn";
	var btn_save = "#saveDesc_"+order_id+"_btn";
	var btn_cancle = "#cancleAddDesc_"+order_id+"_btn";
	$(btn_add).css("display","block");
	$(btn_save).css("display","none");
	$(btn_cancle).css("display","none");
}

function saveDesc(order_id){
	var dom_id="#add_"+order_id+"_desc";
	var add_content = $(dom_id).val();
	$.showLoading();
	$.ajax({
		type: "POST",
		dataType: 'json',
		url: '/message/all-customer/add-order-desc',
		data:{order_id:order_id,desc:add_content},
		success:function(data){
			$.hideLoading();
			if(data.result){
				var desc_span = "#desc_content_"+order_id;
				$(desc_span).html(data.desc);
				var dom_id="#add_"+order_id+"_desc";
				$(dom_id).val('');
				$(dom_id).css("display","none");
				var btn_add = "#addDesc_"+order_id+"_btn";
				var btn_save = "#saveDesc_"+order_id+"_btn";
				var btn_cancle = "#cancleAddDesc_"+order_id+"_btn";
				$(btn_add).css("display","block");
				$(btn_save).css("display","none");
				$(btn_cancle).css("display","none");
			}else{
				bootbox.alert(data.message);
				return false;
			}
		},
		error:function(){
			$.hideLoading();
			bootbox.alert('数据传输有误，保存失败！');
			return false;
		}
	});
}