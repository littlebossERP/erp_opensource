var sku={
	setsku:function(){
		idstr = [];
		$('input[name="order_id[]"]:checked').each(function(){
			idstr.push($(this).val());
		});
		if(idstr==""){
	        bootbox.alert("请选择您要操作的项");return false;
	    }
		//$.showLoading();
		$.ajax({
			type: "GET",
			url:'/order/aliexpressorder/setsku', 
			data:{order_id:idstr},
			success: function (result) {
				//$.hideLoading();
				bootbox.dialog({
					className : "box",
					title: Translator.t("查看商品"),//
					message: result,
					buttons:{
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});
				//productList.list.initPhotosSetting();
				//$('.lnk-del-img').css('display','none');
				return true;
				
			},
			error :function () {
				return false;
			}
		});	
	},
	setmessage:function(){
		idstr = [];
		$('input[name="order_id[]"]:checked').each(function(){
			idstr.push($(this).val());
		});
		if(idstr==""){
	        bootbox.alert("请选择您要操作的项");return false;
	    }
		$.ajax({
			type: "POST",
			url:'/order/order/generateproduct', 
			data:{order_id:idstr},
			success: function (result) {
				$(".check:checked").each(function(){
					var tr = $(this).parents("tr");
					var value=Number(tr.children("td").children('.value1').val());
					if($('.cul').val()=='a'){			//加
						value+=Number($('.value').val());
						value = value.toFixed(2);
						//console.log(value);
					}
					if($('.cul').val()=='b'){				//减
						value-=Number($('.value').val());	
						value = value.toFixed(2);
						//console.log(value);
					}
					if($('.cul').val()=='c'){					//乘
						value*=$('.value').val();
						value = value.toFixed(2);
						//console.log(value);
					}
					if($('.cul').val()=='d'){											//除
						value/=$('.value').val();
						value = value.toFixed(2);
						//console.log(value);
					}
					tr.children("td").children('.sname1').val($('.sname').val());
					tr.children("td").children('.sename1').val($('.sename').val());
					tr.children("td").children('.name1').val($('.name').val());
					tr.children("td").children('.ename1').val($('.ename').val());
					tr.children("td").children('.value1').val(value);
					tr.children("td").children('.weight1').val($('.weight').val());
					tr.children("td").children('.ele1').val($('.ele').val());
				});
				return true;		
			},
			error :function () {
				return false;
			}
		});	
	},
	
	addOtherAttrHtml : function (obj) {
		var tr = $(obj).parents("tr");
		var thisObj = tr.children("td").children('.attr');
		//console.log(tr);
		var addContent = $($('#other_attribute').html());
		thisObj.append(addContent);
		//$("#catalog-product-list-attributes-panel input[name*=other_attr_]").formValidation({tipPosition:'left',validType:['trim','prod_field'],required:true});
		
	},
	
	delete_form_group:function(obj){
		$(obj).parents(".form-group").remove();
	},
	showDetailView:function(obj){
		if ($(obj).hasClass('glyphicon-plus')){
			$(obj).removeClass('glyphicon-plus');
			$(obj).addClass('glyphicon-minus');
		}else if($(obj).hasClass('glyphicon-minus')){
			$(obj).removeClass('glyphicon-minus');
			$(obj).addClass('glyphicon-plus');
		}
		
		$(obj).parents('tr').next('tr').find('div').slideToggle("normal",function(){
			
		});
		
	} , 
	
}