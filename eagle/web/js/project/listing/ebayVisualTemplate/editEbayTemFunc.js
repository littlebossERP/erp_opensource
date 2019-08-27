
function changeImages(a, b) {
        return false;
    }

function shop_name(){ // shop name font , postion setting 
		$('.shopsubnameaddon').html($('#shop_name_sub_text').val());
		$('.shopnameaddon').html($('#shop_name_text').val());
		$('.shopnameaddon').css({
		   'color': findcolor($('#shop_name_text_color').val())
		});
		$('.shopnameaddon').css({
			'font-family': $('#shop_name_text_style').val()
		});
		$('.shopnameaddon').css({
			'font-size': $('#shop_name_text_size').val()+'px'
		});
		$('.shopsubnameaddon').css({
		   'color': findcolor($('#shop_name_sub_text_color').val())
		});
		$('.shopsubnameaddon').css({
			'font-family': $('#shop_name_sub_text_style').val()
		});
		$('.shopsubnameaddon').css({
			'font-size': $('#shop_name_sub_text_size').val()+'px'
		});
		 $('.shopnameaddon').css({
            'top': parseInt($('#shop_name_text_top').val()) +'px'
        });
        $('.shopnameaddon').css({
            'left': parseInt($('#shop_name_text_left').val()) +'px'
        });
		 $('.shopsubnameaddon').css({
            'top':  parseInt($('#shop_name_sub_text_top').val()) +'px'
        });
        $('.shopsubnameaddon').css({
            'left':  parseInt($('#shop_name_sub_text_left').val()) +'px'
        });
		
		$('.mpicbanner .shopsubnameaddon').css({
            'left':  '2px'
        });
		$('.mpicbanner .shopsubnameaddon').css({
            'top':  'inherit'
        });
		var radio = 430 / 1080;
		$('.mpicbanner .shopsubnameaddon').css({
            'bottom':  '8px'
        });
		$('.mpicbanner .shopnameaddon').css({
            'top':  parseInt($('#shop_name_text_top').val() * radio) +'px'
        });
		$('.mpicbanner .shopnameaddon').css({
            'left': parseInt($('#shop_name_text_left').val() * radio) +'px'
        });
		$('.mpicbanner .shopnameaddon').css({
            'font-size':  parseInt($('#shop_name_text_size').val() * radio)+'px'
        });
	}

    var F_S_Tags = [
        "Arial",
        "Times",
        "Andale Mono",
        "Comic Sans",
        "Impact",
        "Squada One",
        "Germania One",
        "Old Standard TT",
        "Orbitron",
        "Tulpen One",
		"Covered By Your Grace",
        "Kenia",
        "UnifrakturMaguntia",
        "Devonshire",
        "Alex Brush",
		"Tangerine",
        "Snowburst One",
        "Griffy",
        "Bangers",
        "Frijole"	
    ];

function displayPage(e) {
    var current = this.parentNode.getAttribute("data-current");
    //remove class of activetabheader and hide old contents
    document.getElementById("tabHeader_" + current).removeAttribute("class");
    document.getElementById("tabHeader_" + current).setAttribute("class","tabHeaderi");
    
	document.getElementById("tabpage_" + current).style.display = "none";
    var ident = this.id.split("_")[1];
    //add class of activetabheader to new active tab and show contents
    this.setAttribute("class", "tabActiveHeader");
    document.getElementById("tabpage_" + ident).style.display = "block";
    this.parentNode.setAttribute("data-current", ident);
    //$('#policy_html').css("background-color", findcolor($('input[name=eb_tp_clr_infobox_background]').val()));
    $('#tabs > ul > li').css("background-color", findcolor($('input[name=eb_tp_tab_Header_color]').val()));
    $('#tabs > ul > li').css("color", findcolor($('input[name=eb_tp_tab_Header_font]').val()));
    $('#tabs > ul > li.tabActiveHeader').css("background-color", findcolor($('input[name=eb_tp_tab_Header_selected]').val()));
//    $('#tool_tab').tabs('select', 0);
    var offset = $(this).offset();
	$('#bigb').show("fast");	
}

$(document).on('click', ".goCancel" , function () {
        $(".tooltips").hide('fast');  
		})

