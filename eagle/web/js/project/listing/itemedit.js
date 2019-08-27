$('#checkNegative').click(function(){
	var checked = $(this).is(':checked');
    $('input[name^=setitemvalues]').each(function(){
        if(checked){
        	$(this).prop('checked','true');
        }else{
        	$(this).removeAttr('checked');
        }
    });
});
$('.part').click(function(){
	var checked = $(this).is(':checked');
	$(this).parent().parent().next().find('input').each(function(){
		if(checked){
        	$(this).prop('checked','true');
        }else{
        	$(this).removeAttr('checked');
        }
    });
});

$('#primarycategory').bind("change",function(){
	document.a.action="";
	document.a.submit()}
);
//页面加载完自动加载店铺类目
$(function(){
	var selleruserid = $('#selleruserid').val();
	loadStoreCategory(selleruserid);
});

//加载Ebay店铺分类
function loadStoreCategory(selleruserid){
	if(typeof(selleruserid)!='undefined'){
	$.ajax({
		type: 'get',
		url:global.baseUrl+"listing/ebaystorecategory/data?selleruserid="+selleruserid,
		//data: {keys: selleruserid},
		cache: false,
		dataType:'json',
		beforeSend: function(XMLHttpRequest){
		},
		success: function(data){
			$('#storecategoryid').combotree('loadData', convertTree(data));
			$('#storecategory2id').combotree('loadData', convertTree(data));
		},
		error: function(XMLResponse){
			bootbox.alert('eBay店铺类目加载失败！');
		}
	});	}
}

function toEditStep3(){
    document.a.action=global.baseUrl+'item/item/EditStep3';
    document.a.submit();
    document.a.action="";
}