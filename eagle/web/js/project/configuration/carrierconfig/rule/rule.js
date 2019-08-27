$(function(){
	ruleJS.Init();
});

if (typeof ruleJS === 'undefined')  ruleJS = new Object();

ruleJS={
		openRule:function(obj){
			id = $(obj).parent().parent().attr('data');
			sname = $(obj).parent().prev().prev().prev().text();
			sid = null;
//			$.openModal('/configuration/carrierconfig/shippingrules',{id:id , sid:sid},'编辑','get');
			
			$.modal({
				  url:'/configuration/carrierconfig/shippingrules',
				  method:'get',
				  data:{id:id , sid:sid}
				},'编辑',{footer:false,inside:false}).done(function($modal){
					$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
			});
		},
		move:function($ruleIdHigh, $ruleIdLow){
			
			var Url=global.baseUrl +'configuration/carrierconfig/rule-priority';
			$.ajax({
		        type : 'post',
		        cache : 'false',
		        data : {
		        	ruleIdHigh:$ruleIdHigh,
		        	ruleIdLow:$ruleIdLow,
		        },
				url: Url,
		        success:function(response) {
		        	if(response[0] == 0){
//		        		alert(response.substring(2));
		        		//移动
		        		low = $('tr[data='+$ruleIdLow+']');
		    			high = $('tr[data='+$ruleIdHigh+']');
		    			lowData = low.attr('data');
		    			highData = high.attr('data');
		    			low.attr('data',highData);
		    			high.attr('data',lowData);
		    			low1 = low.find('td[data="1"]').html();
		    			low2 = low.find('td[data="2"]').html();
		    			low3 = low.find('td[data="3"]').html();
		    			low4 = low.find('td[data="4"]').html();
		    			high1 = high.find('td[data="1"]').html();
		    			high2 = high.find('td[data="2"]').html();
		    			high3 = high.find('td[data="3"]').html();
		    			high4 = high.find('td[data="4"]').html();
		    			low.find('td[data="1"]').html(high1);
		    			low.find('td[data="2"]').html(high2);
		    			low.find('td[data="3"]').html(high3);
		    			low.find('td[data="4"]').html(high4);
		    			high.find('td[data="1"]').html(low1);
		    			high.find('td[data="2"]').html(low2);
		    			high.find('td[data="3"]').html(low3);
		    			high.find('td[data="4"]').html(low4);
			        }
		        	else{
		        		alert(response.substring(2));
			        }
		        }
		    });
		},
		
		Init:function(){
//			$("select[name^='carrier_name']").combobox({removeIfInvalid:false});
//			$("select[name^='shipping_method_name']").combobox({removeIfInvalid:false});
//			$("select[name^='proprietary_warehouse']").combobox({removeIfInvalid:false});
			$('#searchBtn').click(function(){
				codes = $('#carrier_name').val();
				shipping_method_name = $('#shipping_method_name').val();
				proprietary_warehouse = $('#proprietary_warehouse').val();
				
				$t = false;
				var Url=global.baseUrl +
				'configuration/carrierconfig/rule?';
				if(codes != -1){
					Url += 'codes=' + codes;
					$t = true;
				}
				if(shipping_method_name != -1){
					if($t) Url += '&';
					Url += 'shipping_method_name=' + shipping_method_name;
					$t = true;
				}
				if(proprietary_warehouse != -1){
					if($t) Url += '&';
					Url += 'proprietary_warehouse=' + proprietary_warehouse ;
				}
				self.location = Url;
			});
			$('.btn-up').click(function(){
				low = $(this).parent().parent().attr('data');
				high = $(this).parent().parent().prev().attr('data');
				ruleJS.move(high,low);
			});
			$('.btn-down').click(function(){
				high = $(this).parent().parent().attr('data');
				low = $(this).parent().parent().next().attr('data');
				ruleJS.move(high,low);
			});
		},
		delRule:function(obj){
			id = $(obj).parent().parent().attr('data');
			sname = $(obj).parent().parent().find('td[data=1]').text();
			$e = $.confirmBox('<h4 class="text-danger">您确认删除此运输服务匹配规则? -- <br><br>'+sname+'</h4>');
			$e.then(function(){
				var Url=global.baseUrl +'configuration/carrierconfig/del-rule';
				$.ajax({
			        type : 'post',
			        cache : 'false',
			        data : {
			        	id:id,
			        },
					url: Url,
			        success:function(response) {
			        	var res = JSON.parse(response);
			        	if(!res.code){
			        		$e = $.alert(res.msg,'success');
			        		$e.then(function(){
			        			window.location.reload();
			        		});
			        	}else{
			        		$.alert(res.msg,'danger');
			        	}
			        }
			    });
			});
			
		},
}


