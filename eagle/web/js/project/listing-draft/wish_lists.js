
$('#moveto').click(function(){
	if($("input:checked").not("#goodIds").length==0){
		bootbox.alert('您还未选择商品');
		return false;
	}
	var handle= $.openModal('modal','选择店铺','post');  // 打开窗口命令
		handle.done(function(){
     // 窗口载入完毕事件
     	

     
     })




});
	