function FS_Auto() {
        $(".FS_Auto").autocomplete({
            source: F_S_Tags,
            minLength: 0,
			select: function (event, ui) {
				
				 var reportname = ui.item.value
				var thelinks = $('a.large:contains("' + reportname + '")').filter(
            function (i) { return (this.text === reportname) })
				$(this).change();
            }
        }).each(function () {
            $(this).data("autocomplete")._renderItem = function (ul, item) {
                return $("<li></li>").data("item.autocomplete", item).append(
                    "<a style='font-family:" + item.value + "'><strong>" + item.value + " </strong></a>")
                    .appendTo(ul);
            };
        });

    }
	function getImgSize(imgSrc) { 
		var newImg = new Image(); 
		newImg.src = imgSrc; 
		var width = newImg.width; 
		return width;
	} 



	function set_array(){
		arrayeditor = new Array;
		$(".editor").each(function () {
			arrayeditor.push(CKEDITOR.instances[$(this).attr('id')].getData().trim());
		});
		return arrayeditor;
	}
	function infochange(){		// side bar font background .etc setting
		if($('#infobox_bkgd_type').val() == 'Color' ){
			$('.layoutborder').css("background-image", "none");
			$('.layoutborder').css({'background-color': findcolor($('#infobox_bkgd_color').val())});
		}else{
			$('.layoutborder').css("background-image", "url(" + $('#infobox_bkgd_pattern').val() + ")");
		}
			
		$('.pbp').css('border', "0px solid #7ced04" );
		
		$('.gbox').css({
			'color': findcolor($('#text_fontcolor').val())
		});
		
		$('.Cat-List').css({
			'color': findcolor($('#text_fontcolor').val())
		})
		$('.navitemitem').css({
			'color': findcolor($('#text_fontcolor').val())
		})
		$('.Cat_gbox').css({
			'color': findcolor($('#text_fontcolor').val())
		})
		$('.gboxout').css({
		   'color': findcolor($('#title_fontColor').val())
		});
		$('.gboxout').css({
			'font-family': $('#title_fontStyle').val()
		});
		$('.gboxout').css({
			'font-size': $('#title_fontSize').val()+'px'
		});
		
		$('.gbox').css({
			'font-size': $('#text_fontsize').val() + 'px'
		});
		$('.gbox').css({
			'font-family': $('#text_fontstyle').val()
		});
	   $('.Cat-List').css({
			'font-family': $('#text_fontstyle').val()
		})
		$('.Cat-List').css({
			'font-size': $('#text_fontsize').val()+'px'
		})
		if($('#title_bkgd_type').val() == 'Pattern'){
			$('#title_bkgd_type_c').hide();
			$('#title_bkgd_type_p').show();
			$('.gboxout').css("background-image", "url('"+$('#title_bkgd_pattern').val()+"')");
		}else{
			$('#title_bkgd_type_p').hide();
			$('#title_bkgd_type_c').show();
			$('.gboxout').css("background-image", "none");
			$('.gboxout').css({
				'background-color': findcolor($('#title_bkgd_color').val())
			});
		}
		
		if($('#cat_bkgd_type').val() == 'Pattern'){
			$('#cat_bkgd_type_c').hide();
			$('#cat_bkgd_type_p').show();
			$('.Cat_List').css("background-image", "url('"+$('#cat_bkgd_pattern').val()+"')");
		}else{
			$('#cat_bkgd_type_p').hide();
			$('#cat_bkgd_type_c').show();
			$('.Cat_List').css("background-image", "none");
			$('.Cat_List').css({
				'background-color': findcolor($('#cat_bkgd_color').val())
			});
			
		}
		if($('#cat_bkgd_type1').val() == 'Pattern'){
			$('#cat_bkgd_type_c1').hide();
			$('#cat_bkgd_type_p1').show();
			$('.Cat_List').css("background-image", "url('"+$('#cat_overbkgd_pattern').val()+"')");
		}else{
			$('#cat_bkgd_type_p1').hide();
			$('#cat_bkgd_type_c1').show();
			$('.Cat_List').css("background-image", "none");
			$('.Cat_List').css({
				'background-color': findcolor($('#cat_overbkgd_color').val())
			});
			
		}
		$(".catbutton").mouseover(function(){
	            	$('.catbutton').css({
	                        'background-color': findcolor($('#btn_overcolor').val())
	                    });
	            });
		$(".catbutton").mouseout(function(){
	            	$('.catbutton').css({
	                        'background-color': findcolor($('#btn_bkgdcolor').val())
	                    });
	            });
		$(".Cat_List").mouseover(function(){
	            	$('.Cat_List').css({
	                        'background-color': findcolor($('#cat_overbkgd_color').val())
	                    });
	            });
		$(".Cat_List").mouseout(function(){
	            	$('.Cat_List').css({
	                        'background-color': findcolor($('#cat_bkgd_color').val())
	                    });
	            });
		$('.layoutborder').css('border', $('#cat_layoutborder_size').val() + "px " + $('#cat_layoutborder_style').val() + " " + findcolor($('input[name=cat_layoutborder_color]').val()));
			$('.catbutton').css({
				'background-color': findcolor($('#btn_bkgdcolor').val())
			});
			$('.catbutton:hover').css({
				'background-color': findcolor($('#btn_overcolor').val())
			});
			$('.catbutton').css({
				'color': findcolor($('#btn_textcolor').val())
			});
			$('.catbutton').css({
				'font-size': $('#btn_textsize').val()+'px'
			});
			$('.catbutton').css({
				'font-family': $('#btn_textstyle').val()
			});
		return false;	
	}
	function change_Descriptions() {
        $(".master_PD_string").html($("#InputProductDescriptions").val());
        $(".master_Desc_string").text($("#InputDesc").val());
        $(".master_size_string").text($("#Inputsize").val());
        if ($("#InputDesc").val() == "") {
            $("#master_Desc_string2").hide("fast");
        }else{
			$("#master_Desc_string2").show("fast");
		};
        if ($("#Inputsize").val() == "") {
            $(".master_size_string2").hide("fast");
        }else{
		$(".master_size_string2").show("fast");
		};
    }
	
	
	function startchange(loadTemplateInfo) {// pc , mobile body font background .etc setting
		if(typeof(loadTemplateInfo) == "undefined")
			loadTemplateInfo = true;
		$('.desc_details').css({
			'font-size': $('#DescriptionsfontSize').val() + 'px'
		});
		
		if($('#tb_eb_CFI_style_master_body_border_style').val()=='none'){
			$('.Canvas_B_Hide').hide("fast");
		}
		if($('#eb_tp_clr_infobox_border_style').val() == 'none'){
			$('.Policy_B_hide').hide("fast");
		}
		if($('#eb_tp_mobile_border_style').val() == 'none'){
			$('.Mobile_b_hide').hide("fast");
		}
        if ($('#tb_eb_CFI_style_master_HBP').val() == 'No') {
            $('.HBP_show').show("fast");
        } else {
            $('.HBP_show').hide("fast");
        }
       
        if ($('#tb_eb_CFI_style_master_desc_list').val() == "none") {
            $('.desc_details ul').css({
                'margin-left': '0px'
            });
        } else {
            $('.desc_details ul').css({
                'margin-left': '16px'
            });
        }
        $('.desc_details ul').css({
            'list-style': $('#tb_eb_CFI_style_master_desc_list').val()
        });
        if ($('#eb_tp_mobile_desc_list').val() == "none") {
            $('#mobile_details ul').css({
                'margin-left': '0px'
            });
        } else {
            $('#mobile_details ul').css({
                'margin-left': '16px'
            });
        }
        $('#mobile_details ul').css({
            'list-style': $('#eb_tp_mobile_desc_list').val()
        });
        $('.desc_details').css({
            'margin-right': '0'
        });
        $('.desc_details').css({
            'margin-left': '0'
        });

        $('.desc_details').css({
            'text-align': $('#tb_eb_CFI_style_master_desc_details').val()
        });
        if ($('#tb_eb_CFI_style_master_desc_details').val() == 'right') {
            $('.desc_details').css({
                'margin-left': 'auto'
            });
        }
        if ($('#tb_eb_CFI_style_master_desc_details').val() == 'left') {
            $('.desc_details').css({
                'margin-right': 'auto'
            });
        }
        if ($('#tb_eb_CFI_style_master_desc_details').val() == 'center') {
            $('.desc_details').css({
                'margin': 'auto'
            });
        }

        $('.theme').css({
        	'background':$('#c_theme').val(),
        	 'font-size': $('#Title_f_size').val(),
        	 textAlign:$('#Title_f_align').val(),
        	 'font-family':$('#name_text_style option:selected').val()

        });
         $('#Attrid2 td').css({
				textAlign:$('#Text_f_align').val(),
				'font-size':$('#Text_f_size').val(),
				'border':$('#attributes_border_style').val(),
				'border-width':$('#attributes_size').val(),
				'font-family':$('#Text_f_FS option:selected').val()
		});
	      // $('.theme').css("background-image", "url(" + $('#theme').val() + ")");
		$('.smalltext').css("background-image","url(" + $('#attr_b_b').val() + ")");
		$('.littletext').css("background-image","url(" + $('#attr_b2_b').val() + ")");		
        //$('.theme').css("color", findcolor($('input[name=Mainbody_background]').val()));
	                     //$('.theme').css("background", findcolor($('input[name=title_background]').val()));
		$('.littletext').css("background", findcolor($('input[name=b1_Mainbody_background]').val()));
		$('.smalltext').css("background", findcolor($('input[name=b2_Mainbody_background]').val()));
		$('#Attrid2 td').css("border-color", findcolor($('input[name=border_Mainbody_background]').val()));
        $('.subtitle').css("color", findcolor($('input[name=eb_tp_clr_Title]').val()));
		$('#mobile_subtitle').css("color", findcolor($('input[name=eb_tp_clr_Title_mobile]').val()));
        $('#desc_header').css("color", findcolor($('input[name=eb_tp_clr_Description_header]').val()));
		$('#mobilebox').css({
            'border': $('#tb_eb_CFI_style_master_body_size').val() + 'px' + ' ' + $('#tb_eb_CFI_style_master_body_border_style').val() + ' ' + findcolor($('input[name=eb_tp_clr_Mainbody_border]').val())
        });
        $('.desc_details').css("color", findcolor($('input[name=eb_tp_clr_Description_details]').val()));
        $('.desc_details').css("color", findcolor($('input[name=eb_tp_clr_Description_details]').val()));
        $('#smail_pic_box').css("background-color", findcolor($('input[name=eb_tp_clr_Small_photo_background]').val()));
        $('.smail_pic').css("border-color", findcolor($('input[name=eb_tp_clr_Small_photo_border]').val()));
        $('#subbody').css("background-color", findcolor($('input[name=eb_tp_clr_Mainbody_background]').val()));
        $('#subbody').css('border', $('#tb_eb_CFI_style_master_body_size').val() + "px " + $('#tb_eb_CFI_style_master_body_border_style').val() + " " + $('input[name=eb_tp_clr_Mainbody_border]').val());
        $('.subtitle').css("font-family", $('input[name=eb_tp_font_Title]').val());
		$('.subtitle').css("font-family", $('input[name=eb_tp_font_Title_mobile]').val());
        $('#desc_header').css("font-family", $('input[name=eb_tp_font_Description_header]').val());
        $('.desc_details').css("font-family", $('input[name=eb_tp_font_Description]').val());
        $('.desc_details').css("font-family", $('input[name=eb_tp_font_Description]').val());
		
		if(loadTemplateInfo){
			$('#tabHeader1').html($('input[name=sh_ch_info_Policy1_header]').val());
			$('#tabHeader2').html($('input[name=sh_ch_info_Policy2_header]').val());
			$('#tabHeader3').html($('input[name=sh_ch_info_Policy3_header]').val());
			$('#tabHeader4').html($('input[name=sh_ch_info_Policy4_header]').val());
			$('#tabHeader5').html($('input[name=sh_ch_info_Policy5_header]').val());
			$('#policy_box1_text').html(CKEDITOR.instances['sh_ch_info_Policy1']?CKEDITOR.instances['sh_ch_info_Policy1'].getData():"");
			$('#policy_box2_text').html(CKEDITOR.instances['sh_ch_info_Policy2']?CKEDITOR.instances['sh_ch_info_Policy2'].getData():"");
			$('#policy_box3_text').html(CKEDITOR.instances['sh_ch_info_Policy3']?CKEDITOR.instances['sh_ch_info_Policy3'].getData():"");
			$('#policy_box4_text').html(CKEDITOR.instances['sh_ch_info_Policy4']?CKEDITOR.instances['sh_ch_info_Policy4'].getData():"");
			$('#policy_box5_text').html(CKEDITOR.instances['sh_ch_info_Policy5']?CKEDITOR.instances['sh_ch_info_Policy5'].getData():"");
			$('#policy_bot_text').html(CKEDITOR.instances['sh_ch_info_Policybot']?CKEDITOR.instances['sh_ch_info_Policybot'].getData():"");
		}
		        
		$('#desc_header').css({
            'text-align': $('input:radio[name=tb_eb_CFI_style_master_desc_header]:checked').val()
        });
		
		var css='.Cat-List a:hover{color: '+findcolor($('#text_overcolor').val())+';text-decoration: none;}';
						 style=document.createElement('style');
							if (style.styleSheet)
								{style.styleSheet.cssText=css;}
							else 
								{style.appendChild(document.createTextNode(css));}
							document.getElementsByTagName('head')[0].appendChild(style);
		$('.gbox').css({
			'color': findcolor($('#eb_tp_cat_top_color').val())
		});
		$('.gboxout').css({
			'background-color': findcolor($('#eb_tp_cat_top_b_c').val())
		});
		$('.gboxout').css({
		   'color': findcolor($('#eb_tp_cat_top_b_color').val())
		});
		$('.lv1a').css({
			'color': findcolor($('#eb_tp_cat_lv1_color').val())
		});
		$('.lv2a').css({
			'color': findcolor($('#eb_tp_cat_lv2_color').val())
		});
		$('.gbox').css({
			'font-size': $('#eb_tp_cat_top_size').val() + 'px'
		});
		$('.lv1a').css({
			'font-size': $('#eb_tp_cat_lv1_size').val() + 'px'
		});
		$('.lv2a').css({
			'font-size': $('#eb_tp_cat_lv2_size').val() + 'px'
		});
		$('.gbox').css({
			'font-family':  $('#eb_tp_cat_top_style').val()
		});
		$('.lv1a').css({
			'font-family':  $('#eb_tp_cat_lv1_style').val()
		});
		$('.lv2a').css({
			'font-family':  $('#eb_tp_cat_lv2_style').val()	
		});
        if ($('#tb_shop_master_Setting_menu_On').val() == 'No') {
            $('#menuset').hide("fast");
            $('#menudisplay').hide("fast");
        } else {
            $('#menuset').show("fast");
            $('#menudisplay').show("fast");
        }
        if ($('#tb_eb_CFI_style_master_BP').val() == 'Pattern') {
			$('#bg_P').show("fast");
			$('#bg_C').hide("fast");
			$('#subbody').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
			$('#mobilebox').css("background-image", "url(" + $('#tb_eb_CFI_style_master_background_Pattern').val() + ")");
        } else {
			$('#bg_P').hide("fast");
			$('#bg_C').show("fast");
            $('#subbody').css("background-image", "none");
			$('#mobilebox').css("background-image", "none");
        }

        if ($('#tb_shop_master_Setting_menu_On').val() == 'No') {
            $('#menuset').hide("fast");
            $('#menudisplay').hide("fast");
        } else {
            $('#menuset').show("fast");
            $('#menudisplay').show("fast");
        }
		if($('#infobox_bkgd_type').val() == 'Color'){	
			$('#Catg_B').show();
			$('#Catg_P').hide();
		}else{
			$('#Catg_P').show();
			$('#Catg_B').hide();
		}
       
        
        $('#tabHeader_1').css("font-family", $('#eb_tp_tab_Font_style').val());
        $('#tabHeader_2').css("font-family", $('#eb_tp_tab_Font_style').val());
        $('#tabHeader_3').css("font-family", $('#eb_tp_tab_Font_style').val());
        $('#tabHeader_4').css("font-family", $('#eb_tp_tab_Font_style').val());
        $('#tabHeader_5').css("font-family", $('#eb_tp_tab_Font_style').val());
        $('.tabActiveHeader').css("font-family", $('#eb_tp_tab_Font_style').val());
        $('#tabHeader_1').css({
            'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
        });
        $('#tabHeader_2').css({
            'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
        });
        $('#tabHeader_3').css({
            'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
        });
        $('#tabHeader_4').css({
            'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
        });
        $('#tabHeader_5').css({
            'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
        });
        $('.tabActiveHeader').css({
            'font-size': $('#eb_tp_tab_Font_size').val() + 'px'
        });
        $('.cke_wysiwyg_frame').css("width", '400px');
        //$('#policy_html').css("background-color", findcolor($('input[name=eb_tp_clr_infobox_background]').val()));
        $('#tabs > ul > li').css("font-family", $('input[name=eb_tp_tab_Font_style]').val());
        $('#tabs > ul > li').css("color", findcolor($('input[name=eb_tp_tab_Header_font]').val()));
        $('#tabs > ul > li.tabActiveHeader').css("background-color", findcolor($('input[name=eb_tp_tab_Header_selected]').val()));
        if ($('#eb_tp_mobile_true').val() == '1') {
            $('#Mobile_tab').show("fast");
        } else {
            $('#Mobile_tab').hide("fast");
        }
        $('.change_text').click();
        $('.subtitle').css({
            'font-family': $('input[name=eb_tp_font_Title]').val()
        });
		 $('#mobile_subtitle').css({
            'font-family': $('input[name=eb_tp_font_Title_mobile]').val()
        });
        $('#desc_html .subtitle').css({
            'font-size': $('#eb_tp_Title_Size').val() + 'px'
        });
		$('#mobile_subtitle').css({
            'font-size': $('input[name=eb_tp_Title_Size_mobile]').val() + 'px'
        });
//        $('#tb_eb_CFI_style_master_HBP').change();

		if($('#nbynn').val() == 'On'){
			$('.nbynn').show("fast");
			$('.NBanner').show("fast");
			$('#sample2_graphic_setting_Notice_Banner').show("fast");
		}else{
			$('.nbynn').hide("fast");
			$('.NBanner').hide("fast");
			$('#NBanner').hide("fast");
			$('#sample2_graphic_setting_Notice_Banner').hide("fast");
		}
		if($('#hpynn').val() == 'On'){
			$('.hpynn').show("fast");
		}else{
			$('.hpynn').hide("fast");
		}
		if($('#vpynn').val() == 'On'){
			$('.vpynn').show("fast");
		}else{
			$('.vpynn').hide("fast");
		}
		$('#logo').attr("name", "FIN");
					
					
		$('#eb_tp_mobile_true').change();
		if($('#tb_eb_CFI_style_policy_BP').val()){
		
		if ($('#tb_eb_CFI_style_policy_BP').val() == "Color") {
            $('#Pg_P').hide("fast");
            $('#Pg_C').show("fast");
            $('.policy_box').css("background-image", "none");
            $('.policy_box').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
			$('.mobpovinfo').css("background-image", "none");
            $('.mobpovinfo').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
			$('#policy_html').css("background-image", "none");
			$('#policy_html').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
        } else {
            $('#Pg_C').hide("fast");
            $('#Pg_P').show("fast");
            $('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
			$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
			// $('.mobpovinfo').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
        }}
		change_Descriptions();	
    }
	
	function starttab() {// policy tab onclick event binding
        if (document.getElementById("policy_html") != null) {
            var container = document.getElementById("policy_html");
            var tabcon = document.getElementById("tabscontent");
            var navitem = document.getElementById("tabHeader_1");
            var ident = navitem.id.split("_")[1];
            navitem.parentNode.setAttribute("data-current", ident);
            navitem.setAttribute("class", "tabActiveHeader");
           /*
            var pages = tabcon.getElementsByTagName("div");
			for (var i = 1; i < pages.length; i++) {
				if(pages.item(i).className == "tabpage")
					pages.item(i).style.display="none";
			};
			*/							
            var tabs = container.getElementsByTagName("li");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].onclick = displayPage;
            }
            // ready_tab();
            $('.tabpage').css('border', $('#eb_tp_clr_infobox_border_size').val() + "px " + $('#eb_tp_clr_infobox_border_style').val() + " " + findcolor($('input[name=eb_tp_clr_infobox_border]').val()));
            $('.tabpage').css('border-top', "0px");

        }
        if (document.getElementById("policy_html") != null) {
           
        } else {
            $('.tardisplay').hide("fast");
        }
    }
	
	function ready_tab() {// policy content ready than trigger this to set background and $('.m_Pre_info').click(); init mobile view
        if ($('#tabpage_2').css('display') != 'none') {           
		if ($('#tb_eb_CFI_style_policy_BP').val() == "Color") {           
        } else {            
			$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
        }           
        }
		// $('.m_Pre_info').click();
		if ($('#tb_eb_CFI_style_policy_BP').val() == "Color") {
            $('#Pg_P').hide("fast");
            $('#Pg_C').show("fast");
            $('.policy_box').css("background-image", "none");
            $('.policy_box').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
			$('.mobpovinfo').css("background-image", "none");
            $('.mobpovinfo').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
			$('#policy_html').css("background-image", "none");
			$('#policy_html').css("background-color", $('input[name=eb_tp_clr_infobox_background]').val());
        } else {
            $('#Pg_C').hide("fast");
            $('#Pg_P').show("fast");
            $('.policy_box').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
			$('#policy_html').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
			// $('.mobpovinfo').css("background-image", "url(" + $('#eb_tp_policy_Pattern').val() + ")");
        }
    }
	
	function policy(isGetHtml) {
		if(typeof(isGetHtml) == "undefined")
			isGetHtml = true;
        var separator = '';
        arrayeditor = new Array;
        $(".ckeditor").each(function () {
			if($(this).attr('id')){
				arrayeditor.push(CKEDITOR.instances[$(this).attr('id')].getData());
			}
        });
        var data = {
            'allItem': $('#allItem').serializeArray(),
            'editor[]': arrayeditor,
            'find': 'policy',
			'layout': $('#layout_style_name').val()
        };
        $.ajax({
            type: 'POST',
            data: data,
            dataType: 'html',

            url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=policyView',
            success: function (data) {
				if(isGetHtml){
					$('#policy_html').html(data).ready(function () {
						$('#policy_bot_text').attr('contenteditable','true');
						$('#policy_box1_text').attr('contenteditable','true');
						$('#policy_box2_text').attr('contenteditable','true');
						$('#policy_box3_text').attr('contenteditable','true');
						$('#policy_box4_text').attr('contenteditable','true');
						$('#policy_box5_text').attr('contenteditable','true');
						$(".ckinline").each(function () {
							ckinline($(this).attr('id'));
						});
						starttab();
						ready_height()
						$('#tabs > ul > li').css("width", $('#tabsno').attr('title') + 'px');
						$('#tabs > ul > li.tabActiveHeader').css("width", $('#tabsno').attr('title') + 'px');
						$('#tabs > ul > li').css("font-family", $('input[name=eb_tp_tab_Font_style]').val());
						$('#tabs > ul > li').css("color", $('input[name=eb_tp_tab_Header_font]').val());
						$('#tabs > ul > li').css("background-color", findcolor($('input[name=eb_tp_tab_Header_color]').val()));
						$('#tabs > ul > li.tabActiveHeader').css("background-color", $('input[name=eb_tp_tab_Header_selected]').val());
					});
				}else{
					$('#policy_bot_text').attr('contenteditable','true');
					$('#policy_box1_text').attr('contenteditable','true');
					$('#policy_box2_text').attr('contenteditable','true');
					$('#policy_box3_text').attr('contenteditable','true');
					$('#policy_box4_text').attr('contenteditable','true');
					$('#policy_box5_text').attr('contenteditable','true');
					$(".ckinline").each(function () {
						ckinline($(this).attr('id'));
					});
					starttab();
					ready_height()
					$('#tabs > ul > li').css("width", $('#tabsno').attr('title') + 'px');
					$('#tabs > ul > li.tabActiveHeader').css("width", $('#tabsno').attr('title') + 'px');
					$('#tabs > ul > li').css("font-family", $('input[name=eb_tp_tab_Font_style]').val());
					$('#tabs > ul > li').css("color", $('input[name=eb_tp_tab_Header_font]').val());
					$('#tabs > ul > li').css("background-color", findcolor($('input[name=eb_tp_tab_Header_color]').val()));
					$('#tabs > ul > li.tabActiveHeader').css("background-color", $('input[name=eb_tp_tab_Header_selected]').val());

				}
            }
        });
        return false;
    }
	function findcolor(e) {
		if(e !='' && e !='#'){
        if (e) {
            var findc = e.indexOf("#");
            if (findc > -1) {
                return e;
            } else {
                return '#' + e;
            }
        }}else{
			return '';
		}
    }
	function resizeImg(img) {
		var wwi = document.getElementById('mobilebox').offsetWidth - 35;
	    img.width = wwi;
		return false;
	}
	
	
	function menu() {
		var separator = '';
		if ($('input:checkbox[name=tb_eb_CFI_style_master_menu_separator]:checked').val() == 'Yes') {
		    separator = "border-right: " + $('input[name=eb_tp_clr_Font_Color]').val() + " 1px solid ;";
		}
		var data = {
		    'allItem': $('#allItem').serializeArray(),
		    'find': 'menu',
			'layout': $('#layout_style_name').val()
		};
		//console.log(data)
		$.ajax({
		    type: 'POST',
		    data: data,
		    dataType: 'html',
		    url: global.baseUrl+'listing/ebay-template/get-partial-template?partial=menuBar',
		    success: function (data) {
		        $('#menudisplay').html(data);
		        if ($('#tb_shop_master_Setting_menu_On').val() == 'No') {
		            $('#menuset').hide("fast");
		            $('#menudisplay').hide("fast");
		        } else {
		            $('#menuset').show("fast");
		            $('#menudisplay').show("fast");
		        }
		        $('#menudisplay').css({
		            "background-image": "url(" + $('input#tb_shop_master_Setting_menu_bar').val() + ")"
		        });
		        $('#menubar').css({
		            "height": "25px"
		        });
		        $('.menurow').css({
		            "float": "left",
		            "width": "152px",
		            "position": "relative",
		            "font-size": $('#FontSize').val() + "px",
					"font-family": $('input[name=eb_tp_font_style_menurow]').val(),
					"color": findcolor($('#FontColor').val()) + "px"
		        });
		        $('.menurow a:link').css({
		            "font-family": $('input[name=eb_tp_font_style_menurow]').val(),
		            "color": '#' + $('input[name=eb_tp_clr_Font_Color]').val(),
		            "text-decoration": "none"
		        });
		        $('.menurow a:visited').css({
		            "font-family": $('input[name=eb_tp_font_style_menurow]').val(),
		            "color": findcolor($('input[name=eb_tp_clr_Font_Color]').val()),
		            "text-decoration": "none"
		        });
		        $('.menurow a:hover').css({
		            "font-family": $('input[name=eb_tp_font_style_menurow]').val(),
		            "color": findcolor($('input[name=eb_tp_clr_Font_Color]').val()),
		            "text-decoration": "none"
		        });
		        if ($('input:checkbox[name=tb_eb_CFI_style_master_menu_separator]:checked').val() == 'Yes') {
		            $('.menuright').css({
		                "border-right": findcolor($('input[name=eb_tp_clr_Font_Color]').val()) + " 1px solid"
		            });
		        } else {
		        	$('.menuright').css({
		        		"border-right": findcolor($('input[name=eb_tp_clr_Font_Color]').val()) + " 0px solid"
	                });
	            }	
		    }
		});
	}
	
	
	

	
	
	function popup(id) {
		if(document.getElementById(id) != null){ 
			popup_a = document.getElementById(id);
			popup_a.onclick = show_popup;
		}
		return false;
	}
	function hide_popup() {
		document.getElementById('popupbox').style.display='none';
		return false;
	}
	
	function show_popup() {
		popup_i_a = document.getElementById('popup_img_a');
		popup_i = document.getElementById('popup_img');
		popup_i.style.width = 'auto';
		popup_i.style.height = 'auto';
		popup_i.src = this.href;
		popup_i_a.href = this.href;
		var org_popup_i_width=popup_i.width;
		var org_popup_i_height=popup_i.height;
		
		if(popup_i.height > window.innerHeight && org_popup_i_width<=org_popup_i_height)
		{
			popup_i.style.height = window.innerHeight - 30 +'px';
			document.getElementById('ct2').style.top = '5px';
			popup_i.style.width = 'auto';
		}
		if(popup_i.width > window.innerWidth && org_popup_i_width>=org_popup_i_height)
		{		
			popup_i.style.width = window.innerWidth - 10 +'px';
			document.getElementById('ct2').style.left = '0px';
			popup_i.style.height = 'auto';
			if(window.innerHeight / 2 - popup_i.height / 2 < 400 && window.innerHeight / 2 - popup_i.height / 2 > 0){
				document.getElementById('ct2').style.top = window.innerHeight / 2 - popup_i.height / 2  + 'px';
			}else{
				document.getElementById('ct2').style.top =  '5px';
			}
			if(popup_i.height > window.innerHeight && org_popup_i_width<=org_popup_i_height && window.innerWidth > window.innerHeight)
			{
				popup_i.style.height = window.innerHeight - 30 +'px';
				popup_i.style.width = 'auto';
			}
		}
		; 
		
		document.getElementById('popupbox').style.display='block';
		return false;
	}
	function showmobile() {
		document.getElementById('subbody').style.display = 'none';
        document.getElementById('mobilebox').style.display = 'block';
		document.getElementById('showbtnm').style.display = 'none';
		document.getElementById('showbtnd').style.display = 'block';
		}
	function showdesktop() {
        document.getElementById('subbody').style.display = 'block';
		document.getElementById('mobilebox').style.display = 'none';
		document.getElementById('showbtnm').style.display = 'block';
		document.getElementById('showbtnd').style.display = 'none';
		}
		
	function gobuy(oj) {

		var fside = findsite();
		var itm = fside['itm'];
		var site = fside['site'];
		var ebay_site = fside['ebay_site'];
  
		var buy_url='http://offer.'+ebay_site+site+'/ws/eBayISAPI.dll?BinConfirm&item='+itm ;
		document.getElementById(oj).href=buy_url;
	}
	function goBid(oj) {

		var fside = findsite();
		var itm = fside['itm'];
		var site = fside['site'];
		var ebay_site = fside['ebay_site'];
	  
		var buy_url='http://offer.'+ebay_site+site+'/ws/eBayISAPI.dll?MakeBid&item='+itm ;
		document.getElementById(oj).href=buy_url;
	}	
	function create_bin() {
		var fside = findsite();
		var itm = fside['itm'];
		var site = fside['site'];
		var ebay_site = fside['ebay_site'];
		bin = document.getElementById('btn_bin');
		buy = document.getElementById('btn_buy');
		if(bin&&itm){
			var bin_url='http://cgi1.'+ebay_site+site+'/ws/eBayISAPI.dll?MakeTrack&item='+itm;
			var buy_url='http://offer.'+ebay_site+site+'/ws/eBayISAPI.dll?BinConfirm&item='+itm;
			bin.href=bin_url;
			buy.href=buy_url;
		}else{
			if(bin){ bin.style.display = 'none';}
			if(buy){buy.style.display = 'none';}
		}
	}
	function findsite(){
		var itm, ebay_site, url = location.href,
		site = location.hostname.split(/\.ebay\.|\.ebaydesc\./i)[1],
		res = url.match(/item=\d+/),
		is_sandbox=location.hostname.match(/\.sandbox\./i);
		if(is_sandbox){
			ebay_site='sandbox.ebay.';
		}else{
			ebay_site='ebay.';
		}
		if (!res){res = url.match(/\/\d+/)};
		if (!res){res = url.match(/\d{12}/)};
		if (res){itm = res[0].match(/\d+/)};
		var IDs = new Array();
		IDs['ebay_site'] = ebay_site;
		IDs['site'] = site;
		IDs['itm'] = itm;
		return IDs;
	}
	function create_qr() {	
		var qr_code_img = document.getElementById('qr_code_img');
		var qr_code_a = document.getElementById('qr_code_a');  	
		var fside = findsite();
		var itm = fside['itm'];
		var site = fside['site'];
		var ebay_site = fside['ebay_site'];
		if(qr_code_img != null){ 
		if (location.hostname.match(/\.ebaydesc\./i)) { 
			var code_url='https://chart.googleapis.com/chart?chs=173x173&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
			var code_url400='https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
		}else{
			var code_url='https://chart.googleapis.com/chart?chs=173x173&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
			var code_url400='https://chart.googleapis.com/chart?chs=400x400&cht=qr&chl=http%3A%2F%2Fwww.'+ebay_site+site+'/itm/'+itm+'&choe=UTF-8';
			}
			qr_code_a.href=code_url400;
			qr_code_img.src=code_url;
			popup('qr_code_a');
		}
	}
	function showme(id, activeHeader) {
		if("mobpovah" == activeHeader.className){
			activeHeader.removeAttribute('class');
			activeHeader.setAttribute("class","mobpovh");
		}else{
			activeHeader.removeAttribute('class');
			activeHeader.setAttribute("class","mobpovah");
		}
        var divid = document.getElementById(id);
        if (divid.style.display == 'block') {
             
            divid.style.display = 'none';        }
        else {
            
            divid.style.display = 'block';
        }
		return false;
    }
    function showme1(id, linkid) {
        var divid = document.getElementById(id);
        var toggleLink = document.getElementById(linkid);
        if (divid.style.display == 'block') {
             toggleLink.innerHTML = '&#9658;';
            divid.style.display = 'none';        
			}else{
            toggleLink.innerHTML = '&#9660;';
            divid.style.display = 'block';
        }
		return false;
    } 
	 function showme2(id, linkid) {
        var divid = document.getElementById(id);
        var toggleLink = document.getElementById(linkid);
        if (divid.style.display == 'block') {
             toggleLink.innerHTML = '&#9655;';
            divid.style.display = 'none';        }
        else {
            toggleLink.innerHTML = '&#9661;';
            divid.style.display = 'block';
        }
		return false;
    }  

	function chipad(id,num) {
		if(document.getElementById(id)){
		document.getElementById(id).style.width = num+'px';
		}
		return false;
	}
	
	function ckinline(e) {
        if ($('#' + e)) {
            CKEDITOR.inline(e);
        }
    }
	
	function ready_height() {
        if ($('#tabHeader1').height() != 12 || $('#tabHeader2').height() != 12 || $('#tabHeader3').height() != 12 || $('#tabHeader4').height() != 12 || $('#tabHeader5').height() != 12) {
			$('#tabHeader1').height(12);
			$('#tabHeader2').height(12);
			$('#tabHeader3').height(12);
			$('#tabHeader4').height(12);
			$('#tabHeader5').height(12);
		   
        }
    }
	
	function genFinalhtml(){
		arrayeditor = new Array;
		$(".editor").each(function () {
			arrayeditor.push(CKEDITOR.instances[$(this).attr('id')].getData().trim());
		});

		for(var i=1; i <= arrayeditor.length; i++ ){
			if(i < 6)
				$('#sh_ch_info_Policy'+(i)).val(arrayeditor[i-1]);
			else
				$('#sh_ch_info_Policybot').val(arrayeditor[i-1]);
		}
		var data = {
			'allItem': $('#allItem').serializeArray(),
			'sortable': $('#sortablef').serializeArray(),	
			'msortable': $('#msortablef').serializeArray(),
			'dsortable': $('#dsortablef').serializeArray(),					
			'editor[]': arrayeditor,					
			'infodetclass':$(".infodetclass").serializeArray(),
			'template_id': 0,
		};
		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'html',
			url: global.baseUrl+'listing/ebay-template/get-final-template-view',
			success: function (data) {
				
				// window.location =  global.baseUrl+'listing/ebay-template/edit?template_id='+data;				
			}
		});
	}
	//END
	
	$(document).on('click', '.showall' ,function () {
        // showall makes notice banner unable to show
		$(".preview").each(function () {
            var str = $("#" + this.title).val();
            var HWstr = $("#" + this.title).val();
            if (str) {
                $("#sample2_" + this.title).show("fast");
                if (str.search("http") >= 0) {
                    if (str.search("swf") >= 0) {
                        var H = $('#'+this.title + '_HW .HW_H').val() + 'PX';
                        var W = $('#'+this.title + '_HW .HW_W').val() + 'PX';
                        $("#sample2_" + this.title).empty();
                        $("#sample2_" + this.title).flash({
                            src: $("#" + this.title).val(),
                            'width': W,
                            'height': H
                        }, {
                            version: '6.0.65'
                        });

                    } else {
                        $("#sample_" + this.title).attr("src", $("#" + this.title).val());
						var rlink =  $("#" + this.title).val();
                    }
                } else {

                    if (str.search("http") >= 0) {

                        $("#sample_" + this.title).attr("src", $("#" + this.name).val() + $("#" + this.title).val());
						var rlink =  $("#" + this.name).val() + $("#" + this.title).val();
                    } else {


                        $("#sample_" + this.title).attr("src", $("#BasedPath").val() + $("#" + this.name).val() + $("#" + this.title).val());
						var rlink =  $("#BasedPath").val() + $("#" + this.name).val() + $("#" + this.title).val();
                    }
                }
            } else {
                $("#sample2_" + this.title).hide("fast");
            }
        });
        $(".img_border").attr("src", global.baseUrl+'images/ebay/template/sample/1.jpg');
        $(".sm0").attr("src", global.baseUrl+'images/ebay/template/sample/1.jpg');
        $(".sm1").attr("src", global.baseUrl+'images/ebay/template/sample/2.jpg');
        $(".sm2").attr("src", global.baseUrl+'images/ebay/template/sample/3.jpg');
        $(".sm3").attr("src", global.baseUrl+'images/ebay/template/sample/4.jpg');
        $(".sm4").attr("src", global.baseUrl+'images/ebay/template/sample/5.jpg');
        $(".sm5").attr("src", global.baseUrl+'images/ebay/template/sample/1.jpg');	
		$('#MD0').mousedown(function() {
		changeImages2(global.baseUrl+'images/ebay/template/sample/1.jpg');
		});
		$('#MD1').mousedown(function() {
		changeImages2(global.baseUrl+'images/ebay/template/sample/2.jpg');
		});
		$('#MD2').mousedown(function() {
		changeImages2(global.baseUrl+'images/ebay/template/sample/3.jpg');
		});
		$('#MD3').mousedown(function() {
		changeImages2(global.baseUrl+'images/ebay/template/sample/4.jpg');
		});
		$('#MD4').mousedown(function() {
		changeImages2(global.baseUrl+'images/ebay/template/sample/5.jpg');
		});
		$('#MD5').mousedown(function() {
		changeImages2(global.baseUrl+'images/ebay/template/sample/1.jpg');
		});
        $("#sample_poster_img").attr("src", global.baseUrl+'images/ebay/template/Poster_Sample1.jpg');
        $("#sample_poster_img2").attr("src",global.baseUrl+'images/ebay/template/Poster_Sample2.jpg');
        // $('.menupreview').click();
        startchange();
		$('.Pre_info').click();
		menu();
    });
	
		
	var isiPad = navigator.userAgent.match(/iPad/i) != null;
	if(isiPad){
		if(document.getElementById('left1080')){
			document.getElementById('left1080').style.display='none';
		}
		chipad('subbody','850');
		chipad('menudisplay','850');
		chipad('usegraphic_setting_Shop_Name_Banner','850');
		chipad('policy_html','800');
		// chipad('tabContainer','800');
		chipad('feedback_html','800');
		chipad('policy_box1','800');
		chipad('policy_box2','800');
		chipad('policy_box3','800');
		chipad('policy_box4','800');
		chipad('policy_box5','800');
	}