var fixHelper = function(e, ui){  
	//console.log(ui);
	ui.children().each(function(){
		$(this).width($(this).width());  //在拖动时，拖动行的cell（单元格）宽度会发生改变。在这里做了处理就没问题了
	});
	return ui;
};

$(".sortable_r").sortable({
	    cursor: "move",
	    helper: fixHelper,          
	    axis:"y",
	    start:function(e, ui){
		    ui.helper.css({"background":"#fff"}) 
		    return ui;
	    },
	    
	    sort:function(e, ui){
	    	array = [];                     
	    	select_item = ui.item; //当前拖动的元素
	    	var select_id = select_item.attr("id"); 
	    	select_sort = select_item.attr("sort"); //当前元素的顺序
	    	
	    	place_item = $(this).find('tr').filter('.ui-sortable-placeholder').next('tr');//新位置下的下一个元素
	    	place_sort = place_item.attr('sort');
	    	
	    	place_sx = parseInt(place_sort);
	    	select_sx = parseInt(select_sort);
	    	
	    	//说明是 向上移动
	    	if(select_sx > place_sx){
		    	temp = place_sort;
		    	place_sx = select_sort;//最大
		    	select_sx = temp;//最小
		    	flag = false;
	    	}else{ //向下移动
	    	    place_sort = $(this).find('tr').filter('.ui-sortable-placeholder').prev('tr').attr('sort');
	    	    place_sx = parseInt(place_sort);
	    	    flag = true;
	    	}
	    },
	    
	    stop:function(e, ui){
	    	//alert(ui.item.attr("id"));//可以拿到id
	        //alert(ui.position.top);//可以拿到id
	    	
	    	//console.log(ui.item.attr("id"));
	    	
//	    	console.log(flag);
//	    	
//	    	var temp = "";
//	    	#{list items:eventTypeList, as:'n'}
	       // #{list items:eventTypeList, as:'n'}
	       // var sort = parseInt(${n.sort});
	    	
	    	var r_ids = [];
	    	var tmp_i = 1;
	    	var is_ch = false;
	    	
	    	$(".sortable_r").find("tr").each(function(){
	            //console.log($(this).attr('sort'));
	    		r_ids.push($(this).attr('id'));
	    		
	    		if($(this).attr('sort') != tmp_i){
	    			is_ch = true;
	    		}
	    		
	    		tmp_i++;
	        });
	    	
	    	if(is_ch == true){
//	    		console.log(r_ids);
				$.ajax({
			        type : 'post',
			        dataType: 'json',
			        cache : 'false',
			        data : {
			        	r_ids:r_ids,
			        },
					url: global.baseUrl +'configuration/carrierconfig/rule-move-priority',
			        success:function(response) {
			        	if(response.success){
			        		var tmp_i = 1;
				    		$(".sortable_r").find("tr").each(function(){
				    			$(this).attr('sort',tmp_i);
				    			
				    			$(this).eq(0).find("td").eq(0).text(tmp_i);
					    		
					    		tmp_i++;
					        });
			        	}else{
			        		$.alert(response.message,'danger');
			        		
			        	}
			        }
			    });
	    	}
	    },
	}
);

$(".sortable_r" ).disableSelection();

