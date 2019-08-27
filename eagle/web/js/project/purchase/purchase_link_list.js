// 显示和隐藏按钮
//    <a class=purchase_link_list_show> 内容</a>
$(function () {
    var purchase_link_list_div = $("<div class='purchase_link_list_div' style='position:absolute;width:60px;z-index: 100;height:25px;text-align: center; margin-top: -5px; clear: both; '></div>");
	var purchase_link_list_wrap=$("<div class='purchase_link_list_wrap' style='position:relative;display: inline'></div>");
    $('.purchase_link_list_show').css('display','inline-block');
    
    var purchase_link_list_ul = $('<ul class="purchase_link_list_ul" style="top: 100%;left: 0;z-index: 1000;float: left;min-width: 160px;padding: 5px 10px;margin: 2px 0 0;font-size: 14px;text-align: left;list-style: none;background-color: #fff;-webkit-background-clip: padding-box;background-clip: padding-box;border: 1px solid #ccc;border: 1px solid rgba(0, 0, 0, .15);-webkit-box-shadow: 0 6px 12px rgba(0, 0, 0, .175);"></ul>');

    $(document).on('mouseover', '.purchase_link_list_show', function () {
		//采购链接列表
		var li_list = '';
		if($(this).attr('purchase_link_json') != undefined){
			var purchase_link_list = $.parseJSON($(this).attr('purchase_link_json'));
			for(var link in purchase_link_list){
				if(purchase_link_list[link]['purchase_link'] != undefined && purchase_link_list[link]['supplier_name'] != undefined){
					li_list += '<li><a target="_blank" href="'+ purchase_link_list[link]['purchase_link'] +'" >'+ purchase_link_list[link]['supplier_name'] +'</a></li>';
				}
			}
			if(li_list != ''){
				purchase_link_list_div.append(purchase_link_list_ul);
				if($(this).closest($(".purchase_link_list_wrap")).length<1){
				    $(this).wrap(purchase_link_list_wrap);
				}
				$(this).closest($(".purchase_link_list_wrap")).append(purchase_link_list_div);
				
				purchase_link_list_div.find('.purchase_link_list_ul').html('<li>所有采购链接：</li>'+ li_list);
				$(this).next(purchase_link_list_div).css("display", "inline-block");
			}
		}
	});
    
    $(document).on('mouseover', '.purchase_link_list_div', function () {
       $(this).css("display", "inline-block");
    });
    $(document).on('mouseout', '.purchase_link_list_div', function () {
        $(this).css("display", "none");
    });
    $(document).on('mouseout', '.purchase_link_list_show', function () {
       $(this).next(purchase_link_list_div).css("display", "none");
    });
});

