$(document).ready(function(){

	$('input[name="chk_wish_fanben_all"]').click(function(){
		if($(this).is(":checked")){
			// $('input[name="fanben_id"]').attr("checked","true");
			$('input[name="fanben_id"]').prop("checked","true");		
		}else{
			$('input[name="fanben_id"]').removeAttr('checked');
		}
	});
	var $keyword;
	// $('input[name="search_key"]').blur(function(){
	// 	if($(this).val()== ''){
	// 		$(this).val($keyword);
	// 	}
// });<script>
	if($(".slide-toggle i").hasClass('right')){
        setTimeout(function(){
            $('.slide-toggle').click();
        },5000);  
    }

	$('.wish_site_id').change(function(){
		$('#wish_site_search').submit();	
	});


});

function batchDel(){
	var $delList =[];
	if($('input[name="fanben_id"]:checked').length == 0){
		alert('请至少选中一件商品');
		return false;
	}
	$('input[name="fanben_id"]:checked').each(function(){
		$delList.push($(this).val());
	});
	$lb_status = $('input[name="lb_status"]').val();
	$.ajax({
		type:"post",
		url:"/listing/wish/batch-del-fan-ben?lb_status="+$lb_status,
		data:'id='+$delList,
		success:function(data){
			console.log(data);
			data = eval("("+ data +")");
			if(data['success'] == true){
				for(var i=0;i<$delList.length;i++){
					$('input[name="fanben_id"][value="'+$delList[i]+'"]').parents('tr').remove();
				}
				window.location.reload();
			}
		}
	});
}

function batchPost(){
	var $postList = [];
	var $site_id_list =[];
	if($('input[name="fanben_id"]:checked').length == 0){
		alert('请至少选中一件商品');
		return false;
	}
	$('input[name="fanben_id"]:checked').each(function(){
		$postList.push($(this).val());
		$site_id_list.push($(this).parents('tr').find('input[name="wish_product_site_id"]').val());
	});
	$lb_status = $('input[name="lb_status"]').val();
	$.ajax({
		type:"get",
		url:"/listing/wish/batch-post-fan-ben?lb_status="+$lb_status,
		data: 'id='+$postList+'&site_id='+$site_id_list,
	    success:function(data){
			data = eval("("+ data +")");
			console.log(data);
			if(data['posted_product_list'] != ''){
				var product_list = data['posted_product_list'].split(',');
				for(var i in product_list){
					if(product_list.hasOwnProperty(i)){
						$('input[name="fanben_id"][value='+product_list[i].toString()+']').parents('tr').remove();
					}
				}
			}
			if(data['success'] == false){
				tips({type:'error',msg:data['message']});
			}else{
				tips({type:'success',msg:'恭喜你，产品发布成功'});
			}		
			// for(var i=0;i<$postList.length;i++){
			// 	$('input[name="fanben_id"][value="'+$postList[i]+'"]').parents('tr').remove();
			// }
			// tips({type:"success",msg:"恭喜你，产品发布成功!",existTime:3000});
			// window.setTimeout(
			// 	"window.location.href='/listing/wish-online/online-product-list'"
			
			// ,2000);
		}
	});
}

function sendfanben(obj){
	$id = $(obj).parents('tr').find('input[name="fanben_id"]').val();
	$lb_status = $('input[name="lb_status"]').val();
	$site_id = $(obj).parents('tr').find('input[name="wish_product_site_id"]').val();
	// console.log($id);
	$.ajax({
		type:'get',
		data:'id='+ $id+'&lb_status='+$lb_status+'&site_id='+$site_id,
		url: '/listing/wish/send-fan-ben',
		success:function(data){
				// console.log(data);
			data = eval("("+ data +")");
			if(data['success'] == true){
				console.log($('input[name="fanben_id"][value="'+$id+'"]').parents('tr').html());
				$('input[name="fanben_id"][value="'+$id+'"]').parents('tr').remove();
				tips({type:"success",msg:"恭喜你！产品发布成功！",existTime:3000});
			}else{
				console.log(data);
				tips({type:"error",msg:data['message']});
			}
		}

	});
}

function delfanben(obj){
	$id = $(obj).parents('tr').find('input[name="fanben_id"]').val();
	$lb_status = $('input[name="lb_status"]').val();
	console.log($id);
	$.ajax({
		type:'post',
		data:'id='+ $id +'&lb_status='+$lb_status,
		url: '/listing/wish/del-fan-ben',
		success:function(data){
			data = eval("("+ data +")");
			if(data['success'] == true){
				tips({type:"success",msg:"产品删除成功!",existTime:3000});
				location.reload();
			}else{
				tips({type:"error",msg:data['message']});
			}
		}
	});

}

function tips(args){

	var tips = args['type'];
	var tips_content= args['msg'];
	if(args['existTime'] != undefined){
		var tips_time = args['existTime'];
	}
	// console.log(tips);
	// alert(tips_content);
	// alert(tips);
	if(tips == 'error'){
		$warning = '错误提醒:';
		$colorclass = 'alert-danger';
	}else{
		$warning = '温馨提示:';
		$colorclass = 'alert-success';
	}
	$content = ' <div class="alert '+ $colorclass +'" role="alert" style="z-index: 9999999; width: 680px; left: 30%; right: 30%; margin: auto; top: 8%; position: fixed;"><button type="button" class="close" data-dismiss="alert">×</button>';
	$tip = '<div class="pull-left mLeft10"><strong>'+ $warning+'</strong><div>';
	$tip_content = '<div class="class="pull-left mLeft10"><span>'+ tips_content +'</span></div>'
	$content += $tip + $tip_content;
	$('.right_content').append($content);
	if(args['existTime'] != undefined){
		setTimeout(function(){
			$('.alert').remove();
		},tips_time);
	}
}